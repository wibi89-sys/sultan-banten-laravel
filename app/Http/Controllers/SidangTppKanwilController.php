<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KanwilPegawai;
use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\TppKanwilSetting;
use App\Models\User;
use App\Services\GeminiAiService;
use App\Services\TppKanwilDokumenService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SidangTppKanwilController extends Controller
{
    /**
     * Pastikan pengguna memiliki hak akses modul Sidang TPP tingkat Kantor Wilayah.
     */
    protected function checkKanwilAccess(string $requiredLevel = 'view'): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isKanwil()) {
            abort(403, 'Akses ditolak. Fitur Pelaksanaan Sidang TPP Kantor Wilayah hanya diperuntukkan bagi personil Kantor Wilayah.');
        }

        if (! $user->hasAccess('sidang_tpp_kanwil', $requiredLevel)) {
            $msg = $requiredLevel === 'full'
                ? 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Sidang TPP Kanwil.'
                : 'Akses ditolak. Anda tidak memiliki izin untuk mengakses fitur Sidang TPP Kanwil.';
            abort(403, $msg);
        }
    }

    /**
     * Daftar Sidang TPP Kantor Wilayah.
     */
    public function index(Request $request): View
    {
        $this->checkKanwilAccess();

        $statusSidang = $request->query('status', '');
        $statusDisposisi = $request->query('disposisi', '');
        $search = $request->query('search', '');
        $tanggalMulai = $request->query('tanggal_mulai', date('Y-01-01'));
        $tanggalSampai = $request->query('tanggal_sampai', date('Y-12-31'));

        $query = SidangTppKanwil::with(['creator'])->orderByDesc('tanggal_sidang')->orderByDesc('id');

        if ($statusSidang !== '') {
            $query->where('status_sidang', $statusSidang);
        }

        if ($statusDisposisi !== '') {
            $query->where('tanggapan_kakanwil_status', $statusDisposisi);
        }

        if (! empty($tanggalMulai) && ! empty($tanggalSampai)) {
            $query->whereBetween('tanggal_sidang', [$tanggalMulai, $tanggalSampai]);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_sidang', 'like', "%{$search}%")
                    ->orWhere('nomor_nota_dinas', 'like', "%{$search}%")
                    ->orWhere('agenda_judul', 'like', "%{$search}%")
                    ->orWhere('agenda_kategori', 'like', "%{$search}%")
                    ->orWhere('ketua_nama', 'like', "%{$search}%");
            });
        }

        $sidangList = $query->paginate(15)->withQueryString();

        // Statistik Ringkasan
        $stats = [
            'total_sidang' => SidangTppKanwil::count(),
            'total_tahun_ini' => SidangTppKanwil::whereYear('tanggal_sidang', date('Y'))->count(),
            'total_berlangsung' => SidangTppKanwil::whereIn('status_sidang', ['undangan_terbit', 'berlangsung'])->count(),
            'total_menunggu_kakanwil' => SidangTppKanwil::where('status_sidang', 'menunggu_kakanwil')->count(),
            'total_selesai' => SidangTppKanwil::where('status_sidang', 'selesai')->count(),
        ];

        return view('sidang_tpp_kanwil.index', compact(
            'sidangList',
            'stats',
            'statusSidang',
            'statusDisposisi',
            'search',
            'tanggalMulai',
            'tanggalSampai'
        ));
    }

    /**
     * Formulir Registrasi Sidang TPP Kanwil Baru.
     */
    public function create(): View
    {
        $this->checkKanwilAccess('full');

        $setting = TppKanwilSetting::getFirst();

        $pegawaiList = KanwilPegawai::where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        // Rekomendasi Nomor Sidang Otomatis
        $countThisMonth = SidangTppKanwil::whereYear('tanggal_sidang', date('Y'))
            ->whereMonth('tanggal_sidang', date('m'))
            ->count() + 1;
        $suggestedNomorSidang = 'STPP-KW/'.date('Y/m').'/'.str_pad($countThisMonth, 3, '0', STR_PAD_LEFT);
        $suggestedNomorNota = ($setting->nomor_nota_prefix ?? 'WP.12.UM.01.01-').str_pad($countThisMonth, 3, '0', STR_PAD_LEFT);
        $suggestedNomorRisalah = ($setting->nomor_risalah_prefix ?? 'WP.12.PK.06.05-').str_pad($countThisMonth, 2, '0', STR_PAD_LEFT);
        $suggestedNomorLaporan = ($setting->nomor_laporan_prefix ?? 'WP.12.PK.06.06-').str_pad($countThisMonth, 2, '0', STR_PAD_LEFT);

        $defaultKetua = $pegawaiList->firstWhere('peran_tpp', 'Ketua') ?? $pegawaiList->first();
        $defaultSekretaris = $pegawaiList->firstWhere('peran_tpp', 'Sekretaris');
        $defaultAnggotaIds = $pegawaiList->where('peran_tpp', 'Anggota')->pluck('id')->toArray();

        return view('sidang_tpp_kanwil.create', compact(
            'pegawaiList',
            'suggestedNomorSidang',
            'suggestedNomorNota',
            'suggestedNomorRisalah',
            'suggestedNomorLaporan',
            'setting',
            'defaultKetua',
            'defaultSekretaris',
            'defaultAnggotaIds'
        ));
    }

    /**
     * Simpan Sidang TPP Kanwil Baru beserta Tim yang Ditugaskan.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $setting = TppKanwilSetting::getFirst();

        $validated = $request->validate([
            'nomor_sidang' => 'required|string|max:100',
            'tanggal_sidang' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'nullable',
            'tempat' => 'required|string|max:255',
            'metode' => 'required|in:luring,daring,hybrid',
            'link_daring' => 'nullable|string|max:255',
            'meeting_id' => 'nullable|string|max:100',
            'passcode' => 'nullable|string|max:100',
            'nomor_nota_dinas' => 'nullable|string|max:150',
            'tanggal_nota_dinas' => 'nullable|date',
            'nomor_risalah' => 'nullable|string|max:150',
            'nomor_laporan' => 'nullable|string|max:150',
            'agenda_kategori' => 'required|string|max:100',
            'agenda_judul' => 'required|string|max:255',
            'agenda_deskripsi' => 'nullable|string',
            'file_bahan_sidang' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:15360',
            'ketua_id' => 'required|exists:kanwil_pegawai,id',
            'sekretaris_id' => 'required|exists:kanwil_pegawai,id',
            'anggota_ids' => 'required|array|min:1',
            'anggota_ids.*' => 'exists:kanwil_pegawai,id',
            'is_klien_bapas' => 'nullable|boolean',
            'klien_nama' => 'nullable|string|max:150',
            'klien_bin' => 'nullable|string|max:100',
            'klien_no_register' => 'nullable|string|max:100',
            'klien_bapas_nama' => 'nullable|string|max:150',
            'klien_nomor_surat_bapas' => 'nullable|string|max:150',
            'klien_tanggal_surat_bapas' => 'nullable|date',
            'klien_nama_pk' => 'nullable|string|max:150',
            'klien_program' => 'nullable|string|max:50',
            'klien_keperluan_ln' => 'nullable|string|max:150',
            'klien_negara_tujuan' => 'nullable|string|max:100',
            'klien_tgl_berangkat' => 'nullable|date',
            'klien_tgl_kembali' => 'nullable|date',
            'klien_durasi_hari' => 'nullable|numeric',
            'klien_penjamin_nama' => 'nullable|string|max:150',
            'klien_penjamin_hubungan' => 'nullable|string|max:100',
            'klien_penjamin_telepon' => 'nullable|string|max:50',
            'file_litmas_bapas' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_surat_bapas' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_bukti_travel' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip|max:15360',
            'file_surat_jaminan' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip|max:15360',
        ]);

        $ketua = KanwilPegawai::findOrFail($request->ketua_id);
        $sekretaris = KanwilPegawai::findOrFail($request->sekretaris_id);

        $fileBahanPath = null;
        if ($request->hasFile('file_bahan_sidang')) {
            $dir = public_path('uploads/sidang_tpp_kanwil/bahan');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
            $file = $request->file('file_bahan_sidang');
            $filename = 'bahan_'.date('Ymd_His').'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $fileBahanPath = 'uploads/sidang_tpp_kanwil/bahan/'.$filename;
        }

        $isKlienBapas = $request->boolean('is_klien_bapas')
            || str_contains(strtolower($request->agenda_kategori), 'klien')
            || str_contains(strtolower($request->agenda_kategori), 'luar negeri')
            || str_contains(strtolower($request->agenda_kategori), 'bapas')
            || str_contains(strtolower($request->agenda_kategori), 'umroh')
            || str_contains(strtolower($request->agenda_kategori), 'berobat');

        $klienData = null;
        if ($isKlienBapas) {
            $dirKlien = public_path('uploads/sidang_tpp_kanwil/klien');
            if (! File::isDirectory($dirKlien)) {
                File::makeDirectory($dirKlien, 0755, true, true);
            }

            $litmasPath = null;
            if ($request->hasFile('file_litmas_bapas')) {
                $f = $request->file('file_litmas_bapas');
                $fn = 'litmas_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirKlien, $fn);
                $litmasPath = 'uploads/sidang_tpp_kanwil/klien/'.$fn;
            }

            $suratBapasPath = null;
            if ($request->hasFile('file_surat_bapas')) {
                $f = $request->file('file_surat_bapas');
                $fn = 'surat_bapas_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirKlien, $fn);
                $suratBapasPath = 'uploads/sidang_tpp_kanwil/klien/'.$fn;
            }

            $buktiTravelPath = null;
            if ($request->hasFile('file_bukti_travel')) {
                $f = $request->file('file_bukti_travel');
                $fn = 'travel_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirKlien, $fn);
                $buktiTravelPath = 'uploads/sidang_tpp_kanwil/klien/'.$fn;
            }

            $suratJaminanPath = null;
            if ($request->hasFile('file_surat_jaminan')) {
                $f = $request->file('file_surat_jaminan');
                $fn = 'jaminan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirKlien, $fn);
                $suratJaminanPath = 'uploads/sidang_tpp_kanwil/klien/'.$fn;
            }

            $klienData = [
                'nama_klien' => $request->input('klien_nama', $request->agenda_judul),
                'bin' => $request->input('klien_bin'),
                'no_register_bapas' => $request->input('klien_no_register'),
                'bapas_nama' => $request->input('klien_bapas_nama', 'Balai Pemasyarakatan Kelas I Serang'),
                'nomor_surat_bapas' => $request->input('klien_nomor_surat_bapas'),
                'tanggal_surat_bapas' => $request->input('klien_tanggal_surat_bapas'),
                'nama_pk' => $request->input('klien_nama_pk', 'Pembimbing Kemasyarakatan'),
                'program_integrasi' => $request->input('klien_program', 'Pembebasan Bersyarat (PB)'),
                'keperluan_ln' => $request->input('klien_keperluan_ln', 'Ibadah Umroh'),
                'negara_tujuan' => $request->input('klien_negara_tujuan', 'Kerajaan Arab Saudi'),
                'tgl_berangkat' => $request->input('klien_tgl_berangkat'),
                'tgl_kembali' => $request->input('klien_tgl_kembali'),
                'durasi_hari' => $request->input('klien_durasi_hari', 14),
                'penjamin_nama' => $request->input('klien_penjamin_nama'),
                'penjamin_hubungan' => $request->input('klien_penjamin_hubungan', 'Keluarga'),
                'penjamin_telepon' => $request->input('klien_penjamin_telepon'),
                'file_litmas_bapas' => $litmasPath,
                'file_surat_bapas' => $suratBapasPath,
                'file_bukti_travel' => $buktiTravelPath,
                'file_surat_jaminan' => $suratJaminanPath,
            ];
        }

        $countTotal = SidangTppKanwil::count() + 1;
        $nomorRisalah = $request->input('nomor_risalah') ?: (($setting->nomor_risalah_prefix ?? 'WP.12.PK.06.05-').str_pad($countTotal, 2, '0', STR_PAD_LEFT));
        $nomorLaporan = $request->input('nomor_laporan') ?: (($setting->nomor_laporan_prefix ?? 'WP.12.PK.06.06-').str_pad($countTotal, 2, '0', STR_PAD_LEFT));

        $sidang = SidangTppKanwil::create([
            'nomor_sidang' => $validated['nomor_sidang'],
            'tanggal_sidang' => $validated['tanggal_sidang'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'tempat' => $validated['tempat'],
            'metode' => $validated['metode'],
            'link_daring' => $validated['link_daring'],
            'meeting_id' => $validated['meeting_id'],
            'passcode' => $validated['passcode'],
            'nomor_nota_dinas' => $validated['nomor_nota_dinas'],
            'tanggal_nota_dinas' => $validated['tanggal_nota_dinas'],
            'nomor_risalah' => $nomorRisalah,
            'nomor_laporan' => $nomorLaporan,
            'agenda_kategori' => $validated['agenda_kategori'],
            'agenda_judul' => $validated['agenda_judul'],
            'agenda_deskripsi' => $validated['agenda_deskripsi'],
            'is_klien_bapas' => $isKlienBapas,
            'klien_data' => $klienData,
            'file_bahan_sidang' => $fileBahanPath,
            'ketua_id' => $ketua->id,
            'ketua_nama' => $ketua->nama,
            'ketua_nip' => $ketua->nip,
            'ketua_jabatan' => $ketua->jabatan,
            'sekretaris_id' => $sekretaris->id,
            'sekretaris_nama' => $sekretaris->nama,
            'sekretaris_nip' => $sekretaris->nip,
            'sekretaris_jabatan' => $sekretaris->jabatan,
            'status_sidang' => 'draft',
            'created_by' => auth()->id(),
        ]);

        // Input Ketua ke tabel anggota
        SidangTppKanwilAnggota::create([
            'sidang_tpp_kanwil_id' => $sidang->id,
            'pegawai_id' => $ketua->id,
            'token_akses' => Str::random(40),
            'nama' => $ketua->nama,
            'nip' => $ketua->nip,
            'jabatan' => $ketua->jabatan,
            'no_wa' => $this->getPegawaiWa($ketua),
            'peran_sidang' => 'Ketua',
        ]);

        // Input Sekretaris ke tabel anggota
        if ($sekretaris->id !== $ketua->id) {
            SidangTppKanwilAnggota::create([
                'sidang_tpp_kanwil_id' => $sidang->id,
                'pegawai_id' => $sekretaris->id,
                'token_akses' => Str::random(40),
                'nama' => $sekretaris->nama,
                'nip' => $sekretaris->nip,
                'jabatan' => $sekretaris->jabatan,
                'no_wa' => $this->getPegawaiWa($sekretaris),
                'peran_sidang' => 'Sekretaris',
            ]);
        }

        // Input Seluruh Anggota Sidang
        $anggotaList = KanwilPegawai::whereIn('id', $request->anggota_ids)
            ->whereNotIn('id', [$ketua->id, $sekretaris->id])
            ->get();

        foreach ($anggotaList as $peg) {
            $peran = str_contains(strtolower($peg->jabatan), 'pembimbing kemasyarakatan') ? 'PK Bapas' : 'Anggota';
            SidangTppKanwilAnggota::create([
                'sidang_tpp_kanwil_id' => $sidang->id,
                'pegawai_id' => $peg->id,
                'token_akses' => Str::random(40),
                'nama' => $peg->nama,
                'nip' => $peg->nip,
                'jabatan' => $peg->jabatan,
                'no_wa' => $this->getPegawaiWa($peg),
                'peran_sidang' => $peran,
            ]);
        }

        $sidang->recalculateStats();

        ActivityLog::record(
            'create',
            'Sidang TPP Kanwil',
            "Mendaftarkan Sidang TPP Kantor Wilayah Nomor {$sidang->nomor_sidang} dengan agenda: {$sidang->agenda_judul}",
            $sidang
        );

        return redirect()->route('sidang-tpp-kanwil.show', $sidang->id)
            ->with('success', "Sidang TPP Kantor Wilayah Nomor {$sidang->nomor_sidang} berhasil didaftarkan. Silakan terbitkan nota dinas dan sebar link undangan WhatsApp.");
    }

    /**
     * Helper cari nomor WhatsApp pegawai dari master KanwilPegawai atau User jika ada.
     */
    protected function getPegawaiWa(KanwilPegawai $pegawai): ?string
    {
        if (! empty($pegawai->no_wa)) {
            return $pegawai->no_wa;
        }

        // Cari user yang NIP-nya sama atau namanya sama
        $user = User::where(function ($q) use ($pegawai) {
            if (! empty($pegawai->nip)) {
                $q->where('nip', $pegawai->nip);
            }
        })->orWhere('nama_lengkap', $pegawai->nama)->first();

        return $user?->no_wa ?? null;
    }

    /**
     * Detail Sidang TPP Kanwil: Daftar Hadir, Pendapat, Sebar WA, Generate BA & Disposisi Kakanwil.
     */
    public function show(int $id): View
    {
        $this->checkKanwilAccess();

        $sidang = SidangTppKanwil::with(['anggota.pegawai', 'creator'])->findOrFail($id);
        $sidang->recalculateStats();

        $anggotaList = $sidang->anggota;
        $totalAnggota = $anggotaList->count();
        $totalHadir = $anggotaList->whereIn('status_kehadiran', ['hadir_luring', 'hadir_daring'])->count();
        $totalSudahPendapat = $anggotaList->where('status_pendapat', '!=', 'belum_mengisi')->count();
        $totalWaTerkirim = $anggotaList->where('wa_sent_status', 'terkirim')->count();

        return view('sidang_tpp_kanwil.show', compact(
            'sidang',
            'anggotaList',
            'totalAnggota',
            'totalHadir',
            'totalSudahPendapat',
            'totalWaTerkirim'
        ));
    }

    /**
     * Sebar Link WhatsApp Sekali Klik ke Seluruh Tim Sidang.
     */
    public function broadcastWaAll(int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');

        $sidang = SidangTppKanwil::with('anggota')->findOrFail($id);
        $anggotaList = $sidang->anggota;

        if ($anggotaList->isEmpty()) {
            return back()->with('warning', 'Belum ada anggota sidang yang ditugaskan.');
        }

        $sentCount = 0;
        $failCount = 0;
        $missingWaCount = 0;

        foreach ($anggotaList as $anggota) {
            if (empty($anggota->no_wa)) {
                $missingWaCount++;
                $anggota->update([
                    'wa_sent_status' => 'gagal',
                    'wa_error_message' => 'Nomor WhatsApp belum terdaftar.',
                ]);

                continue;
            }

            $res = WhatsAppService::notifyUndanganSidangTppKanwil($anggota);
            if (! empty($res['success'])) {
                $sentCount++;
            } else {
                $failCount++;
            }
        }

        if ($sidang->status_sidang === 'draft') {
            $sidang->update(['status_sidang' => 'undangan_terbit']);
        }

        ActivityLog::record(
            'broadcast',
            'Sidang TPP Kanwil',
            "Menyebarkan undangan & link pendapat Sidang TPP Kanwil #{$sidang->id} via WhatsApp ({$sentCount} berhasil, {$failCount} gagal)",
            $sidang
        );

        $msg = "Undangan WhatsApp berhasil disebar! Sukses terkirim: {$sentCount} orang.";
        if ($failCount > 0 || $missingWaCount > 0) {
            $msg .= " (Gagal terkirim: {$failCount}, Nomor WA kosong: {$missingWaCount})";
        }

        return back()->with('success', $msg);
    }

    /**
     * Kirim/Kirim Ulang Pesan WhatsApp ke Satu Anggota.
     */
    public function broadcastWaSingle(int $id, int $anggotaId): RedirectResponse
    {
        $this->checkKanwilAccess('full');

        $sidang = SidangTppKanwil::findOrFail($id);
        $anggota = SidangTppKanwilAnggota::where('sidang_tpp_kanwil_id', $sidang->id)->findOrFail($anggotaId);

        if (empty($anggota->no_wa)) {
            return back()->with('error', "Nomor WhatsApp untuk {$anggota->nama} belum diisi.");
        }

        $res = WhatsAppService::notifyUndanganSidangTppKanwil($anggota);
        if (! empty($res['success'])) {
            return back()->with('success', "Undangan WhatsApp berhasil dikirim ke {$anggota->nama} ({$anggota->no_wa}).");
        }

        return back()->with('warning', 'Gagal mengirim WhatsApp: '.($res['message'] ?? 'Gateway tidak merespons'));
    }

    /**
     * Portal Pengisian Presensi & Pendapat Anggota (Akses via Token Unik WhatsApp Tanpa Login).
     */
    public function portalAnggota(string $token): View
    {
        $anggota = SidangTppKanwilAnggota::with('sidangKanwil')->where('token_akses', $token)->firstOrFail();
        $sidang = $anggota->sidangKanwil;

        return view('sidang_tpp_kanwil.portal_anggota', compact('anggota', 'sidang'));
    }

    /**
     * Simpan Konfirmasi Presensi & Pendapat dari Portal Anggota.
     */
    public function submitPortalAnggota(Request $request, string $token): RedirectResponse
    {
        $anggota = SidangTppKanwilAnggota::with('sidangKanwil')->where('token_akses', $token)->firstOrFail();

        $validated = $request->validate([
            'status_kehadiran' => 'required|in:hadir_luring,hadir_daring,izin,sakit,tidak_hadir',
            'keterangan_kehadiran' => 'nullable|string|max:255',
            'status_pendapat' => 'required|in:setuju,setuju_catatan,tolak,tunda',
            'catatan_pendapat' => 'nullable|string',
        ]);

        $anggota->update([
            'status_kehadiran' => $validated['status_kehadiran'],
            'keterangan_kehadiran' => $validated['keterangan_kehadiran'],
            'waktu_kehadiran' => now(),
            'status_pendapat' => $validated['status_pendapat'],
            'catatan_pendapat' => $validated['catatan_pendapat'],
            'waktu_pendapat' => now(),
        ]);

        $anggota->sidangKanwil->recalculateStats();

        // Jika sidang berstatus draft atau undangan terbit, ubah menjadi berlangsung
        if (in_array($anggota->sidangKanwil->status_sidang, ['draft', 'undangan_terbit'])) {
            $anggota->sidangKanwil->update(['status_sidang' => 'berlangsung']);
        }

        return back()->with('submitted', true)
            ->with('success', 'Terima kasih, konfirmasi presensi dan pendapat sidang Anda berhasil tersimpan dalam sistem.');
    }

    /**
     * Download Nota Dinas Undangan Sidang (PDF / Word).
     */
    public function downloadNotaDinas(int $id, string $format = 'pdf'): Response
    {
        $this->checkKanwilAccess();
        $sidang = SidangTppKanwil::with('anggota')->findOrFail($id);

        if ($format === 'doc' || $format === 'word') {
            return TppKanwilDokumenService::generateNotaDinasWord($sidang);
        }

        return TppKanwilDokumenService::generateNotaDinasPdf($sidang);
    }

    /**
     * Download Berita Acara (BA) & Lembar Disposisi Kakanwil Siap Cetak (PDF / Word).
     */
    public function downloadBaDraft(int $id, string $format = 'pdf'): Response
    {
        $this->checkKanwilAccess();
        $sidang = SidangTppKanwil::with('anggota')->findOrFail($id);

        if ($format === 'doc' || $format === 'word') {
            return TppKanwilDokumenService::generateBeritaAcaraWord($sidang);
        }

        return TppKanwilDokumenService::generateBeritaAcaraPdf($sidang);
    }

    /**
     * Download Risalah Hasil Sidang TPP (Word / PDF).
     */
    public function downloadRisalah(int $id, string $format = 'pdf'): Response
    {
        $this->checkKanwilAccess();
        $sidang = SidangTppKanwil::with('anggota')->findOrFail($id);

        if ($format === 'doc' || $format === 'word') {
            return TppKanwilDokumenService::generateRisalahWord($sidang);
        }

        return TppKanwilDokumenService::generateRisalahPdf($sidang);
    }

    /**
     * Download Laporan Pelaksanaan Sidang TPP (Word / PDF).
     */
    public function downloadLaporan(int $id, string $format = 'pdf'): Response
    {
        $this->checkKanwilAccess();
        $sidang = SidangTppKanwil::with('anggota')->findOrFail($id);

        if ($format === 'doc' || $format === 'word') {
            return TppKanwilDokumenService::generateLaporanWord($sidang);
        }

        return TppKanwilDokumenService::generateLaporanPdf($sidang);
    }

    /**
     * Upload Berkas Fisik Risalah Hasil Sidang Bertanda Tangan Basah.
     */
    public function uploadRisalahSigned(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        $request->validate([
            'nomor_risalah' => 'required|string|max:150',
            'file_risalah_signed' => 'required|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        $dir = public_path('uploads/sidang_tpp_kanwil/risalah_signed');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $file = $request->file('file_risalah_signed');
        $filename = 'risalah_signed_'.$sidang->id.'_'.date('Ymd_His').'.'.$file->getClientOriginalExtension();
        $file->move($dir, $filename);
        $filePath = 'uploads/sidang_tpp_kanwil/risalah_signed/'.$filename;

        $sidang->update([
            'nomor_risalah' => $request->nomor_risalah,
            'file_risalah_signed' => $filePath,
        ]);

        ActivityLog::record(
            'upload',
            'Sidang TPP Kanwil',
            "Mengunggah berkas fisik Risalah Hasil Sidang bertanda tangan basah Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return back()->with('success', 'Berkas fisik Risalah Hasil Sidang bertanda tangan basah berhasil diunggah.');
    }

    /**
     * Upload Berkas Fisik Laporan Pelaksanaan Sidang Bertanda Tangan Sah.
     */
    public function uploadLaporanSigned(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        $request->validate([
            'nomor_laporan' => 'required|string|max:150',
            'foto_dokumentasi_1' => 'nullable|image|max:10240',
            'foto_dokumentasi_2' => 'nullable|image|max:10240',
            'file_laporan_signed' => 'required|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        $dir = public_path('uploads/sidang_tpp_kanwil/laporan_signed');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $file = $request->file('file_laporan_signed');
        $filename = 'laporan_signed_'.$sidang->id.'_'.date('Ymd_His').'.'.$file->getClientOriginalExtension();
        $file->move($dir, $filename);
        $filePath = 'uploads/sidang_tpp_kanwil/laporan_signed/'.$filename;

        $updates = [
            'nomor_laporan' => $request->nomor_laporan,
            'file_laporan_signed' => $filePath,
        ];

        $dirFoto = public_path('uploads/sidang_tpp_kanwil/dokumentasi');
        if (! File::isDirectory($dirFoto)) {
            File::makeDirectory($dirFoto, 0755, true, true);
        }

        if ($request->hasFile('foto_dokumentasi_1')) {
            $f = $request->file('foto_dokumentasi_1');
            $fn = 'foto1_'.$sidang->id.'_'.date('Ymd_His').'.'.$f->getClientOriginalExtension();
            $f->move($dirFoto, $fn);
            $updates['foto_dokumentasi_1'] = 'uploads/sidang_tpp_kanwil/dokumentasi/'.$fn;
        }

        if ($request->hasFile('foto_dokumentasi_2')) {
            $f = $request->file('foto_dokumentasi_2');
            $fn = 'foto2_'.$sidang->id.'_'.date('Ymd_His').'.'.$f->getClientOriginalExtension();
            $f->move($dirFoto, $fn);
            $updates['foto_dokumentasi_2'] = 'uploads/sidang_tpp_kanwil/dokumentasi/'.$fn;
        }

        $sidang->update($updates);

        ActivityLog::record(
            'upload',
            'Sidang TPP Kanwil',
            "Mengunggah berkas fisik Laporan Pelaksanaan Sidang bertanda tangan sah Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return back()->with('success', 'Berkas fisik Laporan Pelaksanaan Sidang bertanda tangan sah berhasil diunggah.');
    }

    /**
     * Upload Berkas Fisik Berita Acara Bertanda Tangan Basah.
     */
    public function uploadBaSigned(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        $request->validate([
            'nomor_berita_acara' => 'required|string|max:150',
            'kesimpulan_sidang' => 'nullable|string',
            'file_ba_signed' => 'required|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        $dir = public_path('uploads/sidang_tpp_kanwil/ba_signed');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $file = $request->file('file_ba_signed');
        $filename = 'ba_signed_'.$sidang->id.'_'.date('Ymd_His').'.'.$file->getClientOriginalExtension();
        $file->move($dir, $filename);
        $filePath = 'uploads/sidang_tpp_kanwil/ba_signed/'.$filename;

        $sidang->update([
            'nomor_berita_acara' => $request->nomor_berita_acara,
            'kesimpulan_sidang' => $request->kesimpulan_sidang,
            'file_ba_signed' => $filePath,
            'status_sidang' => 'menunggu_kakanwil',
        ]);

        ActivityLog::record(
            'upload',
            'Sidang TPP Kanwil',
            "Mengunggah berkas fisik Berita Acara bertanda tangan basah Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return back()->with('success', 'Berkas fisik Berita Acara bertanda tangan basah berhasil diunggah. Berkas kini siap diajukan untuk disposisi Kepala Kantor Wilayah.');
    }

    /**
     * Upload Berkas Fisik Lembar Tanggapan / Disposisi Kakanwil.
     */
    public function uploadDisposisiKakanwil(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        $request->validate([
            'tanggapan_kakanwil_status' => 'required|in:disetujui,disetujui_bersyarat,ditolak,perlu_pendalaman',
            'tanggapan_kakanwil_catatan' => 'nullable|string',
            'tanggapan_kakanwil_tanggal' => 'required|date',
            'kakanwil_nama' => 'required|string|max:150',
            'kakanwil_nip' => 'nullable|string|max:50',
            'file_disposisi_kakanwil' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        $filePath = $sidang->file_disposisi_kakanwil;
        if ($request->hasFile('file_disposisi_kakanwil')) {
            $dir = public_path('uploads/sidang_tpp_kanwil/disposisi');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
            $file = $request->file('file_disposisi_kakanwil');
            $filename = 'disposisi_kakanwil_'.$sidang->id.'_'.date('Ymd_His').'.'.$file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $filePath = 'uploads/sidang_tpp_kanwil/disposisi/'.$filename;
        }

        $sidang->update([
            'tanggapan_kakanwil_status' => $request->tanggapan_kakanwil_status,
            'tanggapan_kakanwil_catatan' => $request->tanggapan_kakanwil_catatan,
            'tanggapan_kakanwil_tanggal' => $request->tanggapan_kakanwil_tanggal,
            'kakanwil_nama' => $request->kakanwil_nama,
            'kakanwil_nip' => $request->kakanwil_nip,
            'file_disposisi_kakanwil' => $filePath,
            'status_sidang' => 'selesai',
        ]);

        ActivityLog::record(
            'verify',
            'Sidang TPP Kanwil',
            "Mencatat tanggapan/disposisi fisik Kepala Kantor Wilayah ({$request->tanggapan_kakanwil_status}) untuk Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return back()->with('success', 'Tanggapan dan berkas fisik disposisi Kepala Kantor Wilayah berhasil dicatat. Status sidang dinyatakan Selesai.');
    }

    /**
     * Hapus Sidang TPP Kanwil.
     * Hanya dapat dilakukan oleh Administrator Kantor Wilayah.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $user = auth()->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Akses ditolak. Hanya Administrator Kantor Wilayah yang memiliki wewenang untuk menghapus Sidang TPP Kanwil.');
        }

        $sidang = SidangTppKanwil::findOrFail($id);

        if ($sidang->status_sidang === 'selesai') {
            return back()->with('error', 'Sidang TPP Kanwil yang telah selesai dan memiliki arsip sah tidak dapat dihapus.');
        }

        $nomor = $sidang->nomor_sidang;
        $sidang->delete();

        ActivityLog::record('delete', 'Sidang TPP Kanwil', "Menghapus data Sidang TPP Kanwil {$nomor}");

        return redirect()->route('sidang-tpp-kanwil.index')->with('success', "Sidang TPP Kanwil {$nomor} berhasil dihapus.");
    }

    /**
     * AI Asisten: Generate draft naskah dinas Sidang TPP Kanwil (Laporan, Risalah, Berita Acara).
     */
    public function aiGenerateNaskah(Request $request, int $id): JsonResponse
    {
        $this->checkKanwilAccess('edit');
        $sidang = SidangTppKanwil::with('anggota')->findOrFail($id);

        $request->validate([
            'jenis_dokumen' => 'required|in:laporan,risalah,berita_acara',
            'instruksi_khusus' => 'nullable|string|max:1000',
        ]);

        $result = GeminiAiService::generateNaskahSidangTppKanwil([
            'jenis_dokumen' => $request->jenis_dokumen,
            'sidang' => $sidang,
            'instruksi_khusus' => $request->instruksi_khusus,
        ]);

        return response()->json($result);
    }

    /**
     * Simpan kustomisasi naskah dinas hasil penyelarasan AI / pengeditan operator ke dalam database.
     */
    public function aiSaveNaskah(Request $request, int $id): JsonResponse
    {
        $this->checkKanwilAccess('edit');
        $sidang = SidangTppKanwil::findOrFail($id);

        $request->validate([
            'jenis_dokumen' => 'required|in:laporan,risalah,berita_acara',
            'data' => 'required',
        ]);

        $custom = $sidang->dokumen_custom ?: [];
        $data = is_array($request->data) ? $request->data : json_decode($request->data, true);

        if (! is_array($data)) {
            return response()->json(['success' => false, 'message' => 'Format data naskah tidak valid.'], 422);
        }

        $custom[$request->jenis_dokumen] = $data;
        $sidang->dokumen_custom = $custom;
        $sidang->save();

        ActivityLog::record(
            'update',
            'Sidang TPP Kanwil',
            "Menyimpan kustomisasi naskah dinas ({$request->jenis_dokumen}) untuk Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return response()->json([
            'success' => true,
            'message' => 'Naskah dinas ('.ucfirst(str_replace('_', ' ', $request->jenis_dokumen)).') berhasil disimpan ke dokumen sidang.',
            'dokumen_custom' => $sidang->dokumen_custom,
        ]);
    }

    /**
     * Reset naskah dinas agar kembali ke formula/template standar sistem.
     */
    public function aiResetNaskah(Request $request, int $id): JsonResponse
    {
        $this->checkKanwilAccess('edit');
        $sidang = SidangTppKanwil::findOrFail($id);

        $jenis = $request->input('jenis_dokumen');
        $custom = $sidang->dokumen_custom ?: [];

        if ($jenis && isset($custom[$jenis])) {
            unset($custom[$jenis]);
            $sidang->dokumen_custom = ! empty($custom) ? $custom : null;
            $msg = 'Naskah dinas ('.ucfirst(str_replace('_', ' ', $jenis)).') berhasil di-reset ke standar bawaan dinas.';
        } else {
            $sidang->dokumen_custom = null;
            $msg = 'Seluruh naskah dinas berhasil di-reset ke standar bawaan dinas.';
        }

        $sidang->save();

        ActivityLog::record(
            'update',
            'Sidang TPP Kanwil',
            "Mereset kustomisasi naskah dinas untuk Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return response()->json([
            'success' => true,
            'message' => $msg,
            'dokumen_custom' => $sidang->dokumen_custom,
        ]);
    }
}
