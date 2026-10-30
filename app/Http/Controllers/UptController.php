<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Upt;
use Illuminate\Http\Request;

class UptController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (! $user->hasAccess('upt')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('upt.index', compact('uptList'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user->canManage('upt')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Data UPT.');
        }

        $request->validate([
            'nama_upt' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:0',
        ]);

        $upt = Upt::create([
            'nama_upt' => $request->input('nama_upt'),
            'alamat' => $request->input('alamat'),
            'kapasitas' => (int) $request->input('kapasitas', 0),
            'latitude' => $request->filled('latitude') ? (float) $request->input('latitude') : null,
            'longitude' => $request->filled('longitude') ? (float) $request->input('longitude') : null,
        ]);

        ActivityLog::log('tambah', 'upt', "Menambahkan UPT baru: {$upt->nama_upt}", $upt->toArray());

        return back()->with('success', 'Data UPT berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user->canManage('upt')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Data UPT.');
        }

        $upt = Upt::findOrFail($id);

        $request->validate([
            'nama_upt' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:0',
        ]);

        $upt->update([
            'nama_upt' => $request->input('nama_upt'),
            'alamat' => $request->input('alamat'),
            'kapasitas' => (int) $request->input('kapasitas', 0),
            'latitude' => $request->filled('latitude') ? (float) $request->input('latitude') : null,
            'longitude' => $request->filled('longitude') ? (float) $request->input('longitude') : null,
        ]);

        ActivityLog::log('edit', 'upt', "Mengubah data UPT: {$upt->nama_upt}", $upt->toArray());

        return back()->with('success', 'Data UPT berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (! $user->canManage('upt')) {
            abort(403, 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Data UPT.');
        }

        $upt = Upt::findOrFail($id);
        $old = $upt->toArray();
        $upt->delete();

        ActivityLog::log('hapus', 'upt', "Menghapus UPT: {$upt->nama_upt}", $old);

        return back()->with('success', 'Data UPT berhasil dihapus.');
    }
}
