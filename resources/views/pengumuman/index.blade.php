@extends('layouts.app')

@section('title', 'Pengumuman & Siaran WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-bullhorn me-2"></i>Pengumuman &amp; Siaran WhatsApp
@endsection

@section('content')
@php
    $activeTab = request('tab', 'web');
@endphp

<div class="content-card">
    <!-- Top Nav Tabs: Pengumuman Web vs Siaran WhatsApp -->
    <ul class="nav nav-pills mb-4 pb-2 border-bottom" id="pengumumanTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold {{ $activeTab !== 'broadcast' ? 'active' : '' }}" id="tab-web" data-bs-toggle="pill" data-bs-target="#content-web" type="button" role="tab" aria-controls="content-web" aria-selected="{{ $activeTab !== 'broadcast' ? 'true' : 'false' }}">
                <i class="fas fa-desktop me-1.5"></i>1. Pengumuman Portal &amp; Marquee
                <span class="badge bg-light text-secondary border ms-1">{{ $pengumumanList->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold {{ $activeTab === 'broadcast' ? 'active' : '' }}" id="tab-broadcast" data-bs-toggle="pill" data-bs-target="#content-broadcast" type="button" role="tab" aria-controls="content-broadcast" aria-selected="{{ $activeTab === 'broadcast' ? 'true' : 'false' }}">
                <i class="fab fa-whatsapp me-1.5 text-success"></i>2. Siaran WhatsApp Operator (Broadcast WA)
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Baru</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pengumumanTabContent">
        <!-- ============================================================== -->
        <!-- TAB 1: PENGUMUMAN PORTAL & MARQUEE WEB -->
        <!-- ============================================================== -->
        <div class="tab-pane fade {{ $activeTab !== 'broadcast' ? 'show active' : '' }}" id="content-web" role="tabpanel" aria-labelledby="tab-web">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div>
                    <h5 class="mb-1 fw-bold text-dark"><i class="fas fa-bullhorn text-primary me-2"></i>Daftar Pengumuman Web &amp; Berita</h5>
                    <p class="text-muted small mb-0">Kelola pengumuman untuk marquee bar bawah, banner selamat datang, dan portal wilayah</p>
                </div>
                @if(auth()->user()->canManage('pengumuman'))
                <button class="btn btn-primary btn-sm px-3 mt-2 mt-md-0 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAdd">
                    <i class="fas fa-plus me-1"></i> Tambah Pengumuman Web
                </button>
                @else
                <span class="badge bg-secondary px-3 py-2 mt-2 mt-md-0">
                    <i class="fas fa-eye me-1"></i> Mode Lihat Saja
                </span>
                @endif
            </div>

            <!-- Filter Form -->
            <div class="card mb-3 bg-light border-0">
                <div class="card-body py-2">
                    <form method="GET" action="{{ route('pengumuman.index') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="web">
                        <div class="col-6 col-md-3">
                            <label for="filter_kategori" class="form-label small fw-semibold">Kategori</label>
                            <select id="filter_kategori" name="kategori" class="form-select form-select-sm" aria-label="Filter Kategori">
                                <option value="">-- Semua Kategori --</option>
                                <option value="Penting" {{ request('kategori') == 'Penting' ? 'selected' : '' }}>Penting</option>
                                <option value="Biasa" {{ request('kategori') == 'Biasa' ? 'selected' : '' }}>Biasa</option>
                                <option value="Agenda" {{ request('kategori') == 'Agenda' ? 'selected' : '' }}>Agenda</option>
                                <option value="Peringatan" {{ request('kategori') == 'Peringatan' ? 'selected' : '' }}>Peringatan</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="filter_status" class="form-label small fw-semibold">Status</label>
                            <select id="filter_status" name="status" class="form-select form-select-sm" aria-label="Filter Status">
                                <option value="">-- Semua Status --</option>
                                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill" aria-label="Terapkan Filter Pengumuman"><i class="fas fa-search me-1"></i>Filter</button>
                            <a href="{{ route('pengumuman.index', ['tab' => 'web']) }}" class="btn btn-secondary btn-sm flex-fill" aria-label="Reset Filter">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm mb-0" style="font-size: 13px;">
                    <thead class="bg-primary bg-opacity-10 text-dark">
                        <tr>
                            <th class="text-center" width="4%">No</th>
                            <th width="30%">Judul &amp; Isi</th>
                            <th class="text-center" width="10%">Kategori</th>
                            <th class="text-center" width="12%">Tipe / Sasaran</th>
                            <th class="text-center" width="10%">Marquee</th>
                            <th class="text-center" width="8%">Status</th>
                            <th class="text-center" width="12%">Periode</th>
                            <th class="text-center" width="14%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengumumanList as $p)
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $loop->iteration + ($pengumumanList->currentPage() - 1) * $pengumumanList->perPage() }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $p->judul }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 300px;">{{ strip_tags($p->isi) }}</div>
                                @if($p->gambar)
                                <small class="text-primary"><i class="fas fa-paperclip me-1"></i>Ada lampiran gambar</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $p->kategori == 'Penting' ? 'bg-danger' : ($p->kategori == 'Agenda' ? 'bg-info text-dark' : 'bg-primary') }}">
                                    {{ $p->kategori }}
                                </span>
                            </td>
                            <td class="text-center small">
                                {{ $p->tipe === 'UPT' ? ($p->upt?->nama_upt ?? 'UPT Spesifik') : 'Semua UPT' }}
                            </td>
                            <td class="text-center">
                                @if($p->tampilkan_marquee)
                                <span class="badge bg-success bg-opacity-25 text-success"><i class="fas fa-check"></i> Muncul</span>
                                @else
                                <span class="badge bg-secondary bg-opacity-25 text-secondary">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($p->aktif)
                                <span class="badge bg-success">Aktif</span>
                                @else
                                <span class="badge bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center small text-muted">
                                @if($p->tanggal_mulai && $p->tanggal_selesai)
                                {{ \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/y') }}
                                @else
                                Selalu Aktif
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <!-- Tombol Cepat Siarkan ke WhatsApp -->
                                    <button type="button" class="btn btn-outline-success btn-xs py-0.5 px-1.5 fw-semibold" onclick="siarkanPengumumanKeWa({{ json_encode($p) }})" title="Kirimkan Pengumuman ini ke WhatsApp Operator">
                                        <i class="fab fa-whatsapp me-1 text-success"></i>Siar WA
                                    </button>

                                    @if(auth()->user()->canManage('pengumuman'))
                                    <button type="button" class="btn btn-warning btn-xs py-0.5 px-1.5" onclick="editData({{ json_encode($p) }})" title="Edit Pengumuman">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('pengumuman.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs py-0.5 px-1.5" title="Hapus Pengumuman">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada pengumuman portal web.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $pengumumanList->links('pagination::bootstrap-5') }}
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: SIARAN WHATSAPP OPERATOR TERDAFTAR (BROADCAST WA) -->
        <!-- ============================================================== -->
        <div class="tab-pane fade {{ $activeTab === 'broadcast' ? 'show active' : '' }}" id="content-broadcast" role="tabpanel" aria-labelledby="tab-broadcast">
            <div class="row g-4">
                <!-- FORM SIARAN PESAN BEBAS & FILTER DINAMIS (KOLOM KIRI) -->
                <div class="col-lg-7">
                    <div class="card border border-secondary-subtle shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="fab fa-whatsapp text-success me-2 fa-lg"></i>Buat Siaran WhatsApp ke Operator
                                </h6>
                                <small class="text-muted">Kirimkan instruksi atau pengumuman massal langsung ke nomor WhatsApp pribadi operator</small>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                <i class="fas fa-satellite-dish me-1"></i>Fonnte Gateway
                            </span>
                        </div>

                        <div class="card-body p-3">
                            <form action="{{ route('pengumuman.broadcast-wa') }}" method="POST" id="formBroadcastWa" onsubmit="return confirmKirimBroadcast()">
                                @csrf
                                <input type="hidden" name="pengumuman_id" id="bc_pengumuman_id" value="">

                                <div class="row g-2 mb-3">
                                    <div class="col-md-8">
                                        <label for="bc_judul" class="form-label small fw-semibold">Judul / Topik Pengumuman: <span class="text-danger">*</span></label>
                                        <input type="text" name="judul" id="bc_judul" class="form-control form-control-sm" required placeholder="Contoh: Pengingat Batas Akhir Input Laporan Harian..." oninput="updateLiveMockup()">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="bc_kategori" class="form-label small fw-semibold">Kategori: <span class="text-danger">*</span></label>
                                        <select name="kategori" id="bc_kategori" class="form-select form-select-sm" required onchange="updateLiveMockup()">
                                            <option value="PENTING">🚨 PENTING</option>
                                            <option value="PENGINGAT" selected>⏰ PENGINGAT</option>
                                            <option value="EDARAN">📋 EDARAN</option>
                                            <option value="AGENDA">📅 AGENDA</option>
                                            <option value="INFORMASI">ℹ️ INFORMASI UMUM</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- SELEKTOR TARGET DINAMIS (ROLE / UPT / SEMUA) -->
                                <div class="p-3 rounded bg-light border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2.5">
                                        <label class="form-label small fw-bold text-dark mb-0 fs-6">
                                            <i class="fas fa-users-cog text-primary me-1.5"></i>Sasaran Penerima Siaran WhatsApp:
                                        </label>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2.5 fw-semibold shadow-xs" onclick="bukaModalListPenerima()">
                                            <i class="fas fa-user-check me-1"></i>Lihat Daftar Penerima (<span id="count_badge_num">{{ $roleCounts['total'] ?? 0 }}</span>)
                                        </button>
                                    </div>

                                    <!-- Mode Target: Radio Card -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <div class="form-check p-2.5 rounded border bg-white h-100 shadow-xs">
                                                <input class="form-check-input ms-1" type="radio" name="target_type" id="tt_role" value="role" checked onchange="handleTargetTypeChange()">
                                                <label class="form-check-label ps-2 small fw-bold text-dark d-block cursor-pointer" for="tt_role">
                                                    <span class="d-block text-primary"><i class="fas fa-id-badge me-1"></i>Pilih Berdasarkan Role</span>
                                                    <span class="text-muted fw-normal" style="font-size: 11px;">Kirim ke role spesifik (Operator UPT, Operator Wilayah, Admin, dll)</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check p-2.5 rounded border bg-white h-100 shadow-xs">
                                                <input class="form-check-input ms-1" type="radio" name="target_type" id="tt_all" value="all" onchange="handleTargetTypeChange()">
                                                <label class="form-check-label ps-2 small fw-bold text-dark d-block cursor-pointer" for="tt_all">
                                                    <span class="d-block text-success"><i class="fas fa-globe me-1"></i>Semua Pengguna Terdaftar</span>
                                                    <span class="text-muted fw-normal" style="font-size: 11px;">Kirimkan ke seluruh operator, admin, dan viewer di seluruh Satker</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pilihan Role (Muncul saat Mode Role) -->
                                    <div id="containerRoles" class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-1">
                                            <span class="small fw-bold text-dark" style="font-size: 12px;">Pilih Role Sasaran:</span>
                                            <div class="btn-group btn-group-xs">
                                                <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none me-2" onclick="setRoleFilter('operators')" title="Pilih hanya Operator UPT & Operator Wilayah">
                                                    <i class="fas fa-check-double me-1"></i>Hanya Operator
                                                </button>
                                                <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none me-2" onclick="setRoleFilter('all')">
                                                    Pilih Semua
                                                </button>
                                                <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none text-danger" onclick="setRoleFilter('none')">
                                                    Kosongkan
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row g-2">
                                            <!-- Operator UPT (Satker) -->
                                            <div class="col-sm-6">
                                                <label class="form-check d-flex align-items-center justify-content-between p-2 rounded border bg-white mb-0 cursor-pointer role-card" for="role_operator" id="card_role_operator">
                                                    <div class="d-flex align-items-center">
                                                        <input class="form-check-input role-check mt-0 me-2" type="checkbox" name="target_roles[]" id="role_operator" value="operator" checked onchange="fetchRecipientsCount()">
                                                        <div>
                                                            <div class="fw-bold text-dark" style="font-size: 12px;">Operator UPT</div>
                                                            <small class="text-muted d-block" style="font-size: 10.5px;">Satker Lapas, Rutan, Bapas, Rupbasan</small>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-primary rounded-pill px-2 py-1" id="badge_count_operator">{{ $roleCounts['operator'] ?? 0 }} Kontak</span>
                                                </label>
                                            </div>

                                            <!-- Operator Wilayah (Kanwil) -->
                                            <div class="col-sm-6">
                                                <label class="form-check d-flex align-items-center justify-content-between p-2 rounded border bg-white mb-0 cursor-pointer role-card" for="role_wilayah" id="card_role_wilayah">
                                                    <div class="d-flex align-items-center">
                                                        <input class="form-check-input role-check mt-0 me-2" type="checkbox" name="target_roles[]" id="role_wilayah" value="operator_wilayah" checked onchange="fetchRecipientsCount()">
                                                        <div>
                                                            <div class="fw-bold text-dark" style="font-size: 12px;">Operator Wilayah</div>
                                                            <small class="text-muted d-block" style="font-size: 10.5px;">Operator Kantor Wilayah Banten</small>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-info text-dark rounded-pill px-2 py-1" id="badge_count_operator_wilayah">{{ $roleCounts['operator_wilayah'] ?? 0 }} Kontak</span>
                                                </label>
                                            </div>

                                            <!-- Administrator -->
                                            <div class="col-sm-6">
                                                <label class="form-check d-flex align-items-center justify-content-between p-2 rounded border bg-white mb-0 cursor-pointer role-card" for="role_admin" id="card_role_admin">
                                                    <div class="d-flex align-items-center">
                                                        <input class="form-check-input role-check mt-0 me-2" type="checkbox" name="target_roles[]" id="role_admin" value="admin" onchange="fetchRecipientsCount()">
                                                        <div>
                                                            <div class="fw-bold text-dark" style="font-size: 12px;">Administrator</div>
                                                            <small class="text-muted d-block" style="font-size: 10.5px;">Admin Wilayah / Superuser</small>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-danger rounded-pill px-2 py-1" id="badge_count_admin">{{ $roleCounts['admin'] ?? 0 }} Kontak</span>
                                                </label>
                                            </div>

                                            <!-- Viewer -->
                                            <div class="col-sm-6">
                                                <label class="form-check d-flex align-items-center justify-content-between p-2 rounded border bg-white mb-0 cursor-pointer role-card" for="role_viewer" id="card_role_viewer">
                                                    <div class="d-flex align-items-center">
                                                        <input class="form-check-input role-check mt-0 me-2" type="checkbox" name="target_roles[]" id="role_viewer" value="viewer" onchange="fetchRecipientsCount()">
                                                        <div>
                                                            <div class="fw-bold text-dark" style="font-size: 12px;">Viewer / Pemantau</div>
                                                            <small class="text-muted d-block" style="font-size: 10.5px;">Pimpinan & Hak Akses Monitoring</small>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-secondary rounded-pill px-2 py-1" id="badge_count_viewer">{{ $roleCounts['viewer'] ?? 0 }} Kontak</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Opsi Pembatasan UPT (Opsional) -->
                                    <div class="p-2.5 rounded bg-white border mb-3">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" name="filter_upt" id="filter_upt_toggle" value="1" onchange="handleUptFilterToggle()">
                                            <label class="form-check-label small fw-bold text-dark" for="filter_upt_toggle">
                                                <i class="fas fa-building text-primary me-1"></i>Batasi Hanya Satker (UPT) Tertentu
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mb-2" style="font-size: 10.5px;" id="desc_upt_filter">
                                            Saat ini mencakup <b>Semua UPT Pemasyarakatan Banten + Kantor Wilayah</b>. Aktifkan switch jika ingin mengirim ke UPT tertentu saja.
                                        </small>

                                        <div id="containerUptList" style="display: none;">
                                            <label for="bc_target_upts" class="form-label small fw-semibold mb-1" style="font-size: 11px;">Pilih UPT Sasaran:</label>
                                            <select name="target_upt_ids[]" id="bc_target_upts" class="form-select form-select-sm" multiple style="height: 110px; font-size: 12px;" onchange="fetchRecipientsCount()">
                                                @foreach($uptList as $upt)
                                                <option value="{{ $upt->id }}" selected>{{ $upt->nama_upt }}</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex justify-content-between mt-1">
                                                <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" onclick="selectAllUpts(true)">Pilih Semua UPT</button>
                                                <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none text-danger" onclick="selectAllUpts(false)">Hapus Semua Pilihan</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tembusan Grup WA -->
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="kirim_ke_grup" id="bc_kirim_grup" value="1" checked>
                                        <label class="form-check-label small fw-semibold" for="bc_kirim_grup">
                                            <i class="fab fa-whatsapp text-success me-1"></i>Kirimkan juga tembusan ke Grup WhatsApp Resmi
                                        </label>
                                    </div>

                                    <!-- Live Target Summary Display (Menjawab: "tidak terlihat role apa yang kita tuju") -->
                                    <div class="card border border-primary-subtle bg-white shadow-xs p-2.5 mb-0">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <div class="small fw-bold text-dark mb-1">
                                                    <i class="fas fa-bullseye text-danger me-1"></i>Role Sasaran yang Dituju:
                                                </div>
                                                <div id="target_roles_display" class="d-flex flex-wrap gap-1 align-items-center">
                                                    <span class="badge bg-primary px-2 py-1"><i class="fas fa-user-shield me-1"></i>Operator UPT</span>
                                                    <span class="badge bg-info text-dark px-2 py-1"><i class="fas fa-building me-1"></i>Operator Wilayah</span>
                                                </div>
                                                <div class="small text-muted mt-1" id="target_upt_display" style="font-size: 11px;">
                                                    <i class="fas fa-map-marker-alt text-secondary me-1"></i>Cakupan: <b>Semua Satker UPT Banten &amp; Kanwil</b>
                                                </div>
                                            </div>
                                            <div class="text-md-end">
                                                <span class="badge bg-success py-1.5 px-2.5 fs-6 shadow-sm" id="badge_valid_wa">
                                                    <i class="fab fa-whatsapp me-1"></i><span id="badge_valid_num">0</span> Nomor Siap Kirim
                                                </span>
                                                <div class="small text-muted mt-1" id="label_total_target" style="font-size: 11px;">
                                                    Menghitung sasaran...
                                                </div>
                                                <div class="badge bg-warning text-dark d-none mt-1" id="badge_missing_wa" style="font-size: 10px;">
                                                    0 Tanpa Nomor WA
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- EDITOR PESAN WHATSAPP BEBAS (FREE-TEXT EDITOR) -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                                        <label for="bc_pesan" class="form-label small fw-semibold mb-0">
                                            Isi Pesan Siaran WhatsApp: <span class="text-danger">*</span>
                                        </label>
                                        <!-- Template Cepat Dropdown -->
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-xs dropdown-toggle py-0.5 px-2 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 11px;">
                                                <i class="fas fa-magic me-1 text-warning"></i>Muat Template Pesan
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: 12px;">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadTemplateBc('laporan_harian')">⏰ Pengingat Laporan Harian (Bebas/Overstaying)</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadTemplateBc('mutasi_narapidana')">🚚 Instruksi Pelaksanaan Mutasi Narapidana</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadTemplateBc('sidang_tpp')">⚖️ Pengingat Berita Acara (BA) Sidang TPP</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadTemplateBc('maintenance')">🛠️ Pemberitahuan Pemeliharaan Sistem SULTAN</a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Toolbar Format WhatsApp & Variabel Dinamis -->
                                    <div class="d-flex flex-wrap gap-1 mb-1.5 p-1 bg-light rounded border">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertBcFormat('*', '*')" title="Format Tebal (*teks*)">
                                            <i class="fas fa-bold"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertBcFormat('_', '_')" title="Format Miring (_teks_)">
                                            <i class="fas fa-italic"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertBcFormat('~', '~')" title="Format Coret (~teks~)">
                                            <i class="fas fa-strikethrough"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertBcFormat('```', '```')" title="Format Monospace">
                                            <i class="fas fa-code"></i>
                                        </button>
                                        <div class="vr mx-1"></div>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-1.5" onclick="insertBcVariable('{nama}')" title="Nama Operator Penerima">
                                            + {nama}
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-1.5" onclick="insertBcVariable('{role}')" title="Role Akun">
                                            + {role}
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-1.5" onclick="insertBcVariable('{satker}')" title="Nama Satker UPT">
                                            + {satker}
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-1.5" onclick="insertBcVariable('{tanggal}')" title="Tanggal Hari Ini">
                                            + {tanggal}
                                        </button>
                                    </div>

                                    <textarea name="pesan" id="bc_pesan" class="form-control font-monospace" rows="9" style="font-size: 12px; line-height: 1.45;" placeholder="Tuliskan isi pengumuman atau instruksi bebas di sini..." required oninput="updateLiveMockup()"></textarea>
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        Gunakan tanda bintang <code>*teks*</code> untuk huruf tebal dan garis bawah <code>_teks_</code> untuk huruf miring.
                                    </small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted" style="font-size: 11px;">
                                        <i class="fas fa-shield-alt text-success me-1"></i>Dilengkapi jeda otomatis antar pesan (anti-spam)
                                    </span>
                                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm" id="btnSubmitBroadcast">
                                        <i class="fas fa-paper-plane me-1.5"></i>Kirim Siaran ke WhatsApp
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- LIVE PHONE SIMULATOR (KOLOM KANAN) -->
                <div class="col-lg-5">
                    <div class="card border border-secondary-subtle shadow-sm sticky-top" style="top: 80px;">
                        <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold" style="font-size: 12px;">
                                <i class="fas fa-mobile-alt text-warning me-1.5"></i>Simulasi Tampilan WhatsApp
                            </span>
                            <span class="badge bg-success px-2 py-0.5" style="font-size: 10px;">Live Preview</span>
                        </div>
                        <div class="card-body p-3" style="background-color: #e5ddd5; min-height: 480px; background-image: radial-gradient(#d4cbbe 1px, transparent 1px); background-size: 16px 16px;">
                            <!-- Balon Chat WhatsApp -->
                            <div class="rounded-3 p-3 shadow-sm bg-white border" style="max-width: 95%; margin: 0 auto; position: relative;">
                                <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                                    <div class="rounded-circle bg-success text-white p-1 text-center" style="width: 28px; height: 28px; font-size: 11px;">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 11.5px;">SULTAN BANTEN NOTIFIKASI</div>
                                        <div class="text-muted" style="font-size: 9.5px;">Kantor Wilayah Ditjenpas Banten</div>
                                    </div>
                                </div>

                                <!-- Konten Pesan Yang Dirender -->
                                <div id="liveChatBubble" style="font-size: 11.5px; line-height: 1.45; white-space: pre-wrap; word-break: break-word; color: #111827;">
                                    Menunggu isi pesan diketik...
                                </div>

                                <div class="text-end mt-2 pt-1 border-top" style="font-size: 9.5px; color: #6b7280;">
                                    <span>{{ now()->format('H:i') }}</span>
                                    <i class="fas fa-check-double text-primary ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PUSAT AKSES CEPAT PENGINGAT BERKAS PENDING -->
                    <div class="card border border-success-subtle shadow-sm mt-3">
                        <div class="card-header bg-success bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-success" style="font-size: 12px;">
                                <i class="fab fa-whatsapp me-1.5 fa-lg"></i>Pusat Pengingat Berkas Pending
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Tinggal Klik</span>
                        </div>
                        <div class="card-body p-2.5">
                            <p class="small text-muted mb-2" style="font-size: 11px;">
                                Mengingatkan satker yang belum unggah berkas? Klik menu di bawah untuk langsung kirim pengingat:
                            </p>
                            <div class="d-grid gap-1.5">
                                <a href="{{ route('mutasi.pelaksanaan') }}" class="btn btn-outline-success btn-sm text-start py-1.5 px-2.5 d-flex justify-content-between align-items-center shadow-2xs" style="font-size: 11.5px;">
                                    <div>
                                        <div class="fw-bold"><i class="fas fa-truck-moving me-1.5 text-success"></i>1. Pelaksanaan Mutasi &amp; BAST</div>
                                        <small class="text-muted" style="font-size: 10.5px;">Ingatkan Lap. Pemindahan, Penerimaan &amp; BAST</small>
                                    </div>
                                    <i class="fas fa-arrow-right text-success small"></i>
                                </a>
                                <a href="{{ route('sidang-tpp.dokumen-kanwil.index') }}" class="btn btn-outline-primary btn-sm text-start py-1.5 px-2.5 d-flex justify-content-between align-items-center shadow-2xs" style="font-size: 11.5px;">
                                    <div>
                                        <div class="fw-bold"><i class="fas fa-gavel me-1.5 text-primary"></i>2. Naskah Dokumen Sidang TPP</div>
                                        <small class="text-muted" style="font-size: 10.5px;">Ingatkan Berita Acara (BA) Sidang UPT</small>
                                    </div>
                                    <i class="fas fa-arrow-right text-primary small"></i>
                                </a>
                                <a href="{{ route('sidang-tpp.index', ['status_ba' => 'belum_upload', 'periode' => 'semua']) }}" class="btn btn-outline-warning text-dark btn-sm text-start py-1.5 px-2.5 d-flex justify-content-between align-items-center shadow-2xs" style="font-size: 11.5px;">
                                    <div>
                                        <div class="fw-bold"><i class="fas fa-calendar-alt me-1.5 text-warning"></i>3. Jadwal Sidang (Belum Upload BA)</div>
                                        <small class="text-muted" style="font-size: 10.5px;">Tabel agenda sidang yang belum ada BA</small>
                                    </div>
                                    <i class="fas fa-arrow-right text-dark small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL RIWAYAT SIARAN WHATSAPP TERTENTU -->
            <div class="card border border-secondary-subtle shadow-sm mt-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i>Riwayat Siaran WhatsApp Operator</h6>
                        <small class="text-muted">Daftar pengumuman yang telah disiarkan secara massal melalui WhatsApp</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="text-center" width="4%">No</th>
                                    <th width="16%">Waktu Kirim</th>
                                    <th width="32%">Judul &amp; Pesan</th>
                                    <th class="text-center" width="12%">Kategori</th>
                                    <th class="text-center" width="16%">Sasaran &amp; Status</th>
                                    <th class="text-center" width="10%">Pengirim</th>
                                    <th class="text-center" width="10%">Rincian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($broadcastList as $idx => $bc)
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $broadcastList->firstItem() + $idx }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $bc->sent_at ? $bc->sent_at->translatedFormat('d M Y') : $bc->created_at->translatedFormat('d M Y') }}</div>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i>{{ $bc->sent_at ? $bc->sent_at->format('H:i') : $bc->created_at->format('H:i') }} WIB</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 320px;" title="{{ $bc->judul }}">{{ $bc->judul }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 320px;">{{ Str::limit($bc->pesan, 75) }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">{{ $bc->kategori }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="mb-1">
                                            <span class="badge bg-success text-white px-1.5 py-0.5"><i class="fas fa-check me-1"></i>{{ $bc->total_sent }} Terkirim</span>
                                            @if($bc->total_failed > 0)
                                            <span class="badge bg-danger text-white px-1.5 py-0.5"><i class="fas fa-times me-1"></i>{{ $bc->total_failed }} Gagal</span>
                                            @endif
                                        </div>
                                        <small class="text-muted" style="font-size: 10px;">Total: {{ $bc->total_target }} Target</small>
                                    </td>
                                    <td class="text-center small text-muted">
                                        {{ $bc->creator?->nama_lengkap ?: ($bc->creator?->username ?? 'Sistem') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 fw-semibold" onclick="lihatLogBroadcast({{ $bc->id }})" title="Lihat rincian status pengiriman per operator">
                                            <i class="fas fa-list-alt me-1"></i>Log Detail
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat siaran WhatsApp.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2 border-top">
                        {{ $broadcastList->appends(['tab' => 'broadcast'])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL DETAIL LOG BROADCAST (DAFTAR PER OPERATOR) -->
<!-- ============================================================== -->
<div class="modal fade" id="modalBroadcastLogs" tabindex="-1" aria-labelledby="modalBroadcastLogsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="modalBroadcastLogsTitle">
                    <i class="fas fa-clipboard-list text-warning me-2"></i>Detail Log Pengiriman WhatsApp
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="logDetailHeader" class="p-2 rounded bg-light border mb-3">
                    <div class="fw-bold text-dark fs-6" id="logJudul">-</div>
                    <div class="small text-muted" id="logMeta">-</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="28%">Nama Operator</th>
                                <th width="24%">Satuan Kerja (UPT)</th>
                                <th width="20%">No. WhatsApp</th>
                                <th width="13%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="logDetailTbody">
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Memuat data log...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-1.5 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL PREVIEW CALON PENERIMA SEBELUM KIRIM -->
<!-- ============================================================== -->
<div class="modal fade" id="modalListRecipients" tabindex="-1" aria-labelledby="modalListRecipientsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-users-cog fa-lg text-white"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalListRecipientsTitle">
                            Daftar Sasaran Penerima Siaran WhatsApp
                        </h6>
                        <small class="text-white-50" style="font-size: 11px;">Rincian nama, username, role, satker, dan nomor WhatsApp yang akan menerima siaran</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Search & Role Filter Tabs inside Modal -->
                <div class="row g-2 mb-2.5 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchRecipInput" class="form-control form-control-sm border-start-0" placeholder="Cari nama, satker, atau nomor..." oninput="renderRecipientsList()">
                        </div>
                    </div>
                    <div class="col-md-7 text-md-end">
                        <div class="btn-group btn-group-xs flex-wrap" role="group">
                            <button type="button" class="btn btn-primary btn-xs btn-filter-recip px-2 py-1 active" data-role="all" onclick="filterRecipientsModal('all')">Semua</button>
                            <button type="button" class="btn btn-outline-primary btn-xs btn-filter-recip px-2 py-1" data-role="operator" onclick="filterRecipientsModal('operator')">Operator UPT</button>
                            <button type="button" class="btn btn-outline-primary btn-xs btn-filter-recip px-2 py-1" data-role="operator_wilayah" onclick="filterRecipientsModal('operator_wilayah')">Op. Wilayah</button>
                            <button type="button" class="btn btn-outline-primary btn-xs btn-filter-recip px-2 py-1" data-role="admin" onclick="filterRecipientsModal('admin')">Admin</button>
                            <button type="button" class="btn btn-outline-primary btn-xs btn-filter-recip px-2 py-1" data-role="viewer" onclick="filterRecipientsModal('viewer')">Viewer</button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 px-2.5 py-1.5 bg-light rounded border">
                    <span class="small fw-semibold text-dark">Total Terpilih: <b id="modalRecipTotal" class="text-primary fs-6">0</b> Pengguna</span>
                    <span class="small text-success fw-bold" id="modalRecipValid">0 Nomor Siap Kirim</span>
                </div>

                <div class="list-group list-group-flush border rounded" id="modalRecipList" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Memuat daftar...</div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Web Pengumuman -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus me-2"></i>Tambah Pengumuman Web Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
            </div>
            <form method="POST" action="{{ route('pengumuman.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_judul" class="form-label small fw-semibold">Judul Pengumuman <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="add_judul" class="form-control form-control-sm" required placeholder="Judul ringkas" aria-label="Judul Pengumuman">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="add_kategori" class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="add_kategori" class="form-select form-select-sm" required aria-label="Kategori Pengumuman">
                                <option value="Penting">Penting</option>
                                <option value="Biasa" selected>Biasa</option>
                                <option value="Agenda">Agenda</option>
                                <option value="Peringatan">Peringatan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_tipe" class="form-label small fw-semibold">Tipe Sasaran</label>
                            <select name="tipe" class="form-select form-select-sm" id="add_tipe" onchange="toggleUptSelect('add')" aria-label="Tipe Sasaran">
                                <option value="Global">Global (Semua UPT &amp; Publik)</option>
                                <option value="UPT">UPT Spesifik</option>
                            </select>
                        </div>
                        <div class="col-12" id="add_upt_container" style="display: none;">
                            <label for="add_upt_id" class="form-label small fw-semibold">Pilih UPT Target</label>
                            <select name="upt_id" id="add_upt_id" class="form-select form-select-sm" aria-label="Pilih UPT Target">
                                <option value="">-- Pilih UPT --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_isi" class="form-label small fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
                        <textarea name="isi" id="add_isi" class="form-control form-control-sm" rows="4" required placeholder="Tuliskan isi pengumuman atau informasi lengkap" aria-label="Isi Pengumuman"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="add_tanggal_mulai" class="form-label small fw-semibold">Tanggal Mulai (Opsional)</label>
                            <input type="date" name="tanggal_mulai" id="add_tanggal_mulai" class="form-control form-control-sm" aria-label="Tanggal Mulai">
                        </div>
                        <div class="col-md-6">
                            <label for="add_tanggal_selesai" class="form-label small fw-semibold">Tanggal Selesai (Opsional)</label>
                            <input type="date" name="tanggal_selesai" id="add_tanggal_selesai" class="form-control form-control-sm" aria-label="Tanggal Selesai">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_gambar" class="form-label small fw-semibold">Lampiran Gambar (Opsional)</label>
                        <input type="file" name="gambar" id="add_gambar" class="form-control form-control-sm" accept="image/*" aria-label="Lampiran Gambar">
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="aktif" id="add_aktif" checked>
                            <label class="form-check-label small fw-semibold" for="add_aktif">Status Aktif</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tampilkan_marquee" id="add_marquee" checked>
                            <label class="form-check-label small fw-semibold" for="add_marquee">Tampilkan di Running Text Bawah</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Web Pengumuman -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning text-dark">
                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
            </div>
            <form method="POST" id="formEdit" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_judul" class="form-label small fw-semibold">Judul Pengumuman <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="edit_judul" class="form-control form-control-sm" required aria-label="Judul Pengumuman">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="edit_kategori" class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="edit_kategori" class="form-select form-select-sm" required aria-label="Kategori Pengumuman">
                                <option value="Penting">Penting</option>
                                <option value="Biasa">Biasa</option>
                                <option value="Agenda">Agenda</option>
                                <option value="Peringatan">Peringatan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_tipe" class="form-label small fw-semibold">Tipe Sasaran</label>
                            <select name="tipe" id="edit_tipe" class="form-select form-select-sm" onchange="toggleUptSelect('edit')" aria-label="Tipe Sasaran">
                                <option value="Global">Global (Semua UPT &amp; Publik)</option>
                                <option value="UPT">UPT Spesifik</option>
                            </select>
                        </div>
                        <div class="col-12" id="edit_upt_container" style="display: none;">
                            <label for="edit_upt_id" class="form-label small fw-semibold">Pilih UPT Target</label>
                            <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm" aria-label="Pilih UPT Target">
                                <option value="">-- Pilih UPT --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_isi" class="form-label small fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
                        <textarea name="isi" id="edit_isi" class="form-control form-control-sm" rows="4" required aria-label="Isi Pengumuman"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="edit_tanggal_mulai" class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="form-control form-control-sm" aria-label="Tanggal Mulai">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_tanggal_selesai" class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="form-control form-control-sm" aria-label="Tanggal Selesai">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_gambar" class="form-label small fw-semibold">Ganti Gambar (Opsional)</label>
                        <div id="edit_gambar_preview" class="mb-2"></div>
                        <input type="file" name="gambar" id="edit_gambar" class="form-control form-control-sm" accept="image/*" aria-label="Ganti Gambar">
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="aktif" id="edit_aktif">
                            <label class="form-check-label small fw-semibold" for="edit_aktif">Status Aktif</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tampilkan_marquee" id="edit_marquee">
                            <label class="form-check-label small fw-semibold" for="edit_marquee">Tampilkan di Running Text Bawah</label>
                        </div>
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
@endsection

@push('scripts')
<script>
    let cachedRecipientsData = [];

    function toggleUptSelect(prefix) {
        const tipe = document.getElementById(prefix + '_tipe').value;
        const container = document.getElementById(prefix + '_upt_container');
        if (container) container.style.display = tipe === 'UPT' ? 'block' : 'none';
    }

    function editData(p) {
        document.getElementById('formEdit').action = '/pengumuman/' + p.id;
        document.getElementById('edit_judul').value = p.judul;
        document.getElementById('edit_kategori').value = p.kategori;
        document.getElementById('edit_tipe').value = p.tipe;
        document.getElementById('edit_isi').value = p.isi;
        document.getElementById('edit_tanggal_mulai').value = p.tanggal_mulai ? p.tanggal_mulai.substring(0, 10) : '';
        document.getElementById('edit_tanggal_selesai').value = p.tanggal_selesai ? p.tanggal_selesai.substring(0, 10) : '';
        document.getElementById('edit_aktif').checked = !!p.aktif;
        document.getElementById('edit_marquee').checked = !!p.tampilkan_marquee;

        if (p.upt_id) {
            document.getElementById('edit_upt_id').value = p.upt_id;
        }
        toggleUptSelect('edit');

        const preview = document.getElementById('edit_gambar_preview');
        if (p.gambar) {
            preview.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="/' + p.gambar + '" style="max-height: 45px;" class="img-thumbnail"><div class="form-check"><input class="form-check-input" type="checkbox" name="delete_gambar" value="1" id="del_gbr"><label class="form-check-label small text-danger" for="del_gbr">Hapus gambar ini</label></div></div>';
        } else {
            preview.innerHTML = '';
        }

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    // ==========================================
    // LOGIKA BROADCAST WHATSAPP
    // ==========================================
    let currentRecipientsFilter = 'all';

    function handleTargetTypeChange() {
        const type = document.querySelector('input[name="target_type"]:checked')?.value || 'role';
        const roleContainer = document.getElementById('containerRoles');

        if (roleContainer) {
            roleContainer.style.display = (type === 'role') ? 'block' : 'none';
        }

        fetchRecipientsCount();
    }

    function handleUptFilterToggle() {
        const toggle = document.getElementById('filter_upt_toggle');
        const container = document.getElementById('containerUptList');
        const desc = document.getElementById('desc_upt_filter');

        if (toggle && container) {
            container.style.display = toggle.checked ? 'block' : 'none';
            if (desc) {
                desc.innerHTML = toggle.checked 
                    ? 'Pilih satu atau beberapa UPT sasaran dari daftar di bawah:' 
                    : 'Saat ini mencakup <b>Semua UPT Pemasyarakatan Banten + Kantor Wilayah</b>.';
            }
        }
        fetchRecipientsCount();
    }

    function setRoleFilter(mode) {
        const checks = document.querySelectorAll('.role-check');
        checks.forEach(c => {
            if (mode === 'all') {
                c.checked = true;
            } else if (mode === 'none') {
                c.checked = false;
            } else if (mode === 'operators') {
                c.checked = (c.value === 'operator' || c.value === 'operator_wilayah');
            }
        });
        fetchRecipientsCount();
    }

    function selectAllUpts(isSelect) {
        const select = document.getElementById('bc_target_upts');
        if (!select) return;
        for (let i = 0; i < select.options.length; i++) {
            select.options[i].selected = isSelect;
        }
        fetchRecipientsCount();
    }

    function fetchRecipientsCount() {
        const type = document.querySelector('input[name="target_type"]:checked')?.value || 'role';
        const roles = [];
        document.querySelectorAll('.role-check:checked').forEach(c => roles.push(c.value));

        const isFilterUpt = document.getElementById('filter_upt_toggle')?.checked || false;
        const uptSelect = document.getElementById('bc_target_upts');
        const uptIds = [];
        if (isFilterUpt && uptSelect) {
            for (let i = 0; i < uptSelect.options.length; i++) {
                if (uptSelect.options[i].selected) uptIds.push(uptSelect.options[i].value);
            }
        }

        // Tampilkan indikator visual peran yang dituju secara eksplisit
        updateTargetRolesDisplay(type, roles, isFilterUpt, uptIds.length);

        const labelTarget = document.getElementById('label_total_target');
        const badgeValidNum = document.getElementById('badge_valid_num');
        const badgeMissing = document.getElementById('badge_missing_wa');
        const countBadgeNum = document.getElementById('count_badge_num');

        if (labelTarget) labelTarget.innerText = 'Menghitung sasaran...';

        fetch('{{ route("pengumuman.recipients-count") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                target_type: type,
                target_roles: roles,
                target_upt_ids: uptIds,
                filter_upt: isFilterUpt ? 1 : 0
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cachedRecipientsData = data.users || [];
                if (labelTarget) labelTarget.innerText = `Total ${data.total} Pengguna Terpilih`;
                if (countBadgeNum) countBadgeNum.innerText = data.total;
                if (badgeValidNum) badgeValidNum.innerText = data.valid_wa;

                if (badgeMissing) {
                    if (data.missing_wa > 0) {
                        badgeMissing.classList.remove('d-none');
                        badgeMissing.innerText = `${data.missing_wa} Tanpa No. WA`;
                    } else {
                        badgeMissing.classList.add('d-none');
                    }
                }

                // Update badge jumlah kontak di masing-masing kartu role
                if (data.role_counts) {
                    const opBadge = document.getElementById('badge_count_operator');
                    const wilBadge = document.getElementById('badge_count_operator_wilayah');
                    const admBadge = document.getElementById('badge_count_admin');
                    const viewBadge = document.getElementById('badge_count_viewer');

                    if (opBadge) opBadge.innerText = `${data.role_counts.operator || 0} Kontak`;
                    if (wilBadge) wilBadge.innerText = `${data.role_counts.operator_wilayah || 0} Kontak`;
                    if (admBadge) admBadge.innerText = `${data.role_counts.admin || 0} Kontak`;
                    if (viewBadge) viewBadge.innerText = `${data.role_counts.viewer || 0} Kontak`;
                }
            }
        })
        .catch(err => console.error(err));
    }

    function updateTargetRolesDisplay(type, roles, isFilterUpt, selectedUptCount) {
        const displayEl = document.getElementById('target_roles_display');
        const uptDisplayEl = document.getElementById('target_upt_display');
        if (!displayEl) return;

        if (type === 'all') {
            displayEl.innerHTML = '<span class="badge bg-success px-2.5 py-1 fs-7"><i class="fas fa-users me-1"></i>Semua Role (Operator UPT, Operator Wilayah, Admin, Viewer)</span>';
        } else {
            if (roles.length === 0) {
                displayEl.innerHTML = '<span class="badge bg-danger px-2.5 py-1 fs-7"><i class="fas fa-exclamation-triangle me-1"></i>Belum Ada Role Dipilih!</span>';
            } else {
                let badges = '';
                if (roles.includes('operator')) {
                    badges += '<span class="badge bg-primary px-2.5 py-1 me-1 fs-7"><i class="fas fa-user-shield me-1"></i>Operator UPT</span>';
                }
                if (roles.includes('operator_wilayah')) {
                    badges += '<span class="badge bg-info text-dark px-2.5 py-1 me-1 fs-7"><i class="fas fa-building me-1"></i>Operator Wilayah</span>';
                }
                if (roles.includes('admin')) {
                    badges += '<span class="badge bg-danger px-2.5 py-1 me-1 fs-7"><i class="fas fa-crown me-1"></i>Administrator</span>';
                }
                if (roles.includes('viewer')) {
                    badges += '<span class="badge bg-secondary px-2.5 py-1 me-1 fs-7"><i class="fas fa-eye me-1"></i>Viewer</span>';
                }
                displayEl.innerHTML = badges;
            }
        }

        if (uptDisplayEl) {
            if (isFilterUpt) {
                uptDisplayEl.innerHTML = `<i class="fas fa-filter text-primary me-1"></i>Cakupan: <b>Khusus ${selectedUptCount} Satker Terpilih</b>`;
            } else {
                uptDisplayEl.innerHTML = '<i class="fas fa-map-marker-alt text-secondary me-1"></i>Cakupan: <b>Semua Satker UPT Banten &amp; Kanwil</b>';
            }
        }
    }

    function filterRecipientsModal(roleKey) {
        currentRecipientsFilter = roleKey;
        document.querySelectorAll('.btn-filter-recip').forEach(btn => {
            const isMatch = btn.dataset.role === roleKey;
            btn.classList.toggle('active', isMatch);
            btn.classList.toggle('btn-primary', isMatch);
            btn.classList.toggle('btn-outline-primary', !isMatch);
        });
        renderRecipientsList();
    }

    function renderRecipientsList() {
        const listContainer = document.getElementById('modalRecipList');
        const searchInput = document.getElementById('searchRecipInput')?.value.toLowerCase().trim() || '';
        if (!listContainer) return;

        let filtered = cachedRecipientsData;

        if (currentRecipientsFilter !== 'all') {
            filtered = filtered.filter(u => u.role === currentRecipientsFilter);
        }

        if (searchInput) {
            filtered = filtered.filter(u => 
                (u.nama && u.nama.toLowerCase().includes(searchInput)) ||
                (u.username && u.username.toLowerCase().includes(searchInput)) ||
                (u.upt && u.upt.toLowerCase().includes(searchInput)) ||
                (u.no_wa && u.no_wa.toLowerCase().includes(searchInput))
            );
        }

        if (filtered.length === 0) {
            listContainer.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-search me-1"></i>Tidak ditemukan kontak yang sesuai kriteria pencarian/filter role.</div>';
            return;
        }

        let html = '';
        filtered.forEach(u => {
            let roleBadgeClass = 'bg-secondary';
            if (u.role === 'operator') roleBadgeClass = 'bg-primary';
            else if (u.role === 'operator_wilayah') roleBadgeClass = 'bg-info text-dark';
            else if (u.role === 'admin') roleBadgeClass = 'bg-danger';

            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 12.5px;">${u.nama} <small class="text-muted fw-normal">(@${u.username || '-'})</small></div>
                        <div class="text-muted" style="font-size: 11px;">
                            <span>${u.upt}</span> &bull; 
                            <span class="badge ${roleBadgeClass}" style="font-size: 10px;">${u.role_label || u.role}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        ${u.has_wa 
                            ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace" style="font-size: 11px;"><i class="fab fa-whatsapp me-1"></i>${u.no_wa}</span>`
                            : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 10.5px;"><i class="fas fa-times-circle me-1"></i>Belum Ada WA</span>`}
                    </div>
                </div>
            `;
        });
        listContainer.innerHTML = html;
    }

    function bukaModalListPenerima() {
        const modal = new bootstrap.Modal(document.getElementById('modalListRecipients'));
        const totalEl = document.getElementById('modalRecipTotal');
        const validEl = document.getElementById('modalRecipValid');

        totalEl.innerText = cachedRecipientsData.length;
        let valid = cachedRecipientsData.filter(u => u.has_wa).length;
        validEl.innerText = `${valid} Nomor Siap Kirim`;

        currentRecipientsFilter = 'all';
        const searchInput = document.getElementById('searchRecipInput');
        if (searchInput) searchInput.value = '';

        filterRecipientsModal('all');
        modal.show();
    }

    function insertBcFormat(startTag, endTag) {
        const area = document.getElementById('bc_pesan');
        if (!area) return;
        const start = area.selectionStart;
        const end = area.selectionEnd;
        const text = area.value;
        const selected = text.substring(start, end);

        area.value = text.substring(0, start) + startTag + selected + endTag + text.substring(end);
        area.focus();
        area.setSelectionRange(start + startTag.length, end + startTag.length);
        updateLiveMockup();
    }

    function insertBcVariable(tag) {
        const area = document.getElementById('bc_pesan');
        if (!area) return;
        const start = area.selectionStart;
        const end = area.selectionEnd;
        const text = area.value;

        area.value = text.substring(0, start) + tag + text.substring(end);
        area.focus();
        area.setSelectionRange(start + tag.length, start + tag.length);
        updateLiveMockup();
    }

    function updateLiveMockup() {
        const judul = document.getElementById('bc_judul')?.value.trim() || 'Judul Pengumuman';
        const kategori = document.getElementById('bc_kategori')?.value.trim() || 'PENGINGAT';
        const pesan = document.getElementById('bc_pesan')?.value || '';
        const bubble = document.getElementById('liveChatBubble');
        if (!bubble) return;

        if (!pesan.trim()) {
            bubble.innerHTML = '<span class="text-muted fst-italic">Menunggu isi pesan diketik di editor sebelah kiri...</span>';
            return;
        }

        // Simulasi penggantian variabel untuk live preview
        let previewText = pesan
            .replace(/{nama}/g, 'Bapak/Ibu Operator')
            .replace(/{nama_operator}/g, 'Bapak/Ibu Operator')
            .replace(/{role}/g, 'Operator UPT')
            .replace(/{satker}/g, 'Lapas Kelas IIA Serang')
            .replace(/{nama_upt}/g, 'Lapas Kelas IIA Serang')
            .replace(/{tanggal}/g, '{{ now()->translatedFormat("d F Y") }}')
            .replace(/{judul}/g, judul)
            .replace(/{kategori}/g, kategori);

        // Escape HTML
        let escaped = previewText
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Konversi format markdown WhatsApp
        escaped = escaped
            .replace(/\*(.*?)\*/g, '<b>$1</b>')
            .replace(/_(.*?)_/g, '<i>$1</i>')
            .replace(/~(.*?)~/g, '<strike>$1</strike>')
            .replace(/```(.*?)```/g, '<code>$1</code>');

        bubble.innerHTML = escaped;
    }

    function loadTemplateBc(type) {
        const area = document.getElementById('bc_pesan');
        const judulInput = document.getElementById('bc_judul');
        const katInput = document.getElementById('bc_kategori');
        if (!area) return;

        const appUrl = window.location.origin;

        if (type === 'laporan_harian') {
            judulInput.value = 'Pengingat Batas Akhir Input Laporan Harian Satker';
            katInput.value = 'PENGINGAT';
            area.value = `📢 *PENGINGAT RESMI SULTAN BANTEN*\n` +
                         `*Kantor Wilayah Ditjenpas Banten*\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `📌 *Kategori:* {kategori}\n` +
                         `📅 *Tanggal:* {tanggal}\n` +
                         `📝 *Perihal:* *{judul}*\n\n` +
                         `Yth. Rekan *{nama}*\n` +
                         `*{role}* - *{satker}*\n\n` +
                         `Diberitahukan kepada seluruh jajaran operator satker untuk segera menyelesaikan penginputan data harian sebelum batas waktu pukul *15:00 WIB*:\n` +
                         `1. Laporan Bebas Harian (PB/CB/CMB/Asimilasi/Bebas Murni)\n` +
                         `2. Laporan Overstaying Tahanan\n` +
                         `3. Rekapitulasi Fluktuasi & Isi Hunian\n\n` +
                         `🔗 *Tautan Sistem SULTAN:* \n👉 ${appUrl}\n\n` +
                         `Terima kasih atas kerja sama dan disiplin pelaporan rekan-rekan.\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `_Pesan otomatis disiarkan melalui Sistem SULTAN BANTEN._`;
        } else if (type === 'mutasi_narapidana') {
            judulInput.value = 'Instruksi Tindak Lanjut Berkas Pelaksanaan Mutasi Narapidana';
            katInput.value = 'PENTING';
            area.value = `📢 *PENGUMUMAN RESMI SULTAN BANTEN*\n` +
                         `*Divisi Pemasyarakatan Kanwil Banten*\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `📌 *Kategori:* {kategori}\n` +
                         `📅 *Tanggal:* {tanggal}\n` +
                         `📝 *Perihal:* *{judul}*\n\n` +
                         `Yth. Rekan *{nama}* ({satker}),\n\n` +
                         `Menindaklanjuti usulan mutasi narapidana yang telah disetujui Kantor Wilayah, dimohon kepada:\n` +
                         `• *UPT Pengirim:* Segera mengunggah *Laporan Pemindahan* dan dokumen pengawalan.\n` +
                         `• *UPT Penerima:* Segera mengunggah *Laporan Penerimaan* setelah narapidana tiba di satker tujuan.\n` +
                         `• *Kedua UPT:* Melengkapi Berita Acara Serah Terima (BAST).\n\n` +
                         `🔗 *Modul Mutasi:* \n👉 ${appUrl}/mutasi/pelaksanaan\n\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `_Pesan resmi Divisi Pemasyarakatan Kanwil Kemenkumham Banten._`;
        } else if (type === 'sidang_tpp') {
            judulInput.value = 'Pengingat Unggah Berita Acara (BA) Hasil Sidang TPP';
            katInput.value = 'PENGINGAT';
            area.value = `📢 *PENGINGAT SIDANG TPP SULTAN BANTEN*\n` +
                         `*Kantor Wilayah Ditjenpas Banten*\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `📌 *Kategori:* {kategori}\n` +
                         `📅 *Tanggal:* {tanggal}\n` +
                         `📝 *Perihal:* *{judul}*\n\n` +
                         `Yth. Rekan Tim TPP *{satker}*,\n\n` +
                         `Bagi Satker UPT yang telah selesai menyelenggarakan Sidang TPP sesuai jadwal undangan, dimohon untuk segera mengunggah *Berita Acara (BA) Sidang TPP* ke aplikasi SULTAN BANTEN guna verifikasi dan penyusunan naskah laporan ke Kakanwil.\n\n` +
                         `🔗 *Tautan Dokumen Sidang TPP:* \n👉 ${appUrl}/sidang-tpp/dokumen-kanwil\n\n` +
                         `Atas kerja sama rekan-rekan diucapkan terima kasih.\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━`;
        } else if (type === 'maintenance') {
            judulInput.value = 'Pemberitahuan Pemeliharaan & Pembaruan Sistem SULTAN BANTEN';
            katInput.value = 'EDARAN';
            area.value = `📢 *PEMBERITAHUAN PEMELIHARAAN SISTEM*\n` +
                         `*Kantor Wilayah Ditjenpas Banten*\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `📌 *Kategori:* {kategori}\n` +
                         `📅 *Tanggal:* {tanggal}\n` +
                         `📝 *Perihal:* *{judul}*\n\n` +
                         `Yth. Rekan *{nama}* ({satker}),\n\n` +
                         `Diberitahukan bahwa sistem SULTAN BANTEN akan melakukan pembaruan berkala dan optimalisasi database. Selama proses ini, beberapa layanan mungkin mengalami jeda sejenak.\n\n` +
                         `Mohon pastikan seluruh pengisian data yang sedang berlangsung telah disimpan terlebih dahulu.\n\n` +
                         `🔗 *Situs Resmi:* ${appUrl}\n\n` +
                         `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                         `_Tim IT & Pengembang Aplikasi SULTAN BANTEN._`;
        }

        updateLiveMockup();
    }

    function siarkanPengumumanKeWa(p) {
        // Pindah ke tab broadcast
        const tabEl = document.getElementById('tab-broadcast');
        if (tabEl) {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
        }

        // Isi form broadcast
        document.getElementById('bc_pengumuman_id').value = p.id;
        document.getElementById('bc_judul').value = p.judul;
        document.getElementById('bc_kategori').value = (p.kategori || 'INFORMASI').toUpperCase();

        const cleanIsi = (p.isi || '').replace(/<[^>]*>/g, '').trim();
        const appUrl = window.location.origin;

        document.getElementById('bc_pesan').value = 
            `📢 *PENGUMUMAN RESMI SULTAN BANTEN*\n` +
            `*Kantor Wilayah Kementerian Hukum dan HAM Banten*\n` +
            `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
            `📌 *Kategori:* ${p.kategori}\n` +
            `📅 *Tanggal:* {tanggal}\n` +
            `📝 *Perihal:* *${p.judul}*\n\n` +
            `Yth. Rekan *{nama}*\n` +
            `*{role}* - *{satker}*\n\n` +
            `${cleanIsi}\n\n` +
            `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
            `🔗 *Tautan Informasi:* ${appUrl}\n` +
            `_Pesan resmi disiarkan melalui Sistem SULTAN BANTEN._`;

        updateLiveMockup();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function confirmKirimBroadcast() {
        const total = cachedRecipientsData.length;
        if (total === 0) {
            alert('Tidak ada operator atau pengguna sasaran yang terpilih. Harap periksa kembali pilihan role atau UPT.');
            return false;
        }

        return confirm(`Apakah Anda yakin ingin mengirimkan siaran WhatsApp pengumuman ini ke ${total} operator yang terpilih?`);
    }

    function lihatLogBroadcast(bcId) {
        const modal = new bootstrap.Modal(document.getElementById('modalBroadcastLogs'));
        const tbody = document.getElementById('logDetailTbody');
        const judulEl = document.getElementById('logJudul');
        const metaEl = document.getElementById('logMeta');

        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Memuat data log...</td></tr>';
        modal.show();

        fetch('/pengumuman/broadcast/' + bcId)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.broadcast) {
                    const bc = data.broadcast;
                    judulEl.innerText = bc.judul;
                    metaEl.innerHTML = `Kategori: <b>${bc.kategori}</b> &bull; Terkirim: <b class="text-success">${bc.total_sent}</b> &bull; Gagal: <b class="text-danger">${bc.total_failed}</b> &bull; Waktu: ${bc.sent_at || '-'}`;

                    if (!bc.logs || bc.logs.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada data log detail.</td></tr>';
                    } else {
                        let html = '';
                        bc.logs.forEach((log, idx) => {
                            html += `
                                <tr>
                                    <td class="text-center text-muted">${idx + 1}</td>
                                    <td><div class="fw-bold text-dark">${log.nama_penerima}</div><small class="text-muted">${log.role || '-'}</small></td>
                                    <td>${log.nama_upt || 'Kantor Wilayah'}</td>
                                    <td class="font-monospace">${log.no_wa}</td>
                                    <td class="text-center">
                                        ${log.status === 'sent' 
                                            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Terkirim</span>' 
                                            : `<span class="badge bg-danger" title="${log.response_message || ''}"><i class="fas fa-times me-1"></i>Gagal</span>`}
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    }
                }
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-danger">Gagal memuat log detail.</td></tr>';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        fetchRecipientsCount();
        updateLiveMockup();
    });
</script>
@endpush
