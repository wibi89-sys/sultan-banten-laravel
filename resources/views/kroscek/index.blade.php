@extends('layouts.app')

@section('title', 'Kroscek Data - Sultan Banten')
@section('page_title', 'Kroscek Data')

@push('styles')
<style>
    /* Styling Kartu KPI dengan Garis Batas Jelas */
    .kpi-card {
        border-radius: 12px;
        border: 1.5px solid #cbd5e1 !important;
        background: #ffffff;
        transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        border-color: #94a3b8 !important;
    }
    .kpi-card .icon-box {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    /* Card Kroscek Data Khusus */
    .kroscek-card-box {
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    /* Padding content-card optimal agar tabel pas di layar tanpa terpotong */
    .content-card {
        padding: 18px 20px !important;
        margin: 15px !important;
    }

    @media (max-width: 767.98px) {
        .content-card {
            padding: 12px 10px !important;
            margin: 8px 4px !important;
        }
        .kpi-card {
            padding: 12px !important;
        }
        .btn-quick-filter,
        .btn-quick-filter-detail {
            font-size: 11px !important;
            padding: 4px 10px !important;
        }
        #kroscekNavTabs .nav-link {
            padding: 8px 12px !important;
            font-size: 12.5px !important;
        }
    }

    /* Status Pill Badges Nyaman & Jelas */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        font-size: 11px !important;
        padding: 3px 8px !important;
        border-radius: 50rem;
        line-height: 1.25;
        white-space: nowrap;
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

    /* Tabel dengan Ukuran Nyaman, Tipografi Seimbang, & Kolom Nomor Ramping */
    .kroscek-table-grid {
        border: 1.5px solid #cbd5e1 !important;
        border-collapse: collapse !important;
        font-size: 12px !important;
        width: 100% !important;
        min-width: 1060px !important;
        table-layout: fixed !important;
    }
    .kroscek-table-grid th, 
    .kroscek-table-grid td {
        border: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        padding: 6px 5px !important;
        line-height: 1.3 !important;
    }
    .kroscek-table-grid thead th {
        border-bottom: 2px solid #cbd5e1 !important;
        padding: 7px 4px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        letter-spacing: -0.1px;
    }
    .kroscek-table-grid col.col-no,
    .kroscek-table-grid th.col-no,
    .kroscek-table-grid td.col-no {
        width: 3.5% !important;
        max-width: 42px !important;
        min-width: 34px !important;
        padding-left: 2px !important;
        padding-right: 2px !important;
        text-align: center !important;
        white-space: nowrap !important;
    }
    .th-group-kroscek {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        font-weight: 600;
    }
    .th-sub-kroscek {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 500;
    }
    .th-group-modul {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        font-weight: 600;
    }
    .th-sub-modul {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 500;
    }

    /* Container Scrollable Tabel Kroscek */
    .table-responsive-kroscek {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
    }
    .table-responsive-kroscek::-webkit-scrollbar {
        height: 7px;
    }
    .table-responsive-kroscek::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .table-responsive-kroscek::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-responsive-kroscek::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Filter Buttons */
    .btn-quick-filter,
    .btn-quick-filter-detail {
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 50rem;
        padding: 6px 14px;
        border: 1.5px solid #cbd5e1;
        background-color: #fff;
        color: #4b5563;
        transition: all 0.15s ease;
    }
    .btn-quick-filter:hover,
    .btn-quick-filter-detail:hover {
        background-color: #f9fafb;
        color: #111827;
        border-color: #94a3b8;
    }
    .btn-quick-filter.active,
    .btn-quick-filter-detail.active {
        background-color: #2563eb;
        color: #fff !important;
        border-color: #2563eb;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }

    /* Mobile Card Styles */
    .kroscek-mobile-card {
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 12px;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kroscek-mobile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    }

    .kroscek-table-grid td {
        background-color: #ffffff;
        color: #1e293b;
    }
    .kroscek-table-grid tbody tr:hover td {
        background-color: #f8fafc;
    }

    [data-theme="dark"] .kpi-card,
    [data-theme="dark"] .kroscek-card-box {
        background: #1e293b;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .kroscek-table-grid {
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] .kroscek-table-grid th,
    [data-theme="dark"] .kroscek-table-grid td {
        border-color: #334155 !important;
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] .kroscek-table-grid tbody tr:hover td {
        background-color: #273549 !important;
    }
    [data-theme="dark"] .th-group-kroscek,
    [data-theme="dark"] .th-group-modul {
        background-color: #0f172a !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .th-sub-kroscek,
    [data-theme="dark"] .th-sub-modul {
        background-color: #1e293b !important;
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .kroscek-table-grid thead th {
        border-bottom-color: #475569 !important;
    }
    [data-theme="dark"] .table-responsive-kroscek {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .table-responsive-kroscek::-webkit-scrollbar-track {
        background: #0f172a;
    }
    [data-theme="dark"] .table-responsive-kroscek::-webkit-scrollbar-thumb {
        background: #475569;
    }
    [data-theme="dark"] .btn-quick-filter,
    [data-theme="dark"] .btn-quick-filter-detail {
        background-color: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }
    [data-theme="dark"] .btn-quick-filter:hover,
    [data-theme="dark"] .btn-quick-filter-detail:hover {
        background-color: #334155;
        color: #ffffff;
    }
    [data-theme="dark"] .btn-quick-filter.active,
    [data-theme="dark"] .btn-quick-filter-detail.active {
        background-color: #2563eb;
        color: #ffffff !important;
    }
    [data-theme="dark"] .status-pill-secondary {
        background-color: #334155;
        color: #94a3b8;
        border-color: #475569;
    }
    [data-theme="dark"] .kroscek-mobile-card {
        background-color: #1e293b;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .kroscek-mobile-card .bg-light {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
</style>
@endpush

@section('content')
<div class="content-card">

@if($isOperator)
    {{-- ========================================================================= --}}
    {{-- TAMPILAN OPERATOR UPT (KELENGKAPAN DETAIL PER TANGGAL SESUAI ROLE UPT)     --}}
    {{-- ========================================================================= --}}
    {{-- Header Operator --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-calendar-alt text-primary me-2"></i>Kroscek Data & Kelengkapan Pelaporan Harian UPT
                </h5>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 small fw-semibold">
                    <i class="fas fa-lock me-1"></i>Role Operator UPT
                </span>
            </div>
            <p class="text-muted small mb-0">
                Pantau riwayat kelengkapan penginputan 4 modul laporan dan kesesuaian data tahanan tanggal demi tanggal untuk UPT Anda.
            </p>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-6 shadow-sm">
                <i class="fas fa-building me-1"></i> {{ $selectedDetailUpt->nama_upt ?? 'UPT Anda' }}
            </span>
        </div>
    </div>

    {{-- Filter Card Operator: Rentang Tanggal & Presets --}}
    <div class="card mb-4 bg-white shadow-sm rounded-3" style="border: 1.5px solid #cbd5e1 !important;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('kroscek.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="detail_upt_id" value="{{ $selectedDetailUptId }}">

                {{-- 1. Info UPT (Readonly sesuai role UPT operator) --}}
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="fas fa-building text-primary me-1"></i> UPT Anda:
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="fas fa-shield-alt text-primary"></i>
                        </span>
                        <input type="text" class="form-control form-control-sm bg-light fw-bold text-dark border-start-0" value="{{ $selectedDetailUpt->nama_upt ?? 'UPT Anda' }}" readonly>
                    </div>
                </div>

                {{-- 2. Mulai Tanggal --}}
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Mulai Tanggal:
                    </label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" style="border-color: #cbd5e1;" value="{{ $detailTanggalMulai }}" aria-label="Mulai Tanggal">
                </div>

                {{-- 3. Sampai Tanggal --}}
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="fas fa-calendar-check text-primary me-1"></i> Sampai Tanggal:
                    </label>
                    <input type="date" name="tanggal_akhir" class="form-control form-control-sm" style="border-color: #cbd5e1;" value="{{ $detailTanggalAkhir }}" aria-label="Sampai Tanggal">
                </div>

                {{-- 4. Tombol Filter --}}
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 shadow-sm fw-semibold">
                        <i class="fas fa-filter me-1"></i> Terapkan
                    </button>
                </div>
            </form>

            {{-- Presets Cepat Tanggal --}}
            <div class="d-flex align-items-center flex-wrap gap-2 pt-2 mt-2 border-top small">
                <span class="text-muted"><i class="fas fa-bolt text-warning me-1"></i>Preset Cepat:</span>
                <a href="{{ route('kroscek.index', ['tanggal_mulai' => date('Y-m-01'), 'tanggal_akhir' => date('Y-m-d')]) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small">
                    Bulan Ini
                </a>
                <a href="{{ route('kroscek.index', ['tanggal_mulai' => \Carbon\Carbon::now()->subDays(6)->format('Y-m-d'), 'tanggal_akhir' => date('Y-m-d')]) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small">
                    7 Hari Terakhir
                </a>
                <a href="{{ route('kroscek.index', ['tanggal_mulai' => \Carbon\Carbon::now()->subDays(29)->format('Y-m-d'), 'tanggal_akhir' => date('Y-m-d')]) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small">
                    30 Hari Terakhir
                </a>
            </div>
        </div>
    </div>

    {{-- Partial Table Detail Tanggal (KPI Cards, Search Toolbar, and Table) --}}
    @include('kroscek.partials.table_detail_tanggal')

@else
    {{-- ========================================================================= --}}
    {{-- TAMPILAN ADMIN KANWIL (2 TAB: 1. MATRIKS 12 UPT, 2. DETAIL PER TANGGAL)     --}}
    {{-- ========================================================================= --}}
    {{-- Header Admin Kanwil --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="mb-1 fw-bold text-dark">
                <i class="fas fa-check-double text-primary me-2"></i>Kroscek Data Tahanan & Monitoring 4 Modul Wilayah
            </h5>
            <p class="text-muted small mb-0">
                Pusat pemantauan kecocokan data tahanan dan jam input 4 modul laporan harian seluruh UPT se-Banten
            </p>
        </div>
    </div>

    {{-- Nav Pills Pilihan Tampilan --}}
    <ul class="nav nav-pills gap-2 mb-4 p-1 bg-light rounded-3 shadow-sm border" id="kroscekNavTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'harian' ? 'active' : '' }} fw-semibold px-4 py-2" id="tab-harian-btn" data-bs-toggle="pill" data-bs-target="#tab-harian-pane" type="button" role="tab" aria-controls="tab-harian-pane" aria-selected="{{ $activeTab === 'harian' ? 'true' : 'false' }}">
                <i class="fas fa-th-large me-2"></i> 1. Matriks 12 UPT (Monitoring Harian)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'detail' ? 'active' : '' }} fw-semibold px-4 py-2" id="tab-detail-btn" data-bs-toggle="pill" data-bs-target="#tab-detail-pane" type="button" role="tab" aria-controls="tab-detail-pane" aria-selected="{{ $activeTab === 'detail' ? 'true' : 'false' }}">
                <i class="fas fa-calendar-alt me-2"></i> 2. Kelengkapan Detail per Tanggal (Per UPT)
                <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-25 ms-1">Tabel Tanggal</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="kroscekTabContent">
        {{-- ========================================================================= --}}
        {{-- TAB 1: MATRIKS 12 UPT (MONITORING HARIAN)                                 --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade {{ $activeTab === 'harian' ? 'show active' : '' }}" id="tab-harian-pane" role="tabpanel" aria-labelledby="tab-harian-btn">
            
            {{-- Toolbar Tanggal Khusus Tab 1 --}}
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-calendar-day me-1 text-primary"></i> Evaluasi Tanggal: <strong>{{ \Carbon\Carbon::parse($filterTanggal)->translatedFormat('l, d F Y') }}</strong></h6>
                    <small class="text-muted">Pilih tanggal untuk melihat kroscek data dan jam input 12 UPT</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="{{ route('kroscek.index') }}" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="tab" value="harian">
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
                        <a href="{{ route('kroscek.index', ['tanggal' => $today, 'tab' => 'harian']) }}" class="btn btn-sm btn-outline-secondary" title="Kembali ke Hari Ini">
                            Hari Ini
                        </a>
                    @endif
                </div>
            </div>

            @php
                $pctSinkron = $totalUpt > 0 ? round(($totalSinkron / $totalUpt) * 100) : 0;
                $pctLengkap = $totalUpt > 0 ? round(($totalLengkap4 / $totalUpt) * 100) : 0;
                $pctTahanan = $totalPopulasiSeBanten > 0 ? round(($totalTahananSeBanten / $totalPopulasiSeBanten) * 100) : 0;
            @endphp

            {{-- 4 Kartu KPI Wilayah Se-Banten dengan Garis Jelas & Desain Premium --}}
            <div class="row g-3 mb-4">
                {{-- KPI 1: Kroscek Tahanan Sinkron --}}
                <div class="col-6 col-lg-3">
                    <div class="p-3 kpi-card h-100" style="border-top: 4px solid #10b981 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                                Kroscek Data Tahanan
                            </span>
                            <div class="icon-box bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check-double"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline gap-1 mb-1">
                            <h3 class="mb-0 fw-bold text-success font-monospace">{{ $totalSinkron }}</h3>
                            <span class="text-muted small">/ {{ $totalUpt }} UPT Sinkron</span>
                        </div>

                        {{-- Mini Progress Bar --}}
                        <div class="progress mb-2" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctSinkron }}%;" aria-valuenow="{{ $pctSinkron }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 10.5px;">
                                <i class="fas fa-check-circle me-1"></i>{{ $pctSinkron }}% Sinkron
                            </span>
                            @if($totalSelisih > 0)
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10.5px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $totalSelisih }} Selisih
                                </span>
                            @else
                                <span class="badge bg-light text-muted border" style="font-size: 10.5px;">
                                    0 Selisih
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- KPI 2: Kelengkapan 4 Modul --}}
                <div class="col-6 col-lg-3">
                    <div class="p-3 kpi-card h-100" style="border-top: 4px solid #2563eb !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                                Kelengkapan Modul
                            </span>
                            <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-layer-group"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline gap-1 mb-1">
                            <h3 class="mb-0 fw-bold text-primary font-monospace">{{ $totalLengkap4 }}</h3>
                            <span class="text-muted small">/ {{ $totalUpt }} UPT Lengkap</span>
                        </div>

                        {{-- Mini Progress Bar --}}
                        <div class="progress mb-2" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pctLengkap }}%;" aria-valuenow="{{ $pctLengkap }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10.5px;">
                                <i class="fas fa-check-circle me-1"></i>{{ $pctLengkap }}% Lengkap
                            </span>
                            @if($totalBelumLengkap > 0)
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size: 10.5px;">
                                    <i class="fas fa-clock me-1"></i>{{ $totalBelumLengkap }} Belum
                                </span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 10.5px;">
                                    Semua 4/4
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- KPI 3: Total Populasi WBP Se-Banten --}}
                <div class="col-6 col-lg-3">
                    <div class="p-3 kpi-card h-100" style="border-top: 4px solid #0891b2 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                                Total Isi WBP Banten
                            </span>
                            <div class="icon-box bg-info bg-opacity-10 text-info">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline gap-1 mb-1">
                            <h3 class="mb-0 fw-bold text-dark font-monospace">{{ number_format($totalPopulasiSeBanten) }}</h3>
                            <span class="text-muted small">Jiwa</span>
                        </div>

                        {{-- Mini Progress Bar (Komposisi Tahanan vs Napi) --}}
                        <div class="progress mb-2" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pctTahanan }}%;" aria-valuenow="{{ $pctTahanan }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span class="badge bg-light text-dark border" style="font-size: 10.5px;">
                                Tahanan: <strong>{{ number_format($totalTahananSeBanten) }}</strong>
                            </span>
                            <span class="badge bg-light text-dark border" style="font-size: 10.5px;">
                                Napi: <strong>{{ number_format($totalNapiSeBanten) }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- KPI 4: Bebas Hari Ini & Overstaying --}}
                <div class="col-6 col-lg-3">
                    <div class="p-3 kpi-card h-100" style="border-top: 4px solid #6366f1 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                                Bebas Harian Input
                            </span>
                            <div class="icon-box bg-indigo bg-opacity-10" style="background-color: rgba(99, 102, 241, 0.1); color: #6366f1;">
                                <i class="fas fa-door-open"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline gap-1 mb-1">
                            <h3 class="mb-0 fw-bold text-dark font-monospace">{{ number_format($totalBebasSeBanten) }}</h3>
                            <span class="text-muted small">Orang Bebas</span>
                        </div>

                        {{-- Mini Progress Bar --}}
                        <div class="progress mb-2" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                            <div class="progress-bar" role="progressbar" style="width: 100%; background-color: #6366f1;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span class="badge bg-light text-dark border" style="font-size: 10.5px;">
                                Usulan: <strong>{{ number_format($totalUsulanSeBanten) }}</strong>
                            </span>
                            <span class="badge {{ $totalOverstayingSeBanten > 0 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-light text-dark border' }}" style="font-size: 10.5px;">
                                Overstay: <strong>{{ number_format($totalOverstayingSeBanten) }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Toolbar Pencarian & Quick Filter dengan Garis Jelas --}}
            <div class="card mb-4 bg-light shadow-sm rounded-3" style="border: 1.5px solid #cbd5e1 !important;">
                <div class="card-body py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0" style="border-color: #cbd5e1;"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="searchUpt" class="form-control form-control-sm border-start-0" style="border-color: #cbd5e1;" placeholder="Cari nama UPT..." aria-label="Cari nama UPT">
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

            {{-- Switcher Format Tampilan: Tabel Matriks vs Kartu Grid (Aktif di PC & Mobile) --}}
            <div class="d-flex align-items-center justify-content-between mb-3 p-1.5 bg-light rounded-pill border">
                <span class="text-muted small ps-2 fw-semibold" style="font-size: 11.5px;">
                    <i class="fas fa-sliders-h text-primary me-1"></i> Format Tampilan:
                </span>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1 fw-semibold active" id="btnShowTable" onclick="setKroscekViewMode('table')">
                        <i class="fas fa-table me-1"></i> Tabel
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold" id="btnShowCards" onclick="setKroscekViewMode('cards')">
                        <i class="fas fa-th-large me-1"></i> Kartu Grid
                    </button>
                </div>
            </div>

            {{-- Petunjuk Geser Tabel pada Layar HP / Mobile --}}
            <div class="d-md-none alert alert-light border py-2 px-3 mb-2 small text-muted d-flex align-items-center justify-content-between rounded-3 shadow-xs" id="kroscekScrollHint">
                <span class="small fw-medium"><i class="fas fa-arrows-alt-h text-primary me-1"></i> Geser tabel ke samping untuk melihat detail modul & kroscek</span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10px;">11 Kolom</span>
            </div>

            {{-- Tabel Terpadu 12 UPT Se-Banten dengan Garis Jelas dan Kolom Modul Terpisah --}}
            <div class="table-responsive table-responsive-kroscek shadow-sm mb-4" id="kroscekTableWrapper">
                <table class="table table-bordered table-hover align-middle mb-0 kroscek-table-grid">
                    <colgroup>
                        <col class="col-no" style="width: 3.5%;">
                        <col style="width: 19.5%;">
                        <col style="width: 6.5%;">
                        <col style="width: 6.5%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 17%;">
                        <col style="width: 7%;">
                    </colgroup>
                    <thead>
                        <tr class="text-center align-middle">
                            <th rowspan="2" class="text-center col-no">No</th>
                            <th rowspan="2" class="text-start">Nama UPT</th>
                            <th colspan="3" class="text-center th-group-kroscek">
                                <i class="fas fa-balance-scale me-1"></i> Kroscek Tahanan
                            </th>
                            <th colspan="4" class="text-center th-group-modul">
                                <i class="fas fa-clock me-1"></i> Status & Jam Input 4 Modul Laporan
                            </th>
                            <th rowspan="2" class="text-center">Populasi & Dinamika</th>
                            <th rowspan="2" class="text-center">Aksi</th>
                        </tr>
                        <tr class="text-center align-middle">
                            {{-- Sub-header Kroscek Tahanan --}}
                            <th class="th-sub-kroscek">Penghuni</th>
                            <th class="th-sub-kroscek">Overstay</th>
                            <th class="th-sub-kroscek">Status</th>
                            
                            {{-- Sub-header 4 Modul Laporan --}}
                            <th class="th-sub-modul">Penghuni</th>
                            <th class="th-sub-modul">Overstay</th>
                            <th class="th-sub-modul">Integrasi</th>
                            <th class="th-sub-modul">Bebas Harian</th>
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
                                <td class="text-center text-muted col-no" style="font-size: 12px;">{{ $loop->iteration }}</td>

                                {{-- Nama UPT --}}
                                <td class="text-start">
                                    <div class="fw-semibold text-dark" style="font-size: 12.5px; line-height: 1.35;">{{ $row['nama_upt'] }}</div>
                                    <div class="mt-1">
                                        @if($rowIsLengkap)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                                <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                            </span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                                <i class="fas fa-clock me-1"></i> {{ $row['completed_count'] }}/4 Terisi
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Kroscek Tahanan: Penghuni --}}
                                <td class="text-center font-monospace {{ $row['tahanan_penghuni'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 12.5px;">
                                    {{ $row['tahanan_penghuni'] !== null ? number_format($row['tahanan_penghuni']) : '-' }}
                                </td>

                                {{-- Kroscek Tahanan: Overstaying --}}
                                <td class="text-center font-monospace {{ $row['tahanan_overstaying'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 12.5px;">
                                    {{ $row['tahanan_overstaying'] !== null ? number_format($row['tahanan_overstaying']) : '-' }}
                                </td>

                                {{-- Kroscek Tahanan: Status --}}
                                <td class="text-center">
                                    @if($rowIsSinkron)
                                        <span class="status-pill status-pill-success">
                                            <i class="fas fa-check-circle"></i> Sinkron
                                        </span>
                                    @elseif($rowIsSelisih)
                                        <span class="status-pill status-pill-danger">
                                            <i class="fas fa-times-circle"></i> Selisih {{ $row['selisih'] }}
                                        </span>
                                    @else
                                        <span class="status-pill status-pill-secondary">
                                            <i class="fas fa-minus-circle"></i> Belum
                                        </span>
                                    @endif
                                </td>

                                {{-- Modul 1: Penghuni --}}
                                <td class="text-center">
                                    @if($row['penghuni']['exists'])
                                        <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['penghuni']['jam_str'] }}</div>
                                        @if($row['penghuni']['is_tepat'])
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                                        @endif
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                            <i class="fas fa-times me-0.5"></i>Belum
                                        </span>
                                    @endif
                                </td>

                                {{-- Modul 2: Overstaying --}}
                                <td class="text-center">
                                    @if($row['overstaying']['exists'])
                                        <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['overstaying']['jam_str'] }}</div>
                                        @if($row['overstaying']['is_tepat'])
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                                        @endif
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                            <i class="fas fa-times me-0.5"></i>Belum
                                        </span>
                                    @endif
                                </td>

                                {{-- Modul 3: Integrasi --}}
                                <td class="text-center">
                                    @if($row['integrasi']['exists'])
                                        <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['integrasi']['jam_str'] }}</div>
                                        @if($row['integrasi']['is_tepat'])
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                                        @endif
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                            <i class="fas fa-times me-0.5"></i>Belum
                                        </span>
                                    @endif
                                </td>

                                {{-- Modul 4: Bebas Harian --}}
                                <td class="text-center">
                                    @if($row['bebas']['exists'])
                                        <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['bebas']['jam_str'] }}</div>
                                        @if($row['bebas']['is_tepat'])
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                                        @endif
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                            <i class="fas fa-times me-0.5"></i>Belum
                                        </span>
                                    @endif
                                </td>

                                {{-- Populasi & Dinamika --}}
                                <td class="text-center" style="font-size: 12px; white-space: nowrap;">
                                    <div class="text-dark fw-semibold">
                                        {{ number_format($row['penghuni']['total_penghuni']) }} <span class="fw-normal text-muted" style="font-size: 11px;">WBP</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        Bbs: {{ $row['bebas']['total_bebas'] }} · Ost: {{ $row['overstaying']['total_overstaying'] }}
                                    </div>
                                </td>

                                {{-- Aksi: Lihat Detail Modal --}}
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-medium" style="font-size: 11.5px;" onclick="showDetailModal({{ json_encode($row) }})" title="Lihat Rincian Lengkap UPT">
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

            {{-- Cards Container (Tampilan Kartu Grid PC & Mobile) --}}
            <div id="kroscekCardsContainer" class="d-none mb-4">
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 g-3" id="kroscekCardsGrid">
                    @forelse($kroscekList as $row)
                        @php
                            $rowIsSinkron = ($row['status_kroscek'] === 'sinkron');
                            $rowIsSelisih = ($row['status_kroscek'] === 'selisih');
                            $rowIsLengkap = $row['is_all_completed'];
                            $rowHasIssues = (!$rowIsSinkron || !$rowIsLengkap);
                        @endphp
                        <div class="col kroscek-card-col"
                             data-upt="{{ strtolower($row['nama_upt']) }}"
                             data-sinkron="{{ $rowIsSinkron ? '1' : '0' }}"
                             data-selisih="{{ $rowIsSelisih ? '1' : '0' }}"
                             data-lengkap="{{ $rowIsLengkap ? '1' : '0' }}"
                             data-masalah="{{ $rowHasIssues ? '1' : '0' }}">
                            <div class="card h-100 shadow-sm border rounded-3 kroscek-mobile-card d-flex flex-column justify-content-between">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                            {{-- Header Card: No, UPT Name, Status Badge --}}
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2 pb-2 border-bottom">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill mt-0.5" style="font-size: 11px;">#{{ $loop->iteration }}</span>
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 13.5px; line-height: 1.35;">{{ $row['nama_upt'] }}</h6>
                                        <div>
                                            @if($rowIsLengkap)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                                    <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                                </span>
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                                    <i class="fas fa-clock me-1"></i> {{ $row['completed_count'] }}/4 Terisi
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    @if($rowIsSinkron)
                                        <span class="status-pill status-pill-success">
                                            <i class="fas fa-check-circle"></i> Sinkron
                                        </span>
                                    @elseif($rowIsSelisih)
                                        <span class="status-pill status-pill-danger">
                                            <i class="fas fa-times-circle"></i> Selisih {{ $row['selisih'] }}
                                        </span>
                                    @else
                                        <span class="status-pill status-pill-secondary">
                                            <i class="fas fa-minus-circle"></i> Belum
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Kroscek Data Tahanan --}}
                            <div class="bg-light p-2.5 rounded-3 mb-2.5 border">
                                <div class="d-flex justify-content-between align-items-center small text-muted mb-1 pb-1 border-bottom">
                                    <span class="fw-semibold text-dark"><i class="fas fa-balance-scale text-primary me-1"></i> Kroscek Tahanan:</span>
                                    <span class="font-monospace fw-bold {{ $rowIsSinkron ? 'text-success' : ($rowIsSelisih ? 'text-danger' : 'text-muted') }}" style="font-size: 11.5px;">
                                        {{ $rowIsSinkron ? 'SINKRON' : ($rowIsSelisih ? 'SELISIH '.$row['selisih'] : 'BELUM LENGKAP') }}
                                    </span>
                                </div>
                                <div class="row text-center g-2 pt-1">
                                    <div class="col-6 border-end">
                                        <div class="text-muted" style="font-size: 11px;">Penghuni</div>
                                        <div class="fw-bold font-monospace {{ $row['tahanan_penghuni'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 14px;">
                                            {{ $row['tahanan_penghuni'] !== null ? number_format($row['tahanan_penghuni']) : '-' }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted" style="font-size: 11px;">Overstaying</div>
                                        <div class="fw-bold font-monospace {{ $row['tahanan_overstaying'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 14px;">
                                            {{ $row['tahanan_overstaying'] !== null ? number_format($row['tahanan_overstaying']) : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Jam Input 4 Modul --}}
                            <div class="mb-2.5">
                                <div class="small fw-semibold text-muted mb-1.5"><i class="fas fa-clock text-primary me-1"></i> Jam Input 4 Modul:</div>
                                <div class="row g-1.5 text-center">
                                    <div class="col-3">
                                        <div class="p-1.5 rounded-2 bg-light border">
                                            <div class="text-muted" style="font-size: 10px;">Penghuni</div>
                                            @if($row['penghuni']['exists'])
                                                <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['penghuni']['jam_str'] }}</div>
                                                <span class="badge {{ $row['penghuni']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['penghuni']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['penghuni']['is_tepat'] ? 'Tepat' : 'Terlambat' }}</span>
                                            @else
                                                <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                                <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-1.5 rounded-2 bg-light border">
                                            <div class="text-muted" style="font-size: 10px;">Overstay</div>
                                            @if($row['overstaying']['exists'])
                                                <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['overstaying']['jam_str'] }}</div>
                                                <span class="badge {{ $row['overstaying']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['overstaying']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['overstaying']['is_tepat'] ? 'Tepat' : 'Terlambat' }}</span>
                                            @else
                                                <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                                <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-1.5 rounded-2 bg-light border">
                                            <div class="text-muted" style="font-size: 10px;">Integrasi</div>
                                            @if($row['integrasi']['exists'])
                                                <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['integrasi']['jam_str'] }}</div>
                                                <span class="badge {{ $row['integrasi']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['integrasi']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['integrasi']['is_tepat'] ? 'Tepat' : 'Terlambat' }}</span>
                                            @else
                                                <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                                <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-1.5 rounded-2 bg-light border">
                                            <div class="text-muted" style="font-size: 10px;">Bebas</div>
                                            @if($row['bebas']['exists'])
                                                <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['bebas']['jam_str'] }}</div>
                                                <span class="badge {{ $row['bebas']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['bebas']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['bebas']['is_tepat'] ? 'Tepat' : 'Terlambat' }}</span>
                                            @else
                                                <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                                <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Populasi & Tombol Aksi --}}
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 12.5px;">{{ number_format($row['penghuni']['total_penghuni']) }} WBP</div>
                                            <div class="text-muted" style="font-size: 11px;">Bbs: {{ $row['bebas']['total_bebas'] }} · Ost: {{ $row['overstaying']['total_overstaying'] }}</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 fw-medium rounded-pill shadow-xs" style="font-size: 11.5px;" onclick="showDetailModal({{ json_encode($row) }})">
                                            <i class="fas fa-eye me-1"></i> Rincian
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="card p-4 text-center text-muted">Belum ada data UPT terdaftar.</div></div>
                    @endforelse
                </div>
            </div>

        </div>{{-- Penutup Tab 1 (#tab-harian-pane) --}}

        {{-- ========================================================================= --}}
        {{-- TAB 2: KELENGKAPAN DETAIL PER TANGGAL (PER UPT / RIWAYAT TANGGAL)        --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade {{ $activeTab === 'detail' ? 'show active' : '' }}" id="tab-detail-pane" role="tabpanel" aria-labelledby="tab-detail-btn">
            
            {{-- Header Penjelasan Tab 2 --}}
            <div class="mb-3 pb-2 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> 
                        Kelengkapan Pelaporan & Kroscek Harian per Tanggal (Detil Tiap UPT)
                    </h6>
                    <small class="text-muted">
                        Pantau histori kepatuhan penginputan 4 modul laporan dan kecocokan data tahanan tanggal demi tanggal untuk UPT yang dipilih
                    </small>
                </div>
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fw-semibold">
                        <i class="fas fa-building me-1"></i> {{ $selectedDetailUpt->nama_upt ?? 'UPT' }}
                    </span>
                </div>
            </div>

            {{-- Card Filter Khusus Tabel 2 Kanwil (dengan Dropdown Pilih UPT) --}}
            <div class="card mb-4 bg-white shadow-sm rounded-3" style="border: 1.5px solid #cbd5e1 !important;">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('kroscek.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="tab" value="detail">
                        @if(request()->has('tanggal'))
                            <input type="hidden" name="tanggal" value="{{ $filterTanggal }}">
                        @endif

                        {{-- 1. Filter UPT --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-building text-primary me-1"></i> Pilih UPT:
                            </label>
                            <select name="detail_upt_id" class="form-select form-select-sm fw-semibold text-dark" style="border-color: #cbd5e1;" onchange="this.form.submit()" aria-label="Pilih UPT">
                                @foreach($allUptList as $uptItem)
                                    <option value="{{ $uptItem->id }}" {{ $selectedDetailUptId == $uptItem->id ? 'selected' : '' }}>
                                        {{ $uptItem->nama_upt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 2. Mulai Tanggal --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-calendar-alt text-primary me-1"></i> Mulai Tanggal:
                            </label>
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm" style="border-color: #cbd5e1;" value="{{ $detailTanggalMulai }}" aria-label="Mulai Tanggal">
                        </div>

                        {{-- 3. Sampai Tanggal --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-calendar-check text-primary me-1"></i> Sampai Tanggal:
                            </label>
                            <input type="date" name="tanggal_akhir" class="form-control form-control-sm" style="border-color: #cbd5e1;" value="{{ $detailTanggalAkhir }}" aria-label="Sampai Tanggal">
                        </div>

                        {{-- 4. Tombol Filter --}}
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100 shadow-sm fw-semibold">
                                <i class="fas fa-filter me-1"></i> Terapkan
                            </button>
                        </div>
                    </form>

                    {{-- Presets Cepat Tanggal --}}
                    <div class="d-flex align-items-center flex-wrap gap-2 pt-2 mt-2 border-top small">
                        <span class="text-muted"><i class="fas fa-bolt text-warning me-1"></i>Preset Cepat:</span>
                        <a href="{{ route('kroscek.index', ['tab' => 'detail', 'detail_upt_id' => $selectedDetailUptId, 'tanggal_mulai' => date('Y-m-01'), 'tanggal_akhir' => date('Y-m-d')]) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small">
                            Bulan Ini
                        </a>
                        <a href="{{ route('kroscek.index', ['tab' => 'detail', 'detail_upt_id' => $selectedDetailUptId, 'tanggal_mulai' => \Carbon\Carbon::now()->subDays(6)->format('Y-m-d'), 'tanggal_akhir' => date('Y-m-d')]) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small">
                            7 Hari Terakhir
                        </a>
                        <a href="{{ route('kroscek.index', ['tab' => 'detail', 'detail_upt_id' => $selectedDetailUptId, 'tanggal_mulai' => \Carbon\Carbon::now()->subDays(29)->format('Y-m-d'), 'tanggal_akhir' => date('Y-m-d')]) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small">
                            30 Hari Terakhir
                        </a>
                    </div>
                </div>
            </div>

            {{-- Include Partial Table Detail Tanggal --}}
            @include('kroscek.partials.table_detail_tanggal')

        </div>{{-- Penutup Tab 2 (#tab-detail-pane) --}}

    </div>{{-- Penutup .tab-content --}}
@endif

</div>{{-- Penutup .content-card --}}

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
                <div id="detailKroscekAlert" class="alert mb-4 border-0 p-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i id="detailKroscekIcon" class="fas fa-check-circle fs-4 flex-shrink-0"></i>
                        <div>
                            <strong id="detailKroscekTitle"></strong>
                            <div class="small" id="detailKroscekDesc"></div>
                            <div id="detailKroscekActions" class="mt-2 d-none flex-wrap gap-2 align-items-center">
                                <a href="#" id="btnFixPenghuni" class="btn btn-sm btn-danger py-1 px-2.5 shadow-sm fw-semibold" style="font-size: 11.5px;">
                                    <i class="fas fa-users me-1"></i> Periksa Form Penghuni
                                </a>
                                <a href="#" id="btnFixOverstaying" class="btn btn-sm btn-danger py-1 px-2.5 shadow-sm fw-semibold" style="font-size: 11.5px;">
                                    <i class="fas fa-clock me-1"></i> Periksa Form Overstaying
                                </a>
                                <span id="selisih_lock_notice" class="badge bg-light text-danger border border-danger-subtle py-1 px-2.5 fw-normal d-none align-self-center" style="font-size: 11px;">
                                    <i class="fas fa-lock me-1"></i> Akses Terbatas: Hubungi Petugas Terkait / Admin UPT
                                </span>
                            </div>
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
                        <div id="p_notif_belum" class="alert alert-warning py-2 px-3 small rounded-3 d-none align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle fs-5 me-2 text-warning flex-shrink-0"></i>
                            <div><strong>Modul Penghuni Belum Diinput!</strong> Data penghuni belum tercatat untuk tanggal evaluasi ini.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-4 text-center p-3 bg-light rounded-3" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted small">Tahanan</span>
                                <h4 class="fw-bold my-1 text-primary font-monospace" id="det_p_tahanan">0</h4>
                            </div>
                            <div class="col-sm-4 text-center p-3 bg-light rounded-3" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted small">Narapidana</span>
                                <h4 class="fw-bold my-1 text-dark font-monospace" id="det_p_napi">0</h4>
                            </div>
                            <div class="col-sm-4 text-center p-3 bg-light rounded-3" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted small">Total Isi WBP</span>
                                <h4 class="fw-bold my-1 text-success font-monospace" id="det_p_total">0</h4>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small text-muted">
                                <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_p_waktu">-</strong>
                            </div>
                            <div id="p_action_container" class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="#" id="p_btn_input" class="btn btn-sm btn-primary fw-semibold d-none">
                                    <i class="fas fa-plus me-1"></i> Input Data Tanggal Ini
                                </a>
                                <a href="#" id="p_btn_manage" class="btn btn-sm btn-outline-primary fw-semibold d-none">
                                    <i class="fas fa-external-link-alt me-1"></i> Buka / Kelola Form Penghuni
                                </a>
                                <div id="p_lock_notice" class="badge bg-secondary bg-opacity-75 py-1.5 px-2.5 text-wrap fw-normal d-none" style="font-size: 11px;">
                                    <i class="fas fa-lock me-1"></i> <span id="p_lock_text">Akses Terbatas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Overstaying --}}
                    <div class="tab-pane fade" id="tab-overstaying" role="tabpanel">
                        <div id="o_notif_belum" class="alert alert-warning py-2 px-3 small rounded-3 d-none align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle fs-5 me-2 text-warning flex-shrink-0"></i>
                            <div><strong>Modul Overstaying Belum Diinput!</strong> Data overstaying belum tercatat untuk tanggal evaluasi ini.</div>
                        </div>
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
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small text-muted">
                                <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_o_waktu">-</strong>
                            </div>
                            <div id="o_action_container" class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="#" id="o_btn_input" class="btn btn-sm btn-primary fw-semibold d-none">
                                    <i class="fas fa-plus me-1"></i> Input Data Tanggal Ini
                                </a>
                                <a href="#" id="o_btn_manage" class="btn btn-sm btn-outline-primary fw-semibold d-none">
                                    <i class="fas fa-external-link-alt me-1"></i> Buka / Kelola Form Overstaying
                                </a>
                                <div id="o_lock_notice" class="badge bg-secondary bg-opacity-75 py-1.5 px-2.5 text-wrap fw-normal d-none" style="font-size: 11px;">
                                    <i class="fas fa-lock me-1"></i> <span id="o_lock_text">Akses Terbatas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: Integrasi --}}
                    <div class="tab-pane fade" id="tab-integrasi" role="tabpanel">
                        <div id="i_notif_belum" class="alert alert-warning py-2 px-3 small rounded-3 d-none align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle fs-5 me-2 text-warning flex-shrink-0"></i>
                            <div><strong>Modul Integrasi Belum Diinput!</strong> Data integrasi belum tercatat untuk tanggal evaluasi ini.</div>
                        </div>
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
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small text-muted">
                                <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_i_waktu">-</strong>
                            </div>
                            <div id="i_action_container" class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="#" id="i_btn_input" class="btn btn-sm btn-primary fw-semibold d-none">
                                    <i class="fas fa-plus me-1"></i> Input Data Tanggal Ini
                                </a>
                                <a href="#" id="i_btn_manage" class="btn btn-sm btn-outline-primary fw-semibold d-none">
                                    <i class="fas fa-external-link-alt me-1"></i> Buka / Kelola Form Integrasi
                                </a>
                                <div id="i_lock_notice" class="badge bg-secondary bg-opacity-75 py-1.5 px-2.5 text-wrap fw-normal d-none" style="font-size: 11px;">
                                    <i class="fas fa-lock me-1"></i> <span id="i_lock_text">Akses Terbatas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 4: Bebas Harian --}}
                    <div class="tab-pane fade" id="tab-bebas" role="tabpanel">
                        <div id="b_notif_belum" class="alert alert-warning py-2 px-3 small rounded-3 d-none align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle fs-5 me-2 text-warning flex-shrink-0"></i>
                            <div><strong>Modul Bebas Harian Belum Diinput!</strong> Data bebas harian belum tercatat untuk tanggal evaluasi ini.</div>
                        </div>
                        <div class="row g-2 text-center small">
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted d-block">Bebas Murni</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_murni">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted d-block">PB</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_pb">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted d-block">CB</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_cb">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted d-block">CMB</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_cmb">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted d-block">Asimilasi</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_asimilasi">0</strong>
                            </div>
                            <div class="col-4 col-sm-2 p-2 bg-light rounded-2" style="border: 1px solid #cbd5e1;">
                                <span class="text-muted d-block">BDH / Lain</span>
                                <strong class="fs-6 text-dark font-monospace" id="det_b_lain">0</strong>
                            </div>
                        </div>
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 mt-3 d-flex justify-content-between align-items-center" style="border: 1px solid #a7f3d0;">
                            <span class="fw-bold"><i class="fas fa-door-open me-2"></i>Total Bebas Hari Ini:</span>
                            <h4 class="mb-0 fw-bold font-monospace" id="det_b_total">0 Orang</h4>
                        </div>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small text-muted">
                                <i class="fas fa-info-circle me-1 text-primary"></i> Waktu Input: <strong id="det_b_waktu">-</strong>
                            </div>
                            <div id="b_action_container" class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="#" id="b_btn_input" class="btn btn-sm btn-primary fw-semibold d-none">
                                    <i class="fas fa-plus me-1"></i> Input Data Tanggal Ini
                                </a>
                                <a href="#" id="b_btn_manage" class="btn btn-sm btn-outline-primary fw-semibold d-none">
                                    <i class="fas fa-external-link-alt me-1"></i> Buka / Kelola Form Bebas Harian
                                </a>
                                <div id="b_lock_notice" class="badge bg-secondary bg-opacity-75 py-1.5 px-2.5 text-wrap fw-normal d-none" style="font-size: 11px;">
                                    <i class="fas fa-lock me-1"></i> <span id="b_lock_text">Akses Terbatas</span>
                                </div>
                            </div>
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
    // Konfigurasi Hak Akses User yang Sedang Login
    const currentUser = {
        isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }},
        uptId: {{ (int) (auth()->user()->upt_id ?? 0) }},
        permissions: {
            penghuni: {{ auth()->user()->hasAccess('penghuni') ? 'true' : 'false' }},
            overstaying: {{ auth()->user()->hasAccess('overstaying') ? 'true' : 'false' }},
            integrasi: {{ auth()->user()->hasAccess('integrasi') ? 'true' : 'false' }},
            bebas_harian: {{ auth()->user()->hasAccess('bebas_harian') ? 'true' : 'false' }}
        }
    };

    // Fitur Pencarian Realtime & Quick Filter untuk Admin (Matriks 12 UPT)
    const searchInput = document.getElementById('searchUpt');
    const filterBtns = document.querySelectorAll('.btn-quick-filter');
    const tableRows = document.querySelectorAll('#kroscekTableBody tr[data-upt]');
    const mobileCards = document.querySelectorAll('#kroscekCardsContainer .kroscek-card-col');

    function applyAdminFilters() {
        const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const activeBtn = document.querySelector('.btn-quick-filter.active');
        const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';

        const filterElement = (el) => {
            const uptName = el.getAttribute('data-upt') || '';
            const isSinkron = el.getAttribute('data-sinkron') === '1';
            const isSelisih = el.getAttribute('data-selisih') === '1';
            const isLengkap = el.getAttribute('data-lengkap') === '1';
            const hasIssues = el.getAttribute('data-masalah') === '1';

            const matchesSearch = !searchVal || uptName.includes(searchVal);
            let matchesCategory = true;

            if (activeFilter === 'masalah') {
                matchesCategory = hasIssues;
            } else if (activeFilter === 'sinkron') {
                matchesCategory = isSinkron;
            } else if (activeFilter === 'lengkap') {
                matchesCategory = isLengkap;
            }

            el.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
        };

        tableRows.forEach(filterElement);
        mobileCards.forEach(filterElement);
    }

    searchInput?.addEventListener('input', applyAdminFilters);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyAdminFilters();
        });
    });

    // Switcher Tampilan (PC & Mobile): Tabel vs Kartu Grid dengan Auto-save LocalStorage
    const KROSCEK_VIEW_KEY = 'sultan_kroscek_view_mode';

    function setKroscekViewMode(mode, save = true) {
        const btnShowTable = document.getElementById('btnShowTable');
        const btnShowCards = document.getElementById('btnShowCards');
        const tableWrapper = document.getElementById('kroscekTableWrapper');
        const scrollHint = document.getElementById('kroscekScrollHint');
        const cardsContainer = document.getElementById('kroscekCardsContainer');

        if (mode === 'cards') {
            btnShowCards?.classList.remove('btn-outline-secondary');
            btnShowCards?.classList.add('btn-primary', 'active');
            btnShowTable?.classList.remove('btn-primary', 'active');
            btnShowTable?.classList.add('btn-outline-secondary');

            tableWrapper?.classList.add('d-none');
            scrollHint?.classList.add('d-none');
            cardsContainer?.classList.remove('d-none');
            if (save) {
                try { localStorage.setItem(KROSCEK_VIEW_KEY, 'cards'); } catch(e) {}
            }
        } else {
            btnShowTable?.classList.remove('btn-outline-secondary');
            btnShowTable?.classList.add('btn-primary', 'active');
            btnShowCards?.classList.remove('btn-primary', 'active');
            btnShowCards?.classList.add('btn-outline-secondary');

            tableWrapper?.classList.remove('d-none');
            scrollHint?.classList.remove('d-none');
            cardsContainer?.classList.add('d-none');
            if (save) {
                try { localStorage.setItem(KROSCEK_VIEW_KEY, 'table'); } catch(e) {}
            }
        }
    }
    window.setKroscekViewMode = setKroscekViewMode;

    document.getElementById('btnShowTable')?.addEventListener('click', () => setKroscekViewMode('table'));
    document.getElementById('btnShowCards')?.addEventListener('click', () => setKroscekViewMode('cards'));

    try {
        const savedKroscekMode = localStorage.getItem(KROSCEK_VIEW_KEY);
        if (savedKroscekMode === 'cards') {
            setKroscekViewMode('cards', false);
        } else {
            setKroscekViewMode('table', false);
        }
    } catch(e) {
        setKroscekViewMode('table', false);
    }

    // Modal Popup Detail UPT
    function showDetailModal(data) {
        const uptName = data.nama_upt || 'UPT';
        const tglText = data.tanggal_label || '{{ \Carbon\Carbon::parse($filterTanggal)->translatedFormat('l, d F Y') }}';
        document.getElementById('detailUptName').innerHTML = `<i class="fas fa-building me-2"></i>` + uptName;
        document.getElementById('detailTanggalLaporan').innerText = 'Tanggal Evaluasi: ' + tglText;

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

        // Quick Actions & Dynamic URLs
        const tgl = data.tanggal;
        const uptId = parseInt(data.upt_id);
        const canManageUpt = currentUser.isAdmin || (currentUser.uptId === uptId);

        // Quick Action Perbaikan Selisih Tahanan
        const detailKroscekActions = document.getElementById('detailKroscekActions');
        const btnFixPenghuni = document.getElementById('btnFixPenghuni');
        const btnFixOverstaying = document.getElementById('btnFixOverstaying');
        const selisihLockNotice = document.getElementById('selisih_lock_notice');

        if (data.status_kroscek === 'selisih') {
            detailKroscekActions.classList.remove('d-none');
            detailKroscekActions.classList.add('d-flex');

            const canFixPenghuni = canManageUpt && currentUser.permissions.penghuni;
            const canFixOverstaying = canManageUpt && currentUser.permissions.overstaying;

            if (canFixPenghuni) {
                btnFixPenghuni.classList.remove('d-none');
                btnFixPenghuni.href = `{{ url('/penghuni') }}?tanggal_mulai=${tgl}&tanggal_sampai=${tgl}&upt_id=${uptId}`;
            } else {
                btnFixPenghuni.classList.add('d-none');
            }

            if (canFixOverstaying) {
                btnFixOverstaying.classList.remove('d-none');
                btnFixOverstaying.href = `{{ url('/overstaying') }}?tanggal_mulai=${tgl}&tanggal_sampai=${tgl}&upt_id=${uptId}`;
            } else {
                btnFixOverstaying.classList.add('d-none');
            }

            if (selisihLockNotice) {
                if (!canFixPenghuni && !canFixOverstaying) {
                    selisihLockNotice.classList.remove('d-none');
                    selisihLockNotice.innerHTML = !canManageUpt
                        ? '<i class="fas fa-lock me-1"></i> Data UPT Lain (Hanya Baca)'
                        : '<i class="fas fa-lock me-1"></i> Akses Terbatas: Anda tidak memiliki izin kelola form Penghuni / Overstaying';
                } else {
                    selisihLockNotice.classList.add('d-none');
                }
            }
        } else {
            detailKroscekActions.classList.add('d-none');
            detailKroscekActions.classList.remove('d-flex');
        }

        // Helper untuk memperbarui aksi tab modul dengan izin akses
        function updateTabActions(modulKey, exists, urlBase, notifEl, btnInput, btnManage, lockEl, lockTextEl, moduleName) {
            const hasModulePerm = currentUser.permissions[modulKey];
            const canManage = canManageUpt && hasModulePerm;

            if (exists) {
                notifEl.classList.add('d-none');
                notifEl.classList.remove('d-flex');
            } else {
                notifEl.classList.remove('d-none');
                notifEl.classList.add('d-flex');
            }

            if (canManage) {
                if (lockEl) lockEl.classList.add('d-none');
                if (exists) {
                    btnInput.classList.add('d-none');
                    btnManage.classList.remove('d-none');
                    btnManage.href = `${urlBase}?tanggal_mulai=${tgl}&tanggal_sampai=${tgl}&upt_id=${uptId}`;
                } else {
                    btnInput.classList.remove('d-none');
                    btnManage.classList.add('d-none');
                    btnInput.href = `${urlBase}?tanggal_mulai=${tgl}&tanggal_sampai=${tgl}&tanggal=${tgl}&upt_id=${uptId}&action=input`;
                }
            } else {
                btnInput.classList.add('d-none');
                btnManage.classList.add('d-none');
                if (lockEl && lockTextEl) {
                    lockEl.classList.remove('d-none');
                    if (!canManageUpt) {
                        lockTextEl.innerText = 'Data UPT Lain (Hanya Baca)';
                    } else {
                        lockTextEl.innerText = `Akses Terbatas: Anda tidak memiliki izin kelola modul ${moduleName}`;
                    }
                }
            }
        }

        // Tab 1: Penghuni Action Buttons
        updateTabActions(
            'penghuni',
            data.penghuni.exists,
            `{{ url('/penghuni') }}`,
            document.getElementById('p_notif_belum'),
            document.getElementById('p_btn_input'),
            document.getElementById('p_btn_manage'),
            document.getElementById('p_lock_notice'),
            document.getElementById('p_lock_text'),
            'Penghuni'
        );

        // Tab 2: Overstaying Action Buttons
        updateTabActions(
            'overstaying',
            data.overstaying.exists,
            `{{ url('/overstaying') }}`,
            document.getElementById('o_notif_belum'),
            document.getElementById('o_btn_input'),
            document.getElementById('o_btn_manage'),
            document.getElementById('o_lock_notice'),
            document.getElementById('o_lock_text'),
            'Overstaying'
        );

        // Tab 3: Integrasi Action Buttons
        updateTabActions(
            'integrasi',
            data.integrasi.exists,
            `{{ url('/integrasi') }}`,
            document.getElementById('i_notif_belum'),
            document.getElementById('i_btn_input'),
            document.getElementById('i_btn_manage'),
            document.getElementById('i_lock_notice'),
            document.getElementById('i_lock_text'),
            'Integrasi'
        );

        // Tab 4: Bebas Harian Action Buttons
        updateTabActions(
            'bebas_harian',
            data.bebas.exists,
            `{{ url('/bebas-harian') }}`,
            document.getElementById('b_notif_belum'),
            document.getElementById('b_btn_input'),
            document.getElementById('b_btn_manage'),
            document.getElementById('b_lock_notice'),
            document.getElementById('b_lock_text'),
            'Bebas Harian'
        );

        // Trigger Tab 1 Active
        const firstTab = new bootstrap.Tab(document.getElementById('tab-p-btn'));
        firstTab.show();

        const modalEl = new bootstrap.Modal(document.getElementById('modalDetailKroscek'));
        modalEl.show();
    }

    // Fitur Pencarian Realtime & Quick Filter untuk Tabel 2 (Detail per Tanggal)
    const searchTanggalInput = document.getElementById('searchTanggal');
    const filterDetailBtns = document.querySelectorAll('.btn-quick-filter-detail');
    const detailTableRows = document.querySelectorAll('#detailKroscekTableBody tr[data-tanggal]');
    const detailMobileCards = document.querySelectorAll('#detailCardsContainer .detail-card-col');

    function applyDetailFilters() {
        const query = searchTanggalInput ? searchTanggalInput.value.toLowerCase().trim() : '';
        const activeBtn = document.querySelector('.btn-quick-filter-detail.active');
        const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';

        const filterElement = (el) => {
            const tglData = el.getAttribute('data-tanggal') || '';
            const isSinkron = el.getAttribute('data-sinkron') === '1';
            const isSelisih = el.getAttribute('data-selisih') === '1';
            const isLengkap = el.getAttribute('data-lengkap') === '1';
            const hasIssues = el.getAttribute('data-masalah') === '1';

            const matchesSearch = !query || tglData.includes(query);
            let matchesCategory = true;

            if (activeFilter === 'masalah') {
                matchesCategory = hasIssues;
            } else if (activeFilter === 'sinkron') {
                matchesCategory = isSinkron;
            } else if (activeFilter === 'lengkap') {
                matchesCategory = isLengkap;
            }

            el.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
        };

        detailTableRows.forEach(filterElement);
        detailMobileCards.forEach(filterElement);
    }

    searchTanggalInput?.addEventListener('input', applyDetailFilters);

    filterDetailBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterDetailBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyDetailFilters();
        });
    });

    // Switcher Tampilan (PC & Mobile) untuk Tab 2 / Detail Tanggal
    const KROSCEK_DETAIL_VIEW_KEY = 'sultan_kroscek_detail_view_mode';

    function setKroscekDetailViewMode(mode, save = true) {
        const btnShowDetailTable = document.getElementById('btnShowDetailTable');
        const btnShowDetailCards = document.getElementById('btnShowDetailCards');
        const detailTableWrapper = document.getElementById('detailTableWrapper');
        const detailScrollHint = document.getElementById('detailScrollHint');
        const detailCardsContainer = document.getElementById('detailCardsContainer');

        if (mode === 'cards') {
            btnShowDetailCards?.classList.remove('btn-outline-secondary');
            btnShowDetailCards?.classList.add('btn-primary', 'active');
            btnShowDetailTable?.classList.remove('btn-primary', 'active');
            btnShowDetailTable?.classList.add('btn-outline-secondary');

            detailTableWrapper?.classList.add('d-none');
            detailScrollHint?.classList.add('d-none');
            detailCardsContainer?.classList.remove('d-none');
            if (save) {
                try { localStorage.setItem(KROSCEK_DETAIL_VIEW_KEY, 'cards'); } catch(e) {}
            }
        } else {
            btnShowDetailTable?.classList.remove('btn-outline-secondary');
            btnShowDetailTable?.classList.add('btn-primary', 'active');
            btnShowDetailCards?.classList.remove('btn-primary', 'active');
            btnShowDetailCards?.classList.add('btn-outline-secondary');

            detailTableWrapper?.classList.remove('d-none');
            detailScrollHint?.classList.remove('d-none');
            detailCardsContainer?.classList.add('d-none');
            if (save) {
                try { localStorage.setItem(KROSCEK_DETAIL_VIEW_KEY, 'table'); } catch(e) {}
            }
        }
    }
    window.setKroscekDetailViewMode = setKroscekDetailViewMode;

    document.getElementById('btnShowDetailTable')?.addEventListener('click', () => setKroscekDetailViewMode('table'));
    document.getElementById('btnShowDetailCards')?.addEventListener('click', () => setKroscekDetailViewMode('cards'));

    try {
        const savedDetailMode = localStorage.getItem(KROSCEK_DETAIL_VIEW_KEY);
        if (savedDetailMode === 'cards') {
            setKroscekDetailViewMode('cards', false);
        } else {
            setKroscekDetailViewMode('table', false);
        }
    } catch(e) {
        setKroscekDetailViewMode('table', false);
    }
</script>
@endpush
