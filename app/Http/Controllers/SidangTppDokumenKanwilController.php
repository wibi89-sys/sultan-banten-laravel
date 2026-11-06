<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiSetting;
use App\Models\KanwilPegawai;
use App\Models\SidangTpp;
use App\Models\TppNaskahKanwil;
use App\Models\Upt;
use App\Services\GeminiAiService;
use App\Services\LaporanTppGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SidangTppDokumenKanwilController extends Controller
{
    /**
     * Pastikan hanya akun Kantor Wilayah / Administrator yang dapat mengakses menu ini.
     */
    protected function checkKanwilAccess(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isKanwil()) {
            abort(403, 'Akses ditolak. Fitur pengelolaan naskah dan laporan sidang TPP hanya dapat diakses oleh akun Kantor Wilayah.');
        }
    }

    /**
     * Tampilkan Halaman Utama Submenu "3. Naskah & Laporan Kanwil"
     */
    public function index(Request $request): View
    {
        $this->checkKanwilAccess();

        $uptList = Upt::orderBy('nama_upt')->get();
        $setting = AiSetting::getFirst();
        $pegawaiList = KanwilPegawai::where('is_active', true)->orderBy('urutan')->get();

        $filterUpt = (int) $request->query('upt_id', 0);
        $filterStatus = $request->query('status', '');
        $search = trim((string) $request->query('search', ''));

        $query = SidangTpp::with(['upt', 'creator', 'usulan', 'naskahKanwil'])
            ->orderByDesc('tanggal_sidang');

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        if ($filterStatus !== '') {
            $query->where('status_sidang', $filterStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_undangan', 'like', "%{$search}%")
                    ->orWhere('nomor_berita_acara', 'like', "%{$search}%")
                    ->orWhere('nomor_laporan_kanwil', 'like', "%{$search}%")
                    ->orWhere('agenda_sidang', 'like', "%{$search}%")
                    ->orWhere('agenda_kategori', 'like', "%{$search}%");
            });
        }

        $sidangList = $query->paginate(15)->withQueryString();

        return view('sidang_tpp.dokumen_kanwil.index', compact(
            'sidangList',
            'uptList',
            'setting',
            'pegawaiList',
            'filterUpt',
            'filterStatus',
            'search'
        ));
    }

    /**
     * AJAX: Analisis Berita Acara PDF menggunakan AI Gemini untuk Draf Laporan TPP Kanwil
     */
    public function analyzeBaAi(int $id): JsonResponse
    {
        $this->checkKanwilAccess();

        $sidang = SidangTpp::with(['upt', 'usulan'])->findOrFail($id);

        if (empty($sidang->file_berita_acara)) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas Berita Acara (BA) belum diunggah oleh pihak UPT untuk sidang ini.',
            ], 422);
        }

        $meta = [
            'upt_nama' => $sidang->upt->nama_upt ?? 'UPT Pemasyarakatan',
            'hari_tanggal_sidang' => $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-',
            'metode_sidang' => $sidang->metode_sidang === 'daring' ? 'secara virtual melalui media Zoom Meeting' : 'secara tatap muka langsung',
            'nama_pengawas' => $sidang->nama_pengawas_kanwil ?: 'Perwakilan Kantor Wilayah',
        ];

        $result = GeminiAiService::analyzeBeritaAcaraPdf($sidang->file_berita_acara, $meta);

        return response()->json($result);
    }

    /**
     * Generate & Simpan Laporan Pelaksanaan Sidang TPP Kanwil (Word / PDF)
     */
    public function generateLaporanTpp(Request $request, int $id): Response|RedirectResponse
    {
        $this->checkKanwilAccess();

        $sidang = SidangTpp::with(['upt'])->findOrFail($id);
        $setting = AiSetting::getFirst();
        $user = auth()->user();

        $validated = $request->validate([
            'format_output' => 'required|in:word,pdf,preview',
            'nomor_dokumen' => 'nullable|string|max:150',
            'tanggal_laporan' => 'nullable|string|max:100',
            'topik_sidang' => 'nullable|string|max:255',
            'materi_items' => 'nullable|array',
            'paragraf_a1' => 'nullable|string',
            'paragraf_a2' => 'nullable|string',
            'paragraf_a3' => 'nullable|string',
            'paragraf_a4' => 'nullable|string',
            'paragraf_b1' => 'nullable|string',
            'paragraf_b2_poin' => 'nullable|array',
            'simpulan_poin' => 'nullable|array',
            'saran_poin' => 'nullable|array',
            'penutup' => 'nullable|string',
            'pengawas_nama' => 'nullable|string|max:150',
            'pengawas_jabatan' => 'nullable|string|max:150',
            'penandatangan_nama' => 'nullable|string|max:150',
            'penandatangan_nip' => 'nullable|string|max:50',
            'penandatangan_jabatan' => 'nullable|string|max:150',
            'tampilkan_nip' => 'nullable|boolean',
            'metode_ttd' => 'required|in:manual,digital_qr',
        ]);

        // Simpan / update record di tpp_naskah_kanwil
        $naskah = TppNaskahKanwil::firstOrNew([
            'sidang_tpp_id' => $sidang->id,
            'jenis_dokumen' => 'laporan_tpp',
        ]);

        if (empty($naskah->kode_verifikasi)) {
            $naskah->kode_verifikasi = TppNaskahKanwil::generateUniqueCode();
        }

        $naskah->nomor_dokumen = $validated['nomor_dokumen'] ?? $sidang->nomor_laporan_kanwil;
        $naskah->tanggal_dokumen = date('Y-m-d');
        $naskah->judul_naskah = 'Laporan Pelaksanaan Sidang TPP - '.($sidang->upt->nama_upt ?? 'Kanwil');
        $naskah->materi_agenda = $validated['topik_sidang'] ?? $sidang->agenda_kategori;
        $naskah->pengawas_nama = $validated['pengawas_nama'] ?? ($sidang->nama_pengawas_kanwil ?: 'Gunawan Wibisono');
        $naskah->pengawas_jabatan = $validated['pengawas_jabatan'] ?? 'Tim Pembinaan Narapidana dan Anak Binaan';
        $naskah->penandatangan_nama = $validated['penandatangan_nama'] ?? ($setting->penandatangan_nama ?? 'Surya Dharma');
        $naskah->penandatangan_nip = $validated['penandatangan_nip'] ?? ($setting->penandatangan_nip ?? null);
        $naskah->penandatangan_jabatan = $validated['penandatangan_jabatan'] ?? ($setting->penandatangan_jabatan ?? 'Kepala Bidang Pelayanan dan Pembinaan');
        $naskah->tampilkan_nip = $request->boolean('tampilkan_nip');
        $naskah->metode_ttd = $validated['metode_ttd'];
        $naskah->status = 'generated';
        $naskah->created_by = $user->id;
        $naskah->signed_at = now();

        $naskah->konten_data = [
            'topik_sidang' => $validated['topik_sidang'] ?? '',
            'materi_sidang_items' => array_values(array_filter((array) ($validated['materi_items'] ?? []))),
            'paragraf_a1' => $validated['paragraf_a1'] ?? '',
            'paragraf_a2' => $validated['paragraf_a2'] ?? '',
            'paragraf_a3' => $validated['paragraf_a3'] ?? '',
            'paragraf_a4' => $validated['paragraf_a4'] ?? '',
            'paragraf_b1' => $validated['paragraf_b1'] ?? '',
            'paragraf_b2_poin' => array_values(array_filter((array) ($validated['paragraf_b2_poin'] ?? []))),
            'simpulan_poin' => array_values(array_filter((array) ($validated['simpulan_poin'] ?? []))),
            'saran_poin' => array_values(array_filter((array) ($validated['saran_poin'] ?? []))),
            'penutup' => $validated['penutup'] ?? '',
        ];

        $naskah->save();

        // Update nomor laporan kanwil pada sidang_tpp jika belum terisi
        if (! empty($naskah->nomor_dokumen) && empty($sidang->nomor_laporan_kanwil)) {
            $sidang->nomor_laporan_kanwil = $naskah->nomor_dokumen;
            $sidang->save();
        }

        ActivityLog::log('export', 'sidang_tpp', "Generate Laporan Pelaksanaan Sidang TPP Kanwil ID: {$sidang->id} ({$validated['format_output']})");

        // Susun parameter untuk generator
        $params = array_merge($naskah->konten_data, [
            'upt_nama' => $sidang->upt->nama_upt ?? 'UPT Pemasyarakatan',
            'hari_tanggal_sidang' => $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-',
            'metode_sidang' => $sidang->metode_sidang === 'daring' ? 'secara virtual melalui media Zoom Meeting' : 'secara tatap muka langsung',
            'tempat_sidang' => $sidang->upt->nama_upt ?? 'UPT Pemasyarakatan',
            'nomor_dokumen' => $naskah->nomor_dokumen,
            'tanggal_laporan' => $validated['tanggal_laporan'] ?: date('d F Y'),
            'pengawas_nama' => $naskah->pengawas_nama,
            'pengawas_jabatan' => $naskah->pengawas_jabatan,
            'penandatangan_nama' => $naskah->penandatangan_nama,
            'penandatangan_nip' => $naskah->penandatangan_nip,
            'penandatangan_jabatan' => $naskah->penandatangan_jabatan,
            'tampilkan_nip' => $naskah->tampilkan_nip,
            'metode_ttd' => $naskah->metode_ttd,
            'qr_url' => $naskah->getPublicVerificationUrl(),
            'foto_dokumentasi' => is_array($sidang->file_dokumentasi) ? $sidang->file_dokumentasi : (json_decode((string) $sidang->file_dokumentasi, true) ?: []),
        ]);

        if ($validated['format_output'] === 'word') {
            return LaporanTppGeneratorService::generateWord($params);
        }

        return LaporanTppGeneratorService::generatePdf($params);
    }

    /**
     * Upload Scan Berkas Laporan Final yang telah Ditandatangani Basah / Disahkan
     */
    public function uploadSigned(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess();

        $request->validate([
            'file_signed' => 'required|file|mimes:pdf|max:20480', // max 20 MB
            'jenis_dokumen' => 'required|in:nota_dinas,laporan_tpp',
            'nomor_dokumen' => 'nullable|string|max:150',
        ]);

        $sidang = SidangTpp::findOrFail($id);
        $jenis = $request->input('jenis_dokumen');

        $uploadDir = public_path('uploads/sidang_tpp_signed');
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $file = $request->file('file_signed');
        $filename = "{$jenis}_signed_{$sidang->id}_".time().'.pdf';
        $file->move($uploadDir, $filename);
        $filePath = "uploads/sidang_tpp_signed/{$filename}";

        $naskah = TppNaskahKanwil::firstOrNew([
            'sidang_tpp_id' => $sidang->id,
            'jenis_dokumen' => $jenis,
        ]);

        if (empty($naskah->kode_verifikasi)) {
            $naskah->kode_verifikasi = TppNaskahKanwil::generateUniqueCode();
        }

        if ($request->filled('nomor_dokumen')) {
            $naskah->nomor_dokumen = $request->input('nomor_dokumen');
        }

        $naskah->file_signed = $filePath;
        $naskah->status = 'signed';
        $naskah->signed_at = now();
        $naskah->save();

        // Jika laporan TPP, sinkronkan juga dengan sidang_tpp.file_laporan_kakanwil
        if ($jenis === 'laporan_tpp') {
            $sidang->file_laporan_kakanwil = $filePath;
            if (! empty($naskah->nomor_dokumen)) {
                $sidang->nomor_laporan_kanwil = $naskah->nomor_dokumen;
            }
            $sidang->status_sidang = 'selesai_ttd_kakanwil';
            $sidang->tanggal_ttd_kakanwil = date('Y-m-d');
            $sidang->save();
        }

        ActivityLog::log('upload', 'tpp_naskah_kanwil', "Unggah berkas {$jenis} bertanda tangan untuk Sidang TPP ID: {$sidang->id}");

        return back()->with('success', 'Berkas bertanda tangan berhasil diunggah dan disimpan ke arsip naskah resmi.');
    }
}
