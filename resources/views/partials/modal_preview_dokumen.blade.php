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
            <div class="modal-body p-0 position-relative" style="background-color: var(--bs-tertiary-bg, #f1f5f9); min-height: 520px; height: 80vh;">
                <!-- Loading Spinner -->
                <div id="previewLoadingSpinner" class="position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center justify-content-center text-muted" style="z-index: 5;">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                    <span class="small fw-semibold">Memuat tampilan berkas...</span>
                </div>

                <!-- Fallback Alert -->
                <div id="previewFallbackAlert" class="alert alert-warning m-3 small d-none" style="z-index: 6;">
                    <i class="fas fa-exclamation-triangle me-1"></i> Dokumen mungkin memerlukan pembuka PDF eksternal. Silakan 
                    <a id="previewFallbackLink" href="#" target="_blank" class="fw-bold alert-link">klik di sini untuk membuka di tab baru</a> atau gunakan tombol Unduh di atas.
                </div>

                <!-- PDF / Iframe Viewer -->
                <iframe id="previewIframe" src="" class="w-100 h-100 border-0" style="display: none;" title="Pratinjau Berkas"></iframe>

                <!-- Image Viewer -->
                <div id="previewImageContainer" class="w-100 h-100 d-flex align-items-center justify-content-center p-3 overflow-auto" style="display: none;">
                    <img id="previewImage" src="" alt="Pratinjau Dokumen" class="img-fluid rounded shadow-sm" style="max-height: 75vh; object-fit: contain;">
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted" style="font-size: 11.5px;">
                    <i class="fas fa-info-circle text-primary me-1"></i>Gunakan mode fullscreen atau buka di tab baru jika ingin memperbesar halaman berkas.
                </span>
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function previewDokumen(previewUrl, title, downloadUrl) {
    if (!previewUrl) return;

    const modalTitle = document.getElementById('previewModalTitle');
    const openNewTab = document.getElementById('previewOpenNewTab');
    const downloadBtn = document.getElementById('previewDownloadBtn');
    const iframe = document.getElementById('previewIframe');
    const imgContainer = document.getElementById('previewImageContainer');
    const img = document.getElementById('previewImage');
    const spinner = document.getElementById('previewLoadingSpinner');
    const fallback = document.getElementById('previewFallbackAlert');
    const fallbackLink = document.getElementById('previewFallbackLink');

    if (modalTitle) modalTitle.textContent = title || 'Pratinjau Dokumen';
    if (openNewTab) openNewTab.href = previewUrl;
    if (downloadBtn) downloadBtn.href = downloadUrl || previewUrl.replace(/([?&])preview=1(&|$)/, '$1').replace(/[?&]$/, '');
    if (fallbackLink) fallbackLink.href = previewUrl;

    if (spinner) spinner.style.display = 'flex';
    if (iframe) {
        iframe.style.display = 'none';
        iframe.src = '';
    }
    if (imgContainer) imgContainer.style.display = 'none';
    if (img) img.src = '';
    if (fallback) fallback.classList.add('d-none');

    const isImage = /\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i.test(previewUrl);

    if (isImage) {
        if (img) {
            img.onload = function() {
                if (spinner) spinner.style.display = 'none';
                if (imgContainer) imgContainer.style.display = 'flex';
            };
            img.onerror = function() {
                if (spinner) spinner.style.display = 'none';
                if (fallback) fallback.classList.remove('d-none');
            };
            img.src = previewUrl;
        }
    } else {
        if (iframe) {
            iframe.onload = function() {
                if (spinner) spinner.style.display = 'none';
                iframe.style.display = 'block';
            };
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
        // Multi-modal stacking support (supaya jika dibuka dari modal verifikasi, backdrop & scrollbar tidak rusak)
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
            const iframe = document.getElementById('previewIframe');
            if (iframe) iframe.src = '';
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
