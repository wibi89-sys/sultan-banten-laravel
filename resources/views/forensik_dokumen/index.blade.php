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
                                    <div class="rounded-3 p-3 text-center position-relative" id="inspectDropZone" style="cursor: pointer; border: 2px dashed #cbd5e1; background-color: #f8fafc; transition: all 0.2s ease-in-out;">
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-2 opacity-75"></i>
                                        <div class="small fw-bold text-dark mb-1" id="inspectFileName">Pilih berkas atau seret berkas ke sini</div>
                                        <div class="text-muted small" style="font-size: 10.5px;">Format berkas harus PDF asli (.pdf) &bull; Maks 30MB</div>
                                        <div class="mt-2" id="inspectFileAction" style="display: none;">
                                            <button type="button" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10.5px;" onclick="resetInspectFileInput(event)">
                                                <i class="fas fa-times me-1"></i>Ganti / Hapus Berkas
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Input file ditempatkan di luar drop zone dan tanpa attribute required untuk mencegah konflik Chromium focusable -->
                                    <input type="file" name="file_pdf" id="inspect_file_pdf" class="d-none" accept=".pdf,application/pdf" onchange="handleInspectFileChange(this)">
                                </div>

                                <!-- Alert Pesan Validasi Form -->
                                <div id="inspectFormAlert" class="alert alert-danger py-2 px-2.5 small mb-3 d-none" style="font-size: 11px;"></div>

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

                                <!-- Alert Deteksi Pemotongan / Penghapusan Lampiran -->
                                <div id="boxDeletionAlert" class="alert alert-danger py-2 px-3 small mt-2.5 mb-0 d-none align-items-start gap-2 border border-danger">
                                    <i class="fas fa-cut fa-lg mt-1 text-danger"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-danger mb-1" style="font-size: 12px;">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Peringatan Forensik: Terdeteksi Pemotongan / Penghapusan Lampiran Naskah!
                                        </div>
                                        <div id="txtDeletionEvidence" class="text-dark" style="font-size: 11.5px; line-height: 1.45;">-</div>
                                    </div>
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

                        <!-- KARTU 2: REKONSTRUKSI & TEKS HALAMAN YANG DIHAPUS (DIPULIHKAN DARI BINER PDF) -->
                        <div class="card border-0 shadow-sm border-danger-subtle d-none mb-3" id="cardRecoveredContent">
                            <div class="card-header bg-danger text-white py-2 px-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold small" style="font-size: 12.5px;">
                                    <i class="fas fa-trash-restore me-1.5"></i>Rekonstruksi &amp; Teks Halaman yang Dihapus (Dipulihkan dari Biner Berkas)
                                </span>
                                <span class="badge bg-white text-danger fw-bold" id="badgeRecoveredCount">Teks Berhasil Dipulihkan</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="alert alert-danger-subtle border border-danger-subtle py-2 px-2.5 mb-2.5 small" style="font-size: 11px;">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                    <b>Temuan Forensik Berkas:</b> Objek halaman berikut telah sengaja dihapus/dipotong dari naskah menggunakan software editor (seperti Nitro Pro / Acrobat / PDF24). Namun, isi teks biner aslinya berhasil diekstraksi dan dipulihkan secara utuh dari slack space berkas PDF.
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <span class="text-muted small fw-semibold" id="txtRecoveredStats" style="font-size: 11px;">Isi Teks Rekonstruksi:</span>
                                    <div class="d-flex gap-1.5">
                                        <button type="button" class="btn btn-outline-danger btn-xs py-1 px-2 fw-semibold" style="font-size: 11px;" onclick="copyRecoveredText()">
                                            <i class="fas fa-copy me-1"></i>Salin Teks
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs py-1 px-2 fw-semibold" style="font-size: 11px;" onclick="downloadRecoveredText()">
                                            <i class="fas fa-file-download me-1"></i>Unduh Berkas Teks (.txt)
                                        </button>
                                    </div>
                                </div>
                                <div class="font-monospace text-dark p-2.5 bg-light rounded border border-secondary-subtle" id="boxRecoveredText" style="font-size: 11.5px; max-height: 240px; overflow-y: auto; white-space: pre-wrap; line-height: 1.5; user-select: text;"></div>
                                <small class="text-muted d-block mt-1.5" style="font-size: 10.5px;">*Teks di atas dapat Anda gunakan sebagai bukti otentik forensik mengenai lampiran atau narapidana yang sengaja dihilangkan.</small>
                            </div>
                        </div>

                        <!-- KARTU 2.5: BERKAS LAMPIRAN TERTANAM (EMBEDDED FILES & LAMPIRAN) -->
                        <div class="card border-0 shadow-sm" id="cardEmbeddedFiles">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small" style="font-size: 12px;">
                                    <i class="fas fa-paperclip text-warning me-1.5"></i>Berkas Lampiran Digital (Embedded Files &amp; Jejak Lampiran)
                                </span>
                                <span class="badge bg-light text-secondary border" id="badgeEmbeddedCount">0 Berkas</span>
                            </div>
                            <div class="card-body p-3" id="boxEmbeddedFilesContainer">
                                <!-- Konten Lampiran diisi oleh JavaScript -->
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

                        <!-- KARTU 3.5: INSPEKTUR RIWAYAT MODIFIKASI & SOFTWARE EDITOR (NITRO, ADOBE, PDF24) -->
                        <div class="card border-0 shadow-sm" id="cardEditorInspector">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small" style="font-size: 12px;">
                                    <i class="fas fa-user-secret text-danger me-1.5"></i>Inspektur Riwayat Modifikasi &amp; Software Editor (Nitro, Adobe, PDF24)
                                </span>
                                <span class="badge bg-light text-secondary border" id="badgeEditorStatus">Mesin Dokumen</span>
                            </div>
                            <div class="card-body p-3 small">
                                <div id="boxEditorProfileBanner" class="p-2.5 rounded border mb-2.5">
                                    <!-- Banner diisi oleh JavaScript -->
                                </div>

                                <div class="fw-bold text-dark mb-2" style="font-size: 11.5px;">
                                    <i class="fas fa-history text-primary me-1"></i>Kronologi Garis Waktu Modifikasi Berkas:
                                </div>
                                <div id="boxEditorTimeline" class="list-group list-group-flush border rounded mb-1">
                                    <!-- Timeline diisi oleh JavaScript -->
                                </div>
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
                                                <th class="bg-light text-secondary" style="width: 25%;">Karakteristik Berkas</th>
                                                <td class="fw-bold text-dark" id="metaDocType">-</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-secondary">Software Pembuat (Creator)</th>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Reset / Hapus Berkas yang Dipilih
    function resetInspectFileInput(e) {
        if (e) {
            e.stopPropagation();
        }
        const fileInput = document.getElementById('inspect_file_pdf');
        if (fileInput) {
            fileInput.value = '';
        }
        document.getElementById('inspectFileName').innerHTML = 'Pilih berkas atau seret berkas ke sini';
        const actionBox = document.getElementById('inspectFileAction');
        if (actionBox) {
            actionBox.style.display = 'none';
        }
        const alertBox = document.getElementById('inspectFormAlert');
        if (alertBox) {
            alertBox.classList.add('d-none');
        }
    }

    // Tangani Perubahan Pilihan Berkas Inspeksi
    function handleInspectFileChange(input) {
        const alertBox = document.getElementById('inspectFormAlert');
        if (alertBox) alertBox.classList.add('d-none');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            const ext = file.name.split('.').pop().toLowerCase();

            // Validasi ekstensi di sisi klien
            if (ext !== 'pdf') {
                const msg = 'Format berkas tidak didukung. Harap pilih berkas dokumen berekstensi .pdf!';
                if (alertBox) {
                    alertBox.innerText = msg;
                    alertBox.classList.remove('d-none');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Format Tidak Sesuai', text: msg, confirmButtonColor: '#dc3545' });
                } else {
                    alert(msg);
                }
                resetInspectFileInput();
                return;
            }

            // Validasi batas ukuran 30 MB di sisi klien
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            if (file.size > 30 * 1024 * 1024) {
                const msg = `Ukuran berkas (${sizeMb} MB) melebihi batas maksimal yang diizinkan (30 MB).`;
                if (alertBox) {
                    alertBox.innerText = msg;
                    alertBox.classList.remove('d-none');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Ukuran Berkas Terlalu Besar', text: msg, confirmButtonColor: '#dc3545' });
                } else {
                    alert(msg);
                }
                resetInspectFileInput();
                return;
            }

            document.getElementById('inspectFileName').innerHTML = `<span class="text-primary fw-bold">${file.name}</span> <span class="badge bg-secondary ms-1">${sizeMb} MB</span>`;
            const actionBox = document.getElementById('inspectFileAction');
            if (actionBox) {
                actionBox.style.display = 'block';
            }
        }
    }

    // Inisialisasi Drag & Drop dan Event Listener Drop Zone
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('inspectDropZone');
        const fileInput = document.getElementById('inspect_file_pdf');

        if (dropZone && fileInput) {
            // Klik pada area drop zone memicu input file
            dropZone.addEventListener('click', function(e) {
                if (e.target.closest('#inspectFileAction')) return;
                fileInput.click();
            });

            // Efek dragover / dragenter
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '#dc3545';
                    dropZone.style.backgroundColor = '#fee2e2';
                }, false);
            });

            // Efek dragleave / dragend
            ['dragleave', 'dragend'].forEach(eventName => {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '#cbd5e1';
                    dropZone.style.backgroundColor = '#f8fafc';
                }, false);
            });

            // Event saat file di-drop
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#cbd5e1';
                dropZone.style.backgroundColor = '#f8fafc';

                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    handleInspectFileChange(fileInput);
                }
            }, false);
        }
    });

    // Validasi pemilihan berkas pada Tab Sanitasi
    function handleSanitizeFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'pdf') {
                const msg = 'Format berkas tidak didukung. Harap pilih berkas PDF!';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Format Tidak Sesuai', text: msg, confirmButtonColor: '#0d6efd' });
                } else {
                    alert(msg);
                }
                input.value = '';
                return;
            }

            if (file.size > 30 * 1024 * 1024) {
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                const msg = `Ukuran berkas (${sizeMb} MB) melebihi batas maksimal 30 MB.`;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Ukuran Terlalu Besar', text: msg, confirmButtonColor: '#0d6efd' });
                } else {
                    alert(msg);
                }
                input.value = '';
                return;
            }
        }
    }

    // Handle AJAX Form Inspeksi Forensik
    document.getElementById('formInspectPdf')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('inspect_file_pdf');
        const alertBox = document.getElementById('inspectFormAlert');
        if (alertBox) alertBox.classList.add('d-none');

        if (!fileInput.files || fileInput.files.length === 0) {
            const msg = 'Silakan pilih atau seret berkas PDF terlebih dahulu sebelum memulai uji forensik.';
            if (alertBox) {
                alertBox.innerText = msg;
                alertBox.classList.remove('d-none');
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Berkas Belum Dipilih',
                    text: msg,
                    confirmButtonColor: '#dc3545'
                });
            } else {
                alert(msg);
            }
            return;
        }

        const formData = new FormData(this);
        const btn = document.getElementById('btnStartInspect');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sedang Membedah &amp; Menganalisis...';

        document.getElementById('inspectEmptyState')?.classList.add('d-none');
        document.getElementById('inspectResultContainer')?.classList.add('d-none');
        document.getElementById('inspectLoadingState')?.classList.remove('d-none');
        document.getElementById('inspectLoadingState')?.classList.add('d-flex');

        fetch("{{ route('forensik-dokumen.inspect') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async (response) => {
            let resData = null;
            try {
                resData = await response.json();
            } catch (err) {
                resData = null;
            }

            if (!response.ok) {
                let errorMsg = 'Gagal melakukan analisis berkas.';
                if (resData && resData.message) {
                    errorMsg = resData.message;
                } else if (resData && resData.errors && resData.errors.file_pdf) {
                    errorMsg = resData.errors.file_pdf[0];
                } else if (response.status === 413) {
                    errorMsg = 'Ukuran berkas melebihi batas muatan server (HTTP 413 Payload Too Large). Silakan perkecil berkas.';
                } else if (response.status === 419) {
                    errorMsg = 'Sesi keamanan Anda telah kedaluwarsa (CSRF expired). Silakan muat ulang (refresh) halaman ini.';
                } else {
                    errorMsg = `Terjadi kesalahan pada server (Status HTTP ${response.status}: ${response.statusText || 'Error'}).`;
                }
                throw new Error(errorMsg);
            }

            if (!resData || !resData.success) {
                throw new Error(resData?.message || 'Gagal melakukan analisis berkas.');
            }

            return resData;
        })
        .then(res => {
            document.getElementById('inspectLoadingState')?.classList.add('d-none');
            document.getElementById('inspectLoadingState')?.classList.remove('d-flex');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-microscope me-1.5"></i>Jalankan Uji Forensik &amp; Analisis AI';

            renderForensicResult(res.data);
            document.getElementById('inspectResultContainer')?.classList.remove('d-none');
        })
        .catch(err => {
            document.getElementById('inspectLoadingState')?.classList.add('d-none');
            document.getElementById('inspectLoadingState')?.classList.remove('d-flex');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-microscope me-1.5"></i>Jalankan Uji Forensik &amp; Analisis AI';
            document.getElementById('inspectEmptyState')?.classList.remove('d-none');

            if (alertBox) {
                alertBox.innerText = err.message;
                alertBox.classList.remove('d-none');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memproses Berkas',
                    text: err.message,
                    confirmButtonColor: '#dc3545'
                });
            } else {
                alert('Gagal Memproses Berkas:\n' + err.message);
            }
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

        // Tampilkan Alert Khusus jika Terdeteksi Pemotongan/Penghapusan Lampiran
        const del = data.deleted_content || {};
        const boxDel = document.getElementById('boxDeletionAlert');
        const txtDel = document.getElementById('txtDeletionEvidence');
        if (boxDel && txtDel) {
            if (del.has_deletion) {
                boxDel.classList.remove('d-none');
                boxDel.classList.add('d-flex');
                let html = (del.evidence || []).map(e => `<div>• ${e}</div>`).join('');
                txtDel.innerHTML = html;
            } else {
                boxDel.classList.add('d-none');
                boxDel.classList.remove('d-flex');
            }
        }

        // Tampilkan Kartu Rekonstruksi Halaman Terhapus
        const cardRec = document.getElementById('cardRecoveredContent');
        const boxRecText = document.getElementById('boxRecoveredText');
        const txtRecStats = document.getElementById('txtRecoveredStats');
        if (cardRec && boxRecText) {
            if (del.has_deletion && del.deleted_text_sample) {
                cardRec.classList.remove('d-none');
                boxRecText.innerText = del.deleted_text_sample;
                const len = del.deleted_text_sample.length;
                const words = del.deleted_text_sample.trim().split(/\s+/).length;
                if (txtRecStats) txtRecStats.innerText = `Isi Teks Rekonstruksi (${len} karakter, ~${words} kata):`;
            } else {
                cardRec.classList.add('d-none');
                boxRecText.innerText = '';
            }
        }

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

        // 2.5. Berkas Lampiran Digital (Embedded Files)
        const embeddedFiles = data.embedded_files || [];
        const badgeEmbedded = document.getElementById('badgeEmbeddedCount');
        const boxEmbedded = document.getElementById('boxEmbeddedFilesContainer');
        if (badgeEmbedded) badgeEmbedded.innerText = `${embeddedFiles.length} Berkas Lampiran`;

        if (boxEmbedded) {
            if (embeddedFiles.length === 0) {
                boxEmbedded.innerHTML = `
                    <div class="alert alert-light border py-2 px-3 small mb-0 text-muted d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle fa-lg"></i>
                        <div>Tidak ditemukan berkas lampiran digital (Embedded Files) yang tersemat di dalam dokumen ini.</div>
                    </div>
                `;
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 align-middle">';
                html += '<thead class="table-light small"><tr><th style="width: 30%;">Nama Berkas Lampiran</th><th style="width: 15%;">Ukuran</th><th style="width: 25%;">Status Keterikatan</th><th>Pratinjau Isi Dokumen</th></tr></thead><tbody>';
                embeddedFiles.forEach(f => {
                    const isDel = f.status === 'terhapus';
                    const badgeClass = isDel ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle';
                    const statusIcon = isDel ? '<i class="fas fa-cut me-1"></i>Dihapus / Terlepas' : '<i class="fas fa-check-circle me-1"></i>Terlampir Aktif';
                    let preview = f.text_sample 
                        ? `<div class="font-monospace text-secondary p-1.5 rounded bg-light border" style="font-size: 10.5px; max-height: 85px; overflow-y: auto; line-height: 1.35;">${f.text_sample}</div>` 
                        : '<em class="text-muted small">Tidak ada cuplikan teks digital</em>';
                    html += `<tr>
                        <td>
                            <div class="fw-bold ${isDel ? 'text-danger' : 'text-primary'}"><i class="fas fa-paperclip me-1.5"></i>${f.filename}</div>
                            ${f.description ? `<div class="text-muted" style="font-size: 10px;">${f.description}</div>` : ''}
                        </td>
                        <td class="small font-monospace">${f.size_formatted || '-'}</td>
                        <td><span class="badge ${badgeClass} py-1.5 px-2">${statusIcon}</span></td>
                        <td>${preview}</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                boxEmbedded.innerHTML = html;
            }
        }

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
                if (qr.status === 'otentik_asli_sultan') {
                    html += `
                        <div class="p-3 rounded bg-success-subtle border border-success mb-2.5 shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-success text-white py-1.5 px-2.5" style="font-size: 11px;">
                                    <i class="fas fa-shield-alt me-1"></i>100% ASLI &amp; IDENTIK DENGAN MASTER SULTAN BANTEN
                                </span>
                                <code class="fw-bold text-success fs-6">${qr.kode}</code>
                            </div>
                            <div class="p-2.5 bg-white rounded border mb-2 text-success small" style="font-size: 11px;">
                                <i class="fas fa-check-double me-1"></i><b>Verifikasi Kriptografi Lulus:</b> Hash SHA-256 berkas yang diunggah cocok persis (bit-for-bit) dengan master arsip digital server. Berkas ini murni langsung dari unduhan resmi Sultan Banten tanpa ada 1 karakter pun yang diubah.
                            </div>
                            <div class="row g-2 small text-dark">
                                <div class="col-md-6"><b>Satker UPT:</b> ${qr.satker_upt}</div>
                                <div class="col-md-6"><b>Jenis Naskah:</b> ${qr.jenis_dokumen}</div>
                                <div class="col-md-6"><b>No. Dokumen:</b> ${qr.nomor_dokumen}</div>
                                <div class="col-md-6"><b>Waktu Pengesahan:</b> ${qr.signed_at}</div>
                            </div>
                        </div>
                    `;
                } else if (qr.status === 'dokumen_sultan_diedit_kembali') {
                    let diffList = (qr.diff_details || []).map(d => `<li>${d}</li>`).join('');
                    html += `
                        <div class="p-3 rounded bg-danger-subtle border border-danger mb-2.5 shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-danger text-white py-1.5 px-2.5" style="font-size: 11px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>PERINGATAN: DOKUMEN RESMI SULTAN TELAH DIEDIT / DIMANIPULASI
                                </span>
                                <code class="fw-bold text-danger fs-6">${qr.kode}</code>
                            </div>
                            <div class="p-2.5 bg-white rounded border border-danger mb-2 text-danger small">
                                <div class="fw-bold mb-1"><i class="fas fa-times-circle me-1"></i>Temuan Manipulasi Dokumen Resmi:</div>
                                <div class="mb-1.5" style="font-size: 11px;">${qr.pesan}</div>
                                ${diffList ? `<ul class="mb-0 ps-3 font-monospace text-dark" style="font-size: 10.5px;">${diffList}</ul>` : ''}
                            </div>
                            <div class="row g-2 small text-dark">
                                <div class="col-md-6"><b>Satker UPT Terdaftar:</b> ${qr.satker_upt}</div>
                                <div class="col-md-6"><b>Jenis Naskah Asli:</b> ${qr.jenis_dokumen}</div>
                                <div class="col-md-6"><b>No. Dokumen Asli:</b> ${qr.nomor_dokumen}</div>
                                <div class="col-md-6"><b>Waktu Pengesahan Asli:</b> ${qr.signed_at}</div>
                            </div>
                            <div class="alert alert-danger py-1.5 px-2 mt-2 mb-0 small" style="font-size: 11px;">
                                <i class="fas fa-ban me-1"></i><b>REKOMENDASI:</b> TOLAK DOKUMEN INI! Naskah ini memang memiliki cap QR resmi, namun telah disunting ulang atau halamannya dipotong setelah diterbitkan oleh Kanwil.
                            </div>
                        </div>
                    `;
                } else if (qr.status === 'qr_dicatut_berkas_dimanipulasi') {
                    let diffList = (qr.diff_details || []).map(d => `<li>${d}</li>`).join('');
                    html += `
                        <div class="p-3 rounded bg-danger-subtle border border-danger mb-2.5 shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-danger text-white py-1.5 px-2.5" style="font-size: 11px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>PERINGATAN KRITIS: PEMALSUAN DOKUMEN / QR RESMI DICATUT
                                </span>
                                <code class="fw-bold text-danger fs-6">${qr.kode}</code>
                            </div>
                            <div class="p-2.5 bg-white rounded border border-danger mb-2 text-danger small">
                                <div class="fw-bold mb-1"><i class="fas fa-times-circle me-1"></i>Hasil Komparasi Integritas dengan Master Resmi Server:</div>
                                <div class="mb-1.5" style="font-size: 11px;">${qr.pesan}</div>
                                ${diffList ? `<ul class="mb-0 ps-3 font-monospace text-dark" style="font-size: 10.5px;">${diffList}</ul>` : ''}
                            </div>
                            <div class="row g-2 small text-dark">
                                <div class="col-md-6"><b>Satker UPT Terdaftar:</b> ${qr.satker_upt}</div>
                                <div class="col-md-6"><b>Jenis Naskah Asli:</b> ${qr.jenis_dokumen}</div>
                                <div class="col-md-6"><b>No. Dokumen Asli:</b> ${qr.nomor_dokumen}</div>
                                <div class="col-md-6"><b>Waktu Pengesahan Asli:</b> ${qr.signed_at}</div>
                            </div>
                            <div class="alert alert-danger py-1.5 px-2 mt-2 mb-0 small" style="font-size: 11px;">
                                <i class="fas fa-ban me-1"></i><b>REKOMENDASI:</b> TOLAK DOKUMEN INI! Berkas ini terbukti merupakan hasil suntingan/rekayasa yang menempelkan cap QR resmi untuk mengelabui verifikator.
                            </div>
                        </div>
                    `;
                } else if (qr.status === 'valid_terdaftar') {
                    let diffList = (qr.diff_details || []).map(d => `<li>${d}</li>`).join('');
                    html += `
                        <div class="p-3 rounded bg-success-subtle border border-success-subtle mb-2.5 shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <span class="badge bg-success text-white py-1.5 px-2.5" style="font-size: 11px;">
                                    <i class="fas fa-check-circle me-1"></i>ASLI &amp; SAH TERDAFTAR DI DATABASE SULTAN BANTEN
                                </span>
                                <code class="fw-bold text-success fs-6">${qr.kode}</code>
                            </div>
                            <div class="p-2 bg-white rounded border mb-2 text-success small" style="font-size: 11px;">
                                <i class="fas fa-check-double me-1"></i>${qr.pesan}
                                ${diffList ? `<ul class="mb-0 ps-3 mt-1 font-monospace text-secondary" style="font-size: 10px;">${diffList}</ul>` : ''}
                            </div>
                            <div class="row g-2 small text-dark">
                                <div class="col-md-6"><b>Satker UPT:</b> ${qr.satker_upt}</div>
                                <div class="col-md-6"><b>Jenis Naskah:</b> ${qr.jenis_dokumen}</div>
                                <div class="col-md-6"><b>No. Dokumen:</b> ${qr.nomor_dokumen}</div>
                                <div class="col-md-6"><b>Waktu Pengesahan:</b> ${qr.signed_at}</div>
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

        // 3.5. Inspektur Editor Profile & Timeline
        const ed = data.editor_profile || {};
        const badgeEd = document.getElementById('badgeEditorStatus');
        const boxEdBanner = document.getElementById('boxEditorProfileBanner');
        const boxEdTimeline = document.getElementById('boxEditorTimeline');

        if (badgeEd) {
            badgeEd.className = `badge bg-${ed.badge_class || 'secondary'}-subtle text-${ed.badge_class || 'secondary'} border border-${ed.badge_class || 'secondary'}-subtle`;
            badgeEd.innerText = ed.software_name || 'Standar';
        }

        if (boxEdBanner) {
            const isDan = ed.danger_level === 'danger';
            const isWarn = ed.danger_level === 'warning';
            const bgClass = isDan ? 'bg-danger-subtle border-danger text-danger' : (isWarn ? 'bg-warning-subtle border-warning text-warning-emphasis' : 'bg-success-subtle border-success text-success');
            const icon = isDan ? 'fa-exclamation-triangle' : (isWarn ? 'fa-info-circle' : 'fa-check-circle');
            boxEdBanner.className = `p-2.5 rounded border mb-2.5 ${bgClass}`;
            boxEdBanner.innerHTML = `
                <div class="fw-bold d-flex align-items-center gap-1.5 mb-1" style="font-size: 11.5px;">
                    <i class="fas ${icon}"></i>
                    <span>Analisis Perangkat Lunak: <b>${ed.software_name || 'Standar'}</b></span>
                </div>
                <div class="small" style="font-size: 11px; line-height: 1.45;">
                    ${ed.description || '-'}
                </div>
            `;
        }

        if (boxEdTimeline) {
            const timeline = ed.timeline || [];
            if (timeline.length === 0) {
                boxEdTimeline.innerHTML = '<div class="list-group-item text-muted small py-2">Tidak ditemukan rekaman timeline revisi pada berkas.</div>';
            } else {
                let tHtml = '';
                timeline.forEach(t => {
                    tHtml += `
                        <div class="list-group-item py-2 px-2.5 d-flex align-items-start gap-2.5">
                            <span class="badge bg-secondary rounded-pill mt-0.5" style="font-size: 10px;">#${t.step}</span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark" style="font-size: 11.5px;">${t.action}</span>
                                    <span class="text-muted font-monospace" style="font-size: 10px;">${t.when}</span>
                                </div>
                                <div class="text-secondary small mt-0.5" style="font-size: 11px;">
                                    <b>Software:</b> <span class="text-primary font-monospace">${t.software}</span>
                                    ${t.changed ? ` &bull; <b>Bagian:</b> <code>${t.changed}</code>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                boxEdTimeline.innerHTML = tHtml;
            }
        }

        // 4. Metadata Teknis
        document.getElementById('txtPdfVersion').innerText = `Versi: ${fileInfo.version || '1.7'}`;
        const docTypeEl = document.getElementById('metaDocType');
        if (docTypeEl) {
            docTypeEl.innerText = fileInfo.doc_type_hint || (fileInfo.is_scanned ? 'Berkas Hasil Pemindaian (Scan Fisik)' : 'Dokumen Digital Asli (Teks)');
        }
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

    // Salin Teks Pemulihan ke Clipboard
    function copyRecoveredText() {
        const box = document.getElementById('boxRecoveredText');
        if (!box || !box.innerText) return;
        navigator.clipboard.writeText(box.innerText).then(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Teks Berhasil Disalin!',
                    text: 'Teks halaman yang dipulihkan telah disalin ke clipboard Anda.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Teks berhasil disalin ke clipboard!');
            }
        }).catch(() => {
            alert('Gagal menyalin teks ke clipboard. Silakan salin teks secara manual.');
        });
    }

    // Unduh Teks Pemulihan sebagai file .txt
    function downloadRecoveredText() {
        const box = document.getElementById('boxRecoveredText');
        if (!box || !box.innerText) return;
        const blob = new Blob([box.innerText], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'pemulihan_halaman_terhapus_forensik.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
</script>
@endsection

