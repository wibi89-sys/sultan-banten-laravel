<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MutasiNarapidana;
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

    private function checkAccess(): void
    {
        $this->ensureTableExists();

        $user = auth()->user();
        if (! $user || ! $user->hasAccess('mutasi')) {
            abort(403, 'Anda tidak memiliki hak akses ke modul Mutasi Narapidana.');
        }
    }

    public function index(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $query = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'creator'])->latest('tanggal_surat');

        // Scoping per UPT jika operator
        if ($user->isOperator() && $user->upt_id) {
            $query->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id);
            });
        } elseif ($request->filled('upt_id')) {
            $uptId = (int) $request->input('upt_id');
            $query->where(function ($q) use ($uptId) {
                $q->where('upt_asal_id', $uptId)
                    ->orWhere('upt_tujuan_id', $uptId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori_mutasi', $request->input('kategori'));
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
                    ->orWhere('upt_tujuan_id', $user->upt_id);
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

    public function create(): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.create', compact('uptList', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkAccess();
        $user = auth()->user();

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'upt_asal_id' => 'required|exists:upt,id',
            'kategori_mutasi' => 'required|in:Pembinaan,Keamanan,Overkapasitas,Lainnya',
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
            'file_bap.required' => 'Untuk kategori Keamanan, Berita Acara Pemeriksaan (BAP) wajib dilampirkan.',
            'file_surat_usulan.required' => 'Surat Pengusulan resmi dari UPT wajib diunggah.',
        ]);

        // Jika operator, paksa UPT asal sesuai penempatan user
        if ($user->isOperator() && $user->upt_id) {
            $validated['upt_asal_id'] = $user->upt_id;
        }

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

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => $validated['nomor_surat'],
            'tanggal_surat' => $validated['tanggal_surat'],
            'upt_asal_id' => $validated['upt_asal_id'],
            'kategori_mutasi' => $validated['kategori_mutasi'],
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

        $mutasi = MutasiNarapidana::with(['uptAsal', 'uptTujuan', 'creator', 'verifikator'])->findOrFail($id);

        // Proteksi jika operator mencoba membuka mutasi UPT lain
        if ($user->isOperator() && $user->upt_id) {
            $allowed = ((int) $mutasi->upt_asal_id === (int) $user->upt_id || (int) $mutasi->upt_tujuan_id === (int) $user->upt_id);
            if (! $allowed) {
                abort(403, 'Anda tidak berhak melihat data mutasi UPT lain.');
            }
        }

        $uptList = Upt::orderBy('nama_upt')->get();

        return view('mutasi.show', compact('mutasi', 'uptList', 'user'));
    }

    public function verifikasi(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();
        $user = auth()->user();

        // Hanya Kanwil / Admin yang berwenang memverifikasi
        if (! $user->isAdmin() && ! empty($user->upt_id)) {
            abort(403, 'Hanya Tim Kantor Wilayah / Administrator yang berhak memberikan penetapan persetujuan.');
        }

        $mutasi = MutasiNarapidana::findOrFail($id);

        $rules = [
            'status' => 'required|in:disetujui,ditolak,perlu_perbaikan',
            'catatan_kanwil' => 'nullable|string|max:1000',
        ];

        if ($request->input('status') === 'disetujui') {
            $rules['upt_tujuan_id'] = 'required|exists:upt,id|different:upt_asal_id';
            $rules['nomor_surat_kanwil'] = 'required|string|max:100';
            $rules['tanggal_surat_kanwil'] = 'required|date';
            $rules['file_persetujuan_kanwil'] = empty($mutasi->file_persetujuan_kanwil)
                ? 'required|file|mimes:pdf|max:10240'
                : 'nullable|file|mimes:pdf|max:10240';
            $rules['jumlah_disetujui_narkotika'] = 'required|integer|min:0';
            $rules['jumlah_disetujui_pidum'] = 'required|integer|min:0';
            $rules['jumlah_disetujui_korupsi'] = 'required|integer|min:0';
        }

        $validated = $request->validate($rules, [
            'upt_tujuan_id.required' => 'UPT Penerima (Tujuan) wajib ditentukan oleh Kanwil.',
            'upt_tujuan_id.different' => 'UPT Penerima tidak boleh sama dengan UPT Pengusul.',
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
            $totalDisetujui = (int) $validated['jumlah_disetujui_narkotika'] + (int) $validated['jumlah_pidum'] + (int) $validated['jumlah_korupsi'];

            $filePersetujuan = $this->handleUpload($request, 'file_persetujuan_kanwil', 'persetujuan_kanwil');
            if ($filePersetujuan) {
                $updateData['file_persetujuan_kanwil'] = $filePersetujuan;
            }

            $updateData['upt_tujuan_id'] = (int) $validated['upt_tujuan_id'];
            $updateData['nomor_surat_kanwil'] = $validated['nomor_surat_kanwil'];
            $updateData['tanggal_surat_kanwil'] = $validated['tanggal_surat_kanwil'];
            $updateData['jumlah_disetujui_narkotika'] = (int) $validated['jumlah_disetujui_narkotika'];
            $updateData['jumlah_disetujui_pidum'] = (int) $validated['jumlah_disetujui_pidum'];
            $updateData['jumlah_disetujui_korupsi'] = (int) $validated['jumlah_disetujui_korupsi'];
            $updateData['total_disetujui'] = $totalDisetujui;
        }

        $mutasi->update($updateData);

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
        $this->checkAccess();
        $user = auth()->user();

        $mutasi = MutasiNarapidana::findOrFail($id);

        if (! in_array($mutasi->status, ['disetujui', 'selesai'], true)) {
            return back()->with('error', 'Laporan Pemindahan hanya dapat diunggah setelah ada persetujuan Kanwil.');
        }

        // Hanya UPT Pengusul atau Admin
        if ($user->isOperator() && $user->upt_id && (int) $user->upt_id !== (int) $mutasi->upt_asal_id) {
            abort(403, 'Hanya UPT Pengusul yang berhak mengunggah Laporan Pemindahan.');
        }

        $validated = $request->validate([
            'tanggal_pemindahan' => 'required|date',
            'file_laporan_pemindahan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'catatan_pemindahan' => 'nullable|string|max:1000',
        ], [
            'file_laporan_pemindahan.required' => 'Berkas Laporan Pemindahan wajib diunggah.',
        ]);

        $filePath = $this->handleUpload($request, 'file_laporan_pemindahan', 'laporan_pemindahan');

        $update = [
            'tanggal_pemindahan' => $validated['tanggal_pemindahan'],
            'file_laporan_pemindahan' => $filePath,
            'catatan_pemindahan' => $validated['catatan_pemindahan'] ?? null,
        ];

        if ($mutasi->hasBast() && $mutasi->hasLaporanPenerimaan()) {
            $update['status'] = 'selesai';
        }

        $mutasi->update($update);

        ActivityLog::log(
            'laporan_pemindahan',
            'mutasi',
            "Mengunggah Laporan Pemindahan Narapidana oleh UPT Pengusul No: {$mutasi->nomor_surat}",
            $mutasi->toArray()
        );

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Laporan Pemindahan dari UPT Pengusul berhasil diunggah.');
    }

    public function laporanPenerimaanStore(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();
        $user = auth()->user();

        $mutasi = MutasiNarapidana::findOrFail($id);

        if (! in_array($mutasi->status, ['disetujui', 'selesai'], true)) {
            return back()->with('error', 'Laporan Penerimaan hanya dapat diunggah setelah ada persetujuan Kanwil.');
        }

        // Hanya UPT Penerima atau Admin
        if ($user->isOperator() && $user->upt_id && (int) $user->upt_id !== (int) $mutasi->upt_tujuan_id) {
            abort(403, 'Hanya UPT Penerima yang berhak mengunggah Laporan Penerimaan.');
        }

        $validated = $request->validate([
            'tanggal_penerimaan' => 'required|date',
            'file_laporan_penerimaan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'catatan_penerimaan' => 'nullable|string|max:1000',
        ], [
            'file_laporan_penerimaan.required' => 'Berkas Laporan Penerimaan wajib diunggah.',
        ]);

        $filePath = $this->handleUpload($request, 'file_laporan_penerimaan', 'laporan_penerimaan');

        $update = [
            'tanggal_penerimaan' => $validated['tanggal_penerimaan'],
            'file_laporan_penerimaan' => $filePath,
            'catatan_penerimaan' => $validated['catatan_penerimaan'] ?? null,
        ];

        if ($mutasi->hasBast() && $mutasi->hasLaporanPemindahan()) {
            $update['status'] = 'selesai';
        }

        $mutasi->update($update);

        ActivityLog::log(
            'laporan_penerimaan',
            'mutasi',
            "Mengunggah Laporan Penerimaan Narapidana oleh UPT Penerima No: {$mutasi->nomor_surat}",
            $mutasi->toArray()
        );

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Laporan Penerimaan dari UPT Penerima berhasil diunggah.');
    }

    public function bastStore(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();
        $user = auth()->user();

        $mutasi = MutasiNarapidana::findOrFail($id);

        if (! in_array($mutasi->status, ['disetujui', 'selesai'], true)) {
            return back()->with('error', 'Hanya usulan yang telah disetujui yang dapat melampirkan BAST.');
        }

        // Hanya UPT terkait atau admin
        if ($user->isOperator() && $user->upt_id && ! in_array((int) $user->upt_id, [(int) $mutasi->upt_asal_id, (int) $mutasi->upt_tujuan_id], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'nomor_bast' => 'required|string|max:100',
            'tanggal_bast' => 'required|date',
            'file_bast' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'catatan_pelaksanaan' => 'nullable|string|max:1000',
        ], [
            'nomor_bast.required' => 'Nomor Berita Acara Serah Terima (BAST) wajib diisi.',
            'file_bast.required' => 'Scan Berita Acara Serah Terima (BAST) fisik wajib diunggah.',
        ]);

        $fileBast = $this->handleUpload($request, 'file_bast', 'bast');

        $mutasi->update([
            'status' => 'selesai',
            'nomor_bast' => $validated['nomor_bast'],
            'tanggal_bast' => $validated['tanggal_bast'],
            'tanggal_pelaksanaan' => $validated['tanggal_bast'],
            'file_bast' => $fileBast,
            'catatan_pelaksanaan' => $validated['catatan_pelaksanaan'] ?? null,
        ]);

        ActivityLog::log(
            'selesai',
            'mutasi',
            "Mengunggah BAST pemindahan mutasi No: {$mutasi->nomor_surat} - Status: Selesai",
            $mutasi->toArray()
        );

        return redirect()->route('mutasi.show', $mutasi->id)
            ->with('success', 'Berita Acara Serah Terima (BAST) berhasil diunggah. Proses pemindahan telah selesai.');
    }

    public function downloadFile(int $id, string $type): BinaryFileResponse
    {
        $this->checkAccess();
        $user = auth()->user();

        $mutasi = MutasiNarapidana::findOrFail($id);

        // Cek izin akses berkas
        if ($user->isOperator() && $user->upt_id) {
            $allowed = ((int) $mutasi->upt_asal_id === (int) $user->upt_id || (int) $mutasi->upt_tujuan_id === (int) $user->upt_id);
            if (! $allowed) {
                abort(403, 'Anda tidak berhak mengunduh berkas ini.');
            }
        }

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
        $this->checkAccess();
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

    public function rekapitulasi(Request $request): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $tahun = (int) $request->input('tahun', date('Y'));

        $query = MutasiNarapidana::with(['uptAsal', 'uptTujuan'])
            ->whereYear('tanggal_surat', $tahun);

        if ($user->isOperator() && $user->upt_id) {
            $query->where(function ($q) use ($user) {
                $q->where('upt_asal_id', $user->upt_id)
                    ->orWhere('upt_tujuan_id', $user->upt_id);
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
            $masuk = $mutasiAll->where('upt_tujuan_id', $upt->id)->whereIn('status', ['disetujui', 'selesai']);

            return [
                'upt' => $upt,
                'usul_keluar_berkas' => $keluar->count(),
                'usul_keluar_wbp' => $keluar->sum('total_usulan'),
                'keluar_narkotika' => $keluar->sum('jumlah_narkotika'),
                'keluar_pidum' => $keluar->sum('jumlah_pidum'),
                'keluar_korupsi' => $keluar->sum('jumlah_korupsi'),
                'masuk_wbp' => $masuk->sum('total_disetujui'),
            ];
        });

        // Rekap per Kategori
        $kategoriSummary = [
            'Pembinaan' => $mutasiAll->where('kategori_mutasi', 'Pembinaan')->sum('total_usulan'),
            'Keamanan' => $mutasiAll->where('kategori_mutasi', 'Keamanan')->sum('total_usulan'),
            'Overkapasitas' => $mutasiAll->where('kategori_mutasi', 'Overkapasitas')->sum('total_usulan'),
            'Lainnya' => $mutasiAll->where('kategori_mutasi', 'Lainnya')->sum('total_usulan'),
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

        $mutasiList = MutasiNarapidana::with(['uptAsal', 'uptTujuan'])
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
                    $m->uptTujuan?->nama_upt ?? '-',
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
