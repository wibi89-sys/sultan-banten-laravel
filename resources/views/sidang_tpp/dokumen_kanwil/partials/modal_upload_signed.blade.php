<!-- Modal Upload Berkas Bertanda Tangan Basah (Scan PDF) & Pembubuhan QR Manual Mirip e-Meterai -->
<div class="modal fade" id="modalUploadSigned" tabindex="-1" aria-labelledby="modalUploadSignedTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw;">
        <div class="modal-content border-0 shadow-lg" style="max-height: 94vh; display: flex; flex-direction: column;">
            <form id="formUploadSigned" method="POST" enctype="multipart/form-data" class="d-flex flex-column h-100 mb-0">
                @csrf
                <input type="hidden" name="jenis_dokumen" id="up_jenis_dokumen" value="laporan_tpp">
                <input type="hidden" name="qr_percent_x" id="up_qr_percent_x" value="6">
                <input type="hidden" name="qr_percent_y" id="up_qr_percent_y" value="86">

                <div class="modal-header bg-dark text-white py-2.5 px-3 flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-stamp text-warning fa-lg"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalUploadSignedTitle" style="font-size: 14.5px;">
                                Pembubuhan Cap QR Otentifikasi Dokumen (Pratinjau Presisi e-Meterai)
                            </h5>
                            <small class="text-white-50" style="font-size: 11px;">
                                Unggah berkas PDF asli, pratinjau lembar naskah nyata, dan geser cap QR langsung ke titik yang diinginkan
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3 overflow-auto flex-grow-1" style="background-color: #f8fafc;">
                    <div class="row g-3 h-100">
                        <!-- KOLOM KIRI: KONTROL, METADATA & PENGATURAN CAP (4 Kolom) -->
                        <div class="col-lg-4 col-md-5 d-flex flex-column gap-2.5">
                            <!-- Kartu Info Dokumen & Input Berkas -->
                            <div class="card border border-secondary-subtle shadow-sm">
                                <div class="card-header bg-white py-2 px-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-dark text-uppercase" style="font-size: 11px;">
                                            <i class="fas fa-file-alt text-primary me-1"></i>1. Berkas Naskah PDF
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="up_jenis_badge" style="font-size: 10px;">
                                            Naskah Kanwil
                                        </span>
                                    </div>
                                    <div class="fw-bold text-dark text-truncate mt-1" id="up_jenis_label" style="font-size: 12.5px;">
                                        Laporan Pelaksanaan Sidang TPP Kanwil
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-2.5">
                                        <label for="up_nomor_dokumen" class="form-label small fw-semibold mb-1" style="font-size: 11.5px;">
                                            Nomor Surat / Naskah Final:
                                        </label>
                                        <input type="text" name="nomor_dokumen" id="up_nomor_dokumen" class="form-control form-control-sm font-monospace" placeholder="Contoh: W.12.PAS.UM.01.01-..." style="font-size: 12px;">
                                    </div>

                                    <div>
                                        <label for="file_signed" class="form-label small fw-semibold mb-1" style="font-size: 11.5px;">
                                            Pilih Berkas PDF Scan TTD: <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" name="file_signed" id="file_signed" class="form-control form-control-sm" accept=".pdf,application/pdf" required onchange="handleFileSignedSelected(this)">
                                        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 10.5px;">
                                            <span class="text-muted" id="pdfFileInfo">Format: PDF (Maks. 20 MB)</span>
                                            <span class="badge bg-light text-secondary border d-none" id="pdfPageBadge">0 Hal</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kartu Pengaturan Pembubuhan Cap QR -->
                            <div class="card border border-primary-subtle shadow-sm flex-grow-1">
                                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="bubuhi_qr" value="0">
                                        <input class="form-check-input" type="checkbox" name="bubuhi_qr" id="up_bubuhi_qr" value="1" checked onchange="toggleStampingPanel(this.checked)">
                                        <label class="form-check-label fw-bold text-dark small" for="up_bubuhi_qr" style="font-size: 11.5px;">
                                            <i class="fas fa-qrcode text-primary me-1"></i>Bubuhkan Cap QR Otentifikasi
                                        </label>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">
                                        <i class="fas fa-check-circle me-1"></i>Aktif
                                    </span>
                                </div>

                                <div class="card-body p-3" id="panelStampingQr">
                                    <!-- Pilihan Target Halaman & Model -->
                                    <div class="row g-2 mb-2.5">
                                        <div class="col-7">
                                            <label class="form-label text-muted mb-1" style="font-size: 11px;">Halaman Target Cap:</label>
                                            <select name="qr_target_page" id="up_qr_target_page" class="form-select form-select-sm" style="font-size: 11.5px;" onchange="handleTargetPageChange(this.value)">
                                                <option value="last" selected>Halaman Terakhir (Tempat TTD)</option>
                                                <option value="first">Halaman Pertama</option>
                                                <option value="all">Semua Halaman</option>
                                            </select>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label text-muted mb-1" style="font-size: 11px;">Model Cap:</label>
                                            <select name="qr_style" id="up_qr_style" class="form-select form-select-sm" style="font-size: 11.5px;" onchange="updateStampModelPreview(this.value)">
                                                <option value="badge" selected>Badge e-Meterai</option>
                                                <option value="qr_only">QR Code Bersih</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Tombol Preset Cepat -->
                                    <label class="form-label text-muted mb-1 d-block" style="font-size: 11px;">
                                        Pilihan Posisi Cepat (Preset):
                                    </label>
                                    <div class="d-flex flex-wrap gap-1.5 mb-2.5">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(6, 88, 'Kiri Bawah')">
                                            <i class="fas fa-arrow-down me-1"></i>Kiri Bawah
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(42, 75, 'Samping TTD')">
                                            <i class="fas fa-signature me-1"></i>Samping TTD
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(64, 88, 'Kanan Bawah')">
                                            <i class="fas fa-arrow-down me-1"></i>Kanan Bawah
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(6, 6, 'Kiri Atas')">
                                            <i class="fas fa-arrow-up me-1"></i>Kiri Atas
                                        </button>
                                    </div>

                                    <!-- Tombol Geser Presisi (D-PAD Micro Adjustment) -->
                                    <div class="p-2 rounded bg-light border mb-2.5">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <span class="small fw-bold text-dark" style="font-size: 11px;">
                                                <i class="fas fa-arrows-alt text-primary me-1"></i>Geser Presisi (Mikro 1%):
                                            </span>
                                            <small class="text-muted" style="font-size: 10px;">Atau klik/tarik langsung di kertas</small>
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2" onclick="nudgeStamp(-1, 0)" title="Geser Kiri (1%)">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <div class="d-flex flex-column gap-1">
                                                <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" onclick="nudgeStamp(0, -1)" title="Geser Atas (1%)">
                                                    <i class="fas fa-chevron-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" onclick="nudgeStamp(0, 1)" title="Geser Bawah (1%)">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2" onclick="nudgeStamp(1, 0)" title="Geser Kanan (1%)">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Pengatur Transparansi Preview (Agar teks di bawahnya tetap terbaca) -->
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label text-muted mb-0" style="font-size: 11px;">
                                                Transparansi Cap Pratinjau:
                                            </label>
                                            <span class="badge bg-light text-dark border" id="opacityValueBadge" style="font-size: 10px;">85%</span>
                                        </div>
                                        <input type="range" class="form-range" id="stampOpacityRange" min="40" max="100" value="85" oninput="updateStampOpacity(this.value)">
                                    </div>

                                    <!-- Indikator Koordinat Real-time -->
                                    <div class="alert alert-secondary py-1.5 px-2 mb-0 d-flex align-items-center justify-content-between" style="font-size: 11px;">
                                        <div>
                                            <i class="fas fa-crosshairs text-danger me-1"></i>
                                            <b id="stampCoordText">X: 6%, Y: 86%</b>
                                        </div>
                                        <span class="badge bg-white text-primary border" id="stampPageStatus">Hal. TTD</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: WORKSPACE DOKUMEN PDF ASLI (INTERACTIVE VIEWER) (8 Kolom) -->
                        <div class="col-lg-8 col-md-7 d-flex flex-column h-100">
                            <div class="card border border-secondary-subtle shadow-sm d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 480px; max-height: calc(90vh - 120px);">
                                <!-- Toolbar PDF Viewer -->
                                <div class="card-header bg-dark text-white py-1.5 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2 flex-shrink-0">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <span class="small fw-semibold text-white me-1" style="font-size: 11px;">
                                            <i class="fas fa-eye text-warning me-1"></i>Pratinjau Lembar Naskah:
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-light btn-xs py-0.5 px-2" id="btnPrevPage" onclick="changePdfPage(-1)" title="Halaman Sebelumnya" disabled>
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <span class="btn btn-outline-light btn-xs py-0.5 px-2 disabled text-white fw-bold" id="pageNumberDisplay" style="font-size: 11px; min-width: 90px;">
                                                Hal 0 / 0
                                            </span>
                                            <button type="button" class="btn btn-outline-light btn-xs py-0.5 px-2" id="btnNextPage" onclick="changePdfPage(1)" title="Halaman Selanjutnya" disabled>
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-warning btn-xs py-0.5 px-2 fw-semibold text-dark d-none" id="btnJumpLastPage" onclick="jumpToLastPage()" title="Langsung Lompat ke Lembar TTD">
                                            <i class="fas fa-signature me-1"></i>Halaman TTD
                                        </button>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-light btn-xs py-0.5 px-2" onclick="zoomPdf(-0.15)" title="Perkecil Zoom">
                                                <i class="fas fa-search-minus"></i>
                                            </button>
                                            <span class="btn btn-outline-light btn-xs py-0.5 px-2 disabled text-white" id="zoomLevelDisplay" style="font-size: 10.5px;">100%</span>
                                            <button type="button" class="btn btn-outline-light btn-xs py-0.5 px-2" onclick="zoomPdf(0.15)" title="Perbesar Zoom">
                                                <i class="fas fa-search-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-light btn-xs py-0.5 px-2" onclick="resetZoomPdf()" title="Sesuaikan Lebar">
                                                <i class="fas fa-expand-arrows-alt"></i> Fit
                                            </button>
                                        </div>
                                        <span class="badge bg-success" id="stampPageMatchBadge" style="font-size: 10px;">
                                            <i class="fas fa-stamp me-1"></i>Cap di Halaman Ini
                                        </span>
                                    </div>
                                </div>

                                <!-- Viewport Scrollable untuk Canvas PDF & Draggable Stamp -->
                                <div class="card-body p-3 overflow-auto flex-grow-1 text-center" id="pdfScrollContainer" style="background-color: #3e444a; min-height: 420px; position: relative;">
                                    
                                    <!-- STATE 1: Belum Ada Berkas Terpilih -->
                                    <div id="pdfPlaceholderNotice" class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-white-50">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-dark p-3 d-inline-block shadow">
                                                <i class="fas fa-file-pdf fa-3x text-danger opacity-75"></i>
                                            </div>
                                        </div>
                                        <h6 class="fw-bold text-white mb-1">Pratinjau Lembar Naskah PDF Belum Tersedia</h6>
                                        <p class="small text-white-50 mb-3" style="max-width: 420px;">
                                            Silakan klik tombol <b>Pilih Berkas PDF</b> di panel kiri. Sistem akan langsung merender naskah asli Anda secara utuh di sini agar Anda dapat membubuhkan cap QR otentifikasi persis di atas atau di samping tanda tangan.
                                        </p>
                                        <label for="file_signed" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
                                            <i class="fas fa-folder-open me-1.5"></i>Pilih Berkas PDF Scan Sekarang
                                        </label>
                                    </div>

                                    <!-- STATE 2: Sedang Merender PDF -->
                                    <div id="pdfLoadingSpinner" class="d-none flex-column align-items-center justify-content-center h-100 py-5 text-white">
                                        <div class="spinner-border text-warning mb-2" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                                        <div class="fw-semibold small">Memuat dan merender halaman naskah...</div>
                                        <div class="text-white-50 small" style="font-size: 11px;">Harap tunggu sebentar</div>
                                    </div>

                                    <!-- STATE 3: Workspace Aktif (Wrapper Canvas & Draggable Stamp Badge) -->
                                    <div id="pdfViewerWrapper" class="d-none" style="position: relative; display: inline-block; margin: 0 auto; box-shadow: 0 8px 30px rgba(0,0,0,0.5); background-color: #ffffff; border-radius: 3px; user-select: none;">
                                        <!-- Canvas Render Halaman PDF Real -->
                                        <canvas id="pdfPageCanvas" style="display: block; cursor: crosshair;" onclick="handleCanvasClick(event)"></canvas>

                                        <!-- DRAGGABLE STAMP BADGE (Mewakili Cap QR e-Meterai Sultan Banten) -->
                                        <div id="stampBadgeItem" style="position: absolute; left: 20px; top: 200px; width: 170px; height: 50px; background: rgba(15, 71, 97, 0.88); color: #ffffff; border: 1.5px solid #0F4761; border-radius: 4px; display: flex; align-items: center; padding: 4px 6px; cursor: move; box-shadow: 0 4px 14px rgba(0,0,0,0.45); z-index: 20; touch-action: none; transition: box-shadow 0.15s ease;" title="Geser cap ini ke tempat yang diinginkan">
                                            <!-- Aksen Samping -->
                                            <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #f59e0b; border-top-left-radius: 3px; border-bottom-left-radius: 3px;"></div>
                                            
                                            <!-- Icon QR Barcode -->
                                            <div id="stampQrIconBox" class="d-flex align-items-center justify-content-center bg-white rounded p-1 me-2 shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;">
                                                <i class="fas fa-qrcode text-dark fa-lg" style="font-size: 24px;"></i>
                                            </div>

                                            <!-- Teks Otentifikasi Sultan Banten -->
                                            <div id="stampBadgeText" class="text-start overflow-hidden" style="line-height: 1.15; flex-grow: 1;">
                                                <div style="font-size: 8px; font-weight: 800; letter-spacing: 0.4px; color: #f59e0b;">TEROTENTIFIKASI RESMI</div>
                                                <div style="font-size: 7px; font-weight: 700; color: #ffffff;">SULTAN DITJENPAS BANTEN</div>
                                                <div style="font-size: 6.5px; opacity: 0.9; color: #e2e8f0; font-family: monospace;" id="badgeKodePreview">Kode: SB-VERIFIKASI</div>
                                                <div style="font-size: 5.5px; opacity: 0.75; font-style: italic; color: #cbd5e1;">Pindai untuk validasi naskah</div>
                                            </div>

                                            <!-- Tombol Bantu Drag Handle -->
                                            <div style="position: absolute; top: -8px; right: -8px; background: #f59e0b; color: #000; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                                                <i class="fas fa-arrows-alt"></i>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 justify-content-between flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted" style="font-size: 11px;">
                            <i class="fas fa-info-circle text-primary me-1"></i>
                            Cap QR akan dibubuhkan langsung pada file PDF asli pada koordinat yang Anda tentukan di atas.
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold" id="btnSubmitUploadSigned">
                            <i class="fas fa-stamp me-1.5"></i>Simpan &amp; Bubuhkan QR
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Muat PDF.js dari direktori lokal vendor, dengan fallback ke CDN jika diperlukan -->
<script src="{{ asset('assets/vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    if (typeof pdfjsLib === 'undefined') {
        document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"><\/script>');
    }
