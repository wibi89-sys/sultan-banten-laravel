<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SidangTpp;
use App\Models\SidangTppUsulan;
use App\Models\Upt;
use App\Services\ImageCompressionService;
use App\Services\TppExcelService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SidangTppController extends Controller
{
    /**
     * Pastikan user memiliki hak akses menu Sidang TPP.
     */
    protected function checkAccess(string $requiredLevel = 'view'): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAccess('sidang_tpp', $requiredLevel)) {
            $msg = $requiredLevel === 'full'
                ? 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Sidang TPP.'
                : 'Akses ditolak. Anda tidak memiliki izin untuk mengakses fitur Sidang TPP.';
            abort(403, $msg);
        }
    }

    /**
     * ANAK MENU 1: Jadwal & Undangan Daring
     */
    public function index(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();
        $userUptFilter = $user->getUserUptFilter();

        $uptList = Upt::orderBy('nama_upt')->get();
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);
        $statusSidang = $request->query('status', '');
        $tanggalMulai = $request->query('tanggal_mulai', date('Y-m-01'));
        $tanggalSampai = $request->query('tanggal_sampai', date('Y-m-t'));

        $query = SidangTpp::with(['upt', 'creator'])->orderByDesc('tanggal_sidang')->orderByDesc('id');

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        if ($statusSidang !== '') {
            $query->where('status_sidang', $statusSidang);
        }

        if ($tanggalMulai && $tanggalSampai) {
            $query->whereBetween('tanggal_sidang', [$tanggalMulai, $tanggalSampai]);
        }

        $sidangList = $query->paginate(20)->withQueryString();

        // Statistik Ringkasan
        $statsQuery = SidangTpp::query();
        if ($filterUpt > 0) {
            $statsQuery->where('upt_id', $filterUpt);
        }
        $totalSidangBulanIni = (clone $statsQuery)->whereMonth('tanggal_sidang', date('m'))->whereYear('tanggal_sidang', date('Y'))->count();
        $totalUsulanBulanIni = (clone $statsQuery)->whereMonth('tanggal_sidang', date('m'))->whereYear('tanggal_sidang', date('Y'))->sum('total_usulan');
        $totalSelesaiTtd = (clone $statsQuery)->where('status_sidang', 'selesai_ttd_kakanwil')->count();
        $totalMenungguVerifikasi = (clone $statsQuery)->where('status_verifikasi_kanwil', 'menunggu_verifikasi')->count();

        return view('sidang_tpp.index', compact(
            'sidangList',
            'uptList',
            'filterUpt',
            'userUptFilter',
            'statusSidang',
            'tanggalMulai',
            'tanggalSampai',
            'totalSidangBulanIni',
            'totalUsulanBulanIni',
            'totalSelesaiTtd',
            'totalMenungguVerifikasi'
        ));
    }

    /**
     * Form Pendaftaran Sidang TPP (Undangan Kanwil, Link Daring, Import Excel & Input Manual)
     * Hanya dapat diakses oleh user UPT Pemasyarakatan.
     */
    public function create(): View|RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Kanwil hanya memantau, kewenangan mendaftarkan jadwal sidang sepenuhnya milik UPT
        if (empty($user->upt_id)) {
            return redirect()->route('sidang-tpp.index')
                ->with('error', 'Kewenangan mendaftarkan jadwal dan mengusulkan peserta sidang TPP hanya dimiliki oleh operator UPT Pemasyarakatan. Akun Kantor Wilayah bertindak sebagai pengawas bintorwasdal dan verifikator.');
        }

        $userUptFilter = $user->getUserUptFilter();
        $uptList = Upt::orderBy('nama_upt')->get();

        return view('sidang_tpp.create', compact('user', 'uptList', 'userUptFilter'));
    }

    /**
     * AJAX Preview Excel yang diunggah sebelum submit
     */
    public function previewExcel(Request $request): JsonResponse
    {
        $this->checkAccess('full');

        $request->validate([
            'file_excel' => 'required|file|max:10240',
        ]);

        try {
            $file = $request->file('file_excel');
            $result = TppExcelService::parseUploadedFile($file);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca berkas Excel: '.$e->getMessage(),
                'data' => [],
            ], 422);
        }
    }

    /**
     * Download Template Excel
     */
    public function downloadTemplate(): StreamedResponse
    {
        $this->checkAccess();

        return TppExcelService::downloadTemplate();
    }

    /**
     * Simpan Sidang TPP Baru beserta daftar usulan WBP (Bisa Manual atau Import Excel)
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Kanwil hanya melihat, semua kewenangan pendaftaran sidang milik user UPT
        if (empty($user->upt_id)) {
            abort(403, 'Akses ditolak. Kewenangan mendaftarkan jadwal sidang TPP hanya dimiliki oleh user UPT Pemasyarakatan.');
        }

        $rules = [
            'upt_id' => 'required|exists:upt,id',
            'nomor_undangan' => 'required|string|max:150',
            'tanggal_surat' => 'required|date',
            'tanggal_sidang' => 'required|date',
            'jam_mulai' => 'required',
            'agenda_sidang' => 'required|string|max:500',
            'metode_sidang' => 'required|in:tatap_muka,daring,hybrid',
            'link_daring' => 'nullable|string',
            'meeting_id' => 'nullable|string|max:100',
            'passcode' => 'nullable|string|max:100',
            'ketua_tpp' => 'required|string|max:150',
            'sekretaris_tpp' => 'required|string|max:150',
            'nama_pk_bapas' => 'nullable|string|max:150',
            'file_undangan' => 'nullable|file|mimes:pdf|max:10240',
            'file_excel' => 'nullable|file|max:10240',
            'wbp_json' => 'nullable|string',
            'peserta' => 'nullable|array',
        ];

        // Pastikan operator UPT hanya mendaftarkan sidang untuk UPT miliknya
        $request->merge(['upt_id' => $user->upt_id]);

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // Upload File Undangan jika ada
            $fileUndanganPath = null;
            if ($request->hasFile('file_undangan')) {
                $uploadDir = public_path('uploads/sidang_tpp');
                if (! File::isDirectory($uploadDir)) {
                    File::makeDirectory($uploadDir, 0755, true);
                }
                $file = $request->file('file_undangan');
                $filename = 'undangan_'.time().'_'.bin2hex(random_bytes(4)).'.pdf';
                $file->move($uploadDir, $filename);
                $fileUndanganPath = 'uploads/sidang_tpp/'.$filename;
            }

            // Susun array anggota TPP jika dikirim
            $anggotaTpp = [];
            if ($request->has('anggota_tpp') && is_array($request->input('anggota_tpp'))) {
                $anggotaTpp = array_values(array_filter($request->input('anggota_tpp'), fn ($item) => ! empty(trim((string) ($item['nama'] ?? '')))));
            }

            $sidang = SidangTpp::create([
                'upt_id' => $validated['upt_id'],
                'nomor_undangan' => $validated['nomor_undangan'],
                'tanggal_surat' => $validated['tanggal_surat'],
                'tanggal_sidang' => $validated['tanggal_sidang'],
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $request->input('jam_selesai'),
                'file_undangan' => $fileUndanganPath,
                'agenda_sidang' => $validated['agenda_sidang'],
                'agenda_deskripsi' => $validated['agenda_sidang'],
                'agenda_kategori' => 'hak_integrasi',
                'metode_sidang' => $validated['metode_sidang'],
                'link_daring' => $request->input('link_daring'),
                'meeting_id' => $request->input('meeting_id'),
                'passcode' => $request->input('passcode'),
                'ketua_tpp' => $validated['ketua_tpp'],
                'sekretaris_tpp' => $validated['sekretaris_tpp'],
                'nama_pk_bapas' => $request->input('nama_pk_bapas'),
                'anggota_tpp' => $anggotaTpp,
                'status_sidang' => 'dijadwalkan',
                'status_verifikasi_kanwil' => 'menunggu_sidang',
                'created_by' => $user->id,
            ]);

            // Simpan usulan WBP: mendukung input manual (peserta/wbp_json) ATAU import Excel langsung
            $usulanRows = [];
            if ($request->filled('wbp_json')) {
                $decoded = json_decode($request->input('wbp_json'), true);
                if (is_array($decoded) && ! empty($decoded)) {
                    $usulanRows = $decoded;
                }
            } elseif ($request->filled('peserta') && is_array($request->input('peserta'))) {
                $usulanRows = $request->input('peserta');
            } elseif ($request->hasFile('file_excel')) {
                $parsed = TppExcelService::parseUploadedFile($request->file('file_excel'));
                if (! empty($parsed['data'])) {
                    $usulanRows = $parsed['data'];
                }
            }

            foreach ($usulanRows as $row) {
                $namaWbp = trim((string) ($row['nama_wbp'] ?? ''));
                if ($namaWbp === '') {
                    continue;
                }

                $rawKep = strtolower(trim((string) ($row['keputusan'] ?? 'belum_putus')));
                $keputusan = match ($rawKep) {
                    'disetujui' => 'setuju',
                    'ditolak' => 'tidak_setuju',
                    'ditunda' => 'tunda',
                    default => in_array($rawKep, ['setuju', 'tidak_setuju', 'tunda']) ? $rawKep : 'belum_putus',
                };

                SidangTppUsulan::create([
                    'sidang_tpp_id' => $sidang->id,
                    'nama_wbp' => $namaWbp,
                    'nomor_register' => $row['nomor_register'] ?? '-',
                    'perkara_pasal' => $row['perkara_pasal'] ?? '-',
                    'lama_pidana' => $row['lama_pidana'] ?? '-',
                    'tanggal_ekspirasi' => ! empty($row['tanggal_ekspirasi']) ? $row['tanggal_ekspirasi'] : null,
                    'tanggal_setengah_pidana' => ! empty($row['tanggal_setengah_pidana']) ? $row['tanggal_setengah_pidana'] : null,
                    'tanggal_dua_pertiga' => ! empty($row['tanggal_dua_pertiga']) ? $row['tanggal_dua_pertiga'] : null,
                    'jenis_usulan' => $row['jenis_usulan'] ?? 'PB',
                    'perlu_perhatian' => ! empty($row['perlu_perhatian']),
                    'catatan_perhatian' => $row['catatan_perhatian'] ?? ($row['alasan_keterangan'] ?? null),
                    'skor_sppn' => isset($row['skor_sppn']) && is_numeric($row['skor_sppn']) ? (float) $row['skor_sppn'] : null,
                    'status_litmas' => $row['status_litmas'] ?? 'memenuhi_syarat',
                    'bebas_register_f' => isset($row['bebas_register_f']) ? (bool) $row['bebas_register_f'] : true,
                    'keputusan' => $keputusan,
                    'alasan_keterangan' => $row['alasan_keterangan'] ?? ($row['catatan_perhatian'] ?? null),
                ]);
            }

            $sidang->recalculateCounters();

            ActivityLog::log(
                'tambah',
                'sidang_tpp',
                "Jadwal Sidang TPP baru didaftarkan untuk {$sidang->upt?->nama_upt} pada tanggal {$sidang->tanggal_sidang->format('d/m/Y')}",
                [
                    'sidang_id' => $sidang->id,
                    'nomor_undangan' => $sidang->nomor_undangan,
                    'total_usulan' => $sidang->total_usulan,
                ]
            );

            DB::commit();

            // WhatsApp Notification Hook jika link daring diisi
            try {
                if (! empty($sidang->link_daring)) {
                    $pesan = sprintf(
                        "🏛️ *AGENDA SIDANG TPP PEMASYARAKATAN BANTEN*\n".
                        "━━━━━━━━━━━━━━━━━━━━━━\n".
                        "📍 *UPT*: %s\n".
                        "📅 *Tanggal*: %s (Pukul %s WIB)\n".
                        "🔗 *Metode*: %s\n".
                        "💻 *Link Daring*: %s\n".
                        "🆔 *Meeting ID*: %s | Passcode: %s\n".
                        "👥 *Jumlah Usulan Warga Binaan*: %d Orang\n".
                        "📌 *Agenda*: %s\n".
                        "━━━━━━━━━━━━━━━━━━━━━━\n".
                        '_SULTAN BANTEN - Sistem Monitoring Terpadu Bintorwasdal_',
                        $sidang->upt?->nama_upt ?? 'UPT Banten',
                        $sidang->tanggal_sidang->translatedFormat('l, d F Y'),
                        substr((string) $sidang->jam_mulai, 0, 5),
                        $sidang->metode_label,
                        $sidang->link_daring,
                        $sidang->meeting_id ?: '-',
                        $sidang->passcode ?: '-',
                        $sidang->total_usulan,
                        $sidang->agenda_label
                    );
                    WhatsAppService::sendToDefaultGroup($pesan);
                }
            } catch (\Throwable $e) {
                // Abaikan jika WA gateway belum terhubung
            }

            return redirect()->route('sidang-tpp.index')
                ->with('success', "Jadwal Sidang TPP berhasil disimpan dengan {$sidang->total_usulan} data usulan Warga Binaan.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data sidang: '.$e->getMessage());
        }
    }

    /**
     * Detail Sidang TPP
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $this->checkAccess();
        $sidang = SidangTpp::with(['upt', 'creator', 'verifier', 'usulan'])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'sidang' => $sidang,
                'usulan' => $sidang->usulan,
            ]);
        }

        return view('sidang_tpp.detail', compact('sidang'));
    }

    /**
     * ANAK MENU 2: Hasil Sidang UPT
     */
    public function hasilIndex(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();
        $userUptFilter = $user->getUserUptFilter();

        $uptList = Upt::orderBy('nama_upt')->get();
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = SidangTpp::with(['upt', 'creator'])->orderByDesc('tanggal_sidang');

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        // Tampilkan sidang yang menunggu hasil atau sedang berjalan
        $sidangList = $query->paginate(20)->withQueryString();

        return view('sidang_tpp.hasil', compact('sidangList', 'uptList', 'filterUpt', 'userUptFilter'));
    }

    /**
     * Update Putusan Hasil Sidang oleh UPT & Upload BA
     * Hanya dapat dilakukan oleh operator UPT pelaksana.
     */
    public function hasilUpdate(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        $sidang = SidangTpp::with('usulan')->findOrFail($id);

        // Hanya UPT pemilik sidang yang berwenang mengisi hasil sidang
        if (empty($user->upt_id) || (int) $user->upt_id !== (int) $sidang->upt_id) {
            abort(403, 'Akses ditolak. Pengisian hasil sidang, penetapan putusan Warga Binaan, dan pengunggahan Berita Acara hanya dapat dilakukan oleh operator UPT pelaksana.');
        }

        $request->validate([
            'nomor_berita_acara' => 'required|string|max:150',
            'file_berita_acara' => 'nullable|file|mimes:pdf|max:15360',
            'file_dokumentasi' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'keputusan' => 'nullable|array',
            'alasan_keterangan' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $uploadDir = public_path('uploads/sidang_tpp');
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            // Upload BA UPT
            if ($request->hasFile('file_berita_acara')) {
                $file = $request->file('file_berita_acara');
                $filename = 'ba_'.$sidang->id.'_'.time().'.pdf';
                $file->move($uploadDir, $filename);
                $sidang->file_berita_acara = 'uploads/sidang_tpp/'.$filename;
            }

            // Upload Foto Dokumentasi (Otomatis Kompres ke format JPG <= 500 KB per foto)
            $existingPhotos = $sidang->dokumentasi_list;
            $newPhotos = [];

            if ($request->hasFile('dokumentasi')) {
                $newPhotos = ImageCompressionService::compressMultipleToJpg($request->file('dokumentasi'), 'uploads/sidang_tpp', 512000);
            } elseif ($request->hasFile('file_dokumentasi')) {
                $newPhotos = ImageCompressionService::compressMultipleToJpg($request->file('file_dokumentasi'), 'uploads/sidang_tpp', 512000);
            }

            if (! empty($newPhotos)) {
                $allPhotos = array_merge($existingPhotos, $newPhotos);
                $sidang->file_dokumentasi = json_encode($allPhotos);
            }

            $sidang->nomor_berita_acara = $request->input('nomor_berita_acara');

            // Simpan keputusan tiap WBP (Setuju / Tidak Setuju / Tunda)
            $keputusanArr = $request->input('keputusan', []);
            $alasanArr = $request->input('alasan_keterangan', []);

            foreach ($sidang->usulan as $item) {
                if (isset($keputusanArr[$item->id])) {
                    $raw = $keputusanArr[$item->id];
                    $val = match ($raw) {
                        'disetujui', 'setuju' => 'setuju',
                        'ditolak', 'tidak_setuju' => 'tidak_setuju',
                        'ditunda', 'tunda' => 'tunda',
                        default => $raw,
                    };
                    $item->keputusan = $val;
                    $item->alasan_keterangan = $alasanArr[$item->id] ?? $item->alasan_keterangan;
                    $item->save();
                }
            }

            // Hitung ulang dan update status
            $sidang->recalculateCounters();
            $sidang->status_sidang = 'menunggu_verifikasi_kanwil';
            $sidang->status_verifikasi_kanwil = 'menunggu_verifikasi';
            $sidang->save();

            ActivityLog::log(
                'edit',
                'sidang_tpp',
                "UPT {$sidang->upt?->nama_upt} mengisi hasil sidang TPP No. BA {$sidang->nomor_berita_acara} (Setuju: {$sidang->total_setuju}, Tunda: {$sidang->total_tunda}, Tidak Setuju: {$sidang->total_tidak_setuju})",
                ['sidang_id' => $sidang->id]
            );

            DB::commit();

            return redirect()->route('sidang-tpp.hasil')
                ->with('success', 'Hasil sidang TPP dan Berita Acara berhasil disimpan dan diajukan ke Kantor Wilayah untuk Verifikasi Kesesuaian Dokumen.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memperbarui hasil sidang: '.$e->getMessage());
        }
    }

    /**
     * ANAK MENU 3: Verifikasi & TTD Kanwil (Bintorwasdal)
     */
    public function kanwilIndex(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();
        $uptList = Upt::orderBy('nama_upt')->get();

        $filterUpt = (int) $request->query('upt_id', 0);
        $statusVerif = $request->query('status_verifikasi', '');

        $query = SidangTpp::with(['upt', 'creator', 'usulan'])
            ->orderByDesc('tanggal_sidang');

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        if ($statusVerif !== '') {
            $query->where('status_verifikasi_kanwil', $statusVerif);
        }

        $sidangList = $query->paginate(20)->withQueryString();

        return view('sidang_tpp.kanwil', compact('sidangList', 'uptList', 'filterUpt', 'statusVerif', 'user'));
    }

    /**
     * Simpan Verifikasi Kesesuaian Dokumen oleh Kanwil
     * Hanya berwenang dilakukan oleh akun Kantor Wilayah.
     */
    public function kanwilVerifikasi(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Hanya role Kanwil (tanpa upt_id) yang berwenang memverifikasi
        if (! empty($user->upt_id)) {
            abort(403, 'Akses ditolak. Kewenangan Verifikasi Kesesuaian Dokumen hanya dimiliki oleh Kantor Wilayah.');
        }

        $sidang = SidangTpp::findOrFail($id);

        $request->validate([
            'status_verifikasi_kanwil' => 'required|in:sesuai,tidak_sesuai,perbaikan,terverifikasi,ditolak,perlu_perbaikan',
            'catatan_bintorwasdal' => 'nullable|string',
            'nama_pengawas_kanwil' => 'nullable|string|max:150',
            'nomor_laporan_kanwil' => 'nullable|string|max:150',
            'tanggal_ttd_kakanwil' => 'nullable|date',
            'file_laporan_kakanwil' => 'nullable|file|mimes:pdf|max:15360',
        ]);

        DB::beginTransaction();
        try {
            $uploadDir = public_path('uploads/sidang_tpp');
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            if ($request->hasFile('file_laporan_kakanwil')) {
                $file = $request->file('file_laporan_kakanwil');
                $filename = 'kakanwil_'.$sidang->id.'_'.time().'.pdf';
                $file->move($uploadDir, $filename);
                $sidang->file_laporan_kakanwil = 'uploads/sidang_tpp/'.$filename;
            }

            $rawStatus = $request->input('status_verifikasi_kanwil');
            $statusVerif = match ($rawStatus) {
                'terverifikasi', 'sesuai' => 'sesuai',
                'ditolak', 'tidak_sesuai' => 'tidak_sesuai',
                'perlu_perbaikan', 'perbaikan' => 'perbaikan',
                default => $rawStatus,
            };

            $sidang->kanwil_hadir = $request->boolean('kanwil_hadir');
            $sidang->nama_pengawas_kanwil = $request->input('nama_pengawas_kanwil');
            $sidang->catatan_bintorwasdal = $request->input('catatan_bintorwasdal');
            $sidang->nomor_laporan_kanwil = $request->input('nomor_laporan_kanwil');
            $sidang->tanggal_ttd_kakanwil = $request->input('tanggal_ttd_kakanwil');
            $sidang->status_verifikasi_kanwil = $statusVerif;
            $sidang->verified_by = $user->id;
            $sidang->verified_at = now();

            if ($statusVerif === 'sesuai' && ! empty($sidang->file_laporan_kakanwil)) {
                $sidang->status_sidang = 'selesai_ttd_kakanwil';
            }

            $sidang->save();

            ActivityLog::log(
                'edit',
                'sidang_tpp',
                "Kanwil melakukan Verifikasi Kesesuaian Dokumen Sidang TPP {$sidang->upt?->nama_upt} (Hasil: {$sidang->status_verifikasi_kanwil})",
                ['sidang_id' => $sidang->id]
            );

            DB::commit();

            return redirect()->route('sidang-tpp.kanwil')
                ->with('success', 'Verifikasi Kesesuaian Dokumen berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memproses Verifikasi Kesesuaian Dokumen: '.$e->getMessage());
        }
    }

    /**
     * Upload Khusus: Laporan Sidang TPP Kantor Wilayah (Bertanda Tangan Ka. Kanwil)
     * Hanya berwenang dilakukan oleh akun Kantor Wilayah.
     */
    public function uploadLaporanKanwil(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Hanya akun Kanwil (tanpa upt_id) yang berwenang mengunggah laporan hasil sidang Ka. Kanwil
        if (! empty($user->upt_id)) {
            abort(403, 'Akses ditolak. Kewenangan mengunggah Laporan Sidang TPP Kantor Wilayah hanya dimiliki oleh Kantor Wilayah.');
        }

        $sidang = SidangTpp::with('upt')->findOrFail($id);

        $request->validate([
            'file_laporan_kakanwil' => 'required|file|mimes:pdf|max:20480',
            'nomor_laporan_kanwil' => 'required|string|max:150',
            'tanggal_ttd_kakanwil' => 'required|date',
            'catatan_bintorwasdal' => 'nullable|string',
        ], [
            'file_laporan_kakanwil.required' => 'Wajib memilih file PDF Laporan Sidang TPP Kantor Wilayah yang telah ditandatangani Ka. Kanwil.',
            'nomor_laporan_kanwil.required' => 'Nomor Surat/Laporan Kantor Wilayah harus diisi.',
            'tanggal_ttd_kakanwil.required' => 'Tanggal pengesahan tanda tangan Ka. Kanwil harus diisi.',
        ]);

        $uploadDir = public_path('uploads/sidang_tpp');
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $file = $request->file('file_laporan_kakanwil');
        $filename = 'laporan_kanwil_'.$sidang->id.'_'.time().'.pdf';
        $file->move($uploadDir, $filename);

        $sidang->file_laporan_kakanwil = 'uploads/sidang_tpp/'.$filename;
        $sidang->nomor_laporan_kanwil = $request->input('nomor_laporan_kanwil');
        $sidang->tanggal_ttd_kakanwil = $request->input('tanggal_ttd_kakanwil');
        if ($request->filled('catatan_bintorwasdal')) {
            $sidang->catatan_bintorwasdal = $request->input('catatan_bintorwasdal');
        }
        $sidang->status_verifikasi_kanwil = 'sesuai';
        $sidang->status_sidang = 'selesai_ttd_kakanwil';
        $sidang->verified_by = $user->id;
        $sidang->verified_at = now();
        $sidang->save();

        ActivityLog::log(
            'edit',
            'sidang_tpp',
            "Kanwil mengunggah Laporan Sidang TPP Kantor Wilayah No. {$sidang->nomor_laporan_kanwil} untuk {$sidang->upt?->nama_upt} (Dokumen siap diunduh UPT)",
            ['sidang_id' => $sidang->id]
        );

        // Notifikasi Otomatis WhatsApp ke UPT & Pimpinan
        try {
            $pesan = sprintf(
                "✅ *LAPORAN SIDANG TPP KANTOR WILAYAH TELAH TERBIT*\n".
                "━━━━━━━━━━━━━━━━━━━━━━\n".
                "📍 *UPT*: %s\n".
                "📄 *Nomor Laporan Ka. Kanwil*: %s\n".
                "📅 *Tanggal Pengesahan*: %s\n".
                "⚖️ *Agenda Sidang*: %s\n".
                "👥 *Total Usulan*: %d Orang (Setuju: %d)\n".
                "📌 *Status*: Laporan telah ditandatangani Kepala Kantor Wilayah Banten dan siap diunduh oleh UPT sebagai dasar pengusulan hak bersyarat di SDP.\n".
                "━━━━━━━━━━━━━━━━━━━━━━\n".
                '_SULTAN BANTEN - Sistem Monitoring Terpadu Bintorwasdal_',
                $sidang->upt?->nama_upt ?? 'UPT Banten',
                $sidang->nomor_laporan_kanwil,
                $sidang->tanggal_ttd_kakanwil->format('d/m/Y'),
                $sidang->agenda_label,
                $sidang->total_usulan,
                $sidang->total_setuju
            );
            WhatsAppService::sendToDefaultGroup($pesan);
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'Laporan Sidang TPP Kantor Wilayah yang telah ditandatangani Ka. Kanwil berhasil diunggah. Dokumen resmi siap diunduh oleh UPT.');
    }

    /**
     * Download Resmi Laporan Sidang TPP Kantor Wilayah untuk UPT
     */
    public function downloadLaporanKanwil(int $id): BinaryFileResponse
    {
        $this->checkAccess();
        $sidang = SidangTpp::with('upt')->findOrFail($id);

        if (! $sidang->hasLaporanKanwil()) {
            abort(404, 'Laporan Sidang TPP Kantor Wilayah belum diunggah atau berkas tidak ditemukan.');
        }

        $fullPath = public_path($sidang->file_laporan_kakanwil);
        $cleanUpt = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->upt?->nama_upt ?? 'UPT');
        $downloadName = "Laporan_Sidang_TPP_Kanwil_{$cleanUpt}_{$sidang->tanggal_sidang->format('Ymd')}.pdf";

        return response()->download($fullPath, $downloadName);
    }

    /**
     * ANAK MENU 4: Rekap & Download Dokumen
     */
    public function rekapitulasiIndex(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();
        $userUptFilter = $user->getUserUptFilter();

        $uptList = Upt::orderBy('nama_upt')->get();
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);
        $searchWbp = trim((string) $request->query('search_wbp', ''));
        $tanggalMulai = $request->query('tanggal_mulai', date('Y-01-01'));
        $tanggalSampai = $request->query('tanggal_sampai', date('Y-12-31'));

        $query = SidangTpp::with(['upt', 'creator', 'usulan'])
            ->orderByDesc('tanggal_sidang');

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        if ($tanggalMulai && $tanggalSampai) {
            $query->whereBetween('tanggal_sidang', [$tanggalMulai, $tanggalSampai]);
        }

        if ($searchWbp !== '') {
            $query->whereHas('usulan', function ($q) use ($searchWbp) {
                $q->where('nama_wbp', 'like', "%{$searchWbp}%")
                    ->orWhere('nomor_register', 'like', "%{$searchWbp}%");
            });
        }

        $sidangList = $query->paginate(25)->withQueryString();

        return view('sidang_tpp.rekapitulasi', compact(
            'sidangList',
            'uptList',
            'filterUpt',
            'userUptFilter',
            'searchWbp',
            'tanggalMulai',
            'tanggalSampai'
        ));
    }

    /**
     * Unduh File Berkas Sidang (Undangan, BA, atau Laporan TTD Ka. Kanwil)
     */
    public function downloadFile(int $id, string $type): BinaryFileResponse
    {
        $this->checkAccess();

        $sidang = SidangTpp::findOrFail($id);

        $relativePath = match ($type) {
            'undangan' => $sidang->file_undangan,
            'berita_acara' => $sidang->file_berita_acara,
            'dokumentasi' => $sidang->file_dokumentasi,
            'laporan_kakanwil' => $sidang->file_laporan_kakanwil,
            default => null,
        };

        if (empty($relativePath) || ! File::exists(public_path($relativePath))) {
            abort(404, 'Berkas yang diminta tidak ditemukan di server.');
        }

        $fullPath = public_path($relativePath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $cleanUpt = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->upt?->nama_upt ?? 'UPT');
        $downloadName = "{$type}_{$cleanUpt}_{$sidang->tanggal_sidang->format('Ymd')}.{$extension}";

        return response()->download($fullPath, $downloadName);
    }

    /**
     * Hapus Sidang TPP
     * Hanya dapat dilakukan oleh operator UPT pelaksana sebelum disahkan Kanwil.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        $sidang = SidangTpp::findOrFail($id);

        if (empty($user->upt_id) || (int) $user->upt_id !== (int) $sidang->upt_id) {
            abort(403, 'Akses ditolak. Hanya operator UPT pelaksana pemilik berkas yang berwenang menghapus jadwal sidang.');
        }

        if ($sidang->status_verifikasi_kanwil === 'sesuai' || ! empty($sidang->file_laporan_kakanwil)) {
            abort(403, 'Jadwal sidang yang telah diverifikasi sesuai atau diterbitkan Laporan Ka. Kanwil tidak dapat dihapus.');
        }

        // Hapus file fisik jika ada
        $deleteFiles = [
            $sidang->file_undangan,
            $sidang->file_berita_acara,
            $sidang->file_laporan_kakanwil,
        ];
        foreach ($sidang->dokumentasi_list as $foto) {
            $deleteFiles[] = $foto;
        }

        foreach ($deleteFiles as $filePath) {
            if (! empty($filePath) && File::exists(public_path($filePath))) {
                @File::delete(public_path($filePath));
            }
        }

        $sidang->delete();

        ActivityLog::log('hapus', 'sidang_tpp', "Data Sidang TPP ID {$id} berhasil dihapus");

        return back()->with('success', 'Data Sidang TPP berhasil dihapus.');
    }

    /**
     * Export Rekapitulasi Sidang TPP se-Banten ke Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->checkAccess();

        $user = auth()->user();
        $userUptFilter = $user->getUserUptFilter();
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);
        $tanggalMulai = $request->query('tanggal_mulai', date('Y-01-01'));
        $tanggalSampai = $request->query('tanggal_sampai', date('Y-12-31'));

        $query = SidangTpp::with(['upt', 'usulan'])->orderByDesc('tanggal_sidang');

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }
        if ($tanggalMulai && $tanggalSampai) {
            $query->whereBetween('tanggal_sidang', [$tanggalMulai, $tanggalSampai]);
        }

        $list = $query->get();
        $filename = 'Rekapitulasi_Sidang_TPP_Banten_'.date('Ymd_His').'.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return new StreamedResponse(function () use ($list) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
            echo '<table border="1">';
            echo '<tr bgcolor="#1e3a8a" style="color:white; font-weight:bold;">';
            echo '<th>No</th><th>UPT</th><th>No. Undangan</th><th>Tanggal Sidang</th><th>Agenda Sidang</th><th>Metode</th><th>Link Daring</th><th>Total Usulan</th><th>Setuju</th><th>Tunda</th><th>Tidak Setuju</th><th>No. BA UPT</th><th>Verifikasi Kesesuaian Dokumen</th><th>No. Laporan Ka. Kanwil</th>';
            echo '</tr>';

            $no = 1;
            foreach ($list as $r) {
                $verifLabel = match ($r->status_verifikasi_kanwil) {
                    'sesuai', 'terverifikasi' => 'Sesuai',
                    'tidak_sesuai', 'ditolak' => 'Tidak Sesuai',
                    'perbaikan', 'perlu_perbaikan' => 'Perbaikan',
                    'menunggu_verifikasi' => 'Menunggu Verifikasi',
                    default => 'Menunggu Sidang',
                };

                echo '<tr>';
                echo "<td align=\"center\">{$no}</td>";
                echo '<td>'.htmlspecialchars($r->upt?->nama_upt ?? '-').'</td>';
                echo '<td>'.htmlspecialchars($r->nomor_undangan).'</td>';
                echo "<td align=\"center\">{$r->tanggal_sidang->format('d/m/Y')}</td>";
                echo '<td>'.htmlspecialchars($r->agenda_label).'</td>';
                echo '<td>'.htmlspecialchars($r->metode_label).'</td>';
                echo '<td>'.htmlspecialchars($r->link_daring ?? '-').'</td>';
                echo "<td align=\"center\">{$r->total_usulan}</td>";
                echo "<td align=\"center\">{$r->total_setuju}</td>";
                echo "<td align=\"center\">{$r->total_tunda}</td>";
                echo "<td align=\"center\">{$r->total_tidak_setuju}</td>";
                echo '<td>'.htmlspecialchars($r->nomor_berita_acara ?? '-').'</td>';
                echo '<td>'.htmlspecialchars($verifLabel).'</td>';
                echo '<td>'.htmlspecialchars($r->nomor_laporan_kanwil ?? '-').'</td>';
                echo '</tr>';
                $no++;
            }

            echo '</table></body></html>';
        }, 200, $headers);
    }

    /**
     * Tambah Peserta Sidang ke Sidang yang sudah terdaftar
     */
    public function pesertaStore(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();
        $sidang = SidangTpp::findOrFail($id);

        if (empty($user->upt_id) || (int) $user->upt_id !== (int) $sidang->upt_id) {
            abort(403, 'Akses ditolak. Kewenangan mengelola peserta sidang hanya dimiliki oleh user UPT terkait.');
        }

        if ($sidang->status_sidang === 'selesai_ttd_kakanwil') {
            return back()->with('error', 'Tidak dapat menambah peserta. Sidang ini telah selesai dan disahkan oleh Kepala Kantor Wilayah.');
        }

        $validated = $request->validate([
            'nama_wbp' => 'required|string|max:200',
            'nomor_register' => 'required|string|max:100',
            'perkara_pasal' => 'nullable|string|max:255',
            'lama_pidana' => 'nullable|string|max:100',
            'tanggal_ekspirasi' => 'nullable|date',
            'tanggal_setengah_pidana' => 'nullable|date',
            'tanggal_dua_pertiga' => 'nullable|date',
            'jenis_usulan' => 'required|string|max:50',
            'perlu_perhatian' => 'nullable',
            'catatan_perhatian' => 'nullable|string',
            'alasan_keterangan' => 'nullable|string',
        ]);

        $cleanJenis = strtoupper(trim((string) ($validated['jenis_usulan'] ?? '')));
        if (str_contains($cleanJenis, 'ASIMILASI')) {
            $validated['jenis_usulan'] = 'Asimilasi';
        }

        $validated['perkara_pasal'] = $validated['perkara_pasal'] ?? '-';
        $validated['lama_pidana'] = $validated['lama_pidana'] ?? '-';
        $validated['perlu_perhatian'] = $request->boolean('perlu_perhatian');
        $validated['keputusan'] = 'belum_putus';

        $sidang->usulan()->create($validated);
        $sidang->recalculateCounters();

        ActivityLog::log(
            'tambah',
            'sidang_tpp',
            "Menambahkan Warga Binaan {$validated['nama_wbp']} ke Sidang TPP #{$sidang->id}",
            ['sidang_id' => $sidang->id]
        );

        return back()->with('success', "Warga Binaan {$validated['nama_wbp']} berhasil ditambahkan ke daftar peserta sidang.");
    }

    /**
     * Edit / Update Data Peserta Sidang
     */
    public function pesertaUpdate(Request $request, int $id, int $pesertaId): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();
        $sidang = SidangTpp::findOrFail($id);
        $usulan = $sidang->usulan()->findOrFail($pesertaId);

        if (empty($user->upt_id) || (int) $user->upt_id !== (int) $sidang->upt_id) {
            abort(403, 'Akses ditolak. Kewenangan mengelola peserta sidang hanya dimiliki oleh user UPT terkait.');
        }

        if ($sidang->status_sidang === 'selesai_ttd_kakanwil') {
            return back()->with('error', 'Tidak dapat mengubah peserta. Sidang ini telah selesai dan disahkan oleh Kepala Kantor Wilayah.');
        }

        $validated = $request->validate([
            'nama_wbp' => 'required|string|max:200',
            'nomor_register' => 'required|string|max:100',
            'perkara_pasal' => 'nullable|string|max:255',
            'lama_pidana' => 'nullable|string|max:100',
            'tanggal_ekspirasi' => 'nullable|date',
            'tanggal_setengah_pidana' => 'nullable|date',
            'tanggal_dua_pertiga' => 'nullable|date',
            'jenis_usulan' => 'required|string|max:50',
            'perlu_perhatian' => 'nullable',
            'catatan_perhatian' => 'nullable|string',
            'alasan_keterangan' => 'nullable|string',
        ]);

        $cleanJenis = strtoupper(trim((string) ($validated['jenis_usulan'] ?? '')));
        if (str_contains($cleanJenis, 'ASIMILASI')) {
            $validated['jenis_usulan'] = 'Asimilasi';
        }

        $validated['perkara_pasal'] = $validated['perkara_pasal'] ?? '-';
        $validated['lama_pidana'] = $validated['lama_pidana'] ?? '-';
        $validated['perlu_perhatian'] = $request->boolean('perlu_perhatian');

        $usulan->update($validated);
        $sidang->recalculateCounters();

        ActivityLog::log(
            'edit',
            'sidang_tpp',
            "Mengubah data Warga Binaan {$usulan->nama_wbp} pada Sidang TPP #{$sidang->id}",
            ['sidang_id' => $sidang->id, 'usulan_id' => $usulan->id]
        );

        return back()->with('success', "Data Warga Binaan {$usulan->nama_wbp} berhasil diperbarui.");
    }

    /**
     * Hapus Peserta Sidang dari Sidang
     */
    public function pesertaDestroy(int $id, int $pesertaId): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();
        $sidang = SidangTpp::findOrFail($id);
        $usulan = $sidang->usulan()->findOrFail($pesertaId);

        if (empty($user->upt_id) || (int) $user->upt_id !== (int) $sidang->upt_id) {
            abort(403, 'Akses ditolak. Kewenangan mengelola peserta sidang hanya dimiliki oleh user UPT terkait.');
        }

        if ($sidang->status_sidang === 'selesai_ttd_kakanwil') {
            return back()->with('error', 'Tidak dapat menghapus peserta. Sidang ini telah selesai dan disahkan oleh Kepala Kantor Wilayah.');
        }

        $namaWbp = $usulan->nama_wbp;
        $usulan->delete();
        $sidang->recalculateCounters();

        ActivityLog::log(
            'hapus',
            'sidang_tpp',
            "Menghapus Warga Binaan {$namaWbp} dari Sidang TPP #{$sidang->id}",
            ['sidang_id' => $sidang->id]
        );

        return back()->with('success', "Warga Binaan {$namaWbp} berhasil dihapus dari daftar peserta sidang.");
    }
}
