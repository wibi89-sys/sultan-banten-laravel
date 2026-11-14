@extends('layouts.app')

@section('title', 'Registrasi Sidang TPP Kanwil - Sultan Banten')
@section('page_title', 'Registrasi Sidang TPP Kanwil')

@section('content')
<div class="content-wrapper">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 12px;">
                    <li class="breadcrumb-item"><a href="{{ route('sidang-tpp-kanwil.index') }}" class="text-decoration-none">Sidang TPP Kanwil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Registrasi Sidang</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 20px;">
                <i class="fas fa-file-signature text-primary me-2"></i>Registrasi Sidang TPP Kantor Wilayah
            </h4>
        </div>
        <div>
            <a href="{{ route('sidang-tpp-kanwil.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 20px;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Mohon periksa isian formulir:</div>
        <ul class="mb-0 small">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <form action="{{ route('sidang-tpp-kanwil.store') }}" method="POST" enctype="multipart/form-data" id="formCreateSidangKanwil">
        @csrf

        <div class="row g-4">
            <!-- Kolom Kiri: Informasi Pelaksanaan & Agenda -->
            <div class="col-lg-7">
                <!-- Card 1: Data Pelaksanaan -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary p-2" style="border-radius: 8px;">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <h6 class="fw-bold mb-0 text-dark">1. Jadwal &amp; Tempat Pelaksanaan</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Nomor Register Sidang <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_sidang" class="form-control form-control-sm @error('nomor_sidang') is-invalid @enderror" value="{{ old('nomor_sidang', $suggestedNomorSidang) }}" required placeholder="Contoh: STPP-KW/2026/11/001">
                                <small class="text-muted" style="font-size: 11px;">Nomor registrasi berkas sidang internal Kanwil</small>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Tanggal Pelaksanaan Sidang <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_sidang" class="form-control form-control-sm @error('tanggal_sidang') is-invalid @enderror" value="{{ old('tanggal_sidang', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-bold text-dark">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_mulai" class="form-control form-control-sm @error('jam_mulai') is-invalid @enderror" value="{{ old('jam_mulai', '09:00') }}" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-bold text-dark">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control form-control-sm" value="{{ old('jam_selesai', '12:00') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Metode Pelaksanaan <span class="text-danger">*</span></label>
                                <select name="metode" id="selectMetode" class="form-select form-select-sm" required onchange="toggleMetodeDaring(this.value)">
                                    <option value="luring" {{ old('metode') == 'luring' ? 'selected' : '' }}>Tatap Muka Langsung (Luring)</option>
                                    <option value="daring" {{ old('metode') == 'daring' ? 'selected' : '' }}>Virtual Online (Daring)</option>
                                    <option value="hybrid" {{ old('metode') == 'hybrid' ? 'selected' : '' }}>Hybrid (Luring &amp; Virtual)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Tempat Pelaksanaan Fisik <span class="text-danger">*</span></label>
                                <input type="text" name="tempat" class="form-control form-control-sm @error('tempat') is-invalid @enderror" value="{{ old('tempat', $setting->tempat_sidang_default ?? 'Ruang Rapat Divisi Pemasyarakatan Kantor Wilayah') }}" required placeholder="Lokasi ruang rapat fisik">
                            </div>

                            <!-- Field Daring / Zoom (Dinamis) -->
                            <div id="boxDaringFields" class="col-12 p-3 bg-light rounded border {{ old('metode', 'luring') == 'luring' ? 'd-none' : '' }}">
                                <div class="fw-bold text-primary small mb-2"><i class="fas fa-video me-1"></i>Informasi Tautan Rapat Daring (Zoom/Google Meet)</div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-1">Tautan / Link Sidang Daring</label>
                                        <input type="url" name="link_daring" class="form-control form-control-sm" value="{{ old('link_daring', $setting->link_daring_default ?? '') }}" placeholder="https://zoom.us/j/... atau https://meet.google.com/...">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Meeting ID</label>
                                        <input type="text" name="meeting_id" class="form-control form-control-sm" value="{{ old('meeting_id', $setting->meeting_id_default ?? '') }}" placeholder="834 9182 9182">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Passcode / Kata Sandi</label>
                                        <input type="text" name="passcode" class="form-control form-control-sm" value="{{ old('passcode', $setting->passcode_default ?? '') }}" placeholder="123456">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Nota Dinas Undangan -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                        <span class="badge bg-info-subtle text-info p-2" style="border-radius: 8px;">
                            <i class="fas fa-file-contract"></i>
                        </span>
                        <h6 class="fw-bold mb-0 text-dark">2. Nota Dinas Undangan &amp; Administrasi Sidang</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                <label class="form-label small fw-bold text-dark">Nomor Nota Dinas <span class="badge bg-secondary-subtle text-secondary py-0.5 px-1.5" style="font-size: 10px;">Input Manual</span></label>
                                <input type="text" name="nomor_nota_dinas" class="form-control form-control-sm" value="{{ old('nomor_nota_dinas', $suggestedNomorNota) }}" placeholder="Contoh: W.12-UM.01.01-1245">
                                <small class="text-muted" style="font-size: 11px;">Nomor surat nota dinas undangan persidangan</small>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-bold text-dark">Tanggal Nota Dinas</label>
                                <input type="date" name="tanggal_nota_dinas" class="form-control form-control-sm" value="{{ old('tanggal_nota_dinas', date('Y-m-d')) }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Nomor Risalah Sidang</label>
                                <input type="text" name="nomor_risalah" class="form-control form-control-sm" value="{{ old('nomor_risalah', $suggestedNomorRisalah) }}" placeholder="Contoh: WP.12.PK.06.05-28">
                                <small class="text-muted" style="font-size: 11px;">Nomor registrasi naskah Risalah Sidang TPP</small>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Nomor Laporan Sidang</label>
                                <input type="text" name="nomor_laporan" class="form-control form-control-sm" value="{{ old('nomor_laporan', $suggestedNomorLaporan) }}" placeholder="Contoh: WP.12.PK.06.06-28">
                                <small class="text-muted" style="font-size: 11px;">Nomor naskah Laporan Pelaksanaan Sidang TPP</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Daftar Materi / Agenda Sidang Dinamis -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white p-2" style="border-radius: 8px;">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">3. Materi / Agenda Persidangan</h6>
                                <small class="text-muted" style="font-size: 11px;">Satu sesi sidang dapat membahas beberapa materi/agenda sekaligus.</small>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" style="border-radius: 20px; font-size: 12px;" onclick="tambahMateriItem()">
                                <i class="fas fa-plus-circle me-1"></i> Tambahkan Materi Sidang
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div id="materiContainer" class="d-flex flex-column gap-3">

                            <!-- Materi Item #0 (Awal) -->
                            <div class="card border shadow-none card-materi-item" id="cardMateri_0" style="border-radius: 10px; border-color: #cbd5e1 !important;">
                                <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                                        <span class="badge bg-primary px-2.5 py-1.5 materi-badge-urutan" style="font-size: 11px;">Materi #1</span>
                                        <select name="materis[0][kategori_materi]" class="form-select form-select-sm w-auto fw-bold text-dark border-primary border-opacity-25" onchange="switchMateriRepeaterCategory(0, this.value)">
                                            <option value="mutasi_narapidana" selected>🏢 Mutasi Narapidana Antar-UPT</option>
                                            <option value="mutasi_keinginan_sendiri">👤 Mutasi Permintaan Sendiri (Mendekati Keluarga)</option>
                                            <option value="izin_berobat">🏥 Izin Berobat Keluar Kota</option>
                                            <option value="klien_bapas">⚖️ Klien Pemasyarakatan (Bapas)</option>
                                            <option value="lainnya">📋 Lainnya / Agenda Mandiri</option>
                                        </select>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-hapus-materi" style="display: none;" onclick="removeMateriItem(0)">
                                            <i class="fas fa-trash-alt me-1"></i>Hapus
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2.5">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold text-dark mb-1">Judul Materi / Pokok Agenda <span class="text-danger">*</span></label>
                                            <input type="text" name="materis[0][judul_materi]" id="materi_judul_0" class="form-control form-control-sm" required placeholder="Contoh: Permohonan Pemindahan WBP an. Budi Santoso bin Suwandi">
                                        </div>

                                        <!-- SUB-FORM 1: MUTASI NARAPIDANA ANTAR-UPT (UMUM) -->
                                        <div id="boxMateriMutasi_0" class="col-12">
                                            <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                                                <div class="fw-bold text-warning-emphasis small mb-2"><i class="fas fa-exchange-alt me-1"></i>Data Usulan Pemindahan Narapidana Antar-UPT</div>
                                                
                                                <div class="mb-2">
                                                    <label class="form-label small text-muted mb-1">Tarik Usulan dari Modul Mutasi UPT (Opsional):</label>
                                                    <select name="materis[0][mutasi_id]" class="form-select form-select-sm" onchange="autoFillMutasiRepeater(this, 0)">
                                                        <option value="">-- Pilih Usulan Mutasi UPT --</option>
                                                        @foreach($mutasiList as $mu)
                                                        <option value="{{ $mu->id }}"
                                                            data-nama="{{ $mu->keterangan ? Str::limit($mu->keterangan, 50) : 'WBP Usulan Mutasi ' . ($mu->uptAsal?->nama_upt ?? '') }}"
                                                            data-uptasal="{{ $mu->upt_asal_id }}"
                                                            data-upttujuan="{{ $mu->upt_tujuan_id }}"
                                                            data-upttujuannama="{{ $mu->uptTujuan?->nama_upt ?? '' }}"
                                                            data-alasan="{{ $mu->kategori_mutasi ?? $mu->keterangan }}">
                                                            {{ $mu->nomor_surat }} &bull; {{ $mu->uptAsal?->nama_upt }} &rarr; {{ $mu->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }} ({{ $mu->total_usulan }} Orang)
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="row g-2">
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Nama Narapidana / WBP <span class="text-danger">*</span></label>
                                                        <input type="text" name="materis[0][mutasi_wbp_nama]" id="mutasi_wbp_nama_0" class="form-control form-control-sm" placeholder="Nama narapidana">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Nomor Register</label>
                                                        <input type="text" name="materis[0][mutasi_nomor_register]" class="form-control form-control-sm" placeholder="BI.124/2025">
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                                        <input type="text" name="materis[0][mutasi_perkara]" class="form-control form-control-sm" placeholder="Narkotika Ps 114">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                                        <input type="text" name="materis[0][mutasi_lama_pidana]" class="form-control form-control-sm" placeholder="5 Tahun">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">UPT Asal</label>
                                                        <select name="materis[0][mutasi_upt_asal_id]" id="mutasi_upt_asal_id_0" class="form-select form-select-sm">
                                                            <option value="">-- Pilih UPT Asal --</option>
                                                            @foreach($uptList as $u)
                                                            <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">UPT Tujuan</label>
                                                        <input type="text" name="materis[0][mutasi_upt_tujuan]" id="upt_tujuan_rekomendasi_id_0" class="form-control form-control-sm border-warning" placeholder="Contoh: Lapas Kelas I Cipinang / Lapas Semarang" list="listUptTujuan">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                                        <input type="text" name="materis[0][mutasi_alasan]" id="mutasi_alasan_0" class="form-control form-control-sm" placeholder="Pembinaan lanjutan / redistribusi">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Hubungan</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Orang Tua">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_kontak]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                                    </div>

                                                    <!-- Unggah Berkas Mutasi -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-warning-emphasis me-1"></i>Unggah Berkas Mutasi (Opsional):</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Litmas Bapas</label>
                                                        <input type="file" name="materis[0][file_mutasi_litmas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Surat Permohonan / Usulan</label>
                                                        <input type="file" name="materis[0][file_mutasi_permohonan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Jaminan Keluarga</label>
                                                        <input type="file" name="materis[0][file_mutasi_jaminan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Register F</label>
                                                        <input type="file" name="materis[0][file_mutasi_reg_f]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Asesmen Risiko</label>
                                                        <input type="file" name="materis[0][file_mutasi_asesmen]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SUB-FORM 2: MUTASI KEINGINAN SENDIRI (MENDEKATI KELUARGA) -->
                                        <div id="boxMateriPribadi_0" class="col-12 d-none">
                                            <div class="p-3 bg-light rounded border" style="border-color: #6f42c1 !important;">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="fw-bold small" style="color: #6f42c1;"><i class="fas fa-user-friends me-1"></i>Data Mutasi Permintaan Sendiri (Mendekati Keluarga & Biaya Mandiri)</div>
                                                    <span class="badge text-white" style="background-color: #6f42c1 !important; font-size: 10px;">Khusus Keinginan Sendiri</span>
                                                </div>
                                                <div class="alert alert-info py-1.5 px-2.5 mb-2.5 small" style="font-size: 11px;">
                                                    <i class="fas fa-info-circle me-1"></i>Dilengkapi surat pernyataan bermaterai kesanggupan biaya mandiri, Litmas PK Bapas, Berita Acara (BA) Sidang TPP UPT asal, dan jaminan keluarga.
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <label class="form-label small text-muted mb-1">Tarik Usulan dari Modul Mutasi UPT (Opsional):</label>
                                                    <select name="materis[0][mutasi_id]" class="form-select form-select-sm" onchange="autoFillMutasiPribadiRepeater(this, 0)">
                                                        <option value="">-- Pilih Usulan Mutasi UPT --</option>
                                                        @foreach($mutasiList as $mu)
                                                        <option value="{{ $mu->id }}"
                                                            data-nama="{{ $mu->keterangan ? Str::limit($mu->keterangan, 50) : 'WBP Usulan Mutasi ' . ($mu->uptAsal?->nama_upt ?? '') }}"
                                                            data-uptasal="{{ $mu->upt_asal_id }}"
                                                            data-upttujuan="{{ $mu->upt_tujuan_id }}"
                                                            data-upttujuannama="{{ $mu->uptTujuan?->nama_upt ?? '' }}"
                                                            data-alasan="{{ $mu->kategori_mutasi ?? $mu->keterangan }}">
                                                            {{ $mu->nomor_surat }} &bull; {{ $mu->uptAsal?->nama_upt }} &rarr; {{ $mu->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }} ({{ $mu->total_usulan }} Orang)
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="row g-2">
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Nama Narapidana / WBP <span class="text-danger">*</span></label>
                                                        <input type="text" name="materis[0][mutasi_wbp_nama]" id="mutasi_pribadi_wbp_nama_0" class="form-control form-control-sm" placeholder="Nama narapidana">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Nomor Register</label>
                                                        <input type="text" name="materis[0][mutasi_nomor_register]" class="form-control form-control-sm" placeholder="BI.124/2025">
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                                        <input type="text" name="materis[0][mutasi_perkara]" class="form-control form-control-sm" placeholder="Narkotika Ps 114">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                                        <input type="text" name="materis[0][mutasi_lama_pidana]" class="form-control form-control-sm" placeholder="5 Tahun">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">UPT Asal</label>
                                                        <select name="materis[0][mutasi_upt_asal_id]" id="mutasi_pribadi_upt_asal_id_0" class="form-select form-select-sm">
                                                            <option value="">-- Pilih UPT Asal --</option>
                                                            @foreach($uptList as $u)
                                                            <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">UPT Tujuan</label>
                                                        <input type="text" name="materis[0][mutasi_upt_tujuan]" id="mutasi_pribadi_upt_tujuan_id_0" class="form-control form-control-sm border-primary" placeholder="Contoh: Lapas Kelas I Cipinang / Lapas Besi Nusakambangan" list="listUptTujuan">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                                        <input type="text" name="materis[0][mutasi_alasan]" id="mutasi_pribadi_alasan_0" class="form-control form-control-sm" value="Permintaan sendiri mendekati keluarga penjamin dengan kesanggupan pembiayaan mandiri">
                                                    </div>

                                                    <!-- Bagian Litmas & PK Bapas -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-balance-scale text-primary me-1"></i>Penelitian Kemasyarakatan (Litmas) & PK Bapas:</span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Nama Petugas PK Bapas</label>
                                                        <input type="text" name="materis[0][nama_pk_bapas]" class="form-control form-control-sm" placeholder="Nama PK Bapas">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">NIP / Kontak PK</label>
                                                        <input type="text" name="materis[0][nip_pk_bapas]" class="form-control form-control-sm" placeholder="19850101...">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Bapas Asal</label>
                                                        <input type="text" name="materis[0][bapas_nama]" class="form-control form-control-sm" placeholder="Bapas Kelas I Serang">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small text-muted mb-1">Kesimpulan & Rekomendasi Litmas Bapas</label>
                                                        <input type="text" name="materis[0][rekomendasi_litmas]" class="form-control form-control-sm" placeholder="Dapat direkomendasikan pemindahan mendekati domisili keluarga penjamin...">
                                                    </div>

                                                    <!-- Bagian BA Sidang TPP UPT -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-gavel text-warning-emphasis me-1"></i>Hasil Sidang TPP UPT Asal (Lapas/Rutan):</span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Nomor Berita Acara (BA) UPT</label>
                                                        <input type="text" name="materis[0][nomor_ba_tpp_upt]" class="form-control form-control-sm" placeholder="W12.PAS.1.PK.01...">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Tanggal BA Sidang UPT</label>
                                                        <input type="date" name="materis[0][tanggal_ba_tpp_upt]" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Rekomendasi Sidang UPT</label>
                                                        <input type="text" name="materis[0][rekomendasi_tpp_upt]" class="form-control form-control-sm" placeholder="Disetujui untuk diusulkan ke Kanwil">
                                                    </div>

                                                    <!-- Bagian Penjamin Keluarga -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-home text-success me-1"></i>Data Penjamin Keluarga:</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1">NIK KTP Penjamin</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_nik]" class="form-control form-control-sm" placeholder="367101...">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1">Hubungan</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Orang Tua / Saudara">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1">No Kontak / WA</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_kontak]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small text-muted mb-1">Alamat Domisili Penjamin</label>
                                                        <input type="text" name="materis[0][mutasi_penjamin_alamat]" class="form-control form-control-sm" placeholder="Alamat lengkap penjamin (sesuai KTP/KK)">
                                                    </div>

                                                    <!-- Unggah Berkas Persyaratan Khusus -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-primary me-1"></i>Unggah Berkas Persyaratan Khusus (PDF/Doc/Zip/Gambar maks 15MB):</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-file-signature text-primary me-1"></i>Surat Pernyataan Mandiri Bermaterai (Biaya Sendiri)</label>
                                                        <input type="file" name="materis[0][file_mutasi_pernyataan_pribadi]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-file-contract text-warning me-1"></i>Berita Acara (BA) Sidang TPP UPT Bertandatangan</label>
                                                        <input type="file" name="materis[0][file_mutasi_ba_upt]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Laporan Hasil Litmas Bapas</label>
                                                        <input type="file" name="materis[0][file_mutasi_litmas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Surat Jaminan Penjamin Keluarga (Bermaterai)</label>
                                                        <input type="file" name="materis[0][file_mutasi_jaminan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-id-card text-info me-1"></i>KTP & Kartu Keluarga Penjamin</label>
                                                        <input type="file" name="materis[0][file_mutasi_ktp_penjamin]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-book text-secondary me-1"></i>Buku Register F (Bebas Disiplin)</label>
                                                        <input type="file" name="materis[0][file_mutasi_reg_f]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1"><i class="fas fa-heartbeat text-danger me-1"></i>Surat Kesehatan Dokter (Fit to Travel)</label>
                                                        <input type="file" name="materis[0][file_mutasi_kesehatan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SUB-FORM 3: IZIN BEROBAT KELUAR KOTA -->
                                        <div id="boxMateriBerobat_0" class="col-12 d-none">
                                            <div class="p-3 bg-light rounded border border-danger border-opacity-50">
                                                <div class="fw-bold text-danger small mb-2"><i class="fas fa-heartbeat me-1"></i>Data Usulan Izin Berobat Keluar Kota</div>
                                                <div class="row g-2">
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Nama Pasien / WBP <span class="text-danger">*</span></label>
                                                        <input type="text" name="materis[0][berobat_wbp_nama]" class="form-control form-control-sm" placeholder="Nama narapidana">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Nomor Register</label>
                                                        <input type="text" name="materis[0][berobat_nomor_register]" class="form-control form-control-sm" placeholder="Nomor register">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Perkara / Pidana</label>
                                                        <input type="text" name="materis[0][berobat_perkara]" class="form-control form-control-sm" placeholder="Pencurian Ps 363">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">UPT Asal Pengusul</label>
                                                        <select name="materis[0][berobat_upt_asal_id]" class="form-select form-select-sm">
                                                            <option value="">-- Pilih UPT Lapas / Rutan Asal --</option>
                                                            @foreach($uptList as $u)
                                                            <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Diagnosa Penyakit / Resume Medis <span class="text-danger">*</span></label>
                                                        <input type="text" name="materis[0][berobat_diagnosa]" class="form-control form-control-sm" placeholder="Contoh: Ca Colon Stadium III...">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Dokter Penanggung Jawab</label>
                                                        <input type="text" name="materis[0][berobat_dokter]" class="form-control form-control-sm" placeholder="dr. Sp.B">
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Rumah Sakit Rujukan Luar Kota <span class="text-danger">*</span></label>
                                                        <input type="text" name="materis[0][berobat_rs_nama]" class="form-control form-control-sm" placeholder="RSUPN Dr. Cipto Mangunkusumo">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">Kota / Domisili RS</label>
                                                        <input type="text" name="materis[0][berobat_rs_kota]" class="form-control form-control-sm" placeholder="Jakarta Pusat">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Penjamin Keluarga</label>
                                                        <input type="text" name="materis[0][berobat_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Hubungan</label>
                                                        <input type="text" name="materis[0][berobat_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Anak">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                                        <input type="text" name="materis[0][berobat_penjamin_kontak]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                                    </div>

                                                    <!-- Unggah Berkas Medis -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-file-medical text-danger me-1"></i>Unggah Berkas Medis (Opsional):</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Resume Rekam Medis</label>
                                                        <input type="file" name="materis[0][file_medis_rekam]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Surat Rujukan Dokter/RS</label>
                                                        <input type="file" name="materis[0][file_medis_rujukan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Jaminan Pembiayaan</label>
                                                        <input type="file" name="materis[0][file_medis_jaminan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">BA Sidang TPP UPT</label>
                                                        <input type="file" name="materis[0][file_medis_ba_upt]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SUB-FORM 4: KLIEN PEMASYARAKATAN (BAPAS) -->
                                        <div id="boxMateriKlien_0" class="col-12 d-none">
                                            <div class="p-3 bg-light rounded border border-primary border-opacity-50">
                                                <div class="fw-bold text-primary small mb-2"><i class="fas fa-passport me-1"></i>Data Usulan Klien Bapas (Izin Bepergian ke Luar Negeri)</div>
                                                <div class="row g-2">
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1">Nama Lengkap Klien <span class="text-danger">*</span></label>
                                                        <input type="text" name="materis[0][klien_nama]" class="form-control form-control-sm" placeholder="Nama klien pemasyarakatan">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">BIN (Nama Ayah)</label>
                                                        <input type="text" name="materis[0][klien_bin]" class="form-control form-control-sm" placeholder="Nama ayah">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Nomor Register Bapas</label>
                                                        <input type="text" name="materis[0][klien_no_register]" class="form-control form-control-sm" placeholder="BPS-SRG/PB/...">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Bapas Pembimbing</label>
                                                        <select name="materis[0][klien_bapas_nama]" class="form-select form-select-sm">
                                                            <option value="Balai Pemasyarakatan Kelas I Serang">Bapas Kelas I Serang</option>
                                                            <option value="Balai Pemasyarakatan Kelas II Ciangir">Bapas Kelas II Ciangir</option>
                                                            <option value="Bapas Lainnya">Bapas Lainnya</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Program Integrasi</label>
                                                        <input type="text" name="materis[0][klien_program]" class="form-control form-control-sm" value="Pembebasan Bersyarat (PB)">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Nama Pembimbing (PK)</label>
                                                        <input type="text" name="materis[0][klien_nama_pk]" class="form-control form-control-sm" placeholder="Nama PK Bapas">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Keperluan Bepergian</label>
                                                        <input type="text" name="materis[0][klien_keperluan_ln]" class="form-control form-control-sm" value="Menjalankan Ibadah Umroh">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Negara Tujuan</label>
                                                        <input type="text" name="materis[0][klien_negara_tujuan]" class="form-control form-control-sm" value="Kerajaan Arab Saudi">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Tgl Berangkat</label>
                                                        <input type="date" name="materis[0][klien_tgl_berangkat]" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Tgl Kembali</label>
                                                        <input type="date" name="materis[0][klien_tgl_kembali]" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                                        <input type="text" name="materis[0][klien_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Hubungan</label>
                                                        <input type="text" name="materis[0][klien_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Saudara">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted mb-1">Telepon Penjamin</label>
                                                        <input type="text" name="materis[0][klien_penjamin_telepon]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                                    </div>

                                                    <!-- Unggah Berkas Klien Bapas -->
                                                    <div class="col-12 mt-2 pt-2 border-top">
                                                        <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-primary me-1"></i>Unggah Berkas Klien Bapas (Opsional):</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Litmas Bapas</label>
                                                        <input type="file" name="materis[0][file_litmas_bapas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Surat Kepala Bapas</label>
                                                        <input type="file" name="materis[0][file_surat_bapas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Bukti Tiket / Jadwal Travel</label>
                                                        <input type="file" name="materis[0][file_bukti_travel]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small text-muted mb-1">Surat Jaminan Bermaterai</label>
                                                        <input type="file" name="materis[0][file_surat_jaminan]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload File Pokok Bahan Sidang & Dokumen Tambahan Lainnya -->
                                        <!-- Upload File Pokok Bahan Sidang & Dokumen Tambahan Lainnya -->
                                        <div class="col-12 col-md-6 mt-2">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-file-download text-primary me-1"></i>Bahan Materi Sidang Utama (Opsional)</label>
                                            <input type="file" name="materis[0][file_bahan_sidang]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                                            <div class="form-text text-muted" style="font-size: 11px;">Format: PDF, Word, Excel, ZIP (Maks. 15MB)</div>
                                        </div>
                                        <div class="col-12 col-md-6 mt-2">
                                            <div class="p-2 bg-white rounded border">
                                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-paperclip text-secondary me-1"></i>Dokumen Tambahan Lainnya (Opsional)</label>
                                                <div class="row g-1">
                                                    <div class="col-12 col-sm-5">
                                                        <input type="text" name="materis[0][dokumen_tambahan_nama]" class="form-control form-control-sm" placeholder="Nama Dokumen (Kustom)">
                                                    </div>
                                                    <div class="col-12 col-sm-7">
                                                        <input type="file" name="materis[0][dokumen_tambahan_file]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Pimpinan & Susunan Anggota Tim Sidang -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                        <span class="badge bg-warning-subtle text-warning-emphasis p-2" style="border-radius: 8px;">
                            <i class="fas fa-users-cog"></i>
                        </span>
                        <h6 class="fw-bold mb-0 text-dark">3. Susunan Tim Sidang TPP Kanwil</h6>
                    </div>
                    <div class="card-body p-4">
                        <!-- Ketua Sidang -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Ketua Sidang TPP <span class="text-danger">*</span></label>
                            <select name="ketua_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Ketua Sidang --</option>
                                @foreach($pegawaiList as $p)
                                <option value="{{ $p->id }}" {{ old('ketua_id', $defaultKetua?->id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama }} - {{ $p->jabatan }} {{ $p->peran_tpp ? '('.$p->peran_tpp.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Otomatis terisi dari Master Tim Tetap Pengaturan TPP</small>
                        </div>

                        <!-- Sekretaris Sidang -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Sekretaris Sidang TPP <span class="text-danger">*</span></label>
                            <select name="sekretaris_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Sekretaris Sidang --</option>
                                @foreach($pegawaiList as $p)
                                <option value="{{ $p->id }}" {{ old('sekretaris_id', $defaultSekretaris?->id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama }} - {{ $p->jabatan }} {{ $p->peran_tpp ? '('.$p->peran_tpp.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Anggota Sidang Checkbox List -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Pilih Anggota Sidang yang Diundang <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" style="font-size: 11px;" onclick="selectAllAnggota(true)">Pilih Semua</button>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" style="font-size: 11px;" onclick="selectAllAnggota(false)">Hapus Pilihan</button>
                                </div>
                            </div>
                            <input type="text" id="cariAnggotaInput" class="form-control form-control-sm mb-2" placeholder="Cari nama atau jabatan anggota..." onkeyup="filterAnggotaList()">
                            
                            <div class="border rounded p-2" style="max-height: 280px; overflow-y: auto; background: var(--bg-card);">
                                @foreach($pegawaiList as $p)
                                <div class="form-check anggota-item py-1 border-bottom" data-name="{{ strtolower($p->nama) }}" data-jabatan="{{ strtolower($p->jabatan) }}">
                                    <input class="form-check-input anggota-checkbox" type="checkbox" name="anggota_ids[]" value="{{ $p->id }}" id="anggota_{{ $p->id }}" {{ is_array(old('anggota_ids')) ? (in_array($p->id, old('anggota_ids')) ? 'checked' : '') : (in_array($p->id, $defaultAnggotaIds ?? []) ? 'checked' : '') }}>
                                    <label class="form-check-label w-100" for="anggota_{{ $p->id }}" style="cursor: pointer;">
                                        <div class="fw-semibold text-dark" style="font-size: 12.5px;">
                                            {{ $p->nama }}
                                            @if($p->peran_tpp)
                                            <span class="badge bg-light text-secondary border ms-1" style="font-size: 9.5px;">{{ $p->peran_tpp }}</span>
                                            @endif
                                            @if($p->no_wa)
                                            <span class="text-success ms-1" title="WA Terdaftar: {{ $p->no_wa }}" style="font-size: 10px;"><i class="fab fa-whatsapp"></i></span>
                                            @endif
                                        </div>
                                        <div class="small text-muted" style="font-size: 11px;">{{ $p->jabatan }}</div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                <i class="fab fa-whatsapp text-success me-1"></i>Undangan WhatsApp dan link token presensi/pendapat akan otomatis dikirimkan ke anggota terpilih.
                            </small>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm py-2 shadow-sm fw-bold" style="border-radius: 8px;">
                                <i class="fas fa-check-circle me-1"></i> Simpan &amp; Daftarkan Sidang
                            </button>
                            <a href="{{ route('sidang-tpp-kanwil.index') }}" class="btn btn-light btn-sm py-2 border text-secondary" style="border-radius: 8px;">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<!-- TEMPLATE REPEATER MATERI SIDANG (CLONING DENGAN JS) -->
<template id="materiTemplate">
    <div class="card border shadow-none card-materi-item" id="cardMateri___INDEX__" style="border-radius: 10px; border-color: #cbd5e1 !important;">
        <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <span class="badge bg-primary px-2.5 py-1.5 materi-badge-urutan" style="font-size: 11px;">Materi #__NUM__</span>
                <select name="materis[__INDEX__][kategori_materi]" class="form-select form-select-sm w-auto fw-bold text-dark border-primary border-opacity-25" onchange="switchMateriRepeaterCategory(__INDEX__, this.value)">
                    <option value="mutasi_narapidana" selected>🏢 Mutasi Narapidana Antar-UPT</option>
                    <option value="mutasi_keinginan_sendiri">👤 Mutasi Permintaan Sendiri (Mendekati Keluarga)</option>
                    <option value="izin_berobat">🏥 Izin Berobat Keluar Kota</option>
                    <option value="klien_bapas">⚖️ Klien Pemasyarakatan (Bapas)</option>
                    <option value="lainnya">📋 Lainnya / Agenda Mandiri</option>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-hapus-materi" onclick="removeMateriItem(__INDEX__)">
                    <i class="fas fa-trash-alt me-1"></i>Hapus
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-2.5">
                <div class="col-12">
                    <label class="form-label small fw-bold text-dark mb-1">Judul Materi / Pokok Agenda <span class="text-danger">*</span></label>
                    <input type="text" name="materis[__INDEX__][judul_materi]" id="materi_judul___INDEX__" class="form-control form-control-sm" required placeholder="Contoh: Permohonan Pemindahan WBP an. Budi Santoso bin Suwandi">
                </div>

                <!-- SUB-FORM 1: MUTASI NARAPIDANA ANTAR-UPT (UMUM) -->
                <div id="boxMateriMutasi___INDEX__" class="col-12">
                    <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                        <div class="fw-bold text-warning-emphasis small mb-2"><i class="fas fa-exchange-alt me-1"></i>Data Usulan Pemindahan Narapidana Antar-UPT</div>
                        
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Tarik Usulan dari Modul Mutasi UPT (Opsional):</label>
                            <select name="materis[__INDEX__][mutasi_id]" class="form-select form-select-sm" onchange="autoFillMutasiRepeater(this, __INDEX__)">
                                <option value="">-- Pilih Usulan Mutasi UPT --</option>
                                @foreach($mutasiList as $mu)
                                <option value="{{ $mu->id }}"
                                    data-nama="{{ $mu->keterangan ? Str::limit($mu->keterangan, 50) : 'WBP Usulan Mutasi ' . ($mu->uptAsal?->nama_upt ?? '') }}"
                                    data-uptasal="{{ $mu->upt_asal_id }}"
                                    data-upttujuan="{{ $mu->upt_tujuan_id }}"
                                    data-upttujuannama="{{ $mu->uptTujuan?->nama_upt ?? '' }}"
                                    data-alasan="{{ $mu->kategori_mutasi ?? $mu->keterangan }}">
                                    {{ $mu->nomor_surat }} &bull; {{ $mu->uptAsal?->nama_upt }} &rarr; {{ $mu->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }} ({{ $mu->total_usulan }} Orang)
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Nama Narapidana / WBP <span class="text-danger">*</span></label>
                                <input type="text" name="materis[__INDEX__][mutasi_wbp_nama]" id="mutasi_wbp_nama___INDEX__" class="form-control form-control-sm" placeholder="Nama narapidana">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Nomor Register</label>
                                <input type="text" name="materis[__INDEX__][mutasi_nomor_register]" class="form-control form-control-sm" placeholder="BI.124/2025">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                <input type="text" name="materis[__INDEX__][mutasi_perkara]" class="form-control form-control-sm" placeholder="Narkotika Ps 114">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                <input type="text" name="materis[__INDEX__][mutasi_lama_pidana]" class="form-control form-control-sm" placeholder="5 Tahun">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">UPT Asal</label>
                                <select name="materis[__INDEX__][mutasi_upt_asal_id]" id="mutasi_upt_asal_id___INDEX__" class="form-select form-select-sm">
                                    <option value="">-- Pilih UPT Asal --</option>
                                    @foreach($uptList as $u)
                                    <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">UPT Tujuan</label>
                                <input type="text" name="materis[__INDEX__][mutasi_upt_tujuan]" id="upt_tujuan_rekomendasi_id___INDEX__" class="form-control form-control-sm border-warning" placeholder="Contoh: Lapas Kelas I Cipinang / Lapas Semarang" list="listUptTujuan">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                <input type="text" name="materis[__INDEX__][mutasi_alasan]" id="mutasi_alasan___INDEX__" class="form-control form-control-sm" placeholder="Pembinaan lanjutan / redistribusi">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Hubungan</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Orang Tua">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_kontak]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                            </div>

                            <!-- Unggah Berkas Mutasi -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-warning-emphasis me-1"></i>Unggah Berkas Mutasi (Opsional):</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Litmas Bapas</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_litmas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Surat Permohonan / Usulan</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_permohonan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Jaminan Keluarga</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_jaminan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Register F</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_reg_f]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Asesmen Risiko</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_asesmen]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-FORM 2: MUTASI KEINGINAN SENDIRI (MENDEKATI KELUARGA) -->
                <div id="boxMateriPribadi___INDEX__" class="col-12 d-none">
                    <div class="p-3 bg-light rounded border" style="border-color: #6f42c1 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold small" style="color: #6f42c1;"><i class="fas fa-user-friends me-1"></i>Data Mutasi Permintaan Sendiri (Mendekati Keluarga & Biaya Mandiri)</div>
                            <span class="badge text-white" style="background-color: #6f42c1 !important; font-size: 10px;">Khusus Keinginan Sendiri</span>
                        </div>
                        <div class="alert alert-info py-1.5 px-2.5 mb-2.5 small" style="font-size: 11px;">
                            <i class="fas fa-info-circle me-1"></i>Dilengkapi surat pernyataan bermaterai kesanggupan biaya mandiri, Litmas PK Bapas, Berita Acara (BA) Sidang TPP UPT asal, dan jaminan keluarga.
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Tarik Usulan dari Modul Mutasi UPT (Opsional):</label>
                            <select name="materis[__INDEX__][mutasi_id]" class="form-select form-select-sm" onchange="autoFillMutasiPribadiRepeater(this, __INDEX__)">
                                <option value="">-- Pilih Usulan Mutasi UPT --</option>
                                @foreach($mutasiList as $mu)
                                <option value="{{ $mu->id }}"
                                    data-nama="{{ $mu->keterangan ? Str::limit($mu->keterangan, 50) : 'WBP Usulan Mutasi ' . ($mu->uptAsal?->nama_upt ?? '') }}"
                                    data-uptasal="{{ $mu->upt_asal_id }}"
                                    data-upttujuan="{{ $mu->upt_tujuan_id }}"
                                    data-upttujuannama="{{ $mu->uptTujuan?->nama_upt ?? '' }}"
                                    data-alasan="{{ $mu->kategori_mutasi ?? $mu->keterangan }}">
                                    {{ $mu->nomor_surat }} &bull; {{ $mu->uptAsal?->nama_upt }} &rarr; {{ $mu->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }} ({{ $mu->total_usulan }} Orang)
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Nama Narapidana / WBP <span class="text-danger">*</span></label>
                                <input type="text" name="materis[__INDEX__][mutasi_wbp_nama]" id="mutasi_pribadi_wbp_nama___INDEX__" class="form-control form-control-sm" placeholder="Nama narapidana">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Nomor Register</label>
                                <input type="text" name="materis[__INDEX__][mutasi_nomor_register]" class="form-control form-control-sm" placeholder="BI.124/2025">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                <input type="text" name="materis[__INDEX__][mutasi_perkara]" class="form-control form-control-sm" placeholder="Narkotika Ps 114">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                <input type="text" name="materis[__INDEX__][mutasi_lama_pidana]" class="form-control form-control-sm" placeholder="5 Tahun">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">UPT Asal</label>
                                <select name="materis[__INDEX__][mutasi_upt_asal_id]" id="mutasi_pribadi_upt_asal_id___INDEX__" class="form-select form-select-sm">
                                    <option value="">-- Pilih UPT Asal --</option>
                                    @foreach($uptList as $u)
                                    <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">UPT Tujuan</label>
                                <input type="text" name="materis[__INDEX__][mutasi_upt_tujuan]" id="mutasi_pribadi_upt_tujuan_id___INDEX__" class="form-control form-control-sm border-primary" placeholder="Contoh: Lapas Kelas I Cipinang / Lapas Besi Nusakambangan" list="listUptTujuan">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                <input type="text" name="materis[__INDEX__][mutasi_alasan]" id="mutasi_pribadi_alasan___INDEX__" class="form-control form-control-sm" value="Permintaan sendiri mendekati keluarga penjamin dengan kesanggupan pembiayaan mandiri">
                            </div>

                            <!-- Bagian Litmas & PK Bapas -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-balance-scale text-primary me-1"></i>Penelitian Kemasyarakatan (Litmas) & PK Bapas:</span>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Nama Petugas PK Bapas</label>
                                <input type="text" name="materis[__INDEX__][nama_pk_bapas]" class="form-control form-control-sm" placeholder="Nama PK Bapas">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">NIP / Kontak PK</label>
                                <input type="text" name="materis[__INDEX__][nip_pk_bapas]" class="form-control form-control-sm" placeholder="19850101...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Bapas Asal</label>
                                <input type="text" name="materis[__INDEX__][bapas_nama]" class="form-control form-control-sm" placeholder="Bapas Kelas I Serang">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Kesimpulan & Rekomendasi Litmas Bapas</label>
                                <input type="text" name="materis[__INDEX__][rekomendasi_litmas]" class="form-control form-control-sm" placeholder="Dapat direkomendasikan pemindahan mendekati domisili keluarga penjamin...">
                            </div>

                            <!-- Bagian BA Sidang TPP UPT -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-gavel text-warning-emphasis me-1"></i>Hasil Sidang TPP UPT Asal (Lapas/Rutan):</span>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Nomor Berita Acara (BA) UPT</label>
                                <input type="text" name="materis[__INDEX__][nomor_ba_tpp_upt]" class="form-control form-control-sm" placeholder="W12.PAS.1.PK.01...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Tanggal BA Sidang UPT</label>
                                <input type="date" name="materis[__INDEX__][tanggal_ba_tpp_upt]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Rekomendasi Sidang UPT</label>
                                <input type="text" name="materis[__INDEX__][rekomendasi_tpp_upt]" class="form-control form-control-sm" placeholder="Disetujui untuk diusulkan ke Kanwil">
                            </div>

                            <!-- Bagian Penjamin Keluarga -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-home text-success me-1"></i>Data Penjamin Keluarga:</span>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">NIK KTP Penjamin</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_nik]" class="form-control form-control-sm" placeholder="367101...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Hubungan</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Orang Tua / Saudara">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">No Kontak / WA</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_kontak]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Alamat Domisili Penjamin</label>
                                <input type="text" name="materis[__INDEX__][mutasi_penjamin_alamat]" class="form-control form-control-sm" placeholder="Alamat lengkap penjamin (sesuai KTP/KK)">
                            </div>

                            <!-- Unggah Berkas Persyaratan Khusus -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-primary me-1"></i>Unggah Berkas Persyaratan Khusus (PDF/Doc/Zip/Gambar maks 15MB):</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-file-signature text-primary me-1"></i>Surat Pernyataan Mandiri Bermaterai (Biaya Sendiri)</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_pernyataan_pribadi]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-file-contract text-warning me-1"></i>Berita Acara (BA) Sidang TPP UPT Bertandatangan</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_ba_upt]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Laporan Hasil Litmas Bapas</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_litmas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Surat Jaminan Penjamin Keluarga (Bermaterai)</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_jaminan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-id-card text-info me-1"></i>KTP & Kartu Keluarga Penjamin</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_ktp_penjamin]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-book text-secondary me-1"></i>Buku Register F (Bebas Disiplin)</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_reg_f]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1"><i class="fas fa-heartbeat text-danger me-1"></i>Surat Kesehatan Dokter (Fit to Travel)</label>
                                <input type="file" name="materis[__INDEX__][file_mutasi_kesehatan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-FORM 3: IZIN BEROBAT KELUAR KOTA -->
                <div id="boxMateriBerobat___INDEX__" class="col-12 d-none">
                    <div class="p-3 bg-light rounded border border-danger border-opacity-50">
                        <div class="fw-bold text-danger small mb-2"><i class="fas fa-heartbeat me-1"></i>Data Usulan Izin Berobat Keluar Kota</div>
                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Nama Pasien / WBP <span class="text-danger">*</span></label>
                                <input type="text" name="materis[__INDEX__][berobat_wbp_nama]" class="form-control form-control-sm" placeholder="Nama narapidana">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Nomor Register</label>
                                <input type="text" name="materis[__INDEX__][berobat_nomor_register]" class="form-control form-control-sm" placeholder="Nomor register">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Perkara / Pidana</label>
                                <input type="text" name="materis[__INDEX__][berobat_perkara]" class="form-control form-control-sm" placeholder="Pencurian Ps 363">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">UPT Asal Pengusul</label>
                                <select name="materis[__INDEX__][berobat_upt_asal_id]" class="form-select form-select-sm">
                                    <option value="">-- Pilih UPT Lapas / Rutan Asal --</option>
                                    @foreach($uptList as $u)
                                    <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Diagnosa Penyakit / Resume Medis <span class="text-danger">*</span></label>
                                <input type="text" name="materis[__INDEX__][berobat_diagnosa]" class="form-control form-control-sm" placeholder="Contoh: Ca Colon Stadium III...">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Dokter Penanggung Jawab</label>
                                <input type="text" name="materis[__INDEX__][berobat_dokter]" class="form-control form-control-sm" placeholder="dr. Sp.B">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Rumah Sakit Rujukan Luar Kota <span class="text-danger">*</span></label>
                                <input type="text" name="materis[__INDEX__][berobat_rs_nama]" class="form-control form-control-sm" placeholder="RSUPN Dr. Cipto Mangunkusumo">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Kota / Domisili RS</label>
                                <input type="text" name="materis[__INDEX__][berobat_rs_kota]" class="form-control form-control-sm" placeholder="Jakarta Pusat">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Penjamin Keluarga</label>
                                <input type="text" name="materis[__INDEX__][berobat_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Hubungan</label>
                                <input type="text" name="materis[__INDEX__][berobat_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Anak">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                <input type="text" name="materis[__INDEX__][berobat_penjamin_kontak]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                            </div>

                            <!-- Unggah Berkas Medis -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-file-medical text-danger me-1"></i>Unggah Berkas Medis (Opsional):</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Resume Rekam Medis</label>
                                <input type="file" name="materis[__INDEX__][file_medis_rekam]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Surat Rujukan Dokter/RS</label>
                                <input type="file" name="materis[__INDEX__][file_medis_rujukan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Jaminan Pembiayaan</label>
                                <input type="file" name="materis[__INDEX__][file_medis_jaminan]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">BA Sidang TPP UPT</label>
                                <input type="file" name="materis[__INDEX__][file_medis_ba_upt]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-FORM 4: KLIEN PEMASYARAKATAN (BAPAS) -->
                <div id="boxMateriKlien___INDEX__" class="col-12 d-none">
                    <div class="p-3 bg-light rounded border border-primary border-opacity-50">
                        <div class="fw-bold text-primary small mb-2"><i class="fas fa-passport me-1"></i>Data Usulan Klien Bapas (Izin Bepergian ke Luar Negeri)</div>
                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Nama Lengkap Klien <span class="text-danger">*</span></label>
                                <input type="text" name="materis[__INDEX__][klien_nama]" class="form-control form-control-sm" placeholder="Nama klien pemasyarakatan">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">BIN (Nama Ayah)</label>
                                <input type="text" name="materis[__INDEX__][klien_bin]" class="form-control form-control-sm" placeholder="Nama ayah">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Nomor Register Bapas</label>
                                <input type="text" name="materis[__INDEX__][klien_no_register]" class="form-control form-control-sm" placeholder="BPS-SRG/PB/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Bapas Pembimbing</label>
                                <select name="materis[__INDEX__][klien_bapas_nama]" class="form-select form-select-sm">
                                    <option value="Balai Pemasyarakatan Kelas I Serang">Bapas Kelas I Serang</option>
                                    <option value="Balai Pemasyarakatan Kelas II Ciangir">Bapas Kelas II Ciangir</option>
                                    <option value="Bapas Lainnya">Bapas Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Program Integrasi</label>
                                <input type="text" name="materis[__INDEX__][klien_program]" class="form-control form-control-sm" value="Pembebasan Bersyarat (PB)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Nama Pembimbing (PK)</label>
                                <input type="text" name="materis[__INDEX__][klien_nama_pk]" class="form-control form-control-sm" placeholder="Nama PK Bapas">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Keperluan Bepergian</label>
                                <input type="text" name="materis[__INDEX__][klien_keperluan_ln]" class="form-control form-control-sm" value="Menjalankan Ibadah Umroh">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Negara Tujuan</label>
                                <input type="text" name="materis[__INDEX__][klien_negara_tujuan]" class="form-control form-control-sm" value="Kerajaan Arab Saudi">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Tgl Berangkat</label>
                                <input type="date" name="materis[__INDEX__][klien_tgl_berangkat]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Tgl Kembali</label>
                                <input type="date" name="materis[__INDEX__][klien_tgl_kembali]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                <input type="text" name="materis[__INDEX__][klien_penjamin_nama]" class="form-control form-control-sm" placeholder="Nama penjamin">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Hubungan</label>
                                <input type="text" name="materis[__INDEX__][klien_penjamin_hubungan]" class="form-control form-control-sm" placeholder="Istri / Saudara">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Telepon Penjamin</label>
                                <input type="text" name="materis[__INDEX__][klien_penjamin_telepon]" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                            </div>

                            <!-- Unggah Berkas Klien Bapas -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-primary me-1"></i>Unggah Berkas Klien Bapas (Opsional):</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Litmas Bapas</label>
                                <input type="file" name="materis[__INDEX__][file_litmas_bapas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Surat Kepala Bapas</label>
                                <input type="file" name="materis[__INDEX__][file_surat_bapas]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Bukti Tiket / Jadwal Travel</label>
                                <input type="file" name="materis[__INDEX__][file_bukti_travel]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Surat Jaminan Bermaterai</label>
                                <input type="file" name="materis[__INDEX__][file_surat_jaminan]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload File Pokok Bahan Sidang & Dokumen Tambahan Lainnya -->
                <div class="col-12 col-md-6 mt-2">
                    <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-file-download text-primary me-1"></i>Bahan Materi Sidang Utama (Opsional)</label>
                    <input type="file" name="materis[__INDEX__][file_bahan_sidang]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                    <div class="form-text text-muted" style="font-size: 11px;">Format: PDF, Word, Excel, ZIP (Maks. 15MB)</div>
                </div>
                <div class="col-12 col-md-6 mt-2">
                    <div class="p-2 bg-white rounded border">
                        <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-paperclip text-secondary me-1"></i>Dokumen Tambahan Lainnya (Opsional)</label>
                        <div class="row g-1">
                            <div class="col-12 col-sm-5">
                                <input type="text" name="materis[__INDEX__][dokumen_tambahan_nama]" class="form-control form-control-sm" placeholder="Nama Dokumen (Kustom)">
                            </div>
                            <div class="col-12 col-sm-7">
                                <input type="file" name="materis[__INDEX__][dokumen_tambahan_file]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<datalist id="listUptTujuan">
    @foreach($uptList as $u)
    <option value="{{ $u->nama_upt }}"></option>
    @endforeach
</datalist>

@push('scripts')
<script>
    let materiCount = 1;

    function switchMateriRepeaterCategory(index, val) {
        const boxMutasi = document.getElementById('boxMateriMutasi_' + index);
        const boxPribadi = document.getElementById('boxMateriPribadi_' + index);
        const boxBerobat = document.getElementById('boxMateriBerobat_' + index);
        const boxKlien = document.getElementById('boxMateriKlien_' + index);

        if (boxMutasi) boxMutasi.classList.add('d-none');
        if (boxPribadi) boxPribadi.classList.add('d-none');
        if (boxBerobat) boxBerobat.classList.add('d-none');
        if (boxKlien) boxKlien.classList.add('d-none');

        if (val === 'mutasi_keinginan_sendiri' && boxPribadi) {
            boxPribadi.classList.remove('d-none');
        } else if (val === 'mutasi_narapidana' && boxMutasi) {
            boxMutasi.classList.remove('d-none');
        } else if (val === 'izin_berobat' && boxBerobat) {
            boxBerobat.classList.remove('d-none');
        } else if (val === 'klien_bapas' && boxKlien) {
            boxKlien.classList.remove('d-none');
        }
    }

    function autoFillMutasiRepeater(selectEl, index) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) return;
        const nama = opt.getAttribute('data-nama') || '';
        const uptAsal = opt.getAttribute('data-uptasal') || '';
        const uptTujuan = opt.getAttribute('data-upttujuan') || '';
        const uptTujuanNama = opt.getAttribute('data-upttujuannama') || '';
        const alasan = opt.getAttribute('data-alasan') || '';

        const namaEl = document.getElementById('mutasi_wbp_nama_' + index);
        const uptAsalEl = document.getElementById('mutasi_upt_asal_id_' + index);
        const uptTujuanEl = document.getElementById('upt_tujuan_rekomendasi_id_' + index);
        const alasanEl = document.getElementById('mutasi_alasan_' + index);
        const judulEl = document.getElementById('materi_judul_' + index);

        if (namaEl && nama && !namaEl.value) namaEl.value = nama;
        if (uptAsalEl && uptAsal) uptAsalEl.value = uptAsal;
        if (uptTujuanEl) uptTujuanEl.value = uptTujuanNama || uptTujuan;
        if (alasanEl && alasan && !alasanEl.value) alasanEl.value = alasan;
        if (judulEl && (!judulEl.value || judulEl.value.trim() === '')) {
            judulEl.value = 'Usulan Pemindahan Narapidana an. ' + nama;
        }
    }

    function autoFillMutasiPribadiRepeater(selectEl, index) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) return;
        const nama = opt.getAttribute('data-nama') || '';
        const uptAsal = opt.getAttribute('data-uptasal') || '';
        const uptTujuan = opt.getAttribute('data-upttujuan') || '';
        const uptTujuanNama = opt.getAttribute('data-upttujuannama') || '';
        const alasan = opt.getAttribute('data-alasan') || '';

        const namaEl = document.getElementById('mutasi_pribadi_wbp_nama_' + index);
        const uptAsalEl = document.getElementById('mutasi_pribadi_upt_asal_id_' + index);
        const uptTujuanEl = document.getElementById('mutasi_pribadi_upt_tujuan_id_' + index);
        const alasanEl = document.getElementById('mutasi_pribadi_alasan_' + index);
        const judulEl = document.getElementById('materi_judul_' + index);

        if (namaEl && nama && !namaEl.value) namaEl.value = nama;
        if (uptAsalEl && uptAsal) uptAsalEl.value = uptAsal;
        if (uptTujuanEl) uptTujuanEl.value = uptTujuanNama || uptTujuan;
        if (alasanEl && alasan && !alasanEl.value) alasanEl.value = alasan;
        if (judulEl && (!judulEl.value || judulEl.value.trim() === '')) {
            judulEl.value = 'Permohonan Pemindahan Mandiri (Mendekati Keluarga) an. ' + nama;
        }
    }

    function tambahMateriItem() {
        const tmpl = document.getElementById('materiTemplate').innerHTML;
        const nextIdx = materiCount;
        materiCount++;

        const rendered = tmpl.replace(/__INDEX__/g, nextIdx).replace(/__NUM__/g, materiCount);
        const container = document.getElementById('materiContainer');
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = rendered.trim();
        const newCard = tempDiv.firstChild;
        container.appendChild(newCard);

        updateMateriNumbers();
    }

    function removeMateriItem(index) {
        const card = document.getElementById('cardMateri_' + index);
        if (card) {
            card.remove();
            updateMateriNumbers();
        }
    }

    function updateMateriNumbers() {
        const items = document.querySelectorAll('.card-materi-item');
        items.forEach((item, idx) => {
            const badge = item.querySelector('.materi-badge-urutan');
            if (badge) {
                badge.textContent = 'Materi #' + (idx + 1);
            }
            const btnHapus = item.querySelector('.btn-hapus-materi');
            if (btnHapus) {
                btnHapus.style.display = items.length > 1 ? 'inline-block' : 'none';
            }
        });
    }

    function toggleMetodeDaring(metode) {
        const box = document.getElementById('boxDaringFields');
        if (metode === 'daring' || metode === 'hybrid') {
            box.classList.remove('d-none');
        } else {
            box.classList.add('d-none');
        }
    }

    function handleKategoriChange(val) {
        const isBapas = val.includes('Klien') || val.includes('Luar Negeri') || val.includes('Bapas') || val.includes('Umroh') || val.includes('Berobat');
        const toggle = document.getElementById('checkIsKlienBapas');
        if (isBapas) {
            toggle.checked = true;
            toggleKlienBapasFields(true);
        }
    }

    function toggleKlienBapasFields(show) {
        const box = document.getElementById('boxKlienBapasFields');
        if (show) {
            box.classList.remove('d-none');
        } else {
            box.classList.add('d-none');
        }
    }

    function hitungDurasiHari() {
        const d1 = document.getElementById('inputTglBerangkat').value;
        const d2 = document.getElementById('inputTglKembali').value;
        if (d1 && d2) {
            const start = new Date(d1);
            const end = new Date(d2);
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            if (diffDays > 0) {
                document.getElementById('inputDurasiHari').value = diffDays;
            }
        }
    }

    function selectAllAnggota(checked) {
        const checkboxes = document.querySelectorAll('.anggota-checkbox');
        checkboxes.forEach(cb => cb.checked = checked);
    }

    function filterAnggotaList() {
        const query = document.getElementById('cariAnggotaInput').value.toLowerCase();
        const items = document.querySelectorAll('.anggota-item');
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            const jabatan = item.getAttribute('data-jabatan');
            if (name.includes(query) || jabatan.includes(query)) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    }
</script>
@endpush
@endsection
