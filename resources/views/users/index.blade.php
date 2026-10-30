@extends('layouts.app')

@section('title', 'Manajemen User - Sultan Banten')
@section('page_title')
    <i class="fas fa-user-cog me-2"></i>Manajemen User
@endsection

@php
    $moduleList = [
        'penghuni' => ['label' => 'Data Penghuni (Lapas/Rutan)', 'icon' => 'fas fa-users'],
        'overstaying' => ['label' => 'Data Overstaying Tahanan', 'icon' => 'fas fa-clock'],
        'integrasi' => ['label' => 'Data Integrasi (PB, CB, CMB)', 'icon' => 'fas fa-user-check'],
        'bebas_harian' => ['label' => 'Bebas Harian', 'icon' => 'fas fa-door-open'],
        'sidang_tpp' => ['label' => 'Sidang TPP', 'icon' => 'fas fa-gavel'],
        'mutasi' => ['label' => 'Mutasi Narapidana Antar-UPT', 'icon' => 'fas fa-exchange-alt'],
        'dashboard' => ['label' => 'Dashboard & Statistik', 'icon' => 'fas fa-chart-line'],
        'laporan' => ['label' => 'Cetak Laporan & Rekapitulasi', 'icon' => 'fas fa-print'],
        'activity_log' => ['label' => 'Log Aktivitas Pengguna', 'icon' => 'fas fa-history'],
        'upt' => ['label' => 'Master Data UPT', 'icon' => 'fas fa-building'],
        'user' => ['label' => 'Manajemen User & Hak Akses', 'icon' => 'fas fa-user-shield'],
        'instansi' => ['label' => 'Pengaturan Instansi & KOP', 'icon' => 'fas fa-cogs'],
        'pengumuman' => ['label' => 'Pengumuman Internal', 'icon' => 'fas fa-bullhorn'],
        'wa_settings' => ['label' => 'WhatsApp Gateway & Kontak', 'icon' => 'fab fa-whatsapp'],
        'wa_schedule' => ['label' => 'WhatsApp Jadwal & Template Pesan', 'icon' => 'fas fa-calendar-alt'],
    ];
@endphp

