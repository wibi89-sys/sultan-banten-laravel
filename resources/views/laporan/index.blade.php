@extends('layouts.app')

@section('title', $reportTitles[$reportType] . ' - Sultan Banten')
@section('page_title')
    <i class="fas fa-print me-2"></i>{{ $reportTitles[$reportType] }}
@endsection

@push('styles')
<style>
    .report-type-card {
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 18px 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
        background: var(--card-bg, #fff);
        text-decoration: none;
        display: block;
        color: var(--text-primary, #1e293b);
    }
    .report-type-card:hover {
        transform: translateY(-3px);
        border-color: #3b82f6;
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.15);
        color: #2563eb;
    }
    .report-type-card.active {
        border-color: #2563eb;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white !important;
        box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
    }
    .report-type-card.active i, .report-type-card.active h6 {
        color: white !important;
    }
    .report-type-card i {
        font-size: 32px;
        color: #3b82f6;
        margin-bottom: 8px;
    }
</style>
@endpush

@section('content')
<div class="content-card">
    <h6 class="mb-3 fw-bold"><i class="fas fa-layer-group text-primary me-2"></i>Pilih Jenis Laporan</h6>
    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <a href="{{ route('laporan.index', ['type' => 'penghuni']) }}" class="report-type-card {{ $reportType == 'penghuni' ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <h6 class="mb-0 fs-6">Penghuni</h6>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laporan.index', ['type' => 'overstaying']) }}" class="report-type-card {{ $reportType == 'overstaying' ? 'active' : '' }}">
                <i class="fas fa-clock"></i>
                <h6 class="mb-0 fs-6">Overstaying</h6>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laporan.index', ['type' => 'integrasi']) }}" class="report-type-card {{ $reportType == 'integrasi' ? 'active' : '' }}">
                <i class="fas fa-exchange-alt"></i>
                <h6 class="mb-0 fs-6">Integrasi</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('laporan.index', ['type' => 'bebas_harian']) }}" class="report-type-card {{ $reportType == 'bebas_harian' ? 'active' : '' }}">
                <i class="fas fa-door-open"></i>
                <h6 class="mb-0 fs-6">Bebas Harian</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('laporan.index', ['type' => 'rekapitulasi']) }}" class="report-type-card {{ $reportType == 'rekapitulasi' ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <h6 class="mb-0 fs-6">Rekapitulasi</h6>
            </a>
        </div>
    </div>
    
    <hr class="my-4">
    
    <!-- Filter Form -->
    <form id="formLaporan" method="GET" action="{{ route('laporan.excel') }}" target="_blank">
        <input type="hidden" name="type" value="{{ $reportType }}">
        
        <div class="row g-3">
            @if(!$userUptFilter)
            <div class="col-md-4">
                <label for="upt_id" class="form-label fw-semibold small">Pilih UPT</label>
                <select name="upt_id" id="upt_id" class="form-select" aria-label="Pilih UPT">
                    <option value="">Semua UPT</option>
                    @foreach ($uptList as $upt)
                    <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="upt_id" value="{{ $userUptFilter }}">
            <div class="col-md-4">
                <label for="upt_readonly" class="form-label fw-semibold small">UPT</label>
                <input type="text" id="upt_readonly" class="form-control" value="{{ auth()->user()->upt?->nama_upt ?? '-' }}" aria-label="UPT" readonly>
            </div>
            @endif
            
            <div class="col-md-4">
                <label for="tanggal_mulai" class="form-label fw-semibold small">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="{{ $defaultStartDate }}" aria-label="Tanggal Mulai" required>
            </div>
            
            <div class="col-md-4">
                <label for="tanggal_akhir" class="form-label fw-semibold small">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $defaultEndDate }}" aria-label="Tanggal Akhir" required>
            </div>
        </div>
        
        <!-- Pengaturan Format Dokumen & Penandatangan (PDF) -->
        <div class="card border-0 shadow-sm mt-4" style="background: var(--card-bg, #fff); border-radius: 12px; border: 1px solid var(--border-color, #e2e8f0);">
            <div class="card-header bg-transparent py-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-sliders-h me-2"></i>Pengaturan Dokumen Laporan (PDF)
                        </h6>
                        <small class="text-muted">Atur keberadaan Tanda Tangan, QR Code, dan Stempel resmi pada cetakan laporan</small>
                    </div>
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-file-pdf text-danger me-1"></i> Opsi Cetak PDF
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- 3 Toggle Switches Utama -->
                <div class="row g-3 p-3 rounded mb-3" style="background-color: var(--bs-tertiary-bg, #f8fafc); border: 1px dashed var(--border-color, #cbd5e1); border-radius: 10px;">
                    <!-- 1. Tanda Tangan -->
                    <div class="col-md-4">
                        <div class="p-2 h-100">
                            <div class="form-check form-switch mb-1">
                                <input type="hidden" name="show_ttd" value="0">
                                <input class="form-check-input" type="checkbox" name="show_ttd" id="show_ttd" value="1" checked onchange="toggleSignatureOptions()">
                                <label class="form-check-label fw-semibold small" for="show_ttd">
                                    <i class="fas fa-signature text-primary me-1"></i> Sertakan Tanda Tangan
                                </label>
                            </div>
                            <small class="text-muted d-block ms-4" style="font-size: 11px;">
                                Tampilkan blok tanda tangan di bagian kanan bawah laporan
                            </small>
                        </div>
                    </div>

                    <!-- 2. Stempel Laporan -->
                    <div class="col-md-4">
                        <div class="p-2 h-100">
                            <div class="form-check form-switch mb-1">
                                <input type="hidden" name="show_stempel" value="0">
                                <input class="form-check-input" type="checkbox" name="show_stempel" id="show_stempel" value="1" checked>
                                <label class="form-check-label fw-semibold small" for="show_stempel">
                                    <i class="fas fa-stamp text-danger me-1"></i> Sertakan Stempel Laporan
                                </label>
                            </div>
                            <div class="ms-4">
                                <small class="text-muted d-block" style="font-size: 11px;">
                                    Tempelkan cap dinas resmi instansi pada area tanda tangan
                                </small>
                                @if(!empty($instansi?->stempel_image) && file_exists(public_path($instansi->stempel_image)))
                                    <span class="badge bg-success-subtle text-success mt-1" style="font-size: 10px;"><i class="fas fa-check-circle me-1"></i>Aset stempel aktif</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning mt-1" style="font-size: 10px;"><i class="fas fa-info-circle me-1"></i>Belum ada file stempel</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 3. QR Code Validasi -->
                    <div class="col-md-4">
                        <div class="p-2 h-100">
                            <div class="form-check form-switch mb-1">
                                <input type="hidden" name="show_qrcode" value="0">
                                <input class="form-check-input" type="checkbox" name="show_qrcode" id="show_qrcode" value="1" checked>
                                <label class="form-check-label fw-semibold small" for="show_qrcode">
                                    <i class="fas fa-qrcode text-dark me-1"></i> Sertakan QR Code
                                </label>
                            </div>
                            <small class="text-muted d-block ms-4" style="font-size: 11px;">
                                Cetak barcode validasi dokumen pada footer bawah laporan
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Detail Penanda Tangan -->
                <div id="signatureDetailsContainer" class="p-3 border rounded" style="background: var(--card-bg, #fff);">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="tempat_ttd" class="form-label fw-semibold small">Tempat Penandatanganan</label>
                            <input type="text" name="tempat_ttd" id="tempat_ttd" class="form-control form-control-sm" placeholder="Contoh: Serang" value="Serang" aria-label="Tempat Penandatanganan">
                        </div>
                        <div class="col-md-4">
                            <label for="jabatan_ttd" class="form-label fw-semibold small">Jabatan Penanda Tangan</label>
                            <input type="text" name="jabatan_ttd" id="jabatan_ttd" class="form-control form-control-sm" placeholder="Contoh: Kepala Bidang Pelayanan dan Pembinaan" value="Kepala Bidang Pelayanan dan Pembinaan" aria-label="Jabatan Penanda Tangan">
                        </div>
                        <div class="col-md-3">
                            <label for="nama_ttd" class="form-label fw-semibold small">Nama Penanda Tangan</label>
                            <input type="text" name="nama_ttd" id="nama_ttd" class="form-control form-control-sm" placeholder="Contoh: Surya Dharma" value="Surya Dharma" aria-label="Nama Penanda Tangan">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small d-block">Gambar TTD Digital</label>
                            <div class="form-check form-switch pt-1">
                                <input type="hidden" name="show_ttd_image" value="0">
                                <input class="form-check-input" type="checkbox" name="show_ttd_image" id="show_ttd_image" value="1" checked>
                                <label class="form-check-label small" for="show_ttd_image" title="Jika dinonaktifkan, sediakan ruang tanda tangan basah">Aktif</label>
                            </div>
                            @if(!empty($instansi?->ttd_image) && file_exists(public_path($instansi->ttd_image)))
                                <span class="badge bg-success-subtle text-success" style="font-size: 10px;"><i class="fas fa-check-circle me-1"></i>Aset TTD aktif</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning" style="font-size: 10px;"><i class="fas fa-info-circle me-1"></i>Belum ada file TTD</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()" aria-label="Reset Filter">
                <i class="fas fa-redo me-1"></i> Reset Filter
            </button>
            <button type="button" class="btn btn-danger px-4" onclick="submitPdf()" aria-label="Cetak Dokumen PDF">
                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
            </button>
            <button type="submit" class="btn btn-success px-4" onclick="document.getElementById('formLaporan').action='{{ route('laporan.excel') }}';" aria-label="Download Laporan Excel">
                <i class="fas fa-file-excel me-1"></i> Download Excel
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function toggleSignatureOptions() {
        const isTtdChecked = document.getElementById('show_ttd').checked;
        const container = document.getElementById('signatureDetailsContainer');
        if (container) {
            container.style.opacity = isTtdChecked ? '1' : '0.4';
            container.style.pointerEvents = isTtdChecked ? 'auto' : 'none';
            const inputs = container.querySelectorAll('input');
            inputs.forEach(input => {
                input.disabled = !isTtdChecked;
            });
        }
    }

    function submitPdf() {
        const form = document.getElementById('formLaporan');
        form.action = "{{ route('laporan.pdf') }}";
        form.submit();
    }

    function resetForm() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        document.getElementById('tanggal_mulai').value = firstDay.toISOString().split('T')[0];
        document.getElementById('tanggal_akhir').value = lastDay.toISOString().split('T')[0];
        const uptSelect = document.getElementById('upt_id');
        if (uptSelect) uptSelect.value = '';
        
        document.getElementById('show_ttd').checked = true;
        document.getElementById('show_stempel').checked = true;
        document.getElementById('show_qrcode').checked = true;
        document.getElementById('show_ttd_image').checked = true;
        toggleSignatureOptions();

        if (document.getElementById('jabatan_ttd')) {
            document.getElementById('tempat_ttd').value = 'Serang';
            document.getElementById('jabatan_ttd').value = 'Kepala Bidang Pelayanan dan Pembinaan';
            document.getElementById('nama_ttd').value = 'Surya Dharma';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleSignatureOptions();
    });
</script>
@endpush
