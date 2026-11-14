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

                <!-- Card 2: Nota Dinas & Agenda -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                        <span class="badge bg-info-subtle text-info p-2" style="border-radius: 8px;">
                            <i class="fas fa-file-contract"></i>
                        </span>
                        <h6 class="fw-bold mb-0 text-dark">2. Nota Dinas Undangan &amp; Agenda Sidang</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                <label class="form-label small fw-bold text-dark">Nomor Nota Dinas <span class="badge bg-secondary-subtle text-secondary py-0.5 px-1.5" style="font-size: 10px;">Input Manual</span></label>
                                <input type="text" name="nomor_nota_dinas" class="form-control form-control-sm" value="{{ old('nomor_nota_dinas', $suggestedNomorNota) }}" placeholder="Contoh: W.12-UM.01.01-1245">
                                <small class="text-muted" style="font-size: 11px;">Nomor surat nota dinas undangan dinas</small>
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

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Kategori Agenda <span class="text-danger">*</span></label>
                                <select name="agenda_kategori" id="selectAgendaKategori" class="form-select form-select-sm" required onchange="handleKategoriChange(this.value)">
                                    <optgroup label="🏢 Warga Binaan Pemasyarakatan (Lapas / Rutan)">
                                        <option value="Usulan Mutasi Narapidana Antar-UPT" {{ old('agenda_kategori') == 'Usulan Mutasi Narapidana Antar-UPT' ? 'selected' : '' }}>Usulan Mutasi Narapidana Antar-UPT</option>
                                        <option value="Usulan PB/CB/CMB Kasus Khusus" {{ old('agenda_kategori') == 'Usulan PB/CB/CMB Kasus Khusus' ? 'selected' : '' }}>Usulan PB/CB/CMB Kasus Khusus</option>
                                        <option value="Rekomendasi Asimilasi Pihak Ketiga" {{ old('agenda_kategori') == 'Rekomendasi Asimilasi Pihak Ketiga' ? 'selected' : '' }}>Rekomendasi Asimilasi Pihak Ketiga</option>
                                        <option value="Pembinaan Khusus Anak (LPKA)" {{ old('agenda_kategori') == 'Pembinaan Khusus Anak (LPKA)' ? 'selected' : '' }}>Pembinaan Khusus Anak (LPKA)</option>
                                        <option value="Permohonan Izin Luar Biasa" {{ old('agenda_kategori') == 'Permohonan Izin Luar Biasa' ? 'selected' : '' }}>Permohonan Izin Luar Biasa</option>
                                        <option value="Evaluasi Keamanan & Ketertiban" {{ old('agenda_kategori') == 'Evaluasi Keamanan & Ketertiban' ? 'selected' : '' }}>Evaluasi Keamanan & Ketertiban</option>
                                    </optgroup>
                                    <optgroup label="⚖️ Klien Pemasyarakatan (Balai Pemasyarakatan / Bapas)">
                                        <option value="Izin Bepergian ke Luar Negeri untuk Beribadah (Umroh / Haji)" {{ old('agenda_kategori') == 'Izin Bepergian ke Luar Negeri untuk Beribadah (Umroh / Haji)' ? 'selected' : '' }}>Izin Bepergian ke Luar Negeri untuk Beribadah (Umroh / Haji)</option>
                                        <option value="Izin Bepergian ke Luar Negeri untuk Berobat (Alasan Medis Mendesak)" {{ old('agenda_kategori') == 'Izin Bepergian ke Luar Negeri untuk Berobat (Alasan Medis Mendesak)' ? 'selected' : '' }}>Izin Bepergian ke Luar Negeri untuk Berobat (Alasan Medis Mendesak)</option>
                                        <option value="Izin Bepergian ke Luar Negeri untuk Pendidikan / Kemanusiaan" {{ old('agenda_kategori') == 'Izin Bepergian ke Luar Negeri untuk Pendidikan / Kemanusiaan' ? 'selected' : '' }}>Izin Bepergian ke Luar Negeri untuk Pendidikan / Kemanusiaan</option>
                                        <option value="Pindah Bimbingan Klien Luar Wilayah / Luar Negeri" {{ old('agenda_kategori') == 'Pindah Bimbingan Klien Luar Wilayah / Luar Negeri' ? 'selected' : '' }}>Pindah Bimbingan Klien Luar Wilayah / Luar Negeri</option>
                                        <option value="Rekomendasi Pencabutan Hak Integrasi Klien" {{ old('agenda_kategori') == 'Rekomendasi Pencabutan Hak Integrasi Klien' ? 'selected' : '' }}>Rekomendasi Pencabutan Hak Integrasi Klien</option>
                                    </optgroup>
                                    <optgroup label="Lainnya">
                                        <option value="Lainnya" {{ old('agenda_kategori') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Judul Agenda Pembahasan <span class="text-danger">*</span></label>
                                <input type="text" name="agenda_judul" id="inputAgendaJudul" class="form-control form-control-sm @error('agenda_judul') is-invalid @enderror" value="{{ old('agenda_judul') }}" required placeholder="Contoh: Permohonan Izin Umroh Klien PB Bapas Kelas I Serang an. H. Ahmad Fauzi">
                            </div>

                            <!-- Toggle Checklist Khusus Klien Bapas -->
                            <div class="col-12">
                                <div class="form-check form-switch bg-primary-subtle p-2 px-3 rounded border border-primary-subtle">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_klien_bapas" value="1" id="checkIsKlienBapas" {{ old('is_klien_bapas') ? 'checked' : '' }} onchange="toggleKlienBapasFields(this.checked)">
                                    <label class="form-check-label fw-bold text-primary small" for="checkIsKlienBapas" style="cursor: pointer;">
                                        <i class="fas fa-passport me-1"></i>Agenda Perkara Klien Pemasyarakatan (Bapas) / Izin Bepergian ke Luar Negeri
                                    </label>
                                    <div class="small text-muted" style="font-size: 11px;">Aktifkan untuk menampilkan formulir detail Klien Bapas, data penjamin keluarga, dan unggah berkas Litmas.</div>
                                </div>
                            </div>

                            <!-- SUB-FORM DINAMIS KLIEN BAPAS -->
                            <div id="boxKlienBapasFields" class="col-12 {{ old('is_klien_bapas') ? '' : 'd-none' }}">
                                <div class="p-3 bg-light rounded border border-primary border-opacity-25 shadow-sm">
                                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                        <span class="badge bg-primary text-white p-1.5" style="border-radius: 6px;"><i class="fas fa-user-shield"></i></span>
                                        <h6 class="fw-bold mb-0 text-primary" style="font-size: 13.5px;">Data Substantif Klien Pemasyarakatan (Bapas)</h6>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-12 col-md-7">
                                            <label class="form-label small fw-bold text-dark mb-1">Nama Lengkap Klien <span class="text-danger">*</span></label>
                                            <input type="text" name="klien_nama" class="form-control form-control-sm" value="{{ old('klien_nama') }}" placeholder="Contoh: H. Ahmad Fauzi">
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <label class="form-label small fw-bold text-dark mb-1">BIN (Nama Ayah)</label>
                                            <input type="text" name="klien_bin" class="form-control form-control-sm" value="{{ old('klien_bin') }}" placeholder="Contoh: Zulkarnain">
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1">Nomor Register Bapas</label>
                                            <input type="text" name="klien_no_register" class="form-control form-control-sm" value="{{ old('klien_no_register') }}" placeholder="Contoh: BPS-SRG/PB/2026/042">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1">Balai Pemasyarakatan (Bapas) Pembimbing</label>
                                            <select name="klien_bapas_nama" class="form-select form-select-sm">
                                                <option value="Balai Pemasyarakatan Kelas I Serang" {{ old('klien_bapas_nama') == 'Balai Pemasyarakatan Kelas I Serang' ? 'selected' : '' }}>Balai Pemasyarakatan Kelas I Serang</option>
                                                <option value="Balai Pemasyarakatan Kelas II Ciangir" {{ old('klien_bapas_nama') == 'Balai Pemasyarakatan Kelas II Ciangir' ? 'selected' : '' }}>Balai Pemasyarakatan Kelas II Ciangir</option>
                                                <option value="Balai Pemasyarakatan Lainnya" {{ old('klien_bapas_nama') == 'Balai Pemasyarakatan Lainnya' ? 'selected' : '' }}>Balai Pemasyarakatan Lainnya</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold text-dark mb-1">Program Integrasi</label>
                                            <select name="klien_program" class="form-select form-select-sm">
                                                <option value="Pembebasan Bersyarat (PB)" {{ old('klien_program') == 'Pembebasan Bersyarat (PB)' ? 'selected' : '' }}>Pembebasan Bersyarat (PB)</option>
                                                <option value="Cuti Menjelang Bebas (CMB)" {{ old('klien_program') == 'Cuti Menjelang Bebas (CMB)' ? 'selected' : '' }}>Cuti Menjelang Bebas (CMB)</option>
                                                <option value="Cuti Bersyarat (CB)" {{ old('klien_program') == 'Cuti Bersyarat (CB)' ? 'selected' : '' }}>Cuti Bersyarat (CB)</option>
                                                <option value="Asimilasi Rumah" {{ old('klien_program') == 'Asimilasi Rumah' ? 'selected' : '' }}>Asimilasi Rumah</option>
                                                <option value="Bimbingan Klien Anak" {{ old('klien_program') == 'Bimbingan Klien Anak' ? 'selected' : '' }}>Bimbingan Klien Anak</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold text-dark mb-1">Nama PK Bapas</label>
                                            <input type="text" name="klien_nama_pk" class="form-control form-control-sm" value="{{ old('klien_nama_pk') }}" placeholder="Nama Pembimbing Kemasyarakatan">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold text-dark mb-1">Keperluan Bepergian</label>
                                            <select name="klien_keperluan_ln" id="selectKeperluanLn" class="form-select form-select-sm">
                                                <option value="Menjalankan Ibadah Umroh" {{ old('klien_keperluan_ln') == 'Menjalankan Ibadah Umroh' ? 'selected' : '' }}>Menjalankan Ibadah Umroh</option>
                                                <option value="Menjalankan Ibadah Haji" {{ old('klien_keperluan_ln') == 'Menjalankan Ibadah Haji' ? 'selected' : '' }}>Menjalankan Ibadah Haji</option>
                                                <option value="Berobat Medis Mendesak" {{ old('klien_keperluan_ln') == 'Berobat Medis Mendesak' ? 'selected' : '' }}>Berobat Medis Mendesak</option>
                                                <option value="Pendidikan / Pelatihan" {{ old('klien_keperluan_ln') == 'Pendidikan / Pelatihan' ? 'selected' : '' }}>Pendidikan / Pelatihan</option>
                                                <option value="Kemanusiaan / Keluarga Sakit Keras" {{ old('klien_keperluan_ln') == 'Kemanusiaan / Keluarga Sakit Keras' ? 'selected' : '' }}>Kemanusiaan / Keluarga Sakit Keras</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold text-dark mb-1">Negara Tujuan</label>
                                            <input type="text" name="klien_negara_tujuan" id="inputNegaraTujuan" class="form-control form-control-sm" value="{{ old('klien_negara_tujuan', 'Kerajaan Arab Saudi') }}" placeholder="Contoh: Arab Saudi">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold text-dark mb-1">Tgl Keberangkatan</label>
                                            <input type="date" name="klien_tgl_berangkat" id="inputTglBerangkat" class="form-control form-control-sm" value="{{ old('klien_tgl_berangkat') }}" onchange="hitungDurasiHari()">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small fw-bold text-dark mb-1">Tgl Kepulangan</label>
                                            <input type="date" name="klien_tgl_kembali" id="inputTglKembali" class="form-control form-control-sm" value="{{ old('klien_tgl_kembali') }}" onchange="hitungDurasiHari()">
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <label class="form-label small fw-bold text-dark mb-1">Durasi (Hari)</label>
                                            <input type="number" name="klien_durasi_hari" id="inputDurasiHari" class="form-control form-control-sm" value="{{ old('klien_durasi_hari', 14) }}">
                                        </div>

                                        <!-- Surat Pengantar Bapas -->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1">Nomor Surat Kepala Bapas</label>
                                            <input type="text" name="klien_nomor_surat_bapas" class="form-control form-control-sm" value="{{ old('klien_nomor_surat_bapas') }}" placeholder="Contoh: WP.12.PAS.BAPAS-124">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1">Tanggal Surat Kepala Bapas</label>
                                            <input type="date" name="klien_tanggal_surat_bapas" class="form-control form-control-sm" value="{{ old('klien_tanggal_surat_bapas', date('Y-m-d')) }}">
                                        </div>

                                        <!-- Data Penjamin -->
                                        <div class="col-12 col-md-5">
                                            <label class="form-label small fw-bold text-dark mb-1">Nama Penjamin Keluarga</label>
                                            <input type="text" name="klien_penjamin_nama" class="form-control form-control-sm" value="{{ old('klien_penjamin_nama') }}" placeholder="Nama keluarga penjamin">
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label class="form-label small fw-bold text-dark mb-1">Hubungan Penjamin</label>
                                            <select name="klien_penjamin_hubungan" class="form-select form-select-sm">
                                                <option value="Istri / Suami" {{ old('klien_penjamin_hubungan') == 'Istri / Suami' ? 'selected' : '' }}>Istri / Suami</option>
                                                <option value="Orang Tua Kandung" {{ old('klien_penjamin_hubungan') == 'Orang Tua Kandung' ? 'selected' : '' }}>Orang Tua Kandung</option>
                                                <option value="Anak Kandung" {{ old('klien_penjamin_hubungan') == 'Anak Kandung' ? 'selected' : '' }}>Anak Kandung</option>
                                                <option value="Saudara Kandung" {{ old('klien_penjamin_hubungan') == 'Saudara Kandung' ? 'selected' : '' }}>Saudara Kandung</option>
                                                <option value="Wali / Penjamin Sah" {{ old('klien_penjamin_hubungan') == 'Wali / Penjamin Sah' ? 'selected' : '' }}>Wali / Penjamin Sah</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold text-dark mb-1">No. Kontak / WA Penjamin</label>
                                            <input type="text" name="klien_penjamin_telepon" class="form-control form-control-sm" value="{{ old('klien_penjamin_telepon') }}" placeholder="08xxxxxxxxxx">
                                        </div>

                                        <!-- Unggah Berkas Klien Bapas -->
                                        <div class="col-12 col-md-6 mt-2">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Berkas Litmas Bapas</label>
                                            <input type="file" name="file_litmas_bapas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-12 col-md-6 mt-2">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Surat Permohonan Kepala Bapas</label>
                                            <input type="file" name="file_surat_bapas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-ticket-alt text-primary me-1"></i>Bukti Tiket / Jadwal Travel / Rujukan RS</label>
                                            <input type="file" name="file_bukti_travel" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Surat Pernyataan Jaminan Bermaterai</label>
                                            <input type="file" name="file_surat_jaminan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Uraian / Deskripsi Pokok Agenda</label>
                                <textarea name="agenda_deskripsi" id="inputAgendaDeskripsi" class="form-control form-control-sm" rows="3" placeholder="Jelaskan pokok bahasan, latar belakang usulan, dan dasar pertimbangan hukum...">{{ old('agenda_deskripsi') }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Unggah Berkas Bahan Sidang Tambahan (Opsional)</label>
                                <input type="file" name="file_bahan_sidang" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                                <small class="text-muted" style="font-size: 11px;">Format: PDF, Word, Excel, atau ZIP (Maksimal 15 MB). Bahan ini dapat diunduh oleh anggota melalui portal sidang.</small>
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

@push('scripts')
<script>
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
