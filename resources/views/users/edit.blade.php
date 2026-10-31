@extends('layouts.app')

@section('title', 'Edit Pengguna: ' . $targetUser->nama_lengkap . ' - Sultan Banten')
@section('page_title')
    <div class="d-flex align-items-center">
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm me-2" title="Kembali ke Daftar User">
            <i class="fas fa-arrow-left"></i>
        </a>
        <span><i class="fas fa-user-edit me-2 text-warning"></i>Edit Pengguna: {{ $targetUser->nama_lengkap }}</span>
    </div>
@endsection

@push('styles')
<style>
    .perm-card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 16px;
        font-weight: 700;
        font-size: 13px;
        color: #334155;
    }
    .perm-row {
        border-bottom: 1px solid #f1f5f9;
        padding: 12px 16px;
        transition: background-color 0.15s ease;
    }
    .perm-row:hover {
        background-color: #f8fafc;
    }
    .perm-row:last-child {
        border-bottom: none;
    }
    .radio-pill-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 8px;
        gap: 2px;
    }
    .radio-pill-group label {
        margin-bottom: 0;
        padding: 4px 10px;
        font-size: 11.5px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        color: #64748b;
        user-select: none;
    }
    .radio-pill-group input[type="radio"] {
        display: none;
    }
    .radio-pill-group input[value="none"]:checked + label {
        background: #94a3b8;
        color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .radio-pill-group input[value="view"]:checked + label {
        background: #0284c7;
        color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .radio-pill-group input[value="full"]:checked + label {
        background: #16a34a;
        color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .sticky-actions {
        position: sticky;
        top: 70px;
        z-index: 10;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('users.update', $targetUser->id) }}" id="formEditUser">
    @csrf
    @method('PUT')

    <!-- Top Action Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 sticky-actions">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none">Manajemen User</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit: {{ $targetUser->username }}</li>
                </ol>
            </nav>
            <h6 class="fw-bold mb-0 text-dark mt-1">
                Edit Data &amp; Hak Akses: <span class="text-primary">{{ $targetUser->nama_lengkap }}</span> (<code>{{ $targetUser->username }}</code>)
            </h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-times me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSubmitTop">
                <i class="fas fa-save me-1"></i> Simpan Perubahan
            </button>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger py-2 px-3 mb-4 rounded-3">
        <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Terjadi kesalahan pada formulir:</div>
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row g-4">
        <!-- ================================= KOLOM KIRI: PROFIL & KREDENSIAL ================================= -->
        <div class="col-lg-4">
            <!-- Card Profil & Kredensial Akun -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <div class="me-3">{!! $targetUser->getAvatarHtml(42) !!}</div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">{{ $targetUser->nama_lengkap }}</h6>
                        <small class="text-muted">Username: <strong>{{ $targetUser->username }}</strong></small>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control form-control-sm @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap', $targetUser->nama_lengkap) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username Login</label>
                        <input type="text" class="form-control form-control-sm bg-light" value="{{ $targetUser->username }}" disabled readonly>
                        <small class="text-muted" style="font-size: 11px;">Username sistem tidak dapat diubah.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Ganti Password (Opsional)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" minlength="6" placeholder="Kosongkan jika tidak ingin diubah">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Biarkan kosong jika tetap menggunakan password saat ini.</small>
                    </div>

                    <div class="mb-3">
                        <label for="no_wa" class="form-label small fw-semibold">Nomor WhatsApp</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                            <input type="text" name="no_wa" id="no_wa" class="form-control" value="{{ old('no_wa', $targetUser->no_wa) }}" placeholder="Contoh: 08123456789">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Peran & UPT -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-user-shield text-primary me-2"></i>Peran &amp; Satuan Kerja
                    </h6>
                    <small class="text-muted">Tentukan tingkatan peran pengguna</small>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label for="role" class="form-label small fw-semibold">Peran Utama (Role) <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select form-select-sm" required onchange="handleRoleChange(this.value)">
                            <option value="operator" {{ old('role', $targetUser->role) == 'operator' ? 'selected' : '' }}>Operator UPT</option>
                            <option value="viewer" {{ old('role', $targetUser->role) == 'viewer' ? 'selected' : '' }}>Viewer (Hanya Lihat / Kanwil)</option>
                            <option value="admin" {{ old('role', $targetUser->role) == 'admin' ? 'selected' : '' }}>Administrator Wilayah</option>
                        </select>
                    </div>

                    <div class="mb-3" id="container_upt">
                        <label for="upt_id" class="form-label small fw-semibold">Penetapan Unit Pelaksana Teknis (UPT)</label>
                        <select name="upt_id" id="upt_id" class="form-select form-select-sm">
                            <option value="">-- Kantor Wilayah / Semua UPT --</option>
                            @foreach ($uptList as $upt)
                            <option value="{{ $upt->id }}" {{ old('upt_id', $targetUser->upt_id) == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size: 11px;">Pilih UPT tempat tugas operator. Kosongkan jika staf Kantor Wilayah.</small>
                    </div>

                    <div id="notice_role_admin" class="alert alert-info py-2 px-3 small d-none mb-0">
                        <i class="fas fa-info-circle me-1"></i><strong>Catatan:</strong> Akun Administrator Wilayah otomatis memiliki <strong>Akses Penuh</strong> ke seluruh modul sistem.
                    </div>

                    <div id="notice_role_viewer" class="alert alert-secondary py-2 px-3 small d-none mb-0">
                        <i class="fas fa-info-circle me-1"></i><strong>Catatan:</strong> Akun Viewer dibatasi maksimal hanya pada mode <strong>Lihat Saja</strong>.
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================= KOLOM KANAN: MATRIKS HAK AKSES MODUL ================================= -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-sliders-h text-primary me-2"></i>Matriks Hak Akses Granular per Modul
                        </h6>
                        <small class="text-muted">Sesuaikan tingkat izin modul untuk pengguna ini</small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-success btn-sm px-2.5" onclick="setAllPermissions('full')">
                            <i class="fas fa-check-double me-1"></i> Semua Penuh
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm px-2.5" onclick="setAllPermissions('view')">
                            <i class="fas fa-eye me-1"></i> Semua Lihat
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2.5" onclick="setAllPermissions('none')">
                            <i class="fas fa-times me-1"></i> Kosongkan
                        </button>
                    </div>
                </div>

                <div class="card-body p-3">
                    <!-- Note Dashboard -->
                    <div class="alert alert-light border py-2 px-3 small mb-3 d-flex align-items-center" style="font-size: 12px;">
                        <i class="fas fa-info-circle text-primary me-2 fs-6"></i>
                        <div>
                            <strong>Informasi Sistem:</strong> Modul <strong>Dashboard &amp; Statistik Publik</strong> bersifat terbuka otomatis untuk seluruh pengguna sehingga tidak memerlukan konfigurasi hak akses khusus.
                        </div>
                    </div>

                    <!-- Kategori Modul -->
                    @foreach($groupedModules as $groupTitle => $modules)
                    <div class="card border border-light-subtle rounded-3 mb-3 overflow-hidden shadow-none">
                        <div class="perm-card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-folder-open text-primary opacity-75 me-2"></i>{{ $groupTitle }}</span>
                            <span class="badge bg-white text-secondary border small">{{ count($modules) }} Modul</span>
                        </div>
                        <div class="card-body p-0">
                            @foreach($modules as $modKey => $mod)
                            @php
                                $currentLevel = old('akses_' . $modKey, $targetUser->getAccessLevel($modKey));
                            @endphp
                            <div class="perm-row d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1" style="min-width: 260px;">
                                    <div class="d-flex align-items-center mb-0.5">
                                        <i class="{{ $mod['icon'] }} me-2" style="width: 18px; font-size: 14px;"></i>
                                        <span class="fw-bold text-dark fs-6">{{ $mod['label'] }}</span>
                                    </div>
                                    <p class="text-muted mb-0 small" style="font-size: 11.5px; line-height: 1.4;">
                                        {{ $mod['desc'] }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="radio-pill-group">
                                        <input type="radio" name="akses_{{ $modKey }}" id="edit_akses_{{ $modKey }}_none" value="none" {{ $currentLevel == 'none' ? 'checked' : '' }}>
                                        <label for="edit_akses_{{ $modKey }}_none" title="Tidak ada akses ke modul ini">
                                            Tidak Ada
                                        </label>

                                        <input type="radio" name="akses_{{ $modKey }}" id="edit_akses_{{ $modKey }}_view" value="view" {{ $currentLevel == 'view' ? 'checked' : '' }}>
                                        <label for="edit_akses_{{ $modKey }}_view" title="Hanya dapat melihat tabel data &amp; ekspor laporan (Read-Only)">
                                            <i class="fas fa-eye me-1"></i>Lihat Saja
                                        </label>

                                        <input type="radio" name="akses_{{ $modKey }}" id="edit_akses_{{ $modKey }}_full" value="full" {{ $currentLevel == 'full' ? 'checked' : '' }}>
                                        <label for="edit_akses_{{ $modKey }}_full" title="Dapat menambah, mengubah, dan menghapus data (Read-Write)">
                                            <i class="fas fa-check me-1"></i>Akses Penuh
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Bottom Action Bar -->
                <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar User
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSubmitBottom">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function setAllPermissions(level) {
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            if (radio.value === level && !radio.disabled) {
                radio.checked = true;
            }
        });
    }

    function handleRoleChange(role) {
        const adminNotice = document.getElementById('notice_role_admin');
        const viewerNotice = document.getElementById('notice_role_viewer');
        const fullRadios = document.querySelectorAll('input[type="radio"][value="full"]');

        if (role === 'admin') {
            adminNotice.classList.remove('d-none');
            viewerNotice.classList.add('d-none');
            setAllPermissions('full');
            fullRadios.forEach(r => r.disabled = false);
        } else if (role === 'viewer') {
            adminNotice.classList.add('d-none');
            viewerNotice.classList.remove('d-none');
            // Disable full options and switch to view if previously full
            fullRadios.forEach(r => {
                if (r.checked) {
                    const viewRadio = r.closest('.radio-pill-group').querySelector('input[value="view"]');
                    if (viewRadio) viewRadio.checked = true;
                }
                r.disabled = true;
            });
        } else {
            adminNotice.classList.add('d-none');
            viewerNotice.classList.add('d-none');
            fullRadios.forEach(r => r.disabled = false);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const roleEl = document.getElementById('role');
        if (roleEl) {
            handleRoleChange(roleEl.value);
        }
    });
</script>
@endpush

