@extends('layouts.app')

@section('title', 'Dinamika & Fluktuasi Tambah Kurang Penghuni - Sultan Banten')
@section('page_title')
    <i class="fas fa-exchange-alt me-2 text-primary"></i>Dinamika &amp; Fluktuasi Tambah Kurang Penghuni
@endsection

@push('styles')
<style>
    .font-speedometer {
        font-family: 'Rajdhani', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* ===================================================
       4 KPI SUMMARY CARDS (THEME ADAPTIVE & RESPONSIVE)
       =================================================== */
    .stat-kpi-card {
        border-radius: 14px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }
    .stat-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }
    .stat-kpi-card .stat-icon {
        position: absolute;
        right: 14px;
        bottom: 10px;
        font-size: 44px;
        opacity: 0.16;
        pointer-events: none;
        transition: transform 0.2s ease;
    }
    .stat-kpi-card:hover .stat-icon {
        transform: scale(1.08);
    }
    .kpi-card-label {
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #475569;
    }
    .kpi-card-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.15;
        color: #0f172a;
    }
    .kpi-card-sub {
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
        margin-top: 4px;
    }

    /* Light Mode Accent Gradients & Borders */
    .kpi-card-tahanan {
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%) !important;
        border-color: #bfdbfe !important;
        border-left: 4px solid #3b82f6 !important;
    }
    .kpi-card-tahanan .kpi-card-label { color: #1e40af; }
    .kpi-card-tahanan .kpi-card-value { color: #1d4ed8; }
    .kpi-card-tahanan .stat-icon { color: #3b82f6; }

    .kpi-card-napi {
        background: linear-gradient(135deg, #ffffff 0%, #ecfeff 100%) !important;
        border-color: #a5f3fc !important;
        border-left: 4px solid #06b6d4 !important;
    }
    .kpi-card-napi .kpi-card-label { color: #0e7490; }
    .kpi-card-napi .kpi-card-value { color: #0891b2; }
    .kpi-card-napi .stat-icon { color: #06b6d4; }

    .kpi-card-total {
        background: linear-gradient(135deg, #ffffff 0%, #eef2ff 100%) !important;
        border-color: #c7d2fe !important;
        border-left: 4px solid #6366f1 !important;
    }
    .kpi-card-total .kpi-card-label { color: #4338ca; }
    .kpi-card-total .kpi-card-value { color: #4f46e5; }
    .kpi-card-total .stat-icon { color: #6366f1; }

    .kpi-card-pengeluaran {
        background: linear-gradient(135deg, #ffffff 0%, #ecfdf5 100%) !important;
        border-color: #a7f3d0 !important;
        border-left: 4px solid #10b981 !important;
    }
    .kpi-card-pengeluaran .kpi-card-label { color: #065f46; }
    .kpi-card-pengeluaran .kpi-card-value { color: #059669; }
    .kpi-card-pengeluaran .stat-icon { color: #10b981; }

    /* KPI Badges - Light Mode */
    .kpi-badge-pos {
        background-color: #dcfce7 !important;
        color: #15803d !important;
        border: 1px solid #86efac !important;
        font-size: 11px;
    }
    .kpi-badge-neg {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
        border: 1px solid #fca5a5 !important;
        font-size: 11px;
    }
    .kpi-badge-zero {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 11px;
    }
    .kpi-badge-amber {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fcd34d !important;
        font-size: 11px;
    }
    .kpi-badge-mutasi {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 10px;
    }
    .kpi-badge-bdh {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
        border: 1px solid #fecaca !important;
        font-size: 10px;
    }

    /* Dark Mode KPI Card Overrides */
    [data-theme="dark"] .stat-kpi-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25) !important;
    }
    [data-theme="dark"] .stat-kpi-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
    }
    [data-theme="dark"] .kpi-card-sub {
        color: #94a3b8 !important;
    }
    [data-theme="dark"] .kpi-card-tahanan {
        background: linear-gradient(135deg, #1e293b 0%, rgba(30, 58, 138, 0.25) 100%) !important;
        border-color: rgba(59, 130, 246, 0.35) !important;
        border-left: 4px solid #60a5fa !important;
    }
    [data-theme="dark"] .kpi-card-tahanan .kpi-card-label { color: #93c5fd !important; }
    [data-theme="dark"] .kpi-card-tahanan .kpi-card-value { color: #ffffff !important; }
    [data-theme="dark"] .kpi-card-tahanan .stat-icon { color: #60a5fa !important; opacity: 0.22 !important; }

    [data-theme="dark"] .kpi-card-napi {
        background: linear-gradient(135deg, #1e293b 0%, rgba(8, 145, 178, 0.22) 100%) !important;
        border-color: rgba(6, 182, 212, 0.35) !important;
        border-left: 4px solid #22d3ee !important;
    }
    [data-theme="dark"] .kpi-card-napi .kpi-card-label { color: #67e8f9 !important; }
    [data-theme="dark"] .kpi-card-napi .kpi-card-value { color: #ffffff !important; }
    [data-theme="dark"] .kpi-card-napi .stat-icon { color: #22d3ee !important; opacity: 0.22 !important; }

    [data-theme="dark"] .kpi-card-total {
        background: linear-gradient(135deg, #1e293b 0%, rgba(67, 56, 202, 0.22) 100%) !important;
        border-color: rgba(99, 102, 241, 0.35) !important;
        border-left: 4px solid #818cf8 !important;
    }
    [data-theme="dark"] .kpi-card-total .kpi-card-label { color: #a5b4fc !important; }
    [data-theme="dark"] .kpi-card-total .kpi-card-value { color: #ffffff !important; }
    [data-theme="dark"] .kpi-card-total .stat-icon { color: #818cf8 !important; opacity: 0.22 !important; }

    [data-theme="dark"] .kpi-card-pengeluaran {
        background: linear-gradient(135deg, #1e293b 0%, rgba(6, 95, 70, 0.22) 100%) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
        border-left: 4px solid #34d399 !important;
    }
    [data-theme="dark"] .kpi-card-pengeluaran .kpi-card-label { color: #6ee7b7 !important; }
    [data-theme="dark"] .kpi-card-pengeluaran .kpi-card-value { color: #ffffff !important; }
    [data-theme="dark"] .kpi-card-pengeluaran .stat-icon { color: #34d399 !important; opacity: 0.22 !important; }

    /* Dark Mode Badges */
    [data-theme="dark"] .kpi-badge-pos {
        background-color: rgba(34, 197, 94, 0.2) !important;
        color: #4ade80 !important;
        border-color: rgba(34, 197, 94, 0.45) !important;
    }
    [data-theme="dark"] .kpi-badge-neg {
        background-color: rgba(239, 68, 68, 0.2) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.45) !important;
    }
    [data-theme="dark"] .kpi-badge-zero {
        background-color: rgba(148, 163, 184, 0.15) !important;
        color: #cbd5e1 !important;
        border-color: rgba(148, 163, 184, 0.35) !important;
    }
    [data-theme="dark"] .kpi-badge-amber {
        background-color: rgba(245, 158, 11, 0.2) !important;
        color: #fde68a !important;
        border-color: rgba(245, 158, 11, 0.45) !important;
    }
    [data-theme="dark"] .kpi-badge-mutasi {
        background-color: rgba(148, 163, 184, 0.15) !important;
        color: #cbd5e1 !important;
        border-color: rgba(148, 163, 184, 0.35) !important;
    }
    [data-theme="dark"] .kpi-badge-bdh {
        background-color: rgba(239, 68, 68, 0.2) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.45) !important;
    }
    /* Overcrowding Classification Badges (Terkalibrasi Banten) */
    .badge-overcrowding-normal,
    .badge.badge-overcrowding-normal {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #10b981 !important;
        font-weight: 600 !important;
    }
    .badge-overcrowding-warning,
    .badge.badge-overcrowding-warning {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #f59e0b !important;
        font-weight: 600 !important;
    }
    .badge-overcrowding-critical,
    .badge.badge-overcrowding-critical {
        background-color: #ffedd5 !important;
        color: #9a3412 !important;
        border: 1px solid #ea580c !important;
        font-weight: 600 !important;
    }
    .badge-overcrowding-extreme,
    .badge.badge-overcrowding-extreme {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #ef4444 !important;
        font-weight: 700 !important;
        box-shadow: 0 0 6px rgba(220, 38, 38, 0.25) !important;
    }
    [data-theme="dark"] .badge-overcrowding-normal,
    [data-theme="dark"] .badge.badge-overcrowding-normal {
        background-color: rgba(16, 185, 129, 0.22) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.45) !important;
    }
    [data-theme="dark"] .badge-overcrowding-warning,
    [data-theme="dark"] .badge.badge-overcrowding-warning {
        background-color: rgba(245, 158, 11, 0.22) !important;
        color: #fde68a !important;
        border-color: rgba(245, 158, 11, 0.45) !important;
    }
    [data-theme="dark"] .badge-overcrowding-critical,
    [data-theme="dark"] .badge.badge-overcrowding-critical {
        background-color: rgba(234, 88, 12, 0.25) !important;
        color: #fdba74 !important;
        border-color: rgba(234, 88, 12, 0.5) !important;
    }
    [data-theme="dark"] .badge-overcrowding-extreme,
    [data-theme="dark"] .badge.badge-overcrowding-extreme {
        background-color: rgba(220, 38, 38, 0.28) !important;
        color: #fca5a5 !important;
        border-color: rgba(239, 68, 68, 0.55) !important;
        box-shadow: 0 0 10px rgba(220, 38, 38, 0.35) !important;
    }
    .table-fluktuasi {
        border-collapse: separate !important;
        border-spacing: 0 !important;
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
    .table-fluktuasi .col-sticky-no {
        left: 0 !important;
        width: 38px !important;
        min-width: 38px !important;
        max-width: 38px !important;
        text-align: center !important;
        padding-left: 2px !important;
        padding-right: 2px !important;
        border-right: 1px solid var(--border-color, #e2e8f0) !important;
    }
    .table-fluktuasi .col-sticky-upt {
        left: 38px !important;
        min-width: 175px !important;
        border-right: 2px solid #cbd5e1 !important;
    }
    @media (max-width: 767.98px) {
        .table-fluktuasi .col-sticky-upt {
            min-width: 135px !important;
            font-size: 12px !important;
        }
    }

    /* Mobile Cards View Styling */
    #fluktuasiCardsView {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        box-sizing: border-box;
    }
    .fluktuasi-card {
        border-radius: 14px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        box-sizing: border-box;
    }
    .fluktuasi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    [data-theme="dark"] .fluktuasi-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    /* Executive Summary Banner (Total Se-Wilayah Banten) */
    .fluktuasi-summary-banner {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
        border-radius: 14px !important;
        border: none !important;
        overflow: hidden !important;
        margin-bottom: 24px !important;
    }
    [data-theme="dark"] .fluktuasi-summary-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%) !important;
        border: 1px solid #334155 !important;
    }
    .fluktuasi-banner-card {
        background: #ffffff !important;
        border-radius: 10px !important;
        border: 1px solid rgba(255, 255, 255, 0.9) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12) !important;
        padding: 8px 4px !important;
        overflow: hidden;
        word-break: break-word;
    }
    .fluktuasi-banner-card-total {
        background: #eff6ff !important;
        border-color: #bfdbfe !important;
    }
    [data-theme="dark"] .fluktuasi-banner-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    }
    [data-theme="dark"] .fluktuasi-banner-card-total {
        background: rgba(30, 64, 175, 0.25) !important;
        border-color: rgba(59, 130, 246, 0.4) !important;
    }
    .fluktuasi-banner-label {
        font-size: 10px;
        font-weight: 700;
        color: #475569 !important;
        letter-spacing: 0.3px;
    }
    .fluktuasi-banner-label-total {
        font-size: 10px;
        font-weight: 800;
        color: #1d4ed8 !important;
        letter-spacing: 0.3px;
    }
    [data-theme="dark"] .fluktuasi-banner-label {
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .fluktuasi-banner-label-total {
        color: #93c5fd !important;
    }
    .fluktuasi-banner-val {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a !important;
        line-height: 1.25;
    }
    .fluktuasi-banner-val-total {
        font-size: 17px;
        font-weight: 800;
        color: #1e40af !important;
        line-height: 1.25;
    }
    [data-theme="dark"] .fluktuasi-banner-val {
        color: #ffffff !important;
    }
    [data-theme="dark"] .fluktuasi-banner-val-total {
        color: #60a5fa !important;
    }

    /* UPT Index Badge */
    .fluktuasi-upt-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        min-width: 24px;
        border-radius: 50%;
        background: #eff6ff;
        color: #1d4ed8 !important;
        font-weight: 800;
        font-size: 11px;
        border: 1px solid #bfdbfe;
        flex-shrink: 0;
    }
    [data-theme="dark"] .fluktuasi-upt-badge {
        background: #1e3a8a;
        color: #93c5fd !important;
        border-color: #3b82f6;
    }

    /* UPT Title in Header */
    .fluktuasi-upt-title {
        font-size: 13.5px;
        font-weight: 700;
        line-height: 1.35;
        color: #0f172a;
        margin-bottom: 0;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    [data-theme="dark"] .fluktuasi-upt-title {
        color: #f8fafc;
    }

    /* Net Delta Badges */
    .badge-net-pos {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 11px;
        padding: 3px 8px;
        white-space: nowrap;
    }
    .badge-net-neg {
        background-color: #16a34a !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 11px;
        padding: 3px 8px;
        white-space: nowrap;
    }
    .badge-net-zero {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 700;
        font-size: 11px;
        padding: 3px 8px;
        white-space: nowrap;
    }
    [data-theme="dark"] .badge-net-zero {
        background-color: #334155 !important;
        color: #f1f5f9 !important;
        border-color: #475569 !important;
    }

    /* Mini Boxes */
    .fluktuasi-mini-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 4px;
        overflow: hidden;
        word-break: break-word;
    }
    .fluktuasi-mini-box-total {
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    [data-theme="dark"] .fluktuasi-mini-box {
        background: rgba(255, 255, 255, 0.04) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    [data-theme="dark"] .fluktuasi-mini-box-total {
        background: rgba(30, 64, 175, 0.15) !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }

    /* Typography inside Mini Boxes */
    .fluktuasi-box-label {
        font-size: 10px;
        font-weight: 700;
        color: #475569;
        letter-spacing: 0.3px;
    }
    .fluktuasi-box-label-total {
        font-size: 10px;
        font-weight: 800;
        color: #1d4ed8;
        letter-spacing: 0.3px;
    }
    [data-theme="dark"] .fluktuasi-box-label {
        color: #94a3b8;
    }
    [data-theme="dark"] .fluktuasi-box-label-total {
        color: #93c5fd;
    }

    .fluktuasi-box-val {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .fluktuasi-box-val-total {
        font-size: 15px;
        font-weight: 800;
        color: #1e40af;
        line-height: 1.2;
    }
    [data-theme="dark"] .fluktuasi-box-val {
        color: #f8fafc;
    }
    [data-theme="dark"] .fluktuasi-box-val-total {
        color: #60a5fa;
    }

    .fluktuasi-box-sub {
        font-size: 9.5px;
        font-weight: 600;
        color: #64748b;
    }
    .fluktuasi-box-sub-total {
        font-size: 9.5px;
        font-weight: 600;
        color: #2563eb;
    }
    [data-theme="dark"] .fluktuasi-box-sub {
        color: #94a3b8;
    }
    [data-theme="dark"] .fluktuasi-box-sub-total {
        color: #93c5fd;
    }

    /* Delta Badges in Mini Boxes */
    .badge-delta-pos {
        background-color: #dcfce7 !important;
        color: #15803d !important;
        border: 1px solid #86efac !important;
        font-weight: 700 !important;
        font-size: 10px !important;
        padding: 2px 5px !important;
        display: inline-block;
    }
    .badge-delta-neg {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
        border: 1px solid #fca5a5 !important;
        font-weight: 700 !important;
        font-size: 10px !important;
        padding: 2px 5px !important;
        display: inline-block;
    }
    .badge-delta-zero {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 600 !important;
        font-size: 10px !important;
        padding: 2px 5px !important;
        display: inline-block;
    }
    [data-theme="dark"] .badge-delta-pos {
        background-color: rgba(34, 197, 94, 0.2) !important;
        color: #4ade80 !important;
        border-color: rgba(34, 197, 94, 0.4) !important;
    }
    [data-theme="dark"] .badge-delta-neg {
        background-color: rgba(239, 68, 68, 0.2) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.4) !important;
    }
    [data-theme="dark"] .badge-delta-zero {
        background-color: rgba(148, 163, 184, 0.15) !important;
        color: #cbd5e1 !important;
        border-color: rgba(148, 163, 184, 0.3) !important;
    }

    /* Pengeluaran Strip */
    .fluktuasi-strip {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 10px;
    }
    [data-theme="dark"] .fluktuasi-strip {
        background: rgba(255, 255, 255, 0.04) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    .fluktuasi-strip-label {
        color: #334155;
        font-weight: 700;
        font-size: 11px;
    }
    [data-theme="dark"] .fluktuasi-strip-label {
        color: #cbd5e1;
    }
    .badge-strip-total {
        background-color: #16a34a !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 10px;
        padding: 2px 8px;
    }
    .badge-strip-mutasi {
        background-color: #475569 !important;
        color: #ffffff !important;
        font-weight: 600;
        font-size: 9.5px;
        padding: 2px 6px;
    }
    .badge-strip-bdh {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 9.5px;
        padding: 2px 6px;
    }
    .fluktuasi-strip-nihil {
        color: #64748b;
        font-weight: 600;
        font-size: 10.5px;
    }
    [data-theme="dark"] .fluktuasi-strip-nihil {
        color: #94a3b8;
    }

    /* Analisis Box & High Contrast Colors for Light Mode */
    .fluktuasi-analisis-box {
        background-color: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 11px;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    [data-theme="dark"] .fluktuasi-analisis-box {
        background-color: rgba(14, 165, 233, 0.08);
        border-color: rgba(14, 165, 233, 0.25);
    }
    .fluktuasi-analisis-title {
        color: #0369a1;
        font-size: 11.5px;
    }
    [data-theme="dark"] .fluktuasi-analisis-title {
        color: #38bdf8;
    }

    /* Force readable text colors inside fluktuasi cards in light mode */
    .fluktuasi-card .text-secondary {
        color: #334155 !important;
    }
    .fluktuasi-card .text-info {
        color: #0284c7 !important;
        font-weight: 600;
    }
    .fluktuasi-card .text-warning {
        color: #b45309 !important;
        font-weight: 600;
    }
    .fluktuasi-card .text-muted {
        color: #475569 !important;
    }
    .fluktuasi-card .text-success {
        color: #15803d !important;
    }
    .fluktuasi-card .text-danger {
        color: #b91c1c !important;
    }

    /* Dark Mode Text Colors */
    [data-theme="dark"] .fluktuasi-card .text-secondary {
        color: #94a3b8 !important;
    }
    [data-theme="dark"] .fluktuasi-card .text-info {
        color: #38bdf8 !important;
    }
    [data-theme="dark"] .fluktuasi-card .text-warning {
        color: #fbbf24 !important;
    }
    [data-theme="dark"] .fluktuasi-card .text-muted {
        color: #94a3b8 !important;
    }
    [data-theme="dark"] .fluktuasi-card .text-success {
        color: #4ade80 !important;
    }
    [data-theme="dark"] .fluktuasi-card .text-danger {
        color: #f87171 !important;
    }

    /* Breakdown Button */
    .btn-breakdown-upt {
        background: #eff6ff;
        color: #1d4ed8 !important;
        border: 1.5px solid #93c5fd;
        font-weight: 700;
        font-size: 12px;
        transition: all 0.2s ease;
    }
    .btn-breakdown-upt:hover {
        background: #1d4ed8;
        color: #ffffff !important;
        border-color: #1d4ed8;
        transform: translateY(-1px);
    }
    [data-theme="dark"] .btn-breakdown-upt {
        background: rgba(37, 99, 235, 0.15);
        color: #93c5fd !important;
        border-color: rgba(59, 130, 246, 0.4);
    }
    [data-theme="dark"] .btn-breakdown-upt:hover {
        background: #2563eb;
        color: #ffffff !important;
    }

    /* View Switcher & Header */
    .fluktuasi-card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    [data-theme="dark"] .fluktuasi-card-header {
        background-color: #1e293b !important;
        border-bottom-color: #334155 !important;
    }
    .fluktuasi-toggle-group {
        background-color: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    [data-theme="dark"] .fluktuasi-toggle-group {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    .btn-view-mode {
        color: #64748b;
        transition: all 0.2s ease;
    }
    .btn-view-mode.active {
        background: #1e40af !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(30, 64, 175, 0.3) !important;
    }
    [data-theme="dark"] .btn-view-mode {
        color: #94a3b8;
    }
    [data-theme="dark"] .btn-view-mode.active {
        background: #2563eb !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4) !important;
    }

    /* Edukasi Dinamika Alert & Reset Button */
    .fluktuasi-info-alert {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
    }
    [data-theme="dark"] .fluktuasi-info-alert {
        background-color: rgba(30, 41, 59, 0.75) !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .fluktuasi-info-alert strong {
        color: #f1f5f9 !important;
    }
    .btn-reset-fluktuasi {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
    }
    .btn-reset-fluktuasi:hover {
        background: #f1f5f9;
        color: #334155;
    }
    [data-theme="dark"] .btn-reset-fluktuasi {
        background: #334155 !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .btn-reset-fluktuasi:hover {
        background: #475569 !important;
        color: #ffffff !important;
    }

    /* Table Matrix Subheaders & Dark Mode */
    .th-col-tahanan {
        background-color: #eff6ff !important;
        color: #1d4ed8 !important;
    }
    .th-col-napi {
        background-color: #ecfeff !important;
        color: #0e7490 !important;
    }
    .th-col-total {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
    }
    [data-theme="dark"] .table-fluktuasi thead.table-light {
        background-color: #334155 !important;
        color: #f1f5f9 !important;
        border-color: #475569 !important;
    }
    [data-theme="dark"] .table-fluktuasi thead th {
        background-color: #334155 !important;
        color: #f1f5f9 !important;
        border-color: #475569 !important;
    }
    [data-theme="dark"] .th-col-tahanan {
        background-color: rgba(30, 58, 138, 0.35) !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .th-col-napi {
        background-color: rgba(8, 145, 178, 0.3) !important;
        color: #67e8f9 !important;
    }
    [data-theme="dark"] .th-col-total {
        background-color: rgba(51, 65, 85, 0.5) !important;
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .table-fluktuasi td {
        border-color: #334155 !important;
        color: #f1f5f9;
    }
    [data-theme="dark"] .table-fluktuasi td.col-sticky-upt {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
    [data-theme="dark"] .table-fluktuasi td.col-sticky-no {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }
    [data-theme="dark"] .table-fluktuasi tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }
    [data-theme="dark"] .table-fluktuasi tbody tr:hover td.col-sticky-no,
    [data-theme="dark"] .table-fluktuasi tbody tr:hover td.col-sticky-upt {
        background-color: #273549 !important;
    }
    [data-theme="dark"] .table-fluktuasi tfoot {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }

    /* Modal Detail Fluktuasi Header, Body, Footer & Pill Badges */
    #modalDetailFluktuasi .modal-header {
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%) !important;
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
    }
    #modalDetailFluktuasi .modal-title {
        color: #ffffff !important;
    }
    #modalDetailFluktuasi .badge-kapasitas-pill {
        background: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.45) !important;
        font-weight: 600;
        letter-spacing: 0.2px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
    }
    [data-theme="dark"] #modalDetailFluktuasi .badge-kapasitas-pill {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .modal-content {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .modal-body {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .modal-footer {
        background-color: #1e293b !important;
        border-top: 1px solid #334155 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .table thead.table-light th,
    [data-theme="dark"] #modalDetailFluktuasi .table tfoot.table-light td {
        background-color: #334155 !important;
        color: #f1f5f9 !important;
        border-color: #475569 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .table td {
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .nav-pills .nav-link:not(.active) {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #94a3b8 !important;
        border: 1px solid #334155 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .nav-pills .nav-link.active {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .bg-light {
        background-color: rgba(255, 255, 255, 0.05) !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .text-dark {
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .alert-warning {
        background-color: rgba(245, 158, 11, 0.15) !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
        color: #fef3c7 !important;
    }
    [data-theme="dark"] #modalDetailFluktuasi .alert-success {
        background-color: rgba(16, 185, 129, 0.15) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
        color: #d1fae5 !important;
    }

    /* Mobile Responsive Optimizations */
    @media (max-width: 575.98px) {
        .stat-kpi-card {
            padding: 10px 10px !important;
            border-radius: 10px !important;
        }
        .kpi-card-label {
            font-size: 9.5px !important;
            letter-spacing: 0.2px !important;
        }
        .kpi-card-value {
            font-size: 17px !important;
            margin: 2px 0 !important;
        }
        .kpi-card-sub {
            font-size: 9.5px !important;
            margin-top: 2px !important;
        }
        .stat-kpi-card .badge {
            font-size: 8.5px !important;
            padding: 2px 4px !important;
        }
        .stat-kpi-card .stat-icon {
            font-size: 24px !important;
            right: 6px !important;
            bottom: 6px !important;
            opacity: 0.12 !important;
        }
        .fluktuasi-banner-card {
            padding: 6px 3px !important;
        }
        .fluktuasi-banner-label,
        .fluktuasi-banner-label-total {
            font-size: 9px !important;
        }
        .fluktuasi-banner-val,
        .fluktuasi-banner-val-total {
            font-size: 14px !important;
        }
    }

    @media print {
        .sidebar, .navbar, .top-header, .btn, form, th:last-child, td:last-child {
            display: none !important;
        }
        body, .content-wrapper, .container-fluid {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
        .table-fluktuasi {
            font-size: 9.5px !important;
        }
        .table-fluktuasi th, .table-fluktuasi td {
            padding: 3px 2px !important;
        }
        @page {
            size: landscape;
            margin: 8mm;
        }
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
                    <p class="text-muted small mb-0">Analisis pergerakan harian Tahanan (A.I - A.V), Narapidana, Alih Status Vonis, Pembebasan &amp; Mutasi Keluar</p>
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
                    <a href="{{ route('fluktuasi.index') }}" class="btn btn-reset-fluktuasi btn-sm" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 4 Kartu KPI Ringkasan Fluktuasi Se-Banten -->
    <div class="row g-2 g-sm-3 mb-4">
        <!-- Kartu 1: Tahanan -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card kpi-card-tahanan p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div style="min-width: 0;">
                        <div class="kpi-card-label text-truncate">Tahanan Se-Banten</div>
                        <div class="kpi-card-value font-speedometer my-1">{{ number_format($summary['total_tahanan_curr']) }}</div>
                    </div>
                    @if($summary['delta_tahanan'] > 0)
                        <span class="badge kpi-badge-neg fw-bold shadow-xs">+{{ number_format($summary['delta_tahanan']) }} (Naik)</span>
                    @elseif($summary['delta_tahanan'] < 0)
                        <span class="badge kpi-badge-pos fw-bold shadow-xs">{{ number_format($summary['delta_tahanan']) }} (Turun)</span>
                    @else
                        <span class="badge kpi-badge-zero fw-semibold shadow-xs">0 (Stabil)</span>
                    @endif
                </div>
                <div class="kpi-card-sub text-truncate">
                    Kemarin: <strong>{{ number_format($summary['total_tahanan_prev']) }}</strong> Orang
                </div>
                <i class="fas fa-user-lock stat-icon"></i>
            </div>
        </div>

        <!-- Kartu 2: Narapidana -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card kpi-card-napi p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div style="min-width: 0;">
                        <div class="kpi-card-label text-truncate">Narapidana Se-Banten</div>
                        <div class="kpi-card-value font-speedometer my-1">{{ number_format($summary['total_napi_curr']) }}</div>
                    </div>
                    @if($summary['delta_napi'] > 0)
                        <span class="badge kpi-badge-amber fw-bold shadow-xs">+{{ number_format($summary['delta_napi']) }}</span>
                    @elseif($summary['delta_napi'] < 0)
                        <span class="badge kpi-badge-neg fw-bold shadow-xs">{{ number_format($summary['delta_napi']) }}</span>
                    @else
                        <span class="badge kpi-badge-zero fw-semibold shadow-xs">0 (Stabil)</span>
                    @endif
                </div>
                <div class="kpi-card-sub text-truncate">
                    Kemarin: <strong>{{ number_format($summary['total_napi_prev']) }}</strong> Orang
                </div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>

        <!-- Kartu 3: Total Isi Penghuni -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card kpi-card-total p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div style="min-width: 0;">
                        <div class="kpi-card-label text-truncate">Total Isi Penghuni</div>
                        <div class="kpi-card-value font-speedometer my-1">{{ number_format($summary['total_penghuni_curr']) }}</div>
                    </div>
                    @if($summary['delta_penghuni'] > 0)
                        <span class="badge kpi-badge-neg fw-bold shadow-xs">+{{ number_format($summary['delta_penghuni']) }} Net</span>
                    @elseif($summary['delta_penghuni'] < 0)
                        <span class="badge kpi-badge-pos fw-bold shadow-xs">{{ number_format($summary['delta_penghuni']) }} Net</span>
                    @else
                        <span class="badge kpi-badge-zero fw-semibold shadow-xs">0 Net</span>
                    @endif
                </div>
                <div class="kpi-card-sub text-truncate">
                    Kemarin: <strong>{{ number_format($summary['total_penghuni_prev']) }}</strong> Orang
                </div>
                <i class="fas fa-chart-line stat-icon"></i>
            </div>
        </div>

        <!-- Kartu 4: Total Bebas Harian, BDH & Mutasi -->
        <div class="col-6 col-xl-3">
            <div class="card stat-kpi-card kpi-card-pengeluaran p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div style="min-width: 0;">
                        <div class="kpi-card-label text-truncate">Total Pengeluaran</div>
                        <div class="kpi-card-value font-speedometer my-1">
                            {{ number_format($summary['total_pengurang']) }} <span class="fs-6 fw-normal">Orang</span>
                        </div>
                    </div>
                    @if($summary['total_mutasi_keluar'] > 0)
                        <span class="badge kpi-badge-mutasi fw-bold shadow-xs">{{ $summary['total_mutasi_keluar'] }} Mutasi</span>
                    @elseif($summary['total_bdh'] > 0)
                        <span class="badge kpi-badge-bdh fw-bold shadow-xs">{{ $summary['total_bdh'] }} BDH</span>
                    @else
                        <span class="badge kpi-badge-pos fw-semibold shadow-xs">{{ $summary['total_bebas'] }} Bebas</span>
                    @endif
                </div>
                <div class="kpi-card-sub text-truncate">
                    Bebas: {{ $summary['total_bebas'] }} &bull; Mutasi: {{ $summary['total_mutasi_keluar'] }} &bull; BDH: {{ $summary['total_bdh'] }}
                </div>
                <i class="fas fa-door-open stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Alert Edukasi Dinamika Status -->
    <div class="alert fluktuasi-info-alert d-flex align-items-center gap-3 p-3 rounded-3 shadow-xs mb-4">
        <i class="fas fa-info-circle text-primary fs-3 flex-shrink-0"></i>
        <div class="small">
            <strong>Panduan Membaca Dinamika Tambah Kurang:</strong> 
            Perubahan jumlah penghuni hari ini (posisi apel pagi) dipengaruhi oleh pergerakan warga binaan pada hari kemarin (pembebasan, alih status, dan penerimaan baru). 
            Ketika Tahanan menerima vonis hakim yang telah berkekuatan hukum tetap (<em>inkrah</em>), statusnya beralih menjadi <strong>Narapidana</strong> (Tahanan berkurang, Narapidana bertambah, Total Penghuni tetap).
            Perpindahan antar-tingkat penahanan (A.I &rarr; A.II &rarr; A.III dst.) juga dipantau sebagai dinamika proses peradilan.
        </div>
    </div>

    <!-- Tabel & Kartu Matriks Fluktuasi Tambah Kurang Per UPT -->
    <div class="card border-0 shadow-sm mb-4" style="overflow: hidden;">
        <div class="card-header fluktuasi-card-header py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-table me-2 text-primary"></i>Matriks Fluktuasi Harian Per UPT
                </h6>
                <small class="text-muted" style="font-size: 11px;">
                    {{ \Carbon\Carbon::parse($tanggalPembanding)->translatedFormat('d M') }} &rarr; {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }} &bull; Data Terintegrasi
                </small>
            </div>
            <!-- View Switcher (Kartu vs Tabel) -->
            <div class="btn-group btn-group-sm p-0.5 fluktuasi-toggle-group rounded-pill border shadow-xs" role="group" id="fluktuasiViewToggle" aria-label="Pilihan Tampilan">
                <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 py-1 fw-semibold btn-view-mode active" id="btnViewCards" onclick="switchFluktuasiView('cards')" title="Tampilan Kartu Ringkasan (Nyaman di Mobile)">
                    <i class="fas fa-th-large me-1"></i><span>Mode Kartu</span> <span class="badge bg-primary bg-opacity-10 text-primary ms-1 d-none d-sm-inline" style="font-size: 9.5px; font-weight: 700;">Mobile</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 py-1 fw-semibold btn-view-mode" id="btnViewTable" onclick="switchFluktuasiView('table')" title="Tampilan Tabel Matriks Spreadsheet">
                    <i class="fas fa-table-list me-1"></i><span>Tabel Matriks</span>
                </button>
            </div>
        </div>

        <!-- 1. TAMPILAN KARTU FLUKTUASI (Mobile-Friendly Executive Cards) -->
        <div id="fluktuasiCardsView" class="p-2 p-sm-3">
            <!-- Ringkasan Akumulasi Wilayah (Executive Summary Banner) -->
            <div class="card border-0 mb-4 text-white shadow-sm fluktuasi-summary-banner">
                <div class="card-body p-3 p-sm-3.5">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center flex-wrap gap-1.5">
                            <span class="badge bg-white text-primary px-2.5 py-1 fw-bold shadow-xs" style="font-size: 11px;">
                                <i class="fas fa-shield-alt me-1"></i>TOTAL SE-WILAYAH BANTEN
                            </span>
                            {!! renderOvercrowdingBadge($summary['total_penghuni_curr'], $summary['total_kapasitas']) !!}
                        </div>
                        <span class="fw-bold text-white" style="font-size: 12px;">
                            {{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }} &rarr; {{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}
                        </span>
                    </div>

                    @if(!empty($summary['upt_belum_input_curr_list']))
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-25 text-white py-2 px-3 mb-3 rounded-3 small d-flex align-items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-warning flex-shrink-0"></i>
                        <div class="text-start" style="font-size: 11.5px;">
                            <strong>{{ count($summary['upt_belum_input_curr_list']) }} UPT Belum Menginput Hari Ini:</strong>
                            <span class="opacity-90">{{ implode(', ', $summary['upt_belum_input_curr_list']) }}</span>.
                            <span class="d-block opacity-75 mt-0.5" style="font-size: 10.5px;">Fluktuasi UPT tersebut ditangguhkan untuk mencegah selisih semu pada data wilayah.</span>
                        </div>
                    </div>
                    @endif

                    <div class="row g-1 g-sm-2 text-center">
                        <!-- Box 1: Total Tahanan -->
                        <div class="col-4">
                            <div class="fluktuasi-banner-card h-100">
                                <span class="fluktuasi-banner-label d-block text-truncate">TOTAL TAHANAN</span>
                                <div class="fluktuasi-banner-val my-0.5">{{ number_format($summary['total_tahanan_curr']) }}</div>
                                <div class="mt-0.5">
                                    @if($summary['delta_tahanan'] > 0)
                                        <span class="badge badge-delta-neg">+{{ number_format($summary['delta_tahanan']) }}</span>
                                    @elseif($summary['delta_tahanan'] < 0)
                                        <span class="badge badge-delta-pos">{{ number_format($summary['delta_tahanan']) }}</span>
                                    @else
                                        <span class="badge badge-delta-zero">0</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Box 2: Total Napi -->
                        <div class="col-4">
                            <div class="fluktuasi-banner-card h-100">
                                <span class="fluktuasi-banner-label d-block text-truncate">TOTAL NAPI</span>
                                <div class="fluktuasi-banner-val my-0.5">{{ number_format($summary['total_napi_curr']) }}</div>
                                <div class="mt-0.5">
                                    @if($summary['delta_napi'] > 0)
                                        <span class="badge badge-delta-neg">+{{ number_format($summary['delta_napi']) }}</span>
                                    @elseif($summary['delta_napi'] < 0)
                                        <span class="badge badge-delta-pos">{{ number_format($summary['delta_napi']) }}</span>
                                    @else
                                        <span class="badge badge-delta-zero">0</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Box 3: Total Penghuni -->
                        <div class="col-4">
                            <div class="fluktuasi-banner-card fluktuasi-banner-card-total h-100">
                                <span class="fluktuasi-banner-label-total d-block text-truncate">TOTAL PENGHUNI</span>
                                <div class="fluktuasi-banner-val-total my-0.5">{{ number_format($summary['total_penghuni_curr']) }}</div>
                                <div class="mt-0.5">
                                    @if($summary['delta_penghuni'] > 0)
                                        <span class="badge badge-delta-neg">+{{ number_format($summary['delta_penghuni']) }} Net</span>
                                    @elseif($summary['delta_penghuni'] < 0)
                                        <span class="badge badge-delta-pos">{{ number_format($summary['delta_penghuni']) }} Net</span>
                                    @else
                                        <span class="badge badge-delta-zero">0 Net</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-top border-white border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-1 text-white" style="font-size: 11px;">
                        <span><i class="fas fa-warehouse me-1"></i>Kapasitas: <strong>{{ number_format($summary['total_kapasitas']) }}</strong> &bull; Hunian: <strong>{{ $summary['overcrowding']['tingkat_hunian'] }}%</strong> &bull; <i class="fas fa-door-open me-1"></i>Pengeluaran: <strong>{{ number_format($summary['total_pengurang']) }} Org</strong></span>
                        <span class="opacity-90">{{ number_format($summary['estimasi_tahanan_baru']) }} Tahanan Masuk &bull; {{ number_format($summary['total_bebas']) }} Bebas</span>
                    </div>
                </div>
            </div>

            <!-- Header Pemisah: Rincian Fluktuasi Per UPT -->
            <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 fw-bold" style="font-size: 11px;">
                        <i class="fas fa-building me-1"></i>{{ count($rows) }} UPT
                    </span>
                    <span class="small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Rincian Fluktuasi Per Satuan Kerja</span>
                </div>
                <span class="text-muted small d-none d-sm-inline" style="font-size: 11px;"><i class="fas fa-hand-pointer me-1"></i>Sentuh kartu untuk rincian</span>
            </div>

            <!-- Daftar Kartu Per UPT -->
            <div class="row g-2 g-sm-3">
                @forelse($rows as $idx => $r)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 fluktuasi-card shadow-xs">
                        <!-- Card Header -->
                        <div class="card-header bg-transparent py-2.5 px-3 border-bottom d-flex justify-content-between align-items-start gap-2" style="min-width: 0;">
                            <div class="d-flex align-items-start gap-2" style="min-width: 0; flex: 1 1 auto;">
                                <span class="fluktuasi-upt-badge mt-0.5">{{ $idx + 1 }}</span>
                                <div style="min-width: 0;">
                                    <h6 class="fluktuasi-upt-title mb-1" title="{{ $r['nama_upt'] }}">
                                        {{ $r['nama_upt'] }}
                                    </h6>
                                    <div class="d-flex align-items-center flex-wrap gap-1">
                                        @if($r['has_input_curr'])
                                            {!! renderOvercrowdingBadge($r['penghuni_curr'], $r['kapasitas']) !!}
                                            <small class="text-muted" style="font-size: 10px;">Kap: {{ number_format($r['kapasitas']) }}</small>
                                        @elseif($r['has_input_prev'])
                                            {!! renderOvercrowdingBadge($r['penghuni_prev'], $r['kapasitas']) !!}
                                            <small class="text-muted" style="font-size: 10px;">(Data Kemarin)</small>
                                        @else
                                            <span class="badge bg-secondary text-white" style="font-size: 10px;">Nihil Data</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if(!$r['has_input_curr'])
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Belum Input</span>
                                @elseif($r['delta_penghuni'] > 0)
                                    <span class="badge badge-net-pos">+{{ $r['delta_penghuni'] }} Net</span>
                                @elseif($r['delta_penghuni'] < 0)
                                    <span class="badge badge-net-neg">{{ $r['delta_penghuni'] }} Net</span>
                                @else
                                    <span class="badge badge-net-zero">0 Net</span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-2.5 p-sm-3">
                            <!-- 3 Grid Metrik Fluktuasi -->
                            <div class="row g-1 g-sm-2 text-center mb-2.5">
                                <!-- Tahanan -->
                                <div class="col-4 px-1">
                                    <div class="fluktuasi-mini-box h-100">
                                        <span class="fluktuasi-box-label d-block text-truncate">1. TAHANAN</span>
                                        @if($r['has_input_curr'])
                                            <div class="fluktuasi-box-val my-1">{{ number_format($r['tahanan_curr']) }}</div>
                                            <div class="mb-1">
                                                @if($r['delta_tahanan'] > 0)
                                                    <span class="badge badge-delta-neg">+{{ $r['delta_tahanan'] }}</span>
                                                @elseif($r['delta_tahanan'] < 0)
                                                    <span class="badge badge-delta-pos">{{ $r['delta_tahanan'] }}</span>
                                                @else
                                                    <span class="badge badge-delta-zero">0</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="fluktuasi-box-val my-1 text-warning fs-6"><i class="fas fa-clock me-0.5"></i>-</div>
                                            <div class="mb-1"><span class="badge badge-delta-zero">-</span></div>
                                        @endif
                                        <span class="fluktuasi-box-sub d-block text-truncate">Lalu: {{ number_format($r['tahanan_prev']) }}</span>
                                    </div>
                                </div>

                                <!-- Narapidana -->
                                <div class="col-4 px-1">
                                    <div class="fluktuasi-mini-box h-100">
                                        <span class="fluktuasi-box-label d-block text-truncate">2. NAPI</span>
                                        @if($r['has_input_curr'])
                                            <div class="fluktuasi-box-val my-1">{{ number_format($r['napi_curr']) }}</div>
                                            <div class="mb-1">
                                                @if($r['delta_napi'] > 0)
                                                    <span class="badge badge-delta-neg">+{{ $r['delta_napi'] }}</span>
                                                @elseif($r['delta_napi'] < 0)
                                                    <span class="badge badge-delta-pos">{{ $r['delta_napi'] }}</span>
                                                @else
                                                    <span class="badge badge-delta-zero">0</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="fluktuasi-box-val my-1 text-warning fs-6"><i class="fas fa-clock me-0.5"></i>-</div>
                                            <div class="mb-1"><span class="badge badge-delta-zero">-</span></div>
                                        @endif
                                        <span class="fluktuasi-box-sub d-block text-truncate">Lalu: {{ number_format($r['napi_prev']) }}</span>
                                    </div>
                                </div>

                                <!-- Total Penghuni -->
                                <div class="col-4 px-1">
                                    <div class="fluktuasi-mini-box fluktuasi-mini-box-total h-100">
                                        <span class="fluktuasi-box-label-total d-block text-truncate">3. TOTAL</span>
                                        @if($r['has_input_curr'])
                                            <div class="fluktuasi-box-val-total my-1">{{ number_format($r['penghuni_curr']) }}</div>
                                            <div class="mb-1">
                                                @if($r['delta_penghuni'] > 0)
                                                    <span class="badge badge-delta-neg">+{{ $r['delta_penghuni'] }}</span>
                                                @elseif($r['delta_penghuni'] < 0)
                                                    <span class="badge badge-delta-pos">{{ $r['delta_penghuni'] }}</span>
                                                @else
                                                    <span class="badge badge-delta-zero">0</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="fluktuasi-box-val-total my-1 text-warning fs-6"><i class="fas fa-clock me-0.5"></i>Pending</div>
                                            <div class="mb-1"><span class="badge badge-delta-zero">Pending</span></div>
                                        @endif
                                        <span class="fluktuasi-box-sub-total d-block text-truncate">Lalu: {{ number_format($r['penghuni_prev']) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pengeluaran Harian Strip -->
                            <div class="fluktuasi-strip d-flex justify-content-between align-items-center flex-wrap gap-1 mb-2">
                                <span class="fluktuasi-strip-label">
                                    <i class="fas fa-door-open me-1 text-success"></i>Pengeluaran:
                                </span>
                                <div class="d-flex align-items-center flex-wrap gap-1">
                                    @if($r['total_pengurang'] > 0)
                                        <span class="badge badge-strip-total">{{ $r['total_pengurang'] }} Org</span>
                                        @if($r['mutasi_keluar'] > 0)
                                            <span class="badge badge-strip-mutasi">{{ $r['mutasi_keluar'] }} Mutasi</span>
                                        @endif
                                        @if($r['bdh'] > 0)
                                            <span class="badge badge-strip-bdh">{{ $r['bdh'] }} BDH</span>
                                        @endif
                                        @if($r['lainnya'] > 0)
                                            <span class="badge badge-strip-mutasi" title="{{ implode('; ', $r['rincian_lainnya']) }}">{{ $r['lainnya'] }} Lainnya</span>
                                        @endif
                                    @else
                                        <span class="fluktuasi-strip-nihil">0 (Nihil)</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Analisis Dinamika -->
                            @if(!empty($r['analisis']))
                            <div class="mb-2 fluktuasi-analisis-box">
                                <span class="fw-bold fluktuasi-analisis-title d-block mb-1">
                                    <i class="fas fa-info-circle me-1"></i>Analisis &amp; Dinamika:
                                </span>
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-1 ps-1">
                                    @foreach($r['analisis'] as $an)
                                        <li>{!! $an !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <!-- Action Button -->
                            <button type="button" class="btn btn-breakdown-upt btn-sm w-100 rounded-pill py-1.5 shadow-xs" onclick='bukaModalDetailFluktuasi(@json($r))'>
                                <i class="fas fa-eye me-1"></i>Lihat Breakdown Rincian UPT
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                    <h6>Tidak ada data untuk tanggal yang dipilih.</h6>
                </div>
                @endforelse
            </div>
        </div>

        <!-- 2. TAMPILAN TABEL MATRIKS (Classic Spreadsheet Table) -->
        <div id="fluktuasiTableView">
            <div class="scroll-hint-mobile mx-3 mt-3">
                <i class="fas fa-arrows-left-right text-primary me-1"></i>
                <span>Geser ke kanan untuk melihat rincian Narapidana, Pengeluaran &amp; Analisis. Kolom No &amp; UPT terkunci rapat di kiri.</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-fluktuasi mb-0 align-middle">
                    <thead class="table-light text-secondary text-center">
                        <tr>
                            <th rowspan="2" class="col-sticky-no text-center">No</th>
                            <th rowspan="2" class="col-sticky-upt text-start">Unit Pelaksana Teknis (UPT)</th>
                        <th colspan="3" class="th-col-tahanan">1. Tahanan (Overstaying A1-A5)</th>
                        <th colspan="3" class="th-col-napi">2. Narapidana</th>
                        <th colspan="3" class="th-col-total">3. Total Penghuni</th>
                        <th rowspan="2" style="min-width: 140px;">
                            4. Pengeluaran (Bebas &amp; Mutasi)
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
                        <td class="text-center text-muted fw-bold col-sticky-no">{{ $idx + 1 }}</td>
                        <td class="fw-semibold text-dark text-start col-sticky-upt">
                            <div>{{ $r['nama_upt'] }}</div>
                            <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                @if($r['has_input_curr'])
                                    {!! renderOvercrowdingBadge($r['penghuni_curr'], $r['kapasitas']) !!}
                                    <small class="text-muted" style="font-size: 10.5px;">Kap: {{ number_format($r['kapasitas']) }}</small>
                                @elseif($r['has_input_prev'])
                                    {!! renderOvercrowdingBadge($r['penghuni_prev'], $r['kapasitas']) !!}
                                    <small class="text-muted" style="font-size: 10px;">(Data Kemarin)</small>
                                @else
                                    <span class="badge bg-secondary text-white" style="font-size: 10px;">Nihil Data</span>
                                @endif
                            </div>
                        </td>

                        <!-- Tahanan -->
                        <td class="text-center text-muted">{{ number_format($r['tahanan_prev']) }}</td>
                        <td class="text-center fw-bold">
                            @if($r['has_input_curr'])
                                {{ number_format($r['tahanan_curr']) }}
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fas fa-clock me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!$r['has_input_curr'])
                                <span class="badge badge-delta-zero px-2 py-1">-</span>
                            @elseif($r['delta_tahanan'] > 0)
                                <span class="badge badge-delta-neg px-2 py-1">+{{ $r['delta_tahanan'] }}</span>
                            @elseif($r['delta_tahanan'] < 0)
                                <span class="badge badge-delta-pos px-2 py-1">{{ $r['delta_tahanan'] }}</span>
                            @else
                                <span class="badge badge-delta-zero px-2 py-1">0</span>
                            @endif
                        </td>

                        <!-- Narapidana -->
                        <td class="text-center text-muted">{{ number_format($r['napi_prev']) }}</td>
                        <td class="text-center fw-bold">
                            @if($r['has_input_curr'])
                                {{ number_format($r['napi_curr']) }}
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fas fa-clock me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!$r['has_input_curr'])
                                <span class="badge badge-delta-zero px-2 py-1">-</span>
                            @elseif($r['delta_napi'] > 0)
                                <span class="badge badge-delta-neg px-2 py-1">+{{ $r['delta_napi'] }}</span>
                            @elseif($r['delta_napi'] < 0)
                                <span class="badge badge-delta-pos px-2 py-1">{{ $r['delta_napi'] }}</span>
                            @else
                                <span class="badge badge-delta-zero px-2 py-1">0</span>
                            @endif
                        </td>

                        <!-- Total Penghuni -->
                        <td class="text-center text-muted fw-semibold">{{ number_format($r['penghuni_prev']) }}</td>
                        <td class="text-center fw-bold text-primary">
                            @if($r['has_input_curr'])
                                {{ number_format($r['penghuni_curr']) }}
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fas fa-clock me-1"></i>Belum Input</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!$r['has_input_curr'])
                                <span class="badge bg-secondary text-white px-2 py-1">Pending</span>
                            @elseif($r['delta_penghuni'] > 0)
                                <span class="badge bg-danger text-white px-2 py-1 fw-bold">+{{ $r['delta_penghuni'] }}</span>
                            @elseif($r['delta_penghuni'] < 0)
                                <span class="badge bg-success text-white px-2 py-1 fw-bold">{{ $r['delta_penghuni'] }}</span>
                            @else
                                <span class="badge bg-secondary text-white px-2 py-1">0</span>
                            @endif
                        </td>

                        <!-- Pengeluaran Harian (Bebas & Mutasi) -->
                        <td class="text-center">
                            @if($r['total_pengurang'] > 0)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                    {{ $r['total_pengurang'] }} Orang
                                </span>
                                @if($r['mutasi_keluar'] > 0)
                                <div class="mt-1">
                                    <span class="badge bg-secondary text-white" style="font-size: 10px;">{{ $r['mutasi_keluar'] }} Mutasi Keluar</span>
                                </div>
                                @endif
                                @if($r['bdh'] > 0)
                                <div class="mt-1">
                                    <span class="badge bg-danger text-white" style="font-size: 10px;">{{ $r['bdh'] }} BDH</span>
                                </div>
                                @endif
                                @if($r['lainnya'] > 0)
                                <div class="mt-1">
                                    <span class="badge bg-secondary text-white" style="font-size: 10px;" title="{{ implode('; ', $r['rincian_lainnya']) }}">{{ $r['lainnya'] }} Lainnya</span>
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
                        <td colspan="2" class="text-start col-sticky-total">
                            <div>TOTAL SE-WILAYAH BANTEN</div>
                            <div class="mt-1 d-flex align-items-center gap-1.5 flex-wrap font-monospace fw-normal" style="font-size: 10.5px;">
                                {!! renderOvercrowdingBadge($summary['total_penghuni_curr'], $summary['total_kapasitas']) !!}
                                <span class="text-light opacity-75">Kapasitas: {{ number_format($summary['total_kapasitas']) }}</span>
                            </div>
                        </td>
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
                        <td>{{ number_format($summary['total_pengurang']) }} Orang</td>
                        <td colspan="2" class="text-start small fw-normal">
                            <strong>Akumulasi Wilayah:</strong> 
                            {{ number_format($summary['estimasi_tahanan_baru']) }} Tahanan Masuk &bull; 
                            {{ number_format($summary['estimasi_alih_status']) }} Vonis Inkrah &bull; 
                            {{ number_format($summary['total_bebas']) }} Bebas
                            @if($summary['total_mutasi_keluar'] > 0)
                                &bull; {{ number_format($summary['total_mutasi_keluar']) }} Mutasi Keluar
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Modal Breakdown Interaktif Rincian Fluktuasi -->
<div class="modal fade" id="modalDetailFluktuasi" tabindex="-1" aria-labelledby="modalDetailFluktuasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2.5 px-3 d-flex align-items-start justify-content-between gap-2">
                <div class="pe-2" style="min-width: 0; flex: 1 1 auto;">
                    <h5 class="modal-title fw-bold d-flex align-items-center fs-6 mb-1 text-truncate" id="modalDetailFluktuasiLabel">
                        <i class="fas fa-balance-scale text-warning me-2 flex-shrink-0"></i>
                        <span>Rincian Fluktuasi Penghuni</span>
                    </h5>
                    <div id="mdf_upt_title" class="text-white small" style="min-width: 0; word-break: break-word;"></div>
                </div>
                <button type="button" class="btn-close btn-close-white flex-shrink-0 ms-1 mt-1" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
            </div>
            <div class="modal-body p-3 p-sm-4">
                <!-- Nav Tabs -->
                <ul class="nav nav-pills nav-fill mb-3 gap-1 flex-column flex-sm-row" id="fluktuasiTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small fw-bold py-2 text-start text-sm-center" id="tab-overstaying-btn" data-bs-toggle="pill" data-bs-target="#tab-overstaying" type="button" role="tab">
                            <i class="fas fa-clock me-1"></i>Tahap Penahanan (A.I - A.V)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small fw-bold py-2 text-start text-sm-center" id="tab-napi-btn" data-bs-toggle="pill" data-bs-target="#tab-napi" type="button" role="tab">
                            <i class="fas fa-users me-1"></i>Komparasi Narapidana
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small fw-bold py-2 text-start text-sm-center" id="tab-bebas-btn" data-bs-toggle="pill" data-bs-target="#tab-bebas" type="button" role="tab">
                            <i class="fas fa-door-open me-1"></i>Rincian Bebas &amp; Mutasi
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

                    <!-- Tab 3: Rincian Bebas & Mutasi Rekonsiliasi -->
                    <div class="tab-pane fade" id="tab-bebas" role="tabpanel">
                        <!-- Alert Box Komparasi & Rekonsiliasi Bebas & Mutasi -->
                        <div id="mdf_rekonsiliasi_box" class="mb-3">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <div class="small fw-semibold text-secondary mb-2">
                            <i class="fas fa-list-alt me-1"></i>Kategori Pengeluaran (Bebas &amp; Mutasi) Kemarin ({{ \Carbon\Carbon::parse($tglBebasMulai)->translatedFormat('d M Y') }}):
                        </div>
                        <div class="row g-2 text-center" id="mdf_bebas_container">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
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
    let ocBadge = '';
    if (row.overcrowding && row.overcrowding.status) {
        ocBadge = `<span class="badge ${row.overcrowding.badge_class} px-2 py-1 shadow-xs" title="${row.overcrowding.keterangan || ''}"><i class="fas ${row.overcrowding.icon} me-1"></i>${row.overcrowding.status} (${row.overcrowding.tingkat_hunian}%)</span>`;
    }

    let kapText = '';
    if (row.has_input_curr) {
        kapText = `<span class="badge badge-kapasitas-pill px-2 py-1 shadow-xs" style="background: rgba(255,255,255,0.22) !important; color: #ffffff !important; border: 1px solid rgba(255,255,255,0.45) !important;"><i class="fas fa-warehouse me-1"></i>Kap: ${(row.kapasitas || 0).toLocaleString()} &bull; Isi: ${(row.penghuni_curr || 0).toLocaleString()}</span>`;
    } else if (row.has_input_prev) {
        kapText = `<span class="badge badge-kapasitas-pill px-2 py-1 shadow-xs" style="background: rgba(255,255,255,0.22) !important; color: #ffffff !important; border: 1px solid rgba(255,255,255,0.45) !important;"><i class="fas fa-warehouse me-1"></i>Kap: ${(row.kapasitas || 0).toLocaleString()} &bull; Kemarin: ${(row.penghuni_prev || 0).toLocaleString()}</span>` +
                  `<span class="badge bg-warning text-dark px-2 py-1 shadow-xs"><i class="fas fa-clock me-1"></i>Belum Input Hari Ini</span>`;
    } else {
        kapText = `<span class="badge bg-warning text-dark px-2 py-1 shadow-xs"><i class="fas fa-clock me-1"></i>Belum Ada Data</span>`;
    }

    document.getElementById('mdf_upt_title').innerHTML = 
        `<div class="d-flex flex-wrap align-items-center gap-1.5 mt-1" style="min-width: 0;">` +
            `<span class="badge bg-warning text-dark fw-bold px-2 py-1 text-wrap text-start shadow-xs" style="line-height: 1.35; max-width: 100%; word-break: break-word;">${row.nama_upt}</span>` +
            ocBadge +
            kapText +
            `<span class="text-white small text-nowrap"><i class="fas fa-calendar-alt me-1 text-warning"></i>{{ \Carbon\Carbon::parse($tanggalPembanding)->format('d/m') }} &rarr; {{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}</span>` +
        `</div>`;

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
    if (row.has_input_curr) {
        document.getElementById('mdf_tot_curr').textContent = row.tahanan_curr.toLocaleString();
        document.getElementById('mdf_tot_delta').innerHTML = formatBadge(row.delta_tahanan);
    } else {
        document.getElementById('mdf_tot_curr').innerHTML = '<span class="badge bg-warning text-dark">Pending</span>';
        document.getElementById('mdf_tot_delta').innerHTML = '<span class="text-muted">-</span>';
    }

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
                    <td class="fw-bold">${row.has_input_curr ? k.curr.toLocaleString() : '<span class="badge bg-warning text-dark">Pending</span>'}</td>
                    <td>${row.has_input_curr ? formatBadge(k.delta) : '<span class="text-muted">-</span>'}</td>
                    <td>${row.has_input_curr ? ket : '<span class="badge bg-warning text-dark">Pending</span>'}</td>
                </tr>
            `;
        });
    }
    napiTbody.innerHTML = napiRowsHtml;
    document.getElementById('mdf_napi_tot_prev').textContent = row.napi_prev.toLocaleString();
    if (row.has_input_curr) {
        document.getElementById('mdf_napi_tot_curr').textContent = row.napi_curr.toLocaleString();
        document.getElementById('mdf_napi_tot_delta').innerHTML = formatBadge(row.delta_napi);
        document.getElementById('mdf_napi_tot_ket').innerHTML = row.delta_napi < 0 
            ? `<span class="text-danger fw-bold">Berkurang ${Math.abs(row.delta_napi)}</span>` 
            : (row.delta_napi > 0 ? `<span class="text-success fw-bold">Bertambah ${row.delta_napi}</span>` : '<span class="text-muted">Stabil</span>');
    } else {
        document.getElementById('mdf_napi_tot_curr').innerHTML = '<span class="badge bg-warning text-dark">Pending</span>';
        document.getElementById('mdf_napi_tot_delta').innerHTML = '<span class="text-muted">-</span>';
        document.getElementById('mdf_napi_tot_ket').innerHTML = '<span class="badge bg-warning text-dark">Laporan Belum Diinput</span>';
    }

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
                    <span class="fs-6 fw-bold ${row.has_input_curr ? 'text-primary' : 'text-warning'}">
                        ${row.has_input_curr ? row.napi_curr.toLocaleString() + ' Orang' : 'Belum Diinput'}
                    </span>
                </div>
                <div class="col-sm-4 text-center">
                    <small class="text-muted d-block" style="font-size: 11px;">Perubahan Net</small>
                    <span class="fs-6 fw-bold ${!row.has_input_curr ? 'text-muted' : (row.delta_napi < 0 ? 'text-danger' : (row.delta_napi > 0 ? 'text-success' : 'text-secondary'))}">
                        ${!row.has_input_curr ? 'Pending' : ((row.delta_napi > 0 ? '+' : '') + row.delta_napi + ' Orang')}
                    </span>
                </div>
            </div>
        </div>
    `;

    const pengurangNapi = (row.pengurang_napi !== undefined) ? row.pengurang_napi : ((row.bebas_napi || 0) + (row.mutasi_keluar || 0));
    const totalPengurang = (row.total_pengurang !== undefined) ? row.total_pengurang : ((row.total_bebas || 0) + (row.mutasi_keluar || 0));
    const mutasiKeluar = row.mutasi_keluar || 0;

    if (!row.has_input_curr) {
        rekonHtml += `
            <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                <i class="fas fa-clock text-warning fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Laporan Penghuni Hari Ini Belum Diinput:</strong><br>
                    Operator UPT belum mengirim laporan penghuni (apel pagi) pada tanggal acuan ini. Analisis perbandingan dan rekonsiliasi fluktuasi ditangguhkan sementara sampai operator menginput data penghuni terbaru untuk mencegah timbulnya selisih semu.
                </div>
            </div>
        `;
    } else if (row.delta_napi < 0) {
        const selisihNapi = Math.abs(row.delta_napi);
        const rincianBerkurang = row.napi_berkurang_text ? ` pada klasifikasi: <strong>${row.napi_berkurang_text}</strong>` : '';

        if (pengurangNapi === 0) {
            rekonHtml += `
                <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                    <i class="fas fa-exclamation-triangle text-warning fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Pengurangan Narapidana Belum Terketerangan di Bebas &amp; Mutasi:</strong><br>
                        Terdapat pengurangan <strong>-${selisihNapi} Narapidana</strong>${rincianBerkurang}, 
                        namun <strong>Laporan Bebas &amp; Mutasi kemarin tercatat 0 (Nihil)</strong>.<br>
                        <span class="text-dark">&bull; <strong>Kemungkinan 1</strong>: Narapidana telah <strong>dipindahkan / mutasi keluar</strong> ke Lapas lain namun belum diinput di laporan.<br>
                        &bull; <strong>Kemungkinan 2</strong>: Pembebasan (Bebas Murni/PB/CB) belum terinput oleh operator pada tanggal tersebut.</span>
                    </div>
                </div>
            `;
        } else if (selisihNapi > pengurangNapi) {
            const gap = selisihNapi - pengurangNapi;
            rekonHtml += `
                <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle text-warning fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Sebagian Pengurangan Belum Terketerangan:</strong><br>
                        Narapidana berkurang <strong>-${selisihNapi} orang</strong>${rincianBerkurang}. 
                        Tercatat <strong>${pengurangNapi} orang di Laporan Bebas &amp; Mutasi</strong> (Bebas: ${row.bebas_napi || 0}, Mutasi: ${mutasiKeluar}), namun masih terdapat selisih <strong>-${gap} orang</strong> di luar laporan (potensi mutasi keluar/ekspirasi lain yang belum dicatat).
                    </div>
                </div>
            `;
        } else if (selisihNapi === pengurangNapi) {
            let labelKlop = `${pengurangNapi} orang`;
            if (mutasiKeluar > 0 && row.bebas_napi > 0) {
                labelKlop += ` (${row.bebas_napi} Bebas + ${mutasiKeluar} Mutasi Keluar)`;
            } else if (mutasiKeluar > 0) {
                labelKlop += ` (${mutasiKeluar} Mutasi Keluar)`;
            } else {
                labelKlop += ` (${row.bebas_napi} Bebas)`;
            }
            rekonHtml += `
                <div class="alert alert-success border-success d-flex align-items-center gap-2 py-2 px-3 small mb-3">
                    <i class="fas fa-check-circle text-success fs-5 flex-shrink-0"></i>
                    <div>
                        <strong>Data Sinkron Sempurna:</strong><br>
                        Pengurangan <strong>-${selisihNapi} Narapidana</strong>${rincianBerkurang} klop 100% dengan <strong>${labelKlop}</strong> pada Laporan Bebas &amp; Mutasi.
                    </div>
                </div>
            `;
        }
    } else if (row.delta_napi === 0 && totalPengurang > 0) {
        rekonHtml += `
            <div class="alert alert-warning border-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
                <i class="fas fa-exclamation-circle text-warning fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Perlu Kroscek Laporan:</strong><br>
                    Tercatat <strong>${totalPengurang} pengeluaran (Bebas/Mutasi)</strong> di laporan harian kemarin, tetapi jumlah narapidana hari ini tetap sama (potensi laporan penghuni belum dipotong atau ada alih status tahanan pengimbang).
                </div>
            </div>
        `;
    }

    rekonBox.innerHTML = rekonHtml;

    // Tab 3: Rincian Box Bebas & Mutasi
    let mutasiDetailTeks = '';
    if (row.rincian_mutasi && row.rincian_mutasi.length > 0) {
        mutasiDetailTeks = `<div class="mt-1 small text-muted text-start" style="font-size: 10.5px;">${row.rincian_mutasi.join('<br>')}</div>`;
    }

    let lainnyaDetailTeks = '';
    if (row.rincian_lainnya && row.rincian_lainnya.length > 0) {
        lainnyaDetailTeks = `<div class="mt-1 small text-muted text-start" style="font-size: 10.5px;">${row.rincian_lainnya.join('<br>')}</div>`;
    }

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
        <div class="col-6">
            <div class="p-2 border rounded bg-secondary bg-opacity-10 border-secondary">
                <small class="text-secondary d-block fw-semibold" style="font-size: 11px;">Mutasi Keluar ke Lapas Lain</small>
                <strong class="fs-6 text-secondary">${row.mutasi_keluar || 0} Orang</strong>
                ${mutasiDetailTeks}
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block" style="font-size: 11px;">Pengeluaran Lainnya</small>
                <strong class="fs-6 text-dark">${row.lainnya || 0} Orang</strong>
                ${lainnyaDetailTeks}
            </div>
        </div>
        <div class="col-12 mt-2">
            <div class="p-2 bg-success bg-opacity-10 border border-success rounded text-success fw-bold d-flex justify-content-around flex-wrap gap-2">
                <span>Total Bebas: <strong>${row.total_bebas} Orang</strong></span>
                <span>&bull;</span>
                <span>Mutasi Keluar: <strong>${row.mutasi_keluar || 0} Orang</strong></span>
                <span>&bull;</span>
                <span>Grand Total Pengeluaran: <strong>${row.total_pengurang || row.total_bebas} Orang</strong></span>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalDetailFluktuasi'));
    modal.show();
}

function switchFluktuasiView(mode) {
    const cardsView = document.getElementById('fluktuasiCardsView');
    const tableView = document.getElementById('fluktuasiTableView');
    const btnCards = document.getElementById('btnViewCards');
    const btnTable = document.getElementById('btnViewTable');

    if (!cardsView || !tableView) return;

    if (mode === 'cards') {
        cardsView.classList.remove('d-none');
        tableView.classList.add('d-none');
        if (btnCards) {
            btnCards.classList.add('active');
            btnCards.classList.remove('text-secondary');
        }
        if (btnTable) {
            btnTable.classList.remove('active');
            btnTable.classList.add('text-secondary');
        }
        localStorage.setItem('fluktuasi_view_mode', 'cards');
    } else {
        cardsView.classList.add('d-none');
        tableView.classList.remove('d-none');
        if (btnTable) {
            btnTable.classList.add('active');
            btnTable.classList.remove('text-secondary');
        }
        if (btnCards) {
            btnCards.classList.remove('active');
            btnCards.classList.add('text-secondary');
        }
        localStorage.setItem('fluktuasi_view_mode', 'table');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const savedMode = localStorage.getItem('fluktuasi_view_mode');
    if (savedMode) {
        switchFluktuasiView(savedMode);
    } else {
        if (window.innerWidth < 768) {
            switchFluktuasiView('cards');
        } else {
            switchFluktuasiView('table');
        }
    }
});
</script>
@endpush

