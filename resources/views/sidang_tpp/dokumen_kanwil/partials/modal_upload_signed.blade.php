<!-- Modal Upload Berkas Bertanda Tangan Basah (Scan PDF) & Pembubuhan QR Manual -->
<div class="modal fade" id="modalUploadSigned" tabindex="-1" aria-labelledby="modalUploadSignedTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="formUploadSigned" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="jenis_dokumen" id="up_jenis_dokumen" value="laporan_tpp">
                <input type="hidden" name="qr_percent_x" id="up_qr_percent_x" value="6">
                <input type="hidden" name="qr_percent_y" id="up_qr_percent_y" value="86">

                <div class="modal-header bg-dark text-white py-2.5 px-3">
                    <h5 class="modal-title fw-bold" id="modalUploadSignedTitle" style="font-size: 14px;">
                        <i class="fas fa-stamp text-warning me-2"></i>Upload Berkas TTD &amp; Pembubuhan QR Otentifikasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-muted mb-0 d-block">Jenis Naskah:</label>
                            <div class="fw-bold text-dark" id="up_jenis_label">Laporan Pelaksanaan Sidang TPP Kanwil</div>
                        </div>
                        <div class="col-md-5">
                            <label for="up_nomor_dokumen" class="form-label small fw-semibold">Nomor Surat / Naskah Final</label>
                            <input type="text" name="nomor_dokumen" id="up_nomor_dokumen" class="form-control form-control-sm font-monospace" placeholder="Nomor resmi dokumen...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="file_signed" class="form-label small fw-semibold">Pilih Berkas PDF Hasil Scan TTD <span class="text-danger">*</span></label>
                        <input type="file" name="file_signed" id="file_signed" class="form-control form-control-sm" accept=".pdf,application/pdf" required onchange="handleFileSignedSelected(this)">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Format: PDF (Maksimal 20 MB). Pastikan lembar tanda tangan dan stempel terbaca jelas.</small>
                    </div>

                    <!-- Panel Opsi Pembubuhan QR (Seperti e-Meterai) -->
                    <div class="card border border-primary-subtle shadow-sm mb-2">
                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="bubuhi_qr" id="up_bubuhi_qr" value="1" checked onchange="toggleStampingPanel(this.checked)">
                                <label class="form-check-label fw-bold text-dark small" for="up_bubuhi_qr">
                                    <i class="fas fa-qrcode text-primary me-1"></i>Bubuhkan Cap QR Code Otentifikasi (e-Stamp)
                                </label>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10.5px;">
                                <i class="fas fa-check-double me-1"></i>Otentifikasi Resmi
                            </span>
                        </div>

                        <div class="card-body p-3" id="panelStampingQr">
                            <div class="row g-3">
                                <!-- Kolom Pengaturan Posisi & Format -->
                                <div class="col-md-7">
                                    <div class="row g-2 mb-2.5">
                                        <div class="col-6">
                                            <label class="form-label text-muted" style="font-size: 11px;">Target Halaman Cap:</label>
                                            <select name="qr_target_page" id="up_qr_target_page" class="form-select form-select-sm" style="font-size: 11.5px;">
                                                <option value="last" selected>Halaman Terakhir (TTD)</option>
                                                <option value="first">Halaman Pertama</option>
                                                <option value="all">Semua Halaman</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted" style="font-size: 11px;">Model Cap Otentifikasi:</label>
                                            <select name="qr_style" id="up_qr_style" class="form-select form-select-sm" style="font-size: 11.5px;" onchange="updateStampModelPreview(this.value)">
                                                <option value="badge" selected>Badge e-Meterai (QR + Teks)</option>
                                                <option value="qr_only">Hanya QR Code Bersih</option>
                                            </select>
                                        </div>
                                    </div>

                                    <label class="form-label text-muted mb-1 d-block" style="font-size: 11px;">
                                        Pilihan Posisi Cepat (Preset):
                                    </label>
                                    <div class="d-flex flex-wrap gap-1.5 mb-2.5">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(6, 86, 'Kiri Bawah')">
                                            <i class="fas fa-arrow-down me-1"></i>Kiri Bawah
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(42, 70, 'Samping TTD')">
                                            <i class="fas fa-signature me-1"></i>Samping TTD
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(62, 86, 'Kanan Bawah')">
                                            <i class="fas fa-arrow-down me-1"></i>Kanan Bawah
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11px;" onclick="setStampPosition(6, 6, 'Kiri Atas')">
                                            <i class="fas fa-arrow-up me-1"></i>Kiri Atas
                                        </button>
                                    </div>

                                    <div class="p-2 rounded bg-light border small text-muted" style="font-size: 11px; line-height: 1.4;">
                                        <i class="fas fa-hand-pointer text-primary me-1"></i>
                                        <b>Penempatan Fleksibel:</b> Anda dapat menggeser (*drag &amp; drop*) kotak cap merah di simulasi lembar kertas di samping, atau langsung klik pada titik mana saja di kertas untuk menempatkan cap secara presisi.
                                    </div>

                                    <div class="mt-2 text-start">
                                        <span class="badge bg-white text-secondary border px-2 py-1" id="stampCoordIndicator" style="font-size: 11px;">
                                            <i class="fas fa-crosshairs text-danger me-1"></i>Posisi Cap: Kiri Bawah (X: 6%, Y: 86%)
                                        </span>
                                    </div>
                                </div>

                                <!-- Kolom Simulasi Lembar Kertas A4 (Interactive Drag Canvas) -->
                                <div class="col-md-5 d-flex flex-column align-items-center">
                                    <div class="small fw-semibold text-muted mb-1" style="font-size: 11px;">
                                        <i class="fas fa-file-alt me-1"></i>Simulasi Penempatan di Kertas:
                                    </div>
                                    <div id="stampPaperSheet" style="width: 210px; height: 297px; background: #ffffff; border: 2px dashed #0F4761; border-radius: 4px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.12); cursor: crosshair; user-select: none; overflow: hidden;" onclick="handlePaperClick(event)">
                                        <!-- Simulasi Header Naskah -->
                                        <div style="position: absolute; top: 12px; left: 16px; right: 16px; border-bottom: 2px solid #ccc; padding-bottom: 4px;">
                                            <div style="height: 3px; background: #bbb; width: 60%; margin: 0 auto 3px;"></div>
                                            <div style="height: 2px; background: #ddd; width: 80%; margin: 0 auto;"></div>
                                        </div>

                                        <!-- Simulasi Paragraf Dokumen -->
                                        <div style="position: absolute; top: 38px; left: 16px; right: 16px;">
                                            <div style="height: 2px; background: #eee; width: 95%; margin-bottom: 4px;"></div>
                                            <div style="height: 2px; background: #eee; width: 100%; margin-bottom: 4px;"></div>
                                            <div style="height: 2px; background: #eee; width: 85%; margin-bottom: 4px;"></div>
                                            <div style="height: 2px; background: #eee; width: 90%; margin-bottom: 4px;"></div>
                                            <div style="height: 2px; background: #eee; width: 70%; margin-bottom: 4px;"></div>
                                        </div>

                                        <!-- Simulasi Area Tanda Tangan -->
                                        <div style="position: absolute; bottom: 32px; right: 16px; width: 75px; text-align: center; border-top: 1px dotted #ccc; padding-top: 3px;">
                                            <div style="font-size: 7px; color: #999; line-height: 1;">Area TTD</div>
                                            <div style="height: 18px;"></div>
                                            <div style="height: 2px; background: #999; width: 100%;"></div>
                                        </div>

                                        <!-- DRAGGABLE STAMP BADGE (Mewakili Cap QR e-Meterai) -->
                                        <div id="stampBadgeItem" style="position: absolute; left: 12px; top: 248px; width: 72px; height: 28px; background: rgba(15, 71, 97, 0.95); color: #fff; border: 1.5px solid #000; border-radius: 3px; display: flex; align-items: center; justify-content: center; padding: 2px; cursor: move; box-shadow: 0 2px 6px rgba(0,0,0,0.3); z-index: 10;">
                                            <i class="fas fa-qrcode fa-lg me-1 text-warning" style="font-size: 13px;"></i>
                                            <div id="stampBadgeText" style="font-size: 6px; line-height: 1.1; font-weight: bold;">
                                                QR SULTAN<br><span style="font-size: 5px; opacity: 0.85;">OTENTIFIKASI</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-muted mt-1 text-center" style="font-size: 10.5px;">
                                        <i class="fas fa-arrows-alt me-1 text-primary"></i>Geser kotak merah di atas ke posisi yang diinginkan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold" id="btnSubmitUploadSigned">
                        <i class="fas fa-stamp me-1.5"></i>Simpan &amp; Bubuhkan QR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let isDraggingStamp = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let stampInitialLeft = 0;
    let stampInitialTop = 0;

    function initStampDraggable() {
        const paper = document.getElementById('stampPaperSheet');
        const badge = document.getElementById('stampBadgeItem');
        if (!paper || !badge) return;

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

        // Touch support untuk tablet / hp
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

    function getPaperDimensions() {
        const paper = document.getElementById('stampPaperSheet');
        const w = (paper && paper.clientWidth > 50) ? paper.clientWidth : 210;
        const h = (paper && paper.clientHeight > 50) ? paper.clientHeight : 297;
        return { w, h };
    }

    function moveStampBadgeTo(targetLeft, targetTop) {
        const paper = document.getElementById('stampPaperSheet');
        const badge = document.getElementById('stampBadgeItem');
        if (!paper || !badge) return;

        const dim = getPaperDimensions();
        const badgeW = badge.offsetWidth || 72;
        const badgeH = badge.offsetHeight || 28;

        const maxLeft = Math.max(10, dim.w - badgeW);
        const maxTop = Math.max(10, dim.h - badgeH);

        const clampedLeft = Math.max(2, Math.min(maxLeft - 2, targetLeft));
        const clampedTop = Math.max(2, Math.min(maxTop - 2, targetTop));

        badge.style.left = clampedLeft + 'px';
        badge.style.top = clampedTop + 'px';

        let percentX = Math.round((clampedLeft / dim.w) * 100);
        let percentY = Math.round((clampedTop / dim.h) * 100);

        if (!isFinite(percentX) || isNaN(percentX) || percentX < 0 || percentX > 100) percentX = 6;
        if (!isFinite(percentY) || isNaN(percentY) || percentY < 0 || percentY > 100) percentY = 86;

        document.getElementById('up_qr_percent_x').value = percentX;
        document.getElementById('up_qr_percent_y').value = percentY;

        const indicator = document.getElementById('stampCoordIndicator');
        if (indicator) {
            indicator.innerHTML = `
                <i class="fas fa-crosshairs text-danger me-1"></i>Posisi Cap: X: ${percentX}%, Y: ${percentY}% (Kustom)
            `;
        }
    }

    function handlePaperClick(e) {
        const paper = document.getElementById('stampPaperSheet');
        const badge = document.getElementById('stampBadgeItem');
        if (!paper || !badge) return;

        const rect = paper.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickY = e.clientY - rect.top;

        // Pusatkan badge pada titik klik
        const badgeW = badge.offsetWidth || 72;
        const badgeH = badge.offsetHeight || 28;
        const targetLeft = clickX - (badgeW / 2);
        const targetTop = clickY - (badgeH / 2);

        moveStampBadgeTo(targetLeft, targetTop);
    }

    function setStampPosition(percentX, percentY, label) {
        const paper = document.getElementById('stampPaperSheet');
        const badge = document.getElementById('stampBadgeItem');
        if (!paper || !badge) return;

        const dim = getPaperDimensions();
        const targetLeft = (percentX / 100) * dim.w;
        const targetTop = (percentY / 100) * dim.h;

        moveStampBadgeTo(targetLeft, targetTop);

        const indicator = document.getElementById('stampCoordIndicator');
        if (indicator) {
            indicator.innerHTML = `
                <i class="fas fa-crosshairs text-danger me-1"></i>Posisi Cap: ${label} (X: ${percentX}%, Y: ${percentY}%)
            `;
        }
    }

    function updateStampModelPreview(model) {
        const badge = document.getElementById('stampBadgeItem');
        const text = document.getElementById('stampBadgeText');
        if (!badge) return;

        if (model === 'qr_only') {
            badge.style.width = '32px';
            badge.style.height = '32px';
            if (text) text.classList.add('d-none');
        } else {
            badge.style.width = '72px';
            badge.style.height = '28px';
            if (text) text.classList.remove('d-none');
        }
    }

    function toggleStampingPanel(isActive) {
        const panel = document.getElementById('panelStampingQr');
        const btn = document.getElementById('btnSubmitUploadSigned');
        if (panel) {
            panel.style.display = isActive ? 'block' : 'none';
        }
        if (btn) {
            btn.innerHTML = isActive 
                ? '<i class="fas fa-stamp me-1.5"></i>Simpan &amp; Bubuhkan QR' 
                : '<i class="fas fa-cloud-upload-alt me-1.5"></i>Unggah Berkas Tanpa QR';
        }
    }

    function handleFileSignedSelected(input) {
        if (input.files && input.files[0]) {
            const f = input.files[0];
            if (f.type !== 'application/pdf' && !f.name.toLowerCase().endsWith('.pdf')) {
                alert('Silakan pilih berkas dalam format PDF.');
                input.value = '';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initStampDraggable();

        const modalEl = document.getElementById('modalUploadSigned');
        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function() {
                const curX = parseFloat(document.getElementById('up_qr_percent_x').value) || 6;
                const curY = parseFloat(document.getElementById('up_qr_percent_y').value) || 86;
                setStampPosition(curX, curY, 'Kiri Bawah');
            });
        }
    });
</script>
