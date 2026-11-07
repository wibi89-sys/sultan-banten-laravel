@extends('layouts.app')

@section('title', 'Forensik & Sanitasi Dokumen Digital')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- HEADER HALAMAN -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="rounded p-2 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-fingerprint fa-lg"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Forensik &amp; Sanitasi Dokumen Digital</h5>
                    <small class="text-muted" style="font-size: 11.5px;">
                        Pusat Investigasi Keaslian Naskah, Deteksi Dokumen Palsu Berbantuan AI, serta Pembersih Jejak Digital &amp; Penguncian Berkas
                    </small>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5" style="font-size: 11px;">
                <i class="fas fa-shield-alt me-1"></i>Fitur Khusus Wewenang Pengawasan
            </span>
        </div>
    </div>

    <!-- ALERT PESAN SUKSES / ERROR -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-1.5"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-1.5"></i>{{ session('error') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- NAVIGASI TAB UTAMA (TERPISAH JELAS) -->
    <ul class="nav nav-pills nav-fill bg-white p-1.5 rounded-3 shadow-sm border mb-3" id="forensikTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'inspeksi' ? 'active' : '' }} fw-bold py-2 px-3 d-flex align-items-center justify-content-center gap-2" id="tab-inspeksi-btn" data-bs-toggle="pill" data-bs-target="#tab-inspeksi" type="button" role="tab" aria-controls="tab-inspeksi" aria-selected="{{ $activeTab === 'inspeksi' ? 'true' : 'false' }}">
                <i class="fas fa-search-plus text-danger"></i>
                <span>1. Inspeksi Forensik &amp; Deteksi Dokumen Palsu</span>
                <span class="badge bg-danger ms-1 text-white" style="font-size: 9.5px;">AI Powered</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'sanitasi' ? 'active' : '' }} fw-bold py-2 px-3 d-flex align-items-center justify-content-center gap-2" id="tab-sanitasi-btn" data-bs-toggle="pill" data-bs-target="#tab-sanitasi" type="button" role="tab" aria-controls="tab-sanitasi" aria-selected="{{ $activeTab === 'sanitasi' ? 'true' : 'false' }}">
                <i class="fas fa-broom text-primary"></i>
                <span>2. Pembersih Metadata (Sanitizer) &amp; Penguncian Dokumen</span>
                <span class="badge bg-primary ms-1 text-white" style="font-size: 9.5px;">AES-128 Lock</span>
            </button>
        </li>
    </ul>

    <!-- KONTEN TAB -->
    <div class="tab-content" id="forensikTabsContent">

        <!-- ========================================================================= -->
        <!-- TAB 1: INSPEKSI FORENSIK & DETEKSI DOKUMEN PALSU -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade {{ $activeTab === 'inspeksi' ? 'show active' : '' }}" id="tab-inspeksi" role="tabpanel" aria-labelledby="tab-inspeksi-btn">
            <div class="row g-3">
                <!-- FORM UPLOAD INSPEKSI -->
                <div class="col-lg-4 col-md-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark small" style="font-size: 12px;">
                                <i class="fas fa-upload text-danger me-1.5"></i>Unggah Berkas untuk Diinspeksi
                            </span>
                            <span class="badge bg-light text-secondary border" style="font-size: 10px;">PDF Maks 30MB</span>
                        </div>
                        <div class="card-body p-3">
                            <form id="formInspectPdf" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="inspect_file_pdf" class="form-label small fw-semibold text-dark mb-1">
                                        Pilih Berkas PDF yang Dicurigai / Ingin Diuji: <span class="text-danger">*</span>
                                    </label>
                                    <div class="border border-2 border-dashed rounded-3 p-3 text-center bg-light" id="inspectDropZone" style="cursor: pointer;">
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-2 opacity-75"></i>
                                        <div class="small fw-bold text-dark mb-1" id="inspectFileName">Pilih berkas atau seret ke sini</div>
                                        <div class="text-muted small" style="font-size: 10.5px;">Format berkas harus PDF asli (.pdf)</div>
                                        <input type="file" name="file_pdf" id="inspect_file_pdf" class="d-none" accept=".pdf,application/pdf" required onchange="handleInspectFileChange(this)">
                                    </div>
                                </div>

                                <div class="alert alert-secondary py-2 px-2.5 small mb-3" style="font-size: 11px;">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    <b>Cakupan Inspeksi Forensik:</b>
                                    <ul class="mb-0 ps-3 mt-1" style="line-height: 1.45;">
                                        <li>Pelacakan software pembuat asal (Word, Canva, Photoshop, Corel, dll.)</li>
                                        <li>Pemeriksaan XMP log riwayat penyuntingan</li>
                                        <li>Pemeriksaan anomali tanggal (Creation vs ModDate)</li>
                                        <li>Uji validasi QR Code / Barcode resmi ke database</li>
                                        <li>Skor probabilitas keaslian berkas oleh AI Gemini</li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm py-2 d-flex align-items-center justify-content-center" id="btnStartInspect">
                                    <i class="fas fa-microscope me-1.5"></i>Jalankan Uji Forensik &amp; Analisis AI
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- HASIL LAPORAN INVESTIGASI FORENSIK -->
                <div class="col-lg-8 col-md-7">
                    <!-- STATE AWAL: BELUM DIINSPEKSI -->
                    <div id="inspectEmptyState" class="card border-0 shadow-sm text-center p-5 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 480px; background-color: #fbfbfc;">
                        <div class="rounded-circle bg-danger-subtle text-danger p-3 mb-3 d-inline-block shadow-sm">
                            <i class="fas fa-fingerprint fa-3x"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Hasil Inspeksi Forensik Belum Tersedia</h6>
                        <p class="text-muted small mb-0" style="max-width: 440px;">
                            Silakan unggah berkas PDF naskah kedinasan, surat tugas, atau laporan yang ingin Anda periksa keasliannya di panel sebelah kiri. Sistem akan membongkar seluruh metadata teknis dan menyajikan laporan keaslian.
                        </p>
                    </div>

                    <!-- STATE LOADING SPINNER -->
                    <div id="inspectLoadingState" class="card border-0 shadow-sm text-center p-5 h-100 d-none flex-column align-items-center justify-content-center" style="min-height: 480px;">
                        <div class="spinner-border text-danger mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h6 class="fw-bold text-dark mb-1">Sedang Membedah Struktur Berkas &amp; Memanggil AI Forensik...</h6>
                        <p class="text-muted small mb-0">
                            Mengekstraksi Info Dict, XML XMP History, deteksi QR Code, dan kalkulasi risiko manipulasi. Harap tunggu...
                        </p>
                    </div>

                    <!-- STATE HASIL FORENSIK LENGKAP -->
                    <div id="inspectResultContainer" class="d-none d-flex flex-column gap-3">

                        <!-- KARTU 1: GAUGE SKOR KEASLIAN & STATUS UTAMA -->
                        <div class="card border-0 shadow-sm overflow-hidden" id="cardAuthenticityScore">
                            <div class="card-body p-3">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge" id="badgeRiskLevel" style="font-size: 11px;">Risiko Rendah</span>
                                            <small class="text-muted font-monospace" id="forensicSource">AI Gemini Analysis</small>
                                        </div>
                                        <h4 class="fw-bold mb-1 text-dark" id="txtStatusLabel">Dokumen Terindikasi Asli / Wajar</h4>
                                        <p class="text-secondary small mb-0" id="txtSummary">Hasil telaah forensik.</p>
                                    </div>
                                    <div class="text-md-end text-center flex-shrink-0">
                                        <div class="display-5 fw-bold" id="txtScoreNumber">95%</div>
                                        <div class="small fw-semibold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Indeks Keaslian</div>
                                    </div>
                                </div>

                                <!-- Progress bar skor -->
                                <div class="progress mt-2.5" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" id="barScore" style="width: 95%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- KARTU 2: REKOMENDASI & ANOMALI AI -->
                        <div class="card border-0 shadow-sm" id="cardAiRecommendations">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small" style="font-size: 12px;">
                                    <i class="fas fa-brain text-primary me-1.5"></i>Telaah Ahli Forensik Digital (Kemenimipas)
                                </span>
                                <span class="badge bg-primary-subtle text-primary border" style="font-size: 10px;">Rekomendasi Tindak Lanjut</span>
                            </div>
                            <div class="card-body p-3 small">
                                <div class="alert alert-light border py-2 px-2.5 mb-2.5" id="boxRecommendation" style="font-size: 12px;">
                                    <strong class="text-dark"><i class="fas fa-lightbulb text-warning me-1"></i>Saran Pemeriksa:</strong>
                                    <div id="txtRecommendation" class="mt-1 text-secondary">-</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-2.5 rounded bg-light border h-100">
                                            <span class="fw-bold text-dark d-block mb-1" style="font-size: 11.5px;">
                                                <i class="fas fa-check-circle text-success me-1"></i>Poin Temuan Teknis:
                                            </span>
                                            <ul class="mb-0 ps-3 text-secondary" id="listKeyFindings" style="font-size: 11px; line-height: 1.45;"></ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2.5 rounded bg-light border h-100">
                                            <span class="fw-bold text-dark d-block mb-1" style="font-size: 11.5px;">
                                                <i class="fas fa-exclamation-triangle text-danger me-1"></i>Indikasi Anomali / Kejanggalan:
                                            </span>
                                            <ul class="mb-0 ps-3 text-secondary" id="listAnomalies" style="font-size: 11px; line-height: 1.45;"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KARTU 3: VERIFIKASI QR CODE / BARCODE SULTAN BANTEN -->
                        <div class="card border-0 shadow-sm" id="cardQrVerification">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small" style="font-size: 12px;">
                                    <i class="fas fa-qrcode text-success me-1.5"></i>Hasil Verifikasi Silang Barcode / Cap QR Dokumen
                                </span>
                                <span class="badge bg-light text-secondary border" id="badgeQrCount">0 QR Terdeteksi</span>
                            </div>
                            <div class="card-body p-3" id="boxQrResultsContainer">
                                <!-- Konten QR diisi oleh JavaScript -->
                            </div>
                        </div>

                        <!-- KARTU 4: RINCIAN METADATA TEKNIS BERKAS -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small" style="font-size: 12px;">
                                    <i class="fas fa-info-circle text-info me-1.5"></i>Metadata Teknis Dokumen (Jejak Digital Asli)
                                </span>
                                <span class="badge bg-light text-secondary border" id="txtPdfVersion">PDF 1.7</span>
                            </div>
                            <div class="card-body p-3 small">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 align-middle">
                                        <tbody>
                                            <tr>
                                                <th class="bg-light text-secondary" style="width: 25%;">Software Pembuat (Creator)</th>
                                                <td class="fw-bold" id="metaCreator">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Mesin PDF (Producer)</th>
                                                <td id="metaProducer">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Waktu Dibuat (CreationDate)</th>
                                                <td id="metaCreationDate">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Waktu Terakhir Disimpan (ModDate)</th>
                                                <td id="metaModDate">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Nama Pengarang (Author)</th>
                                                <td id="metaAuthor">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Judul Dokumen (Title)</th>
                                                <td id="metaTitle">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Jumlah Revisi Simpan (%%EOF)</th>
                                                <td id="metaRevisions">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Jejak Software XMP History</th>
                                                <td id="metaXmpAgents">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Integritas Hash SHA-256</th>
                                                <td class="font-monospace text-muted text-break" style="font-size: 10px;" id="metaSha256">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: PEMBERSIH METADATA (SANITIZER) & PENGUNCIAN DOKUMEN -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade {{ $activeTab === 'sanitasi' ? 'show active' : '' }}" id="tab-sanitasi" role="tabpanel" aria-labelledby="tab-sanitasi-btn">
            <div class="row g-3">
                <div class="col-lg-6 col-md-7 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white py-2.5 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold small" style="font-size: 13px;">
                                <i class="fas fa-broom text-warning me-1.5"></i>Pembersihan Jejak Metadata &amp; Penguncian Berkas Resmi
                            </span>
                            <span class="badge bg-warning text-dark" style="font-size: 10px;">Anti-Edit AES-128</span>
                        </div>
                        <div class="card-body p-3">
                            <p class="small text-secondary mb-3" style="font-size: 12px; line-height: 1.5;">
                                Fitur ini digunakan untuk <b>membersihkan berkas PDF kedinasan yang sah</b> dari jejak identitas laptop/komputer pribadi, riwayat nama perangkat lunak pengolah grafis (misal Canva, Word, Photoshop), serta <b>mengunci berkas secara permanen (Read-Only)</b> agar tidak bisa diubah teksnya atau dihapus capnya oleh pihak luar saat berkas disebarkan.
                            </p>

                            <form action="{{ route('forensik-dokumen.sanitize') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <!-- Pilih Berkas -->
                                <div class="mb-3">
                                    <label for="sanitize_file_pdf" class="form-label small fw-semibold text-dark mb-1">
                                        Pilih Berkas PDF yang Ingin Dibersihkan &amp; Dikunci: <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" name="file_pdf" id="sanitize_file_pdf" class="form-control form-control-sm" accept=".pdf,application/pdf" required onchange="handleSanitizeFileChange(this)">
                                    <small class="text-muted" style="font-size: 11px;">Maksimal ukuran: 30 MB (Format: .pdf)</small>
                                </div>

                                <!-- Pengaturan Sanitasi -->
                                <div class="card border bg-light mb-3">
                                    <div class="card-body p-2.5 small">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="clean_metadata" id="san_clean_metadata" value="1" checked>
                                            <label class="form-check-label fw-bold text-dark" for="san_clean_metadata" style="font-size: 11.5px;">
                                                <i class="fas fa-eraser text-danger me-1"></i>Hapus Seluruh Jejak Metadata &amp; XMP History
                                            </label>
                                            <div class="text-muted" style="font-size: 10.5px;">
                                                Menghapus nama author pribadi, nama komputer, tags editor lama, dan mencatat identitas resmi instansi (*Kemenimipas Ditjenpas Banten*).
                                            </div>
                                        </div>

                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="lock_document" id="san_lock_document" value="1" checked>
                                            <label class="form-check-label fw-bold text-dark" for="san_lock_document" style="font-size: 11.5px;">
                                                <i class="fas fa-lock text-success me-1"></i>Kunci Berkas dengan Enkripsi AES-128 (Read-Only)
                                            </label>
                                            <div class="text-muted" style="font-size: 10.5px;">
                                                Memblokir pengeditan teks, penghapusan objek/cap QR, perombakan halaman, dan penambahan catatan. Berkas tetap dapat dibuka dan dicetak resmi.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kustomisasi Judul Resmi -->
                                <div class="mb-3">
                                    <label for="custom_title" class="form-label small fw-semibold text-dark mb-1">
                                        Judul Resmi Dokumen (Title Metadata):
                                    </label>
                                    <input type="text" name="custom_title" id="custom_title" class="form-control form-control-sm" placeholder="Contoh: Laporan Pelaksanaan Sidang TPP Kanwil Banten" value="Naskah Resmi Kedinasan">
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm py-2 d-flex align-items-center justify-content-center" id="btnSubmitSanitize">
                                    <i class="fas fa-file-shield me-1.5"></i>Bersihkan Jejak &amp; Unduh Berkas Terkunci
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function handleInspectFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            document.getElementById('inspectFileName').innerHTML = `<span class="text-primary">${file.name}</span> <span class="badge bg-secondary ms-1">${sizeMb} MB</span>`;
        }
    }

    document.getElementById('inspectDropZone')?.addEventListener('click', function() {
        document.getElementById('inspect_file_pdf')?.click();
    });

    function handleSanitizeFileChange(input) {
        // file selected feedback
    }

    // Handle AJAX Form Inspeksi Forensik
    document.getElementById('formInspectPdf')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('inspect_file_pdf');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Silakan pilih berkas PDF terlebih dahulu.');
            return;
        }

        const formData = new FormData(this);
        const btn = document.getElementById('btnStartInspect');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Memproses Forensik...';

        document.getElementById('inspectEmptyState')?.classList.add('d-none');
        document.getElementById('inspectResultContainer')?.classList.add('d-none');
        document.getElementById('inspectLoadingState')?.classList.remove('d-none');
        document.getElementById('inspectLoadingState')?.classList.add('d-flex');

        fetch("{{ route('forensik-dokumen.inspect') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            document.getElementById('inspectLoadingState')?.classList.add('d-none');
            document.getElementById('inspectLoadingState')?.classList.remove('d-flex');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-microscope me-1.5"></i>Jalankan Uji Forensik &amp; Analisis AI';

            if (res.success) {
                renderForensicResult(res.data);
                document.getElementById('inspectResultContainer')?.classList.remove('d-none');
            } else {
                alert(res.message || 'Gagal melakukan analisis berkas.');
                document.getElementById('inspectEmptyState')?.classList.remove('d-none');
            }
        })
        .catch(err => {
            document.getElementById('inspectLoadingState')?.classList.add('d-none');
            document.getElementById('inspectLoadingState')?.classList.remove('d-flex');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-microscope me-1.5"></i>Jalankan Uji Forensik &amp; Analisis AI';
            alert('Terjadi kesalahan jaringan atau server saat memproses berkas.');
            document.getElementById('inspectEmptyState')?.classList.remove('d-none');
        });
    });

    function renderForensicResult(data) {
        const ai = data.ai_analysis || {};
        const meta = data.metadata || {};
        const fileInfo = data.file_info || {};
        const qrAnalysis = data.qr_analysis || {};
        const score = ai.authenticity_score !== undefined ? ai.authenticity_score : (data.heuristic_score || 0);

        // 1. Skor & Status Utama
        const scoreBox = document.getElementById('txtScoreNumber');
        const scoreBar = document.getElementById('barScore');
        const statusLabel = document.getElementById('txtStatusLabel');
        const summary = document.getElementById('txtSummary');
        const riskBadge = document.getElementById('badgeRiskLevel');

        if (scoreBox) scoreBox.innerText = score + '%';
        if (scoreBar) {
            scoreBar.style.width = score + '%';
            scoreBar.className = score >= 80 ? 'progress-bar bg-success' : (score >= 50 ? 'progress-bar bg-warning' : 'progress-bar bg-danger');
        }

        if (statusLabel) statusLabel.innerText = ai.status_label || (score >= 80 ? 'Dokumen Terindikasi Asli / Wajar' : (score >= 50 ? 'Dokumen Perlu Diwaspadai' : 'Terindikasi Kuat Manipulasi / Palsu'));
        if (summary) summary.innerText = ai.summary || '-';

        if (riskBadge) {
            const risk = ai.risk_level || (score >= 80 ? 'rendah' : (score >= 50 ? 'sedang' : 'tinggi'));
            if (risk === 'rendah') {
                riskBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
                riskBadge.innerText = 'Risiko Pemalsuan Rendah';
            } else if (risk === 'sedang') {
                riskBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                riskBadge.innerText = 'Risiko Pemalsuan Sedang';
            } else {
                riskBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                riskBadge.innerText = 'Risiko Pemalsuan Tinggi / Kritis';
            }
        }

        document.getElementById('forensicSource').innerText = ai.source === 'gemini_ai' ? '🤖 Gemini AI Forensic Analysis' : '⚖️ Internal Rule-based Forensic Engine';

        // 2. Rekomendasi & Temuan
        document.getElementById('txtRecommendation').innerText = ai.recommendation || 'Lakukan verifikasi fisik langsung.';

        const listFindings = document.getElementById('listKeyFindings');
        listFindings.innerHTML = '';
        (ai.key_findings || []).forEach(f => {
            const li = document.createElement('li');
            li.innerText = f;
            listFindings.appendChild(li);
        });

        const listAnomalies = document.getElementById('listAnomalies');
        listAnomalies.innerHTML = '';
        const anomalies = (ai.anomalies && ai.anomalies.length > 0) ? ai.anomalies : ['Tidak terdeteksi anomali software grafis mencurigakan.'];
        anomalies.forEach(a => {
            const li = document.createElement('li');
            li.innerText = a;
            listAnomalies.appendChild(li);
        });

        // 3. QR Code Analysis Box
        const qrContainer = document.getElementById('boxQrResultsContainer');
        const badgeQrCount = document.getElementById('badgeQrCount');
        const qrResults = qrAnalysis.results || [];
        if (badgeQrCount) badgeQrCount.innerText = `${qrResults.length} Kode Terdeteksi`;

        if (qrResults.length === 0) {
            qrContainer.innerHTML = `
                <div class="alert alert-secondary py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-muted fa-lg"></i>
                    <div>Tidak terdeteksi kode QR atau barcode verifikasi resmi Sultan Banten pada berkas ini.</div>
                </div>
            `;
        } else {
            let html = '';
            qrResults.forEach(qr => {
                if (qr.status === 'valid_terdaftar') {
                    html += `
                        <div class="p-3 rounded bg-success-subtle border border-success-subtle mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>KODE QR TERDAFTAR RESMI</span>
                                <code class="fw-bold text-success fs-6">${qr.kode}</code>
                            </div>
                            <div class="row g-2 small text-dark mt-1">
                                <div class="col-md-6"><b>Satker UPT:</b> ${qr.satker_upt}</div>
                                <div class="col-md-6"><b>Jenis Naskah:</b> ${qr.jenis_dokumen}</div>
                                <div class="col-md-6"><b>No. Dokumen:</b> ${qr.nomor_dokumen}</div>
                                <div class="col-md-6"><b>Waktu Pengesahan:</b> ${qr.signed_at}</div>
                            </div>
                            <div class="alert alert-warning py-1.5 px-2 mt-2 mb-0 small" style="font-size: 11px;">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                <b>Uji Kloning Cap:</b> Periksa apakah nomor surat, nama pejabat, dan nama warga binaan pada kertas PDF fisik sama persis dengan rincian di atas. Jika berbeda, dokumen ini menggunakan <b>cap hasil kloning (curian)</b>.
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="p-3 rounded bg-danger-subtle border border-danger-subtle mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>KODE QR TIDAK TERDAFTAR</span>
                                <code class="fw-bold text-danger fs-6">${qr.kode}</code>
                            </div>
                            <div class="text-danger small fw-semibold">
                                <i class="fas fa-ban me-1"></i>${qr.pesan}
                            </div>
                        </div>
                    `;
                }
            });
            qrContainer.innerHTML = html;
        }

        // 4. Metadata Teknis
        document.getElementById('txtPdfVersion').innerText = `Versi: ${fileInfo.version || '1.7'}`;
        document.getElementById('metaCreator').innerHTML = meta.creator;
        document.getElementById('metaProducer').innerText = meta.producer;
        document.getElementById('metaCreationDate').innerText = meta.creation_date;
        document.getElementById('metaModDate').innerText = meta.mod_date;
        document.getElementById('metaAuthor').innerText = meta.author;
        document.getElementById('metaTitle').innerText = meta.title;
        document.getElementById('metaRevisions').innerText = `${fileInfo.revisions_count || 1} kali simpan (%%EOF)`;
        
        const xmpAgents = (data.xmp && data.xmp.software_agents && data.xmp.software_agents.length > 0)
            ? data.xmp.software_agents.join(', ')
            : '(Tidak ditemukan riwayat agen XMP)';
        document.getElementById('metaXmpAgents').innerText = xmpAgents;
        document.getElementById('metaSha256').innerText = fileInfo.sha256 || '-';
    }
</script>
@endsection

