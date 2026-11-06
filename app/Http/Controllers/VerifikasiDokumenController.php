<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\TppNaskahKanwil;
use Illuminate\View\View;

class VerifikasiDokumenController extends Controller
{
    /**
     * Halaman Publik Otentifikasi Keabsahan Dokumen Digital (dapat dibuka tanpa login).
     */
    public function show(string $kode): View
    {
        $naskah = TppNaskahKanwil::with(['sidangTpp.upt'])
            ->where('kode_verifikasi', $kode)
            ->first();

        $instansi = Instansi::getFirst();

        return view('verifikasi_dokumen.show', compact('naskah', 'kode', 'instansi'));
    }
}
