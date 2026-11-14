<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KanwilPegawai;
use App\Models\TppKanwilSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class TppKanwilSettingController extends Controller
{
    /**
     * Pastikan pengguna memiliki izin akses Kanwil.
     */
    protected function checkAccess(string $requiredLevel = 'view'): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isKanwil()) {
            abort(403, 'Akses ditolak. Fitur Pengaturan Sidang TPP Kanwil hanya untuk personil Kantor Wilayah.');
        }

        if (! $user->hasAccess('sidang_tpp_kanwil', $requiredLevel)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola Pengaturan Sidang TPP Kanwil.');
        }
    }

    /**
     * Tampilkan Halaman Pengaturan Sidang TPP Kanwil.
     */
    public function index(): View
    {
        $this->checkAccess();

        $setting = TppKanwilSetting::getFirst();
        $pegawaiList = KanwilPegawai::orderBy('urutan')->orderBy('nama')->get();

        return view('sidang_tpp_kanwil.setting', compact('setting', 'pegawaiList'));
    }

    /**
     * Simpan Pengaturan Umum, Profil Kakanwil, TTD & Stempel.
     */
    public function updateSetting(Request $request): RedirectResponse
    {
        $this->checkAccess('full');

        $section = $request->input('setting_section');
        $setting = TppKanwilSetting::getFirst();

        // 1. Jika Menyimpan Tab 2: Profil & Spesimen Kakanwil
        if ($section === 'kakanwil' || ($request->has('kakanwil_nama') && ! $request->has('nomor_nota_prefix'))) {
            $validated = $request->validate([
                'kakanwil_nama' => 'nullable|string|max:150',
                'kakanwil_nip' => 'nullable|string|max:50',
                'kakanwil_pangkat' => 'nullable|string|max:100',
                'kakanwil_jabatan' => 'nullable|string|max:150',
                'auto_embed_ttd' => 'nullable|boolean',
                'kakanwil_ttd_image' => 'nullable|image|mimes:png,webp,jpg,jpeg|max:2048',
                'kanwil_stempel_image' => 'nullable|image|mimes:png,webp,jpg,jpeg|max:2048',
            ]);

            $uploadDir = public_path('uploads/sidang_tpp_kanwil');
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true, true);
            }

            // Upload TTD Digital Kakanwil
            if ($request->hasFile('kakanwil_ttd_image')) {
                $file = $request->file('kakanwil_ttd_image');
                $filename = 'ttd_kakanwil_'.time().'.'.$file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $validated['kakanwil_ttd_image'] = 'uploads/sidang_tpp_kanwil/'.$filename;
            }

            // Upload Stempel Resmi Kanwil
            if ($request->hasFile('kanwil_stempel_image')) {
                $file = $request->file('kanwil_stempel_image');
                $filename = 'stempel_kanwil_'.time().'.'.$file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $validated['kanwil_stempel_image'] = 'uploads/sidang_tpp_kanwil/'.$filename;
            }

            $validated['kakanwil_nama'] = ! empty($validated['kakanwil_nama']) ? $validated['kakanwil_nama'] : ($setting->kakanwil_nama ?: 'Dody Fahrudin, Bc.IP., S.H., M.H.');
            $validated['kakanwil_jabatan'] = ! empty($validated['kakanwil_jabatan']) ? $validated['kakanwil_jabatan'] : ($setting->kakanwil_jabatan ?: 'Kepala Kantor Wilayah');
            $validated['auto_embed_ttd'] = $request->has('auto_embed_ttd');

            $setting->update($validated);

            ActivityLog::log('edit', 'sidang_tpp_kanwil', 'Memperbarui Profil & Spesimen TTD Kakanwil');

            return redirect()->to(route('sidang-tpp-kanwil.setting').'#tab-kakanwil')
                ->with('success', 'Profil Kepala Kantor Wilayah dan spesimen TTD/Stempel berhasil disimpan.');
        }

        // 2. Jika Menyimpan Tab 3: Format Naskah & Lokasi Standar
        if ($section === 'naskah' || $request->has('nomor_nota_prefix') || $request->has('dasar_hukum')) {
            $validated = $request->validate([
                'nomor_nota_prefix' => 'nullable|string|max:50',
                'nomor_risalah_prefix' => 'nullable|string|max:50',
                'nomor_laporan_prefix' => 'nullable|string|max:50',
                'tempat_sidang_default' => 'nullable|string|max:255',
                'link_daring_default' => 'nullable|url|max:255',
                'meeting_id_default' => 'nullable|string|max:100',
                'passcode_default' => 'nullable|string|max:50',
                'dasar_hukum' => 'nullable|array',
                'dasar_hukum.*' => 'nullable|string|max:500',
            ]);

            if (isset($validated['dasar_hukum'])) {
                $filteredDasar = array_values(array_filter($validated['dasar_hukum'], fn ($val) => ! empty(trim((string) $val))));
                $validated['dasar_hukum_json'] = $filteredDasar;
                unset($validated['dasar_hukum']);
            }

            $setting->update($validated);

            ActivityLog::log('edit', 'sidang_tpp_kanwil', 'Memperbarui Format Penomoran Naskah & Dasar Hukum Sidang TPP Kanwil');

            return redirect()->to(route('sidang-tpp-kanwil.setting').'#tab-naskah')
                ->with('success', 'Format penomoran naskah dinas, lokasi standar, dan dasar hukum berhasil disimpan.');
        }

        // 3. Fallback jika dikirim bersamaan
        $validated = $request->validate([
            'kakanwil_nama' => 'nullable|string|max:150',
            'kakanwil_nip' => 'nullable|string|max:50',
            'kakanwil_pangkat' => 'nullable|string|max:100',
            'kakanwil_jabatan' => 'nullable|string|max:150',
            'auto_embed_ttd' => 'nullable|boolean',
            'nomor_nota_prefix' => 'nullable|string|max:50',
            'nomor_risalah_prefix' => 'nullable|string|max:50',
            'nomor_laporan_prefix' => 'nullable|string|max:50',
            'tempat_sidang_default' => 'nullable|string|max:255',
            'link_daring_default' => 'nullable|url|max:255',
            'meeting_id_default' => 'nullable|string|max:100',
            'passcode_default' => 'nullable|string|max:50',
            'dasar_hukum' => 'nullable|array',
            'dasar_hukum.*' => 'nullable|string|max:500',
            'kakanwil_ttd_image' => 'nullable|image|mimes:png,webp,jpg,jpeg|max:2048',
            'kanwil_stempel_image' => 'nullable|image|mimes:png,webp,jpg,jpeg|max:2048',
        ]);

        $uploadDir = public_path('uploads/sidang_tpp_kanwil');
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true, true);
        }

        if ($request->hasFile('kakanwil_ttd_image')) {
            $file = $request->file('kakanwil_ttd_image');
            $filename = 'ttd_kakanwil_'.time().'.'.$file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $validated['kakanwil_ttd_image'] = 'uploads/sidang_tpp_kanwil/'.$filename;
        }

        if ($request->hasFile('kanwil_stempel_image')) {
            $file = $request->file('kanwil_stempel_image');
            $filename = 'stempel_kanwil_'.time().'.'.$file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $validated['kanwil_stempel_image'] = 'uploads/sidang_tpp_kanwil/'.$filename;
        }

        if ($request->has('kakanwil_nama')) {
            $validated['kakanwil_nama'] = ! empty($validated['kakanwil_nama']) ? $validated['kakanwil_nama'] : ($setting->kakanwil_nama ?: 'Dody Fahrudin, Bc.IP., S.H., M.H.');
            $validated['kakanwil_jabatan'] = ! empty($validated['kakanwil_jabatan']) ? $validated['kakanwil_jabatan'] : ($setting->kakanwil_jabatan ?: 'Kepala Kantor Wilayah');
            $validated['auto_embed_ttd'] = $request->has('auto_embed_ttd');
        }

        if (isset($validated['dasar_hukum'])) {
            $filteredDasar = array_values(array_filter($validated['dasar_hukum'], fn ($val) => ! empty(trim((string) $val))));
            $validated['dasar_hukum_json'] = $filteredDasar;
            unset($validated['dasar_hukum']);
        }

        $setting->update(array_filter($validated, fn ($v) => $v !== null));

        ActivityLog::log('edit', 'sidang_tpp_kanwil', 'Memperbarui Pengaturan Sidang TPP Kanwil');

        return redirect()->route('sidang-tpp-kanwil.setting')
            ->with('success', 'Pengaturan Sidang TPP Kanwil berhasil disimpan.');
    }

    /**
     * Tambah Personil Tim Sidang TPP Kanwil.
     */
    public function storePegawai(Request $request): RedirectResponse
    {
        $this->checkAccess('full');

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
            'pangkat_golongan' => 'nullable|string|max:100',
            'jabatan' => 'required|string|max:150',
            'no_wa' => 'nullable|string|max:30',
            'peran_tpp' => 'required|in:ketua,sekretaris,anggota',
            'urutan' => 'nullable|integer',
        ]);

        $validated['urutan'] = $validated['urutan'] ?? ((KanwilPegawai::max('urutan') ?? 0) + 1);
        $validated['is_active'] = true;

        $pegawai = KanwilPegawai::create($validated);

        ActivityLog::log('tambah', 'sidang_tpp_kanwil', "Menambahkan personil Tim TPP Kanwil: {$pegawai->nama} ({$pegawai->peran_tpp})");

        return redirect()->route('sidang-tpp-kanwil.setting')
            ->with('success', "Personil Tim Sidang {$pegawai->nama} berhasil ditambahkan sebagai {$pegawai->peran_tpp}.");
    }

    /**
     * Update Personil Tim Sidang TPP Kanwil.
     */
    public function updatePegawai(Request $request, $id): RedirectResponse
    {
        $this->checkAccess('full');

        $pegawai = KanwilPegawai::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
            'pangkat_golongan' => 'nullable|string|max:100',
            'jabatan' => 'required|string|max:150',
            'no_wa' => 'nullable|string|max:30',
            'peran_tpp' => 'required|in:ketua,sekretaris,anggota',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $pegawai->update($validated);

        ActivityLog::log('edit', 'sidang_tpp_kanwil', "Memperbarui data personil Tim TPP: {$pegawai->nama}");

        return redirect()->route('sidang-tpp-kanwil.setting')
            ->with('success', "Data personil {$pegawai->nama} berhasil diperbarui.");
    }

    /**
     * Hapus Personil Tim Sidang TPP Kanwil.
     */
    public function destroyPegawai($id): RedirectResponse
    {
        $this->checkAccess('full');

        $pegawai = KanwilPegawai::findOrFail($id);
        $nama = $pegawai->nama;
        $pegawai->delete();

        ActivityLog::log('hapus', 'sidang_tpp_kanwil', "Menghapus personil Tim TPP Kanwil: {$nama}");

        return redirect()->route('sidang-tpp-kanwil.setting')
            ->with('success', "Personil {$nama} berhasil dihapus dari master Tim Sidang Kanwil.");
    }
}
