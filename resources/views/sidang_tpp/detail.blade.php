@extends('layouts.app')

@section('title', 'Detail Sidang TPP - Sultan Banten')
@section('page_title')
    <i class="fas fa-gavel me-2 text-primary"></i>Detail Sidang TPP &bull; {{ $sidang->upt?->nama_upt }}
@endsection

@push('styles')
<style>
    .photo-card {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .photo-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    .photo-thumb {
        width: 100%;
        height: 180px;
        object-fit: cover;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
@php
    $currentUser = auth()->user();
    $canUploadHasil = $currentUser->canManage('sidang_tpp') && ($currentUser->isAdmin() || (!empty($currentUser->upt_id) && (int) $currentUser->upt_id === (int) $sidang->upt_id));
@endphp
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('sidang-tpp.index') }}" class="text-decoration-none">Sidang TPP</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail Sidang #{{ $sidang->id }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">Sidang TPP: {{ $sidang->upt?->nama_upt }} ({{ $sidang->tanggal_sidang->translatedFormat('d F Y') }})</h4>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('sidang-tpp.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
            <form action="{{ route('sidang-tpp.broadcast-wa', $sidang->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirimkan notifikasi WhatsApp undangan sidang ini ke Grup sekarang?');">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm shadow-sm" title="Kirim Notifikasi WhatsApp Undangan Sidang TPP ke Grup">
                    <i class="fab fa-whatsapp me-1"></i>Kirim Undangan ke Grup WA
                </button>
            </form>
        </div>
    </div>

    <!-- BANNER KHUSUS: Laporan Sidang TPP Kantor Wilayah (Hasil Akhir untuk Usulan Hak Bersyarat) -->
    @if($sidang->hasLaporanKanwil())
    <div class="card border-0 text-white shadow mb-4 p-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; border-radius: 12px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-file-signature fa-3x text-white-50"></i>
                <div>
                    <span class="badge bg-white text-success fw-bold px-2 py-1 mb-1">DOKUMEN HASIL AKHIR TERSEDIA</span>
                    <h5 class="fw-bold mb-1">Laporan Sidang TPP Kantor Wilayah (TTD Ka. Kanwil)</h5>
                    <div class="small text-white-50">
                        Nomor Surat Kanwil: <b class="text-white">{{ $sidang->nomor_laporan_kanwil }}</b> &bull; Tanggal Pengesahan: <b class="text-white">{{ $sidang->tanggal_ttd_kakanwil ? $sidang->tanggal_ttd_kakanwil->format('d/m/Y') : '-' }}</b>
                    </div>
                    <div class="small mt-1 text-white opacity-90">
                        Dokumen resmi ini merupakan hasil akhir yang diunduh UPT sebagai dasar pengusulan hak bersyarat (PB/CB/CMB/Asimilasi/Remisi) pada SDP Ditjenpas.
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sidang-tpp.download-laporan-kanwil', ['id' => $sidang->id, 'preview' => 1]) }}" target="_blank" rel="noopener noreferrer" class="btn btn-light btn-lg px-3 text-success fw-bold shadow">
                    <i class="fas fa-external-link-alt me-2"></i>Buka Laporan (Tab Baru)
                </a>
                <a href="{{ route('sidang-tpp.download-laporan-kanwil', $sidang->id) }}" class="btn btn-outline-light btn-lg px-3 text-white fw-bold shadow">
                    <i class="fas fa-download me-2"></i>Download (PDF)
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="card border-0 bg-light border-start border-4 border-warning shadow-sm mb-4 p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-hourglass-half fa-2x text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Laporan Sidang TPP Kantor Wilayah Belum Diunggah</h6>
                    <p class="small text-muted mb-0">Menunggu Kantor Wilayah memverifikasi dan mengunggah dokumen Laporan Hasil Sidang bertanda tangan Ka. Kanwil untuk diunduh UPT.</p>
                </div>
            </div>
            @if(auth()->user()->isAdmin() || !auth()->user()->upt_id)
            <a href="{{ route('sidang-tpp.kanwil') }}" class="btn btn-success btn-sm px-3 fw-bold">
                <i class="fas fa-file-upload me-1"></i>Buka Menu Verifikasi &amp; Upload Laporan Kanwil
            </a>
            @endif
        </div>
    </div>
    @endif

    <!-- Status Banner Ringkasan -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block">Status Pelaksanaan</small>
                    <div class="mt-1">{!! $sidang->status_badge !!}</div>
                </div>
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block">Verifikasi Kesesuaian Dokumen</small>
                    <div class="mt-1">{!! $sidang->verifikasi_badge !!}</div>
                </div>
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block">Total Usulan Warga Binaan</small>
                    <div class="fw-bold fs-5 text-dark mt-1">
                        {{ $sidang->total_usulan }} <span class="fs-6 text-muted font-normal">Orang</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Rasio Keputusan</small>
                    <div class="small fw-semibold mt-1">
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $sidang->total_setuju }} Setuju</span> &bull; 
                        <span class="text-warning"><i class="fas fa-pause-circle me-1"></i>{{ $sidang->total_tunda }} Tunda</span> &bull; 
                        <span class="text-danger"><i class="fas fa-times-circle me-1"></i>{{ $sidang->total_tidak_setuju }} Tidak Setuju</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Detail Grid -->
    <div class="row g-4 mb-4">
        <!-- Kolom Kiri: Undangan & Susunan Tim -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-envelope-open-text me-2"></i>1. Surat Undangan Ke Kantor Wilayah</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 170px;">Nomor Undangan</td>
                            <td class="fw-bold text-dark">: {{ $sidang->nomor_undangan }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Surat</td>
                            <td>: {{ $sidang->tanggal_surat->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jadwal Pelaksanaan</td>
                            <td>: {{ $sidang->tanggal_sidang->format('d/m/Y') }} (Pukul {{ substr((string)$sidang->jam_mulai, 0, 5) }} WIB)</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Agenda Sidang</td>
                            <td class="fw-semibold text-dark">: {{ $sidang->agenda_sidang ?? $sidang->agenda_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ketua &amp; Sekretaris TPP</td>
                            <td>: {{ $sidang->ketua_tpp }} &amp; {{ $sidang->sekretaris_tpp }}</td>
                        </tr>
                        @if(!empty($sidang->nama_pk_bapas))
                        <tr>
                            <td class="text-muted">PK Bapas Pendamping</td>
                            <td>: {{ $sidang->nama_pk_bapas }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Berkas Undangan (PDF)</td>
                            <td>: 
                                @if(!empty($sidang->file_undangan))
                                <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                                    <a href="{{ route('sidang-tpp.public-undangan', $sidang->id) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold" title="Buka Surat Undangan di Tab Baru (Tanpa Login)">
                                        <i class="fas fa-external-link-alt me-1"></i>Lihat Undangan
                                    </a>
                                    <a href="{{ route('sidang-tpp.public-undangan', ['id' => $sidang->id, 'download' => 1]) }}" class="btn btn-light btn-sm py-0 px-1.5 border" title="Unduh File Asli">
                                        <i class="fas fa-download text-muted"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 fw-semibold" onclick="copyPublicUndanganLink('{{ route('sidang-tpp.public-undangan', $sidang->id) }}')" title="Salin tautan publik yang dapat dibuka siapa saja tanpa login">
                                        <i class="fas fa-share-alt me-1"></i>Salin Link Publik
                                    </button>
                                </div>
                                <div class="mt-1 small text-muted">
                                    <i class="fas fa-globe text-success me-1"></i>Link publik: <a href="{{ route('sidang-tpp.public-undangan', $sidang->id) }}" target="_blank" class="text-decoration-none font-monospace">{{ route('sidang-tpp.public-undangan', $sidang->id) }}</a> <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Bisa Dibuka Tanpa Login</span>
                                </div>
                                @else
                                <span class="text-muted small">Tidak ada lampiran PDF</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Fasilitasi Daring & Bintorwasdal Kanwil -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-video me-2"></i>2. Fasilitasi Sidang Daring &amp; Bintorwasdal Kanwil</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 170px;">Metode Sidang</td>
                            <td class="fw-bold">: {{ $sidang->metode_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tautan / Link Daring</td>
                            <td>: 
                                @if(!empty($sidang->link_daring))
                                <a href="{{ $sidang->link_daring }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm py-0 px-2 fw-semibold">
                                    <i class="fas fa-video me-1"></i>Buka Ruang Zoom / Meet
                                </a>
                                @else
                                <span class="text-muted small">Tatap Muka di Lapas/Rutan</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Meeting ID / Passcode</td>
                            <td>: {{ $sidang->meeting_id ?: '-' }} / {{ $sidang->passcode ?: '-' }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted pt-2">Pengawas Kanwil Hadir</td>
                            <td class="pt-2">: {{ $sidang->kanwil_hadir ? 'Ya (Hadir/Memantau)' : 'Belum/Tidak Hadir Langsung' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama Pengawas Kanwil</td>
                            <td>: {{ $sidang->nama_pengawas_kanwil ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Catatan Bintorwasdal</td>
                            <td>: <em>{{ $sidang->catatan_bintorwasdal ?: 'Belum ada catatan dari pembina Kanwil.' }}</em></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nomor Berita Acara (BA) UPT</td>
                            <td>: 
                                @if(!empty($sidang->nomor_berita_acara))
                                <b>{{ $sidang->nomor_berita_acara }}</b>
                                @if(!empty($sidang->file_berita_acara))
                                <div class="d-inline-flex align-items-center gap-1 ms-1">
                                    <a href="{{ route('sidang-tpp.download-file', ['id' => $sidang->id, 'type' => 'berita_acara', 'preview' => 1]) }}" target="_blank" rel="noopener noreferrer" class="badge bg-light text-primary border text-decoration-none py-1 px-2" title="Buka Berita Acara di Tab Baru">
                                        <i class="fas fa-external-link-alt text-primary me-1"></i>Lihat BA
                                    </a>
                                    <a href="{{ route('sidang-tpp.download-file', ['id' => $sidang->id, 'type' => 'berita_acara']) }}" class="badge bg-light text-secondary border text-decoration-none py-1 px-1.5" title="Unduh Berkas Asli">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                                @endif
                                @if($canUploadHasil)
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 ms-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalHasilSidangDetail" title="Perbarui Berita Acara atau Ganti Berkas PDF">
                                    <i class="fas fa-edit me-1"></i>Ganti / Edit BA
                                </button>
                                @endif
                                @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle me-2">Belum Diunggah</span>
                                @if($canUploadHasil)
                                <button type="button" class="btn btn-primary btn-sm py-0 px-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHasilSidangDetail" title="Unggah Berita Acara Sidang TPP">
                                    <i class="fas fa-file-upload me-1"></i>Upload BA Sekarang
                                </button>
                                @else
                                <span class="text-muted small">-</span>
                                @endif
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. DOKUMEN & NASKAH KANTOR WILAYAH (KHUSUS PENGGUNA KANWIL) -->
    @if(auth()->user()->isKanwil())
    @php
        $notaDinasKanwil = $sidang->naskahKanwil?->where('jenis_dokumen', 'nota_dinas')->first();
        $stKanwil = $sidang->naskahKanwil?->where('jenis_dokumen', 'surat_tugas')->first();
        $lapKanwilDok = $sidang->naskahKanwil?->where('jenis_dokumen', 'laporan_tpp')->first();
        $fileSt = !empty($sidang->file_surat_tugas) ? $sidang->file_surat_tugas : (!empty($stKanwil?->file_signed) ? $stKanwil->file_signed : null);
        $noSt = $sidang->nomor_surat_tugas ?: ($stKanwil?->nomor_dokumen ?: '');
        $fileFinalLap = !empty($lapKanwilDok?->file_signed) ? $lapKanwilDok->file_signed : $sidang->file_laporan_kakanwil;
    @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-stamp text-primary me-2"></i>3. Dokumen &amp; Naskah Kantor Wilayah
                </h6>
                <small class="text-muted">Pengelolaan Nota Dinas, Surat Tugas Tim Kanwil, dan Laporan Hasil Sidang Kanwil</small>
            </div>
            <a href="{{ route('sidang-tpp.dokumen-kanwil.index', ['search' => $sidang->nomor_undangan ?: ($sidang->upt?->nama_upt ?? '')]) }}" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="fas fa-folder-open me-1"></i>Buka Menu Dokumen Kanwil
            </a>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <!-- KOTAK 1: NOTA DINAS PERMOHONAN ST -->
                <div class="col-lg-4 col-md-6">
                    <div class="border rounded p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1" style="font-size: 10px;">DOKUMEN 1</span>
                                    <h6 class="fw-bold mb-0 text-dark">Nota Dinas Permohonan ST</h6>
                                </div>
                                @if(!empty($notaDinasKanwil?->file_signed))
                                    <span class="badge bg-success text-white"><i class="fas fa-qrcode me-1"></i>Ber-QR</span>
                                @elseif($notaDinasKanwil)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-file-word me-1 text-primary"></i>Draf Siap</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border">Belum Dibuat</span>
                                @endif
                            </div>

                            <div class="small text-muted mb-3">
                                @if($notaDinasKanwil)
                                    Nomor: <b class="text-dark">{{ $notaDinasKanwil->nomor_dokumen ?: '-' }}</b>
                                    @if($notaDinasKanwil->kode_verifikasi)
                                        <br><span class="text-muted">Kode QR:</span> <code class="text-primary">{{ $notaDinasKanwil->kode_verifikasi }}</code>
                                    @endif
                                @else
                                    Dibuat dari surat undangan UPT untuk permohonan penerbitan Surat Tugas pembinaan sidang.
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-1.5 pt-2 border-top">
                            @if(!empty($notaDinasKanwil?->file_signed))
                                <a href="{{ asset($notaDinasKanwil->file_signed) }}" target="_blank" class="btn btn-success btn-sm fw-bold shadow-sm w-100 mb-1">
                                    <i class="fas fa-file-pdf me-1"></i>Unduh Nota Dinas
                                </a>
                                @if($notaDinasKanwil->kode_verifikasi)
                                    <a href="{{ $notaDinasKanwil->getPublicVerificationUrl() }}" target="_blank" class="btn btn-outline-secondary btn-xs py-1 px-2" title="Periksa Keabsahan QR Code">
                                        <i class="fas fa-qrcode me-1"></i>QR
                                    </a>
                                @endif
                                @if($notaDinasKanwil->canBeReplacedBy(auth()->user()))
                                    <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalNotaDinas" title="Edit Draf / Generate Ulang Word">
                                        <i class="fas fa-edit me-1"></i>Edit Word
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2" onclick="bukaModalUploadSignedDetail({{ $sidang->id }}, 'nota_dinas', '{{ addslashes($notaDinasKanwil->nomor_dokumen) }}')" title="Upload Ulang Scan & Pembubuhan QR (Khusus Admin Kanwil)">
                                        <i class="fas fa-sync-alt me-1"></i>Re-upload
                                    </button>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-1 px-2 small" title="Dokumen ini terkunci setelah dibubuhi QR Code resmi. Hanya Admin Kanwil yang dapat mengganti berkas.">
                                        <i class="fas fa-lock me-1 text-danger"></i>Terkunci (Khusus Admin)
                                    </span>
                                @endif
                            @elseif($notaDinasKanwil)
                                <button type="button" class="btn btn-outline-primary btn-sm fw-semibold flex-grow-1" data-bs-toggle="modal" data-bs-target="#modalNotaDinas">
                                    <i class="fas fa-file-word me-1"></i>Edit / Word
                                </button>
                                <button type="button" class="btn btn-warning btn-sm text-dark fw-bold shadow-sm flex-grow-1" onclick="bukaModalUploadSignedDetail({{ $sidang->id }}, 'nota_dinas', '{{ addslashes($notaDinasKanwil->nomor_dokumen) }}')">
                                    <i class="fas fa-qrcode me-1"></i>Upload &amp; QR
                                </button>
                            @else
                                <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#modalNotaDinas">
                                    <i class="fas fa-magic me-1"></i>Buat Nota Dinas (ST)
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- KOTAK 2: SURAT TUGAS TIM KANWIL (UPLOAD ADMIN KANWIL) -->
                <div class="col-lg-4 col-md-6">
                    <div class="border rounded p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle mb-1" style="font-size: 10px;">DOKUMEN 2</span>
                                    <h6 class="fw-bold mb-0 text-dark">Surat Tugas (ST) Tim Kanwil</h6>
                                </div>
                                @if(!empty($fileSt))
                                    <span class="badge bg-success text-white"><i class="fas fa-file-pdf me-1"></i>Tersedia</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-clock me-1"></i>Belum Diunggah</span>
                                @endif
                            </div>

                            <div class="small text-muted mb-3">
                                Nomor: <b class="text-dark">{{ $noSt ?: '-' }}</b>
                                @if($sidang->tanggal_surat_tugas)
                                    <br><span class="text-muted"><i class="far fa-calendar-alt me-1"></i>Tanggal:</span> {{ $sidang->tanggal_surat_tugas->translatedFormat('d F Y') }}
                                @endif
                                @if($stKanwil?->kode_verifikasi)
                                    <br><span class="text-muted">Kode QR:</span> <code class="text-primary">{{ $stKanwil->kode_verifikasi }}</code>
                                @endif
                                @if(empty($fileSt))
                                    <div class="text-muted small mt-1">Diterbitkan oleh Pimpinan (Kakanwil/Kadivpas) berdasarkan Nota Dinas. Diunggah oleh Admin Kanwil.</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-1.5 pt-2 border-top">
                            @if(!empty($fileSt))
                                <a href="{{ asset($fileSt) }}" target="_blank" class="btn btn-success btn-sm fw-bold shadow-sm w-100 mb-1">
                                    <i class="fas fa-file-pdf me-1"></i>Unduh Surat Tugas
                                </a>
                                @if($stKanwil?->kode_verifikasi)
                                    <a href="{{ $stKanwil->getPublicVerificationUrl() }}" target="_blank" class="btn btn-outline-secondary btn-xs py-1 px-2" title="Periksa Keabsahan QR Code">
                                        <i class="fas fa-qrcode me-1"></i>Verifikasi QR
                                    </a>
                                @endif
                                @if(! $stKanwil || $stKanwil->canBeReplacedBy(auth()->user()))
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 flex-grow-1" onclick="bukaModalUploadSignedDetail({{ $sidang->id }}, 'surat_tugas', '{{ addslashes($noSt) }}')" title="Upload Ulang / Timpa File Surat Tugas (Khusus Admin Kanwil)">
                                        <i class="fas fa-sync-alt me-1"></i>Re-upload ST
                                    </button>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-1 px-2 small flex-grow-1 text-center" title="Surat Tugas terkunci. Hanya Admin Kanwil yang dapat mengganti berkas.">
                                        <i class="fas fa-lock me-1 text-danger"></i>Terkunci (Khusus Admin)
                                    </span>
                                @endif
                            @else
                                <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm w-100" onclick="bukaModalUploadSignedDetail({{ $sidang->id }}, 'surat_tugas', '{{ addslashes($noSt) }}')">
                                    <i class="fas fa-upload me-1"></i>Upload Surat Tugas Tim
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- KOTAK 3: LAPORAN SIDANG TPP KANWIL -->
                <div class="col-lg-4 col-md-12">
                    <div class="border rounded p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-1" style="font-size: 10px;">DOKUMEN 3</span>
                                    <h6 class="fw-bold mb-0 text-dark">Laporan Sidang TPP Kanwil</h6>
                                </div>
                                @if(!empty($fileFinalLap))
                                    <span class="badge bg-success text-white"><i class="fas fa-check-double me-1"></i>Selesai &amp; Ber-QR</span>
                                @elseif($lapKanwilDok)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-clock me-1"></i>Draf Siap</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border">Belum Dibuat</span>
                                @endif
                            </div>

                            <div class="small text-muted mb-3">
                                @if($lapKanwilDok || $sidang->nomor_laporan_kanwil)
                                    Nomor: <b class="text-dark">{{ $lapKanwilDok?->nomor_dokumen ?: ($sidang->nomor_laporan_kanwil ?: '-') }}</b>
                                    @if($lapKanwilDok?->kode_verifikasi)
                                        <br><span class="text-muted">Kode QR:</span> <code class="text-primary">{{ $lapKanwilDok->kode_verifikasi }}</code>
                                    @endif
                                @else
                                    Dibuat dari Berita Acara (BA) sidang UPT sebagai laporan pertanggungjawaban ke Kakanwil.
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-1.5 pt-2 border-top">
                            @if(!empty($fileFinalLap))
                                <a href="{{ asset($fileFinalLap) }}" target="_blank" class="btn btn-success btn-sm fw-bold shadow-sm w-100 mb-1">
                                    <i class="fas fa-file-pdf me-1"></i>Unduh Laporan (Ber-QR)
                                </a>
                                @if($lapKanwilDok?->kode_verifikasi)
                                    <a href="{{ $lapKanwilDok->getPublicVerificationUrl() }}" target="_blank" class="btn btn-outline-secondary btn-xs py-1 px-2" title="Periksa Keabsahan QR Code">
                                        <i class="fas fa-qrcode me-1"></i>QR
                                    </a>
                                @endif
                            @endif
                            <a href="{{ route('sidang-tpp.dokumen-kanwil.index', ['search' => $sidang->nomor_undangan ?: ($sidang->upt?->nama_upt ?? '')]) }}" class="btn btn-outline-primary btn-sm fw-semibold flex-grow-1">
                                <i class="fas fa-cog me-1"></i>Kelola Laporan di Kanwil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- DOKUMENTASI FOTO SIDANG TPP (KOMPRESI JPG <= 500 KB) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-camera text-primary me-2"></i>Foto Dokumentasi Sidang TPP (Format JPG &bull; Maks. 500 KB / Foto)</h6>
                <small class="text-muted">Foto kegiatan sidang yang telah dikompresi otomatis oleh sistem agar hemat kapasitas penyimpanan.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border">{{ count($sidang->dokumentasi_list) }} Foto Dokumentasi</span>
                @if($canUploadHasil)
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalHasilSidangDetail">
                    <i class="fas fa-plus me-1"></i>Tambah Foto
                </button>
                @endif
            </div>
        </div>
        <div class="card-body p-3">
            @if(count($sidang->dokumentasi_list) > 0)
            <div class="row g-3">
                @foreach($sidang->dokumentasi_list as $idx => $fotoPath)
                @php
                    $fullPath = public_path($fotoPath);
                    $exists = file_exists($fullPath);
                    $fileKb = $exists ? round(filesize($fullPath) / 1024, 1) : 0;
                @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="photo-card shadow-sm">
                        @if($exists)
                        <a href="{{ asset($fotoPath) }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset($fotoPath) }}" alt="Dokumentasi Sidang {{ $idx + 1 }}" class="photo-thumb" loading="lazy">
                        </a>
                        <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Foto {{ $idx + 1 }} (.JPG)</span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="small fw-semibold {{ $fileKb <= 500 ? 'text-success' : 'text-danger' }}" style="font-size: 11px;">
                                    <i class="fas fa-weight-hanging me-1"></i>{{ $fileKb }} KB
                                </span>
                                @if($canUploadHasil)
                                <form action="{{ route('sidang-tpp.destroy-dokumentasi', $sidang->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus foto dokumentasi ini?');">
                                    @csrf
                                    <input type="hidden" name="photo_index" value="{{ $idx }}">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-1" title="Hapus Foto">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="photo-thumb d-flex align-items-center justify-content-center bg-light text-muted">
                            <small>Berkas tidak ditemukan</small>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-camera fa-2x mb-2 text-secondary opacity-50"></i>
                <p class="small mb-2">Belum ada foto dokumentasi yang diunggah untuk sidang ini.</p>
                @if($canUploadHasil)
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHasilSidangDetail">
                    <i class="fas fa-camera me-1"></i>Unggah Foto Dokumentasi Sekarang
                </button>
                @else
                <small class="text-muted">UPT pelaksana dapat mengunggah foto melalui form Hasil Sidang.</small>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Tabel Daftar Usulan Warga Binaan -->
    @php
        $canManagePeserta = $currentUser->canManage('sidang_tpp') && !empty($currentUser->upt_id) && ((int) $currentUser->upt_id === (int) $sidang->upt_id) && $sidang->status_sidang !== 'selesai_ttd_kakanwil';
    @endphp

    <!-- Tabel Daftar Usulan Warga Binaan -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users text-primary me-2"></i>Daftar Warga Binaan Pemasyarakatan yang Disidangkan ({{ $sidang->usulan->count() }} Orang)</h6>
            <div class="d-flex align-items-center gap-2">
                @if($canUploadHasil)
                <button type="button" class="btn btn-outline-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHasilSidangDetail">
                    <i class="fas fa-clipboard-check me-1"></i>Input / Edit Rekomendasi Putusan
                </button>
                @endif
                @if($canManagePeserta)
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" onclick="bukaModalTambahPesertaDetail()">
                    <i class="fas fa-user-plus me-1"></i>Tambah Peserta Sidang
                </button>
                @elseif(!empty($currentUser->upt_id))
                <span class="badge bg-secondary py-1 px-2 small">
                    <i class="fas fa-eye me-1"></i>Mode Lihat Saja
                </span>
                @elseif(empty($currentUser->upt_id))
                <span class="badge bg-light text-secondary border py-1 px-2 small">
                    <i class="fas fa-shield-alt text-primary me-1"></i>Akses Kanwil: Pengawas &amp; Verifikator (Read Only)
                </span>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light small fw-bold text-secondary">
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th>Nama Warga Binaan</th>
                        <th>No. Register</th>
                        <th>Tindak Pidana / Pasal</th>
                        <th>Pidana</th>
                        <th class="text-center">Tgl 2/3</th>
                        <th class="text-center">Jenis Usulan</th>
                        <th class="text-center" style="width: 170px;">Menarik Perhatian</th>
                        <th class="text-center">Hasil Keputusan</th>
                        <th>Catatan / Alasan</th>
                        @if($canManagePeserta)
                        <th style="width: 100px;" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidang->usulan as $idx => $u)
                    <tr class="{{ $u->perlu_perhatian ? 'table-danger bg-opacity-25' : '' }}">
                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                        <td class="fw-bold text-dark">
                            {{ $u->nama_wbp }}
                            @if($u->perlu_perhatian)
                                <span class="badge bg-danger text-white ms-1" title="Atensi Kanwil (Menarik Perhatian)"><i class="fas fa-exclamation-triangle"></i></span>
                            @endif

                            @if($u->isMutasiNarapidana() || $u->jenis_usulan === 'Penempatan/Mutasi')
                                <div class="mt-1">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 10px;">
                                        <i class="fas fa-exchange-alt me-1"></i>Tujuan: {{ $u->uptTujuan?->nama_upt ?? 'Belum Ditentukan' }}
                                    </span>
                                    @if($u->alasan_pemindahan)
                                    <div class="text-muted fst-italic" style="font-size: 10.5px;">"{{ Str::limit($u->alasan_pemindahan, 40) }}"</div>
                                    @endif
                                </div>
                            @elseif($u->isIzinBerobat() || $u->jenis_usulan === 'Izin Berobat')
                                <div class="mt-1">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10px;">
                                        <i class="fas fa-hospital me-1"></i>RS: {{ $u->rs_rujukan_nama ?? '-' }} ({{ $u->rs_rujukan_kota ?? '-' }})
                                    </span>
                                    @if($u->diagnosa_medis)
                                    <div class="text-danger fw-semibold" style="font-size: 10.5px;">{{ Str::limit($u->diagnosa_medis, 35) }}</div>
                                    @endif
                                </div>
                            @endif

                            @if(!empty($u->berkas_khusus))
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @if(!empty($u->berkas_khusus['file_litmas']))
                                <a href="{{ asset($u->berkas_khusus['file_litmas']) }}" target="_blank" class="badge bg-light text-danger border py-1" style="font-size: 9.5px;" title="Litmas Bapas">
                                    <i class="fas fa-file-pdf me-0.5"></i>Litmas
                                </a>
                                @endif
                                @if(!empty($u->berkas_khusus['file_permohonan']))
                                <a href="{{ asset($u->berkas_khusus['file_permohonan']) }}" target="_blank" class="badge bg-light text-primary border py-1" style="font-size: 9.5px;" title="Surat Permohonan">
                                    <i class="fas fa-file-alt me-0.5"></i>Permohonan
                                </a>
                                @endif
                                @if(!empty($u->berkas_khusus['file_rekam_medis']))
                                <a href="{{ asset($u->berkas_khusus['file_rekam_medis']) }}" target="_blank" class="badge bg-light text-danger border py-1" style="font-size: 9.5px;" title="Resume Rekam Medis">
                                    <i class="fas fa-file-medical me-0.5"></i>Medis
                                </a>
                                @endif
                                @if(!empty($u->berkas_khusus['file_rujukan']))
                                <a href="{{ asset($u->berkas_khusus['file_rujukan']) }}" target="_blank" class="badge bg-light text-info border py-1" style="font-size: 9.5px;" title="Rujukan RS">
                                    <i class="fas fa-hospital me-0.5"></i>Rujukan
                                </a>
                                @endif
                                @if(!empty($u->berkas_khusus['dokumen_tambahan']['file']))
                                <a href="{{ asset($u->berkas_khusus['dokumen_tambahan']['file']) }}" target="_blank" class="badge bg-light text-dark border py-1" style="font-size: 9.5px;" title="{{ $u->berkas_khusus['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}">
                                    <i class="fas fa-paperclip me-0.5"></i>{{ Str::limit($u->berkas_khusus['dokumen_tambahan']['nama'] ?? 'Tambahan', 12) }}
                                </a>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td><code>{{ $u->nomor_register }}</code></td>
                        <td>{{ $u->perkara_pasal }}</td>
                        <td>{{ $u->lama_pidana }}</td>
                        <td class="text-center">{{ $u->tanggal_dua_pertiga ? $u->tanggal_dua_pertiga->format('d/m/Y') : '-' }}</td>
                        <td class="text-center">{!! $u->jenis_usulan_badge !!}</td>
                        <td class="text-center">
                            @if($u->perlu_perhatian)
                                <span class="badge bg-danger text-white fw-bold shadow-sm"><i class="fas fa-exclamation-circle me-1"></i>MENARIK PERHATIAN</span>
                                @if($u->catatan_perhatian)
                                    <div class="small text-danger fw-semibold mt-1">{{ $u->catatan_perhatian }}</div>
                                @endif
                            @else
                                <span class="badge bg-light text-muted border">Normal</span>
                            @endif
                        </td>
                        <td class="text-center">{!! $u->keputusan_badge !!}</td>
                        <td class="small text-muted">{{ $u->alasan_keterangan ?: '-' }}</td>
                        @if($canManagePeserta)
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2" title="Edit Peserta Ini" onclick='editPesertaDetail(@json($u))'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('sidang-tpp.peserta.destroy', ['id' => $sidang->id, 'peserta_id' => $u->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus Warga Binaan {{ addslashes($u->nama_wbp) }} dari daftar sidang ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Hapus Peserta">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canManagePeserta ? '11' : '10' }}" class="text-center py-4 text-muted">Belum ada rincian data Warga Binaan dalam sidang ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canManagePeserta)
<!-- MODAL: Tambah / Edit Peserta Sidang pada Detail -->
<div class="modal fade" id="modalPesertaDetail" tabindex="-1" aria-labelledby="modalPesertaDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formPesertaDetail" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalPesertaDetailTitle">
                        <i class="fas fa-user-plus me-2"></i>Tambah Peserta Sidang TPP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="pd_nama_wbp" class="form-label small fw-semibold">Nama Lengkap Warga Binaan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_wbp" id="pd_nama_wbp" class="form-control form-control-sm" placeholder="Contoh: Ahmad Sulaeman bin Dahlan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pd_nomor_register" class="form-label small fw-semibold">Nomor Register Lapas/Rutan <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_register" id="pd_nomor_register" class="form-control form-control-sm" placeholder="Contoh: BI.124/2024" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pd_perkara_pasal" class="form-label small fw-semibold">Perkara / Pasal Tindak Pidana</label>
                            <input type="text" name="perkara_pasal" id="pd_perkara_pasal" class="form-control form-control-sm" placeholder="Contoh: Narkotika Ps. 114 ayat (1) UU 35/2009">
                        </div>
                        <div class="col-md-6">
                            <label for="pd_lama_pidana" class="form-label small fw-semibold">Lama Pidana (Vonis)</label>
                            <input type="text" name="lama_pidana" id="pd_lama_pidana" class="form-control form-control-sm" placeholder="Contoh: 5 Tahun Penjara">
                        </div>
                        <div class="col-md-4">
                            <label for="pd_tanggal_ekspirasi" class="form-label small fw-semibold">Tanggal Ekspirasi Bebas</label>
                            <input type="date" name="tanggal_ekspirasi" id="pd_tanggal_ekspirasi" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="pd_tanggal_setengah" class="form-label small fw-semibold">Tanggal 1/2 Pidana</label>
                            <input type="date" name="tanggal_setengah_pidana" id="pd_tanggal_setengah" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="pd_tanggal_dua_pertiga" class="form-label small fw-semibold">Tanggal 2/3 Pidana</label>
                            <input type="date" name="tanggal_dua_pertiga" id="pd_tanggal_dua_pertiga" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label for="pd_jenis_usulan" class="form-label small fw-semibold">Jenis Usulan <span class="text-danger">*</span></label>
                            <select name="jenis_usulan" id="pd_jenis_usulan" class="form-select form-select-sm" required onchange="toggleFormKhususPeserta(this.value)">
                                <option value="PB">PB (Pembebasan Bersyarat)</option>
                                <option value="CB">CB (Cuti Bersyarat)</option>
                                <option value="CMB">CMB (Cuti Menjelang Bebas)</option>
                                <option value="Asimilasi">Asimilasi</option>
                                <option value="Remisi">Remisi</option>
                                <option value="Penempatan/Mutasi">Penempatan / Mutasi Narapidana (Pindah UPT)</option>
                                <option value="Izin Berobat">Izin Berobat Keluar Kota</option>
                                <option value="Register F">Register F</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="pd_keterangan" class="form-label small fw-semibold">Keterangan Tambahan</label>
                            <input type="text" name="alasan_keterangan" id="pd_keterangan" class="form-control form-control-sm" placeholder="Catatan syarat, penjamin, dll">
                        </div>

                        <!-- SUB-FORM KHUSUS 1: MUTASI / PINDAH UPT -->
                        <div id="pd_box_mutasi" class="col-12 d-none">
                            <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                                <h6 class="fw-bold text-dark small mb-2"><i class="fas fa-exchange-alt text-warning-emphasis me-1"></i>Rincian Usulan Mutasi Narapidana Antar-UPT</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Rekomendasi UPT Tujuan <span class="text-danger">*</span></label>
                                        <select name="upt_tujuan_id" id="pd_upt_tujuan_id" class="form-select form-select-sm">
                                            <option value="">-- Pilih UPT Tujuan --</option>
                                            @foreach($uptList as $u)
                                            <option value="{{ $u->id }}">{{ $u->nama_upt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Alasan Pemindahan</label>
                                        <input type="text" name="alasan_pemindahan" id="pd_alasan_pemindahan" class="form-control form-control-sm" placeholder="Contoh: Keinginan sendiri mendekati keluarga">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Nama Penjamin</label>
                                        <input type="text" name="penjamin_nama" id="pd_penjamin_nama" class="form-control form-control-sm" placeholder="Nama penjamin keluarga">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Hubungan Penjamin</label>
                                        <input type="text" name="penjamin_hubungan" id="pd_penjamin_hubungan" class="form-control form-control-sm" placeholder="Contoh: Istri / Orang Tua">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Alamat Penjamin</label>
                                        <input type="text" name="penjamin_alamat" id="pd_penjamin_alamat" class="form-control form-control-sm" placeholder="Alamat domisili">
                                    </div>

                                    <!-- Berkas Upload Mutasi -->
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <small class="fw-bold text-dark d-block mb-1">Unggah Berkas Persyaratan Mutasi (PDF/Doc/Zip maks 15MB):</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-file-pdf text-danger me-1"></i>Litmas Bapas</label>
                                        <input type="file" name="file_litmas" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-file-alt text-primary me-1"></i>Surat Permohonan</label>
                                        <input type="file" name="file_permohonan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Jaminan Keluarga</label>
                                        <input type="file" name="file_jaminan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                    </div>

                                    <!-- Dokumen Tambahan Lainnya -->
                                    <div class="col-12 mt-2">
                                        <div class="p-2 bg-white rounded border">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-plus-circle text-primary me-1"></i>Dokumen Tambahan Lainnya (Opsional)</label>
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <input type="text" name="dokumen_tambahan_nama" id="pd_dokumen_tambahan_nama" class="form-control form-control-sm" placeholder="Nama Dokumen Tambahan (Kustom)">
                                                </div>
                                                <div class="col-md-7">
                                                    <input type="file" name="dokumen_tambahan_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                                                </div>
                                            </div>
                                            <small class="text-muted" style="font-size: 10.5px;">Satu tempat upload file tambahan jika tidak ada pada pilihan default dengan memberi nama dokumen di sana.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-FORM KHUSUS 2: IZIN BEROBAT KELUAR KOTA -->
                        <div id="pd_box_berobat" class="col-12 d-none">
                            <div class="p-3 bg-light rounded border border-danger border-opacity-50">
                                <h6 class="fw-bold text-dark small mb-2"><i class="fas fa-heartbeat text-danger me-1"></i>Rincian Usulan Izin Berobat Keluar Kota</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Diagnosa Medis / Penyakit <span class="text-danger">*</span></label>
                                        <input type="text" name="diagnosa_medis" id="pd_diagnosa_medis" class="form-control form-control-sm" placeholder="Contoh: Kanker Hati / Gagal Ginjal / Kedaruratan Medis">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Dokter Spesialis / Penanggung Jawab</label>
                                        <input type="text" name="dokter_penanggung_jawab" id="pd_dokter_penanggung_jawab" class="form-control form-control-sm" placeholder="dr. Sp.B / Dokter RS Lapas">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">RS Rujukan Luar Kota <span class="text-danger">*</span></label>
                                        <input type="text" name="rs_rujukan_nama" id="pd_rs_rujukan_nama" class="form-control form-control-sm" placeholder="Contoh: RSCM Jakarta / RS Kanker Dharmais">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Kota RS Rujukan</label>
                                        <input type="text" name="rs_rujukan_kota" id="pd_rs_rujukan_kota" class="form-control form-control-sm" placeholder="Contoh: Jakarta Pusat">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Nama Penjamin Biaya / Keluarga</label>
                                        <input type="text" name="penjamin_nama" id="pd_berobat_penjamin_nama" class="form-control form-control-sm" placeholder="Nama keluarga penjamin">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Hubungan Penjamin</label>
                                        <input type="text" name="penjamin_hubungan" id="pd_berobat_penjamin_hubungan" class="form-control form-control-sm" placeholder="Contoh: Istri / Saudara Kandung">
                                    </div>

                                    <!-- Berkas Upload Medis -->
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <small class="fw-bold text-dark d-block mb-1">Unggah Berkas Medis &amp; Sidang UPT (PDF/Doc/Zip maks 15MB):</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-file-medical text-danger me-1"></i>Resume Rekam Medis</label>
                                        <input type="file" name="file_rekam_medis" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-hospital text-primary me-1"></i>Surat Rujukan Dokter/RSUD</label>
                                        <input type="file" name="file_rujukan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-gavel text-secondary me-1"></i>BA Sidang TPP UPT</label>
                                        <input type="file" name="file_ba_upt" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.zip">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Jaminan Pembiayaan Keluarga</label>
                                        <input type="file" name="file_jaminan" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                    </div>

                                    <!-- Dokumen Tambahan Lainnya -->
                                    <div class="col-12 mt-2">
                                        <div class="p-2 bg-white rounded border">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-plus-circle text-primary me-1"></i>Dokumen Tambahan Lainnya (Opsional)</label>
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <input type="text" name="dokumen_tambahan_nama" id="pd_berobat_dokumen_tambahan_nama" class="form-control form-control-sm" placeholder="Nama Dokumen (misal: Hasil Lab / Rontgen)">
                                                </div>
                                                <div class="col-md-7">
                                                    <input type="file" name="dokumen_tambahan_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                                                </div>
                                            </div>
                                            <small class="text-muted" style="font-size: 10.5px;">Satu tempat upload file tambahan jika tidak ada pada pilihan default dengan memberi nama dokumen di sana.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CENTANG MENARIK PERHATIAN DENGAN LABEL MERAH UNTUK KANWIL -->
                        <div class="col-md-12">
                            <div class="p-3 rounded border border-danger-subtle bg-danger-subtle bg-opacity-25 mt-2">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="perlu_perhatian" id="pd_perlu_perhatian" value="1" style="cursor: pointer; width: 2.5em; height: 1.3em;">
                                    <label class="form-check-label fw-bold text-danger ms-2" for="pd_perlu_perhatian" style="cursor: pointer;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Tandai "Menarik Perhatian (Beri Label Merah)"
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-2">
                                    Beri tanda centang jika Warga Binaan ini memiliki perkara menonjol, atensi pimpinan, residivis/tipikor, memiliki catatan khusus, atau membutuhkan verifikasi ekstra oleh Kantor Wilayah.
                                </small>
                                <div>
                                    <label for="pd_catatan_perhatian" class="form-label small fw-semibold text-dark mb-1">Catatan / Alasan Perhatian Khusus (Opsional):</label>
                                    <input type="text" name="catatan_perhatian" id="pd_catatan_perhatian" class="form-control form-control-sm" placeholder="Contoh: Perkara Tipikor / Atensi Pimpinan, mohon verifikasi ketelitian pembayaran denda & UP">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold" id="btnSimpanPesertaDetail">
                        <i class="fas fa-save me-1"></i>Simpan Peserta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const modalPesertaDetail = new bootstrap.Modal(document.getElementById('modalPesertaDetail'));
    const formPesertaDetail = document.getElementById('formPesertaDetail');
    const methodField = document.getElementById('methodField');

    function toggleFormKhususPeserta(jenis) {
        const boxMutasi = document.getElementById('pd_box_mutasi');
        const boxBerobat = document.getElementById('pd_box_berobat');
        boxMutasi.classList.add('d-none');
        boxBerobat.classList.add('d-none');

        const jUpper = (jenis || '').toUpperCase();
        if (jUpper.includes('MUTASI') || jUpper.includes('PENEMPATAN') || jUpper.includes('PINDAH')) {
            boxMutasi.classList.remove('d-none');
        } else if (jUpper.includes('BEROBAT')) {
            boxBerobat.classList.remove('d-none');
        }
    }

    function bukaModalTambahPesertaDetail() {
        formPesertaDetail.action = "{{ route('sidang-tpp.peserta.store', $sidang->id) }}";
        methodField.innerHTML = '';
        document.getElementById('modalPesertaDetailTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Tambah Peserta Sidang TPP';
        formPesertaDetail.reset();
        document.getElementById('pd_perlu_perhatian').checked = false;
        document.getElementById('pd_jenis_usulan').value = 'PB';
        toggleFormKhususPeserta('PB');
        modalPesertaDetail.show();
    }

    function editPesertaDetail(u) {
        formPesertaDetail.action = "/sidang-tpp/{{ $sidang->id }}/peserta/" + u.id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('modalPesertaDetailTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit Data Peserta: ' + (u.nama_wbp || '');

        document.getElementById('pd_nama_wbp').value = u.nama_wbp || '';
        document.getElementById('pd_nomor_register').value = u.nomor_register || '';
        document.getElementById('pd_perkara_pasal').value = u.perkara_pasal || '';
        document.getElementById('pd_lama_pidana').value = u.lama_pidana || '';
        document.getElementById('pd_tanggal_ekspirasi').value = u.tanggal_ekspirasi ? u.tanggal_ekspirasi.substring(0, 10) : '';
        document.getElementById('pd_tanggal_setengah').value = u.tanggal_setengah_pidana ? u.tanggal_setengah_pidana.substring(0, 10) : '';
        document.getElementById('pd_tanggal_dua_pertiga').value = u.tanggal_dua_pertiga ? u.tanggal_dua_pertiga.substring(0, 10) : '';
        document.getElementById('pd_jenis_usulan').value = u.jenis_usulan || 'PB';
        document.getElementById('pd_perlu_perhatian').checked = !!u.perlu_perhatian;
        document.getElementById('pd_catatan_perhatian').value = u.catatan_perhatian || '';
        document.getElementById('pd_keterangan').value = u.alasan_keterangan || '';

        // Field khusus Mutasi
        if (document.getElementById('pd_upt_tujuan_id')) {
            document.getElementById('pd_upt_tujuan_id').value = u.upt_tujuan_id || '';
        }
        if (document.getElementById('pd_alasan_pemindahan')) {
            document.getElementById('pd_alasan_pemindahan').value = u.alasan_pemindahan || '';
        }
        if (document.getElementById('pd_penjamin_nama')) {
            document.getElementById('pd_penjamin_nama').value = u.penjamin_nama || '';
        }
        if (document.getElementById('pd_penjamin_hubungan')) {
            document.getElementById('pd_penjamin_hubungan').value = u.penjamin_hubungan || '';
        }
        if (document.getElementById('pd_penjamin_alamat')) {
            document.getElementById('pd_penjamin_alamat').value = u.penjamin_alamat || '';
        }

        // Field khusus Berobat
        if (document.getElementById('pd_diagnosa_medis')) {
            document.getElementById('pd_diagnosa_medis').value = u.diagnosa_medis || '';
        }
        if (document.getElementById('pd_dokter_penanggung_jawab')) {
            document.getElementById('pd_dokter_penanggung_jawab').value = u.dokter_penanggung_jawab || '';
        }
        if (document.getElementById('pd_rs_rujukan_nama')) {
            document.getElementById('pd_rs_rujukan_nama').value = u.rs_rujukan_nama || '';
        }
        if (document.getElementById('pd_rs_rujukan_kota')) {
            document.getElementById('pd_rs_rujukan_kota').value = u.rs_rujukan_kota || '';
        }
        if (document.getElementById('pd_berobat_penjamin_nama')) {
            document.getElementById('pd_berobat_penjamin_nama').value = u.penjamin_nama || '';
        }
        if (document.getElementById('pd_berobat_penjamin_hubungan')) {
            document.getElementById('pd_berobat_penjamin_hubungan').value = u.penjamin_hubungan || '';
        }

        const bKhusus = u.berkas_khusus || {};
        if (document.getElementById('pd_dokumen_tambahan_nama') && bKhusus.dokumen_tambahan) {
            document.getElementById('pd_dokumen_tambahan_nama').value = bKhusus.dokumen_tambahan.nama || '';
        }

        toggleFormKhususPeserta(u.jenis_usulan);
        modalPesertaDetail.show();
    }
</script>
@endpush
@endif

@if($canUploadHasil)
<!-- MODAL: Upload Berita Acara, Dokumentasi & Hasil Rekomendasi Sidang TPP -->
<div class="modal fade" id="modalHasilSidangDetail" tabindex="-1" aria-labelledby="modalHasilDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('sidang-tpp.hasil.update', $sidang->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="redirect_to" value="detail">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalHasilDetailTitle">
                        <i class="fas fa-file-upload me-2"></i>Upload Berita Acara, Dokumentasi &amp; Hasil Sidang TPP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Banner Informasi Sidang -->
                    <div class="card bg-light border-0 p-3 mb-3">
                        <div class="row g-2 small">
                            <div class="col-md-3">
                                <span class="text-muted d-block">Unit Pelaksana Teknis:</span>
                                <b class="text-primary">{{ $sidang->upt?->nama_upt ?? '-' }}</b>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Tanggal Pelaksanaan:</span>
                                <b class="text-dark">{{ $sidang->tanggal_sidang->translatedFormat('d F Y') }}</b>
                                @if($sidang->tanggal_sidang->isPast())
                                <span class="badge bg-secondary-subtle text-secondary border ms-1"><i class="fas fa-history me-1"></i>Sidang Lalu</span>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Agenda Sidang:</span>
                                <b class="text-dark">{{ $sidang->agenda_sidang ?? $sidang->agenda_label }}</b>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Total Warga Binaan:</span>
                                <b class="text-dark">{{ $sidang->usulan->count() }} Orang</b>
                            </div>
                        </div>
                    </div>

                    @if($sidang->tanggal_sidang->isPast())
                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle fa-lg me-2 text-info"></i>
                        <div>
                            <b>Sidang Terlaksana / Masa Lalu:</b> Berita Acara, dokumentasi foto, maupun penetapan rekomendasi putusan tetap dapat diunggah dan disimpan kapan saja tanpa batasan tanggal.
                        </div>
                    </div>
                    @endif

                    <!-- Bagian 1: Berita Acara & Dokumentasi -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fas fa-file-alt text-primary me-2"></i>1. Berita Acara (BA) &amp; Foto Dokumentasi
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="detail_nomor_berita_acara" class="form-label small fw-semibold">Nomor Berita Acara (BA) Sidang TPP <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_berita_acara" id="detail_nomor_berita_acara" class="form-control" value="{{ old('nomor_berita_acara', $sidang->nomor_berita_acara) }}" placeholder="Contoh: W12.PAS.PAS.1-BA.01-085/2026" required>
                        </div>
                        <div class="col-md-6">
                            <label for="detail_file_berita_acara" class="form-label small fw-semibold">Unggah Berkas Berita Acara TPP Bertanda Tangan (PDF)</label>
                            <input type="file" name="file_berita_acara" id="detail_file_berita_acara" class="form-control" accept=".pdf">
                            @if(!empty($sidang->file_berita_acara))
                            <div class="form-text small mt-1">
                                <i class="fas fa-check-circle text-success me-1"></i>Berkas saat ini: 
                                <a href="{{ route('sidang-tpp.download-file', ['id' => $sidang->id, 'type' => 'berita_acara', 'preview' => 1]) }}" target="_blank" class="fw-semibold text-decoration-none">
                                    {{ basename($sidang->file_berita_acara) }}
                                </a> (Pilih berkas baru jika ingin mengganti berkas ini).
                            </div>
                            @else
                            <div class="form-text text-muted small" style="font-size: 11px;">
                                Scan PDF Berita Acara TPP lengkap dengan tanda tangan Ketua, Sekretaris &amp; Anggota Tim (Maks. 15 MB).
                            </div>
                            @endif
                        </div>
                        <div class="col-md-12">
                            <label for="detail_dokumentasi" class="form-label small fw-semibold">
                                <i class="fas fa-camera text-primary me-1"></i>Unggah Foto Dokumentasi Sidang TPP (Bisa Pilih Sekaligus Beberapa Foto)
                            </label>
                            <input type="file" name="dokumentasi[]" id="detail_dokumentasi" class="form-control" accept="image/*" multiple>
                            <div class="form-text text-muted" style="font-size: 11px;">
                                <i class="fas fa-magic text-success me-1"></i>Sistem secara otomatis mengonversi ke format <b>JPG</b> dan mengompres resolusi agar ukuran file <b>maksimal 500 KB per foto dokumentasi</b>.
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Hasil Rekomendasi Putusan per WBP -->
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-gavel text-primary me-2"></i>2. Rekomendasi Hasil Putusan Sidang ({{ $sidang->usulan->count() }} Orang)
                        </h6>
                        <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm px-3" onclick="setSemuaSetujuDetail()" title="Otomatis pilih hasil 'Setuju' untuk seluruh Warga Binaan dalam sidang ini">
                            <i class="fas fa-check-double me-1.5"></i>Pilih "Setuju" Untuk Semua
                        </button>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 380px;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                            <thead class="table-dark sticky-top small">
                                <tr>
                                    <th style="width: 35px;" class="text-center">No</th>
                                    <th>Nama Warga Binaan &amp; Register</th>
                                    <th>Perkara / Pidana</th>
                                    <th class="text-center">Jenis Usulan</th>
                                    <th class="text-center" style="width: 150px;">Atensi</th>
                                    <th style="width: 175px;" class="text-center">Keputusan Sidang</th>
                                    <th>Alasan / Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sidang->usulan as $idx => $item)
                                @php
                                    $curKep = $item->keputusan;
                                    if ($curKep === 'disetujui') $curKep = 'setuju';
                                    if ($curKep === 'ditolak') $curKep = 'tidak_setuju';
                                    if ($curKep === 'ditunda') $curKep = 'tunda';
                                    if (empty($curKep) || $curKep === 'belum_putus') $curKep = 'setuju';
                                @endphp
                                <tr class="{{ $item->perlu_perhatian ? 'table-danger bg-opacity-25' : '' }}">
                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <b>{{ $item->nama_wbp }}</b><br>
                                        <small class="text-muted"><code>{{ $item->nomor_register }}</code></small>
                                    </td>
                                    <td>
                                        <small>{{ $item->perkara_pasal }}</small><br>
                                        <small class="text-muted">{{ $item->lama_pidana }}</small>
                                    </td>
                                    <td class="text-center">{!! $item->jenis_usulan_badge !!}</td>
                                    <td class="text-center">
                                        @if($item->perlu_perhatian)
                                            <span class="badge bg-danger text-white fw-bold shadow-sm"><i class="fas fa-exclamation-triangle me-1"></i>MENARIK PERHATIAN</span>
                                            @if($item->catatan_perhatian)
                                                <div class="small text-danger fw-semibold mt-1">{{ $item->catatan_perhatian }}</div>
                                            @endif
                                        @else
                                            <span class="badge bg-light text-muted border">Normal</span>
                                        @endif
                                    </td>
                                    <td>
                                        <select name="keputusan[{{ $item->id }}]" class="form-select form-select-sm keputusan-select-detail fw-semibold">
                                            <option value="setuju" {{ $curKep === 'setuju' ? 'selected' : '' }} class="text-success fw-bold">🟢 Setuju</option>
                                            <option value="tidak_setuju" {{ $curKep === 'tidak_setuju' ? 'selected' : '' }} class="text-danger fw-bold">🔴 Tidak Setuju</option>
                                            <option value="tunda" {{ $curKep === 'tunda' ? 'selected' : '' }} class="text-warning fw-bold">🟡 Tunda</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="alasan_keterangan[{{ $item->id }}]" value="{{ $item->alasan_keterangan }}" class="form-control form-control-sm" placeholder="Catatan syarat / perbaikan jika ada">
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">Belum ada daftar usulan warga binaan. Silakan tambahkan peserta terlebih dahulu.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="fas fa-paper-plane me-1"></i>Simpan BA &amp; Rekomendasi Hasil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isKanwil())
    @include('sidang_tpp.partials.modal_nota_dinas', ['sidang' => $sidang])
    @include('sidang_tpp.dokumen_kanwil.partials.modal_upload_signed')
@endif

@push('scripts')
<script>
    function setSemuaSetujuDetail() {
        document.querySelectorAll('.keputusan-select-detail').forEach(function(el) {
            el.value = 'setuju';
        });
    }

    function bukaModalUploadSignedDetail(sidangId, jenis, noDokumen) {
        const form = document.getElementById('formUploadSigned');
        if (form) form.action = '/sidang-tpp/' + sidangId + '/dokumen-kanwil/upload-signed';
        const upJenis = document.getElementById('up_jenis_dokumen');
        if (upJenis) upJenis.value = jenis;
        const upNo = document.getElementById('up_nomor_dokumen');
        if (upNo) upNo.value = noDokumen || '';
        const upLabel = document.getElementById('up_jenis_label');
        if (upLabel) {
            if (jenis === 'surat_tugas') {
                upLabel.innerText = 'Surat Tugas Tim Pembinaan Kanwil';
            } else if (jenis === 'laporan_tpp') {
                upLabel.innerText = 'Laporan Pelaksanaan Sidang TPP Kanwil';
            } else {
                upLabel.innerText = 'Nota Dinas Permohonan Surat Tugas';
            }
        }
        const fileIn = document.getElementById('file_signed');
        if (fileIn) fileIn.value = '';
        if (typeof setStampPosition === 'function') {
            setStampPosition(6, 86, 'Kiri Bawah');
        }
        const modalEl = document.getElementById('modalUploadSigned');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    function copyPublicUndanganLink(url) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function() {
                alert('Tautan Surat Undangan Publik berhasil disalin:\n' + url + '\n\nTautan ini dapat langsung dibuka oleh siapa saja tanpa harus login.');
            }).catch(function() {
                fallbackCopyPublic(url);
            });
        } else {
            fallbackCopyPublic(url);
        }
    }

    function fallbackCopyPublic(url) {
        const tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('Tautan Surat Undangan Publik berhasil disalin:\n' + url + '\n\nTautan ini dapat langsung dibuka oleh siapa saja tanpa harus login.');
    }
</script>
@endpush
@endsection
