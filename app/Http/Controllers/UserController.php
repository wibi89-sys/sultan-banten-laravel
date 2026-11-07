<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    private $permissionFields = [
        'akses_dashboard',
        'akses_penghuni',
        'akses_overstaying',
        'akses_integrasi',
        'akses_bebas_harian',
        'akses_sidang_tpp',
        'akses_mutasi',
        'akses_upt',
        'akses_user',
        'akses_instansi',
        'akses_laporan',
        'akses_pengumuman',
        'akses_wa_settings',
        'akses_wa_schedule',
        'akses_activity_log',
    ];

    private function ensurePermissionColumns(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        try {
            if (DB::getDriverName() === 'sqlite') {
                $existingColumns = Schema::getColumnListing('users');
                $missing = array_diff($this->permissionFields, $existingColumns);
                if (! empty($missing)) {
                    Schema::table('users', function ($table) use ($missing) {
                        foreach ($missing as $col) {
                            $table->string($col, 10)->default('none');
                        }
                    });
                }

                return;
            }

            // Untuk MySQL / MariaDB (hosting production & lokal)
            $columns = DB::select('SHOW COLUMNS FROM users');
            $columnTypes = [];
            foreach ($columns as $c) {
                $colName = is_object($c) ? ($c->Field ?? '') : ($c['Field'] ?? '');
                $colType = is_object($c) ? ($c->Type ?? '') : ($c['Type'] ?? '');
                if ($colName) {
                    $columnTypes[$colName] = strtolower($colType);
                }
            }

            // Pastikan kolom role adalah VARCHAR(30) bukan ENUM agar mendukung operator_wilayah
            if (isset($columnTypes['role']) && str_contains($columnTypes['role'], 'enum')) {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(30) NOT NULL DEFAULT 'operator'");
            }

            foreach ($this->permissionFields as $col) {
                if (! isset($columnTypes[$col])) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `{$col}` VARCHAR(10) NOT NULL DEFAULT 'none'");
                } elseif (str_contains($columnTypes[$col], 'tinyint') || str_contains($columnTypes[$col], 'int')) {
                    DB::statement("ALTER TABLE `users` MODIFY COLUMN `{$col}` VARCHAR(10) NOT NULL DEFAULT 'none'");
                    DB::statement("UPDATE `users` SET `{$col}` = 'full' WHERE `{$col}` = '1'");
                    DB::statement("UPDATE `users` SET `{$col}` = 'none' WHERE `{$col}` = '0' OR `{$col}` IS NULL OR `{$col}` = ''");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Auto fix permission columns error: '.$e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->canView('user')) {
            abort(403);
        }

        $query = User::with('upt')->orderBy('username');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('upt_id')) {
            $uptId = $request->input('upt_id');
            if ($uptId === 'kanwil') {
                $query->whereNull('upt_id');
            } else {
                $query->where('upt_id', (int) $uptId);
            }
        }

        $users = $query->get();
        $uptList = Upt::orderBy('nama_upt')->get();

        return view('users.index', compact('users', 'uptList'));
    }

    public function create()
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->canManage('user')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Manajemen User.');
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $groupedModules = $this->getGroupedModules();

        return view('users.create', compact('uptList', 'groupedModules'));
    }

    public function edit($id)
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->canManage('user')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Manajemen User.');
        }

        $targetUser = User::with('upt')->findOrFail($id);
        $uptList = Upt::orderBy('nama_upt')->get();
        $groupedModules = $this->getGroupedModules();

        return view('users.edit', compact('targetUser', 'uptList', 'groupedModules'));
    }

    public function store(Request $request)
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->canManage('user')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Manajemen User.');
        }

        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,operator,operator_wilayah,viewer',
        ]);

        $data = [
            'username' => $request->input('username'),
            'nama_lengkap' => $request->input('nama_lengkap'),
            'password' => Hash::make($request->input('password')),
            'no_wa' => $request->input('no_wa'),
            'role' => $request->input('role'),
            'upt_id' => $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
        ];

        $userColumns = Schema::getColumnListing('users');
        foreach ($this->permissionFields as $field) {
            if (in_array($field, $userColumns, true)) {
                $val = $request->input($field, 'none');
                if ($val === 'on' || $val === '1' || $val === true) {
                    $val = 'full';
                } elseif (! in_array($val, ['none', 'view', 'full'], true)) {
                    $val = 'none';
                }

                $data[$field] = $val;
            }
        }

        // Dashboard selalu terbuka otomatis untuk semua pengguna
        $data['akses_dashboard'] = ($data['role'] === 'admin') ? 'full' : 'view';

        try {
            $newUser = User::create($data);
            ActivityLog::log('tambah', 'user', "Menambahkan user baru: {$newUser->nama_lengkap} ({$newUser->username})", $newUser->toArray());

            return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal tambah user: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Gagal menambahkan user: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->canManage('user')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Manajemen User.');
        }

        $targetUser = User::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'role' => 'required|in:admin,operator,operator_wilayah,viewer',
        ]);

        $data = [
            'nama_lengkap' => $request->input('nama_lengkap'),
            'no_wa' => $request->input('no_wa'),
            'role' => $request->input('role'),
            'upt_id' => $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $data['password'] = Hash::make($request->input('password'));
        }

        $userColumns = Schema::getColumnListing('users');
        foreach ($this->permissionFields as $field) {
            if (in_array($field, $userColumns, true)) {
                $val = $request->input($field, 'none');
                if ($val === 'on' || $val === '1' || $val === true) {
                    $val = 'full';
                } elseif (! in_array($val, ['none', 'view', 'full'], true)) {
                    $val = 'none';
                }

                $data[$field] = $val;
            }
        }

        // Dashboard selalu terbuka otomatis untuk semua pengguna
        $data['akses_dashboard'] = ($data['role'] === 'admin') ? 'full' : 'view';

        try {
            $targetUser->update($data);
            ActivityLog::log('edit', 'user', "Mengubah data user: {$targetUser->nama_lengkap}", $targetUser->toArray());

            return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal update user id '.$id.': '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Gagal menyimpan perubahan user: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (! $user->canManage('user')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Manajemen User.');
        }

        if ($id == $user->id) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $targetUser = User::findOrFail($id);

        try {
            $old = $targetUser->toArray();
            $targetUser->delete();
            ActivityLog::log('hapus', 'user', "Menghapus user: {$targetUser->username}", $old);

            return back()->with('success', 'User berhasil dihapus.');
        } catch (QueryException $e) {
            return back()->with('error', 'Tidak dapat menghapus user ini karena masih memiliki relasi data terkait di sistem.');
        }
    }

    public function getGroupedModules(): array
    {
        return [
            'Layanan & Data Pemasyarakatan' => [
                'penghuni' => [
                    'label' => 'Data Penghuni (Lapas/Rutan)',
                    'desc' => 'Laporan rekapitulasi harian jumlah tahanan dan narapidana per kamar/blok.',
                    'icon' => 'fas fa-users text-primary',
                ],
                'overstaying' => [
                    'label' => 'Data Overstaying Tahanan',
                    'desc' => 'Monitoring masa penahanan habis tahap kepolisian, kejaksaan, pengadilan.',
                    'icon' => 'fas fa-clock text-warning',
                ],
                'integrasi' => [
                    'label' => 'Data Integrasi (PB, CB, CMB, Asimilasi)',
                    'desc' => 'Pencatatan usulan dan SK terbit hak integrasi warga binaan.',
                    'icon' => 'fas fa-user-check text-success',
                ],
                'bebas_harian' => [
                    'label' => 'Pencatatan Bebas Harian',
                    'desc' => 'Rekap harian wargabinaan bebas murni, integrasi, asimilasi, bdh.',
                    'icon' => 'fas fa-door-open text-info',
                ],
                'sidang_tpp' => [
                    'label' => 'Agenda Sidang TPP',
                    'desc' => 'Jadwal, undangan, unggah usulan WBP, verifikasi Kanwil, & hasil sidang.',
                    'icon' => 'fas fa-gavel text-secondary',
                ],
                'mutasi' => [
                    'label' => 'Usulan Mutasi Narapidana Antar-UPT',
                    'desc' => 'Pengusulan pemindahan napi, telaah, penetapan kuota Kanwil, & BAST.',
                    'icon' => 'fas fa-exchange-alt text-danger',
                ],
            ],
            'Pelaporan & Audit Sistem' => [
                'laporan' => [
                    'label' => 'Cetak Laporan',
                    'desc' => 'Cetak dokumen laporan harian, bulanan, ekspor Excel & PDF.',
                    'icon' => 'fas fa-print text-primary',
                ],
                'activity_log' => [
                    'label' => 'Log Aktivitas Pengguna',
                    'desc' => 'Audit jejak rekam login, penambahan, pengubahan, dan penghapusan data.',
                    'icon' => 'fas fa-history text-muted',
                ],
            ],
            'Master Data & Pengaturan Wilayah' => [
                'upt' => [
                    'label' => 'Master Data Unit Pelaksana Teknis (UPT)',
                    'desc' => 'Kelola daftar Lapas, Rutan, Bapas, kapasitas hunian, dan titik koordinat.',
                    'icon' => 'fas fa-building text-info',
                ],
                'user' => [
                    'label' => 'Manajemen User & Hak Akses',
                    'desc' => 'Kelola akun pengguna, peran, hak akses granular 14 modul sistem.',
                    'icon' => 'fas fa-user-shield text-dark',
                ],
                'instansi' => [
                    'label' => 'Pengaturan Instansi & Tampilan',
                    'desc' => 'Identitas instansi, batas jam input harian, running text, logo/stempel.',
                    'icon' => 'fas fa-cogs text-secondary',
                ],
                'pengumuman' => [
                    'label' => 'Pengumuman & Informasi Internal',
                    'desc' => 'Kelola pengumuman running text, banner pop-up, dan edaran pimpinan.',
                    'icon' => 'fas fa-bullhorn text-warning',
                ],
            ],
            'Layanan Notifikasi WhatsApp' => [
                'wa_settings' => [
                    'label' => 'WhatsApp Gateway & Kontak Pimpinan',
                    'desc' => 'Konfigurasi token Fonnte, nomor grup WhatsApp, dan daftar kontak pimpinan.',
                    'icon' => 'fab fa-whatsapp text-success',
                ],
                'wa_schedule' => [
                    'label' => 'Jadwal & Template Pesan WhatsApp',
                    'desc' => 'Pengaturan otomatisasi pengiriman pesan pengingat dan laporan terpadu.',
                    'icon' => 'fas fa-calendar-alt text-primary',
                ],
            ],
        ];
    }
}
