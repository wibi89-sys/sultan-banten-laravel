@extends('layouts.app')

@section('title', 'Dashboard - Sultan Banten')
@section('page_title', 'Dashboard')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Dashboard Modern Styles -->
<link href="{{ asset('assets/css/dashboard-page.css') }}" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('content')
<div class="content-wrapper">

    <!-- Executive Glass Filter Bar -->
    <div class="exec-filter-bar mb-3">
        <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-auto d-flex align-items-center gap-1 mb-2 mb-md-0">
                <span class="text-secondary small fw-bold me-1"><i class="fas fa-calendar-alt text-primary me-1"></i>Preset:</span>
                <button type="button" class="date-quick-btn {{ $filterTanggal == date('Y-m-d') ? 'active' : '' }}" data-days="0">Hari Ini</button>
                <button type="button" class="date-quick-btn {{ $filterTanggal == date('Y-m-d', strtotime('-1 day')) ? 'active' : '' }}" data-days="1">Kemarin</button>
                <button type="button" class="date-quick-btn {{ $filterTanggal == date('Y-m-d', strtotime('-7 days')) ? 'active' : '' }}" data-days="7">7 Hari Lalu</button>
            </div>
            <div class="col-6 col-md-3">
                <input type="date" id="filterTanggalInput" name="tanggal" class="form-control form-control-sm" value="{{ $filterTanggal }}">
            </div>
            <div class="col-6 col-md-4">
                <select name="upt_id" class="form-select form-select-sm">
                    <option value="0">-- Seluruh UPT Banten --</option>
                    @foreach ($uptList as $upt)
                    <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm" style="border-radius: 20px; font-weight: 600;">
                    <i class="fas fa-filter me-1"></i> Terapkan
                </button>
                @if($filterUpt > 0 || $filterTanggal != date('Y-m-d'))
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px;" title="Reset Filter">
                    <i class="fas fa-redo"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Executive Sticky Tabs Nav -->
    <div class="exec-tabs-wrapper">
        <button type="button" class="exec-tab-btn active" data-tab="executive">
            <i class="fas fa-chart-line"></i> Ringkasan Eksekutif
            <span class="tab-badge">Hero</span>
        </button>
        <button type="button" class="exec-tab-btn" data-tab="gis">
            <i class="fas fa-map-marked-alt"></i> Peta Sebaran UPT
            <span class="tab-badge">{{ $stats['total_upt'] }} UPT</span>
        </button>
        <button type="button" class="exec-tab-btn" data-tab="klasifikasi">
            <i class="fas fa-gavel"></i> Pidum & Pidsus
            <span class="tab-badge">{{ number_format($stats['total_penghuni']) }}</span>
        </button>
        <button type="button" class="exec-tab-btn" data-tab="bebas">
            <i class="fas fa-door-open"></i> Bebas Harian
            <span class="tab-badge">{{ $stats['total_bebas'] }}</span>
        </button>
        <button type="button" class="exec-tab-btn" data-tab="overstaying">
            <i class="fas fa-clock text-danger"></i> Overstaying
            <span class="tab-badge text-danger fw-bold">{{ $stats['total_overstaying'] }}</span>
        </button>
        <button type="button" class="exec-tab-btn ms-auto" data-tab="all" title="Lihat seluruh bagian secara bersamaan">
            <i class="fas fa-th-large"></i> Semua Tampilan
        </button>
    </div>

    <!-- ======================================================== -->
    <!-- TAB SECTION 1: RINGKASAN EKSEKUTIF                       -->
    <!-- ======================================================== -->
    <div class="tab-section active" data-tab-section="executive">
        
        <div class="section-header command-section-title">
            <i class="fas fa-chart-bar me-1"></i>Statistik Utama
        </div>

        @php
            $tingkatVal = (float) ($stats['tingkat_hunian'] ?? 0);
            $persenOver = (float) ($stats['persen_overkapasitas'] ?? 0);
            $gaugeStroke = 339.29; // 2 * PI * 54
            $gaugePercent = min(100, max(0, $tingkatVal));
            $gaugeOffset = $gaugeStroke - ($gaugeStroke * $gaugePercent / 100);
            
            if ($persenOver > 50) {
                $statusColor = '#f43f5e';
                $statusBadgeClass = 'pill-badge-rose';
                $statusText = 'Overkapasitas Kritis';
            } elseif ($persenOver > 0) {
                $statusColor = '#f59e0b';
                $statusBadgeClass = 'pill-badge-amber';
                $statusText = 'Sedikit Over';
            } else {
                $statusColor = '#10b981';
                $statusBadgeClass = 'pill-badge-emerald';
                $statusText = 'Kapasitas Normal';
            }
        @endphp

        <!-- Bento Hero Grid -->
        <div class="bento-hero-grid">
            
            <!-- Bento Hero Card (Radial Occupancy Gauge) -->
            <div class="bento-card bento-card-hero">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="live-beacon" style="background-color: {{ $statusColor }};">
                                <span class="beacon-ping"></span>
                                <span class="beacon-dot"></span>
                            </span>
                            <span class="fw-bold" style="font-size: 13px; color: var(--text-primary);">Tingkat Hunian Wilayah</span>
                        </div>
                        <span class="pill-badge {{ $statusBadgeClass }}">
                            {{ $persenOver > 0 ? '+' : '' }}{{ $persenOver }}% Over
                        </span>
                    </div>

                    <!-- High-Tech Speedometer Arc Gauge -->
                    @php
                        $arcLen = 251.32; // PI * R (80)
                        $clampedVal = min(100, max(0, $tingkatVal));
                        $arcOffset = $arcLen - ($arcLen * $clampedVal / 100);
                    @endphp
                    <div class="speedo-gauge-box my-2 mb-4">
                        <svg class="speedo-gauge-svg" viewBox="0 0 200 125">
                            <defs>
                                <linearGradient id="speedoGradDynamic" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#00f2fe"/>
                                    <stop offset="70%" stop-color="{{ $statusColor }}"/>
                                    <stop offset="100%" stop-color="{{ $statusColor }}"/>
                                </linearGradient>
                            </defs>
                            <!-- Speedometer Track -->
                            <path class="speedo-track" d="M 20 105 A 80 80 0 0 1 180 105" />
                            <!-- Speedometer Active Progress -->
                            <path class="speedo-fill" d="M 20 105 A 80 80 0 0 1 180 105"
                                  stroke="url(#speedoGradDynamic)"
                                  stroke-dasharray="251.32"
                                  stroke-dashoffset="{{ $arcOffset }}"
                                  style="--gauge-glow: {{ $statusColor }}77;" />
                        </svg>
                        
                        <div class="speedo-gauge-content">
                            <div class="radial-gauge-val font-speedometer count-up" data-target="{{ $tingkatVal }}">{{ $tingkatVal }}%</div>
                            <div class="radial-gauge-sub">{{ $statusText }}</div>
                        </div>

                        <div class="speedo-ticks">
                            <span class="speedo-tick-item tick-min" title="Batas Minimum (0%)">
                                <span class="tick-pip"></span>
                                <span class="tick-label">0%</span>
                            </span>
                            <span class="speedo-tick-item tick-mid" title="Batas Normal (50%)">
                                <span class="tick-pip"></span>
                                <span class="tick-label">50%</span>
                            </span>
                            <span class="speedo-tick-item tick-max" title="Kapasitas Penuh (100%)">
                                <span class="tick-pip"></span>
                                <span class="tick-label">100%</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--bento-border) !important;">
                    <div>
                        <span class="text-secondary small d-block text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">Total Penghuni</span>
                        <strong class="count-up font-speedometer" data-target="{{ $stats['total_penghuni'] }}" style="font-size: 20px; color: var(--text-primary);">
                            {{ number_format($stats['total_penghuni'], 0, ',', '.') }}
                        </strong>
                    </div>
                    <div class="text-center px-2">
                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 0.05em;">Status</div>
                        <span class="badge {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }} px-2 py-1" style="font-size: 10.5px; border-radius: 12px; font-weight: 600;">
                            {{ $persenOver > 0 ? 'Overkapasitas' : 'Normal' }}
                        </span>
                    </div>
                    <div class="text-end">
                        <span class="text-secondary small d-block text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">Total Kapasitas</span>
                        <strong class="count-up font-speedometer" data-target="{{ $stats['total_kapasitas'] }}" style="font-size: 20px; color: var(--text-primary);">
                            {{ number_format($stats['total_kapasitas'], 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Bento Subhero Grid (6 Key Metric Tiles) -->
            <div class="bento-card-subhero">
                
                <!-- Tile 1: Total Tahanan -->
                <div class="bento-stat-mini">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.25) 100%); color: #3b82f6;">
                            <i class="fas fa-user-lock"></i>
                        </div>
                        <span class="pill-badge pill-badge-indigo" style="font-size: 9px;">Tahanan</span>
                    </div>
                    <div>
                        <div class="bento-stat-value count-up" data-target="{{ $stats['total_tahanan'] }}">
                            {{ number_format($stats['total_tahanan'], 0, ',', '.') }}
                        </div>
                        <p class="bento-stat-label">Total Tahanan</p>
                    </div>
                </div>

                <!-- Tile 2: Total Narapidana -->
                <div class="bento-stat-mini">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(244, 63, 94, 0.15) 0%, rgba(225, 29, 72, 0.25) 100%); color: #f43f5e;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <span class="pill-badge pill-badge-rose" style="font-size: 9px;">Narapidana</span>
                    </div>
                    <div>
                        <div class="bento-stat-value count-up" data-target="{{ $stats['total_narapidana'] }}">
                            {{ number_format($stats['total_narapidana'], 0, ',', '.') }}
                        </div>
                        <p class="bento-stat-label">Total Narapidana</p>
                    </div>
                </div>

                <!-- Tile 3: UPT dengan Data -->
                <div class="bento-stat-mini">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.25) 100%); color: #10b981;">
                            <i class="fas fa-building"></i>
                        </div>
                        <span class="pill-badge pill-badge-emerald" style="font-size: 9px;">Aktif</span>
                    </div>
                    <div>
                        <div class="bento-stat-value count-up" data-target="{{ $stats['total_upt'] }}">
                            {{ number_format($stats['total_upt'], 0, ',', '.') }}
                        </div>
                        <p class="bento-stat-label">UPT dengan Data</p>
                    </div>
                </div>

                <!-- Tile 4: Overstaying Alert -->
                <div class="bento-stat-mini" style="border-left: 3px solid #ef4444;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.25) 100%); color: #ef4444;">
                            <i class="fas fa-clock"></i>
                        </div>
                        @if($stats['total_overstaying'] > 0)
                        <span class="live-beacon" style="background-color: #ef4444;">
                            <span class="beacon-ping"></span>
                            <span class="beacon-dot"></span>
                        </span>
                        @endif
                    </div>
                    <div>
                        <div class="bento-stat-value text-danger count-up" data-target="{{ $stats['total_overstaying'] }}">
                            {{ number_format($stats['total_overstaying'], 0, ',', '.') }}
                        </div>
                        <p class="bento-stat-label">Overstaying Tahanan</p>
                    </div>
                </div>

                <!-- Tile 5: Pidana Umum -->
                <div class="bento-stat-mini">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(124, 58, 237, 0.25) 100%); color: #8b5cf6;">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <span class="pill-badge pill-badge-purple" style="font-size: 9px;">Pidum</span>
                    </div>
                    <div>
                        <div class="bento-stat-value count-up" data-target="{{ $stats['total_pidum'] }}">
                            {{ number_format($stats['total_pidum'], 0, ',', '.') }}
                        </div>
                        <p class="bento-stat-label">Total Pidum</p>
                    </div>
                </div>

                <!-- Tile 6: Pidana Khusus -->
                <div class="bento-stat-mini">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(8, 145, 178, 0.25) 100%); color: #06b6d4;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="pill-badge pill-badge-cyan" style="font-size: 9px;">Pidsus</span>
                    </div>
                    <div>
                        <div class="bento-stat-value count-up" data-target="{{ $stats['total_pidsus'] }}">
                            {{ number_format($stats['total_pidsus'], 0, ',', '.') }}
                        </div>
                        <p class="bento-stat-label">Total Pidsus</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Quick Summary Snapshot: Kasus Khusus & Bebas Harian -->
        <div class="row row-cols-2 row-cols-sm-3 row-cols-xl-5 g-2 mb-3">
            <!-- Card 1: Pidana Mati -->
            <div class="col">
                <div class="bento-card p-3 h-100 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="snapshot-card-label">Pidana Mati</div>
                        <div class="snapshot-card-value font-speedometer text-danger count-up" data-target="{{ $stats['total_pidana_mati'] }}">
                            {{ number_format($stats['total_pidana_mati'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="snapshot-card-icon" style="background: rgba(244, 63, 94, 0.14); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.25);">
                        <i class="fas fa-skull-crossbones"></i>
                    </div>
                </div>
            </div>

            <!-- Card 2: Seumur Hidup -->
            <div class="col">
                <div class="bento-card p-3 h-100 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="snapshot-card-label">Seumur Hidup</div>
                        <div class="snapshot-card-value font-speedometer text-danger count-up" data-target="{{ $stats['total_seumur_hidup'] }}">
                            {{ number_format($stats['total_seumur_hidup'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="snapshot-card-icon" style="background: rgba(225, 29, 72, 0.14); color: #e11d48; border: 1px solid rgba(225, 29, 72, 0.25);">
                        <i class="fas fa-infinity"></i>
                    </div>
                </div>
            </div>

            <!-- Card 3: WNA -->
            <div class="col">
                <div class="bento-card p-3 h-100 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="snapshot-card-label">WNA</div>
                        <div class="snapshot-card-value font-speedometer text-warning count-up" data-target="{{ $stats['total_wna'] }}">
                            {{ number_format($stats['total_wna'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="snapshot-card-icon" style="background: rgba(245, 158, 11, 0.14); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25);">
                        <i class="fas fa-globe"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4: Lansia > 70 Tahun -->
            <div class="col">
                <div class="bento-card p-3 h-100 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="snapshot-card-label">Lansia > 70 Thn</div>
                        <div class="snapshot-card-value font-speedometer text-warning count-up" data-target="{{ $stats['total_lansia'] }}">
                            {{ number_format($stats['total_lansia'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="snapshot-card-icon" style="background: rgba(249, 115, 22, 0.14); color: #f97316; border: 1px solid rgba(249, 115, 22, 0.25);">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Card 5: Bebas Hari Ini -->
            <div class="col col-12 col-sm-auto flex-sm-grow-1 flex-xl-grow-0">
                <div class="bento-card p-3 h-100 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="snapshot-card-label">Bebas Hari Ini</div>
                        <div class="snapshot-card-value font-speedometer text-success count-up" data-target="{{ $stats['total_bebas'] }}">
                            {{ number_format($stats['total_bebas'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="snapshot-card-icon" style="background: rgba(16, 185, 129, 0.14); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);">
                        <i class="fas fa-door-open"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Overview Row: Klasifikasi & Distribusi Napi/Tahanan -->
        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="chart-card-modern h-100 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="bento-stat-icon mb-0" style="width: 28px; height: 28px; font-size: 12px; background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                                <i class="fas fa-chart-bar"></i>
                            </span>
                            <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 14px;">
                                Penghuni per Klasifikasi
                            </h6>
                        </div>
                        <span class="pill-badge pill-badge-cyan" style="font-size: 10px;">
                            <i class="fas fa-shield-alt me-1"></i>Klasifikasi Pidana
                        </span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartKlasifikasi"></canvas>
                    </div>
                    <div id="klasifikasiLegend" class="mt-3 pt-2 border-top w-100" style="border-color: var(--bento-border) !important;"></div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="chart-card-modern h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="bento-stat-icon mb-0" style="width: 28px; height: 28px; font-size: 12px; background: rgba(99, 102, 241, 0.15); color: #818cf8;">
                                    <i class="fas fa-chart-pie"></i>
                                </span>
                                <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 14px;">
                                    Distribusi Napi & Tahanan
                                </h6>
                            </div>
                            @php
                                $napiRatio = $stats['total_penghuni'] > 0 ? round(($stats['total_narapidana'] / $stats['total_penghuni']) * 100, 1) : 0;
                            @endphp
                            <span class="pill-badge pill-badge-indigo" style="font-size: 10px;">
                                {{ $napiRatio }}% Napi
                            </span>
                        </div>

                        <!-- High-Tech Speedometer / Cockpit Donut Ring -->
                        <div class="donut-chart-container my-3 position-relative d-flex align-items-center justify-content-center" style="height: 200px;">
                            <canvas id="chartNarapidanaTahanan"></canvas>
                            <div class="donut-center-telemetry">
                                <div class="donut-center-val font-speedometer count-up" data-target="{{ $stats['total_penghuni'] }}">
                                    {{ number_format($stats['total_penghuni'], 0, ',', '.') }}
                                </div>
                                <div class="donut-center-sub">Total Jiwa</div>
                            </div>
                        </div>
                    </div>

                    <!-- Telemetry Ratio Breakdown Footer -->
                    <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top" style="border-color: var(--bento-border) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="donut-legend-pip" style="background: #6366f1; box-shadow: 0 0 8px rgba(99, 102, 241, 0.6);"></span>
                            <div>
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing: 0.05em;">Narapidana</div>
                                <div class="fw-bold font-speedometer" style="font-size: 15px; color: var(--text-primary);">
                                    {{ number_format($stats['total_narapidana']) }}
                                    <small class="text-secondary fw-normal font-speedometer" style="font-size: 11px;">({{ $napiRatio }}%)</small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-end">
                            <div>
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing: 0.05em;">Tahanan</div>
                                <div class="fw-bold font-speedometer" style="font-size: 15px; color: var(--text-primary);">
                                    {{ number_format($stats['total_tahanan']) }}
                                    <small class="text-secondary fw-normal font-speedometer" style="font-size: 11px;">({{ round(100 - $napiRatio, 1) }}%)</small>
                                </div>
                            </div>
                            <span class="donut-legend-pip" style="background: #f43f5e; box-shadow: 0 0 8px rgba(244, 63, 94, 0.6);"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- tab-section executive -->

    <!-- ======================================================== -->
    <!-- TAB SECTION 2: PETA SEBARAN & DATA HUNIAN UPT            -->
    <!-- ======================================================== -->
    <div class="tab-section" data-tab-section="gis">
        
        <!-- Peta Lokasi UPT di Banten -->
        <div class="chart-card-modern mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="section-header command-section-title mb-0">
                    <i class="fas fa-map-marked-alt text-primary me-2"></i>Peta Lokasi UPT di Banten
                </div>
                <div class="btn-group btn-group-sm mt-2 mt-sm-0" role="group">
                    <button type="button" class="btn btn-outline-primary" id="btnStreet">Street</button>
                    <button type="button" class="btn btn-outline-primary" id="btnSatellite">Satellite</button>
                    <button type="button" class="btn btn-outline-primary active" id="btnHybrid">Hybrid</button>
                </div>
            </div>
            <div id="mapBanten" style="height: 420px; width: 100%; border-radius: 14px; z-index: 1;"></div>
            <div class="d-flex align-items-center justify-content-between mt-2 flex-wrap gap-2 text-muted small">
                <div><i class="fas fa-info-circle text-primary me-1"></i> Klik marker UPT untuk memunculkan detail hunian & kapasitas</div>
                <div class="d-flex gap-2 align-items-center" style="font-size: 10px;">
                    <span class="pill-badge pill-badge-emerald">Longgar</span>
                    <span class="pill-badge pill-badge-amber">Hampir Penuh</span>
                    <span class="pill-badge pill-badge-rose">Overkapasitas</span>
                </div>
            </div>
        </div>

        <!-- Overkapasitas Table with Instant Search Filter -->
        <div class="table-card-modern">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 14px;">
                        <i class="fas fa-table text-primary me-2"></i>Data Penghuni & Overkapasitas per UPT
                    </h6>
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }}</small>
                </div>
                
                <!-- Instant Search Box -->
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tableOverkapasitas" placeholder="Cari nama UPT...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tableOverkapasitas" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-2" style="width: 35px;">No</th>
                            <th class="py-2" style="min-width: 140px;">Unit Pelaksana Teknis</th>
                            <th class="text-center py-2" style="width: 70px;">Kapasitas</th>
                            <th class="text-center py-2" style="width: 70px;">Tahanan</th>
                            <th class="text-center py-2" style="width: 70px;">Narapidana</th>
                            <th class="text-center py-2" style="width: 80px;">Total</th>
                            <th class="text-center py-2" style="width: 120px;">Tingkat Hunian</th>
                            <th class="text-center py-2" style="width: 80px;">% Over</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse ($uptDataTable as $row)
                            @php
                                $persen = $row->kapasitas > 0 ? round((($row->total_penghuni - $row->kapasitas) / $row->kapasitas) * 100, 1) : 0;
                                $tingkatHunianUpt = $row->kapasitas > 0 ? round(($row->total_penghuni / $row->kapasitas) * 100, 1) : 0;
                                $colorClass = getOverkapasitasColorClass($persen);
                                $barWidth = min(100, max(0, $tingkatHunianUpt));
                                $barColor = $persen > 50 ? '#f43f5e' : ($persen > 0 ? '#f59e0b' : '#10b981');
                            @endphp
                            <tr>
                                <td class="text-center py-2">{{ $no++ }}</td>
                                <td class="py-2 fw-medium">{{ $row->nama_upt }}</td>
                                <td class="text-center py-2">{{ number_format($row->kapasitas) }}</td>
                                <td class="text-center py-2">{{ number_format($row->total_tahanan) }}</td>
                                <td class="text-center py-2">{{ number_format($row->total_narapidana) }}</td>
                                <td class="text-center py-2"><strong>{{ number_format($row->total_penghuni) }}</strong></td>
                                <td class="py-2">
                                    <div class="d-flex justify-content-between align-items-center" style="font-size: 10px;">
                                        <strong>{{ $tingkatHunianUpt }}%</strong>
                                    </div>
                                    <div class="table-bar-track">
                                        <div class="table-bar-fill" style="width: {{ $barWidth }}%; background-color: {{ $barColor }};"></div>
                                    </div>
                                </td>
                                <td class="text-center py-2 {{ $colorClass }}" style="font-size: 11px;">
                                    <strong>{{ $persen > 0 ? '+' : '' }}{{ $persen }}%</strong>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2">{{ number_format($stats['total_kapasitas']) }}</td>
                            <td class="text-center py-2">{{ number_format($stats['total_tahanan']) }}</td>
                            <td class="text-center py-2">{{ number_format($stats['total_narapidana']) }}</td>
                            <td class="text-center py-2"><strong>{{ number_format($stats['total_penghuni']) }}</strong></td>
                            @php $total_tingkat_hunian = $stats['total_kapasitas'] > 0 ? round(($stats['total_penghuni'] / $stats['total_kapasitas']) * 100, 1) : 0; @endphp
                            <td class="text-center py-2 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}" style="font-size: 11px;">
                                <strong>{{ $total_tingkat_hunian }}%</strong>
                            </td>
                            <td class="text-center py-2 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}" style="font-size: 11px;">
                                <strong>{{ $stats['persen_overkapasitas'] > 0 ? '+' : '' }}{{ $stats['persen_overkapasitas'] }}%</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div><!-- tab-section gis -->

    <!-- ======================================================== -->
    <!-- TAB SECTION 3: KLASIFIKASI PIDUM & PIDSUS                -->
    <!-- ======================================================== -->
    <div class="tab-section" data-tab-section="klasifikasi">

        <!-- Klasifikasi Statistics Cards -->
        <div class="section-header command-section-title"><i class="fas fa-gavel me-1"></i>Pidum dan Pidsus</div>
        <div class="row mb-3 g-2">
            <!-- Pidana Umum -->
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm pidum h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-7 me-2"><i class="fas fa-user"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm count-up" data-target="{{ $stats['total_pidum'] }}">{{ number_format($stats['total_pidum'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Pidum</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pidana Khusus -->
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm pidsus h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-8 me-2"><i class="fas fa-gavel"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm count-up" data-target="{{ $stats['total_pidsus'] }}">{{ number_format($stats['total_pidsus'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Pidsus</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- UU ITE -->
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm uu-ite h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-9 me-2"><i class="fas fa-laptop"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm count-up" data-target="{{ $stats['total_uu_ite'] }}">{{ number_format($stats['total_uu_ite'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total UU ITE</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Penghuni Lainnya Cards -->
        <div class="section-header lainnya"><i class="fas fa-exclamation-triangle me-1"></i>Lainnya</div>
        <div class="row mb-3 g-2">
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm lainnya h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-globe"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-lainnya count-up" data-target="{{ $stats['total_wna'] }}">{{ number_format($stats['total_wna'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total WNA</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm lainnya h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-hospital"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-lainnya count-up" data-target="{{ $stats['total_sakit'] }}">{{ number_format($stats['total_sakit'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Sakit Berkepanjangan</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm lainnya h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-user-clock"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-lainnya count-up" data-target="{{ $stats['total_lansia'] }}">{{ number_format($stats['total_lansia'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Lansia >70 Tahun</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pidana Mati dan Seumur Hidup Cards -->
        <div class="section-header text-danger" style="border-bottom: 2px solid #dc3545;"><i class="fas fa-skull-crossbones me-1"></i>Pidana Mati dan Seumur Hidup</div>
        <div class="row mb-3 g-2">
            <div class="col-6 col-md-6">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #dc3545;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-danger text-white me-2"><i class="fas fa-skull-crossbones"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-danger count-up" data-target="{{ $stats['total_pidana_mati'] }}">{{ number_format($stats['total_pidana_mati'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Pidana Mati</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #dc3545;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-danger text-white me-2"><i class="fas fa-infinity"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-danger count-up" data-target="{{ $stats['total_seumur_hidup'] }}">{{ number_format($stats['total_seumur_hidup'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Seumur Hidup</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Penghuni Berdasarkan Jenis Kejahatan per UPT -->
        <div class="table-card-modern">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 14px;">
                        <i class="fas fa-table text-primary me-2"></i>Data Penghuni Berdasarkan Jenis Kejahatan per UPT
                    </h6>
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }}</small>
                </div>
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tableKejahatan" placeholder="Cari UPT...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tableKejahatan" style="font-size: 10px;">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="text-center align-middle py-1" style="width: 25px;">No</th>
                            <th rowspan="2" class="align-middle py-1" style="min-width: 80px;">UPT</th>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px;">Pidum</th>
                            <th colspan="9" class="text-center py-1 bg-info bg-opacity-25" style="font-size: 9px;">Pidsus</th>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px;">ITE</th>
                            <th colspan="5" class="text-center py-1 bg-warning bg-opacity-25" style="font-size: 9px;">Lainnya</th>
                        </tr>
                        <tr>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px; font-size: 9px;">Jml</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Tipikor</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Nrk<br>Br</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Nrk<br>Pg</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Teror</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Traff</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Ileg<br>Log</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Money<br>Lau</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Geno</th>
                            <th class="text-center py-1 bg-success text-white" style="width: 42px; font-size: 9px;"><strong>Jml</strong></th>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px; font-size: 9px;">Jml</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">WNA</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">Sakit</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">Lansia</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">MT</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">SH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                        $no = 1;
                        $totalPidum = 0; $totalTipikor = 0; $totalNarkobaBandar = 0; $totalNarkobaPengguna = 0;
                        $totalTeroris = 0; $totalTrafficking = 0; $totalIlegalLogging = 0;
                        $totalMoneyLaundering = 0; $totalGenosida = 0; $totalPidsus = 0;
                        $totalUUITE = 0;
                        $totalWNA = 0; $totalSakit = 0; $totalLansia = 0; $totalPidanaMati = 0; $totalSeumurHidup = 0;
                        @endphp
                        @forelse ($jenisKejahatanData as $row)
                            @php
                            $wna = (int)($row->wna ?? 0);
                            $sakit = (int)($row->sakit ?? 0);
                            $lansia = (int)($row->lansia ?? 0);
                            $pidana_mati = (int)($row->pidana_mati ?? 0);
                            $seumur_hidup = (int)($row->seumur_hidup ?? 0);

                            $pidsusTotal = (int)$row->tipikor + (int)$row->narkoba_bandar + (int)$row->narkoba_pengguna + 
                                           (int)$row->teroris + (int)$row->trafficking + (int)$row->ilegal_logging + 
                                           (int)$row->money_laundering + (int)$row->genosida;

                            $totalPidum += (int)$row->pidum;
                            $totalTipikor += (int)$row->tipikor;
                            $totalNarkobaBandar += (int)$row->narkoba_bandar;
                            $totalNarkobaPengguna += (int)$row->narkoba_pengguna;
                            $totalTeroris += (int)$row->teroris;
                            $totalTrafficking += (int)$row->trafficking;
                            $totalIlegalLogging += (int)$row->ilegal_logging;
                            $totalMoneyLaundering += (int)$row->money_laundering;
                            $totalGenosida += (int)$row->genosida;
                            $totalPidsus += $pidsusTotal;
                            $totalUUITE += (int)$row->uu_ite;
                            $totalWNA += $wna;
                            $totalSakit += $sakit;
                            $totalLansia += $lansia;
                            $totalPidanaMati += $pidana_mati;
                            $totalSeumurHidup += $seumur_hidup;

                            $statistikTotalRow = (int)$row->pidum + $pidsusTotal + (int)$row->uu_ite;
                            $p_pidum = $statistikTotalRow > 0 ? round(($row->pidum / $statistikTotalRow) * 100, 1) : 0;
                            $p_pidsus = $statistikTotalRow > 0 ? round(($pidsusTotal / $statistikTotalRow) * 100, 1) : 0;
                            $p_ite = $statistikTotalRow > 0 ? round(($row->uu_ite / $statistikTotalRow) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td class="text-center py-1">{{ $no++ }}</td>
                                <td class="py-1 fw-medium">{{ $row->nama_upt }}</td>
                                <td class="text-center py-1">{{ number_format($row->pidum) }}<br><small class="text-muted" style="font-size: 8px;">{{ $p_pidum }}%</small></td>
                                <td class="text-center py-1">{{ number_format($row->tipikor) }}</td>
                                <td class="text-center py-1">{{ number_format($row->narkoba_bandar) }}</td>
                                <td class="text-center py-1">{{ number_format($row->narkoba_pengguna) }}</td>
                                <td class="text-center py-1">{{ number_format($row->teroris) }}</td>
                                <td class="text-center py-1">{{ number_format($row->trafficking) }}</td>
                                <td class="text-center py-1">{{ number_format($row->ilegal_logging) }}</td>
                                <td class="text-center py-1">{{ number_format($row->money_laundering) }}</td>
                                <td class="text-center py-1">{{ number_format($row->genosida) }}</td>
                                <td class="text-center py-1 table-success"><strong>{{ number_format($pidsusTotal) }}</strong><br><small class="text-success" style="font-size: 8px;">({{ $p_pidsus }}%)</small></td>
                                <td class="text-center py-1">{{ number_format($row->uu_ite) }}<br><small class="text-muted" style="font-size: 8px;">{{ $p_ite }}%</small></td>
                                <td class="text-center py-1 text-warning-emphasis">{{ number_format($wna) }}</td>
                                <td class="text-center py-1 text-warning-emphasis">{{ number_format($sakit) }}</td>
                                <td class="text-center py-1 text-warning-emphasis">{{ number_format($lansia) }}</td>
                                <td class="text-center py-1 text-danger fw-bold">{{ number_format($pidana_mati) }}</td>
                                <td class="text-center py-1 text-danger fw-bold">{{ number_format($seumur_hidup) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="18" class="text-center py-2 text-muted">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        @php $statistikGrandTotal = $totalPidum + $totalPidsus + $totalUUITE; @endphp
                        <tr class="fw-bold" style="font-size: 10px;">
                            <td colspan="2" class="text-end py-1">TOTAL</td>
                            <td class="text-center py-1">
                                {{ number_format($totalPidum) }}<br>
                                <small class="text-muted" style="font-size: 8px;">{{ $statistikGrandTotal > 0 ? round(($totalPidum / $statistikGrandTotal) * 100, 1) : 0 }}%</small>
                            </td>
                            <td class="text-center py-1">{{ number_format($totalTipikor) }}</td>
                            <td class="text-center py-1">{{ number_format($totalNarkobaBandar) }}</td>
                            <td class="text-center py-1">{{ number_format($totalNarkobaPengguna) }}</td>
                            <td class="text-center py-1">{{ number_format($totalTeroris) }}</td>
                            <td class="text-center py-1">{{ number_format($totalTrafficking) }}</td>
                            <td class="text-center py-1">{{ number_format($totalIlegalLogging) }}</td>
                            <td class="text-center py-1">{{ number_format($totalMoneyLaundering) }}</td>
                            <td class="text-center py-1">{{ number_format($totalGenosida) }}</td>
                            <td class="text-center py-1 table-success">
                                {{ number_format($totalPidsus) }}<br>
                                <small class="text-success" style="font-size: 8px;">{{ $statistikGrandTotal > 0 ? round(($totalPidsus / $statistikGrandTotal) * 100, 1) : 0 }}%</small>
                            </td>
                            <td class="text-center py-1">
                                {{ number_format($totalUUITE) }}<br>
                                <small class="text-muted" style="font-size: 8px;">{{ $statistikGrandTotal > 0 ? round(($totalUUITE / $statistikGrandTotal) * 100, 1) : 0 }}%</small>
                            </td>
                            <td class="text-center py-1 text-warning-emphasis">{{ number_format($totalWNA) }}</td>
                            <td class="text-center py-1 text-warning-emphasis">{{ number_format($totalSakit) }}</td>
                            <td class="text-center py-1 text-warning-emphasis">{{ number_format($totalLansia) }}</td>
                            <td class="text-center py-1 text-danger">{{ number_format($totalPidanaMati) }}</td>
                            <td class="text-center py-1 text-danger">{{ number_format($totalSeumurHidup) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Tabel Pidana Mati dan Seumur Hidup per UPT -->
        <div class="table-card-modern">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-danger fw-bold"><i class="fas fa-skull-crossbones me-2"></i>Data Pidana Mati dan Seumur Hidup per UPT</h6>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">
                    <i class="fas fa-calendar-day me-1"></i>{{ formatIndonesianDate($filterTanggal, false) }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-2" style="width: 40px;">No</th>
                            <th class="py-2" style="min-width: 150px;">Unit Pelaksana Teknis</th>
                            <th class="text-center py-2" style="width: 120px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">Pidana Mati</th>
                            <th class="text-center py-2" style="width: 120px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">Seumur Hidup</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                        $no = 1;
                        $totalPM = 0;
                        $totalSH = 0;
                        @endphp
                        @forelse ($pmshData as $row)
                            @php
                            $pm = (int)$row->pidana_mati;
                            $sh = (int)$row->seumur_hidup;
                            $totalPM += $pm;
                            $totalSH += $sh;
                            @endphp
                            <tr>
                                <td class="text-center py-2">{{ $no++ }}</td>
                                <td class="py-2">{{ $row->nama_upt }}</td>
                                <td class="text-center py-2 text-danger"><strong>{{ number_format($pm) }}</strong></td>
                                <td class="text-center py-2 text-danger"><strong>{{ number_format($sh) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2 text-danger" style="background-color: rgba(220, 53, 69, 0.1);">{{ number_format($totalPM) }}</td>
                            <td class="text-center py-2 text-danger" style="background-color: rgba(220, 53, 69, 0.1);">{{ number_format($totalSH) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Chart Penghuni Berdasarkan Umur dan Jenis Kelamin -->
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="chart-card-modern h-100">
                    <h6 class="mb-3 fw-bold" style="color: var(--text-primary);"><i class="fas fa-venus-mars text-primary me-2"></i>Penghuni Berdasarkan Umur & Jenis Kelamin</h6>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(54, 162, 235, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(54, 162, 235, 0.9); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Dewasa ♂: <span id="legend-dewasa-laki">0</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(54, 162, 235, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(54, 162, 235, 0.6); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Anak ♂: <span id="legend-anak-laki">0</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 99, 132, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(255, 99, 132, 0.9); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Dewasa ♀: <span id="legend-dewasa-perempuan">0</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 99, 132, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(255, 99, 132, 0.6); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Anak ♀: <span id="legend-anak-perempuan">0</span></span>
                        </div>
                    </div>
                    <div style="height: 220px;">
                        <canvas id="chartUmurGender"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="chart-card-modern h-100">
                    <h6 class="mb-3 fw-bold" style="color: var(--text-primary);"><i class="fas fa-chart-pie text-primary me-2"></i>Distribusi Jenis Kelamin</h6>
                    <div class="row align-items-center">
                        <div class="col-5">
                            <div style="height: 180px;">
                                <canvas id="chartGenderDistribution"></canvas>
                            </div>
                        </div>
                        <div class="col-7">
                            <div id="genderLegendContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- tab-section klasifikasi -->

    <!-- ======================================================== -->
    <!-- TAB SECTION 4: LAYANAN PENGELUARAN / BEBAS HARIAN        -->
    <!-- ======================================================== -->
    <div class="tab-section" data-tab-section="bebas">

        <!-- Bebas Harian Statistics Cards -->
        <div class="section-header command-section-title" style="border-bottom: 2px solid #10b981; color: #10b981;">
            <i class="fas fa-door-open me-1"></i>Pengeluaran / Bebas Harian
        </div>
        <div class="row mb-3 g-2">
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #6b7280;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-secondary text-white me-2"><i class="fas fa-check-circle"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm count-up" data-target="{{ $stats['bebas_murni'] }}">{{ number_format($stats['bebas_murni'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Bebas Murni</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #3b82f6;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-primary text-white me-2"><i class="fas fa-user-check"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-primary count-up" data-target="{{ $stats['bebas_pb'] }}">{{ number_format($stats['bebas_pb'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">PB (Bersyarat)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #06b6d4;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-info text-white me-2"><i class="fas fa-calendar-check"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-info count-up" data-target="{{ $stats['bebas_cb'] }}">{{ number_format($stats['bebas_cb'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">CB (Cuti Bersyarat)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #f59e0b;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning text-dark me-2"><i class="fas fa-hourglass-end"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-warning count-up" data-target="{{ $stats['bebas_cmb'] }}">{{ number_format($stats['bebas_cmb'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">CMB</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #10b981;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-success text-white me-2"><i class="fas fa-home"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-success count-up" data-target="{{ $stats['bebas_asimilasi'] }}">{{ number_format($stats['bebas_asimilasi'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Asimilasi</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #ef4444;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-danger text-white me-2"><i class="fas fa-balance-scale"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-danger count-up" data-target="{{ $stats['bebas_bdh'] }}">{{ number_format($stats['bebas_bdh'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">BDH (Demi Hukum)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #8b5cf6;">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm text-white me-2" style="background: #8b5cf6;"><i class="fas fa-flag-checkered"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm fw-bold count-up" data-target="{{ $stats['total_bebas'] }}" style="color: #8b5cf6;">{{ number_format($stats['total_bebas'], 0, ',', '.') }}</div>
                            <p class="stat-label-sm">Total Bebas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Bebas Harian per UPT -->
        <div class="table-card-modern">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 text-success fw-bold"><i class="fas fa-door-open me-2"></i>Data Pengeluaran / Bebas Harian per UPT</h6>
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }}</small>
                </div>
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tableBebasHarian" placeholder="Cari UPT...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tableBebasHarian" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-2" style="width: 40px;">No</th>
                            <th class="py-2" style="min-width: 170px;">Unit Pelaksana Teknis</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(107, 114, 128, 0.08);">Bebas Murni</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(59, 130, 246, 0.08); color: #1d4ed8;">PB</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(6, 182, 212, 0.08); color: #0891b2;">CB</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(139, 92, 246, 0.08); color: #7c3aed;">CMB</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(245, 158, 11, 0.08); color: #b45309;">Asimilasi</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(239, 68, 68, 0.08); color: #b91c1c;">BDH</th>
                            <th class="text-center py-2" style="width: 95px; background-color: rgba(100, 116, 139, 0.08); color: #475569;">Lainnya</th>
                            <th class="text-center py-2" style="width: 100px; background-color: rgba(16, 185, 129, 0.15); color: #047857;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                        $noBebas = 1;
                        $totBM = 0; $totPB = 0; $totCB = 0; $totCMB = 0;
                        $totAsim = 0; $totBDH = 0; $totLny = 0; $totSemua = 0;
                        @endphp
                        @forelse ($bebasHarianUptData as $rowBebas)
                            @php
                            $bm = (int)$rowBebas->bebas_murni;
                            $pb = (int)$rowBebas->pb;
                            $cb = (int)$rowBebas->cb;
                            $cmb = (int)$rowBebas->cmb;
                            $asim = (int)$rowBebas->asimilasi;
                            $bdh = (int)$rowBebas->bdh;
                            $lny = (int)$rowBebas->lainnya;
                            $totalUpt = $bm + $pb + $cb + $cmb + $asim + $bdh + $lny;
                            
                            $totBM += $bm; $totPB += $pb; $totCB += $cb; $totCMB += $cmb;
                            $totAsim += $asim; $totBDH += $bdh; $totLny += $lny;
                            $totSemua += $totalUpt;
                            @endphp
                            <tr>
                                <td class="text-center py-2">{{ $noBebas++ }}</td>
                                <td class="py-2 fw-medium">{{ $rowBebas->nama_upt }}</td>
                                <td class="text-center py-2 {{ $bm > 0 ? 'fw-bold' : 'text-muted' }}">{{ number_format($bm) }}</td>
                                <td class="text-center py-2 {{ $pb > 0 ? 'fw-bold text-primary' : 'text-muted' }}">{{ number_format($pb) }}</td>
                                <td class="text-center py-2 {{ $cb > 0 ? 'fw-bold text-info' : 'text-muted' }}">{{ number_format($cb) }}</td>
                                <td class="text-center py-2 {{ $cmb > 0 ? 'fw-bold' : 'text-muted' }}" style="{{ $cmb > 0 ? 'color: #7c3aed;' : '' }}">{{ number_format($cmb) }}</td>
                                <td class="text-center py-2 {{ $asim > 0 ? 'fw-bold text-warning-emphasis' : 'text-muted' }}">{{ number_format($asim) }}</td>
                                <td class="text-center py-2 {{ $bdh > 0 ? 'fw-bold text-danger' : 'text-muted' }}">{{ number_format($bdh) }}</td>
                                <td class="text-center py-2 {{ $lny > 0 ? 'fw-bold text-secondary' : 'text-muted' }}">{{ number_format($lny) }}</td>
                                <td class="text-center py-2 fw-bold text-success" style="background-color: rgba(16, 185, 129, 0.05);">{{ number_format($totalUpt) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-3">Belum ada data pengeluaran / bebas harian</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2" style="background-color: rgba(107, 114, 128, 0.08);">{{ number_format($totBM) }}</td>
                            <td class="text-center py-2 text-primary" style="background-color: rgba(59, 130, 246, 0.08);">{{ number_format($totPB) }}</td>
                            <td class="text-center py-2 text-info" style="background-color: rgba(6, 182, 212, 0.08);">{{ number_format($totCB) }}</td>
                            <td class="text-center py-2" style="background-color: rgba(139, 92, 246, 0.08); color: #7c3aed;">{{ number_format($totCMB) }}</td>
                            <td class="text-center py-2 text-warning-emphasis" style="background-color: rgba(245, 158, 11, 0.08);">{{ number_format($totAsim) }}</td>
                            <td class="text-center py-2 text-danger" style="background-color: rgba(239, 68, 68, 0.08);">{{ number_format($totBDH) }}</td>
                            <td class="text-center py-2 text-secondary" style="background-color: rgba(100, 116, 139, 0.08);">{{ number_format($totLny) }}</td>
                            <td class="text-center py-2 text-success" style="background-color: rgba(16, 185, 129, 0.18); font-size: 12px;">{{ number_format($totSemua) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div><!-- tab-section bebas -->

    <!-- ======================================================== -->
    <!-- TAB SECTION 5: DETEKSI OVERSTAYING                       -->
    <!-- ======================================================== -->
    <div class="tab-section" data-tab-section="overstaying">

        <div class="chart-card-modern">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="section-header command-section-title text-danger mb-0">
                    <i class="fas fa-clock me-1"></i>Overstaying Tahanan per Kategori
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="pill-badge pill-badge-emerald">Normal</span>
                    <span class="pill-badge pill-badge-rose">Overstaying</span>
                </div>
            </div>

            <!-- Overstaying Alert Banner -->
            @if($stats['total_overstaying'] > 0)
            <div class="alert alert-danger d-flex align-items-center gap-3 p-3 mb-4 rounded-3 border-0 shadow-sm" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                <div class="fs-3"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <h6 class="mb-1 fw-bold">Peringatan: Terdeteksi {{ number_format($stats['total_overstaying']) }} Tahanan Overstaying</h6>
                    <small>Segera lakukan koordinasi dan tindak lanjut perpanjangan penahanan dengan instansi penahan terkait.</small>
                </div>
            </div>
            @endif

            <div class="row g-3">
                @php 
                $kategoriLabels = ['ai' => 'AI (Kepolisian)', 'aii' => 'AII (Kejaksaan)', 'aiii' => 'AIII (Pengadilan Negeri)', 'aiv' => 'AIV (Pengadilan Tinggi)', 'av' => 'AV (Mahkamah Agung)'];
                @endphp
                @foreach ($kategoriLabels as $key => $label)
                    @php $stat = $overstayingStats[$key]; @endphp
                    <div class="col-6 col-md-4 col-xl mb-3">
                        <div class="bento-card text-center p-3 h-100">
                            <div style="position: relative; width: 100%; max-width: 100px; margin: 0 auto;">
                                <canvas id="chartOverstaying{{ strtoupper($key) }}"></canvas>
                            </div>
                            <div class="mt-2">
                                <strong style="font-size: 13px; color: var(--text-primary);">{{ $label }}</strong>
                                <div class="small mt-1">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">{{ $stat['overstaying'] }} Over</span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">{{ $stat['tahanan'] }} Total</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div><!-- tab-section overstaying -->

</div><!-- content-wrapper -->

@if($user && $pengumumanPopup->isNotEmpty())
<!-- Modal Pengumuman Popup -->
<div class="modal fade pengumuman-modal" id="pengumumanPopupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bullhorn me-2"></i>Pengumuman & Berita
                    <span class="pengumuman-counter" id="pengumumanCounter"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="pengumumanCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                    <div class="carousel-inner">
                        @foreach ($pengumumanPopup as $index => $p)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="pengumuman-modal-item">
                                <div class="pengumuman-modal-item-header">
                                    <span class="pengumuman-modal-kategori kategori-{{ strtolower($p->kategori) }}">{{ $p->kategori }}</span>
                                    <span class="pengumuman-modal-judul">{{ $p->judul }}</span>
                                </div>
                                @if($p->gambar && file_exists(public_path($p->gambar)))
                                <div class="pengumuman-modal-image-container">
                                    <img src="{{ asset($p->gambar) }}" class="pengumuman-modal-image" alt="Gambar Pengumuman">
                                </div>
                                @endif
                                <div class="pengumuman-modal-isi">{!! nl2br(e($p->isi)) !!}</div>
                                <div class="pengumuman-modal-meta">
                                    <span><i class="fas fa-clock"></i>{{ $p->created_at ? $p->created_at->format('d/m/Y H:i') : '' }}</span>
                                    @if(!empty($p->upt?->nama_upt))
                                    <span><i class="fas fa-building"></i>{{ $p->upt->nama_upt }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if(count($pengumumanPopup) > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                    @endif
                </div>
            </div>
            <div class="pengumuman-modal-footer">
                <i class="fas fa-info-circle me-1"></i>Klik tombol <strong>X</strong> di pojok kanan atas untuk menutup
            </div>
        </div>
    </div>
</div>
@endif

<!-- Welcome Modal -->
<div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 800px;">
        <div class="modal-content border-0 shadow-lg" style="background: var(--bg-card, #fff);">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body text-center">
                @if(!empty($instansi->welcome_banner))
                <div class="welcome-banner-container">
                    <img src="{{ asset($instansi->welcome_banner) }}" class="img-fluid welcome-banner-img" alt="Welcome Sultan Banten">
                </div>
                @endif
                <div class="welcome-text-container">
                    <h3 class="fw-bold mb-2" style="color: var(--text-primary, #212529);">Selamat Datang di Sultan Banten</h3>
                    <p class="mb-3" style="color: var(--text-secondary, #6c757d);">Sistem Informasi Layanan Terpadu dan Notifikasi Banten</p>
                    <button type="button" class="btn btn-primary px-5 py-2 shadow-sm" data-bs-dismiss="modal" style="border-radius: 30px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        Mulai Jelajahi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Helper function for Indonesian number format
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Register ChartDataLabels plugin
    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    // Tab Switching Logic
    function switchDashboardTab(tabName) {
        const tabBtns = document.querySelectorAll('.exec-tab-btn');
        const sections = document.querySelectorAll('.tab-section');

        tabBtns.forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
        });

        sections.forEach(sec => {
            if (tabName === 'all') {
                sec.style.display = 'block';
                sec.classList.add('active');
            } else {
                const matches = sec.getAttribute('data-tab-section') === tabName;
                sec.style.display = matches ? 'block' : 'none';
                if (matches) sec.classList.add('active');
                else sec.classList.remove('active');
            }
        });

        // Invalidate Leaflet map size when tab becomes visible
        if (tabName === 'gis' || tabName === 'all') {
            setTimeout(() => {
                if (typeof mapBanten !== 'undefined') {
                    mapBanten.invalidateSize();
                }
            }, 200);
        }
    }

    document.querySelectorAll('.exec-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchDashboardTab(tabName);
        });
    });

    // Quick Date Presets
    document.querySelectorAll('.date-quick-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const days = parseInt(this.getAttribute('data-days')) || 0;
            const d = new Date();
            d.setDate(d.getDate() - days);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            const dateInput = document.getElementById('filterTanggalInput');
            if (dateInput) {
                dateInput.value = `${yyyy}-${mm}-${dd}`;
                dateInput.closest('form').submit();
            }
        });
    });

    // Client-side Instant Table Search
    document.querySelectorAll('.table-search-input').forEach(input => {
        input.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            const tableSelector = this.getAttribute('data-table');
            const targetTable = document.querySelector(tableSelector);
            if (!targetTable) return;
            const rows = targetTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    // Smooth Count-Up Animation
    function animateCountUp() {
        const counters = document.querySelectorAll('.count-up');
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-target')) || 0;
            const isPercent = counter.textContent.includes('%');
            const duration = 1000;
            const startTime = performance.now();

            function step(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 4);
                const current = target * easeOut;

                if (isPercent) {
                    counter.textContent = current.toFixed(1) + '%';
                } else {
                    counter.textContent = formatNumber(Math.round(current));
                }

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    if (isPercent) {
                        counter.textContent = target.toFixed(1) + '%';
                    } else {
                        counter.textContent = formatNumber(Math.round(target));
                    }
                }
            }
            requestAnimationFrame(step);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(animateCountUp, 150);
    });

    // Chart Klasifikasi
    @if(!empty($penghuniByKlasifikasi) && $penghuniByKlasifikasi->isNotEmpty())
    (function() {
        const labels = {!! json_encode($penghuniByKlasifikasi->pluck('klasifikasi_pidana')) !!};
        const dataCounts = {!! json_encode($penghuniByKlasifikasi->pluck('total')) !!};
        const colors = [
            '#00f2fe', '#6366f1', '#3b82f6', '#8b5cf6', '#10b981', 
            '#f59e0b', '#f43f5e', '#ec4899', '#14b8a6', '#f97316', '#64748b'
        ];
        const total = dataCounts.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        const ctxEl = document.getElementById('chartKlasifikasi');
        if (!ctxEl) return;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        new Chart(ctxEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Penghuni',
                    data: dataCounts,
                    backgroundColor: colors,
                    borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                    borderWidth: 1,
                    borderColor: isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.08)',
                    maxBarThickness: 34
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 900,
                    easing: 'easeOutQuart'
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const val = context.parsed.y;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return 'Jumlah: ' + formatNumber(val) + ' orang (' + pct + '%)';
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        offset: 2,
                        color: function() {
                            return document.documentElement.getAttribute('data-theme') === 'dark' ? '#38bdf8' : '#0284c7';
                        },
                        font: { family: 'Rajdhani', weight: '700', size: 12 },
                        formatter: function(value) {
                            return formatNumber(value);
                        }
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { 
                            color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(148, 163, 184, 0.12)' 
                        },
                        ticks: { 
                            font: { family: 'Rajdhani', size: 11, weight: '600' },
                            callback: function(value) { return formatNumber(value); }
                        } 
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { 
                            maxRotation: 35, 
                            minRotation: 35, 
                            font: { family: 'Plus Jakarta Sans', size: 10, weight: '600' },
                            color: isDark ? '#94a3b8' : '#64748b'
                        } 
                    } 
                }
            },
            plugins: [ChartDataLabels]
        });

        // Populate Modern Telemetry Legend
        const legendContainer = document.getElementById('klasifikasiLegend');
        if (legendContainer) {
            let itemsHtml = '';
            labels.forEach((label, i) => {
                const count = dataCounts[i];
                const percent = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                const col = colors[i % colors.length];
                itemsHtml += `
                    <div class="telemetry-legend-item">
                        <div class="d-flex align-items-center text-truncate me-2">
                            <span class="telemetry-legend-color" style="background: ${col}; box-shadow: 0 0 6px ${col}88;"></span>
                            <span class="telemetry-legend-name" title="${label}">${label}</span>
                        </div>
                        <div class="d-flex align-items-center ms-auto">
                            <span class="telemetry-legend-val">${formatNumber(count)}</span>
                            <span class="telemetry-legend-pct">(${percent}%)</span>
                        </div>
                    </div>
                `;
            });
            legendContainer.innerHTML = `<div class="telemetry-legend-grid">${itemsHtml}</div>`;
        }
    })();
    @endif

    // High-Tech Doughnut Chart Distribusi Narapidana & Tahanan
    const ctxNapiTahanan = document.getElementById('chartNarapidanaTahanan');
    if (ctxNapiTahanan) {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        new Chart(ctxNapiTahanan, {
            type: 'doughnut',
            data: {
                labels: ['Narapidana', 'Tahanan'],
                datasets: [{
                    data: [{{ $stats['total_narapidana'] }}, {{ $stats['total_tahanan'] }}],
                    backgroundColor: ['#6366f1', '#f43f5e'],
                    hoverBackgroundColor: ['#818cf8', '#fb7185'],
                    borderWidth: 3,
                    borderColor: isDark ? 'rgba(13, 22, 42, 0.9)' : '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + formatNumber(context.parsed) + ' (' + percentage + '%)';
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { family: 'Rajdhani', weight: '700', size: 12.5 },
                        formatter: function(value, context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return percentage + '%';
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    // Overstaying Donut Charts
    @foreach ($overstayingStats as $key => $stat)
    (function() {
        const ctx = document.getElementById('chartOverstaying{{ strtoupper($key) }}');
        if (!ctx) return;
        
        const normal = {{ $stat['normal'] }};
        const overstaying = {{ $stat['overstaying'] }};
        
        if (normal === 0 && overstaying === 0) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Tidak Ada Data'],
                    datasets: [{ data: [1], backgroundColor: ['#e2e8f0'], borderWidth: 0 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '60%',
                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Normal', 'Overstaying'],
                    datasets: [{
                        data: [normal, overstaying],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '60%',
                    animation: { animateRotate: true, animateScale: true, duration: 800 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 8,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    })();
    @endforeach

    // Umur & Gender Data Chart
    const umurGenderData = {!! json_encode($umurGenderData) !!};
    const intDewasaLaki = parseInt(umurGenderData?.dewasa_laki) || 0;
    const intAnakLaki = parseInt(umurGenderData?.anak_laki) || 0;
    const intDewasaPerempuan = parseInt(umurGenderData?.dewasa_perempuan) || 0;
    const intAnakPerempuan = parseInt(umurGenderData?.anak_perempuan) || 0;
    const totalUmurGender = intDewasaLaki + intAnakLaki + intDewasaPerempuan + intAnakPerempuan;

    function getLegendText(val) {
        const percent = totalUmurGender > 0 ? ((val / totalUmurGender) * 100).toFixed(1) : 0;
        return formatNumber(val) + ' (' + percent + '%)';
    }

    if (document.getElementById('legend-dewasa-laki')) {
        document.getElementById('legend-dewasa-laki').textContent = getLegendText(intDewasaLaki);
        document.getElementById('legend-anak-laki').textContent = getLegendText(intAnakLaki);
        document.getElementById('legend-dewasa-perempuan').textContent = getLegendText(intDewasaPerempuan);
        document.getElementById('legend-anak-perempuan').textContent = getLegendText(intAnakPerempuan);
    }

    const ctxUmurGender = document.getElementById('chartUmurGender');
    if (ctxUmurGender) {
        new Chart(ctxUmurGender, {
            type: 'bar',
            data: {
                labels: ['Dewasa', 'Anak'],
                datasets: [
                    {
                        label: 'Laki-laki',
                        data: [intDewasaLaki, intAnakLaki],
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    },
                    {
                        label: 'Perempuan',
                        data: [intDewasaPerempuan, intAnakPerempuan],
                        backgroundColor: '#ec4899',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatNumber(context.parsed.y) + ' orang';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: { callback: function(value) { return formatNumber(value); } }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Gender Distribution Chart
    const totalLaki = intDewasaLaki + intAnakLaki;
    const totalPerempuan = intDewasaPerempuan + intAnakPerempuan;
    const totalGender = totalLaki + totalPerempuan;

    const ctxGenderDist = document.getElementById('chartGenderDistribution');
    if (ctxGenderDist) {
        new Chart(ctxGenderDist, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [totalLaki, totalPerempuan],
                    backgroundColor: ['#3b82f6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        callbacks: {
                            label: function(context) {
                                const percentage = totalGender > 0 ? ((context.parsed / totalGender) * 100).toFixed(1) : 0;
                                return context.label + ': ' + formatNumber(context.parsed) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('genderLegendContainer')) {
        const persenLaki = totalGender > 0 ? ((totalLaki / totalGender) * 100).toFixed(1) : 0;
        const persenPerempuan = totalGender > 0 ? ((totalPerempuan / totalGender) * 100).toFixed(1) : 0;
        document.getElementById('genderLegendContainer').innerHTML = `
            <table class="table table-sm table-borderless mb-0" style="font-size: 11px;">
                <tbody>
                    <tr>
                        <td style="width: 15px;"><span style="display:inline-block;width:10px;height:10px;background:#3b82f6;border-radius:2px;"></span></td>
                        <td>Laki-laki</td>
                        <td class="text-end fw-bold">${formatNumber(totalLaki)}</td>
                        <td class="text-end text-muted">(${persenLaki}%)</td>
                    </tr>
                    <tr>
                        <td><span style="display:inline-block;width:10px;height:10px;background:#ec4899;border-radius:2px;"></span></td>
                        <td>Perempuan</td>
                        <td class="text-end fw-bold">${formatNumber(totalPerempuan)}</td>
                        <td class="text-end text-muted">(${persenPerempuan}%)</td>
                    </tr>
                    <tr class="border-top">
                        <td colspan="2" class="fw-bold">Total</td>
                        <td class="text-end fw-bold">${formatNumber(totalGender)}</td>
                        <td class="text-end text-muted">(100%)</td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    // Leaflet GIS Map Initialization
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Esri World Imagery'
    });

    const hybridLayer = L.layerGroup([
        satelliteLayer,
        L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}')
    ]);

    const mapBanten = L.map('mapBanten', {
        layers: [hybridLayer]
    }).setView([-6.4409, 106.1385], 9);

    const btnStreet = document.getElementById('btnStreet');
    const btnSatellite = document.getElementById('btnSatellite');
    const btnHybrid = document.getElementById('btnHybrid');

    function updateActiveMapBtn(activeBtn) {
        [btnStreet, btnSatellite, btnHybrid].forEach(btn => btn?.classList.remove('active'));
        activeBtn?.classList.add('active');
    }

    btnStreet?.addEventListener('click', function() {
        mapBanten.removeLayer(satelliteLayer);
        mapBanten.removeLayer(hybridLayer);
        streetLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    btnSatellite?.addEventListener('click', function() {
        mapBanten.removeLayer(streetLayer);
        mapBanten.removeLayer(hybridLayer);
        satelliteLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    btnHybrid?.addEventListener('click', function() {
        mapBanten.removeLayer(streetLayer);
        mapBanten.removeLayer(satelliteLayer);
        hybridLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    // Markers with glowing status pin
    const uptLocations = {!! json_encode($uptLocations) !!};
    const markers = [];

    uptLocations.forEach(upt => {
        const totalPenghuni = parseInt(upt.total_penghuni) || 0;
        const kapasitas = parseInt(upt.kapasitas) || 0;
        const persen = kapasitas > 0 ? Math.round(((totalPenghuni - kapasitas) / kapasitas) * 100) : 0;
        const persenText = persen > 0 ? '+' + persen + '%' : persen + '%';
        
        let markerColor = '#10b981';
        let statusBadge = 'Longgar';
        if (persen > 50) { markerColor = '#f43f5e'; statusBadge = 'Sangat Over'; }
        else if (persen > 0) { markerColor = '#f59e0b'; statusBadge = 'Sedikit Over'; }

        const markerHtml = `
            <div style="background: ${markerColor}; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 0 10px ${markerColor}99; border: 2px solid white;">
                <i class="fas fa-building" style="font-size: 11px;"></i>
            </div>
        `;

        const customIcon = L.divIcon({
            className: 'custom-upt-marker',
            html: markerHtml,
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });

        if (upt.latitude && upt.longitude) {
            const marker = L.marker([parseFloat(upt.latitude), parseFloat(upt.longitude)], { icon: customIcon })
                .addTo(mapBanten)
                .bindPopup(`
                    <div style="min-width: 220px; font-family: var(--font-ui, sans-serif);">
                        <div style="font-weight: 700; color: #38bdf8; margin-bottom: 4px; font-size: 13px;">
                            ${upt.nama_upt}
                        </div>
                        <div style="font-size: 11px; color: #94a3b8; margin-bottom: 8px;">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> ${upt.alamat || 'Provinsi Banten'}
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11.5px; margin-bottom: 3px;">
                            <span style="opacity: 0.8;">Kapasitas:</span> <strong class="font-speedometer" style="font-size: 13px;">${formatNumber(kapasitas)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11.5px; margin-bottom: 3px;">
                            <span style="opacity: 0.8;">Penghuni:</span> <strong class="font-speedometer" style="font-size: 13px;">${formatNumber(totalPenghuni)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11.5px; margin-top: 5px; padding-top: 5px; border-top: 1px dashed rgba(148, 163, 184, 0.3);">
                            <span style="opacity: 0.8;">Status:</span> <strong class="font-speedometer" style="color: ${markerColor}; font-size: 13px;">${persenText} (${statusBadge})</strong>
                        </div>
                    </div>
                `);
            markers.push(marker);
        }
    });

    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        mapBanten.fitBounds(group.getBounds().pad(0.1));
    }

    setTimeout(() => { mapBanten.invalidateSize(); }, 400);

    @if($user && $pengumumanPopup->isNotEmpty())
    document.addEventListener('DOMContentLoaded', function() {
        const pengumumanModalEl = document.getElementById('pengumumanPopupModal');
        if (pengumumanModalEl) {
            const pengumumanModal = new bootstrap.Modal(pengumumanModalEl);
            pengumumanModal.show();
        }
    });
    @endif

    document.addEventListener('DOMContentLoaded', function() {
        if (!sessionStorage.getItem('welcomeShown') && {{ !empty($instansi->welcome_banner) ? 'true' : 'false' }}) {
            const welcomeModalEl = document.getElementById('welcomeModal');
            if (welcomeModalEl) {
                const welcomeModal = new bootstrap.Modal(welcomeModalEl);
                setTimeout(() => welcomeModal.show(), 1000);
                sessionStorage.setItem('welcomeShown', 'true');
            }
        }
    });
</script>
@endpush
