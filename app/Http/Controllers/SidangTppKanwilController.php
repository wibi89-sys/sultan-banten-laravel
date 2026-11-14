<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KanwilPegawai;
use App\Models\MutasiNarapidana;
use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\SidangTppKanwilMateri;
use App\Models\SidangTppKanwilMateriPendapat;
use App\Models\TppKanwilSetting;
use App\Models\Upt;
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

        $hasKanwilAccess = $user->hasAccess('sidang_tpp_kanwil', $requiredLevel)
            || ($user->hasAccess('sidang_tpp', $requiredLevel) && $user->isKanwil());

        if (! $hasKanwilAccess) {
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

        // Data integrasi usulan Mutasi Narapidana UPT & Daftar UPT se-Banten
        $mutasiList = MutasiNarapidana::with(['uptAsal', 'uptTujuan'])
            ->whereIn('status', ['diajukan', 'perlu_perbaikan'])
            ->latest('tanggal_surat')
            ->get();
        $uptList = Upt::orderBy('nama_upt')->get();

        return view('sidang_tpp_kanwil.create', compact(
            'pegawaiList',
            'suggestedNomorSidang',
            'suggestedNomorNota',
            'suggestedNomorRisalah',
            'suggestedNomorLaporan',
            'setting',
            'defaultKetua',
            'defaultSekretaris',
            'defaultAnggotaIds',
            'mutasiList',
            'uptList'
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
            'agenda_kategori' => 'nullable|string|max:100',
            'agenda_judul' => 'nullable|string|max:255',
            'agenda_deskripsi' => 'nullable|string',
            'materis' => 'nullable|array',
            'subjek_tipe' => 'nullable|string|in:umum,klien_umroh,mutasi_narapidana,izin_berobat',
            'mutasi_id' => 'nullable|exists:mutasi_narapidana,id',
            'upt_tujuan_rekomendasi_id' => 'nullable',
            'mutasi_upt_tujuan' => 'nullable|string|max:150',
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
            // Field Khusus Mutasi Narapidana & Permintaan Sendiri
            'mutasi_wbp_nama' => 'nullable|string|max:150',
            'mutasi_nomor_register' => 'nullable|string|max:100',
            'mutasi_perkara' => 'nullable|string|max:255',
            'mutasi_lama_pidana' => 'nullable|string|max:100',
            'mutasi_upt_asal_id' => 'nullable|exists:upt,id',
            'mutasi_alasan' => 'nullable|string',
            'nama_pk_bapas' => 'nullable|string|max:150',
            'nip_pk_bapas' => 'nullable|string|max:50',
            'bapas_nama' => 'nullable|string|max:150',
            'rekomendasi_litmas' => 'nullable|string',
            'nomor_ba_tpp_upt' => 'nullable|string|max:150',
            'tanggal_ba_tpp_upt' => 'nullable|date',
            'rekomendasi_tpp_upt' => 'nullable|string',
            'mutasi_penjamin_nama' => 'nullable|string|max:150',
            'mutasi_penjamin_nik' => 'nullable|string|max:30',
            'mutasi_penjamin_hubungan' => 'nullable|string|max:100',
            'mutasi_penjamin_alamat' => 'nullable|string|max:255',
            'mutasi_penjamin_kontak' => 'nullable|string|max:50',
            'file_mutasi_permohonan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_pernyataan_pribadi' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_ba_upt' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_jaminan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_ktp_penjamin' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_litmas' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_reg_f' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_kesehatan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_asesmen' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            // Field Khusus Izin Berobat Keluar Kota
            'berobat_wbp_nama' => 'nullable|string|max:150',
            'berobat_nomor_register' => 'nullable|string|max:100',
            'berobat_perkara' => 'nullable|string|max:255',
            'berobat_upt_asal_id' => 'nullable|exists:upt,id',
            'berobat_diagnosa' => 'nullable|string',
            'berobat_dokter' => 'nullable|string|max:150',
            'berobat_rs_nama' => 'nullable|string|max:150',
            'berobat_rs_kota' => 'nullable|string|max:100',
            'berobat_penjamin_nama' => 'nullable|string|max:150',
            'berobat_penjamin_hubungan' => 'nullable|string|max:100',
            'berobat_penjamin_kontak' => 'nullable|string|max:50',
            'file_medis_rekam' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_medis_rujukan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_medis_jaminan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_medis_ba_upt' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            // Dokumen Tambahan Lainnya (Kustom)
            'dokumen_tambahan_nama' => 'nullable|string|max:150',
            'dokumen_tambahan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip|max:15360',
        ]);

        $hasMateris = $request->filled('materis') && is_array($request->input('materis')) && count($request->input('materis')) > 0;
        if (empty($validated['agenda_judul'])) {
            if ($hasMateris) {
                $firstMateriJudul = $request->input('materis.0.judul_materi');
                $countMateris = count($request->input('materis'));
                $validated['agenda_judul'] = $firstMateriJudul ?: ("Sidang Pembahasan {$countMateris} Agenda");
            } else {
                return back()->withInput()->withErrors(['agenda_judul' => 'Judul agenda atau minimal satu materi sidang wajib diisi.']);
            }
        }
        if (empty($validated['agenda_kategori'])) {
            $validated['agenda_kategori'] = $hasMateris
                ? (str_replace('_', ' ', ucwords($request->input('materis.0.kategori_materi', 'Multi-Agenda'))))
                : 'Umum';
        }

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

        $subjekTipe = $request->input('subjek_tipe');
        if (empty($subjekTipe)) {
            $katLower = strtolower($request->agenda_kategori ?? '');
            if (str_contains($katLower, 'sendiri') || str_contains($katLower, 'pribadi')) {
                $subjekTipe = 'mutasi_keinginan_sendiri';
            } elseif ($request->filled('mutasi_id') || str_contains($katLower, 'mutasi')) {
                $subjekTipe = 'mutasi_narapidana';
            } elseif (str_contains($katLower, 'berobat') && ! str_contains($katLower, 'klien')) {
                $subjekTipe = 'izin_berobat';
            } elseif ($request->boolean('is_klien_bapas') || str_contains($katLower, 'klien') || str_contains($katLower, 'luar negeri') || str_contains($katLower, 'umroh')) {
                $subjekTipe = 'klien_umroh';
            } else {
                $subjekTipe = 'umum';
            }
        }

        $isKlienBapas = $subjekTipe === 'klien_umroh'
            || $request->boolean('is_klien_bapas')
            || str_contains(strtolower($request->agenda_kategori), 'klien')
            || str_contains(strtolower($request->agenda_kategori), 'luar negeri')
            || str_contains(strtolower($request->agenda_kategori), 'bapas')
            || str_contains(strtolower($request->agenda_kategori), 'umroh');

        $dirBerkas = public_path('uploads/sidang_tpp_kanwil/berkas');
        if (! File::isDirectory($dirBerkas)) {
            File::makeDirectory($dirBerkas, 0755, true, true);
        }

        $dokumenTambahan = null;
        if ($request->hasFile('dokumen_tambahan_file')) {
            $f = $request->file('dokumen_tambahan_file');
            $fn = 'tambahan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
            $f->move($dirBerkas, $fn);
            $dokumenTambahan = [
                'nama' => $request->input('dokumen_tambahan_nama', 'Dokumen Tambahan'),
                'file' => 'uploads/sidang_tpp_kanwil/berkas/'.$fn,
            ];
        }

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
                'dokumen_tambahan' => $dokumenTambahan,
            ];
        }

        $narapidanaData = null;
        $uptTujuanId = null;
        if ($subjekTipe === 'mutasi_narapidana' || $subjekTipe === 'mutasi_keinginan_sendiri') {
            $filePermohonan = null;
            if ($request->hasFile('file_mutasi_permohonan')) {
                $f = $request->file('file_mutasi_permohonan');
                $fn = 'mutasi_permohonan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $filePermohonan = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $filePernyataanPribadi = null;
            if ($request->hasFile('file_mutasi_pernyataan_pribadi')) {
                $f = $request->file('file_mutasi_pernyataan_pribadi');
                $fn = 'mutasi_pernyataan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $filePernyataanPribadi = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileBaUpt = null;
            if ($request->hasFile('file_mutasi_ba_upt')) {
                $f = $request->file('file_mutasi_ba_upt');
                $fn = 'mutasi_ba_upt_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileBaUpt = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileJaminan = null;
            if ($request->hasFile('file_mutasi_jaminan')) {
                $f = $request->file('file_mutasi_jaminan');
                $fn = 'mutasi_jaminan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileJaminan = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileKtpPenjamin = null;
            if ($request->hasFile('file_mutasi_ktp_penjamin')) {
                $f = $request->file('file_mutasi_ktp_penjamin');
                $fn = 'mutasi_ktp_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileKtpPenjamin = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileLitmas = null;
            if ($request->hasFile('file_mutasi_litmas')) {
                $f = $request->file('file_mutasi_litmas');
                $fn = 'mutasi_litmas_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileLitmas = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileRegF = null;
            if ($request->hasFile('file_mutasi_reg_f')) {
                $f = $request->file('file_mutasi_reg_f');
                $fn = 'mutasi_regf_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileRegF = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileKesehatan = null;
            if ($request->hasFile('file_mutasi_kesehatan')) {
                $f = $request->file('file_mutasi_kesehatan');
                $fn = 'mutasi_kesehatan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileKesehatan = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileAsesmen = null;
            if ($request->hasFile('file_mutasi_asesmen')) {
                $f = $request->file('file_mutasi_asesmen');
                $fn = 'mutasi_asesmen_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileAsesmen = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }

            $uptAsalNama = null;
            if ($request->filled('mutasi_upt_asal_id')) {
                $uptAsalNama = Upt::find($request->mutasi_upt_asal_id)?->nama_upt;
            }
            $uptInput = $request->input('mutasi_upt_tujuan') ?: $request->input('upt_tujuan_rekomendasi_id');
            [$uptTujuanId, $uptTujuanNama] = $this->resolveUptTujuan($uptInput);

            $narapidanaData = [
                'nama' => $request->input('mutasi_wbp_nama', $request->agenda_judul),
                'nomor_register' => $request->input('mutasi_nomor_register'),
                'tindak_pidana' => $request->input('mutasi_perkara'),
                'lama_pidana' => $request->input('mutasi_lama_pidana'),
                'upt_asal_id' => $request->input('mutasi_upt_asal_id'),
                'upt_asal_nama' => $uptAsalNama,
                'upt_tujuan_id' => $uptTujuanId,
                'upt_tujuan_nama' => $uptTujuanNama,
                'alasan_pemindahan' => $request->input('mutasi_alasan', 'Keinginan sendiri mendekati keluarga'),
                'nama_pk_bapas' => $request->input('nama_pk_bapas'),
                'nip_pk_bapas' => $request->input('nip_pk_bapas'),
                'bapas_nama' => $request->input('bapas_nama'),
                'rekomendasi_litmas' => $request->input('rekomendasi_litmas'),
                'pk_bapas' => [
                    'nama' => $request->input('nama_pk_bapas'),
                    'nip' => $request->input('nip_pk_bapas'),
                    'bapas' => $request->input('bapas_nama'),
                    'rekomendasi' => $request->input('rekomendasi_litmas'),
                ],
                'nomor_ba_tpp_upt' => $request->input('nomor_ba_tpp_upt'),
                'tanggal_ba_tpp_upt' => $request->input('tanggal_ba_tpp_upt'),
                'rekomendasi_tpp_upt' => $request->input('rekomendasi_tpp_upt'),
                'tpp_upt' => [
                    'nomor_ba' => $request->input('nomor_ba_tpp_upt'),
                    'tanggal_ba' => $request->input('tanggal_ba_tpp_upt'),
                    'rekomendasi' => $request->input('rekomendasi_tpp_upt'),
                ],
                'penjamin_nama' => $request->input('mutasi_penjamin_nama'),
                'penjamin_nik' => $request->input('mutasi_penjamin_nik'),
                'penjamin_hubungan' => $request->input('mutasi_penjamin_hubungan'),
                'penjamin_alamat' => $request->input('mutasi_penjamin_alamat'),
                'penjamin_kontak' => $request->input('mutasi_penjamin_kontak'),
                'penjamin' => [
                    'nama' => $request->input('mutasi_penjamin_nama'),
                    'nik' => $request->input('mutasi_penjamin_nik'),
                    'hubungan' => $request->input('mutasi_penjamin_hubungan'),
                    'alamat' => $request->input('mutasi_penjamin_alamat'),
                    'telepon' => $request->input('mutasi_penjamin_kontak'),
                ],
                'berkas' => [
                    'file_permohonan' => $filePermohonan,
                    'file_pernyataan_pribadi' => $filePernyataanPribadi,
                    'pernyataan_pribadi' => $filePernyataanPribadi,
                    'file_ba_upt' => $fileBaUpt,
                    'ba_upt' => $fileBaUpt,
                    'file_jaminan' => $fileJaminan,
                    'surat_jaminan' => $fileJaminan,
                    'file_ktp_penjamin' => $fileKtpPenjamin,
                    'ktp_penjamin' => $fileKtpPenjamin,
                    'file_litmas' => $fileLitmas,
                    'litmas' => $fileLitmas,
                    'file_reg_f' => $fileRegF,
                    'register_f' => $fileRegF,
                    'file_kesehatan' => $fileKesehatan,
                    'kesehatan' => $fileKesehatan,
                    'file_asesmen' => $fileAsesmen,
                    'asesmen' => $fileAsesmen,
                    'dokumen_tambahan' => $dokumenTambahan,
                ],
            ];
        } elseif ($subjekTipe === 'izin_berobat') {
            $fileRekam = null;
            if ($request->hasFile('file_medis_rekam')) {
                $f = $request->file('file_medis_rekam');
                $fn = 'medis_rekam_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileRekam = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileRujukan = null;
            if ($request->hasFile('file_medis_rujukan')) {
                $f = $request->file('file_medis_rujukan');
                $fn = 'medis_rujukan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileRujukan = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileMedisJaminan = null;
            if ($request->hasFile('file_medis_jaminan')) {
                $f = $request->file('file_medis_jaminan');
                $fn = 'medis_jaminan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileMedisJaminan = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            $fileBaUpt = null;
            if ($request->hasFile('file_medis_ba_upt')) {
                $f = $request->file('file_medis_ba_upt');
                $fn = 'medis_ba_upt_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $fileBaUpt = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }

            $uptAsalNama = null;
            if ($request->filled('berobat_upt_asal_id')) {
                $uptAsalNama = Upt::find($request->berobat_upt_asal_id)?->nama_upt;
            }

            $narapidanaData = [
                'nama' => $request->input('berobat_wbp_nama', $request->agenda_judul),
                'nomor_register' => $request->input('berobat_nomor_register'),
                'tindak_pidana' => $request->input('berobat_perkara'),
                'upt_asal_id' => $request->input('berobat_upt_asal_id'),
                'upt_asal_nama' => $uptAsalNama,
                'diagnosa_medis' => $request->input('berobat_diagnosa'),
                'dokter_penanggung_jawab' => $request->input('berobat_dokter'),
                'rs_rujukan_nama' => $request->input('berobat_rs_nama'),
                'rs_rujukan_kota' => $request->input('berobat_rs_kota'),
                'penjamin_nama' => $request->input('berobat_penjamin_nama'),
                'penjamin_hubungan' => $request->input('berobat_penjamin_hubungan'),
                'penjamin_kontak' => $request->input('berobat_penjamin_kontak'),
                'berkas' => [
                    'file_rekam_medis' => $fileRekam,
                    'file_rujukan' => $fileRujukan,
                    'file_jaminan' => $fileMedisJaminan,
                    'file_ba_upt' => $fileBaUpt,
                    'dokumen_tambahan' => $dokumenTambahan,
                ],
            ];
        }

        $countTotal = SidangTppKanwil::count() + 1;
        $nomorRisalah = $request->input('nomor_risalah') ?: (($setting->nomor_risalah_prefix ?? 'WP.12.PK.06.05-').str_pad($countTotal, 2, '0', STR_PAD_LEFT));
        $nomorLaporan = $request->input('nomor_laporan') ?: (($setting->nomor_laporan_prefix ?? 'WP.12.PK.06.06-').str_pad($countTotal, 2, '0', STR_PAD_LEFT));

        $sidang = SidangTppKanwil::create([
            'nomor_sidang' => $validated['nomor_sidang'],
            'tanggal_sidang' => $validated['tanggal_sidang'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'tempat' => $validated['tempat'],
            'metode' => $validated['metode'],
            'link_daring' => $validated['link_daring'] ?? null,
            'meeting_id' => $validated['meeting_id'] ?? null,
            'passcode' => $validated['passcode'] ?? null,
            'nomor_nota_dinas' => $validated['nomor_nota_dinas'] ?? null,
            'tanggal_nota_dinas' => $validated['tanggal_nota_dinas'] ?? null,
            'nomor_risalah' => $nomorRisalah,
            'nomor_laporan' => $nomorLaporan,
            'agenda_kategori' => $validated['agenda_kategori'],
            'agenda_judul' => $validated['agenda_judul'],
            'agenda_deskripsi' => $validated['agenda_deskripsi'] ?? null,
            'subjek_tipe' => $subjekTipe,
            'mutasi_id' => $request->mutasi_id,
            'upt_tujuan_rekomendasi_id' => $uptTujuanId,
            'is_klien_bapas' => $isKlienBapas,
            'klien_data' => $klienData,
            'narapidana_data' => $narapidanaData,
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

        // Simpan daftar materi sidang jika dikirimkan sebagai array materi
        if ($request->filled('materis') && is_array($request->input('materis'))) {
            foreach ($request->input('materis') as $idx => $mVal) {
                if (empty($mVal['judul_materi'])) {
                    continue;
                }

                $mKategori = $mVal['kategori_materi'] ?? 'lainnya';
                $mBahanPath = null;
                if ($request->hasFile("materis.{$idx}.file_bahan_sidang")) {
                    $dirBahan = public_path('uploads/sidang_tpp_kanwil/bahan');
                    if (! File::isDirectory($dirBahan)) {
                        File::makeDirectory($dirBahan, 0755, true, true);
                    }
                    $f = $request->file("materis.{$idx}.file_bahan_sidang");
                    $fn = 'bahan_materi_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                    $f->move($dirBahan, $fn);
                    $mBahanPath = 'uploads/sidang_tpp_kanwil/bahan/'.$fn;
                }

                $mDokumenTambahan = null;
                if ($request->hasFile("materis.{$idx}.dokumen_tambahan_file")) {
                    $f = $request->file("materis.{$idx}.dokumen_tambahan_file");
                    $fn = 'tambahan_materi_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                    $f->move($dirBerkas, $fn);
                    $mDokumenTambahan = [
                        'nama' => $mVal['dokumen_tambahan_nama'] ?? 'Dokumen Tambahan',
                        'file' => 'uploads/sidang_tpp_kanwil/berkas/'.$fn,
                    ];
                }

                $mNarapidanaData = null;
                $mKlienData = null;
                $mUptTujuanId = null;

                if ($mKategori === 'mutasi_narapidana' || $mKategori === 'mutasi_keinginan_sendiri') {
                    $berkasMutasi = [];
                    foreach (['file_mutasi_permohonan', 'file_mutasi_pernyataan_pribadi', 'file_mutasi_ba_upt', 'file_mutasi_jaminan', 'file_mutasi_ktp_penjamin', 'file_mutasi_litmas', 'file_mutasi_reg_f', 'file_mutasi_kesehatan', 'file_mutasi_asesmen'] as $fField) {
                        if ($request->hasFile("materis.{$idx}.{$fField}")) {
                            $f = $request->file("materis.{$idx}.{$fField}");
                            $fn = str_replace('file_mutasi_', '', $fField).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                            $f->move($dirBerkas, $fn);
                            $berkasMutasi[str_replace('file_mutasi_', 'file_', $fField)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                            $berkasMutasi[str_replace('file_mutasi_', '', $fField)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                        }
                    }
                    if ($mDokumenTambahan) {
                        $berkasMutasi['dokumen_tambahan'] = $mDokumenTambahan;
                    }

                    $uptAsalNama = ! empty($mVal['mutasi_upt_asal_id']) ? Upt::find($mVal['mutasi_upt_asal_id'])?->nama_upt : null;
                    $mUptInput = $mVal['mutasi_upt_tujuan'] ?? ($mVal['upt_tujuan_rekomendasi_id'] ?? null);
                    [$mUptTujuanId, $mUptTujuanNama] = $this->resolveUptTujuan($mUptInput);

                    $mNarapidanaData = [
                        'nama' => $mVal['mutasi_wbp_nama'] ?? $mVal['judul_materi'],
                        'nomor_register' => $mVal['mutasi_nomor_register'] ?? null,
                        'tindak_pidana' => $mVal['mutasi_perkara'] ?? null,
                        'lama_pidana' => $mVal['mutasi_lama_pidana'] ?? null,
                        'upt_asal_id' => $mVal['mutasi_upt_asal_id'] ?? null,
                        'upt_asal_nama' => $uptAsalNama,
                        'upt_tujuan_id' => $mUptTujuanId,
                        'upt_tujuan_nama' => $mUptTujuanNama,
                        'alasan_pemindahan' => $mVal['mutasi_alasan'] ?? null,
                        'nama_pk_bapas' => $mVal['nama_pk_bapas'] ?? null,
                        'nip_pk_bapas' => $mVal['nip_pk_bapas'] ?? null,
                        'bapas_nama' => $mVal['bapas_nama'] ?? null,
                        'rekomendasi_litmas' => $mVal['rekomendasi_litmas'] ?? null,
                        'pk_bapas' => [
                            'nama' => $mVal['nama_pk_bapas'] ?? null,
                            'nip' => $mVal['nip_pk_bapas'] ?? null,
                            'bapas' => $mVal['bapas_nama'] ?? null,
                            'rekomendasi' => $mVal['rekomendasi_litmas'] ?? null,
                        ],
                        'nomor_ba_tpp_upt' => $mVal['nomor_ba_tpp_upt'] ?? null,
                        'tanggal_ba_tpp_upt' => $mVal['tanggal_ba_tpp_upt'] ?? null,
                        'rekomendasi_tpp_upt' => $mVal['rekomendasi_tpp_upt'] ?? null,
                        'tpp_upt' => [
                            'nomor_ba' => $mVal['nomor_ba_tpp_upt'] ?? null,
                            'tanggal_ba' => $mVal['tanggal_ba_tpp_upt'] ?? null,
                            'rekomendasi' => $mVal['rekomendasi_tpp_upt'] ?? null,
                        ],
                        'penjamin_nama' => $mVal['mutasi_penjamin_nama'] ?? null,
                        'penjamin_nik' => $mVal['mutasi_penjamin_nik'] ?? null,
                        'penjamin_hubungan' => $mVal['mutasi_penjamin_hubungan'] ?? null,
                        'penjamin_alamat' => $mVal['mutasi_penjamin_alamat'] ?? null,
                        'penjamin_kontak' => $mVal['mutasi_penjamin_kontak'] ?? null,
                        'penjamin' => [
                            'nama' => $mVal['mutasi_penjamin_nama'] ?? null,
                            'nik' => $mVal['mutasi_penjamin_nik'] ?? null,
                            'hubungan' => $mVal['mutasi_penjamin_hubungan'] ?? null,
                            'alamat' => $mVal['mutasi_penjamin_alamat'] ?? null,
                            'telepon' => $mVal['mutasi_penjamin_kontak'] ?? null,
                        ],
                        'berkas' => $berkasMutasi,
                    ];
                } elseif ($mKategori === 'izin_berobat') {
                    $berkasMedis = [];
                    foreach (['file_medis_rekam', 'file_medis_rujukan', 'file_medis_jaminan', 'file_medis_ba_upt'] as $fField) {
                        if ($request->hasFile("materis.{$idx}.{$fField}")) {
                            $f = $request->file("materis.{$idx}.{$fField}");
                            $fn = str_replace('file_medis_', 'medis_', $fField).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                            $f->move($dirBerkas, $fn);
                            $berkasMedis[str_replace('file_medis_', 'file_', $fField)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                        }
                    }
                    if ($mDokumenTambahan) {
                        $berkasMedis['dokumen_tambahan'] = $mDokumenTambahan;
                    }

                    $uptAsalNama = ! empty($mVal['berobat_upt_asal_id']) ? Upt::find($mVal['berobat_upt_asal_id'])?->nama_upt : null;

                    $mNarapidanaData = [
                        'nama' => $mVal['berobat_wbp_nama'] ?? $mVal['judul_materi'],
                        'nomor_register' => $mVal['berobat_nomor_register'] ?? null,
                        'tindak_pidana' => $mVal['berobat_perkara'] ?? null,
                        'upt_asal_id' => $mVal['berobat_upt_asal_id'] ?? null,
                        'upt_asal_nama' => $uptAsalNama,
                        'diagnosa_medis' => $mVal['berobat_diagnosa'] ?? null,
                        'dokter_penanggung_jawab' => $mVal['berobat_dokter'] ?? null,
                        'rs_rujukan_nama' => $mVal['berobat_rs_nama'] ?? null,
                        'rs_rujukan_kota' => $mVal['berobat_rs_kota'] ?? null,
                        'penjamin_nama' => $mVal['berobat_penjamin_nama'] ?? null,
                        'penjamin_hubungan' => $mVal['berobat_penjamin_hubungan'] ?? null,
                        'penjamin_kontak' => $mVal['berobat_penjamin_kontak'] ?? null,
                        'berkas' => $berkasMedis,
                    ];
                } elseif ($mKategori === 'klien_bapas') {
                    $berkasKlien = [];
                    foreach (['file_litmas_bapas', 'file_surat_bapas', 'file_bukti_travel', 'file_surat_jaminan'] as $fField) {
                        if ($request->hasFile("materis.{$idx}.{$fField}")) {
                            $f = $request->file("materis.{$idx}.{$fField}");
                            $fn = str_replace('file_', 'klien_', $fField).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                            $f->move($dirBerkas, $fn);
                            $berkasKlien[$fField] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                        }
                    }
                    if ($mDokumenTambahan) {
                        $berkasKlien['dokumen_tambahan'] = $mDokumenTambahan;
                    }

                    $mKlienData = [
                        'nama_klien' => $mVal['klien_nama'] ?? $mVal['judul_materi'],
                        'bin' => $mVal['klien_bin'] ?? null,
                        'no_register_bapas' => $mVal['klien_no_register'] ?? null,
                        'bapas_nama' => $mVal['klien_bapas_nama'] ?? null,
                        'nomor_surat_bapas' => $mVal['klien_nomor_surat_bapas'] ?? null,
                        'tanggal_surat_bapas' => $mVal['klien_tanggal_surat_bapas'] ?? null,
                        'nama_pk' => $mVal['klien_nama_pk'] ?? null,
                        'program_integrasi' => $mVal['klien_program'] ?? null,
                        'keperluan_ln' => $mVal['klien_keperluan_ln'] ?? null,
                        'negara_tujuan' => $mVal['klien_negara_tujuan'] ?? null,
                        'tgl_berangkat' => $mVal['klien_tgl_berangkat'] ?? null,
                        'tgl_kembali' => $mVal['klien_tgl_kembali'] ?? null,
                        'durasi_hari' => $mVal['klien_durasi_hari'] ?? null,
                        'penjamin_nama' => $mVal['klien_penjamin_nama'] ?? null,
                        'penjamin_hubungan' => $mVal['klien_penjamin_hubungan'] ?? null,
                        'penjamin_telepon' => $mVal['klien_penjamin_telepon'] ?? null,
                        'berkas' => $berkasKlien,
                    ];
                }

                SidangTppKanwilMateri::create([
                    'sidang_tpp_kanwil_id' => $sidang->id,
                    'urutan' => $idx + 1,
                    'kategori_materi' => $mKategori,
                    'judul_materi' => $mVal['judul_materi'],
                    'deskripsi_materi' => $mVal['deskripsi_materi'] ?? null,
                    'mutasi_id' => ! empty($mVal['mutasi_id']) ? $mVal['mutasi_id'] : null,
                    'upt_tujuan_rekomendasi_id' => $mUptTujuanId,
                    'narapidana_data' => $mNarapidanaData,
                    'klien_data' => $mKlienData,
                    'file_bahan_sidang' => $mBahanPath,
                    'dokumen_tambahan' => $mDokumenTambahan,
                ]);

                if (! empty($mVal['mutasi_id'])) {
                    MutasiNarapidana::find($mVal['mutasi_id'])?->update(['sidang_tpp_kanwil_id' => $sidang->id]);
                }
            }
        } else {
            // Fallback / single materi dari input form utama
            SidangTppKanwilMateri::create([
                'sidang_tpp_kanwil_id' => $sidang->id,
                'urutan' => 1,
                'kategori_materi' => $subjekTipe,
                'judul_materi' => $validated['agenda_judul'],
                'deskripsi_materi' => $validated['agenda_deskripsi'] ?? null,
                'mutasi_id' => $request->mutasi_id,
                'upt_tujuan_rekomendasi_id' => $uptTujuanId,
                'narapidana_data' => $narapidanaData,
                'klien_data' => $klienData,
                'file_bahan_sidang' => $fileBahanPath,
                'dokumen_tambahan' => $dokumenTambahan,
            ]);
        }

        // Hubungkan usulan mutasi UPT jika ada
        if (! empty($sidang->mutasi_id)) {
            $mutasiToLink = MutasiNarapidana::find($sidang->mutasi_id);
            if ($mutasiToLink) {
                $mutasiToLink->update([
                    'sidang_tpp_kanwil_id' => $sidang->id,
                ]);
            }
        }

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

        // Cari user yang namanya sama
        $user = User::where('nama_lengkap', $pegawai->nama)->first();

        return $user?->no_wa ?? null;
    }

    /**
     * Detail Sidang TPP Kanwil: Daftar Hadir, Pendapat, Sebar WA, Generate BA & Disposisi Kakanwil.
     */
    public function show(int $id): View
    {
        $this->checkKanwilAccess();

        $sidang = SidangTppKanwil::with([
            'anggota.pegawai',
            'materis.mutasi.uptAsal',
            'materis.uptTujuanRekomendasi',
            'materis.pendapats.anggota',
            'creator',
        ])->findOrFail($id);
        $sidang->recalculateStats();

        $anggotaList = $sidang->anggota;
        $totalAnggota = $anggotaList->count();
        $totalHadir = $anggotaList->whereIn('status_kehadiran', ['hadir_luring', 'hadir_daring'])->count();
        $totalSudahPendapat = $anggotaList->where('status_pendapat', '!=', 'belum_mengisi')->count();
        $totalWaTerkirim = $anggotaList->where('wa_sent_status', 'terkirim')->count();

        $mutasiList = MutasiNarapidana::with(['uptAsal', 'uptTujuan'])
            ->whereIn('status', ['diajukan', 'perlu_perbaikan'])
            ->latest('tanggal_surat')
            ->get();
        $uptList = Upt::orderBy('nama_upt')->get();

        return view('sidang_tpp_kanwil.show', compact(
            'sidang',
            'anggotaList',
            'totalAnggota',
            'totalHadir',
            'totalSudahPendapat',
            'totalWaTerkirim',
            'mutasiList',
            'uptList'
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
        $anggota = SidangTppKanwilAnggota::with([
            'sidangKanwil.materis.mutasi.uptAsal',
            'sidangKanwil.materis.uptTujuanRekomendasi',
            'materiPendapats',
        ])->where('token_akses', $token)->firstOrFail();
        $sidang = $anggota->sidangKanwil;

        return view('sidang_tpp_kanwil.portal_anggota', compact('anggota', 'sidang'));
    }

    /**
     * Simpan Konfirmasi Presensi & Pendapat dari Portal Anggota (termasuk catatan khusus per materi).
     */
    public function submitPortalAnggota(Request $request, string $token): RedirectResponse
    {
        $anggota = SidangTppKanwilAnggota::with('sidangKanwil.materis')->where('token_akses', $token)->firstOrFail();

        $validated = $request->validate([
            'status_kehadiran' => 'required|in:hadir_luring,hadir_daring,izin,sakit,tidak_hadir',
            'keterangan_kehadiran' => 'nullable|string|max:255',
            'status_pendapat' => 'nullable|in:setuju,setuju_catatan,tolak,tunda',
            'catatan_pendapat' => 'nullable|string',
            'materi_pendapat' => 'nullable|array',
            'materi_pendapat.*.status' => 'nullable|in:setuju,setuju_catatan,tolak,tunda',
            'materi_pendapat.*.catatan' => 'nullable|string',
        ]);

        $hasTolak = false;
        $hasCatatan = false;
        $hasTunda = false;
        $hasSetuju = false;

        // Simpan pendapat dan catatan khusus per materi sidang
        if ($request->has('materi_pendapat') && is_array($request->input('materi_pendapat'))) {
            foreach ($request->input('materi_pendapat') as $materiId => $mVal) {
                $statusP = $mVal['status'] ?? null;
                $catatanP = $mVal['catatan'] ?? null;

                if (! empty($statusP)) {
                    SidangTppKanwilMateriPendapat::updateOrCreate(
                        [
                            'sidang_tpp_kanwil_materi_id' => (int) $materiId,
                            'sidang_tpp_kanwil_anggota_id' => $anggota->id,
                        ],
                        [
                            'status_pendapat' => $statusP,
                            'catatan_pendapat' => $catatanP,
                        ]
                    );

                    if ($statusP === 'tolak') {
                        $hasTolak = true;
                    } elseif ($statusP === 'setuju_catatan') {
                        $hasCatatan = true;
                    } elseif ($statusP === 'tunda') {
                        $hasTunda = true;
                    } elseif ($statusP === 'setuju') {
                        $hasSetuju = true;
                    }
                }
            }
        }

        // Tentukan status pendapat umum sidang
        $statusPendapat = $validated['status_pendapat'] ?? null;
        if (empty($statusPendapat)) {
            if ($hasTolak) {
                $statusPendapat = 'tolak';
            } elseif ($hasCatatan) {
                $statusPendapat = 'setuju_catatan';
            } elseif ($hasTunda) {
                $statusPendapat = 'tunda';
            } elseif ($hasSetuju) {
                $statusPendapat = 'setuju';
            } else {
                $statusPendapat = 'setuju';
            }
        }

        $anggota->update([
            'status_kehadiran' => $validated['status_kehadiran'],
            'keterangan_kehadiran' => $validated['keterangan_kehadiran'] ?? null,
            'waktu_kehadiran' => now(),
            'status_pendapat' => $statusPendapat,
            'catatan_pendapat' => $validated['catatan_pendapat'] ?? null,
            'waktu_pendapat' => now(),
        ]);

        $anggota->sidangKanwil->recalculateStats();

        // Jika sidang berstatus draft atau undangan terbit, ubah menjadi berlangsung
        if (in_array($anggota->sidangKanwil->status_sidang, ['draft', 'undangan_terbit'])) {
            $anggota->sidangKanwil->update(['status_sidang' => 'berlangsung']);
        }

        return back()->with('submitted', true)
            ->with('success', 'Terima kasih, konfirmasi presensi dan catatan pendapat sidang per materi Anda berhasil tersimpan dalam sistem.');
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
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canEdit($user)) {
            abort(403, 'Akses ditolak. Sidang TPP Kanwil telah diverifikasi terakhir oleh Kepala Kantor Wilayah sehingga berkas terkunci dan hanya dapat diubah oleh Administrator.');
        }

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
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canEdit($user)) {
            abort(403, 'Akses ditolak. Sidang TPP Kanwil telah diverifikasi terakhir oleh Kepala Kantor Wilayah sehingga berkas terkunci dan hanya dapat diubah oleh Administrator.');
        }

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
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canEdit($user)) {
            abort(403, 'Akses ditolak. Sidang TPP Kanwil telah diverifikasi terakhir oleh Kepala Kantor Wilayah sehingga berkas terkunci dan hanya dapat diubah oleh Administrator.');
        }

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
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if ($sidang->isVerifiedKakanwil() && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak. Sidang TPP Kanwil telah memiliki disposisi final dari Kepala Kantor Wilayah. Perubahan hanya dapat dilakukan oleh Administrator.');
        }

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

        // Sinkronisasi Dua Arah ke Seluruh Modul Mutasi Narapidana yang Terkait di Sidang Ini
        $mutasiIds = $sidang->materis()->whereNotNull('mutasi_id')->pluck('mutasi_id')->toArray();
        if (! empty($sidang->mutasi_id) && ! in_array($sidang->mutasi_id, $mutasiIds)) {
            $mutasiIds[] = $sidang->mutasi_id;
        }

        $linkedMutasiIds = MutasiNarapidana::where('sidang_tpp_kanwil_id', $sidang->id)->pluck('id')->toArray();
        $allMutasiIds = array_unique(array_filter(array_merge($mutasiIds, $linkedMutasiIds)));

        foreach ($allMutasiIds as $mId) {
            $mutasi = MutasiNarapidana::find($mId);
            if (! $mutasi) {
                continue;
            }

            $materiTerkait = $sidang->materis()->where('mutasi_id', $mId)->first();
            $uptTujuanId = $materiTerkait?->upt_tujuan_rekomendasi_id ?: $sidang->upt_tujuan_rekomendasi_id;

            $statusMutasi = match ($request->tanggapan_kakanwil_status) {
                'disetujui' => 'disetujui',
                'ditolak' => 'ditolak',
                'disetujui_bersyarat', 'perlu_pendalaman' => 'perlu_perbaikan',
                default => $mutasi->status,
            };

            $catatanTambahan = "[Disposisi Ka. Kanwil Sidang TPP #{$sidang->nomor_sidang} ({$request->tanggapan_kakanwil_tanggal}) - Status: ".strtoupper($request->tanggapan_kakanwil_status).']';
            if (! empty($request->tanggapan_kakanwil_catatan)) {
                $catatanTambahan .= "\nCatatan: ".$request->tanggapan_kakanwil_catatan;
            }

            $updateMutasiData = [
                'status' => $statusMutasi,
                'sidang_tpp_kanwil_id' => $sidang->id,
                'tanggal_verifikasi' => $request->tanggapan_kakanwil_tanggal,
                'catatan_kanwil' => ! empty($mutasi->catatan_kanwil)
                    ? $mutasi->catatan_kanwil."\n\n".$catatanTambahan
                    : $catatanTambahan,
            ];

            if ($filePath) {
                $updateMutasiData['file_persetujuan_kanwil'] = $filePath;
            }

            if (! empty($uptTujuanId)) {
                $updateMutasiData['upt_tujuan_id'] = $uptTujuanId;
            }

            $mutasi->update($updateMutasiData);

            ActivityLog::record(
                'sync',
                'Mutasi Narapidana',
                "Sinkronisasi otomatis hasil Sidang TPP Kanwil #{$sidang->nomor_sidang} ke Usulan Mutasi Narapidana #{$mutasi->id} (Status: {$statusMutasi})",
                $mutasi
            );
        }

        ActivityLog::record(
            'verify',
            'Sidang TPP Kanwil',
            "Mencatat tanggapan/disposisi fisik Kepala Kantor Wilayah ({$request->tanggapan_kakanwil_status}) untuk Sidang TPP Kanwil #{$sidang->id}",
            $sidang
        );

        return back()->with('success', 'Tanggapan dan berkas fisik disposisi Kepala Kantor Wilayah berhasil dicatat. Status sidang dinyatakan Selesai.');
    }

    /**
     * Perbarui Data dan Jadwal Sidang TPP Kanwil.
     * Dapat dilakukan oleh user yang menginput atau Administrator sebelum diverifikasi terakhir Ka Kanwil.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canEdit($user)) {
            if ($sidang->isVerifiedKakanwil()) {
                abort(403, 'Akses ditolak. Sidang TPP Kanwil yang telah diverifikasi terakhir oleh Kepala Kantor Wilayah terkunci dan hanya dapat diubah oleh Administrator.');
            }

            abort(403, 'Akses ditolak. Hanya user yang menginput sidang atau Administrator yang memiliki wewenang untuk mengubah data Sidang TPP Kanwil ini.');
        }

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
            'nomor_berita_acara' => 'nullable|string|max:150',
            'agenda_kategori' => 'required|string|max:100',
            'agenda_judul' => 'required|string|max:255',
            'agenda_deskripsi' => 'nullable|string',
            'kesimpulan_sidang' => 'nullable|string',
            'subjek_tipe' => 'nullable|string|in:umum,klien_umroh,mutasi_narapidana,izin_berobat',
            'mutasi_id' => 'nullable|exists:mutasi_narapidana,id',
            'upt_tujuan_rekomendasi_id' => 'nullable',
            'mutasi_upt_tujuan' => 'nullable|string|max:150',
            'file_bahan_sidang' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:15360',
            // Field Mutasi
            'mutasi_wbp_nama' => 'nullable|string|max:150',
            'mutasi_nomor_register' => 'nullable|string|max:100',
            'mutasi_perkara' => 'nullable|string|max:255',
            'mutasi_lama_pidana' => 'nullable|string|max:100',
            'mutasi_upt_asal_id' => 'nullable|exists:upt,id',
            'mutasi_alasan' => 'nullable|string',
            'mutasi_penjamin_nama' => 'nullable|string|max:150',
            'mutasi_penjamin_hubungan' => 'nullable|string|max:100',
            'mutasi_penjamin_alamat' => 'nullable|string|max:255',
            'mutasi_penjamin_kontak' => 'nullable|string|max:50',
            'file_mutasi_permohonan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_jaminan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_litmas' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_reg_f' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_asesmen' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            // Field Medis
            'berobat_wbp_nama' => 'nullable|string|max:150',
            'berobat_nomor_register' => 'nullable|string|max:100',
            'berobat_perkara' => 'nullable|string|max:255',
            'berobat_upt_asal_id' => 'nullable|exists:upt,id',
            'berobat_diagnosa' => 'nullable|string',
            'berobat_dokter' => 'nullable|string|max:150',
            'berobat_rs_nama' => 'nullable|string|max:150',
            'berobat_rs_kota' => 'nullable|string|max:100',
            'berobat_penjamin_nama' => 'nullable|string|max:150',
            'berobat_penjamin_hubungan' => 'nullable|string|max:100',
            'berobat_penjamin_kontak' => 'nullable|string|max:50',
            'file_medis_rekam' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_medis_rujukan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_medis_jaminan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_medis_ba_upt' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            // Dokumen Tambahan
            'dokumen_tambahan_nama' => 'nullable|string|max:150',
            'dokumen_tambahan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip|max:15360',
        ]);

        if ($request->hasFile('file_bahan_sidang')) {
            $dirBahan = public_path('uploads/sidang_tpp_kanwil/bahan');
            if (! File::isDirectory($dirBahan)) {
                File::makeDirectory($dirBahan, 0755, true, true);
            }
            $fileBahan = $request->file('file_bahan_sidang');
            $filenameBahan = 'bahan_'.date('Ymd_His').'_'.Str::random(6).'.'.$fileBahan->getClientOriginalExtension();
            $fileBahan->move($dirBahan, $filenameBahan);
            $validated['file_bahan_sidang'] = 'uploads/sidang_tpp_kanwil/bahan/'.$filenameBahan;
        }

        $dirBerkas = public_path('uploads/sidang_tpp_kanwil/berkas');
        if (! File::isDirectory($dirBerkas)) {
            File::makeDirectory($dirBerkas, 0755, true, true);
        }

        $subjekTipe = $request->input('subjek_tipe', $sidang->subjek_tipe ?? 'umum');
        $validated['subjek_tipe'] = $subjekTipe;
        $validated['mutasi_id'] = $request->input('mutasi_id', $sidang->mutasi_id);

        $uptInput = $request->filled('mutasi_upt_tujuan') ? $request->input('mutasi_upt_tujuan') : ($request->filled('upt_tujuan_rekomendasi_id') ? $request->input('upt_tujuan_rekomendasi_id') : null);
        if ($uptInput !== null && trim((string) $uptInput) !== '') {
            [$uptTujuanId, $uptTujuanNama] = $this->resolveUptTujuan($uptInput);
            $validated['upt_tujuan_rekomendasi_id'] = $uptTujuanId;
        } else {
            $uptTujuanId = $sidang->upt_tujuan_rekomendasi_id;
            $uptTujuanNama = $sidang->narapidana_data['upt_tujuan_nama'] ?? null;
            $validated['upt_tujuan_rekomendasi_id'] = $uptTujuanId;
        }

        $narapidanaData = $sidang->narapidana_data ?? [];
        $dokumenTambahan = $narapidanaData['berkas']['dokumen_tambahan'] ?? null;
        if ($request->hasFile('dokumen_tambahan_file')) {
            $f = $request->file('dokumen_tambahan_file');
            $fn = 'tambahan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
            $f->move($dirBerkas, $fn);
            $dokumenTambahan = [
                'nama' => $request->input('dokumen_tambahan_nama', 'Dokumen Tambahan'),
                'file' => 'uploads/sidang_tpp_kanwil/berkas/'.$fn,
            ];
        } elseif ($request->filled('dokumen_tambahan_nama') && ! empty($dokumenTambahan)) {
            $dokumenTambahan['nama'] = $request->input('dokumen_tambahan_nama');
        }

        if ($subjekTipe === 'mutasi_narapidana') {
            $existingBerkas = $narapidanaData['berkas'] ?? [];
            if ($request->hasFile('file_mutasi_permohonan')) {
                $f = $request->file('file_mutasi_permohonan');
                $fn = 'mutasi_permohonan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_permohonan'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_mutasi_jaminan')) {
                $f = $request->file('file_mutasi_jaminan');
                $fn = 'mutasi_jaminan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_jaminan'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_mutasi_litmas')) {
                $f = $request->file('file_mutasi_litmas');
                $fn = 'mutasi_litmas_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_litmas'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_mutasi_reg_f')) {
                $f = $request->file('file_mutasi_reg_f');
                $fn = 'mutasi_regf_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_reg_f'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_mutasi_asesmen')) {
                $f = $request->file('file_mutasi_asesmen');
                $fn = 'mutasi_asesmen_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_asesmen'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($dokumenTambahan) {
                $existingBerkas['dokumen_tambahan'] = $dokumenTambahan;
            }

            $uptAsalNama = $narapidanaData['upt_asal_nama'] ?? null;
            if ($request->filled('mutasi_upt_asal_id')) {
                $uptAsalNama = Upt::find($request->mutasi_upt_asal_id)?->nama_upt;
            }

            $validated['narapidana_data'] = [
                'nama' => $request->input('mutasi_wbp_nama', $narapidanaData['nama'] ?? $request->agenda_judul),
                'nomor_register' => $request->input('mutasi_nomor_register', $narapidanaData['nomor_register'] ?? null),
                'tindak_pidana' => $request->input('mutasi_perkara', $narapidanaData['tindak_pidana'] ?? null),
                'lama_pidana' => $request->input('mutasi_lama_pidana', $narapidanaData['lama_pidana'] ?? null),
                'upt_asal_id' => $request->input('mutasi_upt_asal_id', $narapidanaData['upt_asal_id'] ?? null),
                'upt_asal_nama' => $uptAsalNama,
                'upt_tujuan_id' => $uptTujuanId,
                'upt_tujuan_nama' => $uptTujuanNama,
                'alasan_pemindahan' => $request->input('mutasi_alasan', $narapidanaData['alasan_pemindahan'] ?? null),
                'penjamin_nama' => $request->input('mutasi_penjamin_nama', $narapidanaData['penjamin_nama'] ?? null),
                'penjamin_hubungan' => $request->input('mutasi_penjamin_hubungan', $narapidanaData['penjamin_hubungan'] ?? null),
                'penjamin_alamat' => $request->input('mutasi_penjamin_alamat', $narapidanaData['penjamin_alamat'] ?? null),
                'penjamin_kontak' => $request->input('mutasi_penjamin_kontak', $narapidanaData['penjamin_kontak'] ?? null),
                'berkas' => $existingBerkas,
            ];
        } elseif ($subjekTipe === 'izin_berobat') {
            $existingBerkas = $narapidanaData['berkas'] ?? [];
            if ($request->hasFile('file_medis_rekam')) {
                $f = $request->file('file_medis_rekam');
                $fn = 'medis_rekam_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_rekam_medis'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_medis_rujukan')) {
                $f = $request->file('file_medis_rujukan');
                $fn = 'medis_rujukan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_rujukan'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_medis_jaminan')) {
                $f = $request->file('file_medis_jaminan');
                $fn = 'medis_jaminan_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_jaminan'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($request->hasFile('file_medis_ba_upt')) {
                $f = $request->file('file_medis_ba_upt');
                $fn = 'medis_ba_upt_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                $f->move($dirBerkas, $fn);
                $existingBerkas['file_ba_upt'] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
            }
            if ($dokumenTambahan) {
                $existingBerkas['dokumen_tambahan'] = $dokumenTambahan;
            }

            $uptAsalNama = $narapidanaData['upt_asal_nama'] ?? null;
            if ($request->filled('berobat_upt_asal_id')) {
                $uptAsalNama = Upt::find($request->berobat_upt_asal_id)?->nama_upt;
            }

            $validated['narapidana_data'] = [
                'nama' => $request->input('berobat_wbp_nama', $narapidanaData['nama'] ?? $request->agenda_judul),
                'nomor_register' => $request->input('berobat_nomor_register', $narapidanaData['nomor_register'] ?? null),
                'tindak_pidana' => $request->input('berobat_perkara', $narapidanaData['tindak_pidana'] ?? null),
                'upt_asal_id' => $request->input('berobat_upt_asal_id', $narapidanaData['upt_asal_id'] ?? null),
                'upt_asal_nama' => $uptAsalNama,
                'diagnosa_medis' => $request->input('berobat_diagnosa', $narapidanaData['diagnosa_medis'] ?? null),
                'dokter_penanggung_jawab' => $request->input('berobat_dokter', $narapidanaData['dokter_penanggung_jawab'] ?? null),
                'rs_rujukan_nama' => $request->input('berobat_rs_nama', $narapidanaData['rs_rujukan_nama'] ?? null),
                'rs_rujukan_kota' => $request->input('berobat_rs_kota', $narapidanaData['rs_rujukan_kota'] ?? null),
                'penjamin_nama' => $request->input('berobat_penjamin_nama', $narapidanaData['penjamin_nama'] ?? null),
                'penjamin_hubungan' => $request->input('berobat_penjamin_hubungan', $narapidanaData['penjamin_hubungan'] ?? null),
                'penjamin_kontak' => $request->input('berobat_penjamin_kontak', $narapidanaData['penjamin_kontak'] ?? null),
                'berkas' => $existingBerkas,
            ];
        }

        $validated['updated_by'] = $user->id;
        $sidang->update($validated);

        if (! empty($validated['mutasi_id'])) {
            $mutasi = MutasiNarapidana::find($validated['mutasi_id']);
            if ($mutasi && $mutasi->sidang_tpp_kanwil_id !== $sidang->id) {
                $mutasi->update(['sidang_tpp_kanwil_id' => $sidang->id]);
            }
        }

        ActivityLog::record(
            'update',
            'Sidang TPP Kanwil',
            "Memperbarui data dan jadwal Sidang TPP Kanwil {$sidang->nomor_sidang}",
            $sidang
        );

        return back()->with('success', 'Data Sidang TPP Kanwil berhasil diperbarui.');
    }

    /**
     * Hapus Sidang TPP Kanwil.
     * Sebelum verifikasi Ka Kanwil: Admin dan User yang menginput dapat menghapus.
     * Setelah verifikasi Ka Kanwil: Tidak bisa dihapus, KECUALI Administrator.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canDelete($user)) {
            if ($sidang->isVerifiedKakanwil()) {
                abort(403, 'Akses ditolak. Sidang TPP Kanwil yang telah diverifikasi/disposisi oleh Kepala Kantor Wilayah tidak dapat dihapus, kecuali oleh Administrator.');
            }

            abort(403, 'Akses ditolak. Hanya user yang menginput sidang atau Administrator yang memiliki wewenang untuk menghapus Sidang TPP Kanwil ini.');
        }

        $nomor = $sidang->nomor_sidang;
        $sidang->delete();

        ActivityLog::record('delete', 'Sidang TPP Kanwil', "Menghapus data Sidang TPP Kanwil {$nomor}");

        return redirect()->route('sidang-tpp-kanwil.index')->with('success', "Sidang TPP Kanwil {$nomor} berhasil dihapus.");
    }

    /**
     * Tambah Materi Sidang Baru ke Sidang Kanwil yang sudah terdaftar.
     */
    public function materiStore(Request $request, int $id): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        if ($sidang->isVerifiedKakanwil()) {
            return back()->with('error', 'Tidak dapat menambah materi. Sidang ini telah selesai dan disahkan oleh Kepala Kantor Wilayah.');
        }

        $validated = $request->validate([
            'kategori_materi' => 'required|string|in:mutasi_narapidana,mutasi_keinginan_sendiri,izin_berobat,klien_bapas,lainnya',
            'judul_materi' => 'required|string|max:255',
            'deskripsi_materi' => 'nullable|string',
            'mutasi_id' => 'nullable|exists:mutasi_narapidana,id',
            'upt_tujuan_rekomendasi_id' => 'nullable',
            'mutasi_upt_tujuan' => 'nullable|string|max:150',
            'file_bahan_sidang' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:15360',
            'dokumen_tambahan_nama' => 'nullable|string|max:150',
            'dokumen_tambahan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip|max:15360',
            // Mutasi fields
            'mutasi_wbp_nama' => 'nullable|string|max:150',
            'mutasi_nomor_register' => 'nullable|string|max:100',
            'mutasi_perkara' => 'nullable|string|max:255',
            'mutasi_lama_pidana' => 'nullable|string|max:100',
            'mutasi_upt_asal_id' => 'nullable|exists:upt,id',
            'mutasi_alasan' => 'nullable|string',
            'nama_pk_bapas' => 'nullable|string|max:150',
            'nip_pk_bapas' => 'nullable|string|max:50',
            'bapas_nama' => 'nullable|string|max:150',
            'rekomendasi_litmas' => 'nullable|string',
            'nomor_ba_tpp_upt' => 'nullable|string|max:150',
            'tanggal_ba_tpp_upt' => 'nullable|date',
            'rekomendasi_tpp_upt' => 'nullable|string',
            'mutasi_penjamin_nama' => 'nullable|string|max:150',
            'mutasi_penjamin_nik' => 'nullable|string|max:30',
            'mutasi_penjamin_hubungan' => 'nullable|string|max:100',
            'mutasi_penjamin_alamat' => 'nullable|string|max:255',
            'mutasi_penjamin_kontak' => 'nullable|string|max:50',
            'file_mutasi_permohonan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_pernyataan_pribadi' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_ba_upt' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_jaminan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_ktp_penjamin' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_litmas' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_reg_f' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_mutasi_kesehatan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_mutasi_asesmen' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            // Medis fields
            'berobat_wbp_nama' => 'nullable|string|max:150',
            'berobat_nomor_register' => 'nullable|string|max:100',
            'berobat_perkara' => 'nullable|string|max:255',
            'berobat_upt_asal_id' => 'nullable|exists:upt,id',
            'berobat_diagnosa' => 'nullable|string',
            'berobat_dokter' => 'nullable|string|max:150',
            'berobat_rs_nama' => 'nullable|string|max:150',
            'berobat_rs_kota' => 'nullable|string|max:100',
            'berobat_penjamin_nama' => 'nullable|string|max:150',
            'berobat_penjamin_hubungan' => 'nullable|string|max:100',
            'berobat_penjamin_kontak' => 'nullable|string|max:50',
            'file_medis_rekam' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            'file_medis_rujukan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_medis_jaminan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:15360',
            'file_medis_ba_upt' => 'nullable|file|mimes:pdf,doc,docx,zip|max:15360',
            // Klien fields
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

        $dirBerkas = public_path('uploads/sidang_tpp_kanwil/berkas');
        if (! File::isDirectory($dirBerkas)) {
            File::makeDirectory($dirBerkas, 0755, true, true);
        }

        $fileBahanPath = null;
        if ($request->hasFile('file_bahan_sidang')) {
            $dirBahan = public_path('uploads/sidang_tpp_kanwil/bahan');
            if (! File::isDirectory($dirBahan)) {
                File::makeDirectory($dirBahan, 0755, true, true);
            }
            $f = $request->file('file_bahan_sidang');
            $fn = 'bahan_materi_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
            $f->move($dirBahan, $fn);
            $fileBahanPath = 'uploads/sidang_tpp_kanwil/bahan/'.$fn;
        }

        $dokumenTambahan = null;
        if ($request->hasFile('dokumen_tambahan_file')) {
            $f = $request->file('dokumen_tambahan_file');
            $fn = 'tambahan_materi_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
            $f->move($dirBerkas, $fn);
            $dokumenTambahan = [
                'nama' => $request->input('dokumen_tambahan_nama', 'Dokumen Tambahan'),
                'file' => 'uploads/sidang_tpp_kanwil/berkas/'.$fn,
            ];
        }

        $narapidanaData = null;
        $klienData = null;
        $uptTujuanId = null;
        $kategori = $validated['kategori_materi'];

        if ($kategori === 'mutasi_narapidana' || $kategori === 'mutasi_keinginan_sendiri') {
            $berkasMutasi = [];
            foreach (['file_mutasi_permohonan', 'file_mutasi_pernyataan_pribadi', 'file_mutasi_ba_upt', 'file_mutasi_jaminan', 'file_mutasi_ktp_penjamin', 'file_mutasi_litmas', 'file_mutasi_reg_f', 'file_mutasi_kesehatan', 'file_mutasi_asesmen'] as $field) {
                if ($request->hasFile($field)) {
                    $f = $request->file($field);
                    $fn = str_replace('file_mutasi_', '', $field).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                    $f->move($dirBerkas, $fn);
                    $berkasMutasi[str_replace('file_mutasi_', 'file_', $field)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                    $berkasMutasi[str_replace('file_mutasi_', '', $field)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                }
            }
            if ($dokumenTambahan) {
                $berkasMutasi['dokumen_tambahan'] = $dokumenTambahan;
            }

            $uptAsalNama = null;
            if ($request->filled('mutasi_upt_asal_id')) {
                $uptAsalNama = Upt::find($request->mutasi_upt_asal_id)?->nama_upt;
            }
            $mUptInput = $request->input('mutasi_upt_tujuan') ?: $request->input('upt_tujuan_rekomendasi_id');
            [$uptTujuanId, $uptTujuanNama] = $this->resolveUptTujuan($mUptInput);

            $narapidanaData = [
                'nama' => $request->input('mutasi_wbp_nama', $validated['judul_materi']),
                'nomor_register' => $request->input('mutasi_nomor_register'),
                'tindak_pidana' => $request->input('mutasi_perkara'),
                'lama_pidana' => $request->input('mutasi_lama_pidana'),
                'upt_asal_id' => $request->input('mutasi_upt_asal_id'),
                'upt_asal_nama' => $uptAsalNama,
                'upt_tujuan_id' => $uptTujuanId,
                'upt_tujuan_nama' => $uptTujuanNama,
                'alasan_pemindahan' => $request->input('mutasi_alasan'),
                'nama_pk_bapas' => $request->input('nama_pk_bapas'),
                'nip_pk_bapas' => $request->input('nip_pk_bapas'),
                'bapas_nama' => $request->input('bapas_nama'),
                'rekomendasi_litmas' => $request->input('rekomendasi_litmas'),
                'pk_bapas' => [
                    'nama' => $request->input('nama_pk_bapas'),
                    'nip' => $request->input('nip_pk_bapas'),
                    'bapas' => $request->input('bapas_nama'),
                    'rekomendasi' => $request->input('rekomendasi_litmas'),
                ],
                'nomor_ba_tpp_upt' => $request->input('nomor_ba_tpp_upt'),
                'tanggal_ba_tpp_upt' => $request->input('tanggal_ba_tpp_upt'),
                'rekomendasi_tpp_upt' => $request->input('rekomendasi_tpp_upt'),
                'tpp_upt' => [
                    'nomor_ba' => $request->input('nomor_ba_tpp_upt'),
                    'tanggal_ba' => $request->input('tanggal_ba_tpp_upt'),
                    'rekomendasi' => $request->input('rekomendasi_tpp_upt'),
                ],
                'penjamin_nama' => $request->input('mutasi_penjamin_nama'),
                'penjamin_nik' => $request->input('mutasi_penjamin_nik'),
                'penjamin_hubungan' => $request->input('mutasi_penjamin_hubungan'),
                'penjamin_alamat' => $request->input('mutasi_penjamin_alamat'),
                'penjamin_kontak' => $request->input('mutasi_penjamin_kontak'),
                'penjamin' => [
                    'nama' => $request->input('mutasi_penjamin_nama'),
                    'nik' => $request->input('mutasi_penjamin_nik'),
                    'hubungan' => $request->input('mutasi_penjamin_hubungan'),
                    'alamat' => $request->input('mutasi_penjamin_alamat'),
                    'telepon' => $request->input('mutasi_penjamin_kontak'),
                ],
                'berkas' => $berkasMutasi,
            ];
        } elseif ($kategori === 'izin_berobat') {
            $berkasMedis = [];
            foreach (['file_medis_rekam', 'file_medis_rujukan', 'file_medis_jaminan', 'file_medis_ba_upt'] as $field) {
                if ($request->hasFile($field)) {
                    $f = $request->file($field);
                    $fn = str_replace('file_medis_', 'medis_', $field).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                    $f->move($dirBerkas, $fn);
                    $berkasMedis[str_replace('file_medis_', 'file_', $field)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                }
            }
            if ($dokumenTambahan) {
                $berkasMedis['dokumen_tambahan'] = $dokumenTambahan;
            }

            $uptAsalNama = null;
            if ($request->filled('berobat_upt_asal_id')) {
                $uptAsalNama = Upt::find($request->berobat_upt_asal_id)?->nama_upt;
            }

            $narapidanaData = [
                'nama' => $request->input('berobat_wbp_nama', $validated['judul_materi']),
                'nomor_register' => $request->input('berobat_nomor_register'),
                'tindak_pidana' => $request->input('berobat_perkara'),
                'upt_asal_id' => $request->input('berobat_upt_asal_id'),
                'upt_asal_nama' => $uptAsalNama,
                'diagnosa_medis' => $request->input('berobat_diagnosa'),
                'dokter_penanggung_jawab' => $request->input('berobat_dokter'),
                'rs_rujukan_nama' => $request->input('berobat_rs_nama'),
                'rs_rujukan_kota' => $request->input('berobat_rs_kota'),
                'penjamin_nama' => $request->input('berobat_penjamin_nama'),
                'penjamin_hubungan' => $request->input('berobat_penjamin_hubungan'),
                'penjamin_kontak' => $request->input('berobat_penjamin_kontak'),
                'berkas' => $berkasMedis,
            ];
        } elseif ($kategori === 'klien_bapas') {
            $berkasKlien = [];
            foreach (['file_litmas_bapas', 'file_surat_bapas', 'file_bukti_travel', 'file_surat_jaminan'] as $field) {
                if ($request->hasFile($field)) {
                    $f = $request->file($field);
                    $fn = str_replace('file_', 'klien_', $field).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                    $f->move($dirBerkas, $fn);
                    $berkasKlien[$field] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                }
            }
            if ($dokumenTambahan) {
                $berkasKlien['dokumen_tambahan'] = $dokumenTambahan;
            }

            $klienData = [
                'nama_klien' => $request->input('klien_nama', $validated['judul_materi']),
                'bin' => $request->input('klien_bin'),
                'no_register_bapas' => $request->input('klien_no_register'),
                'bapas_nama' => $request->input('klien_bapas_nama'),
                'nomor_surat_bapas' => $request->input('klien_nomor_surat_bapas'),
                'tanggal_surat_bapas' => $request->input('klien_tanggal_surat_bapas'),
                'nama_pk' => $request->input('klien_nama_pk'),
                'program_integrasi' => $request->input('klien_program'),
                'keperluan_ln' => $request->input('klien_keperluan_ln'),
                'negara_tujuan' => $request->input('klien_negara_tujuan'),
                'tgl_berangkat' => $request->input('klien_tgl_berangkat'),
                'tgl_kembali' => $request->input('klien_tgl_kembali'),
                'durasi_hari' => $request->input('klien_durasi_hari'),
                'penjamin_nama' => $request->input('klien_penjamin_nama'),
                'penjamin_hubungan' => $request->input('klien_penjamin_hubungan'),
                'penjamin_telepon' => $request->input('klien_penjamin_telepon'),
                'berkas' => $berkasKlien,
            ];
        }

        $nextUrutan = (int) $sidang->materis()->max('urutan') + 1;

        $materi = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $sidang->id,
            'urutan' => $nextUrutan,
            'kategori_materi' => $kategori,
            'judul_materi' => $validated['judul_materi'],
            'deskripsi_materi' => $validated['deskripsi_materi'] ?? null,
            'mutasi_id' => $request->mutasi_id,
            'upt_tujuan_rekomendasi_id' => $uptTujuanId,
            'narapidana_data' => $narapidanaData,
            'klien_data' => $klienData,
            'file_bahan_sidang' => $fileBahanPath,
            'dokumen_tambahan' => $dokumenTambahan,
        ]);

        if (! empty($request->mutasi_id)) {
            $mutasi = MutasiNarapidana::find($request->mutasi_id);
            if ($mutasi) {
                $mutasi->update(['sidang_tpp_kanwil_id' => $sidang->id]);
            }
        }

        ActivityLog::record(
            'tambah',
            'Sidang TPP Kanwil',
            "Menambahkan Materi #{$materi->urutan} ({$materi->judul_materi}) pada Sidang TPP Kanwil #{$sidang->nomor_sidang}",
            $sidang
        );

        return back()->with('success', "Materi #{$materi->urutan} '{$materi->judul_materi}' berhasil ditambahkan ke dalam agenda sidang.");
    }

    /**
     * Perbarui Materi Sidang Tertentu.
     */
    public function materiUpdate(Request $request, int $id, int $materiId): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        if ($sidang->isVerifiedKakanwil()) {
            return back()->with('error', 'Tidak dapat mengubah materi. Sidang ini telah selesai dan disahkan oleh Kepala Kantor Wilayah.');
        }

        $materi = SidangTppKanwilMateri::where('sidang_tpp_kanwil_id', $sidang->id)->findOrFail($materiId);

        $validated = $request->validate([
            'kategori_materi' => 'required|string|in:mutasi_narapidana,mutasi_keinginan_sendiri,izin_berobat,klien_bapas,lainnya',
            'judul_materi' => 'required|string|max:255',
            'deskripsi_materi' => 'nullable|string',
            'mutasi_id' => 'nullable|exists:mutasi_narapidana,id',
            'upt_tujuan_rekomendasi_id' => 'nullable',
            'mutasi_upt_tujuan' => 'nullable|string|max:150',
            'rekomendasi_sidang' => 'nullable|string|max:50',
            'catatan_rekomendasi' => 'nullable|string',
            'file_bahan_sidang' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:15360',
            'dokumen_tambahan_nama' => 'nullable|string|max:150',
            'dokumen_tambahan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip|max:15360',
        ]);

        $dirBerkas = public_path('uploads/sidang_tpp_kanwil/berkas');
        if (! File::isDirectory($dirBerkas)) {
            File::makeDirectory($dirBerkas, 0755, true, true);
        }

        $fileBahanPath = $materi->file_bahan_sidang;
        if ($request->hasFile('file_bahan_sidang')) {
            $dirBahan = public_path('uploads/sidang_tpp_kanwil/bahan');
            if (! File::isDirectory($dirBahan)) {
                File::makeDirectory($dirBahan, 0755, true, true);
            }
            $f = $request->file('file_bahan_sidang');
            $fn = 'bahan_materi_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
            $f->move($dirBahan, $fn);
            $fileBahanPath = 'uploads/sidang_tpp_kanwil/bahan/'.$fn;
        }

        $dokumenTambahan = $materi->dokumen_tambahan;
        if ($request->hasFile('dokumen_tambahan_file')) {
            $f = $request->file('dokumen_tambahan_file');
            $fn = 'tambahan_materi_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
            $f->move($dirBerkas, $fn);
            $dokumenTambahan = [
                'nama' => $request->input('dokumen_tambahan_nama', $dokumenTambahan['nama'] ?? 'Dokumen Tambahan'),
                'file' => 'uploads/sidang_tpp_kanwil/berkas/'.$fn,
            ];
        } elseif ($request->filled('dokumen_tambahan_nama') && ! empty($dokumenTambahan['file'])) {
            $dokumenTambahan['nama'] = $request->input('dokumen_tambahan_nama');
        }

        $mUptInput = $request->filled('mutasi_upt_tujuan') ? $request->input('mutasi_upt_tujuan') : ($request->filled('upt_tujuan_rekomendasi_id') ? $request->input('upt_tujuan_rekomendasi_id') : null);
        $uptTujuanId = $materi->upt_tujuan_rekomendasi_id;
        $uptTujuanNama = $materi->narapidana_data['upt_tujuan_nama'] ?? null;
        if ($mUptInput !== null && trim((string) $mUptInput) !== '') {
            [$uptTujuanId, $uptTujuanNama] = $this->resolveUptTujuan($mUptInput);
        }

        $updateData = [
            'kategori_materi' => $validated['kategori_materi'],
            'judul_materi' => $validated['judul_materi'],
            'deskripsi_materi' => $validated['deskripsi_materi'] ?? null,
            'mutasi_id' => $request->input('mutasi_id', $materi->mutasi_id),
            'upt_tujuan_rekomendasi_id' => $uptTujuanId,
            'rekomendasi_sidang' => $validated['rekomendasi_sidang'] ?? $materi->rekomendasi_sidang,
            'catatan_rekomendasi' => $validated['catatan_rekomendasi'] ?? $materi->catatan_rekomendasi,
            'file_bahan_sidang' => $fileBahanPath,
            'dokumen_tambahan' => $dokumenTambahan,
        ];

        $narapidanaData = $materi->narapidana_data ?? [];
        if ($validated['kategori_materi'] === 'mutasi_narapidana' || $validated['kategori_materi'] === 'mutasi_keinginan_sendiri') {
            if ($request->filled('mutasi_wbp_nama')) {
                $narapidanaData['nama'] = $request->input('mutasi_wbp_nama');
            }
            if ($request->filled('mutasi_nomor_register')) {
                $narapidanaData['nomor_register'] = $request->input('mutasi_nomor_register');
            }
            if ($request->filled('mutasi_perkara')) {
                $narapidanaData['tindak_pidana'] = $request->input('mutasi_perkara');
            }
            if ($request->filled('mutasi_lama_pidana')) {
                $narapidanaData['lama_pidana'] = $request->input('mutasi_lama_pidana');
            }
            if ($request->filled('mutasi_alasan')) {
                $narapidanaData['alasan_pemindahan'] = $request->input('mutasi_alasan');
            }
            if ($request->filled('mutasi_upt_asal_id')) {
                $narapidanaData['upt_asal_id'] = $request->input('mutasi_upt_asal_id');
                $narapidanaData['upt_asal_nama'] = Upt::find($request->input('mutasi_upt_asal_id'))?->nama_upt;
            }
            if ($mUptInput !== null && trim((string) $mUptInput) !== '') {
                $narapidanaData['upt_tujuan_id'] = $uptTujuanId;
                $narapidanaData['upt_tujuan_nama'] = $uptTujuanNama;
            }
            if ($request->filled('nama_pk_bapas')) {
                $narapidanaData['nama_pk_bapas'] = $request->input('nama_pk_bapas');
                $narapidanaData['pk_bapas']['nama'] = $request->input('nama_pk_bapas');
            }
            if ($request->filled('nip_pk_bapas')) {
                $narapidanaData['nip_pk_bapas'] = $request->input('nip_pk_bapas');
                $narapidanaData['pk_bapas']['nip'] = $request->input('nip_pk_bapas');
            }
            if ($request->filled('bapas_nama')) {
                $narapidanaData['bapas_nama'] = $request->input('bapas_nama');
                $narapidanaData['pk_bapas']['bapas'] = $request->input('bapas_nama');
            }
            if ($request->filled('rekomendasi_litmas')) {
                $narapidanaData['rekomendasi_litmas'] = $request->input('rekomendasi_litmas');
                $narapidanaData['pk_bapas']['rekomendasi'] = $request->input('rekomendasi_litmas');
            }
            if ($request->filled('nomor_ba_tpp_upt')) {
                $narapidanaData['nomor_ba_tpp_upt'] = $request->input('nomor_ba_tpp_upt');
                $narapidanaData['tpp_upt']['nomor_ba'] = $request->input('nomor_ba_tpp_upt');
            }
            if ($request->filled('tanggal_ba_tpp_upt')) {
                $narapidanaData['tanggal_ba_tpp_upt'] = $request->input('tanggal_ba_tpp_upt');
                $narapidanaData['tpp_upt']['tanggal_ba'] = $request->input('tanggal_ba_tpp_upt');
            }
            if ($request->filled('rekomendasi_tpp_upt')) {
                $narapidanaData['rekomendasi_tpp_upt'] = $request->input('rekomendasi_tpp_upt');
                $narapidanaData['tpp_upt']['rekomendasi'] = $request->input('rekomendasi_tpp_upt');
            }
            if ($request->filled('mutasi_penjamin_nama')) {
                $narapidanaData['penjamin_nama'] = $request->input('mutasi_penjamin_nama');
                $narapidanaData['penjamin']['nama'] = $request->input('mutasi_penjamin_nama');
            }
            if ($request->filled('mutasi_penjamin_nik')) {
                $narapidanaData['penjamin_nik'] = $request->input('mutasi_penjamin_nik');
                $narapidanaData['penjamin']['nik'] = $request->input('mutasi_penjamin_nik');
            }
            if ($request->filled('mutasi_penjamin_hubungan')) {
                $narapidanaData['penjamin_hubungan'] = $request->input('mutasi_penjamin_hubungan');
                $narapidanaData['penjamin']['hubungan'] = $request->input('mutasi_penjamin_hubungan');
            }
            if ($request->filled('mutasi_penjamin_alamat')) {
                $narapidanaData['penjamin_alamat'] = $request->input('mutasi_penjamin_alamat');
                $narapidanaData['penjamin']['alamat'] = $request->input('mutasi_penjamin_alamat');
            }
            if ($request->filled('mutasi_penjamin_kontak')) {
                $narapidanaData['penjamin_kontak'] = $request->input('mutasi_penjamin_kontak');
                $narapidanaData['penjamin']['telepon'] = $request->input('mutasi_penjamin_kontak');
            }

            $berkas = $narapidanaData['berkas'] ?? [];
            foreach (['file_mutasi_permohonan', 'file_mutasi_pernyataan_pribadi', 'file_mutasi_ba_upt', 'file_mutasi_jaminan', 'file_mutasi_ktp_penjamin', 'file_mutasi_litmas', 'file_mutasi_reg_f', 'file_mutasi_kesehatan', 'file_mutasi_asesmen'] as $field) {
                if ($request->hasFile($field)) {
                    $f = $request->file($field);
                    $fn = str_replace('file_mutasi_', '', $field).'_'.date('Ymd_His').'_'.Str::random(6).'.'.$f->getClientOriginalExtension();
                    $f->move($dirBerkas, $fn);
                    $berkas[str_replace('file_mutasi_', 'file_', $field)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                    $berkas[str_replace('file_mutasi_', '', $field)] = 'uploads/sidang_tpp_kanwil/berkas/'.$fn;
                }
            }
            $narapidanaData['berkas'] = $berkas;
            $updateData['narapidana_data'] = $narapidanaData;
        } elseif ($validated['kategori_materi'] === 'izin_berobat') {
            if ($request->filled('berobat_wbp_nama')) {
                $narapidanaData['nama'] = $request->input('berobat_wbp_nama');
            }
            if ($request->filled('berobat_diagnosa')) {
                $narapidanaData['diagnosa_medis'] = $request->input('berobat_diagnosa');
            }
            if ($request->filled('berobat_dokter')) {
                $narapidanaData['dokter_penanggung_jawab'] = $request->input('berobat_dokter');
            }
            if ($request->filled('berobat_rs_nama')) {
                $narapidanaData['rs_rujukan_nama'] = $request->input('berobat_rs_nama');
            }
            if ($request->filled('berobat_rs_kota')) {
                $narapidanaData['rs_rujukan_kota'] = $request->input('berobat_rs_kota');
            }
            $updateData['narapidana_data'] = $narapidanaData;
        }

        $materi->update($updateData);

        if (! empty($updateData['mutasi_id'])) {
            MutasiNarapidana::find($updateData['mutasi_id'])?->update(['sidang_tpp_kanwil_id' => $sidang->id]);
        }

        ActivityLog::record(
            'edit',
            'Sidang TPP Kanwil',
            "Memperbarui Materi #{$materi->urutan} ({$materi->judul_materi}) pada Sidang TPP Kanwil #{$sidang->nomor_sidang}",
            $sidang
        );

        return back()->with('success', "Materi #{$materi->urutan} berhasil diperbarui.");
    }

    /**
     * Hapus Materi Sidang Tertentu.
     */
    public function materiDestroy(int $id, int $materiId): RedirectResponse
    {
        $this->checkKanwilAccess('full');
        $sidang = SidangTppKanwil::findOrFail($id);

        if ($sidang->isVerifiedKakanwil()) {
            return back()->with('error', 'Tidak dapat menghapus materi. Sidang ini telah selesai dan disahkan oleh Kepala Kantor Wilayah.');
        }

        $materi = SidangTppKanwilMateri::where('sidang_tpp_kanwil_id', $sidang->id)->findOrFail($materiId);
        $judul = $materi->judul_materi;
        $mutasiId = $materi->mutasi_id;

        $materi->delete();

        // Urutkan kembali materi yang tersisa
        $remaining = $sidang->materis()->orderBy('urutan')->get();
        foreach ($remaining as $i => $m) {
            $m->update(['urutan' => $i + 1]);
        }

        // Putuskan relasi ke mutasi jika tidak ada materi lain yang menghubungkannya
        if ($mutasiId && ! $sidang->materis()->where('mutasi_id', $mutasiId)->exists()) {
            MutasiNarapidana::where('id', $mutasiId)->where('sidang_tpp_kanwil_id', $sidang->id)
                ->update(['sidang_tpp_kanwil_id' => null]);
        }

        ActivityLog::record(
            'hapus',
            'Sidang TPP Kanwil',
            "Menghapus Materi '{$judul}' dari Sidang TPP Kanwil #{$sidang->nomor_sidang}",
            $sidang
        );

        return back()->with('success', "Materi '{$judul}' berhasil dihapus dari agenda sidang.");
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
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canEdit($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Sidang TPP Kanwil telah diverifikasi terakhir oleh Kepala Kantor Wilayah sehingga naskah dinas terkunci dan hanya dapat diubah oleh Administrator.',
            ], 403);
        }

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
        $user = auth()->user();
        $sidang = SidangTppKanwil::findOrFail($id);

        if (! $sidang->canEdit($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Sidang TPP Kanwil telah diverifikasi terakhir oleh Kepala Kantor Wilayah sehingga naskah dinas terkunci dan hanya dapat diubah oleh Administrator.',
            ], 403);
        }

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

    /**
     * Resolusi UPT tujuan rekomendasi sidang, baik berupa ID UPT lokal atau teks nama UPT bebas / luar wilayah.
     *
     * @return array{0: ?int, 1: ?string} [uptTujuanId, uptTujuanNama]
     */
    private function resolveUptTujuan(mixed $input): array
    {
        if (empty($input)) {
            return [null, null];
        }

        $inputStr = trim((string) $input);
        if ($inputStr === '') {
            return [null, null];
        }

        // Jika berupa ID numerik dari tabel UPT
        if (ctype_digit($inputStr) || is_int($input)) {
            $upt = Upt::find((int) $inputStr);
            if ($upt) {
                return [$upt->id, $upt->nama_upt];
            }
        }

        // Cek kesesuaian nama UPT di database (UPT lokal Banten)
        $uptByName = Upt::where('nama_upt', $inputStr)->first();
        if ($uptByName) {
            return [$uptByName->id, $uptByName->nama_upt];
        }

        $uptLike = Upt::whereRaw('LOWER(nama_upt) = ?', [strtolower($inputStr)])->first();
        if ($uptLike) {
            return [$uptLike->id, $uptLike->nama_upt];
        }

        // Jika tidak cocok dengan data UPT lokal, maka merupakan UPT luar wilayah (manual string)
        return [null, $inputStr];
    }
}
