@extends('layouts.app')

@section('title', 'Dinamika & Fluktuasi Tambah Kurang Penghuni - Sultan Banten')
@section('page_title')
    <i class="fas fa-exchange-alt me-2 text-primary"></i>Dinamika &amp; Fluktuasi Tambah Kurang Penghuni
@endsection

@push('styles')
<style>
    .stat-kpi-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    .stat-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .stat-kpi-card .stat-icon {
        position: absolute;
        right: 15px;
        bottom: 12px;
        font-size: 42px;
        opacity: 0.16;
    }
    .badge-delta-pos {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
        font-weight: 700;
    }
    .badge-delta-neg {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-weight: 700;
    }
    .badge-delta-zero {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-weight: 600;
    }
    .table-fluktuasi th {
        font-size: 11.5px;
        letter-spacing: 0.2px;
        text-transform: uppercase;
        vertical-align: middle;
    }
    .table-fluktuasi td {
        vertical-align: middle;
        font-size: 13px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Toolbar & Filter Tanggal Pembanding -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-balance-scale text-primary me-2"></i>Laporan Fluktuasi Tambah Kurang Penghuni</h5>
                    <p class="text-muted small mb-0">Analisis pergerakan harian Tahanan (A.I - A.V), Narapidana, Alih Status Vonis, dan Bebas Harian / Bebas Demi Hukum</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Cetak Laporan
                    </button>
                </div>
            </div>

            <form method="GET" action="{{ route('fluktuasi.index') }}" class="row g-2 align-items-end pt-2 border-top">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tanggal Acuan (Hari Ini)</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $tanggal }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tanggal Pembanding (Kemarin / H-1)</label>
                    <input type="date" name="tanggal_pembanding" class="form-control form-control-sm" value="{{ $tanggalPembanding }}" required>
                </div>
                @if(!$userUptFilter)
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Filter UPT</label>
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Seluruh UPT Se-Wilayah Banten --</option>
                        @foreach($allUptList as $u)
                        <option value="{{ $u->id }}" {{ $filterUpt == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3 w-100">
                        <i class="fas fa-filter me-1"></i>Analisis
                    </button>
                    <a href="{{ route('fluktuasi.index') }}" class="btn btn-light btn-sm text-secondary border">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 4 Kartu KPI Ringkasan Fluktuasi Se-Banten -->
    <div class="row g-3 mb-4">
        <!-- Kartu 1: Tahanan -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small fw-semibold text-white-50">Tahanan Se-Banten</div>
                        <div class="h3 fw-bold mb-0 mt-1">{{ number_format($summary['total_tahanan_curr']) }}</div>
                    </div>
                    @if($summary['delta_tahanan'] > 0)
                        <span class="badge bg-white text-danger fw-bold shadow-xs">+{{ number_format($summary['delta_tahanan']) }} (Naik)</span>
                    @elseif($summary['delta_tahanan'] < 0)
                        <span class="badge bg-white text-success fw-bold shadow-xs">{{ number_format($summary['delta_tahanan']) }} (Turun)</span>
                    @else
                        <span class="badge bg-white text-secondary fw-semibold shadow-xs">0 (Stabil)</span>
                    @endif
                </div>
                <div class="small mt-2 text-white-50">
                    Kemarin: <strong>{{ number_format($summary['total_tahanan_prev']) }}</strong> Orang
                </div>
                <i class="fas fa-user-lock stat-icon"></i>
            </div>
        </div>

        <!-- Kartu 2: Narapidana -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card bg-info text-white p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small fw-semibold text-white-50">Narapidana Se-Banten</div>
                        <div class="h3 fw-bold mb-0 mt-1">{{ number_format($summary['total_napi_curr']) }}</div>
                    </div>
                    @if($summary['delta_napi'] > 0)
                        <span class="badge bg-white text-warning text-dark fw-bold shadow-xs">+{{ number_format($summary['delta_napi']) }}</span>
                    @elseif($summary['delta_napi'] < 0)
                        <span class="badge bg-white text-danger fw-bold shadow-xs">{{ number_format($summary['delta_napi']) }}</span>
                    @else
                        <span class="badge bg-white text-secondary fw-semibold shadow-xs">0 (Stabil)</span>
                    @endif
                </div>
                <div class="small mt-2 text-white-50">
                    Kemarin: <strong>{{ number_format($summary['total_napi_prev']) }}</strong> Orang
                </div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>

        <!-- Kartu 3: Total Isi Penghuni -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card bg-dark text-white p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small fw-semibold text-white-50">Total Isi Penghuni</div>
                        <div class="h3 fw-bold mb-0 mt-1">{{ number_format($summary['total_penghuni_curr']) }}</div>
                    </div>
                    @if($summary['delta_penghuni'] > 0)
                        <span class="badge bg-danger text-white fw-bold shadow-xs">+{{ number_format($summary['delta_penghuni']) }} Net</span>
                    @elseif($summary['delta_penghuni'] < 0)
                        <span class="badge bg-success text-white fw-bold shadow-xs">{{ number_format($summary['delta_penghuni']) }} Net</span>
                    @else
                        <span class="badge bg-secondary text-white fw-semibold shadow-xs">0 Net</span>
                    @endif
                </div>
                <div class="small mt-2 text-white-50">
                    Kemarin: <strong>{{ number_format($summary['total_penghuni_prev']) }}</strong> Orang
                </div>
                <i class="fas fa-chart-line stat-icon"></i>
            </div>
        </div>

        <!-- Kartu 4: Total Bebas Harian & BDH -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small fw-semibold text-white-50">Total Bebas (Pengurang)</div>
                        <div class="h3 fw-bold mb-0 mt-1">{{ number_format($summary['total_bebas']) }} <span class="fs-6 fw-normal">Orang</span></div>
                    </div>
                    @if($summary['total_bdh'] > 0)
                        <span class="badge bg-warning text-dark fw-bold shadow-xs">{{ $summary['total_bdh'] }} BDH</span>
                    @endif
                </div>
                <div class="small mt-2 text-white-50">
                    PB: {{ $summary['total_pb'] }} &bull; CB: {{ $summary['total_cb'] }} &bull; Murni: {{ $summary['total_bebas_murni'] }}
                </div>
                <i class="fas fa-door-open stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Alert Edukasi Dinamika Status -->
    <div class="alert alert-light border d-flex align-items-center gap-3 p-3 rounded-3 shadow-xs mb-4" style="background-color: #f8fafc;">
        <i class="fas fa-info-circle text-primary fs-3 flex-shrink-0"></i>
        <div class="small">
            <strong>Panduan Membaca Dinamika Tambah Kurang:</strong> 
            Perubahan jumlah penghuni hari ini (posisi apel pagi) dipengaruhi oleh pergerakan warga binaan pada hari kemarin (pembebasan, alih status, dan penerimaan baru). 
            Ketika Tahanan menerima vonis hakim yang telah berkekuatan hukum tetap (<em>inkrah</em>), statusnya beralih menjadi <strong>Narapidana</strong> (Tahanan berkurang, Narapidana bertambah, Total Penghuni tetap).
            Perpindahan antar-tingkat penahanan (A.I &rarr; A.II &rarr; A.III dst.) juga dipantau sebagai dinamika proses peradilan.
        </div>
    </div>

    <!-- Tabel Matriks Fluktuasi Tambah Kurang Per UPT -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-table me-2 text-primary"></i>Matriks Fluktuasi Harian Per UPT ({{ \Carbon\Carbon::parse($tanggalPembanding)->translatedFormat('d M') }} &rarr; {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }})
            </h6>
            <small class="text-muted"><i class="fas fa-check-double text-success me-1"></i>Data Terintegrasi Laporan Penghuni, Overstaying &amp; Bebas Harian</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-fluktuasi mb-0 align-middle">
                <thead class="table-light text-secondary text-center">
                    <tr>
                        <th rowspan="2" style="width: 45px;">No</th>
                        <th rowspan="2" style="min-width: 190px;" class="text-start">Unit Pelaksana Teknis (UPT)</th>
                        <th colspan="3" class="bg-primary bg-opacity-10 text-primary">1. Tahanan (Overstaying A1-A5)</th>
                        <th colspan="3" class="bg-info bg-opacity-10 text-info">2. Narapidana</th>
                        <th colspan="3" class="bg-dark bg-opacity-10 text-dark">3. Total Penghuni</th>
                        <th rowspan="2" style="min-width: 140px;">
                            4. Bebas Harian (Pengurang)
                            <div class="small text-muted fw-normal" style="font-size: 10px; text-transform: none;">
                                Periode: {{ \Carbon\Carbon::parse($tglBebasMulai)->format('d/m') }}@if($tglBebasSelesai != $tglBebasMulai)-{{ \Carbon\Carbon::parse($tglBebasSelesai)->format('d/m') }}@endif
                            </div>
                        </th>
                        <th rowspan="2" style="min-width: 250px;" class="text-start">5. Analisis Dinamika &amp; Penyebab</th>
                        <th rowspan="2" style="width: 80px;">Rincian</th>
                    </tr>
                    <tr>
                        <!-- Sub-kolom Tahanan -->
                        <th style="width: 65px;" title="Jumlah Tahanan Tanggal Pembanding">{{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }}</th>
                        <th style="width: 65px;" title="Jumlah Tahanan Tanggal Acuan">{{ \Carbon\Carbon::parse($tanggal)->format('d/m') }}</th>
                        <th style="width: 70px;">&plusmn; Tahanan</th>
                        <!-- Sub-kolom Napi -->
                        <th style="width: 65px;" title="Jumlah Narapidana Tanggal Pembanding">{{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }}</th>
                        <th style="width: 65px;" title="Jumlah Narapidana Tanggal Acuan">{{ \Carbon\Carbon::parse($tanggal)->format('d/m') }}</th>
                        <th style="width: 70px;">&plusmn; Napi</th>
                        <!-- Sub-kolom Total -->
                        <th style="width: 70px;" title="Total Penghuni Tanggal Pembanding">{{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }}</th>
                        <th style="width: 70px;" title="Total Penghuni Tanggal Acuan">{{ \Carbon\Carbon::parse($tanggal)->format('d/m') }}</th>
                        <th style="width: 75px;">&Delta; Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $idx => $r)
                    <tr>
                        <td class="text-center text-muted fw-bold">{{ $idx + 1 }}</td>
                        <td class="fw-semibold text-dark text-start">
                            {{ $r['nama_upt'] }}
                        </td>

                        <!-- Tahanan -->
                        <td class="text-center text-muted">{{ number_format($r['tahanan_prev']) }}</td>
                        <td class="text-center fw-bold">{{ number_format($r['tahanan_curr']) }}</td>
                        <td class="text-center">
                            @if($r['delta_tahanan'] > 0)
                                <span class="badge badge-delta-neg px-2 py-1">+{{ $r['delta_tahanan'] }}</span>
                            @elseif($r['delta_tahanan'] < 0)
                                <span class="badge badge-delta-pos px-2 py-1">{{ $r['delta_tahanan'] }}</span>
                            @else
                                <span class="badge badge-delta-zero px-2 py-1">0</span>
                            @endif
                        </td>

                        <!-- Narapidana -->
                        <td class="text-center text-muted">{{ number_format($r['napi_prev']) }}</td>
                        <td class="text-center fw-bold">{{ number_format($r['napi_curr']) }}</td>
                        <td class="text-center">
                            @if($r['delta_napi'] > 0)
                                <span class="badge badge-delta-neg px-2 py-1">+{{ $r['delta_napi'] }}</span>
                            @elseif($r['delta_napi'] < 0)
                                <span class="badge badge-delta-pos px-2 py-1">{{ $r['delta_napi'] }}</span>
                            @else
                                <span class="badge badge-delta-zero px-2 py-1">0</span>
                            @endif
                        </td>

                        <!-- Total Penghuni -->
                        <td class="text-center text-muted fw-semibold">{{ number_format($r['penghuni_prev']) }}</td>
                        <td class="text-center fw-bold text-primary">{{ number_format($r['penghuni_curr']) }}</td>
                        <td class="text-center">
                            @if($r['delta_penghuni'] > 0)
                                <span class="badge bg-danger text-white px-2 py-1 fw-bold">+{{ $r['delta_penghuni'] }}</span>
                            @elseif($r['delta_penghuni'] < 0)
                                <span class="badge bg-success text-white px-2 py-1 fw-bold">{{ $r['delta_penghuni'] }}</span>
                            @else
                                <span class="badge bg-secondary text-white px-2 py-1">0</span>
                            @endif
                        </td>

                        <!-- Bebas Harian -->
                        <td class="text-center">
                            @if($r['total_bebas'] > 0)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                    {{ $r['total_bebas'] }} Orang
                                </span>
                                @if($r['bdh'] > 0)
                                <div class="mt-1">
                                    <span class="badge bg-danger text-white" style="font-size: 10px;">{{ $r['bdh'] }} BDH</span>
                                </div>
                                @endif
                            @else
                                <span class="text-muted small">0 (Nihil)</span>
                            @endif
                        </td>

                        <!-- Analisis & Penyebab Dinamika -->
                        <td class="text-start small">
                            @if(!empty($r['analisis']))
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                                    @foreach($r['analisis'] as $an)
                                        <li>{!! $an !!}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">Tidak ada perubahan data</span>
                            @endif
                        </td>

                        <!-- Aksi: Detail Modal -->
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-1 shadow-xs" title="Lihat Breakdown Rincian" onclick='bukaModalDetailFluktuasi(@json($r))'>
                                <i class="fas fa-eye me-1"></i>Rincian
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6>Tidak ada data untuk tanggal yang dipilih.</h6>
                            <p class="small">Pastikan data penghuni pada kedua tanggal sudah diinput oleh UPT.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <!-- Footer Total Akumulasi -->
                <tfoot class="table-dark text-center fw-bold">
                    <tr>
                        <td colspan="2" class="text-start">TOTAL SE-WILAYAH BANTEN</td>
                        <td>{{ number_format($summary['total_tahanan_prev']) }}</td>
                        <td>{{ number_format($summary['total_tahanan_curr']) }}</td>
                        <td>
                            @if($summary['delta_tahanan'] > 0) +{{ number_format($summary['delta_tahanan']) }}
                            @else {{ number_format($summary['delta_tahanan']) }}
                            @endif
                        </td>
                        <td>{{ number_format($summary['total_napi_prev']) }}</td>
                        <td>{{ number_format($summary['total_napi_curr']) }}</td>
                        <td>
                            @if($summary['delta_napi'] > 0) +{{ number_format($summary['delta_napi']) }}
                            @else {{ number_format($summary['delta_napi']) }}
                            @endif
                        </td>
                        <td>{{ number_format($summary['total_penghuni_prev']) }}</td>
                        <td>{{ number_format($summary['total_penghuni_curr']) }}</td>
                        <td>
                            @if($summary['delta_penghuni'] > 0) <span class="text-danger">+{{ number_format($summary['delta_penghuni']) }}</span>
                            @elseif($summary['delta_penghuni'] < 0) <span class="text-success">{{ number_format($summary['delta_penghuni']) }}</span>
                            @else 0
                            @endif
                        </td>
                        <td>{{ number_format($summary['total_bebas']) }} Orang</td>
                        <td colspan="2" class="text-start small fw-normal">
                            <strong>Akumulasi Wilayah:</strong> 
                            {{ number_format($summary['estimasi_tahanan_baru']) }} Tahanan Masuk &bull; 
                            {{ number_format($summary['estimasi_alih_status']) }} Vonis Inkrah &bull; 
                            {{ number_format($summary['total_bebas']) }} Bebas
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal Breakdown Interaktif Rincian Fluktuasi -->
<div class="modal fade" id="modalDetailFluktuasi" tabindex="-1" aria-labelledby="modalDetailFluktuasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="modalDetailFluktuasiLabel">
                        <i class="fas fa-balance-scale text-warning me-2 fs-5"></i>Rincian Fluktuasi Penghuni
                    </h5>
                    <div id="mdf_upt_title" class="text-white fw-semibold small mt-1"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nav Tabs -->
                <ul class="nav nav-pills nav-fill mb-3" id="fluktuasiTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small fw-bold py-2" id="tab-overstaying-btn" data-bs-toggle="pill" data-bs-target="#tab-overstaying" type="button" role="tab">
                            <i class="fas fa-clock me-1"></i>Tahap Penahanan (A.I - A.V)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small fw-bold py-2" id="tab-napi-btn" data-bs-toggle="pill" data-bs-target="#tab-napi" type="button" role="tab">
                            <i class="fas fa-users me-1"></i>Komparasi Narapidana
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small fw-bold py-2" id="tab-bebas-btn" data-bs-toggle="pill" data-bs-target="#tab-bebas" type="button" role="tab">
                            <i class="fas fa-door-open me-1"></i>Rincian Bebas &amp; Rekonsiliasi
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="fluktuasiTabContent">
                    <!-- Tab 1: Tahap Penahanan A.I s/d A.V -->
                    <div class="tab-pane fade show active" id="tab-overstaying" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center mb-0 align-middle">
                                <thead class="table-light small fw-bold">
                                    <tr>
                                        <th class="text-start">Tingkat Penahanan</th>
                                        <th width="25%">Kemarin ({{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }})</th>
                                        <th width="25%">Hari Ini ({{ \Carbon\Carbon::parse($tanggal)->format('d/m') }})</th>
                                        <th width="20%">Fluktuasi (&plusmn;)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-semibold">A.I - Kepolisian</td>
                                        <td id="mdf_ai_prev">-</td>
                                        <td id="mdf_ai_curr" class="fw-bold">-</td>
                                        <td id="mdf_delta_ai">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-semibold">A.II - Kejaksaan</td>
                                        <td id="mdf_aii_prev">-</td>
                                        <td id="mdf_aii_curr" class="fw-bold">-</td>
                                        <td id="mdf_delta_aii">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-semibold">A.III - Pengadilan Negeri</td>
                                        <td id="mdf_aiii_prev">-</td>
                                        <td id="mdf_aiii_curr" class="fw-bold">-</td>
                                        <td id="mdf_delta_aiii">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-semibold">A.IV - Pengadilan Tinggi (Banding)</td>
                                        <td id="mdf_aiv_prev">-</td>
                                        <td id="mdf_aiv_curr" class="fw-bold">-</td>
                                        <td id="mdf_delta_aiv">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-semibold">A.V - Mahkamah Agung (Kasasi)</td>
                                        <td id="mdf_av_prev">-</td>
                                        <td id="mdf_av_curr" class="fw-bold">-</td>
                                        <td id="mdf_delta_av">-</td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td class="text-start">TOTAL TAHANAN</td>
                                        <td id="mdf_tot_prev">-</td>
                                        <td id="mdf_tot_curr">-</td>
                                        <td id="mdf_tot_delta">-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="fas fa-info-circle text-primary me-1"></i>Penurunan pada <strong>A.III (Pengadilan Negeri)</strong>, <strong>A.IV (Pengadilan Tinggi/Banding)</strong>, atau <strong>A.V (Mahkamah Agung/Kasasi)</strong> yang dibarengi naiknya jumlah Narapidana menandakan perkara telah berkekuatan hukum tetap (<em>inkrah</em>) di tingkat peradilan masing-masing dan beralih status menjadi Narapidana.
                        </div>
                    </div>

                    <!-- Tab 2: Komparasi Narapidana Per Tindak Pidana -->
                    <div class="tab-pane fade" id="tab-napi" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center mb-0 align-middle">
                                <thead class="table-light small fw-bold">
                                    <tr>
                                        <th class="text-start">Klasifikasi Tindak Pidana</th>
                                        <th width="22%">Kemarin ({{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }})</th>
                                        <th width="22%">Hari Ini ({{ \Carbon\Carbon::parse($tanggal)->format('d/m') }})</th>
                                        <th width="18%">Fluktuasi (&plusmn;)</th>
                                        <th width="22%">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="mdf_napi_tbody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td class="text-start">TOTAL NARAPIDANA</td>
                                        <td id="mdf_napi_tot_prev">-</td>
                                        <td id="mdf_napi_tot_curr">-</td>
                                        <td id="mdf_napi_tot_delta">-</td>
                                        <td id="mdf_napi_tot_ket">-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="fas fa-info-circle text-primary me-1"></i>Perbandingan jumlah narapidana berdasarkan 10 klasifikasi perkara pokok tindak pidana.
                        </div>
                    </div>

                    <!-- Tab 3: Rincian Bebas Harian & Rekonsiliasi -->
                    <div class="tab-pane fade" id="tab-bebas" role="tabpanel">
                        <!-- Alert Box Komparasi & Rekonsiliasi Bebas -->
                        <div id="mdf_rekonsiliasi_box" class="mb-3">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <div class="small fw-semibold text-secondary mb-2">
                            <i class="fas fa-list-alt me-1"></i>Kategori Pembebasan Kemarin ({{ \Carbon\Carbon::parse($tglBebasMulai)->translatedFormat('d M Y') }}):
                        </div>
                        <div class="row g-2 text-center" id="mdf_bebas_container">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function formatBadge(val) {
    if (val > 0) return `<span class="badge badge-delta-neg px-2 py-1">+${val}</span>`;
    if (val < 0) return `<span class="badge badge-delta-pos px-2 py-1">${val}</span>`;
    return `<span class="badge badge-delta-zero px-2 py-1">0</span>`;
}

function bukaModalDetailFluktuasi(row) {
    document.getElementById('mdf_upt_title').innerHTML = 
        `<span class="badge bg-warning text-dark fw-bold px-2 py-1 me-2">${row.nama_upt}</span>` +
        `<span class="text-white">Fluktuasi Tanggal <strong>{{ $tanggalPembanding }}</strong> &rarr; <strong>{{ $tanggal }}</strong></span>`;

    // Tab 1: Overstaying A.I - A.V
    document.getElementById('mdf_ai_prev').textContent = row.ai_prev.toLocaleString();
    document.getElementById('mdf_ai_curr').textContent = row.ai_curr.toLocaleString();
    document.getElementById('mdf_delta_ai').innerHTML = formatBadge(row.delta_ai);

    document.getElementById('mdf_aii_prev').textContent = row.aii_prev.toLocaleString();
    document.getElementById('mdf_aii_curr').textContent = row.aii_curr.toLocaleString();
    document.getElementById('mdf_delta_aii').innerHTML = formatBadge(row.delta_aii);

    document.getElementById('mdf_aiii_prev').textContent = row.aiii_prev.toLocaleString();
    document.getElementById('mdf_aiii_curr').textContent = row.aiii_curr.toLocaleString();
    document.getElementById('mdf_delta_aiii').innerHTML = formatBadge(row.delta_aiii);

    document.getElementById('mdf_aiv_prev').textContent = row.aiv_prev.toLocaleString();
    document.getElementById('mdf_aiv_curr').textContent = row.aiv_curr.toLocaleString();
    document.getElementById('mdf_delta_aiv').innerHTML = formatBadge(row.delta_aiv);

    document.getElementById('mdf_av_prev').textContent = row.av_prev.toLocaleString();
    document.getElementById('mdf_av_curr').textContent = row.av_curr.toLocaleString();
    document.getElementById('mdf_delta_av').innerHTML = formatBadge(row.delta_av);

    document.getElementById('mdf_tot_prev').textContent = row.tahanan_prev.toLocaleString();
    document.getElementById('mdf_tot_curr').textContent = row.tahanan_curr.toLocaleString();
    document.getElementById('mdf_tot_delta').innerHTML = formatBadge(row.delta_tahanan);

    // Tab 2: Komparasi Narapidana
    const napiTbody = document.getElementById('mdf_napi_tbody');
    let napiRowsHtml = '';
    if (row.komparasi_napi && row.komparasi_napi.length > 0) {
        row.komparasi_napi.forEach(k => {
            let ket = '<span class="text-muted small">Tetap</span>';
            if (k.delta < 0) {
                ket = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Berkurang ${Math.abs(k.delta)}</span>`;
            } else if (k.delta > 0) {
                ket = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Bertambah ${k.delta}</span>`;
            }

            napiRowsHtml += `
                <tr>
                    <td class="text-start fw-semibold">${k.klasifikasi}</td>
                    <td>${k.prev.toLocaleString()}</td>
                    <td class="fw-bold">${k.curr.toLocaleString()}</td>
                    <td>${formatBadge(k.delta)}</td>
                    <td>${ket}</td>
                </tr>
            `;
        });
    }
    napiTbody.innerHTML = napiRowsHtml;
    document.getElementById('mdf_napi_tot_prev').textContent = row.napi_prev.toLocaleString();
    document.getElementById('mdf_napi_tot_curr').textContent = row.napi_curr.toLocaleString();
    document.getElementById('mdf_napi_tot_delta').innerHTML = formatBadge(row.delta_napi);
    document.getElementById('mdf_napi_tot_ket').innerHTML = row.delta_napi < 0 
        ? `<span class="text-danger fw-bold">Berkurang ${Math.abs(row.delta_napi)}</span>` 
        : (row.delta_napi > 0 ? `<span class="text-success fw-bold">Bertambah ${row.delta_napi}</span>` : '<span class="text-muted">Stabil</span>');

    // Tab 3: Rincian Bebas & Rekonsiliasi Box
    const rekonBox = document.getElementById('mdf_rekonsiliasi_box');
    let rekonHtml = `
        <div class="card border bg-light p-3 mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-sm-4 text-center border-end">
                    <small class="text-muted d-block" style="font-size: 11px;">Narapidana Kemarin</small>
                    <span class="fs-6 fw-bold text-dark">${row.napi_prev.toLocaleString()} Orang</span>
                </div>
                <div class="col-sm-4 text-center border-end">
                    <small class="text-muted d-block" style="font-size: 11px;">Narapidana Hari Ini</small>
                    <span class="fs-6 fw-bold text-primary">${row.napi_curr.toLocaleString()} Orang</span>
                </div>
                <div class="col-sm-4 text-center">
                    <small class="text-muted d-block" style="font-size: 11px;">Perubahan Net</small>
                    <span class="fs-6 fw-bold ${row.delta_napi < 0 ? 'text-danger' : (row.delta_napi > 0 ? 'text-success' : 'text-secondary')}">
                        ${row.delta_napi > 0 ? '+' : ''}${row.delta_napi} Orang
                    </span>
                </div>
            </div>
        </div>
    `;

    if (row.delta_napi < 0) {
        const selisihNapi = Math.abs(row.delta_napi);
        const rincianBerkurang = row.napi_berkurang_text ? ` pada klasifikasi: <strong>${row.napi_berkurang_text}</strong>` : '';

        if (row.total_bebas === 0) {
            rekonHtml += `
                <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                    <i class="fas fa-exclamation-triangle text-warning fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Pengurangan Narapidana Belum Terketerangan di Bebas Harian:</strong><br>
                        Terdapat pengurangan <strong>-${selisihNapi} Narapidana</strong>${rincianBerkurang}, 
                        namun <strong>Laporan Bebas Harian kemarin tercatat 0 (Nihil)</strong>.<br>
                        <span class="text-dark">&bull; <strong>Kemungkinan 1</strong>: Narapidana telah <strong>dipindahkan / mutasi keluar</strong> ke Lapas lain.<br>
                        &bull; <strong>Kemungkinan 2</strong>: Pembebasan (Bebas Murni/PB/CB) belum terinput oleh operator pada tanggal tersebut.</span>
                    </div>
                </div>
            `;
        } else if (selisihNapi > row.total_bebas) {
            const gap = selisihNapi - row.total_bebas;
            rekonHtml += `
                <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle text-warning fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Sebagian Pengurangan Belum Terketerangan:</strong><br>
                        Narapidana berkurang <strong>-${selisihNapi} orang</strong>${rincianBerkurang}. 
                        Tercatat <strong>${row.total_bebas} orang di Laporan Bebas Harian</strong>, namun masih terdapat selisih <strong>-${gap} orang</strong> di luar laporan bebas harian (potensi mutasi keluar).
                    </div>
                </div>
            `;
        } else if (selisihNapi === row.total_bebas) {
            rekonHtml += `
                <div class="alert alert-success border-success d-flex align-items-center gap-2 py-2 px-3 small mb-3">
                    <i class="fas fa-check-circle text-success fs-5 flex-shrink-0"></i>
                    <div>
                        <strong>Data Sinkron Sempurna:</strong><br>
                        Pengurangan <strong>-${selisihNapi} Narapidana</strong>${rincianBerkurang} klop 100% dengan <strong>${row.total_bebas} orang</strong> pada Laporan Bebas Harian.
                    </div>
                </div>
            `;
        }
    } else if (row.delta_napi === 0 && row.total_bebas > 0) {
        rekonHtml += `
            <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                <i class="fas fa-exclamation-circle text-warning fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Perlu Kroscek Laporan:</strong><br>
                    Tercatat <strong>${row.total_bebas} orang bebas</strong> di laporan bebas harian kemarin, tetapi jumlah narapidana hari ini tetap sama (potensi laporan penghuni belum dipotong atau ada alih status tahanan pengimbang).
                </div>
            </div>
        `;
    }

    rekonBox.innerHTML = rekonHtml;

    // Tab 3: Rincian Bebas
    const bCont = document.getElementById('mdf_bebas_container');
    bCont.innerHTML = `
        <div class="col-4">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block" style="font-size: 11px;">Bebas Murni</small>
                <strong class="fs-6 text-dark">${row.bebas_murni} Orang</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block" style="font-size: 11px;">Pembebasan Bersyarat (PB)</small>
                <strong class="fs-6 text-primary">${row.pb} Orang</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block" style="font-size: 11px;">Cuti Bersyarat (CB)</small>
                <strong class="fs-6 text-primary">${row.cb} Orang</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block" style="font-size: 11px;">Cuti Menjelang Bebas (CMB)</small>
                <strong class="fs-6 text-dark">${row.cmb} Orang</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block" style="font-size: 11px;">Asimilasi</small>
                <strong class="fs-6 text-dark">${row.asimilasi} Orang</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-danger bg-opacity-10 border-danger">
                <small class="text-danger d-block fw-semibold" style="font-size: 11px;">Bebas Demi Hukum (BDH)</small>
                <strong class="fs-6 text-danger">${row.bdh} Orang</strong>
            </div>
        </div>
        <div class="col-12 mt-2">
            <div class="p-2 bg-success bg-opacity-10 border border-success rounded text-success fw-bold">
                Total Seluruh Warga Binaan Bebas: ${row.total_bebas} Orang
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalDetailFluktuasi'));
    modal.show();
}
</script>
@endpush

