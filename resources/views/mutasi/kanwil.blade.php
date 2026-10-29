@extends('layouts.app')

@section('title', 'Persetujuan Kanwil - Sultan Banten')
@section('page_title')
    <i class="fas fa-stamp me-2 text-primary"></i>3. Persetujuan &amp; Penetapan Kanwil
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('mutasi.index') }}" class="text-decoration-none">Mutasi Narapidana</a></li>
            <li class="breadcrumb-item active" aria-current="page">3. Persetujuan Kanwil</li>
        </ol>
    </nav>

    <!-- Header Description Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-user-check me-2 text-primary"></i>Tahap 3: Telaah &amp; Penerbitan Persetujuan Kantor Wilayah</h5>
                    <small class="text-muted">Pemeriksaan kelengkapan berkas mutasi, pertimbangan kapasitas hunian, penunjukan UPT Penerima, dan penerbitan <strong>Surat Persetujuan Kanwil</strong>.</small>
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
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-warning border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Perlu Telaah Kanwil</small>
                <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['perlu_telaah']) }} <span class="fs-6 fw-normal text-muted">Usulan</span></div>
                <small class="text-muted mt-1"><i class="fas fa-hourglass-half me-1"></i>Menunggu Verifikasi</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-success border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Surat Persetujuan Terbit</small>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['disetujui']) }} <span class="fs-6 fw-normal text-muted">Berkas</span></div>
                <small class="text-muted mt-1"><i class="fas fa-check-circle me-1"></i>Siap Pemindahan</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-danger border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Usulan Ditolak</small>
                <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($stats['ditolak']) }}</div>
                <small class="text-muted mt-1"><i class="fas fa-times-circle me-1"></i>Tidak Disetujui</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-primary border-4 p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total Usulan Masuk</small>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($stats['total']) }}</div>
                <small class="text-muted mt-1"><i class="fas fa-folder me-1"></i>Seluruh Riwayat</small>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('mutasi.kanwil') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari No. Surat Usulan atau Keterangan..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu Telaah Kanwil</option>
                        <option value="perlu_perbaikan" {{ request('status') == 'perlu_perbaikan' ? 'selected' : '' }}>Perlu Perbaikan Berkas</option>
                        <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui Kanwil</option>
                        <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                @if(auth()->user()->isAdmin() || empty(auth()->user()->upt_id))
                <div class="col-md-3">
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Semua UPT Pengusul --</option>
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
                    @if(request()->anyFilled(['search', 'status', 'upt_id']))
                    <a href="{{ route('mutasi.kanwil') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
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
                            <th width="22%">Surat Usulan UPT</th>
                            <th width="20%">UPT Pengusul &amp; Kategori</th>
                            <th width="20%">Pendataan Perkara WBP</th>
                            <th width="14%">Kelengkapan Berkas</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutasiList as $m)
                        <tr>
                            <td class="text-center fw-semibold text-muted">{{ $loop->iteration + ($mutasiList->currentPage() - 1) * $mutasiList->perPage() }}</td>
                            <td>
                                <div class="fw-bold font-monospace text-dark">{{ $m->nomor_surat }}</div>
                                <small class="text-muted d-block">
                                    <i class="far fa-calendar me-1"></i>{{ $m->tanggal_surat ? $m->tanggal_surat->format('d/m/Y') : '-' }}
                                </small>
                                <small class="text-muted">
                                    Oleh: {{ $m->creator?->nama_lengkap ?? 'Petugas UPT' }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ $m->uptAsal?->nama_upt ?? '-' }}</div>
                                <span class="badge {{ $m->kategori_badge_class }} mt-1">
                                    <i class="fas fa-tag me-1"></i>{{ $m->kategori_mutasi }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10px;">
                                        Narkotika: {{ $m->jumlah_narkotika }}
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">
                                        Pidum: {{ $m->jumlah_pidum }}
                                    </span>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 10px;">
                                        Korupsi: {{ $m->jumlah_korupsi }}
                                    </span>
                                </div>
                                <div class="small fw-bold text-dark mt-1">
                                    Total: <span class="text-primary">{{ $m->total_usulan }} Orang</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($m->file_surat_usulan)
                                    <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'surat_usulan']) }}" class="badge bg-primary text-decoration-none" target="_blank" title="Surat Usulan">
                                        <i class="fas fa-file-pdf me-1"></i>Surat
                                    </a>
                                    @endif
                                    @if($m->file_asesmen)
                                    <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'asesmen']) }}" class="badge bg-secondary text-decoration-none" target="_blank" title="Asesmen">
                                        <i class="fas fa-file-alt me-1"></i>Asesmen
                                    </a>
                                    @endif
                                    @if($m->file_bap)
                                    <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'bap']) }}" class="badge bg-warning text-dark text-decoration-none" target="_blank" title="BAP">
                                        <i class="fas fa-shield-alt me-1"></i>BAP
                                    </a>
                                    @endif
                                    @if($m->file_reg_f)
                                    <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'reg_f']) }}" class="badge bg-info text-white text-decoration-none" target="_blank" title="Register F">
                                        <i class="fas fa-clipboard-list me-1"></i>Reg F
                                    </a>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $m->status_badge_class }} px-2 py-1">
                                    {{ $m->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('mutasi.show', $m->id) }}" class="btn btn-outline-primary btn-sm px-2.5 shadow-sm" title="Telaah & Verifikasi Berkas">
                                    <i class="fas fa-stamp me-1"></i> Telaah
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold">Tidak Ada Usulan yang Memerlukan Telaah</h6>
                                <p class="small text-muted mb-0">Seluruh usulan mutasi telah diproses oleh Kantor Wilayah.</p>
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

