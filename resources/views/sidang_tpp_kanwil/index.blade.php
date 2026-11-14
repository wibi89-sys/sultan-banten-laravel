@extends('layouts.app')

@section('title', 'Sidang TPP Kantor Wilayah - Sultan Banten')
@section('page_title', 'Sidang TPP Kantor Wilayah')

@section('content')
<div class="content-wrapper">

    <!-- Header & Action Row -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text-primary); font-size: 20px;">
                <i class="fas fa-university text-primary me-2"></i>Sidang TPP Tingkat Kantor Wilayah
            </h4>
            <p class="text-secondary small mb-0">
                Pencatatan dan pelaksanaan Sidang Tim Pengamat Pemasyarakatan internal Kantor Wilayah Ditjenpas Banten
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sidang-tpp-kanwil.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm" style="border-radius: 20px; font-weight: 600;">
                <i class="fas fa-plus-circle me-1"></i> Registrasi Sidang Baru
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- 5 Snapshot Bento Cards Row -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-sm-4 col-xl">
            <div class="bento-card snapshot-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="snapshot-card-label" style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Total Sidang</span>
                    <span class="badge bg-primary-subtle text-primary p-2" style="border-radius: 8px;">
                        <i class="fas fa-gavel"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-primary font-speedometer">
                    {{ number_format($stats['total_sidang']) }}
                </div>
                <small class="text-muted" style="font-size: 10.5px;">Seluruh Pelaksanaan</small>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-xl">
            <div class="bento-card snapshot-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="snapshot-card-label" style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Tahun {{ date('Y') }}</span>
                    <span class="badge bg-info-subtle text-info p-2" style="border-radius: 8px;">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-info font-speedometer">
                    {{ number_format($stats['total_tahun_ini']) }}
                </div>
                <small class="text-muted" style="font-size: 10.5px;">Sidang Tahun Ini</small>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-xl">
            <div class="bento-card snapshot-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="snapshot-card-label" style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Aktif / Berlangsung</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis p-2" style="border-radius: 8px;">
                        <i class="fas fa-comments"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-warning-emphasis font-speedometer">
                    {{ number_format($stats['total_berlangsung']) }}
                </div>
                <small class="text-muted" style="font-size: 10.5px;">Undangan &amp; Pendapat</small>
            </div>
        </div>
        <div class="col-6 col-sm-6 col-xl">
            <div class="bento-card snapshot-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="snapshot-card-label" style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Menunggu Kakanwil</span>
                    <span class="badge bg-danger-subtle text-danger p-2" style="border-radius: 8px;">
                        <i class="fas fa-user-clock"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-danger font-speedometer">
                    {{ number_format($stats['total_menunggu_kakanwil']) }}
                </div>
                <small class="text-muted" style="font-size: 10.5px;">Proses Disposisi</small>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl">
            <div class="bento-card snapshot-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="snapshot-card-label" style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Selesai (Sah)</span>
                    <span class="badge bg-success-subtle text-success p-2" style="border-radius: 8px;">
                        <i class="fas fa-check-double"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-success font-speedometer">
                    {{ number_format($stats['total_selesai']) }}
                </div>
                <small class="text-muted" style="font-size: 10.5px;">Arsip Fisik Terunggah</small>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--bg-card); border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sidang-tpp-kanwil.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-search me-1"></i>Pencarian</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="No. Sidang, Nota Dinas, Agenda..." value="{{ $search }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-flag me-1"></i>Status Sidang</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="draft" {{ $statusSidang == 'draft' ? 'selected' : '' }}>Draf</option>
                        <option value="undangan_terbit" {{ $statusSidang == 'undangan_terbit' ? 'selected' : '' }}>Undangan Terbit</option>
                        <option value="berlangsung" {{ $statusSidang == 'berlangsung' ? 'selected' : '' }}>Berlangsung</option>
                        <option value="menunggu_kakanwil" {{ $statusSidang == 'menunggu_kakanwil' ? 'selected' : '' }}>Menunggu Kakanwil</option>
                        <option value="selesai" {{ $statusSidang == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-stamp me-1"></i>Disposisi Kakanwil</label>
                    <select name="disposisi" class="form-select form-select-sm">
                        <option value="">-- Semua --</option>
                        <option value="menunggu" {{ $statusDisposisi == 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                        <option value="disetujui" {{ $statusDisposisi == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="disetujui_bersyarat" {{ $statusDisposisi == 'disetujui_bersyarat' ? 'selected' : '' }}>Disetujui Bersyarat</option>
                        <option value="ditolak" {{ $statusDisposisi == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $tanggalMulai }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Sampai</label>
                    <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ $tanggalSampai }}">
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Terapkan Filter">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(!empty($search) || !empty($statusSidang) || !empty($statusDisposisi) || $tanggalMulai != date('Y-01-01') || $tanggalSampai != date('Y-12-31'))
                    <a href="{{ route('sidang-tpp-kanwil.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm" style="background: var(--bg-card); border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead style="background: rgba(148, 163, 184, 0.08); border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
                    <tr>
                        <th class="text-center py-3" style="width: 4%;">No</th>
                        <th class="py-3" style="width: 18%;">No. Sidang &amp; Nota Dinas</th>
                        <th class="py-3" style="width: 14%;">Tanggal &amp; Waktu</th>
                        <th class="py-3" style="width: 24%;">Agenda Pembahasan</th>
                        <th class="py-3" style="width: 14%;">Pimpinan Sidang</th>
                        <th class="text-center py-3" style="width: 12%;">Presensi &amp; Rekap</th>
                        <th class="text-center py-3" style="width: 14%;">Status &amp; Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidangList as $s)
                    <tr>
                        <td class="text-center text-muted fw-bold">{{ $sidangList->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('sidang-tpp-kanwil.show', $s->id) }}" class="fw-bold text-primary text-decoration-none d-block">
                                {{ $s->nomor_sidang }}
                            </a>
                            <div class="small text-muted mt-0.5">
                                <i class="fas fa-file-alt me-1 text-secondary"></i>Nota: <span class="fw-semibold">{{ $s->nomor_nota_dinas ?: '-' }}</span>
                            </div>
                            <span class="badge bg-light text-dark border mt-1" style="font-size: 10px;">
                                <i class="fas fa-map-marker-alt me-1 text-danger"></i>{{ ucfirst($s->metode) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $s->tanggal_sidang ? $s->tanggal_sidang->format('d/m/Y') : '-' }}</div>
                            <small class="text-muted d-block">
                                {{ $s->tanggal_sidang ? $s->tanggal_sidang->translatedFormat('l') : '' }}
                            </small>
                            <span class="badge bg-light text-secondary border mt-1" style="font-size: 10px;">
                                <i class="fas fa-clock me-1 text-info"></i>{{ substr($s->jam_mulai, 0, 5) }} WIB
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary mb-1" style="font-size: 10.5px;">
                                {{ $s->agenda_kategori }}
                            </span>
                            <div class="fw-bold text-dark" style="line-height: 1.3;">
                                {{ $s->agenda_judul }}
                            </div>
                            @if(!empty($s->agenda_deskripsi))
                            <small class="text-muted d-block mt-0.5 text-truncate" style="max-width: 260px;" title="{{ $s->agenda_deskripsi }}">
                                {{ $s->agenda_deskripsi }}
                            </small>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size: 12.5px;">
                                <span class="text-muted small">Ketua:</span> {{ $s->ketua_nama }}
                            </div>
                            <div class="small text-secondary mt-0.5">
                                <span class="text-muted">Sekr:</span> {{ $s->sekretaris_nama }}
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1 mb-1">
                                <span class="badge bg-light text-dark border" title="Hadir: {{ $s->total_hadir }} dari {{ $s->total_anggota }}">
                                    <i class="fas fa-users text-primary me-1"></i>{{ $s->total_hadir }}/{{ $s->total_anggota }}
                                </span>
                            </div>
                            <div class="small text-muted" style="font-size: 10px;">
                                <span class="text-success fw-bold">{{ $s->total_setuju }}</span> Setuju &bull;
                                <span class="text-danger fw-bold">{{ $s->total_tolak }}</span> Tolak
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="mb-1">{!! $s->status_badge !!}</div>
                            <div class="mb-2">{!! $s->tanggapan_badge !!}</div>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('sidang-tpp-kanwil.show', $s->id) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2" title="Buka Detail & Kelola Sidang">
                                    <i class="fas fa-folder-open me-1"></i>Buka
                                </a>
                                @if($s->status_sidang !== 'selesai')
                                <form action="{{ route('sidang-tpp-kanwil.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Sidang TPP Kanwil {{ $s->nomor_sidang }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0.5 px-1.5" title="Hapus Sidang">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-university fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="fw-bold">Belum Ada Pelaksanaan Sidang TPP Kantor Wilayah</div>
                            <p class="small text-muted mb-3">Daftarkan agenda sidang TPP internal Kantor Wilayah untuk memulai pencatatan dan penyebaran undangan.</p>
                            <a href="{{ route('sidang-tpp-kanwil.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm" style="border-radius: 20px;">
                                <i class="fas fa-plus-circle me-1"></i> Daftarkan Sidang Baru
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sidangList->hasPages())
        <div class="card-footer bg-transparent py-3 border-top">
            {{ $sidangList->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
