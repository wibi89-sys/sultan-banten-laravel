@extends('layouts.app')

@section('title', 'Kontak & Target WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-address-book text-info me-2"></i>Buku Kontak & Target WhatsApp
@endsection

@section('content')
<!-- Header Quick Navigation Tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.gateway') }}">
                    <i class="fas fa-plug me-1 text-primary"></i>1. Gateway & Koneksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active py-1 px-3 fw-semibold" href="{{ route('wa.contacts') }}">
                    <i class="fas fa-address-book me-1 text-info"></i>2. Kontak & Target
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.schedules') }}">
                    <i class="fas fa-clock me-1 text-warning"></i>3. Jadwal & Template
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.logs') }}">
                    <i class="fas fa-history me-1 text-success"></i>4. Riwayat & Log
                </a>
            </li>
        </ul>
        <span class="badge bg-light text-dark border px-3 py-2 small">
            Total: <strong>{{ count($groups) }}</strong> Grup &bull; <strong>{{ count($pimpinanList) }}</strong> Pimpinan &bull; <strong>{{ count($operators) }}</strong> Operator
        </span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
    <i class="fas fa-exclamation-triangle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Nav Tabs Konten Kontak -->
<ul class="nav nav-tabs mb-3" id="contactTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="groups-tab" data-bs-toggle="tab" data-bs-target="#tab-groups" type="button" role="tab">
            <i class="fas fa-users text-primary me-2"></i>Daftar Grup WhatsApp ({{ count($groups) }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="pimpinan-tab" data-bs-toggle="tab" data-bs-target="#tab-pimpinan" type="button" role="tab">
            <i class="fas fa-user-tie text-success me-2"></i>Daftar Pimpinan ({{ count($pimpinanList) }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="operators-tab" data-bs-toggle="tab" data-bs-target="#tab-operators" type="button" role="tab">
            <i class="fas fa-user-cog text-warning me-2"></i>Daftar Operator UPT ({{ count($operators) }})
        </button>
    </li>
</ul>

<div class="tab-content" id="contactTabsContent">
    <!-- ================================= TAB 1: GRUP WHATSAPP ================================= -->
    <div class="tab-pane fade show active" id="tab-groups" role="tabpanel">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-1"><i class="fas fa-users text-primary me-2"></i>Target Grup WhatsApp</h6>
                    <small class="text-muted">Kelola ID grup WhatsApp untuk tujuan pengiriman laporan monitoring dan terpadu.</small>
                </div>
                @if(auth()->user()->canManage('wa_settings'))
                <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddGroup">
                    <i class="fas fa-plus me-1"></i>Tambah Grup Baru
                </button>
                @else
                <span class="badge bg-secondary px-3 py-2">
                    <i class="fas fa-eye me-1"></i> Mode Lihat Saja
                </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Grup</th>
                            <th>Group JID / ID WhatsApp</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-center" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $index => $grp)
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-semibold">{{ $grp->nama_grup }}</span>
                                @if($grp->is_default)
                                    <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 10px;">Default</span>
                                @endif
                            </td>
                            <td>
                                <code class="text-primary">{{ $grp->group_id }}</code>
                            </td>
                            <td class="small text-muted">{{ $grp->keterangan ?? '-' }}</td>
                            <td class="text-center">
                                <form action="{{ route('wa.groups.toggle', $grp->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs py-0 px-2 badge {{ $grp->aktif ? 'bg-success' : 'bg-secondary' }}" style="border:none;" title="Klik untuk ubah status">
                                        {{ $grp->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                @if(auth()->user()->canManage('wa_settings'))
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditGroup{{ $grp->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('wa.groups.destroy', $grp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus grup ini dari daftar target?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                @else
                                <span class="text-muted small"><i class="fas fa-lock text-secondary"></i></span>
                                @endif

                                <!-- Modal Edit Group -->
                                <div class="modal fade" id="modalEditGroup{{ $grp->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered text-start">
                                        <div class="modal-content">
                                            <div class="modal-header py-2 bg-primary text-white">
                                                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Grup WhatsApp</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('wa.groups.update', $grp->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Grup <span class="text-danger">*</span></label>
                                                        <input type="text" name="nama_grup" class="form-control form-control-sm" value="{{ $grp->nama_grup }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Group JID / ID Grup WhatsApp <span class="text-danger">*</span></label>
                                                        <input type="text" name="group_id" class="form-control form-control-sm font-monospace" value="{{ $grp->group_id }}" required>
                                                        <small class="text-muted" style="font-size: 11px;">Format Fonnte: <code>120363xxx@g.us</code> atau link invite grup.</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Keterangan</label>
                                                        <input type="text" name="keterangan" class="form-control form-control-sm" value="{{ $grp->keterangan }}">
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" name="aktif" id="edit_aktif_grp_{{ $grp->id }}" {{ $grp->aktif ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="edit_aktif_grp_{{ $grp->id }}">Status Grup Aktif</label>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_default" id="edit_default_grp_{{ $grp->id }}" {{ $grp->is_default ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="edit_default_grp_{{ $grp->id }}">Jadikan Grup Default Utama</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer py-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada grup WhatsApp yang didaftarkan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================= TAB 2: KONTAK PIMPINAN ================================= -->
    <div class="tab-pane fade" id="tab-pimpinan" role="tabpanel">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-1"><i class="fas fa-user-tie text-success me-2"></i>Daftar Kontak Pimpinan</h6>
                    <small class="text-muted">Pimpinan yang menerima kiriman laporan harian otomatis via nomor WhatsApp pribadi.</small>
                </div>
                @if(auth()->user()->canManage('wa_settings'))
                <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddPimpinan">
                    <i class="fas fa-plus me-1"></i>Tambah Pimpinan
                </button>
                @else
                <span class="badge bg-secondary px-3 py-2">
                    <i class="fas fa-eye me-1"></i> Mode Lihat Saja
                </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">Urutan</th>
                            <th>Nama Lengkap</th>
                            <th>Jabatan</th>
                            <th>Nomor WhatsApp</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pimpinanList as $p)
                        <tr>
                            <td class="fw-semibold text-center">{{ $p->urutan }}</td>
                            <td class="fw-semibold">{{ $p->nama }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $p->jabatan ?? '-' }}</span></td>
                            <td>
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $p->no_wa)) }}" target="_blank" class="text-decoration-none text-success fw-bold font-monospace">
                                    <i class="fab fa-whatsapp me-1"></i>{{ $p->no_wa }}
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $p->aktif ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if(auth()->user()->canManage('wa_settings'))
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditPimpinan{{ $p->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('wa.pimpinan.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pimpinan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                @else
                                <span class="text-muted small"><i class="fas fa-lock text-secondary"></i></span>
                                @endif

                                <!-- Modal Edit Pimpinan -->
                                <div class="modal fade" id="modalEditPimpinan{{ $p->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered text-start">
                                        <div class="modal-content">
                                            <div class="modal-header py-2 bg-success text-white">
                                                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Data Pimpinan</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('wa.pimpinan.update', $p->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                                        <input type="text" name="nama" class="form-control form-control-sm" value="{{ $p->nama }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Jabatan</label>
                                                        <input type="text" name="jabatan" class="form-control form-control-sm" value="{{ $p->jabatan }}">
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-8">
                                                            <label class="form-label small fw-semibold">Nomor WhatsApp <span class="text-danger">*</span></label>
                                                            <input type="text" name="no_wa" class="form-control form-control-sm" value="{{ $p->no_wa }}" required>
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="form-label small fw-semibold">Urutan</label>
                                                            <input type="number" name="urutan" class="form-control form-control-sm" value="{{ $p->urutan }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="aktif" id="edit_aktif_pim_{{ $p->id }}" {{ $p->aktif ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="edit_aktif_pim_{{ $p->id }}">Status Aktif Menerima Laporan</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer py-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success btn-sm px-3">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data pimpinan yang ditambahkan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================= TAB 3: OPERATOR UPT ================================= -->
    <div class="tab-pane fade" id="tab-operators" role="tabpanel">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-1"><i class="fas fa-user-cog text-warning me-2"></i>Daftar Nomor WhatsApp Operator UPT</h6>
                    <small class="text-muted">Target penerima pengingat pagi otomatis bagi operator yang belum mengisi data hari ini.</small>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-sm px-3">
                    <i class="fas fa-user-edit me-1"></i>Kelola User &amp; Nomor WA
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Operator</th>
                            <th>Satuan Kerja / UPT</th>
                            <th>Nomor WhatsApp</th>
                            <th class="text-center" style="width: 140px;">Status Nomor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operators as $index => $op)
                        @php
                            $hasPhone = !empty($op->no_wa);
                            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$op->no_wa);
                            $isValidFormat = $hasPhone && strlen($cleanPhone) >= 10;
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $op->nama_lengkap }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $op->upt?->nama_upt ?? '-' }}</span></td>
                            <td>
                                @if($hasPhone)
                                    <span class="font-monospace text-dark">{{ $op->no_wa }}</span>
                                @else
                                    <span class="text-danger small fst-italic"><i class="fas fa-times-circle me-1"></i>Belum diisi</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isValidFormat)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fas fa-check-circle me-1"></i>Siap Terima WA
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="fas fa-exclamation-circle me-1"></i>Perlu Diisi
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada akun operator terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Group -->
<div class="modal fade" id="modalAddGroup" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Grup WhatsApp Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('wa.groups.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Grup <span class="text-danger">*</span></label>
                        <input type="text" name="nama_grup" class="form-control form-control-sm" placeholder="Contoh: Grup Kanwil Banten" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Group JID / ID Grup WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="group_id" class="form-control form-control-sm font-monospace" placeholder="120363xxx@g.us atau kode tautan grup" required>
                        <small class="text-muted" style="font-size: 11px;">Didapatkan dari Fonnte / info tautan grup WhatsApp.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Contoh: Grup koordinasi harian kepala UPT">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="aktif" id="add_aktif_grp" checked>
                        <label class="form-check-label small" for="add_aktif_grp">Status Grup Aktif</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_default" id="add_default_grp">
                        <label class="form-check-label small" for="add_default_grp">Jadikan Grup Default Utama</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Tambah Grup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Pimpinan -->
<div class="modal fade" id="modalAddPimpinan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-success text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Kontak Pimpinan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('wa.pimpinan.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Contoh: Dr. Jalu Budhi Santoso, Bc.I.P., M.H." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control form-control-sm" placeholder="Contoh: Kepala Kantor Wilayah">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" name="no_wa" class="form-control form-control-sm" placeholder="Contoh: 081234567890" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Urutan</label>
                            <input type="number" name="urutan" class="form-control form-control-sm" value="1">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="aktif" id="add_aktif_pim" checked>
                        <label class="form-check-label small" for="add_aktif_pim">Aktif Menerima Laporan Terjadwal</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">Simpan Pimpinan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
