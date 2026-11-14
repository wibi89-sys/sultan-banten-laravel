<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KanwilPegawai;
use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\User;
use App\Services\TppKanwilDokumenService;
use App\Services\WhatsAppService;
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

        $pegawaiList = KanwilPegawai::where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        // Rekomendasi Nomor Sidang Otomatis
        $countThisMonth = SidangTppKanwil::whereYear('tanggal_sidang', date('Y'))
            ->whereMonth('tanggal_sidang', date('m'))
            ->count() + 1;
        $suggestedNomorSidang = 'STPP-KW/'.date('Y/m').'/'.str_pad($countThisMonth, 3, '0', STR_PAD_LEFT);

        return view('sidang_tpp_kanwil.create', compact('pegawaiList', 'suggestedNomorSidang'));
    }

    /**
     * Simpan Sidang TPP Kanwil Baru beserta Tim yang Ditugaskan.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkKanwilAccess('full');

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
            'agenda_kategori' => 'required|string|max:100',
            'agenda_judul' => 'required|string|max:255',
            'agenda_deskripsi' => 'nullable|string',
            'file_bahan_sidang' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:15360',
            'ketua_id' => 'required|exists:kanwil_pegawai,id',
            'sekretaris_id' => 'required|exists:kanwil_pegawai,id',
            'anggota_ids' => 'required|array|min:1',
            'anggota_ids.*' => 'exists:kanwil_pegawai,id',
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
            'agenda_kategori' => $validated['agenda_kategori'],
            'agenda_judul' => $validated['agenda_judul'],
            'agenda_deskripsi' => $validated['agenda_deskripsi'],
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
     * Helper cari nomor WhatsApp pegawai dari tabel User jika ada.
     */
    protected function getPegawaiWa(KanwilPegawai $pegawai): ?string
    {
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
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        if ($sidang->status_sidang === 'selesai') {
            return back()->with('error', 'Sidang TPP Kanwil yang telah selesai dan memiliki arsip sah tidak dapat dihapus.');
        }

        $nomor = $sidang->nomor_sidang;
        $sidang->delete();

        ActivityLog::record('delete', 'Sidang TPP Kanwil', "Menghapus data Sidang TPP Kanwil {$nomor}");

        return redirect()->route('sidang-tpp-kanwil.index')->with('success', "Sidang TPP Kanwil {$nomor} berhasil dihapus.");
    }
}