@push('styles')
<style>
    .role-badge {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 600;
    }
    .role-admin { background: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%); color: white; }
    .role-operator { background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%); color: white; }
    .role-viewer { background: #64748b; color: white; }
    .perm-table th, .perm-table td { vertical-align: middle; }
    .perm-table tbody tr:hover { background-color: rgba(14, 165, 233, 0.04); }
</style>
@endpush

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-users-cog text-primary me-2"></i>Daftar Pengguna Sistem</h5>
            <p class="text-muted small mb-0">Kelola akun pengguna, peran, tingkat hak akses per modul (Lihat Saja vs Akses Penuh), dan penetapan UPT</p>
        </div>
        @if(auth()->user()->canManage('user'))
        <button class="btn btn-primary btn-sm px-3 mt-2 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-user-plus me-1"></i> Tambah User
        </button>
        @else
        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
            <i class="fas fa-eye me-1"></i> Mode Lihat Saja
        </span>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="bg-primary bg-opacity-10">
                <tr>
                    <th class="text-center" width="4%">No</th>
                    <th width="20%">Nama & Username</th>
                    <th width="11%">No. WA</th>
                    <th class="text-center" width="9%">Role</th>
                    <th width="16%">UPT Terkait</th>
                    <th width="30%">Tingkat Hak Akses per Modul</th>
                    <th class="text-center" width="10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                <tr>
                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">{!! $u->getAvatarHtml(32) !!}</div>
                            <div>
                                <div class="fw-bold text-dark">{{ $u->nama_lengkap }}</div>
                                <small class="text-muted"><i class="fas fa-user small me-1"></i>{{ $u->username }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $u->no_wa ?: '-' }}</td>
                    <td class="text-center">
                        <span class="role-badge role-{{ $u->role }}">
                            {{ ucfirst($u->role) }}
                        </span>
                    </td>
                    <td class="small fw-semibold text-secondary">
                        {{ $u->upt?->nama_upt ?? 'Semua UPT (Wilayah)' }}
                    </td>
                    <td class="small">
                        @if($u->isAdmin())
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 py-1 px-2">
                                <i class="fas fa-shield-alt me-1"></i>Akses Penuh Seluruh Modul (Admin)
                            </span>
                        @else
                            <div class="d-flex flex-wrap gap-1">
                                @php $hasAny = false; @endphp
                                @foreach($moduleList as $modKey => $modInfo)
                                    @php $lvl = $u->getAccessLevel($modKey); @endphp
                                    @if($lvl === 'full')
                                        @php $hasAny = true; @endphp
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-0.5 px-1.5" title="{{ $modInfo['label'] }}: Akses Penuh (Kelola & Edit)">
                                            <i class="{{ $modInfo['icon'] }} me-1" style="font-size: 9px;"></i>{{ Str::limit($modInfo['label'], 16) }}
                                        </span>
                                    @elseif($lvl === 'view')
                                        @php $hasAny = true; @endphp
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-0.5 px-1.5" title="{{ $modInfo['label'] }}: Lihat Saja (Read-Only)">
                                            <i class="fas fa-eye me-1" style="font-size: 9px;"></i>{{ Str::limit($modInfo['label'], 16) }}
                                        </span>
                                    @endif
                                @endforeach
                                @if(! $hasAny)
                                    <span class="text-muted fst-italic" style="font-size: 11px;">Tidak ada hak akses</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(auth()->user()->canManage('user'))
                        <button class="btn btn-warning btn-sm py-0 px-2" onclick="editData({{ json_encode($u) }})" title="Edit" aria-label="Edit User {{ $u->username }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if($u->id != auth()->id())
                        <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm py-0 px-2" title="Hapus" aria-label="Hapus User {{ $u->username }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                        @else
                        <span class="text-muted small fst-italic">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada pengguna terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-user-plus me-2"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="add_nama_lengkap" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="add_nama_lengkap" class="form-control form-control-sm" required placeholder="Nama lengkap staf/operator" aria-label="Nama Lengkap">
                        </div>
                        <div class="col-md-6">
                            <label for="add_username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="add_username" class="form-control form-control-sm" required placeholder="Username unik login" aria-label="Username">
                        </div>
                        <div class="col-md-6">
                            <label for="add_password" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="add_password" class="form-control form-control-sm" required minlength="6" placeholder="Minimal 6 karakter" aria-label="Password">
                        </div>
                        <div class="col-md-6">
                            <label for="add_no_wa" class="form-label small fw-semibold">Nomor WhatsApp</label>
                            <input type="text" name="no_wa" id="add_no_wa" class="form-control form-control-sm" placeholder="Contoh: 08123456789" aria-label="Nomor WhatsApp">
                        </div>
                        <div class="col-md-6">
                            <label for="add_role" class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" id="add_role" class="form-select form-select-sm" required onchange="onRoleChange('add')">
                                <option value="operator">Operator UPT</option>
                                <option value="viewer">Viewer (Pengawas / Hanya Lihat)</option>
                                <option value="admin">Administrator Wilayah</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_upt_id" class="form-label small fw-semibold">Penetapan UPT</label>
                            <select name="upt_id" id="add_upt_id" class="form-select form-select-sm" aria-label="Penetapan UPT">
                                <option value="">-- Semua UPT / Kanwil --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Kosongkan jika admin/operator wilayah</small>
                        </div>
                    </div>

                    <div id="add_admin_notice" class="alert alert-info py-2 px-3 small d-none mb-3">
                        <i class="fas fa-info-circle me-1"></i><strong>Catatan:</strong> Akun Administrator Wilayah secara otomatis memiliki hak <strong>Akses Penuh</strong> pada seluruh modul sistem.
                    </div>

                    <!-- MATRIKS HAK AKSES MODUL -->
                    <div class="card border border-light-subtle rounded-3 mb-2 shadow-none bg-light bg-opacity-25">
                        <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                            <div>
                                <span class="fw-bold small text-dark"><i class="fas fa-sliders-h me-1 text-primary"></i>Tingkat Hak Akses per Modul</span>
                                <small class="text-muted d-block" style="font-size: 11px;">Pilih antara: <strong>Tidak Ada</strong>, <strong>Lihat Saja</strong> (Read-Only), atau <strong>Akses Penuh</strong> (Kelola, Tambah &amp; Edit)</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setMatrixPermissions('add', 'full')">
                                    <i class="fas fa-check-double me-1"></i>Semua Penuh
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm py-0 px-2" style="font-size: 11px;" onclick="setMatrixPermissions('add', 'view')">
                                    <i class="fas fa-eye me-1"></i>Semua Lihat
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setMatrixPermissions('add', 'none')">
                                    <i class="fas fa-times me-1"></i>Kosongkan
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 perm-table" style="font-size: 12px;">
                                <thead class="bg-light sticky-top shadow-sm" style="z-index: 1;">
                                    <tr>
                                        <th width="46%" class="ps-3">Nama Modul</th>
                                        <th class="text-center" width="18%"><span class="badge bg-secondary bg-opacity-10 text-secondary border">Tidak Ada</span></th>
                                        <th class="text-center" width="18%"><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><i class="fas fa-eye me-1"></i>Lihat Saja</span></th>
                                        <th class="text-center" width="18%"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="fas fa-check me-1"></i>Akses Penuh</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($moduleList as $modKey => $modInfo)
                                    <tr>
                                        <td class="ps-3 py-1.5">
                                            <i class="{{ $modInfo['icon'] }} me-2 text-secondary" style="width: 16px;"></i>
                                            <span class="fw-semibold text-dark">{{ $modInfo['label'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block mb-0">
                                                <input class="form-check-input" type="radio" name="akses_{{ $modKey }}" id="add_akses_{{ $modKey }}_none" value="none">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block mb-0">
                                                <input class="form-check-input" type="radio" name="akses_{{ $modKey }}" id="add_akses_{{ $modKey }}_view" value="view">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block mb-0">
                                                <input class="form-check-input" type="radio" name="akses_{{ $modKey }}" id="add_akses_{{ $modKey }}_full" value="full" checked>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning text-dark">
                <h5 class="modal-title fs-6"><i class="fas fa-user-edit me-2"></i>Edit Pengguna &amp; Hak Akses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
            </div>
            <form method="POST" id="formEdit">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="edit_nama_lengkap" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control form-control-sm" required aria-label="Nama Lengkap">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_username" class="form-label small fw-semibold">Username</label>
                            <input type="text" id="edit_username" class="form-control form-control-sm bg-light" readonly disabled aria-label="Username">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_password" class="form-label small fw-semibold">Password Baru (Opsional)</label>
                            <input type="password" name="password" id="edit_password" class="form-control form-control-sm" minlength="6" placeholder="Kosongkan jika tidak diganti" aria-label="Password Baru">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_no_wa" class="form-label small fw-semibold">Nomor WhatsApp</label>
                            <input type="text" name="no_wa" id="edit_no_wa" class="form-control form-control-sm" aria-label="Nomor WhatsApp">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_role" class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" id="edit_role" class="form-select form-select-sm" required onchange="onRoleChange('edit')">
                                <option value="operator">Operator UPT</option>
                                <option value="viewer">Viewer (Pengawas / Hanya Lihat)</option>
                                <option value="admin">Administrator Wilayah</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_upt_id" class="form-label small fw-semibold">Penetapan UPT</label>
                            <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm" aria-label="Penetapan UPT">
                                <option value="">-- Semua UPT / Kanwil --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Kosongkan jika admin/operator wilayah</small>
                        </div>
                    </div>

                    <div id="edit_admin_notice" class="alert alert-info py-2 px-3 small d-none mb-3">
                        <i class="fas fa-info-circle me-1"></i><strong>Catatan:</strong> Akun Administrator Wilayah secara otomatis memiliki hak <strong>Akses Penuh</strong> pada seluruh modul sistem.
                    </div>

                    <!-- MATRIKS HAK AKSES MODUL -->
                    <div class="card border border-light-subtle rounded-3 mb-2 shadow-none bg-light bg-opacity-25">
                        <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                            <div>
                                <span class="fw-bold small text-dark"><i class="fas fa-sliders-h me-1 text-primary"></i>Tingkat Hak Akses per Modul</span>
                                <small class="text-muted d-block" style="font-size: 11px;">Pilih antara: <strong>Tidak Ada</strong>, <strong>Lihat Saja</strong> (Read-Only), atau <strong>Akses Penuh</strong> (Kelola, Tambah &amp; Edit)</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setMatrixPermissions('edit', 'full')">
                                    <i class="fas fa-check-double me-1"></i>Semua Penuh
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm py-0 px-2" style="font-size: 11px;" onclick="setMatrixPermissions('edit', 'view')">
                                    <i class="fas fa-eye me-1"></i>Semua Lihat
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setMatrixPermissions('edit', 'none')">
                                    <i class="fas fa-times me-1"></i>Kosongkan
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 perm-table" style="font-size: 12px;">
                                <thead class="bg-light sticky-top shadow-sm" style="z-index: 1;">
                                    <tr>
                                        <th width="46%" class="ps-3">Nama Modul</th>
                                        <th class="text-center" width="18%"><span class="badge bg-secondary bg-opacity-10 text-secondary border">Tidak Ada</span></th>
                                        <th class="text-center" width="18%"><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><i class="fas fa-eye me-1"></i>Lihat Saja</span></th>
                                        <th class="text-center" width="18%"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="fas fa-check me-1"></i>Akses Penuh</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($moduleList as $modKey => $modInfo)
                                    <tr>
                                        <td class="ps-3 py-1.5">
                                            <i class="{{ $modInfo['icon'] }} me-2 text-secondary" style="width: 16px;"></i>
                                            <span class="fw-semibold text-dark">{{ $modInfo['label'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block mb-0">
                                                <input class="form-check-input" type="radio" name="akses_{{ $modKey }}" id="edit_akses_{{ $modKey }}_none" value="none">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block mb-0">
                                                <input class="form-check-input" type="radio" name="akses_{{ $modKey }}" id="edit_akses_{{ $modKey }}_view" value="view">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block mb-0">
                                                <input class="form-check-input" type="radio" name="akses_{{ $modKey }}" id="edit_akses_{{ $modKey }}_full" value="full">
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const allModuleKeys = @json(array_keys($moduleList));

    function setMatrixPermissions(prefix, level) {
        allModuleKeys.forEach(field => {
            const radio = document.getElementById(`${prefix}_akses_${field}_${level}`);
            if (radio) {
                radio.checked = true;
            }
        });
    }

    function onRoleChange(prefix) {
        const role = document.getElementById(`${prefix}_role`).value;
        const alertNotice = document.getElementById(`${prefix}_admin_notice`);
        if (role === 'admin') {
            if (alertNotice) alertNotice.classList.remove('d-none');
            setMatrixPermissions(prefix, 'full');
        } else {
            if (alertNotice) alertNotice.classList.add('d-none');
            if (role === 'viewer') {
                setMatrixPermissions(prefix, 'view');
            }
        }
    }

    function editData(u) {
        document.getElementById('formEdit').action = '/users/' + u.id;
        document.getElementById('edit_nama_lengkap').value = u.nama_lengkap;
        document.getElementById('edit_username').value = u.username;
        document.getElementById('edit_no_wa').value = u.no_wa || '';
        document.getElementById('edit_role').value = u.role;
        document.getElementById('edit_upt_id').value = u.upt_id || '';

        allModuleKeys.forEach(field => {
            let val = u['akses_' + field];
            if (val === true || val === 1 || val === '1' || val === 'full') {
                val = 'full';
            } else if (val === 'view' || val === 'lihat') {
                val = 'view';
            } else {
                val = 'none';
            }

            // Jika user viewer, batasi maksimal hanya view
            if (u.role === 'viewer' && val === 'full') {
                val = 'view';
            }

            const radio = document.getElementById(`edit_akses_${field}_${val}`);
            if (radio) {
                radio.checked = true;
            }
        });

        onRoleChange('edit');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
@endpush
