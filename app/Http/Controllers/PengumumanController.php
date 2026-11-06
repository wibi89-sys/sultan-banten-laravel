<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pengumuman;
use App\Models\Upt;
use App\Models\User;
use App\Models\WaBroadcast;
use App\Models\WaSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('pengumuman')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $query = Pengumuman::with('upt')->orderByDesc('id');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'aktif') {
                $query->where('aktif', 1);
            }
            if ($status === 'nonaktif') {
                $query->where('aktif', 0);
            }
        }

        $pengumumanList = $query->paginate(20)->withQueryString();

        // Hitung total pengguna per role untuk indikator di antarmuka
        $roleCounts = [
            'operator' => User::where('role', 'operator')->count(),
            'operator_wilayah' => User::where('role', 'operator_wilayah')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'viewer' => User::where('role', 'viewer')->count(),
            'total' => User::count(),
        ];

        // Riwayat Siaran WhatsApp Massal
        $broadcastList = WaBroadcast::with('creator')->orderByDesc('id')->paginate(10, ['*'], 'broadcast_page');

        return view('pengumuman.index', compact('pengumumanList', 'uptList', 'broadcastList', 'roleCounts'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user->canManage('pengumuman')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Pengumuman.');
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'kategori' => 'required|string|max:50',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $uploadPath = public_path('uploads/pengumuman');
            if (! File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            $file = $request->file('gambar');
            $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
            if (in_array($ext, ['php', 'phtml', 'phar', 'blade', 'exe', 'sh', 'bat'])) {
                return back()->with('error', 'Format gambar tidak diizinkan.');
            }
            $filename = 'pengumuman_'.time().'_'.bin2hex(random_bytes(8)).'.'.$ext;
            $file->move($uploadPath, $filename);
            $gambar = "uploads/pengumuman/{$filename}";
        }

        $p = Pengumuman::create([
            'judul' => $request->input('judul'),
            'isi' => $request->input('isi'),
            'kategori' => $request->input('kategori'),
            'tipe' => $request->input('tipe', 'Global'),
            'upt_id' => $request->input('tipe') === 'UPT' && $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
            'gambar' => $gambar,
            'aktif' => $request->has('aktif'),
            'tampilkan_marquee' => $request->has('tampilkan_marquee'),
            'tanggal_mulai' => $request->input('tanggal_mulai') ?: null,
            'tanggal_selesai' => $request->input('tanggal_selesai') ?: null,
            'created_by' => $user->id,
        ]);

        ActivityLog::log('tambah', 'pengumuman', "Menambahkan pengumuman baru: {$p->judul}", $p->toArray());

        return back()->with('success', 'Pengumuman berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user->canManage('pengumuman')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Pengumuman.');
        }

        $p = Pengumuman::findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'kategori' => 'required|string|max:50',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $gambar = $p->gambar;
        if ($request->has('delete_gambar')) {
            if ($gambar && File::exists(public_path($gambar))) {
                File::delete(public_path($gambar));
            }
            $gambar = null;
        }

        if ($request->hasFile('gambar')) {
            $uploadPath = public_path('uploads/pengumuman');
            if (! File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            $file = $request->file('gambar');
            $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
            if (in_array($ext, ['php', 'phtml', 'phar', 'blade', 'exe', 'sh', 'bat'])) {
                return back()->with('error', 'Format gambar tidak diizinkan.');
            }
            $filename = 'pengumuman_'.time().'_'.bin2hex(random_bytes(8)).'.'.$ext;
            $file->move($uploadPath, $filename);

            if ($gambar && File::exists(public_path($gambar))) {
                File::delete(public_path($gambar));
            }
            $gambar = "uploads/pengumuman/{$filename}";
        }

        $p->update([
            'judul' => $request->input('judul'),
            'isi' => $request->input('isi'),
            'kategori' => $request->input('kategori'),
            'tipe' => $request->input('tipe', 'Global'),
            'upt_id' => $request->input('tipe') === 'UPT' && $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
            'gambar' => $gambar,
            'aktif' => $request->has('aktif'),
            'tampilkan_marquee' => $request->has('tampilkan_marquee'),
            'tanggal_mulai' => $request->input('tanggal_mulai') ?: null,
            'tanggal_selesai' => $request->input('tanggal_selesai') ?: null,
        ]);

        ActivityLog::log('edit', 'pengumuman', "Mengubah pengumuman: {$p->judul}", $p->toArray());

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (! $user->canManage('pengumuman')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Pengumuman.');
        }

        $p = Pengumuman::findOrFail($id);
        if ($p->gambar && File::exists(public_path($p->gambar))) {
            File::delete(public_path($p->gambar));
        }
        $old = $p->toArray();
        $p->delete();

        ActivityLog::log('hapus', 'pengumuman', "Menghapus pengumuman: {$p->judul}", $old);

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * Kirim siaran pengumuman WhatsApp dinamis ke operator terdaftar.
     */
    public function broadcastWa(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->canManage('pengumuman') && ! $user->canManage('wa_settings')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengirimkan siaran WhatsApp.');
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'pesan' => 'required|string',
            'kategori' => 'required|string|max:50',
            'target_type' => 'required|string|in:all,role,upt',
            'target_roles' => 'nullable|array',
            'target_upt_ids' => 'nullable|array',
            'filter_upt' => 'nullable',
            'kirim_ke_grup' => 'nullable',
            'pengumuman_id' => 'nullable|exists:pengumuman,id',
        ]);

        $targetType = $request->input('target_type', 'role');
        $targetRoles = $request->input('target_roles', []);
        $targetUptIds = $request->input('target_upt_ids', []);
        $filterUpt = $request->boolean('filter_upt', false);

        $query = User::with('upt')->whereNotNull('id');

        if ($targetType === 'all') {
            // Semua pengguna terdaftar tanpa filter
        } elseif ($targetType === 'upt') {
            if (! empty($targetUptIds)) {
                $query->whereIn('upt_id', $targetUptIds);
            }
            if (! empty($targetRoles)) {
                $query->whereIn('role', $targetRoles);
            }
        } else {
            // target_type === 'role'
            if (! empty($targetRoles)) {
                $query->whereIn('role', $targetRoles);
            }
            // Hanya filter UPT jika user secara eksplisit mengaktifkan opsi filter UPT
            if ($filterUpt && ! empty($targetUptIds)) {
                $query->whereIn('upt_id', $targetUptIds);
            }
        }

        $users = $query->orderBy('nama_lengkap')->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan operator / pengguna yang sesuai dengan kriteria filter sasaran.');
        }

        $broadcast = WaBroadcast::create([
            'pengumuman_id' => $request->input('pengumuman_id'),
            'judul' => $request->input('judul'),
            'kategori' => $request->input('kategori'),
            'pesan' => $request->input('pesan'),
            'target_type' => $targetType,
            'target_roles' => $targetRoles,
            'target_upt_ids' => $filterUpt ? $targetUptIds : null,
            'kirim_ke_grup' => $request->boolean('kirim_ke_grup'),
            'total_target' => $users->count(),
            'total_sent' => 0,
            'total_failed' => 0,
            'status' => 'processing',
            'created_by' => $user->id,
            'sent_at' => now(),
        ]);

        $result = WhatsAppService::sendBroadcastToUsers($broadcast, $users, $request->boolean('kirim_ke_grup'));

        ActivityLog::log(
            'tambah',
            'wa_broadcast',
            "Mengirimkan siaran pengumuman WhatsApp: {$broadcast->judul} ke {$result['total_sent']} dari {$result['total_target']} operator."
        );

        $msg = "Siaran pengumuman WhatsApp berhasil dikirim ke {$result['total_sent']} operator.";
        if ($result['total_failed'] > 0) {
            $msg .= " ({$result['total_failed']} nomor belum valid atau kosong di profil).";
        }

        return redirect()->route('pengumuman.index', ['tab' => 'broadcast'])->with('success', $msg);
    }

    /**
     * Hitung live counter calon penerima siaran berdasarkan filter dinamis.
     */
    public function getRecipientsCount(Request $request): JsonResponse
    {
        $targetType = $request->input('target_type', 'role');
        $targetRoles = $request->input('target_roles', []);
        $targetUptIds = $request->input('target_upt_ids', []);
        $filterUpt = $request->boolean('filter_upt', false);

        // Hitung total pengguna di database per masing-masing role
        $roleCounts = [
            'operator' => User::where('role', 'operator')->count(),
            'operator_wilayah' => User::where('role', 'operator_wilayah')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'viewer' => User::where('role', 'viewer')->count(),
            'total' => User::count(),
        ];

        $query = User::with('upt')->whereNotNull('id');

        if ($targetType === 'all') {
            // Semua pengguna tanpa filter
        } elseif ($targetType === 'upt') {
            if (! empty($targetUptIds)) {
                $query->whereIn('upt_id', $targetUptIds);
            }
            if (! empty($targetRoles)) {
                $query->whereIn('role', $targetRoles);
            }
        } else {
            // target_type === 'role'
            if (! empty($targetRoles)) {
                $query->whereIn('role', $targetRoles);
            }
            if ($filterUpt && ! empty($targetUptIds)) {
                $query->whereIn('upt_id', $targetUptIds);
            }
        }

        $users = $query->orderBy('nama_lengkap')->get();

        $validCount = 0;
        $missingCount = 0;
        $userList = [];

        foreach ($users as $u) {
            $rawWa = $u->no_wa ?? '';
            $cleanWa = preg_replace('/[^0-9]/', '', $rawWa);
            $hasWa = ! empty($cleanWa) && strlen($cleanWa) >= 9;

            if ($hasWa) {
                $validCount++;
            } else {
                $missingCount++;
            }

            $roleLabel = match ($u->role) {
                'operator' => 'Operator UPT',
                'operator_wilayah' => 'Operator Wilayah',
                'admin' => 'Administrator',
                'viewer' => 'Viewer / Pemantau',
                default => ucwords(str_replace('_', ' ', $u->role ?? 'Operator')),
            };

            $userList[] = [
                'id' => $u->id,
                'nama' => $u->nama_lengkap ?: $u->username,
                'username' => $u->username,
                'role' => $u->role,
                'role_label' => $roleLabel,
                'upt' => $u->upt?->nama_upt ?? 'Kantor Wilayah',
                'upt_id' => $u->upt_id,
                'has_wa' => $hasWa,
                'no_wa' => $rawWa ?: '-',
            ];
        }

        return response()->json([
            'success' => true,
            'total' => $users->count(),
            'valid_wa' => $validCount,
            'missing_wa' => $missingCount,
            'role_counts' => $roleCounts,
            'target_type' => $targetType,
            'target_roles' => $targetRoles,
            'filter_upt' => $filterUpt,
            'users' => $userList,
        ]);
    }

    /**
     * Ambil rincian data broadcast beserta log pengirimannya.
     */
    public function showBroadcast(int $id): JsonResponse
    {
        $broadcast = WaBroadcast::with(['creator', 'logs'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'broadcast' => $broadcast,
        ]);
    }

    /**
     * Ambil daftar operator dari suatu UPT untuk pengingat kontekstual.
     */
    public function getUptOperators(int $uptId): JsonResponse
    {
        $operators = User::where('upt_id', $uptId)
            ->whereIn('role', ['operator', 'operator_wilayah', 'admin'])
            ->get(['id', 'nama_lengkap', 'username', 'no_wa', 'role']);

        $upt = Upt::find($uptId);
        $setting = WaSetting::getFirst();

        return response()->json([
            'success' => true,
            'upt_nama' => $upt?->nama_upt ?? 'UPT Terkait',
            'template_sidang_ba' => $setting?->template_pengingat_sidang_ba ?: WaSetting::getDefaultTppBaReminderTemplate(),
            'template_mutasi' => $setting?->template_pengingat_mutasi ?: WaSetting::getDefaultMutasiReminderTemplate(),
            'operators' => $operators->map(function ($op) {
                $raw = $op->no_wa ?? '';
                $clean = preg_replace('/[^0-9]/', '', $raw);

                return [
                    'id' => $op->id,
                    'nama' => $op->nama_lengkap ?: $op->username,
                    'role' => ucwords(str_replace('_', ' ', $op->role)),
                    'no_wa' => $raw ?: '-',
                    'has_wa' => ! empty($clean) && strlen($clean) >= 9,
                ];
            }),
        ]);
    }

    /**
     * Kirim pengingat laporan pending ke nomor WhatsApp operator.
     */
    public function sendPendingReminder(Request $request): JsonResponse
    {
        $request->validate([
            'target_phone' => 'required|string',
            'message' => 'required|string',
            'recipient_name' => 'nullable|string',
            'upt_name' => 'nullable|string',
            'context_type' => 'nullable|string',
        ]);

        $res = WhatsAppService::sendPendingReminder(
            $request->input('target_phone'),
            $request->input('message'),
            $request->input('recipient_name'),
            $request->input('upt_name'),
            $request->input('context_type', 'laporan_pending')
        );

        if (! empty($res['success'])) {
            ActivityLog::log(
                'kirim_wa',
                'wa_pengingat',
                "Mengirimkan pesan pengingat laporan ke {$request->input('recipient_name')} ({$request->input('target_phone')}): {$request->input('context_type')}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Pesan pengingat WhatsApp berhasil dikirim.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $res['message'] ?? 'Gagal mengirim pesan melalui WhatsApp Gateway.',
        ], 422);
    }
}
