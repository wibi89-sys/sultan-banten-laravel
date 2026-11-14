@extends('layouts.app')

@section('title', 'Detail Sidang TPP Kanwil - ' . $sidang->nomor_sidang)
@section('page_title', 'Detail Sidang TPP Kanwil')

@section('content')
<div class="content-wrapper">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 12px;">
                    <li class="breadcrumb-item"><a href="{{ route('sidang-tpp-kanwil.index') }}" class="text-decoration-none">Sidang TPP Kanwil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $sidang->nomor_sidang }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fas fa-university text-primary me-2"></i>{{ $sidang->nomor_sidang }}
            </h4>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Sebar Undangan & Link WA Sekali Klik -->
            <form action="{{ route('sidang-tpp-kanwil.broadcast-wa', $sidang->id) }}" method="POST" onsubmit="return confirm('Kirim notifikasi undangan resmi dan link portal pengisian pendapat ke seluruh tim sidang (Ketua, Sekretaris, dan Anggota) melalui WhatsApp?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm fw-bold" style="border-radius: 20px;">
                    <i class="fab fa-whatsapp me-1"></i> Sebar Link WA ke Tim Sidang
                </button>
            </form>

            <!-- Dropdown Cetak Dokumen -->
            <div class="dropdown">
                <button class="btn btn-primary btn-sm px-3 shadow-sm dropdown-toggle fw-bold" style="border-radius: 20px;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-print me-1"></i> Cetak Dokumen Fisik
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: 13px;">
                    <li class="dropdown-header text-uppercase fw-bold" style="font-size: 10px;">Berita Acara (BA) &amp; Disposisi Fisik</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'pdf']) }}" target="_blank">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Cetak Berita Acara (PDF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'word']) }}">
                            <i class="fas fa-file-word text-primary me-2"></i>Unduh Berita Acara (Word .doc)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header text-uppercase fw-bold" style="font-size: 10px;">Nota Dinas Undangan</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-nota', [$sidang->id, 'pdf']) }}" target="_blank">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Cetak Nota Dinas (PDF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-nota', [$sidang->id, 'word']) }}">
                            <i class="fas fa-file-word text-primary me-2"></i>Unduh Nota Dinas (Word .doc)
                        </a>
                    </li>
                </ul>
            </div>

            <a href="{{ route('sidang-tpp-kanwil.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 20px;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Main Overview Card -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size: 11px;">
                            {{ $sidang->agenda_kategori }}
                        </span>
                        {!! $sidang->status_badge !!}
                        {!! $sidang->tanggapan_badge !!}
                    </div>
                    <h5 class="fw-bold mb-2 text-dark" style="font-size: 18px;">{{ $sidang->agenda_judul }}</h5>
                    @if($sidang->agenda_deskripsi)
                    <p class="text-secondary small mb-3">{{ $sidang->agenda_deskripsi }}</p>
                    @endif

                    <div class="d-flex flex-wrap gap-4 small text-muted">
                        <div>
                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                            <b>{{ $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-' }}</b>
                        </div>
                        <div>
                            <i class="fas fa-clock text-info me-1"></i>
                            Pukul <b>{{ substr($sidang->jam_mulai, 0, 5) }} s/d {{ substr($sidang->jam_selesai, 0, 5) }} WIB</b>
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            <b>{{ $sidang->tempat }}</b> ({{ ucfirst($sidang->metode) }})
                        </div>
                        <div>
                            <i class="fas fa-file-alt text-secondary me-1"></i>
                            Nota: <b>{{ $sidang->nomor_nota_dinas ?: '-' }}</b>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 border-start-lg ps-lg-4">
                    <div class="p-3 bg-light rounded border">
                        <div class="fw-bold text-dark small mb-2"><i class="fas fa-user-tie text-primary me-1"></i>Pimpinan Sidang</div>
                        <div class="small mb-1">
                            <span class="text-muted">Ketua:</span> <b>{{ $sidang->ketua_nama }}</b>
                            <div class="text-secondary" style="font-size: 11px;">{{ $sidang->ketua_jabatan }}</div>
                        </div>
                        <div class="small">
                            <span class="text-muted">Sekretaris:</span> <b>{{ $sidang->sekretaris_nama }}</b>
                            <div class="text-secondary" style="font-size: 11px;">{{ $sidang->sekretaris_jabatan }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Deliberation Metric Cards -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid #10b981 !important; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-success fw-bold small text-uppercase" style="font-size: 11px;">Setuju</span>
                    <i class="fas fa-check-circle text-success fs-5"></i>
                </div>
                <div class="fs-3 fw-bold text-success font-speedometer">{{ $sidang->total_setuju }}</div>
                <small class="text-muted" style="font-size: 10.5px;">Anggota menyetujui penuh</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: rgba(6, 182, 212, 0.08); border-left: 4px solid #06b6d4 !important; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-info fw-bold small text-uppercase" style="font-size: 11px;">Setuju Catatan</span>
                    <i class="fas fa-clipboard-check text-info fs-5"></i>
                </div>
                <div class="fs-3 fw-bold text-info font-speedometer">{{ $sidang->total_setuju_catatan }}</div>
                <small class="text-muted" style="font-size: 10.5px;">Setuju dengan syarat</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: rgba(245, 158, 11, 0.08); border-left: 4px solid #f59e0b !important; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-warning-emphasis fw-bold small text-uppercase" style="font-size: 11px;">Tunda</span>
                    <i class="fas fa-pause-circle text-warning fs-5"></i>
                </div>
                <div class="fs-3 fw-bold text-warning-emphasis font-speedometer">{{ $sidang->total_tunda }}</div>
                <small class="text-muted" style="font-size: 10.5px;">Perlu pendalaman data</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: rgba(239, 68, 68, 0.08); border-left: 4px solid #ef4444 !important; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-danger fw-bold small text-uppercase" style="font-size: 11px;">Tolak</span>
                    <i class="fas fa-times-circle text-danger fs-5"></i>
                </div>
                <div class="fs-3 fw-bold text-danger font-speedometer">{{ $sidang->total_tolak }}</div>
                <small class="text-muted" style="font-size: 10.5px;">Tidak memenuhi syarat</small>
            </div>
        </div>
    </div>

    <!-- Nav Tabs: Daftar Hadir & Pendapat VS Pemberkasan Fisik -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="sidangTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold py-2.5 px-3" id="tab-anggota-btn" data-bs-toggle="tab" data-bs-target="#tab-anggota" type="button" role="tab">
                <i class="fas fa-users text-primary me-2"></i>Daftar Hadir &amp; Pendapat Tim ({{ $totalAnggota }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold py-2.5 px-3" id="tab-fisik-btn" data-bs-toggle="tab" data-bs-target="#tab-fisik" type="button" role="tab">
                <i class="fas fa-stamp text-danger me-2"></i>Pemberkasan Fisik &amp; Disposisi Kakanwil
                @if($sidang->file_ba_signed && $sidang->file_disposisi_kakanwil)
                <span class="badge bg-success ms-1">Lengkap</span>
                @elseif($sidang->file_ba_signed)
                <span class="badge bg-warning ms-1">Menunggu Kakanwil</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content" id="sidangTabsContent">
        <!-- TAB 1: DAFTAR HADIR & PENDAPAT ANGGOTA -->
        <div class="tab-pane fade show active" id="tab-anggota" role="tabpanel">
            <div class="card border-0 shadow-sm" style="background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-user-check text-success me-2"></i>Presensi Kehadiran &amp; Lembar Pendapat Deliberasi</h6>
                        <small class="text-muted">Presensi: <b>{{ $totalHadir }}</b> Hadir dari {{ $totalAnggota }} Anggota &bull; Pendapat terisi: <b>{{ $totalSudahPendapat }}</b> Orang</small>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark border py-1.5 px-2">
                            <i class="fab fa-whatsapp text-success me-1"></i>Undangan Terkirim: {{ $totalWaTerkirim }}/{{ $totalAnggota }}
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead style="background: rgba(148, 163, 184, 0.08); border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
                            <tr>
                                <th class="text-center py-3" style="width: 4%;">No</th>
                                <th class="py-3" style="width: 25%;">Nama Anggota &amp; Jabatan</th>
                                <th class="py-3" style="width: 12%;">Kedudukan</th>
                                <th class="py-3" style="width: 14%;">Presensi Kehadiran</th>
                                <th class="py-3" style="width: 23%;">Pendapat &amp; Pertimbangan</th>
                                <th class="text-center py-3" style="width: 22%;">Status WA &amp; Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($anggotaList as $idx => $a)
                            <tr>
                                <td class="text-center text-muted fw-bold">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $a->nama }}</div>
                                    @if($a->nip)
                                    <div class="text-muted small" style="font-size: 11px;">NIP. {{ $a->nip }}</div>
                                    @endif
                                    <small class="text-secondary d-block mt-0.5">{{ $a->jabatan }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border fw-bold" style="font-size: 11px;">
                                        {{ $a->peran_sidang }}
                                    </span>
                                </td>
                                <td>
                                    <div class="mb-1">{!! $a->kehadiran_badge !!}</div>
                                    @if($a->waktu_kehadiran)
                                    <small class="text-muted d-block" style="font-size: 10.5px;">
                                        <i class="fas fa-clock me-1"></i>{{ $a->waktu_kehadiran->format('d/m/Y H:i') }}
                                    </small>
                                    @endif
                                    @if(!empty($a->keterangan_kehadiran))
                                    <small class="text-secondary d-block fst-italic">"{{ $a->keterangan_kehadiran }}"</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="mb-1">{!! $a->pendapat_badge !!}</div>
                                    @if(!empty($a->catatan_pendapat))
                                    <div class="p-2 bg-light rounded small border mt-1" style="font-size: 11.5px; line-height: 1.35;">
                                        "{{ $a->catatan_pendapat }}"
                                    </div>
                                    @endif
                                    @if($a->waktu_pendapat)
                                    <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                        Diisi: {{ $a->waktu_pendapat->format('d/m/Y H:i') }}
                                    </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="mb-2">
                                        {!! $a->wa_badge !!}
                                        @if(!empty($a->no_wa))
                                        <div class="text-muted small mt-0.5" style="font-size: 10.5px;">{{ $a->no_wa }}</div>
                                        @else
                                        <div class="text-danger small mt-0.5" style="font-size: 10px;">Nomor WA Kosong</div>
                                        @endif
                                    </div>

                                    <div class="btn-group btn-group-sm">
                                        <!-- Kirim Ulang WA Single -->
                                        @if(!empty($a->no_wa))
                                        <form action="{{ route('sidang-tpp-kanwil.broadcast-wa-single', [$sidang->id, $a->id]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm py-0.5 px-2" title="Kirim/Kirim Ulang Undangan WA ke {{ $a->nama }}">
                                                <i class="fab fa-whatsapp"></i> Kirim WA
                                            </button>
                                        </form>
                                        @endif

                                        <!-- Salin Link Portal Khusus -->
                                        @php
                                            $portalUrl = route('sidang-tpp-kanwil.portal-anggota', $a->token_akses);
                                        @endphp
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2" onclick="copyToClipboard('{{ $portalUrl }}', 'Tautan portal pengisian khusus {{ $a->nama }} berhasil disalin!')" title="Salin link formulir pengisian khusus untuk dikirim via WA Japri/Desktop">
                                            <i class="fas fa-copy"></i> Salin Link
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada anggota sidang yang ditugaskan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: PEMBERKASAN FISIK & DISPOSISI KAKANWIL -->
        <div class="tab-pane fade" id="tab-fisik" role="tabpanel">
            <div class="row g-4">
                <!-- Kolom Kiri: Panduan & Generator Dokumen Siap Cetak -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary p-2" style="border-radius: 8px;">
                                <i class="fas fa-print"></i>
                            </span>
                            <h6 class="fw-bold mb-0 text-dark">Tahap 1: Cetak Berkas Fisik</h6>
                        </div>
                        <div class="card-body p-4">
                            <p class="small text-muted mb-3" style="line-height: 1.5;">
                                Sistem telah menyusun <b>Berita Acara (BA) Sidang TPP Kanwil</b> yang memuat rekapitulasi kehadiran, pendapat tim, matriks tanda tangan manual basah, dan lembar tanggapan/petunjuk Kepala Kantor Wilayah.
                            </p>
                            <div class="d-grid gap-2 mb-3">
                                <a href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'pdf']) }}" target="_blank" class="btn btn-primary btn-sm py-2 fw-bold">
                                    <i class="fas fa-file-pdf me-2"></i>Buka &amp; Cetak Berita Acara (PDF)
                                </a>
                                <a href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'word']) }}" class="btn btn-outline-primary btn-sm py-2">
                                    <i class="fas fa-file-word me-2"></i>Unduh Format Word (.doc)
                                </a>
                            </div>

                            <div class="p-3 bg-light rounded border small">
                                <div class="fw-bold text-dark mb-1"><i class="fas fa-info-circle text-primary me-1"></i>Alur Tanda Tangan Basah:</div>
                                <ol class="mb-0 ps-3 text-muted" style="line-height: 1.45;">
                                    <li>Cetak dokumen Berita Acara di atas pada kertas F4/A4.</li>
                                    <li>Lakukan penandatanganan manual basah oleh Ketua, Sekretaris, dan Anggota sidang.</li>
                                    <li>Serahkan berkas kepada Kepala Kantor Wilayah untuk memperoleh tanggapan/disposisi fisik dan tanda tangan.</li>
                                    <li>Scan dokumen yang sudah lengkap bertanda tangan, lalu unggah pada form di samping.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Upload Berkas Fisik & Disposisi Kakanwil -->
                <div class="col-lg-7">
                    <!-- Card Upload Berita Acara Bertanda Tangan Basah -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info-subtle text-info p-2" style="border-radius: 8px;">
                                    <i class="fas fa-file-signature"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Tahap 2: Unggah Berita Acara Fisik Bertanda Tangan</h6>
                            </div>
                            @if($sidang->file_ba_signed)
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diunggah</span>
                            @else
                            <span class="badge bg-secondary">Belum Diunggah</span>
                            @endif
                        </div>
                        <div class="card-body p-4">
                            @if($sidang->file_ba_signed)
                            <div class="alert alert-success border-0 p-3 mb-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    <div>
                                        <div class="fw-bold small text-dark">Berkas Scan Berita Acara Bertanda Tangan Basah</div>
                                        <small class="text-muted">Nomor BA: {{ $sidang->nomor_berita_acara ?: '-' }}</small>
                                    </div>
                                </div>
                                <a href="{{ asset($sidang->file_ba_signed) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-external-link-alt me-1"></i>Lihat Berkas
                                </a>
                            </div>
                            @endif

                            <form action="{{ route('sidang-tpp-kanwil.upload-ba-signed', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Nomor Berita Acara Sah <span class="text-danger">*</span></label>
                                        <input type="text" name="nomor_berita_acara" class="form-control form-control-sm" value="{{ old('nomor_berita_acara', $sidang->nomor_berita_acara ?: ('BA-TPP-KW/'.date('Y/m').'/'.str_pad($sidang->id, 3, '0', STR_PAD_LEFT))) }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Unggah File Scan Berita Acara <span class="text-danger">*</span></label>
                                        <input type="file" name="file_ba_signed" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="text-muted" style="font-size: 11px;">Format PDF/Gambar (Maks. 15 MB)</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">Kesimpulan / Rekomendasi Akhir Sidang TPP</label>
                                        <textarea name="kesimpulan_sidang" class="form-control form-control-sm" rows="2" placeholder="Catat kesimpulan musyawarah sidang...">{{ old('kesimpulan_sidang', $sidang->kesimpulan_sidang) }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                                            <i class="fas fa-upload me-1"></i> Simpan &amp; Unggah Berita Acara Bertanda Tangan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card Tanggapan / Disposisi Fisik Kepala Kantor Wilayah -->
                    <div class="card border-0 shadow-sm" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger-subtle text-danger p-2" style="border-radius: 8px;">
                                    <i class="fas fa-stamp"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Tahap 3: Tanggapan &amp; Disposisi Fisik Kakanwil</h6>
                            </div>
                            {!! $sidang->tanggapan_badge !!}
                        </div>
                        <div class="card-body p-4">
                            @if($sidang->file_disposisi_kakanwil)
                            <div class="alert alert-info border-0 p-3 mb-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-pdf fa-2x text-primary"></i>
                                    <div>
                                        <div class="fw-bold small text-dark">Lembar Disposisi Fisik Bertanda Tangan Kakanwil</div>
                                        <small class="text-muted">Keputusan: <b>{{ ucfirst(str_replace('_', ' ', $sidang->tanggapan_kakanwil_status)) }}</b> &bull; Tgl: {{ $sidang->tanggapan_kakanwil_tanggal ? $sidang->tanggapan_kakanwil_tanggal->format('d/m/Y') : '-' }}</small>
                                    </div>
                                </div>
                                <a href="{{ asset($sidang->file_disposisi_kakanwil) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-external-link-alt me-1"></i>Lihat Disposisi
                                </a>
                            </div>
                            @endif

                            <form action="{{ route('sidang-tpp-kanwil.upload-disposisi-kakanwil', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Status Keputusan Kakanwil <span class="text-danger">*</span></label>
                                        <select name="tanggapan_kakanwil_status" class="form-select form-select-sm" required>
                                            <option value="disetujui" {{ old('tanggapan_kakanwil_status', $sidang->tanggapan_kakanwil_status) == 'disetujui' ? 'selected' : '' }}>Disetujui Penuh</option>
                                            <option value="disetujui_bersyarat" {{ old('tanggapan_kakanwil_status', $sidang->tanggapan_kakanwil_status) == 'disetujui_bersyarat' ? 'selected' : '' }}>Disetujui Bersyarat / Dengan Catatan</option>
                                            <option value="perlu_pendalaman" {{ old('tanggapan_kakanwil_status', $sidang->tanggapan_kakanwil_status) == 'perlu_pendalaman' ? 'selected' : '' }}>Perlu Pendalaman / Dibahas Ulang</option>
                                            <option value="ditolak" {{ old('tanggapan_kakanwil_status', $sidang->tanggapan_kakanwil_status) == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Tanggal Disposisi Kakanwil <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggapan_kakanwil_tanggal" class="form-control form-control-sm" value="{{ old('tanggapan_kakanwil_tanggal', $sidang->tanggapan_kakanwil_tanggal ? $sidang->tanggapan_kakanwil_tanggal->format('Y-m-d') : date('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Nama Kepala Kantor Wilayah <span class="text-danger">*</span></label>
                                        <input type="text" name="kakanwil_nama" class="form-control form-control-sm" value="{{ old('kakanwil_nama', $sidang->kakanwil_nama ?: 'DODOT ADIKOESWANTO, S.H., M.Si.') }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">NIP Kepala Kantor Wilayah</label>
                                        <input type="text" name="kakanwil_nip" class="form-control form-control-sm" value="{{ old('kakanwil_nip', $sidang->kakanwil_nip ?: '196811201991031001') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">Catatan / Arahan Tertulis Kakanwil</label>
                                        <textarea name="tanggapan_kakanwil_catatan" class="form-control form-control-sm" rows="3" placeholder="Tuliskan arahan resmi pimpinan dari lembar disposisi fisik...">{{ old('tanggapan_kakanwil_catatan', $sidang->tanggapan_kakanwil_catatan) }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">Unggah Scan Lembar Disposisi Bertanda Tangan</label>
                                        <input type="file" name="file_disposisi_kakanwil" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted" style="font-size: 11px;">Format PDF/Gambar (Maks. 15 MB)</small>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm fw-bold">
                                            <i class="fas fa-check-double me-1"></i> Simpan Disposisi &amp; Nyatakan Sidang Selesai
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function copyToClipboard(text, successMsg) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert(successMsg || 'Tautan berhasil disalin!');
            }).catch(err => {
                fallbackCopy(text, successMsg);
            });
        } else {
            fallbackCopy(text, successMsg);
        }
    }

    function fallbackCopy(text, successMsg) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            alert(successMsg || 'Tautan berhasil disalin!');
        } catch (err) {
            alert('Gagal menyalin tautan: ' + err);
        }
        document.body.removeChild(textArea);
    }
</script>
@endpush
@endsection
