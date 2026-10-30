@extends('layouts.app')

@section('title', 'Laporan Pemindahan & BAST - Sultan Banten')
@section('page_title')
    <i class="fas fa-truck-moving me-2 text-primary"></i>4. Laporan Pemindahan &amp; BAST
@endsection

@push('styles')
<style>
    .badge-doc-status {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('mutasi.index') }}" class="text-decoration-none">Mutasi Narapidana</a></li>
            <li class="breadcrumb-item active" aria-current="page">4. Laporan Pemindahan &amp; BAST</li>
        </ol>
    </nav>

    <!-- Header Description Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-check me-2 text-primary"></i>Tahap 4: Pelaporan Pelaksanaan &amp; Berita Acara Serah Terima</h5>
                    <small class="text-muted">Kelola dan pantau 3 dokumen pelaksanaan pasca-persetujuan Kanwil: <strong>Laporan Pemindahan</strong> (UPT Pengirim), <strong>Laporan Penerimaan</strong> (UPT Penerima), dan <strong>BAST Bersama</strong>.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('mutasi.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-list me-1"></i> Semua Usulan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-primary border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total Disetujui</small>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($stats['total_disetujui']) }} <span class="fs-6 fw-normal text-muted">Berkas</span></div>
                <small class="text-muted mt-1"><i class="fas fa-stamp me-1"></i>Persetujuan Terbit</small>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-warning border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Menunggu Lap. Pemindahan</small>
                <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['belum_pemindahan']) }}</div>
                <small class="text-muted mt-1"><i class="fas fa-truck-departure me-1"></i>UPT Pengusul</small>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-info border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Menunggu Lap. Penerimaan</small>
                <div class="fs-4 fw-bold text-info mt-1">{{ number_format($stats['belum_penerimaan']) }}</div>
                <small class="text-muted mt-1"><i class="fas fa-building me-1"></i>UPT Penerima</small>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-danger border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Menunggu BAST</small>
                <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($stats['belum_bast']) }}</div>
                <small class="text-muted mt-1"><i class="fas fa-file-signature me-1"></i>Kedua UPT</small>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-success border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Mutasi Selesai (Tuntas)</small>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['tuntas']) }}</div>
                <small class="text-muted mt-1"><i class="fas fa-check-double me-1"></i>BAST Terunggah</small>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('mutasi.pelaksanaan') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari No. Surat Usulan, No. Persetujuan Kanwil, atau No. BAST..." value="{{ request('search') }}">
                    </div>
                </div>
                @if(auth()->user()->isAdmin() || empty(auth()->user()->upt_id))
                <div class="col-md-4">
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Semua UPT Terkait --</option>
                        @foreach($uptList as $u)
                        <option value="{{ $u->id }}" {{ request('upt_id') == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'upt_id']))
                    <a href="{{ route('mutasi.pelaksanaan') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th width="4%" class="text-center">No</th>
                            <th width="20%">Surat Persetujuan Kanwil</th>
                            <th width="24%">Arus UPT (Asal &rarr; Tujuan)</th>
                            <th width="32%">Kelengkapan 3 Dokumen Pelaksanaan</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutasiList as $m)
                        <tr>
                            <td class="text-center fw-semibold text-muted">{{ $loop->iteration + ($mutasiList->currentPage() - 1) * $mutasiList->perPage() }}</td>
                            <td>
                                <div class="fw-bold font-monospace text-dark">{{ $m->nomor_surat_kanwil ?: 'Surat Persetujuan Kanwil' }}</div>
                                <small class="text-muted d-block">
                                    <i class="far fa-calendar me-1"></i>{{ $m->tanggal_surat_kanwil ? $m->tanggal_surat_kanwil->format('d/m/Y') : '-' }}
                                </small>
                                <small class="text-success fw-semibold">
                                    <i class="fas fa-users me-1"></i>Disetujui: {{ $m->total_disetujui ?? $m->total_usulan }} WBP
                                </small>
                                @if($m->file_persetujuan_kanwil)
                                <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'persetujuan_kanwil']) }}" class="badge bg-success text-decoration-none mt-1 d-inline-block" target="_blank">
                                    <i class="fas fa-download me-1"></i>Unduh Surat Kanwil
                                </a>
                                @endif
                            </td>
                            <td>
                                <div class="mb-1">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">UPT Pengirim / Asal</span>
                                    <div class="fw-semibold text-dark">{{ $m->uptAsal?->nama_upt ?? '-' }}</div>
                                </div>
                                <div>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 10px;">UPT Penerima / Tujuan</span>
                                    <div class="fw-semibold text-dark small" style="line-height: 1.3;">{{ $m->daftar_tujuan_text }}</div>
                                </div>
                            </td>
                            <td>
                                <!-- 1. Laporan Pemindahan -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5 p-1.5 rounded bg-light border">
                                    <div>
                                        <i class="fas fa-truck-departure text-primary me-1"></i>
                                        <span class="small fw-semibold">1. Lap. Pemindahan (Pengirim)</span>
                                    </div>
                                    <div>
                                        @if($m->hasLaporanPemindahan())
                                        <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'laporan_pemindahan']) }}" class="badge bg-success text-decoration-none" target="_blank" title="Unduh Laporan Pemindahan">
                                            <i class="fas fa-check me-1"></i>Ada (Unduh)
                                        </a>
                                        @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Belum Unggah</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- 2. Laporan Penerimaan -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5 p-1.5 rounded bg-light border">
                                    <div>
                                        <i class="fas fa-building text-info me-1"></i>
                                        <span class="small fw-semibold">2. Lap. Penerimaan (Penerima)</span>
                                    </div>
                                    <div>
                                        @if($m->hasLaporanPenerimaan())
                                        <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'laporan_penerimaan']) }}" class="badge bg-success text-decoration-none" target="_blank" title="Unduh Laporan Penerimaan">
                                            <i class="fas fa-check me-1"></i>Ada (Unduh)
                                        </a>
                                        @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Belum Unggah</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- 3. BAST Bersama -->
                                <div class="d-flex justify-content-between align-items-center p-1.5 rounded bg-light border">
                                    <div>
                                        <i class="fas fa-file-signature text-success me-1"></i>
                                        <span class="small fw-semibold">3. BA Serah Terima (BAST)</span>
                                    </div>
                                    <div>
                                        @if($m->hasBast())
                                        <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'bast']) }}" class="badge bg-success text-decoration-none" target="_blank" title="Unduh BAST">
                                            <i class="fas fa-check me-1"></i>Ada (Unduh)
                                        </a>
                                        @else
                                        <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Belum Unggah</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $m->status_badge_class }} px-2 py-1">
                                    {{ $m->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('mutasi.show', $m->id) }}" class="btn btn-primary btn-sm px-2.5 shadow-sm" title="Buka Detail & Unggah Dokumen">
                                    <i class="fas fa-folder-open me-1"></i> Buka / Unggah
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold">Belum Ada Usulan Mutasi yang Disetujui</h6>
                                <p class="small text-muted mb-0">Halaman ini memuat seluruh usulan mutasi yang telah memperoleh Surat Persetujuan dari Kantor Wilayah untuk dilengkapi dokumen pelaksanaan pemindahan dan BAST.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($mutasiList->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $mutasiList->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