</script>

<script>
    // Konfigurasi Worker PDF.js
    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('assets/vendor/pdfjs/pdf.worker.min.js') }}";
    }

    // State Pratinjau PDF & Dragging
    let currentPdfDoc = null;
    let currentPdfPage = 1;
    let totalPdfPages = 0;
    let currentPdfZoom = 1.0;
    let isRenderingPdf = false;
    let pendingRenderPage = null;

    let isDraggingStamp = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let stampInitialLeft = 0;
    let stampInitialTop = 0;

    // Target halaman cap yang dipilih ('last', 'first', 'all', atau nomor halaman)
    let stampTargetPage = 'last';

    function initStampDraggable() {
        const badge = document.getElementById('stampBadgeItem');
        if (!badge) return;

        badge.addEventListener('mousedown', function(e) {
            e.stopPropagation();
            e.preventDefault();
            isDraggingStamp = true;
            dragStartX = e.clientX;
            dragStartY = e.clientY;
            stampInitialLeft = badge.offsetLeft;
            stampInitialTop = badge.offsetTop;

            document.addEventListener('mousemove', onStampMouseMove);
            document.addEventListener('mouseup', onStampMouseUp);
        });

        // Touch support (Tablet / Smartphone)
        badge.addEventListener('touchstart', function(e) {
            e.stopPropagation();
            if (e.touches.length === 1) {
                isDraggingStamp = true;
                dragStartX = e.touches[0].clientX;
                dragStartY = e.touches[0].clientY;
                stampInitialLeft = badge.offsetLeft;
                stampInitialTop = badge.offsetTop;

                document.addEventListener('touchmove', onStampTouchMove, { passive: false });
                document.addEventListener('touchend', onStampTouchEnd);
            }
        });
    }

    function onStampMouseMove(e) {
        if (!isDraggingStamp) return;
        const deltaX = e.clientX - dragStartX;
        const deltaY = e.clientY - dragStartY;
        moveStampBadgeTo(stampInitialLeft + deltaX, stampInitialTop + deltaY);
    }

    function onStampMouseUp() {
        isDraggingStamp = false;
        document.removeEventListener('mousemove', onStampMouseMove);
        document.removeEventListener('mouseup', onStampMouseUp);
    }

    function onStampTouchMove(e) {
        if (!isDraggingStamp || e.touches.length !== 1) return;
        e.preventDefault();
        const deltaX = e.touches[0].clientX - dragStartX;
        const deltaY = e.touches[0].clientY - dragStartY;
        moveStampBadgeTo(stampInitialLeft + deltaX, stampInitialTop + deltaY);
    }

    function onStampTouchEnd() {
        isDraggingStamp = false;
        document.removeEventListener('touchmove', onStampTouchMove);
        document.removeEventListener('touchend', onStampTouchEnd);
    }

    function getCanvasDimensions() {
        const canvas = document.getElementById('pdfPageCanvas');
        const w = (canvas && canvas.clientWidth > 50) ? canvas.clientWidth : 600;
        const h = (canvas && canvas.clientHeight > 50) ? canvas.clientHeight : 848;
        return { w, h };
    }

    function moveStampBadgeTo(targetLeft, targetTop) {
        const badge = document.getElementById('stampBadgeItem');
        if (!badge) return;

        const dim = getCanvasDimensions();
        const badgeW = badge.offsetWidth || 170;
        const badgeH = badge.offsetHeight || 50;

        const maxLeft = Math.max(0, dim.w - badgeW);
        const maxTop = Math.max(0, dim.h - badgeH);

        const clampedLeft = Math.max(0, Math.min(maxLeft, targetLeft));
        const clampedTop = Math.max(0, Math.min(maxTop, targetTop));

        badge.style.left = clampedLeft + 'px';
        badge.style.top = clampedTop + 'px';

        // Hitung persentase presisi (1 angka di belakang koma)
        let percentX = Math.round((clampedLeft / dim.w) * 1000) / 10;
        let percentY = Math.round((clampedTop / dim.h) * 1000) / 10;

        if (!isFinite(percentX) || isNaN(percentX) || percentX < 0) percentX = 6.0;
        if (!isFinite(percentY) || isNaN(percentY) || percentY < 0) percentY = 86.0;

        document.getElementById('up_qr_percent_x').value = percentX;
        document.getElementById('up_qr_percent_y').value = percentY;

        const coordText = document.getElementById('stampCoordText');
        if (coordText) {
            coordText.innerText = `X: ${percentX}%, Y: ${percentY}%`;
        }
    }

    function nudgeStamp(stepX, stepY) {
        const badge = document.getElementById('stampBadgeItem');
        if (!badge) return;
        const dim = getCanvasDimensions();

        const deltaPixelsX = (stepX / 100) * dim.w;
        const deltaPixelsY = (stepY / 100) * dim.h;

        moveStampBadgeTo(badge.offsetLeft + deltaPixelsX, badge.offsetTop + deltaPixelsY);
    }

    function setStampPosition(percentX, percentY, label) {
        const badge = document.getElementById('stampBadgeItem');
        if (!badge) return;

        const dim = getCanvasDimensions();
        const targetLeft = (percentX / 100) * dim.w;
        const targetTop = (percentY / 100) * dim.h;

        moveStampBadgeTo(targetLeft, targetTop);

        const coordText = document.getElementById('stampCoordText');
        if (coordText) {
            coordText.innerText = `${label ? label + ' (' : ''}X: ${percentX}%, Y: ${percentY}%${label ? ')' : ''}`;
        }
    }

    function handleCanvasClick(e) {
        const canvas = document.getElementById('pdfPageCanvas');
        const badge = document.getElementById('stampBadgeItem');
        if (!canvas || !badge) return;

        const rect = canvas.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickY = e.clientY - rect.top;

        const badgeW = badge.offsetWidth || 170;
        const badgeH = badge.offsetHeight || 50;

        // Pusatkan badge pada titik yang diklik
        const targetLeft = clickX - (badgeW / 2);
        const targetTop = clickY - (badgeH / 2);

        moveStampBadgeTo(targetLeft, targetTop);
    }

    function updateStampModelPreview(model) {
        const badge = document.getElementById('stampBadgeItem');
        const text = document.getElementById('stampBadgeText');
        const iconBox = document.getElementById('stampQrIconBox');
        if (!badge) return;

        const dim = getCanvasDimensions();

        if (model === 'qr_only') {
            const qrSize = Math.max(40, Math.round(dim.w * 0.081));
            badge.style.width = qrSize + 'px';
            badge.style.height = qrSize + 'px';
            badge.style.padding = '2px';
            if (iconBox) {
                iconBox.style.width = (qrSize - 4) + 'px';
                iconBox.style.height = (qrSize - 4) + 'px';
                iconBox.classList.remove('me-2');
            }
            if (text) text.classList.add('d-none');
        } else {
            // Skala proporsional badge (mirip 65mm x 19mm)
            const badgeW = Math.max(140, Math.round(dim.w * 0.31));
            const badgeH = Math.max(42, Math.round(badgeW * (19 / 65)));
            badge.style.width = badgeW + 'px';
            badge.style.height = badgeH + 'px';
            badge.style.padding = '4px 6px';
            if (iconBox) {
                iconBox.style.width = Math.round(badgeH * 0.76) + 'px';
                iconBox.style.height = Math.round(badgeH * 0.76) + 'px';
                iconBox.classList.add('me-2');
            }
            if (text) text.classList.remove('d-none');
        }

        // Pastikan posisi badge tetap dalam batas canvas
        moveStampBadgeTo(badge.offsetLeft, badge.offsetTop);
    }

    function updateStampOpacity(val) {
        const badge = document.getElementById('stampBadgeItem');
        const badgeIndicator = document.getElementById('opacityValueBadge');
        if (badge) {
            badge.style.background = `rgba(15, 71, 97, ${val / 100})`;
        }
        if (badgeIndicator) {
            badgeIndicator.innerText = `${val}%`;
        }
    }

    function toggleStampingPanel(isActive) {
        const panel = document.getElementById('panelStampingQr');
        const btn = document.getElementById('btnSubmitUploadSigned');
        const badge = document.getElementById('stampBadgeItem');
        if (panel) panel.style.display = isActive ? 'block' : 'none';
        if (badge) badge.style.display = isActive ? 'flex' : 'none';
        if (btn) {
            btn.innerHTML = isActive 
                ? '<i class="fas fa-stamp me-1.5"></i>Simpan &amp; Bubuhkan QR' 
                : '<i class="fas fa-cloud-upload-alt me-1.5"></i>Unggah Berkas Tanpa QR';
        }
    }

    // ==========================================
    // PDF RENDERING ENGINE (PDF.JS)
    // ==========================================
    function handleFileSignedSelected(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Silakan pilih berkas dalam format PDF.');
            input.value = '';
            return;
        }

        const info = document.getElementById('pdfFileInfo');
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        if (info) info.innerText = `${file.name} (${sizeMb} MB)`;

        // Tampilkan loading spinner
        document.getElementById('pdfPlaceholderNotice')?.classList.add('d-none');
        document.getElementById('pdfViewerWrapper')?.classList.add('d-none');
        document.getElementById('pdfLoadingSpinner')?.classList.remove('d-none');
        document.getElementById('pdfLoadingSpinner')?.classList.add('d-flex');

        const reader = new FileReader();
        reader.onload = function(e) {
            const typedArray = new Uint8Array(e.target.result);
            loadPdfDocument(typedArray);
        };
        reader.readAsArrayBuffer(file);
    }

    async function loadPdfDocument(data) {
        if (!window.pdfjsLib) {
            alert('Pustaka PDF viewer belum selesai dimuat. Harap periksa koneksi internet atau muat ulang halaman.');
            return;
        }

        try {
            const loadingTask = pdfjsLib.getDocument({ data: data });
            currentPdfDoc = await loadingTask.promise;
            totalPdfPages = currentPdfDoc.numPages;

            const pageBadge = document.getElementById('pdfPageBadge');
            if (pageBadge) {
                pageBadge.innerText = `${totalPdfPages} Halaman`;
                pageBadge.classList.remove('d-none');
            }

            // Perbarui opsi pilihan target halaman cap
            populateTargetPageOptions(totalPdfPages);

            // Buka tombol lompat ke lembar TTD jika halaman > 1
            const jumpBtn = document.getElementById('btnJumpLastPage');
            if (jumpBtn) {
                if (totalPdfPages > 1) jumpBtn.classList.remove('d-none');
                else jumpBtn.classList.add('d-none');
            }

            // Secara default, langsung buka halaman terakhir (tempat tanda tangan basah)
            currentPdfPage = totalPdfPages;
            await renderPdfPage(currentPdfPage);

            // Tampilkan workspace canvas
            document.getElementById('pdfLoadingSpinner')?.classList.add('d-none');
            document.getElementById('pdfLoadingSpinner')?.classList.remove('d-flex');
            document.getElementById('pdfViewerWrapper')?.classList.remove('d-none');

            // Sesuaikan model ukuran badge
            const style = document.getElementById('up_qr_style')?.value || 'badge';
            updateStampModelPreview(style);

            // Posisikan default di kiri bawah
            const curX = parseFloat(document.getElementById('up_qr_percent_x')?.value) || 6.0;
            const curY = parseFloat(document.getElementById('up_qr_percent_y')?.value) || 86.0;
            setStampPosition(curX, curY, 'Kiri Bawah');

        } catch (error) {
            console.error('Gagal membaca PDF:', error);
            alert('Gagal membaca struktur berkas PDF: ' + error.message);
            document.getElementById('pdfLoadingSpinner')?.classList.add('d-none');
            document.getElementById('pdfPlaceholderNotice')?.classList.remove('d-none');
        }
    }

    async function renderPdfPage(pageNum) {
        if (!currentPdfDoc || isRenderingPdf) {
            pendingRenderPage = pageNum;
            return;
        }

        isRenderingPdf = true;
        currentPdfPage = pageNum;

        try {
            const page = await currentPdfDoc.getPage(pageNum);
            const canvas = document.getElementById('pdfPageCanvas');
            const ctx = canvas.getContext('2d');

            // Hitung lebar kontainer agar pas di layar
            const container = document.getElementById('pdfScrollContainer');
            const availWidth = Math.max(500, (container.clientWidth - 40));

            const unscaledViewport = page.getViewport({ scale: 1.0 });
            let scale = (availWidth / unscaledViewport.width) * currentPdfZoom;
            if (scale < 0.6) scale = 0.6;
            if (scale > 2.5) scale = 2.5;

            const viewport = page.getViewport({ scale: scale });

            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.style.width = viewport.width + 'px';
            canvas.style.height = viewport.height + 'px';

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            await page.render(renderContext).promise;

            // Update UI Toolbar Navigasi
            updatePageNavUI();

            // Sesuaikan ukuran badge cap dengan skala canvas
            const style = document.getElementById('up_qr_style')?.value || 'badge';
            updateStampModelPreview(style);

            // Re-sync posisi badge ke koordinat persentase yang tersimpan
            const curX = parseFloat(document.getElementById('up_qr_percent_x')?.value) || 6.0;
            const curY = parseFloat(document.getElementById('up_qr_percent_y')?.value) || 86.0;
            setStampPosition(curX, curY, '');

        } catch (err) {
            console.error('Error saat merender halaman:', err);
        } finally {
            isRenderingPdf = false;
            if (pendingRenderPage !== null) {
                const next = pendingRenderPage;
                pendingRenderPage = null;
                renderPdfPage(next);
            }
        }
    }

    function populateTargetPageOptions(totalPages) {
        const select = document.getElementById('up_qr_target_page');
        if (!select) return;

        select.innerHTML = `
            <option value="last" selected>Halaman Terakhir (Hal. ${totalPages} - TTD)</option>
            <option value="first">Halaman Pertama (Hal. 1)</option>
            <option value="all">Semua Halaman (1 s.d ${totalPages})</option>
        `;

        if (totalPages > 1) {
            for (let i = 1; i <= totalPages; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.text = `Khusus Halaman ${i}`;
                select.appendChild(opt);
            }
        }
    }

    function handleTargetPageChange(val) {
        stampTargetPage = val;
        let targetNum = 1;
        if (val === 'last') {
            targetNum = totalPdfPages;
        } else if (val === 'first') {
            targetNum = 1;
        } else if (val === 'all') {
            targetNum = currentPdfPage; // Tetap di halaman saat ini
        } else if (isFinite(val)) {
            targetNum = parseInt(val, 10);
        }

        if (currentPdfDoc && targetNum !== currentPdfPage) {
            renderPdfPage(targetNum);
        } else {
            updatePageNavUI();
        }
    }

    function updatePageNavUI() {
        const pageDisplay = document.getElementById('pageNumberDisplay');
        if (pageDisplay) {
            pageDisplay.innerText = `Hal ${currentPdfPage} / ${totalPdfPages}`;
        }

        const prevBtn = document.getElementById('btnPrevPage');
        const nextBtn = document.getElementById('btnNextPage');
        if (prevBtn) prevBtn.disabled = (currentPdfPage <= 1);
        if (nextBtn) nextBtn.disabled = (currentPdfPage >= totalPdfPages);

        const zoomDisplay = document.getElementById('zoomLevelDisplay');
        if (zoomDisplay) {
            zoomDisplay.innerText = Math.round(currentPdfZoom * 100) + '%';
        }

        // Periksa apakah cap aktif di halaman yang sedang dilihat
        const stampStatus = document.getElementById('stampPageStatus');
        const matchBadge = document.getElementById('stampPageMatchBadge');
        const badgeItem = document.getElementById('stampBadgeItem');

        let isMatch = false;
        if (stampTargetPage === 'all') {
            isMatch = true;
        } else if (stampTargetPage === 'last' && currentPdfPage === totalPdfPages) {
            isMatch = true;
        } else if (stampTargetPage === 'first' && currentPdfPage === 1) {
            isMatch = true;
        } else if (parseInt(stampTargetPage, 10) === currentPdfPage) {
            isMatch = true;
        }

        if (stampStatus) {
            stampStatus.innerText = stampTargetPage === 'last' ? 'Hal. TTD' : (stampTargetPage === 'all' ? 'Semua Hal.' : `Hal. ${stampTargetPage}`);
        }

        if (matchBadge) {
            if (isMatch) {
                matchBadge.className = 'badge bg-success';
                matchBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Cap Aktif di Lembar Ini';
            } else {
                matchBadge.className = 'badge bg-warning text-dark';
                matchBadge.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>Cap di Hal. ${stampTargetPage === 'last' ? totalPdfPages : stampTargetPage}`;
            }
        }

        if (badgeItem) {
            // Tampilkan cap dengan jelas jika sesuai, atau redupkan jika melihat halaman lain
            if (isMatch) {
                badgeItem.style.opacity = '1';
                badgeItem.style.pointerEvents = 'auto';
            } else {
                badgeItem.style.opacity = '0.35';
                badgeItem.style.pointerEvents = 'auto'; // Tetap bisa digeser untuk memindahkan ke halaman ini
            }
        }
    }

    function changePdfPage(delta) {
        const next = currentPdfPage + delta;
        if (next >= 1 && next <= totalPdfPages) {
            renderPdfPage(next);
        }
    }

    function jumpToLastPage() {
        if (totalPdfPages > 0) {
            renderPdfPage(totalPdfPages);
        }
    }

    function zoomPdf(delta) {
        currentPdfZoom += delta;
        if (currentPdfZoom < 0.6) currentPdfZoom = 0.6;
        if (currentPdfZoom > 2.0) currentPdfZoom = 2.0;
        if (currentPdfDoc) {
            renderPdfPage(currentPdfPage);
        }
    }

    function resetZoomPdf() {
        currentPdfZoom = 1.0;
        if (currentPdfDoc) {
            renderPdfPage(currentPdfPage);
        }
    }

    // ==========================================
    // INTEGRASI BUKA MODAL DARI TABEL & DETAIL
    // ==========================================
    function bukaModalUploadSignedDetail(sidangId, jenis, noDokumen) {
        bukaModalUploadSignedUniversal(sidangId, jenis, noDokumen);
    }

    function bukaModalUploadSigned(sidangId, jenis, noDokumen) {
        bukaModalUploadSignedUniversal(sidangId, jenis, noDokumen);
    }

    function bukaModalUploadSignedUniversal(sidangId, jenis, noDokumen) {
        const form = document.getElementById('formUploadSigned');
        if (form) form.action = '/sidang-tpp/' + sidangId + '/dokumen-kanwil/upload-signed';

        const upJenis = document.getElementById('up_jenis_dokumen');
        if (upJenis) upJenis.value = jenis;

        const upNo = document.getElementById('up_nomor_dokumen');
        if (upNo) upNo.value = noDokumen || '';

        const upLabel = document.getElementById('up_jenis_label');
        const upBadge = document.getElementById('up_jenis_badge');
        if (upLabel) {
            if (jenis === 'surat_tugas') {
                upLabel.innerText = 'Surat Tugas Tim Pembinaan Kanwil';
                if (upBadge) upBadge.innerText = 'Surat Tugas (ST)';
            } else if (jenis === 'laporan_tpp') {
                upLabel.innerText = 'Laporan Pelaksanaan Sidang TPP Kanwil';
                if (upBadge) upBadge.innerText = 'Laporan Kanwil';
            } else {
                upLabel.innerText = 'Nota Dinas Permohonan Surat Tugas';
                if (upBadge) upBadge.innerText = 'Nota Dinas (ND)';
            }
        }

        // Reset file input & state render jika dokumen berganti
        const fileIn = document.getElementById('file_signed');
        if (fileIn) fileIn.value = '';
        currentPdfDoc = null;
        totalPdfPages = 0;
        currentPdfPage = 1;
        stampTargetPage = 'last';

        document.getElementById('pdfPlaceholderNotice')?.classList.remove('d-none');
        document.getElementById('pdfViewerWrapper')?.classList.add('d-none');
        document.getElementById('pdfLoadingSpinner')?.classList.add('d-none');
        document.getElementById('pdfFileInfo') && (document.getElementById('pdfFileInfo').innerText = 'Format: PDF (Maks. 20 MB)');
        document.getElementById('pdfPageBadge')?.classList.add('d-none');

        // Buka modal Bootstrap
        const modalEl = document.getElementById('modalUploadSigned');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initStampDraggable();
    });
</script>
