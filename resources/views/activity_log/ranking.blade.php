@extends('layouts.app')

@section('title', 'Leaderboard & Peringkat Kedisiplinan UPT - Sultan Banten')
@section('page_title')
    <i class="fas fa-trophy me-2 text-warning"></i>Leaderboard & Kepatuhan UPT
@endsection

@push('styles')
<style>
    /* Styling Leaderboard & Podium */
    .podium-container {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 16px;
        margin-bottom: 28px;
        padding: 24px 16px;
        background: linear-gradient(180deg, rgba(79, 70, 229, 0.04) 0%, rgba(79, 70, 229, 0) 100%);
        border-radius: 18px;
        border: 1px dashed var(--border-color, #e2e8f0);
    }

    .podium-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        padding: 20px 16px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        flex: 1;
        max-width: 320px;
    }

    .podium-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1);
    }

    .podium-1 {
        order: 2;
        border: 2px solid #f59e0b;
        background: linear-gradient(180deg, #fffbeb 0%, var(--card-bg, #ffffff) 100%);
        padding-top: 28px;
        padding-bottom: 28px;
        transform: translateY(-10px);
    }
    .podium-1:hover {
        transform: translateY(-16px);
    }

    .podium-2 {
        order: 1;
        border: 1px solid #94a3b8;
        background: linear-gradient(180deg, #f8fafc 0%, var(--card-bg, #ffffff) 100%);
    }

    .podium-3 {
        order: 3;
        border: 1px solid #ea580c;
        background: linear-gradient(180deg, #fff7ed 0%, var(--card-bg, #ffffff) 100%);
    }

    .podium-badge {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
    }
    .podium-1 .podium-badge {
        width: 52px;
        height: 52px;
        font-size: 24px;
        background: linear-gradient(135deg, #fbbf24, #d97706);
        color: #fff;
    }
    .podium-2 .podium-badge {
        background: linear-gradient(135deg, #cbd5e1, #64748b);
        color: #fff;
    }
    .podium-3 .podium-badge {
        background: linear-gradient(135deg, #fdba74, #ea580c);
        color: #fff;
    }

    /* KPI Cards */
    .stat-kpi-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 18px 20px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .stat-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
    }

    /* Rank Badges */
    .rank-pill {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
    }
    .rank-pill-1 { background: #fef3c7; color: #b45309; border: 2px solid #f59e0b; }
    .rank-pill-2 { background: #f1f5f9; color: #475569; border: 2px solid #94a3b8; }
    .rank-pill-3 { background: #ffedd5; color: #c2410c; border: 2px solid #ea580c; }
    .rank-pill-other { background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; }
    .rank-pill-worst { background: #fee2e2; color: #dc2626; border: 2px solid #ef4444; font-weight: 800; }

    /* Interactive Selisih Button */
    .btn-selisih-interactive {
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-selisih-interactive:hover {
        transform: translateY(-1px) scale(1.05);
        background-color: #b91c1c !important;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35) !important;
    }

    /* Progress bar */
    .custom-progress {
        height: 8px;
        border-radius: 6px;
        background-color: rgba(0,0,0,0.06);
        overflow: hidden;
    }

    /* Dark Mode Adjustments */
    [data-theme="dark"] .podium-container {
        background: rgba(30, 41, 59, 0.4);
        border-color: #334155;
    }
    [data-theme="dark"] .podium-card,
    [data-theme="dark"] .stat-kpi-card {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .podium-1 {
        background: linear-gradient(180deg, rgba(245, 158, 11, 0.15) 0%, #1e293b 100%);
    }
    [data-theme="dark"] .podium-2 {
        background: linear-gradient(180deg, rgba(148, 163, 184, 0.15) 0%, #1e293b 100%);
    }
    [data-theme="dark"] .podium-3 {
        background: linear-gradient(180deg, rgba(234, 88, 12, 0.15) 0%, #1e293b 100%);
    }
    [data-theme="dark"] .rank-pill-1 { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border-color: #f59e0b; }
    [data-theme="dark"] .rank-pill-2 { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; border-color: #94a3b8; }
    [data-theme="dark"] .rank-pill-3 { background: rgba(234, 88, 12, 0.2); color: #fdba74; border-color: #ea580c; }
    [data-theme="dark"] .rank-pill-other { background: rgba(30, 41, 59, 0.8); color: #94a3b8; border-color: #475569; }
    [data-theme="dark"] .custom-progress { background-color: #334155; }

    /* Table Responsive & Mobile Card for Ranking */
    .table-ranking-grid {
        min-width: 880px !important;
        border-collapse: collapse;
    }
    .table-responsive-ranking {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 10px;
    }
    .table-responsive-ranking::-webkit-scrollbar {
        height: 7px;
    }
    .table-responsive-ranking::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .table-responsive-ranking::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-responsive-ranking::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .ranking-mobile-card {
        border: 1.5px solid var(--border-color, #e2e8f0) !important;
        border-radius: 14px;
        background: var(--card-bg, #ffffff);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ranking-mobile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    }

    [data-theme="dark"] .table-ranking-grid td {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .table-ranking-grid thead th {
        background-color: #0f172a !important;
        color: #93c5fd !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .table-ranking-grid tbody tr:hover td {
        background-color: #273549 !important;
    }
    [data-theme="dark"] .table-ranking-grid tbody tr.table-danger td {
        background-color: rgba(239, 68, 68, 0.15) !important;
    }
    [data-theme="dark"] .ranking-mobile-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .ranking-mobile-card .bg-light {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .ranking-mobile-card h6,
    [data-theme="dark"] .ranking-mobile-card .text-dark {
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] .table-responsive-ranking::-webkit-scrollbar-track {
        background: #0f172a;
    }
    [data-theme="dark"] .table-responsive-ranking::-webkit-scrollbar-thumb {
        background: #475569;
    }
</style>
@endpush

@section('content')
<!-- Top Tab Navigation -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 pb-2 border-bottom">
    <ul class="nav nav-pills gap-2 p-1 bg-light rounded-pill border">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold text-secondary" href="{{ route('activity-log.index') }}">
                <i class="fas fa-list-alt me-2"></i>Riwayat Log Sistem
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active rounded-pill px-4 py-2 fw-semibold" href="{{ route('activity-log.ranking') }}">
                <i class="fas fa-trophy me-2 text-warning"></i>Leaderboard & Kepatuhan UPT
            </a>
        </li>
    </ul>

    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill small">
            <i class="fas fa-clock me-1"></i> Batas Waktu: <strong>{{ $rankingData['period']['jam_selesai'] }} WIB</strong>
        </span>
        <a href="{{ route('activity-log.ranking.excel', ['tanggal_mulai' => $startDate, 'tanggal_akhir' => $endDate]) }}" 
           class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm"
           aria-label="Export Excel Ranking Kepatuhan UPT">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- Executive KPI Summary Cards -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 g-3 mb-4">
    <!-- Card 1: UPT Peringkat 1 -->
    <div class="col">
        <div class="stat-kpi-card border-start border-4 border-warning h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Peringkat 1 (Top UPT)</span>
                    <h6 class="fw-bold mb-0 text-warning mt-1 lh-sm text-break" title="{{ $rankingData['summary']['top_upt']['nama_upt'] ?? '-' }}">
                        {{ $rankingData['summary']['top_upt']['nama_upt'] ?? 'Belum ada data' }}
                    </h6>
                </div>
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-2" style="width: 38px; height: 38px;">
                    <i class="fas fa-crown fs-5"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span>Skor: <strong class="text-dark">{{ number_format($rankingData['summary']['top_upt']['total_points'] ?? 0) }}</strong></span>
                <span class="badge bg-warning-subtle text-warning">{{ $rankingData['summary']['top_upt']['persentase_kepatuhan'] ?? 0 }}% Kepatuhan</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Peringkat Terendah (Perlu Evaluasi) -->
    <div class="col">
        <div class="stat-kpi-card border-start border-4 border-danger h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-danger small fw-bold text-uppercase">Peringkat Terendah</span>
                        <span class="badge bg-danger-subtle text-danger" style="font-size: 9px;">Evaluasi</span>
                    </div>
                    <h6 class="fw-bold mb-0 text-danger mt-1 lh-sm text-break" title="{{ $rankingData['summary']['lowest_upt']['nama_upt'] ?? '-' }}">
                        {{ $rankingData['summary']['lowest_upt']['nama_upt'] ?? '-' }}
                    </h6>
                </div>
                <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-2" style="width: 38px; height: 38px;">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span>Skor: <strong class="text-danger">{{ number_format($rankingData['summary']['lowest_upt']['total_points'] ?? 0) }}</strong></span>
                <span class="badge bg-danger text-white">#{{ $rankingData['summary']['lowest_upt']['rank'] ?? count($rankingData['rankings']) }} ({{ $rankingData['summary']['lowest_upt']['persentase_kepatuhan'] ?? 0 }}%)</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Rata-rata Kepatuhan Wilayah -->
    <div class="col">
        <div class="stat-kpi-card border-start border-4 border-primary h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Rata-rata Kepatuhan</span>
                    <h3 class="fw-bold mb-0 text-primary mt-1">{{ $rankingData['summary']['avg_compliance'] }}%</h3>
                </div>
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-2" style="width: 38px; height: 38px;">
                    <i class="fas fa-chart-pie fs-5"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span>Total <strong>{{ $rankingData['summary']['total_upt'] }} UPT</strong></span>
                <span class="badge bg-primary-subtle text-primary">se-Banten</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Kroscek Tahanan Sinkron -->
    <div class="col">
        <div class="stat-kpi-card border-start border-4 border-success h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Kroscek Tahanan</span>
                    <h3 class="fw-bold mb-0 text-success mt-1">{{ number_format($rankingData['summary']['total_sync_kroscek']) }} <small class="fs-6 fw-normal text-muted">Sesuai</small></h3>
                </div>
                <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-2" style="width: 38px; height: 38px;">
                    <i class="fas fa-check-double fs-5"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span>Penghuni = Overstaying</span>
                <span class="badge bg-success-subtle text-success">+20 Poin/Hari</span>
            </div>
        </div>
    </div>

    <!-- Card 5: Total Hari Evaluasi -->
    <div class="col">
        <div class="stat-kpi-card border-start border-4 border-info h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Hari Evaluasi</span>
                    <h3 class="fw-bold mb-0 text-info mt-1">{{ $rankingData['period']['total_days'] }} <small class="fs-6 fw-normal text-muted">Hari</small></h3>
                </div>
                <div class="p-2 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-2" style="width: 38px; height: 38px;">
                    <i class="fas fa-calendar-check fs-5"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span>Maks: <strong>{{ number_format($rankingData['period']['max_points']) }} Poin</strong></span>
                <span class="badge bg-info-subtle text-info">100 Poin/Hari</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('activity-log.ranking') }}" class="row g-3 align-items-end">
            <input type="hidden" name="preset" id="presetInput" value="{{ $preset }}">

            <!-- Quick Presets -->
            <div class="col-12 col-lg-5">
                <label class="form-label small fw-bold text-muted mb-2">
                    <i class="fas fa-bolt text-warning me-1"></i>Pilihan Cepat Periode
                </label>
                <div class="btn-group w-100 shadow-none" role="group">
                    <a href="{{ route('activity-log.ranking', ['preset' => 'today']) }}" 
                       class="btn btn-sm btn-outline-primary {{ $preset === 'today' ? 'active fw-bold' : '' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('activity-log.ranking', ['preset' => 'this_week']) }}" 
                       class="btn btn-sm btn-outline-primary {{ $preset === 'this_week' ? 'active fw-bold' : '' }}">
                        Pekan Ini
                    </a>
                    <a href="{{ route('activity-log.ranking', ['preset' => 'this_month']) }}" 
                       class="btn btn-sm btn-outline-primary {{ $preset === 'this_month' ? 'active fw-bold' : '' }}">
                        Bulan Ini
                    </a>
                    <a href="{{ route('activity-log.ranking', ['preset' => 'last_month']) }}" 
                       class="btn btn-sm btn-outline-primary {{ $preset === 'last_month' ? 'active fw-bold' : '' }}">
                        Bulan Lalu
                    </a>
                </div>
            </div>

            <!-- Custom Date Inputs -->
            <div class="col-6 col-lg-3">
                <label for="rank_start_date" class="form-label small fw-bold text-muted mb-1">
                    <i class="fas fa-calendar-alt text-primary me-1"></i>Tanggal Mulai
                </label>
                <input type="date" id="rank_start_date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $startDate }}" aria-label="Tanggal Mulai" required onchange="document.getElementById('presetInput').value='custom'">
            </div>
            <div class="col-6 col-lg-3">
                <label for="rank_end_date" class="form-label small fw-bold text-muted mb-1">
                    <i class="fas fa-calendar-alt text-primary me-1"></i>Tanggal Akhir
                </label>
                <input type="date" id="rank_end_date" name="tanggal_akhir" class="form-control form-control-sm" value="{{ $endDate }}" aria-label="Tanggal Akhir" required onchange="document.getElementById('presetInput').value='custom'">
            </div>
            <div class="col-12 col-lg-1">
                <button type="submit" class="btn btn-primary btn-sm w-100" aria-label="Terapkan Filter Periode">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-2 border-top small text-muted">
            <div>
                <i class="fas fa-info-circle text-primary me-1"></i>
                Periode Aktif: <strong>{{ \Carbon\Carbon::parse($rankingData['period']['start_date'])->translatedFormat('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($rankingData['period']['end_date'])->translatedFormat('d F Y') }}</strong>
                @if($rankingData['period']['end_date'] > $rankingData['period']['effective_end_date'])
                    <span class="text-warning ms-1">(Dievaluasi s/d hari ini: {{ \Carbon\Carbon::parse($rankingData['period']['effective_end_date'])->translatedFormat('d F Y') }})</span>
                @endif
            </div>
            <div>
                <span class="badge bg-light text-dark border">
                    <i class="fas fa-shield-alt text-success me-1"></i>7 Hari Seminggu (Non-stop)
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Podium Top 3 UPT -->
@if(count($rankingData['rankings']) >= 3)
    @php
        $top1 = $rankingData['rankings'][0] ?? null;
        $top2 = $rankingData['rankings'][1] ?? null;
        $top3 = $rankingData['rankings'][2] ?? null;
    @endphp
    <div class="podium-container d-none d-md-flex">
        <!-- Juara 2 (Perak) -->
        @if($top2)
        <div class="podium-card podium-2">
            <div class="podium-badge">🥈</div>
            <span class="badge bg-secondary-subtle text-secondary mb-2 fw-bold">Peringkat 2</span>
            <h6 class="fw-bold mb-2 lh-sm text-break" style="min-height: 38px;" title="{{ $top2['nama_upt'] }}">{{ $top2['nama_upt'] }}</h6>
            <div class="fs-4 fw-extrabold text-secondary">{{ number_format($top2['total_points']) }} <small class="fs-7 text-muted">Poin</small></div>
            <div class="text-muted small mt-2">
                <span class="badge bg-light text-dark border me-1">{{ $top2['persentase_kepatuhan'] }}% Kepatuhan</span>
                <span class="badge bg-success-subtle text-success"><i class="fas fa-check-double me-1"></i>{{ $top2['count_kroscek_sesuai'] }}x Sinkron</span>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill mt-3 w-100" onclick="showDetailModal({{ json_encode($top2) }})">
                <i class="fas fa-eye me-1"></i> Detail Riwayat
            </button>
        </div>
        @endif

        <!-- Juara 1 (Emas) -->
        @if($top1)
        <div class="podium-card podium-1">
            <div class="podium-badge">👑</div>
            <span class="badge bg-warning text-dark mb-2 fw-bold px-3 py-1"><i class="fas fa-star me-1"></i>Peringkat 1 (Juara)</span>
            <h5 class="fw-bold mb-2 lh-sm text-break text-warning" style="min-height: 44px;" title="{{ $top1['nama_upt'] }}">{{ $top1['nama_upt'] }}</h5>
            <div class="fs-3 fw-bolder text-warning">{{ number_format($top1['total_points']) }} <small class="fs-6 text-muted">Poin</small></div>
            <div class="text-muted small mt-2">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle me-1 fw-bold">{{ $top1['persentase_kepatuhan'] }}% Kepatuhan</span>
                <span class="badge bg-success-subtle text-success fw-bold"><i class="fas fa-check-double me-1"></i>{{ $top1['count_kroscek_sesuai'] }}x Sinkron</span>
            </div>
            <button class="btn btn-warning btn-sm rounded-pill mt-3 w-100 fw-bold shadow-sm" onclick="showDetailModal({{ json_encode($top1) }})">
                <i class="fas fa-trophy me-1"></i> Detail Riwayat Juara
            </button>
        </div>
        @endif

        <!-- Juara 3 (Perunggu) -->
        @if($top3)
        <div class="podium-card podium-3">
            <div class="podium-badge">🥉</div>
            <span class="badge bg-danger-subtle text-danger mb-2 fw-bold">Peringkat 3</span>
            <h6 class="fw-bold mb-2 lh-sm text-break" style="min-height: 38px;" title="{{ $top3['nama_upt'] }}">{{ $top3['nama_upt'] }}</h6>
            <div class="fs-4 fw-extrabold text-danger">{{ number_format($top3['total_points']) }} <small class="fs-7 text-muted">Poin</small></div>
            <div class="text-muted small mt-2">
                <span class="badge bg-light text-dark border me-1">{{ $top3['persentase_kepatuhan'] }}% Kepatuhan</span>
                <span class="badge bg-success-subtle text-success"><i class="fas fa-check-double me-1"></i>{{ $top3['count_kroscek_sesuai'] }}x Sinkron</span>
            </div>
            <button class="btn btn-outline-danger btn-sm rounded-pill mt-3 w-100" onclick="showDetailModal({{ json_encode($top3) }})">
                <i class="fas fa-eye me-1"></i> Detail Riwayat
            </button>
        </div>
        @endif
    </div>
@endif

<!-- Callout Alert UPT Peringkat Terendah / Perlu Evaluasi -->
@if(!empty($rankingData['summary']['lowest_upt']))
    @php
        $lowest = $rankingData['summary']['lowest_upt'];
    @endphp
    <div class="alert alert-danger bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-4 p-3 mb-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="badge bg-danger text-white px-2 py-1">Peringkat Terendah / Perlu Evaluasi</span>
                        <h6 class="fw-bold text-danger mb-0">{{ $lowest['nama_upt'] }}</h6>
                    </div>
                    <div class="small text-muted mt-1">
                        Skor: <strong class="text-danger">{{ number_format($lowest['total_points']) }} Poin</strong> (Kepatuhan: <strong class="text-danger">{{ $lowest['persentase_kepatuhan'] }}%</strong>) &bull; 
                        <strong>{{ $lowest['count_modul_kosong'] }} modul belum diisi</strong> &bull; 
                        <strong>{{ $lowest['count_kroscek_selisih'] }} hari terdapat selisih kroscek</strong>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($lowest['count_kroscek_selisih'] > 0)
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm fw-semibold" onclick="showSelisihDatesModal({{ json_encode($lowest['nama_upt']) }}, {{ json_encode($lowest['selisih_dates']) }})">
                        <i class="fas fa-calendar-times me-1"></i> Periksa {{ $lowest['count_kroscek_selisih'] }} Hari Selisih
                    </button>
                @endif
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm text-white fw-semibold" onclick="showDetailModal({{ json_encode($lowest) }})">
                    <i class="fas fa-search me-1"></i> Periksa Riwayat Lengkap
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Tabel Klasemen Peringkat Lengkap Seluruh UPT -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="mb-0 fw-bold text-primary">
                <i class="fas fa-medal me-2 text-warning"></i>Klasemen Lengkap Kedisiplinan UPT
            </h6>
            <small class="text-muted">Total 12 UPT Pemasyarakatan diurutkan berdasarkan perolehan poin dan kepatuhan</small>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary border px-3 py-2 small fw-semibold">
            <i class="fas fa-calendar me-1"></i> Periode: {{ $rankingData['period']['total_days'] }} Hari Efektif
        </span>
    </div>

    <!-- Toolbar: Pencarian Realtime & Switcher Format Tampilan (PC & Mobile) -->
    <div class="px-3 pt-3 pb-2 border-bottom">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="searchLeaderboard" class="form-control bg-light border-start-0" placeholder="Cari nama UPT..." autocomplete="off">
                </div>
            </div>
            <div class="col-12 col-md-auto d-flex align-items-center justify-content-between justify-content-md-end gap-2">
                {{-- Switcher Format Tampilan: Tabel vs Kartu Grid (Aktif di PC & Mobile) --}}
                <div class="d-flex align-items-center gap-1 p-1 bg-light rounded-pill border">
                    <span class="small text-muted fw-semibold ps-2 pe-1 d-none d-md-inline" style="font-size: 11px;">
                        <i class="fas fa-sliders-h text-primary me-1"></i>Format:
                    </span>
                    <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1 fw-semibold active" id="btnShowRankingTable" onclick="setLeaderboardViewMode('table')">
                        <i class="fas fa-table me-1"></i> Tabel
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold" id="btnShowRankingCards" onclick="setLeaderboardViewMode('cards')">
                        <i class="fas fa-th-large me-1"></i> Kartu Grid
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Petunjuk Geser Tabel pada Layar HP / Mobile --}}
    <div class="d-md-none alert alert-light border py-2 px-3 mx-3 mt-3 mb-2 small text-muted d-flex align-items-center justify-content-between rounded-3 shadow-xs" id="rankingScrollHint">
        <span class="small fw-medium"><i class="fas fa-arrows-alt-h text-primary me-1"></i> Geser tabel ke samping untuk melihat detail nilai & kroscek</span>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10px;">7 Kolom</span>
    </div>

    <!-- Tampilan 1: Tabel Matriks Lengkap dengan Horizontal Scrolling -->
    <div class="table-responsive table-responsive-ranking shadow-sm" id="rankingTableWrapper">
        <table class="table table-hover align-middle table-sm mb-0 table-ranking-grid">
            <thead class="bg-light">
                <tr>
                    <th class="text-center" width="6%">Rank</th>
                    <th class="text-start" width="28%">Nama UPT</th>
                    <th class="text-center" width="14%">Total Poin</th>
                    <th class="text-center" width="14%">Kepatuhan</th>
                    <th class="text-center" width="14%">Ketepatan Modul</th>
                    <th class="text-center" width="12%">Kroscek Tahanan</th>
                    <th class="text-center" width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rankingData['rankings'] as $upt)
                    @php
                        $rank = $upt['rank'];
                        $isWorst = !empty($upt['is_worst']);
                        $rankPillClass = match($rank) {
                            1 => 'rank-pill-1',
                            2 => 'rank-pill-2',
                            3 => 'rank-pill-3',
                            default => ($isWorst ? 'rank-pill-worst' : 'rank-pill-other'),
                        };
                        $medalIcon = match($rank) {
                            1 => '🥇',
                            2 => '🥈',
                            3 => '🥉',
                            default => ($isWorst ? '⚠️ ' . $rank : '#' . $rank),
                        };
                        $rowClass = $isWorst ? 'table-danger bg-danger bg-opacity-10 border-start border-4 border-danger' : '';
                    @endphp
                    <tr class="{{ $rowClass }}" data-upt="{{ strtolower($upt['nama_upt']) }}">
                        <!-- Rank -->
                        <td class="text-center">
                            <span class="rank-pill {{ $rankPillClass }}">{{ $medalIcon }}</span>
                        </td>

                        <!-- Nama UPT -->
                        <td>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-dark">{{ $upt['nama_upt'] }}</span>
                                @if($isWorst)
                                    <span class="badge bg-danger text-white shadow-sm" style="font-size: 10px;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Terendah / Perlu Evaluasi
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                <small class="text-muted" style="font-size: 11px;">
                                    <i class="fas fa-clock text-primary me-1"></i>Rata-rata: <strong>{{ $upt['rata_rata_jam_input'] }}</strong>
                                </small>
                                @if($upt['count_hari_sempurna'] > 0)
                                    <span class="badge bg-warning-subtle text-warning" style="font-size: 10px;">
                                        <i class="fas fa-star me-1"></i>{{ $upt['count_hari_sempurna'] }}x Hari Sempurna (100 Poin)
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Total Poin -->
                        <td class="text-center">
                            <span class="fs-6 fw-bold text-primary">{{ number_format($upt['total_points']) }}</span>
                            <span class="text-muted small"> / {{ number_format($upt['max_points']) }}</span>
                            <div class="custom-progress mt-1" style="max-width: 140px; margin: 0 auto;">
                                <div class="bg-primary h-100" style="width: {{ $upt['persentase_kepatuhan'] }}%;"></div>
                            </div>
                        </td>

                        <!-- Persentase Kepatuhan -->
                        <td class="text-center">
                            @php
                                $badgeColor = $upt['persentase_kepatuhan'] >= 85 ? 'success' : ($upt['persentase_kepatuhan'] >= 60 ? 'warning' : 'danger');
                            @endphp
                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle px-2 py-1 fw-bold fs-7">
                                {{ $upt['persentase_kepatuhan'] }}%
                            </span>
                        </td>

                        <!-- Rincian Modul -->
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1" title="Tepat Waktu / Terlambat / Kosong">
                                <span class="badge bg-success-subtle text-success" title="{{ $upt['count_modul_tepat'] }} modul tepat waktu">
                                    <i class="fas fa-check me-1"></i>{{ $upt['count_modul_tepat'] }}
                                </span>
                                <span class="badge bg-warning-subtle text-warning" title="{{ $upt['count_modul_terlambat'] }} modul terlambat">
                                    <i class="fas fa-clock me-1"></i>{{ $upt['count_modul_terlambat'] }}
                                </span>
                                <span class="badge bg-danger-subtle text-danger" title="{{ $upt['count_modul_kosong'] }} modul belum diisi">
                                    <i class="fas fa-times me-1"></i>{{ $upt['count_modul_kosong'] }}
                                </span>
                            </div>
                        </td>

                        <!-- Kroscek Tahanan -->
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap">
                                <span class="badge bg-success-subtle text-success py-1 px-2" title="{{ $upt['count_kroscek_sesuai'] }} hari sinkron/sesuai">
                                    <i class="fas fa-check-double me-1"></i>{{ $upt['count_kroscek_sesuai'] }}x
                                </span>
                                @if(!empty($upt['count_kroscek_perbaikan']) && $upt['count_kroscek_perbaikan'] > 0)
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-1 small" title="{{ $upt['count_kroscek_perbaikan'] }} hari sinkron hasil perbaikan">
                                        <i class="fas fa-wrench text-warning me-1"></i>{{ $upt['count_kroscek_perbaikan'] }}x Edit
                                    </span>
                                @endif
                                @if($upt['count_kroscek_selisih'] > 0)
                                    <button type="button" 
                                            class="btn btn-danger btn-sm py-0 px-2 rounded-pill shadow-sm d-inline-flex align-items-center gap-1 btn-selisih-interactive text-white" 
                                            style="font-size: 11px; font-weight: 600;"
                                            onclick="showSelisihDatesModal({{ json_encode($upt['nama_upt']) }}, {{ json_encode($upt['selisih_dates']) }})" 
                                            title="Klik untuk melihat daftar tanggal berapa saja yang terdapat selisih">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>{{ $upt['count_kroscek_selisih'] }}x Selisih</span>
                                        <i class="fas fa-calendar-alt ms-1" style="font-size: 9px;"></i>
                                    </button>
                                @endif
                            </div>
                        </td>

                        <!-- Tombol Aksi Detail -->
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="showDetailModal({{ json_encode($upt) }})">
                                <i class="fas fa-calendar-alt me-1"></i> Detail
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Tampilan 2: Kartu Grid Responsif untuk PC & Mobile -->
    <div id="rankingCardsContainer" class="p-3 d-none">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-xl-4" id="rankingCardsGrid">
            @forelse($rankingData['rankings'] as $upt)
                @php
                    $rank = $upt['rank'];
                    $isWorst = !empty($upt['is_worst']);
                    $rankPillClass = match($rank) {
                        1 => 'rank-pill-1',
                        2 => 'rank-pill-2',
                        3 => 'rank-pill-3',
                        default => ($isWorst ? 'rank-pill-worst' : 'rank-pill-other'),
                    };
                    $medalIcon = match($rank) {
                        1 => '🥇',
                        2 => '🥈',
                        3 => '🥉',
                        default => ($isWorst ? '⚠️ ' . $rank : '#' . $rank),
                    };
                    $badgeColor = $upt['persentase_kepatuhan'] >= 85 ? 'success' : ($upt['persentase_kepatuhan'] >= 60 ? 'warning' : 'danger');
                @endphp
                <div class="col ranking-card-col" data-upt="{{ strtolower($upt['nama_upt']) }}">
                    <div class="ranking-mobile-card h-100 p-3 shadow-xs d-flex flex-column justify-content-between {{ $isWorst ? 'border-danger border-2' : '' }}">
                        <div>
                            <!-- Card Header: Rank, Nama UPT, Evaluasi Badge & Tombol Detail -->
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rank-pill {{ $rankPillClass }} fs-6">{{ $medalIcon }}</span>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark" style="font-size: 13.5px; line-height: 1.35;">{{ $upt['nama_upt'] }}</h6>
                                        @if($isWorst)
                                            <span class="badge bg-danger text-white shadow-sm mt-1" style="font-size: 10px;">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Terendah / Perlu Evaluasi
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 fw-semibold flex-shrink-0" style="font-size: 11px;" onclick="showDetailModal({{ json_encode($upt) }})">
                                    <i class="fas fa-calendar-alt me-1"></i> Detail
                                </button>
                            </div>

                            <!-- Card Metrics: Total Poin & Kepatuhan -->
                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <div class="p-2 bg-light rounded-3 h-100 border border-light-subtle">
                                        <small class="text-muted d-block fw-semibold" style="font-size: 10px;">TOTAL POIN</small>
                                        <div class="d-flex align-items-baseline gap-1">
                                            <span class="fs-6 fw-bold text-primary">{{ number_format($upt['total_points']) }}</span>
                                            <span class="text-muted small" style="font-size: 11px;">/ {{ number_format($upt['max_points']) }}</span>
                                        </div>
                                        <div class="custom-progress mt-1">
                                            <div class="bg-primary h-100" style="width: {{ $upt['persentase_kepatuhan'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="p-2 bg-light rounded-3 h-100 border border-light-subtle text-center d-flex flex-column justify-content-center">
                                        <small class="text-muted d-block fw-semibold" style="font-size: 10px;">KEPATUHAN</small>
                                        <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle px-2 py-1 fw-bold fs-7 mt-1">
                                            {{ $upt['persentase_kepatuhan'] }}%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Jam Input & Hari Sempurna -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-2 pt-1 small">
                                <span class="text-muted" style="font-size: 11px;">
                                    <i class="fas fa-clock text-primary me-1"></i>Rata-rata: <strong>{{ $upt['rata_rata_jam_input'] }}</strong>
                                </span>
                                @if($upt['count_hari_sempurna'] > 0)
                                    <span class="badge bg-warning-subtle text-warning" style="font-size: 10.5px;">
                                        <i class="fas fa-star me-1"></i>{{ $upt['count_hari_sempurna'] }}x Sempurna
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Status Ketepatan Modul & Kroscek Tahanan -->
                        <div class="p-2 bg-light rounded-3 border border-light-subtle mt-1">
                            <div class="d-flex align-items-center justify-content-between mb-1.5 pb-1 border-bottom">
                                <span class="text-muted small fw-medium" style="font-size: 10.5px;">
                                    <i class="fas fa-cubes text-secondary me-1"></i>4 Modul Harian:
                                </span>
                                <div class="d-flex gap-1">
                                    <span class="badge bg-success-subtle text-success" title="{{ $upt['count_modul_tepat'] }} modul tepat waktu" style="font-size: 10px;">
                                        <i class="fas fa-check me-1"></i>{{ $upt['count_modul_tepat'] }} Tepat
                                    </span>
                                    <span class="badge bg-warning-subtle text-warning" title="{{ $upt['count_modul_terlambat'] }} modul terlambat" style="font-size: 10px;">
                                        <i class="fas fa-clock me-1"></i>{{ $upt['count_modul_terlambat'] }} Terlambat
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger" title="{{ $upt['count_modul_kosong'] }} modul belum diisi" style="font-size: 10px;">
                                        <i class="fas fa-times me-1"></i>{{ $upt['count_modul_kosong'] }} Kosong
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between pt-1">
                                <span class="text-muted small fw-medium" style="font-size: 10.5px;">
                                    <i class="fas fa-clipboard-check text-info me-1"></i>Kroscek:
                                </span>
                                <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                                    <span class="badge bg-success-subtle text-success py-1 px-1.5" style="font-size: 10px;">
                                        <i class="fas fa-check-double me-1"></i>{{ $upt['count_kroscek_sesuai'] }}x
                                    </span>
                                    @if(!empty($upt['count_kroscek_perbaikan']) && $upt['count_kroscek_perbaikan'] > 0)
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-1.5 small" style="font-size: 10px;">
                                            <i class="fas fa-wrench text-warning me-1"></i>{{ $upt['count_kroscek_perbaikan'] }}x Edit
                                        </span>
                                    @endif
                                    @if($upt['count_kroscek_selisih'] > 0)
                                        <button type="button" 
                                                class="btn btn-danger btn-sm py-0 px-2 rounded-pill shadow-xs d-inline-flex align-items-center gap-1 btn-selisih-interactive text-white" 
                                                style="font-size: 10px; font-weight: 600;"
                                                onclick="showSelisihDatesModal({{ json_encode($upt['nama_upt']) }}, {{ json_encode($upt['selisih_dates']) }})" 
                                                title="Klik untuk melihat tanggal selisih">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>{{ $upt['count_kroscek_selisih'] }}x Selisih</span>
                                            <i class="fas fa-calendar-alt ms-1" style="font-size: 8px;"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Tidak ada data untuk periode ini.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Penjelasan Aturan Penilaian (Collapsible Accordion) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent py-3" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#rulesCollapse">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-secondary">
                <i class="fas fa-book-open me-2 text-primary"></i>Panduan Skema Perhitungan Poin Kedisiplinan & Kroscek
            </h6>
            <i class="fas fa-chevron-down text-muted"></i>
        </div>
    </div>
    <div class="collapse show" id="rulesCollapse">
        <div class="card-body pt-0">
            <div class="row g-3 small">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <h6 class="fw-bold text-primary mb-2"><i class="fas fa-star me-1"></i>Komposisi 100 Poin Harian (5 Komponen @ 20 Poin):</h6>
                        <ul class="mb-0 ps-3 text-secondary">
                            <li><strong>Data Penghuni:</strong> Tepat Waktu = 20 Poin | Terlambat Hari-H = 10 Poin | Terlambat Lewat Hari = 5 Poin | Kosong = 0 Poin</li>
                            <li><strong>Data Overstaying:</strong> Tepat Waktu = 20 Poin | Terlambat Hari-H = 10 Poin | Terlambat Lewat Hari = 5 Poin | Kosong = 0 Poin</li>
                            <li><strong>Data Integrasi:</strong> Tepat Waktu = 20 Poin | Terlambat Hari-H = 10 Poin | Terlambat Lewat Hari = 5 Poin | Kosong = 0 Poin</li>
                            <li><strong>Data Bebas Harian:</strong> Tepat Waktu = 20 Poin | Terlambat Hari-H = 10 Poin | Terlambat Lewat Hari = 5 Poin | Kosong = 0 Poin</li>
                            <li><strong>Kroscek Tahanan Sinkron:</strong> Sesuai Langsung = <strong>+20 Poin</strong> | Sesuai Hasil Perbaikan di jam Input = <strong>+20 Poin</strong> | Sesuai Hasil Perbaikan diluar jam input dihari yang sama = <strong>+10 Poin</strong> | Masih Selisih / Kosong / diperbaiki lewat hari = <strong>0 Poin</strong>.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <h6 class="fw-bold text-success mb-2"><i class="fas fa-check-circle me-1"></i>Aturan Khusus Lapangan:</h6>
                        <ul class="mb-0 ps-3 text-secondary">
                            <li><strong>Input H-1 (Pre-Input):</strong> Pengisian sore hari H-1 untuk tanggal esok hari dihitung <strong>Tepat Waktu (20 Poin Penuh)</strong> karena data sudah siap sebelum deadline.</li>
                            <li><strong>Input Lebih Dari Jam Batas (Hari-H):</strong> Pengisian lewat pukul {{ $rankingData['period']['jam_selesai'] }} WIB pada hari yang sama (hari-H) mendapat <strong>10 Poin</strong> per modul.</li>
                            <li><strong>Input Terlambat Melewati Hari:</strong> Pengisian terlambat yang baru diinput setelah melewati hari-H (hari berikutnya / susulan) mendapat <strong>5 Poin</strong> per modul.</li>
                            <li><strong>Perbaikan Selisih Kroscek Tahanan:</strong> Jika data kroscek awalnya terdapat selisih lalu diperbaiki hingga sinkron di jam input (sebelum batas jam {{ $rankingData['period']['jam_selesai'] }} WIB), UPT memperoleh <strong>+20 Poin</strong> penuh. Jika diperbaiki di luar batas jam input namun masih di hari yang sama (hari-H), mendapat <strong>+10 Poin</strong>. Jika baru diperbaiki melewati hari-H (hari berikutnya / susulan) atau masih berselisih / kosong, mendapat <strong>0 Poin</strong>.</li>
                            <li><strong>Input Pagi lalu di-Edit Sore:</strong> Ketepatan waktu dinilai dari waktu pembuatan awal (<code>created_at</code>), sehingga data yang diedit tetap <strong>Tepat Waktu</strong>.</li>
                            <li><strong>Operasional 7 Hari Seminggu:</strong> Setiap hari tanpa hari libur dihitung wajib lapor sesuai standar operasional Lapas/Rutan Ditjenpas.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rincian Riwayat Harian UPT -->
<div class="modal fade" id="modalDetailHarian" tabindex="-1" aria-labelledby="modalDetailHarianLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="modalDetailHarianLabel">
                        <i class="fas fa-calendar-alt me-2"></i>Rincian Riwayat Harian UPT
                    </h5>
                    <small id="modalSubTitle" class="text-white-50"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Summary Mini Cards -->
                <div class="row g-2 mb-3 text-center" id="modalSummaryCards">
                    <!-- Populated by Javascript -->
                </div>

                <!-- Filter Buttons -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="btn-group btn-group-sm" role="group" id="filterButtonGroup">
                        <button type="button" class="btn btn-outline-primary active" id="btnFilterAll" onclick="filterDetailTable('all')">
                            Semua Hari (<span id="countAllDays">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="btnFilterSelisih" onclick="filterDetailTable('selisih')">
                            <i class="fas fa-exclamation-triangle me-1"></i>Hanya Yang Selisih (<span id="countSelisihDays">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-warning" id="btnFilterTelat" onclick="filterDetailTable('telat')">
                            <i class="fas fa-clock me-1"></i>Ada Terlambat/Kosong (<span id="countTelatDays">0</span>)
                        </button>
                    </div>
                    <small class="text-muted"><i class="fas fa-filter text-primary me-1"></i>Filter baris riwayat berdasarkan status</small>
                </div>

                <!-- Table Breakdown -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="13%">Tanggal & Hari</th>
                                <th width="14%">Penghuni (20)</th>
                                <th width="14%">Overstaying (20)</th>
                                <th width="14%">Integrasi (20)</th>
                                <th width="14%">Bebas Harian (20)</th>
                                <th width="18%">Kroscek Tahanan (20)</th>
                                <th width="13%">Total Poin</th>
                            </tr>
                        </thead>
                        <tbody id="modalBreakdownBody">
                            <!-- Populated by Javascript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Daftar Tanggal Selisih Kroscek Tahanan -->
<div class="modal fade" id="modalSelisihDates" tabindex="-1" aria-labelledby="modalSelisihDatesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="modalSelisihDatesLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Daftar Tanggal Selisih Kroscek Tahanan
                    </h5>
                    <small id="modalSelisihUptName" class="text-white-50"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 d-flex align-items-center gap-3 p-3 rounded-3 mb-3">
                    <i class="fas fa-info-circle text-danger fs-4 flex-shrink-0"></i>
                    <div class="small">
                        Berikut adalah tanggal di mana <strong>Jumlah Tahanan pada Data Penghuni</strong> tidak sesuai dengan <strong>Jumlah Tahanan pada Data Overstaying</strong>. Selisih data mengakibatkan nilai kroscek menjadi <strong>0 Poin</strong> pada tanggal bersangkutan. Klik tombol <strong>Periksa Data</strong> untuk langsung melihat tabel kroscek pada tanggal tersebut.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="6%">No</th>
                                <th width="26%" class="text-start">Tanggal & Hari</th>
                                <th width="22%">Tahanan (Penghuni)</th>
                                <th width="22%">Tahanan (Overstaying)</th>
                                <th width="12%">Selisih</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="modalSelisihTableBody">
                            <!-- Populated dynamically by Javascript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <small class="text-muted"><i class="fas fa-lightbulb text-warning me-1"></i>Kroscek sinkron langsung / perbaikan di jam input: <strong>+20 Poin</strong> | Perbaikan luar jam hari-H: <strong>+10 Poin</strong> | Lewat hari / selisih: <strong>0 Poin</strong>.</small>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentDetailRows = [];

    function showSelisihDatesModal(namaUpt, selisihDates) {
        const count = selisihDates ? selisihDates.length : 0;
        document.getElementById('modalSelisihUptName').innerHTML = `UPT: <strong>${namaUpt}</strong> &bull; Ditemukan <strong>${count} hari</strong> terdapat selisih`;
        const tbody = document.getElementById('modalSelisihTableBody');
        tbody.innerHTML = '';

        if (!selisihDates || selisihDates.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-muted py-4"><i class="fas fa-check-circle text-success fs-3 d-block mb-2"></i>Tidak ada tanggal yang berselisih. Semua data sinkron!</td></tr>';
        } else {
            selisihDates.forEach((item, index) => {
                const tr = document.createElement('tr');
                
                const dateParts = item.tanggal.split('-');
                const formattedDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}` : item.tanggal;
                const kroscekUrl = `{{ route('kroscek.index') }}?tanggal=${item.tanggal}`;

                tr.innerHTML = `
                    <td class="text-center fw-bold text-muted">${index + 1}</td>
                    <td class="text-start">
                        <strong class="d-block text-dark">${formattedDate}</strong>
                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>${item.hari || ''}</small>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fs-7">
                            ${Number(item.tahanan_penghuni || 0).toLocaleString()} Orang
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fs-7">
                            ${Number(item.tahanan_overstaying || 0).toLocaleString()} Orang
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-danger text-white px-2 py-1 fw-bold fs-7">
                            <i class="fas fa-exclamation-triangle me-1"></i>${Number(item.selisih || 0).toLocaleString()}
                        </span>
                    </td>
                    <td>
                        <a href="${kroscekUrl}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 shadow-sm" title="Buka Halaman Kroscek Tanggal Ini">
                            <i class="fas fa-external-link-alt me-1"></i>Periksa
                        </a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('modalSelisihDates'));
        modal.show();
    }

    function showDetailModal(upt) {
        document.getElementById('modalSubTitle').textContent = upt.nama_upt + ' (Total: ' + Number(upt.total_points).toLocaleString() + ' Poin | Kepatuhan: ' + upt.persentase_kepatuhan + '%)';

        // Summary Mini Cards
        const summaryHtml = `
            <div class="col-md-3 col-6">
                <div class="p-2 border rounded bg-light">
                    <small class="text-muted d-block" style="font-size: 11px;">Total Poin</small>
                    <strong class="text-primary fs-6">${Number(upt.total_points).toLocaleString()} / ${Number(upt.max_points).toLocaleString()}</strong>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-2 border rounded bg-light">
                    <small class="text-muted d-block" style="font-size: 11px;">Kepatuhan</small>
                    <strong class="text-success fs-6">${upt.persentase_kepatuhan}%</strong>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-2 border rounded bg-light">
                    <small class="text-muted d-block" style="font-size: 11px;">Hari 100 Poin</small>
                    <strong class="text-warning fs-6">${upt.count_hari_sempurna} Hari Sempurna</strong>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-2 border rounded bg-light ${upt.count_kroscek_selisih > 0 ? 'border-danger' : ''}">
                    <small class="text-muted d-block" style="font-size: 11px;">Kroscek Tahanan</small>
                    <strong class="text-success fs-6">${upt.count_kroscek_sesuai}x Sesuai</strong>
                    ${(upt.count_kroscek_perbaikan || 0) > 0 ? `<span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1" title="${upt.count_kroscek_perbaikan}x hasil edit/perbaikan">${upt.count_kroscek_perbaikan}x Edit</span>` : ''}
                    ${upt.count_kroscek_selisih > 0 ? `<span class="badge bg-danger ms-1">${upt.count_kroscek_selisih}x Selisih</span>` : ''}
                </div>
            </div>
        `;
        document.getElementById('modalSummaryCards').innerHTML = summaryHtml;

        currentDetailRows = upt.daily_breakdown || [];

        // Count stats for filter buttons
        const totalCount = currentDetailRows.length;
        const selisihCount = currentDetailRows.filter(r => r.kroscek && r.kroscek.status === 'selisih').length;
        const telatCount = currentDetailRows.filter(r => {
            return (r.penghuni && r.penghuni.status !== 'tepat') ||
                   (r.overstaying && r.overstaying.status !== 'tepat') ||
                   (r.integrasi && r.integrasi.status !== 'tepat') ||
                   (r.bebas && r.bebas.status !== 'tepat') ||
                   (r.kroscek && r.kroscek.status !== 'sesuai');
        }).length;

        document.getElementById('countAllDays').textContent = totalCount;
        document.getElementById('countSelisihDays').textContent = selisihCount;
        document.getElementById('countTelatDays').textContent = telatCount;

        // Reset filter to 'all'
        filterDetailTable('all');

        const modal = new bootstrap.Modal(document.getElementById('modalDetailHarian'));
        modal.show();
    }

    function filterDetailTable(type) {
        // Update active class on buttons
        document.getElementById('btnFilterAll').classList.toggle('active', type === 'all');
        document.getElementById('btnFilterSelisih').classList.toggle('active', type === 'selisih');
        document.getElementById('btnFilterTelat').classList.toggle('active', type === 'telat');

        let filtered = currentDetailRows;
        if (type === 'selisih') {
            filtered = currentDetailRows.filter(r => r.kroscek && r.kroscek.status === 'selisih');
        } else if (type === 'telat') {
            filtered = currentDetailRows.filter(r => {
                return (r.penghuni && r.penghuni.status !== 'tepat') ||
                       (r.overstaying && r.overstaying.status !== 'tepat') ||
                       (r.integrasi && r.integrasi.status !== 'tepat') ||
                       (r.bebas && r.bebas.status !== 'tepat') ||
                       (r.kroscek && r.kroscek.status !== 'sesuai');
            });
        }

        renderDetailTableRows(filtered);
    }

    function renderDetailTableRows(rows) {
        const tbody = document.getElementById('modalBreakdownBody');
        tbody.innerHTML = '';

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-muted py-4"><i class="fas fa-info-circle me-1"></i>Tidak ada data riwayat untuk kriteria filter ini.</td></tr>';
            return;
        }

        rows.forEach(row => {
            const tr = document.createElement('tr');

            // Render Badge helper
            const renderModulCell = (mod) => {
                if (!mod) {
                    return `<span class="badge bg-secondary-subtle text-muted d-block py-1 mb-1">-</span>`;
                }
                if (mod.status === 'tepat') {
                    return `<span class="badge bg-success-subtle text-success d-block py-1 mb-1"><i class="fas fa-check-circle me-1"></i>Tepat (${mod.poin})</span><small class="text-muted" style="font-size:10px;">${mod.keterangan}</small>`;
                } else if (mod.status === 'terlambat') {
                    const isSusulan = mod.poin === 5;
                    const badgeLabel = isSusulan ? 'Susulan' : 'Terlambat';
                    return `<span class="badge bg-warning-subtle text-warning d-block py-1 mb-1"><i class="fas fa-clock me-1"></i>${badgeLabel} (${mod.poin})</span><small class="text-muted" style="font-size:10px;">${mod.keterangan}</small>`;
                } else {
                    return `<span class="badge bg-danger-subtle text-danger d-block py-1 mb-1"><i class="fas fa-times-circle me-1"></i>Kosong (0)</span><small class="text-muted" style="font-size:10px;">-</small>`;
                }
            };

            // Kroscek Cell
            let kroscekHtml = '';
            const kroscekUrl = `{{ route('kroscek.index') }}?tanggal=${row.tanggal}`;
            if (row.kroscek.status === 'sesuai') {
                kroscekHtml = `<span class="badge bg-success-subtle text-success d-block py-1 mb-1"><i class="fas fa-check-double me-1"></i>Sesuai Langsung (+20)</span><small class="text-muted" style="font-size:10px;">Tahanan: ${row.kroscek.tahanan_penghuni ?? 0} = ${row.kroscek.tahanan_overstaying ?? 0}</small>`;
            } else if (row.kroscek.status === 'sesuai_perbaikan_jam_input') {
                const editInfo = row.kroscek.edit_time ? ` (${row.kroscek.edit_time})` : '';
                kroscekHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle d-block py-1 mb-1"><i class="fas fa-wrench me-1"></i>Perbaikan Jam Input (+20)</span><small class="text-muted" style="font-size:10px;">Sinkron (${row.kroscek.tahanan_penghuni ?? 0})${editInfo}</small>`;
            } else if (row.kroscek.status === 'sesuai_perbaikan_luar_jam' || row.kroscek.status === 'sesuai_perbaikan') {
                const editInfo = row.kroscek.edit_time ? ` (${row.kroscek.edit_time})` : '';
                kroscekHtml = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle d-block py-1 mb-1"><i class="fas fa-wrench text-warning me-1"></i>Perbaikan Luar Jam (+10)</span><small class="text-muted" style="font-size:10px;">Sinkron (${row.kroscek.tahanan_penghuni ?? 0})${editInfo}</small>`;
            } else if (row.kroscek.status === 'sesuai_perbaikan_lewat_hari') {
                const editInfo = row.kroscek.edit_time ? ` (${row.kroscek.edit_time})` : '';
                kroscekHtml = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle d-block py-1 mb-1"><i class="fas fa-history me-1"></i>Perbaikan Lewat Hari (0)</span><small class="text-muted" style="font-size:10px;">Sinkron Susulan${editInfo}</small>`;
            } else if (row.kroscek.status === 'selisih') {
                kroscekHtml = `
                    <span class="badge bg-danger-subtle text-danger d-block py-1 mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Selisih (0)</span>
                    <small class="text-danger fw-semibold d-block" style="font-size:10px;">Selisih: ${row.kroscek.selisih} (P:${row.kroscek.tahanan_penghuni ?? 0} vs O:${row.kroscek.tahanan_overstaying ?? 0})</small>
                    <a href="${kroscekUrl}" target="_blank" class="btn btn-xs btn-outline-danger py-0 px-2 rounded-pill mt-1 d-inline-flex align-items-center gap-1" style="font-size: 10px;">
                        <i class="fas fa-external-link-alt"></i>Periksa Kroscek
                    </a>
                `;
            } else {
                kroscekHtml = `<span class="badge bg-secondary-subtle text-secondary d-block py-1 mb-1">Belum Lengkap (0)</span><small class="text-muted" style="font-size:10px;">Data belum terisi</small>`;
            }

            const totalBadgeColor = row.total_poin === 100 ? 'bg-success' : (row.total_poin >= 60 ? 'bg-primary' : (row.total_poin > 0 ? 'bg-warning text-dark' : 'bg-danger'));

            tr.innerHTML = `
                <td class="text-start">
                    <strong class="d-block">${row.tanggal}</strong>
                    <small class="text-muted">${row.hari}</small>
                </td>
                <td>${renderModulCell(row.penghuni)}</td>
                <td>${renderModulCell(row.overstaying)}</td>
                <td>${renderModulCell(row.integrasi)}</td>
                <td>${renderModulCell(row.bebas)}</td>
                <td>${kroscekHtml}</td>
                <td>
                    <span class="badge ${totalBadgeColor} fs-7 px-3 py-1 fw-bold">${row.total_poin} Poin</span>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    // Fitur Pencarian Realtime & Switcher Format Tampilan untuk Klasemen UPT
    const searchLeaderboardInput = document.getElementById('searchLeaderboard');
    const leaderboardTableRows = document.querySelectorAll('#rankingTableWrapper tbody tr[data-upt]');
    const leaderboardCardCols = document.querySelectorAll('#rankingCardsContainer .ranking-card-col[data-upt]');
    const btnShowRankingTable = document.getElementById('btnShowRankingTable');
    const btnShowRankingCards = document.getElementById('btnShowRankingCards');
    const rankingTableWrapper = document.getElementById('rankingTableWrapper');
    const rankingScrollHint = document.getElementById('rankingScrollHint');
    const rankingCardsContainer = document.getElementById('rankingCardsContainer');

    function applyLeaderboardFilter() {
        const query = searchLeaderboardInput ? searchLeaderboardInput.value.toLowerCase().trim() : '';

        const filterItem = (el) => {
            const uptName = el.getAttribute('data-upt') || '';
            if (!query || uptName.includes(query)) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        };

        leaderboardTableRows.forEach(filterItem);
        leaderboardCardCols.forEach(filterItem);
    }

    searchLeaderboardInput?.addEventListener('input', applyLeaderboardFilter);

    // Switcher Tampilan (PC & Mobile): Tabel vs Kartu Grid dengan Auto-save LocalStorage
    const LEADERBOARD_VIEW_KEY = 'sultan_leaderboard_view_mode';

    function setLeaderboardViewMode(mode, save = true) {
        if (mode === 'cards') {
            btnShowRankingCards?.classList.remove('btn-outline-secondary');
            btnShowRankingCards?.classList.add('btn-primary', 'active');
            btnShowRankingTable?.classList.remove('btn-primary', 'active');
            btnShowRankingTable?.classList.add('btn-outline-secondary');

            rankingTableWrapper?.classList.add('d-none');
            rankingScrollHint?.classList.add('d-none');
            rankingCardsContainer?.classList.remove('d-none');
            if (save) {
                try { localStorage.setItem(LEADERBOARD_VIEW_KEY, 'cards'); } catch(e) {}
            }
        } else {
            btnShowRankingTable?.classList.remove('btn-outline-secondary');
            btnShowRankingTable?.classList.add('btn-primary', 'active');
            btnShowRankingCards?.classList.remove('btn-primary', 'active');
            btnShowRankingCards?.classList.add('btn-outline-secondary');

            rankingTableWrapper?.classList.remove('d-none');
            rankingScrollHint?.classList.remove('d-none');
            rankingCardsContainer?.classList.add('d-none');
            if (save) {
                try { localStorage.setItem(LEADERBOARD_VIEW_KEY, 'table'); } catch(e) {}
            }
        }
    }
    window.setLeaderboardViewMode = setLeaderboardViewMode;

    btnShowRankingTable?.addEventListener('click', () => setLeaderboardViewMode('table'));
    btnShowRankingCards?.addEventListener('click', () => setLeaderboardViewMode('cards'));

    // Muat preferensi tampilan tersimpan dari localStorage saat halaman dimuat
    try {
        const savedViewMode = localStorage.getItem(LEADERBOARD_VIEW_KEY);
        if (savedViewMode === 'cards') {
            setLeaderboardViewMode('cards', false);
        } else {
            setLeaderboardViewMode('table', false);
        }
    } catch(e) {
        setLeaderboardViewMode('table', false);
    }
</script>
@endpush
