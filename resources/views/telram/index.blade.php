@extends('layouts.app')

@section('title', 'Kalkulator Telram & Integrasi - Sultan Banten')
@section('page_title', 'Kalkulator Telram & Integrasi')

@push('styles')
<style>
    /* Monospace Numeric Font for Ultra-Accurate Numbers */
    .font-mono-num {
        font-family: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
        font-feature-settings: "tnum" 1;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.2px;
    }

    /* Card Styles for Telram - Modern Glassmorphic Elevated Surfaces */
    .telram-card {
        background: rgba(255, 255, 255, 0.92) !important;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.85) !important;
        border-radius: 16px;
        color: var(--text-primary, #0f172a);
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04), 0 8px 10px -6px rgba(15, 23, 42, 0.02);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }
    [data-theme="dark"] .telram-card {
        background: rgba(30, 41, 59, 0.85) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #f8fafc !important;
        box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.45);
    }

    /* Text & Label Visibility */
    .telram-text-title {
        color: var(--text-primary, #0f172a);
    }
    [data-theme="dark"] .telram-text-title,
    [data-theme="dark"] .text-dark {
        color: #f1f5f9 !important;
    }

    [data-theme="dark"] .form-label {
        color: #f1f5f9 !important;
    }

    [data-theme="dark"] .text-muted {
        color: #94a3b8 !important;
    }

    /* Mode Switcher Grid: macOS / Linear Style Segmented Controller */
    .mode-switch-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.03);
    }
    [data-theme="dark"] .mode-switch-grid {
        background: #0b1329 !important;
        border-color: #1e293b !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
    }

    .btn-mode-tab {
        background: transparent;
        color: #64748b;
        font-size: 12.5px;
        font-weight: 700;
        border: none;
        border-radius: 10px;
        padding: 10px 12px;
        text-align: center;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        line-height: 1.25;
        white-space: nowrap;
        position: relative;
    }
    .btn-mode-tab:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.7);
        transform: translateY(-1px);
    }
    [data-theme="dark"] .btn-mode-tab {
        color: #94a3b8;
    }
    [data-theme="dark"] .btn-mode-tab:hover {
        color: #ffffff;
        background: rgba(51, 65, 85, 0.6);
    }
    .btn-mode-tab.active {
        background: #ffffff;
        color: #1e40af;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.04);
        transform: translateY(0);
    }
    [data-theme="dark"] .btn-mode-tab.active {
        background: #1e293b;
        color: #60a5fa;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Parameter Box Containers - Tinted Layered Surfaces */
    .param-group-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 15px;
        margin-bottom: 14px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .param-group-box:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .param-box-wbp {
        border-left: 4px solid #6366f1 !important;
        background: linear-gradient(to right, rgba(99, 102, 241, 0.03), #ffffff 20%);
    }
    .param-box-vonis {
        border-left: 4px solid #2563eb !important;
        background: linear-gradient(to right, rgba(37, 99, 235, 0.03), #ffffff 20%);
    }
    [data-theme="dark"] .param-group-box {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: none;
    }
    [data-theme="dark"] .param-box-wbp {
        border-left: 4px solid #818cf8 !important;
        background: linear-gradient(to right, rgba(99, 102, 241, 0.12), #1e293b 25%) !important;
    }
    [data-theme="dark"] .param-box-vonis {
        border-left: 4px solid #3b82f6 !important;
        background: linear-gradient(to right, rgba(37, 99, 235, 0.12), #1e293b 25%) !important;
    }

    .param-group-title {
        font-size: 11.5px;
        font-weight: 800;
        letter-spacing: 0.4px;
        text-transform: uppercase;
    }
    [data-theme="dark"] .param-group-title {
        color: #60a5fa !important;
    }

    /* Stepper Touch Buttons with Tactile Animation */
    .btn-stepper {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        padding: 0;
        transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        user-select: none;
        flex-shrink: 0;
    }
    .btn-stepper:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    .btn-stepper:active {
        transform: scale(0.90);
        background: #cbd5e1;
    }
    [data-theme="dark"] .btn-stepper {
        background: #334155;
        border-color: #475569;
        color: #f8fafc;
    }
    [data-theme="dark"] .btn-stepper:hover {
        background: #475569;
        border-color: #64748b;
        color: #ffffff;
    }
    [data-theme="dark"] .btn-stepper:active {
        background: #1e293b;
    }

    /* Collapsible Accordion Parameter Box */
    .param-accordion-box {
        background: #ffffff;
        border: 1px solid #cbd5e1 !important;
        border-left: 4px solid #10b981 !important;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    [data-theme="dark"] .param-accordion-box {
        background: rgba(15, 23, 42, 0.6) !important;
        border-color: #334155 !important;
        border-left: 4px solid #10b981 !important;
    }
    .transition-icon {
        transition: transform 0.25s ease;
    }
    [aria-expanded="true"] .transition-icon {
        transform: rotate(180deg);
    }

    /* Form Controls & Inputs - Reliable Cross-Browser Form Select */
    .form-select,
    select.form-select {
        display: block;
        width: 100%;
        padding: 0.5rem 2.25rem 0.5rem 0.75rem !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        line-height: normal !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 14px 10px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        min-height: 40px !important;
        height: 40px !important;
        box-sizing: border-box !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }
    .form-select option,
    select.form-select option {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        padding: 6px 10px !important;
    }
    .form-select:focus,
    select.form-select:focus {
        border-color: #3b82f6 !important;
        outline: 0 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }
    [data-theme="dark"] .form-control {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] .form-select,
    [data-theme="dark"] select.form-select {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
    }
    [data-theme="dark"] .form-select option,
    [data-theme="dark"] select.form-select option {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] input[type="date"] {
        color-scheme: dark;
    }
    [data-theme="dark"] .input-group-text {
        background-color: #334155 !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .btn-outline-secondary {
        color: #cbd5e1 !important;
        border-color: #475569 !important;
    }
    [data-theme="dark"] .btn-outline-secondary:hover {
        background-color: #334155 !important;
        color: #ffffff !important;
    }
    [data-theme="dark"] .btn-outline-primary {
        color: #93c5fd !important;
        border-color: #3b82f6 !important;
    }
    [data-theme="dark"] .btn-outline-primary:hover {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    /* Stepper Box (Tahun, Bulan, Hari) */
    .stepper-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 6px 4px;
        text-align: center;
        transition: all 0.2s ease;
    }
    .stepper-box:focus-within {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    [data-theme="dark"] .stepper-box {
        background: rgba(15, 23, 42, 0.7) !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .stepper-box:focus-within {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
    }
    .stepper-box input {
        color: var(--text-primary, #1f2937) !important;
        font-size: 19px;
    }
    .stepper-box input:focus {
        outline: none;
        box-shadow: none;
    }
    .stepper-box input::-webkit-outer-spin-button,
    .stepper-box input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .stepper-box input[type=number] {
        -moz-appearance: textfield;
    }
    [data-theme="dark"] .stepper-box input {
        color: #f8fafc !important;
    }
    .stepper-label {
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    [data-theme="dark"] .stepper-label {
        color: #94a3b8;
    }

    /* Mobile Sticky Bottom Bar */
    .mobile-sticky-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-top: 1px solid #e2e8f0;
        z-index: 1030;
        padding-bottom: max(6px, env(safe-area-inset-bottom));
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    }
    [data-theme="dark"] .mobile-sticky-action-bar {
        background: rgba(15, 23, 42, 0.95);
        border-color: #334155;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
    }
    @media (max-width: 991.98px) {
        body {
            padding-bottom: 74px !important;
        }
    }

    /* Placeholder Section */
    .placeholder-card {
        background: var(--card-bg, #ffffff);
        border: 2px dashed #cbd5e1 !important;
        border-radius: 14px;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    [data-theme="dark"] .placeholder-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    /* Fraction Cards (1/3, 1/2, 2/3) - Modern Metrics Dashboard */
    .fraction-card-blue,
    .fraction-card-amber,
    .fraction-card-emerald {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
    }
    .fraction-card-blue:hover,
    .fraction-card-amber:hover,
    .fraction-card-emerald:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.08);
    }
    .fraction-card-blue {
        background: linear-gradient(to bottom, rgba(59, 130, 246, 0.08), rgba(255, 255, 255, 0.95) 45%);
        border: 1px solid rgba(59, 130, 246, 0.2) !important;
        border-top: 4px solid #3b82f6 !important;
    }
    [data-theme="dark"] .fraction-card-blue {
        background: linear-gradient(to bottom, rgba(59, 130, 246, 0.18), #1e293b 50%) !important;
        border: 1px solid rgba(59, 130, 246, 0.3) !important;
        border-top: 4px solid #3b82f6 !important;
    }

    .fraction-card-amber {
        background: linear-gradient(to bottom, rgba(245, 158, 11, 0.08), rgba(255, 255, 255, 0.95) 45%);
        border: 1px solid rgba(245, 158, 11, 0.2) !important;
        border-top: 4px solid #f59e0b !important;
    }
    [data-theme="dark"] .fraction-card-amber {
        background: linear-gradient(to bottom, rgba(245, 158, 11, 0.18), #1e293b 50%) !important;
        border: 1px solid rgba(245, 158, 11, 0.3) !important;
        border-top: 4px solid #f59e0b !important;
    }

    .fraction-card-emerald {
        background: linear-gradient(to bottom, rgba(16, 185, 129, 0.08), rgba(255, 255, 255, 0.95) 45%);
        border: 1px solid rgba(16, 185, 129, 0.2) !important;
        border-top: 4px solid #10b981 !important;
    }
    [data-theme="dark"] .fraction-card-emerald {
        background: linear-gradient(to bottom, rgba(16, 185, 129, 0.18), #1e293b 50%) !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
        border-top: 4px solid #10b981 !important;
    }

    .fraction-date-val {
        color: var(--text-primary, #1f2937);
        font-size: clamp(15px, 3vw, 17px);
        letter-spacing: -0.3px;
        word-break: break-word;
    }
    [data-theme="dark"] .fraction-date-val {
        color: #f8fafc !important;
    }

    /* Summary Info Box */
    .summary-info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 12.5px;
    }
    [data-theme="dark"] .summary-info-box {
        background: rgba(15, 23, 42, 0.6) !important;
        border-color: #334155 !important;
    }
    .summary-info-box strong {
        color: var(--text-primary, #1f2937);
    }
    [data-theme="dark"] .summary-info-box strong {
        color: #f8fafc !important;
    }

    /* Hero Alert Pengusulan PB / CB */
    .hero-usulan-pb {
        background: linear-gradient(135deg, #78350f 0%, #b45309 45%, #d97706 100%);
        color: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(251, 191, 36, 0.35);
        box-shadow: 0 12px 30px -4px rgba(180, 83, 9, 0.35);
        overflow: hidden;
        position: relative;
    }

    /* Table Ledger & Cards */
    .table-ledger {
        font-size: 12.5px;
    }
    [data-theme="dark"] .table-ledger th {
        background-color: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .table-ledger td {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
        border-color: #334155 !important;
    }

    /* Mobile Timeline Cards */
    .timeline-remisi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .timeline-remisi-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    [data-theme="dark"] .timeline-remisi-card {
        background: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }

    [data-theme="dark"] #sectionIdentitasWbp {
        background: rgba(15, 23, 42, 0.6) !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] #sectionDasawarsa {
        background: rgba(180, 83, 9, 0.15) !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }

    /* Print Styles */
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        .header-card, .btn, .mode-switch-grid, #colFormInput, #placeholderSection, #cardQuickActionsToolbar, #viewModeLedgerGroup, .d-print-none {
            display: none !important;
        }
        #hasilSection {
            display: block !important;
        }
        .telram-card {
            border: none !important;
            box-shadow: none !important;
        }
        #colHasilKanan {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-2 px-md-3 px-lg-4 py-2 py-md-3">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-3 mb-md-4 header-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border-radius: 14px;">
        <div class="card-body p-3 px-md-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2.5 gap-md-3">
                    <div style="width: 44px; height: 44px; border-radius: 11px; background: rgba(255,255,255,0.12); display: flex; align-items: center; justify-content: center; font-size: 19px; border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fas fa-calculator text-warning"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="fw-bold mb-0 text-white" style="font-size: clamp(16px, 3.5vw, 20px);">Kalkulator Telram &amp; Integrasi</h4>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-0.5" style="font-size: 10.5px;">SDP DITJENPAS</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 d-none d-sm-inline-block" style="font-size: 10px;">Kanwil Ditjenpas Banten</span>
                        </div>
                        <small class="text-white-50 d-block mt-0.5" style="font-size: 11.5px;">
                            Perhitungan Ekspirasi, Perkiraan Remisi (Keppres 174/1999) &amp; Waktu Pengusulan PB/CB (UU 22/2022)
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnInstallTelramPwa" onclick="installTelramApp()" style="font-size: 11.5px; border-color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.1);">
                        <i class="fas fa-mobile-alt text-warning"></i>
                        <span>Install Aplikasi (PWA)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area: 2 Columns on Desktop, Single Column Responsive on Mobile -->
    <div class="row g-3 g-md-4">
        <!-- Left Column: Input Form (Sticky on Desktop) -->
        <div class="col-lg-5 col-xl-5" id="colFormInput">
            <div class="card border-0 shadow-sm telram-card sticky-lg-top" style="top: 1rem; z-index: 5;">
                <div class="card-header bg-transparent border-bottom p-3 px-md-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 telram-text-title d-flex align-items-center gap-2">
                        <i class="fas fa-edit text-primary"></i> Parameter Perhitungan
                    </h6>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1" onclick="resetForm()" style="font-size: 11px;">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
                <div class="card-body p-3 p-md-4">
                    <!-- Mode Selector Tab: Clean 2x2 Grid -->
                    <div class="mb-3">
                        <label class="stepper-label mb-1.5 d-block">PILIH MODE PERHITUNGAN:</label>
                        <div class="mode-switch-grid">
                            <button type="button" id="btnModeRemisi" onclick="setMode('simulasi_remisi')" class="btn-mode-tab active">
                                Perkiraan Remisi
                            </button>
                            <button type="button" id="btnModeMaju" onclick="setMode('maju')" class="btn-mode-tab">
                                Cari Ekspirasi
                            </button>
                            <button type="button" id="btnModeMundur" onclick="setMode('mundur')" class="btn-mode-tab">
                                Lama Pidana
                            </button>
                            <button type="button" id="btnModeSisa" onclick="setMode('sisa')" class="btn-mode-tab">
                                Sisa Pidana
                            </button>
                        </div>
                    </div>

                    <!-- GRUP 1: Identitas Narapidana / WBP (Khusus Simulasi Remisi & Cetak PDF) -->
                    <div class="param-group-box param-box-wbp mb-3" id="sectionIdentitasWbp">
                        <div class="d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                            <span class="param-group-title text-primary">
                                <i class="fas fa-id-card me-1.5"></i> Identitas WBP
                            </span>
                            <span class="badge bg-light text-secondary border fw-normal" style="font-size: 9.5px;">Opsional / Berkas PDF</span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label telram-text-title fw-semibold mb-1" style="font-size: 11.5px;">Nama Lengkap</label>
                            <input type="text" id="txtNamaWbp" class="form-control fw-semibold" placeholder="Contoh: Budi Santoso bin Ahmad" style="height: 38px; border-radius: 8px; font-size: 12.5px;">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label telram-text-title fw-semibold mb-1" style="font-size: 11.5px;">Nomor Register</label>
                                <input type="text" id="txtNoRegister" class="form-control fw-semibold" placeholder="Contoh: BI.01/24" style="height: 38px; border-radius: 8px; font-size: 12.5px;">
                            </div>
                            <div class="col-6">
                                <label class="form-label telram-text-title fw-semibold mb-1" style="font-size: 11.5px;">Perkara / Pasal</label>
                                <input type="text" id="txtPerkaraPasal" class="form-control fw-semibold" placeholder="Contoh: 363 KUHP" style="height: 38px; border-radius: 8px; font-size: 12.5px;">
                            </div>
                        </div>
                    </div>

                    <!-- GRUP 2: Parameter Penahanan, Vonis Pokok & Agama -->
                    <div class="param-group-box param-box-vonis mb-3" id="groupIdentitasVonis">
                        <div class="d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                            <span class="param-group-title text-primary">
                                <i class="fas fa-balance-scale me-1.5"></i> Periode Penahanan &amp; Vonis
                            </span>
                            <span class="badge bg-primary text-white fw-semibold" style="font-size: 9.5px;">Wajib</span>
                        </div>

                        <!-- Tanggal Mulai Ditahan & Tanggal Putusan Pengadilan -->
                        <div class="row g-2 mb-2">
                            <div class="col-6" id="colTglAwal">
                                <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
                                    <label class="form-label telram-text-title fw-bold mb-0 text-truncate" id="labelTglAwal" style="font-size: 11.5px;" title="Tanggal Mulai Ditahan">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i> <span id="txtLabelTglAwal">Tgl. Ditahan</span> <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none fw-semibold px-1.5 py-0.5" onclick="setTglAwalHariIni()" style="font-size: 10px; line-height: 1.3; cursor: pointer;" title="Klik untuk mengisi tanggal hari ini">
                                        Hari Ini
                                    </button>
                                </div>
                                <input type="date" id="tglAwal" class="form-control fw-semibold font-mono-num" style="height: 38px; border-radius: 8px; font-size: 12.5px;">
                            </div>
                            <div class="col-6" id="wrapTglPutusan">
                                <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
                                    <label class="form-label telram-text-title fw-bold mb-0 text-truncate text-primary" style="font-size: 11.5px;" title="Tanggal Putusan Pengadilan">
                                        <i class="fas fa-gavel me-1"></i> Tgl. Putusan
                                    </label>
                                    <span class="badge bg-light text-secondary border fw-normal px-1.5 py-0.5" style="font-size: 10px; line-height: 1.3;">
                                        Opsional
                                    </span>
                                </div>
                                <input type="date" id="tglPutusan" class="form-control fw-semibold font-mono-num" style="height: 38px; border-radius: 8px; font-size: 12.5px;" title="Tanggal putusan pengadilan / vonis berkekuatan hukum tetap">
                            </div>
                            <div class="col-12" id="helpTglPutusanWrap">
                                <small class="text-muted d-block mt-0.5" style="font-size: 10.5px; line-height: 1.35;">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Tgl. Putusan digunakan untuk menghitung potongan masa tahanan (Potah).
                                </small>
                            </div>
                        </div>

                        <!-- RIWAYAT PENAHANAN & PEMBANTARAN (MULTI-PERIODE) -->
                        <div class="mb-3 pt-2.5 border-top" id="wrapRiwayatPenahananToggle" style="border-color: rgba(0,0,0,0.06) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label telram-text-title fw-bold mb-0 text-primary" style="font-size: 11.5px; cursor: pointer;" for="switchRiwayatPenahanan">
                                    <i class="fas fa-layer-group me-1"></i> Riwayat Penahanan &amp; Pembantaran
                                </label>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="switchRiwayatPenahanan" role="switch" onchange="toggleRiwayatPenahanan()">
                                    <label class="form-check-label text-muted fw-semibold" for="switchRiwayatPenahanan" style="font-size: 10.5px;">Aktifkan</label>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-0.5" style="font-size: 10.5px; line-height: 1.35;">
                                Gunakan jika terdapat penangguhan, pembantaran sakit (RS), tahanan kota (1/5), atau tahanan rumah (1/3).
                            </small>

                            <!-- Container Repeater Riwayat Penahanan -->
                            <div id="containerRiwayatPenahanan" class="mt-2.5 p-2.5 bg-light rounded-3 border" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark" style="font-size: 11.5px;">
                                        <i class="fas fa-list-ol me-1 text-primary"></i> Rincian Segmen Penahanan (Pasal 22 &amp; 31 KUHAP)
                                    </span>
                                    <button type="button" class="btn btn-xs btn-primary py-1 px-2 fw-semibold" onclick="tambahBarisRiwayat()" style="font-size: 10.5px; border-radius: 6px;">
                                        <i class="fas fa-plus me-1"></i> Tambah Periode
                                    </button>
                                </div>

                                <div id="listRiwayatPenahanan" class="d-flex flex-column gap-2">
                                    <!-- Dynamic rows will be rendered here -->
                                </div>

                                <!-- Summary Badge Box -->
                                <div id="summaryRiwayatBadgeBox" class="mt-2.5 p-2.5 bg-white rounded-2 border" style="font-size: 11.5px;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">Total Penahanan (Inklusif):</span>
                                        <span class="fw-bold font-mono-num text-dark" id="txtTotalHariKalender">0 Hari</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1" id="rowJedaKalender" style="display: none;">
                                        <span class="text-muted">Hari di Luar UPT (T2 - T1):</span>
                                        <span class="fw-bold font-mono-num text-danger" id="txtTotalHariJeda">0 Hari (0 Hari Potah)</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1" id="rowLuarUptBreakdown" style="display: none;">
                                        <span class="text-muted" style="font-size: 10.5px;">Rincian Luar UPT:</span>
                                        <span class="fw-semibold font-mono-num text-warning-emphasis" id="txtLuarUptBreakdown" style="font-size: 10.5px;">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-1.5 border-top">
                                        <span class="fw-bold text-success">Total Potongan Tahanan (Potah):</span>
                                        <span class="fw-bold font-mono-num text-success fs-6" id="txtTotalHariPotah">0 Hari</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-1" id="rowTglAwalEfektif" style="display: none;">
                                        <span class="text-muted" style="font-size: 10.5px;">Tgl. Mulai Ditahan Efektif:</span>
                                        <span class="fw-bold font-mono-num text-primary" id="txtTglAwalEfektif" style="font-size: 11px;">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Pidana Pokok (Untuk Mode Maju & Simulasi Remisi) -->
                        <div id="sectionInputVonis" class="mb-3">
                            <label class="form-label telram-text-title fw-bold mb-1.5 d-block" style="font-size: 12px;">
                                Pidana Pokok <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-1">TAHUN</label>
                                        <div class="d-flex align-items-center justify-content-between px-1">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vTahun', -1)" aria-label="Kurangi Tahun"><i class="fas fa-minus"></i></button>
                                            <input type="number" id="vTahun" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0 font-mono-num" style="font-size: 19px;">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vTahun', 1)" aria-label="Tambah Tahun"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-1">BULAN</label>
                                        <div class="d-flex align-items-center justify-content-between px-1">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vBulan', -1)" aria-label="Kurangi Bulan"><i class="fas fa-minus"></i></button>
                                            <input type="number" id="vBulan" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0 font-mono-num" style="font-size: 19px;">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vBulan', 1)" aria-label="Tambah Bulan"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-1">HARI</label>
                                        <div class="d-flex align-items-center justify-content-between px-1">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vHari', -1)" aria-label="Kurangi Hari"><i class="fas fa-minus"></i></button>
                                            <input type="number" id="vHari" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0 font-mono-num" style="font-size: 19px;">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vHari', 1)" aria-label="Tambah Hari"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Pidana Denda & Subsider Kurungan -->
                        <div id="sectionInputSubsider" class="mb-3 pt-2.5 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label telram-text-title fw-bold mb-0" style="font-size: 12px;">
                                    <i class="fas fa-hand-holding-usd me-1 text-primary"></i> Denda &amp; Pidana Subsider
                                </label>
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size: 9.5px;">Opsional</span>
                            </div>

                            <!-- Besaran Denda Nominal (Rupiah) & Status Bayar -->
                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">Besaran Denda (Rp)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text fw-bold text-muted" style="font-size: 12px;">Rp</span>
                                        <input type="text" id="vDendaNominal" class="form-control fw-bold font-mono-num" placeholder="0" style="font-size: 13px;" oninput="formatRupiahInput(this)">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">Status Denda</label>
                                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="Status Denda">
                                        <input type="radio" class="btn-check" name="statusDendaRadio" id="statusDendaBB" value="bb" checked autocomplete="off" onchange="toggleStatusDenda(this.value)">
                                        <label class="btn btn-outline-danger fw-bold" for="statusDendaBB" style="font-size: 11px;" title="Belum Bayar (Menjalani Kurungan Subsider)">BB</label>
                                        
                                        <input type="radio" class="btn-check" name="statusDendaRadio" id="statusDendaSB" value="sb" autocomplete="off" onchange="toggleStatusDenda(this.value)">
                                        <label class="btn btn-outline-success fw-bold" for="statusDendaSB" style="font-size: 11px;" title="Sudah Bayar / Lunas (Kurungan Tidak Dijalani)">SB</label>
                                    </div>
                                    <div id="statusDendaHelper" class="text-danger mt-0.5 text-center" style="font-size: 9.5px; font-weight: 600;">Belum Bayar</div>
                                </div>
                            </div>

                            <!-- Kurungan Pengganti Denda (Subsider) -->
                            <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">Kurungan Pengganti (Subsider)</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-1">TAHUN</label>
                                        <div class="d-flex align-items-center justify-content-between px-1">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vSubTahun', -1)" aria-label="Kurangi Subsider Tahun"><i class="fas fa-minus"></i></button>
                                            <input type="number" id="vSubTahun" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0 font-mono-num" style="font-size: 19px;">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vSubTahun', 1)" aria-label="Tambah Subsider Tahun"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-1">BULAN</label>
                                        <div class="d-flex align-items-center justify-content-between px-1">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vSubBulan', -1)" aria-label="Kurangi Subsider Bulan"><i class="fas fa-minus"></i></button>
                                            <input type="number" id="vSubBulan" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0 font-mono-num" style="font-size: 19px;">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vSubBulan', 1)" aria-label="Tambah Subsider Bulan"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-1">HARI</label>
                                        <div class="d-flex align-items-center justify-content-between px-1">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vSubHari', -1)" aria-label="Kurangi Subsider Hari"><i class="fas fa-minus"></i></button>
                                            <input type="number" id="vSubHari" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0 font-mono-num" style="font-size: 19px;">
                                            <button type="button" class="btn-stepper" onclick="stepVal('vSubHari', 1)" aria-label="Tambah Subsider Hari"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Agama WBP (Khusus Simulasi Remisi) -->
                        <div id="groupRemisiKeagamaan" class="mb-1 pt-2.5 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                            <div id="sectionAgama">
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <label class="form-label telram-text-title fw-bold mb-0" style="font-size: 12px;">
                                        <i class="fas fa-mosque me-1.5 text-primary"></i> Agama WBP <span class="text-danger">*</span>
                                    </label>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 9.5px; font-weight: 600;">
                                        <i class="fas fa-gift me-0.5"></i> Remisi Khusus (RK)
                                    </span>
                                </div>
                                <select id="selAgama" class="form-select fw-semibold">
                                    <option value="islam" selected>Islam — Idul Fitri</option>
                                    <option value="kristen">Kristen Protestan — Natal (25 Des)</option>
                                    <option value="katolik">Katolik — Natal (25 Des)</option>
                                    <option value="hindu">Hindu — Nyepi</option>
                                    <option value="buddha">Buddha — Waisak</option>
                                    <option value="khonghucu">Khonghucu — Tahun Baru Imlek</option>
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size: 10px; line-height: 1.35;">
                                    <i class="fas fa-info-circle me-0.5 text-primary"></i> Menentukan kalender penetapan Remisi Khusus Hari Raya Keagamaan tiap tahunnya.
                                </small>
                            </div>
                        </div>

                        <!-- Input Mode Mundur & Sisa: Tanggal Ekspirasi Input -->
                        <div id="sectionInputEkspirasi" class="d-none mt-2">
                            <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 12px;">
                                Tgl. Ekspirasi (Sebelum Remisi) <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tglEkspirasiInput" class="form-control fw-semibold" style="height: 38px; border-radius: 8px; font-size: 13px;">
                            <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                <i class="fas fa-info-circle me-1"></i> Masukkan tanggal ekspirasi sebelum remisi vonis.
                            </small>
                        </div>
                    </div>

                    <!-- GRUP 3: Opsi Lanjutan SDP Ditjenpas (Collapsible Accordion yang Elegan & Kompak) -->
                    <div class="card param-accordion-box mb-3" id="groupOpsiSdplanjutan">
                        <div class="card-header bg-transparent p-2.5 px-3 border-0 d-flex justify-content-between align-items-center" 
                             role="button" data-bs-toggle="collapse" data-bs-target="#collapseOpsiLanjutan" 
                             aria-expanded="false" aria-controls="collapseOpsiLanjutan" id="btnToggleOpsiLanjutan" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-sliders-h text-primary" style="font-size: 13px;"></i>
                                <span class="fw-bold telram-text-title" style="font-size: 12px;">Pengaturan Lanjutan SDP &amp; Remisi</span>
                                <span class="badge bg-secondary-subtle text-secondary border" id="badgeOpsiLanjutanSummary" style="font-size: 9.5px;">Standar</span>
                            </div>
                            <i class="fas fa-chevron-down text-muted transition-icon" id="iconChevronOpsi"></i>
                        </div>
                        <div class="collapse" id="collapseOpsiLanjutan">
                            <div class="card-body p-2.5 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                                <!-- Input Remisi Sebelumnya (Opsional) -->
                                <div id="sectionRemisiAwal" class="mb-2.5">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label telram-text-title fw-bold mb-0" style="font-size: 12px;">
                                            Remisi SK Terdahulu (Jika Ada)
                                        </label>
                                        <span class="badge bg-light text-secondary border fw-normal" style="font-size: 9.5px;">Opsional</span>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" id="vRemisiAwal" min="0" value="0" class="form-control fw-semibold" placeholder="0" style="height: 38px; border-radius: 8px 0 0 8px; font-size: 13px;" onchange="updateBadgeOpsiLanjutan()">
                                        <span class="input-group-text fw-bold text-muted" style="height: 38px; border-radius: 0 8px 8px 0; font-size: 12px; background: #e2e8f0;">HARI</span>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 10px;">
                                        Diisi jika narapidana mutasi/lanjutan yang telah memiliki perolehan remisi SK sebelumnya.
                                    </small>
                                </div>

                                <!-- Standar Batas Simulasi: 2/3 vs Ekspirasi -->
                                <div id="sectionTargetBasis" class="mb-2.5">
                                    <label class="form-label telram-text-title fw-bold mb-1 d-block" style="font-size: 12px;">
                                        <i class="fas fa-bullseye text-primary me-1"></i> Standar Batas Simulasi:
                                    </label>
                                    <select id="selTargetBasis" class="form-select fw-semibold" onchange="updateHelpTargetBasis(); updateBadgeOpsiLanjutan();">
                                        <option value="2_3" selected>2/3 Masa Pidana (PB / CB)</option>
                                        <option value="ekspirasi">Ekspirasi Akhir (Bebas Murni)</option>
                                    </select>
                                    <small class="text-muted d-block mt-1" style="font-size: 10px; line-height: 1.35;" id="helpTargetBasis">
                                        <i class="fas fa-info-circle text-primary me-0.5"></i> Standar 2/3: Remisi dihitung hingga 2/3 Definitif tercapai saat bebas bersyarat.
                                    </small>
                                </div>

                                <!-- Remisi Dasawarsa (Tiap 10 Tahun Kemerdekaan RI) -->
                                <div id="sectionDasawarsa" class="mb-2.5 p-2.5 rounded-3 border" style="background: rgba(254, 243, 199, 0.4); border-color: #fde68a !important;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <label class="form-check-label fw-bold telram-text-title d-block mb-0" for="chkDasawarsa" style="font-size: 11.5px; cursor: pointer;">
                                                <i class="fas fa-flag text-warning me-1"></i> Remisi Dasawarsa (HUT RI ke-10 Th)
                                            </label>
                                            <small class="text-muted d-block mt-0.5" style="font-size: 9.5px; line-height: 1.3;">
                                                Diberikan tiap 10 tahun (2025, 2035) sebesar 1/12 vonis (maks 3 bln).
                                            </small>
                                        </div>
                                        <div class="form-check form-switch ms-2 mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="chkDasawarsa" checked style="cursor: pointer; width: 2.2em; height: 1.2em;" onchange="updateBadgeOpsiLanjutan()">
                                        </div>
                                    </div>
                                </div>

                                <!-- Remisi Tambahan Independen per Tahun RU (Pasal 4 Keppres 174/1999) -->
                                <div id="sectionRemisiTambahan">
                                    <label class="form-label telram-text-title fw-bold mb-1 d-block" style="font-size: 12px;">
                                        <i class="fas fa-award text-warning me-1.5"></i> Remisi Tambahan (Keppres 174/99):
                                    </label>
                                    <small class="text-muted d-block mb-2" style="font-size: 10px; line-height: 1.35;">
                                        <i class="fas fa-info-circle me-0.5 text-primary"></i> Pilih secara independen tahun RU ke-berapa WBP mendapat Pemuka (+1/3 RU) dan/atau Donor Darah / Jasa Negara (+1/2 RU). Keduanya dapat dipilih bersamaan.
                                    </small>

                                    <!-- 1. Pemuka Lapas (+1/3 RU) -->
                                    <div class="p-2.5 rounded-3 border mb-2" style="background: rgba(37, 99, 235, 0.04); border-color: rgba(37, 99, 235, 0.18) !important;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="chkTambahanPemuka" onchange="togglePanelTambahan('pemuka'); updateBadgeOpsiLanjutan();">
                                            <label class="form-check-label fw-bold text-primary" for="chkTambahanPemuka" style="font-size: 11.5px;">
                                                Pemuka Lapas (+1/3 RU)
                                            </label>
                                        </div>
                                        <div id="wrapTahunPemuka" class="mt-2 pt-2 border-top d-none">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-1">
                                                <span class="text-muted fw-semibold" style="font-size: 10px;">Pilih Tahun RU Pemuka:</span>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1.5" style="font-size: 9.5px;" onclick="setPresetTahunTambahan('pemuka', 'mulai2')">Mulai Th-2 dst</button>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1.5" style="font-size: 9.5px;" onclick="setPresetTahunTambahan('pemuka', 'all')">Semua</button>
                                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1.5" style="font-size: 9.5px;" onclick="setPresetTahunTambahan('pemuka', 'clear')">Kosongkan</button>
                                                </div>
                                            </div>
                                            <div id="chipsTahunPemuka" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>

                                    <!-- 2. Donor Darah / Jasa Negara (+1/2 RU) -->
                                    <div class="p-2.5 rounded-3 border" style="background: rgba(220, 38, 38, 0.04); border-color: rgba(220, 38, 38, 0.18) !important;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="chkTambahanDonor" onchange="togglePanelTambahan('donor'); updateBadgeOpsiLanjutan();">
                                            <label class="form-check-label fw-bold text-danger" for="chkTambahanDonor" style="font-size: 11.5px;">
                                                Donor Darah / Jasa Negara (+1/2 RU)
                                            </label>
                                        </div>
                                        <div id="wrapTahunDonor" class="mt-2 pt-2 border-top d-none">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-1">
                                                <span class="text-muted fw-semibold" style="font-size: 10px;">Pilih Tahun RU Donor/Jasa:</span>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1.5" style="font-size: 9.5px;" onclick="setPresetTahunTambahan('donor', 'mulai2')">Mulai Th-2 dst</button>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1.5" style="font-size: 9.5px;" onclick="setPresetTahunTambahan('donor', 'all')">Semua</button>
                                                    <button type="button" class="btn btn-xs btn-outline-dark py-0 px-1.5" style="font-size: 9.5px;" onclick="setPresetTahunTambahan('donor', 'clear')">Kosongkan</button>
                                                </div>
                                            </div>
                                            <div id="chipsTahunDonor" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Utama Hitung -->
                    <button type="button" id="btnHitungUtama" onclick="hitungSesuaiMode()" class="btn btn-warning text-dark w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="border-radius: 12px; font-size: 14px; letter-spacing: 0.3px;">
                        <i class="fas fa-calculator fs-5"></i>
                        <span>HITUNG PERKIRAAN REMISI &amp; INTEGRASI</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Results & Actions -->
        <div class="col-lg-7 col-xl-7" id="colHasilKanan">
            <!-- Empty Placeholder (When No Calculation Yet) -->
            <div id="placeholderSection" class="card border-0 shadow-sm text-center p-4 p-md-5 placeholder-card">
                <div class="py-3 py-md-4">
                    <div class="mb-3" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(245, 158, 11, 0.1); display: inline-flex; align-items: center; justify-content: center; font-size: 26px; color: #f59e0b;">
                        <i class="fas fa-calculator" id="placeholderIcon"></i>
                    </div>
                    <h5 class="fw-bold telram-text-title mb-2" id="placeholderTitle">Perkiraan Perhitungan</h5>
                    <p class="text-muted mb-4 mx-auto" id="placeholderDesc" style="max-width: 440px; font-size: 13px; line-height: 1.5;">
                        Tentukan Tanggal Mulai Penahanan, Lama Vonis, dan Agama WBP di sebelah kiri, kemudian klik tombol <strong>"HITUNG PERKIRAAN REMISI &amp; INTEGRASI"</strong> untuk melihat perkiraan tanggal ekspirasi baru, waktu pengusulan PB/CB (H-2 bulan), serta tabel rincian remisi berkala.
                    </p>
                    <div class="d-inline-flex gap-2">
                        <button type="button" class="btn btn-outline-warning text-dark btn-sm px-3 rounded-pill fw-semibold" onclick="demoSampleData()">
                            <i class="fas fa-magic me-1"></i> Coba Data Contoh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section (Initially Hidden) -->
            <div id="hasilSection" class="d-none">
                <!-- 1. Quick Action Toolbar Card (Atas Hasil) -->
                <div class="card border-0 shadow-sm mb-3 telram-card" id="cardQuickActionsToolbar">
                    <div class="card-body p-2 px-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" id="badgeProgramStatus" style="font-size: 11.5px; padding: 5px 10px;">PB</span>
                                <span class="fw-bold telram-text-title" id="hasilHeaderTitle" style="font-size: 13.5px;">
                                    Perkiraan Perhitungan
                                </span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-1.5" id="btnGroupAksiHasil">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 shadow-sm fw-semibold" onclick="tampilkanModalHitunganManual()" title="Lihat Rumus &amp; Hitungan Manual Lengkap" style="font-size: 11.5px;">
                                    <i class="fas fa-calculator me-1"></i> Hitungan Manual &amp; Rumus
                                </button>
                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 py-1.5 shadow-sm fw-semibold" onclick="unduhPdfResmi()" title="Unduh Lembar Resmi PDF" style="font-size: 11.5px;">
                                    <i class="fas fa-file-pdf me-1"></i> Unduh PDF
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1.5 fw-semibold" onclick="cetakLembarPerhitungan()" title="Cetak Lembar Perhitungan" style="font-size: 11.5px;">
                                    <i class="fas fa-print me-1"></i> Cetak
                                </button>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-1.5 shadow-sm fw-semibold" onclick="salinRingkasanTeks()" title="Salin Ringkasan untuk WhatsApp" style="font-size: 11.5px;">
                                    <i class="fab fa-whatsapp me-1"></i> Salin WA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.5. Interactive Milestone Timeline Progress Card (Roadmap Visual Perjalanan Pidana) -->
                <div id="boxMilestoneTimeline" class="card border-0 shadow-sm mb-3 telram-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10.5px;">
                                    <i class="fas fa-stream me-1"></i> TIMELINE MASA PIDANA
                                </span>
                                <span class="fw-bold telram-text-title" style="font-size: 12px;">Visualisasi Progres Tahapan</span>
                            </div>
                            <span class="badge bg-primary text-white font-mono-num px-2 py-1" id="badgeProgressHariIni" style="font-size: 10.5px;">Hari Ini: 0%</span>
                        </div>
                        
                        <!-- Progress Bar Track -->
                        <div class="position-relative my-2.5 pt-1">
                            <div class="progress" style="height: 10px; border-radius: 6px; background: #e2e8f0; overflow: visible;">
                                <div id="progressBarHariIni" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; border-radius: 6px; transition: width 0.6s ease;"></div>
                            </div>
                            
                            <!-- Milestone Labels -->
                            <div class="d-flex justify-content-between mt-2 font-mono-num text-center" style="font-size: 10px;">
                                <div style="flex: 1;" class="text-start">
                                    <span class="text-muted d-block" style="font-size: 9.5px;">Ditahan</span>
                                    <strong id="tmTglAwal" class="telram-text-title d-block" style="font-size: 10px;">-</strong>
                                </div>
                                <div style="flex: 1;">
                                    <span class="text-primary d-block fw-bold" style="font-size: 9.5px;">1/3</span>
                                    <strong id="tmTgl13" class="text-primary d-block" style="font-size: 10px;">-</strong>
                                </div>
                                <div style="flex: 1;">
                                    <span class="text-warning-emphasis d-block fw-bold" style="font-size: 9.5px;">1/2</span>
                                    <strong id="tmTgl12" class="text-warning-emphasis d-block" style="font-size: 10px;">-</strong>
                                </div>
                                <div style="flex: 1.3;" class="px-1 py-0.5 rounded bg-warning-subtle border border-warning-subtle">
                                    <span class="text-danger fw-bold d-block" style="font-size: 9.5px;"><i class="fas fa-bullhorn me-0.5"></i> Usul SDP</span>
                                    <strong id="tmTglUsul" class="text-danger d-block fw-bold" style="font-size: 10.5px;">-</strong>
                                </div>
                                <div style="flex: 1;">
                                    <span class="text-success d-block fw-bold" style="font-size: 9.5px;">2/3</span>
                                    <strong id="tmTgl23" class="text-success d-block" style="font-size: 10px;">-</strong>
                                </div>
                                <div style="flex: 1;" class="text-end">
                                    <span class="text-danger d-block fw-bold" style="font-size: 9.5px;">Bebas</span>
                                    <strong id="tmTglEkspirasi" class="text-danger d-block" style="font-size: 10px;">-</strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Real-time Status Card Alert -->
                        <div class="p-2 px-2.5 rounded-2 mt-2 d-flex align-items-center justify-content-between flex-wrap gap-2" id="alertProgresHariIni" style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.18); font-size: 11px;">
                            <div class="d-flex align-items-center gap-1.5">
                                <i class="fas fa-info-circle text-primary" id="iconProgresStatus"></i>
                                <span id="textProgresStatus" class="telram-text-title fw-semibold">Menghitung progres masa pidana...</span>
                            </div>
                            <span class="badge bg-white text-primary border font-mono-num fw-bold shadow-xs" id="textProgresHariBerjalan" style="font-size: 10px;">- Hari Dijalani</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Hero Card Mulai Pengusulan di SDP & Tanggal 2/3 Masa Pidana -->
                <div id="boxHeroPengusulan" class="card border-0 shadow-sm mb-3 hero-usulan-pb">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-stretch g-3">
                            <div class="col-12 col-md-7 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="text-white-50 fw-bold mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-calendar-check text-warning me-1"></i> Mulai Pengusulan di SDP:
                                    </div>
                                    <h2 class="fw-black mb-1.5 text-white font-mono-num" id="resTglMulaiPengusulan" style="font-weight: 800; font-size: clamp(20px, 4.5vw, 26px); word-break: break-word;">-</h2>
                                </div>
                                <p class="mb-0 text-white-50" id="pesanPengusulan" style="font-size: 12px; line-height: 1.45;">
                                    -
                                </p>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="p-3 rounded-3 h-100 d-flex flex-column justify-content-center" style="background: rgba(0,0,0,0.22); border: 1px solid rgba(255,255,255,0.12);">
                                    <div class="text-white-50 fw-bold" style="font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        2/3 Masa Pidana:
                                    </div>
                                    <strong class="text-white d-block my-1 font-mono-num" id="resTgl23Highlight" style="font-weight: 800; font-size: clamp(16px, 3.5vw, 20px); word-break: break-word;">-</strong>
                                    <div class="text-white-50" style="font-size: 10.5px;">Metode SDP: 2/3 Pokok - Full Remisi</div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-Container 2 Opsi Pengusulan Riil SDP (Muncul jika ada penyesuaian remisi terakhir) -->
                        <div id="boxDuaOpsiPengusulan" class="mt-3 pt-3 border-top border-white border-opacity-10 d-none">
                            <div class="alert alert-warning text-dark border-0 py-2 px-3 mb-2.5 d-flex align-items-start gap-2 shadow-sm" style="font-size: 11.5px; border-radius: 8px; background: rgba(254, 240, 138, 0.95);">
                                <i class="fas fa-exclamation-triangle mt-0.5 text-warning-emphasis"></i>
                                <div>
                                    <strong>Penyesuaian Kalender SDP:</strong> Hitungan matematis murni (<span id="resTglUsulMurniText">-</span>) mendahului tanggal pemberian remisi terakhir (<span id="resTglRemisiTerakhirText">-</span>). Pada SDP, pengusulan tidak dapat diinput sebelum SK remisi terbit. Tersedia 2 opsi operasional SDP:
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <div class="p-2.5 rounded-3 h-100" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2);">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-info text-dark fw-bold" style="font-size: 10px;">OPSI 1: USUL LEBIH AWAL</span>
                                            <small class="text-white-50" style="font-size: 9.5px;">Pra-Remisi Terakhir</small>
                                        </div>
                                        <div class="text-white fw-bold mb-1 font-mono-num" style="font-size: clamp(15px, 3vw, 18px);" id="resTglUsulOpsi1">-</div>
                                        <div class="text-white-50" style="font-size: 11px; line-height: 1.35;">
                                            Dasar 2/3 Berjalan: <strong class="text-white font-mono-num" id="resTgl23Opsi1">-</strong><br>
                                            <span class="text-white-50">Pengusulan diajukan H-60 sebelum 2/3 berjalan dengan SK yang sudah definitif di tangan.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2.5 rounded-3 h-100" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2);">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-success text-white fw-bold" style="font-size: 10px;">OPSI 2: HARI H SK REMISI</span>
                                            <small class="text-white-50" style="font-size: 9.5px;">Pasca-SK Terbit</small>
                                        </div>
                                        <div class="text-white fw-bold mb-1 font-mono-num" style="font-size: clamp(15px, 3vw, 18px);" id="resTglUsulOpsi2">-</div>
                                        <div class="text-white-50" style="font-size: 11px; line-height: 1.35;">
                                            Dasar 2/3 Definitif: <strong class="text-white font-mono-num" id="resTgl23Opsi2">-</strong><br>
                                            <span class="text-white-50">Pengusulan diajukan segera setelah SK remisi terakhir resmi terbit di SDP.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Mode Sisa Result Card (Dominant Highlight) -->
                <div id="boxHasilSisa" class="card border-0 shadow-sm mb-3 d-none" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border-radius: 14px; position: relative; overflow: hidden;">
                    <div class="card-body p-4 position-relative" style="z-index: 2;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-white text-success px-2.5 py-1 mb-2 fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                                    <i class="fas fa-hourglass-half me-1"></i> SISA MASA PIDANA
                                </span>
                                <h2 class="fw-black mb-0 text-white font-mono-num" id="resSisaPidana" style="font-weight: 800; font-size: clamp(20px, 4.5vw, 26px);">-</h2>
                            </div>
                            <div class="d-none d-sm-block text-white" style="font-size: 44px; opacity: 0.25;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Mode Mundur Result Box: Lama Pidana -->
                <div id="boxHasilPidana" class="card border-0 shadow-sm mb-3 d-none" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white; border-radius: 14px;">
                    <div class="card-body p-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase fw-bold text-white-50" style="font-size: 11px; letter-spacing: 1px;">
                                Total Lama Pidana (Vonis Pokok)
                            </span>
                            <h3 class="fw-bold mb-0 text-white mt-1 font-mono-num" id="resLamaPidana" style="font-size: clamp(18px, 4vw, 24px);">-</h3>
                        </div>
                        <div style="font-size: 30px; opacity: 0.3;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>

                <!-- 5. Ekspirasi Bebas Card (Dominant Highlight) -->
                <div id="cardEkspirasi" class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white; border-radius: 14px; position: relative; overflow: hidden;">
                    <div class="card-body p-3 p-md-4 position-relative" style="z-index: 2;">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                            <div>
                                <span class="badge bg-white text-danger px-2.5 py-1 mb-1.5 fw-bold" id="badgeEkspirasiTitle" style="font-size: 11px; letter-spacing: 0.3px;">
                                    <i class="fas fa-door-open me-1"></i> EKSPIRASI BEBAS DEFINITIF
                                </span>
                                <h2 class="fw-black mb-0 text-white font-mono-num" id="resEkspirasi" style="font-weight: 800; font-size: clamp(20px, 4.5vw, 26px); word-break: break-word;">-</h2>
                                <div id="subEkspirasiMurni" class="mt-1 text-white-50" style="font-size: 12px;">
                                    Ekspirasi Pokok (Sebelum Remisi): <span class="text-white fw-bold font-mono-num" id="resEkspirasiMurniText">-</span>
                                </div>
                            </div>
                            <div class="text-start text-sm-end" id="boxTotalRemisiBadge">
                                <div class="text-white-50" style="font-size: 10.5px; text-transform: uppercase;">Total Perkiraan Remisi:</div>
                                <div class="fw-bold text-warning mt-0.5 font-mono-num" id="resTotalRemisiText" style="font-size: clamp(17px, 3.8vw, 22px);">0 Hari</div>
                            </div>
                        </div>
                        <!-- Highlight Ekspirasi Subsider Jika Ada -->
                        <div id="boxEkspirasiSubsider" class="mt-2.5 pt-2.5 border-top d-none" style="border-color: rgba(255,255,255,0.2) !important;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-0.5" style="font-size: 10.5px;">
                                        <i class="fas fa-hand-holding-usd me-1"></i> + PIDANA SUBSIDER (<span id="resSubsiderLabel">3 Bulan</span>)
                                    </span>
                                    <div class="text-white-50 mt-1" style="font-size: 11.5px;">Ekspirasi Akhir (+ Kurungan Pengganti Denda):</div>
                                </div>
                                <div class="text-start text-sm-end">
                                    <strong class="text-white d-block font-mono-num" id="resEkspirasiSubsiderVal" style="font-size: 18px; font-weight: 800;">-</strong>
                                    <small class="text-white-50" style="font-size: 11px;">Sebelum Remisi: <span id="resEkspirasiSubsiderMurniVal" class="text-white fw-semibold font-mono-num">-</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. 3 Cards Grid (1/3, 1/2 Asimilasi, 2/3 PB/CB) -->
                <div id="rowFractionCards" class="row row-cols-1 row-cols-sm-3 g-2 g-md-3 mb-3">
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm p-3 text-start fraction-card-blue">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary px-2 py-0.5 text-white fw-bold" style="font-size: 10.5px; border-radius: 6px;">
                                    1/3 Masa Pidana
                                </span>
                                <i class="fas fa-book-reader text-primary opacity-75 fs-6"></i>
                            </div>
                            <h4 class="fw-bold fraction-date-val font-mono-num mt-2 mb-0" id="res13">-</h4>
                            <small class="text-muted d-block mt-0.5" id="subInfo13" style="font-size: 10.5px;">Syarat dasar pembinaan</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm p-3 text-start fraction-card-amber">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-warning px-2 py-0.5 text-dark fw-bold" style="font-size: 10.5px; border-radius: 6px;">
                                    1/2 Asimilasi
                                </span>
                                <i class="fas fa-hands-helping text-warning opacity-75 fs-6"></i>
                            </div>
                            <h4 class="fw-bold fraction-date-val font-mono-num mt-2 mb-0" id="res12">-</h4>
                            <small class="text-muted d-block mt-0.5" id="subInfo12" style="font-size: 10.5px;">Mulai berhak Asimilasi Lapas</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm p-3 text-start fraction-card-emerald">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-success px-2 py-0.5 text-white fw-bold" id="badgeLabel23" style="font-size: 10.5px; border-radius: 6px;">
                                    2/3 PB
                                </span>
                                <i class="fas fa-user-check text-success opacity-75 fs-6"></i>
                            </div>
                            <h4 class="fw-bold fraction-date-val font-mono-num mt-2 mb-0" id="res23">-</h4>
                            <small class="text-muted d-block mt-0.5" id="subInfo23" style="font-size: 10.5px;">Syarat substantif waktu PB/CB</small>
                        </div>
                    </div>
                </div>

                <!-- 7. Info Ringkasan Ekstra -->
                <div class="p-2.5 px-3 summary-info-box mb-3">
                    <div class="row g-2 text-muted" style="font-size: 12px;">
                        <div class="col-sm-6">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> <span id="labelInfoTglAwal">Tgl. Ditahan:</span> <strong class="telram-text-title font-mono-num" id="infoTglAwal">-</strong>
                        </div>
                        <div class="col-sm-6">
                            <i class="fas fa-clock text-info me-1"></i> <span id="labelInfoTotalHari">Pidana Pokok:</span> <strong class="telram-text-title font-mono-num" id="infoTotalHari">-</strong>
                        </div>
                        <div class="col-sm-6 d-none" id="wrapInfoSubsider">
                            <i class="fas fa-hand-holding-usd text-warning me-1"></i> Pidana Subsider: <strong class="telram-text-title font-mono-num" id="infoSubsider">-</strong>
                        </div>
                        <div class="col-sm-6 d-none" id="wrapInfoPutusan">
                            <i class="fas fa-gavel text-warning me-1"></i> Tgl. Putusan: <strong class="telram-text-title font-mono-num" id="infoTglPutusan">-</strong>
                        </div>
                        <div class="col-sm-6 d-none" id="wrapInfoMasaTahanan">
                            <i class="fas fa-user-clock text-success me-1"></i> Potongan Tahanan (Potah): <strong class="telram-text-title font-mono-num" id="infoMasaTahanan">-</strong>
                        </div>
                    </div>
                </div>

                <!-- 8. Section Rincian Perkiraan Remisi Berkala (Dual-View Mobile) -->
                <div id="sectionTableLedger" class="card border-0 shadow-sm telram-card">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2.5 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold mb-0 telram-text-title d-flex align-items-center gap-1.5" style="font-size: 13.5px;">
                                    <i class="fas fa-list-ol text-warning"></i> Perkiraan Perolehan Remisi
                                </h6>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="badgeLedgerBasis" style="font-size: 10.5px;">Standar 2/3 PB/CB</span>
                            </div>

                            <!-- Dual-View Switcher (Tabel vs Kartu Mobile) -->
                            <div class="btn-group btn-group-sm rounded-pill p-0.5 border" role="group" id="viewModeLedgerGroup">
                                <button type="button" class="btn btn-sm btn-primary active rounded-pill px-2.5 py-1" id="btnViewTable" onclick="setLedgerView('table')" title="Tampilan Tabel SDP Lengkap" style="font-size: 11px;">
                                    <i class="fas fa-table me-1"></i> Tabel SDP
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" id="btnViewCards" onclick="setLedgerView('cards')" title="Tampilan Kartu Kronologis (Nyaman di Ponsel)" style="font-size: 11px;">
                                    <i class="fas fa-id-badge me-1"></i> Kartu Mobile
                                </button>
                            </div>
                        </div>

                        <!-- OPSI TAMPILAN 1: Tabel SDP Lengkap (Responsive dengan min-width agar tidak gepeng) -->
                        <div id="ledgerTableView">
                            <div class="d-block d-md-none text-muted mb-1" style="font-size: 11px;">
                                <i class="fas fa-arrows-left-right text-primary me-1"></i> Geser tabel ke samping untuk melihat kolom lengkap
                            </div>
                            <div class="table-responsive border rounded-3" style="border-color: #cbd5e1;">
                                <table class="table table-hover table-striped align-middle mb-0 table-ledger" style="min-width: 860px;">
                                    <thead class="table-light">
                                        <tr style="font-size: 11.5px;">
                                            <th style="width: 40px;" class="text-center align-middle">No</th>
                                            <th style="min-width: 170px;" class="text-center align-middle">Jenis Remisi/Tahun</th>
                                            <th style="min-width: 105px; white-space: nowrap;" class="text-center align-middle">Tgl. Pelaksanaan</th>
                                            <th style="min-width: 100px; white-space: nowrap;" class="text-center align-middle">Lama Menjalani Pidana</th>
                                            <th style="min-width: 95px; white-space: nowrap;" class="text-center align-middle">Status</th>
                                            <th style="min-width: 115px; white-space: nowrap;" class="text-center align-middle">Besaran Remisi</th>
                                            <th style="min-width: 140px; white-space: nowrap;" class="text-center align-middle">Ekspirasi setelah Remisi</th>
                                            <th style="min-width: 200px;" class="text-center align-middle">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyProyeksiRemisi">
                                        <!-- Rendered dynamically via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- OPSI TAMPILAN 2: Kartu Kronologis Mobile (Anti Horizontal Scroll) -->
                        <div id="ledgerCardsView" class="d-none">
                            <div id="cardsProyeksiRemisi" class="d-flex flex-column gap-2.5">
                                <!-- Rendered dynamically as timeline cards -->
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2.5" id="noteLedger" style="font-size: 11px; line-height: 1.45;">
                            <i class="fas fa-info-circle me-1 text-primary"></i> <b>Standar 2/3 (SDP Ditjenpas &amp; UU No. 22/2022):</b> Remisi dihitung selama narapidana menjalani pidana di Lapas s.d. 2/3 Definitif tercapai saat bebas bersyarat PB/CB. Remisi tahun-tahun setelahnya tidak dihitung di Lapas karena WBP menjalani pembinaan luar di Bapas.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hitungan Manual Lengkap & Rumus -->
<div class="modal fade" id="modalHitunganManual" tabindex="-1" aria-labelledby="modalHitunganManualLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-light py-2.5 px-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary p-2 rounded-circle">
                        <i class="fas fa-calculator text-white" style="font-size: 13px;"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-dark" id="modalHitunganManualLabel" style="font-size: 14.5px;">
                            Hitungan Manual Lengkap &amp; Rumus
                        </h6>
                        <small class="text-muted" style="font-size: 11px;">
                            Audit Trail Matematis Sesuai Kaidah Baku Pemasyarakatan &amp; Keppres No. 174/1999
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-3">
                <!-- Nav Tabs: Kartu Visual vs Format Teks -->
                <ul class="nav nav-pills nav-fill mb-3 p-1 bg-light rounded-pill border" id="pillsHitunganManual" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill py-1.5 fw-semibold" id="tabVisualManual" data-bs-toggle="pill" data-bs-target="#contentVisualManual" type="button" role="tab" style="font-size: 12px;">
                            <i class="fas fa-layer-group me-1.5"></i> Tampilan Visual (Matematis)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1.5 fw-semibold" id="tabTeksManual" data-bs-toggle="pill" data-bs-target="#contentTeksManual" type="button" role="tab" style="font-size: 12px;">
                            <i class="fas fa-file-lines me-1.5"></i> Format Salin Teks / Berkas
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="pillsHitunganManualContent">
                    <!-- Tab Visual Cards -->
                    <div class="tab-pane fade show active" id="contentVisualManual" role="tabpanel">
                        <div id="containerHitunganManualHtml">
                            <!-- Injected from data.hitungan_manual_html -->
                        </div>
                    </div>

                    <!-- Tab Preformatted Plain Text -->
                    <div class="tab-pane fade" id="contentTeksManual" role="tabpanel">
                        <div class="position-relative">
                            <pre id="preHitunganManualTeks" class="p-3 rounded-3 bg-dark text-light border font-monospace mb-0" style="font-size: 11.5px; max-height: 480px; overflow-y: auto; white-space: pre-wrap; word-break: break-word; line-height: 1.55;"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                    Tutup
                </button>
                <div class="d-flex gap-1.5">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" onclick="salinHitunganManualTeks()">
                        <i class="fas fa-copy me-1"></i> Salin Rincian Hitungan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Alert Container (Floating) -->
<div id="telramToast" class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999; display: none;">
    <div id="telramToastContent" class="toast align-items-center text-white border-0 shadow-lg show" role="alert" style="border-radius: 30px; padding: 6px 16px;">
        <div class="d-flex align-items-center">
            <span id="telramToastIcon" class="me-2"><i class="fas fa-check-circle"></i></span>
            <div class="toast-body fw-bold py-2" id="telramToastMsg" style="font-size: 13.5px;"></div>
        </div>
    </div>
</div>

<!-- Mobile Sticky Bottom Action Bar (Khusus Layar < 992px / Mobile-First) -->
<div id="mobileStickyActionBar" class="d-lg-none mobile-sticky-action-bar">
    <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2">
        <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill py-2 px-3 flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2" onclick="hitungSesuaiMode()" id="btnHitungMobile" style="font-size: 12.5px;">
            <i class="fas fa-calculator fs-6"></i>
            <span id="txtBtnHitungMobile">Hitung Sekarang</span>
        </button>
        <div class="d-flex align-items-center gap-1.5 d-none" id="mobileResultQuickGroup">
            <button type="button" class="btn btn-outline-primary btn-sm rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-xs" onclick="tampilkanModalHitunganManual()" title="Rumus &amp; Hitungan Manual" style="width: 38px; height: 38px;">
                <i class="fas fa-calculator" style="font-size: 13.5px;"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-xs" onclick="unduhPdfResmi()" title="Unduh Lembar Resmi PDF" style="width: 38px; height: 38px;">
                <i class="fas fa-file-pdf" style="font-size: 13.5px;"></i>
            </button>
            <button type="button" class="btn btn-success btn-sm rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-xs" onclick="salinRingkasanTeks()" title="Salin Ringkasan untuk WhatsApp" style="width: 38px; height: 38px;">
                <i class="fab fa-whatsapp" style="font-size: 16px;"></i>
            </button>
        </div>
    </div>
</div>

<script>
    // State Aplikasi
    let currentMode = 'simulasi_remisi';
    let currentLedgerView = window.innerWidth < 768 ? 'cards' : 'table';
    let lastResult = null;
    let deferredInstallPrompt = null;

    // Listener PWA Install Prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        const btnInstall = document.getElementById('btnInstallTelramPwa');
        if (btnInstall) btnInstall.classList.remove('d-none');
    });

    function installTelramApp() {
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            deferredInstallPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    showToast('Aplikasi Kalkulator Telram berhasil ditambahkan ke perangkat!', 'success');
                }
                deferredInstallPrompt = null;
            });
        } else {
            showToast('Buka menu browser Anda lalu pilih "Install App" atau "Tambahkan ke Layar Utama"', 'info');
        }
    }

    // Helper Touch Stepper (- / +) untuk Layar Sentuh Mobile
    function stepVal(inputId, delta) {
        const el = document.getElementById(inputId);
        if (!el) return;
        let val = parseInt(el.value, 10) || 0;
        val = Math.max(0, val + delta);
        el.value = val;
        if (inputId === 'vTahun') {
            renderChipsTahunTambahan();
        }
    }

    // Toast Notification System
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('telramToast');
        const toastContent = document.getElementById('telramToastContent');
        const toastMsg = document.getElementById('telramToastMsg');
        const toastIcon = document.getElementById('telramToastIcon');

        toastMsg.innerText = message;
        toastContent.className = 'toast align-items-center text-white border-0 shadow-lg show';

        if (type === 'error') {
            toastContent.style.background = '#dc2626';
            toastIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        } else if (type === 'warning') {
            toastContent.style.background = '#d97706';
            toastIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
        } else {
            toastContent.style.background = '#16a34a';
            toastIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
        }

        toastEl.style.display = 'block';

        setTimeout(() => {
            toastEl.style.display = 'none';
        }, 3200);
    }

    // Toggle Tampilan Tabel vs Kartu Mobile
    function setLedgerView(viewType) {
        currentLedgerView = viewType;
        const btnTable = document.getElementById('btnViewTable');
        const btnCards = document.getElementById('btnViewCards');
        const tableView = document.getElementById('ledgerTableView');
        const cardsView = document.getElementById('ledgerCardsView');

        if (viewType === 'cards') {
            if (btnCards) {
                btnCards.className = 'btn btn-sm btn-primary active rounded-pill px-2.5 py-1';
            }
            if (btnTable) {
                btnTable.className = 'btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1';
            }
            if (tableView) tableView.classList.add('d-none');
            if (cardsView) cardsView.classList.remove('d-none');
        } else {
            if (btnTable) {
                btnTable.className = 'btn btn-sm btn-primary active rounded-pill px-2.5 py-1';
            }
            if (btnCards) {
                btnCards.className = 'btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1';
            }
            if (tableView) tableView.classList.remove('d-none');
            if (cardsView) cardsView.classList.add('d-none');
        }
    }

    // Switcher Mode: Simulasi Remisi vs Maju vs Mundur vs Sisa
    function setMode(mode) {
        currentMode = mode;
        const btnRemisi = document.getElementById('btnModeRemisi');
        const btnMaju = document.getElementById('btnModeMaju');
        const btnMundur = document.getElementById('btnModeMundur');
        const btnSisa = document.getElementById('btnModeSisa');
        const sectionIdentitasWbp = document.getElementById('sectionIdentitasWbp');
        const sectionInputVonis = document.getElementById('sectionInputVonis');
        const sectionInputSubsider = document.getElementById('sectionInputSubsider');
        const groupRemisiKeagamaan = document.getElementById('groupRemisiKeagamaan');
        const groupOpsiSdplanjutan = document.getElementById('groupOpsiSdplanjutan');
        const sectionInputEkspirasi = document.getElementById('sectionInputEkspirasi');
        const btnHitung = document.getElementById('btnHitungUtama');
        const labelTglAwal = document.getElementById('labelTglAwal');
        const colTglAwal = document.getElementById('colTglAwal');
        const wrapTglPutusan = document.getElementById('wrapTglPutusan');
        const helpTglPutusanWrap = document.getElementById('helpTglPutusanWrap');
        const placeholderTitle = document.getElementById('placeholderTitle');
        const placeholderDesc = document.getElementById('placeholderDesc');
        const placeholderIcon = document.getElementById('placeholderIcon');

        [btnRemisi, btnMaju, btnMundur, btnSisa].forEach(b => { if (b) b.classList.remove('active'); });

        if (mode === 'simulasi_remisi') {
            if (btnRemisi) btnRemisi.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.remove('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.remove('d-none');
            if (sectionInputSubsider) sectionInputSubsider.classList.remove('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.remove('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.remove('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.add('d-none');
            if (wrapTglPutusan) {
                wrapTglPutusan.className = 'col-6';
                wrapTglPutusan.classList.remove('d-none');
            }
            if (helpTglPutusanWrap) helpTglPutusanWrap.classList.remove('d-none');
            if (colTglAwal) colTglAwal.className = 'col-6';
            const txtLabelTglAwal = document.getElementById('txtLabelTglAwal');
            if (txtLabelTglAwal) {
                txtLabelTglAwal.innerText = 'Tgl. Ditahan';
            }

            btnHitung.className = 'btn btn-warning text-dark w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2';
            btnHitung.innerHTML = '<i class="fas fa-calculator fs-5"></i> <span>HITUNG PERKIRAAN REMISI &amp; INTEGRASI</span>';

            placeholderTitle.innerText = 'Perkiraan Perhitungan';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan, Lama Pidana Pokok, Denda/Subsider, dan Agama WBP di sebelah kiri, kemudian klik tombol <strong>"HITUNG PERKIRAAN REMISI &amp; INTEGRASI"</strong> untuk melihat perkiraan tanggal ekspirasi baru, waktu pengusulan PB/CB (H-2 bulan), serta tabel rincian remisi berkala.';
            placeholderIcon.className = 'fas fa-calculator';
        } else if (mode === 'maju') {
            if (btnMaju) btnMaju.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.add('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.remove('d-none');
            if (sectionInputSubsider) sectionInputSubsider.classList.remove('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.add('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.add('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.add('d-none');
            if (wrapTglPutusan) wrapTglPutusan.classList.add('d-none');
            if (helpTglPutusanWrap) helpTglPutusanWrap.classList.add('d-none');
            if (colTglAwal) colTglAwal.className = 'col-12';
            const txtLabelTglAwal = document.getElementById('txtLabelTglAwal');
            if (txtLabelTglAwal) {
                txtLabelTglAwal.innerText = 'Tgl. Mulai Penahanan';
            }

            btnHitung.className = 'btn btn-primary w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2';
            btnHitung.innerHTML = '<i class="fas fa-calculator fs-5"></i> <span>HITUNG EKSPIRASI</span>';

            placeholderTitle.innerText = 'Hasil Perhitungan Ekspirasi';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan dan Lama Pidana Pokok &amp; Subsider di sebelah kiri, kemudian klik tombol <strong>"HITUNG EKSPIRASI"</strong> untuk melihat perkiraan tanggal ekspirasi dan tahapan masa pidana secara rinci.';
            placeholderIcon.className = 'fas fa-calendar-alt';
        } else if (mode === 'mundur') {
            if (btnMundur) btnMundur.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.add('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.add('d-none');
            if (sectionInputSubsider) sectionInputSubsider.classList.add('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.add('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.add('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.remove('d-none');
            if (wrapTglPutusan) wrapTglPutusan.classList.add('d-none');
            if (helpTglPutusanWrap) helpTglPutusanWrap.classList.add('d-none');
            if (colTglAwal) colTglAwal.className = 'col-12';
            const txtLabelTglAwal = document.getElementById('txtLabelTglAwal');
            if (txtLabelTglAwal) {
                txtLabelTglAwal.innerText = 'Tgl. Mulai Penahanan';
            }

            btnHitung.className = 'btn btn-secondary w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2';
            btnHitung.innerHTML = '<i class="fas fa-history fs-5"></i> <span>HITUNG LAMA PIDANA</span>';

            placeholderTitle.innerText = 'Hasil Perhitungan Lama Pidana';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan dan Tanggal Ekspirasi di sebelah kiri, kemudian klik tombol <strong>"HITUNG LAMA PIDANA"</strong> untuk mencari durasi lama vonis hukuman secara otomatis.';
            placeholderIcon.className = 'fas fa-hourglass-half';
        } else if (mode === 'sisa') {
            if (btnSisa) btnSisa.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.add('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.add('d-none');
            if (sectionInputSubsider) sectionInputSubsider.classList.add('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.add('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.add('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.remove('d-none');
            if (wrapTglPutusan) wrapTglPutusan.classList.add('d-none');
            if (helpTglPutusanWrap) helpTglPutusanWrap.classList.add('d-none');
            if (colTglAwal) colTglAwal.className = 'col-12';
            const txtLabelTglAwal = document.getElementById('txtLabelTglAwal');
            if (txtLabelTglAwal) {
                txtLabelTglAwal.innerText = 'Per Tanggal (Perhitungan)';
            }

            btnHitung.className = 'btn btn-info text-white w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2';
            btnHitung.innerHTML = '<i class="fas fa-hourglass-half fs-5"></i> <span>HITUNG SISA PIDANA</span>';

            placeholderTitle.innerText = 'Hasil Perhitungan Sisa Pidana';
            placeholderDesc.innerHTML = 'Tentukan Per Tanggal Perhitungan dan Tanggal Ekspirasi di sebelah kiri, kemudian klik tombol <strong>"HITUNG SISA PIDANA"</strong> untuk melihat sisa masa pidana yang belum dijalani.';
            placeholderIcon.className = 'fas fa-hourglass-half';

            const tglAwalInput = document.getElementById('tglAwal');
            if (tglAwalInput && !tglAwalInput.value) {
                tglAwalInput.value = new Date().toISOString().split('T')[0];
            }
        }

        if (lastResult && lastResult.mode !== mode) {
            document.getElementById('hasilSection').classList.add('d-none');
            document.getElementById('placeholderSection').classList.remove('d-none');
        }
    }

    // Set Tanggal Hari Ini
    function setTglAwalHariIni() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tglAwal').value = today;
    }

    // Update Helper Teks Standarisasi Target Basis
    function updateHelpTargetBasis() {
        const sel = document.getElementById('selTargetBasis');
        const help = document.getElementById('helpTargetBasis');
        if (!sel || !help) return;
        if (sel.value === '2_3') {
            help.innerHTML = '<i class="fas fa-info-circle text-primary me-0.5"></i> <b>Standar 2/3 (SDP):</b> Remisi dihitung hanya selama WBP menjalani pidana di Lapas sampai 2/3 tercapai (bebas PB/CB).';
        } else {
            help.innerHTML = '<i class="fas fa-door-open text-warning me-0.5"></i> <b>Ekspirasi Penuh:</b> Perhitungan remisi dilanjutkan hingga ekspirasi akhir tanpa hak integrasi PB/CB.';
        }
    }

    // State & Helper UI Remisi Tambahan Independen per Tahun RU
    let pemukaTahunSelected = [];
    let donorTahunSelected = [];

    function getMaxTahunChips() {
        const vTahun = parseInt(document.getElementById('vTahun') ? document.getElementById('vTahun').value : 0) || 0;
        return Math.max(6, Math.min(25, vTahun + 2));
    }

    function renderChipsTahunTambahan(targetType = null) {
        const types = targetType ? [targetType] : ['pemuka', 'donor'];
        const maxTahun = getMaxTahunChips();

        types.forEach(type => {
            const container = document.getElementById(type === 'pemuka' ? 'chipsTahunPemuka' : 'chipsTahunDonor');
            if (!container) return;

            const selectedArr = type === 'pemuka' ? pemukaTahunSelected : donorTahunSelected;
            const activeClass = type === 'pemuka' ? 'btn-primary text-white' : 'btn-danger text-white';

            let html = '';
            for (let th = 1; th <= maxTahun; th++) {
                const isSelected = selectedArr.includes(th);
                html += `<button type="button" class="btn btn-xs ${isSelected ? activeClass : 'btn-outline-secondary'} fw-semibold px-2 py-0.5" style="font-size: 10.5px; border-radius: 6px;" onclick="toggleTahunTambahanItem('${type}', ${th})">Th-${th}</button>`;
            }
            container.innerHTML = html;
        });
    }

    function togglePanelTambahan(type) {
        const chk = document.getElementById(type === 'pemuka' ? 'chkTambahanPemuka' : 'chkTambahanDonor');
        const wrap = document.getElementById(type === 'pemuka' ? 'wrapTahunPemuka' : 'wrapTahunDonor');
        if (!chk || !wrap) return;

        if (chk.checked) {
            wrap.classList.remove('d-none');
            const arr = type === 'pemuka' ? pemukaTahunSelected : donorTahunSelected;
            if (arr.length === 0) {
                setPresetTahunTambahan(type, 'mulai2');
            } else {
                renderChipsTahunTambahan(type);
            }
        } else {
            wrap.classList.add('d-none');
            if (type === 'pemuka') {
                pemukaTahunSelected = [];
            } else {
                donorTahunSelected = [];
            }
            renderChipsTahunTambahan(type);
        }
    }

    function toggleTahunTambahanItem(type, tahun) {
        const arr = type === 'pemuka' ? pemukaTahunSelected : donorTahunSelected;
        const idx = arr.indexOf(tahun);
        if (idx > -1) {
            arr.splice(idx, 1);
        } else {
            arr.push(tahun);
            arr.sort((a, b) => a - b);
        }
        renderChipsTahunTambahan(type);
        updateBadgeOpsiLanjutan();
    }

    function setPresetTahunTambahan(type, preset) {
        const maxTahun = getMaxTahunChips();
        let nextArr = [];
        if (preset === 'mulai2') {
            for (let th = 2; th <= maxTahun; th++) nextArr.push(th);
        } else if (preset === 'all') {
            for (let th = 1; th <= maxTahun; th++) nextArr.push(th);
        }

        if (type === 'pemuka') {
            pemukaTahunSelected = nextArr;
        } else {
            donorTahunSelected = nextArr;
        }
        renderChipsTahunTambahan(type);
        updateBadgeOpsiLanjutan();
    }

    function getRemisiTambahanPayload() {
        const chkPemuka = document.getElementById('chkTambahanPemuka');
        const chkDonor = document.getElementById('chkTambahanDonor');
        const pemukaList = chkPemuka && chkPemuka.checked ? [...pemukaTahunSelected].sort((a, b) => a - b) : [];
        const donorList = chkDonor && chkDonor.checked ? [...donorTahunSelected].sort((a, b) => a - b) : [];

        let kategori = 'none';
        if (pemukaList.length > 0 && donorList.length > 0) {
            kategori = 'kombinasi';
        } else if (pemukaList.length > 0) {
            kategori = 'pemuka';
        } else if (donorList.length > 0) {
            kategori = 'jasa_negara';
        }

        const allYears = [...pemukaList, ...donorList];
        const tahunMulai = allYears.length > 0 ? Math.min(...allYears) : 1;

        return {
            kategori_tambahan: kategori,
            tahun_mulai_tambahan: tahunMulai,
            pemuka_tahun_list: pemukaList,
            donor_tahun_list: donorList,
        };
    }

    // Format input angka ribuan Rupiah
    function formatRupiahInput(el) {
        let val = el.value.replace(/[^0-9]/g, '');
        if (!val) {
            el.value = '';
            return;
        }
        el.value = new Intl.NumberFormat('id-ID').format(val);
    }

    // Toggle Helper Status Denda
    function toggleStatusDenda(val) {
        const helper = document.getElementById('statusDendaHelper');
        if (!helper) return;
        if (val === 'sb') {
            helper.className = 'text-success mt-0.5 text-center';
            helper.innerText = 'Sudah Bayar / Lunas';
        } else {
            helper.className = 'text-danger mt-0.5 text-center';
            helper.innerText = 'Belum Bayar';
        }
    }

    // Update Badge Ringkasan Pengaturan Lanjutan SDP
    function updateBadgeOpsiLanjutan() {
        const badge = document.getElementById('badgeOpsiLanjutanSummary');
        if (!badge) return;

        const chkDasawarsa = document.getElementById('chkDasawarsa');
        const tambahanPayload = getRemisiTambahanPayload();
        const selBasis = document.getElementById('selTargetBasis');
        const remisiAwal = parseInt(document.getElementById('vRemisiAwal') ? document.getElementById('vRemisiAwal').value : 0) || 0;

        const dasawarsaOff = chkDasawarsa && !chkDasawarsa.checked;
        const adaTambahan = tambahanPayload.kategori_tambahan !== 'none';
        const basisEkspirasi = selBasis && selBasis.value === 'ekspirasi';
        const adaRemisiAwal = remisiAwal > 0;

        if (dasawarsaOff || adaTambahan || basisEkspirasi || adaRemisiAwal) {
            badge.className = 'badge bg-warning text-dark border border-warning';
            let summary = [];
            if (tambahanPayload.kategori_tambahan === 'kombinasi') summary.push('Pemuka & Donor');
            else if (tambahanPayload.kategori_tambahan === 'pemuka') summary.push('Pemuka');
            else if (tambahanPayload.kategori_tambahan === 'jasa_negara') summary.push('Donor/Jasa');
            if (basisEkspirasi) summary.push('Ekspirasi Akhir');
            if (dasawarsaOff) summary.push('Tanpa Dasawarsa');
            if (adaRemisiAwal) summary.push(remisiAwal + ' Hri');
            badge.innerText = summary.join(' | ') || 'Kustom';
        } else {
            badge.className = 'badge bg-secondary-subtle text-secondary border';
            badge.innerText = 'Standar';
        }
    }

    // ==============================================================
    // MANAJEMEN RIWAYAT PENAHANAN & PEMBANTARAN (MULTI-PERIODE)
    // ==============================================================
    let riwayatIndexCounter = 0;

    function toggleRiwayatPenahanan() {
        const sw = document.getElementById('switchRiwayatPenahanan');
        const container = document.getElementById('containerRiwayatPenahanan');
        if (sw && sw.checked) {
            container.style.display = 'block';
            if (document.querySelectorAll('.item-riwayat-row').length === 0) {
                tambahBarisRiwayat(
                    document.getElementById('tglAwal') ? document.getElementById('tglAwal').value : '',
                    document.getElementById('tglPutusan') ? document.getElementById('tglPutusan').value : '',
                    'rutan',
                    'Tahanan Lapas/Rutan'
                );
            }
        } else {
            container.style.display = 'none';
        }
        hitungUlangRiwayat();
    }

    function tambahBarisRiwayat(tglMulai = '', tglSelesai = '', jenis = 'rutan', ket = '') {
        const list = document.getElementById('listRiwayatPenahanan');
        if (!list) return;

        riwayatIndexCounter++;
        const rowId = `riwayat_row_${riwayatIndexCounter}`;

        const rowHtml = `
        <div class="item-riwayat-row p-2.5 bg-white rounded-3 border shadow-xs mb-2.5 position-relative" id="${rowId}" style="font-size: 11px;">
            <!-- Baris 1: Header Segmen, Jenis Status & Tombol Hapus -->
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                <div class="d-flex align-items-center gap-1.5 flex-grow-1">
                    <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 10.5px; flex-shrink: 0;">${riwayatIndexCounter}</span>
                    <select class="form-select form-select-sm riwayat-jenis fw-bold text-primary" onchange="hitungUlangRiwayat()" style="font-size: 12px; height: 36px; border-radius: 8px;">
                        <option value="rutan" ${jenis === 'rutan' ? 'selected' : ''}>Tahanan Lapas/Rutan (1:1 / 100%)</option>
                        <option value="rumah" ${jenis === 'rumah' ? 'selected' : ''}>Tahanan Rumah (1/3)</option>
                        <option value="kota" ${jenis === 'kota' ? 'selected' : ''}>Tahanan Kota (1/5)</option>
                        <option value="tangguh" ${jenis === 'tangguh' ? 'selected' : ''}>Penangguhan Penahanan (0% - T2-T1)</option>
                        <option value="bantar" ${jenis === 'bantar' ? 'selected' : ''}>Pembantaran / Dirawat RS (0% - T2-T1)</option>
                        <option value="luar_upt" ${jenis === 'luar_upt' ? 'selected' : ''}>Hari di Luar UPT Lainnya (0% - T2-T1)</option>
                        <option value="masuk_kembali" ${jenis === 'masuk_kembali' ? 'selected' : ''}>Masuk Kembali (1:1 / 100%)</option>
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger px-2.5 py-1 d-inline-flex align-items-center justify-content-center" onclick="hapusBarisRiwayat('${rowId}')" title="Hapus segmen ini" style="height: 36px; width: 36px; border-radius: 8px; flex-shrink: 0;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>

            <!-- Baris 2: Tanggal Mulai & Tanggal Selesai (50% - 50% Lebar Penuh, Sangat Jelas) -->
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label telram-text-title fw-bold mb-1 text-muted d-block" style="font-size: 11px;">
                        <i class="fas fa-calendar-alt me-1 text-primary"></i> Tgl. Mulai <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control riwayat-tgl-mulai fw-semibold" value="${tglMulai}" onchange="onRiwayatDateChange()" style="height: 38px; border-radius: 8px; font-size: 12.5px;">
                </div>
                <div class="col-6">
                    <label class="form-label telram-text-title fw-bold mb-1 text-muted d-block" style="font-size: 11px;">
                        <i class="fas fa-calendar-check me-1 text-primary"></i> Tgl. Selesai <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control riwayat-tgl-selesai fw-semibold" value="${tglSelesai}" onchange="onRiwayatDateChange()" style="height: 38px; border-radius: 8px; font-size: 12.5px;">
                </div>
            </div>

            <!-- Baris 3: Keterangan & Badge Hasil Perhitungan -->
            <div class="row g-2 align-items-center">
                <div class="col-7">
                    <input type="text" class="form-control riwayat-keterangan" value="${ket}" placeholder="Keterangan / No. Penetapan (opsional)" style="height: 34px; border-radius: 6px; font-size: 11.5px;">
                </div>
                <div class="col-5 text-end">
                    <span class="badge bg-light text-dark border riwayat-badge-info py-1.5 px-2 w-100 text-truncate d-block" style="font-size: 10.5px;">0 Hari</span>
                </div>
            </div>
        </div>`;

        list.insertAdjacentHTML('beforeend', rowHtml);
        hitungUlangRiwayat();
    }

    function hapusBarisRiwayat(rowId) {
        const el = document.getElementById(rowId);
        if (el) {
            el.remove();
            hitungUlangRiwayat();
        }
    }

    function onRiwayatDateChange() {
        hitungUlangRiwayat();
    }

    function hitungUlangRiwayat() {
        const sw = document.getElementById('switchRiwayatPenahanan');
        if (!sw || !sw.checked) {
            return;
        }

        const rows = document.querySelectorAll('.item-riwayat-row');
        const tglPutusanEl = document.getElementById('tglPutusan');
        const tglPutusanStr = tglPutusanEl ? tglPutusanEl.value : '';
        const dPutusan = tglPutusanStr ? new Date(tglPutusanStr + 'T00:00:00') : null;

        let totalPenahananInklusif = 0;
        let totalPotah = 0;
        let totalLuarUpt = 0;
        let totalLuarUptPra = 0;
        let totalLuarUptPasca = 0;
        let earliestStart = null;
        let latestPenahananEnd = null;
        let latestEnd = null;

        rows.forEach(row => {
            const jenis = row.querySelector('.riwayat-jenis').value;
            const tglMulaiStr = row.querySelector('.riwayat-tgl-mulai').value;
            const tglSelesaiStr = row.querySelector('.riwayat-tgl-selesai').value;
            const badge = row.querySelector('.riwayat-badge-info');

            if (tglMulaiStr && tglSelesaiStr) {
                const d1 = new Date(tglMulaiStr + 'T00:00:00');
                const d2 = new Date(tglSelesaiStr + 'T00:00:00');

                if (d2 >= d1) {
                    const diffDays = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
                    const isLuarUpt = (jenis === 'tangguh' || jenis === 'bantar' || jenis === 'luar_upt');
                    // Penahanan inklusif (T2 - T1 + 1), Hari di Luar UPT eksklusif Opsi A (T2 - T1)
                    const days = isLuarUpt ? diffDays : (diffDays + 1);

                    let potah = 0;
                    if (jenis === 'rutan' || jenis === 'masuk_kembali') {
                        potah = days;
                    } else if (jenis === 'rumah') {
                        potah = Math.ceil(days / 3); // favor reo
                    } else if (jenis === 'kota') {
                        potah = Math.ceil(days / 5); // favor reo
                    }

                    if (isLuarUpt) {
                        totalLuarUpt += days;
                        let praPart = days;
                        let pascaPart = 0;
                        if (dPutusan) {
                            if (d2 <= dPutusan) {
                                praPart = days;
                                pascaPart = 0;
                            } else if (d1 > dPutusan) {
                                praPart = 0;
                                pascaPart = days;
                            } else {
                                praPart = Math.max(0, Math.round((dPutusan - d1) / (1000 * 60 * 60 * 24)));
                                pascaPart = Math.max(0, days - praPart);
                            }
                        }
                        totalLuarUptPra += praPart;
                        totalLuarUptPasca += pascaPart;

                        if (badge) {
                            badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2';
                            if (pascaPart > 0) {
                                badge.innerHTML = `<i class="fas fa-pause me-1"></i> ${days} H Luar UPT (${pascaPart} H Pasca-Putusan)`;
                            } else {
                                badge.innerHTML = `<i class="fas fa-pause me-1"></i> ${days} H Luar UPT (T2-T1 / 0 Potah)`;
                            }
                        }
                    } else {
                        totalPenahananInklusif += days;
                        totalPotah += potah;
                        if (!earliestStart || d1 < earliestStart) earliestStart = d1;
                        if (!latestPenahananEnd || d2 > latestPenahananEnd) latestPenahananEnd = d2;

                        if (badge) {
                            badge.className = 'badge bg-success-subtle text-success border border-success-subtle py-1 px-2';
                            badge.innerHTML = `<i class="fas fa-check me-1"></i> ${days} Hari &rarr; <b>${potah} Hari Potah</b>`;
                        }
                    }

                    if (!latestEnd || d2 > latestEnd) latestEnd = d2;
                } else {
                    if (badge) {
                        badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle py-1 px-2';
                        badge.innerHTML = 'Tgl. Selesai &lt; Tgl. Mulai';
                    }
                }
            } else {
                if (badge) {
                    badge.className = 'badge bg-light text-muted border py-1 px-2';
                    badge.innerHTML = '0 Hari';
                }
            }
        });

        // Update summary text
        const txtTotalKal = document.getElementById('txtTotalHariKalender');
        if (txtTotalKal) txtTotalKal.innerText = `${totalPenahananInklusif} Hari`;

        const txtTotalPot = document.getElementById('txtTotalHariPotah');
        if (txtTotalPot) txtTotalPot.innerText = `${totalPotah} Hari`;

        const rowJeda = document.getElementById('rowJedaKalender');
        if (rowJeda) {
            if (totalLuarUpt > 0) {
                rowJeda.style.display = 'flex';
                document.getElementById('txtTotalHariJeda').innerText = `${totalLuarUpt} Hari (0 Potah)`;
            } else {
                rowJeda.style.display = 'none';
            }
        }

        const rowLuarUptBreakdown = document.getElementById('rowLuarUptBreakdown');
        if (rowLuarUptBreakdown) {
            if (totalLuarUpt > 0 && dPutusan) {
                rowLuarUptBreakdown.style.display = 'flex';
                document.getElementById('txtLuarUptBreakdown').innerText = `Pra: ${totalLuarUptPra} H | Pasca: ${totalLuarUptPasca} H`;
            } else {
                rowLuarUptBreakdown.style.display = 'none';
            }
        }

        const rowEfektif = document.getElementById('rowTglAwalEfektif');
        const refEndForEfektif = latestPenahananEnd || latestEnd;
        if (rowEfektif && refEndForEfektif && totalPotah >= 0) {
            const dEfektif = new Date(refEndForEfektif);
            dEfektif.setDate(dEfektif.getDate() - totalPotah);
            const yyyy = dEfektif.getFullYear();
            const mm = String(dEfektif.getMonth() + 1).padStart(2, '0');
            const dd = String(dEfektif.getDate()).padStart(2, '0');
            rowEfektif.style.display = 'flex';
            document.getElementById('txtTglAwalEfektif').innerText = `${dd}/${mm}/${yyyy}`;
        } else if (rowEfektif) {
            rowEfektif.style.display = 'none';
        }

        // Auto-sync tglAwal dari segmen penahanan pertama, dan tglPutusan hanya jika masih kosong
        if (earliestStart) {
            const y1 = earliestStart.getFullYear();
            const m1 = String(earliestStart.getMonth() + 1).padStart(2, '0');
            const d1 = String(earliestStart.getDate()).padStart(2, '0');
            const inputAwal = document.getElementById('tglAwal');
            if (inputAwal) inputAwal.value = `${y1}-${m1}-${d1}`;
        }
        if (latestPenahananEnd) {
            const y2 = latestPenahananEnd.getFullYear();
            const m2 = String(latestPenahananEnd.getMonth() + 1).padStart(2, '0');
            const d2 = String(latestPenahananEnd.getDate()).padStart(2, '0');
            if (tglPutusanEl && !tglPutusanEl.value) {
                tglPutusanEl.value = `${y2}-${m2}-${d2}`;
            }
        }
    }

    function getRiwayatPenahananArray() {
        const sw = document.getElementById('switchRiwayatPenahanan');
        if (!sw || !sw.checked) return [];

        const rows = document.querySelectorAll('.item-riwayat-row');
        const result = [];
        rows.forEach(row => {
            const jenis = row.querySelector('.riwayat-jenis').value;
            const tgl_mulai = row.querySelector('.riwayat-tgl-mulai').value;
            const tgl_selesai = row.querySelector('.riwayat-tgl-selesai').value;
            const keterangan = row.querySelector('.riwayat-keterangan').value;

            if (tgl_mulai && tgl_selesai) {
                result.push({
                    jenis: jenis,
                    tgl_mulai: tgl_mulai,
                    tgl_selesai: tgl_selesai,
                    keterangan: keterangan || ''
                });
            }
        });
        return result;
    }

    // Reset Form
    function resetForm() {
        const swRiwayat = document.getElementById('switchRiwayatPenahanan');
        if (swRiwayat) swRiwayat.checked = false;
        const contRiwayat = document.getElementById('containerRiwayatPenahanan');
        if (contRiwayat) contRiwayat.style.display = 'none';
        const listRiwayat = document.getElementById('listRiwayatPenahanan');
        if (listRiwayat) listRiwayat.innerHTML = '';
        hitungUlangRiwayat();
        if (document.getElementById('txtNamaWbp')) document.getElementById('txtNamaWbp').value = '';
        if (document.getElementById('txtNoRegister')) document.getElementById('txtNoRegister').value = '';
        if (document.getElementById('txtPerkaraPasal')) document.getElementById('txtPerkaraPasal').value = '';
        document.getElementById('tglAwal').value = '';
        if (document.getElementById('tglPutusan')) document.getElementById('tglPutusan').value = '';
        document.getElementById('vTahun').value = '0';
        document.getElementById('vBulan').value = '0';
        document.getElementById('vHari').value = '0';
        if (document.getElementById('vSubTahun')) document.getElementById('vSubTahun').value = '0';
        if (document.getElementById('vSubBulan')) document.getElementById('vSubBulan').value = '0';
        if (document.getElementById('vSubHari')) document.getElementById('vSubHari').value = '0';
        document.getElementById('selAgama').value = 'islam';
        if (document.getElementById('vDendaNominal')) document.getElementById('vDendaNominal').value = '';
        if (document.getElementById('statusDendaBB')) {
            document.getElementById('statusDendaBB').checked = true;
            toggleStatusDenda('bb');
        }
        if (document.getElementById('chkDasawarsa')) document.getElementById('chkDasawarsa').checked = true;
        pemukaTahunSelected = [];
        donorTahunSelected = [];
        if (document.getElementById('chkTambahanPemuka')) document.getElementById('chkTambahanPemuka').checked = false;
        if (document.getElementById('chkTambahanDonor')) document.getElementById('chkTambahanDonor').checked = false;
        togglePanelTambahan('pemuka');
        togglePanelTambahan('donor');
        if (document.getElementById('selTargetBasis')) document.getElementById('selTargetBasis').value = '2_3';
        updateHelpTargetBasis();
        document.getElementById('vRemisiAwal').value = '0';
        document.getElementById('tglEkspirasiInput').value = '';

        if (document.getElementById('boxEkspirasiSubsider')) document.getElementById('boxEkspirasiSubsider').classList.add('d-none');
        if (document.getElementById('wrapInfoSubsider')) document.getElementById('wrapInfoSubsider').classList.add('d-none');

        updateBadgeOpsiLanjutan();

        const collapseEl = document.getElementById('collapseOpsiLanjutan');
        if (collapseEl && collapseEl.classList.contains('show') && typeof bootstrap !== 'undefined') {
            const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
            if (bsCollapse) {
                bsCollapse.hide();
            }
        }

        document.getElementById('hasilSection').classList.add('d-none');
        document.getElementById('placeholderSection').classList.remove('d-none');
        const boxTimeline = document.getElementById('boxMilestoneTimeline');
        if (boxTimeline) boxTimeline.classList.add('d-none');
        const txtBtnHitungMobile = document.getElementById('txtBtnHitungMobile');
        if (txtBtnHitungMobile) txtBtnHitungMobile.innerText = 'Hitung Sekarang';
        const mobileResultGroup = document.getElementById('mobileResultQuickGroup');
        if (mobileResultGroup) mobileResultGroup.classList.add('d-none');
        lastResult = null;
        showToast('Form berhasil di-reset', 'info');
    }

    // Demo Data Contoh
    function demoSampleData() {
        const now = new Date();
        const yyyy = now.getFullYear();
        document.getElementById('tglAwal').value = `${yyyy}-01-01`;

        if (currentMode === 'simulasi_remisi' || currentMode === 'maju') {
            if (document.getElementById('txtNamaWbp')) document.getElementById('txtNamaWbp').value = 'Budi Santoso bin Ahmad';
            if (document.getElementById('txtNoRegister')) document.getElementById('txtNoRegister').value = 'BI.042/2024';
            if (document.getElementById('txtPerkaraPasal')) document.getElementById('txtPerkaraPasal').value = 'Pasal 363 KUHP';
            if (document.getElementById('tglPutusan')) {
                const samplePutusan = new Date(yyyy, 2, 15);
                const pY = samplePutusan.getFullYear();
                const pM = String(samplePutusan.getMonth() + 1).padStart(2, '0');
                const pD = String(samplePutusan.getDate()).padStart(2, '0');
                document.getElementById('tglPutusan').value = `${pY}-${pM}-${pD}`;
            }
            document.getElementById('vTahun').value = '3';
            document.getElementById('vBulan').value = '0';
            document.getElementById('vHari').value = '0';
            if (document.getElementById('vDendaNominal')) document.getElementById('vDendaNominal').value = '100.000.000';
            if (document.getElementById('statusDendaBB')) {
                document.getElementById('statusDendaBB').checked = true;
                toggleStatusDenda('bb');
            }
            if (document.getElementById('vSubTahun')) document.getElementById('vSubTahun').value = '0';
            if (document.getElementById('vSubBulan')) document.getElementById('vSubBulan').value = '3';
            if (document.getElementById('vSubHari')) document.getElementById('vSubHari').value = '0';
            document.getElementById('selAgama').value = 'islam';
            if (document.getElementById('chkDasawarsa')) document.getElementById('chkDasawarsa').checked = true;
            pemukaTahunSelected = [];
            donorTahunSelected = [];
            if (document.getElementById('chkTambahanPemuka')) document.getElementById('chkTambahanPemuka').checked = false;
            if (document.getElementById('chkTambahanDonor')) document.getElementById('chkTambahanDonor').checked = false;
            togglePanelTambahan('pemuka');
            togglePanelTambahan('donor');
            renderChipsTahunTambahan();
            if (document.getElementById('selTargetBasis')) document.getElementById('selTargetBasis').value = '2_3';
            updateHelpTargetBasis();
            document.getElementById('vRemisiAwal').value = '0';
        } else {
            if (document.getElementById('tglPutusan')) document.getElementById('tglPutusan').value = '';
            if (document.getElementById('vDendaNominal')) document.getElementById('vDendaNominal').value = '';
            if (document.getElementById('vSubTahun')) document.getElementById('vSubTahun').value = '0';
            if (document.getElementById('vSubBulan')) document.getElementById('vSubBulan').value = '0';
            if (document.getElementById('vSubHari')) document.getElementById('vSubHari').value = '0';
            const futureDate = new Date(now);
            futureDate.setFullYear(futureDate.getFullYear() + 2);
            const fY = futureDate.getFullYear();
            const fM = String(futureDate.getMonth() + 1).padStart(2, '0');
            const fD = String(futureDate.getDate()).padStart(2, '0');
            document.getElementById('tglEkspirasiInput').value = `${fY}-${fM}-${fD}`;
        }

        updateBadgeOpsiLanjutan();
        hitungSesuaiMode();
    }

    // Controller Utama Eksekusi Perhitungan
    async function hitungSesuaiMode() {
        const inputTgl = document.getElementById('tglAwal').value;
        if (!inputTgl) {
            showToast('Harap tentukan Tanggal Mulai Penahanan / Per Tanggal!', 'error');
            document.getElementById('tglAwal').focus();
            return;
        }

        const btnHitung = document.getElementById('btnHitungUtama');
        const originalBtnHtml = btnHitung.innerHTML;
        btnHitung.disabled = true;
        btnHitung.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menghitung...';

        try {
            let payload = { mode: currentMode, tgl_awal: inputTgl };

            if (currentMode === 'simulasi_remisi') {
                const vThn = parseInt(document.getElementById('vTahun').value) || 0;
                const vBln = parseInt(document.getElementById('vBulan').value) || 0;
                const vHri = parseInt(document.getElementById('vHari').value) || 0;
                const vSubThn = parseInt(document.getElementById('vSubTahun') ? document.getElementById('vSubTahun').value : 0) || 0;
                const vSubBln = parseInt(document.getElementById('vSubBulan') ? document.getElementById('vSubBulan').value : 0) || 0;
                const vSubHri = parseInt(document.getElementById('vSubHari') ? document.getElementById('vSubHari').value : 0) || 0;
                const agama = document.getElementById('selAgama').value;
                const remisiAwal = parseInt(document.getElementById('vRemisiAwal').value) || 0;

                if (vThn === 0 && vBln === 0 && vHri === 0) {
                    showToast('Harap tentukan Lama Pidana Pokok!', 'error');
                    btnHitung.disabled = false;
                    btnHitung.innerHTML = originalBtnHtml;
                    return;
                }

                payload.tahun = vThn;
                payload.bulan = vBln;
                payload.hari = vHri;
                payload.sub_tahun = vSubThn;
                payload.sub_bulan = vSubBln;
                payload.sub_hari = vSubHri;
                payload.denda_nominal = document.getElementById('vDendaNominal') ? document.getElementById('vDendaNominal').value : '';
                payload.subsider_status = document.querySelector('input[name="statusDendaRadio"]:checked') ? document.querySelector('input[name="statusDendaRadio"]:checked').value : 'bb';
                payload.agama = agama;
                payload.remisi_awal_hari = remisiAwal;
                payload.tgl_putusan = document.getElementById('tglPutusan') ? document.getElementById('tglPutusan').value : '';
                payload.nama_wbp = document.getElementById('txtNamaWbp') ? document.getElementById('txtNamaWbp').value : '';
                payload.no_register = document.getElementById('txtNoRegister') ? document.getElementById('txtNoRegister').value : '';
                payload.perkara_pasal = document.getElementById('txtPerkaraPasal') ? document.getElementById('txtPerkaraPasal').value : '';
                payload.include_dasawarsa = document.getElementById('chkDasawarsa') ? (document.getElementById('chkDasawarsa').checked ? 1 : 0) : 1;
                const tambahanPayload = getRemisiTambahanPayload();
                payload.kategori_tambahan = tambahanPayload.kategori_tambahan;
                payload.tahun_mulai_tambahan = tambahanPayload.tahun_mulai_tambahan;
                payload.pemuka_tahun_list = tambahanPayload.pemuka_tahun_list;
                payload.donor_tahun_list = tambahanPayload.donor_tahun_list;
                payload.target_basis = document.getElementById('selTargetBasis') ? document.getElementById('selTargetBasis').value : '2_3';

                const riwayatData = getRiwayatPenahananArray();
                if (riwayatData.length > 0) {
                    payload.riwayat_penahanan = riwayatData;
                }
            } else if (currentMode === 'maju') {
                payload.tahun = parseInt(document.getElementById('vTahun').value) || 0;
                payload.bulan = parseInt(document.getElementById('vBulan').value) || 0;
                payload.hari = parseInt(document.getElementById('vHari').value) || 0;
                payload.sub_tahun = parseInt(document.getElementById('vSubTahun') ? document.getElementById('vSubTahun').value : 0) || 0;
                payload.sub_bulan = parseInt(document.getElementById('vSubBulan') ? document.getElementById('vSubBulan').value : 0) || 0;
                payload.sub_hari = parseInt(document.getElementById('vSubHari') ? document.getElementById('vSubHari').value : 0) || 0;
                payload.denda_nominal = document.getElementById('vDendaNominal') ? document.getElementById('vDendaNominal').value : '';
                payload.subsider_status = document.querySelector('input[name="statusDendaRadio"]:checked') ? document.querySelector('input[name="statusDendaRadio"]:checked').value : 'bb';

                if (payload.tahun === 0 && payload.bulan === 0 && payload.hari === 0) {
                    showToast('Harap tentukan Lama Pidana Pokok!', 'error');
                    btnHitung.disabled = false;
                    btnHitung.innerHTML = originalBtnHtml;
                    return;
                }
            } else if (currentMode === 'mundur') {
                payload.tgl_ekspirasi = document.getElementById('tglEkspirasiInput').value;
                if (!payload.tgl_ekspirasi) {
                    showToast('Harap tentukan Tanggal Ekspirasi!', 'error');
                    btnHitung.disabled = false;
                    btnHitung.innerHTML = originalBtnHtml;
                    return;
                }
            } else if (currentMode === 'sisa') {
                payload.tgl_perhitungan = inputTgl;
                payload.tgl_ekspirasi = document.getElementById('tglEkspirasiInput').value;
                if (!payload.tgl_ekspirasi) {
                    showToast('Harap tentukan Tanggal Ekspirasi!', 'error');
                    btnHitung.disabled = false;
                    btnHitung.innerHTML = originalBtnHtml;
                    return;
                }
            }

            const response = await fetch('{{ route("telram.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const resJson = await response.json();

            if (!resJson.success) {
                showToast(resJson.message || 'Gagal menghitung kalkulasi Telram', 'error');
                return;
            }

            renderHasilApi(resJson.data);
        } catch (err) {
            console.error('Error saat kalkulasi:', err);
            showToast('Terjadi kesalahan koneksi saat menghitung data', 'error');
        } finally {
            btnHitung.disabled = false;
            btnHitung.innerHTML = originalBtnHtml;
        }
    }

    // Render Data Hasil dari Response API Backend
    function renderHasilApi(data) {
        lastResult = data;

        const boxHeroPengusulan = document.getElementById('boxHeroPengusulan');
        const boxHasilSisa = document.getElementById('boxHasilSisa');
        const boxHasilPidana = document.getElementById('boxHasilPidana');
        const cardEkspirasi = document.getElementById('cardEkspirasi');
        const rowFractionCards = document.getElementById('rowFractionCards');
        const sectionTableLedger = document.getElementById('sectionTableLedger');
        const badgeProgramStatus = document.getElementById('badgeProgramStatus');
        const hasilHeaderTitle = document.getElementById('hasilHeaderTitle');
        const btnGroupAksiHasil = document.getElementById('btnGroupAksiHasil');

        if (data.mode === 'simulasi_remisi') {
            boxHeroPengusulan.classList.remove('d-none');
            boxHasilSisa.classList.add('d-none');
            boxHasilPidana.classList.add('d-none');
            cardEkspirasi.classList.remove('d-none');
            rowFractionCards.classList.remove('d-none');
            sectionTableLedger.classList.remove('d-none');
            btnGroupAksiHasil.classList.remove('d-none');

            // Badge Program
            badgeProgramStatus.innerText = data.jenis_program;
            badgeProgramStatus.className = data.jenis_program === 'CB' ? 'badge bg-info text-white' : 'badge bg-success text-white';
            const wbpLabel = (data.nama_wbp && data.nama_wbp !== 'Narapidana') ? ` (${data.nama_wbp})` : '';
            hasilHeaderTitle.innerText = `Perkiraan Perhitungan${wbpLabel}`;

            // Hero Pengusulan
            document.getElementById('resTglMulaiPengusulan').innerText = data.formatted.tgl_mulai_pengusulan;
            document.getElementById('resTgl23Highlight').innerText = data.formatted.tgl_2_3_definitif;
            document.getElementById('pesanPengusulan').innerText = data.status_pengusulan.pesan;

            const boxDuaOpsi = document.getElementById('boxDuaOpsiPengusulan');
            if (boxDuaOpsi) {
                if (data.opsi_pengusulan && data.opsi_pengusulan.has_benturan) {
                    boxDuaOpsi.classList.remove('d-none');
                    const remisiTerk = data.opsi_pengusulan.remisi_terakhir || {};
                    const o1 = data.opsi_pengusulan.opsi_1_pra_remisi || {};
                    const o2 = data.opsi_pengusulan.opsi_2_pasca_remisi || {};
                    document.getElementById('resTglUsulMurniText').innerText = data.opsi_pengusulan.tgl_usul_murni_indo;
                    document.getElementById('resTglRemisiTerakhirText').innerText = (remisiTerk.tanggal_indo || '-') + ' (' + (remisiTerk.nama_event || '-') + ')';
                    document.getElementById('resTglUsulOpsi1').innerText = o1.tgl_usul_indo || '-';
                    document.getElementById('resTgl23Opsi1').innerText = o1.tgl_2_3_indo || '-';
                    document.getElementById('resTglUsulOpsi2').innerText = o2.tgl_usul_indo || '-';
                    document.getElementById('resTgl23Opsi2').innerText = o2.tgl_2_3_indo || '-';
                } else {
                    boxDuaOpsi.classList.add('d-none');
                }
            }

            const badgeStatusPengusulan = document.getElementById('badgeStatusPengusulan');
            if (badgeStatusPengusulan) {
                badgeStatusPengusulan.innerText = data.status_pengusulan.label;
                badgeStatusPengusulan.className = `badge ${data.status_pengusulan.badge_class}`;
            }

            // Ekspirasi Definitif
            document.getElementById('badgeEkspirasiTitle').innerHTML = '<i class="fas fa-door-open me-1"></i> EKSPIRASI BEBAS SETELAH REMISI';
            document.getElementById('resEkspirasi').innerText = data.formatted.ekspirasi_definitif;
            document.getElementById('subEkspirasiMurni').classList.remove('d-none');
            document.getElementById('resEkspirasiMurniText').innerText = data.formatted.ekspirasi_murni;
            document.getElementById('boxTotalRemisiBadge').classList.remove('d-none');
            document.getElementById('resTotalRemisiText').innerText = `${data.total_proyeksi_remisi_hari} Hari (${data.total_proyeksi_remisi_teks})`;

            // 3 Fraction Cards
            document.getElementById('res13').innerText = data.formatted.tgl_1_3_definitif || data.formatted.tgl_1_3_murni;
            const subInfo13 = document.getElementById('subInfo13');
            if (subInfo13) subInfo13.innerText = `1/3 Definitif (Murni: ${data.formatted.tgl_1_3_murni})`;

            document.getElementById('res12').innerText = data.formatted.tgl_1_2_definitif;
            document.getElementById('subInfo12').innerText = `Asimilasi (Murni: ${data.formatted.tgl_1_2_murni})`;

            document.getElementById('badgeLabel23').innerText = `2/3 ${data.jenis_program}`;
            document.getElementById('res23').innerText = data.formatted.tgl_2_3_definitif;
            if (data.opsi_pengusulan && data.opsi_pengusulan.has_benturan) {
                const o1Tgl23 = data.opsi_pengusulan.opsi_1_pra_remisi?.tgl_2_3_indo || '-';
                document.getElementById('subInfo23').innerHTML = `<span class="badge bg-warning text-dark me-1" style="font-size: 9.5px;">2 Opsi</span> Definitif: ${data.formatted.tgl_2_3_definitif}<br>Opsi 1 (Berjalan): <strong>${o1Tgl23}</strong>`;
            } else {
                document.getElementById('subInfo23').innerText = `(Murni: ${data.formatted.tgl_2_3_murni})`;
            }

            // Render Ledger Tabel & Cards
            renderTableLedger(data.proyeksi_ledger);

            // Update badge basis proyeksi dan note penjelasan
            const badgeLedgerBasis = document.getElementById('badgeLedgerBasis');
            if (badgeLedgerBasis) {
                badgeLedgerBasis.innerText = data.target_basis_label || (data.target_basis === '2_3' ? 'Standar 2/3 PB/CB' : 'Ekspirasi Penuh');
                badgeLedgerBasis.className = data.target_basis === '2_3'
                    ? 'badge bg-primary-subtle text-primary border border-primary-subtle'
                    : 'badge bg-warning-subtle text-warning border border-warning-subtle';
            }

            const noteLedger = document.getElementById('noteLedger');
            if (noteLedger) {
                if (data.target_basis === '2_3') {
                    noteLedger.innerHTML = `<i class="fas fa-info-circle me-1 text-primary"></i> <b>Standar 2/3 (SDP Ditjenpas &amp; UU No. 22/2022):</b> Remisi dihitung selama narapidana menjalani pidana di Lapas s.d. 2/3 Definitif tercapai saat bebas bersyarat PB/CB (<strong>${data.formatted.tgl_2_3_definitif}</strong>). Remisi tahun-tahun setelahnya tidak dihitung di Lapas karena WBP telah bebas menjalani pembinaan luar di Bapas.`;
                } else {
                    noteLedger.innerHTML = `<i class="fas fa-info-circle me-1 text-warning"></i> <b>Basis Ekspirasi Penuh:</b> Perkiraan remisi dihitung secara berkala hingga bebas murni tanpa hak integrasi PB/CB.`;
                }
            }

            // Info ringkasan
            document.getElementById('labelInfoTglAwal').innerText = data.has_riwayat_penahanan ? 'Tgl. Ditahan (Fisik):' : 'Tgl. Ditahan:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_awal;
            document.getElementById('labelInfoTotalHari').innerText = 'Pidana Pokok:';
            document.getElementById('infoTotalHari').innerText = `${data.total_hari_vonis.toLocaleString('id-ID')} (${data.lama_pidana_teks})`;

            const wrapInfoPutusan = document.getElementById('wrapInfoPutusan');
            const wrapInfoMasaTahanan = document.getElementById('wrapInfoMasaTahanan');
            if (data.formatted.tgl_putusan) {
                if (wrapInfoPutusan) {
                    wrapInfoPutusan.classList.remove('d-none');
                    document.getElementById('infoTglPutusan').innerText = data.formatted.tgl_putusan;
                }
                if (wrapInfoMasaTahanan) {
                    wrapInfoMasaTahanan.classList.remove('d-none');
                    if (data.has_riwayat_penahanan) {
                        document.getElementById('infoMasaTahanan').innerHTML = `<b class="text-success">${data.masa_tahanan_teks}</b> (${data.masa_tahanan_hari} hr potah)`;
                    } else {
                        document.getElementById('infoMasaTahanan').innerText = data.masa_tahanan_teks;
                    }
                }
            } else {
                if (wrapInfoPutusan) wrapInfoPutusan.classList.add('d-none');
                if (wrapInfoMasaTahanan) wrapInfoMasaTahanan.classList.add('d-none');
            }

            // Subsider & Denda Check
            const boxEkspirasiSubsider = document.getElementById('boxEkspirasiSubsider');
            const wrapInfoSubsider = document.getElementById('wrapInfoSubsider');
            const isSubsiderBb = data.has_subsider && ((data.subsider_status || 'bb') === 'bb');
            if (isSubsiderBb) {
                if (boxEkspirasiSubsider) {
                    boxEkspirasiSubsider.classList.remove('d-none');
                    document.getElementById('resSubsiderLabel').innerText = (data.subsider_teks || '') + ' (BB)';
                    document.getElementById('resEkspirasiSubsiderVal').innerText = data.formatted.ekspirasi_subsider_definitif || '-';
                    document.getElementById('resEkspirasiSubsiderMurniVal').innerText = data.formatted.ekspirasi_subsider_sebelum_remisi || data.formatted.ekspirasi_subsider_murni || '-';
                }
            } else {
                if (boxEkspirasiSubsider) boxEkspirasiSubsider.classList.add('d-none');
            }

            if (data.has_subsider || data.has_denda) {
                if (wrapInfoSubsider) {
                    wrapInfoSubsider.classList.remove('d-none');
                    document.getElementById('infoSubsider').innerText = data.formatted.subsider_identitas_teks || data.subsider_identitas_teks || `${data.subsider_teks}`;
                }
            } else {
                if (wrapInfoSubsider) wrapInfoSubsider.classList.add('d-none');
            }

            showToast(`Perkiraan Remisi & Waktu Pengusulan ${data.jenis_program} Berhasil Dihitung!`, 'success');
        } else if (data.mode === 'sisa') {
            boxHeroPengusulan.classList.add('d-none');
            boxHasilSisa.classList.remove('d-none');
            boxHasilPidana.classList.add('d-none');
            cardEkspirasi.classList.add('d-none');
            rowFractionCards.classList.add('d-none');
            sectionTableLedger.classList.add('d-none');
            btnGroupAksiHasil.classList.add('d-none');

            if (document.getElementById('wrapInfoPutusan')) document.getElementById('wrapInfoPutusan').classList.add('d-none');
            if (document.getElementById('wrapInfoMasaTahanan')) document.getElementById('wrapInfoMasaTahanan').classList.add('d-none');
            if (document.getElementById('boxEkspirasiSubsider')) document.getElementById('boxEkspirasiSubsider').classList.add('d-none');
            if (document.getElementById('wrapInfoSubsider')) document.getElementById('wrapInfoSubsider').classList.add('d-none');

            document.getElementById('resSisaPidana').innerText = data.sisa_pidana_teks;
            document.getElementById('labelInfoTglAwal').innerText = 'Per Tanggal:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_perhitungan;
            document.getElementById('labelInfoTotalHari').innerText = 'Total Sisa Hari:';
            document.getElementById('infoTotalHari').innerText = data.total_hari_sisa.toLocaleString('id-ID');
            hasilHeaderTitle.innerText = 'Hasil Perhitungan Sisa Pidana';

            showToast('Sisa Pidana Berhasil Dihitung!', 'success');
        } else if (data.mode === 'mundur') {
            boxHeroPengusulan.classList.add('d-none');
            boxHasilSisa.classList.add('d-none');
            boxHasilPidana.classList.remove('d-none');
            cardEkspirasi.classList.remove('d-none');
            rowFractionCards.classList.remove('d-none');
            sectionTableLedger.classList.add('d-none');
            btnGroupAksiHasil.classList.add('d-none');

            if (document.getElementById('wrapInfoPutusan')) document.getElementById('wrapInfoPutusan').classList.add('d-none');
            if (document.getElementById('wrapInfoMasaTahanan')) document.getElementById('wrapInfoMasaTahanan').classList.add('d-none');
            if (document.getElementById('boxEkspirasiSubsider')) document.getElementById('boxEkspirasiSubsider').classList.add('d-none');
            if (document.getElementById('wrapInfoSubsider')) document.getElementById('wrapInfoSubsider').classList.add('d-none');

            document.getElementById('resLamaPidana').innerText = data.lama_pidana_teks;
            document.getElementById('badgeEkspirasiTitle').innerHTML = '<i class="fas fa-door-open me-1"></i> TANGGAL EKSPIRASI';
            document.getElementById('resEkspirasi').innerText = data.formatted.tgl_ekspirasi;
            document.getElementById('subEkspirasiMurni').classList.add('d-none');
            document.getElementById('boxTotalRemisiBadge').classList.add('d-none');

            document.getElementById('res13').innerText = data.formatted.tgl_1_3;
            document.getElementById('res12').innerText = data.formatted.tgl_1_2;
            document.getElementById('subInfo12').innerText = '1/2 Masa Pidana';
            document.getElementById('badgeLabel23').innerText = '2/3 Masa Pidana';
            document.getElementById('res23').innerText = data.formatted.tgl_2_3;
            document.getElementById('subInfo23').innerText = '2/3 Masa Pidana';

            document.getElementById('labelInfoTglAwal').innerText = 'Tgl. Ditahan:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_awal;
            document.getElementById('labelInfoTotalHari').innerText = 'Total Hari Vonis:';
            document.getElementById('infoTotalHari').innerText = `${data.total_hari_vonis.toLocaleString('id-ID')} (${data.lama_pidana_teks})`;
            hasilHeaderTitle.innerText = 'Hasil Perhitungan Lama Pidana';

            showToast('Lama Pidana Berhasil Dihitung!', 'success');
        } else {
            // Mode Maju Standar
            boxHeroPengusulan.classList.add('d-none');
            boxHasilSisa.classList.add('d-none');
            boxHasilPidana.classList.add('d-none');
            cardEkspirasi.classList.remove('d-none');
            rowFractionCards.classList.remove('d-none');
            sectionTableLedger.classList.add('d-none');
            btnGroupAksiHasil.classList.add('d-none');

            if (document.getElementById('wrapInfoPutusan')) document.getElementById('wrapInfoPutusan').classList.add('d-none');
            if (document.getElementById('wrapInfoMasaTahanan')) document.getElementById('wrapInfoMasaTahanan').classList.add('d-none');

            document.getElementById('badgeEkspirasiTitle').innerHTML = '<i class="fas fa-door-open me-1"></i> TANGGAL EKSPIRASI';
            document.getElementById('resEkspirasi').innerText = data.formatted.tgl_ekspirasi;
            document.getElementById('subEkspirasiMurni').classList.add('d-none');
            document.getElementById('boxTotalRemisiBadge').classList.add('d-none');

            document.getElementById('res13').innerText = data.formatted.tgl_1_3;
            document.getElementById('res12').innerText = data.formatted.tgl_1_2;
            document.getElementById('subInfo12').innerText = '1/2 Masa Pidana';
            document.getElementById('badgeLabel23').innerText = '2/3 Masa Pidana';
            document.getElementById('res23').innerText = data.formatted.tgl_2_3;
            document.getElementById('subInfo23').innerText = '2/3 Masa Pidana';

            // Subsider Check
            const boxEkspirasiSubsider = document.getElementById('boxEkspirasiSubsider');
            const wrapInfoSubsider = document.getElementById('wrapInfoSubsider');
            const isSubsiderBbMaju = data.has_subsider && ((data.subsider_status || 'bb') === 'bb');
            if (isSubsiderBbMaju) {
                if (boxEkspirasiSubsider) {
                    boxEkspirasiSubsider.classList.remove('d-none');
                    document.getElementById('resSubsiderLabel').innerText = data.subsider_teks;
                    document.getElementById('resEkspirasiSubsiderVal').innerText = data.formatted.tgl_ekspirasi_subsider;
                    document.getElementById('resEkspirasiSubsiderMurniVal').innerText = data.formatted.tgl_ekspirasi_subsider;
                }
            } else {
                if (boxEkspirasiSubsider) boxEkspirasiSubsider.classList.add('d-none');
            }
            if (data.has_subsider || data.has_denda) {
                if (wrapInfoSubsider) {
                    wrapInfoSubsider.classList.remove('d-none');
                    document.getElementById('infoSubsider').innerText = data.formatted.subsider_identitas_teks || data.subsider_identitas_teks || `${data.subsider_teks}`;
                }
            } else {
                if (wrapInfoSubsider) wrapInfoSubsider.classList.add('d-none');
            }

            document.getElementById('labelInfoTglAwal').innerText = 'Tgl. Ditahan:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_awal;
            document.getElementById('labelInfoTotalHari').innerText = 'Total Hari Vonis:';
            document.getElementById('infoTotalHari').innerText = `${data.total_hari_vonis.toLocaleString('id-ID')} (${data.lama_pidana_teks})`;
            hasilHeaderTitle.innerText = 'Hasil Perhitungan Ekspirasi';

            showToast('Kalkulasi Ekspirasi Selesai!', 'success');
        }

        document.getElementById('placeholderSection').classList.add('d-none');
        document.getElementById('hasilSection').classList.remove('d-none');

        // Update Milestone Timeline & Mobile Sticky Bar
        updateMilestoneTimeline(data);
        const txtBtnHitungMobile = document.getElementById('txtBtnHitungMobile');
        if (txtBtnHitungMobile) txtBtnHitungMobile.innerText = 'Hitung Ulang';
        const mobileResultGroup = document.getElementById('mobileResultQuickGroup');
        if (mobileResultGroup) mobileResultGroup.classList.remove('d-none');

        // Mobile UX: Auto smooth scroll ke hasil kalkulasi agar user langsung melihat angka
        if (window.innerWidth < 992) {
            setTimeout(() => {
                const el = document.getElementById('hasilSection');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }

    // Update Visual Milestone Timeline Masa Pidana (Progress Hari Ini)
    function updateMilestoneTimeline(data) {
        const box = document.getElementById('boxMilestoneTimeline');
        if (!box) return;

        if (data.mode !== 'simulasi_remisi' && data.mode !== 'maju') {
            box.classList.add('d-none');
            return;
        }
        box.classList.remove('d-none');

        const tglAwalStr = data.raw ? data.raw.tgl_awal : null;
        const tgl23Str = data.raw ? (data.raw.tgl_2_3_definitif || data.raw.tgl_2_3) : null;
        const tglEkspirasiStr = data.raw ? (data.raw.ekspirasi_definitif || data.raw.tgl_ekspirasi) : null;
        const tglUsulStr = (data.opsi_pengusulan && data.opsi_pengusulan.has_benturan && data.opsi_pengusulan.opsi_2_pasca_remisi)
            ? data.opsi_pengusulan.opsi_2_pasca_remisi.tgl_usul
            : (data.raw ? data.raw.tgl_mulai_pengusulan : null);

        document.getElementById('tmTglAwal').innerText = data.formatted.tgl_awal || '-';
        document.getElementById('tmTgl13').innerText = data.formatted.tgl_1_3_definitif || data.formatted.tgl_1_3 || data.formatted.tgl_1_3_murni || '-';
        document.getElementById('tmTgl12').innerText = data.formatted.tgl_1_2_definitif || data.formatted.tgl_1_2 || '-';
        document.getElementById('tmTglUsul').innerText = data.formatted.tgl_mulai_pengusulan || '-';
        document.getElementById('tmTgl23').innerText = data.formatted.tgl_2_3_definitif || data.formatted.tgl_2_3 || '-';
        document.getElementById('tmTglEkspirasi').innerText = data.formatted.ekspirasi_definitif || data.formatted.tgl_ekspirasi || '-';

        if (!tglAwalStr || !tglEkspirasiStr) return;

        const dStart = new Date(tglAwalStr);
        const dEnd = new Date(tglEkspirasiStr);
        const dToday = new Date();
        const dUsul = tglUsulStr ? new Date(tglUsulStr) : null;
        const d23 = tgl23Str ? new Date(tgl23Str) : null;

        const totalMs = dEnd - dStart;
        const elapsedMs = Math.max(0, dToday - dStart);
        let pct = totalMs > 0 ? Math.min(100, Math.max(0, (elapsedMs / totalMs) * 100)) : 0;
        pct = Math.round(pct * 10) / 10;

        const progressBar = document.getElementById('progressBarHariIni');
        const badgeProgress = document.getElementById('badgeProgressHariIni');
        const textHari = document.getElementById('textProgresHariBerjalan');
        const textStatus = document.getElementById('textProgresStatus');
        const iconStatus = document.getElementById('iconProgresStatus');

        if (progressBar) progressBar.style.width = pct + '%';
        if (badgeProgress) badgeProgress.innerText = `Hari Ini: ${pct}%`;

        const elapsedDays = Math.floor(elapsedMs / (1000 * 60 * 60 * 24));
        if (textHari) textHari.innerText = `${elapsedDays.toLocaleString('id-ID')} Hari Dijalani`;

        if (dToday < dStart) {
            if (textStatus) textStatus.innerHTML = '<span class="text-secondary">Masa penahanan belum dimulai atau tanggal awal berada di masa depan.</span>';
            if (iconStatus) iconStatus.className = 'fas fa-clock text-secondary';
        } else if (dUsul && dToday < dUsul) {
            const daysToUsul = Math.ceil((dUsul - dToday) / (1000 * 60 * 60 * 24));
            if (textStatus) textStatus.innerHTML = `Tahap pembinaan awal. Kurang lebih <b>${daysToUsul} hari lagi</b> menuju batas mulai pengusulan di SDP.`;
            if (iconStatus) iconStatus.className = 'fas fa-hourglass-start text-primary';
        } else if (d23 && dToday <= d23) {
            if (textStatus) textStatus.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Masa aktif pengusulan integrasi PB/CB di SDP (H-60 s.d. 2/3). Segera proses berkas!</span>';
            if (iconStatus) iconStatus.className = 'fas fa-bullhorn text-warning';
        } else if (dToday <= dEnd) {
            if (textStatus) textStatus.innerHTML = 'Telah melewati 2/3 masa pidana. Sedang menjalani tahap akhir pidana / integrasi Bapas.';
            if (iconStatus) iconStatus.className = 'fas fa-walking text-info';
        } else {
            if (textStatus) textStatus.innerHTML = '<span class="text-danger fw-bold">Narapidana telah melewati tanggal ekspirasi bebas!</span>';
            if (iconStatus) iconStatus.className = 'fas fa-flag-checkered text-danger';
        }
    }

    // Render Tabel Proyeksi Remisi (Tabel SDP & Mobile Timeline Cards)
    function renderTableLedger(ledger) {
        const tbody = document.getElementById('tbodyProyeksiRemisi');
        const cardsContainer = document.getElementById('cardsProyeksiRemisi');
        tbody.innerHTML = '';
        cardsContainer.innerHTML = '';

        if (!ledger || ledger.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada jadwal event remisi sebelum tanggal ekspirasi.</td></tr>';
            cardsContainer.innerHTML = '<div class="text-center py-3 text-muted border rounded-3 p-3">Tidak ada jadwal event remisi sebelum tanggal ekspirasi.</div>';
            return;
        }

        ledger.forEach((row, index) => {
            // 1. Status Badge
            const statusBadge = row.eligible
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="white-space: nowrap;"><i class="fas fa-check-circle me-1"></i>${row.tahap || 'Memenuhi Syarat'}</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="white-space: nowrap;"><i class="fas fa-clock me-1"></i>Belum 6 Bln</span>`;

            // Badge Tahap Warna
            let badgeBg = '';
            if (!row.eligible) {
                badgeBg = 'background-color: #fff1f2;';
            } else if (row.jenis === 'RD') {
                badgeBg = 'background-color: #fffbeb;';
            } else if (row.jenis === 'RT') {
                badgeBg = 'background-color: #f0fdf4;';
            }

            // A. Row Tabel SDP Lengkap
            const tr = document.createElement('tr');
            if (badgeBg) tr.setAttribute('style', badgeBg);

            tr.innerHTML = `
                <td class="text-center fw-bold text-muted font-mono-num">${index + 1}</td>
                <td>
                    <strong class="text-dark">${row.nama_event}</strong>
                    ${row.tahap && row.tahap !== '-' ? `<br><small class="text-primary fw-semibold" style="font-size: 11px;">[Tahap: ${row.tahap}]</small>` : ''}
                </td>
                <td style="white-space: nowrap;" class="font-mono-num">${row.tanggal_indo}</td>
                <td style="white-space: nowrap;" class="font-mono-num"><small class="text-muted fw-semibold">${row.hari_dijalani} hr<br><span style="font-size: 10.5px;">(${row.masa_dijalani_teks})</span></small></td>
                <td style="white-space: nowrap;">${statusBadge}</td>
                <td style="white-space: nowrap;" class="fw-bold font-mono-num ${row.besaran_hari > 0 ? 'text-primary' : 'text-danger'}">${row.besaran_teks}</td>
                <td style="white-space: nowrap;">
                    <div style="font-size: 12px; line-height: 1.45; font-weight: 700;" class="font-mono-num">
                        <div class="text-danger">Eks: ${row.perkiraan_ekspirasi_baru_indo}</div>
                        <div class="text-primary">1/3: ${row.tgl_1_3_baru_indo || '-'}</div>
                        <div style="color: #b45309;">1/2: ${row.tgl_1_2_baru_indo || '-'}</div>
                        <div class="text-success">2/3: ${row.tgl_2_3_baru_indo || '-'}</div>
                    </div>
                </td>
                <td style="max-width: 280px;"><small class="text-secondary" style="font-size: 11px; line-height: 1.35; display: block;">${row.keterangan_alasan || '-'}</small></td>
            `;
            tbody.appendChild(tr);

            // B. Kartu Kronologis Mobile
            const card = document.createElement('div');
            card.className = 'timeline-remisi-card';
            if (badgeBg) card.setAttribute('style', badgeBg);

            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary text-white rounded-circle font-mono-num" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 10.5px;">${index + 1}</span>
                        <div>
                            <strong class="text-dark" style="font-size: 13px;">${row.nama_event}</strong>
                            ${row.tahap && row.tahap !== '-' ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1.5" style="font-size: 10px;">${row.tahap}</span>` : ''}
                        </div>
                    </div>
                    ${statusBadge}
                </div>
                <div class="row g-2 mb-2 pt-1 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                    <div class="col-6">
                        <small class="text-muted d-block" style="font-size: 10px; text-transform: uppercase;">TANGGAL EVENT</small>
                        <span class="fw-bold text-dark font-mono-num" style="font-size: 12.5px;">${row.tanggal_indo}</span>
                        <small class="text-muted d-block font-mono-num" style="font-size: 10.5px;">Dijalani: ${row.hari_dijalani} hr (${row.masa_dijalani_teks})</small>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted d-block" style="font-size: 10px; text-transform: uppercase;">BESARAN REMISI</small>
                        <span class="fw-bold font-mono-num ${row.besaran_hari > 0 ? 'text-primary' : 'text-danger'}" style="font-size: 13.5px;">${row.besaran_teks}</span>
                        <small class="text-success fw-bold d-block font-mono-num" style="font-size: 10.5px;">Akumulasi: ${row.total_akumulasi_hari} Hari</small>
                    </div>
                </div>
                <div class="p-2 rounded-2 border mb-1.5" style="background: rgba(254, 242, 242, 0.6); border-color: #fecaca !important;">
                    <div class="row g-1 font-mono-num" style="font-size: 12px; font-weight: 700; line-height: 1.4;">
                        <div class="col-6 text-danger">Eks: ${row.perkiraan_ekspirasi_baru_indo}</div>
                        <div class="col-6 text-primary">1/3: ${row.tgl_1_3_baru_indo || '-'}</div>
                        <div class="col-6" style="color: #b45309;">1/2: ${row.tgl_1_2_baru_indo || '-'}</div>
                        <div class="col-6 text-success">2/3: ${row.tgl_2_3_baru_indo || '-'}</div>
                    </div>
                </div>
                <small class="text-muted d-block" style="font-size: 10.5px; line-height: 1.35;">
                    <i class="fas fa-balance-scale text-secondary me-1"></i> ${row.keterangan_alasan || '-'}
                </small>
            `;
            cardsContainer.appendChild(card);
        });
    }

    // Unduh Dokumen Resmi PDF Perkiraan Remisi & Telram
    function unduhPdfResmi() {
        if (!lastResult || lastResult.mode !== 'simulasi_remisi') {
            showToast('Lakukan simulasi perkiraan remisi terlebih dahulu!', 'warning');
            return;
        }

        const inputTgl = document.getElementById('tglAwal').value;
        const tglPutusan = document.getElementById('tglPutusan') ? document.getElementById('tglPutusan').value : '';
        const vThn = parseInt(document.getElementById('vTahun').value) || 0;
        const vBln = parseInt(document.getElementById('vBulan').value) || 0;
        const vHri = parseInt(document.getElementById('vHari').value) || 0;
        const vSubThn = parseInt(document.getElementById('vSubTahun') ? document.getElementById('vSubTahun').value : 0) || 0;
        const vSubBln = parseInt(document.getElementById('vSubBulan') ? document.getElementById('vSubBulan').value : 0) || 0;
        const vSubHri = parseInt(document.getElementById('vSubHari') ? document.getElementById('vSubHari').value : 0) || 0;
        const agama = document.getElementById('selAgama').value;
        const remisiAwal = parseInt(document.getElementById('vRemisiAwal').value) || 0;
        const namaWbp = document.getElementById('txtNamaWbp') ? document.getElementById('txtNamaWbp').value : '';
        const noRegister = document.getElementById('txtNoRegister') ? document.getElementById('txtNoRegister').value : '';
        const perkaraPasal = document.getElementById('txtPerkaraPasal') ? document.getElementById('txtPerkaraPasal').value : '';
        const includeDasawarsa = document.getElementById('chkDasawarsa') ? (document.getElementById('chkDasawarsa').checked ? '1' : '0') : '1';
        const tambahanPayload = getRemisiTambahanPayload();
        const targetBasis = document.getElementById('selTargetBasis') ? document.getElementById('selTargetBasis').value : '2_3';

        const dendaNominal = document.getElementById('vDendaNominal') ? document.getElementById('vDendaNominal').value : '';
        const subsiderStatus = document.querySelector('input[name="statusDendaRadio"]:checked') ? document.querySelector('input[name="statusDendaRadio"]:checked').value : 'bb';

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("telram.export-pdf") }}';
        form.target = '_blank';

        const fields = {
            _token: '{{ csrf_token() }}',
            tgl_awal: inputTgl,
            tgl_putusan: tglPutusan,
            tahun: vThn,
            bulan: vBln,
            hari: vHri,
            sub_tahun: vSubThn,
            sub_bulan: vSubBln,
            sub_hari: vSubHri,
            denda_nominal: dendaNominal,
            subsider_status: subsiderStatus,
            agama: agama,
            remisi_awal_hari: remisiAwal,
            nama_wbp: namaWbp,
            no_register: noRegister,
            perkara_pasal: perkaraPasal,
            include_dasawarsa: includeDasawarsa,
            kategori_tambahan: tambahanPayload.kategori_tambahan,
            tahun_mulai_tambahan: tambahanPayload.tahun_mulai_tambahan,
            pemuka_tahun_list: JSON.stringify(tambahanPayload.pemuka_tahun_list),
            donor_tahun_list: JSON.stringify(tambahanPayload.donor_tahun_list),
            target_basis: targetBasis
        };

        const riwayatDataPdf = getRiwayatPenahananArray();
        if (riwayatDataPdf.length > 0) {
            fields.riwayat_penahanan = JSON.stringify(riwayatDataPdf);
        }

        for (const [k, v] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = k;
            input.value = v;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        showToast('Dokumen PDF resmi dibuka di tab baru!', 'success');
    }

    // Cetak Lembar Perhitungan Telram & Remisi
    function cetakLembarPerhitungan() {
        window.print();
    }

    // Buka Modal Hitungan Manual & Rumus
    function tampilkanModalHitunganManual() {
        if (!lastResult || lastResult.mode !== 'simulasi_remisi') {
            showToast('Lakukan simulasi remisi terlebih dahulu!', 'warning');
            return;
        }

        const labelEl = document.getElementById('modalHitunganManualLabel');
        if (labelEl && lastResult.judul_program) {
            labelEl.innerText = lastResult.judul_program;
        }

        const containerHtml = document.getElementById('containerHitunganManualHtml');
        const preTeks = document.getElementById('preHitunganManualTeks');

        if (containerHtml && lastResult.hitungan_manual_html) {
            containerHtml.innerHTML = lastResult.hitungan_manual_html;
        }

        if (preTeks && lastResult.hitungan_manual_teks) {
            preTeks.textContent = lastResult.hitungan_manual_teks;
        }

        const modalEl = document.getElementById('modalHitunganManual');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    // Salin Teks Hitungan Manual ke Clipboard
    function salinHitunganManualTeks() {
        if (!lastResult || !lastResult.hitungan_manual_teks) {
            showToast('Rincian hitungan manual belum tersedia.', 'warning');
            return;
        }

        navigator.clipboard.writeText(lastResult.hitungan_manual_teks).then(() => {
            showToast('Rincian hitungan manual berhasil disalin!', 'success');
        }).catch(() => {
            showToast('Gagal menyalin teks ke clipboard.', 'error');
        });
    }

    // Salin Ringkasan Teks untuk WhatsApp
    function salinRingkasanTeks() {
        if (!lastResult || lastResult.mode !== 'simulasi_remisi') {
            showToast('Lakukan simulasi remisi terlebih dahulu!', 'warning');
            return;
        }

        const d = lastResult;
        const putusanLine = d.formatted && d.formatted.tgl_putusan ? `\n*Tgl. Putusan:* ${d.formatted.tgl_putusan}\n*Potongan Tahanan (Potah):* ${d.masa_tahanan_teks}` : '';
        const subsiderLine = d.formatted && d.formatted.subsider_identitas_teks && d.formatted.subsider_identitas_teks !== '-' ? `\n*Pidana Subsider:* ${d.formatted.subsider_identitas_teks}` : '';
        let usulanSection = '';
        let baris23Wa = '';
        if (d.opsi_pengusulan && d.opsi_pengusulan.has_benturan) {
            const remisiNama = d.opsi_pengusulan.remisi_terakhir?.nama_event || 'Remisi Terakhir';
            const remisiJenis = d.opsi_pengusulan.remisi_terakhir?.jenis || 'RK';
            baris23Wa = `• *Tersedia 2 Opsi Perhitungan 2/3 Masa Pidana:*
  - *Opsi 1 = 2/3 Sebelum Remisi ${remisiNama} (${remisiJenis}):* ${d.opsi_pengusulan.opsi_1_pra_remisi.tgl_2_3_indo}
    _(Prediksi Usul SDP: ${d.opsi_pengusulan.opsi_1_pra_remisi.tgl_usul_indo})_
  - *Opsi 2 = 2/3 Setelah Remisi ${remisiNama} (${remisiJenis}):* ${d.formatted.tgl_2_3_definitif}
    _(Prediksi Usul SDP: ${d.opsi_pengusulan.opsi_2_pasca_remisi.tgl_usul_indo} [Hari H SK Remisi])_`;

            usulanSection = `⚠️ *MULAI PENGUSULAN DI SDP (2 OPSI OPERASIONAL SDP):*
• *Opsi 1 = Usul Sebelum Remisi (${remisiJenis}):* ${d.opsi_pengusulan.opsi_1_pra_remisi.tgl_usul_indo}
  _(Dasar 2/3: ${d.opsi_pengusulan.opsi_1_pra_remisi.tgl_2_3_indo} - Usul lebih awal dengan SK di tangan)_
• *Opsi 2 = Usul Setelah Remisi (${remisiJenis}):* ${d.opsi_pengusulan.opsi_2_pasca_remisi.tgl_usul_indo}
  _(Hari H SK ${remisiNama} - Dasar 2/3: ${d.opsi_pengusulan.opsi_2_pasca_remisi.tgl_2_3_indo})_
*Catatan:* Hitungan murni (${d.opsi_pengusulan.tgl_usul_murni_indo}) mendahului tanggal remisi. Di SDP, pengusulan tidak kurang dari tanggal remisi, sehingga disesuaikan tepat pada Hari H SK.`;
        } else {
            baris23Wa = `• *2/3 Masa Pidana:* ${d.formatted.tgl_2_3_definitif} (Sebelum Remisi: ${d.formatted.tgl_2_3_sebelum_remisi || d.formatted.tgl_2_3_murni})`;
            usulanSection = `⚠️ *MULAI PENGUSULAN DI SDP (H-2 BULAN):*
*Tanggal:* ${d.formatted.tgl_mulai_pengusulan}
*Status:* ${d.status_pengusulan.label}
*Catatan:* ${d.status_pengusulan.pesan}`;
        }

        const text = `*${d.judul_program || 'PERKIRAAN PEROLEHAN REMISI'}*
*Sultan Banten - Kanwil Ditjenpas Banten*
---------------------------------------
*Nama:* ${d.nama_wbp || 'Narapidana'}
*No. Register:* ${d.no_register || '-'}
*Pidana / Pasal:* ${d.perkara_pasal || '-'}
*Tanggal Ditahan:* ${d.formatted.tgl_awal}${putusanLine}
*Pidana Pokok:* ${d.lama_pidana_teks}${subsiderLine}
*Agama WBP:* ${d.agama}
*Status Pembinaan:* ${d.label_kategori_tambahan || d.kategori_tambahan_label || 'Standar (Reguler)'}
*Remisi Dasawarsa:* ${d.include_dasawarsa ? 'Disertakan (1/12 vonis)' : 'Tidak Disertakan'}
*Basis Simulasi:* ${d.target_basis_label || 'Standar 2/3 Masa Pidana'}
---------------------------------------
*Pentahapan Masa Pidana:*
• *1/3 Masa Pidana:* ${d.formatted.tgl_1_3_definitif || d.formatted.tgl_1_3_sebelum_remisi || d.formatted.tgl_1_3_murni}
• *1/2 Asimilasi:* ${d.formatted.tgl_1_2_definitif} (Sebelum Remisi: ${d.formatted.tgl_1_2_sebelum_remisi || d.formatted.tgl_1_2_murni})
${baris23Wa}
• *Ekspirasi Definitif:* ${d.formatted.ekspirasi_definitif} (Sebelum Remisi: ${d.formatted.ekspirasi_sebelum_remisi || d.formatted.ekspirasi_murni})
• *Total Perkiraan Remisi:* ${d.total_proyeksi_remisi_hari} Hari (${d.total_proyeksi_remisi_teks})
---------------------------------------
${usulanSection}
---------------------------------------
_Catatan: Penghitungan ini merupakan simulasi reguler berdasarkan UU No. 22/2022, Keppres No. 174/1999 dan standar rumus SDP Ditjenpas._`;

        navigator.clipboard.writeText(text).then(() => {
            showToast('Ringkasan berhasil disalin ke clipboard!', 'success');
        }).catch(() => {
            showToast('Gagal menyalin teks ke clipboard.', 'error');
        });
    }

    // Inisialisasi saat halaman dimuat
    document.addEventListener('DOMContentLoaded', () => {
        setMode('simulasi_remisi');
        renderChipsTahunTambahan();
        updateBadgeOpsiLanjutan();

        const vTahunEl = document.getElementById('vTahun');
        if (vTahunEl) {
            vTahunEl.addEventListener('input', () => renderChipsTahunTambahan());
            vTahunEl.addEventListener('change', () => renderChipsTahunTambahan());
        }

        const tglPutusanEl = document.getElementById('tglPutusan');
        if (tglPutusanEl) {
            tglPutusanEl.addEventListener('change', () => hitungUlangRiwayat());
        }

        // Deteksi layar ponsel: Jika mobile (< 768px), otomatis pilih Kartu Mobile
        if (window.innerWidth < 768) {
            setLedgerView('cards');
        } else {
            setLedgerView('table');
        }
    });
</script>
@endsection
