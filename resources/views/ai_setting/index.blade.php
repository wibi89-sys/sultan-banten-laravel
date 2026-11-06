@extends('layouts.app')

@section('title', 'Pengaturan AI & Tata Naskah - Sultan Banten')
@section('page_title')
    <i class="fas fa-robot me-2 text-primary"></i>Pengaturan AI &amp; Tata Naskah Nota Dinas
@endsection

@section('content')
<div class="container-fluid px-0">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-check-circle fa-lg me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Nav Tabs -->
    <ul class="nav nav-pills mb-3 border-bottom pb-2" id="aiTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="konfigurasi-tab" data-bs-toggle="pill" data-bs-target="#tabKonfigurasi" type="button" role="tab">
                <i class="fas fa-microchip me-1"></i>1. Konfigurasi AI &amp; Format Nota Dinas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="pegawai-tab" data-bs-toggle="pill" data-bs-target="#tabPegawai" type="button" role="tab">
                <i class="fas fa-users-cog me-1"></i>2. Master Personil Pegawai Kanwil ({{ $pegawaiList->count() }} Orang)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="aiTabContent">
        <!-- TAB 1: KONFIGURASI AI & DEFAULT NOTA DINAS -->
        <div class="tab-pane fade show active" id="tabKonfigurasi" role="tabpanel">
            <form method="POST" action="{{ route('ai-setting.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    <!-- Kolom Kiri: Pengaturan API Key & Model -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-primary text-white py-2.5 fw-semibold small d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-brain me-1"></i> Integrasi Google Gemini AI</span>
                                <span class="badge {{ $setting->is_active && !empty($setting->api_key) ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $setting->is_active && !empty($setting->api_key) ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info py-2 px-3 small mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-lightbulb fa-lg me-2 text-warning mt-1"></i>
                                        <div>
                                            <b>Gratis &amp; Aman:</b> API Key disimpan di database (master data). Dapatkan API key gratis tanpa kartu kredit di <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="fw-bold text-decoration-none">Google AI Studio <i class="fas fa-external-link-alt"></i></a>. Kuota gratis resmi: 1.500 permintaan per hari.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $setting->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold small text-dark" for="is_active">
                                        Aktifkan Fitur Ekstraksi Surat Undangan AI
                                    </label>
                                    <small class="text-muted d-block" style="font-size: 11px;">Jika nonaktif, pembuatan Nota Dinas tetap dapat dilakukan 100% secara manual tanpa hambatan.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="provider" class="form-label small fw-semibold">Penyedia AI (Provider) <span class="text-danger">*</span></label>
                                    <select name="provider" id="provider" class="form-select form-select-sm" required>
                                        <option value="gemini" {{ $setting->provider == 'gemini' ? 'selected' : '' }}>Google Gemini (Mendukung Pembacaan PDF Langsung)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="model_select" class="form-label small fw-semibold mb-0">Model AI <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" style="font-size: 11px;" id="btnMuatModel" onclick="muatModelGemini()" title="Sinkronkan daftar model aktif dari Google AI Studio menggunakan API Key">
                                            <i class="fas fa-sync-alt me-1"></i>Muat Model dari API Key
                                        </button>
                                    </div>
                                    @php
                                        $knownModels = ['gemini-3.8-flash', 'gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
                                        $isCustom = !empty($setting->model) && !in_array($setting->model, $knownModels);
                                    @endphp
                                    <select id="model_select" class="form-select form-select-sm" onchange="onModelSelectChange(this.value)">
                                        <option value="gemini-3.8-flash" {{ ($setting->model == 'gemini-3.8-flash' || empty($setting->model)) ? 'selected' : '' }}>Gemini 3.8 Flash (Paling Direkomendasikan Google &bull; Sangat Cepat)</option>
                                        <option value="gemini-2.5-flash" {{ $setting->model == 'gemini-2.5-flash' ? 'selected' : '' }}>Gemini 2.5 Flash (Generasi Baru &bull; Cepat &amp; Responsif)</option>
                                        <option value="gemini-1.5-flash" {{ $setting->model == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Versi Stabil &bull; Kuota Harian Besar)</option>
                                        <option value="gemini-1.5-pro" {{ $setting->model == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Penalaran Kompleks &bull; Kuota Harian Lebih Sedikit)</option>
                                        <option value="custom" {{ $isCustom ? 'selected' : '' }}>-- Input Model Manual / Kustom --</option>
                                    </select>
                                    <div id="customModelWrapper" class="{{ $isCustom ? '' : 'd-none' }} mt-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted" style="font-size: 11px;">Nama Model:</span>
                                            <input type="text" id="model_custom" class="form-control form-control-sm" value="{{ $isCustom ? $setting->model : '' }}" placeholder="Contoh: gemini-3.8-flash" oninput="document.getElementById('model').value = this.value">
                                        </div>
                                        <small class="text-muted" style="font-size: 10.5px;">Ketik kode model Google AI Studio yang ingin digunakan.</small>
                                    </div>
                                    <input type="hidden" name="model" id="model" value="{{ $setting->model ?: 'gemini-3.8-flash' }}">
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        Google AI merekomendasikan <b>gemini-3.8-flash</b> untuk fitur terbaru dan performa terbaik. Anda juga dapat menekan <i>"Muat Model dari API Key"</i> untuk menampilkan semua model aktif yang didukung akun Anda.
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label for="api_key" class="form-label small fw-semibold">
                                        Gemini API Key
                                        @if(!empty($setting->api_key))
                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Tersimpan</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">Belum Diisi</span>
                                        @endif
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" id="api_key" name="api_key" class="form-control" placeholder="{{ !empty($setting->api_key) ? '••••••••••••••••••••••••••••••••' : 'Masukkan API Key AI Studio (AIzaSy...)' }}" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('api_key')">
                                            <i class="fas fa-eye" id="eye_api_key"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="btnTestKoneksi" onclick="testKoneksiAi()">
                                            <i class="fas fa-plug me-1"></i>Test Koneksi
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">Biarkan kosong jika tidak ingin mengubah API key yang tersimpan saat ini.</small>
                                </div>

                                <div id="testResultArea" class="d-none"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Format Default Nota Dinas -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-dark text-white py-2.5 fw-semibold small">
                                <i class="fas fa-file-signature me-1"></i> Format Baku Nota Dinas Kantor Wilayah
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label for="nomor_surat_prefix" class="form-label small fw-semibold">Awalan Nomor Nota Dinas</label>
                                        <input type="text" id="nomor_surat_prefix" name="nomor_surat_prefix" class="form-control form-control-sm" value="{{ $setting->nomor_surat_prefix }}" placeholder="WP.12-UM.01.01-">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="yth_tujuan" class="form-label small fw-semibold">Tujuan Surat (Yth.) <span class="text-danger">*</span></label>
                                        <input type="text" id="yth_tujuan" name="yth_tujuan" class="form-control form-control-sm" value="{{ $setting->yth_tujuan }}" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="dari_pengirim" class="form-label small fw-semibold">Pengirim (Dari) <span class="text-danger">*</span></label>
                                    <input type="text" id="dari_pengirim" name="dari_pengirim" class="form-control form-control-sm" value="{{ $setting->dari_pengirim }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="hal_surat" class="form-label small fw-semibold">Perihal Baku <span class="text-danger">*</span></label>
                                    <textarea id="hal_surat" name="hal_surat" class="form-control form-control-sm" rows="2" required>{{ $setting->hal_surat }}</textarea>
                                </div>

                                <hr class="my-3">

                                <h6 class="fw-bold text-dark small mb-2"><i class="fas fa-stamp text-primary me-1"></i>Pejabat Penandatangan Nota Dinas</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-7">
                                        <label for="penandatangan_nama" class="form-label small fw-semibold">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
                                        <input type="text" id="penandatangan_nama" name="penandatangan_nama" class="form-control form-control-sm" value="{{ $setting->penandatangan_nama }}" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="penandatangan_nip" class="form-label small fw-semibold">NIP Penandatangan</label>
                                        <input type="text" id="penandatangan_nip" name="penandatangan_nip" class="form-control form-control-sm" value="{{ $setting->penandatangan_nip }}" placeholder="Opsional">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="penandatangan_jabatan" class="form-label small fw-semibold">Jabatan Penandatangan <span class="text-danger">*</span></label>
                                    <input type="text" id="penandatangan_jabatan" name="penandatangan_jabatan" class="form-control form-control-sm" value="{{ $setting->penandatangan_jabatan }}" required>
                                </div>

                                <!-- Toggle Switch NIP Penandatangan -->
                                <div class="card bg-light border p-2.5 mb-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="tampilkan_nip" id="tampilkan_nip" {{ $setting->tampilkan_nip ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-dark" for="tampilkan_nip">
                                            Sertakan / Tampilkan NIP pada Tanda Tangan
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        <i class="fas fa-info-circle text-primary me-1"></i>Sesuai kebiasaan saat ini, NIP penandatangan <b>tidak dicantumkan</b> di bawah nama penandatangan. Aktifkan switch ini jika sewaktu-waktu NIP ingin dimunculkan.
                                    </small>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                                        <i class="fas fa-save me-1"></i>Simpan Pengaturan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB 2: MASTER PERSONIL PEGAWAI KANWIL -->
        <div class="tab-pane fade" id="tabPegawai" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users-cog text-primary me-2"></i>Daftar Personil Pegawai Kantor Wilayah</h6>
                        <small class="text-muted">Master pegawai ini akan muncul sebagai opsi pilihan checklist saat membuat Nota Dinas Surat Tugas TPP.</small>
                    </div>
                    <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPegawai">
                        <i class="fas fa-user-plus me-1"></i>Tambah Personil Pegawai
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light small fw-bold text-secondary">
                            <tr>
                                <th style="width: 45px;" class="text-center">No</th>
                                <th>Nama Lengkap &amp; Gelar</th>
                                <th>NIP</th>
                                <th>Jabatan</th>
                                <th>Pangkat / Golongan</th>
                                <th class="text-center" style="width: 80px;">Status</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pegawaiList as $index => $peg)
                            <tr>
                                <td class="text-center text-muted">{{ $index + 1 }}</td>
                                <td class="fw-bold text-dark">{{ $peg->nama }}</td>
                                <td><code>{{ $peg->nip ?: '-' }}</code></td>
                                <td>{{ $peg->jabatan }}</td>
                                <td>{{ $peg->pangkat_golongan ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $peg->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $peg->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick='editPegawai(@json($peg))' title="Edit Pegawai">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('ai-setting.pegawai.destroy', $peg->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pegawai {{ addslashes($peg->nama) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Pegawai">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada data pegawai Kanwil yang terdaftar. Silakan klik tombol "Tambah Personil Pegawai".</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Tambah Pegawai Kanwil -->
<div class="modal fade" id="modalTambahPegawai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('ai-setting.pegawai.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white py-2.5">
                    <h6 class="modal-title fw-bold"><i class="fas fa-user-plus me-1"></i>Tambah Personil Pegawai Kanwil</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="t_nama" class="form-label small fw-semibold">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="t_nama" class="form-control form-control-sm" placeholder="Contoh: Rizal Rivai, S.H" required>
                    </div>
                    <div class="mb-3">
                        <label for="t_nip" class="form-label small fw-semibold">NIP</label>
                        <input type="text" name="nip" id="t_nip" class="form-control form-control-sm" placeholder="Contoh: 199209122010121001">
                    </div>
                    <div class="mb-3">
                        <label for="t_jabatan" class="form-label small fw-semibold">Jabatan Kedinasan <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan" id="t_jabatan" class="form-control form-control-sm" placeholder="Contoh: Pengadministrasi Perkantoran" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label for="t_pangkat" class="form-label small fw-semibold">Pangkat / Golongan</label>
                            <input type="text" name="pangkat_golongan" id="t_pangkat" class="form-control form-control-sm" placeholder="Contoh: Penata Muda (III/a)">
                        </div>
                        <div class="col-4">
                            <label for="t_urutan" class="form-label small fw-semibold">Urutan</label>
                            <input type="number" name="urutan" id="t_urutan" class="form-control form-control-sm" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 fw-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Edit Pegawai Kanwil -->
<div class="modal fade" id="modalEditPegawai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditPegawai" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark py-2.5">
                    <h6 class="modal-title fw-bold"><i class="fas fa-user-edit me-1"></i>Edit Personil Pegawai Kanwil</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="e_nama" class="form-label small fw-semibold">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="e_nama" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label for="e_nip" class="form-label small fw-semibold">NIP</label>
                        <input type="text" name="nip" id="e_nip" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label for="e_jabatan" class="form-label small fw-semibold">Jabatan Kedinasan <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan" id="e_jabatan" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label for="e_pangkat" class="form-label small fw-semibold">Pangkat / Golongan</label>
                            <input type="text" name="pangkat_golongan" id="e_pangkat" class="form-control form-control-sm">
                        </div>
                        <div class="col-4">
                            <label for="e_urutan" class="form-label small fw-semibold">Urutan</label>
                            <input type="number" name="urutan" id="e_urutan" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="e_is_active" value="1">
                        <label class="form-check-label small fw-semibold" for="e_is_active">Status Pegawai Aktif</label>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm px-3 fw-bold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(id) {
        const input = document.getElementById(id);
        const icon = document.getElementById('eye_' + id);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function onModelSelectChange(val) {
        const hiddenModel = document.getElementById('model');
        const customWrapper = document.getElementById('customModelWrapper');
        const customInput = document.getElementById('model_custom');

        if (val === 'custom') {
            customWrapper.classList.remove('d-none');
            if (!customInput.value) {
                customInput.value = hiddenModel.value || 'gemini-3.8-flash';
            }
            hiddenModel.value = customInput.value;
            customInput.focus();
        } else {
            customWrapper.classList.add('d-none');
            hiddenModel.value = val;
        }
    }

    function muatModelGemini() {
        const btn = document.getElementById('btnMuatModel');
        const select = document.getElementById('model_select');
        const hiddenModel = document.getElementById('model');
        const apiKey = document.getElementById('api_key').value;
        const resultArea = document.getElementById('testResultArea');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 10px; height: 10px;"></span>Memuat...';

        fetch('{{ route("ai-setting.models") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                api_key: apiKey
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Muat Model dari API Key';

            if (data.success && data.models && data.models.length > 0) {
                const currentVal = hiddenModel.value;
                select.innerHTML = '';

                data.models.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.name + ' (' + m.id + ')';
                    if (m.id === currentVal) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });

                const customOpt = document.createElement('option');
                customOpt.value = 'custom';
                customOpt.textContent = '-- Input Model Manual / Kustom --';
                select.appendChild(customOpt);

                if (!data.models.some(m => m.id === currentVal)) {
                    if (data.models.some(m => m.id === 'gemini-3.8-flash')) {
                        select.value = 'gemini-3.8-flash';
                        hiddenModel.value = 'gemini-3.8-flash';
                    } else if (data.models.some(m => m.id === 'gemini-2.5-flash')) {
                        select.value = 'gemini-2.5-flash';
                        hiddenModel.value = 'gemini-2.5-flash';
                    } else {
                        select.value = data.models[0].id;
                        hiddenModel.value = data.models[0].id;
                    }
                }

                resultArea.classList.remove('d-none');
                resultArea.innerHTML = `
                    <div class="alert alert-success py-2 px-3 small mt-2 d-flex align-items-center mb-0">
                        <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
                        <div><b>Berhasil:</b> ${data.message}</div>
                    </div>
                `;
            } else {
                resultArea.classList.remove('d-none');
                resultArea.innerHTML = `
                    <div class="alert alert-warning py-2 px-3 small mt-2 d-flex align-items-center mb-0">
                        <i class="fas fa-exclamation-triangle fa-lg me-2 text-warning"></i>
                        <div>${data.message || 'Gagal memuat model.'}</div>
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Muat Model dari API Key';
            resultArea.classList.remove('d-none');
            resultArea.innerHTML = `
                <div class="alert alert-danger py-2 px-3 small mt-2 d-flex align-items-center mb-0">
                    <i class="fas fa-times-circle fa-lg me-2 text-danger"></i>
                    <div><b>Kesalahan Jaringan:</b> ${err.message}</div>
                </div>
            `;
        });
    }

    function testKoneksiAi() {
        const btn = document.getElementById('btnTestKoneksi');
        const resultArea = document.getElementById('testResultArea');
        const apiKey = document.getElementById('api_key').value;
        const model = document.getElementById('model').value;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menguji...';
        resultArea.classList.add('d-none');

        fetch('{{ route("ai-setting.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                api_key: apiKey,
                model: model
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Koneksi';
            resultArea.classList.remove('d-none');

            if (data.success) {
                resultArea.innerHTML = `
                    <div class="alert alert-success py-2 px-3 small mt-2 d-flex align-items-center mb-0">
                        <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
                        <div><b>Koneksi Berhasil (${data.model}):</b> ${data.message}</div>
                    </div>
                `;
            } else {
                resultArea.innerHTML = `
                    <div class="alert alert-danger py-2 px-3 small mt-2 d-flex align-items-center mb-0">
                        <i class="fas fa-times-circle fa-lg me-2 text-danger"></i>
                        <div><b>Gagal Terhubung:</b> ${data.message}</div>
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Koneksi';
            resultArea.classList.remove('d-none');
            resultArea.innerHTML = `
                <div class="alert alert-danger py-2 px-3 small mt-2 d-flex align-items-center mb-0">
                    <i class="fas fa-times-circle fa-lg me-2 text-danger"></i>
                    <div><b>Kesalahan Jaringan:</b> ${err.message}</div>
                </div>
            `;
        });
    }

    const modalEditPegawai = new bootstrap.Modal(document.getElementById('modalEditPegawai'));
    const formEditPegawai = document.getElementById('formEditPegawai');

    function editPegawai(peg) {
        formEditPegawai.action = '/pengaturan-ai/pegawai/' + peg.id;
        document.getElementById('e_nama').value = peg.nama || '';
        document.getElementById('e_nip').value = peg.nip || '';
        document.getElementById('e_jabatan').value = peg.jabatan || '';
        document.getElementById('e_pangkat').value = peg.pangkat_golongan || '';
        document.getElementById('e_urutan').value = peg.urutan || 0;
        document.getElementById('e_is_active').checked = !!peg.is_active;

        modalEditPegawai.show();
    }
</script>
@endpush

