@extends('layouts.app')

@section('title', 'Pengaturan Instansi - Sultan Banten')
@section('page_title')
    <i class="fas fa-cog me-2"></i>Pengaturan Instansi
@endsection

@section('content')
<div class="content-card">
    <div class="mb-4">
        <h5 class="mb-1 fw-bold"><i class="fas fa-building text-primary me-2"></i>Pengaturan Instansi & Tampilan</h5>
        <p class="text-muted small mb-0">Ubah identitas instansi, batas jam input harian, running text pengumuman, dan aset logo/stempel</p>
    </div>

    @if(!auth()->user()->canManage('instansi'))
    <div class="alert alert-secondary d-flex align-items-center mb-3 py-2">
        <i class="fas fa-lock me-2 text-secondary"></i>
        <span>Anda memiliki hak akses <strong>Lihat Saja</strong> pada modul Pengaturan Instansi. Data tidak dapat diubah.</span>
    </div>
    @endif

    <form method="POST" action="{{ route('instansi.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Informasi Umum -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white py-2.5 fw-semibold small">
                        <i class="fas fa-info-circle me-1"></i> Informasi Instansi
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nama_instansi" class="form-label small fw-semibold">Nama Instansi <span class="text-danger">*</span></label>
                            <input type="text" id="nama_instansi" name="nama_instansi" class="form-control form-control-sm" value="{{ $instansi->nama_instansi }}" aria-label="Nama Instansi" required>
                        </div>
                        <div class="mb-3">
                            <label for="alamat_instansi" class="form-label small fw-semibold">Alamat Kantor</label>
                            <textarea id="alamat_instansi" name="alamat" class="form-control form-control-sm" rows="3" aria-label="Alamat Kantor">{{ $instansi->alamat }}</textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="telepon_instansi" class="form-label small fw-semibold">Telepon</label>
                                <input type="text" id="telepon_instansi" name="telepon" class="form-control form-control-sm" value="{{ $instansi->telepon }}" aria-label="Nomor Telepon">
                            </div>
                            <div class="col-6">
                                <label for="email_instansi" class="form-label small fw-semibold">Email</label>
                                <input type="email" id="email_instansi" name="email" class="form-control form-control-sm" value="{{ $instansi->email }}" aria-label="Email Instansi">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="website_instansi" class="form-label small fw-semibold">Website</label>
                            <input type="text" id="website_instansi" name="website" class="form-control form-control-sm" value="{{ $instansi->website }}" aria-label="Website Instansi">
                        </div>
                        <div class="mb-3">
                            <label for="running_text" class="form-label small fw-semibold">Running Text (Marquee Bar Bawah)</label>
                            <textarea id="running_text" name="running_text" class="form-control form-control-sm" rows="3" placeholder="Teks berjalan di footer dashboard" aria-label="Running Text">{{ $instansi->running_text }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pembatasan Jam & Aset Gambar -->
            <div class="col-lg-6">
                <!-- Pembatasan Jam -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark py-2.5 fw-semibold small">
                        <i class="fas fa-clock me-1"></i> Batasan Waktu Input Operator
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="batas_jam_aktif" id="batas_jam_aktif" {{ $instansi->batas_jam_aktif ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="batas_jam_aktif">Aktifkan Pembatasan Jam Input Harian</label>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="jam_mulai" class="form-label small">Jam Mulai</label>
                                <input type="time" id="jam_mulai" name="jam_mulai" class="form-control form-control-sm" value="{{ substr($instansi->jam_mulai ?? '06:00:00', 0, 5) }}" aria-label="Jam Mulai">
                            </div>
                            <div class="col-6">
                                <label for="jam_selesai" class="form-label small">Jam Selesai</label>
                                <input type="time" id="jam_selesai" name="jam_selesai" class="form-control form-control-sm" value="{{ substr($instansi->jam_selesai ?? '08:00:00', 0, 5) }}" aria-label="Jam Selesai">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size: 11px;">Jika aktif, operator UPT hanya dapat menginput data pada jam yang ditentukan. Admin tetap dapat input kapan saja.</small>
                    </div>
                </div>

                <!-- Logo & Favicon -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white py-2.5 fw-semibold small">
                        <i class="fas fa-image me-1"></i> Logo & Ikon
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="logo_file" class="form-label small fw-semibold">Logo Instansi</label>
                                @if($instansi->logo)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->logo) }}" alt="Logo" class="img-thumbnail" style="max-height: 50px; width: auto; height: 50px;" width="50" height="50">
                                    <button type="submit" name="delete_logo" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus logo?')" aria-label="Hapus Logo"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" id="logo_file" name="logo" class="form-control form-control-sm" accept="image/*" aria-label="Upload Logo Instansi">
                            </div>
                            <div class="col-sm-6">
                                <label for="favicon_file" class="form-label small fw-semibold">Favicon</label>
                                @if($instansi->favicon)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->favicon) }}" alt="Favicon" class="img-thumbnail" style="max-height: 32px; width: auto; height: 32px;" width="32" height="32">
                                    <button type="submit" name="delete_favicon" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus favicon?')" aria-label="Hapus Favicon"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" id="favicon_file" name="favicon" class="form-control form-control-sm" accept="image/*" aria-label="Upload Favicon">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dokumen Laporan (TTD, Stempel & Kop Surat) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-2.5 fw-semibold small">
                        <i class="fas fa-stamp me-1"></i> Aset Laporan (TTD, Stempel & Kop Surat)
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="ttd_file" class="form-label small fw-semibold">Gambar Tanda Tangan (TTD)</label>
                                @if($instansi->ttd_image)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->ttd_image) }}" alt="TTD" class="img-thumbnail" style="max-height: 50px; width: auto; height: 50px;" width="100" height="50">
                                    <button type="submit" name="delete_ttd_image" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus TTD?')" aria-label="Hapus TTD"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" id="ttd_file" name="ttd_image" class="form-control form-control-sm" accept="image/*" aria-label="Upload Tanda Tangan">
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Format PNG transparan dianjurkan</small>
                            </div>
                            <div class="col-sm-6">
                                <label for="stempel_file" class="form-label small fw-semibold">Gambar Stempel Resmi</label>
                                @if($instansi->stempel_image)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->stempel_image) }}" alt="Stempel" class="img-thumbnail" style="max-height: 50px; width: auto; height: 50px;" width="50" height="50">
                                    <button type="submit" name="delete_stempel_image" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus Stempel?')" aria-label="Hapus Stempel"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" id="stempel_file" name="stempel_image" class="form-control form-control-sm" accept="image/*" aria-label="Upload Stempel Resmi">
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Format PNG transparan dianjurkan</small>
                            </div>
                            <div class="col-sm-12">
                                <label for="logo_kop_file" class="form-label small fw-semibold">Logo Kop Surat Laporan</label>
                                @if($instansi->logo_kop)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->logo_kop) }}" alt="Logo Kop" class="img-thumbnail" style="max-height: 50px; width: auto; height: 50px;" width="100" height="50">
                                    <button type="submit" name="delete_logo_kop" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus Logo Kop?')" aria-label="Hapus Logo Kop"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" id="logo_kop_file" name="logo_kop" class="form-control form-control-sm" accept="image/*" aria-label="Upload Logo Kop Surat">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banner Popup -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-secondary text-white py-2.5 fw-semibold small">
                        <i class="fas fa-image me-1"></i> Welcome Banner (Popup)
                    </div>
                    <div class="card-body">
                        @if($instansi->welcome_banner)
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <img src="{{ asset($instansi->welcome_banner) }}" alt="Banner" class="img-thumbnail" style="max-height: 50px; width: auto; height: 50px;" width="100" height="50">
                            <button type="submit" name="delete_welcome_banner" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus banner?')" aria-label="Hapus Banner"><i class="fas fa-trash"></i></button>
                        </div>
                        @endif
                        <input type="file" id="welcome_banner_file" name="welcome_banner" class="form-control form-control-sm" accept="image/*" aria-label="Upload Welcome Banner">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            @if(auth()->user()->canManage('instansi'))
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-1"></i> Simpan Pengaturan
            </button>
            @else
            <button type="button" class="btn btn-secondary px-4" disabled>
                <i class="fas fa-lock me-1"></i> Mode Lihat Saja
            </button>
            @endif
        </div>
    </form>
</div>
@endsection
