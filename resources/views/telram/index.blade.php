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

    /* Mode Switcher Tabs */
    .mode-switch-container {
        background: #e2e8f0;
        padding: 4px;
        border-radius: 10px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        transition: background-color 0.2s ease;
    }
    [data-theme="dark"] .mode-switch-container {
        background: #0f172a !important;
        border: 1px solid #334155;
    }

    .btn-mode-tab {
        background: transparent;
        color: #64748b;
        font-size: 12.5px;
        font-weight: 700;
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        flex: 1 1 auto;
        min-width: 120px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .btn-mode-tab:hover {
        color: var(--text-primary, #1f2937);
    }
    [data-theme="dark"] .btn-mode-tab {
        color: #94a3b8;
    }
    [data-theme="dark"] .btn-mode-tab:hover {
        color: #f8fafc;
    }
    .btn-mode-tab.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    [data-theme="dark"] .btn-mode-tab.active {
        background: #1e293b;
        color: #f8fafc;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
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
        background: rgba(241, 245, 249, 0.6);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px;
        text-align: center;
        transition: all 0.2s ease;
    }
    [data-theme="dark"] .stepper-box {
        background: rgba(15, 23, 42, 0.6) !important;
        border-color: #334155 !important;
    }
    .stepper-box input {
        color: var(--text-primary, #1f2937) !important;
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
        font-size: 17px;
        letter-spacing: -0.3px;
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
        border-radius: 12px;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }

    /* Table Ledger */
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

    /* Print Styles */
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        .header-card, .btn, .mode-switch-container, #sectionInputVonis, #sectionInputEkspirasi, #sectionAgama, #sectionRemisiAwal, #btnHitungUtama, #placeholderSection {
            display: none !important;
        }
        #hasilSection {
            display: block !important;
        }
        .telram-card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border-radius: 14px;">
        <div class="card-body p-3 px-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 46px; height: 46px; border-radius: 11px; background: rgba(255,255,255,0.12); display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fas fa-calculator text-warning"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-white">Kalkulator Telram & Integrasi</h4>
                        <small class="text-white-50" style="font-size: 12px;">
                            Perhitungan Ekspirasi, Proyeksi Remisi (Keppres 174/1999) & Waktu Pengusulan PB/CB (UU 22/2022)
                        </small>
                    </div>
                </div>
                <div>
                    <a href="{{ asset('telram-offline.html') }}" download="kalkulator-ekspirasi-offline.html" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1.5 fw-semibold shadow-sm" style="font-size: 12.5px;">
                        <i class="fas fa-download me-1.5 text-warning"></i> Unduh File Offline (.html)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area: 2 Columns on Desktop -->
    <div class="row g-4">
        <!-- Left Column: Input Form -->
        <div class="col-lg-5 col-xl-5">
            <div class="card border-0 shadow-sm telram-card">
                <div class="card-header bg-transparent border-bottom p-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 telram-text-title">
                        <i class="fas fa-edit text-primary me-2"></i>Parameter Perhitungan
                    </h6>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1" onclick="resetForm()" style="font-size: 11.5px;">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
                <div class="card-body p-4">
                    <!-- Mode Selector Tab -->
                    <div class="mode-switch-container mb-4">
                        <button type="button" id="btnModeRemisi" onclick="setMode('simulasi_remisi')" class="btn-mode-tab active">
                            <i class="fas fa-medal me-1 text-warning"></i> Proyeksi Remisi & PB
                        </button>
                        <button type="button" id="btnModeMaju" onclick="setMode('maju')" class="btn-mode-tab">
                            <i class="fas fa-arrow-right me-1 text-primary"></i> Cari Ekspirasi
                        </button>
                        <button type="button" id="btnModeMundur" onclick="setMode('mundur')" class="btn-mode-tab">
                            <i class="fas fa-history me-1 text-secondary"></i> Cari Lama Pidana
                        </button>
                        <button type="button" id="btnModeSisa" onclick="setMode('sisa')" class="btn-mode-tab">
                            <i class="fas fa-hourglass-half me-1 text-info"></i> Hitung Sisa Pidana
                        </button>
                    </div>

                    <!-- Tanggal Mulai Penahanan / Per Tanggal -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label telram-text-title fw-bold mb-0" id="labelTglAwal" style="font-size: 13px;">
                                Tgl. Mulai Penahanan <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none" onclick="setTglAwalHariIni()" style="font-size: 11.5px;">
                                <i class="fas fa-calendar-day me-1"></i> Hari Ini
                            </button>
                        </div>
                        <input type="date" id="tglAwal" class="form-control form-control-lg fw-semibold" style="border-radius: 10px; font-size: 14.5px;">
                    </div>

                    <!-- Input Lama Pidana Pokok (Untuk Mode Maju & Simulasi Remisi) -->
                    <div id="sectionInputVonis" class="mb-3">
                        <label class="form-label telram-text-title fw-bold mb-2" style="font-size: 13px;">
                            Lama Pidana / Vonis Hukuman <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="stepper-box">
                                    <label class="d-block stepper-label mb-1">TAHUN</label>
                                    <input type="number" id="vTahun" min="0" value="0" class="form-control form-control-lg text-center fw-bold border-0 bg-transparent" style="font-size: 20px;">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stepper-box">
                                    <label class="d-block stepper-label mb-1">BULAN</label>
                                    <input type="number" id="vBulan" min="0" value="0" class="form-control form-control-lg text-center fw-bold border-0 bg-transparent" style="font-size: 20px;">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stepper-box">
                                    <label class="d-block stepper-label mb-1">HARI</label>
                                    <input type="number" id="vHari" min="0" value="0" class="form-control form-control-lg text-center fw-bold border-0 bg-transparent" style="font-size: 20px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Tambahan Khusus Simulasi Remisi: Agama WBP -->
                    <div id="sectionAgama" class="mb-3">
                        <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 13px;">
                            Agama WBP (Untuk Remisi Khusus Hari Raya) <span class="text-danger">*</span>
                        </label>
                        <select id="selAgama" class="form-select fw-semibold" style="border-radius: 10px; font-size: 13.5px;">
                            <option value="islam" selected>Islam (Hari Raya Idul Fitri)</option>
                            <option value="kristen">Kristen Protestan (Hari Raya Natal - 25 Des)</option>
                            <option value="katolik">Katolik (Hari Raya Natal - 25 Des)</option>
                            <option value="hindu">Hindu (Hari Raya Nyepi)</option>
                            <option value="buddha">Buddha (Hari Raya Waisak)</option>
                            <option value="khonghucu">Khonghucu (Tahun Baru Imlek)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 11.5px;">
                            <i class="fas fa-info-circle me-1"></i> Remisi Khusus (RK) diberikan pada Hari Raya Keagamaan masing-masing WBP.
                        </small>
                    </div>

                    <!-- Input Tambahan Khusus Simulasi Remisi: Remisi yang Sudah Pernah Diperoleh (Opsional) -->
                    <div id="sectionRemisiAwal" class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label telram-text-title fw-bold mb-0" style="font-size: 13px;">
                                Remisi Sebelumnya yang Telah Diperoleh
                            </label>
                            <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Opsional</span>
                        </div>
                        <div class="input-group">
                            <input type="number" id="vRemisiAwal" min="0" value="0" class="form-control fw-semibold" placeholder="0" style="border-radius: 10px 0 0 10px; font-size: 14px;">
                            <span class="input-group-text fw-bold text-muted" style="border-radius: 0 10px 10px 0; font-size: 12.5px;">HARI</span>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 11.5px;">
                            Isi jika narapidana pindahan/lanjutan yang sudah mengantongi SK Remisi sebelumnya.
                        </small>
                    </div>

                    <!-- Input Mode Mundur & Sisa: Tanggal Ekspirasi Input -->
                    <div id="sectionInputEkspirasi" class="mb-4 d-none">
                        <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 13px;">
                            Tgl. Ekspirasi (Bebas Murni) <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="tglEkspirasiInput" class="form-control form-control-lg fw-semibold" style="border-radius: 10px; font-size: 14.5px;">
                        <small class="text-muted d-block mt-1" style="font-size: 11.5px;">
                            <i class="fas fa-info-circle me-1"></i> Masukkan Tanggal ekspirasi (Bebas Murni)
                        </small>
                    </div>

                    <!-- Tombol Hitung -->
                    <button type="button" id="btnHitungUtama" onclick="hitungSesuaiMode()" class="btn btn-warning text-dark w-100 py-3 fw-bold shadow-sm" style="border-radius: 10px; font-size: 14.5px; letter-spacing: 0.5px;">
                        <i class="fas fa-medal me-2"></i> HITUNG PROYEKSI REMISI & INTEGRASI
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Results & Actions -->
        <div class="col-lg-7 col-xl-7">
            <!-- Empty Placeholder (When No Calculation Yet) -->
            <div id="placeholderSection" class="card border-0 shadow-sm text-center p-5 placeholder-card">
                <div class="py-4">
                    <div class="mb-3" style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.1); display: inline-flex; align-items: center; justify-content: center; font-size: 30px; color: #f59e0b;">
                        <i class="fas fa-medal" id="placeholderIcon"></i>
                    </div>
                    <h5 class="fw-bold telram-text-title mb-2" id="placeholderTitle">Hasil Proyeksi Remisi & Integrasi</h5>
                    <p class="text-muted mb-4 mx-auto" id="placeholderDesc" style="max-width: 440px; font-size: 13.5px;">
                        Tentukan Tanggal Mulai Penahanan, Lama Vonis, dan Agama WBP di sebelah kiri, kemudian klik tombol <strong>"HITUNG PROYEKSI REMISI & INTEGRASI"</strong> untuk melihat perkiraan tanggal ekspirasi baru, waktu pengusulan PB/CB (H-2 bulan), serta tabel rincian remisi berkala.
                    </p>
                    <div class="d-inline-flex gap-2">
                        <button type="button" class="btn btn-outline-warning text-dark btn-sm px-3 rounded-pill" onclick="demoSampleData()">
                            <i class="fas fa-magic me-1"></i> Coba Data Contoh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section (Initially Hidden) -->
            <div id="hasilSection" class="d-none">
                <div class="card border-0 shadow-sm mb-4 telram-card">
                    <div class="card-header bg-transparent border-bottom p-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" id="badgeProgramStatus" style="font-size: 12px; padding: 6px 12px;">PB</span>
                            <h6 class="fw-bold mb-0 telram-text-title" id="hasilHeaderTitle">
                                Hasil Proyeksi Remisi & Integrasi
                            </h6>
                        </div>
                        <div class="d-flex gap-1.5" id="btnGroupAksiHasil">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1" onclick="cetakLembarPerhitungan()" title="Cetak Lembar Perhitungan" style="font-size: 11.5px;">
                                <i class="fas fa-print me-1"></i> Cetak
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-1" onclick="salinRingkasanTeks()" title="Salin Ringkasan untuk WhatsApp" style="font-size: 11.5px;">
                                <i class="fas fa-copy me-1"></i> Salin Ringkasan
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Mode Remisi: Hero Alert Pengusulan PB / CB (H-2 Bulan) -->
                        <div id="boxHeroPengusulan" class="card border-0 shadow-sm mb-4 hero-usulan-pb">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <span class="badge bg-white text-dark fw-bold px-2.5 py-1" style="font-size: 11.5px; letter-spacing: 0.5px;">
                                        <i class="fas fa-bullhorn text-warning me-1"></i> WAKTU MULAI PENGUSULAN INTEGRASI KE SISTEM (H-2 BULAN SEBELUM 2/3)
                                    </span>
                                    <span class="badge" id="badgeStatusPengusulan" style="font-size: 11.5px; padding: 5px 10px;">
                                        -
                                    </span>
                                </div>
                                <div class="row align-items-center mt-2">
                                    <div class="col-md-7">
                                        <div class="text-white-50" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Mulai Pengusulan Berkas / Litmas Bapas:</div>
                                        <h2 class="fw-black mb-1 text-white" id="resTglMulaiPengusulan" style="font-weight: 800; font-size: 24px;">-</h2>
                                        <p class="mb-0 text-white" id="pesanPengusulan" style="font-size: 12.5px; opacity: 0.95;">
                                            -
                                        </p>
                                    </div>
                                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                        <div class="p-2.5 rounded-3" style="background: rgba(0,0,0,0.2); display: inline-block; text-align: left; min-width: 200px;">
                                            <div class="text-white-50" style="font-size: 11px;">JATUH TEMPO 2/3 DEFINITIF:</div>
                                            <strong class="text-white d-block" id="resTgl23Highlight" style="font-size: 15px;">-</strong>
                                            <div class="text-white-50 mt-1" style="font-size: 11px;">METODE: 2/3 MINUS FULL REMISI</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mode Sisa Result Card (Dominant Highlight) -->
                        <div id="boxHasilSisa" class="card border-0 shadow-sm mb-4 d-none" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border-radius: 12px; position: relative; overflow: hidden;">
                            <div class="card-body p-4 position-relative" style="z-index: 2;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-white text-success px-2.5 py-1 mb-2 fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="fas fa-hourglass-half me-1"></i> SISA MASA PIDANA
                                        </span>
                                        <h2 class="fw-black mb-0 text-white" id="resSisaPidana" style="font-weight: 800; font-size: 26px;">-</h2>
                                    </div>
                                    <div class="d-none d-sm-block text-white" style="font-size: 48px; opacity: 0.25;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mode Mundur Result Box: Lama Pidana -->
                        <div id="boxHasilPidana" class="card border-0 shadow-sm mb-3 d-none" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white; border-radius: 12px;">
                            <div class="card-body p-3 px-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-uppercase fw-bold text-white-50" style="font-size: 11px; letter-spacing: 1px;">
                                        Total Lama Pidana (Vonis Murni)
                                    </span>
                                    <h3 class="fw-bold mb-0 text-white mt-1" id="resLamaPidana">-</h3>
                                </div>
                                <div style="font-size: 32px; opacity: 0.3;">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Ekspirasi Card (Dominant Highlight) -->
                        <div id="cardEkspirasi" class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white; border-radius: 12px; position: relative; overflow: hidden;">
                            <div class="card-body p-4 position-relative" style="z-index: 2;">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="badge bg-white text-danger px-2.5 py-1 mb-2 fw-bold" id="badgeEkspirasiTitle" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="fas fa-door-open me-1"></i> EKSPIRASI BEBAS DEFINITIF
                                        </span>
                                        <h2 class="fw-black mb-0 text-white" id="resEkspirasi" style="font-weight: 800; font-size: 26px;">-</h2>
                                        <div id="subEkspirasiMurni" class="mt-1 text-white-50" style="font-size: 12.5px;">
                                            Ekspirasi Murni: <span class="text-white fw-bold" id="resEkspirasiMurniText">-</span>
                                        </div>
                                    </div>
                                    <div class="text-end" id="boxTotalRemisiBadge">
                                        <div class="text-white-50" style="font-size: 11px; text-transform: uppercase;">Total Proyeksi Remisi:</div>
                                        <div class="fw-bold fs-4 text-warning" id="resTotalRemisiText">0 Hari</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3 Cards Grid (1/3, 1/2 Asimilasi, 2/3 PB/CB) -->
                        <div id="rowFractionCards" class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm p-3 p-xl-3 text-start fraction-card-blue">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-primary px-2.5 py-1 text-white fw-bold" style="font-size: 11px; border-radius: 6px;">
                                            1/3 Masa Pidana
                                        </span>
                                        <i class="fas fa-book-reader text-primary opacity-75 fs-5"></i>
                                    </div>
                                    <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res13">-</h4>
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">Syarat dasar pembinaan</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm p-3 p-xl-3 text-start fraction-card-amber">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-warning px-2.5 py-1 text-dark fw-bold" style="font-size: 11px; border-radius: 6px;">
                                            1/2 Asimilasi
                                        </span>
                                        <i class="fas fa-hands-helping text-warning opacity-75 fs-5"></i>
                                    </div>
                                    <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res12">-</h4>
                                    <small class="text-muted d-block mt-1" id="subInfo12" style="font-size: 11px;">Mulai berhak Asimilasi Lapas</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm p-3 p-xl-3 text-start fraction-card-emerald">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-success px-2.5 py-1 text-white fw-bold" id="badgeLabel23" style="font-size: 11px; border-radius: 6px;">
                                            2/3 PB
                                        </span>
                                        <i class="fas fa-user-check text-success opacity-75 fs-5"></i>
                                    </div>
                                    <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res23">-</h4>
                                    <small class="text-muted d-block mt-1" id="subInfo23" style="font-size: 11px;">Syarat substantif waktu PB/CB</small>
                                </div>
                            </div>
                        </div>

                        <!-- Info Ringkasan Ekstra -->
                        <div class="mt-4 p-3 summary-info-box">
                            <div class="row g-2 text-muted">
                                <div class="col-sm-6">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i> <span id="labelInfoTglAwal">Tgl. Ditahan:</span> <strong class="telram-text-title" id="infoTglAwal">-</strong>
                                </div>
                                <div class="col-sm-6">
                                    <i class="fas fa-clock text-info me-1"></i> <span id="labelInfoTotalHari">Total Hari Vonis:</span> <strong class="telram-text-title" id="infoTotalHari">-</strong> hari
                                </div>
                            </div>
                        </div>

                        <!-- Section Tabel Proyeksi Remisi Keppres 174/1999 (Hanya untuk Mode Remisi) -->
                        <div id="sectionTableLedger" class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 telram-text-title">
                                    <i class="fas fa-list-ol text-warning me-2"></i>Tabel Proyeksi Remisi per Tahun (Keppres No. 174/1999)
                                </h6>
                                <span class="badge bg-light text-muted border" style="font-size: 11px;">Simulasi Otomatis</span>
                            </div>
                            <div class="table-responsive border rounded-3" style="border-color: #e2e8f0;">
                                <table class="table table-hover table-striped align-middle mb-0 table-ledger">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;" class="text-center">#</th>
                                            <th>Nama Event Remisi</th>
                                            <th>Tanggal Event</th>
                                            <th>Masa Dijalani</th>
                                            <th>Status</th>
                                            <th>Besaran Remisi</th>
                                            <th>Akumulasi</th>
                                            <th>Perkiraan Bebas Baru</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyProyeksiRemisi">
                                        <!-- Rendered dynamically via JS -->
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 11.5px;">
                                <i class="fas fa-info-circle me-1"></i> Catatan: Perhitungan remisi di atas merupakan estimasi reguler sesuai Keppres No. 174/1999 dengan asumsi narapidana berkelakuan baik, aktif mengikuti pembinaan (SPPN), dan tidak melanggar tata tertib (*Register F*).
                            </small>
                        </div>
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
    let lastResult = null;

    // Toast Alert Function
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

    // Switcher Mode: Simulasi Remisi vs Maju vs Mundur vs Sisa
    function setMode(mode) {
        currentMode = mode;
        const btnRemisi = document.getElementById('btnModeRemisi');
        const btnMaju = document.getElementById('btnModeMaju');
        const btnMundur = document.getElementById('btnModeMundur');
        const btnSisa = document.getElementById('btnModeSisa');
        const sectionInputVonis = document.getElementById('sectionInputVonis');
        const sectionAgama = document.getElementById('sectionAgama');
        const sectionRemisiAwal = document.getElementById('sectionRemisiAwal');
        const sectionInputEkspirasi = document.getElementById('sectionInputEkspirasi');
        const btnHitung = document.getElementById('btnHitungUtama');
        const labelTglAwal = document.getElementById('labelTglAwal');
        const placeholderTitle = document.getElementById('placeholderTitle');
        const placeholderDesc = document.getElementById('placeholderDesc');
        const placeholderIcon = document.getElementById('placeholderIcon');

        [btnRemisi, btnMaju, btnMundur, btnSisa].forEach(b => { if (b) b.classList.remove('active'); });

        if (mode === 'simulasi_remisi') {
            if (btnRemisi) btnRemisi.classList.add('active');
            sectionInputVonis.classList.remove('d-none');
            sectionAgama.classList.remove('d-none');
            sectionRemisiAwal.classList.remove('d-none');
            sectionInputEkspirasi.classList.add('d-none');
            labelTglAwal.innerHTML = 'Tgl. Mulai Penahanan <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-warning text-dark w-100 py-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-medal me-2"></i> HITUNG PROYEKSI REMISI & INTEGRASI';

            placeholderTitle.innerText = 'Hasil Proyeksi Remisi & Integrasi';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan, Lama Vonis, dan Agama WBP di sebelah kiri, kemudian klik tombol <strong>"HITUNG PROYEKSI REMISI & INTEGRASI"</strong> untuk melihat perkiraan tanggal ekspirasi baru, waktu pengusulan PB/CB (H-2 bulan), serta tabel rincian remisi berkala.';
            placeholderIcon.className = 'fas fa-medal';
        } else if (mode === 'maju') {
            if (btnMaju) btnMaju.classList.add('active');
            sectionInputVonis.classList.remove('d-none');
            sectionAgama.classList.add('d-none');
            sectionRemisiAwal.classList.add('d-none');
            sectionInputEkspirasi.classList.add('d-none');
            labelTglAwal.innerHTML = 'Tgl. Mulai Penahanan <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-primary w-100 py-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-calculator me-2"></i> HITUNG EKSPIRASI';

            placeholderTitle.innerText = 'Hasil Perhitungan Ekspirasi';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan dan Lama Vonis Hukuman di sebelah kiri, kemudian klik tombol <strong>"HITUNG EKSPIRASI"</strong> untuk melihat perkiraan tanggal ekspirasi dan tahapan masa pidana secara rinci.';
            placeholderIcon.className = 'fas fa-calendar-alt';
        } else if (mode === 'mundur') {
            if (btnMundur) btnMundur.classList.add('active');
            sectionInputVonis.classList.add('d-none');
            sectionAgama.classList.add('d-none');
            sectionRemisiAwal.classList.add('d-none');
            sectionInputEkspirasi.classList.remove('d-none');
            labelTglAwal.innerHTML = 'Tgl. Mulai Penahanan <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-secondary w-100 py-3 fw-bold shadow-sm';
            btnHitung.innerHTML = '<i class="fas fa-history me-2"></i> HITUNG LAMA PIDANA';

            placeholderTitle.innerText = 'Hasil Perhitungan Lama Pidana';
            placeholderDesc.innerHTML = 'Tentukan Tanggal Mulai Penahanan dan Tanggal Ekspirasi (Bebas Murni) di sebelah kiri, kemudian klik tombol <strong>"HITUNG LAMA PIDANA"</strong> untuk mencari durasi lama vonis hukuman secara otomatis.';
            placeholderIcon.className = 'fas fa-hourglass-half';
        } else if (mode === 'sisa') {
            if (btnSisa) btnSisa.classList.add('active');
            sectionInputVonis.classList.add('d-none');
            sectionAgama.classList.add('d-none');
            sectionRemisiAwal.classList.add('d-none');
            sectionInputEkspirasi.classList.remove('d-none');
            labelTglAwal.innerHTML = 'Per Tanggal (Tgl. Perhitungan) <span class="text-danger">*</span>';

            btnHitung.className = 'btn btn-info text-white w-100 py-3 fw-bold shadow-sm';
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

    // Reset Form
    function resetForm() {
        document.getElementById('tglAwal').value = '';
        document.getElementById('vTahun').value = '0';
        document.getElementById('vBulan').value = '0';
        document.getElementById('vHari').value = '0';
        document.getElementById('selAgama').value = 'islam';
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
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        document.getElementById('tglAwal').value = `${yyyy}-01-01`;

        if (currentMode === 'simulasi_remisi' || currentMode === 'maju') {
            document.getElementById('vTahun').value = '3';
            document.getElementById('vBulan').value = '0';
            document.getElementById('vHari').value = '0';
            document.getElementById('selAgama').value = 'islam';
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
            hasilHeaderTitle.innerText = `Hasil Proyeksi Remisi & Hak ${data.nama_program}`;

            // Hero Pengusulan
            document.getElementById('resTglMulaiPengusulan').innerText = data.formatted.tgl_mulai_pengusulan;
            document.getElementById('resTgl23Highlight').innerText = data.formatted.tgl_2_3_definitif;
            document.getElementById('pesanPengusulan').innerText = data.status_pengusulan.pesan;

            const badgeStatusPengusulan = document.getElementById('badgeStatusPengusulan');
            badgeStatusPengusulan.innerText = data.status_pengusulan.label;
            badgeStatusPengusulan.className = `badge ${data.status_pengusulan.badge_class}`;

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
            document.getElementById('subInfo23').innerText = `Jatuh Tempo (Murni: ${data.formatted.tgl_2_3_murni})`;

            // Render Ledger Tabel
            renderTableLedger(data.proyeksi_ledger);

            // Info ringkasan
            document.getElementById('labelInfoTglAwal').innerText = 'Tgl. Ditahan:';
            document.getElementById('infoTglAwal').innerText = data.formatted.tgl_awal;
            document.getElementById('labelInfoTotalHari').innerText = 'Total Vonis Pokok:';
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
    }

    // Render Tabel Proyeksi Remisi
    function renderTableLedger(ledger) {
        const tbody = document.getElementById('tbodyProyeksiRemisi');
        tbody.innerHTML = '';

        if (!ledger || ledger.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada jadwal event remisi sebelum tanggal ekspirasi.</td></tr>';
            return;
        }

        ledger.forEach((row, index) => {
            const tr = document.createElement('tr');
            const statusBadge = row.eligible
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>${row.tahap || 'Memenuhi Syarat'}</span>`
                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fas fa-clock me-1"></i>Belum 6 Bln</span>`;

            tr.innerHTML = `
                <td class="text-center fw-bold text-muted">${index + 1}</td>
                <td>
                    <strong>${row.nama_event}</strong>
                </td>
                <td>${row.tanggal_indo}</td>
                <td><small class="text-muted">${row.hari_dijalani} hari (${row.masa_dijalani_teks})</small></td>
                <td>${statusBadge}</td>
                <td class="fw-bold ${row.besaran_hari > 0 ? 'text-primary' : 'text-muted'}">${row.besaran_teks}</td>
                <td class="fw-bold text-success">${row.total_akumulasi_hari} Hari</td>
                <td class="fw-semibold text-danger">${row.perkiraan_ekspirasi_baru_indo}</td>
            `;
            tbody.appendChild(tr);
        });
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
        const text = `*LEMBAR ESTIMASI TELRAM & REMISI PEMASYARAKATAN*
*Sultan Banten - Kemenimipas Banten*
---------------------------------------
*Tanggal Ditahan:* ${d.formatted.tgl_awal}
*Lama Vonis:* ${d.lama_pidana_teks} (${d.total_hari_vonis} hari)
*Agama WBP:* ${d.agama}
*Program Integrasi:* ${d.nama_program}
---------------------------------------
*Tonggak Masa Pidana:*
• *1/3 Masa Pidana:* ${d.formatted.tgl_1_3_murni}
• *1/2 Asimilasi:* ${d.formatted.tgl_1_2_definitif} (Murni: ${d.formatted.tgl_1_2_murni})
• *2/3 ${d.jenis_program}:* ${d.formatted.tgl_2_3_definitif} (Murni: ${d.formatted.tgl_2_3_murni})
• *Ekspirasi Definitif:* ${d.formatted.ekspirasi_definitif} (Murni: ${d.formatted.ekspirasi_murni})
• *Total Proyeksi Remisi:* ${d.total_proyeksi_remisi_hari} Hari (${d.total_proyeksi_remisi_teks})
---------------------------------------
⚠️ *WAKTU MULAI PENGUSULAN KE SISTEM (H-2 BULAN):*
*Tanggal:* ${d.formatted.tgl_mulai_pengusulan}
*Status:* ${d.status_pengusulan.label}
*Catatan:* ${d.status_pengusulan.pesan}
---------------------------------------
_Catatan: Penghitungan ini merupakan simulasi reguler berdasarkan UU No. 22/2022 dan Keppres No. 174/1999._`;

        navigator.clipboard.writeText(text).then(() => {
            showToast('Ringkasan berhasil disalin ke clipboard!', 'success');
        }).catch(() => {
            showToast('Gagal menyalin teks ke clipboard.', 'error');
        });
    }

    // Inisialisasi saat load
    document.addEventListener('DOMContentLoaded', () => {
        setMode('simulasi_remisi');
    });
</script>
@endsection
