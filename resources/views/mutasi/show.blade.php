@extends('layouts.app')

@section('title', 'Detail Usulan Mutasi Narapidana - Sultan Banten')
@section('page_title')
    <i class="fas fa-file-contract me-2 text-primary"></i>Berkas Usulan Mutasi Narapidana
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('mutasi.index') }}" class="text-decoration-none">Mutasi Narapidana</a></li>
            <li class="breadcrumb-item active" aria-current="page">No: {{ $mutasi->nomor_surat }}</li>
        </ol>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3 shadow-sm" role="alert">
        <i class="fas fa-check-circle fa-lg me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3 shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 shadow-sm" role="alert">
        <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Terjadi kesalahan:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Status Banner & Stepper -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Status Alur Berkas</span>
                    <h4 class="mb-0 fw-bold mt-1">
                        <span class="badge {{ $mutasi->status_badge_class }} fs-6 px-3 py-1.5">
                            {{ $mutasi->status_label }}
                        </span>
                    </h4>
                </div>
                <div class="text-end">
                    <span class="text-muted small">Diajukan pada:</span>
                    <div class="fw-semibold text-dark">{{ $mutasi->created_at ? $mutasi->created_at->format('d/m/Y H:i') : '-' }} WIB</div>
                    <small class="text-muted">Oleh: {{ $mutasi->creator?->nama_lengkap ?? 'Petugas UPT' }}</small>
                </div>
            </div>

            <!-- Visual Step Flow (4 Tahap) -->
            <div class="row text-center mt-4 g-2">
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 h-100">
                        <i class="fas fa-file-upload fa-lg text-success mb-1"></i>
                        <div class="small fw-bold text-success">1. Pengajuan UPT</div>
                        <small class="text-muted d-block" style="font-size: 11px;">Berkas &amp; Data Perkara</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 {{ in_array($mutasi->status, ['disetujui', 'selesai']) ? 'bg-success bg-opacity-10 border border-success border-opacity-25' : ($mutasi->status === 'ditolak' ? 'bg-danger bg-opacity-10 border border-danger border-opacity-25' : 'bg-warning bg-opacity-10 border border-warning border-opacity-25') }} h-100">
                        <i class="fas fa-stamp fa-lg {{ in_array($mutasi->status, ['disetujui', 'selesai']) ? 'text-success' : ($mutasi->status === 'ditolak' ? 'text-danger' : 'text-warning') }} mb-1"></i>
                        <div class="small fw-bold {{ in_array($mutasi->status, ['disetujui', 'selesai']) ? 'text-success' : ($mutasi->status === 'ditolak' ? 'text-danger' : 'text-warning') }}">
                            2. Penetapan Kanwil
                        </div>
                        <small class="text-muted d-block" style="font-size: 11px;">
                            {{ $mutasi->uptTujuan ? $mutasi->uptTujuan->nama_upt : 'Penetapan UPT Tujuan' }}
                        </small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    @php
                        $hasPelaporan = $mutasi->hasLaporanPemindahan() || $mutasi->hasLaporanPenerimaan();
                        $fullPelaporan = $mutasi->hasLaporanPemindahan() && $mutasi->hasLaporanPenerimaan();
                    @endphp
                    <div class="p-2 rounded-3 {{ $fullPelaporan ? 'bg-success bg-opacity-10 border border-success border-opacity-25' : ($hasPelaporan ? 'bg-info bg-opacity-10 border border-info border-opacity-25' : 'bg-light border text-muted') }} h-100">
                        <i class="fas fa-truck-moving fa-lg {{ $fullPelaporan ? 'text-success' : ($hasPelaporan ? 'text-info' : 'text-muted') }} mb-1"></i>
                        <div class="small fw-bold {{ $fullPelaporan ? 'text-success' : ($hasPelaporan ? 'text-info' : 'text-muted') }}">
                            3. Laporan Pemindahan
                        </div>
                        <small class="text-muted d-block" style="font-size: 11px;">
                            {{ $fullPelaporan ? 'Kedua UPT Lengkap' : ($hasPelaporan ? 'Sebagian Diunggah' : 'UPT Asal &amp; Tujuan') }}
                        </small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 {{ $mutasi->hasBast() ? 'bg-success bg-opacity-10 border border-success border-opacity-25' : 'bg-light border text-muted' }} h-100">
                        <i class="fas fa-handshake fa-lg {{ $mutasi->hasBast() ? 'text-success' : 'text-muted' }} mb-1"></i>
                        <div class="small fw-bold {{ $mutasi->hasBast() ? 'text-success' : 'text-muted' }}">4. BA Serah Terima</div>
                        <small class="text-muted d-block" style="font-size: 11px;">
                            {{ $mutasi->hasBast() ? 'BAST Tuntas' : 'Menunggu BAST' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT COLUMN: IDENTITAS & RINCIAN USULAN -->
        <div class="col-lg-7">
            <!-- Informasi Usulan UPT -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Usulan UPT</h6>
                    <span class="badge {{ $mutasi->kategori_badge_class }}">{{ $mutasi->kategori_mutasi }}</span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted small" width="38%">UPT Pengusul (Pemohon)</td>
                                <td width="4%">:</td>
                                <td class="fw-bold text-primary">{{ $mutasi->uptAsal?->nama_upt ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Nomor Surat Usulan</td>
                                <td>:</td>
                                <td class="fw-bold font-monospace">{{ $mutasi->nomor_surat }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Tanggal Surat</td>
                                <td>:</td>
                                <td>{{ $mutasi->tanggal_surat ? $mutasi->tanggal_surat->format('d F Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Kategori Mutasi</td>
                                <td>:</td>
                                <td><span class="badge {{ $mutasi->kategori_badge_class }}">{{ $mutasi->kategori_mutasi }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted small align-top">Keterangan / Alasan</td>
                                <td class="align-top">:</td>
                                <td class="text-secondary">{{ $mutasi->keterangan ?: 'Tidak ada keterangan tambahan.' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="my-3">

                    <!-- Rincian Jumlah Perkara -->
                    <h6 class="fw-bold text-dark mb-2 small text-uppercase">Pendataan Jumlah WBP Diusulkan</h6>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="border rounded-3 p-2 bg-light">
                                <small class="text-muted d-block" style="font-size: 11px;">Narkotika</small>
                                <span class="fs-5 fw-bold text-danger">{{ $mutasi->jumlah_narkotika }}</span>
                                <small class="text-muted d-block" style="font-size: 10px;">Orang</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-2 bg-light">
                                <small class="text-muted d-block" style="font-size: 11px;">Pidana Umum</small>
                                <span class="fs-5 fw-bold text-primary">{{ $mutasi->jumlah_pidum }}</span>
                                <small class="text-muted d-block" style="font-size: 10px;">Orang</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-2 bg-light">
                                <small class="text-muted d-block" style="font-size: 11px;">Korupsi</small>
                                <span class="fs-5 fw-bold text-warning text-dark">{{ $mutasi->jumlah_korupsi }}</span>
                                <small class="text-muted d-block" style="font-size: 10px;">Orang</small>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="p-2 bg-primary bg-opacity-10 rounded-3 text-primary fw-bold">
                                Total Narapidana Diusulkan: {{ $mutasi->total_usulan }} Orang
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kelengkapan Berkas Pengajuan UPT -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-folder me-2 text-primary"></i>Berkas Persyaratan Pengusulan</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <span class="fw-semibold small">1. Surat Usulan Resmi UPT</span>
                            </div>
                            @if($mutasi->file_surat_usulan)
                            <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'surat_usulan']) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2.5" target="_blank">
                                <i class="fas fa-download me-1"></i> Unduh PDF
                            </a>
                            @else
                            <span class="badge bg-secondary">Tidak Ada</span>
                            @endif
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div>
                                <i class="fas fa-file-alt text-primary me-2"></i>
                                <span class="fw-semibold small">2. Asesmen Penempatan Narapidana</span>
                            </div>
                            @if($mutasi->file_asesmen)
                            <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'asesmen']) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2.5" target="_blank">
                                <i class="fas fa-download me-1"></i> Unduh Berkas
                            </a>
                            @else
                            <span class="badge bg-light text-muted border">Tidak Dilampirkan</span>
                            @endif
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div>
                                <i class="fas fa-shield-alt text-warning me-2"></i>
                                <span class="fw-semibold small">3. Berita Acara Pemeriksaan (BAP)</span>
                            </div>
                            @if($mutasi->file_bap)
                            <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'bap']) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2.5" target="_blank">
                                <i class="fas fa-download me-1"></i> Unduh BAP
                            </a>
                            @else
                            <span class="badge bg-light text-muted border">Tidak Dilampirkan</span>
                            @endif
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div>
                                <i class="fas fa-clipboard-list text-secondary me-2"></i>
                                <span class="fw-semibold small">4. Buku Register F (Pelanggaran Disiplin)</span>
                            </div>
                            @if($mutasi->file_reg_f)
                            <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'reg_f']) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2.5" target="_blank">
                                <i class="fas fa-download me-1"></i> Unduh Register F
                            </a>
                            @else
                            <span class="badge bg-light text-muted border">Tidak Dilampirkan</span>
                            @endif
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div>
                                <i class="fas fa-users-cog text-info me-2"></i>
                                <span class="fw-semibold small">5. Lampiran Daftar Nominatif WBP</span>
                            </div>
                            @if($mutasi->file_lampiran_wbp)
                            <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'lampiran_wbp']) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2.5" target="_blank">
                                <i class="fas fa-download me-1"></i> Unduh Nominatif
                            </a>
                            @else
                            <span class="badge bg-light text-muted border">Tidak Dilampirkan</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: PENETAPAN KANWIL & SERAH TERIMA -->
        <div class="col-lg-5">
            @php
                $userUptId = auth()->user()->upt_id !== null ? (int) auth()->user()->upt_id : null;
                $isKanwilOrAdmin = auth()->user()->isAdmin() || empty(auth()->user()->upt_id);
                $isPengusul = $userUptId !== null && $userUptId === (int) $mutasi->upt_asal_id;
                $isPenerima = $userUptId !== null && $userUptId === (int) $mutasi->upt_tujuan_id;

                $canUploadPemindahan = $isKanwilOrAdmin || $isPengusul;
                $canUploadPenerimaan = $isKanwilOrAdmin || $isPenerima;
                $canUploadBast = $isKanwilOrAdmin || $isPengusul || $isPenerima;
            @endphp

            @if(in_array($mutasi->status, ['disetujui', 'selesai']))
                @if($isPengusul)
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fas fa-truck-departure fa-lg me-2 text-primary"></i>
                    <div class="small">
                        <strong>Perhatian UPT Pengusul (Pengirim):</strong> Kantor Wilayah telah menyetujui mutasi dan menunjuk <strong>{{ $mutasi->uptTujuan?->nama_upt }}</strong> sebagai UPT Penerima. Silakan unduh <strong>Surat Persetujuan Kanwil</strong> di bawah dan unggah <strong>Laporan Pemindahan</strong> setelah narapidana diberangkatkan.
                    </div>
                </div>
                @elseif($isPenerima)
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fas fa-building fa-lg me-2 text-success"></i>
                    <div class="small">
                        <strong>Pemberitahuan UPT Penerima (Tujuan):</strong> UPT Anda telah ditunjuk sebagai penerima mutasi dari <strong>{{ $mutasi->uptAsal?->nama_upt }}</strong>. Silakan unduh <strong>Surat Persetujuan Kanwil</strong> di bawah untuk memverifikasi kuota WBP, dan unggah <strong>Laporan Penerimaan</strong> saat narapidana tiba.
                    </div>
                </div>
                @endif
            @endif

            <!-- STATUS SURAT PERSETUJUAN KANWIL -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 {{ in_array($mutasi->status, ['disetujui', 'selesai']) ? 'border-start border-success border-4' : '' }}">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-stamp me-2 text-success"></i>Surat Persetujuan Kantor Wilayah</h6>
                    <span class="badge {{ $mutasi->status_badge_class }}">{{ $mutasi->status_label }}</span>
                </div>
                <div class="card-body">
                    @if(in_array($mutasi->status, ['disetujui', 'selesai']))
                    <div class="p-3 bg-success bg-opacity-10 rounded-3 mb-3 border border-success border-opacity-25">
                        <small class="text-muted d-block mb-1">UPT Penerima Ditunjuk:</small>
                        <h5 class="fw-bold text-success mb-1"><i class="fas fa-building me-1"></i>{{ $mutasi->uptTujuan?->nama_upt ?? '-' }}</h5>
                        <small class="text-muted d-block">Nomor Surat Persetujuan: <strong class="font-monospace text-dark">{{ $mutasi->nomor_surat_kanwil }}</strong></small>
                        <small class="text-muted d-block">Tanggal Surat: {{ $mutasi->tanggal_surat_kanwil ? $mutasi->tanggal_surat_kanwil->format('d F Y') : '-' }}</small>
                    </div>

                    <!-- Kuota yang Disetujui -->
                    <div class="mb-3">
                        <span class="small fw-semibold text-muted d-block mb-2">Total WBP Disetujui Kanwil:</span>
                        <div class="d-flex gap-2 text-center">
                            <div class="border rounded p-1.5 flex-fill bg-light small">
                                <div style="font-size: 10px;" class="text-danger fw-bold">Narkotika</div>
                                <div class="fw-bold">{{ $mutasi->jumlah_disetujui_narkotika ?? 0 }}</div>
                            </div>
                            <div class="border rounded p-1.5 flex-fill bg-light small">
                                <div style="font-size: 10px;" class="text-primary fw-bold">Pidum</div>
                                <div class="fw-bold">{{ $mutasi->jumlah_disetujui_pidum ?? 0 }}</div>
                            </div>
                            <div class="border rounded p-1.5 flex-fill bg-light small">
                                <div style="font-size: 10px;" class="text-warning text-dark fw-bold">Korupsi</div>
                                <div class="fw-bold">{{ $mutasi->jumlah_disetujui_korupsi ?? 0 }}</div>
                            </div>
                            <div class="border rounded p-1.5 flex-fill bg-success bg-opacity-25 text-success small">
                                <div style="font-size: 10px;" class="fw-bold">Total</div>
                                <div class="fw-bold">{{ $mutasi->total_disetujui }}</div>
                            </div>
                        </div>
                    </div>

                    @if($mutasi->catatan_kanwil)
                    <div class="mb-3">
                        <small class="text-muted d-block">Catatan / Disposisi Kanwil:</small>
                        <div class="p-2 bg-light rounded small text-secondary mt-1 border">
                            {{ $mutasi->catatan_kanwil }}
                        </div>
                    </div>
                    @endif

                    @if($mutasi->file_persetujuan_kanwil)
                    <div class="d-grid mb-2">
                        <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'persetujuan_kanwil']) }}" class="btn btn-success btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i> Unduh Surat Persetujuan Kanwil (PDF)
                        </a>
                    </div>
                    @endif

                    <small class="text-muted d-block text-end" style="font-size: 10px;">
                        Diverifikasi oleh: {{ $mutasi->verifikator?->nama_lengkap ?? 'Tim Kanwil' }} pada {{ $mutasi->tanggal_verifikasi ? $mutasi->tanggal_verifikasi->format('d/m/Y H:i') : '-' }}
                    </small>

                    @elseif($mutasi->status === 'ditolak')
                    <div class="alert alert-danger mb-0">
                        <h6 class="fw-bold mb-1"><i class="fas fa-times-circle me-1"></i> Usulan Ditolak oleh Kanwil</h6>
                        <p class="small mb-1">{{ $mutasi->catatan_kanwil ?: 'Tidak ada catatan penolakan.' }}</p>
                        <small class="text-muted">Diverifikasi pada: {{ $mutasi->tanggal_verifikasi ? $mutasi->tanggal_verifikasi->format('d/m/Y') : '-' }}</small>
                    </div>

                    @elseif($mutasi->status === 'perlu_perbaikan')
                    <div class="alert alert-warning mb-0">
                        <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> Berkas Perlu Perbaikan</h6>
                        <p class="small mb-1">{{ $mutasi->catatan_kanwil ?: 'Mohon periksa dan lengkapi berkas persyaratan.' }}</p>
                        <small class="text-muted">Catatan Kanwil: {{ $mutasi->tanggal_verifikasi ? $mutasi->tanggal_verifikasi->format('d/m/Y') : '-' }}</small>
                    </div>

                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-hourglass-half fa-3x text-warning mb-2 opacity-50"></i>
                        <p class="small mb-0">Usulan ini sedang menunggu telaah berkas dan penetapan UPT Tujuan dari Kantor Wilayah Banten.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- FORM VERIFIKASI KANWIL (Khusus Admin / Tim Kanwil) -->
            @if(auth()->user()->isAdmin() || empty(auth()->user()->upt_id))
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-primary text-white py-2.5">
                    <h6 class="mb-0 fw-bold fs-6"><i class="fas fa-user-shield me-2"></i>Aksi Verifikasi &amp; Penetapan Kanwil</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('mutasi.verifikasi', $mutasi->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Keputusan Kantor Wilayah <span class="text-danger">*</span></label>
                            <select name="status" id="selectStatusVerifikasi" class="form-select form-select-sm" required>
                                <option value="disetujui" {{ $mutasi->status === 'disetujui' ? 'selected' : '' }}>Disetujui &amp; Tetapkan UPT Penerima</option>
                                <option value="perlu_perbaikan" {{ $mutasi->status === 'perlu_perbaikan' ? 'selected' : '' }}>Perlu Perbaikan Berkas</option>
                                <option value="ditolak" {{ $mutasi->status === 'ditolak' ? 'selected' : '' }}>Tolak Usulan</option>
                            </select>
                        </div>

                        <!-- Bagian Jika Disetujui -->
                        <div id="sectionDisetujui">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Tetapkan UPT Penerima (Tujuan) <span class="text-danger">*</span></label>
                                <select name="upt_tujuan_id" id="selectUptTujuan" class="form-select form-select-sm">
                                    <option value="">-- Pilih UPT Penerima --</option>
                                    @foreach($uptList as $u)
                                    @if($u->id !== $mutasi->upt_asal_id)
                                    <option value="{{ $u->id }}" {{ $mutasi->upt_tujuan_id == $u->id ? 'selected' : '' }}>
                                        {{ $u->nama_upt }} (Kapasitas: {{ $u->kapasitas }})
                                    </option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nomor Surat Persetujuan Kanwil <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_surat_kanwil" class="form-control form-control-sm font-monospace" placeholder="Nomor Surat Persetujuan Kanwil" value="{{ old('nomor_surat_kanwil', $mutasi->nomor_surat_kanwil) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Tanggal Surat Kanwil <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_surat_kanwil" class="form-control form-control-sm" value="{{ old('tanggal_surat_kanwil', $mutasi->tanggal_surat_kanwil ? $mutasi->tanggal_surat_kanwil->format('Y-m-d') : date('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Rincian Narapidana yang Disetujui</label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="small text-danger fw-bold" style="font-size: 10px;">Narkotika</label>
                                        <input type="number" min="0" name="jumlah_disetujui_narkotika" class="form-control form-control-sm text-center" value="{{ old('jumlah_disetujui_narkotika', $mutasi->jumlah_disetujui_narkotika ?? $mutasi->jumlah_narkotika) }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="small text-primary fw-bold" style="font-size: 10px;">Pidum</label>
                                        <input type="number" min="0" name="jumlah_disetujui_pidum" class="form-control form-control-sm text-center" value="{{ old('jumlah_disetujui_pidum', $mutasi->jumlah_disetujui_pidum ?? $mutasi->jumlah_pidum) }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="small text-warning text-dark fw-bold" style="font-size: 10px;">Korupsi</label>
                                        <input type="number" min="0" name="jumlah_disetujui_korupsi" class="form-control form-control-sm text-center" value="{{ old('jumlah_disetujui_korupsi', $mutasi->jumlah_disetujui_korupsi ?? $mutasi->jumlah_korupsi) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Upload Scan Surat Persetujuan Kanwil (PDF) <span class="text-danger">*</span></label>
                                <input type="file" name="file_persetujuan_kanwil" class="form-control form-control-sm" accept=".pdf">
                                @if($mutasi->file_persetujuan_kanwil)
                                <small class="text-success d-block mt-1" style="font-size: 11px;"><i class="fas fa-check me-1"></i>Sudah ada berkas persetujuan terunggah. Unggah baru jika ingin mengganti.</small>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan / Disposisi Kanwil</label>
                            <textarea name="catatan_kanwil" class="form-control form-control-sm" rows="2" placeholder="Catatan atau instruksi pelaksanaan dari Kanwil...">{{ old('catatan_kanwil', $mutasi->catatan_kanwil) }}</textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <i class="fas fa-save me-1"></i> Simpan Penetapan &amp; Distribusikan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- SEKSI 4: LAPORAN PEMINDAHAN, PENERIMAAN, DAN BAST -->
            @if(in_array($mutasi->status, ['disetujui', 'selesai']))

            <!-- 4A. LAPORAN PEMINDAHAN (UPT PENGUSUL) -->
            <div class="card border-0 shadow-sm rounded-3 mb-3 border-start border-primary border-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-truck-departure me-2 text-primary"></i>1. Laporan Pemindahan</h6>
                        <small class="text-muted">Wajib diunggah UPT Pengusul (Pengirim): <strong>{{ $mutasi->uptAsal?->nama_upt ?? '-' }}</strong></small>
                    </div>
                    <div>
                        @if($isPengusul && ! $mutasi->hasLaporanPemindahan())
                        <span class="badge bg-primary me-1"><i class="fas fa-arrow-circle-right me-1"></i>Tindakan UPT Anda</span>
                        @endif
                        @if($mutasi->hasLaporanPemindahan())
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diunggah</span>
                        @else
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Menunggu</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($mutasi->hasLaporanPemindahan())
                    <div class="p-2.5 bg-light rounded-3 border mb-2">
                        <div class="small text-muted">Tanggal Keberangkatan / Pemindahan:</div>
                        <div class="fw-bold text-dark mb-1">{{ $mutasi->tanggal_pemindahan ? $mutasi->tanggal_pemindahan->format('d F Y') : '-' }}</div>
                        @if($mutasi->catatan_pemindahan)
                        <div class="small text-muted">Catatan Pengawalan/Pemindahan:</div>
                        <div class="small text-secondary mb-2">{{ $mutasi->catatan_pemindahan }}</div>
                        @endif
                        <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'laporan_pemindahan']) }}" class="btn btn-outline-primary btn-sm w-100" target="_blank">
                            <i class="fas fa-download me-1"></i> Unduh Berkas Laporan Pemindahan
                        </a>
                    </div>
                    @endif

                    @if(! $mutasi->hasLaporanPemindahan() && $canUploadPemindahan)
                    <form action="{{ route('mutasi.laporan-pemindahan.store', $mutasi->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Tanggal Keberangkatan Pemindahan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pemindahan" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Upload Laporan Pemindahan Resmi (PDF/Gambar) <span class="text-danger">*</span></label>
                            <input type="file" name="file_laporan_pemindahan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Catatan Pengawalan / Pengeluaran</label>
                            <textarea name="catatan_pemindahan" class="form-control form-control-sm" rows="2" placeholder="Catatan personel pengawal, kondisi pengeluaran, dsb..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <i class="fas fa-upload me-1"></i> Unggah Laporan Pemindahan
                            </button>
                        </div>
                    </form>
                    @elseif(! $mutasi->hasLaporanPemindahan())
                    <p class="small text-muted mb-0 fst-italic">Menunggu laporan pengawalan dan pemindahan resmi diunggah oleh pihak UPT Pengusul / Pengirim ({{ $mutasi->uptAsal?->nama_upt }}).</p>
                    @endif
                </div>
            </div>

            <!-- 4B. LAPORAN PENERIMAAN (UPT PENERIMA) -->
            <div class="card border-0 shadow-sm rounded-3 mb-3 border-start border-info border-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-building me-2 text-info"></i>2. Laporan Penerimaan</h6>
                        <small class="text-muted">Wajib diunggah UPT Penerima (Tujuan): <strong>{{ $mutasi->uptTujuan?->nama_upt ?? '-' }}</strong></small>
                    </div>
                    <div>
                        @if($isPenerima && ! $mutasi->hasLaporanPenerimaan())
                        <span class="badge bg-info text-white me-1"><i class="fas fa-arrow-circle-right me-1"></i>Tindakan UPT Anda</span>
                        @endif
                        @if($mutasi->hasLaporanPenerimaan())
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diunggah</span>
                        @else
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Menunggu</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($mutasi->hasLaporanPenerimaan())
                    <div class="p-2.5 bg-light rounded-3 border mb-2">
                        <div class="small text-muted">Tanggal Penerimaan WBP:</div>
                        <div class="fw-bold text-dark mb-1">{{ $mutasi->tanggal_penerimaan ? $mutasi->tanggal_penerimaan->format('d F Y') : '-' }}</div>
                        @if($mutasi->catatan_penerimaan)
                        <div class="small text-muted">Catatan Pemeriksaan &amp; Penerimaan:</div>
                        <div class="small text-secondary mb-2">{{ $mutasi->catatan_penerimaan }}</div>
                        @endif
                        <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'laporan_penerimaan']) }}" class="btn btn-outline-info btn-sm w-100" target="_blank">
                            <i class="fas fa-download me-1"></i> Unduh Berkas Laporan Penerimaan
                        </a>
                    </div>
                    @endif

                    @if(! $mutasi->hasLaporanPenerimaan() && $canUploadPenerimaan)
                    <form action="{{ route('mutasi.laporan-penerimaan.store', $mutasi->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Tanggal Penerimaan di UPT Tujuan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_penerimaan" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Upload Laporan Penerimaan Resmi (PDF/Gambar) <span class="text-danger">*</span></label>
                            <input type="file" name="file_laporan_penerimaan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Catatan Hasil Pemeriksaan &amp; Kamar Hunian</label>
                            <textarea name="catatan_penerimaan" class="form-control form-control-sm" rows="2" placeholder="Catatan kondisi kesehatan WBP saat tiba, kelengkapan berkas fisik, penempatan blok hunian..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-info btn-sm text-white fw-bold">
                                <i class="fas fa-upload me-1"></i> Unggah Laporan Penerimaan
                            </button>
                        </div>
                    </form>
                    @elseif(! $mutasi->hasLaporanPenerimaan())
                    <p class="small text-muted mb-0 fst-italic">Menunggu laporan penerimaan dan pemeriksaan fisik diunggah oleh pihak UPT Penerima ({{ $mutasi->uptTujuan?->nama_upt }}).</p>
                    @endif
                </div>
            </div>

            <!-- 4C. BERITA ACARA SERAH TERIMA (BAST BERSAMA) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-success border-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-file-signature me-2 text-success"></i>3. Berita Acara Serah Terima (BAST)</h6>
                        <small class="text-muted">Dokumen bersama yang ditandatangani kedua UPT</small>
                    </div>
                    @if($mutasi->hasBast())
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Tuntas</span>
                    @else
                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Menunggu</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($mutasi->hasBast())
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="small text-muted">Nomor BAST:</div>
                        <div class="fw-bold font-monospace text-dark mb-1">{{ $mutasi->nomor_bast ?: '-' }}</div>

                        <div class="small text-muted">Tanggal Pelaksanaan BAST:</div>
                        <div class="fw-bold text-dark mb-1">{{ $mutasi->tanggal_bast ? $mutasi->tanggal_bast->format('d F Y') : ($mutasi->tanggal_pelaksanaan ? $mutasi->tanggal_pelaksanaan->format('d F Y') : '-') }}</div>

                        @if($mutasi->catatan_pelaksanaan)
                        <div class="small text-muted">Catatan Serah Terima:</div>
                        <div class="small text-secondary mb-2">{{ $mutasi->catatan_pelaksanaan }}</div>
                        @endif

                        <a href="{{ route('mutasi.download-file', ['id' => $mutasi->id, 'type' => 'bast']) }}" class="btn btn-outline-success btn-sm w-100" target="_blank">
                            <i class="fas fa-download me-1"></i> Unduh Berkas Scan BAST Resmi
                        </a>
                    </div>
                    @endif

                    @if(! $mutasi->hasBast() && $canUploadBast)
                    <p class="text-muted small mb-3">Unggah scan Berita Acara Serah Terima (BAST) fisik yang telah ditandatangani oleh pejabat UPT Pengusul dan UPT Penerima:</p>
                    <form action="{{ route('mutasi.bast.store', $mutasi->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nomor BAST Resmi <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_bast" class="form-control form-control-sm font-monospace" placeholder="No. BAST" value="{{ old('nomor_bast') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tanggal BAST <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_bast" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Upload Scan BAST Resmi (PDF/Gambar) <span class="text-danger">*</span></label>
                            <input type="file" name="file_bast" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan Tambahan Pelaksanaan</label>
                            <textarea name="catatan_pelaksanaan" class="form-control form-control-sm" rows="2" placeholder="Catatan kondisi serah terima barang dan narapidana..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-check-circle me-1"></i> Unggah BAST &amp; Selesaikan Rangkaian Mutasi
                            </button>
                        </div>
                    </form>
                    @elseif(! $mutasi->hasBast())
                    <p class="small text-muted mb-0 fst-italic">Menunggu dokumen Berita Acara Serah Terima (BAST) fisik diunggah oleh pihak UPT terkait.</p>
                    @endif
                </div>
            </div>
            @else
            <!-- SEKSI 4 (TERKUNCI / MENUNGGU PERSETUJUAN KANWIL) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-secondary border-4">
                <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-muted"><i class="fas fa-lock me-2"></i>Tahap 4: Laporan Pemindahan, Penerimaan &amp; BAST</h6>
                        <small class="text-muted">Akan terbuka otomatis setelah Kantor Wilayah menerbitkan Surat Persetujuan</small>
                    </div>
                    <span class="badge bg-secondary"><i class="fas fa-hourglass-start me-1"></i>Belum Aktif</span>
                </div>
                <div class="card-body py-4 text-center">
                    <div class="mb-3">
                        <span class="rounded-circle bg-secondary bg-opacity-10 p-3 d-inline-flex text-secondary">
                            <i class="fas fa-truck-loading fa-2x"></i>
                        </span>
                    </div>
                    <h6 class="fw-bold text-dark">Tahap Pelaporan Pemindahan Masih Menunggu Persetujuan Kanwil</h6>
                    <p class="small text-muted mb-3 mx-auto" style="max-width: 480px;">
                        Setelah Kantor Wilayah menyetujui usulan dan menetapkan UPT Penerima, formulir pelaporan ini akan otomatis aktif bagi:
                    </p>
                    <div class="row g-2 text-start small justify-content-center">
                        <div class="col-md-4">
                            <div class="p-2 border rounded bg-white h-100 shadow-sm">
                                <div class="fw-bold text-primary mb-1"><i class="fas fa-truck-departure me-1"></i>1. UPT Pengusul</div>
                                <span class="text-muted" style="font-size: 11px;">Mengunggah <strong>Laporan Pemindahan</strong> saat narapidana diberangkatkan.</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 border rounded bg-white h-100 shadow-sm">
                                <div class="fw-bold text-info mb-1"><i class="fas fa-building me-1"></i>2. UPT Penerima</div>
                                <span class="text-muted" style="font-size: 11px;">Mengunggah <strong>Laporan Penerimaan</strong> fisik &amp; penempatan kamar hunian.</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 border rounded bg-white h-100 shadow-sm">
                                <div class="fw-bold text-success mb-1"><i class="fas fa-file-signature me-1"></i>3. Dokumen BAST</div>
                                <span class="text-muted" style="font-size: 11px;">Melampirkan <strong>Berita Acara Serah Terima</strong> bersama kedua UPT.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectStatus = document.getElementById('selectStatusVerifikasi');
        const sectionDisetujui = document.getElementById('sectionDisetujui');
        const selectUptTujuan = document.getElementById('selectUptTujuan');

        if (selectStatus && sectionDisetujui) {
            function toggleVerifikasi() {
                if (selectStatus.value === 'disetujui') {
                    sectionDisetujui.style.display = 'block';
                    if (selectUptTujuan) selectUptTujuan.setAttribute('required', 'required');
                } else {
                    sectionDisetujui.style.display = 'none';
                    if (selectUptTujuan) selectUptTujuan.removeAttribute('required');
                }
            }

            selectStatus.addEventListener('change', toggleVerifikasi);
            toggleVerifikasi();
        }
    });
</script>
@endpush
