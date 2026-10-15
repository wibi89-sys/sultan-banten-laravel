@extends('layouts.app')

@section('title', 'Kroscek Data - Sultan Banten')
@section('page_title', 'Kroscek Data')

@push('styles')
<style>
    /* Styling Kartu & Banner */
    .kpi-card {
        border-radius: 14px;
        border: 1px solid rgba(0,0,0,0.06);
        background: #fff;
        transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0,0,0,0.06);
    }
    .kpi-card .icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    /* Status Pill Badges */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 50rem;
        line-height: 1.2;
    }
    .status-pill-success {
        background-color: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .status-pill-danger {
        background-color: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .status-pill-warning {
        background-color: #fffbeb;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .status-pill-secondary {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    /* Tabel dengan Garis Batas Jelas */
    .kroscek-table-grid {
        border: 1.5px solid #94a3b8 !important;
        border-collapse: collapse !important;
    }
    .kroscek-table-grid th, 
    .kroscek-table-grid td {
        border: 1px solid #cbd5e1 !important;
        vertical-align: middle;
    }
    .kroscek-table-grid thead th {
        border-bottom: 2px solid #64748b !important;
    }
    .th-group-kroscek {
        background-color: #e0e7ff !important;
        color: #1e1b4b !important;
        font-weight: 700;
    }
    .th-sub-kroscek {
        background-color: #eef2ff !important;
        color: #312e81 !important;
    }
    .th-group-modul {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 700;
    }
    .th-sub-modul {
        background-color: #f8fafc !important;
        color: #1e293b !important;
    }

    /* Filter Buttons */
    .btn-quick-filter {
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 50rem;
        padding: 6px 14px;
        border: 1px solid #e5e7eb;
        background-color: #fff;
        color: #4b5563;
        transition: all 0.15s ease;
    }
    .btn-quick-filter:hover {
        background-color: #f9fafb;
        color: #111827;
        border-color: #d1d5db;
    }
    .btn-quick-filter.active {
        background-color: #2563eb;
        color: #fff !important;
        border-color: #2563eb;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }
</style>
@endpush

@section('content')
<div class="content-card">

    {{-- Header Judul & Toolbar Tanggal --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h5 class="mb-1 fw-bold text-dark">
                <i class="fas fa-check-double text-primary me-2"></i>
                @if($isOperator)
                    Kroscek Data Tahanan & Verifikasi Mandiri
                @else
                    Kroscek Data Tahanan & Monitoring 4 Modul Wilayah
                @endif
            </h5>
            <p class="text-muted small mb-0">
                @if($isOperator)
                    Pantau sinkronisasi jumlah tahanan dan kepatuhan waktu input 4 modul laporan harian UPT Anda
                @else
                    Pusat pemantauan kecocokan data tahanan dan jam input 4 modul laporan harian seluruh UPT se-Banten
                @endif
            </p>
        </div>

        {{-- Toolbar Tanggal --}}
        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('kroscek.index') }}" class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-calendar-alt text-primary"></i>
                    </span>
                    <input type="date" name="tanggal" class="form-control form-control-sm border-start-0" 
                           value="{{ $filterTanggal }}" onchange="this.form.submit()" aria-label="Pilih Tanggal Kroscek">
                </div>
                <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm">
                    <i class="fas fa-filter me-1"></i> Muat
                </button>
            </form>
            @php
                $today = date('Y-m-d');
            @endphp
            @if($filterTanggal !== $today)
                <a href="{{ route('kroscek.index', ['tanggal' => $today]) }}" class="btn btn-sm btn-outline-secondary" title="Kembali ke Hari Ini">
                    Hari Ini
                </a>
            @endif
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAMPILAN KHUSUS OPERATOR UPT (Mode Checklist Mandiri)                    --}}
    {{-- ========================================================================= --}}
    @if($isOperator && $myUptData)
        @php
            $isSinkron = ($myUptData['status_kroscek'] === 'sinkron');
            $isSelisih = ($myUptData['status_kroscek'] === 'selisih');
            $isLengkap = $myUptData['is_all_completed'];
        @endphp

        {{-- Banner Status Utama UPT --}}
        <div class="alert border-0 shadow-sm p-4 mb-4 rounded-4 @if($isSinkron && $isLengkap) bg-success bg-opacity-10 border-start border-4 border-success @elseif($isSelisih) bg-danger bg-opacity-10 border-start border-4 border-danger @else bg-warning bg-opacity-10 border-start border-4 border-warning @endif">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 d-flex align-items-center justify-content-center @if($isSinkron && $isLengkap) bg-success text-white @elseif($isSelisih) bg-danger text-white @else bg-warning text-dark @endif" style="width: 56px; height: 56px;">
                        <i class="fas @if($isSinkron && $isLengkap) fa-check-circle fs-3 @elseif($isSelisih) fa-exclamation-triangle fs-3 @else fa-hourglass-half fs-3 @endif"></i>
                    </div>
                    <div>
                        <span class="badge bg-white text-dark border px-2 py-1 mb-1 small fw-semibold">
                            <i class="fas fa-building text-primary me-1"></i> {{ $myUptData['nama_upt'] }}
                        </span>
                        <h5 class="mb-1 fw-bold @if($isSinkron && $isLengkap) text-success @elseif($isSelisih) text-danger @else text-dark @endif">
                            @if($isSinkron && $isLengkap)
                                Laporan Lengkap & Data Tahanan Sinkron!
                            @elseif($isSelisih)
                                Perhatian: Terdapat Selisih Tahanan ({{ $myUptData['selisih'] }} Orang)
                            @else
                                Perhatian: Belum Semua Modul Terisi ({{ $myUptData['completed_count'] }}/4 Modul)
                            @endif
                        </h5>
                        <p class="mb-0 text-muted small">
                            Laporan per tanggal <strong>{{ \Carbon\Carbon::parse($filterTanggal)->translatedFormat('l, d F Y') }}</strong> (Batas Jam Selesai: <strong>{{ substr($jamSelesaiStr, 0, 5) }} WIB</strong>)
                        </p>
                    </div>
                </div>

                {{-- Status Pills Ringkas --}}
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="status-pill @if($isSinkron) status-pill-success @elseif($isSelisih) status-pill-danger @else status-pill-warning @endif">
                        <i class="fas @if($isSinkron) fa-check-circle @elseif($isSelisih) fa-times-circle @else fa-clock @endif"></i>
                        Kroscek: {{ $isSinkron ? 'Sinkron' : ($isSelisih ? 'Selisih '.$myUptData['selisih'].' Orang' : 'Belum Lengkap') }}
                    </span>
                    <span class="status-pill @if($isLengkap) status-pill-success @else status-pill-warning @endif">
                        <i class="fas fa-layer-group"></i> {{ $myUptData['completed_count'] }}/4 Modul Terisi
                    </span>
                </div>
            </div>
        </div>

        {{-- Kartu Kroscek Perbandingan Tahanan (Penghuni vs Overstaying) --}}
        <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-primary bg-opacity-10 py-3 border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold text-primary">
                        <i class="fas fa-balance-scale me-2"></i>Komparasi Sinkronisasi Tahanan (Penghuni vs Overstaying)
                    </span>
                    <span class="small text-muted">
                        *Tahanan non-WNA/sakit/lansia/pidana mati di Penghuni harus sama persis dengan Tahanan AI–AV Overstaying
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row align-items-center g-4">
                    {{-- Sisi Penghuni --}}
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border text-center h-100">
                            <span class="text-muted small fw-semibold text-uppercase">Tahanan di Data Penghuni</span>
                            <h2 class="fw-bold my-2 text-dark font-monospace">
                                {{ $myUptData['tahanan_penghuni'] !== null ? number_format($myUptData['tahanan_penghuni']) : '-' }}
                                <small class="fs-6 fw-normal text-muted font-sans-serif">Orang</small>
                            </h2>
                            <div class="small text-muted">
                                @if($myUptData['penghuni']['exists'])
                                    <i class="fas fa-clock text-primary me-1"></i> Jam Input: <strong>{{ $myUptData['penghuni']['jam_str'] }} WIB</strong>
                                    <span class="badge @if($myUptData['penghuni']['is_tepat']) bg-success @else bg-warning text-dark @endif ms-1">
                                        {{ $myUptData['penghuni']['is_tepat'] ? 'Tepat Waktu' : 'Terlambat' }}
                                    </span>
                                @else
                                    <span class="badge bg-danger">Belum Input Penghuni</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Indikator Hasil Tengah --}}
                    <div class="col-md-2 text-center">
                        <div class="my-2">
                            @if($isSinkron)
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow" style="width: 52px; height: 52px;">
                                    <i class="fas fa-equals fs-4"></i>
                                </div>
                                <div class="mt-2 fw-bold text-success small">SINKRON</div>
                            @elseif($isSelisih)
                                <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow" style="width: 52px; height: 52px;">
                                    <i class="fas fa-not-equal fs-4"></i>
                                </div>
                                <div class="mt-2 fw-bold text-danger small">SELISIH {{ $myUptData['selisih'] }}</div>
                            @else
                                <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow" style="width: 52px; height: 52px;">
                                    <i class="fas fa-question fs-4"></i>
                                </div>
                                <div class="mt-2 fw-bold text-muted small">BELUM LENGKAP</div>
                            @endif
                        </div>
                    </div>

                    {{-- Sisi Overstaying --}}
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border text-center h-100">
                            <span class="text-muted small fw-semibold text-uppercase">Tahanan di Data Overstaying (AI–AV)</span>
                            <h2 class="fw-bold my-2 text-dark font-monospace">
                                {{ $myUptData['tahanan_overstaying'] !== null ? number_format($myUptData['tahanan_overstaying']) : '-' }}
                                <small class="fs-6 fw-normal text-muted font-sans-serif">Orang</small>
                            </h2>
                            <div class="small text-muted">
                                @if($myUptData['overstaying']['exists'])
                                    <i class="fas fa-clock text-primary me-1"></i> Jam Input: <strong>{{ $myUptData['overstaying']['jam_str'] }} WIB</strong>
                                    <span class="badge @if($myUptData['overstaying']['is_tepat']) bg-success @else bg-warning text-dark @endif ms-1">
                                        {{ $myUptData['overstaying']['is_tepat'] ? 'Tepat Waktu' : 'Terlambat' }}
                                    </span>
                                @else
                                    <span class="badge bg-danger">Belum Input Overstaying</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alert Saran Aksi jika masih ada kendala --}}
                @if(!$isSinkron || !$isLengkap)
                    <div class="mt-3 p-3 bg-white border border-warning rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 text-dark small">
                            <i class="fas fa-info-circle text-warning fs-5"></i>
                            <span>
                                @if($isSelisih)
                                    Jumlah tahanan berbeda <strong>{{ $myUptData['selisih'] }} orang</strong>. Silakan periksa angka tahanan pada modul Penghuni atau Overstaying Anda.
                                @else
                                    Modul yang belum Anda input hari ini: 
                                    <strong>{{ implode(', ', $myUptData['missing_modules']) }}</strong>.
                                @endif
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            @if(!$myUptData['penghuni']['exists'] || $isSelisih)
                                <a href="{{ route('penghuni.index') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-users me-1"></i> Form Penghuni
                                </a>
                            @endif
                            @if(!$myUptData['overstaying']['exists'] || $isSelisih)
                                <a href="{{ route('overstaying.index') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-clock me-1"></i> Form Overstaying
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Tabel 4 Modul Laporan dengan Garis Jelas Khusus Operator --}}
        <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-table-list text-primary me-2"></i>Status & Jam Input 4 Modul Laporan Harian
                </h6>
                <span class="badge bg-light text-dark border px-2 py-1 small">
                    <i class="fas fa-clock text-primary me-1"></i> Batas Jam Input: <strong>{{ substr($jamSelesaiStr, 0, 5) }} WIB</strong>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle kroscek-table-grid mb-0">
                    <thead class="text-center align-middle bg-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 22%;" class="text-start">Nama Modul Laporan</th>
                            <th style="width: 15%;">Jam Input</th>
                            <th style="width: 15%;">Status Ketepatan</th>
                            <th style="width: 29%;" class="text-start">Ringkasan Data yang Diinput</th>
                            <th style="width: 14%;">Aksi / Form</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Baris 1: Modul Penghuni --}}
                        <tr>
                            <td class="text-center fw-bold text-muted">1</td>
                            <td class="text-start">
                                <div class="fw-bold text-dark">
                                    <i class="fas fa-users text-primary me-2"></i>Penghuni
                                </div>
                                <small class="text-muted">Populasi Tahanan & Narapidana harian</small>
                            </td>
                            <td class="text-center">
                                @if($myUptData['penghuni']['exists'])
                                    <span class="fw-bold text-dark font-monospace fs-6">{{ $myUptData['penghuni']['jam_str'] }} WIB</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Belum Input</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($myUptData['penghuni']['exists'])
                                    @if($myUptData['penghuni']['is_tepat'])
                                        <span class="status-pill status-pill-success py-1 px-2">
                                            <i class="fas fa-check-circle"></i> Tepat Waktu
                                        </span>
                                    @else
                                        <span class="status-pill status-pill-warning py-1 px-2">
                                            <i class="fas fa-clock"></i> Terlambat
                                        </span>
                                    @endif
                                @else
                                    <span class="status-pill status-pill-danger py-1 px-2">
                                        <i class="fas fa-times-circle"></i> Belum Mengisi
                                    </span>
                                @endif
                            </td>
                            <td class="text-start small">
                                @if($myUptData['penghuni']['exists'])
                                    <div>Tahanan: <strong>{{ number_format($myUptData['penghuni']['total_tahanan']) }}</strong> Orang</div>
                                    <div>Narapidana: <strong>{{ number_format($myUptData['penghuni']['total_napi']) }}</strong> Orang</div>
                                    <div class="fw-bold text-primary border-top pt-1 mt-1">Total Isi WBP: {{ number_format($myUptData['penghuni']['total_penghuni']) }} Orang</div>
                                @else
                                    <span class="text-muted fst-italic">Data belum diinput untuk tanggal ini</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('penghuni.index') }}" class="btn btn-sm btn-outline-primary px-3">
                                    <i class="fas fa-edit me-1"></i> {{ $myUptData['penghuni']['exists'] ? 'Kelola Data' : 'Input Sekarang' }}
                                </a>
                            </td>
                        </tr>

                        {{-- Baris 2: Modul Overstaying --}}
                        <tr>
                            <td class="text-center fw-bold text-muted">2</td>
                            <td class="text-start">
                                <div class="fw-bold text-dark">
                                    <i class="fas fa-clock text-primary me-2"></i>Overstaying
                                </div>
                                <small class="text-muted">Tahanan AI–AV & masa penahanan lewat</small>
                            </td>
                            <td class="text-center">
                                @if($myUptData['overstaying']['exists'])
                                    <span class="fw-bold text-dark font-monospace fs-6">{{ $myUptData['overstaying']['jam_str'] }} WIB</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Belum Input</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($myUptData['overstaying']['exists'])
                                    @if($myUptData['overstaying']['is_tepat'])
                                        <span class="status-pill status-pill-success py-1 px-2">
                                            <i class="fas fa-check-circle"></i> Tepat Waktu
                                        </span>
                                    @else
                                        <span class="status-pill status-pill-warning py-1 px-2">
                                            <i class="fas fa-clock"></i> Terlambat
                                        </span>
                                    @endif
                                @else
                                    <span class="status-pill status-pill-danger py-1 px-2">
                                        <i class="fas fa-times-circle"></i> Belum Mengisi
                                    </span>
                                @endif
                            </td>
                            <td class="text-start small">
                                @if($myUptData['overstaying']['exists'])
                                    <div>Total Tahanan (AI–AV): <strong>{{ number_format($myUptData['overstaying']['total_tahanan']) }}</strong> Orang</div>
                                    <div>Overstaying Lewat: <strong class="{{ $myUptData['overstaying']['total_overstaying'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($myUptData['overstaying']['total_overstaying']) }} Orang</strong></div>
                                    <div class="text-muted border-top pt-1 mt-1">Rincian AI–AV: {{ $myUptData['overstaying']['tahanan_ai'] }}/{{ $myUptData['overstaying']['tahanan_aii'] }}/{{ $myUptData['overstaying']['tahanan_aiii'] }}/{{ $myUptData['overstaying']['tahanan_aiv'] }}/{{ $myUptData['overstaying']['tahanan_av'] }}</div>
                                @else
                                    <span class="text-muted fst-italic">Data belum diinput untuk tanggal ini</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('overstaying.index') }}" class="btn btn-sm btn-outline-primary px-3">
                                    <i class="fas fa-edit me-1"></i> {{ $myUptData['overstaying']['exists'] ? 'Kelola Data' : 'Input Sekarang' }}
                                </a>
                            </td>
                        </tr>

                        {{-- Baris 3: Modul Integrasi --}}
                        <tr>
                            <td class="text-center fw-bold text-muted">3</td>
                            <td class="text-start">
                                <div class="fw-bold text-dark">
                                    <i class="fas fa-exchange-alt text-primary me-2"></i>Integrasi
                                </div>
                                <small class="text-muted">Usulan dan SK terbit PB, CB, CMB, dsb</small>
                            </td>
                            <td class="text-center">
                                @if($myUptData['integrasi']['exists'])
                                    <span class="fw-bold text-dark font-monospace fs-6">{{ $myUptData['integrasi']['jam_str'] }} WIB</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Belum Input</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($myUptData['integrasi']['exists'])
                                    @if($myUptData['integrasi']['is_tepat'])
                                        <span class="status-pill status-pill-success py-1 px-2">
                                            <i class="fas fa-check-circle"></i> Tepat Waktu
                                        </span>
                                    @else
                                        <span class="status-pill status-pill-warning py-1 px-2">
                                            <i class="fas fa-clock"></i> Terlambat
                                        </span>
                                    @endif
                                @else
                                    <span class="status-pill status-pill-danger py-1 px-2">
                                        <i class="fas fa-times-circle"></i> Belum Mengisi
                                    </span>
                                @endif
                            </td>
                            <td class="text-start small">
                                @if($myUptData['integrasi']['exists'])
                                    <div>Total Usulan: <strong class="text-primary">{{ number_format($myUptData['integrasi']['total_usulan']) }} Orang</strong></div>
                                    <div>SK Terbit: <strong class="text-success">{{ number_format($myUptData['integrasi']['total_sk']) }} Orang</strong></div>
                                    <div class="text-muted border-top pt-1 mt-1">PB/CB/CMB: {{ $myUptData['integrasi']['usulan_pb'] }}/{{ $myUptData['integrasi']['usulan_cb'] }}/{{ $myUptData['integrasi']['usulan_cmb'] }}</div>
                                @else
                                    <span class="text-muted fst-italic">Data belum diinput untuk tanggal ini</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('integrasi.index') }}" class="btn btn-sm btn-outline-primary px-3">
                                    <i class="fas fa-edit me-1"></i> {{ $myUptData['integrasi']['exists'] ? 'Kelola Data' : 'Input Sekarang' }}
                                </a>
                            </td>
                        </tr>

                        {{-- Baris 4: Modul Bebas Harian --}}
                        <tr>
                            <td class="text-center fw-bold text-muted">4</td>
                            <td class="text-start">
                                <div class="fw-bold text-dark">
                                    <i class="fas fa-door-open text-primary me-2"></i>Bebas Harian
                                </div>
                                <small class="text-muted">WBP yang bebas murni, PB, CB, CMB, asimilasi</small>
                            </td>
                            <td class="text-center">
                                @if($myUptData['bebas']['exists'])
                                    <span class="fw-bold text-dark font-monospace fs-6">{{ $myUptData['bebas']['jam_str'] }} WIB</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Belum Input</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($myUptData['bebas']['exists'])
                                    @if($myUptData['bebas']['is_tepat'])
                                        <span class="status-pill status-pill-success py-1 px-2">
                                            <i class="fas fa-check-circle"></i> Tepat Waktu
                                        </span>
                                    @else
                                        <span class="status-pill status-pill-warning py-1 px-2">
                                            <i class="fas fa-clock"></i> Terlambat
                                        </span>
                                    @endif
                                @else
                                    <span class="status-pill status-pill-danger py-1 px-2">
                                        <i class="fas fa-times-circle"></i> Belum Mengisi
                                    </span>
                                @endif
                            </td>
                            <td class="text-start small">
                                @if($myUptData['bebas']['exists'])
                                    <div>Total Bebas Hari Ini: <strong class="{{ $myUptData['bebas']['total_bebas'] > 0 ? 'text-success' : 'text-muted' }}">{{ $myUptData['bebas']['total_bebas'] > 0 ? number_format($myUptData['bebas']['total_bebas']).' Orang' : 'Nihil (0 Bebas)' }}</strong></div>
                                    <div>Bebas Murni: <strong>{{ $myUptData['bebas']['bebas_murni'] }}</strong> | PB/CB: <strong>{{ $myUptData['bebas']['pb'] }}/{{ $myUptData['bebas']['cb'] }}</strong></div>
                                @else
                                    <span class="text-muted fst-italic">Data belum diinput untuk tanggal ini</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('bebas-harian.index') }}" class="btn btn-sm btn-outline-primary px-3">
                                    <i class="fas fa-edit me-1"></i> {{ $myUptData['bebas']['exists'] ? 'Kelola Data' : 'Input Sekarang' }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- TAMPILAN KHUSUS ADMIN KANWIL (Mode Monitoring Wilayah 12 UPT)             --}}
    {{-- ========================================================================= --}}
    @else

        {{-- 4 Kartu KPI Wilayah Se-Banten --}}
        <div class="row g-3 mb-4">
            {{-- KPI 1: Kroscek Tahanan Sinkron --}}
            <div class="col-6 col-lg-3">
                <div class="p-3 kpi-card h-100 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Kroscek Tahanan</span>
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-success font-monospace">
                        {{ $totalSinkron }} <span class="fs-6 text-muted fw-normal font-sans-serif">/ {{ $totalUpt }} UPT</span>
                    </h3>
                    <small class="text-muted">
                        @if($totalSelisih > 0)
                            <span class="text-danger fw-bold"><i class="fas fa-exclamation-circle"></i> {{ $totalSelisih }} UPT Selisih</span>
                        @else
                            <span class="text-success fw-bold"><i class="fas fa-check-circle"></i> 100% Sinkron</span>
                        @endif
                    </small>
                </div>
            </div>

            {{-- KPI 2: Kelengkapan 4 Modul --}}
            <div class="col-6 col-lg-3">
                <div class="p-3 kpi-card h-100 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Kelengkapan Modul</span>
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-primary font-monospace">
                        {{ $totalLengkap4 }} <span class="fs-6 text-muted fw-normal font-sans-serif">/ {{ $totalUpt }} UPT</span>
                    </h3>
                    <small class="text-muted">
                        @if($totalBelumLengkap > 0)
                            <span class="text-warning fw-bold">{{ $totalBelumLengkap }} UPT Belum 4/4</span>
                        @else
                            <span class="text-success fw-bold">Semua Lengkap 4/4</span>
                        @endif
                    </small>
                </div>
            </div>

            {{-- KPI 3: Total Populasi WBP Se-Banten --}}
            <div class="col-6 col-lg-3">
                <div class="p-3 kpi-card h-100 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Total Isi WBP Banten</span>
                        <div class="icon-box bg-info bg-opacity-10 text-info">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-dark font-monospace">
                        {{ number_format($totalPopulasiSeBanten) }} <small class="fs-6 text-muted fw-normal font-sans-serif">Jiwa</small>
                    </h3>
                    <small class="text-muted">
                        {{ number_format($totalTahananSeBanten) }} Tahanan | {{ number_format($totalNapiSeBanten) }} Napi
                    </small>
                </div>
            </div>

            {{-- KPI 4: Bebas Hari Ini & Overstaying --}}
            <div class="col-6 col-lg-3">
                <div class="p-3 kpi-card h-100 border-start border-4 border-secondary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Bebas Harian Input</span>
                        <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-dark font-monospace">
                        {{ number_format($totalBebasSeBanten) }} <small class="fs-6 text-muted fw-normal font-sans-serif">Bebas</small>
                    </h3>
                    <small class="text-muted">
                        {{ number_format($totalUsulanSeBanten) }} Usulan | {{ number_format($totalOverstayingSeBanten) }} Overstay
                    </small>
                </div>
            </div>
        </div>

        {{-- Toolbar Pencarian & Quick Filter --}}
        <div class="card mb-4 bg-light border-0 shadow-sm rounded-3">
            <div class="card-body py-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchUpt" class="form-control form-control-sm" placeholder="Cari nama UPT..." aria-label="Cari nama UPT">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                            <button type="button" class="btn-quick-filter active" data-filter="all">
                                Semua ({{ $totalUpt }})
                            </button>
                            <button type="button" class="btn-quick-filter text-danger" data-filter="masalah">
                                <i class="fas fa-exclamation-triangle me-1"></i>Butuh Perhatian ({{ $totalSelisih + $totalBelumLengkap }})
                            </button>
                            <button type="button" class="btn-quick-filter text-success" data-filter="sinkron">
                                <i class="fas fa-check-double me-1"></i>Tahanan Sinkron ({{ $totalSinkron }})
                            </button>
                            <button type="button" class="btn-quick-filter text-primary" data-filter="lengkap">
                                <i class="fas fa-check-circle me-1"></i>4/4 Lengkap ({{ $totalLengkap4 }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Terpadu 12 UPT Se-Banten dengan Garis Jelas dan Kolom Modul Terpisah --}}
        <div class="table-responsive rounded-3 shadow-sm mb-4">
            <table class="table table-bordered table-hover align-middle mb-0 kroscek-table-grid">
                <thead>
                    <tr class="text-center align-middle">
                        <th rowspan="2" class="text-center py-2" style="width: 3%;">No</th>
                        <th rowspan="2" class="text-start py-2" style="width: 17%;">Nama UPT</th>
                        <th colspan="3" class="text-center py-2 th-group-kroscek">
                            <i class="fas fa-balance-scale me-1"></i> Kroscek Tahanan
                        </th>
                        <th colspan="4" class="text-center py-2 th-group-modul">
                            <i class="fas fa-clock me-1"></i> Status & Jam Input 4 Modul Laporan
                        </th>
                        <th rowspan="2" class="text-center py-2" style="width: 15%;">Populasi & Dinamika</th>
                        <th rowspan="2" class="text-center py-2" style="width: 7%;">Aksi</th>
                    </tr>
                    <tr class="text-center align-middle">
                        {{-- Sub-header Kroscek Tahanan --}}
                        <th class="py-2 small fw-bold th-sub-kroscek" style="width: 6.5%;">Penghuni</th>
                        <th class="py-2 small fw-bold th-sub-kroscek" style="width: 6.5%;">Overstaying</th>
                        <th class="py-2 small fw-bold th-sub-kroscek" style="width: 8%;">Status</th>
                        
                        {{-- Sub-header 4 Modul Laporan --}}
                        <th class="py-2 small fw-bold th-sub-modul" style="width: 8.5%;">Penghuni</th>
                        <th class="py-2 small fw-bold th-sub-modul" style="width: 8.5%;">Overstaying</th>
                        <th class="py-2 small fw-bold th-sub-modul" style="width: 8.5%;">Integrasi</th>
                        <th class="py-2 small fw-bold th-sub-modul" style="width: 8.5%;">Bebas Harian</th>
                    </tr>
                </thead>
                <tbody id="kroscekTableBody">
                    @forelse($kroscekList as $row)
                        @php
                            $rowIsSinkron = ($row['status_kroscek'] === 'sinkron');
                            $rowIsSelisih = ($row['status_kroscek'] === 'selisih');
                            $rowIsLengkap = $row['is_all_completed'];
                            $rowHasIssues = (!$rowIsSinkron || !$rowIsLengkap);
                        @endphp
                        <tr data-upt="{{ strtolower($row['nama_upt']) }}"
                            data-sinkron="{{ $rowIsSinkron ? '1' : '0' }}"
                            data-selisih="{{ $rowIsSelisih ? '1' : '0' }}"
                            data-lengkap="{{ $rowIsLengkap ? '1' : '0' }}"
                            data-masalah="{{ $rowHasIssues ? '1' : '0' }}">
                            
                            {{-- No --}}
                            <td class="text-center text-muted small fw-semibold">{{ $loop->iteration }}</td>

                            {{-- Nama UPT --}}
                            <td class="text-start">
                                <div class="fw-bold text-dark">
                                    <i class="fas fa-building text-primary me-1 opacity-50"></i>
                                    {{ $row['nama_upt'] }}
                                </div>
                                <div class="mt-1">
                                    @if($rowIsLengkap)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 10.5px;">
                                            <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size: 10.5px;">
                                            <i class="fas fa-clock me-1"></i> {{ $row['completed_count'] }}/4 Terisi
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Kroscek Tahanan: Penghuni --}}
                            <td class="text-center font-monospace fw-bold {{ $row['tahanan_penghuni'] !== null ? 'text-dark' : 'text-muted' }}">
                                {{ $row['tahanan_penghuni'] !== null ? number_format($row['tahanan_penghuni']) : '-' }}
                            </td>

                            {{-- Kroscek Tahanan: Overstaying --}}
                            <td class="text-center font-monospace fw-bold {{ $row['tahanan_overstaying'] !== null ? 'text-dark' : 'text-muted' }}">
                                {{ $row['tahanan_overstaying'] !== null ? number_format($row['tahanan_overstaying']) : '-' }}
                            </td>

                            {{-- Kroscek Tahanan: Status --}}
                            <td class="text-center">
                                @if($rowIsSinkron)
                                    <span class="status-pill status-pill-success py-1 px-2" style="font-size: 11px;">
                                        <i class="fas fa-check-circle"></i> Sinkron
                                    </span>
                                @elseif($rowIsSelisih)
                                    <span class="status-pill status-pill-danger py-1 px-2" style="font-size: 11px;">
                                        <i class="fas fa-times-circle"></i> Selisih {{ $row['selisih'] }}
                                    </span>
                                @else
                                    <span class="status-pill status-pill-secondary py-1 px-2" style="font-size: 11px;">
                                        <i class="fas fa-minus-circle"></i> Belum
                                    </span>
                                @endif
                            </td>

                            {{-- Modul 1: Penghuni --}}
                            <td class="text-center">
                                @if($row['penghuni']['exists'])
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">{{ $row['penghuni']['jam_str'] }} WIB</div>
                                    @if($row['penghuni']['is_tepat'])
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Tepat</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Terlambat</span>
                                    @endif
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10px; padding: 2px 6px;">
                                        <i class="fas fa-times me-1"></i>Belum
                                    </span>
                                @endif
                            </td>

                            {{-- Modul 2: Overstaying --}}
                            <td class="text-center">
                                @if($row['overstaying']['exists'])
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">{{ $row['overstaying']['jam_str'] }} WIB</div>
                                    @if($row['overstaying']['is_tepat'])
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Tepat</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Terlambat</span>
                                    @endif
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10px; padding: 2px 6px;">
                                        <i class="fas fa-times me-1"></i>Belum
                                    </span>
                                @endif
                            </td>

                            {{-- Modul 3: Integrasi --}}
                            <td class="text-center">
                                @if($row['integrasi']['exists'])
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">{{ $row['integrasi']['jam_str'] }} WIB</div>
                                    @if($row['integrasi']['is_tepat'])
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Tepat</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Terlambat</span>
                                    @endif
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10px; padding: 2px 6px;">
                                        <i class="fas fa-times me-1"></i>Belum
                                    </span>
                                @endif
                            </td>

                            {{-- Modul 4: Bebas Harian --}}
                            <td class="text-center">
                                @if($row['bebas']['exists'])
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">{{ $row['bebas']['jam_str'] }} WIB</div>
                                    @if($row['bebas']['is_tepat'])
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Tepat</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 9.5px; padding: 1px 6px;">Terlambat</span>
                                    @endif
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10px; padding: 2px 6px;">
                                        <i class="fas fa-times me-1"></i>Belum
                                    </span>
                                @endif
                            </td>

                            {{-- Populasi & Dinamika --}}
                            <td class="text-center small">
                                <div class="text-dark">
                                    Total: <strong>{{ number_format($row['penghuni']['total_penghuni']) }}</strong> WBP
                                </div>
                                <div class="text-muted" style="font-size: 11px;">
                                    Bebas: <strong>{{ $row['bebas']['total_bebas'] }}</strong> | Overstay: <strong>{{ $row['overstaying']['total_overstaying'] }}</strong>
                                </div>
                            </td>

                            {{-- Aksi: Lihat Detail Modal --}}
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" onclick="showDetailModal({{ json_encode($row) }})" title="Lihat Rincian Lengkap UPT">
                                    <i class="fas fa-eye me-1"></i> Rincian
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">Belum ada data UPT terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

