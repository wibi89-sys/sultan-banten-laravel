<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
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
            $existingColumns = Schema::getColumnListing('users');
            $missing = array_diff($this->permissionFields, $existingColumns);

            if (! empty($missing)) {
                Schema::table('users', function ($table) use ($missing) {
                    foreach ($missing as $col) {
                        $table->boolean($col)->default(false);
                    }
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Auto add permission columns error: '.$e->getMessage());
        }
    }

    public function index()
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->hasAccess('user')) {
            abort(403);
        }

        $users = User::with('upt')->orderBy('username')->get();
        $uptList = Upt::orderBy('nama_upt')->get();

        return view('users.index', compact('users', 'uptList'));
    }

    public function store(Request $request)
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->hasAccess('user')) {
            abort(403);
        }

        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,operator,viewer',
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
                $data[$field] = $request->has($field);
            }
        }

        $newUser = User::create($data);
        ActivityLog::log('tambah', 'user', "Menambahkan user baru: {$newUser->nama_lengkap} ({$newUser->username})", $newUser->toArray());

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $this->ensurePermissionColumns();

        $user = auth()->user();
        if (! $user->hasAccess('user')) {
            abort(403);
        }

        $targetUser = User::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'role' => 'required|in:admin,operator,viewer',
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
                $data[$field] = $request->has($field);
            }
        }

        $targetUser->update($data);
        ActivityLog::log('edit', 'user', "Mengubah data user: {$targetUser->nama_lengkap}", $targetUser->toArray());

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('user')) {
            abort(403);
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
}
