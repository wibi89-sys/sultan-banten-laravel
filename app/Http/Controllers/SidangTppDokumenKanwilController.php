<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiSetting;
use App\Models\KanwilPegawai;
use App\Models\SidangTpp;
use App\Models\TppNaskahKanwil;
use App\Models\Upt;
use App\Services\GeminiAiService;
use App\Services\ImageCompressionService;
use App\Services\LaporanTppGeneratorService;
use App\Services\PdfStamperService;
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
    public function analyzeBaAi(Request $request, int $id): JsonResponse
    {
        $this->checkKanwilAccess();

        $sidang = SidangTpp::with(['upt', 'usulan'])->find($id);

        if (! $sidang) {
            return response()->json([
                'success' => false,
                'message' => "Data Sidang TPP dengan ID #{$id} tidak ditemukan di database.",
            ], 404);
        }

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
            'instruksi_khusus' => $request->input('instruksi_khusus', ''),
            'gaya_penulisan' => $request->input('gaya_penulisan', 'standar'),
        ];

        $result = GeminiAiService::analyzeBeritaAcaraPdf($sidang->file_berita_acara, $meta);

        return response()->json($result);
    }

    /**
     * AJAX: Susun Narasi Nota Dinas Permohonan Surat Tugas cerdas via AI Gemini
     */
    public function draftNotaDinasAi(Request $request, int $id): JsonResponse
    {
        $this->checkKanwilAccess();

        $sidang = SidangTpp::with(['upt'])->find($id);

        if (! $sidang) {
            return response()->json([
                'success' => false,
                'message' => "Data Sidang TPP dengan ID #{$id} tidak ditemukan di database.",
            ], 404);
        }

        $uptNama = $request->input('upt_nama') ?: ($sidang->upt->nama_upt ?? 'UPT Pemasyarakatan');
        $tempatDefault = ! empty($sidang->link_daring)
            ? 'Virtual / Zoom Cloud Meeting'
            : ($sidang->tempat_sidang ?: 'Ruang Sidang TPP '.$uptNama);
        $tempat = $request->input('tempat_pelaksanaan') ?: $tempatDefault;

        $pukulDefault = $sidang->jam_mulai
            ? substr((string) $sidang->jam_mulai, 0, 5).' WIB s.d selesai'
            : '09.00 WIB s.d selesai';

        $params = [
            'upt_nama' => $uptNama,
            'nomor_undangan' => $request->input('nomor_undangan') ?: ($sidang->nomor_undangan ?? '-'),
            'tanggal_surat_upt' => $request->input('tanggal_surat_upt') ?: ($sidang->tanggal_surat ? $sidang->tanggal_surat->translatedFormat('d F Y') : '-'),
            'hal_undangan' => $request->input('hal_undangan') ?: 'Undangan Sidang TPP',
            'hari_tanggal' => $request->input('hari_tanggal_pelaksanaan') ?: ($sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-'),
            'pukul' => $request->input('jam_pelaksanaan') ?: $pukulDefault,
            'tempat' => $tempat,
            'instruksi_khusus' => $request->input('instruksi_khusus', ''),
            'gaya_penulisan' => $request->input('gaya_penulisan', 'standar'),
        ];

        $result = GeminiAiService::draftNotaDinasNarasi($params);

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
            'foto_dokumentasi_1' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_dokumentasi_2' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'existing_foto_1' => 'nullable|string',
            'existing_foto_2' => 'nullable|string',
        ]);

        // Proses Foto Dokumentasi (2 Foto)
        $fotoDokumentasi = [];

        // Foto 1
        if ($request->hasFile('foto_dokumentasi_1')) {
            $fotoDokumentasi[] = ImageCompressionService::compressToJpg($request->file('foto_dokumentasi_1'), 'uploads/sidang_tpp', 512000);
        } elseif ($request->filled('existing_foto_1')) {
            $fotoDokumentasi[] = $request->input('existing_foto_1');
        }

        // Foto 2
        if ($request->hasFile('foto_dokumentasi_2')) {
            $fotoDokumentasi[] = ImageCompressionService::compressToJpg($request->file('foto_dokumentasi_2'), 'uploads/sidang_tpp', 512000);
        } elseif ($request->filled('existing_foto_2')) {
            $fotoDokumentasi[] = $request->input('existing_foto_2');
        }

        // Fallback jika belum ada foto yang diunggah/dipilih, ambil dari foto UPT jika tersedia
        if (empty($fotoDokumentasi) && ! empty($sidang->file_dokumentasi)) {
            $uptPhotos = is_array($sidang->file_dokumentasi) ? $sidang->file_dokumentasi : (json_decode((string) $sidang->file_dokumentasi, true) ?: []);
            if (! empty($uptPhotos)) {
                $fotoDokumentasi = array_values(array_slice($uptPhotos, 0, 2));
            }
        }

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
            'foto_dokumentasi' => $fotoDokumentasi,
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
            'kode_verifikasi' => $naskah->kode_verifikasi,
            'qr_url' => $naskah->getPublicVerificationUrl(),
            'foto_dokumentasi' => $fotoDokumentasi,
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
            'jenis_dokumen' => 'required|in:nota_dinas,laporan_tpp,surat_tugas',
            'nomor_dokumen' => 'nullable|string|max:150',
            'bubuhi_qr' => 'nullable',
            'qr_percent_x' => 'nullable|numeric|min:0|max:100',
            'qr_percent_y' => 'nullable|numeric|min:0|max:100',
            'qr_target_page' => 'nullable|string',
            'qr_style' => 'nullable|string|in:badge,qr_only',
        ]);

        $sidang = SidangTpp::findOrFail($id);
        $jenis = $request->input('jenis_dokumen');

        $uploadDir = public_path('uploads/sidang_tpp_signed');
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $file = $request->file('file_signed');
        $rawFilename = "raw_{$jenis}_signed_{$sidang->id}_".time().'.pdf';
        $finalFilename = "{$jenis}_signed_{$sidang->id}_".time().'.pdf';
        $rawPath = "{$uploadDir}/{$rawFilename}";
        $finalPath = "{$uploadDir}/{$finalFilename}";

        $file->move($uploadDir, $rawFilename);

        $naskah = TppNaskahKanwil::firstOrNew([
            'sidang_tpp_id' => $sidang->id,
            'jenis_dokumen' => $jenis,
        ]);

        if ($request->filled('nomor_dokumen')) {
            $naskah->nomor_dokumen = $request->input('nomor_dokumen');
        }

        $bubuhiQr = $request->boolean('bubuhi_qr', false);

        if ($bubuhiQr) {
            if (empty($naskah->kode_verifikasi)) {
                $naskah->kode_verifikasi = TppNaskahKanwil::generateUniqueCode();
            }

            $qrUrl = $naskah->getPublicVerificationUrl();
            $rawX = $request->input('qr_percent_x');
            $rawY = $request->input('qr_percent_y');
            $percentX = is_numeric($rawX) ? (float) $rawX : 6.0;
            $percentY = is_numeric($rawY) ? (float) $rawY : 86.0;

            if (! is_finite($percentX) || $percentX < 0 || $percentX > 100) {
                $percentX = 6.0;
            }
            if (! is_finite($percentY) || $percentY < 0 || $percentY > 100) {
                $percentY = 86.0;
            }

            $targetPage = $request->input('qr_target_page', 'last');
            $withBadge = ($request->input('qr_style', 'badge') === 'badge');

            $stamped = PdfStamperService::stampQr(
                $rawPath,
                $finalPath,
                $qrUrl,
                $naskah->kode_verifikasi,
                [
                    'percent_x' => $percentX,
                    'percent_y' => $percentY,
                    'target_page' => $targetPage,
                    'with_badge' => $withBadge,
                ]
            );

            if ($stamped) {
                @unlink($rawPath);
                $filePath = "uploads/sidang_tpp_signed/{$finalFilename}";
            } else {
                rename($rawPath, $finalPath);
                $filePath = "uploads/sidang_tpp_signed/{$finalFilename}";
            }
        } else {
            rename($rawPath, $finalPath);
            $filePath = "uploads/sidang_tpp_signed/{$finalFilename}";
            $naskah->kode_verifikasi = null;
        }

        $naskah->file_signed = $filePath;
        $naskah->status = 'signed';
        $naskah->metode_ttd = $bubuhiQr ? 'digital_qr' : 'manual';
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
        } elseif ($jenis === 'surat_tugas') {
            $sidang->file_surat_tugas = $filePath;
            if (! empty($naskah->nomor_dokumen)) {
                $sidang->nomor_surat_tugas = $naskah->nomor_dokumen;
            }
            if (empty($sidang->tanggal_surat_tugas)) {
                $sidang->tanggal_surat_tugas = date('Y-m-d');
            }
            $sidang->save();
        }

        $jenisLabel = match ($jenis) {
            'surat_tugas' => 'Surat Tugas Tim Kanwil',
            'laporan_tpp' => 'Laporan Sidang TPP Kanwil',
            default => 'Nota Dinas Permohonan Surat Tugas',
        };

        ActivityLog::log('upload', 'tpp_naskah_kanwil', "Unggah berkas {$jenisLabel} bertanda tangan untuk Sidang TPP ID: {$sidang->id}");

        $msg = "Berkas {$jenisLabel} berhasil diunggah".($bubuhiQr ? ' dan dibubuhi cap QR Code otentifikasi resmi.' : '.');

        return back()->with('success', $msg);
    }
}
