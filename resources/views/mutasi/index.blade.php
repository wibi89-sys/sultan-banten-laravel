@extends('layouts.app')

@section('title', 'Mutasi Narapidana Antar-UPT - Sultan Banten')
@section('page_title')
    <i class="fas fa-exchange-alt me-2 text-primary"></i>Mutasi Narapidana Antar-UPT Wilayah Banten
@endsection

@push('styles')
<style>
    .stat-card-mutasi {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        position: relative;
    }
    .stat-card-mutasi:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .stat-card-mutasi .stat-icon {
        position: absolute;
        right: 15px;
        bottom: 12px;
        font-size: 42px;
        opacity: 0.16;
    }
    .badge-perkara {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 7px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
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

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card-mutasi bg-white border-start border-primary border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Total Pengajuan</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['total_usulan']) }} <span class="fs-6 fw-normal text-muted">Berkas</span></div>
                <div class="small text-primary mt-1"><i class="fas fa-file-alt me-1"></i>Usulan Antar-UPT</div>
                <i class="fas fa-folder-open stat-icon text-primary"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-mutasi bg-white border-start border-warning border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Menunggu Kanwil</div>
                <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['menunggu']) }} <span class="fs-6 fw-normal text-muted">Usulan</span></div>
                <div class="small text-muted mt-1"><i class="fas fa-hourglass-half me-1"></i>Perlu Telaah Kanwil</div>
                <i class="fas fa-stamp stat-icon text-warning"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-mutasi bg-white border-start border-info border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Disetujui Kanwil</div>
                <div class="fs-4 fw-bold text-info mt-1">{{ number_format($stats['disetujui']) }} <span class="fs-6 fw-normal text-muted">Surat Terbit</span></div>
                <div class="small text-muted mt-1"><i class="fas fa-truck-moving me-1"></i>Siap Pemindahan</div>
                <i class="fas fa-check-double stat-icon text-info"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-mutasi bg-white border-start border-success border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Selesai (BAST)</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['selesai']) }} <span class="fs-6 fw-normal text-muted">Terlaksana</span></div>
                <div class="small text-muted mt-1"><i class="fas fa-users me-1"></i>{{ number_format($stats['total_wbp']) }} WBP Terdata</div>
                <i class="fas fa-handshake stat-icon text-success"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list-ul me-2 text-primary"></i>Daftar Berkas Usulan Mutasi Narapidana</h5>
                    <small class="text-muted">Data permohonan persetujuan dan penetapan pemindahan narapidana se-Wilayah Banten</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    @if(auth()->user()->canManage('mutasi') && ! empty(auth()->user()->upt_id))
                    <a href="{{ route('mutasi.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> Buat Usulan Baru
                    </a>
                    @elseif(! auth()->user()->canManage('mutasi'))
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1.5 px-2.5">
                        <i class="fas fa-eye me-1"></i> Mode Lihat Saja
                    </span>
                    @endif
                    <a href="{{ route('mutasi.rekapitulasi') }}" class="btn btn-outline-success btn-sm px-3 shadow-sm">
                        <i class="fas fa-chart-pie me-1"></i> Rekapitulasi Mutasi
                    </a>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="{{ route('mutasi.index') }}" class="row g-2 align-items-end pt-3 mt-1 border-top">
                @if(auth()->user()->isAdmin() || empty(auth()->user()->upt_id))
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Filter UPT</label>
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Semua UPT --</option>
                        @foreach($uptList as $u)
                        <option value="{{ $u->id }}" {{ request('upt_id') == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Kategori</label>
                    <select name="kategori" class="form-select form-select-sm">
                        <option value="">-- Semua Kategori --</option>
                        <option value="Pembinaan" {{ request('kategori') == 'Pembinaan' ? 'selected' : '' }}>Pembinaan</option>
                        <option value="Keamanan" {{ request('kategori') == 'Keamanan' ? 'selected' : '' }}>Keamanan</option>
                        <option value="Overkapasitas" {{ request('kategori') == 'Overkapasitas' ? 'selected' : '' }}>Overkapasitas</option>
                        <option value="Lainnya" {{ request('kategori') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu Kanwil</option>
                        <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui Kanwil</option>
                        <option value="perlu_perbaikan" {{ request('status') == 'perlu_perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                        <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                        <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai (BAST)</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Cari No. Surat / Keterangan</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="No. surat usulan atau kanwil..." value="{{ request('search') }}">
                </div>

                <div class="col-md-2 col-sm-6 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="{{ route('mutasi.index') }}" class="btn btn-light btn-sm text-secondary border" title="Reset Filter"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" width="4%">No</th>
                        <th width="18%">Surat Usulan UPT</th>
                        <th width="16%">UPT Pemohon &amp; Kategori</th>
                        <th width="18%">Rincian Perkara (Usulan)</th>
                        <th width="16%">Penetapan UPT Tujuan</th>
                        <th class="text-center" width="12%">Status</th>
                        <th class="text-center" width="8%">Berkas</th>
                        <th class="text-center" width="8%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutasiList as $idx => $m)
                    <tr>
                        <td class="text-center text-muted fw-semibold">{{ $mutasiList->firstItem() + $idx }}</td>
                        <td>
                            <div class="fw-bold text-dark font-monospace small">{{ $m->nomor_surat }}</div>
                            <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>{{ $m->tanggal_surat ? $m->tanggal_surat->format('d/m/Y') : '-' }}</small>
                            @if($m->keterangan)
                            <div class="text-truncate small text-secondary mt-1" style="max-width: 220px;" title="{{ $m->keterangan }}">
                                <i class="fas fa-info-circle me-1 text-muted"></i>{{ $m->keterangan }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $m->uptAsal?->nama_upt ?? '-' }}</div>
                            <span class="badge {{ $m->kategori_badge_class }} mt-1" style="font-size: 10px;">
                                <i class="fas fa-tag me-1"></i>{{ $m->kategori_mutasi }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle badge-perkara" title="Narkotika">
                                    Narkotika: <strong>{{ $m->jumlah_narkotika }}</strong>
                                </span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle badge-perkara" title="Pidana Umum">
                                    Pidum: <strong>{{ $m->jumlah_pidum }}</strong>
                                </span>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle badge-perkara" title="Tipikor / Korupsi">
                                    Korupsi: <strong>{{ $m->jumlah_korupsi }}</strong>
                                </span>
                            </div>
                            <div class="small fw-bold text-dark mt-1">
                                Total: <span class="text-primary">{{ $m->total_usulan }} Orang</span>
                            </div>
                        </td>
                        <td>
                            @if($m->tujuans->count() > 1)
                                <div class="fw-bold text-success mb-0.5">
                                    <i class="fas fa-sitemap me-1"></i>{{ $m->tujuans->count() }} UPT Penerima
                                </div>
                                <div class="small text-muted" style="font-size: 11px; line-height: 1.3;">
                                    {{ $m->daftar_tujuan_text }}
                                </div>
                                @if($m->total_disetujui !== null)
                                <small class="text-success fw-semibold d-block mt-0.5"><i class="fas fa-check-circle me-1"></i>Total: {{ $m->total_disetujui }} WBP</small>
                                @endif
                            @elseif($m->uptTujuan)
                                <div class="fw-bold text-success"><i class="fas fa-building me-1"></i>{{ $m->uptTujuan->nama_upt }}</div>
                                @if($m->nomor_surat_kanwil)
                                <small class="text-muted font-monospace d-block" style="font-size: 11px;">Persetujuan: {{ $m->nomor_surat_kanwil }}</small>
                                @endif
                                @if($m->total_disetujui !== null)
                                <small class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Disetujui: {{ $m->total_disetujui }} orang</small>
                                @endif
                            @else
                                <span class="badge bg-light text-muted border fst-italic">
                                    <i class="fas fa-hourglass-start me-1"></i>Ditentukan Kanwil
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $m->status_badge_class }} px-2 py-1">
                                {{ $m->status_label }}
                            </span>
                            @if($m->tanggal_pelaksanaan)
                            <small class="d-block text-muted mt-1" style="font-size: 10px;">
                                BAST: {{ $m->tanggal_pelaksanaan->format('d/m/Y') }}
                            </small>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                @if($m->file_surat_usulan)
                                <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'surat_usulan']) }}" class="btn btn-outline-primary btn-sm py-0 px-1.5" title="Unduh Surat Usulan UPT" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @endif
                                @if($m->file_persetujuan_kanwil)
                                <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'persetujuan_kanwil']) }}" class="btn btn-success btn-sm py-0 px-1.5" title="Unduh Surat Persetujuan Kanwil" target="_blank">
                                    <i class="fas fa-stamp"></i>
                                </a>
                                @endif
                                @if($m->file_laporan_pemindahan)
                                <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'laporan_pemindahan']) }}" class="btn btn-outline-primary btn-sm py-0 px-1.5" title="Unduh Laporan Pemindahan (UPT Pengusul)" target="_blank">
                                    <i class="fas fa-truck-departure"></i>
                                </a>
                                @endif
                                @if($m->file_laporan_penerimaan)
                                <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'laporan_penerimaan']) }}" class="btn btn-outline-info btn-sm py-0 px-1.5" title="Unduh Laporan Penerimaan (UPT Penerima)" target="_blank">
                                    <i class="fas fa-building"></i>
                                </a>
                                @endif
                                @if($m->file_bast)
                                <a href="{{ route('mutasi.download-file', ['id' => $m->id, 'type' => 'bast']) }}" class="btn btn-info btn-sm py-0 px-1.5 text-white" title="Unduh BAST Serah Terima" target="_blank">
                                    <i class="fas fa-file-signature"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('mutasi.show', $m->id) }}" class="btn btn-outline-info py-0 px-2" title="Lihat Rincian & Verifikasi">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->isAdmin() || ($m->status === 'diajukan' && $m->created_by === auth()->id()))
                                <form action="{{ route('mutasi.destroy', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas usulan mutasi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger py-0 px-2" title="Hapus Berkas">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x text-secondary mb-2 d-block opacity-50"></i>
                            Belum ada berkas usulan mutasi narapidana yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutasiList->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $mutasiList->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
