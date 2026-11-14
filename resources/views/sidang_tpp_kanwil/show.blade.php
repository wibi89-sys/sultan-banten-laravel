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
                    <i class="fas fa-file-invoice me-1"></i> Berkas Naskah Dinas
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: 13px; min-width: 250px;">
                    <li class="dropdown-header text-uppercase fw-bold text-primary" style="font-size: 10.5px;"><i class="fas fa-file-contract me-1"></i>Risalah Hasil Sidang TPP</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-risalah', [$sidang->id, 'pdf']) }}" target="_blank">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Risalah Sidang (PDF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-risalah', [$sidang->id, 'word']) }}">
                            <i class="fas fa-file-word text-primary me-2"></i>Risalah Sidang (Word .doc)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header text-uppercase fw-bold text-success" style="font-size: 10.5px;"><i class="fas fa-file-signature me-1"></i>Laporan Pelaksanaan Sidang</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-laporan', [$sidang->id, 'pdf']) }}" target="_blank">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Laporan Pelaksanaan (PDF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-laporan', [$sidang->id, 'word']) }}">
                            <i class="fas fa-file-word text-primary me-2"></i>Laporan Pelaksanaan (Word .doc)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header text-uppercase fw-bold text-secondary" style="font-size: 10.5px;"><i class="fas fa-stamp me-1"></i>Berita Acara &amp; Disposisi Fisik</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'pdf']) }}" target="_blank">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Berita Acara (PDF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'word']) }}">
                            <i class="fas fa-file-word text-primary me-2"></i>Berita Acara (Word .doc)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header text-uppercase fw-bold text-secondary" style="font-size: 10.5px;"><i class="fas fa-envelope-open-text me-1"></i>Nota Dinas Undangan</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-nota', [$sidang->id, 'pdf']) }}" target="_blank">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Nota Dinas (PDF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('sidang-tpp-kanwil.download-nota', [$sidang->id, 'word']) }}">
                            <i class="fas fa-file-word text-primary me-2"></i>Nota Dinas (Word .doc)
                        </a>
                    </li>
                </ul>
            </div>

            <a href="{{ route('sidang-tpp-kanwil.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 20px;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>

            @if($sidang->canEdit(auth()->user()))
            <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm fw-bold" style="border-radius: 20px;" data-bs-toggle="modal" data-bs-target="#modalEditSidangKanwil">
                <i class="fas fa-edit me-1"></i> Edit Data Sidang
            </button>
            @endif

            <!-- Tombol Hapus Sidang -->
            @if($sidang->canDelete(auth()->user()))
            <form action="{{ route('sidang-tpp-kanwil.destroy', $sidang->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Sidang TPP Kanwil {{ $sidang->nomor_sidang }}? Tindakan ini tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm" style="border-radius: 20px;" title="Hapus Sidang">
                    <i class="fas fa-trash-alt me-1"></i> Hapus Sidang
                </button>
            </form>
            @endif
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
                        @if($sidang->isVerifiedKakanwil())
                        <span class="badge bg-secondary text-white" title="Sidang telah diverifikasi/disposisi final oleh Ka. Kanwil. Data terkunci bagi user biasa."><i class="fas fa-lock me-1"></i>Terkunci (Final Ka. Kanwil)</span>
                        @endif
                    </div>
                    <h5 class="fw-bold mb-2 text-dark" style="font-size: 18px;">{{ $sidang->agenda_judul }}</h5>
                    @if($sidang->agenda_deskripsi)
                    <p class="text-secondary small mb-3">{{ $sidang->agenda_deskripsi }}</p>
                    @endif



                    <!-- DAFTAR MATERI / AGENDA PERSIDANGAN -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white p-1.5" style="border-radius: 6px;"><i class="fas fa-layer-group"></i></span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 14.5px;">Daftar Materi / Agenda Sidang ({{ $sidang->materis->count() }})</h6>
                            </div>
                            @if($sidang->canEdit(auth()->user()))
                            <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2.5 fw-bold shadow-sm" style="border-radius: 14px; font-size: 11.5px;" data-bs-toggle="modal" data-bs-target="#modalTambahMateri">
                                <i class="fas fa-plus me-1"></i> Tambah Materi
                            </button>
                            @endif
                        </div>

                        @foreach($sidang->materis as $m)
                        <div class="card border border-2 mb-3 shadow-none" style="border-radius: 10px; background: #ffffff;">
                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark text-white font-mono-num" style="font-size: 11px;">Materi #{{ $m->urutan }}</span>
                                    {!! $m->kategori_badge !!}
                                    <span class="fw-bold text-dark" style="font-size: 13.5px;">{{ $m->judul_materi }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($m->rekomendasi_sidang == 'setuju')
                                    <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>Rekomendasi: Disetujui</span>
                                    @elseif($m->rekomendasi_sidang == 'setuju_catatan')
                                    <span class="badge bg-info text-white"><i class="fas fa-clipboard-check me-1"></i>Rekomendasi: Setuju dg Catatan</span>
                                    @elseif($m->rekomendasi_sidang == 'tunda')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-pause-circle me-1"></i>Rekomendasi: Ditunda</span>
                                    @elseif($m->rekomendasi_sidang == 'tolak')
                                    <span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>Rekomendasi: Ditolak</span>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-hourglass-start me-1"></i>Rekomendasi: Belum Diputuskan</span>
                                    @endif

                                    @if($sidang->canEdit(auth()->user()))
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#modalEditMateri_{{ $m->id }}">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    @if(!$sidang->isVerifiedKakanwil() && $sidang->materis->count() > 1)
                                    <form action="{{ route('sidang-tpp-kanwil.materi.destroy', [$sidang->id, $m->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus Materi #{{ $m->urutan }} ({{ addslashes($m->judul_materi) }})?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 11px;" title="Hapus materi">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-3">
                                @if($m->deskripsi_materi)
                                <p class="text-secondary small mb-2">{{ $m->deskripsi_materi }}</p>
                                @endif

                                @if($m->isMutasiKeinginanSendiri() && !empty($m->narapidana_data))
                                @php 
                                    $ndm = $m->narapidana_data; 
                                    $bm = $ndm['berkas'] ?? [];
                                @endphp
                                <div class="p-2.5 rounded border mb-2 small" style="background-color: #fbf9ff; border-color: #6f42c1 !important;">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1.5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge text-white" style="background-color: #6f42c1 !important;"><i class="fas fa-user-friends me-1"></i>Mutasi Permintaan Sendiri</span>
                                            <span class="fw-bold text-dark fs-6">{{ $ndm['nama'] ?? '-' }}</span>
                                            @if(!empty($ndm['nomor_register']))
                                            <code>{{ $ndm['nomor_register'] }}</code>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($m->mutasi_id)
                                            <a href="{{ route('mutasi-narapidana.show', $m->mutasi_id) }}" class="badge bg-primary text-white text-decoration-none py-1.5 px-2" target="_blank" title="Buka berkas di Modul Mutasi UPT">
                                                <i class="fas fa-external-link-alt me-1"></i>Terkoneksi ke Mutasi UPT #{{ $m->mutasi_id }}
                                            </a>
                                            @endif
                                            <span class="badge bg-light text-primary border border-primary-subtle">
                                                Rekomendasi Tujuan: <b>{{ $m->uptTujuanRekomendasi?->nama_upt ?? ($ndm['upt_tujuan_nama'] ?? 'Ditentukan Sidang') }}</b>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row g-2 small text-dark">
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Perkara / Vonis:</span> <b>{{ $ndm['tindak_pidana'] ?? '-' }}</b> ({{ $ndm['lama_pidana'] ?? '-' }})<br>
                                            <span class="text-muted">UPT Asal Pengusul:</span> <b>{{ $ndm['upt_asal_nama'] ?? '-' }}</b><br>
                                            <span class="text-muted">Rute:</span> <b>{{ $ndm['upt_asal_nama'] ?? 'Asal' }}</b> &rarr; <b class="text-primary">{{ $m->uptTujuanRekomendasi?->nama_upt ?? ($ndm['upt_tujuan_nama'] ?? '-') }}</b>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Petugas PK Bapas:</span> <b>{{ $ndm['nama_pk_bapas'] ?? '-' }}</b> {{ !empty($ndm['bapas_nama']) ? '('.$ndm['bapas_nama'].')' : '' }}<br>
                                            <span class="text-muted">Litmas Bapas:</span> <span class="text-dark">{{ $ndm['rekomendasi_litmas'] ?? '-' }}</span><br>
                                            <span class="text-muted">BA Sidang UPT:</span> <b>{{ $ndm['nomor_ba_tpp_upt'] ?? '-' }}</b> ({{ !empty($ndm['tanggal_ba_tpp_upt']) ? \Carbon\Carbon::parse($ndm['tanggal_ba_tpp_upt'])->format('d/m/Y') : '-' }})
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Penjamin:</span> <b>{{ $ndm['penjamin_nama'] ?? '-' }}</b> ({{ $ndm['penjamin_hubungan'] ?? '-' }})<br>
                                            <span class="text-muted">NIK Penjamin:</span> <code>{{ $ndm['penjamin_nik'] ?? '-' }}</code><br>
                                            <span class="text-muted">Kontak/WA:</span> <b>{{ $ndm['penjamin_kontak'] ?? '-' }}</b><br>
                                            <span class="text-muted">Alamat:</span> {{ $ndm['penjamin_alamat'] ?? '-' }}
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted">Alasan:</span> <span class="fst-italic text-dark">{{ $ndm['alasan_pemindahan'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                    @if(!empty($bm['file_pernyataan_pribadi']) || !empty($bm['file_ba_upt']) || !empty($bm['file_litmas']) || !empty($bm['file_jaminan']) || !empty($bm['file_ktp_penjamin']) || !empty($bm['file_reg_f']) || !empty($bm['file_kesehatan']) || !empty($bm['file_asesmen']) || !empty($bm['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                                    <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                                        @if(!empty($m->file_bahan_sidang))
                                        <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-download me-1"></i>Bahan Sidang
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_pernyataan_pribadi']))
                                        <a href="{{ asset($bm['file_pernyataan_pribadi']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px; color: #6f42c1; border-color: #6f42c1;">
                                            <i class="fas fa-file-signature me-1"></i>Pernyataan Mandiri (Biaya Sendiri)
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_ba_upt']))
                                        <a href="{{ asset($bm['file_ba_upt']) }}" target="_blank" class="btn btn-outline-warning btn-xs py-0.5 px-2 text-dark" style="font-size: 11px;">
                                            <i class="fas fa-file-contract me-1"></i>BA Sidang TPP UPT
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_litmas']))
                                        <a href="{{ asset($bm['file_litmas']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-pdf me-1"></i>Litmas Bapas
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_jaminan']))
                                        <a href="{{ asset($bm['file_jaminan']) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-shield-alt me-1"></i>Surat Jaminan
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_ktp_penjamin']))
                                        <a href="{{ asset($bm['file_ktp_penjamin']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-id-card me-1"></i>KTP/KK Penjamin
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_reg_f']))
                                        <a href="{{ asset($bm['file_reg_f']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-book me-1"></i>Register F
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_kesehatan']))
                                        <a href="{{ asset($bm['file_kesehatan']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-heartbeat me-1"></i>Surat Kesehatan
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_asesmen']))
                                        <a href="{{ asset($bm['file_asesmen']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-chart-line me-1"></i>Asesmen
                                        </a>
                                        @endif
                                        @if(!empty($bm['dokumen_tambahan']['file']))
                                        <a href="{{ asset($bm['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-dark btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-paperclip me-1"></i>{{ $bm['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @elseif($m->isMutasiNarapidana() && !empty($m->narapidana_data))
                                @php 
                                    $ndm = $m->narapidana_data; 
                                    $bm = $ndm['berkas'] ?? [];
                                @endphp
                                <div class="p-2.5 bg-warning-subtle bg-opacity-25 rounded border border-warning border-opacity-50 mb-2 small">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1.5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark"><i class="fas fa-exchange-alt me-1"></i>Mutasi Narapidana</span>
                                            <span class="fw-bold text-dark fs-6">{{ $ndm['nama'] ?? '-' }}</span>
                                            @if(!empty($ndm['nomor_register']))
                                            <code>{{ $ndm['nomor_register'] }}</code>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($m->mutasi_id)
                                            <a href="{{ route('mutasi-narapidana.show', $m->mutasi_id) }}" class="badge bg-primary text-white text-decoration-none py-1.5 px-2" target="_blank" title="Buka berkas di Modul Mutasi UPT">
                                                <i class="fas fa-external-link-alt me-1"></i>Terkoneksi ke Mutasi UPT #{{ $m->mutasi_id }}
                                            </a>
                                            @endif
                                            <span class="badge bg-light text-warning-emphasis border border-warning-subtle">
                                                Rekomendasi Tujuan: <b>{{ $m->uptTujuanRekomendasi?->nama_upt ?? ($ndm['upt_tujuan_nama'] ?? 'Ditentukan Sidang') }}</b>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row g-2 small text-dark">
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Perkara / Vonis:</span> <b>{{ $ndm['tindak_pidana'] ?? '-' }}</b> ({{ $ndm['lama_pidana'] ?? '-' }})<br>
                                            <span class="text-muted">UPT Asal Pengusul:</span> <b>{{ $ndm['upt_asal_nama'] ?? '-' }}</b>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Rute Pemindahan:</span> <b>{{ $ndm['upt_asal_nama'] ?? 'Asal' }}</b> &rarr; <b class="text-primary">{{ $m->uptTujuanRekomendasi?->nama_upt ?? ($ndm['upt_tujuan_nama'] ?? '-') }}</b><br>
                                            <span class="text-muted">Alasan:</span> <span class="fst-italic text-dark">{{ $ndm['alasan_pemindahan'] ?? '-' }}</span>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Penjamin:</span> <b>{{ $ndm['penjamin_nama'] ?? '-' }}</b> ({{ $ndm['penjamin_hubungan'] ?? '-' }})<br>
                                            <span class="text-muted">Kontak/WA:</span> <b>{{ $ndm['penjamin_kontak'] ?? '-' }}</b><br>
                                            <span class="text-muted">Alamat:</span> {{ $ndm['penjamin_alamat'] ?? '-' }}
                                        </div>
                                    </div>
                                    @if(!empty($bm['file_litmas']) || !empty($bm['file_permohonan']) || !empty($bm['file_jaminan']) || !empty($bm['file_reg_f']) || !empty($bm['file_asesmen']) || !empty($bm['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                                    <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                                        @if(!empty($m->file_bahan_sidang))
                                        <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-download me-1"></i>Bahan Sidang
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_litmas']))
                                        <a href="{{ asset($bm['file_litmas']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-pdf me-1"></i>Litmas Bapas
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_permohonan']))
                                        <a href="{{ asset($bm['file_permohonan']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-alt me-1"></i>Surat Permohonan
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_jaminan']))
                                        <a href="{{ asset($bm['file_jaminan']) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-shield-alt me-1"></i>Jaminan Keluarga
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_reg_f']))
                                        <a href="{{ asset($bm['file_reg_f']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-book me-1"></i>Register F
                                        </a>
                                        @endif
                                        @if(!empty($bm['file_asesmen']))
                                        <a href="{{ asset($bm['file_asesmen']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-chart-line me-1"></i>Hasil Asesmen
                                        </a>
                                        @endif
                                        @if(!empty($bm['dokumen_tambahan']['file']))
                                        <a href="{{ asset($bm['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-dark btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-paperclip me-1"></i>{{ $bm['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @elseif($m->isIzinBerobat() && !empty($m->narapidana_data))
                                @php 
                                    $ndm = $m->narapidana_data; 
                                    $bmed = $ndm['berkas'] ?? [];
                                @endphp
                                <div class="p-2.5 bg-danger-subtle bg-opacity-25 rounded border border-danger border-opacity-50 mb-2 small">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1.5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger text-white"><i class="fas fa-heartbeat me-1"></i>Izin Berobat Keluar Kota</span>
                                            <span class="fw-bold text-danger fs-6">{{ $ndm['nama'] ?? '-' }}</span>
                                            @if(!empty($ndm['nomor_register']))
                                            <code>{{ $ndm['nomor_register'] }}</code>
                                            @endif
                                        </div>
                                        <span class="badge bg-light text-danger border border-danger-subtle">
                                            <i class="fas fa-hospital me-1"></i>Rujukan: <b>{{ $ndm['rs_rujukan_nama'] ?? '-' }} ({{ $ndm['rs_rujukan_kota'] ?? '-' }})</b>
                                        </span>
                                    </div>
                                    <div class="row g-2 small text-dark">
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">UPT Asal Narapidana:</span> <b>{{ $ndm['upt_asal_nama'] ?? '-' }}</b><br>
                                            <span class="text-muted">Perkara / Vonis:</span> <b>{{ $ndm['tindak_pidana'] ?? '-' }}</b>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Diagnosa Medis:</span> <b class="text-danger">{{ $ndm['diagnosa_medis'] ?? '-' }}</b><br>
                                            <span class="text-muted">Dokter Penanggung Jawab:</span> <b>{{ $ndm['dokter_penanggung_jawab'] ?? '-' }}</b>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Penjamin / Keluarga:</span> <b>{{ $ndm['penjamin_nama'] ?? '-' }}</b> ({{ $ndm['penjamin_hubungan'] ?? '-' }})<br>
                                            <span class="text-muted">Kontak/WA:</span> <b>{{ $ndm['penjamin_kontak'] ?? '-' }}</b>
                                        </div>
                                    </div>
                                    @if(!empty($bmed['file_rekam_medis']) || !empty($bmed['file_rujukan']) || !empty($bmed['file_jaminan']) || !empty($bmed['file_ba_upt']) || !empty($bmed['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                                    <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                                        @if(!empty($m->file_bahan_sidang))
                                        <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-download me-1"></i>Bahan Sidang
                                        </a>
                                        @endif
                                        @if(!empty($bmed['file_rekam_medis']))
                                        <a href="{{ asset($bmed['file_rekam_medis']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-medical me-1"></i>Resume Rekam Medis
                                        </a>
                                        @endif
                                        @if(!empty($bmed['file_rujukan']))
                                        <a href="{{ asset($bmed['file_rujukan']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-hospital me-1"></i>Surat Rujukan Dokter/RSUD
                                        </a>
                                        @endif
                                        @if(!empty($bmed['file_ba_upt']))
                                        <a href="{{ asset($bmed['file_ba_upt']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-gavel me-1"></i>BA Sidang TPP UPT
                                        </a>
                                        @endif
                                        @if(!empty($bmed['file_jaminan']))
                                        <a href="{{ asset($bmed['file_jaminan']) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-shield-alt me-1"></i>Jaminan Pembiayaan Keluarga
                                        </a>
                                        @endif
                                        @if(!empty($bmed['dokumen_tambahan']['file']))
                                        <a href="{{ asset($bmed['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-dark btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-paperclip me-1"></i>{{ $bmed['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @elseif($m->isKlienBapas() && !empty($m->klien_data))
                                @php 
                                    $kdm = $m->klien_data; 
                                @endphp
                                <div class="p-2.5 bg-primary-subtle bg-opacity-25 rounded border border-primary border-opacity-50 mb-2 small">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white"><i class="fas fa-passport me-1"></i>Klien Pemasyarakatan</span>
                                            <span class="fw-bold text-primary">{{ $kdm['nama_klien'] ?? '-' }} {{ !empty($kdm['bin']) ? 'BIN ' . strtoupper($kdm['bin']) : '' }}</span>
                                        </div>
                                        <span class="badge bg-light text-dark border">{{ $kdm['program_integrasi'] ?? 'PB' }}</span>
                                    </div>
                                    <div class="row g-2 small text-dark">
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Bapas Asal:</span> <b>{{ $kdm['bapas_nama'] ?? '-' }}</b><br>
                                            <span class="text-muted">No. Register:</span> <code>{{ $kdm['no_register_bapas'] ?? '-' }}</code><br>
                                            <span class="text-muted">PK Bapas:</span> <b>{{ $kdm['nama_pk'] ?? '-' }}</b>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Keperluan LN:</span> <b>{{ $kdm['keperluan_ln'] ?? '-' }}</b><br>
                                            <span class="text-muted">Negara Tujuan:</span> <b><i class="fas fa-plane text-primary me-1"></i>{{ $kdm['negara_tujuan'] ?? '-' }}</b><br>
                                            <span class="text-muted">Jadwal:</span> <b>{{ !empty($kdm['tgl_berangkat']) ? \Carbon\Carbon::parse($kdm['tgl_berangkat'])->format('d/m/Y') : '-' }} s/d {{ !empty($kdm['tgl_kembali']) ? \Carbon\Carbon::parse($kdm['tgl_kembali'])->format('d/m/Y') : '-' }} ({{ $kdm['durasi_hari'] ?? '-' }} Hari)</b>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted">Penjamin:</span> <b>{{ $kdm['penjamin_nama'] ?? '-' }}</b> ({{ $kdm['penjamin_hubungan'] ?? '-' }})<br>
                                            <span class="text-muted">Kontak/WA:</span> <b>{{ $kdm['penjamin_telepon'] ?? '-' }}</b><br>
                                            <span class="text-muted">Surat Bapas:</span> {{ $kdm['nomor_surat_bapas'] ?? '-' }} ({{ !empty($kdm['tanggal_surat_bapas']) ? \Carbon\Carbon::parse($kdm['tanggal_surat_bapas'])->format('d/m/Y') : '-' }})
                                        </div>
                                    </div>
                                    @if(!empty($kdm['file_litmas_bapas']) || !empty($kdm['file_surat_bapas']) || !empty($kdm['file_bukti_travel']) || !empty($kdm['file_surat_jaminan']) || !empty($kdm['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                                    <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                                        @if(!empty($m->file_bahan_sidang))
                                        <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-download me-1"></i>Bahan Sidang
                                        </a>
                                        @endif
                                        @if(!empty($kdm['file_litmas_bapas']))
                                        <a href="{{ asset($kdm['file_litmas_bapas']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-pdf me-1"></i>Litmas Bapas
                                        </a>
                                        @endif
                                        @if(!empty($kdm['file_surat_bapas']))
                                        <a href="{{ asset($kdm['file_surat_bapas']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-pdf me-1"></i>Surat Usulan Bapas
                                        </a>
                                        @endif
                                        @if(!empty($kdm['file_bukti_travel']))
                                        <a href="{{ asset($kdm['file_bukti_travel']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-ticket-alt me-1"></i>Bukti Tiket / Travel / RS
                                        </a>
                                        @endif
                                        @if(!empty($kdm['file_surat_jaminan']))
                                        <a href="{{ asset($kdm['file_surat_jaminan']) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-shield-alt me-1"></i>Surat Jaminan Penjamin
                                        </a>
                                        @endif
                                        @if(!empty($kdm['dokumen_tambahan']['file']))
                                        <a href="{{ asset($kdm['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-paperclip me-1"></i>{{ $kdm['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @else
                                    @if(!empty($m->file_bahan_sidang) || !empty($m->dokumen_tambahan['file']))
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        @if(!empty($m->file_bahan_sidang))
                                        <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-file-download me-1"></i>Unduh Bahan Sidang
                                        </a>
                                        @endif
                                        @if(!empty($m->dokumen_tambahan['file']))
                                        <a href="{{ asset($m->dokumen_tambahan['file']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;">
                                            <i class="fas fa-paperclip me-1"></i>{{ $m->dokumen_tambahan['nama'] ?? 'Dokumen Tambahan' }}
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                @endif

                                @if($m->catatan_rekomendasi)
                                <div class="p-2 bg-light rounded border small mb-2" style="font-size: 11.5px;">
                                    <span class="text-muted fw-bold"><i class="fas fa-sticky-note text-warning me-1"></i>Catatan Rekomendasi Sidang:</span>
                                    <span class="text-dark">{{ $m->catatan_rekomendasi }}</span>
                                </div>
                                @endif

                                <!-- Rekap Pendapat Tim Anggota Sidang untuk Materi Ini -->
                                @php
                                    $pSetuju = $m->pendapats->where('status_pendapat', 'setuju')->count();
                                    $pCatatan = $m->pendapats->where('status_pendapat', 'setuju_catatan')->count();
                                    $pTunda = $m->pendapats->where('status_pendapat', 'tunda')->count();
                                    $pTolak = $m->pendapats->where('status_pendapat', 'tolak')->count();
                                    $notes = $m->pendapats->whereNotNull('catatan_pendapat')->where('catatan_pendapat', '!=', '');
                                @endphp
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
                                    <div class="d-flex flex-wrap align-items-center gap-1.5" style="font-size: 10.5px;">
                                        <span class="text-muted">Pendapat Tim:</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Setuju: {{ $pSetuju }}</span>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">Setuju Catatan: {{ $pCatatan }}</span>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Tunda: {{ $pTunda }}</span>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Tolak: {{ $pTolak }}</span>
                                    </div>
                                    @if($notes->count() > 0)
                                    <button class="btn btn-link btn-xs p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#notesMateri_{{ $m->id }}" style="font-size: 11px;">
                                        <i class="fas fa-comments text-primary me-1"></i>Lihat {{ $notes->count() }} Catatan Khusus
                                    </button>
                                    @endif
                                </div>

                                @if($notes->count() > 0)
                                <div class="collapse mt-2" id="notesMateri_{{ $m->id }}">
                                    <div class="p-2.5 bg-light rounded border">
                                        <div class="fw-bold small text-dark mb-1" style="font-size: 11px;"><i class="fas fa-user-edit text-primary me-1"></i>Catatan Khusus Anggota Sidang untuk Materi #{{ $m->urutan }}:</div>
                                        @foreach($notes as $note)
                                        <div class="border-bottom py-1 small" style="font-size: 11px;">
                                            <b>{{ $note->anggota?->nama ?? 'Anggota' }}:</b>
                                            <span class="text-secondary fst-italic">"{{ $note->catatan_pendapat }}"</span>
                                            <span class="badge bg-light text-dark border ms-1" style="font-size: 9px;">{{ $note->status_pendapat_badge }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

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

            <!-- ACTION BANNER: SEBAR UNDANGAN OTOMATIS KE SELURUH ANGGOTA -->
            <div class="card border border-success border-opacity-25 bg-success-subtle bg-opacity-10 mb-3 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-success text-white p-2" style="border-radius: 8px;">
                                    <i class="fab fa-whatsapp fs-5"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold text-success mb-0" style="font-size: 15px;">Kirim Link Presensi &amp; Pendapat ke Seluruh Tim Sidang Sekaligus</h6>
                                    <small class="text-muted">Kirim otomatis via WhatsApp Gateway ke <b>{{ $totalAnggota }} Anggota</b> (Ketua, Sekretaris, dan seluruh Anggota sidang) dengan 1 kali klik.</small>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 mt-2 text-dark small" style="font-size: 11.5px;">
                                <span><i class="fas fa-users text-primary me-1"></i>Total Tim: <b>{{ $totalAnggota }} Orang</b></span>
                                <span><i class="fas fa-check-circle text-success me-1"></i>Sudah Terkirim: <b>{{ $totalWaTerkirim }}</b></span>
                                <span><i class="fas fa-clock text-warning-emphasis me-1"></i>Belum Terkirim: <b>{{ max(0, $totalAnggota - $totalWaTerkirim) }}</b></span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <!-- Tombol 1-Klik Kirim Otomatis via Gateway -->
                            <form action="{{ route('sidang-tpp-kanwil.broadcast-wa', $sidang->id) }}" method="POST" onsubmit="return confirm('Kirim notifikasi undangan resmi dan link portal pengisian pendapat ke SELURUH {{ $totalAnggota }} anggota tim sidang (Ketua, Sekretaris, dan Anggota) melalui WhatsApp secara serentak?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm px-3 py-2 shadow-sm fw-bold text-nowrap" style="border-radius: 8px;">
                                    <i class="fab fa-whatsapp fs-6 me-1"></i> Kirim Otomatis ke Seluruh Tim (1 Klik)
                                </button>
                            </form>

                            <!-- Tombol Salin Format Pesan Siaran Grup WA -->
                            <button type="button" class="btn btn-outline-success btn-sm px-3 py-2 fw-bold text-nowrap" style="border-radius: 8px;" onclick="copyFormatGrupWa()">
                                <i class="fas fa-clipboard-list me-1"></i> Salin Semua Link untuk Grup WA
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teks Format Siaran Grup WA (Hidden) -->
            <textarea id="rawTeksGrupWa" class="d-none">
*UNDANGAN & TAUTAN PENGISIAN SIDANG TPP KANTOR WILAYAH*
Nomor Sidang: {{ $sidang->nomor_sidang }}
Agenda: {{ $sidang->agenda_judul }}
Hari/Tanggal: {{ $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-' }}
Pukul: {{ substr($sidang->jam_mulai, 0, 5) }} WIB s/d {{ substr($sidang->jam_selesai, 0, 5) }} WIB
Tempat: {{ $sidang->tempat }} ({{ ucfirst($sidang->metode) }})
@if(!empty($sidang->link_daring))Link Daring: {{ $sidang->link_daring }}
@endif

Kepada Yth. Ketua, Sekretaris, dan Anggota Tim Sidang TPP Kanwil, mohon kesediaannya untuk mengonfirmasi kehadiran serta mengisi formulir telaahan & pendapat sidang melalui tautan personal masing-masing berikut:

@foreach($anggotaList as $idx => $a)
{{ $idx + 1 }}. *{{ $a->nama }}* ({{ $a->peran_sidang }}):
👉 {{ route('sidang-tpp-kanwil.portal-anggota', $a->token_akses) }}

@endforeach
Demikian disampaikan, atas kerja sama dan perkenannya diucapkan terima kasih.
- Sekretariat TPP Kantor Wilayah
            </textarea>

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
                <!-- Kolom Kiri: Generator Dokumen Resmi Siap Cetak -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary p-2" style="border-radius: 8px;">
                                    <i class="fas fa-file-invoice"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Tahap 1: Cetak Naskah Dinas Resmi</h6>
                            </div>
                            <button type="button" class="btn btn-sm text-white fw-bold shadow-sm d-flex align-items-center gap-1.5" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); border-radius: 8px; font-size: 11.5px; padding: 6px 12px;" data-bs-toggle="modal" data-bs-target="#modalAiNaskah">
                                <i class="fas fa-wand-magic-sparkles"></i> AI Asisten Naskah
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <p class="small text-muted mb-3" style="line-height: 1.5;">
                                Sistem secara otomatis menyusun seluruh naskah dinas resmi Sidang TPP Kanwil sesuai standar tata naskah Kementerian Imigrasi dan Pemasyarakatan Ditjenpas Banten (huruf Arial).
                            </p>

                            @if (! empty($sidang->dokumen_custom))
                                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between" style="font-size: 11.5px; border-radius: 8px;">
                                    <div>
                                        <i class="fas fa-sparkles text-primary me-1.5"></i>
                                        <strong>Naskah Kustom / AI Aktif:</strong>
                                        @if(isset($sidang->dokumen_custom['laporan'])) <span class="badge bg-success-subtle text-success me-1">Laporan</span> @endif
                                        @if(isset($sidang->dokumen_custom['risalah'])) <span class="badge bg-primary-subtle text-primary me-1">Risalah</span> @endif
                                        @if(isset($sidang->dokumen_custom['berita_acara'])) <span class="badge bg-warning-subtle text-dark me-1">Berita Acara</span> @endif
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none fw-bold" onclick="resetSemuaNaskahAi()">
                                        <i class="fas fa-undo me-1"></i>Reset ke Default
                                    </button>
                                </div>
                            @endif

                            <!-- 1. Risalah Hasil Sidang TPP -->
                            <div class="p-3 bg-light rounded border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark small"><i class="fas fa-file-contract text-primary me-1"></i>1. Risalah Hasil Sidang TPP</div>
                                    <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">Ditandatangani Ketua TPP</span>
                                </div>
                                <div class="small text-muted mb-2" style="font-size: 11.5px;">Memuat 9 butir dasar hukum, tabel anggota sidang, materi sidang, dan amar putusan sidang.</div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sidang-tpp-kanwil.download-risalah', [$sidang->id, 'pdf']) }}" target="_blank" class="btn btn-primary btn-sm py-1.5 px-2.5 fw-bold w-50">
                                        <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                                    </a>
                                    <a href="{{ route('sidang-tpp-kanwil.download-risalah', [$sidang->id, 'word']) }}" class="btn btn-outline-primary btn-sm py-1.5 px-2.5 w-50">
                                        <i class="fas fa-file-word me-1"></i> Word (.doc)
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Laporan Pelaksanaan Sidang TPP -->
                            <div class="p-3 bg-light rounded border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark small"><i class="fas fa-file-signature text-success me-1"></i>2. Laporan Pelaksanaan Sidang</div>
                                    <span class="badge bg-success-subtle text-success" style="font-size: 10px;">TTD Ketua TPP &amp; Kakanwil</span>
                                </div>
                                <div class="small text-muted mb-2" style="font-size: 11.5px;">Dilengkapi foto dokumentasi sidang, QR Code verifikasi otentikasi, dan tanda tangan ganda.</div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sidang-tpp-kanwil.download-laporan', [$sidang->id, 'pdf']) }}" target="_blank" class="btn btn-success btn-sm py-1.5 px-2.5 fw-bold w-50">
                                        <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                                    </a>
                                    <a href="{{ route('sidang-tpp-kanwil.download-laporan', [$sidang->id, 'word']) }}" class="btn btn-outline-success btn-sm py-1.5 px-2.5 w-50">
                                        <i class="fas fa-file-word me-1"></i> Word (.doc)
                                    </a>
                                </div>
                            </div>

                            <!-- 3. Berita Acara & Lembar Disposisi -->
                            <div class="p-3 bg-light rounded border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark small"><i class="fas fa-stamp text-danger me-1"></i>3. Berita Acara (BA) &amp; Disposisi Fisik</div>
                                </div>
                                <div class="small text-muted mb-2" style="font-size: 11.5px;">Daftar presensi, telaahan pendapat anggota, dan kotak lembar disposisi fisik pimpinan.</div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'pdf']) }}" target="_blank" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 w-50">
                                        <i class="fas fa-file-pdf text-danger me-1"></i> Cetak PDF
                                    </a>
                                    <a href="{{ route('sidang-tpp-kanwil.download-ba', [$sidang->id, 'word']) }}" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 w-50">
                                        <i class="fas fa-file-word text-primary me-1"></i> Word (.doc)
                                    </a>
                                </div>
                            </div>

                            <!-- 4. Nota Dinas Undangan -->
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark small"><i class="fas fa-envelope-open-text text-info me-1"></i>4. Nota Dinas Undangan Sidang</div>
                                </div>
                                <div class="small text-muted mb-2" style="font-size: 11.5px;">Surat undangan resmi dinas beserta lampiran susunan tim sidang TPP Kanwil.</div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sidang-tpp-kanwil.download-nota', [$sidang->id, 'pdf']) }}" target="_blank" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 w-50">
                                        <i class="fas fa-file-pdf text-danger me-1"></i> Cetak PDF
                                    </a>
                                    <a href="{{ route('sidang-tpp-kanwil.download-nota', [$sidang->id, 'word']) }}" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 w-50">
                                        <i class="fas fa-file-word text-primary me-1"></i> Word (.doc)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Upload Berkas Fisik & Disposisi Kakanwil -->
                <div class="col-lg-7">
                    <!-- Card 1: Upload Risalah Hasil Sidang Bertanda Tangan Basah -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary p-2" style="border-radius: 8px;">
                                    <i class="fas fa-file-contract"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Unggah Risalah Hasil Sidang TPP Bertanda Tangan</h6>
                            </div>
                            @if($sidang->file_risalah_signed)
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diunggah</span>
                            @else
                            <span class="badge bg-secondary">Belum Diunggah</span>
                            @endif
                        </div>
                        <div class="card-body p-4">
                            @if($sidang->file_risalah_signed)
                            <div class="alert alert-success border-0 p-3 mb-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    <div>
                                        <div class="fw-bold small text-dark">Berkas Scan Risalah Sidang Bertanda Tangan Basah</div>
                                        <small class="text-muted">Nomor Risalah: {{ $sidang->nomor_risalah ?: '-' }}</small>
                                    </div>
                                </div>
                                <a href="{{ asset($sidang->file_risalah_signed) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-external-link-alt me-1"></i>Lihat Berkas
                                </a>
                            </div>
                            @endif

                            <form action="{{ route('sidang-tpp-kanwil.upload-risalah-signed', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Nomor Risalah Sah <span class="text-danger">*</span></label>
                                        <input type="text" name="nomor_risalah" class="form-control form-control-sm" value="{{ old('nomor_risalah', $sidang->nomor_risalah ?: ('WP.12.PK.06.05-'.str_pad($sidang->id, 2, '0', STR_PAD_LEFT))) }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">File Scan Risalah Bertanda Tangan <span class="text-danger">*</span></label>
                                        <input type="file" name="file_risalah_signed" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="text-muted" style="font-size: 11px;">Format PDF/Gambar (Maks. 15 MB)</small>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                                            <i class="fas fa-upload me-1"></i> Simpan &amp; Unggah Risalah Sidang
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card 2: Upload Laporan Pelaksanaan Sidang Sah + Foto Dokumentasi -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success-subtle text-success p-2" style="border-radius: 8px;">
                                    <i class="fas fa-file-signature"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Unggah Laporan Pelaksanaan Sidang TPP &amp; Dokumentasi</h6>
                            </div>
                            @if($sidang->file_laporan_signed)
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diunggah</span>
                            @else
                            <span class="badge bg-secondary">Belum Diunggah</span>
                            @endif
                        </div>
                        <div class="card-body p-4">
                            @if($sidang->file_laporan_signed)
                            <div class="alert alert-success border-0 p-3 mb-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    <div>
                                        <div class="fw-bold small text-dark">Berkas Scan Laporan Pelaksanaan Sidang Sah</div>
                                        <small class="text-muted">Nomor Laporan: {{ $sidang->nomor_laporan ?: '-' }}</small>
                                    </div>
                                </div>
                                <a href="{{ asset($sidang->file_laporan_signed) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-external-link-alt me-1"></i>Lihat Berkas
                                </a>
                            </div>
                            @endif

                            <form action="{{ route('sidang-tpp-kanwil.upload-laporan-signed', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">Nomor Laporan Sah <span class="text-danger">*</span></label>
                                        <input type="text" name="nomor_laporan" class="form-control form-control-sm" value="{{ old('nomor_laporan', $sidang->nomor_laporan ?: ('WP.12.PK.06.06-'.str_pad($sidang->id, 2, '0', STR_PAD_LEFT))) }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Foto Dokumentasi 1 (Sidang/Deliberasi)</label>
                                        <input type="file" name="foto_dokumentasi_1" class="form-control form-control-sm" accept="image/*">
                                        @if($sidang->foto_dokumentasi_1)
                                        <small class="text-success"><i class="fas fa-check me-1"></i>Foto 1 sudah terunggah</small>
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">Foto Dokumentasi 2 (Verifikasi Berkas)</label>
                                        <input type="file" name="foto_dokumentasi_2" class="form-control form-control-sm" accept="image/*">
                                        @if($sidang->foto_dokumentasi_2)
                                        <small class="text-success"><i class="fas fa-check me-1"></i>Foto 2 sudah terunggah</small>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">File Scan Laporan Pelaksanaan Sah <span class="text-danger">*</span></label>
                                        <input type="file" name="file_laporan_signed" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="text-muted" style="font-size: 11px;">Format PDF/Gambar (Maks. 15 MB)</small>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm">
                                            <i class="fas fa-upload me-1"></i> Simpan &amp; Unggah Laporan Pelaksanaan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card 3: Upload Berita Acara Bertanda Tangan Basah -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info-subtle text-info p-2" style="border-radius: 8px;">
                                    <i class="fas fa-stamp"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Unggah Berita Acara Fisik Bertanda Tangan</h6>
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
                                        <button type="submit" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                                            <i class="fas fa-upload me-1"></i> Simpan &amp; Unggah Berita Acara
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card 4: Tanggapan / Disposisi Fisik Kepala Kantor Wilayah -->
                    <div class="card border-0 shadow-sm" style="background: var(--bg-card); border-radius: 12px;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger-subtle text-danger p-2" style="border-radius: 8px;">
                                    <i class="fas fa-stamp"></i>
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">Tanggapan &amp; Disposisi Fisik Kepala Kantor Wilayah</h6>
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
                                        <input type="text" name="kakanwil_nama" class="form-control form-control-sm" value="{{ old('kakanwil_nama', $sidang->kakanwil_nama ?: 'Dody Fahrudin, Bc.IP., S.H., M.H.') }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">NIP Kepala Kantor Wilayah</label>
                                        <input type="text" name="kakanwil_nip" class="form-control form-control-sm" value="{{ old('kakanwil_nip', $sidang->kakanwil_nip ?: '197205141994031001') }}">
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

    <!-- Modal AI Asisten Naskah Dinas -->
    <div class="modal fade" id="modalAiNaskah" tabindex="-1" aria-labelledby="modalAiNaskahLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #fff;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="p-2 bg-white bg-opacity-25 rounded-circle text-white">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </span>
                        <div>
                            <h6 class="modal-title fw-bold mb-0 text-white" id="modalAiNaskahLabel">AI Asisten Perumusan Tata Naskah Dinas Sidang TPP</h6>
                            <small class="text-white-50" style="font-size: 11px;">Sesuaikan narasi Laporan, Risalah, atau Berita Acara secara otomatis atau fleksibel</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Pilihan Jenis Dokumen -->
                    <label class="form-label fw-bold small text-dark mb-2">Pilih Naskah Dinas yang Ingin Diselaraskan / Diedit:</label>
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check" name="ai_jenis_dokumen" id="jenis_laporan" value="laporan" checked onchange="switchAiDokumenTab('laporan')">
                        <label class="btn btn-outline-primary py-2 fw-semibold" for="jenis_laporan" style="font-size: 12px;">
                            <i class="fas fa-file-signature me-1"></i> Laporan Pelaksanaan
                        </label>

                        <input type="radio" class="btn-check" name="ai_jenis_dokumen" id="jenis_risalah" value="risalah" onchange="switchAiDokumenTab('risalah')">
                        <label class="btn btn-outline-primary py-2 fw-semibold" for="jenis_risalah" style="font-size: 12px;">
                            <i class="fas fa-file-contract me-1"></i> Risalah Hasil Sidang
                        </label>

                        <input type="radio" class="btn-check" name="ai_jenis_dokumen" id="jenis_ba" value="berita_acara" onchange="switchAiDokumenTab('berita_acara')">
                        <label class="btn btn-outline-primary py-2 fw-semibold" for="jenis_ba" style="font-size: 12px;">
                            <i class="fas fa-stamp me-1"></i> Berita Acara &amp; Rekomendasi
                        </label>
                    </div>

                    <!-- Input Instruksi Khusus -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark d-flex justify-content-between">
                            <span><i class="fas fa-comment-dots text-primary me-1"></i> Instruksi Khusus untuk AI (Opsional):</span>
                            <span class="text-muted fw-normal" style="font-size: 11px;">Misal: penekanan alasan khusus, rekomendasi tambahan, dll</span>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm" id="ai_instruksi_khusus" placeholder="Contoh: Tekankan kelakuan baik klien, masa pembinaan, atau perbaiki redaksi saran...">
                            <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" id="btnRunAiGenerate" onclick="jalankanGenerateAi()">
                                <i class="fas fa-robot me-1"></i> Generate AI
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="aiLoadingIndicator" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary mb-2" role="status" style="width: 2rem; height: 2rem;"></div>
                        <div class="fw-bold small text-dark">AI sedang menyelaraskan naskah dinas...</div>
                        <div class="text-muted" style="font-size: 11.5px;">Menerapkan pedoman tata naskah dinas Ditjenpas dan kaidah hukum yang berlaku.</div>
                    </div>

                    <!-- Status Feedback Banner -->
                    <div id="aiFeedbackBanner" class="alert d-none py-2 px-3 mb-3" style="font-size: 12px; border-radius: 8px;"></div>

                    <!-- Form Editor Konten: Laporan -->
                    <div id="editorGroupLaporan" class="ai-editor-pane">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">A. Narasi Kegiatan yang Dilaksanakan:</label>
                            <textarea class="form-control font-monospace" id="field_lap_kegiatan" rows="4" style="font-size: 12px;"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">B. Butir Hasil yang Dicapai (Satu butir per baris):</label>
                            <textarea class="form-control font-monospace" id="field_lap_hasil" rows="3" style="font-size: 12px;"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">C. Simpulan (Satu butir per baris):</label>
                                <textarea class="form-control font-monospace" id="field_lap_simpulan" rows="3" style="font-size: 12px;"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">C. Saran (Satu butir per baris):</label>
                                <textarea class="form-control font-monospace" id="field_lap_saran" rows="3" style="font-size: 12px;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Editor Konten: Risalah -->
                    <div id="editorGroupRisalah" class="ai-editor-pane d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">III. Materi Sidang TPP (Satu butir per baris):</label>
                            <textarea class="form-control font-monospace" id="field_ris_materi" rows="4" style="font-size: 12px;"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-dark">IV. Hasil Sidang TPP (Satu butir per baris):</label>
                            <textarea class="form-control font-monospace" id="field_ris_hasil" rows="4" style="font-size: 12px;"></textarea>
                        </div>
                    </div>

                    <!-- Form Editor Konten: Berita Acara -->
                    <div id="editorGroupBeritaAcara" class="ai-editor-pane d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Uraian / Ringkasan Agenda Sidang:</label>
                            <textarea class="form-control font-monospace" id="field_ba_uraian" rows="3" style="font-size: 12px;"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-dark">Kesimpulan / Rekomendasi Sidang:</label>
                            <textarea class="form-control font-monospace" id="field_ba_kesimpulan" rows="3" style="font-size: 12px;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2.5 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="resetCurrentNaskahAi()">
                        <i class="fas fa-undo me-1"></i> Reset Naskah Ini ke Default
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-success btn-sm fw-bold px-3" id="btnSaveAiNaskah" onclick="simpanNaskahAi()">
                            <i class="fas fa-check-circle me-1"></i> Terapkan &amp; Simpan ke Dokumen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($sidang->canEdit(auth()->user()))
    <!-- Modal Edit Data Sidang Kanwil -->
    <div class="modal fade" id="modalEditSidangKanwil" tabindex="-1" aria-labelledby="modalEditSidangKanwilLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('sidang-tpp-kanwil.update', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-primary text-white py-3">
                        <h6 class="modal-title fw-bold" id="modalEditSidangKanwilLabel">
                            <i class="fas fa-edit me-2"></i>Edit Data &amp; Jadwal Sidang TPP Kanwil
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Nomor Sidang <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_sidang" class="form-control form-control-sm" value="{{ old('nomor_sidang', $sidang->nomor_sidang) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Kategori Agenda <span class="text-danger">*</span></label>
                                <input type="text" name="agenda_kategori" class="form-control form-control-sm" value="{{ old('agenda_kategori', $sidang->agenda_kategori) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Judul Agenda Sidang <span class="text-danger">*</span></label>
                                <input type="text" name="agenda_judul" class="form-control form-control-sm" value="{{ old('agenda_judul', $sidang->agenda_judul) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Deskripsi / Latar Belakang Agenda</label>
                                <textarea name="agenda_deskripsi" class="form-control form-control-sm" rows="3">{{ old('agenda_deskripsi', $sidang->agenda_deskripsi) }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Tanggal Sidang <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_sidang" class="form-control form-control-sm" value="{{ old('tanggal_sidang', $sidang->tanggal_sidang ? $sidang->tanggal_sidang->format('Y-m-d') : '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_mulai" class="form-control form-control-sm" value="{{ old('jam_mulai', substr($sidang->jam_mulai, 0, 5)) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control form-control-sm" value="{{ old('jam_selesai', $sidang->jam_selesai ? substr($sidang->jam_selesai, 0, 5) : '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Tempat Pelaksanaan <span class="text-danger">*</span></label>
                                <input type="text" name="tempat" class="form-control form-control-sm" value="{{ old('tempat', $sidang->tempat) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Metode Sidang <span class="text-danger">*</span></label>
                                <select name="metode" class="form-select form-select-sm" required>
                                    <option value="luring" {{ old('metode', $sidang->metode) === 'luring' ? 'selected' : '' }}>Tatap Muka Langsung (Luring)</option>
                                    <option value="daring" {{ old('metode', $sidang->metode) === 'daring' ? 'selected' : '' }}>Daring Penuh (Virtual)</option>
                                    <option value="hybrid" {{ old('metode', $sidang->metode) === 'hybrid' ? 'selected' : '' }}>Hybrid (Luring &amp; Daring)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Tautan Zoom / Link Daring</label>
                                <input type="url" name="link_daring" class="form-control form-control-sm" value="{{ old('link_daring', $sidang->link_daring) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-dark">Meeting ID</label>
                                <input type="text" name="meeting_id" class="form-control form-control-sm" value="{{ old('meeting_id', $sidang->meeting_id) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-dark">Passcode</label>
                                <input type="text" name="passcode" class="form-control form-control-sm" value="{{ old('passcode', $sidang->passcode) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Nomor Nota Dinas Undangan</label>
                                <input type="text" name="nomor_nota_dinas" class="form-control form-control-sm" value="{{ old('nomor_nota_dinas', $sidang->nomor_nota_dinas) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Tanggal Nota Dinas Undangan</label>
                                <input type="date" name="tanggal_nota_dinas" class="form-control form-control-sm" value="{{ old('tanggal_nota_dinas', $sidang->tanggal_nota_dinas ? $sidang->tanggal_nota_dinas->format('Y-m-d') : '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Nomor Berita Acara</label>
                                <input type="text" name="nomor_berita_acara" class="form-control form-control-sm" value="{{ old('nomor_berita_acara', $sidang->nomor_berita_acara) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Nomor Risalah Sidang</label>
                                <input type="text" name="nomor_risalah" class="form-control form-control-sm" value="{{ old('nomor_risalah', $sidang->nomor_risalah) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Nomor Laporan Pelaksanaan</label>
                                <input type="text" name="nomor_laporan" class="form-control form-control-sm" value="{{ old('nomor_laporan', $sidang->nomor_laporan) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Kesimpulan / Rekomendasi Hasil Sidang</label>
                                <textarea name="kesimpulan_sidang" class="form-control form-control-sm" rows="3">{{ old('kesimpulan_sidang', $sidang->kesimpulan_sidang) }}</textarea>
                            </div>

                            @if($sidang->isMutasiNarapidana())
                            @php 
                                $nd = $sidang->narapidana_data ?? []; 
                                $bMutasi = $nd['berkas'] ?? [];
                            @endphp
                            <div class="col-12 p-3 bg-light rounded border border-warning border-opacity-50">
                                <h6 class="fw-bold text-dark small mb-2"><i class="fas fa-exchange-alt text-warning-emphasis me-1"></i>Penyesuaian Usulan Mutasi Narapidana</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Rekomendasi UPT Tujuan Sidang</label>
                                        <select name="upt_tujuan_rekomendasi_id" class="form-select form-select-sm border-warning">
                                            <option value="">-- Tetapkan / Ubah UPT Tujuan --</option>
                                            @foreach($uptList as $u)
                                            <option value="{{ $u->id }}" {{ old('upt_tujuan_rekomendasi_id', $sidang->upt_tujuan_rekomendasi_id) == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Alasan Pemindahan</label>
                                        <input type="text" name="mutasi_alasan" class="form-control form-control-sm" value="{{ old('mutasi_alasan', $nd['alasan_pemindahan'] ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Penjamin Keluarga</label>
                                        <input type="text" name="mutasi_penjamin_nama" class="form-control form-control-sm" value="{{ old('mutasi_penjamin_nama', $nd['penjamin_nama'] ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Hubungan</label>
                                        <input type="text" name="mutasi_penjamin_hubungan" class="form-control form-control-sm" value="{{ old('mutasi_penjamin_hubungan', $nd['penjamin_hubungan'] ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Kontak/WA</label>
                                        <input type="text" name="mutasi_penjamin_kontak" class="form-control form-control-sm" value="{{ old('mutasi_penjamin_kontak', $nd['penjamin_kontak'] ?? '') }}">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-bold text-dark mb-1">Upload / Perbarui Dokumen Tambahan Lainnya</label>
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <input type="text" name="dokumen_tambahan_nama" class="form-control form-control-sm" value="{{ old('dokumen_tambahan_nama', $bMutasi['dokumen_tambahan']['nama'] ?? '') }}" placeholder="Nama Dokumen Tambahan">
                                            </div>
                                            <div class="col-md-7">
                                                <input type="file" name="dokumen_tambahan_file" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($sidang->isIzinBerobat())
                            @php 
                                $nd = $sidang->narapidana_data ?? []; 
                                $bMedis = $nd['berkas'] ?? [];
                            @endphp
                            <div class="col-12 p-3 bg-light rounded border border-danger border-opacity-50">
                                <h6 class="fw-bold text-dark small mb-2"><i class="fas fa-heartbeat text-danger me-1"></i>Penyesuaian Data Izin Berobat Keluar Kota</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Diagnosa Medis</label>
                                        <input type="text" name="berobat_diagnosa" class="form-control form-control-sm" value="{{ old('berobat_diagnosa', $nd['diagnosa_medis'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Dokter Penanggung Jawab</label>
                                        <input type="text" name="berobat_dokter" class="form-control form-control-sm" value="{{ old('berobat_dokter', $nd['dokter_penanggung_jawab'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">RS Rujukan</label>
                                        <input type="text" name="berobat_rs_nama" class="form-control form-control-sm" value="{{ old('berobat_rs_nama', $nd['rs_rujukan_nama'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Kota RS</label>
                                        <input type="text" name="berobat_rs_kota" class="form-control form-control-sm" value="{{ old('berobat_rs_kota', $nd['rs_rujukan_kota'] ?? '') }}">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-bold text-dark mb-1">Upload / Perbarui Dokumen Tambahan Lainnya</label>
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <input type="text" name="dokumen_tambahan_nama" class="form-control form-control-sm" value="{{ old('dokumen_tambahan_nama', $bMedis['dokumen_tambahan']['nama'] ?? '') }}" placeholder="Nama Dokumen Tambahan">
                                            </div>
                                            <div class="col-md-7">
                                                <input type="file" name="dokumen_tambahan_file" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Perbarui Berkas Bahan Rapat Sidang (Opsional, PDF/Word/Zip maks 15MB)</label>
                                <input type="file" name="file_bahan_sidang" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                                @if($sidang->file_bahan_sidang)
                                <div class="form-text small text-muted">Berkas saat ini: <a href="{{ asset($sidang->file_bahan_sidang) }}" target="_blank"><i class="fas fa-paperclip me-1"></i>Lihat Berkas Bahan</a></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL TAMBAH MATERI SIDANG -->
    @if($sidang->canEdit(auth()->user()))
    <div class="modal fade" id="modalTambahMateri" tabindex="-1" aria-labelledby="modalTambahMateriLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('sidang-tpp-kanwil.materi.store', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-primary text-white py-3 px-4">
                        <h6 class="modal-title fw-bold" id="modalTambahMateriLabel">
                            <i class="fas fa-plus-circle me-2"></i>Tambah Materi / Agenda Sidang Baru
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Kategori Materi <span class="text-danger">*</span></label>
                                <select name="kategori_materi" class="form-select form-select-sm" required onchange="switchModalMateriCategory('tambah', this.value)">
                                    <option value="mutasi_narapidana">🏢 Mutasi Narapidana Antar-UPT</option>
                                    <option value="mutasi_keinginan_sendiri">👤 Mutasi Permintaan Sendiri (Mendekati Keluarga)</option>
                                    <option value="izin_berobat">🏥 Izin Berobat Keluar Kota</option>
                                    <option value="klien_bapas">⚖️ Klien Pemasyarakatan (Bapas - Luar Negeri)</option>
                                    <option value="lainnya">📋 Lainnya / Pembahasan Mandiri</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Judul Materi / Pokok Agenda <span class="text-danger">*</span></label>
                                <input type="text" name="judul_materi" id="tambah_judul_materi" class="form-control form-control-sm" required placeholder="Contoh: Permohonan Pemindahan WBP an. Budi Santoso">
                            </div>

                            <!-- SUB-FORM: MUTASI NARAPIDANA -->
                            <div id="boxTambahMutasi" class="col-12">
                                <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                                    <div class="fw-bold text-warning-emphasis small mb-2"><i class="fas fa-exchange-alt me-1"></i>Data Usulan Mutasi Narapidana</div>
                                    
                                    <!-- Dropdown Usulan Mutasi UPT -->
                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">Tarik Usulan dari Modul Mutasi UPT (Opsional):</label>
                                        <select name="mutasi_id" class="form-select form-select-sm" onchange="autoFillMutasiModal(this, 'tambah')">
                                            <option value="">-- Pilih Usulan Mutasi UPT --</option>
                                            @foreach($mutasiList as $mu)
                                            <option value="{{ $mu->id }}"
                                                data-nama="{{ $mu->keterangan ? Str::limit($mu->keterangan, 50) : 'WBP Usulan Mutasi ' . ($mu->uptAsal?->nama_upt ?? '') }}"
                                                data-uptasal="{{ $mu->upt_asal_id }}"
                                                data-upttujuan="{{ $mu->upt_tujuan_id }}"
                                                data-alasan="{{ $mu->kategori_mutasi ?? $mu->keterangan }}">
                                                {{ $mu->nomor_surat }} &bull; {{ $mu->uptAsal?->nama_upt }} &rarr; {{ $mu->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }} ({{ $mu->total_usulan }} Orang)
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Narapidana / WBP <span class="text-danger">*</span></label>
                                            <input type="text" name="mutasi_wbp_nama" id="tambah_mutasi_wbp_nama" class="form-control form-control-sm" placeholder="Nama narapidana">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Nomor Register</label>
                                            <input type="text" name="mutasi_nomor_register" class="form-control form-control-sm" placeholder="BI.124/2025">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                            <input type="text" name="mutasi_perkara" class="form-control form-control-sm" placeholder="Narkotika Ps 114">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                            <input type="text" name="mutasi_lama_pidana" class="form-control form-control-sm" placeholder="5 Tahun">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Asal</label>
                                            <select name="mutasi_upt_asal_id" id="tambah_mutasi_upt_asal_id" class="form-select form-select-sm">
                                                <option value="">-- Pilih UPT Asal --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Tujuan Rekomendasi Sidang</label>
                                            <select name="upt_tujuan_rekomendasi_id" id="tambah_upt_tujuan_rekomendasi_id" class="form-select form-select-sm">
                                                <option value="">-- Pilih Rekomendasi Tujuan --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                            <input type="text" name="mutasi_alasan" id="tambah_mutasi_alasan" class="form-control form-control-sm" placeholder="Keinginan sendiri mendekati keluarga">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_nama" class="form-control form-control-sm" placeholder="Nama penjamin">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Hubungan Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_hubungan" class="form-control form-control-sm" placeholder="Istri / Orang Tua">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_kontak" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                        </div>

                                        <!-- Unggah Berkas Mutasi -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-warning-emphasis me-1"></i>Unggah Berkas Mutasi (Opsional):</span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Litmas Bapas</label>
                                            <input type="file" name="file_mutasi_litmas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Permohonan Sendiri/Keluarga</label>
                                            <input type="file" name="file_mutasi_permohonan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Jaminan Keluarga</label>
                                            <input type="file" name="file_mutasi_jaminan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Register F</label>
                                            <input type="file" name="file_mutasi_reg_f" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Asesmen Risiko</label>
                                            <input type="file" name="file_mutasi_asesmen" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-FORM: MUTASI PERMINTAAN SENDIRI -->
                            <div id="boxTambahMutasiPribadi" class="col-12 d-none">
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
                                        <select name="mutasi_id" class="form-select form-select-sm" onchange="autoFillMutasiModal(this, 'tambah_pribadi')">
                                            <option value="">-- Pilih Usulan Mutasi UPT --</option>
                                            @foreach($mutasiList as $mu)
                                            <option value="{{ $mu->id }}"
                                                data-nama="{{ $mu->keterangan ? Str::limit($mu->keterangan, 50) : 'WBP Usulan Mutasi ' . ($mu->uptAsal?->nama_upt ?? '') }}"
                                                data-uptasal="{{ $mu->upt_asal_id }}"
                                                data-upttujuan="{{ $mu->upt_tujuan_id }}"
                                                data-alasan="{{ $mu->kategori_mutasi ?? $mu->keterangan }}">
                                                {{ $mu->nomor_surat }} &bull; {{ $mu->uptAsal?->nama_upt }} &rarr; {{ $mu->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }} ({{ $mu->total_usulan }} Orang)
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Narapidana / WBP <span class="text-danger">*</span></label>
                                            <input type="text" name="mutasi_wbp_nama" id="tambah_pribadi_mutasi_wbp_nama" class="form-control form-control-sm" placeholder="Nama narapidana">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Nomor Register</label>
                                            <input type="text" name="mutasi_nomor_register" class="form-control form-control-sm" placeholder="BI.124/2025">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                            <input type="text" name="mutasi_perkara" class="form-control form-control-sm" placeholder="Narkotika Ps 114">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                            <input type="text" name="mutasi_lama_pidana" class="form-control form-control-sm" placeholder="5 Tahun">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Asal</label>
                                            <select name="mutasi_upt_asal_id" id="tambah_pribadi_mutasi_upt_asal_id" class="form-select form-select-sm">
                                                <option value="">-- Pilih UPT Asal --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Tujuan Rekomendasi Sidang</label>
                                            <select name="upt_tujuan_rekomendasi_id" id="tambah_pribadi_upt_tujuan_rekomendasi_id" class="form-select form-select-sm border-primary">
                                                <option value="">-- Pilih Rekomendasi Tujuan --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                            <input type="text" name="mutasi_alasan" id="tambah_pribadi_mutasi_alasan" class="form-control form-control-sm" value="Permintaan sendiri mendekati keluarga penjamin dengan kesanggupan pembiayaan mandiri">
                                        </div>

                                        <!-- PK Bapas & Litmas -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-balance-scale text-primary me-1"></i>Penelitian Kemasyarakatan (Litmas) & PK Bapas:</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Nama Petugas PK Bapas</label>
                                            <input type="text" name="nama_pk_bapas" class="form-control form-control-sm" placeholder="Nama PK Bapas">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">NIP / Kontak PK</label>
                                            <input type="text" name="nip_pk_bapas" class="form-control form-control-sm" placeholder="19850101...">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Bapas Asal</label>
                                            <input type="text" name="bapas_nama" class="form-control form-control-sm" placeholder="Bapas Kelas I Serang">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Kesimpulan & Rekomendasi Litmas</label>
                                            <input type="text" name="rekomendasi_litmas" class="form-control form-control-sm" placeholder="Dapat direkomendasikan pemindahan mendekati keluarga penjamin...">
                                        </div>

                                        <!-- BA Sidang TPP UPT -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-gavel text-warning-emphasis me-1"></i>Hasil Sidang TPP UPT Asal (Lapas/Rutan):</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Nomor Berita Acara (BA) UPT</label>
                                            <input type="text" name="nomor_ba_tpp_upt" class="form-control form-control-sm" placeholder="W12.PAS.1.PK.01...">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Tanggal BA Sidang UPT</label>
                                            <input type="date" name="tanggal_ba_tpp_upt" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Rekomendasi Sidang UPT</label>
                                            <input type="text" name="rekomendasi_tpp_upt" class="form-control form-control-sm" placeholder="Disetujui untuk diusulkan ke Kanwil">
                                        </div>

                                        <!-- Penjamin -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-home text-success me-1"></i>Data Penjamin Keluarga:</span>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_nama" class="form-control form-control-sm" placeholder="Nama penjamin">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">NIK KTP Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_nik" class="form-control form-control-sm" placeholder="367101...">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Hubungan</label>
                                            <input type="text" name="mutasi_penjamin_hubungan" class="form-control form-control-sm" placeholder="Istri / Orang Tua">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_kontak" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Alamat Domisili Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_alamat" class="form-control form-control-sm" placeholder="Alamat lengkap domisili penjamin">
                                        </div>

                                        <!-- Berkas Khusus -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-primary me-1"></i>Unggah Berkas Persyaratan Khusus:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-signature text-primary me-1"></i>Surat Pernyataan Mandiri Bermaterai (Biaya Sendiri)</label>
                                            <input type="file" name="file_mutasi_pernyataan_pribadi" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-contract text-warning me-1"></i>Berita Acara (BA) Sidang TPP UPT Bertandatangan</label>
                                            <input type="file" name="file_mutasi_ba_upt" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Laporan Hasil Litmas Bapas</label>
                                            <input type="file" name="file_mutasi_litmas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Surat Jaminan Penjamin Keluarga (Bermaterai)</label>
                                            <input type="file" name="file_mutasi_jaminan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-id-card text-info me-1"></i>KTP & Kartu Keluarga Penjamin</label>
                                            <input type="file" name="file_mutasi_ktp_penjamin" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-book text-secondary me-1"></i>Buku Register F (Bebas Disiplin)</label>
                                            <input type="file" name="file_mutasi_reg_f" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-heartbeat text-danger me-1"></i>Surat Kesehatan Dokter (Fit to Travel)</label>
                                            <input type="file" name="file_mutasi_kesehatan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-FORM: IZIN BEROBAT KELUAR KOTA -->
                            <div id="boxTambahBerobat" class="col-12 d-none">
                                <div class="p-3 bg-light rounded border border-danger border-opacity-50">
                                    <div class="fw-bold text-danger small mb-2"><i class="fas fa-heartbeat me-1"></i>Data Usulan Izin Berobat Keluar Kota</div>
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Lengkap Narapidana / Pasien <span class="text-danger">*</span></label>
                                            <input type="text" name="berobat_wbp_nama" class="form-control form-control-sm" placeholder="Nama pasien">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Nomor Register</label>
                                            <input type="text" name="berobat_nomor_register" class="form-control form-control-sm" placeholder="Nomor register">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Asal Lapas/Rutan</label>
                                            <select name="berobat_upt_asal_id" class="form-select form-select-sm">
                                                <option value="">-- Pilih UPT Asal --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Perkara / Vonis</label>
                                            <input type="text" name="berobat_perkara" class="form-control form-control-sm" placeholder="Perkara pidana">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Diagnosa Medis <span class="text-danger">*</span></label>
                                            <input type="text" name="berobat_diagnosa" class="form-control form-control-sm" placeholder="Diagnosa dokter spesialis">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Dokter Spesialis</label>
                                            <input type="text" name="berobat_dokter" class="form-control form-control-sm" placeholder="dr. Sp.B">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">RS Rujukan Luar Kota <span class="text-danger">*</span></label>
                                            <input type="text" name="berobat_rs_nama" class="form-control form-control-sm" placeholder="RSUP RSCM Jakarta / RS Kanker Dharmais">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Kota RS</label>
                                            <input type="text" name="berobat_rs_kota" class="form-control form-control-sm" placeholder="Jakarta Pusat">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Nama Penjamin Keluarga</label>
                                            <input type="text" name="berobat_penjamin_nama" class="form-control form-control-sm" placeholder="Nama penjamin">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Hubungan</label>
                                            <input type="text" name="berobat_penjamin_hubungan" class="form-control form-control-sm" placeholder="Istri / Orang Tua">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">No Kontak / WA</label>
                                            <input type="text" name="berobat_penjamin_kontak" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                        </div>

                                        <!-- Unggah Berkas Medis -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-file-medical text-danger me-1"></i>Unggah Berkas Medis (Opsional):</span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Resume Rekam Medis</label>
                                            <input type="file" name="file_medis_rekam" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Surat Rujukan Dokter/RSUD</label>
                                            <input type="file" name="file_medis_rujukan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">BA Sidang TPP UPT</label>
                                            <input type="file" name="file_medis_ba_upt" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Jaminan Pembiayaan Keluarga</label>
                                            <input type="file" name="file_medis_jaminan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-FORM: KLIEN BAPAS -->
                            <div id="boxTambahKlien" class="col-12 d-none">
                                <div class="p-3 bg-light rounded border border-primary border-opacity-50">
                                    <div class="fw-bold text-primary small mb-2"><i class="fas fa-passport me-1"></i>Data Klien Bapas (Izin Bepergian ke Luar Negeri)</div>
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Lengkap Klien <span class="text-danger">*</span></label>
                                            <input type="text" name="klien_nama" class="form-control form-control-sm" placeholder="Nama klien">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">BIN (Nama Ayah)</label>
                                            <input type="text" name="klien_bin" class="form-control form-control-sm" placeholder="Nama ayah">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Nomor Register Bapas</label>
                                            <input type="text" name="klien_no_register" class="form-control form-control-sm" placeholder="BPS-SRG/PB/2026/042">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Bapas Pembimbing</label>
                                            <select name="klien_bapas_nama" class="form-select form-select-sm">
                                                <option value="Balai Pemasyarakatan Kelas I Serang">Balai Pemasyarakatan Kelas I Serang</option>
                                                <option value="Balai Pemasyarakatan Kelas II Ciangir">Balai Pemasyarakatan Kelas II Ciangir</option>
                                                <option value="Balai Pemasyarakatan Lainnya">Balai Pemasyarakatan Lainnya</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Keperluan</label>
                                            <input type="text" name="klien_keperluan_ln" class="form-control form-control-sm" value="Menjalankan Ibadah Umroh">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Negara Tujuan</label>
                                            <input type="text" name="klien_negara_tujuan" class="form-control form-control-sm" value="Kerajaan Arab Saudi">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Program Integrasi</label>
                                            <input type="text" name="klien_program" class="form-control form-control-sm" value="Pembebasan Bersyarat (PB)">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Tgl Berangkat</label>
                                            <input type="date" name="klien_tgl_berangkat" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Tgl Kembali</label>
                                            <input type="date" name="klien_tgl_kembali" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Durasi (Hari)</label>
                                            <input type="number" name="klien_durasi_hari" class="form-control form-control-sm" value="14">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                            <input type="text" name="klien_penjamin_nama" class="form-control form-control-sm" placeholder="Nama penjamin">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Hubungan</label>
                                            <input type="text" name="klien_penjamin_hubungan" class="form-control form-control-sm" placeholder="Istri / Orang Tua">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Kontak/WA</label>
                                            <input type="text" name="klien_penjamin_telepon" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                                        </div>

                                        <!-- Unggah Berkas Klien -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-file-pdf text-primary me-1"></i>Unggah Berkas Klien (Opsional):</span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Litmas Bapas</label>
                                            <input type="file" name="file_litmas_bapas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Surat Usulan Bapas</label>
                                            <input type="file" name="file_surat_bapas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Tiket / Jadwal Travel</label>
                                            <input type="file" name="file_bukti_travel" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Surat Jaminan Penjamin</label>
                                            <input type="file" name="file_surat_jaminan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Deskripsi Pokok Bahasan -->
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Deskripsi / Uraian Pokok Pembahasan (Opsional)</label>
                                <textarea name="deskripsi_materi" class="form-control form-control-sm" rows="2" placeholder="Uraikan dasar pertimbangan, latar belakang perkara, atau hal spesifik yang dibahas..."></textarea>
                            </div>

                            <!-- Dokumen Tambahan Lainnya -->
                            <div class="col-12">
                                <div class="p-2.5 bg-white rounded border">
                                    <label class="form-label small fw-bold text-dark mb-1">
                                        <i class="fas fa-plus-circle text-primary me-1"></i>Dokumen Tambahan Lainnya (Opsional)
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <input type="text" name="dokumen_tambahan_nama" class="form-control form-control-sm" placeholder="Nama Dokumen (misal: KTP / Hasil Lab)">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="file" name="dokumen_tambahan_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Berkas Bahan Sidang -->
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Unggah File Bahan Sidang Materi Ini (PDF/Word/Excel/Zip)</label>
                                <input type="file" name="file_bahan_sidang" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                            </div>

                            <!-- Rekomendasi Sidang Langsung -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Status Rekomendasi Sidang (Opsional)</label>
                                <select name="rekomendasi_sidang" class="form-select form-select-sm">
                                    <option value="">-- Belum Diputuskan / Ditentukan Saat Sidang --</option>
                                    <option value="setuju">Disetujui</option>
                                    <option value="setuju_catatan">Disetujui Bersyarat (dg Catatan)</option>
                                    <option value="tunda">Ditunda</option>
                                    <option value="tolak">Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Catatan Rekomendasi Sidang</label>
                                <input type="text" name="catatan_rekomendasi" class="form-control form-control-sm" placeholder="Contoh: Disetujui dengan catatan wajib pengawalan ketat">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                            <i class="fas fa-plus me-1"></i> Tambahkan Materi ke Sidang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT MATERI (PER MATERI) -->
    @foreach($sidang->materis as $m)
    <div class="modal fade" id="modalEditMateri_{{ $m->id }}" tabindex="-1" aria-labelledby="modalEditMateriLabel_{{ $m->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('sidang-tpp-kanwil.materi.update', [$sidang->id, $m->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-dark text-white py-3 px-4">
                        <h6 class="modal-title fw-bold" id="modalEditMateriLabel_{{ $m->id }}">
                            <i class="fas fa-edit me-2"></i>Edit Materi #{{ $m->urutan }} - {{ $m->judul_materi }}
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-bold text-dark">Kategori Materi <span class="text-danger">*</span></label>
                                <select name="kategori_materi" class="form-select form-select-sm" required onchange="switchModalMateriCategory('edit_{{ $m->id }}', this.value)">
                                    <option value="mutasi_narapidana" {{ $m->kategori_materi == 'mutasi_narapidana' ? 'selected' : '' }}>🏢 Mutasi Narapidana Antar-UPT</option>
                                    <option value="mutasi_keinginan_sendiri" {{ $m->kategori_materi == 'mutasi_keinginan_sendiri' ? 'selected' : '' }}>👤 Mutasi Permintaan Sendiri (Mendekati Keluarga)</option>
                                    <option value="izin_berobat" {{ $m->kategori_materi == 'izin_berobat' ? 'selected' : '' }}>🏥 Izin Berobat Keluar Kota</option>
                                    <option value="klien_bapas" {{ $m->kategori_materi == 'klien_bapas' ? 'selected' : '' }}>⚖️ Klien Pemasyarakatan (Bapas)</option>
                                    <option value="lainnya" {{ $m->kategori_materi == 'lainnya' ? 'selected' : '' }}>📋 Lainnya / Pembahasan Mandiri</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-7">
                                <label class="form-label small fw-bold text-dark">Judul Materi / Pokok Agenda <span class="text-danger">*</span></label>
                                <input type="text" name="judul_materi" class="form-control form-control-sm" value="{{ old('judul_materi', $m->judul_materi) }}" required>
                            </div>

                            <!-- SUB-FORM EDIT: MUTASI -->
                            @php
                                $ndm = $m->narapidana_data ?? [];
                                $bm = $ndm['berkas'] ?? [];
                            @endphp
                            <div id="boxEditMutasi_{{ $m->id }}" class="col-12 {{ $m->kategori_materi == 'mutasi_narapidana' ? '' : 'd-none' }}">
                                <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                                    <div class="fw-bold text-warning-emphasis small mb-2"><i class="fas fa-exchange-alt me-1"></i>Data Mutasi Narapidana</div>
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Narapidana / WBP</label>
                                            <input type="text" name="mutasi_wbp_nama" class="form-control form-control-sm" value="{{ $ndm['nama'] ?? '' }}">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Nomor Register</label>
                                            <input type="text" name="mutasi_nomor_register" class="form-control form-control-sm" value="{{ $ndm['nomor_register'] ?? '' }}">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                            <input type="text" name="mutasi_perkara" class="form-control form-control-sm" value="{{ $ndm['tindak_pidana'] ?? '' }}">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">UPT Tujuan Rekomendasi Sidang</label>
                                            <select name="upt_tujuan_rekomendasi_id" class="form-select form-select-sm border-warning">
                                                <option value="">-- Pilih Rekomendasi Tujuan --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}" {{ ($m->upt_tujuan_rekomendasi_id == $u->id || ($ndm['upt_tujuan_id'] ?? null) == $u->id) ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                            <input type="text" name="mutasi_alasan" class="form-control form-control-sm" value="{{ $ndm['alasan_pemindahan'] ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-FORM EDIT: MUTASI PERMINTAAN SENDIRI -->
                            <div id="boxEditMutasiPribadi_{{ $m->id }}" class="col-12 {{ $m->kategori_materi == 'mutasi_keinginan_sendiri' ? '' : 'd-none' }}">
                                <div class="p-3 bg-light rounded border" style="border-color: #6f42c1 !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold small" style="color: #6f42c1;"><i class="fas fa-user-friends me-1"></i>Data Mutasi Permintaan Sendiri (Mendekati Keluarga & Biaya Mandiri)</div>
                                        <span class="badge text-white" style="background-color: #6f42c1 !important; font-size: 10px;">Khusus Keinginan Sendiri</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Narapidana / WBP</label>
                                            <input type="text" name="mutasi_wbp_nama" class="form-control form-control-sm" value="{{ $ndm['nama'] ?? '' }}">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Nomor Register</label>
                                            <input type="text" name="mutasi_nomor_register" class="form-control form-control-sm" value="{{ $ndm['nomor_register'] ?? '' }}">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Perkara / Pasal</label>
                                            <input type="text" name="mutasi_perkara" class="form-control form-control-sm" value="{{ $ndm['tindak_pidana'] ?? '' }}">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Lama Pidana</label>
                                            <input type="text" name="mutasi_lama_pidana" class="form-control form-control-sm" value="{{ $ndm['lama_pidana'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Asal</label>
                                            <select name="mutasi_upt_asal_id" class="form-select form-select-sm">
                                                <option value="">-- Pilih UPT Asal --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}" {{ ($ndm['upt_asal_id'] ?? null) == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">UPT Tujuan Rekomendasi Sidang</label>
                                            <select name="upt_tujuan_rekomendasi_id" class="form-select form-select-sm border-primary">
                                                <option value="">-- Pilih Rekomendasi Tujuan --</option>
                                                @foreach($uptList as $u)
                                                <option value="{{ $u->id }}" {{ ($m->upt_tujuan_rekomendasi_id == $u->id || ($ndm['upt_tujuan_id'] ?? null) == $u->id) ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Alasan Pemindahan</label>
                                            <input type="text" name="mutasi_alasan" class="form-control form-control-sm" value="{{ $ndm['alasan_pemindahan'] ?? '' }}">
                                        </div>

                                        <!-- PK Bapas & Litmas -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-balance-scale text-primary me-1"></i>Penelitian Kemasyarakatan (Litmas) & PK Bapas:</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Nama Petugas PK Bapas</label>
                                            <input type="text" name="nama_pk_bapas" class="form-control form-control-sm" value="{{ $ndm['pk_bapas']['nama'] ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">NIP / Kontak PK</label>
                                            <input type="text" name="nip_pk_bapas" class="form-control form-control-sm" value="{{ $ndm['pk_bapas']['nip'] ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Bapas Asal</label>
                                            <input type="text" name="bapas_nama" class="form-control form-control-sm" value="{{ $ndm['pk_bapas']['bapas'] ?? '' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Kesimpulan & Rekomendasi Litmas</label>
                                            <input type="text" name="rekomendasi_litmas" class="form-control form-control-sm" value="{{ $ndm['pk_bapas']['rekomendasi'] ?? '' }}">
                                        </div>

                                        <!-- BA Sidang TPP UPT -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-gavel text-warning-emphasis me-1"></i>Hasil Sidang TPP UPT Asal (Lapas/Rutan):</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Nomor Berita Acara (BA) UPT</label>
                                            <input type="text" name="nomor_ba_tpp_upt" class="form-control form-control-sm" value="{{ $ndm['tpp_upt']['nomor_ba'] ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Tanggal BA Sidang UPT</label>
                                            <input type="date" name="tanggal_ba_tpp_upt" class="form-control form-control-sm" value="{{ $ndm['tpp_upt']['tanggal_ba'] ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Rekomendasi Sidang UPT</label>
                                            <input type="text" name="rekomendasi_tpp_upt" class="form-control form-control-sm" value="{{ $ndm['tpp_upt']['rekomendasi'] ?? '' }}">
                                        </div>

                                        <!-- Penjamin -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-home text-success me-1"></i>Data Penjamin Keluarga:</span>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_nama" class="form-control form-control-sm" value="{{ $ndm['penjamin']['nama'] ?? '' }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">NIK KTP Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_nik" class="form-control form-control-sm" value="{{ $ndm['penjamin']['nik'] ?? '' }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Hubungan</label>
                                            <input type="text" name="mutasi_penjamin_hubungan" class="form-control form-control-sm" value="{{ $ndm['penjamin']['hubungan'] ?? '' }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Kontak Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_kontak" class="form-control form-control-sm" value="{{ $ndm['penjamin']['telepon'] ?? '' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Alamat Domisili Penjamin</label>
                                            <input type="text" name="mutasi_penjamin_alamat" class="form-control form-control-sm" value="{{ $ndm['penjamin']['alamat'] ?? '' }}">
                                        </div>

                                        <!-- Berkas Khusus -->
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <span class="fw-bold small text-dark"><i class="fas fa-file-upload text-primary me-1"></i>Perbarui Berkas Persyaratan Khusus:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-signature text-primary me-1"></i>Surat Pernyataan Mandiri Bermaterai</label>
                                            <input type="file" name="file_mutasi_pernyataan_pribadi" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                            @if(!empty($bm['pernyataan_pribadi']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['pernyataan_pribadi']) }}" target="_blank">Lihat Dokumen</a></div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-contract text-warning me-1"></i>BA Sidang TPP UPT Bertandatangan</label>
                                            <input type="file" name="file_mutasi_ba_upt" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                            @if(!empty($bm['ba_upt']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['ba_upt']) }}" target="_blank">Lihat BA TPP</a></div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Laporan Hasil Litmas Bapas</label>
                                            <input type="file" name="file_mutasi_litmas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                            @if(!empty($bm['litmas']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['litmas']) }}" target="_blank">Lihat Litmas</a></div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Surat Jaminan Penjamin Keluarga</label>
                                            <input type="file" name="file_mutasi_jaminan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                            @if(!empty($bm['surat_jaminan']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['surat_jaminan']) }}" target="_blank">Lihat Jaminan</a></div>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-id-card text-info me-1"></i>KTP & Kartu Keluarga Penjamin</label>
                                            <input type="file" name="file_mutasi_ktp_penjamin" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                            @if(!empty($bm['ktp_penjamin']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['ktp_penjamin']) }}" target="_blank">Lihat KTP/KK</a></div>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-book text-secondary me-1"></i>Buku Register F (Bebas Disiplin)</label>
                                            <input type="file" name="file_mutasi_reg_f" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                            @if(!empty($bm['register_f']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['register_f']) }}" target="_blank">Lihat Register F</a></div>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1"><i class="fas fa-heartbeat text-danger me-1"></i>Surat Kesehatan Dokter (Fit to Travel)</label>
                                            <input type="file" name="file_mutasi_kesehatan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                            @if(!empty($bm['kesehatan']))
                                            <div class="small text-muted mt-0.5">Ada file: <a href="{{ asset($bm['kesehatan']) }}" target="_blank">Lihat Surat Dokter</a></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-FORM EDIT: IZIN BEROBAT -->
                            <div id="boxEditBerobat_{{ $m->id }}" class="col-12 {{ $m->kategori_materi == 'izin_berobat' ? '' : 'd-none' }}">
                                <div class="p-3 bg-light rounded border border-danger border-opacity-50">
                                    <div class="fw-bold text-danger small mb-2"><i class="fas fa-heartbeat me-1"></i>Data Izin Berobat Keluar Kota</div>
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="form-label small text-muted mb-1">Nama Narapidana / Pasien</label>
                                            <input type="text" name="berobat_wbp_nama" class="form-control form-control-sm" value="{{ $ndm['nama'] ?? '' }}">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">Diagnosa Medis</label>
                                            <input type="text" name="berobat_diagnosa" class="form-control form-control-sm" value="{{ $ndm['diagnosa_medis'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Dokter Spesialis</label>
                                            <input type="text" name="berobat_dokter" class="form-control form-control-sm" value="{{ $ndm['dokter_penanggung_jawab'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">RS Rujukan &amp; Kota</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="berobat_rs_nama" class="form-control" value="{{ $ndm['rs_rujukan_nama'] ?? '' }}" placeholder="RSUP RSCM">
                                                <input type="text" name="berobat_rs_kota" class="form-control" value="{{ $ndm['rs_rujukan_kota'] ?? '' }}" placeholder="Jakarta">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Deskripsi / Uraian Pokok Materi</label>
                                <textarea name="deskripsi_materi" class="form-control form-control-sm" rows="2">{{ old('deskripsi_materi', $m->deskripsi_materi) }}</textarea>
                            </div>

                            <!-- Dokumen Tambahan Lainnya -->
                            <div class="col-12">
                                <div class="p-2.5 bg-white rounded border">
                                    <label class="form-label small fw-bold text-dark mb-1">
                                        <i class="fas fa-plus-circle text-primary me-1"></i>Dokumen Tambahan Lainnya
                                    </label>
                                    @if(!empty($m->dokumen_tambahan['file']))
                                    <div class="small mb-1 text-muted">
                                        Berkas saat ini: <a href="{{ asset($m->dokumen_tambahan['file']) }}" target="_blank"><i class="fas fa-paperclip me-1"></i>{{ $m->dokumen_tambahan['nama'] ?? 'Dokumen Tambahan' }}</a>
                                    </div>
                                    @endif
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <input type="text" name="dokumen_tambahan_nama" class="form-control form-control-sm" value="{{ $m->dokumen_tambahan['nama'] ?? '' }}" placeholder="Nama Dokumen Tambahan">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="file" name="dokumen_tambahan_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Berkas Bahan Sidang -->
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Perbarui File Bahan Sidang (PDF/Word/Excel/Zip)</label>
                                <input type="file" name="file_bahan_sidang" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                                @if($m->file_bahan_sidang)
                                <div class="small text-muted mt-1">Berkas saat ini: <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank"><i class="fas fa-paperclip me-1"></i>Unduh Bahan Sidang</a></div>
                                @endif
                            </div>

                            <!-- Rekomendasi Sidang -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Status Rekomendasi Sidang</label>
                                <select name="rekomendasi_sidang" class="form-select form-select-sm">
                                    <option value="" {{ empty($m->rekomendasi_sidang) ? 'selected' : '' }}>-- Belum Diputuskan --</option>
                                    <option value="setuju" {{ $m->rekomendasi_sidang == 'setuju' ? 'selected' : '' }}>Disetujui</option>
                                    <option value="setuju_catatan" {{ $m->rekomendasi_sidang == 'setuju_catatan' ? 'selected' : '' }}>Disetujui Bersyarat (dg Catatan)</option>
                                    <option value="tunda" {{ $m->rekomendasi_sidang == 'tunda' ? 'selected' : '' }}>Ditunda</option>
                                    <option value="tolak" {{ $m->rekomendasi_sidang == 'tolak' ? 'selected' : '' }}>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Catatan Rekomendasi Sidang</label>
                                <input type="text" name="catatan_rekomendasi" class="form-control form-control-sm" value="{{ old('catatan_rekomendasi', $m->catatan_rekomendasi) }}" placeholder="Catatan hasil rekomendasi">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan Materi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @endif
<script>
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const SIDANG_ID = {{ $sidang->id }};
    let dokumenCustomData = @json($sidang->dokumen_custom ?? []);

    let activeJenisDokumen = 'laporan';

    function switchAiDokumenTab(jenis) {
        activeJenisDokumen = jenis;
        document.querySelectorAll('.ai-editor-pane').forEach(el => el.classList.add('d-none'));

        if (jenis === 'laporan') {
            document.getElementById('editorGroupLaporan').classList.remove('d-none');
        } else if (jenis === 'risalah') {
            document.getElementById('editorGroupRisalah').classList.remove('d-none');
        } else if (jenis === 'berita_acara') {
            document.getElementById('editorGroupBeritaAcara').classList.remove('d-none');
        }

        loadSavedValuesIntoForm(jenis);
    }

    function loadSavedValuesIntoForm(jenis) {
        const saved = dokumenCustomData[jenis] || {};

        if (jenis === 'laporan') {
            document.getElementById('field_lap_kegiatan').value = Array.isArray(saved.kegiatan_dilaksanakan) ? saved.kegiatan_dilaksanakan.join("\n\n") : (saved.kegiatan_dilaksanakan || '');
            document.getElementById('field_lap_hasil').value = Array.isArray(saved.hasil_dicapai) ? saved.hasil_dicapai.join("\n") : (saved.hasil_dicapai || '');
            document.getElementById('field_lap_simpulan').value = Array.isArray(saved.simpulan) ? saved.simpulan.join("\n") : (saved.simpulan || '');
            document.getElementById('field_lap_saran').value = Array.isArray(saved.saran) ? saved.saran.join("\n") : (saved.saran || '');
        } else if (jenis === 'risalah') {
            document.getElementById('field_ris_materi').value = Array.isArray(saved.materi_sidang) ? saved.materi_sidang.join("\n") : (saved.materi_sidang || '');
            document.getElementById('field_ris_hasil').value = Array.isArray(saved.hasil_sidang) ? saved.hasil_sidang.join("\n") : (saved.hasil_sidang || '');
        } else if (jenis === 'berita_acara') {
            document.getElementById('field_ba_uraian').value = saved.uraian_sidang || '';
            document.getElementById('field_ba_kesimpulan').value = saved.kesimpulan_rekomendasi || '';
        }
    }

    async function jalankanGenerateAi() {
        const btn = document.getElementById('btnRunAiGenerate');
        const loading = document.getElementById('aiLoadingIndicator');
        const banner = document.getElementById('aiFeedbackBanner');
        const instruksi = document.getElementById('ai_instruksi_khusus').value.trim();

        btn.disabled = true;
        loading.classList.remove('d-none');
        banner.className = 'alert d-none py-2 px-3 mb-3';

        try {
            const resp = await fetch(`{{ url('/sidang-tpp-kanwil') }}/${SIDANG_ID}/ai-generate-naskah`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    jenis_dokumen: activeJenisDokumen,
                    instruksi_khusus: instruksi
                })
            });

            const res = await resp.json();

            if (res.success && res.data) {
                banner.className = 'alert alert-success py-2 px-3 mb-3 d-flex align-items-center gap-2';
                banner.innerHTML = `<i class="fas fa-check-circle"></i> <div>${res.message || 'Naskah berhasil diselaraskan AI. Silakan tinjau dan edit bila diperlukan sebelum disimpan.'}</div>`;
                populateAiGeneratedData(activeJenisDokumen, res.data);
            } else {
                banner.className = 'alert alert-danger py-2 px-3 mb-3';
                banner.innerText = res.message || 'Gagal menyelaraskan naskah.';
            }
        } catch (e) {
            banner.className = 'alert alert-danger py-2 px-3 mb-3';
            banner.innerText = 'Terjadi kesalahan koneksi saat memproses AI: ' + e;
        } finally {
            btn.disabled = false;
            loading.classList.add('d-none');
        }
    }

    function populateAiGeneratedData(jenis, data) {
        if (jenis === 'laporan') {
            if (data.kegiatan_dilaksanakan) {
                document.getElementById('field_lap_kegiatan').value = Array.isArray(data.kegiatan_dilaksanakan) ? data.kegiatan_dilaksanakan.join("\n\n") : data.kegiatan_dilaksanakan;
            }
            if (data.hasil_dicapai) {
                document.getElementById('field_lap_hasil').value = Array.isArray(data.hasil_dicapai) ? data.hasil_dicapai.join("\n") : data.hasil_dicapai;
            }
            if (data.simpulan) {
                document.getElementById('field_lap_simpulan').value = Array.isArray(data.simpulan) ? data.simpulan.join("\n") : data.simpulan;
            }
            if (data.saran) {
                document.getElementById('field_lap_saran').value = Array.isArray(data.saran) ? data.saran.join("\n") : data.saran;
            }
        } else if (jenis === 'risalah') {
            if (data.materi_sidang) {
                document.getElementById('field_ris_materi').value = Array.isArray(data.materi_sidang) ? data.materi_sidang.join("\n") : data.materi_sidang;
            }
            if (data.hasil_sidang) {
                document.getElementById('field_ris_hasil').value = Array.isArray(data.hasil_sidang) ? data.hasil_sidang.join("\n") : data.hasil_sidang;
            }
        } else if (jenis === 'berita_acara') {
            if (data.uraian_sidang) {
                document.getElementById('field_ba_uraian').value = data.uraian_sidang;
            }
            if (data.kesimpulan_rekomendasi) {
                document.getElementById('field_ba_kesimpulan').value = data.kesimpulan_rekomendasi;
            }
        }
    }

    async function simpanNaskahAi() {
        const btn = document.getElementById('btnSaveAiNaskah');
        const banner = document.getElementById('aiFeedbackBanner');
        btn.disabled = true;

        let payloadData = {};
        if (activeJenisDokumen === 'laporan') {
            const lapKegiatan = document.getElementById('field_lap_kegiatan').value.trim();
            const lapHasil = document.getElementById('field_lap_hasil').value.trim().split("\n").filter(x => x.trim().length > 0);
            const lapSimpulan = document.getElementById('field_lap_simpulan').value.trim().split("\n").filter(x => x.trim().length > 0);
            const lapSaran = document.getElementById('field_lap_saran').value.trim().split("\n").filter(x => x.trim().length > 0);

            payloadData = {
                kegiatan_dilaksanakan: lapKegiatan,
                hasil_dicapai: lapHasil,
                simpulan: lapSimpulan,
                saran: lapSaran
            };
        } else if (activeJenisDokumen === 'risalah') {
            const risMateri = document.getElementById('field_ris_materi').value.trim().split("\n").filter(x => x.trim().length > 0);
            const risHasil = document.getElementById('field_ris_hasil').value.trim().split("\n").filter(x => x.trim().length > 0);

            payloadData = {
                materi_sidang: risMateri,
                hasil_sidang: risHasil
            };
        } else if (activeJenisDokumen === 'berita_acara') {
            const baUraian = document.getElementById('field_ba_uraian').value.trim();
            const baKesimpulan = document.getElementById('field_ba_kesimpulan').value.trim();

            payloadData = {
                uraian_sidang: baUraian,
                kesimpulan_rekomendasi: baKesimpulan
            };
        }

        try {
            const resp = await fetch(`{{ url('/sidang-tpp-kanwil') }}/${SIDANG_ID}/ai-save-naskah`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    jenis_dokumen: activeJenisDokumen,
                    data: payloadData
                })
            });

            const res = await resp.json();
            if (res.success) {
                alert(res.message || 'Naskah dinas berhasil disimpan! Dokumen PDF & Word akan langsung menggunakan rumusan baru ini.');
                location.reload();
            } else {
                banner.className = 'alert alert-danger py-2 px-3 mb-3';
                banner.innerText = res.message || 'Gagal menyimpan naskah.';
            }
        } catch (e) {
            banner.className = 'alert alert-danger py-2 px-3 mb-3';
            banner.innerText = 'Terjadi kesalahan saat menyimpan naskah: ' + e;
        } finally {
            btn.disabled = false;
        }
    }

    async function resetCurrentNaskahAi() {
        if (!confirm(`Kembalikan naskah ${activeJenisDokumen} ke standar formula sistem bawaan?`)) return;

        try {
            const resp = await fetch(`{{ url('/sidang-tpp-kanwil') }}/${SIDANG_ID}/ai-reset-naskah`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    jenis_dokumen: activeJenisDokumen
                })
            });

            const res = await resp.json();
            alert(res.message || 'Naskah berhasil dikembalikan ke standar bawaan.');
            location.reload();
        } catch (e) {
            alert('Gagal mereset naskah: ' + e);
        }
    }

    async function resetSemuaNaskahAi() {
        if (!confirm('Kembalikan seluruh naskah dinas ke standar formula dinas bawaan sistem?')) return;

        try {
            const resp = await fetch(`{{ url('/sidang-tpp-kanwil') }}/${SIDANG_ID}/ai-reset-naskah`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });

            const res = await resp.json();
            alert(res.message || 'Seluruh naskah dinas berhasil dikembalikan ke standar bawaan.');
            location.reload();
        } catch (e) {
            alert('Gagal mereset naskah: ' + e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadSavedValuesIntoForm('laporan');
    });

    function switchModalMateriCategory(prefix, val) {
        let boxMutasi, boxMutasiPribadi, boxBerobat, boxKlien;
        if (prefix === 'tambah') {
            boxMutasi = document.getElementById('boxTambahMutasi');
            boxMutasiPribadi = document.getElementById('boxTambahMutasiPribadi');
            boxBerobat = document.getElementById('boxTambahBerobat');
            boxKlien = document.getElementById('boxTambahKlien');
        } else {
            const id = prefix.replace('edit_', '');
            boxMutasi = document.getElementById('boxEditMutasi_' + id);
            boxMutasiPribadi = document.getElementById('boxEditMutasiPribadi_' + id);
            boxBerobat = document.getElementById('boxEditBerobat_' + id);
            boxKlien = document.getElementById('boxEditKlien_' + id);
        }
        if (boxMutasi) boxMutasi.classList.add('d-none');
        if (boxMutasiPribadi) boxMutasiPribadi.classList.add('d-none');
        if (boxBerobat) boxBerobat.classList.add('d-none');
        if (boxKlien) boxKlien.classList.add('d-none');

        if (val === 'mutasi_narapidana' && boxMutasi) boxMutasi.classList.remove('d-none');
        else if (val === 'mutasi_keinginan_sendiri' && boxMutasiPribadi) boxMutasiPribadi.classList.remove('d-none');
        else if (val === 'izin_berobat' && boxBerobat) boxBerobat.classList.remove('d-none');
        else if (val === 'klien_bapas' && boxKlien) boxKlien.classList.remove('d-none');
    }

    function autoFillMutasiModal(selectEl, prefix) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) return;
        const nama = opt.getAttribute('data-nama') || '';
        const uptAsal = opt.getAttribute('data-uptasal') || '';
        const uptTujuan = opt.getAttribute('data-upttujuan') || '';
        const alasan = opt.getAttribute('data-alasan') || '';
        
        const namaEl = document.getElementById(prefix + '_mutasi_wbp_nama');
        const uptAsalEl = document.getElementById(prefix + '_mutasi_upt_asal_id');
        const uptTujuanEl = document.getElementById(prefix + '_upt_tujuan_rekomendasi_id');
        const alasanEl = document.getElementById(prefix + '_mutasi_alasan');
        const modalEl = selectEl.closest('.modal');
        const judulEl = modalEl ? modalEl.querySelector('input[name="judul_materi"]') : document.getElementById(prefix + '_judul_materi');

        if (namaEl && nama) namaEl.value = nama;
        if (uptAsalEl && uptAsal) uptAsalEl.value = uptAsal;
        if (uptTujuanEl && uptTujuan) uptTujuanEl.value = uptTujuan;
        if (alasanEl && alasan) alasanEl.value = alasan;
        if (judulEl && (!judulEl.value || judulEl.value.trim() === '')) {
            judulEl.value = 'Usulan Pemindahan Narapidana an. ' + nama;
        }
    }

    function copyFormatGrupWa() {
        const text = document.getElementById('rawTeksGrupWa').value.trim();
        copyToClipboard(text, 'Seluruh daftar tautan dan format siaran undangan grup WhatsApp berhasil disalin! Silakan langsung paste (Ctrl+V) ke grup WhatsApp.');
    }

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
