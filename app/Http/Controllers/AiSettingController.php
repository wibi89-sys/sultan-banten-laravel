<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiSetting;
use App\Models\KanwilPegawai;
use App\Models\SidangTpp;
use App\Models\TppNaskahKanwil;
use App\Services\GeminiAiService;
use App\Services\NotaDinasGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AiSettingController extends Controller
{
    /**
     * Pastikan hanya akun Kanwil / Admin yang dapat mengelola pengaturan AI dan personil.
     */
    protected function checkKanwilAccess(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isKanwil()) {
            abort(403, 'Akses ditolak. Fitur ini hanya dapat dikelola oleh akun Kantor Wilayah / Administrator.');
        }
    }

    /**
     * Tampilkan Pengaturan Master Data AI & Daftar Personil Pegawai Kanwil
     */
    public function index(): View
    {
        $this->checkKanwilAccess();
        $setting = AiSetting::getFirst();
        $pegawaiList = KanwilPegawai::orderBy('urutan')->orderBy('nama')->get();

        return view('ai_setting.index', compact('setting', 'pegawaiList'));
    }

    /**
     * Simpan pembaruan Pengaturan AI & Default Nota Dinas
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $this->checkKanwilAccess();

        $request->validate([
            'provider' => 'required|string|max:50',
            'api_key' => 'nullable|string',
            'model' => 'required|string|max:100',
            'nomor_surat_prefix' => 'nullable|string|max:100',
            'yth_tujuan' => 'required|string|max:200',
            'dari_pengirim' => 'required|string|max:200',
            'hal_surat' => 'nullable|string|max:255',
            'penandatangan_nama' => 'required|string|max:150',
            'penandatangan_nip' => 'nullable|string|max:50',
            'penandatangan_jabatan' => 'required|string|max:200',
        ]);

        $setting = AiSetting::getFirst();
        $setting->provider = $request->input('provider');

        // Update API Key jika tidak kosong (biarkan tetap jika dikosongkan saat submit)
        if ($request->filled('api_key')) {
            $setting->api_key = trim((string) $request->input('api_key'));
        }

        $setting->model = $request->input('model');
        $setting->is_active = $request->boolean('is_active');
        $setting->nomor_surat_prefix = $request->input('nomor_surat_prefix', 'WP.12-UM.01.01-');
        $setting->yth_tujuan = $request->input('yth_tujuan');
        $setting->dari_pengirim = $request->input('dari_pengirim');
        $setting->hal_surat = $request->input('hal_surat') ?: 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP';
        $setting->penandatangan_nama = $request->input('penandatangan_nama');
        $setting->penandatangan_nip = $request->input('penandatangan_nip');
        $setting->penandatangan_jabatan = $request->input('penandatangan_jabatan');
        $setting->tampilkan_nip = $request->boolean('tampilkan_nip');
        $setting->save();

        ActivityLog::log(
            'edit',
            'ai_settings',
            'Memperbarui konfigurasi AI Gemini dan default tata naskah Nota Dinas Kanwil'
        );

        return back()->with('success', 'Pengaturan AI & Default Nota Dinas berhasil disimpan.');
    }

    /**
     * AJAX Test Koneksi ke Google Gemini API
     */
    public function testConnection(Request $request): JsonResponse
    {
        $this->checkKanwilAccess();

        $apiKey = $request->input('api_key');
        $model = $request->input('model');

        $result = GeminiAiService::testConnection($apiKey, $model);

        return response()->json($result);
    }

    /**
     * AJAX Ambil daftar model yang tersedia dari Google AI Studio
     */
    public function fetchModels(Request $request): JsonResponse
    {
        $this->checkKanwilAccess();

        $apiKey = $request->input('api_key');
        $result = GeminiAiService::getAvailableModels($apiKey);

        return response()->json($result);
    }

    /**
     * Tambah Master Personil Pegawai Kanwil
     */
    public function pegawaiStore(Request $request): RedirectResponse
    {
        $this->checkKanwilAccess();

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
            'jabatan' => 'required|string|max:150',
            'pangkat_golongan' => 'nullable|string|max:100',
            'urutan' => 'nullable|integer',
        ]);

        KanwilPegawai::create([
            'nama' => $validated['nama'],
            'nip' => $validated['nip'] ?? null,
            'jabatan' => $validated['jabatan'],
            'pangkat_golongan' => $validated['pangkat_golongan'] ?? null,
            'urutan' => $validated['urutan'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Data personil pegawai Kanwil berhasil ditambahkan.');
    }

    /**
     * Update Master Personil Pegawai Kanwil
     */
    public function pegawaiUpdate(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess();

        $pegawai = KanwilPegawai::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
            'jabatan' => 'required|string|max:150',
            'pangkat_golongan' => 'nullable|string|max:100',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $pegawai->update([
            'nama' => $validated['nama'],
            'nip' => $validated['nip'] ?? null,
            'jabatan' => $validated['jabatan'],
            'pangkat_golongan' => $validated['pangkat_golongan'] ?? null,
            'urutan' => $validated['urutan'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Data personil pegawai Kanwil berhasil diperbarui.');
    }

    /**
     * Hapus Master Personil Pegawai Kanwil
     */
    public function pegawaiDestroy(int $id): RedirectResponse
    {
        $this->checkKanwilAccess();

        $pegawai = KanwilPegawai::findOrFail($id);
        $pegawai->delete();

        return back()->with('success', 'Data personil pegawai Kanwil berhasil dihapus.');
    }

    /**
     * AJAX Parse / Ekstraksi PDF Surat Undangan Sidang TPP Menggunakan AI
     */
    public function parseUndanganAi(int $sidangId): JsonResponse
    {
        $this->checkKanwilAccess();

        $sidang = SidangTpp::with('upt')->findOrFail($sidangId);

        if (empty($sidang->file_undangan)) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas PDF surat undangan dari UPT belum diunggah. Silakan isi form rujukan secara manual.',
            ], 404);
        }

        $result = GeminiAiService::extractUndanganPdf($sidang->file_undangan);

        // Jika AI mengembalikan data, lengkapi dengan data UPT dari database jika AI kosong
        if ($result['success'] && isset($result['data'])) {
            if (empty($result['data']['asal_upt'])) {
                $result['data']['asal_upt'] = $sidang->upt?->nama_upt ?? '';
            }
            if (empty($result['data']['nomor_undangan'])) {
                $result['data']['nomor_undangan'] = $sidang->nomor_undangan;
            }
            if (empty($result['data']['tanggal_surat']) && $sidang->tanggal_surat) {
                $result['data']['tanggal_surat'] = $sidang->tanggal_surat->translatedFormat('d F Y');
            }
            if (empty($result['data']['hari_tanggal_sidang']) && $sidang->tanggal_sidang) {
                $result['data']['hari_tanggal_sidang'] = $sidang->tanggal_sidang->translatedFormat('l, d F Y');
            }
            if (empty($result['data']['jam_mulai']) && $sidang->jam_mulai) {
                $result['data']['jam_mulai'] = substr((string) $sidang->jam_mulai, 0, 5).' WIB s.d selesai';
            }
            if (empty($result['data']['tempat'])) {
                $result['data']['tempat'] = ! empty($sidang->link_daring)
                    ? 'Virtual / Zoom Cloud Meeting ('.$sidang->metode_label.')'
                    : 'Ruang Sidang TPP '.$sidang->upt?->nama_upt;
            }
        }

        return response()->json($result);
    }

    /**
     * Generate & Download Dokumen Nota Dinas Permohonan Surat Tugas (Word / Print)
     */
    public function generateNotaDinas(Request $request, int $sidangId): Response|View
    {
        $this->checkKanwilAccess();

        $sidang = SidangTpp::with('upt')->findOrFail($sidangId);

        $request->validate([
            'nomor_nota_dinas' => 'required|string|max:150',
            'tanggal_nota_dinas' => 'required|string|max:100',
            'yth_tujuan' => 'required|string|max:200',
            'dari_pengirim' => 'required|string|max:200',
            'hal_surat' => 'required|string|max:255',
            'nomor_undangan' => 'required|string|max:150',
            'tanggal_surat_upt' => 'required|string|max:100',
            'hal_undangan' => 'required|string|max:150',
            'upt_nama' => 'required|string|max:150',
            'hari_tanggal_pelaksanaan' => 'required|string|max:150',
            'jam_pelaksanaan' => 'required|string|max:100',
            'tempat_pelaksanaan' => 'required|string|max:255',
            'penandatangan_nama' => 'required|string|max:150',
            'penandatangan_jabatan' => 'required|string|max:200',
            'penandatangan_nip' => 'nullable|string|max:50',
            'tampilkan_nip' => 'nullable|boolean',
            'pegawai_ids' => 'nullable|array',
            'pegawai_ids.*' => 'integer',
        ]);

        // Kumpulkan personil terpilih dari master data
        $pegawaiIds = $request->input('pegawai_ids', []);
        $pegawaiList = [];

        if (! empty($pegawaiIds)) {
            $selectedPegawai = KanwilPegawai::whereIn('id', $pegawaiIds)
                ->orderBy('urutan')
                ->get();

            foreach ($selectedPegawai as $p) {
                $pegawaiList[] = [
                    'nama' => $p->nama,
                    'nip' => $p->nip ?: '-',
                    'jabatan' => $p->jabatan,
                ];
            }
        }

        // Tambahan pegawai manual jika ada yang diinput langsung
        $manualNama = $request->input('manual_nama', []);
        $manualNip = $request->input('manual_nip', []);
        $manualJabatan = $request->input('manual_jabatan', []);

        if (is_array($manualNama)) {
            foreach ($manualNama as $i => $nama) {
                $trimmed = trim((string) $nama);
                if (! empty($trimmed)) {
                    $pegawaiList[] = [
                        'nama' => $trimmed,
                        'nip' => ! empty($manualNip[$i]) ? trim((string) $manualNip[$i]) : '-',
                        'jabatan' => ! empty($manualJabatan[$i]) ? trim((string) $manualJabatan[$i]) : '-',
                    ];
                }
            }
        }

        $params = [
            'nomor_nota_dinas' => $request->input('nomor_nota_dinas'),
            'tanggal_nota_dinas' => $request->input('tanggal_nota_dinas'),
            'yth_tujuan' => $request->input('yth_tujuan'),
            'dari_pengirim' => $request->input('dari_pengirim'),
            'hal_surat' => $request->input('hal_surat'),
            'nomor_undangan' => $request->input('nomor_undangan'),
            'tanggal_surat_upt' => $request->input('tanggal_surat_upt'),
            'hal_undangan' => $request->input('hal_undangan'),
            'upt_nama' => $request->input('upt_nama'),
            'hari_tanggal_pelaksanaan' => $request->input('hari_tanggal_pelaksanaan'),
            'jam_pelaksanaan' => $request->input('jam_pelaksanaan'),
            'tempat_pelaksanaan' => $request->input('tempat_pelaksanaan'),
            'penandatangan_nama' => $request->input('penandatangan_nama'),
            'penandatangan_jabatan' => $request->input('penandatangan_jabatan'),
            'penandatangan_nip' => $request->input('penandatangan_nip'),
            'tampilkan_nip' => $request->boolean('tampilkan_nip'),
            'pegawai_list' => $pegawaiList,
        ];

        ActivityLog::log(
            'export',
            'sidang_tpp',
            "Kanwil men-generate Nota Dinas Permohonan Surat Tugas Sidang TPP {$sidang->upt?->nama_upt} No. {$params['nomor_nota_dinas']}",
            ['sidang_id' => $sidang->id]
        );

        // Rekam status naskah Nota Dinas di tpp_naskah_kanwil
        $naskah = TppNaskahKanwil::firstOrNew([
            'sidang_tpp_id' => $sidang->id,
            'jenis_dokumen' => 'nota_dinas',
        ]);
        if (empty($naskah->kode_verifikasi)) {
            $naskah->kode_verifikasi = TppNaskahKanwil::generateUniqueCode();
        }
        $naskah->nomor_dokumen = $params['nomor_nota_dinas'];
        $naskah->tanggal_dokumen = date('Y-m-d');
        $naskah->judul_naskah = 'Nota Dinas Permohonan Surat Tugas - '.($sidang->upt->nama_upt ?? 'Kanwil');
        $naskah->materi_agenda = $params['hal_surat'];
        $naskah->penandatangan_nama = $params['penandatangan_nama'];
        $naskah->penandatangan_jabatan = $params['penandatangan_jabatan'];
        $naskah->penandatangan_nip = $params['penandatangan_nip'];
        $naskah->tampilkan_nip = $params['tampilkan_nip'];
        $naskah->konten_data = $params;
        $naskah->status = 'generated';
        $naskah->created_by = auth()->id();
        $naskah->signed_at = now();
        $naskah->save();

        if ($request->input('preview') == '1') {
            return response(NotaDinasGeneratorService::renderHtmlTemplate($params));
        }

        return NotaDinasGeneratorService::generateWord($params);
    }
}
