@extends('layouts.app')

@section('title', 'Kalkulator Telram - Sultan Banten')
@section('page_title', 'Kalkulator Telram')

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
        transition: background-color 0.2s ease;
    }
    [data-theme="dark"] .mode-switch-container {
        background: #0f172a !important;
        border: 1px solid #334155;
    }

    .btn-mode-tab {
        background: transparent;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        border: none;
        border-radius: 8px;
        padding: 9px 12px;
        flex: 1;
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
    [data-theme="dark"] .form-control {
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
        font-size: 18px;
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

    /* Card Header Border */
    [data-theme="dark"] .card-header {
        border-bottom-color: #334155 !important;
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
                        <h4 class="fw-bold mb-0 text-white">Kalkulator Perkiraan Ekspirasi</h4>
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
                        <button type="button" id="btnModeMaju" onclick="setMode('maju')" class="btn-mode-tab active">
                            <i class="fas fa-arrow-right me-1 text-primary"></i> Cari Ekspirasi
                        </button>
                        <button type="button" id="btnModeMundur" onclick="setMode('mundur')" class="btn-mode-tab">
                            <i class="fas fa-history me-1 text-secondary"></i> Cari Lama Pidana
                        </button>
                    </div>

                    <!-- Tanggal Mulai Penahanan -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label telram-text-title fw-bold mb-0" style="font-size: 13px;">
                                Tgl. Mulai Penahanan <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none" onclick="setTglAwalHariIni()" style="font-size: 11.5px;">
                                <i class="fas fa-calendar-day me-1"></i> Hari Ini
                            </button>
                        </div>
                        <input type="date" id="tglAwal" class="form-control form-control-lg fw-semibold" style="border-radius: 10px; font-size: 14.5px;">
                    </div>

                    <!-- Input Mode Maju: Lama Pidana Pokok -->
                    <div id="sectionInputVonis" class="mb-4">
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

                    <!-- Input Mode Mundur: Tanggal Ekspirasi Input -->
                    <div id="sectionInputEkspirasi" class="mb-4 d-none">
                        <label class="form-label telram-text-title fw-bold mb-1" style="font-size: 13px;">
                            Tgl. Ekspirasi (Bebas Murni) <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="tglEkspirasiInput" class="form-control form-control-lg fw-semibold" style="border-radius: 10px; font-size: 14.5px;">
                        <small class="text-muted d-block mt-1" style="font-size: 11.5px;">
                            <i class="fas fa-info-circle me-1"></i> Masukan Tanggal ekspirasi (Bebas Murni)
                        </small>
                    </div>

                    <!-- Tombol Hitung -->
                    <button type="button" id="btnHitungUtama" onclick="hitungSesuaiMode()" class="btn btn-primary w-100 py-3 fw-bold shadow" style="border-radius: 10px; font-size: 14.5px; letter-spacing: 0.5px;">
                        <i class="fas fa-calculator me-2"></i> HITUNG EKSPIRASI
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Results & Actions -->
        <div class="col-lg-7 col-xl-7">
            <!-- Empty Placeholder (When No Calculation Yet) -->
            <div id="placeholderSection" class="card border-0 shadow-sm text-center p-5 placeholder-card">
                <div class="py-4">
                    <div class="mb-3" style="width: 70px; height: 70px; border-radius: 50%; background: rgba(59, 130, 246, 0.1); display: inline-flex; align-items: center; justify-content: center; font-size: 30px; color: #3b82f6;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5 class="fw-bold telram-text-title mb-2">Hasil Perhitungan Ekspirasi</h5>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 420px; font-size: 13.5px;">
                        Tentukan Tanggal Mulai Penahanan dan Lama Vonis Hukuman di sebelah kiri, kemudian klik tombol <strong>"HITUNG EKSPIRASI"</strong> untuk melihat perkiraan tanggal ekspirasi dan tahapan masa pidana secara rinci.
                    </p>
                    <div class="d-inline-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill" onclick="demoSampleData()">
                            <i class="fas fa-magic me-1"></i> Coba Data Contoh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section (Initially Hidden) -->
            <div id="hasilSection" class="d-none">
                <div class="card border-0 shadow-sm mb-4 telram-card">
                    <div class="card-header bg-transparent border-bottom p-3 px-4">
                        <h6 class="fw-bold mb-0 telram-text-title">
                            <i class="fas fa-clipboard-check text-success me-2"></i>Hasil Perhitungan Ekspirasi
                        </h6>
                    </div>
                    <div class="card-body p-4">
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

                        <!-- Ekspirasi Bebas Murni Card (Dominant Highlight) -->
                        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white; border-radius: 12px; position: relative; overflow: hidden;">
                            <div class="card-body p-4 position-relative" style="z-index: 2;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-white text-danger px-2.5 py-1 mb-2 fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="fas fa-door-open me-1"></i> EKSPIRASI BEBAS MURNI
                                        </span>
                                        <h2 class="fw-black mb-0 text-white" id="resEkspirasi" style="font-weight: 800; font-size: 26px;">-</h2>
                                    </div>
                                    <div class="d-none d-sm-block text-white" style="font-size: 48px; opacity: 0.25;">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3 Cards Grid (1/3, 1/2, 2/3 Masa Pidana) -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm p-3 p-xl-4 text-center text-md-start fraction-card-blue">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary px-2.5 py-1 text-white fw-bold" style="font-size: 11.5px; border-radius: 6px;">
                                            1/3 Masa Pidana
                                        </span>
                                        <i class="fas fa-calendar-alt text-primary opacity-75 fs-5"></i>
                                    </div>
                                    <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res13">-</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm p-3 p-xl-4 text-center text-md-start fraction-card-amber">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-warning px-2.5 py-1 text-dark fw-bold" style="font-size: 11.5px; border-radius: 6px;">
                                            1/2 Masa Pidana
                                        </span>
                                        <i class="fas fa-calendar-alt text-warning opacity-75 fs-5"></i>
                                    </div>
                                    <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res12">-</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm p-3 p-xl-4 text-center text-md-start fraction-card-emerald">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-success px-2.5 py-1 text-white fw-bold" style="font-size: 11.5px; border-radius: 6px;">
                                            2/3 Masa Pidana
                                        </span>
                                        <i class="fas fa-calendar-alt text-success opacity-75 fs-5"></i>
                                    </div>
                                    <h4 class="fw-bold fraction-date-val mt-2 mb-0" id="res23">-</h4>
                                </div>
                            </div>
                        </div>

                        <!-- Info Ringkasan Ekstra -->
                        <div class="mt-4 p-3 summary-info-box">
                            <div class="row g-2 text-muted">
                                <div class="col-sm-6">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i> Tgl. Ditahan: <strong class="telram-text-title" id="infoTglAwal">-</strong>
                                </div>
                                <div class="col-sm-6">
                                    <i class="fas fa-clock text-info me-1"></i> Total Hari Vonis: <strong class="telram-text-title" id="infoTotalHari">-</strong> hari
                                </div>
                            </div>
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
    let currentMode = 'maju';
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

    // Switcher Mode: Maju vs Mundur
    function setMode(mode) {
        currentMode = mode;
        const btnMaju = document.getElementById('btnModeMaju');
        const btnMundur = document.getElementById('btnModeMundur');
        const sectionInputVonis = document.getElementById('sectionInputVonis');
        const sectionInputEkspirasi = document.getElementById('sectionInputEkspirasi');
        const btnHitung = document.getElementById('btnHitungUtama');
        const boxHasilPidana = document.getElementById('boxHasilPidana');

        if (mode === 'maju') {
            btnMaju.classList.add('active');
            btnMundur.classList.remove('active');

            sectionInputVonis.classList.remove('d-none');
            sectionInputEkspirasi.classList.add('d-none');
            boxHasilPidana.classList.add('d-none');

            btnHitung.innerHTML = '<i class="fas fa-calculator me-2"></i> HITUNG EKSPIRASI';
        } else {
            btnMundur.classList.add('active');
            btnMaju.classList.remove('active');

            sectionInputVonis.classList.add('d-none');
            sectionInputEkspirasi.classList.remove('d-none');
            boxHasilPidana.classList.remove('d-none');

            btnHitung.innerHTML = '<i class="fas fa-history me-2"></i> HITUNG LAMA PIDANA';
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
        document.getElementById('tglEkspirasiInput').value = '';

        document.getElementById('hasilSection').classList.add('d-none');
        document.getElementById('placeholderSection').classList.remove('d-none');
        lastResult = null;
        showToast('Form berhasil di-reset', 'info');
    }

    // Demo Data Contoh
    function demoSampleData() {
        setMode('maju');
        
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        document.getElementById('tglAwal').value = `${yyyy}-${mm}-${dd}`;
        
        document.getElementById('vTahun').value = '2';
        document.getElementById('vBulan').value = '6';
        document.getElementById('vHari').value = '0';

        hitungSesuaiMode();
    }

    // Controller Utama
    function hitungSesuaiMode() {
        if (currentMode === 'maju') {
            hitungTelramMaju();
        } else {
            hitungTelramMundur();
        }
    }

    // 1. Hitung Mode Maju (Cari Ekspirasi)
    function hitungTelramMaju() {
        const inputTgl = document.getElementById('tglAwal').value;
        const vThn = parseInt(document.getElementById('vTahun').value) || 0;
        const vBln = parseInt(document.getElementById('vBulan').value) || 0;
        const vHri = parseInt(document.getElementById('vHari').value) || 0;

        if (!inputTgl) {
            showToast('Harap tentukan Tanggal Mulai Penahanan!', 'error');
            document.getElementById('tglAwal').focus();
            return;
        }

        if (vThn === 0 && vBln === 0 && vHri === 0) {
            showToast('Harap tentukan Lama Pidana Vonis (Tahun, Bulan, atau Hari)!', 'error');
            document.getElementById('vTahun').focus();
            return;
        }

        // Tgl Awal
        const [y, m, d] = inputTgl.split('-').map(Number);
        const tglAwal = new Date(y, m - 1, d);

        // Rumus Perhitungan Ekspirasi:
        // 1. Dimulai dari tahun: jika ada tahun, tambah tahun di tanggal & bulan yang sama
        // 2. Kemudian geser bulan dan hari: 1 bulan = 30 hari kalender aktual
        let tglEkspirasi = new Date(tglAwal);

        if (vThn > 0) {
            tglEkspirasi = addYearsCalendar(tglEkspirasi, vThn);
        }

        const sisaHari = (vBln * 30) + vHri;
        if (sisaHari > 0) {
            tglEkspirasi.setDate(tglEkspirasi.getDate() + sisaHari);
        }

        // Total hari baku: 1 Tahun = 360 Hari, 1 Bulan = 30 Hari
        const totalHariVonis = (vThn * 360) + (vBln * 30) + vHri;
        const hari13 = Math.floor(totalHariVonis / 3);
        const hari12 = Math.floor(totalHariVonis / 2);
        const hari23 = Math.floor((totalHariVonis * 2) / 3);

        const tgl13 = addDays(tglAwal, hari13);
        const tgl12 = addDays(tglAwal, hari12);
        const tgl23 = addDays(tglAwal, hari23);

        let parts = [];
        if (vThn > 0) parts.push(`${vThn} Tahun`);
        if (vBln > 0) parts.push(`${vBln} Bulan`);
        if (vHri > 0) parts.push(`${vHri} Hari`);
        const lamaPidanaTeks = parts.join(', ') || '0 Hari';

        renderHasil({
            mode: 'maju',
            tglAwal: tglAwal,
            tglEkspirasi: tglEkspirasi,
            tgl13: tgl13,
            tgl12: tgl12,
            tgl23: tgl23,
            vThn: vThn,
            vBln: vBln,
            vHri: vHri,
            totalHariVonis: totalHariVonis,
            lamaPidanaTeks: lamaPidanaTeks
        });
    }

    // 2. Hitung Mode Mundur (Cari Lama Pidana)
    function hitungTelramMundur() {
        const inputTglAwal = document.getElementById('tglAwal').value;
        const inputTglEkspirasi = document.getElementById('tglEkspirasiInput').value;

        if (!inputTglAwal || !inputTglEkspirasi) {
            showToast('Harap tentukan Tanggal Penahanan dan Tanggal Ekspirasi!', 'error');
            return;
        }

        const [y1, m1, d1] = inputTglAwal.split('-').map(Number);
        const tglAwal = new Date(y1, m1 - 1, d1);

        const [y2, m2, d2] = inputTglEkspirasi.split('-').map(Number);
        const tglEkspirasi = new Date(y2, m2 - 1, d2);

        if (tglEkspirasi < tglAwal) {
            showToast('Tgl Ekspirasi tidak boleh mendahului Tgl Penahanan!', 'error');
            return;
        }

        // 1. Tahun: Cari berapa tahun penuh yang bisa ditambahkan dari tglAwal di tanggal & bulan yang sama
        let vThn = 0;
        while (addYearsCalendar(tglAwal, vThn + 1) <= tglEkspirasi) {
            vThn++;
        }
        let curr = addYearsCalendar(tglAwal, vThn);

        // 2. Sisa Hari dikonversi ke bulan (1 bulan = 30 hari) dan hari
        const diffMs = tglEkspirasi.getTime() - curr.getTime();
        const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
        const vBln = Math.floor(diffDays / 30);
        const vHri = diffDays % 30;

        let parts = [];
        if (vThn > 0) parts.push(`${vThn} Tahun`);
        if (vBln > 0) parts.push(`${vBln} Bulan`);
        if (vHri > 0) parts.push(`${vHri} Hari`);
        const lamaPidanaTeks = parts.join(', ') || '0 Hari';

        const totalHariVonis = (vThn * 360) + (vBln * 30) + vHri;
        const hari13 = Math.floor(totalHariVonis / 3);
        const hari12 = Math.floor(totalHariVonis / 2);
        const hari23 = Math.floor((totalHariVonis * 2) / 3);

        const tgl13 = addDays(tglAwal, hari13);
        const tgl12 = addDays(tglAwal, hari12);
        const tgl23 = addDays(tglAwal, hari23);

        renderHasil({
            mode: 'mundur',
            tglAwal: tglAwal,
            tglEkspirasi: tglEkspirasi,
            tgl13: tgl13,
            tgl12: tgl12,
            tgl23: tgl23,
            vThn: vThn,
            vBln: vBln,
            vHri: vHri,
            totalHariVonis: totalHariVonis,
            lamaPidanaTeks: lamaPidanaTeks
        });
    }

    // Helper Penambahan Hari
    function addDays(d, days) {
        const res = new Date(d);
        res.setDate(res.getDate() + days);
        return res;
    }

    // Helper tambah bulan kalender tanpa day-overflow
    function addMonthsCalendar(baseDate, months) {
        const d = new Date(baseDate);
        const originalDay = d.getDate();
        d.setDate(1);
        d.setMonth(d.getMonth() + months);
        const maxDays = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        d.setDate(Math.min(originalDay, maxDays));
        return d;
    }

    // Helper tambah tahun kalender tanpa day-overflow
    function addYearsCalendar(baseDate, years) {
        const d = new Date(baseDate);
        const originalDay = d.getDate();
        const originalMonth = d.getMonth();
        d.setFullYear(d.getFullYear() + years);
        if (d.getMonth() !== originalMonth) {
            d.setDate(0);
        }
        return d;
    }

    // Format Tanggal Indonesia Baku
    function formatDateIndo(d) {
        const bln = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const day = String(d.getDate()).padStart(2, '0');
        const month = bln[d.getMonth()];
        const year = d.getFullYear();
        return `${day} ${month} ${year}`;
    }

    // Render Data ke Tampilan UI
    function renderHasil(res) {
        lastResult = res;

        // Update Hasil Cards
        document.getElementById('resEkspirasi').innerText = formatDateIndo(res.tglEkspirasi);
        document.getElementById('res13').innerText = formatDateIndo(res.tgl13);
        document.getElementById('res12').innerText = formatDateIndo(res.tgl12);
        document.getElementById('res23').innerText = formatDateIndo(res.tgl23);

        if (res.mode === 'mundur') {
            document.getElementById('boxHasilPidana').classList.remove('d-none');
            document.getElementById('resLamaPidana').innerText = res.lamaPidanaTeks;
        } else {
            document.getElementById('boxHasilPidana').classList.add('d-none');
        }

        // Extra Info
        document.getElementById('infoTglAwal').innerText = formatDateIndo(res.tglAwal);
        document.getElementById('infoTotalHari').innerText = res.totalHariVonis.toLocaleString('id-ID');

        // Tampilkan Hasil
        document.getElementById('placeholderSection').classList.add('d-none');
        document.getElementById('hasilSection').classList.remove('d-none');

        showToast(res.mode === 'maju' ? 'Kalkulasi Ekspirasi Selesai!' : 'Lama Pidana Berhasil Dihitung!', 'success');
    }
</script>
@endsection
