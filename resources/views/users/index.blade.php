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
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada pengguna terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
