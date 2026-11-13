@extends('layouts.app')

@section('title', 'Pembersih PDF - Sanitasi Naskah Srikandi & Sultan Banten')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- HEADER HALAMAN -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="rounded p-2 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-broom fa-lg"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Pembersih PDF (Sanitasi Naskah Kedinasan)</h5>
                    <small class="text-muted" style="font-size: 11.5px;">
                        Pembersih Jejak Digital, Riwayat Penyuntingan &amp; Penguncian Dokumen Resmi Sebelum Diunggah ke Srikandi atau SULTAN Banten
                    </small>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5" style="font-size: 11px;">
                <i class="fas fa-check-circle me-1"></i>Standar Srikandi &amp; SPBE
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5" style="font-size: 11px;">
                <i class="fas fa-lock me-1"></i>Anti-Edit AES-128
            </span>
        </div>
    </div>

    <!-- PANDUAN RINGKAS ARSIPARIS / OPERATOR -->
    <div class="alert alert-primary-subtle border border-primary-subtle py-2.5 px-3 rounded-3 shadow-xs mb-3 small" style="font-size: 11.5px; line-height: 1.5;">
        <div class="d-flex align-items-start gap-2">
            <i class="fas fa-info-circle text-primary fs-6 mt-0.5"></i>
            <div>
                <b>Tujuan &amp; Manfaat Sanitasi Naskah Kedinasan:</b>
                Gunakan alat utilitas ini pada naskah dinas (SK, Nota Dinas, Surat Tugas, Laporan) <b>sebelum diunggah ke Aplikasi SRIKANDI (Kearsipan Nasional) atau SULTAN Banten</b>.
                Fitur ini akan menghapus riwayat revisi (*revision history*), jejak laptop/komputer pribadi, serta mengunci dokumen dengan enkripsi AES-128 agar tidak dapat dimanipulasi atau dipotong halamannya oleh pihak luar. Fingerprint SHA-256 yang dihasilkan memudahkan pembuktian jika naskah Anda kelak dipalsukan.
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- FORM PEMBERSIH PDF -->
        <div class="col-lg-5 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark small" style="font-size: 12.5px;">
                        <i class="fas fa-upload text-primary me-1.5"></i>Unggah Naskah untuk Dibersihkan
                    </span>
                    <span class="badge bg-light text-secondary border" style="font-size: 10px;">PDF Maks 30MB</span>
                </div>
                <div class="card-body p-3">
                    <form id="formCleanPdf" enctype="multipart/form-data">
                        @csrf

                        <!-- AREA DRAG & DROP BERKAS -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">
                                Pilih Berkas PDF Naskah Kedinasan: <span class="text-danger">*</span>
                            </label>
                            <div class="rounded-3 p-3 text-center position-relative" id="cleanDropZone" style="cursor: pointer; border: 2px dashed #cbd5e1; background-color: #f8fafc; transition: all 0.2s ease-in-out;">
                                <i class="fas fa-file-pdf fa-3x text-primary mb-2 opacity-75"></i>
                                <div class="small fw-bold text-dark mb-1" id="cleanFileName">Pilih berkas atau seret berkas ke sini</div>
                                <div class="text-muted small" style="font-size: 10.5px;">Format berkas harus PDF (.pdf) &bull; Maksimal 30 MB</div>
                                <div class="mt-2" id="cleanFileAction" style="display: none;">
                                    <button type="button" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10.5px;" onclick="resetCleanFileInput(event)">
                                        <i class="fas fa-times me-1"></i>Ganti / Hapus Berkas
                                    </button>
                                </div>
                            </div>
                            <!-- Input berkas di luar dropzone -->
                            <input type="file" name="file_pdf" id="clean_file_pdf" class="d-none" accept=".pdf,application/pdf" onchange="handleCleanFileChange(this)">
                        </div>

                        <!-- ALERT VALIDASI FORM -->
                        <div id="cleanFormAlert" class="alert alert-danger py-2 px-2.5 small mb-3 d-none" style="font-size: 11px;"></div>

                        <!-- PENGATURAN STANDARISASI METADATA -->
                        <div class="border rounded-3 p-2.5 bg-light mb-3">
                            <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5" style="font-size: 11.5px;">
                                <i class="fas fa-sliders-h text-primary"></i>
                                <span>Standarisasi Metadata Resmi Naskah</span>
                            </div>

                            <div class="mb-2">
                                <label for="custom_title" class="form-label text-muted small mb-0.5" style="font-size: 11px;">Judul Naskah / Surat (Title):</label>
                                <input type="text" name="custom_title" id="custom_title" class="form-control form-control-sm" placeholder="Contoh: Surat Perintah Pelaksanaan Sidang TPP" maxlength="200">
                                <small class="text-muted" style="font-size: 10px;">*Kosongkan untuk menggunakan nama berkas otomatis</small>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label for="custom_author" class="form-label text-muted small mb-0.5" style="font-size: 11px;">Satuan Kerja / Asal Surat (Author):</label>
                                    <input type="text" name="custom_author" id="custom_author" class="form-control form-control-sm" value="{{ $defaultAuthor }}" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label for="custom_creator" class="form-label text-muted small mb-0.5" style="font-size: 11px;">Aplikasi Penerbit (Creator):</label>
                                    <input type="text" name="custom_creator" id="custom_creator" class="form-control form-control-sm" value="{{ $defaultCreator }}" maxlength="150">
                                </div>
                            </div>
                        </div>

                        <!-- OPSI KEAMANAN & PENGUNCIAN AES-128 -->
                        <div class="border rounded-3 p-2.5 bg-light mb-3">
                            <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5" style="font-size: 11.5px;">
                                <i class="fas fa-shield-alt text-success"></i>
                                <span>Proteksi &amp; Pencegahan Pemalsuan Naskah</span>
                            </div>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="lock_document" id="lock_document" value="1" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="lock_document" style="font-size: 11.5px;">
                                    Kunci Dokumen Anti-Edit (AES-128 Permissions Lock)
                                </label>
                                <div class="text-muted small" style="font-size: 10.5px; line-height: 1.35;">
                                    Mencegah berkas diubah, dipotong halamannya, atau diedit ulang di aplikasi pembajak (seperti Nitro/Acrobat). Dokumen tetap bisa dibuka dan dicetak normal tanpa sandi.
                                </div>
                            </div>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="allow_copy" id="allow_copy" value="1" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="allow_copy" style="font-size: 11.5px;">
                                    Izinkan Penyalinan Teks untuk Disposisi / Kearsipan
                                </label>
                                <div class="text-muted small" style="font-size: 10.5px; line-height: 1.35;">
                                    Memudahkan pejabat atau verifikator menyalin kutipan surat saat membuat lembar disposisi di Srikandi.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm py-2 d-flex align-items-center justify-content-center" id="btnStartClean">
                            <i class="fas fa-broom me-1.5"></i>Bersihkan &amp; Kunci Naskah Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- PANEL HASIL PEMBERSIHAN & BUKTI TRACKING -->
        <div class="col-lg-7 col-md-6">

            <!-- STATE 1: BELUM DIPROSES (EMPTY STATE) -->
            <div id="cleanEmptyState" class="card border-0 shadow-sm text-center p-5 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 480px; background-color: #fbfbfc;">
                <div class="rounded-circle bg-primary-subtle text-primary p-3 mb-3 d-inline-block shadow-sm">
                    <i class="fas fa-broom fa-3x"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hasil Pembersihan &amp; Bukti Tracking Belum Ada</h6>
                <p class="text-muted small mb-0" style="max-width: 440px; font-size: 11.5px; line-height: 1.5;">
                    Silakan unggah naskah dinas atau surat keputusan pada panel sebelah kiri. Sistem akan membuang seluruh histori revisi, jejak identitas lokal, serta menghasilkan berkas standar bersih yang siap diunggah ke SRIKANDI atau SULTAN Banten lengkap dengan tanda terima kode Hash SHA-256.
                </p>
            </div>

            <!-- STATE 2: LOADING SPINNER -->
            <div id="cleanLoadingState" class="card border-0 shadow-sm text-center p-5 h-100 d-none flex-column align-items-center justify-content-center" style="min-height: 480px;">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                <h6 class="fw-bold text-dark mb-1">Sedang Membersihkan Jejak Digital &amp; Menstandarisasi Naskah...</h6>
                <p class="text-muted small mb-0" style="font-size: 11.5px;">
                    Merekristalisasi struktur biner PDF, menghapus histori revisi, menerapkan enkripsi izin AES-128, dan menghitung SHA-256 fingerprint. Harap tunggu...
                </p>
            </div>

            <!-- STATE 3: HASIL LENGKAP PEMBERSIHAN -->
            <div id="cleanResultContainer" class="d-none d-flex flex-column gap-3">

                <!-- KARTU 1: STATUS BERSIH & TOMBOL UNDUH UTAMA -->
                <div class="card border-0 shadow-sm overflow-hidden border-top border-4 border-success">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2 pb-2 border-bottom">
                            <div>
                                <span class="badge bg-success py-1.5 px-2.5 text-white" style="font-size: 11px;">
                                    <i class="fas fa-check-double me-1"></i>NASKAH BERSIH &amp; TERSTANDARISASI
                                </span>
                                <h5 class="fw-bold text-dark mt-1.5 mb-0" id="resDocTitle">Naskah Resmi Kedinasan</h5>
                            </div>
                            <div class="text-sm-end">
                                <span class="badge bg-light text-secondary border font-monospace" id="resDocPages">1 Halaman</span>
                                <div class="text-muted small mt-0.5" style="font-size: 10.5px;" id="resDocSize">0 MB</div>
                            </div>
                        </div>

                        <!-- TOMBOL UNDUH BESAR -->
                        <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                            <a href="#" id="btnDownloadClean" class="btn btn-success fw-bold py-2.5 px-3 flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-file-download fa-lg"></i>
                                <span>Unduh Berkas PDF Bersih</span>
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-2 px-3 fw-semibold" onclick="resetAllCleanForm()">
                                <i class="fas fa-redo me-1"></i>Bersihkan Berkas Lain
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KARTU 2: DIGITAL FINGERPRINT (SHA-256 HASH) UNTUK TRACKING -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark small" style="font-size: 12px;">
                            <i class="fas fa-fingerprint text-primary me-1.5"></i>Fingerprint Digital Kriptografis (SHA-256 Hash)
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">ID Pelacakan Otentik</span>
                    </div>
                    <div class="card-body p-3">
                        <p class="small text-secondary mb-2" style="font-size: 11px; line-height: 1.45;">
                            Kode hash di bawah ini adalah <b>sidik jari matematis mutlak</b> berkas bersih Anda. Simpan atau catat hash ini sebelum mengunggah berkas ke <b>SRIKANDI</b> atau <b>SULTAN Banten</b>. Jika suatu saat ada pihak luar yang memalsukan atau mengubah isi berkas ini, Anda dapat membuktikan ketidakaslian mereka karena kode hash berkas palsu tersebut akan berbeda.
                        </p>
                        <div class="d-flex align-items-center gap-2 p-2 rounded bg-light border border-secondary-subtle">
                            <code class="font-monospace text-dark text-break fw-bold small flex-grow-1" id="resCleanHash" style="font-size: 11px; user-select: all;">-</code>
                            <button type="button" class="btn btn-outline-primary btn-sm px-2.5 py-1 flex-shrink-0" onclick="copyCleanHash()">
                                <i class="fas fa-copy me-1"></i>Salin Hash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KARTU 3: RINCIAN KOMPARASI SEBELUM VS SESUDAH -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark small" style="font-size: 12px;">
                            <i class="fas fa-history text-info me-1.5"></i>Audit Komparasi Sanitasi (Sebelum vs Sesudah)
                        </span>
                        <span class="badge bg-light text-secondary border" style="font-size: 10px;">Audit Log</span>
                    </div>
                    <div class="card-body p-3 small">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 align-middle">
                                <thead class="table-light small">
                                    <tr>
                                        <th style="width: 28%;">Atribut Dokumen</th>
                                        <th style="width: 36%;">Sebelum Dibersihkan (Asli)</th>
                                        <th style="width: 36%;" class="text-success">Sesudah Dibersihkan (Standar Resmi)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted fw-semibold">Software Pembuat (Creator)</td>
                                        <td id="cmpOrigCreator" class="text-danger">-</td>
                                        <td id="cmpCleanCreator" class="text-success fw-bold">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Mesin PDF (Producer)</td>
                                        <td id="cmpOrigProducer" class="text-secondary">-</td>
                                        <td id="cmpCleanProducer" class="text-success fw-semibold">SULTAN PDF Sanitizer Engine</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Penulis / Satker (Author)</td>
                                        <td id="cmpOrigAuthor" class="text-secondary">-</td>
                                        <td id="cmpCleanAuthor" class="text-dark fw-bold">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Status Penguncian Izin</td>
                                        <td class="text-danger"><i class="fas fa-times-circle me-1"></i>Tidak Terkunci (Mudah diedit)</td>
                                        <td id="cmpCleanLock" class="text-success fw-bold"><i class="fas fa-lock me-1"></i>Terkunci AES-128 Bit</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Waktu Sanitasi Dokumen</td>
                                        <td class="text-muted" id="cmpOrigDate">-</td>
                                        <td class="text-dark font-monospace" id="cmpCleanDate" style="font-size: 10.5px;">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- CHECKLIST HASIL PEMBERSIHAN -->
                        <div class="mt-3 p-2.5 rounded bg-success-subtle border border-success-subtle">
                            <div class="fw-bold text-success small mb-1.5" style="font-size: 11.5px;">
                                <i class="fas fa-clipboard-check me-1"></i>Tindakan Sanitasi yang Telah Selesai Diterapkan:
                            </div>
                            <ul class="mb-0 ps-3 small text-dark" id="listSanitizedItems" style="font-size: 11px; line-height: 1.45;"></ul>
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
    function resetCleanFileInput(e) {
        if (e) {
            e.stopPropagation();
        }
        const fileInput = document.getElementById('clean_file_pdf');
        if (fileInput) fileInput.value = '';
        document.getElementById('cleanFileName').innerHTML = 'Pilih berkas atau seret berkas ke sini';
        const actionBox = document.getElementById('cleanFileAction');
        if (actionBox) actionBox.style.display = 'none';
        const alertBox = document.getElementById('cleanFormAlert');
        if (alertBox) alertBox.classList.add('d-none');
    }

    // Tangani Perubahan Pilihan Berkas
    function handleCleanFileChange(input) {
        const alertBox = document.getElementById('cleanFormAlert');
        if (alertBox) alertBox.classList.add('d-none');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            const ext = file.name.split('.').pop().toLowerCase();

            // Validasi format ekstensi .pdf
            if (ext !== 'pdf') {
                const msg = 'Format berkas tidak didukung. Harap pilih berkas berekstensi .pdf!';
                if (alertBox) {
                    alertBox.innerText = msg;
                    alertBox.classList.remove('d-none');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Format Tidak Sesuai', text: msg, confirmButtonColor: '#0d6efd' });
                } else {
                    alert(msg);
                }
                resetCleanFileInput();
                return;
            }

            // Validasi ukuran berkas (30 MB)
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            if (file.size > 30 * 1024 * 1024) {
                const msg = `Ukuran berkas (${sizeMb} MB) melebihi batas maksimal 30 MB.`;
                if (alertBox) {
                    alertBox.innerText = msg;
                    alertBox.classList.remove('d-none');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Ukuran Berkas Terlalu Besar', text: msg, confirmButtonColor: '#0d6efd' });
                } else {
                    alert(msg);
                }
                resetCleanFileInput();
                return;
            }

            document.getElementById('cleanFileName').innerHTML = `<span class="text-primary fw-bold">${file.name}</span> <span class="badge bg-secondary ms-1">${sizeMb} MB</span>`;
            const actionBox = document.getElementById('cleanFileAction');
            if (actionBox) actionBox.style.display = 'block';

            // Otomatis isi judul jika masih kosong
            const titleInput = document.getElementById('custom_title');
            if (titleInput && !titleInput.value) {
                const baseName = file.name.replace(/\.[^/.]+$/, "").replace(/[_-]/g, ' ');
                titleInput.value = baseName;
            }
        }
    }

    // Inisialisasi Drag & Drop pada Dropzone
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('cleanDropZone');
        const fileInput = document.getElementById('clean_file_pdf');

        if (dropZone && fileInput) {
            dropZone.addEventListener('click', function(e) {
                if (e.target.closest('#cleanFileAction')) return;
                fileInput.click();
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '#0d6efd';
                    dropZone.style.backgroundColor = '#e7f1ff';
                }, false);
            });

            ['dragleave', 'dragend'].forEach(eventName => {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '#cbd5e1';
                    dropZone.style.backgroundColor = '#f8fafc';
                }, false);
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#cbd5e1';
                dropZone.style.backgroundColor = '#f8fafc';

                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    handleCleanFileChange(fileInput);
                }
            }, false);
        }
    });

    // Form Submit Handler via AJAX
    document.getElementById('formCleanPdf')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('clean_file_pdf');
        const alertBox = document.getElementById('cleanFormAlert');
        if (alertBox) alertBox.classList.add('d-none');

        if (!fileInput.files || fileInput.files.length === 0) {
            const msg = 'Silakan pilih berkas PDF naskah kedinasan yang ingin dibersihkan.';
            if (alertBox) {
                alertBox.innerText = msg;
                alertBox.classList.remove('d-none');
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Berkas Belum Dipilih', text: msg, confirmButtonColor: '#0d6efd' });
            } else {
                alert(msg);
            }
            return;
        }

        const formData = new FormData(this);
        const btn = document.getElementById('btnStartClean');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sedang Membersihkan...';

        document.getElementById('cleanEmptyState')?.classList.add('d-none');
        document.getElementById('cleanResultContainer')?.classList.add('d-none');
        document.getElementById('cleanLoadingState')?.classList.remove('d-none');
        document.getElementById('cleanLoadingState')?.classList.add('d-flex');

        fetch("{{ route('pembersih-pdf.clean') }}", {
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
                let errorMsg = 'Gagal melakukan sanitasi berkas.';
                if (resData && resData.message) {
                    errorMsg = resData.message;
                } else if (resData && resData.errors && resData.errors.file_pdf) {
                    errorMsg = resData.errors.file_pdf[0];
                } else if (response.status === 413) {
                    errorMsg = 'Ukuran berkas melebihi batas muatan server (HTTP 413 Payload Too Large).';
                } else if (response.status === 419) {
                    errorMsg = 'Sesi Anda telah kedaluwarsa (CSRF expired). Silakan muat ulang (refresh) halaman.';
                } else {
                    errorMsg = `Terjadi kesalahan pada server (Status HTTP ${response.status}: ${response.statusText || 'Error'}).`;
                }
                throw new Error(errorMsg);
            }

            if (!resData || !resData.success) {
                throw new Error(resData?.message || 'Gagal membersihkan berkas.');
            }

            return resData;
        })
        .then(res => {
            document.getElementById('cleanLoadingState')?.classList.add('d-none');
            document.getElementById('cleanLoadingState')?.classList.remove('d-flex');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-broom me-1.5"></i>Bersihkan &amp; Kunci Naskah Sekarang';

            renderCleanResult(res.data, res.download_url, res.download_name);
            document.getElementById('cleanResultContainer')?.classList.remove('d-none');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Pembersihan Berhasil!',
                    text: 'Naskah PDF telah dibersihkan dari jejak pribadi dan siap diunggah ke Srikandi / Sultan Banten.',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        })
        .catch(err => {
            document.getElementById('cleanLoadingState')?.classList.add('d-none');
            document.getElementById('cleanLoadingState')?.classList.remove('d-flex');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-broom me-1.5"></i>Bersihkan &amp; Kunci Naskah Sekarang';
            document.getElementById('cleanEmptyState')?.classList.remove('d-none');

            if (alertBox) {
                alertBox.innerText = err.message;
                alertBox.classList.remove('d-none');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memproses Sanitasi',
                    text: err.message,
                    confirmButtonColor: '#0d6efd'
                });
            } else {
                alert('Gagal Memproses Sanitasi:\n' + err.message);
            }
        });
    });

    // Render Data Hasil Sanitasi ke Tampilan
    function renderCleanResult(data, downloadUrl, downloadName) {
        const cleaned = data.cleaned || {};
        const orig = data.original || {};

        document.getElementById('resDocTitle').innerText = cleaned.title || 'Naskah Resmi Kedinasan';
        document.getElementById('resDocPages').innerText = `${cleaned.page_count || 1} Halaman`;
        document.getElementById('resDocSize').innerText = cleaned.file_size_formatted || '-';

        const btnDown = document.getElementById('btnDownloadClean');
        if (btnDown) {
            btnDown.href = downloadUrl;
            btnDown.setAttribute('download', downloadName);
        }

        document.getElementById('resCleanHash').innerText = cleaned.sha256 || '-';

        // Komparasi Tabel
        document.getElementById('cmpOrigCreator').innerText = orig.creator || 'Tidak Terdeteksi';
        document.getElementById('cmpCleanCreator').innerText = cleaned.creator || 'Aplikasi SULTAN Banten';
        document.getElementById('cmpOrigProducer').innerText = orig.producer || 'Tidak Terdeteksi';
        document.getElementById('cmpOrigAuthor').innerText = orig.author || 'Tidak Terdeteksi';
        document.getElementById('cmpCleanAuthor').innerText = cleaned.author || 'Kantor Wilayah Ditjenpas Banten';
        document.getElementById('cmpOrigDate').innerText = orig.creation_date !== '-' ? orig.creation_date : (orig.mod_date !== '-' ? orig.mod_date : '-');
        document.getElementById('cmpCleanDate').innerText = cleaned.sanitized_at || '-';

        const lockCell = document.getElementById('cmpCleanLock');
        if (lockCell) {
            if (cleaned.is_locked) {
                lockCell.innerHTML = '<i class="fas fa-lock text-success me-1"></i>Terkunci AES-128 Bit (Anti-Edit)';
                lockCell.className = 'text-success fw-bold';
            } else {
                lockCell.innerHTML = '<i class="fas fa-unlock text-secondary me-1"></i>Mode Terbuka (Tidak Dikunci)';
                lockCell.className = 'text-secondary fw-semibold';
            }
        }

        // Checklist Tindakan
        const listItems = document.getElementById('listSanitizedItems');
        if (listItems) {
            listItems.innerHTML = '';
            (data.sanitized_items || []).forEach(item => {
                const li = document.createElement('li');
                li.innerText = item;
                listItems.appendChild(li);
            });
        }
    }

    // Salin Kode Hash SHA-256 ke Clipboard
    function copyCleanHash() {
        const hashEl = document.getElementById('resCleanHash');
        if (!hashEl || !hashEl.innerText || hashEl.innerText === '-') return;

        navigator.clipboard.writeText(hashEl.innerText).then(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Kode Hash Disalin!',
                    text: 'Sidik jari SHA-256 telah disalin ke clipboard Anda untuk kebutuhan tracking kearsipan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Kode Hash SHA-256 berhasil disalin!');
            }
        }).catch(() => {
            alert('Gagal menyalin hash. Silakan salin secara manual.');
        });
    }

    // Reset Form untuk berkas baru
    function resetAllCleanForm() {
        resetCleanFileInput();
        document.getElementById('cleanResultContainer')?.classList.add('d-none');
        document.getElementById('cleanEmptyState')?.classList.remove('d-none');
    }
</script>
@endsection

