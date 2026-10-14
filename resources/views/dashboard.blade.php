@extends('layouts.app')

@section('title', 'Dashboard - Sultan Banten')
@section('page_title', 'Dashboard')

@push('styles')
<!-- Leaflet CSS (Non-blocking preload, GIS map is in secondary tab) -->
<link rel="preload" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" as="style" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""></noscript>
<!-- Dashboard Modern Styles -->
<link href="{{ asset('assets/css/dashboard-page.css') }}?v={{ file_exists(public_path('assets/css/dashboard-page.css')) ? filemtime(public_path('assets/css/dashboard-page.css')) : time() }}" rel="stylesheet">
@endpush

@section('content')
<div class="content-wrapper">

    <!-- Executive Glass Filter Bar -->
    <div class="exec-filter-bar mb-3">
        <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-auto d-flex align-items-center flex-wrap gap-1 mb-2 mb-md-0">
                <span class="text-secondary small fw-bold me-1"><i class="fas fa-calendar-alt text-primary me-1"></i>Preset:</span>
                <button type="button" class="date-quick-btn {{ $filterTanggal == date('Y-m-d') ? 'active' : '' }}" data-days="0">Hari Ini</button>
                <button type="button" class="date-quick-btn {{ $filterTanggal == date('Y-m-d', strtotime('-1 day')) ? 'active' : '' }}" data-days="1">Kemarin</button>
                <button type="button" class="date-quick-btn {{ $filterTanggal == date('Y-m-d', strtotime('-7 days')) ? 'active' : '' }}" data-days="7">7 Hari Lalu</button>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <input type="date" id="filterTanggalInput" name="tanggal" class="form-control form-control-sm" value="{{ $filterTanggal }}" aria-label="Filter Tanggal Laporan">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <select name="upt_id" class="form-select form-select-sm" aria-label="Filter Berdasarkan Satuan Kerja UPT">
                    <option value="0">-- Seluruh UPT Banten --</option>
                    @foreach ($uptList as $upt)
                    <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm flex-grow-1 flex-md-grow-0" style="border-radius: 20px; font-weight: 600;">
                    <i class="fas fa-filter me-1"></i> Terapkan
                </button>
                @if($filterUpt > 0 || $filterTanggal != date('Y-m-d'))
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px;" title="Reset Filter" aria-label="Reset Filter Tanggal dan UPT">
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
            $totalPenghuni = (int) ($stats['total_penghuni'] ?? 0);
            $pctTahanan = $totalPenghuni > 0 ? round((($stats['total_tahanan'] ?? 0) / $totalPenghuni) * 100, 1) : 0;
            $pctNapi = $totalPenghuni > 0 ? round((($stats['total_narapidana'] ?? 0) / $totalPenghuni) * 100, 1) : 0;
            $grandTotalPerkara = (($stats['total_pidum'] ?? 0) + ($stats['total_pidsus'] ?? 0)) ?: 1;
            $pctPidum = round((($stats['total_pidum'] ?? 0) / $grandTotalPerkara) * 100, 1);
            $pctPidsus = round((($stats['total_pidsus'] ?? 0) / $grandTotalPerkara) * 100, 1);
            
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

        <!-- Bento Cockpit Center Grid (Speedometer Diapit di Tengah) -->
        <div class="bento-hero-grid bento-cockpit-grid">
            
            <!-- LEFT FLANK: 3 METRIC TILES (12 UPT dengan data, Total Tahanan, Total Narapidana) -->
            <div class="bento-flank flank-left">
                <!-- Tile 1: 12 UPT dengan Data -->
                <div class="speedo-flank-card" title="12 UPT Pemasyarakatan se-Banten">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.25) 100%); color: #10b981;">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <span class="bento-stat-label d-block">12 UPT dengan Data</span>
                                <span class="small text-muted" style="font-size: 10px;">Monitoring Aktif</span>
                            </div>
                        </div>
                        <span class="pill-badge pill-badge-emerald" style="font-size: 9.5px; padding: 3px 8px;">
                            <i class="fas fa-check-circle me-1" style="font-size: 9px;"></i>12/12
                        </span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between my-1">
                        <div class="bento-stat-value font-speedometer count-up text-success" data-target="{{ $stats['total_upt'] }}">
                            {{ number_format($stats['total_upt'], 0, ',', '.') }}
                        </div>
                        <span class="font-speedometer small fw-bold text-success" style="font-size: 11px;">100% SINKRON</span>
                    </div>
                    <div class="speedo-telemetry-track mt-1" title="Kepatuhan Sinkronisasi: 100%">
                        <div class="speedo-telemetry-fill" style="width: 100%; background: linear-gradient(90deg, #10b981, #34d399); --bar-glow: #10b981;"></div>
                    </div>
                </div>

                <!-- Tile 2: Total Tahanan -->
                <div class="speedo-flank-card" title="Total Tahanan: {{ number_format($stats['total_tahanan'], 0, ',', '.') }}">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.25) 100%); color: #3b82f6;">
                                <i class="fas fa-user-lock"></i>
                            </div>
                            <div>
                                <span class="bento-stat-label d-block">Total Tahanan</span>
                                <span class="small text-muted" style="font-size: 10px;">Proses Peradilan</span>
                            </div>
                        </div>
                        <span class="pill-badge pill-badge-indigo" style="font-size: 9.5px; padding: 3px 8px;">Tahanan</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between my-1">
                        <div class="bento-stat-value font-speedometer count-up" data-target="{{ $stats['total_tahanan'] }}">
                            {{ number_format($stats['total_tahanan'], 0, ',', '.') }}
                        </div>
                        <span class="font-speedometer small fw-bold text-primary" style="font-size: 11.5px;">{{ $pctTahanan }}% Porsi</span>
                    </div>
                    <div class="speedo-telemetry-track mt-1" title="Porsi Tahanan: {{ $pctTahanan }}%">
                        <div class="speedo-telemetry-fill" style="width: {{ min(100, max(5, $pctTahanan)) }}%; background: linear-gradient(90deg, #3b82f6, #60a5fa); --bar-glow: #3b82f6;"></div>
                    </div>
                </div>

                <!-- Tile 3: Total Narapidana -->
                <div class="speedo-flank-card" title="Total Narapidana: {{ number_format($stats['total_narapidana'], 0, ',', '.') }}">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(244, 63, 94, 0.15) 0%, rgba(225, 29, 72, 0.25) 100%); color: #f43f5e;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <span class="bento-stat-label d-block">Total Narapidana</span>
                                <span class="small text-muted" style="font-size: 10px;">Vonis Hakim</span>
                            </div>
                        </div>
                        <span class="pill-badge pill-badge-rose" style="font-size: 9.5px; padding: 3px 8px;">Narapidana</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between my-1">
                        <div class="bento-stat-value font-speedometer count-up" data-target="{{ $stats['total_narapidana'] }}">
                            {{ number_format($stats['total_narapidana'], 0, ',', '.') }}
                        </div>
                        <span class="font-speedometer small fw-bold text-danger" style="font-size: 11.5px;">{{ $pctNapi }}% Porsi</span>
                    </div>
                    <div class="speedo-telemetry-track mt-1" title="Porsi Narapidana: {{ $pctNapi }}%">
                        <div class="speedo-telemetry-fill" style="width: {{ min(100, max(5, $pctNapi)) }}%; background: linear-gradient(90deg, #f43f5e, #fb7185); --bar-glow: #f43f5e;"></div>
                    </div>
                </div>
            </div>

            <!-- CENTER COCKPIT: DUAL SPEEDOMETER HERO CARD -->
            <div class="bento-card bento-card-hero speedo-cockpit-center">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.15) 0%, rgba(2, 132, 199, 0.25) 100%); color: #0284c7;">
                                <i class="fas fa-gauge-high"></i>
                            </div>
                            <div>
                                <span class="fw-bold" style="font-size: 13px; color: var(--text-primary); letter-spacing: 0.02em;">Indikator Kapasitas</span>
                                <span class="d-block text-secondary small" style="font-size: 10px;">Monitoring Hunian & Beban</span>
                            </div>
                        </div>
                        <span class="pill-badge {{ $statusBadgeClass }}" style="font-size: 11.5px; padding: 5px 12px; font-weight: 600;">
                            <span class="live-beacon me-1" style="background-color: {{ $statusColor }};">
                                <span class="beacon-ping"></span>
                                <span class="beacon-dot"></span>
                            </span>
                            {{ $statusText }}
                        </span>
                    </div>

                    <!-- Dual Speedometer Cockpit Cluster -->
                    @php
                        $arcLen = 251.32; // PI * R (80)
                        
                        // Left Dial: Tingkat Hunian (0% - 100%+)
                        $clampedHunian = min(100, max(0, $tingkatVal));
                        $arcOffsetHunian = $arcLen - ($arcLen * $clampedHunian / 100);

                        // Right Dial: % Overkapasitas (0% - 100%+)
                        $clampedOver = min(100, max(0, $persenOver));
                        $arcOffsetOver = $arcLen - ($arcLen * $clampedOver / 100);
                    @endphp
                    <div class="speedo-cluster-wrap my-2 mb-3">
                        <!-- DIAL 1: TINGKAT HUNIAN -->
                        <div class="speedo-dial-box" title="Tingkat Hunian: {{ $tingkatVal }}%">
                            <svg class="speedo-gauge-svg" viewBox="0 0 200 125">
                                <defs>
                                    <linearGradient id="speedoGradHunian" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#00f2fe"/>
                                        <stop offset="100%" stop-color="#0284c7"/>
                                    </linearGradient>
                                </defs>
                                <path class="speedo-track" d="M 20 105 A 80 80 0 0 1 180 105" />
                                <path class="speedo-fill" d="M 20 105 A 80 80 0 0 1 180 105"
                                      stroke="url(#speedoGradHunian)"
                                      stroke-dasharray="251.32"
                                      stroke-dashoffset="{{ $arcOffsetHunian }}"
                                      style="--gauge-glow: rgba(0, 242, 254, 0.6);" />
                            </svg>
                            
                            <div class="speedo-gauge-content">
                                <div class="speedo-cluster-val font-speedometer count-up" data-target="{{ $tingkatVal }}" style="color: #0284c7;">
                                    {{ $tingkatVal }}%
                                </div>
                                <div class="speedo-cluster-sub">Tingkat Hunian</div>
                            </div>

                            <div class="speedo-ticks">
                                <span class="speedo-tick-item tick-hunian-min" title="Batas Minimum (0%)">
                                    <span class="tick-pip"></span>
                                    <span class="tick-label">0%</span>
                                </span>
                                <span class="speedo-tick-item tick-mid" title="Batas Normal (50%)">
                                    <span class="tick-pip"></span>
                                    <span class="tick-label">50%</span>
                                </span>
                                <span class="speedo-tick-item tick-hunian-max" title="Kapasitas Penuh (100%)">
                                    <span class="tick-pip"></span>
                                    <span class="tick-label">100%</span>
                                </span>
                            </div>
                        </div>

                        <!-- CLUSTER DIVIDER -->
                        <div class="speedo-cluster-divider"></div>

                        <!-- DIAL 2: % OVERKAPASITAS -->
                        <div class="speedo-dial-box" title="Overkapasitas: {{ $persenOver }}%">
                            <svg class="speedo-gauge-svg" viewBox="0 0 200 125">
                                <defs>
                                    <linearGradient id="speedoGradOver" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="{{ $statusColor }}99"/>
                                        <stop offset="100%" stop-color="{{ $statusColor }}"/>
                                    </linearGradient>
                                </defs>
                                <path class="speedo-track" d="M 20 105 A 80 80 0 0 1 180 105" />
                                <path class="speedo-fill" d="M 20 105 A 80 80 0 0 1 180 105"
                                      stroke="url(#speedoGradOver)"
                                      stroke-dasharray="251.32"
                                      stroke-dashoffset="{{ $arcOffsetOver }}"
                                      style="--gauge-glow: {{ $statusColor }}77;" />
                            </svg>
                            
                            <div class="speedo-gauge-content">
                                <div class="speedo-cluster-val font-speedometer count-up" data-prefix="+" data-target="{{ $persenOver }}" style="color: {{ $statusColor }};">
                                    {{ $persenOver > 0 ? '+' : '' }}{{ $persenOver }}%
                                </div>
                                <div class="speedo-cluster-sub">Over Kapasitas</div>
                            </div>

                            <div class="speedo-ticks">
                                <span class="speedo-tick-item tick-over-min" title="Batas Aman (0%)">
                                    <span class="tick-pip"></span>
                                    <span class="tick-label">0%</span>
                                </span>
                                <span class="speedo-tick-item tick-mid" title="Ambang Waspada (50%)">
                                    <span class="tick-pip"></span>
                                    <span class="tick-label">50%</span>
                                </span>
                                <span class="speedo-tick-item tick-over-max" title="Kritis (100%+) - Over">
                                    <span class="tick-pip"></span>
                                    <span class="tick-label">100%+</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 speedo-bottom-telemetry" style="border-color: var(--bento-border) !important;">
                    <div class="speedo-bottom-stat">
                        <span class="text-secondary small d-block text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">Total Penghuni</span>
                        <strong class="count-up font-speedometer" data-target="{{ $stats['total_penghuni'] }}" style="font-size: 20px; color: var(--text-primary);">
                            {{ number_format($stats['total_penghuni'], 0, ',', '.') }}
                        </strong>
                    </div>
                    <div class="speedo-bottom-stat text-center px-1">
                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 0.05em;">Status</div>
                        <span class="badge {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }} px-2 py-1" style="font-size: 10.5px; border-radius: 12px; font-weight: 600;">
                            {{ $persenOver > 0 ? 'Overkapasitas' : 'Normal' }}
                        </span>
                    </div>
                    <div class="speedo-bottom-stat text-end">
                        <span class="text-secondary small d-block text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">Total Kapasitas</span>
                        <strong class="count-up font-speedometer" data-target="{{ $stats['total_kapasitas'] }}" style="font-size: 20px; color: var(--text-primary);">
                            {{ number_format($stats['total_kapasitas'], 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- RIGHT FLANK: 3 METRIC TILES (Total Pidum, Total Pidsus, Overstaying) -->
            <div class="bento-flank flank-right">
                <!-- Tile 4: Total Pidum -->
                <div class="speedo-flank-card" title="Total Pidana Umum: {{ number_format($stats['total_pidum'], 0, ',', '.') }}">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(124, 58, 237, 0.25) 100%); color: #8b5cf6;">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <div>
                                <span class="bento-stat-label d-block">Total Pidum</span>
                                <span class="small text-muted" style="font-size: 10px;">Pidana Umum</span>
                            </div>
                        </div>
                        <span class="pill-badge pill-badge-purple" style="font-size: 9.5px; padding: 3px 8px;">Pidum</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between my-1">
                        <div class="bento-stat-value font-speedometer count-up" data-target="{{ $stats['total_pidum'] }}">
                            {{ number_format($stats['total_pidum'], 0, ',', '.') }}
                        </div>
                        <span class="font-speedometer small fw-bold" style="font-size: 11.5px; color: #a855f7;">{{ $pctPidum }}% Share</span>
                    </div>
                    <div class="speedo-telemetry-track mt-1" title="Share Pidum: {{ $pctPidum }}%">
                        <div class="speedo-telemetry-fill" style="width: {{ min(100, max(5, $pctPidum)) }}%; background: linear-gradient(90deg, #8b5cf6, #c084fc); --bar-glow: #8b5cf6;"></div>
                    </div>
                </div>

                <!-- Tile 5: Total Pidsus -->
                <div class="speedo-flank-card" title="Total Pidana Khusus: {{ number_format($stats['total_pidsus'], 0, ',', '.') }}">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(8, 145, 178, 0.25) 100%); color: #06b6d4;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <span class="bento-stat-label d-block">Total Pidsus</span>
                                <span class="small text-muted" style="font-size: 10px;">Pidana Khusus</span>
                            </div>
                        </div>
                        <span class="pill-badge pill-badge-cyan" style="font-size: 9.5px; padding: 3px 8px;">Pidsus</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between my-1">
                        <div class="bento-stat-value font-speedometer count-up" data-target="{{ $stats['total_pidsus'] }}">
                            {{ number_format($stats['total_pidsus'], 0, ',', '.') }}
                        </div>
                        <span class="font-speedometer small fw-bold text-info" style="font-size: 11.5px;">{{ $pctPidsus }}% Share</span>
                    </div>
                    <div class="speedo-telemetry-track mt-1" title="Share Pidsus: {{ $pctPidsus }}%">
                        <div class="speedo-telemetry-fill" style="width: {{ min(100, max(5, $pctPidsus)) }}%; background: linear-gradient(90deg, #06b6d4, #22d3ee); --bar-glow: #06b6d4;"></div>
                    </div>
                </div>

                <!-- Tile 6: Overstaying Alert -->
                <div class="speedo-flank-card" style="border-left: 3px solid #ef4444;" title="Tahanan Lewat Batas Waktu">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-stat-icon" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.25) 100%); color: #ef4444;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <span class="bento-stat-label d-block">Overstaying</span>
                                <span class="small text-muted" style="font-size: 10px;">Masa Tahanan Habis</span>
                            </div>
                        </div>
                        @if(($stats['total_overstaying'] ?? 0) > 0)
                        <span class="live-beacon" style="background-color: #ef4444;" title="Peringatan Terdeteksi">
                            <span class="beacon-ping"></span>
                            <span class="beacon-dot"></span>
                        </span>
                        @else
                        <span class="pill-badge pill-badge-emerald" style="font-size: 9.5px; padding: 3px 8px;">Nihil</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between my-1">
                        <div class="bento-stat-value text-danger font-speedometer count-up" data-target="{{ $stats['total_overstaying'] }}">
                            {{ number_format($stats['total_overstaying'], 0, ',', '.') }}
                        </div>
                        <span class="font-speedometer small fw-bold text-danger" style="font-size: 11px;">
                            {{ ($stats['total_overstaying'] ?? 0) > 0 ? 'PERINGATAN' : 'AMAN' }}
                        </span>
                    </div>
                    <div class="speedo-telemetry-track mt-1" title="Status Overstaying">
                        <div class="speedo-telemetry-fill" style="width: {{ ($stats['total_overstaying'] ?? 0) > 0 ? '100' : '0' }}%; background: linear-gradient(90deg, #ef4444, #f87171); --bar-glow: #ef4444;"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Quick Summary Snapshot: Kasus Khusus & Bebas Harian -->
        <div class="row g-2 mb-3">
            <!-- Card 1: Pidana Mati -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="snapshot-card bento-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="snapshot-card-label">Pidana Mati</span>
                        <span class="snapshot-card-icon" style="background: rgba(244, 63, 94, 0.14); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.25);">
                            <i class="fas fa-skull-crossbones"></i>
                        </span>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-danger count-up" data-target="{{ $stats['total_pidana_mati'] }}">
                        {{ number_format($stats['total_pidana_mati'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Card 2: Seumur Hidup -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="snapshot-card bento-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="snapshot-card-label">Seumur Hidup</span>
                        <span class="snapshot-card-icon" style="background: rgba(225, 29, 72, 0.14); color: #e11d48; border: 1px solid rgba(225, 29, 72, 0.25);">
                            <i class="fas fa-infinity"></i>
                        </span>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-danger count-up" data-target="{{ $stats['total_seumur_hidup'] }}">
                        {{ number_format($stats['total_seumur_hidup'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Card 3: WNA -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="snapshot-card bento-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="snapshot-card-label">WNA</span>
                        <span class="snapshot-card-icon" style="background: rgba(245, 158, 11, 0.14); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25);">
                            <i class="fas fa-globe"></i>
                        </span>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-warning count-up" data-target="{{ $stats['total_wna'] }}">
                        {{ number_format($stats['total_wna'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Card 4: Lansia > 70 Tahun -->
            <div class="col-6 col-sm-6 col-xl">
                <div class="snapshot-card bento-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="snapshot-card-label">Lansia > 70 Thn</span>
                        <span class="snapshot-card-icon" style="background: rgba(249, 115, 22, 0.14); color: #f97316; border: 1px solid rgba(249, 115, 22, 0.25);">
                            <i class="fas fa-user-clock"></i>
                        </span>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-warning count-up" data-target="{{ $stats['total_lansia'] }}">
                        {{ number_format($stats['total_lansia'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Card 5: Bebas Hari Ini -->
            <div class="col-12 col-sm-6 col-xl">
                <div class="snapshot-card bento-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="snapshot-card-label">Bebas Hari Ini</span>
                        <span class="snapshot-card-icon" style="background: rgba(16, 185, 129, 0.14); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);">
                            <i class="fas fa-door-open"></i>
                        </span>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-success count-up" data-target="{{ $stats['total_bebas'] }}">
                        {{ number_format($stats['total_bebas'], 0, ',', '.') }}
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
                <div class="chart-card-modern h-100 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-shrink-0">
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

                    <!-- High-Tech Cockpit Donut Ring Centered & Scaled to Frame -->
                    <div class="donut-chart-container position-relative d-flex align-items-center justify-content-center flex-grow-1 my-auto" style="min-height: 280px; max-height: 310px; width: 100%;">
                        <canvas id="chartNarapidanaTahanan"></canvas>
                        <div class="donut-center-telemetry">
                            <div class="donut-center-val font-speedometer count-up" data-target="{{ $stats['total_penghuni'] }}">
                                {{ number_format($stats['total_penghuni'], 0, ',', '.') }}
                            </div>
                            <div class="donut-center-sub">Total Jiwa</div>
                        </div>
                    </div>

                    <!-- Telemetry Ratio Breakdown Footer -->
                    <div class="d-flex justify-content-between align-items-center gap-2 pt-3 border-top mt-auto flex-shrink-0" style="border-color: var(--bento-border) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="donut-legend-pip" style="background: #6366f1; box-shadow: 0 0 10px rgba(99, 102, 241, 0.7); width: 12px; height: 12px;"></span>
                            <div>
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">Narapidana</div>
                                <div class="fw-bold font-speedometer" style="font-size: 16px; color: var(--text-primary);">
                                    {{ number_format($stats['total_narapidana']) }}
                                    <small class="text-secondary fw-bold font-speedometer" style="font-size: 12px;">({{ $napiRatio }}%)</small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-end">
                            <div>
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">Tahanan</div>
                                <div class="fw-bold font-speedometer" style="font-size: 16px; color: var(--text-primary);">
                                    {{ number_format($stats['total_tahanan']) }}
                                    <small class="text-secondary fw-bold font-speedometer" style="font-size: 12px;">({{ round(100 - $napiRatio, 1) }}%)</small>
                                </div>
                            </div>
                            <span class="donut-legend-pip" style="background: #f43f5e; box-shadow: 0 0 10px rgba(244, 63, 94, 0.7); width: 12px; height: 12px;"></span>
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
        <div class="chart-card-modern mb-3 map-command-card">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="section-header command-section-title mb-0">
                    <i class="fas fa-satellite-dish text-primary me-2"></i>Peta Lokasi UPT di Banten
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap mt-2 mt-sm-0">
                    <button type="button" class="btn btn-sm hud-control-btn" id="btnFitBounds" title="Pusatkan radar ke titik tengah persebaran UPT" aria-label="Pusatkan radar ke titik tengah persebaran UPT">
                        <i class="fas fa-crosshairs text-primary"></i>
                        <span>Pusatkan Peta</span>
                    </button>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Pilihan Gaya Peta">
                        <button type="button" class="btn btn-outline-primary" id="btnDark" title="Tampilan Cyber Dark Mode" aria-label="Tampilan Peta Dark HUD">Dark HUD</button>
                        <button type="button" class="btn btn-outline-primary" id="btnStreet" title="Tampilan Peta Jalan OpenStreetMap" aria-label="Tampilan Peta Jalan Street">Street</button>
                        <button type="button" class="btn btn-outline-primary active" id="btnHybrid" title="Tampilan Citra Satelit Esri" aria-label="Tampilan Citra Satelit">Satelit</button>
                    </div>
                </div>
            </div>

            <!-- Interactive Filter Chips -->
            <div class="map-filter-group mb-2" id="mapUptFilterGroup">
                <span class="text-xs text-muted me-1 fw-semibold"><i class="fas fa-filter text-primary me-1"></i>Filter Radar:</span>
                <button type="button" class="map-filter-chip active" data-filter="all">Semua ({{ count($uptLocations) }})</button>
                <button type="button" class="map-filter-chip" data-filter="lapas">Lapas</button>
                <button type="button" class="map-filter-chip" data-filter="rutan">Rutan</button>
                <button type="button" class="map-filter-chip" data-filter="lpka_bapas">LPKA / Terbuka</button>
                <button type="button" class="map-filter-chip" data-filter="over">Overkapasitas</button>
            </div>

            <div id="mapBanten" style="height: 440px; width: 100%; border-radius: 14px; z-index: 1;"></div>

            <div class="d-flex align-items-center justify-content-between mt-2 flex-wrap gap-2 text-muted small">
                <div class="d-flex align-items-center gap-2">
                    <span class="live-pulse"></span>
                    <span><i class="fas fa-info-circle text-primary me-1"></i>Klik radar UPT untuk membuka HUD & rute Google Maps</span>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap" style="font-size: 10px;">
                    <span class="pill-badge pill-badge-emerald"><i class="fas fa-circle text-xs me-1"></i>Normal / Longgar (&le;100%)</span>
                    <span class="pill-badge pill-badge-amber"><i class="fas fa-circle text-xs me-1"></i>Waspada (101-150%)</span>
                    <span class="pill-badge pill-badge-rose"><i class="fas fa-circle text-xs me-1"></i>Kritis (&gt;150%)</span>
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
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }} &bull; Klik alamat untuk rute navigasi</small>
                </div>
                
                <!-- Instant Search Box -->
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tableOverkapasitas" placeholder="Cari nama UPT / kota..." aria-label="Cari nama UPT atau kota di tabel overkapasitas">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tableOverkapasitas" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-2" style="width: 35px;">No</th>
                            <th class="py-2" style="min-width: 170px;">Unit Pelaksana Teknis & Alamat</th>
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
                                $mapsUrl = ($row->latitude && $row->longitude)
                                    ? "https://www.google.com/maps/search/?api=1&query={$row->latitude},{$row->longitude}"
                                    : (!empty($row->alamat) ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($row->nama_upt . ' ' . $row->alamat) : null);
                            @endphp
                            <tr>
                                <td class="text-center py-2 font-speedometer">{{ $no++ }}</td>
                                <td class="py-2">
                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                        <span class="fw-semibold" style="color: var(--text-primary); font-size: 11.5px;">{{ $row->nama_upt }}</span>
                                        @if($row->latitude && $row->longitude)
                                            <button type="button" class="btn btn-focus-marker" data-upt-id="{{ $row->id }}" title="Fokuskan radar ke lokasi ini di peta" aria-label="Fokuskan radar ke lokasi {{ $row->nama_upt }} di peta">
                                                <i class="fas fa-crosshairs"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($mapsUrl)
                                        <div class="mt-1">
                                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="table-address-link" title="Buka di Google Maps / Aplikasi Navigasi">
                                                <i class="fas fa-map-marker-alt text-danger"></i>
                                                <span class="text-truncate" style="max-width: 170px;">{{ $row->alamat ?: 'Provinsi Banten' }}</span>
                                                <i class="fas fa-external-link-alt opacity-75" style="font-size: 8px;"></i>
                                            </a>
                                        </div>
                                    @elseif(!empty($row->alamat))
                                        <div class="small text-muted text-truncate mt-1" style="max-width: 180px;">
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>{{ $row->alamat }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center py-2 font-speedometer">{{ number_format($row->kapasitas) }}</td>
                                <td class="text-center py-2 font-speedometer">{{ number_format($row->total_tahanan) }}</td>
                                <td class="text-center py-2 font-speedometer">{{ number_format($row->total_narapidana) }}</td>
                                <td class="text-center py-2 font-speedometer"><strong>{{ number_format($row->total_penghuni) }}</strong></td>
                                <td class="py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 10px;">
                                        <strong class="font-speedometer" style="font-size: 11px;">{{ $tingkatHunianUpt }}%</strong>
                                    </div>
                                    <div class="table-bar-track">
                                        <div class="table-bar-fill" style="width: {{ $barWidth }}%; background-color: {{ $barColor }};"></div>
                                    </div>
                                </td>
                                <td class="text-center py-2 {{ $colorClass }}">
                                    <strong class="font-speedometer" style="font-size: 11px;">{{ $persen > 0 ? '+' : '' }}{{ $persen }}%</strong>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($stats['total_kapasitas']) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($stats['total_tahanan']) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($stats['total_narapidana']) }}</td>
                            <td class="text-center py-2 font-speedometer"><strong>{{ number_format($stats['total_penghuni']) }}</strong></td>
                            @php $total_tingkat_hunian = $stats['total_kapasitas'] > 0 ? round(($stats['total_penghuni'] / $stats['total_kapasitas']) * 100, 1) : 0; @endphp
                            <td class="text-center py-2 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}">
                                <strong class="font-speedometer" style="font-size: 11px;">{{ $total_tingkat_hunian }}%</strong>
                            </td>
                            <td class="text-center py-2 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}">
                                <strong class="font-speedometer" style="font-size: 11px;">{{ $stats['persen_overkapasitas'] > 0 ? '+' : '' }}{{ $stats['persen_overkapasitas'] }}%</strong>
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

        @php
            $grandTotalPerkara = (int)$stats['total_pidum'] + (int)$stats['total_pidsus'] + (int)$stats['total_uu_ite'];
            $pctPidum = $grandTotalPerkara > 0 ? round(($stats['total_pidum'] / $grandTotalPerkara) * 100, 1) : 0;
            $pctPidsus = $grandTotalPerkara > 0 ? round(($stats['total_pidsus'] / $grandTotalPerkara) * 100, 1) : 0;
            $pctUUIte = $grandTotalPerkara > 0 ? round(($stats['total_uu_ite'] / $grandTotalPerkara) * 100, 1) : 0;
        @endphp

        <!-- Klasifikasi Statistics Cards Header (Preserves 'Pidum dan Pidsus' exact string for tests) -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="section-header command-section-title mb-0">
                <i class="fas fa-balance-scale text-primary me-2"></i>Pidum dan Pidsus
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="live-pulse"></span>
                <span class="text-xs text-muted fw-semibold d-none d-sm-inline">Telemetri Klasifikasi Hukum & Perkara</span>
            </div>
        </div>

        <!-- 4 Primary Legal Pillars -->
        <div class="row mb-3 g-2">
            <!-- 1. Total Penghuni (Pidum + Pidsus + ITE) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="pillar-kpi-card pillar-total h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="pill-badge pill-badge-emerald" style="font-size: 9.5px;">Akumulasi Perkara</span>
                            <div class="pillar-label mt-1">Jumlah Penghuni (Pidum+Pidsus+ITE)</div>
                        </div>
                        <div class="pillar-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mt-auto">
                        <div class="pillar-val count-up" data-target="{{ $grandTotalPerkara }}">
                            {{ number_format($grandTotalPerkara, 0, ',', '.') }}
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle font-speedometer" style="font-size: 11px;">
                            Total Penghuni
                        </span>
                    </div>
                    <div class="table-bar-track mt-2">
                        <div class="table-bar-fill" style="width: 100%; background: linear-gradient(90deg, #10b981, #06b6d4);"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Pidana Umum -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="pillar-kpi-card pillar-pidum h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="pill-badge pill-badge-cyan" style="font-size: 9.5px;">Pidana Umum</span>
                            <div class="pillar-label mt-1">Total Pidum</div>
                        </div>
                        <div class="pillar-icon">
                            <i class="fas fa-gavel"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mt-auto">
                        <div class="pillar-val count-up" data-target="{{ $stats['total_pidum'] }}">
                            {{ number_format($stats['total_pidum'], 0, ',', '.') }}
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle font-speedometer" style="font-size: 11px;">
                            {{ $pctPidum }}% Share
                        </span>
                    </div>
                    <div class="table-bar-track mt-2">
                        <div class="table-bar-fill" style="width: {{ $pctPidum }}%; background: linear-gradient(90deg, #3b82f6, #60a5fa);"></div>
                    </div>
                </div>
            </div>

            <!-- 3. Pidana Khusus -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="pillar-kpi-card pillar-pidsus h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="pill-badge pill-badge-purple" style="font-size: 9.5px;">Pidana Khusus</span>
                            <div class="pillar-label mt-1">Total Pidsus</div>
                        </div>
                        <div class="pillar-icon">
                            <i class="fas fa-handcuffs"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mt-auto">
                        <div class="pillar-val count-up" data-target="{{ $stats['total_pidsus'] }}">
                            {{ number_format($stats['total_pidsus'], 0, ',', '.') }}
                        </div>
                        <span class="badge bg-purple bg-opacity-10 text-purple border border-purple-subtle font-speedometer" style="font-size: 11px; color: #a855f7; border-color: rgba(168, 85, 247, 0.3);">
                            {{ $pctPidsus }}% Share
                        </span>
                    </div>
                    <div class="table-bar-track mt-2">
                        <div class="table-bar-fill" style="width: {{ $pctPidsus }}%; background: linear-gradient(90deg, #8b5cf6, #c084fc);"></div>
                    </div>
                </div>
            </div>

            <!-- 4. UU ITE -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="pillar-kpi-card pillar-uu-ite h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="pill-badge pill-badge-cyan" style="font-size: 9.5px; background: rgba(6, 182, 212, 0.12); color: #06b6d4;">Cyber / Siber</span>
                            <div class="pillar-label mt-1">Total UU ITE</div>
                        </div>
                        <div class="pillar-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mt-auto">
                        <div class="pillar-val count-up" data-target="{{ $stats['total_uu_ite'] }}">
                            {{ number_format($stats['total_uu_ite'], 0, ',', '.') }}
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle font-speedometer" style="font-size: 11px;">
                            {{ $pctUUIte }}% Share
                        </span>
                    </div>
                    <div class="table-bar-track mt-2">
                        <div class="table-bar-fill" style="width: {{ max(4, $pctUUIte) }}%; background: linear-gradient(90deg, #06b6d4, #38bdf8);"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Snapshot Kasus Khusus, Kelompok Rentan & Vonis Berat -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="section-header command-section-title mb-0" style="font-size: 12px;">
                <i class="fas fa-shield-virus text-warning me-2"></i>Atensi Khusus, Kelompok Rentan & Vonis Berat
            </div>
            <span class="text-xs text-muted">5 Indikator Pengawasan Intensif</span>
        </div>

        <!-- 5 Snapshot Bento Cards Row -->
        <div class="row mb-3 g-2">
            <!-- WNA -->
            <div class="col-6 col-md-2-4">
                <div class="bento-card snapshot-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label">WNA</span>
                        <div class="snapshot-card-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                            <i class="fas fa-globe-americas"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value text-warning count-up" data-target="{{ $stats['total_wna'] }}">
                        {{ number_format($stats['total_wna'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Sakit Berkepanjangan -->
            <div class="col-6 col-md-2-4">
                <div class="bento-card snapshot-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label">Sakit Menahun</span>
                        <div class="snapshot-card-icon" style="background: rgba(244, 63, 94, 0.15); color: #f43f5e;">
                            <i class="fas fa-heart-pulse"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value text-danger count-up" data-target="{{ $stats['total_sakit'] }}">
                        {{ number_format($stats['total_sakit'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Lansia >70 Tahun -->
            <div class="col-6 col-md-2-4">
                <div class="bento-card snapshot-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label">Lansia >70 Thn</span>
                        <div class="snapshot-card-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value text-primary count-up" data-target="{{ $stats['total_lansia'] }}">
                        {{ number_format($stats['total_lansia'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Pidana Mati -->
            <div class="col-6 col-md-2-4">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #f43f5e;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-danger">Pidana Mati</span>
                        <div class="snapshot-card-icon" style="background: rgba(244, 63, 94, 0.2); color: #f43f5e;">
                            <i class="fas fa-skull-crossbones"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value text-danger count-up" data-target="{{ $stats['total_pidana_mati'] }}">
                        {{ number_format($stats['total_pidana_mati'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- Seumur Hidup -->
            <div class="col-12 col-md-2-4">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #8b5cf6;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-purple">Seumur Hidup</span>
                        <div class="snapshot-card-icon" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                            <i class="fas fa-infinity"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value text-purple count-up" data-target="{{ $stats['total_seumur_hidup'] }}">
                        {{ number_format($stats['total_seumur_hidup'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Penghuni Berdasarkan Jenis Kejahatan per UPT -->
        <div class="table-card-modern mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 14px;">
                        <i class="fas fa-table text-primary me-2"></i>Data Penghuni Berdasarkan Jenis Kejahatan per UPT
                    </h6>
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }} &bull; Matriks Klasifikasi Perkara Lengkap</small>
                </div>
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tableKejahatan" placeholder="Cari nama UPT..." aria-label="Cari nama UPT di tabel jenis kejahatan">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tableKejahatan" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="text-center align-middle py-2" style="width: 35px;">No</th>
                            <th rowspan="2" class="align-middle py-2" style="min-width: 150px;">Unit Pelaksana Teknis</th>
                            <th class="text-center py-1 th-group-pidum" style="min-width: 55px;">PIDUM</th>
                            <th colspan="9" class="text-center py-1 th-group-pidsus">TINDAK PIDANA KHUSUS (PIDSUS)</th>
                            <th class="text-center py-1 th-group-ite" style="min-width: 50px;">ITE</th>
                            <th colspan="5" class="text-center py-1 th-group-khusus">ATENSI KHUSUS & VONIS BERAT</th>
                        </tr>
                        <tr>
                            <th class="text-center th-sub-col th-group-pidum">Jml</th>
                            <th class="text-center th-sub-col">Tipikor</th>
                            <th class="text-center th-sub-col">Nrk Bdr</th>
                            <th class="text-center th-sub-col">Nrk Peng</th>
                            <th class="text-center th-sub-col">Teror</th>
                            <th class="text-center th-sub-col">Traff</th>
                            <th class="text-center th-sub-col">Ileg Log</th>
                            <th class="text-center th-sub-col">Money Lau</th>
                            <th class="text-center th-sub-col">Geno</th>
                            <th class="text-center th-sub-col th-group-pidsus fw-bold">Total</th>
                            <th class="text-center th-sub-col th-group-ite">Jml</th>
                            <th class="text-center th-sub-col">WNA</th>
                            <th class="text-center th-sub-col">Sakit</th>
                            <th class="text-center th-sub-col">Lansia</th>
                            <th class="text-center th-sub-col th-group-khusus text-danger fw-bold">Mati</th>
                            <th class="text-center th-sub-col th-group-khusus fw-bold text-purple">Seumur</th>
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
                                <td class="text-center py-2 font-speedometer">{{ $no++ }}</td>
                                <td class="py-2 fw-medium">{{ $row->nama_upt }}</td>
                                <td class="text-center py-2">
                                    @if((int)$row->pidum > 0)
                                        <span class="val-active">{{ number_format($row->pidum) }}</span>
                                        <div class="text-muted" style="font-size: 8.5px;">{{ $p_pidum }}%</div>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">{!! (int)$row->tipikor > 0 ? '<span class="val-active">' . number_format($row->tipikor) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->narkoba_bandar > 0 ? '<span class="val-active">' . number_format($row->narkoba_bandar) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->narkoba_pengguna > 0 ? '<span class="val-active">' . number_format($row->narkoba_pengguna) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->teroris > 0 ? '<span class="val-active text-danger fw-bold">' . number_format($row->teroris) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->trafficking > 0 ? '<span class="val-active">' . number_format($row->trafficking) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->ilegal_logging > 0 ? '<span class="val-active">' . number_format($row->ilegal_logging) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->money_laundering > 0 ? '<span class="val-active">' . number_format($row->money_laundering) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! (int)$row->genosida > 0 ? '<span class="val-active">' . number_format($row->genosida) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">
                                    @if($pidsusTotal > 0)
                                        <span class="badge-pidsus-total">{{ number_format($pidsusTotal) }}</span>
                                        <div class="text-purple" style="font-size: 8.5px;">({{ $p_pidsus }}%)</div>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if((int)$row->uu_ite > 0)
                                        <span class="val-active text-info">{{ number_format($row->uu_ite) }}</span>
                                        <div class="text-muted" style="font-size: 8.5px;">{{ $p_ite }}%</div>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">{!! $wna > 0 ? '<span class="val-active text-warning">' . number_format($wna) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! $sakit > 0 ? '<span class="val-active text-danger">' . number_format($sakit) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! $lansia > 0 ? '<span class="val-active text-primary">' . number_format($lansia) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! $pidana_mati > 0 ? '<span class="val-active text-danger fw-bold">' . number_format($pidana_mati) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                                <td class="text-center py-2">{!! $seumur_hidup > 0 ? '<span class="val-active fw-bold text-purple">' . number_format($seumur_hidup) . '</span>' : '<span class="val-zero">-</span>' !!}</td>
                            </tr>
                        @empty
                            <tr><td colspan="18" class="text-center py-3 text-muted">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        @php $statistikGrandTotal = $totalPidum + $totalPidsus + $totalUUITE; @endphp
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2 th-group-pidum">
                                <span class="font-speedometer" style="font-size: 12px;">{{ number_format($totalPidum) }}</span>
                                <div style="font-size: 8.5px;">{{ $statistikGrandTotal > 0 ? round(($totalPidum / $statistikGrandTotal) * 100, 1) : 0 }}%</div>
                            </td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalTipikor) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalNarkobaBandar) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalNarkobaPengguna) }}</td>
                            <td class="text-center py-2 font-speedometer text-danger">{{ number_format($totalTeroris) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalTrafficking) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalIlegalLogging) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalMoneyLaundering) }}</td>
                            <td class="text-center py-2 font-speedometer">{{ number_format($totalGenosida) }}</td>
                            <td class="text-center py-2 th-group-pidsus">
                                <span class="badge-pidsus-total" style="font-size: 12px;">{{ number_format($totalPidsus) }}</span>
                                <div class="text-purple" style="font-size: 8.5px;">{{ $statistikGrandTotal > 0 ? round(($totalPidsus / $statistikGrandTotal) * 100, 1) : 0 }}%</div>
                            </td>
                            <td class="text-center py-2 th-group-ite">
                                <span class="font-speedometer" style="font-size: 12px;">{{ number_format($totalUUITE) }}</span>
                                <div style="font-size: 8.5px;">{{ $statistikGrandTotal > 0 ? round(($totalUUITE / $statistikGrandTotal) * 100, 1) : 0 }}%</div>
                            </td>
                            <td class="text-center py-2 font-speedometer text-warning">{{ number_format($totalWNA) }}</td>
                            <td class="text-center py-2 font-speedometer text-danger">{{ number_format($totalSakit) }}</td>
                            <td class="text-center py-2 font-speedometer text-primary">{{ number_format($totalLansia) }}</td>
                            <td class="text-center py-2 font-speedometer text-danger fw-bold" style="font-size: 12px;">{{ number_format($totalPidanaMati) }}</td>
                            <td class="text-center py-2 font-speedometer fw-bold text-purple" style="font-size: 12px;">{{ number_format($totalSeumurHidup) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Tabel Pidana Mati dan Seumur Hidup per UPT -->
        <div class="table-card-modern mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 text-danger fw-bold" style="font-size: 13.5px;">
                        <i class="fas fa-skull-crossbones me-2"></i>Data Narapidana Pidana Mati & Seumur Hidup per UPT
                    </h6>
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }} &bull; Vonis Hukuman Maksimal</small>
                </div>
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tablePMSH" placeholder="Cari UPT..." aria-label="Cari nama UPT di tabel pidana mati dan seumur hidup">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tablePMSH" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-2" style="width: 45px;">No</th>
                            <th class="py-2">Unit Pelaksana Teknis</th>
                            <th class="text-center py-2 text-danger" style="width: 140px; background-color: rgba(244, 63, 94, 0.12);">
                                <i class="fas fa-skull-crossbones me-1"></i>Pidana Mati
                            </th>
                            <th class="text-center py-2 text-purple" style="width: 140px; background-color: rgba(139, 92, 246, 0.12);">
                                <i class="fas fa-infinity me-1"></i>Seumur Hidup
                            </th>
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
                                <td class="text-center py-2 font-speedometer">{{ $no++ }}</td>
                                <td class="py-2 fw-medium">{{ $row->nama_upt }}</td>
                                <td class="text-center py-2">
                                    @if($pm > 0)
                                        <strong class="text-danger font-speedometer" style="font-size: 12px;">{{ number_format($pm) }}</strong>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($sh > 0)
                                        <strong class="font-speedometer text-purple" style="font-size: 12px;">{{ number_format($sh) }}</strong>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2 text-danger font-speedometer" style="background-color: rgba(244, 63, 94, 0.12); font-size: 12px;">
                                {{ number_format($totalPM) }}
                            </td>
                            <td class="text-center py-2 font-speedometer text-purple" style="background-color: rgba(139, 92, 246, 0.12); font-size: 12px;">
                                {{ number_format($totalSH) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Chart Demografi Umur dan Jenis Kelamin -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="chart-card-modern h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-header command-section-title mb-0" style="font-size: 13px;">
                            <i class="fas fa-venus-mars text-primary me-2"></i>Penghuni Berdasarkan Umur & Gender
                        </div>
                    </div>
                    <!-- Telemetry Badges Grid -->
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-sm-3">
                            <div class="telemetry-legend-item d-flex align-items-center gap-2 p-2">
                                <span class="donut-legend-pip" style="background: #38bdf8;"></span>
                                <div class="text-truncate">
                                    <div class="text-xs text-muted">Dewasa ♂</div>
                                    <strong class="font-speedometer text-xs" id="legend-dewasa-laki">0</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="telemetry-legend-item d-flex align-items-center gap-2 p-2">
                                <span class="donut-legend-pip" style="background: #60a5fa;"></span>
                                <div class="text-truncate">
                                    <div class="text-xs text-muted">Anak ♂</div>
                                    <strong class="font-speedometer text-xs" id="legend-anak-laki">0</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="telemetry-legend-item d-flex align-items-center gap-2 p-2">
                                <span class="donut-legend-pip" style="background: #f43f5e;"></span>
                                <div class="text-truncate">
                                    <div class="text-xs text-muted">Dewasa ♀</div>
                                    <strong class="font-speedometer text-xs" id="legend-dewasa-perempuan">0</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="telemetry-legend-item d-flex align-items-center gap-2 p-2">
                                <span class="donut-legend-pip" style="background: #fb7185;"></span>
                                <div class="text-truncate">
                                    <div class="text-xs text-muted">Anak ♀</div>
                                    <strong class="font-speedometer text-xs" id="legend-anak-perempuan">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="height: 220px; position: relative;">
                        <canvas id="chartUmurGender"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="chart-card-modern h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-header command-section-title mb-0" style="font-size: 13px;">
                            <i class="fas fa-chart-pie text-primary me-2"></i>Distribusi Jenis Kelamin
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-12 col-sm-6">
                            <div class="donut-chart-container position-relative" style="height: 200px;">
                                <canvas id="chartGenderDistribution"></canvas>
                                <div class="donut-center-telemetry">
                                    <div class="donut-center-val font-speedometer" id="donutGenderCenterVal">0</div>
                                    <div class="donut-center-sub">Total Jiwa</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <div id="genderLegendContainer" class="d-flex flex-column gap-2"></div>
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

        <!-- Bebas Harian Section Header -->
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <div class="section-header command-section-title mb-0" style="border-bottom: 2px solid #10b981; color: #10b981;">
                    <i class="fas fa-door-open me-2"></i>Pengeluaran / Bebas Harian
                </div>
                <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }} &bull; Rekapitulasi Pembebasan & Pengeluaran WBP Seluruh UPT Banten</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="pill-badge pill-badge-emerald">
                    <i class="fas fa-check-circle"></i> {{ number_format($stats['total_bebas']) }} Total Bebas Hari Ini
                </span>
            </div>
        </div>

        <!-- Bebas Harian Cockpit Bento Grid (Symmetric 4x2 Grid: 8 Rapi Bento Cards) -->
        <div class="row g-2 mb-3">
            <!-- 1. Total Pengeluaran Hari Ini (Hero Card) -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #10b981; background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(6, 182, 212, 0.04));">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-success">Total Pengeluaran</span>
                        <div class="snapshot-card-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-success count-up" data-target="{{ $stats['total_bebas'] }}">
                        {{ number_format($stats['total_bebas']) }}
                    </div>
                    <div class="snapshot-card-sub text-success">Total Bebas Hari Ini</div>
                </div>
            </div>

            <!-- 2. Bebas Murni -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #64748b;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label">Bebas Murni</span>
                        <div class="snapshot-card-icon" style="background: rgba(100, 116, 139, 0.15); color: #64748b;">
                            <i class="fas fa-certificate"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-secondary count-up" data-target="{{ $stats['bebas_murni'] }}">
                        {{ number_format($stats['bebas_murni']) }}
                    </div>
                    <div class="snapshot-card-sub">Habis Masa Pidana</div>
                </div>
            </div>

            <!-- 3. PB -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #3b82f6;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-primary">PB (Bersyarat)</span>
                        <div class="snapshot-card-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-primary count-up" data-target="{{ $stats['bebas_pb'] }}">
                        {{ number_format($stats['bebas_pb']) }}
                    </div>
                    <div class="snapshot-card-sub">Pembebasan Bersyarat</div>
                </div>
            </div>

            <!-- 4. CB -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #06b6d4;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-info">CB (Bersyarat)</span>
                        <div class="snapshot-card-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-info count-up" data-target="{{ $stats['bebas_cb'] }}">
                        {{ number_format($stats['bebas_cb']) }}
                    </div>
                    <div class="snapshot-card-sub">Cuti Bersyarat</div>
                </div>
            </div>

            <!-- 5. CMB -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #8b5cf6;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-purple">CMB</span>
                        <div class="snapshot-card-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                            <i class="fas fa-walking"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-purple count-up" data-target="{{ $stats['bebas_cmb'] }}">
                        {{ number_format($stats['bebas_cmb']) }}
                    </div>
                    <div class="snapshot-card-sub">Cuti Menjelang Bebas</div>
                </div>
            </div>

            <!-- 6. Asimilasi -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #f59e0b;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-warning-emphasis">Asimilasi</span>
                        <div class="snapshot-card-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                            <i class="fas fa-house-user"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-warning-emphasis count-up" data-target="{{ $stats['bebas_asimilasi'] }}">
                        {{ number_format($stats['bebas_asimilasi']) }}
                    </div>
                    <div class="snapshot-card-sub">Asimilasi Rumah / Kerja</div>
                </div>
            </div>

            <!-- 7. BDH -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #f43f5e;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-danger">BDH (Demi Hukum)</span>
                        <div class="snapshot-card-icon" style="background: rgba(244, 63, 94, 0.15); color: #f43f5e;">
                            <i class="fas fa-scale-balanced"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-danger count-up" data-target="{{ $stats['bebas_bdh'] }}">
                        {{ number_format($stats['bebas_bdh']) }}
                    </div>
                    <div class="snapshot-card-sub">Bebas Demi Hukum</div>
                </div>
            </div>

            <!-- 8. Lainnya -->
            <div class="col-6 col-md-3">
                <div class="bento-card snapshot-card h-100" style="border-left: 3px solid #94a3b8;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="snapshot-card-label text-muted">Lain-lain</span>
                        <div class="snapshot-card-icon" style="background: rgba(148, 163, 184, 0.15); color: #94a3b8;">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                    <div class="snapshot-card-value font-speedometer text-muted count-up" data-target="{{ $stats['bebas_lainnya'] }}">
                        {{ number_format($stats['bebas_lainnya']) }}
                    </div>
                    <div class="snapshot-card-sub">Pengeluaran Lainnya</div>
                </div>
            </div>
        </div>

        <!-- Tabel Bebas Harian per UPT -->
        <div class="table-card-modern mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 text-success fw-bold" style="font-size: 13.5px;">
                        <i class="fas fa-door-open me-2"></i>Data Pengeluaran / Bebas Harian per UPT
                    </h6>
                    <small class="text-secondary">{{ formatIndonesianDate($filterTanggal, false) }} &bull; Rincian Lengkap per Jenis Bebas</small>
                </div>
                <div class="table-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-sm table-search-input" data-table="#tableBebasHarian" placeholder="Cari UPT..." aria-label="Cari nama UPT di tabel bebas harian">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 align-middle" id="tableBebasHarian" style="font-size: 11px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-2" style="width: 40px;">No</th>
                            <th class="py-2" style="min-width: 180px;">Unit Pelaksana Teknis</th>
                            <th class="text-center py-2 th-group-bebas-bm" style="width: 95px;">Bebas Murni</th>
                            <th class="text-center py-2 th-group-bebas-pb" style="width: 90px;">PB</th>
                            <th class="text-center py-2 th-group-bebas-cb" style="width: 90px;">CB</th>
                            <th class="text-center py-2 th-group-bebas-cmb" style="width: 90px;">CMB</th>
                            <th class="text-center py-2 th-group-bebas-asim" style="width: 95px;">Asimilasi</th>
                            <th class="text-center py-2 th-group-bebas-bdh" style="width: 90px;">BDH</th>
                            <th class="text-center py-2 th-group-bebas-lny" style="width: 90px;">Lainnya</th>
                            <th class="text-center py-2 th-group-bebas-total" style="width: 105px;">Total Bebas</th>
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
                                <td class="text-center py-2 font-speedometer">{{ $noBebas++ }}</td>
                                <td class="py-2 fw-medium">{{ $rowBebas->nama_upt }}</td>
                                <td class="text-center py-2">
                                    @if($bm > 0)
                                        <span class="val-active text-secondary">{{ number_format($bm) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($pb > 0)
                                        <span class="val-active text-primary">{{ number_format($pb) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($cb > 0)
                                        <span class="val-active text-info">{{ number_format($cb) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($cmb > 0)
                                        <span class="val-active text-purple">{{ number_format($cmb) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($asim > 0)
                                        <span class="val-active text-warning-emphasis">{{ number_format($asim) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($bdh > 0)
                                        <span class="val-active text-danger">{{ number_format($bdh) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($lny > 0)
                                        <span class="val-active text-muted">{{ number_format($lny) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">
                                    @if($totalUpt > 0)
                                        <span class="badge-bebas-total">{{ number_format($totalUpt) }}</span>
                                    @else
                                        <span class="val-zero">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-3">Belum ada data pengeluaran / bebas harian</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-2">TOTAL</td>
                            <td class="text-center py-2 th-group-bebas-bm font-speedometer" style="font-size: 12px;">{{ number_format($totBM) }}</td>
                            <td class="text-center py-2 th-group-bebas-pb font-speedometer" style="font-size: 12px;">{{ number_format($totPB) }}</td>
                            <td class="text-center py-2 th-group-bebas-cb font-speedometer" style="font-size: 12px;">{{ number_format($totCB) }}</td>
                            <td class="text-center py-2 th-group-bebas-cmb font-speedometer" style="font-size: 12px;">{{ number_format($totCMB) }}</td>
                            <td class="text-center py-2 th-group-bebas-asim font-speedometer" style="font-size: 12px;">{{ number_format($totAsim) }}</td>
                            <td class="text-center py-2 th-group-bebas-bdh font-speedometer" style="font-size: 12px;">{{ number_format($totBDH) }}</td>
                            <td class="text-center py-2 th-group-bebas-lny font-speedometer" style="font-size: 12px;">{{ number_format($totLny) }}</td>
                            <td class="text-center py-2 th-group-bebas-total font-speedometer" style="font-size: 13px;">{{ number_format($totSemua) }}</td>
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
                    <button class="carousel-control-prev" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="prev" aria-label="Pengumuman Sebelumnya">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Sebelumnya</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="next" aria-label="Pengumuman Selanjutnya">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Selanjutnya</span>
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
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            <div class="modal-body text-center">
                @if(!empty($instansi->welcome_banner))
                @php
                    $bannerExt = pathinfo($instansi->welcome_banner, PATHINFO_EXTENSION);
                    $webpBanner = preg_replace('/\.' . preg_quote($bannerExt, '/') . '$/i', '.webp', $instansi->welcome_banner);
                    $hasWebpBanner = file_exists(public_path($webpBanner));
                @endphp
                <div class="welcome-banner-container">
                    @if($hasWebpBanner)
                    <picture>
                        <source srcset="{{ asset($webpBanner) }}" type="image/webp">
                        <img src="{{ asset($instansi->welcome_banner) }}" class="img-fluid welcome-banner-img" alt="Welcome Sultan Banten" width="800" height="400" fetchpriority="high" decoding="async">
                    </picture>
                    @else
                    <img src="{{ asset($instansi->welcome_banner) }}" class="img-fluid welcome-banner-img" alt="Welcome Sultan Banten" width="800" height="400" fetchpriority="high" decoding="async">
                    @endif
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
<!-- Chart.js & Leaflet GIS Core -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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

        // Invalidate Leaflet map size and auto-zoom to cluster center when tab becomes visible
        if (tabName === 'gis' || tabName === 'all') {
            setTimeout(() => {
                if (typeof mapBanten !== 'undefined') {
                    mapBanten.invalidateSize();
                    if (typeof zoomToClusterCenter === 'function') {
                        zoomToClusterCenter(false);
                    }
                }
            }, 60);
            setTimeout(() => {
                if (typeof mapBanten !== 'undefined' && typeof zoomToClusterCenter === 'function') {
                    zoomToClusterCenter(true);
                }
            }, 220);
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
            const hasPlus = counter.getAttribute('data-prefix') === '+' || counter.textContent.trim().startsWith('+');
            const duration = 1000;
            const startTime = performance.now();

            function step(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 4);
                const current = target * easeOut;

                if (isPercent) {
                    counter.textContent = (hasPlus && current > 0 ? '+' : '') + current.toFixed(1) + '%';
                } else {
                    counter.textContent = formatNumber(Math.round(current));
                }

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    if (isPercent) {
                        counter.textContent = (hasPlus && target > 0 ? '+' : '') + target.toFixed(1) + '%';
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
                cutout: '67%',
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
                        font: { family: 'Rajdhani', weight: '700', size: 14 },
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
                        backgroundColor: '#38bdf8',
                        hoverBackgroundColor: '#0284c7',
                        borderRadius: 8,
                        barPercentage: 0.55,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Perempuan',
                        data: [intDewasaPerempuan, intAnakPerempuan],
                        backgroundColor: '#f43f5e',
                        hoverBackgroundColor: '#e11d48',
                        borderRadius: 8,
                        barPercentage: 0.55,
                        categoryPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        callbacks: {
                            label: function(context) {
                                const total = totalUmurGender;
                                const pct = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                return context.dataset.label + ': ' + formatNumber(context.parsed.y) + ' jiwa (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: {
                            callback: function(value) { return formatNumber(value); },
                            font: { family: 'Rajdhani', size: 11, weight: '600' }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    // Gender Distribution Hollow Donut Chart
    const totalLaki = intDewasaLaki + intAnakLaki;
    const totalPerempuan = intDewasaPerempuan + intAnakPerempuan;
    const totalGender = totalLaki + totalPerempuan;

    if (document.getElementById('donutGenderCenterVal')) {
        document.getElementById('donutGenderCenterVal').textContent = formatNumber(totalGender);
    }

    const ctxGenderDist = document.getElementById('chartGenderDistribution');
    if (ctxGenderDist) {
        const isDarkNow = document.documentElement.getAttribute('data-theme') === 'dark';
        new Chart(ctxGenderDist, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [totalLaki, totalPerempuan],
                    backgroundColor: ['#38bdf8', '#f43f5e'],
                    hoverBackgroundColor: ['#0284c7', '#e11d48'],
                    borderWidth: 3,
                    borderColor: isDarkNow ? 'rgba(13, 22, 42, 0.9)' : '#ffffff',
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
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
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
            <div class="telemetry-legend-item d-flex align-items-center p-2 mb-1">
                <span class="donut-legend-pip me-2" style="background:#38bdf8;"></span>
                <span class="telemetry-legend-name">Laki-laki</span>
                <span class="telemetry-legend-val">${formatNumber(totalLaki)}</span>
                <span class="telemetry-legend-pct text-cyan">(${persenLaki}%)</span>
            </div>
            <div class="telemetry-legend-item d-flex align-items-center p-2 mb-1">
                <span class="donut-legend-pip me-2" style="background:#f43f5e;"></span>
                <span class="telemetry-legend-name">Perempuan</span>
                <span class="telemetry-legend-val">${formatNumber(totalPerempuan)}</span>
                <span class="telemetry-legend-pct text-danger">(${persenPerempuan}%)</span>
            </div>
            <div class="telemetry-legend-item d-flex align-items-center p-2 border-top mt-1" style="background: rgba(148, 163, 184, 0.05);">
                <span class="donut-legend-pip me-2" style="background:#8b5cf6;"></span>
                <span class="telemetry-legend-name fw-bold">Total Jiwa</span>
                <span class="telemetry-legend-val fw-bold">${formatNumber(totalGender)}</span>
                <span class="telemetry-legend-pct fw-bold">(100%)</span>
            </div>
        `;
    }

    // Leaflet GIS Map Initialization with Multi-Layer Radar (100% Free - NO API KEY REQUIRED)
    // Esri Canvas Dark Gray provides professional tactical dark basemap without any API key or watermarks
    const darkBaseLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri, HERE, Garmin, &copy; OpenStreetMap',
        maxNativeZoom: 16,
        maxZoom: 19,
        className: 'hud-dark-base-tiles'
    });

    const darkRefLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Reference/MapServer/tile/{z}/{y}/{x}', {
        attribution: '',
        maxNativeZoom: 16,
        maxZoom: 19,
        className: 'hud-dark-ref-tiles'
    });

    const darkLayer = L.layerGroup([darkBaseLayer, darkRefLayer]);

    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Esri World Imagery',
        maxZoom: 19
    });

    const hybridLayer = L.layerGroup([
        satelliteLayer,
        L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19
        })
    ]);

    const isInitDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const initialMapLayer = isInitDark ? darkLayer : hybridLayer;

    // Centered initially at Banten cluster center: [-6.22, 106.35]
    const mapBanten = L.map('mapBanten', {
        layers: [initialMapLayer],
        center: [-6.22, 106.35],
        zoom: 10
    });

    const btnDark = document.getElementById('btnDark');
    const btnStreet = document.getElementById('btnStreet');
    const btnHybrid = document.getElementById('btnHybrid');
    const btnFitBounds = document.getElementById('btnFitBounds');

    function updateActiveMapBtn(activeBtn) {
        [btnDark, btnStreet, btnHybrid].forEach(btn => btn?.classList.remove('active'));
        activeBtn?.classList.add('active');
    }

    function removeAllBaseLayers() {
        [darkLayer, streetLayer, satelliteLayer, hybridLayer].forEach(layer => {
            if (mapBanten.hasLayer(layer)) {
                mapBanten.removeLayer(layer);
            }
        });
    }

    if (isInitDark && btnDark) {
        updateActiveMapBtn(btnDark);
    }

    btnDark?.addEventListener('click', function() {
        removeAllBaseLayers();
        darkLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    btnStreet?.addEventListener('click', function() {
        removeAllBaseLayers();
        streetLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    btnHybrid?.addEventListener('click', function() {
        removeAllBaseLayers();
        hybridLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    // Auto synchronize map basemap when dark mode toggle is clicked
    const themeObserver = new MutationObserver(function() {
        const isDarkNow = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDarkNow) {
            if (!mapBanten.hasLayer(darkLayer) && !mapBanten.hasLayer(hybridLayer)) {
                removeAllBaseLayers();
                darkLayer.addTo(mapBanten);
                updateActiveMapBtn(btnDark);
            }
        } else {
            if (mapBanten.hasLayer(darkLayer)) {
                removeAllBaseLayers();
                streetLayer.addTo(mapBanten);
                updateActiveMapBtn(btnStreet);
            }
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    // Markers with glowing status pin
    const uptLocations = {!! json_encode($uptLocations) !!};
    const markers = [];

    uptLocations.forEach(upt => {
        const totalPenghuni = parseInt(upt.total_penghuni) || 0;
        const totalTahanan = parseInt(upt.total_tahanan) || 0;
        const totalNapi = parseInt(upt.total_narapidana) || 0;
        const kapasitas = parseInt(upt.kapasitas) || 0;
        const persen = kapasitas > 0 ? Math.round(((totalPenghuni - kapasitas) / kapasitas) * 100) : 0;
        const tingkatHunian = kapasitas > 0 ? Math.round((totalPenghuni / kapasitas) * 100) : 0;
        const persenText = persen > 0 ? '+' + persen + '%' : persen + '%';
        
        let statusClass = 'emerald';
        let statusBadge = 'Longgar';
        let markerColor = '#10b981';
        if (persen > 50) { 
            statusClass = 'rose'; 
            statusBadge = 'Kritis'; 
            markerColor = '#f43f5e';
        } else if (persen > 0) { 
            statusClass = 'amber'; 
            statusBadge = 'Waspada'; 
            markerColor = '#f59e0b';
        }

        const namaUpper = (upt.nama_upt || '').toUpperCase();
        let iconClass = 'fa-building';
        if (namaUpper.includes('RUTAN')) {
            iconClass = 'fa-shield-halved';
        } else if (namaUpper.includes('ANAK') || namaUpper.includes('LPKA')) {
            iconClass = 'fa-child';
        } else if (namaUpper.includes('PEREMPUAN')) {
            iconClass = 'fa-venus';
        }

        const markerHtml = `
            <div class="hud-marker-wrapper hud-${statusClass}" title="${upt.nama_upt}">
                <div class="hud-marker-pulse"></div>
                <div class="hud-marker-core">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="hud-marker-badge font-speedometer">${persenText}</div>
            </div>
        `;

        const customIcon = L.divIcon({
            className: 'hud-leaflet-marker',
            html: markerHtml,
            iconSize: [44, 44],
            iconAnchor: [22, 22],
            popupAnchor: [0, -22]
        });

        if (upt.latitude && upt.longitude) {
            const lat = parseFloat(upt.latitude);
            const lng = parseFloat(upt.longitude);
            const gmapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            const addressText = upt.alamat ? upt.alamat : 'Provinsi Banten, Indonesia';

            const popupContent = `
                <div class="hud-popup-card">
                    <div class="hud-popup-header">
                        <div class="hud-popup-cat">UPT PEMASYARAKATAN BANTEN</div>
                        <div class="hud-popup-title">${upt.nama_upt}</div>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <span class="pill-badge pill-badge-${statusClass}">${statusBadge} (${persenText})</span>
                            <span class="text-xs text-muted font-speedometer">${tingkatHunian}% Hunian</span>
                        </div>
                    </div>
                    <div class="hud-popup-body">
                        <a href="${gmapsUrl}" target="_blank" rel="noopener noreferrer" class="hud-popup-address" title="Buka alamat di Google Maps / Aplikasi Navigasi">
                            <i class="fas fa-map-marker-alt text-danger flex-shrink-0"></i>
                            <span class="hud-popup-address-text">${addressText}</span>
                            <i class="fas fa-external-link-alt flex-shrink-0 text-cyan opacity-75"></i>
                        </a>

                        <div class="hud-popup-grid mt-2">
                            <div class="hud-popup-stat">
                                <span class="hud-popup-stat-lbl">Kapasitas</span>
                                <span class="hud-popup-stat-val font-speedometer">${formatNumber(kapasitas)}</span>
                            </div>
                            <div class="hud-popup-stat">
                                <span class="hud-popup-stat-lbl">Penghuni</span>
                                <span class="hud-popup-stat-val font-speedometer" style="color: ${markerColor};">${formatNumber(totalPenghuni)}</span>
                            </div>
                            <div class="hud-popup-stat">
                                <span class="hud-popup-stat-lbl">Tahanan</span>
                                <span class="hud-popup-stat-val font-speedometer">${formatNumber(totalTahanan)}</span>
                            </div>
                            <div class="hud-popup-stat">
                                <span class="hud-popup-stat-lbl">Narapidana</span>
                                <span class="hud-popup-stat-val font-speedometer">${formatNumber(totalNapi)}</span>
                            </div>
                        </div>

                        <div class="hud-popup-progress-track mt-2">
                            <div class="hud-popup-progress-fill bg-${statusClass}" style="width: ${Math.min(100, Math.max(5, tingkatHunian))}%;"></div>
                        </div>

                        <div class="hud-popup-actions mt-3">
                            <a href="${gmapsUrl}" target="_blank" rel="noopener noreferrer" class="hud-btn-maps w-100">
                                <i class="fas fa-location-arrow"></i>
                                <span>Petunjuk Arah (Google Maps)</span>
                                <i class="fas fa-arrow-up-right-from-square ms-auto" style="font-size: 10px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;

            const marker = L.marker([lat, lng], { icon: customIcon })
                .addTo(mapBanten)
                .bindPopup(popupContent, {
                    maxWidth: 320,
                    className: 'hud-custom-popup'
                });

            markers.push({
                id: upt.id,
                data: upt,
                layer: marker
            });
        }
    });

    // Function to zoom directly to the center of the marker cluster
    function zoomToClusterCenter(animate = true) {
        if (!mapBanten || markers.length === 0) return;
        mapBanten.invalidateSize();
        
        const activeMarkers = markers.filter(m => mapBanten.hasLayer(m.layer));
        const targetMarkers = activeMarkers.length > 0 ? activeMarkers : markers;
        
        if (targetMarkers.length > 0) {
            const group = L.featureGroup(targetMarkers.map(m => m.layer));
            const bounds = group.getBounds();
            if (bounds.isValid()) {
                mapBanten.fitBounds(bounds.pad(0.12), {
                    padding: [25, 25],
                    maxZoom: 12,
                    animate: animate,
                    duration: 0.8
                });
            }
        } else {
            mapBanten.setView([-6.22, 106.35], 10, { animate: animate });
        }
    }

    // Connect Pusatkan button
    btnFitBounds?.addEventListener('click', function(e) {
        e.preventDefault();
        zoomToClusterCenter(true);
    });

    // Map Radar Type Filters
    document.querySelectorAll('.map-filter-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.map-filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const filterType = this.getAttribute('data-filter');

            markers.forEach(item => {
                let show = false;
                const nama = (item.data.nama_upt || '').toUpperCase();
                const total = parseInt(item.data.total_penghuni) || 0;
                const kap = parseInt(item.data.kapasitas) || 0;
                const isOver = kap > 0 && total > kap;

                if (filterType === 'all') {
                    show = true;
                } else if (filterType === 'lapas') {
                    show = nama.includes('LAPAS') || nama.includes('LEMBAGA PEMASYARAKATAN');
                } else if (filterType === 'rutan') {
                    show = nama.includes('RUTAN') || nama.includes('RUMAH TAHANAN');
                } else if (filterType === 'lpka_bapas') {
                    show = nama.includes('LPKA') || nama.includes('ANAK') || nama.includes('TERBUKA') || nama.includes('BAPAS') || nama.includes('RUPBASAN');
                } else if (filterType === 'over') {
                    show = isOver;
                }

                if (show) {
                    if (!mapBanten.hasLayer(item.layer)) {
                        mapBanten.addLayer(item.layer);
                    }
                } else {
                    if (mapBanten.hasLayer(item.layer)) {
                        mapBanten.removeLayer(item.layer);
                    }
                }
            });

            zoomToClusterCenter(true);
        });
    });

    // Focus on marker from table row button
    document.querySelectorAll('.btn-focus-marker').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const uptId = parseInt(this.getAttribute('data-upt-id'));
            const target = markers.find(m => m.id === uptId);
            if (target && mapBanten) {
                const mapEl = document.getElementById('mapBanten');
                if (mapEl) {
                    mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                setTimeout(() => {
                    mapBanten.setView([parseFloat(target.data.latitude), parseFloat(target.data.longitude)], 14, { animate: true });
                    target.layer.openPopup();
                }, 400);
            }
        });
    });

    // Initial cluster center calculation
    setTimeout(() => {
        zoomToClusterCenter(false);
    }, 350);

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