</div>

{{-- ========================================================================= --}}
{{-- MODAL POPUP RINCIAN DETAIL UPT                                            --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="modalDetailKroscek" tabindex="-1" aria-labelledby="modalDetailKroscekLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow border-0 rounded-4">
            <div class="modal-header bg-primary bg-opacity-10 py-3 border-0">
                <div>
                    <h5 class="modal-title fw-bold text-primary fs-6" id="detailUptName">
                        <i class="fas fa-building me-2"></i>Detail Rincian UPT
                    </h5>
                    <span class="small text-muted" id="detailTanggalLaporan"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Status Kroscek Box --}}
                <div id="detailKroscekAlert" class="alert mb-4 border-0 p-3 rounded-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i id="detailKroscekIcon" class="fas fa-check-circle fs-4"></i>
                        <div>
                            <strong id="detailKroscekTitle"></strong>
                            <div class="small" id="detailKroscekDesc"></div>
                        </div>
                    </div>
                    <span id="detailKroscekBadge" class="status-pill"></span>
                </div>

                {{-- Tab Navigasi 4 Modul --}}
                <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded-3 small" id="detailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2 fw-semibold" id="tab-p-btn" data-bs-toggle="pill" data-bs-target="#tab-penghuni" type="button" role="tab">
                            <i class="fas fa-users me-1"></i> 1. Penghuni
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 fw-semibold" id="tab-o-btn" data-bs-toggle="pill" data-bs-target="#tab-overstaying" type="button" role="tab">
                            <i class="fas fa-clock me-1"></i> 2. Overstaying
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 fw-semibold" id="tab-i-btn" data-bs-toggle="pill" data-bs-target="#tab-integrasi" type="button" role="tab">
                            <i class="fas fa-exchange-alt me-1"></i> 3. Integrasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 fw-semibold" id="tab-b-btn" data-bs-toggle="pill" data-bs-target="#tab-bebas" type="button" role="tab">
                            <i class="fas fa-door-open me-1"></i> 4. Bebas Harian
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="detailTabContent">
                    {{-- Tab 1: Penghuni --}}
                    <div class="tab-pane fade show active" id="tab-penghuni" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-sm-4 text-center p-3 bg-light rounded-3">
                                <span class="text-muted small">Tahanan</span>
                                <h4 class="fw-bold my-1 text-primary font-monospace" id="det_p_tahanan">0</h4>
                            </div>
                            <div class="col-sm-4 text-center p-3 bg-light rounded-3">
                                <span class="text-muted small">Narapidana</span>
                                <h4 class="fw-bold my-1 text-dark font-monospace" id="det_p_napi">0</h4>
                            </div>
                            <div class="col-sm-4 text-center p-3 bg-light rounded-3">
                                <span class="text-muted small">Total Isi WBP</span>
                                <h4 class="fw-bold my-1 text-success font-monospace" id="det_p_total">0</h4>
                            </div>
                        </div>
                        <div class="mt-3 small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_p_waktu">-</strong>
                        </div>
                    </div>

                    {{-- Tab 2: Overstaying --}}
                    <div class="tab-pane fade" id="tab-overstaying" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center small align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kategori</th>
                                        <th>AI</th>
                                        <th>AII</th>
                                        <th>AIII</th>
                                        <th>AIV</th>
                                        <th>AV</th>
                                        <th class="table-primary fw-bold">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-semibold">Jumlah Tahanan</td>
                                        <td id="det_o_t_ai">0</td>
                                        <td id="det_o_t_aii">0</td>
                                        <td id="det_o_t_aiii">0</td>
                                        <td id="det_o_t_aiv">0</td>
                                        <td id="det_o_t_av">0</td>
                                        <td class="fw-bold text-primary font-monospace" id="det_o_t_total">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-semibold text-danger">Overstaying Lewat</td>
                                        <td id="det_o_o_ai">0</td>
                                        <td id="det_o_o_aii">0</td>
                                        <td id="det_o_o_aiii">0</td>
                                        <td id="det_o_o_aiv">0</td>
                                        <td id="det_o_o_av">0</td>
                                        <td class="fw-bold text-danger font-monospace" id="det_o_o_total">0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_o_waktu">-</strong>
                        </div>
                    </div>

                    {{-- Tab 3: Integrasi --}}
                    <div class="tab-pane fade" id="tab-integrasi" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center small align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Keterangan</th>
                                        <th>PB</th>
                                        <th>CB</th>
                                        <th>CMB</th>
                                        <th>CMK</th>
                                        <th>Asimilasi</th>
                                        <th class="table-primary fw-bold">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-semibold">Usulan Integrasi</td>
                                        <td id="det_i_u_pb">0</td>
                                        <td id="det_i_u_cb">0</td>
                                        <td id="det_i_u_cmb">0</td>
                                        <td id="det_i_u_cmk">0</td>
                                        <td id="det_i_u_asimilasi">0</td>
                                        <td class="fw-bold text-primary font-monospace" id="det_i_u_total">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-semibold text-success">SK Terbit</td>
                                        <td id="det_i_s_pb">0</td>
                                        <td id="det_i_s_cb">0</td>
                                        <td id="det_i_s_cmb">0</td>
                                        <td id="det_i_s_cmk">0</td>
                                        <td id="det_i_s_asimilasi">0</td>
                                        <td class="fw-bold text-success font-monospace" id="det_i_s_total">0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_i_waktu">-</strong>
                        </div>
                    </div>

                    {{-- Tab 4: Bebas Harian --}}
                    <div class="tab-pane fade" id="tab-bebas" role="tabpanel">
                        <div class="row g-2 text-center small">
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2">
                                <span class="text-muted d-block">Bebas Murni</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_murni">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2">
                                <span class="text-muted d-block">PB</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_pb">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2">
                                <span class="text-muted d-block">CB</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_cb">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2">
                                <span class="text-muted d-block">CMB</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_cmb">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2">
                                <span class="text-muted d-block">Asimilasi</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_asimilasi">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2">
                                <span class="text-muted d-block">BDH / Lain</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_lain">0</strong>
                            </div>
                        </div>
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 mt-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="fas fa-door-open me-2"></i>Total Bebas Hari Ini:</span>
                            <h4 class="mb-0 fw-bold font-monospace" id="det_b_total">0 Orang</h4>
                        </div>
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_b_waktu">-</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Fitur Pencarian Realtime & Quick Filter untuk Admin
    const searchInput = document.getElementById('searchUpt');
    const filterBtns = document.querySelectorAll('.btn-quick-filter');
    const tableRows = document.querySelectorAll('#kroscekTableBody tr');

    function applyAdminFilters() {
        const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const activeBtn = document.querySelector('.btn-quick-filter.active');
        const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';

        tableRows.forEach(row => {
            const uptName = row.getAttribute('data-upt') || '';
            const isSinkron = row.getAttribute('data-sinkron') === '1';
            const isSelisih = row.getAttribute('data-selisih') === '1';
            const isLengkap = row.getAttribute('data-lengkap') === '1';
            const hasIssues = row.getAttribute('data-masalah') === '1';

            const matchesSearch = uptName.includes(searchVal);
            let matchesCategory = true;

            if (activeFilter === 'masalah') {
                matchesCategory = hasIssues;
            } else if (activeFilter === 'sinkron') {
                matchesCategory = isSinkron;
            } else if (activeFilter === 'lengkap') {
                matchesCategory = isLengkap;
            }

            row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyAdminFilters);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyAdminFilters();
        });
    });

    // Modal Popup Detail UPT
    function showDetailModal(data) {
        document.getElementById('detailUptName').innerHTML = `<i class="fas fa-building me-2"></i>` + data.nama_upt;
        document.getElementById('detailTanggalLaporan').innerText = 'Tanggal Evaluasi: ' + '{{ \Carbon\Carbon::parse($filterTanggal)->translatedFormat('d F Y') }}';

        // Alert Status Kroscek
        const alertBox = document.getElementById('detailKroscekAlert');
        const alertIcon = document.getElementById('detailKroscekIcon');
        const alertTitle = document.getElementById('detailKroscekTitle');
        const alertDesc = document.getElementById('detailKroscekDesc');
        const alertBadge = document.getElementById('detailKroscekBadge');

        alertBox.className = 'alert mb-4 border-0 p-3 rounded-3 d-flex align-items-center justify-content-between';
        
        if (data.status_kroscek === 'sinkron') {
            alertBox.classList.add('bg-success', 'bg-opacity-10', 'text-success');
            alertIcon.className = 'fas fa-check-circle fs-4 text-success';
            alertTitle.innerText = 'Kroscek Tahanan: SINKRON (Cocok)';
            alertDesc.innerText = `Data Tahanan Penghuni (${data.tahanan_penghuni ?? 0}) = Tahanan Overstaying (${data.tahanan_overstaying ?? 0})`;
            alertBadge.className = 'status-pill status-pill-success';
            alertBadge.innerHTML = '<i class="fas fa-check-circle"></i> Sinkron';
        } else if (data.status_kroscek === 'selisih') {
            alertBox.classList.add('bg-danger', 'bg-opacity-10', 'text-danger');
            alertIcon.className = 'fas fa-exclamation-triangle fs-4 text-danger';
            alertTitle.innerText = `Kroscek Tahanan: SELISIH ${data.selisih} ORANG`;
            alertDesc.innerText = `Data Tahanan Penghuni (${data.tahanan_penghuni ?? 0}) ≠ Tahanan Overstaying (${data.tahanan_overstaying ?? 0})`;
            alertBadge.className = 'status-pill status-pill-danger';
            alertBadge.innerHTML = `<i class="fas fa-times-circle"></i> Selisih ${data.selisih}`;
        } else {
            alertBox.classList.add('bg-secondary', 'bg-opacity-10', 'text-secondary');
            alertIcon.className = 'fas fa-clock fs-4 text-secondary';
            alertTitle.innerText = 'Kroscek Tahanan: Belum Lengkap';
            alertDesc.innerText = 'Salah satu atau kedua modul (Penghuni / Overstaying) belum diinput.';
            alertBadge.className = 'status-pill status-pill-secondary';
            alertBadge.innerHTML = '<i class="fas fa-minus-circle"></i> Belum Lengkap';
        }

        // Tab 1: Penghuni
        document.getElementById('det_p_tahanan').innerText = data.penghuni.total_tahanan !== null ? data.penghuni.total_tahanan.toLocaleString('id-ID') : '-';
        document.getElementById('det_p_napi').innerText = data.penghuni.total_napi !== null ? data.penghuni.total_napi.toLocaleString('id-ID') : '-';
        document.getElementById('det_p_total').innerText = data.penghuni.total_penghuni !== null ? data.penghuni.total_penghuni.toLocaleString('id-ID') : '-';
        document.getElementById('det_p_waktu').innerText = data.penghuni.exists ? `${data.penghuni.jam_str} WIB (${data.penghuni.is_tepat ? 'Tepat Waktu' : 'Terlambat'})` : 'Belum Diinput';

        // Tab 2: Overstaying
        document.getElementById('det_o_t_ai').innerText = data.overstaying.tahanan_ai;
        document.getElementById('det_o_t_aii').innerText = data.overstaying.tahanan_aii;
        document.getElementById('det_o_t_aiii').innerText = data.overstaying.tahanan_aiii;
        document.getElementById('det_o_t_aiv').innerText = data.overstaying.tahanan_aiv;
        document.getElementById('det_o_t_av').innerText = data.overstaying.tahanan_av;
        document.getElementById('det_o_t_total').innerText = data.overstaying.total_tahanan;

        document.getElementById('det_o_o_ai').innerText = data.overstaying.overstaying_ai;
        document.getElementById('det_o_o_aii').innerText = data.overstaying.overstaying_aii;
        document.getElementById('det_o_o_aiii').innerText = data.overstaying.overstaying_aiii;
        document.getElementById('det_o_o_aiv').innerText = data.overstaying.overstaying_aiv;
        document.getElementById('det_o_o_av').innerText = data.overstaying.overstaying_av;
        document.getElementById('det_o_o_total').innerText = data.overstaying.total_overstaying;
        document.getElementById('det_o_waktu').innerText = data.overstaying.exists ? `${data.overstaying.jam_str} WIB (${data.overstaying.is_tepat ? 'Tepat Waktu' : 'Terlambat'})` : 'Belum Diinput';

        // Tab 3: Integrasi
        document.getElementById('det_i_u_pb').innerText = data.integrasi.usulan_pb;
        document.getElementById('det_i_u_cb').innerText = data.integrasi.usulan_cb;
        document.getElementById('det_i_u_cmb').innerText = data.integrasi.usulan_cmb;
        document.getElementById('det_i_u_cmk').innerText = data.integrasi.usulan_cmk;
        document.getElementById('det_i_u_asimilasi').innerText = data.integrasi.usulan_asimilasi;
        document.getElementById('det_i_u_total').innerText = data.integrasi.total_usulan;

        document.getElementById('det_i_s_pb').innerText = data.integrasi.sk_pb;
        document.getElementById('det_i_s_cb').innerText = data.integrasi.sk_cb;
        document.getElementById('det_i_s_cmb').innerText = data.integrasi.sk_cmb;
        document.getElementById('det_i_s_cmk').innerText = data.integrasi.sk_cmk;
        document.getElementById('det_i_s_asimilasi').innerText = data.integrasi.sk_asimilasi;
        document.getElementById('det_i_s_total').innerText = data.integrasi.total_sk;
        document.getElementById('det_i_waktu').innerText = data.integrasi.exists ? `${data.integrasi.jam_str} WIB (${data.integrasi.is_tepat ? 'Tepat Waktu' : 'Terlambat'})` : 'Belum Diinput';

        // Tab 4: Bebas Harian
        document.getElementById('det_b_murni').innerText = data.bebas.bebas_murni;
        document.getElementById('det_b_pb').innerText = data.bebas.pb;
        document.getElementById('det_b_cb').innerText = data.bebas.cb;
        document.getElementById('det_b_cmb').innerText = data.bebas.cmb;
        document.getElementById('det_b_asimilasi').innerText = data.bebas.asimilasi;
        const detBdh = document.getElementById('det_b_bdh');
        if (detBdh) detBdh.innerText = data.bebas.bdh;
        const detLain = document.getElementById('det_b_lain');
        if (detLain) detLain.innerText = (data.bebas.bdh ? data.bebas.bdh + ' / ' : '') + data.bebas.lainnya;
        document.getElementById('det_b_total').innerText = data.bebas.total_bebas + ' Orang';
        document.getElementById('det_b_waktu').innerText = data.bebas.exists ? `${data.bebas.jam_str} WIB (${data.bebas.is_tepat ? 'Tepat Waktu' : 'Terlambat'})` : 'Belum Diinput';

        // Trigger Tab 1 Active
        const firstTab = new bootstrap.Tab(document.getElementById('tab-p-btn'));
        firstTab.show();

        const modalEl = new bootstrap.Modal(document.getElementById('modalDetailKroscek'));
        modalEl.show();
    }
</script>
@endpush
