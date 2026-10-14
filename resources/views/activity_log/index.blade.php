@extends('layouts.app')

@section('title', 'Activity Log - Sultan Banten')
@section('page_title')
    <i class="fas fa-history me-2 text-primary"></i>Activity Log
@endsection

@push('styles')
<style>
    /* Modern Stat Card Styles */
    .stat-card-advanced {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }
    .stat-card-advanced:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.08);
        border-color: var(--primary-400, #818cf8);
    }
    .stat-card-advanced::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }
    .stat-card-advanced.accent-indigo::after { background: linear-gradient(90deg, #4f46e5, #818cf8); }
    .stat-card-advanced.accent-emerald::after { background: linear-gradient(90deg, #059669, #34d399); }
    .stat-card-advanced.accent-sky::after { background: linear-gradient(90deg, #0284c7, #38bdf8); }
    .stat-card-advanced.accent-amber::after { background: linear-gradient(90deg, #d97706, #fbbf24); }

    .stat-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .stat-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
    }
    .stat-card-icon.icon-indigo { background: linear-gradient(135deg, #4f46e5, #6366f1); }
    .stat-card-icon.icon-emerald { background: linear-gradient(135deg, #059669, #10b981); }
    .stat-card-icon.icon-sky { background: linear-gradient(135deg, #0284c7, #06b6d4); }
    .stat-card-icon.icon-amber { background: linear-gradient(135deg, #d97706, #f59e0b); }

    .stat-card-label {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary, #64748b);
        margin-bottom: 4px;
    }
    .stat-card-value {
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        color: var(--text-primary, #0f172a);
        letter-spacing: -0.5px;
    }
    .stat-card-footer {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed var(--border-color, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: var(--text-secondary, #64748b);
    }

    /* Distribution Progress Bars */
    .distribution-item {
        padding: 9px 12px;
        border-radius: 10px;
        transition: background 0.15s ease;
        text-decoration: none;
        color: inherit;
        display: block;
        margin-bottom: 6px;
        background: var(--bg-hover, rgba(0,0,0,0.015));
        border: 1px solid var(--border-color, #f1f5f9);
    }
    .distribution-item:hover {
        background: var(--bg-card-hover, rgba(99, 102, 241, 0.05));
        border-color: var(--primary-300, #a5b4fc);
        transform: translateX(3px);
    }
    .distribution-progress {
        height: 6px;
        border-radius: 999px;
        background: var(--gray-200, #e2e8f0);
        overflow: hidden;
        margin-top: 6px;
    }
    .distribution-progress-bar {
        height: 100%;
        border-radius: 999px;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Leaderboard Card */
    .leaderboard-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color, #f1f5f9);
        margin-bottom: 8px;
        background: var(--bg-hover, rgba(0,0,0,0.015));
        transition: all 0.2s ease;
    }
    .leaderboard-item:hover {
        background: var(--bg-card-hover, rgba(99, 102, 241, 0.05));
        border-color: var(--primary-300, #a5b4fc);
    }
    .rank-badge {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .rank-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .rank-2 { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .rank-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
    .rank-other { background: transparent; color: var(--text-secondary, #64748b); }

    .user-avatar-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        flex-shrink: 0;
        text-transform: uppercase;
        box-shadow: 0 2px 5px rgba(99, 102, 241, 0.25);
    }

    /* Filter Panel */
    .filter-panel {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }

    /* Soft Action Badges */
    .badge-soft {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 9px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-soft-primary { background: rgba(79, 70, 229, 0.1); color: #4f46e5; border: 1px solid rgba(79, 70, 229, 0.2); }
    .badge-soft-success { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
    .badge-soft-warning { background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25); }
    .badge-soft-danger  { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }
    .badge-soft-info    { background: rgba(6, 182, 212, 0.1); color: #0891b2; border: 1px solid rgba(6, 182, 212, 0.2); }
    .badge-soft-secondary { background: rgba(100, 116, 139, 0.1); color: #475569; border: 1px solid rgba(100, 116, 139, 0.2); }

    /* Detail Modal & Code Block */
    .detail-json-card {
        background: #1e1e2e;
        color: #cdd6f4;
        border-radius: 8px;
        padding: 14px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        line-height: 1.5;
        max-height: 280px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-all;
        border: 1px solid #313244;
    }

    /* Active filter chips */
    .active-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--primary-50, #eef2ff);
        color: var(--primary-700, #4338ca);
        border: 1px solid var(--primary-200, #c7d2fe);
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500;
    }
    .active-chip a {
        color: inherit;
        text-decoration: none;
        opacity: 0.7;
    }
    .active-chip a:hover {
        opacity: 1;
    }

    /* Dark Mode Adjustments */
    [data-theme="dark"] .stat-card-advanced {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .stat-card-advanced:hover {
        border-color: #6366f1;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    }
    [data-theme="dark"] .stat-card-value {
        color: #f8fafc;
    }
    [data-theme="dark"] .stat-card-label {
        color: #94a3b8;
    }
    [data-theme="dark"] .stat-card-footer {
        border-color: #334155;
        color: #94a3b8;
    }
    [data-theme="dark"] .distribution-item,
    [data-theme="dark"] .leaderboard-item {
        background: rgba(30, 41, 59, 0.5);
        border-color: #334155;
    }
    [data-theme="dark"] .distribution-item:hover,
    [data-theme="dark"] .leaderboard-item:hover {
        background: rgba(99, 102, 241, 0.15);
        border-color: #6366f1;
    }
    [data-theme="dark"] .distribution-progress {
        background: #334155;
    }
    [data-theme="dark"] .rank-1 { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
    [data-theme="dark"] .rank-2 { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; border-color: rgba(148, 163, 184, 0.3); }
    [data-theme="dark"] .rank-3 { background: rgba(234, 88, 12, 0.2); color: #fdba74; border-color: rgba(234, 88, 12, 0.3); }
    [data-theme="dark"] .filter-panel {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .active-chip {
        background: rgba(99, 102, 241, 0.2);
        color: #c7d2fe;
        border-color: rgba(99, 102, 241, 0.35);
    }
</style>
@endpush

@section('content')
<!-- Top Tab Navigation -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 pb-2 border-bottom">
    <ul class="nav nav-pills gap-2 p-1 bg-light rounded-pill border">
        <li class="nav-item">
            <a class="nav-link active rounded-pill px-4 py-2 fw-semibold" href="{{ route('activity-log.index') }}">
                <i class="fas fa-list-alt me-2"></i>Riwayat Log Sistem
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold text-secondary" href="{{ route('activity-log.ranking') }}">
                <i class="fas fa-trophy me-2 text-warning"></i>Leaderboard & Kepatuhan UPT
            </a>
        </li>
    </ul>

    <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small">
            <i class="fas fa-history me-1"></i> Audit Trail Sistem
        </span>
    </div>
</div>

<!-- Section 1: Executive KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Aktivitas -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-advanced accent-indigo">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Total Aktivitas</div>
                    <div class="stat-card-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="stat-card-icon icon-indigo">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span><i class="fas fa-shield-alt text-primary me-1"></i>Audit trail sistem</span>
                <span class="badge bg-primary-subtle text-primary fw-semibold">All Time</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Aktivitas Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-advanced accent-emerald">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Aktivitas Hari Ini</div>
                    <div class="stat-card-value text-success">{{ number_format($stats['today']) }}</div>
                </div>
                <div class="stat-card-icon icon-emerald">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span><i class="fas fa-clock text-success me-1"></i>{{ now()->translatedFormat('d M Y') }}</span>
                <span class="badge bg-success-subtle text-success fw-semibold">Hari Ini</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Aktivitas Minggu Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-advanced accent-sky">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Aktivitas Minggu Ini</div>
                    <div class="stat-card-value text-info">{{ number_format($stats['this_week']) }}</div>
                </div>
                <div class="stat-card-icon icon-sky">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span><i class="fas fa-calendar-week text-info me-1"></i>Senin s/d hari ini</span>
                <span class="badge bg-info-subtle text-info fw-semibold">Pekan Ini</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Pengguna Aktif -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-advanced accent-amber">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Pengguna Terlibat</div>
                    <div class="stat-card-value text-warning">{{ number_format($stats['total_users_active'] ?? count($stats['top_users'])) }}</div>
                </div>
                <div class="stat-card-icon icon-amber">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="text-truncate" style="max-width: 170px;" title="{{ $stats['top_user']['user_name'] ?? 'Belum ada data' }}">
                    <i class="fas fa-star text-warning me-1"></i>Top: {{ $stats['top_user']['user_name'] ?? '-' }}
                </span>
                <span class="badge bg-warning-subtle text-warning fw-semibold">{{ number_format($stats['top_user']['total'] ?? 0) }} aksi</span>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Proportional Analytical Breakdown (7 : 5 Columns) -->
<div class="row g-3 mb-4">
    <!-- Kolom Kiri: Distribusi Aktivitas per Aksi -->
    <div class="col-lg-7">
        <div class="content-card h-100 d-flex flex-column justify-content-between p-4">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-1 fw-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>Distribusi Aktivitas Sistem
                        </h6>
                        <span class="text-muted small">Proporsi aksi terhadap total seluruh riwayat</span>
                    </div>
                    <span class="badge bg-light text-dark border px-2 py-1 small">
                        {{ count($stats['by_action']) }} Kategori Aksi
                    </span>
                </div>

                <div class="row g-2 mt-2">
                    @forelse ($stats['by_action'] as $item)
                        @php
                            $label = $aksiLabels[$item['aksi']] ?? ['label' => ucfirst($item['aksi']), 'icon' => 'fa-circle', 'color' => 'secondary'];
                            $colorMap = [
                                'success' => '#10b981',
                                'primary' => '#4f46e5',
                                'warning' => '#f59e0b',
                                'danger' => '#ef4444',
                                'secondary' => '#64748b',
                                'info' => '#06b6d4',
                            ];
                            $hexColor = $colorMap[$label['color']] ?? '#4f46e5';
                            $pct = $item['percentage'] ?? round(($item['total'] / max(1, $stats['total'])) * 100, 1);
                        @endphp
                        <div class="col-md-6">
                            <a href="{{ route('activity-log.index', array_merge(request()->query(), ['aksi' => $item['aksi']])) }}" 
                               class="distribution-item {{ $filterAksi === $item['aksi'] ? 'border-primary' : '' }}" 
                               title="Klik untuk filter aksi: {{ $label['label'] }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge-soft badge-soft-{{ $label['color'] }}">
                                            <i class="fas {{ $label['icon'] }}"></i> {{ $label['label'] }}
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold fs-7">{{ number_format($item['total']) }}</span>
                                        <span class="text-muted small ms-1">({{ $pct }}%)</span>
                                    </div>
                                </div>
                                <div class="distribution-progress">
                                    <div class="distribution-progress-bar" style="width: {{ $pct }}%; background-color: {{ $hexColor }};"></div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">Belum ada statistik aksi tercatat.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                <span><i class="fas fa-info-circle me-1"></i>Klik pada salah satu aksi di atas untuk menyaring data riwayat.</span>
                @if($filterAksi)
                    <a href="{{ route('activity-log.index', array_merge(request()->except('aksi'))) }}" class="text-danger fw-semibold text-decoration-none">
                        <i class="fas fa-times-circle me-1"></i>Hapus Filter Aksi
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Top 5 Pengguna Paling Aktif -->
    <div class="col-lg-5">
        <div class="content-card h-100 d-flex flex-column justify-content-between p-4">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-1 fw-bold text-primary">
                            <i class="fas fa-trophy me-2 text-warning"></i>Top 5 Pengguna Paling Aktif
                        </h6>
                        <span class="text-muted small">Peringkat kontributor aktivitas terbanyak</span>
                    </div>
                    <span class="badge bg-warning-subtle text-warning border px-2 py-1 small">
                        <i class="fas fa-star me-1"></i>Leaderboard
                    </span>
                </div>

                <div class="mt-2">
                    @forelse ($stats['top_users'] as $index => $u)
                        @php
                            $rankClass = match($index) {
                                0 => 'rank-1',
                                1 => 'rank-2',
                                2 => 'rank-3',
                                default => 'rank-other',
                            };
                            $medalIcon = match($index) {
                                0 => '🥇',
                                1 => '🥈',
                                2 => '🥉',
                                default => '#' . ($index + 1),
                            };
                            $nameParts = explode(' ', trim($u['user_name']));
                            $initials = count($nameParts) >= 2 
                                ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                                : strtoupper(substr($u['user_name'], 0, 2));
                            $pct = $u['percentage'] ?? round(($u['total'] / max(1, $stats['total'])) * 100, 1);
                        @endphp
                        <div class="leaderboard-item">
                            <div class="rank-badge {{ $rankClass }}">{{ $medalIcon }}</div>
                            <div class="user-avatar-circle">{{ $initials }}</div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-truncate" title="{{ $u['user_name'] }}">{{ $u['user_name'] }}</span>
                                    <span class="badge bg-light text-dark border fw-bold">{{ number_format($u['total']) }} aksi</span>
                                </div>
                                <div class="distribution-progress mt-1">
                                    <div class="distribution-progress-bar bg-primary" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Belum ada riwayat user tercatat.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-3 pt-2 border-top small text-muted d-flex justify-content-between">
                <span>Kontribusi aktivitas sistem</span>
                <span class="fw-semibold text-primary">{{ number_format(collect($stats['top_users'])->sum('total')) }} total aksi</span>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Filter & Logs Table Unified Card -->
<div class="filter-panel mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary-subtle text-primary rounded-3">
                <i class="fas fa-sliders-h"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold">Pencarian & Penyaringan Log</h6>
                <span class="text-muted small">Saring log berdasarkan aksi, modul, pengguna, atau rentang tanggal</span>
            </div>
        </div>

        @if($filterAksi || $filterModul || $filterUserId || $filterTanggalMulai || $filterTanggalAkhir || $filterSearch)
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <span class="small text-muted me-1">Filter Aktif:</span>
                @if($filterSearch)
                    <span class="active-chip">Cari: "{{ $filterSearch }}" <a href="{{ route('activity-log.index', request()->except('search')) }}">×</a></span>
                @endif
                @if($filterAksi)
                    <span class="active-chip">Aksi: {{ $aksiLabels[$filterAksi]['label'] ?? $filterAksi }} <a href="{{ route('activity-log.index', request()->except('aksi')) }}">×</a></span>
                @endif
                @if($filterModul)
                    <span class="active-chip">Modul: {{ $modulLabels[$filterModul] ?? $filterModul }} <a href="{{ route('activity-log.index', request()->except('modul')) }}">×</a></span>
                @endif
                @if($filterUserId)
                    <span class="active-chip">User ID: {{ $filterUserId }} <a href="{{ route('activity-log.index', request()->except('user_id')) }}">×</a></span>
                @endif
                @if($filterTanggalMulai || $filterTanggalAkhir)
                    <span class="active-chip">Tgl: {{ $filterTanggalMulai ?: '...' }} s/d {{ $filterTanggalAkhir ?: '...' }} <a href="{{ route('activity-log.index', request()->except(['tanggal_mulai', 'tanggal_akhir'])) }}">×</a></span>
                @endif
                <a href="{{ route('activity-log.index') }}" class="btn btn-outline-danger btn-sm py-1 px-2 ms-2 rounded-pill small">
                    <i class="fas fa-rotate-left me-1"></i>Reset
                </a>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('activity-log.index') }}">
        <div class="row g-2 align-items-end">
            <!-- Search Text -->
            <div class="col-12 col-md-3">
                <label for="filter_search_log" class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-search me-1"></i>Kata Kunci</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="filter_search_log" name="search" class="form-control border-start-0" placeholder="Cari nama atau deskripsi..." value="{{ $filterSearch }}" aria-label="Kata Kunci Pencarian">
                </div>
            </div>

            <!-- Filter Aksi -->
            <div class="col-6 col-md-2">
                <label for="filter_aksi_log" class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-bolt me-1"></i>Aksi</label>
                <select id="filter_aksi_log" name="aksi" class="form-select form-select-sm" aria-label="Filter Aksi">
                    <option value="">Semua Aksi</option>
                    @foreach ($aksiLabels as $key => $label)
                        <option value="{{ $key }}" {{ $filterAksi == $key ? 'selected' : '' }}>{{ $label['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Modul -->
            <div class="col-6 col-md-2">
                <label for="filter_modul_log" class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-cubes me-1"></i>Modul</label>
                <select id="filter_modul_log" name="modul" class="form-select form-select-sm" aria-label="Filter Modul">
                    <option value="">Semua Modul</option>
                    @foreach ($modulLabels as $key => $label)
                        <option value="{{ $key }}" {{ $filterModul == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter User -->
            <div class="col-6 col-md-2">
                <label for="filter_user_log" class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-user me-1"></i>Pengguna</label>
                <select id="filter_user_log" name="user_id" class="form-select form-select-sm" aria-label="Filter Pengguna">
                    <option value="">Semua User</option>
                    @foreach ($allUsers as $u)
                        <option value="{{ $u->id }}" {{ $filterUserId == $u->id ? 'selected' : '' }}>{{ $u->nama_lengkap ?? $u->username }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Rentang Tanggal -->
            <div class="col-6 col-md-3">
                <label for="filter_tanggal_mulai_log" class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Rentang Tanggal</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filter_tanggal_mulai_log" name="tanggal_mulai" class="form-control" value="{{ $filterTanggalMulai }}" title="Tanggal Mulai" aria-label="Tanggal Mulai">
                    <span class="input-group-text bg-light border-start-0 border-end-0">-</span>
                    <input type="date" id="filter_tanggal_akhir_log" name="tanggal_akhir" class="form-control" value="{{ $filterTanggalAkhir }}" title="Tanggal Akhir" aria-label="Tanggal Akhir">
                    <button type="submit" class="btn btn-primary" title="Terapkan Filter" aria-label="Terapkan Filter">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Section 4: Data Table -->
<div class="content-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>Daftar Riwayat Aktivitas</h6>
            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">
                Total {{ number_format($logs->total()) }} catatan
            </span>
        </div>
        <div class="text-muted small">
            Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ number_format($logs->total()) }} log
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th width="4%" class="text-center">No</th>
                    <th width="14%">Waktu & Tanggal</th>
                    <th width="16%">Pengguna</th>
                    <th width="11%">Aksi</th>
                    <th width="12%">Modul</th>
                    <th>Deskripsi</th>
                    <th width="12%">IP Address</th>
                    <th width="6%" class="text-center">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $i => $log)
                @php
                    $label = $aksiLabels[$log->aksi] ?? ['label' => ucfirst($log->aksi), 'icon' => 'fa-circle', 'color' => 'secondary'];
                    $modulName = $modulLabels[$log->modul] ?? ucfirst($log->modul);
                    $userName = $log->user_name ?? ($log->user?->nama_lengkap ?? 'Sistem');
                    $parts = explode(' ', trim($userName));
                    $initial = count($parts) >= 2 
                        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                        : strtoupper(substr($userName, 0, 2));
                @endphp
                <tr>
                    <td class="text-center text-muted fw-semibold">{{ $logs->firstItem() + $i }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="far fa-clock text-muted"></i>
                            <div>
                                <div class="fw-semibold text-dark">{{ $log->created_at ? $log->created_at->format('d/m/Y') : '-' }}</div>
                                <div class="text-muted small" style="font-size: 11px;">{{ $log->created_at ? $log->created_at->format('H:i:s') . ' WIB' : '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar-circle" style="width: 30px; height: 30px; font-size: 11px;">{{ $initial }}</div>
                            <span class="fw-semibold text-dark text-truncate" style="max-width: 150px;" title="{{ $userName }}">
                                {{ $userName }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge-soft badge-soft-{{ $label['color'] }}">
                            <i class="fas {{ $label['icon'] }}"></i> {{ $label['label'] }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="fas fa-folder-open me-1 text-muted" style="font-size: 10px;"></i>{{ $modulName }}
                        </span>
                    </td>
                    <td>
                        <span class="text-dark">{{ $log->deskripsi }}</span>
                    </td>
                    <td>
                        <span class="badge bg-light text-muted border font-monospace px-2 py-1" style="font-size: 11px;">
                            <i class="fas fa-network-wired me-1"></i>{{ $log->ip_address ?? '-' }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if(!empty($log->detail))
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $log->id }}" title="Lihat detail payload" aria-label="Lihat detail payload log #{{ $log->id }}">
                            <i class="fas fa-eye"></i>
                        </button>
                        
                        <!-- Modal Detail -->
                        <div class="modal fade" id="modalDetail{{ $log->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header py-3 bg-light">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="p-2 bg-primary text-white rounded-3">
                                                <i class="fas fa-file-code"></i>
                                            </div>
                                            <div>
                                                <h6 class="modal-title fs-6 fw-bold mb-0">Detail Payload Log #{{ $log->id }}</h6>
                                                <span class="text-muted small">{{ $userName }} &bull; {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        <!-- Quick Meta Grid -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-sm-6">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="text-muted small" style="font-size: 11px;">MODUL / AKSI</div>
                                                    <div class="fw-semibold text-dark">{{ $modulName }} &bull; {{ $label['label'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="text-muted small" style="font-size: 11px;">IP ADDRESS</div>
                                                    <div class="fw-semibold text-dark font-monospace">{{ $log->ip_address ?? '-' }}</div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="text-muted small" style="font-size: 11px;">DESKRIPSI AKTIVITAS</div>
                                                    <div class="text-dark">{{ $log->deskripsi }}</div>
                                                </div>
                                            </div>
                                            @if($log->user_agent)
                                            <div class="col-12">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="text-muted small" style="font-size: 11px;">USER AGENT</div>
                                                    <div class="text-muted small font-monospace text-truncate" title="{{ $log->user_agent }}">{{ $log->user_agent }}</div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold small"><i class="fas fa-code me-1 text-primary"></i>Data Payload (JSON)</span>
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="navigator.clipboard.writeText(document.getElementById('rawJson{{ $log->id }}').innerText); this.innerHTML='<i class=\'fas fa-check text-success me-1\'></i>Tersalin!'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy me-1\'></i>Salin JSON', 2000);">
                                                <i class="fas fa-copy me-1"></i>Salin JSON
                                            </button>
                                        </div>
                                        <div id="rawJson{{ $log->id }}" class="detail-json-card">{{ is_array($log->detail) ? json_encode($log->detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $log->detail }}</div>
                                    </div>
                                    <div class="modal-footer py-2 bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-muted small">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <div class="p-4">
                            <i class="fas fa-inbox fa-3x text-muted opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-semibold mb-1">Tidak Ada Catatan Log Aktivitas</h6>
                            <p class="text-muted small mb-0">Coba ubah kata kunci pencarian atau sesuaikan filter Anda.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">
            Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }}
        </div>
        <div>
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
