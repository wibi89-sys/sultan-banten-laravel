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
        'laporan' => ['label' => 'Cetak Laporan', 'icon' => 'fas fa-print'],
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
        display: inline-block;
        white-space: nowrap;
    }
    .role-admin { background: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%); color: white; }
    .role-operator_wilayah { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; }
    .role-operator { background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%); color: white; }
    .role-viewer { background: #64748b; color: white; }
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
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm px-3 mt-2 mt-md-0 shadow-sm">
            <i class="fas fa-user-plus me-1"></i> Tambah User
        </a>
        @else
        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
            <i class="fas fa-eye me-1"></i> Mode Lihat Saja
        </span>
        @endif
    </div>

    <!-- Filter & Pencarian Pengguna -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-light">
        <div class="card-body p-2 p-md-3">
            <form action="{{ route('users.index') }}" method="GET" id="filterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="search" id="userSearchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama, username, atau no. WA..." value="{{ request('search') }}" autocomplete="off">
                            @if(request('search'))
                                <a href="{{ route('users.index', request()->except('search')) }}" class="btn btn-outline-secondary border bg-white" title="Hapus teks pencarian"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <select name="role" id="userRoleSelect" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- Semua Role --</option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrator Wilayah</option>
                            <option value="operator_wilayah" {{ request('role') == 'operator_wilayah' ? 'selected' : '' }}>Operator Wilayah</option>
                            <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator UPT</option>
                            <option value="viewer" {{ request('role') == 'viewer' ? 'selected' : '' }}>Viewer</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <select name="upt_id" id="userUptSelect" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- Semua Penugasan / UPT --</option>
                            <option value="kanwil" {{ request('upt_id') == 'kanwil' ? 'selected' : '' }}>🏛️ Kantor Wilayah (Semua UPT)</option>
                            @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}" {{ request('upt_id') == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-1 d-flex gap-1 justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Terapkan Filter">
                            <i class="fas fa-filter"></i>
                        </button>
                        @if(request()->hasAny(['search', 'role', 'upt_id']))
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Semua Filter">
                            <i class="fas fa-undo"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Jumlah & Live Search Note -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <div class="small text-muted">
            Menampilkan <strong id="userCountDisplay" class="text-dark">{{ count($users) }}</strong> pengguna
            @if(request()->hasAny(['search', 'role', 'upt_id']))
                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning ms-1">Filter Aktif</span>
            @endif
        </div>
        <div class="small text-muted d-none d-md-block">
            <i class="fas fa-info-circle me-1 text-primary"></i>Ketik langsung di kolom pencarian untuk menyaring daftar secara instan
        </div>
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
                <tr data-user-row="true" data-search="{{ strtolower($u->nama_lengkap . ' ' . $u->username . ' ' . ($u->no_wa ?? '') . ' ' . $u->role . ' ' . ($u->upt?->nama_upt ?? 'Kanwil Semua UPT')) }}">
                    <td class="text-center text-muted row-number">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">{!! $u->getAvatarHtml(32) !!}</div>
                            <div>
                                <div class="fw-bold text-dark user-nama">{{ $u->nama_lengkap }}</div>
                                <small class="text-muted"><i class="fas fa-user small me-1"></i><span class="user-username">{{ $u->username }}</span></small>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $u->no_wa ?: '-' }}</td>
                    <td class="text-center">
                        <span class="role-badge role-{{ $u->role }}">
                            @if($u->role === 'admin')
                                <i class="fas fa-crown me-1"></i>Administrator
                            @elseif($u->role === 'operator_wilayah')
                                <i class="fas fa-building me-1"></i>Operator Wilayah
                            @elseif($u->role === 'operator')
                                <i class="fas fa-user-tie me-1"></i>Operator UPT
                            @elseif($u->role === 'viewer')
                                <i class="fas fa-eye me-1"></i>Viewer
                            @else
                                {{ ucfirst($u->role) }}
                            @endif
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
                        <a href="{{ route('users.edit', $u->id) }}" class="btn btn-warning btn-sm py-0 px-2" title="Edit Pengguna" aria-label="Edit User {{ $u->username }}">
                            <i class="fas fa-edit"></i>
                        </a>
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
                    <td colspan="7" class="text-center py-4 text-muted">
                        @if(request()->hasAny(['search', 'role', 'upt_id']))
                            <div class="py-2">
                                <i class="fas fa-search-minus text-secondary fs-4 d-block mb-2"></i>
                                <strong>Tidak ditemukan pengguna yang cocok</strong> dengan kriteria filter pencarian.<br>
                                <a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-sm mt-2">
                                    <i class="fas fa-undo me-1"></i> Reset Filter
                                </a>
                            </div>
                        @else
                            Belum ada pengguna terdaftar.
                        @endif
                    </td>
                </tr>
                @endforelse
                <tr id="noSearchMatchRow" style="display: none;">
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-search-minus text-secondary fs-4 d-block mb-2"></i>
                        <strong>Tidak ditemukan pengguna yang cocok</strong> dengan kata kunci pencarian Anda.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('userSearchInput');
        const tableRows = document.querySelectorAll('table tbody tr[data-user-row]');
        const countDisplay = document.getElementById('userCountDisplay');
        const noDataRow = document.getElementById('noSearchMatchRow');

        if (searchInput && tableRows.length > 0) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let visibleCount = 0;

                tableRows.forEach(row => {
                    const searchText = (row.getAttribute('data-search') || '').toLowerCase();
                    if (!query || searchText.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                        const numEl = row.querySelector('.row-number');
                        if (numEl) {
                            numEl.textContent = visibleCount;
                        }
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (countDisplay) {
                    countDisplay.textContent = visibleCount;
                }

                if (noDataRow) {
                    noDataRow.style.display = (visibleCount === 0) ? '' : 'none';
                }
            });
        }
    });
</script>
@endpush
