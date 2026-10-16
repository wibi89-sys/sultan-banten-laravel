<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Services\TelramCalculatorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            if ($mode === 'mundur') {
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
}
