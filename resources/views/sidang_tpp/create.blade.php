@extends('layouts.app')

@section('title', 'Jadwalkan Sidang TPP Baru - Sultan Banten')
@section('page_title')
    <i class="fas fa-plus-circle me-2 text-primary"></i>Pendaftaran Sidang TPP Baru
@endsection

@push('styles')
<style>
    .step-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: var(--card-bg, #ffffff);
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .step-card-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: white;
        padding: 12px 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .step-badge {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
    }
    .excel-dropzone {
        border: 2px dashed #93c5fd;
        border-radius: 10px;
        background-color: #f8fafc;
        padding: 24px;
        text-align: center;
        transition: all 0.2s ease;
    }
    .excel-dropzone:hover {
        background-color: #eff6ff;
        border-color: #3b82f6;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('sidang-tpp.index') }}" class="text-decoration-none">Sidang TPP</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pendaftaran Sidang Baru</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">Formulir Pendaftaran Sidang TPP &amp; Usulan Warga Binaan</h4>
        </div>
        <a href="{{ route('sidang-tpp.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Kembali ke Daftar
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Perhatian! Terdapat kesalahan pengisian:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <form action="{{ route('sidang-tpp.store') }}" method="POST" enctype="multipart/form-data" id="formSidangTpp">
        @csrf
        <input type="hidden" name="wbp_json" id="wbp_json" value="{{ old('wbp_json', '') }}">

        <!-- LANGKAH 1: Data Undangan ke Kantor Wilayah -->
        <div class="step-card">
            <div class="step-card-header">
                <span class="step-badge">1</span>
                <span>Penyampaian Surat Undangan Ke Kantor Wilayah Banten</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="upt_id" class="form-label small fw-semibold">Unit Pelaksana Teknis (UPT) <span class="text-danger">*</span></label>
                        <select name="upt_id" id="upt_id" class="form-select" {{ $userUptFilter ? 'readonly' : 'required' }}>
                            @if(!$userUptFilter)
                            <option value="">-- Pilih UPT Pelaksana --</option>
                            @endif
                            @foreach($uptList as $upt)
                                @if(!$userUptFilter || $userUptFilter == $upt->id)
                                <option value="{{ $upt->id }}" {{ (old('upt_id', $userUptFilter) == $upt->id) ? 'selected' : '' }}>
                                    {{ $upt->nama_upt }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="nomor_undangan" class="form-label small fw-semibold">Nomor Surat Undangan Ke Kanwil <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_undangan" id="nomor_undangan" class="form-control" placeholder="Contoh: W12.PAS.PAS.1-PK.02.02-1240" value="{{ old('nomor_undangan') }}" required>
                        <small class="text-muted" style="font-size: 11px;">Surat permohonan pendampingan/pengawasan yang ditujukan kepada Kepala Divisi Pemasyarakatan</small>
                    </div>

                    <div class="col-md-3">
                        <label for="tanggal_surat" class="form-label small fw-semibold">Tanggal Surat Undangan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_surat" id="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="tanggal_sidang" class="form-label small fw-semibold">Tanggal Pelaksanaan Sidang <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_sidang" id="tanggal_sidang" class="form-control" value="{{ old('tanggal_sidang', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="jam_mulai" class="form-label small fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                        <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" value="{{ old('jam_mulai', '09:00') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="jam_selesai" class="form-label small fw-semibold">Jam Selesai (Estimasi)</label>
                        <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" value="{{ old('jam_selesai', '12:00') }}">
                    </div>

                    <div class="col-md-12">
                        <label for="agenda_sidang" class="form-label small fw-semibold">Agenda Sidang <span class="text-danger">*</span></label>
                        <input type="text" name="agenda_sidang" id="agenda_sidang" class="form-control" placeholder="Tuliskan agenda sidang secara manual (Contoh: Sidang TPP Usulan Hak Integrasi PB dan CB Tahap IV Tahun 2026)" value="{{ old('agenda_sidang') }}" required>
                        <small class="text-muted" style="font-size: 11px;">Tuliskan agenda sidang TPP secara manual dan spesifik sesuai isi surat undangan UPT.</small>
                    </div>

                    <div class="col-md-12">
                        <label for="file_undangan" class="form-label small fw-semibold">Unggah Berkas Surat Undangan (PDF)</label>
                        <input type="file" name="file_undangan" id="file_undangan" class="form-control" accept=".pdf">
                        <small class="text-muted" style="font-size: 11px;">Maksimal 10 MB, format PDF scan bertanda tangan</small>
                    </div>

                    <div class="col-md-6">
                        <label for="ketua_tpp" class="form-label small fw-semibold">Ketua Tim TPP UPT <span class="text-danger">*</span></label>
                        <input type="text" name="ketua_tpp" id="ketua_tpp" class="form-control" placeholder="Nama Ketua TPP (tanpa gelar)" value="{{ old('ketua_tpp') }}" required>
                        <small class="text-muted" style="font-size: 11px;">Tulis nama tanpa gelar</small>
                    </div>
                    <div class="col-md-6">
                        <label for="sekretaris_tpp" class="form-label small fw-semibold">Sekretaris Tim TPP UPT <span class="text-danger">*</span></label>
                        <input type="text" name="sekretaris_tpp" id="sekretaris_tpp" class="form-control" placeholder="Nama Sekretaris TPP (tanpa gelar)" value="{{ old('sekretaris_tpp') }}" required>
                        <small class="text-muted" style="font-size: 11px;">Tulis nama tanpa gelar</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- LANGKAH 2: Fasilitasi Sidang Daring (Pengawasan Kanwil) -->
        <div class="step-card">
            <div class="step-card-header bg-gradient" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                <span class="step-badge">2</span>
                <span>Fasilitasi Link TPP Daring (Bintorwasdal Kanwil)</span>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                    <i class="fas fa-info-circle fa-lg me-2"></i>
                    <div>
                        Sesuai <b>SE No: PAS-38.OT.02.02 Tahun 2026</b>, Kantor Wilayah melakukan pengawasan langsung maupun virtual. Harap cantumkan tautan ruang sidang virtual (Zoom / Google Meet) agar Tim Kanwil dapat masuk memantau jalannya sidang.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="metode_sidang" class="form-label small fw-semibold">Metode Pelaksanaan Sidang <span class="text-danger">*</span></label>
                        <select name="metode_sidang" id="metode_sidang" class="form-select" required>
                            <option value="hybrid" {{ old('metode_sidang', 'hybrid') == 'hybrid' ? 'selected' : '' }}>Hybrid (Tatap Muka &amp; Daring Zoom)</option>
                            <option value="daring" {{ old('metode_sidang') == 'daring' ? 'selected' : '' }}>Daring Penuh (Virtual)</option>
                            <option value="tatap_muka" {{ old('metode_sidang') == 'tatap_muka' ? 'selected' : '' }}>Tatap Muka Penuh di UPT</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label for="link_daring" class="form-label small fw-semibold">Tautan / Link TPP Daring (Zoom / Google Meet / Teams)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-video text-primary"></i></span>
                            <input type="url" name="link_daring" id="link_daring" class="form-control" placeholder="https://zoom.us/j/... atau https://meet.google.com/..." value="{{ old('link_daring') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="meeting_id" class="form-label small fw-semibold">Meeting ID (Opsional)</label>
                        <input type="text" name="meeting_id" id="meeting_id" class="form-control" placeholder="Contoh: 824 1234 5678" value="{{ old('meeting_id') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="passcode" class="form-label small fw-semibold">Passcode / Kata Sandi (Opsional)</label>
                        <input type="text" name="passcode" id="passcode" class="form-control" placeholder="Contoh: TPP2026" value="{{ old('passcode') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- LANGKAH 3: Peserta Sidang TPP (Bisa Input Manual atau Import Excel) -->
        <div class="step-card">
            <div class="step-card-header bg-gradient" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                <span class="step-badge">3</span>
                <span>Daftar Peserta Sidang TPP (Bisa Input Manual atau Import Excel)</span>
            </div>
            <div class="card-body p-4">
                <!-- Dual Action Bar: Import Excel vs Tambah Manual -->
                <div class="row g-3 mb-4">
                    <!-- Opsi 1: Import Excel -->
                    <div class="col-lg-6">
                        <div class="p-3 border rounded bg-white shadow-sm h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="fas fa-file-excel text-success me-1"></i>Opsi 1: Import Berkas Excel / CSV
                                    </h6>
                                    <a href="{{ route('sidang-tpp.download-template') }}" class="btn btn-outline-success btn-sm px-2.5 py-1 shadow-sm fw-semibold" id="btnDownloadTemplateForm">
                                        <i class="fas fa-download me-1"></i>Unduh Template Excel
                                    </a>
                                </div>
                                <p class="small text-muted mb-3">Pilih berkas Excel (.xlsx / .csv) sesuai template resmi untuk mengisi daftar Warga Binaan secara otomatis.</p>
                            </div>
                            <div>
                                <div class="mb-2">
                                    <label for="file_excel" class="form-label small fw-semibold text-secondary mb-1">
                                        <i class="fas fa-paperclip me-1"></i>Pilih Berkas Excel / CSV:
                                    </label>
                                    <input type="file" name="file_excel" id="file_excel" class="form-control form-control-sm" accept=".xlsx,.xls,.csv">
                                </div>
                                <button type="button" class="btn btn-primary btn-sm fw-bold w-100 py-2 shadow-sm d-flex align-items-center justify-content-center" id="btnPratinjauExcel">
                                    <i class="fas fa-search me-1.5"></i>Pratinjau &amp; Muat Data Excel
                                </button>
                                <div id="excelLoading" class="mt-2 text-primary small fw-semibold d-none text-center">
                                    <div class="spinner-border spinner-border-sm me-1" role="status"></div> Membaca &amp; memproses berkas Excel...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi 2: Input Manual -->
                    <div class="col-lg-6">
                        <div class="p-3 border rounded bg-white shadow-sm h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-2 pb-2 border-bottom">
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="fas fa-user-plus text-primary me-1"></i>Opsi 2: Input Manual Peserta Sidang
                                    </h6>
                                </div>
                                <p class="small text-muted mb-3">Tambahkan data Warga Binaan peserta sidang satu per satu secara langsung ke dalam formulir pendaftaran.</p>
                            </div>
                            <div>
                                <div class="mb-2 small text-muted">
                                    <i class="fas fa-info-circle me-1 text-info"></i>Gunakan opsi ini jika data usulan ditambahkan secara langsung tanpa file Excel.
                                </div>
                                <button type="button" class="btn btn-success btn-sm fw-bold w-100 py-2 shadow-sm d-flex align-items-center justify-content-center" onclick="bukaModalTambahPeserta()">
                                    <i class="fas fa-user-plus me-1.5"></i>Tambah Peserta Sidang Manual
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Table Container: Menampung Peserta dari Excel maupun Manual -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold text-dark">
                        <i class="fas fa-users text-primary me-1"></i>Daftar Peserta Sidang TPP: <span id="totalWbpCount" class="badge bg-primary fs-6">0</span> Orang
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearPreview">
                            <i class="fas fa-trash-alt me-1"></i>Kosongkan Daftar
                        </button>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 440px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover align-middle mb-0" id="tablePreviewWbp" style="font-size: 12px;">
                        <thead class="table-dark sticky-top small">
                            <tr>
                                <th style="width: 35px;" class="text-center">No</th>
                                <th>Nama Warga Binaan</th>
                                <th>No. Register</th>
                                <th>Perkara / Pasal</th>
                                <th>Pidana</th>
                                <th class="text-center">Tgl 2/3</th>
                                <th class="text-center">Jenis Usulan</th>
                                <th class="text-center" style="width: 170px;">Menarik Perhatian</th>
                                <th>Catatan / Keterangan</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="previewTbody">
                            <tr id="emptyRowPlaceholder">
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-user-friends fa-2x mb-2 text-secondary opacity-50"></i>
                                    <div>Belum ada peserta usulan yang didaftarkan.</div>
                                    <small class="text-muted">Gunakan tombol <b>"Import Berkas Excel"</b> di atas atau klik <b>"Tambah Peserta Sidang"</b>.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Footer -->
        <div class="card border-0 shadow-sm p-3 mb-5">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('sidang-tpp.index') }}" class="btn btn-light border btn-sm px-3">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
                <button type="submit" class="btn btn-primary btn-lg px-4 shadow" id="btnSubmitSidang">
                    <i class="fas fa-save me-2"></i>Simpan &amp; Daftarkan Sidang TPP
                </button>
            </div>
        </div>
    </form>
</div>

<!-- MODAL: Tambah / Edit Peserta Sidang -->
<div class="modal fade" id="modalTambahPesertaManual" tabindex="-1" aria-labelledby="modalTambahPesertaTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="modalTambahPesertaTitle">
                    <i class="fas fa-user-plus me-2"></i>Tambah Peserta Sidang TPP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formManualPeserta" onsubmit="event.preventDefault(); simpanPesertaManual();">
                    <input type="hidden" id="m_edit_index" value="-1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="m_nama_wbp" class="form-label small fw-semibold">Nama Lengkap Warga Binaan <span class="text-danger">*</span></label>
                            <input type="text" id="m_nama_wbp" class="form-control form-control-sm" placeholder="Contoh: Ahmad Sulaeman bin Dahlan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="m_nomor_register" class="form-label small fw-semibold">Nomor Register Lapas/Rutan <span class="text-danger">*</span></label>
                            <input type="text" id="m_nomor_register" class="form-control form-control-sm" placeholder="Contoh: BI.124/2024" required>
                        </div>
                        <div class="col-md-6">
                            <label for="m_perkara_pasal" class="form-label small fw-semibold">Perkara / Pasal Tindak Pidana</label>
                            <input type="text" id="m_perkara_pasal" class="form-control form-control-sm" placeholder="Contoh: Narkotika Ps. 114 ayat (1) UU 35/2009">
                        </div>
                        <div class="col-md-6">
                            <label for="m_lama_pidana" class="form-label small fw-semibold">Lama Pidana (Vonis)</label>
                            <input type="text" id="m_lama_pidana" class="form-control form-control-sm" placeholder="Contoh: 5 Tahun Penjara">
                        </div>
                        <div class="col-md-4">
                            <label for="m_tanggal_ekspirasi" class="form-label small fw-semibold">Tanggal Ekspirasi Bebas</label>
                            <input type="date" id="m_tanggal_ekspirasi" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="m_tanggal_setengah" class="form-label small fw-semibold">Tanggal 1/2 Pidana</label>
                            <input type="date" id="m_tanggal_setengah" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="m_tanggal_dua_pertiga" class="form-label small fw-semibold">Tanggal 2/3 Pidana</label>
                            <input type="date" id="m_tanggal_dua_pertiga" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label for="m_jenis_usulan" class="form-label small fw-semibold">Jenis Usulan <span class="text-danger">*</span></label>
                            <select id="m_jenis_usulan" class="form-select form-select-sm" required>
                                <option value="PB">PB (Pembebasan Bersyarat)</option>
                                <option value="CB">CB (Cuti Bersyarat)</option>
                                <option value="CMB">CMB (Cuti Menjelang Bebas)</option>
                                <option value="Asimilasi">Asimilasi</option>
                                <option value="Remisi">Remisi</option>
                                <option value="Register F">Register F</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="m_keterangan" class="form-label small fw-semibold">Keterangan Tambahan</label>
                            <input type="text" id="m_keterangan" class="form-control form-control-sm" placeholder="Catatan syarat, penjamin, dll">
                        </div>

                        <!-- CENTANG MENARIK PERHATIAN DENGAN LABEL MERAH UNTUK KANWIL -->
                        <div class="col-md-12">
                            <div class="p-3 rounded border border-danger-subtle bg-danger-subtle bg-opacity-25 mt-2">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="m_perlu_perhatian" style="cursor: pointer; width: 2.5em; height: 1.3em;">
                                    <label class="form-check-label fw-bold text-danger ms-2" for="m_perlu_perhatian" style="cursor: pointer;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Tandai "Menarik Perhatian (Beri Label Merah)"
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-2">
                                    Beri tanda centang jika Warga Binaan ini memiliki perkara menonjol, atensi pimpinan, residivis/tipikor, memiliki catatan khusus, atau membutuhkan verifikasi ekstra oleh Kantor Wilayah.
                                </small>
                                <div>
                                    <label for="m_catatan_perhatian" class="form-label small fw-semibold text-dark mb-1">Catatan / Alasan Perhatian Khusus (Opsional):</label>
                                    <input type="text" id="m_catatan_perhatian" class="form-control form-control-sm" placeholder="Contoh: Perkara Tipikor / Atensi Pimpinan, mohon verifikasi ketelitian pembayaran denda & UP">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success btn-sm px-4 fw-bold" id="btnSimpanPeserta" onclick="simpanPesertaManual()">
                    <i class="fas fa-check me-1"></i><span id="btnSimpanPesertaText">Tambahkan ke Daftar</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Detail Peserta Sidang (Read) -->
<div class="modal fade" id="modalDetailPeserta" tabindex="-1" aria-labelledby="modalDetailPesertaTitle" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title fw-bold" id="modalDetailPesertaTitle">
                    <i class="fas fa-id-card me-2"></i>Rincian Data Peserta Sidang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 160px;">Nama Warga Binaan</td>
                        <td class="fw-bold text-dark">: <span id="d_nama_wbp">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Nomor Register</td>
                        <td>: <code id="d_nomor_register">-</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tindak Pidana / Pasal</td>
                        <td>: <span id="d_perkara_pasal">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Lama Pidana (Vonis)</td>
                        <td>: <span id="d_lama_pidana">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal Ekspirasi</td>
                        <td>: <span id="d_tanggal_ekspirasi">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal 1/2 Pidana</td>
                        <td>: <span id="d_tanggal_setengah">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal 2/3 Pidana</td>
                        <td>: <span id="d_tanggal_dua_pertiga">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jenis Usulan</td>
                        <td>: <span class="badge bg-primary" id="d_jenis_usulan">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Menarik Perhatian</td>
                        <td>: <span id="d_perhatian_badge">-</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Catatan / Keterangan</td>
                        <td>: <span class="fst-italic text-dark" id="d_catatan">-</span></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let pesertaList = [];

    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('file_excel');
        const btnPratinjau = document.getElementById('btnPratinjauExcel');
        const loadingDiv = document.getElementById('excelLoading');
        const previewTbody = document.getElementById('previewTbody');
        const totalWbpCount = document.getElementById('totalWbpCount');
        const wbpJsonInput = document.getElementById('wbp_json');
        const btnClear = document.getElementById('btnClearPreview');

        // Otomatis pratinjau saat file dipilih
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                pratinjauFile();
            }
        });

        btnPratinjau.addEventListener('click', pratinjauFile);

        function pratinjauFile() {
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Silakan pilih berkas Excel atau CSV terlebih dahulu!');
                return;
            }

            const formData = new FormData();
            formData.append('file_excel', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            loadingDiv.classList.remove('d-none');
            btnPratinjau.disabled = true;

            fetch('{{ route("sidang-tpp.preview-excel") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                loadingDiv.classList.add('d-none');
                btnPratinjau.disabled = false;

                if (!res.success) {
                    alert(res.message || 'Gagal memproses data berkas Excel.');
                    return;
                }

                if (!res.data || res.data.length === 0) {
                    alert('Tidak ada baris data usulan Warga Binaan yang ditemukan dalam file ini.');
                    return;
                }

                // Tambahkan data dari Excel ke pesertaList (append atau replace)
                if (pesertaList.length > 0 && confirm('Sudah ada data peserta di tabel. Apakah Anda ingin menggabungkan data Excel ini dengan data yang sudah ada? Klik OK untuk menggabungkan, Batal untuk menimpa.')) {
                    pesertaList = pesertaList.concat(res.data);
                } else {
                    pesertaList = res.data;
                }

                renderTable();
            })
            .catch(err => {
                loadingDiv.classList.add('d-none');
                btnPratinjau.disabled = false;
                console.error(err);
                alert('Terjadi kesalahan jaringan saat memproses file Excel.');
            });
        }

        btnClear.addEventListener('click', function () {
            if (pesertaList.length === 0 || confirm('Apakah Anda yakin ingin mengosongkan seluruh daftar peserta sidang?')) {
                pesertaList = [];
                fileInput.value = '';
                renderTable();
            }
        });

        // Pulihkan data jika ada old input wbp_json
        if (wbpJsonInput.value) {
            try {
                const parsed = JSON.parse(wbpJsonInput.value);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    pesertaList = parsed;
                    renderTable();
                }
            } catch (e) {}
        }
    });

    function bukaModalTambahPeserta() {
        document.getElementById('m_edit_index').value = '-1';
        document.getElementById('modalTambahPesertaTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Tambah Peserta Sidang TPP';
        document.getElementById('btnSimpanPesertaText').innerText = 'Tambahkan ke Daftar';
        document.getElementById('formManualPeserta').reset();
        document.getElementById('m_perlu_perhatian').checked = false;
        document.getElementById('m_jenis_usulan').value = 'PB';

        const modalEl = document.getElementById('modalTambahPesertaManual');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function editPeserta(index) {
        const row = pesertaList[index];
        if (!row) return;

        document.getElementById('m_edit_index').value = index;
        document.getElementById('modalTambahPesertaTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit Data Peserta Sidang TPP';
        document.getElementById('btnSimpanPesertaText').innerText = 'Simpan Perubahan';

        document.getElementById('m_nama_wbp').value = row.nama_wbp || '';
        document.getElementById('m_nomor_register').value = row.nomor_register || '';
        document.getElementById('m_perkara_pasal').value = row.perkara_pasal || '';
        document.getElementById('m_lama_pidana').value = row.lama_pidana || '';
        document.getElementById('m_tanggal_ekspirasi').value = row.tanggal_ekspirasi || '';
        document.getElementById('m_tanggal_setengah').value = row.tanggal_setengah_pidana || '';
        document.getElementById('m_tanggal_dua_pertiga').value = row.tanggal_dua_pertiga || '';
        document.getElementById('m_jenis_usulan').value = row.jenis_usulan || 'PB';
        document.getElementById('m_perlu_perhatian').checked = !!row.perlu_perhatian;
        document.getElementById('m_catatan_perhatian').value = row.catatan_perhatian || '';
        document.getElementById('m_keterangan').value = row.alasan_keterangan || '';

        const modalEl = document.getElementById('modalTambahPesertaManual');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function detailPeserta(index) {
        const row = pesertaList[index];
        if (!row) return;

        document.getElementById('d_nama_wbp').innerText = row.nama_wbp || '-';
        document.getElementById('d_nomor_register').innerText = row.nomor_register || '-';
        document.getElementById('d_perkara_pasal').innerText = row.perkara_pasal || '-';
        document.getElementById('d_lama_pidana').innerText = row.lama_pidana || '-';
        document.getElementById('d_tanggal_ekspirasi').innerText = row.tanggal_ekspirasi || '-';
        document.getElementById('d_tanggal_setengah').innerText = row.tanggal_setengah_pidana || '-';
        document.getElementById('d_tanggal_dua_pertiga').innerText = row.tanggal_dua_pertiga || '-';
        document.getElementById('d_jenis_usulan').innerText = row.jenis_usulan || 'PB';

        const dPerhatian = document.getElementById('d_perhatian_badge');
        if (row.perlu_perhatian) {
            dPerhatian.innerHTML = '<span class="badge bg-danger text-white fw-bold shadow-sm py-1 px-2"><i class="fas fa-exclamation-triangle me-1"></i>MENARIK PERHATIAN</span>';
        } else {
            dPerhatian.innerHTML = '<span class="badge bg-light text-muted border">Normal</span>';
        }

        document.getElementById('d_catatan').innerText = row.catatan_perhatian || row.alasan_keterangan || '-';

        const modalEl = document.getElementById('modalDetailPeserta');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function renderTable() {
        const tbody = document.getElementById('previewTbody');
        const countSpan = document.getElementById('totalWbpCount');
        const wbpJsonInput = document.getElementById('wbp_json');

        tbody.innerHTML = '';
        countSpan.innerText = pesertaList.length;
        wbpJsonInput.value = JSON.stringify(pesertaList);

        if (pesertaList.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyRowPlaceholder">
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fas fa-user-friends fa-2x mb-2 text-secondary opacity-50"></i>
                        <div>Belum ada peserta usulan yang didaftarkan.</div>
                        <small class="text-muted">Gunakan tombol <b>"Import Berkas Excel"</b> di atas atau klik <b>"Tambah Peserta Sidang"</b>.</small>
                    </td>
                </tr>
            `;
            return;
        }

        pesertaList.forEach((row, i) => {
            const tr = document.createElement('tr');
            if (row.perlu_perhatian) {
                tr.classList.add('table-danger', 'bg-opacity-25');
            }
            const badgePerhatian = row.perlu_perhatian
                ? `<span class="badge bg-danger text-white fw-bold shadow-sm py-1 px-2"><i class="fas fa-exclamation-triangle me-1"></i>MENARIK PERHATIAN</span>`
                : `<span class="badge bg-light text-muted border">Normal</span>`;

            tr.innerHTML = `
                <td class="text-center text-muted">${i + 1}</td>
                <td class="fw-bold">${escapeHtml(row.nama_wbp)}</td>
                <td><code>${escapeHtml(row.nomor_register)}</code></td>
                <td><small>${escapeHtml(row.perkara_pasal || '-')}</small></td>
                <td><small>${escapeHtml(row.lama_pidana || '-')}</small></td>
                <td class="text-center"><small>${escapeHtml(row.tanggal_dua_pertiga || '-')}</small></td>
                <td class="text-center"><span class="badge bg-primary">${escapeHtml(row.jenis_usulan || 'PB')}</span></td>
                <td class="text-center">${badgePerhatian}</td>
                <td class="text-muted small">${escapeHtml(row.catatan_perhatian || row.alasan_keterangan || '-')}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm py-0 px-2" title="Lihat Rincian Data Peserta (Read)" onclick="detailPeserta(${i})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2" title="Edit Data Peserta Ini (Update)" onclick="editPeserta(${i})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" title="Hapus Peserta dari Daftar (Delete)" onclick="hapusPeserta(${i})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function simpanPesertaManual() {
        const nama = document.getElementById('m_nama_wbp').value.trim();
        const register = document.getElementById('m_nomor_register').value.trim();

        if (!nama || !register) {
            alert('Nama Warga Binaan dan Nomor Register wajib diisi!');
            return;
        }

        const editIndex = parseInt(document.getElementById('m_edit_index').value, 10);
        const isPerhatian = document.getElementById('m_perlu_perhatian').checked;
        const catatanPerhatian = document.getElementById('m_catatan_perhatian').value.trim();
        const keterangan = document.getElementById('m_keterangan').value.trim();

        const dataPeserta = {
            nama_wbp: nama,
            nomor_register: register,
            perkara_pasal: document.getElementById('m_perkara_pasal').value.trim() || '-',
            lama_pidana: document.getElementById('m_lama_pidana').value.trim() || '-',
            tanggal_ekspirasi: document.getElementById('m_tanggal_ekspirasi').value || null,
            tanggal_setengah_pidana: document.getElementById('m_tanggal_setengah').value || null,
            tanggal_dua_pertiga: document.getElementById('m_tanggal_dua_pertiga').value || null,
            jenis_usulan: document.getElementById('m_jenis_usulan').value || 'PB',
            perlu_perhatian: isPerhatian,
            catatan_perhatian: catatanPerhatian || keterangan,
            alasan_keterangan: keterangan || catatanPerhatian,
            keputusan: (editIndex >= 0 && pesertaList[editIndex] && pesertaList[editIndex].keputusan) ? pesertaList[editIndex].keputusan : 'belum_putus',
        };

        if (editIndex >= 0 && editIndex < pesertaList.length) {
            pesertaList[editIndex] = dataPeserta;
        } else {
            pesertaList.push(dataPeserta);
        }

        renderTable();

        // Reset form & tutup modal
        document.getElementById('formManualPeserta').reset();
        document.getElementById('m_edit_index').value = '-1';
        const modalEl = document.getElementById('modalTambahPesertaManual');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }

    function hapusPeserta(index) {
        if (confirm('Hapus peserta ini dari daftar sidang?')) {
            pesertaList.splice(index, 1);
            renderTable();
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
@endpush
