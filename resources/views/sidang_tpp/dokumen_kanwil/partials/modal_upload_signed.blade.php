<!-- Modal Upload Berkas Bertanda Tangan Basah (Scan PDF) -->
<div class="modal fade" id="modalUploadSigned" tabindex="-1" aria-labelledby="modalUploadSignedTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formUploadSigned" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="jenis_dokumen" id="up_jenis_dokumen" value="laporan_tpp">

                <div class="modal-header bg-dark text-white py-2.5 px-3">
                    <h5 class="modal-title fw-bold" id="modalUploadSignedTitle" style="font-size: 14px;">
                        <i class="fas fa-file-upload text-warning me-1.5"></i>Upload Berkas Bertanda Tangan Basah
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center">
                        <i class="fas fa-info-circle fa-lg me-2 text-info"></i>
                        <div>
                            Unggah pindaian (*scan*) dokumen resmi yang telah ditandatangani basah dan dibubuhi stempel resmi instansi untuk arsip permanen.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Jenis Naskah:</label>
                        <div class="fw-bold text-dark" id="up_jenis_label">Laporan Pelaksanaan Sidang TPP Kanwil</div>
                    </div>

                    <div class="mb-3">
                        <label for="up_nomor_dokumen" class="form-label small fw-semibold">Nomor Surat / Naskah Final</label>
                        <input type="text" name="nomor_dokumen" id="up_nomor_dokumen" class="form-control form-control-sm font-monospace" placeholder="Nomor resmi dokumen...">
                    </div>

                    <div class="mb-3">
                        <label for="file_signed" class="form-label small fw-semibold">Pilih Berkas PDF Scan TTD <span class="text-danger">*</span></label>
                        <input type="file" name="file_signed" id="file_signed" class="form-control form-control-sm" accept=".pdf,application/pdf" required>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Format: PDF (Maksimal 20 MB). Pastikan tanda tangan dan stempel terbaca jelas.</small>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Simpan &amp; Unggah Berkas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

