@extends('layouts.app')

@section('title', 'Koneksi & Gateway WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fab fa-whatsapp text-success me-2"></i>Koneksi & Gateway WhatsApp
@endsection

@section('content')
<!-- Header Quick Navigation Tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item">
                <a class="nav-link active py-1 px-3 fw-semibold" href="{{ route('wa.gateway') }}">
                    <i class="fas fa-plug me-1 text-primary"></i>1. Gateway & Koneksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.contacts') }}">
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
            <i class="fas fa-clock me-1 text-primary"></i>Waktu Server: <strong>{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</strong>
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Form Konfigurasi Gateway -->
    <div class="col-lg-7">
        <div class="content-card h-100">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <h5 class="fw-bold fs-6 mb-0">
                    <i class="fas fa-sliders-h text-primary me-2"></i>Konfigurasi Provider Gateway
                </h5>
                <span class="badge {{ !empty($waSettings->aktif) ? 'bg-success' : 'bg-secondary' }} px-3 py-1">
                    {{ !empty($waSettings->aktif) ? '🟢 Master: AKTIF' : '⚪ Master: NONAKTIF' }}
                </span>
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

            <form action="{{ route('wa.gateway.update') }}" method="POST">
                @csrf

                <!-- Master Toggle Switch -->
                <div class="p-3 mb-4 rounded border bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">Status Master Otomatisasi WhatsApp</div>
                        <small class="text-muted">Jika dinonaktifkan, seluruh pengiriman otomatis (pagi, siang, terjadwal) akan langsung dihentikan.</small>
                    </div>
                    <div class="form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" name="aktif" id="aktif" style="width: 3.2em; height: 1.6em; cursor: pointer;"
                            {{ !empty($waSettings->aktif) ? 'checked' : '' }} aria-label="Aktifkan Notifikasi WhatsApp">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="gateway_name" class="form-label small fw-semibold">Penyedia Gateway <span class="text-danger">*</span></label>
                        <select name="gateway_name" id="gateway_name" class="form-select form-select-sm" aria-label="Gateway WhatsApp">
                            <option value="fonnte" {{ ($waSettings->gateway_name ?? 'fonnte') == 'fonnte' ? 'selected' : '' }}>Fonnte (Rekomendasi Utama)</option>
                            <option value="wablas" {{ ($waSettings->gateway_name ?? '') == 'wablas' ? 'selected' : '' }}>Wablas</option>
                            <option value="starsender" {{ ($waSettings->gateway_name ?? '') == 'starsender' ? 'selected' : '' }}>Starsender</option>
                            <option value="other" {{ ($waSettings->gateway_name ?? '') == 'other' ? 'selected' : '' }}>Kustom / Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="sender_number" class="form-label small fw-semibold">Nomor WA Pengirim (Opsional)</label>
                        <input type="text" name="sender_number" id="sender_number" class="form-control form-control-sm"
                            value="{{ $waSettings->sender_number ?? '' }}"
                            placeholder="Contoh: 6281234567890" aria-label="Nomor WA Pengirim">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="api_key" class="form-label small fw-semibold">API Token / Secret Key <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="api_key" id="api_key" class="form-control form-control-sm font-monospace"
                            value="{{ $waSettings->api_key ?? '' }}"
                            placeholder="Masukkan API Key / Token gateway" aria-label="API Key Gateway">
                        <button class="btn btn-outline-secondary" type="button" id="toggleApiKey" title="Tampilkan/Sembunyikan API Key">
                            <i class="fas fa-eye" id="toggleEyeIcon"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size: 11px;">Token otentikasi resmi yang didapatkan dari dashboard Fonnte.</small>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label for="api_url" class="form-label small fw-semibold">Endpoint API URL <span class="text-danger">*</span></label>
                        <input type="text" name="api_url" id="api_url" class="form-control form-control-sm font-monospace"
                            value="{{ $waSettings->api_url ?? 'https://api.fonnte.com/send' }}"
                            placeholder="https://api.fonnte.com/send" aria-label="API URL Endpoint">
                        <small class="text-muted" style="font-size: 11px;">Default Fonnte: <code>https://api.fonnte.com/send</code></small>
                    </div>
                    <div class="col-md-5">
                        <label for="jeda_kirim" class="form-label small fw-semibold">
                            Jeda Antar Kontak
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 10px;">Anti-Spam</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="jeda_kirim" id="jeda_kirim" class="form-control" min="1" max="30"
                                value="{{ $waSettings->jeda_kirim ?? 4 }}" required>
                            <span class="input-group-text">detik</span>
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Jeda acak (&plusmn;1 dtk) per nomor.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-save me-1"></i>Simpan Konfigurasi Gateway
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Uji Coba Pengiriman (Sandbox) -->
    <div class="col-lg-5">
        <div class="content-card mb-4">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom">
                <i class="fas fa-paper-plane text-success me-2"></i>Uji Coba Pengiriman (Sandbox)
            </h5>
            <p class="text-muted small mb-3">
                Gunakan form ini untuk memverifikasi apakah token API gateway Anda sudah terhubung aktif ke WhatsApp dan bisa mengirimkan pesan riil.
            </p>

            <!-- Test Kirim Nomor Personal -->
            <form action="{{ route('wa.gateway.test') }}" method="POST" class="mb-4 p-3 border rounded bg-light">
                @csrf
                <input type="hidden" name="action" value="test_message">
                <div class="fw-semibold text-dark small mb-2"><i class="fas fa-mobile-alt me-1 text-primary"></i>Tes Kirim ke Nomor Pribadi</div>
                <div class="mb-2">
                    <input type="text" name="test_number" class="form-control form-control-sm"
                        placeholder="Nomor WA (contoh: 081234567890)" required aria-label="Nomor Test">
                </div>
                <div class="mb-2">
                    <input type="text" name="test_message" class="form-control form-control-sm"
                        value="Halo! Ini adalah pesan uji coba dari Sistem Sultan Banten Ditjenpas." required aria-label="Pesan Test">
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="fas fa-paper-plane me-1"></i>Kirim Pesan Uji Coba Personal
                </button>
            </form>

            <!-- Test Kirim Grup -->
            <form action="{{ route('wa.gateway.test') }}" method="POST" class="p-3 border rounded bg-light">
                @csrf
                <input type="hidden" name="action" value="test_group">
                <div class="fw-semibold text-dark small mb-2"><i class="fas fa-users me-1 text-primary"></i>Tes Kirim ke Grup WhatsApp</div>
                <div class="mb-2">
                    <select name="group_id" class="form-select form-select-sm" aria-label="Pilih Grup Test">
                        @forelse($groups as $grp)
                            <option value="{{ $grp->group_id }}">{{ $grp->nama_grup }} ({{ $grp->group_id }})</option>
                        @empty
                            <option value="{{ $waSettings->group_id ?? '' }}">Grup Utama ({{ $waSettings->group_id ?? 'Belum diatur' }})</option>
                        @endforelse
                    </select>
                </div>
                <div class="mb-2">
                    <input type="text" name="test_message" class="form-control form-control-sm"
                        value="📢 *TEST KONEKSI GRUP* - Sistem Sultan Banten Ditjenpas terhubung sukses." required aria-label="Pesan Test Grup">
                </div>
                <button type="submit" class="btn btn-outline-success btn-sm w-100">
                    <i class="fas fa-bullhorn me-1"></i>Kirim Pesan Uji Coba ke Grup
                </button>
            </form>
        </div>

        <!-- Panduan Singkat Fonnte -->
        <div class="card border-0 shadow-sm bg-info-subtle">
            <div class="card-body p-3">
                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-info-circle me-1"></i>Petunjuk Koneksi Fonnte</h6>
                <ol class="small text-secondary mb-0 ps-3" style="line-height: 1.5;">
                    <li>Login ke dashboard resmi di <a href="https://fonnte.com" target="_blank" class="fw-semibold text-primary">fonnte.com</a>.</li>
                    <li>Pindai (*scan*) QR Code perangkat WhatsApp pengirim hingga statusnya <strong>Connected</strong>.</li>
                    <li>Salin Token API perangkat dan tempelkan ke kolom <strong>API Token</strong> di sebelah kiri.</li>
                    <li>Pastikan saldo / kuota pesan di akun Fonnte Anda masih mencukupi.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleApiKey');
    const apiKeyInput = document.getElementById('api_key');
    const eyeIcon = document.getElementById('toggleEyeIcon');

    if (toggleBtn && apiKeyInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            if (apiKeyInput.type === 'password') {
                apiKeyInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                apiKeyInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    }
});
</script>
@endsection
