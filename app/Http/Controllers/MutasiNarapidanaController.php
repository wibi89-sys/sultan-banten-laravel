<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MutasiNarapidana;
use App\Models\MutasiNarapidanaTujuan;
use App\Models\Upt;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MutasiNarapidanaController extends Controller
{
    private function ensureTableExists(): void
    {
        if (! Schema::hasTable('mutasi_narapidana') || ! Schema::hasColumn('mutasi_narapidana', 'file_laporan_pemindahan')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::warning('Auto migrate mutasi error: '.$e->getMessage());
            }
        }
    }

    private function checkAccess(string $requiredLevel = 'view'): void
    {
        $this->ensureTableExists();

        $user = auth()->user();
        if (! $user || ! $user->hasAccess('mutasi', $requiredLevel)) {
            $msg = $requiredLevel === 'full'
                ? 'Akses ditolak. Anda hanya memiliki hak lihat (read-only) pada modul Mutasi Narapidana.'
                : 'Anda tidak memiliki hak akses ke modul Mutasi Narapidana.';
            abort(403, $msg);
        }
    }

    public function index(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $query = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'tujuans.uptTujuan', 'creator'])->latest('tanggal_surat');

        // Scoping per UPT jika operator
        if ($user->isOperator() && $user->upt_id) {
            $query->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        } elseif ($request->filled('upt_id')) {
            $uptId = (int) $request->input('upt_id');
            $query->where(function ($q) use ($uptId) {
                $q->where('upt_asal_id', $uptId)
                    ->orWhere('upt_tujuan_id', $uptId)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $uptId));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('kategori')) {
            $kategoriFilter = $request->input('kategori');
            if ($kategoriFilter === 'Lainnya') {
                $query->where(function ($q) {
                    $q->where('kategori_mutasi', 'like', 'Lainnya%')
                        ->orWhereNotIn('kategori_mutasi', ['Pembinaan', 'Keamanan', 'Overkapasitas']);
                });
            } else {
                $query->where('kategori_mutasi', $kategoriFilter);
            }
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_surat', $request->input('tahun'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('nomor_surat_kanwil', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $mutasiList = $query->paginate(15)->withQueryString();

        // Statistik Cepat (Quick Counters)
        $statQuery = MutasiNarapidana::query();
        if ($user->isOperator() && $user->upt_id) {
            $statQuery->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        }

        $stats = [
            'total_usulan' => (clone $statQuery)->count(),
            'menunggu' => (clone $statQuery)->where('status', 'diajukan')->count(),
            'disetujui' => (clone $statQuery)->where('status', 'disetujui')->count(),
            'selesai' => (clone $statQuery)->where('status', 'selesai')->count(),
            'total_wbp' => (clone $statQuery)->sum('total_usulan'),
            'total_narkotika' => (clone $statQuery)->sum('jumlah_narkotika'),
            'total_pidum' => (clone $statQuery)->sum('jumlah_pidum'),
            'total_korupsi' => (clone $statQuery)->sum('jumlah_korupsi'),
        ];

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.index', compact('mutasiList', 'stats', 'uptList'));
    }

    public function create(): View|RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Kanwil hanya memantau dan memberikan persetujuan, kewenangan mengusulkan hanya dimiliki oleh UPT
        if ($user->isKanwil()) {
            return redirect()->route('mutasi.index')
                ->with('error', 'Kewenangan membuat usulan mutasi narapidana hanya dimiliki oleh operator UPT Pemasyarakatan. Akun Kantor Wilayah bertindak sebagai pemantau dan verifikator penetapan persetujuan.');
        }

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.create', compact('uptList', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Kanwil hanya memantau dan memberikan persetujuan, kewenangan mengusulkan hanya dimiliki oleh UPT
        if ($user->isKanwil()) {
            abort(403, 'Akses ditolak. Kewenangan membuat usulan mutasi narapidana hanya dimiliki oleh operator UPT Pemasyarakatan.');
        }

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'upt_asal_id' => 'required|exists:upt,id',
            'kategori_mutasi' => 'required|in:Pembinaan,Keamanan,Overkapasitas,Lainnya',
            'kategori_lainnya' => $request->input('kategori_mutasi') === 'Lainnya'
                ? 'required|string|max:100'
                : 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:1000',
            'jumlah_narkotika' => 'required|integer|min:0',
            'jumlah_pidum' => 'required|integer|min:0',
            'jumlah_korupsi' => 'required|integer|min:0',
            'file_surat_usulan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_asesmen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_bap' => $request->input('kategori_mutasi') === 'Keamanan'
                ? 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'
                : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_reg_f' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_lampiran_wbp' => 'nullable|file|mimes:pdf,xls,xlsx,csv|max:10240',
        ], [
            'kategori_lainnya.required' => 'Mohon sebutkan kategori lainnya secara manual.',
            'file_bap.required' => 'Untuk kategori Keamanan, Berita Acara Pemeriksaan (BAP) wajib dilampirkan.',
            'file_surat_usulan.required' => 'Surat Pengusulan resmi dari UPT wajib diunggah.',
        ]);

        // Paksa UPT asal sesuai penempatan user UPT
        $validated['upt_asal_id'] = $user->upt_id;

        $totalUsulan = (int) $validated['jumlah_narkotika'] + (int) $validated['jumlah_pidum'] + (int) $validated['jumlah_korupsi'];
        if ($totalUsulan <= 0) {
            return back()->withInput()->withErrors(['total_usulan' => 'Total narapidana yang diusulkan minimal 1 orang.']);
        }

        $uploadDir = public_path('uploads/mutasi');
        if (! File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $fileSuratUsulan = $this->handleUpload($request, 'file_surat_usulan', 'surat_usulan');
        $fileAsesmen = $this->handleUpload($request, 'file_asesmen', 'asesmen');
        $fileBap = $this->handleUpload($request, 'file_bap', 'bap');
        $fileRegF = $this->handleUpload($request, 'file_reg_f', 'reg_f');
        $fileLampiranWbp = $this->handleUpload($request, 'file_lampiran_wbp', 'lampiran_wbp');

        $kategoriFinal = $validated['kategori_mutasi'];
        if ($kategoriFinal === 'Lainnya' && $request->filled('kategori_lainnya')) {
            $inputManual = trim($request->input('kategori_lainnya'));
            $kategoriFinal = str_starts_with(strtolower($inputManual), 'lainnya')
                ? $inputManual
                : 'Lainnya: '.$inputManual;
        }

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => $validated['nomor_surat'],
            'tanggal_surat' => $validated['tanggal_surat'],
            'upt_asal_id' => $validated['upt_asal_id'],
            'kategori_mutasi' => $kategoriFinal,
            'keterangan' => $validated['keterangan'] ?? null,
            'jumlah_narkotika' => (int) $validated['jumlah_narkotika'],
            'jumlah_pidum' => (int) $validated['jumlah_pidum'],
            'jumlah_korupsi' => (int) $validated['jumlah_korupsi'],
            'total_usulan' => $totalUsulan,
            'file_surat_usulan' => $fileSuratUsulan,
            'file_asesmen' => $fileAsesmen,
            'file_bap' => $fileBap,
            'file_reg_f' => $fileRegF,
            'file_lampiran_wbp' => $fileLampiranWbp,
            'status' => 'diajukan',
            'created_by' => $user->id,
        ]);

        ActivityLog::log(
            'tambah',
            'mutasi',
            "Mengajukan usulan mutasi baru No: {$mutasi->nomor_surat} (Total: {$totalUsulan} WBP)",
            $mutasi->toArray()
        );

        // Notifikasi Otomatis ke Grup / Kanwil via WhatsApp
        WhatsAppService::notifyNewMutasiUsulan($mutasi);

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Usulan mutasi narapidana berhasil diajukan ke Kantor Wilayah.');
    }

    public function show(int $id): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $mutasi = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'tujuans.uptTujuan', 'creator', 'verifikator'])->findOrFail($id);

        // Proteksi jika operator mencoba membuka mutasi UPT lain
        if ($user->isOperator() && $user->upt_id) {
            if (! $mutasi->isUserRelated($user)) {
                abort(403, 'Anda tidak berhak melihat data mutasi UPT lain.');
            }
        }

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.show', compact('mutasi', 'uptList', 'user'));
    }

    public function verifikasi(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        // Hanya Kanwil / Admin yang berwenang memverifikasi
        if (! $user->isAdmin() && ! empty($user->upt_id)) {
            abort(403, 'Hanya Tim Kantor Wilayah / Administrator yang berhak memberikan penetapan persetujuan.');
        }

        $mutasi = MutasiNarapidana::with('tujuans')->findOrFail($id);

        $rules = [
            'status' => 'required|in:disetujui,ditolak,perlu_perbaikan',
            'catatan_kanwil' => 'nullable|string|max:1000',
        ];

        if ($request->input('status') === 'disetujui') {
            // Normalisasi jika dikirim format single upt_tujuan_id
            if (! $request->has('alokasi') && $request->filled('upt_tujuan_id')) {
                $request->merge([
                    'alokasi' => [
                        [
                            'upt_tujuan_id' => $request->input('upt_tujuan_id'),
                            'jumlah_narkotika' => $request->input('jumlah_disetujui_narkotika', 0),
                            'jumlah_pidum' => $request->input('jumlah_disetujui_pidum', 0),
                            'jumlah_korupsi' => $request->input('jumlah_disetujui_korupsi', 0),
                        ],
                    ],
                ]);
            }

            $rules['alokasi'] = 'required|array|min:1';
            $rules['alokasi.*.upt_tujuan_id'] = 'required|exists:upt,id';
            $rules['alokasi.*.jumlah_narkotika'] = 'required|integer|min:0';
            $rules['alokasi.*.jumlah_pidum'] = 'required|integer|min:0';
            $rules['alokasi.*.jumlah_korupsi'] = 'required|integer|min:0';
            $rules['nomor_surat_kanwil'] = 'required|string|max:100';
            $rules['tanggal_surat_kanwil'] = 'required|date';
            $rules['file_persetujuan_kanwil'] = empty($mutasi->file_persetujuan_kanwil)
                ? 'required|file|mimes:pdf|max:10240'
                : 'nullable|file|mimes:pdf|max:10240';
        }

        $validated = $request->validate($rules, [
            'alokasi.required' => 'Minimal 1 UPT Penerima (Tujuan) wajib ditentukan oleh Kanwil.',
            'alokasi.*.upt_tujuan_id.required' => 'Pilih UPT Penerima yang valid.',
            'alokasi.*.upt_tujuan_id.exists' => 'UPT Penerima tidak ditemukan.',
            'nomor_surat_kanwil.required' => 'Nomor Surat Persetujuan Kanwil wajib diisi.',
            'file_persetujuan_kanwil.required' => 'Scan Surat Persetujuan Kanwil resmi (PDF) wajib diunggah.',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'catatan_kanwil' => $validated['catatan_kanwil'] ?? null,
            'diverifikasi_oleh' => $user->id,
            'tanggal_verifikasi' => now(),
        ];

        if ($validated['status'] === 'disetujui') {
            $alokasiList = $validated['alokasi'];
            $selectedUptIds = [];
            $totalDisetujuiNarkotika = 0;
            $totalDisetujuiPidum = 0;
            $totalDisetujuiKorupsi = 0;
            $grandTotalDisetujui = 0;

            foreach ($alokasiList as $idx => $alok) {
                $uptTujuanId = (int) $alok['upt_tujuan_id'];
                if ($uptTujuanId === (int) $mutasi->upt_asal_id) {
                    return back()->withInput()->withErrors([
                        "alokasi.{$idx}.upt_tujuan_id" => 'UPT Penerima tidak boleh sama dengan UPT Pengusul.',
                    ]);
                }
                if (in_array($uptTujuanId, $selectedUptIds, true)) {
                    return back()->withInput()->withErrors([
                        "alokasi.{$idx}.upt_tujuan_id" => 'Terdapat duplikasi UPT Penerima dalam alokasi tujuan.',
                    ]);
                }
                $selectedUptIds[] = $uptTujuanId;

                $subtotal = (int) $alok['jumlah_narkotika'] + (int) $alok['jumlah_pidum'] + (int) $alok['jumlah_korupsi'];
                if ($subtotal <= 0) {
                    return back()->withInput()->withErrors([
                        "alokasi.{$idx}.subtotal" => 'Alokasi narapidana untuk masing-masing UPT tujuan minimal 1 orang.',
                    ]);
                }

                $totalDisetujuiNarkotika += (int) $alok['jumlah_narkotika'];
                $totalDisetujuiPidum += (int) $alok['jumlah_pidum'];
                $totalDisetujuiKorupsi += (int) $alok['jumlah_korupsi'];
                $grandTotalDisetujui += $subtotal;
            }

            $filePersetujuan = $this->handleUpload($request, 'file_persetujuan_kanwil', 'persetujuan_kanwil');
            if ($filePersetujuan) {
                $updateData['file_persetujuan_kanwil'] = $filePersetujuan;
            }

            $updateData['upt_tujuan_id'] = $selectedUptIds[0] ?? null;
            $updateData['nomor_surat_kanwil'] = $validated['nomor_surat_kanwil'];
            $updateData['tanggal_surat_kanwil'] = $validated['tanggal_surat_kanwil'];
            $updateData['jumlah_disetujui_narkotika'] = $totalDisetujuiNarkotika;
            $updateData['jumlah_disetujui_pidum'] = $totalDisetujuiPidum;
            $updateData['jumlah_disetujui_korupsi'] = $totalDisetujuiKorupsi;
            $updateData['total_disetujui'] = $grandTotalDisetujui;
        }

        $mutasi->update($updateData);

        if ($validated['status'] === 'disetujui') {
            // Re-sync tabel mutasi_narapidana_tujuan
            $existingTujuans = MutasiNarapidanaTujuan::where('mutasi_id', $mutasi->id)->get()->keyBy('upt_tujuan_id');
            MutasiNarapidanaTujuan::where('mutasi_id', $mutasi->id)->delete();

            foreach ($validated['alokasi'] as $alok) {
                $targetUptId = (int) $alok['upt_tujuan_id'];
                $subtotal = (int) $alok['jumlah_narkotika'] + (int) $alok['jumlah_pidum'] + (int) $alok['jumlah_korupsi'];
                $old = $existingTujuans->get($targetUptId);

                MutasiNarapidanaTujuan::create([
                    'mutasi_id' => $mutasi->id,
                    'upt_tujuan_id' => $targetUptId,
                    'jumlah_disetujui_narkotika' => (int) $alok['jumlah_narkotika'],
                    'jumlah_disetujui_pidum' => (int) $alok['jumlah_pidum'],
                    'jumlah_disetujui_korupsi' => (int) $alok['jumlah_korupsi'],
                    'total_disetujui' => $subtotal,
                    'file_laporan_pemindahan' => $old?->file_laporan_pemindahan,
                    'tanggal_pemindahan' => $old?->tanggal_pemindahan,
                    'file_laporan_penerimaan' => $old?->file_laporan_penerimaan,
                    'tanggal_penerimaan' => $old?->tanggal_penerimaan,
                    'file_bast' => $old?->file_bast,
                    'nomor_bast' => $old?->nomor_bast,
                    'tanggal_bast' => $old?->tanggal_bast,
                    'tanggal_pelaksanaan' => $old?->tanggal_pelaksanaan,
                    'catatan_pelaksanaan' => $old?->catatan_pelaksanaan,
                    'status' => $old?->status ?? 'disetujui',
                ]);
            }
        }

        ActivityLog::log(
            'verifikasi',
            'mutasi',
            "Kanwil memverifikasi usulan mutasi No: {$mutasi->nomor_surat} - Status: {$mutasi->status}",
            $mutasi->toArray()
        );

        if ($mutasi->status === 'disetujui') {
            WhatsAppService::notifyMutasiDisetujui($mutasi);
        }

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Keputusan verifikasi dan penentuan UPT Tujuan berhasil disimpan dan didistribusikan.');
    }

    public function laporanPemindahanStore(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        $mutasi = MutasiNarapidana::with('tujuans')->findOrFail($id);

        if (! in_array($mutasi->status, ['disetujui', 'selesai'], true)) {
            return back()->with('error', 'Laporan Pemindahan hanya dapat diunggah setelah ada persetujuan Kanwil.');
        }

        // Hanya UPT Pengusul atau Admin
        if ($user->isOperator() && $user->upt_id && (int) $user->upt_id !== (int) $mutasi->upt_asal_id) {
            abort(403, 'Hanya UPT Pengusul yang berhak mengunggah Laporan Pemindahan.');
        }

        $validated = $request->validate([
            'tujuan_id' => 'nullable|exists:mutasi_narapidana_tujuan,id',
            'tanggal_pemindahan' => 'required|date',
            'file_laporan_pemindahan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'catatan_pemindahan' => 'nullable|string|max:1000',
        ], [
            'file_laporan_pemindahan.required' => 'Berkas Laporan Pemindahan wajib diunggah.',
        ]);

        $filePath = $this->handleUpload($request, 'file_laporan_pemindahan', 'laporan_pemindahan');

        // Jika tujuan_id diberikan, update record spesifik
        $tujuan = null;
        if ($request->filled('tujuan_id')) {
            $tujuan = $mutasi->tujuans->firstWhere('id', (int) $request->input('tujuan_id'));
        } elseif ($mutasi->tujuans->count() === 1) {
            $tujuan = $mutasi->tujuans->first();
        }

        if ($tujuan) {
            $tujuan->update([
                'tanggal_pemindahan' => $validated['tanggal_pemindahan'],
                'file_laporan_pemindahan' => $filePath,
                'catatan_pelaksanaan' => $validated['catatan_pemindahan'] ?? $tujuan->catatan_pelaksanaan,
            ]);
            if ($tujuan->hasBast() && $tujuan->hasLaporanPenerimaan()) {
                $tujuan->update(['status' => 'selesai']);
            }
        }

        $update = [
            'tanggal_pemindahan' => $validated['tanggal_pemindahan'],
            'file_laporan_pemindahan' => $filePath,
            'catatan_pemindahan' => $validated['catatan_pemindahan'] ?? null,
        ];

        // Jika semua tujuan sudah selesai, selesaikan mutasi induk
        if ($mutasi->tujuans->isNotEmpty() && $mutasi->tujuans->every(fn ($t) => $t->isSelesai())) {
            $update['status'] = 'selesai';
        } elseif ($mutasi->hasBast() && $mutasi->hasLaporanPenerimaan()) {
            $update['status'] = 'selesai';
        }

        $mutasi->update($update);

        ActivityLog::log(
            'laporan_pemindahan',
            'mutasi',
            "Mengunggah Laporan Pemindahan Narapidana oleh UPT Pengusul No: {$mutasi->nomor_surat}".($tujuan ? " (Rute: {$tujuan->uptTujuan?->nama_upt})" : ''),
            $mutasi->toArray()
        );

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Laporan Pemindahan dari UPT Pengusul berhasil diunggah.');
    }

    public function laporanPenerimaanStore(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        $mutasi = MutasiNarapidana::with(['tujuans.uptTujuan'])->findOrFail($id);

        if (! in_array($mutasi->status, ['disetujui', 'selesai'], true)) {
            return back()->with('error', 'Laporan Penerimaan hanya dapat diunggah setelah ada persetujuan Kanwil.');
        }

        // Cari tujuan yang relevan
        $tujuan = null;
        if ($request->filled('tujuan_id')) {
            $tujuan = $mutasi->tujuans->firstWhere('id', (int) $request->input('tujuan_id'));
        } elseif ($user->isOperator() && $user->upt_id) {
            $tujuan = $mutasi->tujuans->firstWhere('upt_tujuan_id', (int) $user->upt_id);
        } elseif ($mutasi->tujuans->count() === 1) {
            $tujuan = $mutasi->tujuans->first();
        }

        // Hak akses: hanya UPT Penerima terkait atau Admin/Kanwil
        if ($user->isOperator() && $user->upt_id) {
            $userUptId = (int) $user->upt_id;
            $isTarget = ($tujuan && (int) $tujuan->upt_tujuan_id === $userUptId) || (int) $mutasi->upt_tujuan_id === $userUptId;
            if (! $isTarget) {
                abort(403, 'Hanya UPT Penerima yang berhak mengunggah Laporan Penerimaan.');
            }
        }

        $validated = $request->validate([
            'tujuan_id' => 'nullable|exists:mutasi_narapidana_tujuan,id',
            'tanggal_penerimaan' => 'required|date',
            'file_laporan_penerimaan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'catatan_penerimaan' => 'nullable|string|max:1000',
        ], [
            'file_laporan_penerimaan.required' => 'Berkas Laporan Penerimaan wajib diunggah.',
        ]);

        $filePath = $this->handleUpload($request, 'file_laporan_penerimaan', 'laporan_penerimaan');

        if ($tujuan) {
            $tujuan->update([
                'tanggal_penerimaan' => $validated['tanggal_penerimaan'],
                'file_laporan_penerimaan' => $filePath,
                'catatan_pelaksanaan' => $validated['catatan_penerimaan'] ?? $tujuan->catatan_pelaksanaan,
            ]);
            if ($tujuan->hasBast() && $tujuan->hasLaporanPemindahan()) {
                $tujuan->update(['status' => 'selesai']);
            }
        }

        $update = [
            'tanggal_penerimaan' => $validated['tanggal_penerimaan'],
            'file_laporan_penerimaan' => $filePath,
            'catatan_penerimaan' => $validated['catatan_penerimaan'] ?? null,
        ];

        if ($mutasi->tujuans->isNotEmpty() && $mutasi->tujuans->every(fn ($t) => $t->isSelesai())) {
            $update['status'] = 'selesai';
        } elseif ($mutasi->hasBast() && $mutasi->hasLaporanPemindahan()) {
            $update['status'] = 'selesai';
        }

        $mutasi->update($update);

        ActivityLog::log(
            'laporan_penerimaan',
            'mutasi',
            "Mengunggah Laporan Penerimaan Narapidana oleh UPT Penerima No: {$mutasi->nomor_surat}".($tujuan ? " ({$tujuan->uptTujuan?->nama_upt})" : ''),
            $mutasi->toArray()
        );

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Laporan Penerimaan dari UPT Penerima berhasil diunggah.');
    }

    public function bastStore(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        $mutasi = MutasiNarapidana::with(['tujuans.uptTujuan'])->findOrFail($id);

        if (! in_array($mutasi->status, ['disetujui', 'selesai'], true)) {
            return back()->with('error', 'Hanya usulan yang telah disetujui yang dapat melampirkan BAST.');
        }

        // Cari tujuan yang relevan
        $tujuan = null;
        if ($request->filled('tujuan_id')) {
            $tujuan = $mutasi->tujuans->firstWhere('id', (int) $request->input('tujuan_id'));
        } elseif ($user->isOperator() && $user->upt_id) {
            $tujuan = $mutasi->tujuans->firstWhere('upt_tujuan_id', (int) $user->upt_id);
        } elseif ($mutasi->tujuans->count() === 1) {
            $tujuan = $mutasi->tujuans->first();
        }

        // Validasi hak akses
        if ($user->isOperator() && $user->upt_id) {
            $userUptId = (int) $user->upt_id;
            $allowed = ($userUptId === (int) $mutasi->upt_asal_id)
                || ($tujuan && $userUptId === (int) $tujuan->upt_tujuan_id)
                || ($userUptId === (int) $mutasi->upt_tujuan_id)
                || $mutasi->tujuans->contains('upt_tujuan_id', $userUptId);

            if (! $allowed) {
                abort(403, 'Anda tidak berhak mengunggah BAST untuk rute mutasi ini.');
            }
        }

        $validated = $request->validate([
            'tujuan_id' => 'nullable|exists:mutasi_narapidana_tujuan,id',
            'nomor_bast' => 'required|string|max:100',
            'tanggal_bast' => 'required|date',
            'file_bast' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'catatan_pelaksanaan' => 'nullable|string|max:1000',
        ], [
            'nomor_bast.required' => 'Nomor Berita Acara Serah Terima (BAST) wajib diisi.',
            'file_bast.required' => 'Scan Berita Acara Serah Terima (BAST) fisik wajib diunggah.',
        ]);

        $fileBast = $this->handleUpload($request, 'file_bast', 'bast');

        if ($tujuan) {
            $tujuan->update([
                'status' => 'selesai',
                'nomor_bast' => $validated['nomor_bast'],
                'tanggal_bast' => $validated['tanggal_bast'],
                'tanggal_pelaksanaan' => $validated['tanggal_bast'],
                'file_bast' => $fileBast,
                'catatan_pelaksanaan' => $validated['catatan_pelaksanaan'] ?? null,
            ]);
        }

        $allTujuansFinished = $mutasi->tujuans->isNotEmpty()
            ? $mutasi->tujuans->every(fn ($t) => $t->isSelesai())
            : true;

        $update = [
            'nomor_bast' => $validated['nomor_bast'],
            'tanggal_bast' => $validated['tanggal_bast'],
            'tanggal_pelaksanaan' => $validated['tanggal_bast'],
            'file_bast' => $fileBast,
            'catatan_pelaksanaan' => $validated['catatan_pelaksanaan'] ?? null,
        ];

        if ($allTujuansFinished) {
            $update['status'] = 'selesai';
        }

        $mutasi->update($update);

        ActivityLog::log(
            'selesai',
            'mutasi',
            "Mengunggah BAST pemindahan mutasi No: {$mutasi->nomor_surat}".($tujuan ? " (Rute: {$tujuan->uptTujuan?->nama_upt})" : '').' - Status: Selesai',
            $mutasi->toArray()
        );

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Berita Acara Serah Terima (BAST) berhasil diunggah.');
    }

    public function downloadFile(Request $request, int $id, string $type): BinaryFileResponse
    {
        $this->checkAccess();
        $user = auth()->user();

        $mutasi = MutasiNarapidana::with('tujuans')->findOrFail($id);

        // Cek izin akses berkas
        if ($user->isOperator() && $user->upt_id) {
            if (! $mutasi->isUserRelated($user)) {
                abort(403, 'Anda tidak berhak mengunduh berkas ini.');
            }
        }

        $relativePath = null;
        if ($request->filled('tujuan_id') && in_array($type, ['laporan_pemindahan', 'laporan_penerimaan', 'bast'], true)) {
            $tujuan = $mutasi->tujuans->firstWhere('id', (int) $request->input('tujuan_id'));
            if ($tujuan) {
                $relativePath = match ($type) {
                    'laporan_pemindahan' => $tujuan->file_laporan_pemindahan,
                    'laporan_penerimaan' => $tujuan->file_laporan_penerimaan,
                    'bast' => $tujuan->file_bast,
                    default => null,
                };
            }
        }

        if (empty($relativePath)) {
            $relativePath = match ($type) {
                'surat_usulan' => $mutasi->file_surat_usulan,
                'asesmen' => $mutasi->file_asesmen,
                'bap' => $mutasi->file_bap,
                'reg_f' => $mutasi->file_reg_f,
                'lampiran_wbp' => $mutasi->file_lampiran_wbp,
                'persetujuan_kanwil' => $mutasi->file_persetujuan_kanwil,
                'laporan_pemindahan' => $mutasi->file_laporan_pemindahan,
                'laporan_penerimaan' => $mutasi->file_laporan_penerimaan,
                'bast' => $mutasi->file_bast,
                default => null,
            };
        }

        if (empty($relativePath) || ! File::exists(public_path($relativePath))) {
            abort(404, 'Berkas yang diminta tidak ditemukan di server.');
        }

        $fullPath = public_path($relativePath);
        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
        $cleanNoSurat = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mutasi->nomor_surat);
        $downloadName = "mutasi_{$type}_{$cleanNoSurat}.{$ext}";

        return response()->download($fullPath, $downloadName);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAccess('full');
        $user = auth()->user();

        $mutasi = MutasiNarapidana::findOrFail($id);

        // Hanya boleh hapus jika belum disetujui / selesai, atau oleh admin
        if ($mutasi->status === 'disetujui' || $mutasi->status === 'selesai') {
            if (! $user->isAdmin()) {
                return back()->with('error', 'Usulan yang sudah disetujui atau selesai tidak dapat dihapus.');
            }
        }

        // Hapus file fisik
        $files = [
            $mutasi->file_surat_usulan,
            $mutasi->file_asesmen,
            $mutasi->file_bap,
            $mutasi->file_reg_f,
            $mutasi->file_lampiran_wbp,
            $mutasi->file_persetujuan_kanwil,
            $mutasi->file_bast,
        ];

        foreach ($files as $file) {
            if (! empty($file) && File::exists(public_path($file))) {
                @unlink(public_path($file));
            }
        }

        $oldData = $mutasi->toArray();
        $mutasi->delete();

        ActivityLog::log(
            'hapus',
            'mutasi',
            "Menghapus berkas usulan mutasi No: {$mutasi->nomor_surat}",
            $oldData
        );

        return redirect()->route('mutasi.index')->with('success', 'Berkas usulan mutasi berhasil dihapus.');
    }

    public function kanwil(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $query = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'tujuans.uptTujuan', 'creator', 'verifikator'])
            ->latest('tanggal_surat');

        // Scoping per UPT jika operator
        if ($user->isOperator() && $user->upt_id) {
            $query->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        } elseif ($request->filled('upt_id')) {
            $uptId = (int) $request->input('upt_id');
            $query->where(function ($q) use ($uptId) {
                $q->where('upt_asal_id', $uptId)
                    ->orWhere('upt_tujuan_id', $uptId)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $uptId));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('nomor_surat_kanwil', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $mutasiList = $query->paginate(15)->withQueryString();

        $statQuery = MutasiNarapidana::query();
        if ($user->isOperator() && $user->upt_id) {
            $statQuery->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        }

        $stats = [
            'perlu_telaah' => (clone $statQuery)->whereIn('status', ['diajukan', 'perlu_perbaikan'])->count(),
            'disetujui' => (clone $statQuery)->where('status', 'disetujui')->count(),
            'ditolak' => (clone $statQuery)->where('status', 'ditolak')->count(),
            'total' => (clone $statQuery)->count(),
        ];

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.kanwil', compact('mutasiList', 'stats', 'uptList', 'user'));
    }

    public function pelaksanaan(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();

        // Menampilkan usulan yang telah disetujui Kanwil atau berstatus selesai
        $query = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'tujuans.uptTujuan', 'creator', 'verifikator'])
            ->whereIn('status', ['disetujui', 'selesai'])
            ->latest('tanggal_surat_kanwil');

        // Scoping per UPT jika operator
        if ($user->isOperator() && $user->upt_id) {
            $query->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        } elseif ($request->filled('upt_id')) {
            $uptId = (int) $request->input('upt_id');
            $query->where(function ($q) use ($uptId) {
                $q->where('upt_asal_id', $uptId)
                    ->orWhere('upt_tujuan_id', $uptId)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $uptId));
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('nomor_surat_kanwil', 'like', "%{$search}%")
                    ->orWhere('nomor_bast', 'like', "%{$search}%");
            });
        }

        $mutasiList = $query->paginate(15)->withQueryString();

        $statQuery = MutasiNarapidana::whereIn('status', ['disetujui', 'selesai']);
        if ($user->isOperator() && $user->upt_id) {
            $statQuery->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        }

        $stats = [
            'total_disetujui' => (clone $statQuery)->count(),
            'belum_pemindahan' => (clone $statQuery)->whereNull('file_laporan_pemindahan')->count(),
            'belum_penerimaan' => (clone $statQuery)->whereNull('file_laporan_penerimaan')->count(),
            'belum_bast' => (clone $statQuery)->whereNull('file_bast')->count(),
            'tuntas' => (clone $statQuery)->where('status', 'selesai')->count(),
        ];

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.pelaksanaan', compact('mutasiList', 'stats', 'uptList', 'user'));
    }

    public function rekapitulasi(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $tahun = (int) $request->input('tahun', date('Y'));

        $query = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'tujuans.uptTujuan'])
            ->whereYear('tanggal_surat', $tahun);

        if ($user->isOperator() && $user->upt_id) {
            $query->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id)
                    ->orWhereHas('tujuans', fn ($tq) => $tq->where('upt_tujuan_id', $user->upt_id));
            });
        }

        $mutasiAll = $query->get();

        // Ringkasan Total
        $totalUsulan = $mutasiAll->count();
        $totalNarkotika = $mutasiAll->sum('jumlah_narkotika');
        $totalPidum = $mutasiAll->sum('jumlah_pidum');
        $totalKorupsi = $mutasiAll->sum('jumlah_korupsi');
        $totalWbp = $mutasiAll->sum('total_usulan');
        $totalDisetujuiWbp = $mutasiAll->whereIn('status', ['disetujui', 'selesai'])->sum('total_disetujui');

        // Rekap per UPT Asal (Pengusul / Arus Keluar)
        $rekapUpt = Upt::orderBy('nama_upt')->get()->map(function ($upt) use ($mutasiAll) {
            $keluar = $mutasiAll->where('upt_asal_id', $upt->id);
            $masukWbp = $mutasiAll->whereIn('status', ['disetujui', 'selesai'])
                ->flatMap->tujuans
                ->where('upt_tujuan_id', $upt->id)
                ->sum('total_disetujui');

            if ($masukWbp === 0) {
                $masukWbp = $mutasiAll->where('upt_tujuan_id', $upt->id)->whereIn('status', ['disetujui', 'selesai'])->sum('total_disetujui');
            }

            return [
                'upt' => $upt,
                'usul_keluar_berkas' => $keluar->count(),
                'usul_keluar_wbp' => $keluar->sum('total_usulan'),
                'keluar_narkotika' => $keluar->sum('jumlah_narkotika'),
                'keluar_pidum' => $keluar->sum('jumlah_pidum'),
                'keluar_korupsi' => $keluar->sum('jumlah_korupsi'),
                'masuk_wbp' => $masukWbp,
            ];
        });

        // Rekap per Kategori
        $kategoriSummary = [
            'Pembinaan' => $mutasiAll->where('kategori_mutasi', 'Pembinaan')->sum('total_usulan'),
            'Keamanan' => $mutasiAll->where('kategori_mutasi', 'Keamanan')->sum('total_usulan'),
            'Overkapasitas' => $mutasiAll->where('kategori_mutasi', 'Overkapasitas')->sum('total_usulan'),
            'Lainnya' => $mutasiAll->reject(fn ($m) => in_array($m->kategori_mutasi, ['Pembinaan', 'Keamanan', 'Overkapasitas'], true))->sum('total_usulan'),
        ];

        return view('mutasi.rekapitulasi', compact(
            'tahun',
            'totalUsulan',
            'totalNarkotika',
            'totalPidum',
            'totalKorupsi',
            'totalWbp',
            'totalDisetujuiWbp',
            'rekapUpt',
            'kategoriSummary'
        ));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->checkAccess();
        $tahun = (int) $request->input('tahun', date('Y'));

        $mutasiList = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'tujuans.uptTujuan'])
            ->whereYear('tanggal_surat', $tahun)
            ->latest('tanggal_surat')
            ->get();

        $filename = "rekap_mutasi_narapidana_banten_{$tahun}.csv";

        return response()->streamDownload(function () use ($mutasiList) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($output, [
                'No',
                'No. Surat Usulan',
                'Tanggal Usulan',
                'UPT Pengusul',
                'Kategori Mutasi',
                'Narkotika',
                'Pidana Umum',
                'Korupsi',
                'Total Usulan WBP',
                'Status',
                'UPT Tujuan (Kanwil)',
                'No. Surat Kanwil',
                'Tanggal Surat Kanwil',
                'Total Disetujui Kanwil',
                'Tgl Pelaksanaan (BAST)',
            ]);

            foreach ($mutasiList as $idx => $m) {
                fputcsv($output, [
                    $idx + 1,
                    $m->nomor_surat,
                    $m->tanggal_surat ? $m->tanggal_surat->format('Y-m-d') : '',
                    $m->uptAsal?->nama_upt ?? '-',
                    $m->kategori_mutasi,
                    $m->jumlah_narkotika,
                    $m->jumlah_pidum,
                    $m->jumlah_korupsi,
                    $m->total_usulan,
                    $m->status_label,
                    $m->daftar_tujuan_text,
                    $m->nomor_surat_kanwil ?? '-',
                    $m->tanggal_surat_kanwil ? $m->tanggal_surat_kanwil->format('Y-m-d') : '',
                    $m->total_disetujui ?? 0,
                    $m->tanggal_pelaksanaan ? $m->tanggal_pelaksanaan->format('Y-m-d') : '',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function handleUpload(Request $request, string $fieldName, string $prefix): ?string
    {
        if (! $request->hasFile($fieldName)) {
            return null;
        }

        $file = $request->file($fieldName);
        $ext = $file->getClientOriginalExtension();
        $safeName = $prefix.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;

        $file->move(public_path('uploads/mutasi'), $safeName);

        return 'uploads/mutasi/'.$safeName;
    }
}
