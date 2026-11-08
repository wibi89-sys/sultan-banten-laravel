<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Services\TelramCalculatorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TelramController extends Controller
{
    public function __construct(
        protected TelramCalculatorService $calculator
    ) {}

    /**
     * Tampilkan halaman utama Kalkulator Telram PAS
     */
    public function index(): View
    {
        $user = auth()->user();
        $instansi = Instansi::getFirst();

        return view('telram.index', compact('user', 'instansi'));
    }

    /**
     * Endpoint API / AJAX untuk kalkulasi Telram
     */
    public function calculate(Request $request): JsonResponse
    {
        $mode = $request->input('mode', 'maju');

        try {
            if ($mode === 'simulasi_remisi') {
                $validated = $request->validate([
                    'tgl_awal' => 'required|date',
                    'tahun' => 'nullable|integer|min:0',
                    'bulan' => 'nullable|integer|min:0',
                    'hari' => 'nullable|integer|min:0',
                    'agama' => 'nullable|string|in:islam,kristen,katolik,hindu,buddha,khonghucu',
                    'remisi_awal_hari' => 'nullable|integer|min:0',
                    'nama_wbp' => 'nullable|string|max:150',
                    'no_register' => 'nullable|string|max:100',
                    'include_dasawarsa' => 'nullable|boolean',
                    'kategori_tambahan' => 'nullable|string|in:none,pemuka,jasa_negara',
                    'tahun_mulai_tambahan' => 'nullable|integer|min:1|max:50',
                    'target_basis' => 'nullable|string|in:2_3,ekspirasi',
                ]);

                $tahun = (int) ($validated['tahun'] ?? 0);
                $bulan = (int) ($validated['bulan'] ?? 0);
                $hari = (int) ($validated['hari'] ?? 0);

                if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Harap masukkan lama vonis hukuman (Tahun, Bulan, atau Hari)!',
                    ], 422);
                }

                $namaWbp = trim((string) ($validated['nama_wbp'] ?? '')) ?: 'Narapidana';
                $noRegister = trim((string) ($validated['no_register'] ?? '')) ?: '-';
                $includeDasawarsa = filter_var($validated['include_dasawarsa'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $kategoriTambahan = $validated['kategori_tambahan'] ?? 'none';
                $tahunMulaiTambahan = (int) ($validated['tahun_mulai_tambahan'] ?? 1);
                $targetBasis = $validated['target_basis'] ?? '2_3';

                $result = $this->calculator->hitungProyeksiRemisiDanIntegrasi(
                    $validated['tgl_awal'],
                    $tahun,
                    $bulan,
                    $hari,
                    $validated['agama'] ?? 'islam',
                    (int) ($validated['remisi_awal_hari'] ?? 0),
                    $namaWbp,
                    $noRegister,
                    $includeDasawarsa,
                    $kategoriTambahan,
                    $targetBasis,
                    $tahunMulaiTambahan
                );
            } elseif ($mode === 'mundur') {
                $validated = $request->validate([
                    'tgl_awal' => 'required|date',
                    'tgl_ekspirasi' => 'required|date|after_or_equal:tgl_awal',
                ]);

                $result = $this->calculator->hitungMundur(
                    $validated['tgl_awal'],
                    $validated['tgl_ekspirasi']
                );
            } elseif ($mode === 'sisa') {
                $validated = $request->validate([
                    'tgl_perhitungan' => 'required|date',
                    'tgl_ekspirasi' => 'required|date|after_or_equal:tgl_perhitungan',
                ]);

                $result = $this->calculator->hitungSisaPidana(
                    $validated['tgl_perhitungan'],
                    $validated['tgl_ekspirasi']
                );
            } else {
                $validated = $request->validate([
                    'tgl_awal' => 'required|date',
                    'tahun' => 'nullable|integer|min:0',
                    'bulan' => 'nullable|integer|min:0',
                    'hari' => 'nullable|integer|min:0',
                ]);

                $tahun = (int) ($validated['tahun'] ?? 0);
                $bulan = (int) ($validated['bulan'] ?? 0);
                $hari = (int) ($validated['hari'] ?? 0);

                if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Harap masukkan lama vonis hukuman (Tahun, Bulan, atau Hari)!',
                    ], 422);
                }

                $result = $this->calculator->hitungMaju(
                    $validated['tgl_awal'],
                    $tahun,
                    $bulan,
                    $hari
                );
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export Lembar Estimasi Telram & Proyeksi Remisi ke Dokumen PDF Resmi
     */
    public function exportPdf(Request $request): Response
    {
        $validated = $request->validate([
            'tgl_awal' => 'required|date',
            'tahun' => 'nullable|integer|min:0',
            'bulan' => 'nullable|integer|min:0',
            'hari' => 'nullable|integer|min:0',
            'agama' => 'nullable|string|in:islam,kristen,katolik,hindu,buddha,khonghucu',
            'remisi_awal_hari' => 'nullable|integer|min:0',
            'nama_wbp' => 'nullable|string|max:150',
            'no_register' => 'nullable|string|max:100',
            'include_dasawarsa' => 'nullable|boolean',
            'kategori_tambahan' => 'nullable|string|in:none,pemuka,jasa_negara',
            'tahun_mulai_tambahan' => 'nullable|integer|min:1|max:50',
            'target_basis' => 'nullable|string|in:2_3,ekspirasi',
        ]);

        $tahun = (int) ($validated['tahun'] ?? 0);
        $bulan = (int) ($validated['bulan'] ?? 0);
        $hari = (int) ($validated['hari'] ?? 0);

        if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
            abort(422, 'Harap masukkan lama vonis hukuman (Tahun, Bulan, atau Hari)!');
        }

        $namaWbp = trim((string) ($validated['nama_wbp'] ?? '')) ?: 'Narapidana';
        $noRegister = trim((string) ($validated['no_register'] ?? '')) ?: '-';
        $includeDasawarsa = filter_var($validated['include_dasawarsa'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $kategoriTambahan = $validated['kategori_tambahan'] ?? 'none';
        $tahunMulaiTambahan = (int) ($validated['tahun_mulai_tambahan'] ?? 1);
        $targetBasis = $validated['target_basis'] ?? '2_3';

        $data = $this->calculator->hitungProyeksiRemisiDanIntegrasi(
            $validated['tgl_awal'],
            $tahun,
            $bulan,
            $hari,
            $validated['agama'] ?? 'islam',
            (int) ($validated['remisi_awal_hari'] ?? 0),
            $namaWbp,
            $noRegister,
            $includeDasawarsa,
            $kategoriTambahan,
            $targetBasis,
            $tahunMulaiTambahan
        );

        $pdfContent = $this->calculator->generatePdfReport($data);
        $filename = 'Telram_Proyeksi_'.preg_replace('/[^A-Za-z0-9_-]/', '_', $namaWbp).'_'.date('Ymd_His').'.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
