<!-- Modal Pratinjau Dokumen Universal (Lihat Tanpa Download) -->
<div class="modal fade" id="modalPratinjauDokumen" tabindex="-1" aria-labelledby="modalPratinjauDokumenLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 94vw;">
        <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 14px;">
            <!-- Modal Header -->
            <div class="modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-2" style="min-width: 0;">
                    <span class="badge bg-primary px-2 py-1 flex-shrink-0"><i class="fas fa-file-alt me-1"></i>Dokumen</span>
                    <h6 class="modal-title fw-bold text-truncate mb-0 fs-6" id="previewModalTitle">Pratinjau Dokumen</h6>
                </div>
                <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                    <a href="#" id="previewOpenNewTab" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light py-1 px-2.5 rounded-pill shadow-xs" title="Buka Dokumen di Tab Browser Baru">
                        <i class="fas fa-external-link-alt me-1"></i><span class="d-none d-sm-inline">Buka Tab Baru</span>
                    </a>
                    <a href="#" id="previewDownloadBtn" class="btn btn-sm btn-outline-light py-1 px-2.5 rounded-pill shadow-xs" title="Unduh Berkas Asli">
                        <i class="fas fa-download me-1"></i><span class="d-none d-sm-inline">Unduh</span>
                    </a>
                    <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0 position-relative" style="background-color: #0f172a; min-height: 520px; height: 82vh;">
                <!-- Loading Spinner Overlay -->
                <div id="previewLoadingSpinner" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="z-index: 10; background-color: rgba(248, 250, 252, 0.95); transition: opacity 0.2s ease;">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 2.8rem; height: 2.8rem;"></div>
                    <span class="fw-bold text-dark">Memuat tampilan berkas...</span>
                    <span class="small text-muted mt-1" style="font-size: 12px;">Jika berkas tidak terbuka otomatis, silakan gunakan tombol <b>Buka Tab Baru</b> di pojok kanan atas.</span>
                </div>

                <!-- Fallback Alert -->
                <div id="previewFallbackAlert" class="alert alert-warning m-3 small d-none position-absolute top-0 start-0 end-0 shadow-sm" style="z-index: 11;">
                    <i class="fas fa-exclamation-triangle me-1"></i> Browser Anda mungkin tidak mengizinkan pratinjau berkas secara langsung di dalam jendela ini. 
                    <a id="previewFallbackLink" href="#" target="_blank" class="fw-bold alert-link">Klik di sini untuk membuka di tab baru</a> atau gunakan tombol Unduh di atas.
                </div>

                <!-- PDF / Iframe Viewer: Harus selalu di dalam layout agar browser PDF engine langsung aktif -->
                <iframe id="previewIframe" src="about:blank" class="w-100 h-100 border-0" style="display: block; width: 100%; height: 100%; min-height: 520px; background-color: #ffffff;" title="Pratinjau Berkas"></iframe>

                <!-- Image Viewer -->
                <div id="previewImageContainer" class="w-100 h-100 d-flex align-items-center justify-content-center p-3 overflow-auto" style="display: none; background-color: #0f172a;">
                    <img id="previewImage" src="" alt="Pratinjau Dokumen" class="img-fluid rounded shadow" style="max-height: 78vh; object-fit: contain;">
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted" style="font-size: 11.5px;">
                    <i class="fas fa-info-circle text-primary me-1"></i>Gunakan mode fullscreen atau <a href="#" id="previewFooterNewTab" target="_blank" class="fw-semibold text-primary text-decoration-none">Buka di Tab Baru</a> jika ingin memperbesar tampilan.
                </span>
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let previewSpinnerTimer = null;

function hidePreviewSpinner() {
    if (previewSpinnerTimer) {
        clearTimeout(previewSpinnerTimer);
        previewSpinnerTimer = null;
    }
    const spinner = document.getElementById('previewLoadingSpinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

function showPreviewSpinner() {
    if (previewSpinnerTimer) {
        clearTimeout(previewSpinnerTimer);
        previewSpinnerTimer = null;
    }
    const spinner = document.getElementById('previewLoadingSpinner');
    if (spinner) {
        spinner.style.display = 'flex';
    }
}

function previewDokumen(previewUrl, title, downloadUrl) {
    if (!previewUrl) return;

    const modalTitle = document.getElementById('previewModalTitle');
    const openNewTab = document.getElementById('previewOpenNewTab');
    const footerNewTab = document.getElementById('previewFooterNewTab');
    const downloadBtn = document.getElementById('previewDownloadBtn');
    const iframe = document.getElementById('previewIframe');
    const imgContainer = document.getElementById('previewImageContainer');
    const img = document.getElementById('previewImage');
    const fallback = document.getElementById('previewFallbackAlert');
    const fallbackLink = document.getElementById('previewFallbackLink');

    if (modalTitle) modalTitle.textContent = title || 'Pratinjau Dokumen';
    if (openNewTab) openNewTab.href = previewUrl;
    if (footerNewTab) footerNewTab.href = previewUrl;
    if (downloadBtn) downloadBtn.href = downloadUrl || previewUrl.replace(/([?&])preview=1(&|$)/, '$1').replace(/[?&]$/, '');
    if (fallbackLink) fallbackLink.href = previewUrl;

    if (fallback) fallback.classList.add('d-none');

    showPreviewSpinner();

    // Auto dismiss spinner maksimal 1.2 detik untuk PDF
    // Alasan: Engine PDF Chromium (Chrome, Edge) sering kali tidak memicu event 'load' pada iframe
    previewSpinnerTimer = setTimeout(function() {
        hidePreviewSpinner();
    }, 1200);

    const isImage = /\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i.test(previewUrl);

    if (isImage) {
        if (iframe) {
            iframe.style.display = 'none';
            iframe.src = 'about:blank';
        }
        if (imgContainer) imgContainer.style.display = 'flex';
        if (img) {
            img.onload = function() {
                hidePreviewSpinner();
            };
            img.onerror = function() {
                hidePreviewSpinner();
                if (fallback) fallback.classList.remove('d-none');
            };
            img.src = previewUrl;
        }
    } else {
        if (imgContainer) imgContainer.style.display = 'none';
        if (img) img.src = '';
        if (iframe) {
            iframe.style.display = 'block';
            iframe.onload = function() {
                hidePreviewSpinner();
            };
            // Set source iframe
            iframe.src = previewUrl;
        }
    }

    const modalEl = document.getElementById('modalPratinjauDokumen');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalPratinjauDokumen');
    if (modalEl) {
        // Multi-modal stacking support
        modalEl.addEventListener('show.bs.modal', function() {
            const openModals = document.querySelectorAll('.modal.show');
            const zIndex = 1060 + 10 * openModals.length;
            this.style.zIndex = zIndex;
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    backdrops[backdrops.length - 1].style.zIndex = zIndex - 1;
                }
            }, 0);
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            hidePreviewSpinner();
            const iframe = document.getElementById('previewIframe');
            if (iframe) iframe.src = 'about:blank';
            const img = document.getElementById('previewImage');
            if (img) img.src = '';

            // Restore body modal-open jika masih ada modal lain yang terbuka di bawahnya
            if (document.querySelectorAll('.modal.show').length > 0) {
                document.body.classList.add('modal-open');
            }
        });
    }
});
</script>
