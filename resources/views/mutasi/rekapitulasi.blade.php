@extends('layouts.app')

@section('title', 'Rekapitulasi Mutasi Narapidana - Sultan Banten')
@section('page_title')
    <i class="fas fa-chart-pie me-2 text-primary"></i>Rekapitulasi &amp; Arus Mutasi Narapidana Se-Banten
@endsection

@push('styles')
<style>
    .card-rekap-stat {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('mutasi.index') }}" class="text-decoration-none">Mutasi Narapidana</a></li>
            <li class="breadcrumb-item active" aria-current="page">Rekapitulasi Tahun {{ $tahun }}</li>
        </ol>
    </nav>

    <!-- Header Filter & Export -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-bar me-2 text-primary"></i>Laporan Rekapitulasi Pemindahan Narapidana Antar-UPT</h5>
                    <small class="text-muted">Statistik persebaran perkara, arus keluar-masuk antar-Lapas/Rutan, dan persetujuan Kanwil</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="{{ route('mutasi.rekapitulasi') }}" class="d-flex align-items-center gap-2">
                        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                            @endfor
                        </select>
                    </form>
                    <a href="{{ route('mutasi.export-excel', ['tahun' => $tahun]) }}" class="btn btn-success btn-sm px-3 shadow-sm text-nowrap">
                        <i class="fas fa-file-excel me-1"></i> Unduh Rekap (CSV/Excel)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik Utama -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card card-rekap-stat bg-white p-3 border-start border-primary border-4 text-center">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10px;">Total Berkas</small>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($totalUsulan) }}</div>
                <small class="text-muted" style="font-size: 10px;">Pengajuan</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-rekap-stat bg-white p-3 border-start border-danger border-4 text-center">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10px;">Perkara Narkotika</small>
                <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($totalNarkotika) }}</div>
                <small class="text-muted" style="font-size: 10px;">Narapidana</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-rekap-stat bg-white p-3 border-start border-info border-4 text-center">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10px;">Pidana Umum</small>
                <div class="fs-4 fw-bold text-info mt-1">{{ number_format($totalPidum) }}</div>
                <small class="text-muted" style="font-size: 10px;">Narapidana</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-rekap-stat bg-white p-3 border-start border-warning border-4 text-center">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10px;">Korupsi (Tipikor)</small>
                <div class="fs-4 fw-bold text-warning text-dark mt-1">{{ number_format($totalKorupsi) }}</div>
                <small class="text-muted" style="font-size: 10px;">Narapidana</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-rekap-stat bg-white p-3 border-start border-secondary border-4 text-center">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10px;">Total WBP Diusulkan</small>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalWbp) }}</div>
                <small class="text-muted" style="font-size: 10px;">Semua Perkara</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-rekap-stat bg-white p-3 border-start border-success border-4 text-center">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10px;">Disetujui Kanwil</small>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($totalDisetujuiWbp) }}</div>
                <small class="text-muted" style="font-size: 10px;">WBP Realisasi</small>
            </div>
        </div>
    </div>

    <!-- Kategori Mutasi Pills -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <span class="fw-semibold small text-muted"><i class="fas fa-tags me-1 text-primary"></i>Distribusi Berdasarkan Kategori Mutasi:</span>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2">
                        Pembinaan: <strong>{{ number_format($kategoriSummary['Pembinaan'] ?? 0) }} WBP</strong>
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                        Keamanan (Kamtib): <strong>{{ number_format($kategoriSummary['Keamanan'] ?? 0) }} WBP</strong>
                    </span>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-2">
                        Overkapasitas: <strong>{{ number_format($kategoriSummary['Overkapasitas'] ?? 0) }} WBP</strong>
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
                        Lainnya: <strong>{{ number_format($kategoriSummary['Lainnya'] ?? 0) }} WBP</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Arus Mutasi Per UPT -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-table me-2 text-primary"></i>Matriks Arus Mutasi Narapidana Per UPT (Tahun {{ $tahun }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" width="4%" rowspan="2">No</th>
                        <th width="24%" rowspan="2">Unit Pelaksana Teknis (UPT)</th>
                        <th class="text-center" width="8%" rowspan="2">Kapasitas</th>
                        <th class="text-center bg-primary bg-opacity-10" colspan="5">Arus Keluar (Usulan UPT Pengusul)</th>
                        <th class="text-center bg-success bg-opacity-10" width="14%" rowspan="2">Arus Masuk Disetujui Kanwil (Penerima)</th>
                    </tr>
                    <tr>
                        <th class="text-center" width="8%">Berkas</th>
                        <th class="text-center text-danger" width="8%">Narkotika</th>
                        <th class="text-center text-primary" width="8%">Pidum</th>
                        <th class="text-center text-warning text-dark" width="8%">Korupsi</th>
                        <th class="text-center fw-bold text-dark" width="9%">Total Usul</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totBerkas = 0;
                        $totNark = 0;
                        $totPid = 0;
                        $totKor = 0;
                        $totKeluar = 0;
                        $totMasuk = 0;
                    @endphp
                    @foreach($rekapUpt as $idx => $r)
                    @php
                        $totBerkas += $r['usul_keluar_berkas'];
                        $totNark += $r['keluar_narkotika'];
                        $totPid += $r['keluar_pidum'];
                        $totKor += $r['keluar_korupsi'];
                        $totKeluar += $r['usul_keluar_wbp'];
                        $totMasuk += $r['masuk_wbp'];
                    @endphp
                    <tr>
                        <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $r['upt']->nama_upt }}</div>
                        </td>
                        <td class="text-center font-monospace">{{ number_format($r['upt']->kapasitas) }}</td>
                        <td class="text-center font-monospace">{{ number_format($r['usul_keluar_berkas']) }}</td>
                        <td class="text-center font-monospace text-danger">{{ number_format($r['keluar_narkotika']) }}</td>
                        <td class="text-center font-monospace text-primary">{{ number_format($r['keluar_pidum']) }}</td>
                        <td class="text-center font-monospace text-warning text-dark">{{ number_format($r['keluar_korupsi']) }}</td>
                        <td class="text-center font-monospace fw-bold text-dark">{{ number_format($r['usul_keluar_wbp']) }}</td>
                        <td class="text-center font-monospace fw-bold text-success bg-success bg-opacity-10">
                            {{ number_format($r['masuk_wbp']) }} WBP
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-center text-uppercase">Total Seluruh UPT Se-Banten</td>
                        <td class="text-center font-monospace">{{ number_format($totBerkas) }}</td>
                        <td class="text-center font-monospace text-danger">{{ number_format($totNark) }}</td>
                        <td class="text-center font-monospace text-primary">{{ number_format($totPid) }}</td>
                        <td class="text-center font-monospace text-warning text-dark">{{ number_format($totKor) }}</td>
                        <td class="text-center font-monospace text-dark">{{ number_format($totKeluar) }} WBP</td>
                        <td class="text-center font-monospace text-success bg-success bg-opacity-10">{{ number_format($totMasuk) }} WBP</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
