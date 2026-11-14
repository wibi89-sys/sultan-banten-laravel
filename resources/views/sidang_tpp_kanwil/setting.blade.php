@extends('layouts.app')

@section('title', 'Pengaturan Sidang TPP Kanwil - Sultan Banten')

@section('page_title')
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-cogs text-primary"></i>
        <span>Pengaturan Sidang TPP Kantor Wilayah</span>
    </div>
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Header Page -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-sliders-h text-primary me-2"></i>Pengaturan Modul Sidang TPP Kanwil
            </h4>
            <p class="text-muted small mb-0">Kelola susunan tetap personil tim sidang, nomor WhatsApp pengiriman undangan, spesimen TTD Kakanwil &amp; stempel resmi, serta standarisasi format naskah dinas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sidang-tpp-kanwil.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Sidang
            </a>
            <a href="{{ route('sidang-tpp-kanwil.create') }}" class="btn btn-success btn-sm px-3 rounded-pill shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Registrasi Sidang Baru
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="fas fa-check-circle me-2 fs-5 align-middle"></i>
        <span>{{ session('success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Terjadi kesalahan pada input data:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Card Nav Tabs -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden; background: var(--bg-card);">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-fill border-0" id="tppSettingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-bold border-0 text-start px-4 d-flex align-items-center gap-2" id="tab-tim-btn" data-bs-toggle="tab" data-bs-target="#tab-tim" type="button" role="tab" aria-selected="true">
                        <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="fas fa-users-cog"></i></span>
                        <div>
                            <div class="text-dark">1. Susunan Tim Sidang TPP</div>
                            <small class="text-muted fw-normal" style="font-size: 11px;">Master Anggota, Peran Default &amp; Nomor WA</small>
                        </div>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 text-start px-4 d-flex align-items-center gap-2" id="tab-kakanwil-btn" data-bs-toggle="tab" data-bs-target="#tab-kakanwil" type="button" role="tab" aria-selected="false">
                        <span class="badge bg-warning-subtle text-warning-emphasis p-2 rounded-circle"><i class="fas fa-signature"></i></span>
                        <div>
                            <div class="text-dark">2. TTD Kakanwil &amp; Stempel</div>
                            <small class="text-muted fw-normal" style="font-size: 11px;">Spesimen Digital Kepala Kantor Wilayah</small>
                        </div>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 text-start px-4 d-flex align-items-center gap-2" id="tab-naskah-btn" data-bs-toggle="tab" data-bs-target="#tab-naskah" type="button" role="tab" aria-selected="false">
                        <span class="badge bg-info-subtle text-info p-2 rounded-circle"><i class="fas fa-file-alt"></i></span>
                        <div>
                            <div class="text-dark">3. Format Naskah &amp; Standar</div>
                            <small class="text-muted fw-normal" style="font-size: 11px;">Prefix Penomoran Surat, Tempat &amp; Dasar Hukum</small>
                        </div>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="tppSettingTabsContent">
                
                <!-- ================= TAB 1: MANAJEMEN TIM SIDANG TPP ================= -->
                <div class="tab-pane fade show active" id="tab-tim" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-users text-primary me-2"></i>Daftar Personil Tim Sidang TPP Kantor Wilayah</h6>
                            <p class="text-muted small mb-0">Personil yang aktif akan otomatis dimuat sebagai Ketua, Sekretaris, dan Anggota pada formulir registrasi sidang baru.</p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPegawai">
                            <i class="fas fa-user-plus me-1"></i> Tambah Personil Tim
                        </button>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
                                    <th class="text-center" style="width: 50px;">Urutan</th>
                                    <th>Nama Pegawai &amp; NIP</th>
                                    <th>Pangkat / Golongan</th>
                                    <th>Jabatan Kedinasan</th>
                                    <th>Nomor WhatsApp</th>
                                    <th class="text-center">Peran Tim TPP</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pegawaiList as $p)
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $p->urutan }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $p->nama }}</div>
                                        <small class="text-muted font-monospace">{{ $p->nip ?: 'NIP: -' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">{{ $p->pangkat_golongan ?: '-' }}</span>
                                    </td>
                                    <td class="small text-dark">{{ $p->jabatan }}</td>
                                    <td>
                                        @if($p->no_wa)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $p->no_wa) }}" target="_blank" class="badge bg-success-subtle text-success text-decoration-none px-2 py-1">
                                            <i class="fab fa-whatsapp me-1"></i>{{ $p->no_wa }}
                                        </a>
                                        @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($p->peran_tpp === 'ketua')
                                            <span class="badge bg-danger px-2.5 py-1.5"><i class="fas fa-crown me-1"></i>Ketua Sidang</span>
                                        @elseif($p->peran_tpp === 'sekretaris')
                                            <span class="badge bg-primary px-2.5 py-1.5"><i class="fas fa-pen-fancy me-1"></i>Sekretaris</span>
                                        @else
                                            <span class="badge bg-secondary px-2.5 py-1.5"><i class="fas fa-user-check me-1"></i>Anggota Tetap</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($p->is_active)
                                            <span class="badge bg-success-subtle text-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Non-aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm px-2" title="Edit Personil" onclick="editPegawai({{ json_encode($p) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('sidang-tpp-kanwil.setting.pegawai.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus personil {{ $p->nama }} dari tim sidang?')" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Hapus Personil">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-users-slash fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada personil Tim Sidang TPP Kanwil yang terdaftar. Silakan klik tombol <b>Tambah Personil Tim</b> di atas.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= TAB 2: PROFIL & SPESIMEN TTD KAKANWIL ================= -->
                <div class="tab-pane fade" id="tab-kakanwil" role="tabpanel">
                    <form action="{{ route('sidang-tpp-kanwil.setting.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            <!-- Kolom Kiri: Biodata Kakanwil -->
                            <div class="col-lg-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold mb-3 text-dark">
                                        <i class="fas fa-user-tie text-primary me-2"></i>Profil Kepala Kantor Wilayah
                                    </h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nama Kepala Kantor Wilayah <span class="text-danger">*</span></label>
                                        <input type="text" name="kakanwil_nama" class="form-control form-control-sm" value="{{ old('kakanwil_nama', $setting->kakanwil_nama) }}" required placeholder="Contoh: Dody Fahrudin, Bc.IP., S.H., M.H.">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">NIP Kepala Kantor Wilayah</label>
                                        <input type="text" name="kakanwil_nip" class="form-control form-control-sm font-monospace" value="{{ old('kakanwil_nip', $setting->kakanwil_nip) }}" placeholder="Contoh: 197205141994031001">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Pangkat / Golongan Ruang</label>
                                        <input type="text" name="kakanwil_pangkat" class="form-control form-control-sm" value="{{ old('kakanwil_pangkat', $setting->kakanwil_pangkat) }}" placeholder="Contoh: Pembina Utama Madya (IV/d)">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Jabatan Resmi <span class="text-danger">*</span></label>
                                        <input type="text" name="kakanwil_jabatan" class="form-control form-control-sm" value="{{ old('kakanwil_jabatan', $setting->kakanwil_jabatan) }}" required placeholder="Contoh: Kepala Kantor Wilayah">
                                    </div>

                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="auto_embed_ttd" id="auto_embed_ttd" value="1" {{ old('auto_embed_ttd', $setting->auto_embed_ttd) ? 'checked' : '' }} style="cursor: pointer; width: 2.5em; height: 1.3em;">
                                        <label class="form-check-label small fw-semibold text-dark ms-2" for="auto_embed_ttd">
                                            Sematkan Tanda Tangan Digital Kakanwil &amp; Stempel Resmi Secara Otomatis
                                        </label>
                                        <div class="text-muted" style="font-size: 11px;">Jika aktif, hasil cetak Berita Acara, Risalah, dan Laporan Pelaksanaan akan otomatis memuat TTD Kakanwil &amp; stempel pada file PDF ketika status disposisi disetujui.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Upload TTD Digital & Stempel -->
                            <div class="col-lg-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold mb-3 text-dark">
                                        <i class="fas fa-stamp text-danger me-2"></i>Spesimen Tanda Tangan &amp; Cap Stempel Resmi
                                    </h6>

                                    <!-- Upload TTD Digital -->
                                    <div class="mb-3 p-3 bg-white rounded border">
                                        <label class="form-label small fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fas fa-signature text-primary me-1"></i>Tanda Tangan Digital Kakanwil</span>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">Format PNG Transparan / WEBP</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-3 mt-2">
                                            <div class="border rounded p-1 bg-light d-flex align-items-center justify-content-center" style="width: 100px; height: 60px;">
                                                @if($setting->kakanwil_ttd_image && file_exists(public_path($setting->kakanwil_ttd_image)))
                                                    <img src="{{ asset($setting->kakanwil_ttd_image) }}" alt="TTD Kakanwil" style="max-width: 90px; max-height: 50px; object-fit: contain;">
                                                @else
                                                    <span class="text-muted small" style="font-size: 10px;">Belum Ada</span>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" name="kakanwil_ttd_image" class="form-control form-control-sm" accept=".png,.webp,.jpg,.jpeg">
                                                <small class="text-muted" style="font-size: 10.5px;">Unggah file gambar tanda tangan berlatar transparan (Maks. 2 MB)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Upload Stempel Resmi Kanwil -->
                                    <div class="mb-3 p-3 bg-white rounded border">
                                        <label class="form-label small fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fas fa-stamp text-danger me-1"></i>Cap Stempel Resmi Kanwil</span>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">Format PNG Transparan / WEBP</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-3 mt-2">
                                            <div class="border rounded p-1 bg-light d-flex align-items-center justify-content-center" style="width: 100px; height: 60px;">
                                                @if($setting->kanwil_stempel_image && file_exists(public_path($setting->kanwil_stempel_image)))
                                                    <img src="{{ asset($setting->kanwil_stempel_image) }}" alt="Stempel Kanwil" style="max-width: 90px; max-height: 50px; object-fit: contain;">
                                                @else
                                                    <span class="text-muted small" style="font-size: 10px;">Belum Ada</span>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" name="kanwil_stempel_image" class="form-control form-control-sm" accept=".png,.webp,.jpg,.jpeg">
                                                <small class="text-muted" style="font-size: 10.5px;">Unggah file cap stempel dinas Kanwil (Maks. 2 MB)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-0" style="font-size: 11px;">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Sistem tetap menyediakan opsi <b>Pemberkasan Fisik</b> jika kantor menghendaki tanda tangan basah fisik manual yang di-scan dan diunggah kembali ke aplikasi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan Profil &amp; Spesimen Kakanwil
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ================= TAB 3: FORMAT NASKAH & STANDAR LOKASI ================= -->
                <div class="tab-pane fade" id="tab-naskah" role="tabpanel">
                    <form action="{{ route('sidang-tpp-kanwil.setting.update') }}" method="POST">
                        @csrf
                        <div class="row g-4">
                            <!-- Prefix Penomoran Naskah -->
                            <div class="col-lg-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold mb-3 text-dark">
                                        <i class="fas fa-hashtag text-info me-2"></i>Prefix Standarisasi Penomoran Naskah Dinas
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Prefix Nomor Nota Dinas Undangan</label>
                                        <input type="text" name="nomor_nota_prefix" class="form-control form-control-sm font-monospace" value="{{ old('nomor_nota_prefix', $setting->nomor_nota_prefix) }}" placeholder="WP.12.UM.01.01-">
                                        <small class="text-muted" style="font-size: 11px;">Format awalan nomor surat nota dinas undangan</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Prefix Nomor Risalah Hasil Sidang TPP</label>
                                        <input type="text" name="nomor_risalah_prefix" class="form-control form-control-sm font-monospace" value="{{ old('nomor_risalah_prefix', $setting->nomor_risalah_prefix) }}" placeholder="WP.12.PK.06.05-">
                                        <small class="text-muted" style="font-size: 11px;">Format awalan nomor berkas risalah hasil sidang</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Prefix Nomor Laporan Pelaksanaan Sidang</label>
                                        <input type="text" name="nomor_laporan_prefix" class="form-control form-control-sm font-monospace" value="{{ old('nomor_laporan_prefix', $setting->nomor_laporan_prefix) }}" placeholder="WP.12.PK.06.06-">
                                        <small class="text-muted" style="font-size: 11px;">Format awalan nomor berkas laporan pelaksanaan sidang</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Tempat Sidang Standar (Fisik)</label>
                                        <input type="text" name="tempat_sidang_default" class="form-control form-control-sm" value="{{ old('tempat_sidang_default', $setting->tempat_sidang_default) }}" placeholder="Ruang Rapat Divisi Pemasyarakatan Kantor Wilayah Banten">
                                    </div>
                                </div>
                            </div>

                            <!-- Link Daring Default & Dasar Hukum -->
                            <div class="col-lg-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold mb-3 text-dark">
                                        <i class="fas fa-balance-scale text-warning me-2"></i>Dasar Hukum &amp; Ruang Sidang Daring
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Tautan Rapat Daring Standar (Zoom/Meet)</label>
                                        <input type="url" name="link_daring_default" class="form-control form-control-sm" value="{{ old('link_daring_default', $setting->link_daring_default) }}" placeholder="https://meet.google.com/...">
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small text-muted mb-1">Meeting ID Default</label>
                                            <input type="text" name="meeting_id_default" class="form-control form-control-sm" value="{{ old('meeting_id_default', $setting->meeting_id_default) }}" placeholder="892 4102 7711">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-muted mb-1">Passcode Default</label>
                                            <input type="text" name="passcode_default" class="form-control form-control-sm" value="{{ old('passcode_default', $setting->passcode_default) }}" placeholder="KANWIL2026">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark d-flex justify-content-between align-items-center">
                                            <span>Dasar Hukum Standar Sidang TPP Kanwil</span>
                                            <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" onclick="tambahDasarHukumRow()" style="font-size: 11px;">
                                                <i class="fas fa-plus me-1"></i> Tambah Butir
                                            </button>
                                        </label>
                                        <div id="containerDasarHukum" class="d-flex flex-column gap-2" style="max-height: 220px; overflow-y: auto;">
                                            @php
                                                $dasarList = $setting->dasar_hukum_json ?: \App\Models\TppKanwilSetting::defaultDasarHukum();
                                            @endphp
                                            @foreach($dasarList as $idx => $dh)
                                            <div class="input-group input-group-sm dasar-hukum-row">
                                                <input type="text" name="dasar_hukum[]" class="form-control form-control-sm" value="{{ $dh }}">
                                                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.dasar-hukum-row').remove()" title="Hapus Butir">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan Format Naskah &amp; Dasar Hukum
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL TAMBAH PERSONIL TIM ================= -->
<div class="modal fade" id="modalTambahPegawai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('sidang-tpp-kanwil.setting.pegawai.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Personil Tim Sidang TPP Kanwil</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Nama Pegawai Beserta Gelar <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control form-control-sm" required placeholder="Contoh: Surya Dharma, Bc.IP., S.H.">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">NIP Pegawai</label>
                        <input type="text" name="nip" class="form-control form-control-sm font-monospace" placeholder="197408151998031001">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">Pangkat / Golongan</label>
                        <input type="text" name="pangkat_golongan" class="form-control form-control-sm" placeholder="Pembina (IV/a)">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Jabatan Kedinasan <span class="text-danger">*</span></label>
                    <input type="text" name="jabatan" class="form-control form-control-sm" required placeholder="Contoh: Kepala Bidang Pelayanan Tahanan &amp; Pembinaan">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Nomor WhatsApp Aktif</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                        <input type="text" name="no_wa" class="form-control" placeholder="Contoh: 08123456789">
                    </div>
                    <small class="text-muted" style="font-size: 11px;">Digunakan untuk menerima notifikasi undangan sidang &amp; link portal.</small>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">Peran Tim TPP Kanwil <span class="text-danger">*</span></label>
                        <select name="peran_tpp" class="form-select form-select-sm" required>
                            <option value="anggota">Anggota Tetap</option>
                            <option value="ketua">Ketua Sidang</option>
                            <option value="sekretaris">Sekretaris Sidang</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">Urutan Penandatanganan</label>
                        <input type="number" name="urutan" class="form-control form-control-sm" value="{{ ($pegawaiList->max('urutan') ?? 0) + 1 }}" min="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                    <i class="fas fa-save me-1"></i> Simpan Personil
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL EDIT PERSONIL TIM ================= -->
<div class="modal fade" id="modalEditPegawai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditPegawai" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i>Edit Data Personil Tim TPP Kanwil</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Nama Pegawai Beserta Gelar <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">NIP Pegawai</label>
                        <input type="text" name="nip" id="edit_nip" class="form-control form-control-sm font-monospace">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">Pangkat / Golongan</label>
                        <input type="text" name="pangkat_golongan" id="edit_pangkat_golongan" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Jabatan Kedinasan <span class="text-danger">*</span></label>
                    <input type="text" name="jabatan" id="edit_jabatan" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Nomor WhatsApp Aktif</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                        <input type="text" name="no_wa" id="edit_no_wa" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">Peran Tim TPP Kanwil <span class="text-danger">*</span></label>
                        <select name="peran_tpp" id="edit_peran_tpp" class="form-select form-select-sm" required>
                            <option value="anggota">Anggota Tetap</option>
                            <option value="ketua">Ketua Sidang</option>
                            <option value="sekretaris">Sekretaris Sidang</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-dark">Urutan</label>
                        <input type="number" name="urutan" id="edit_urutan" class="form-control form-control-sm" min="1">
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1" style="cursor: pointer;">
                    <label class="form-check-label small fw-bold text-dark ms-1" for="edit_is_active">Status Aktif dalam Tim</label>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editPegawai(pegawai) {
        document.getElementById('edit_nama').value = pegawai.nama || '';
        document.getElementById('edit_nip').value = pegawai.nip || '';
        document.getElementById('edit_pangkat_golongan').value = pegawai.pangkat_golongan || '';
        document.getElementById('edit_jabatan').value = pegawai.jabatan || '';
        document.getElementById('edit_no_wa').value = pegawai.no_wa || '';
        document.getElementById('edit_peran_tpp').value = pegawai.peran_tpp || 'anggota';
        document.getElementById('edit_urutan').value = pegawai.urutan || 1;
        document.getElementById('edit_is_active').checked = !!pegawai.is_active;

        const form = document.getElementById('formEditPegawai');
        form.action = '/sidang-tpp-kanwil/setting/pegawai/' + pegawai.id;

        const modal = new bootstrap.Modal(document.getElementById('modalEditPegawai'));
        modal.show();
    }

    function tambahDasarHukumRow() {
        const container = document.getElementById('containerDasarHukum');
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm dasar-hukum-row';
        div.innerHTML = `
            <input type="text" name="dasar_hukum[]" class="form-control form-control-sm" placeholder="Contoh: Undang-Undang Nomor ...">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.dasar-hukum-row').remove()" title="Hapus Butir">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(div);
        div.querySelector('input').focus();
    }
</script>
@endpush
