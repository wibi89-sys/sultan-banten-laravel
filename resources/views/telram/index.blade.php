@extends('layouts.app')

@section('title', 'Kalkulator Telram & Integrasi - Sultan Banten')
@section('page_title', 'Kalkulator Telram & Integrasi')

@push('styles')
<style>
    /* Card Styles for Telram */
    .telram-card {
        background: var(--card-bg, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        border-radius: 14px;
        color: var(--text-primary, #1f2937);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    [data-theme="dark"] .telram-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }

    /* Text & Label Visibility */
    .telram-text-title {
        color: var(--text-primary, #1f2937);
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

    /* Mode Switcher Grid: 4-col Desktop, 2x2 Grid Mobile (No awkward wrapping) */
    .mode-switch-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    @media (min-width: 992px) {
        .mode-switch-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    [data-theme="dark"] .mode-switch-grid {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    .btn-mode-tab {
        background: transparent;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        border: none;
        border-radius: 8px;
        padding: 9px 8px;
        text-align: center;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        line-height: 1.25;
    }
    .btn-mode-tab:hover {
        color: var(--text-primary, #1f2937);
        background: rgba(255, 255, 255, 0.5);
    }
    [data-theme="dark"] .btn-mode-tab {
        color: #94a3b8;
    }
    [data-theme="dark"] .btn-mode-tab:hover {
        color: #f8fafc;
        background: rgba(30, 41, 59, 0.6);
    }
    .btn-mode-tab.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
    }
    [data-theme="dark"] .btn-mode-tab.active {
        background: #1e293b;
        color: #f8fafc;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.4);
    }

    /* Form Controls & Inputs */
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] input[type="date"] {
        color-scheme: dark;
    }

    /* Stepper Box (Tahun, Bulan, Hari) */
    .stepper-box {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 8px 6px;
        text-align: center;
        transition: all 0.2s ease;
    }
    [data-theme="dark"] .stepper-box {
        background: rgba(15, 23, 42, 0.6) !important;
        border-color: #334155 !important;
    }
    .stepper-box input {
        color: var(--text-primary, #1f2937) !important;
        font-size: 18px;
    }
    [data-theme="dark"] .stepper-box input {
        color: #f8fafc !important;
    }
    .stepper-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    [data-theme="dark"] .stepper-label {
        color: #94a3b8;
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

    /* Fraction Cards (1/3, 1/2, 2/3) */
    .fraction-card-blue {
        background: rgba(59, 130, 246, 0.06);
        border-top: 4px solid #3b82f6 !important;
        border-radius: 12px;
    }
    [data-theme="dark"] .fraction-card-blue {
        background: rgba(59, 130, 246, 0.12) !important;
        border: 1px solid rgba(59, 130, 246, 0.25) !important;
        border-top: 4px solid #3b82f6 !important;
    }

    .fraction-card-amber {
        background: rgba(245, 158, 11, 0.06);
        border-top: 4px solid #f59e0b !important;
        border-radius: 12px;
    }
    [data-theme="dark"] .fraction-card-amber {
        background: rgba(245, 158, 11, 0.12) !important;
        border: 1px solid rgba(245, 158, 11, 0.25) !important;
        border-top: 4px solid #f59e0b !important;
    }

    .fraction-card-emerald {
        background: rgba(16, 185, 129, 0.06);
        border-top: 4px solid #10b981 !important;
        border-radius: 12px;
    }
    [data-theme="dark"] .fraction-card-emerald {
        background: rgba(16, 185, 129, 0.12) !important;
        border: 1px solid rgba(16, 185, 129, 0.25) !important;
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
        border-radius: 10px;
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

    /* Hero Alert Pengusulan PB */
    .hero-usulan-pb {
        background: linear-gradient(135deg, #78350f 0%, #b45309 50%, #d97706 100%);
        color: #ffffff;
        border-radius: 14px;
        border: 1px solid rgba(251, 191, 36, 0.3);
        overflow: hidden;
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
                        </div>
                        <small class="text-white-50 d-block mt-0.5" style="font-size: 11.5px;">
                            Perhitungan Ekspirasi, Proyeksi Remisi (Keppres 174/1999) &amp; Waktu Pengusulan PB/CB (UU 22/2022)
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ asset('telram-offline.html') }}" download="kalkulator-ekspirasi-offline.html" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1.5 fw-semibold shadow-sm" style="font-size: 12px;">
                        <i class="fas fa-download me-1 text-warning"></i> Unduh File Offline (.html)
                    </a>
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
                    <!-- Mode Selector Tab: 4-col on Desktop, 2x2 on Mobile -->
                    <div class="mb-3">
                        <label class="stepper-label mb-1.5 d-block">PILIH MODE PERHITUNGAN:</label>
                        <div class="mode-switch-grid">
                            <button type="button" id="btnModeRemisi" onclick="setMode('simulasi_remisi')" class="btn-mode-tab active">
                                <i class="fas fa-medal me-1 text-warning"></i> Proyeksi Remisi
                            </button>
                            <button type="button" id="btnModeMaju" onclick="setMode('maju')" class="btn-mode-tab">
                                <i class="fas fa-arrow-right me-1 text-primary"></i> Cari Ekspirasi
                            </button>
                            <button type="button" id="btnModeMundur" onclick="setMode('mundur')" class="btn-mode-tab">
                                <i class="fas fa-history me-1 text-secondary"></i> Lama Pidana
                            </button>
                            <button type="button" id="btnModeSisa" onclick="setMode('sisa')" class="btn-mode-tab">
                                <i class="fas fa-hourglass-half me-1 text-info"></i> Sisa Pidana
                            </button>
                        </div>
                    </div>

                    <!-- GRUP 1: Identitas WBP & Parameter Vonis -->
                    <div class="p-3 rounded-3 mb-3 border bg-light-subtle" id="groupIdentitasVonis" style="border-color: #cbd5e1;">
                        <!-- Input Identitas WBP (Khusus Simulasi Remisi & Cetak PDF) -->
                        <div id="sectionIdentitasWbp" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <label class="stepper-label mb-0 text-primary">
                                    <i class="fas fa-id-card me-1"></i> IDENTITAS WBP (UNTUK DOKUMEN PDF)
                                </label>
                                <span class="badge bg-light text-secondary border" style="font-size: 10px;">Opsional</span>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <input type="text" id="txtNamaWbp" class="form-control form-control-sm fw-semibold" placeholder="Nama WBP (Contoh: Budi)" style="border-radius: 8px; font-size: 12.5px;">
                                </div>
                                <div class="col-5">
                                    <input type="text" id="txtNoRegister" class="form-control form-control-sm fw-semibold" placeholder="No. Reg (BI.01/24)" style="border-radius: 8px; font-size: 12.5px;">
                                </div>
                            </div>
                            <div>
                                <input type="text" id="txtPerkaraPasal" class="form-control form-control-sm fw-semibold" placeholder="Pidana / Pasal (Contoh: 363 KUHP)" style="border-radius: 8px; font-size: 12.5px;">
                            </div>
                        </div>

                        <!-- Tanggal Mulai Penahanan / Per Tanggal -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label telram-text-title fw-bold mb-0" id="labelTglAwal" style="font-size: 12.5px;">
                                    Tgl. Mulai Penahanan <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none" onclick="setTglAwalHariIni()" style="font-size: 11px;">
                                    <i class="fas fa-calendar-day me-1"></i> Hari Ini
                                </button>
                            </div>
                            <input type="date" id="tglAwal" class="form-control fw-semibold" style="border-radius: 9px; font-size: 14px;">
                        </div>

                        <!-- Input Lama Pidana Pokok (Untuk Mode Maju & Simulasi Remisi) -->
                        <div id="sectionInputVonis">
                            <label class="form-label telram-text-title fw-bold mb-1.5" style="font-size: 12.5px;">
                                Lama Pidana / Vonis Pokok <span class="text-danger">*</span>
                            </label>
                            <div class="row g-1.5 g-sm-2">
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-0.5">TAHUN</label>
                                        <input type="number" id="vTahun" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0" style="font-size: 18px;">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-0.5">BULAN</label>
                                        <input type="number" id="vBulan" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0" style="font-size: 18px;">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stepper-box">
                                        <label class="d-block stepper-label mb-0.5">HARI</label>
                                        <input type="number" id="vHari" min="0" value="0" class="form-control text-center fw-bold border-0 bg-transparent p-0" style="font-size: 18px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Mode Mundur & Sisa: Tanggal Ekspirasi Input -->
                        <div id="sectionInputEkspirasi" class="d-none">
                            <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 12.5px;">
                                Tgl. Ekspirasi (Bebas Murni) <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tglEkspirasiInput" class="form-control fw-semibold" style="border-radius: 9px; font-size: 14px;">
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                <i class="fas fa-info-circle me-1"></i> Masukkan tanggal ekspirasi bebas murni vonis.
                            </small>
                        </div>
                    </div>

                    <!-- GRUP 2: Parameter Remisi Keagamaan (Khusus Mode Simulasi Remisi) -->
                    <div id="groupRemisiKeagamaan" class="mb-3">
                        <!-- Input Agama WBP -->
                        <div id="sectionAgama" class="mb-3">
                            <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 12.5px;">
                                Agama WBP (Remisi Khusus Hari Raya) <span class="text-danger">*</span>
                            </label>
                            <select id="selAgama" class="form-select fw-semibold" style="border-radius: 9px; font-size: 13px;">
                                <option value="islam" selected>Islam (Hari Raya Idul Fitri)</option>
                                <option value="kristen">Kristen Protestan (Hari Raya Natal - 25 Des)</option>
                                <option value="katolik">Katolik (Hari Raya Natal - 25 Des)</option>
                                <option value="hindu">Hindu (Hari Raya Nyepi)</option>
                                <option value="buddha">Buddha (Hari Raya Waisak)</option>
                                <option value="khonghucu">Khonghucu (Tahun Baru Imlek)</option>
                            </select>
                        </div>

                        <!-- Input Remisi Sebelumnya (Opsional) -->
                        <div id="sectionRemisiAwal" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label telram-text-title fw-bold mb-0" style="font-size: 12.5px;">
                                    Remisi SK Terdahulu (Jika Ada)
                                </label>
                                <span class="badge bg-light text-secondary border" style="font-size: 10px;">Opsional</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" id="vRemisiAwal" min="0" value="0" class="form-control fw-semibold" placeholder="0" style="border-radius: 8px 0 0 8px; font-size: 13px;">
                                <span class="input-group-text fw-bold text-muted" style="border-radius: 0 8px 8px 0; font-size: 12px;">HARI</span>
                            </div>
                            <small class="text-muted d-block mt-0.5" style="font-size: 10.5px;">
                                Isi jika narapidana mutasi/lanjutan yang telah memiliki perolehan remisi sebelumnya.
                            </small>
                        </div>
                    </div>

                    <!-- GRUP 3: Opsi Lanjutan SDP Ditjenpas (Collapsible / Terbuka Rapi) -->
                    <div id="groupOpsiSdplanjutan" class="mb-3 p-3 rounded-3 border bg-light-subtle" style="border-color: #cbd5e1;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="stepper-label mb-0 text-primary">
                                <i class="fas fa-balance-scale me-1"></i> STANDAR &amp; OPSI KHUSUS REMISI
                            </label>
                        </div>

                        <!-- Standar Batas Simulasi: 2/3 vs Ekspirasi -->
                        <div id="sectionTargetBasis" class="mb-2.5">
                            <label class="form-label telram-text-title fw-semibold mb-1" style="font-size: 12px;">
                                Standar Batas Simulasi:
                            </label>
                            <select id="selTargetBasis" class="form-select form-select-sm fw-semibold" style="border-radius: 8px; font-size: 12.5px;" onchange="updateHelpTargetBasis()">
                                <option value="2_3" selected>Standar 2/3 Masa Pidana (Untuk Usulan PB/CB)</option>
                                <option value="ekspirasi">Sampai Bebas Murni (Tanpa Hak PB/CB)</option>
                            </select>
                            <small class="text-muted d-block mt-0.5" style="font-size: 10.5px;" id="helpTargetBasis">
                                <i class="fas fa-info-circle text-primary me-0.5"></i> Standar 2/3: Remisi dihitung hingga 2/3 Definitif tercapai saat bebas bersyarat.
                            </small>
                        </div>

                        <!-- Remisi Dasawarsa (Tiap 10 Tahun Kemerdekaan RI) -->
                        <div id="sectionDasawarsa" class="mb-2.5 p-2 rounded-2 border" style="background: rgba(254, 243, 199, 0.4); border-color: #fde68a !important;">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="chkDasawarsa" checked style="cursor: pointer;">
                                <label class="form-check-label fw-bold telram-text-title" for="chkDasawarsa" style="font-size: 11.5px; cursor: pointer;">
                                    Sertakan Remisi Dasawarsa (Tiap 10 Th RI)
                                </label>
                            </div>
                            <small class="text-muted d-block mt-0.5 ps-4" style="font-size: 10px;">
                                Diberikan tiap 10 tahun (2025, 2035) sebesar 1/12 vonis pokok (maks 3 bln).
                            </small>
                        </div>

                        <!-- Remisi Tambahan (Pasal 4 Keppres 174/1999) -->
                        <div id="sectionRemisiTambahan">
                            <label class="form-label telram-text-title fw-semibold mb-1" style="font-size: 12px;">
                                Remisi Tambahan (Pasal 4 Keppres 174/1999):
                            </label>
                            <select id="selRemisiTambahan" class="form-select form-select-sm fw-semibold" style="border-radius: 8px; font-size: 12.5px;" onchange="toggleTahunMulaiTambahan()">
                                <option value="none" selected>Standar / Reguler (Tanpa Tambahan)</option>
                                <option value="pemuka">Pemuka Pembinaan Lapas (+1/3 dari RU)</option>
                                <option value="jasa_negara">Berjasa Bagi Negara / Donor (+1/2 dari RU)</option>
                            </select>

                            <!-- Input Mulai di Tahun ke Berapa -->
                            <div id="wrapTahunMulaiTambahan" class="mt-2 p-2 rounded-2 border d-none" style="background: rgba(37, 99, 235, 0.05); border-color: rgba(37, 99, 235, 0.2) !important;">
                                <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 11px;">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i> Mulai Remisi Tambahan Sejak:
                                </label>
                                <select id="selTahunMulaiTambahan" class="form-select form-select-sm fw-semibold" style="border-radius: 6px; font-size: 11.5px;">
                                    <option value="1">Tahun Ke-1 (Sejak RU Pertama)</option>
                                    <option value="2" selected>Tahun Ke-2 (Setelah 1/3 Pidana / Syarat Pemuka)</option>
                                    <option value="3">Tahun Ke-3</option>
                                    <option value="4">Tahun Ke-4</option>
                                    <option value="5">Tahun Ke-5</option>
                                </select>
                                <small class="text-muted d-block mt-0.5" style="font-size: 10px;">
                                    Remisi tambahan akan dihitung mulai tahun RU yang dipilih dan tahun-tahun berikutnya.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Utama Hitung -->
                    <button type="button" id="btnHitungUtama" onclick="hitungSesuaiMode()" class="btn btn-warning text-dark w-100 py-2.5 py-md-3 fw-bold shadow-sm" style="border-radius: 10px; font-size: 14px; letter-spacing: 0.3px;">
                        <i class="fas fa-medal me-1.5"></i> HITUNG PROYEKSI REMISI &amp; INTEGRASI
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
                        <i class="fas fa-medal" id="placeholderIcon"></i>
                    </div>
                    <h5 class="fw-bold telram-text-title mb-2" id="placeholderTitle">Hasil Proyeksi Remisi &amp; Integrasi</h5>
                    <p class="text-muted mb-4 mx-auto" id="placeholderDesc" style="max-width: 440px; font-size: 13px; line-height: 1.5;">
                        Tentukan Tanggal Mulai Penahanan, Lama Vonis, dan Agama WBP di sebelah kiri, kemudian klik tombol <strong>"HITUNG PROYEKSI REMISI &amp; INTEGRASI"</strong> untuk melihat perkiraan tanggal ekspirasi baru, waktu pengusulan PB/CB (H-2 bulan), serta tabel rincian remisi berkala.
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

                <!-- 2. Hero Card Mulai Pengusulan di SDP & Tanggal 2/3 Masa Pidana -->
                <div id="boxHeroPengusulan" class="card border-0 shadow-sm mb-3 hero-usulan-pb">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-stretch g-3">
                            <div class="col-12 col-md-7 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="text-white-50 fw-bold mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-calendar-check text-warning me-1"></i> Mulai Pengusulan di SDP:
                                    </div>
                                    <h2 class="fw-black mb-1.5 text-white" id="resTglMulaiPengusulan" style="font-weight: 800; font-size: clamp(20px, 4.5vw, 26px); word-break: break-word;">-</h2>
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
                                    <strong class="text-white d-block my-1" id="resTgl23Highlight" style="font-weight: 800; font-size: clamp(16px, 3.5vw, 20px); word-break: break-word;">-</strong>
                                    <div class="text-white-50" style="font-size: 10.5px;">Metode SDP: 2/3 Murni - Full Remisi</div>
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
                                <h2 class="fw-black mb-0 text-white" id="resSisaPidana" style="font-weight: 800; font-size: clamp(20px, 4.5vw, 26px);">-</h2>
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
                                Total Lama Pidana (Vonis Murni)
                            </span>
                            <h3 class="fw-bold mb-0 text-white mt-1" id="resLamaPidana" style="font-size: clamp(18px, 4vw, 24px);">-</h3>
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
                                <h2 class="fw-black mb-0 text-white" id="resEkspirasi" style="font-weight: 800; font-size: clamp(20px, 4.5vw, 26px); word-break: break-word;">-</h2>
                                <div id="subEkspirasiMurni" class="mt-1 text-white-50" style="font-size: 12px;">
                                    Ekspirasi Murni: <span class="text-white fw-bold" id="resEkspirasiMurniText">-</span>
                                </div>
                            </div>
                            <div class="text-start text-sm-end" id="boxTotalRemisiBadge">
                                <div class="text-white-50" style="font-size: 10.5px; text-transform: uppercase;">Total Perkiraan Remisi:</div>
                                <div class="fw-bold text-warning mt-0.5" id="resTotalRemisiText" style="font-size: clamp(17px, 3.8vw, 22px);">0 Hari</div>
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
                            <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res13">-</h4>
                            <small class="text-muted d-block mt-0.5" style="font-size: 10.5px;">Syarat dasar pembinaan</small>
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
                            <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res12">-</h4>
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
                            <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res23">-</h4>
                            <small class="text-muted d-block mt-0.5" id="subInfo23" style="font-size: 10.5px;">Syarat substantif waktu PB/CB</small>
                        </div>
                    </div>
                </div>

                <!-- 7. Info Ringkasan Ekstra -->
                <div class="p-2.5 px-3 summary-info-box mb-3">
                    <div class="row g-2 text-muted" style="font-size: 12px;">
                        <div class="col-sm-6">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> <span id="labelInfoTglAwal">Tgl. Ditahan:</span> <strong class="telram-text-title" id="infoTglAwal">-</strong>
                        </div>
                        <div class="col-sm-6">
                            <i class="fas fa-clock text-info me-1"></i> <span id="labelInfoTotalHari">Pidana Pokok:</span> <strong class="telram-text-title" id="infoTotalHari">-</strong>
                        </div>
                    </div>
                </div>

                <!-- 8. Section Rincian Proyeksi Remisi Berkala (Dual-View Mobile) -->
                <div id="sectionTableLedger" class="card border-0 shadow-sm telram-card">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2.5 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold mb-0 telram-text-title d-flex align-items-center gap-1.5" style="font-size: 13.5px;">
                                    <i class="fas fa-list-ol text-warning"></i> Rincian Proyeksi Remisi Berkala
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
                                            <th style="width: 40px;" class="text-center">#</th>
                                            <th style="min-width: 170px;">Nama Event &amp; Tahapan</th>
                                            <th style="min-width: 105px; white-space: nowrap;">Tanggal Event</th>
                                            <th style="min-width: 100px; white-space: nowrap;">Masa Dijalani</th>
                                            <th style="min-width: 95px; white-space: nowrap;">Status</th>
                                            <th style="min-width: 95px; white-space: nowrap;">Besaran</th>
                                            <th style="min-width: 85px; white-space: nowrap;">Akumulasi</th>
                                            <th style="min-width: 110px; white-space: nowrap;">Ekspirasi Baru</th>
                                            <th style="min-width: 200px;">Keterangan &amp; Landasan Alasan</th>
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

<!-- Toast Alert Container (Floating) -->
<div id="telramToast" class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999; display: none;">
    <div id="telramToastContent" class="toast align-items-center text-white border-0 shadow-lg show" role="alert" style="border-radius: 30px; padding: 6px 16px;">
        <div class="d-flex align-items-center">
            <span id="telramToastIcon" class="me-2"><i class="fas fa-check-circle"></i></span>
            <div class="toast-body fw-bold py-2" id="telramToastMsg" style="font-size: 13.5px;"></div>
        </div>
    </div>
</div>

<script>
    // State Aplikasi
    let currentMode = 'simulasi_remisi';
    let currentLedgerView = 'table';
    let lastResult = null;

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
        const groupRemisiKeagamaan = document.getElementById('groupRemisiKeagamaan');
        const groupOpsiSdplanjutan = document.getElementById('groupOpsiSdplanjutan');
        const sectionInputEkspirasi = document.getElementById('sectionInputEkspirasi');
        const btnHitung = document.getElementById('btnHitungUtama');
        const labelTglAwal = document.getElementById('labelTglAwal');
        const placeholderTitle = document.getElementById('placeholderTitle');
        const placeholderDesc = document.getElementById('placeholderDesc');
        const placeholderIcon = document.getElementById('placeholderIcon');

        [btnRemisi, btnMaju, btnMundur, btnSisa].forEach(b => { if (b) b.classList.remove('active'); });

        if (mode === 'simulasi_remisi') {
            if (btnRemisi) btnRemisi.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.remove('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.remove('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.remove('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.remove('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.add('d-none');
            if (labelTglAwal) labelTglAwal.innerHTML = 'Tgl. Mulai Penahanan <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-warning text-dark w-100 py-2.5 py-md-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-medal me-2"></i> HITUNG PROYEKSI REMISI &amp; INTEGRASI';

            placeholderTitle.innerText = 'Hasil Proyeksi Remisi & Integrasi';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan, Lama Vonis, dan Agama WBP di sebelah kiri, kemudian klik tombol <strong>"HITUNG PROYEKSI REMISI &amp; INTEGRASI"</strong> untuk melihat perkiraan tanggal ekspirasi baru, waktu pengusulan PB/CB (H-2 bulan), serta tabel rincian remisi berkala.';
            placeholderIcon.className = 'fas fa-medal';
        } else if (mode === 'maju') {
            if (btnMaju) btnMaju.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.add('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.remove('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.add('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.add('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.add('d-none');
            if (labelTglAwal) labelTglAwal.innerHTML = 'Tgl. Mulai Penahanan <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-primary w-100 py-2.5 py-md-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-calculator me-2"></i> HITUNG EKSPIRASI';

            placeholderTitle.innerText = 'Hasil Perhitungan Ekspirasi';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan dan Lama Vonis Hukuman di sebelah kiri, kemudian klik tombol <strong>"HITUNG EKSPIRASI"</strong> untuk melihat perkiraan tanggal ekspirasi dan tahapan masa pidana secara rinci.';
            placeholderIcon.className = 'fas fa-calendar-alt';
        } else if (mode === 'mundur') {
            if (btnMundur) btnMundur.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.add('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.add('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.add('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.add('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.remove('d-none');
            if (labelTglAwal) labelTglAwal.innerHTML = 'Tgl. Mulai Penahanan <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-secondary w-100 py-2.5 py-md-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-history me-2"></i> HITUNG LAMA PIDANA';

            placeholderTitle.innerText = 'Hasil Perhitungan Lama Pidana';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan dan Tanggal Ekspirasi (Bebas Murni) di sebelah kiri, kemudian klik tombol <strong>"HITUNG LAMA PIDANA"</strong> untuk mencari durasi lama vonis hukuman secara otomatis.';
            placeholderIcon.className = 'fas fa-hourglass-half';
        } else if (mode === 'sisa') {
            if (btnSisa) btnSisa.classList.add('active');
            if (sectionIdentitasWbp) sectionIdentitasWbp.classList.add('d-none');
            if (sectionInputVonis) sectionInputVonis.classList.add('d-none');
            if (groupRemisiKeagamaan) groupRemisiKeagamaan.classList.add('d-none');
            if (groupOpsiSdplanjutan) groupOpsiSdplanjutan.classList.add('d-none');
            if (sectionInputEkspirasi) sectionInputEkspirasi.classList.remove('d-none');
            if (labelTglAwal) labelTglAwal.innerHTML = 'Per Tanggal (Tgl. Perhitungan) <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-info text-white w-100 py-2.5 py-md-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-hourglass-half me-2"></i> HITUNG SISA PIDANA';

            placeholderTitle.innerText = 'Hasil Perhitungan Sisa Pidana';
            placeholderDesc.innerHTML = 'Tentukan Per Tanggal Perhitungan dan Tanggal Ekspirasi (Bebas Murni) di sebelah kiri, kemudian klik tombol <strong>"HITUNG SISA PIDANA"</strong> untuk melihat sisa masa pidana yang belum dijalani.';
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
            help.innerHTML = '<i class="fas fa-door-open text-warning me-0.5"></i> <b>Ekspirasi Penuh:</b> Perhitungan remisi dilanjutkan hingga bebas murni tanpa hak integrasi PB/CB.';
        }
    }

    // Toggle Tampilan Dropdown Mulai Tahun Remisi Tambahan
    function toggleTahunMulaiTambahan() {
        const sel = document.getElementById('selRemisiTambahan');
        const wrap = document.getElementById('wrapTahunMulaiTambahan');
        if (!sel || !wrap) return;
        if (sel.value !== 'none') {
            wrap.classList.remove('d-none');
        } else {
            wrap.classList.add('d-none');
        }
    }

    // Reset Form
    function resetForm() {
        if (document.getElementById('txtNamaWbp')) document.getElementById('txtNamaWbp').value = '';
        if (document.getElementById('txtNoRegister')) document.getElementById('txtNoRegister').value = '';
        if (document.getElementById('txtPerkaraPasal')) document.getElementById('txtPerkaraPasal').value = '';
        document.getElementById('tglAwal').value = '';
        document.getElementById('vTahun').value = '0';
        document.getElementById('vBulan').value = '0';
        document.getElementById('vHari').value = '0';
        document.getElementById('selAgama').value = 'islam';
        if (document.getElementById('chkDasawarsa')) document.getElementById('chkDasawarsa').checked = true;
        if (document.getElementById('selRemisiTambahan')) document.getElementById('selRemisiTambahan').value = 'none';
        if (document.getElementById('selTahunMulaiTambahan')) document.getElementById('selTahunMulaiTambahan').value = '2';
        toggleTahunMulaiTambahan();
        if (document.getElementById('selTargetBasis')) document.getElementById('selTargetBasis').value = '2_3';
        updateHelpTargetBasis();
        document.getElementById('vRemisiAwal').value = '0';
        document.getElementById('tglEkspirasiInput').value = '';

        document.getElementById('hasilSection').classList.add('d-none');
        document.getElementById('placeholderSection').classList.remove('d-none');
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
            document.getElementById('vTahun').value = '3';
            document.getElementById('vBulan').value = '0';
            document.getElementById('vHari').value = '0';
            document.getElementById('selAgama').value = 'islam';
            if (document.getElementById('chkDasawarsa')) document.getElementById('chkDasawarsa').checked = true;
            if (document.getElementById('selRemisiTambahan')) document.getElementById('selRemisiTambahan').value = 'none';
            if (document.getElementById('selTahunMulaiTambahan')) document.getElementById('selTahunMulaiTambahan').value = '2';
            toggleTahunMulaiTambahan();
            if (document.getElementById('selTargetBasis')) document.getElementById('selTargetBasis').value = '2_3';
            updateHelpTargetBasis();
            document.getElementById('vRemisiAwal').value = '0';
        } else {
            const futureDate = new Date(now);
            futureDate.setFullYear(futureDate.getFullYear() + 2);
            const fY = futureDate.getFullYear();
            const fM = String(futureDate.getMonth() + 1).padStart(2, '0');
            const fD = String(futureDate.getDate()).padStart(2, '0');
            document.getElementById('tglEkspirasiInput').value = `${fY}-${fM}-${fD}`;
        }

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
                const agama = document.getElementById('selAgama').value;
                const remisiAwal = parseInt(document.getElementById('vRemisiAwal').value) || 0;

                if (vThn === 0 && vBln === 0 && vHri === 0) {
                    showToast('Harap tentukan Lama Pidana Vonis!', 'error');
                    btnHitung.disabled = false;
                    btnHitung.innerHTML = originalBtnHtml;
                    return;
                }

                payload.tahun = vThn;
                payload.bulan = vBln;
                payload.hari = vHri;
                payload.agama = agama;
                payload.remisi_awal_hari = remisiAwal;
                payload.nama_wbp = document.getElementById('txtNamaWbp') ? document.getElementById('txtNamaWbp').value : '';
                payload.no_register = document.getElementById('txtNoRegister') ? document.getElementById('txtNoRegister').value : '';
                payload.perkara_pasal = document.getElementById('txtPerkaraPasal') ? document.getElementById('txtPerkaraPasal').value : '';
                payload.include_dasawarsa = document.getElementById('chkDasawarsa') ? (document.getElementById('chkDasawarsa').checked ? 1 : 0) : 1;
                payload.kategori_tambahan = document.getElementById('selRemisiTambahan') ? document.getElementById('selRemisiTambahan').value : 'none';
                payload.tahun_mulai_tambahan = document.getElementById('selTahunMulaiTambahan') ? (parseInt(document.getElementById('selTahunMulaiTambahan').value) || 1) : 1;
                payload.target_basis = document.getElementById('selTargetBasis') ? document.getElementById('selTargetBasis').value : '2_3';
            } else if (currentMode === 'maju') {
                payload.tahun = parseInt(document.getElementById('vTahun').value) || 0;
                payload.bulan = parseInt(document.getElementById('vBulan').value) || 0;
                payload.hari = parseInt(document.getElementById('vHari').value) || 0;

                if (payload.tahun === 0 && payload.bulan === 0 && payload.hari === 0) {
                    showToast('Harap tentukan Lama Pidana Vonis!', 'error');
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
            document.getElementById('res13').innerText = data.formatted.tgl_1_3_murni;
            document.getElementById('res12').innerText = data.formatted.tgl_1_2_definitif;
            document.getElementById('subInfo12').innerText = `Asimilasi (Murni: ${data.formatted.tgl_1_2_murni})`;

            document.getElementById('badgeLabel23').innerText = `2/3 ${data.jenis_program}`;
            document.getElementById('res23').innerText = data.formatted.tgl_2_3_definitif;
            document.getElementById('subInfo23').innerText = `(Murni: ${data.formatted.tgl_2_3_murni})`;

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
                    noteLedger.innerHTML = `<i class="fas fa-info-circle me-1 text-warning"></i> <b>Basis Ekspirasi Penuh:</b> Proyeksi remisi dihitung secara berkala hingga bebas murni tanpa hak integrasi PB/CB.`;
                }
            }

            // Info ringkasan
            document.getElementById('labelInfoTglAwal').innerText = 'Tgl. Ditahan:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_awal;
            document.getElementById('labelInfoTotalHari').innerText = 'Pidana Pokok:';
            document.getElementById('infoTotalHari').innerText = `${data.total_hari_vonis.toLocaleString('id-ID')} (${data.lama_pidana_teks})`;

            showToast(`Proyeksi Remisi & Waktu Pengusulan ${data.jenis_program} Berhasil Dihitung!`, 'success');
        } else if (data.mode === 'sisa') {
            boxHeroPengusulan.classList.add('d-none');
            boxHasilSisa.classList.remove('d-none');
            boxHasilPidana.classList.add('d-none');
            cardEkspirasi.classList.add('d-none');
            rowFractionCards.classList.add('d-none');
            sectionTableLedger.classList.add('d-none');
            btnGroupAksiHasil.classList.add('d-none');

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

            document.getElementById('resLamaPidana').innerText = data.lama_pidana_teks;
            document.getElementById('badgeEkspirasiTitle').innerHTML = '<i class="fas fa-door-open me-1"></i> EKSPIRASI BEBAS MURNI';
            document.getElementById('resEkspirasi').innerText = data.formatted.tgl_ekspirasi;
            document.getElementById('subEkspirasiMurni').classList.add('d-none');
            document.getElementById('boxTotalRemisiBadge').classList.add('d-none');

            document.getElementById('res13').innerText = data.formatted.tgl_1_3;
            document.getElementById('res12').innerText = data.formatted.tgl_1_2;
            document.getElementById('subInfo12').innerText = '1/2 Masa Pidana Murni';
            document.getElementById('badgeLabel23').innerText = '2/3 Masa Pidana';
            document.getElementById('res23').innerText = data.formatted.tgl_2_3;
            document.getElementById('subInfo23').innerText = '2/3 Masa Pidana Murni';

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

            document.getElementById('badgeEkspirasiTitle').innerHTML = '<i class="fas fa-door-open me-1"></i> EKSPIRASI BEBAS MURNI';
            document.getElementById('resEkspirasi').innerText = data.formatted.tgl_ekspirasi;
            document.getElementById('subEkspirasiMurni').classList.add('d-none');
            document.getElementById('boxTotalRemisiBadge').classList.add('d-none');

            document.getElementById('res13').innerText = data.formatted.tgl_1_3;
            document.getElementById('res12').innerText = data.formatted.tgl_1_2;
            document.getElementById('subInfo12').innerText = '1/2 Masa Pidana Murni';
            document.getElementById('badgeLabel23').innerText = '2/3 Masa Pidana';
            document.getElementById('res23').innerText = data.formatted.tgl_2_3;
            document.getElementById('subInfo23').innerText = '2/3 Masa Pidana Murni';

            document.getElementById('labelInfoTglAwal').innerText = 'Tgl. Ditahan:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_awal;
            document.getElementById('labelInfoTotalHari').innerText = 'Total Hari Vonis:';
            document.getElementById('infoTotalHari').innerText = `${data.total_hari_vonis.toLocaleString('id-ID')} (${data.lama_pidana_teks})`;
            hasilHeaderTitle.innerText = 'Hasil Perhitungan Ekspirasi';

            showToast('Kalkulasi Ekspirasi Selesai!', 'success');
        }

        document.getElementById('placeholderSection').classList.add('d-none');
        document.getElementById('hasilSection').classList.remove('d-none');

        // Mobile UX: Auto smooth scroll ke hasil kalkulasi agar user langsung melihat angka
        if (window.innerWidth < 992) {
            setTimeout(() => {
                const el = document.getElementById('hasilSection');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }

    // Render Tabel Proyeksi Remisi (Tabel SDP & Mobile Timeline Cards)
    function renderTableLedger(ledger) {
        const tbody = document.getElementById('tbodyProyeksiRemisi');
        const cardsContainer = document.getElementById('cardsProyeksiRemisi');
        tbody.innerHTML = '';
        cardsContainer.innerHTML = '';

        if (!ledger || ledger.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3 text-muted">Tidak ada jadwal event remisi sebelum tanggal ekspirasi.</td></tr>';
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
                <td class="text-center fw-bold text-muted">${index + 1}</td>
                <td>
                    <strong class="text-dark">${row.nama_event}</strong>
                    ${row.tahap && row.tahap !== '-' ? `<br><small class="text-primary fw-semibold" style="font-size: 11px;">[Tahap: ${row.tahap}]</small>` : ''}
                </td>
                <td style="white-space: nowrap;">${row.tanggal_indo}</td>
                <td style="white-space: nowrap;"><small class="text-muted fw-semibold">${row.hari_dijalani} hr<br><span style="font-size: 10.5px;">(${row.masa_dijalani_teks})</span></small></td>
                <td style="white-space: nowrap;">${statusBadge}</td>
                <td style="white-space: nowrap;" class="fw-bold ${row.besaran_hari > 0 ? 'text-primary' : 'text-danger'}">${row.besaran_teks}</td>
                <td style="white-space: nowrap;" class="fw-bold text-success">${row.total_akumulasi_hari} Hari</td>
                <td style="white-space: nowrap;" class="fw-bold text-danger">${row.perkiraan_ekspirasi_baru_indo}</td>
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
                        <span class="badge bg-secondary text-white rounded-circle" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 10.5px;">${index + 1}</span>
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
                        <span class="fw-bold text-dark" style="font-size: 12.5px;">${row.tanggal_indo}</span>
                        <small class="text-muted d-block" style="font-size: 10.5px;">Dijalani: ${row.hari_dijalani} hr (${row.masa_dijalani_teks})</small>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted d-block" style="font-size: 10px; text-transform: uppercase;">BESARAN REMISI</small>
                        <span class="fw-bold ${row.besaran_hari > 0 ? 'text-primary' : 'text-danger'}" style="font-size: 13.5px;">${row.besaran_teks}</span>
                        <small class="text-success fw-bold d-block" style="font-size: 10.5px;">Akumulasi: ${row.total_akumulasi_hari} Hari</small>
                    </div>
                </div>
                <div class="p-2 rounded-2 border d-flex justify-content-between align-items-center mb-1.5" style="background: rgba(254, 242, 242, 0.6); border-color: #fecaca !important;">
                    <small class="text-danger fw-bold" style="font-size: 10.5px; text-transform: uppercase;">EKSPIRASI SETELAH REMISI:</small>
                    <span class="fw-bold text-danger" style="font-size: 13px;">${row.perkiraan_ekspirasi_baru_indo}</span>
                </div>
                <small class="text-muted d-block" style="font-size: 10.5px; line-height: 1.35;">
                    <i class="fas fa-balance-scale text-secondary me-1"></i> ${row.keterangan_alasan || '-'}
                </small>
            `;
            cardsContainer.appendChild(card);
        });
    }

    // Unduh Dokumen Resmi PDF Proyeksi Remisi & Telram
    function unduhPdfResmi() {
        if (!lastResult || lastResult.mode !== 'simulasi_remisi') {
            showToast('Lakukan simulasi proyeksi remisi terlebih dahulu!', 'warning');
            return;
        }

        const inputTgl = document.getElementById('tglAwal').value;
        const vThn = parseInt(document.getElementById('vTahun').value) || 0;
        const vBln = parseInt(document.getElementById('vBulan').value) || 0;
        const vHri = parseInt(document.getElementById('vHari').value) || 0;
        const agama = document.getElementById('selAgama').value;
        const remisiAwal = parseInt(document.getElementById('vRemisiAwal').value) || 0;
        const namaWbp = document.getElementById('txtNamaWbp') ? document.getElementById('txtNamaWbp').value : '';
        const noRegister = document.getElementById('txtNoRegister') ? document.getElementById('txtNoRegister').value : '';
        const perkaraPasal = document.getElementById('txtPerkaraPasal') ? document.getElementById('txtPerkaraPasal').value : '';
        const includeDasawarsa = document.getElementById('chkDasawarsa') ? (document.getElementById('chkDasawarsa').checked ? '1' : '0') : '1';
        const kategoriTambahan = document.getElementById('selRemisiTambahan') ? document.getElementById('selRemisiTambahan').value : 'none';
        const tahunMulaiTambahan = document.getElementById('selTahunMulaiTambahan') ? document.getElementById('selTahunMulaiTambahan').value : '1';
        const targetBasis = document.getElementById('selTargetBasis') ? document.getElementById('selTargetBasis').value : '2_3';

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("telram.export-pdf") }}';
        form.target = '_blank';

        const fields = {
            _token: '{{ csrf_token() }}',
            tgl_awal: inputTgl,
            tahun: vThn,
            bulan: vBln,
            hari: vHri,
            agama: agama,
            remisi_awal_hari: remisiAwal,
            nama_wbp: namaWbp,
            no_register: noRegister,
            perkara_pasal: perkaraPasal,
            include_dasawarsa: includeDasawarsa,
            kategori_tambahan: kategoriTambahan,
            tahun_mulai_tambahan: tahunMulaiTambahan,
            target_basis: targetBasis
        };

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

    // Salin Ringkasan Teks untuk WhatsApp
    function salinRingkasanTeks() {
        if (!lastResult || lastResult.mode !== 'simulasi_remisi') {
            showToast('Lakukan simulasi remisi terlebih dahulu!', 'warning');
            return;
        }

        const d = lastResult;
        const text = `*LEMBAR PERKIRAAN PERHITUNGAN*
*Sultan Banten - Kemenimipas Banten*
---------------------------------------
*Nama WBP:* ${d.nama_wbp || 'Narapidana'}
*No. Register:* ${d.no_register || '-'}
*Pidana / Pasal:* ${d.perkara_pasal || '-'}
*Tanggal Ditahan:* ${d.formatted.tgl_awal}
*Pidana Pokok:* ${d.lama_pidana_teks} (${d.total_hari_vonis} hari)
*Agama WBP:* ${d.agama}
*Status Pembinaan:* ${d.kategori_tambahan_label || 'Standar (Reguler)'}
*Remisi Dasawarsa:* ${d.include_dasawarsa ? 'Disertakan (1/12 vonis)' : 'Tidak Disertakan'}
*Basis Simulasi:* ${d.target_basis_label || 'Standar 2/3 Masa Pidana (PB/CB)'}
*Program Integrasi:* ${d.nama_program}
---------------------------------------
*Tonggak Masa Pidana:*
• *1/3 Masa Pidana:* ${d.formatted.tgl_1_3_murni}
• *1/2 Asimilasi:* ${d.formatted.tgl_1_2_definitif} (Murni: ${d.formatted.tgl_1_2_murni})
• *2/3 ${d.jenis_program}:* ${d.formatted.tgl_2_3_definitif} (Murni: ${d.formatted.tgl_2_3_murni})
• *Ekspirasi Definitif:* ${d.formatted.ekspirasi_definitif} (Murni: ${d.formatted.ekspirasi_murni})
• *Total Perkiraan Remisi:* ${d.total_proyeksi_remisi_hari} Hari (${d.total_proyeksi_remisi_teks})
---------------------------------------
⚠️ *MULAI PENGUSULAN DI SDP (H-2 BULAN):*
*Tanggal:* ${d.formatted.tgl_mulai_pengusulan}
*Status:* ${d.status_pengusulan.label}
*Catatan:* ${d.status_pengusulan.pesan}
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
        toggleTahunMulaiTambahan();

        // Deteksi layar ponsel: Jika mobile (< 768px), otomatis pilih Kartu Mobile
        if (window.innerWidth < 768) {
            setLedgerView('cards');
        } else {
            setLedgerView('table');
        }
    });
</script>
@endsection
