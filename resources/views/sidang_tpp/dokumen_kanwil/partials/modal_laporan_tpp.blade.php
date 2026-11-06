<!-- Modal Generator & Editor Laporan Pelaksanaan Sidang TPP Kanwil -->
<div class="modal fade" id="modalLaporanTpp" tabindex="-1" aria-labelledby="modalLaporanTppTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form id="formLaporanTpp" method="POST" target="_blank">
                @csrf
                <input type="hidden" name="sidang_id" id="lap_sidang_id">

                <div class="modal-header bg-dark text-white py-2.5 px-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-contract text-warning me-2 fa-lg"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalLaporanTppTitle" style="font-size: 15px;">
                                Laporan Pelaksanaan Sidang TPP Kantor Wilayah
                            </h5>
                            <small class="text-white-50" style="font-size: 11px;">
                                Satker: <span id="lap_upt_nama" class="text-white fw-bold"></span> &bull; Pelaksanaan: <span id="lap_tgl_sidang" class="text-white fw-bold"></span>
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3 bg-light">
                    <!-- Banner AI -->
                    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                        <div class="card-body p-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                <div>
                                    <div class="fw-bold text-success mb-1" style="font-size: 13px;">
                                        <i class="fas fa-magic me-1"></i>Otomatisasi Cerdas dengan AI Gemini
                                    </div>
                                    <div class="text-muted small" style="font-size: 11.5px;">
                                        AI akan membaca berkas Berita Acara (BA) PDF dari UPT, mendeteksi materi usulan faktual (PB, CB, Asimilasi, Remisi, dll.), dan menyusun narasi laporan dinas secara otomatis.
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm px-3 shadow-sm fw-bold text-nowrap" id="btnAnalisisBaAi" onclick="triggerAnalisisBaAi()">
                                    <i class="fas fa-robot me-1 text-warning"></i>Analisis Berita Acara (BA) dengan AI
                                </button>
                            </div>
                            <div id="bannerNoBa" class="text-danger small mt-2 d-none fw-semibold">
                                <i class="fas fa-exclamation-circle me-1"></i>Pihak UPT belum mengunggah Berita Acara (BA) untuk sidang ini. Anda tetap dapat mengisi dan menyusun laporan secara manual di bawah.
                            </div>
                        </div>
                    </div>

                    <div id="lapAiAlertArea" class="d-none"></div>

                    <!-- Meta Dokumen -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label for="lap_nomor_dokumen" class="form-label small fw-semibold">Nomor Laporan Kanwil</label>
                                    <input type="text" name="nomor_dokumen" id="lap_nomor_dokumen" class="form-control form-control-sm font-monospace" placeholder="W.12.PAS.PK.02.02-...">
                                </div>
                                <div class="col-md-4">
                                    <label for="lap_topik_sidang" class="form-label small fw-semibold">Topik Utama Sidang <span class="text-danger">*</span></label>
                                    <input type="text" name="topik_sidang" id="lap_topik_sidang" class="form-control form-control-sm" placeholder="Contoh: Pemberian Hak Integrasi (PB dan CB)" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="lap_tanggal_laporan" class="form-label small fw-semibold">Tanggal Naskah</label>
                                    <input type="text" name="tanggal_laporan" id="lap_tanggal_laporan" class="form-control form-control-sm" value="{{ date('d F Y') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Struktur Bagian Laporan -->
                    <div class="accordion mb-3" id="accordionLaporan">
                        <!-- BAGIAN A -->
                        <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button py-2.5 px-3 fw-bold bg-white text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseA" aria-expanded="true">
                                    <i class="fas fa-layer-group text-primary me-2"></i>A. KEGIATAN YANG DILAKSANAKAN
                                </button>
                            </h2>
                            <div id="collapseA" class="accordion-collapse collapse show">
                                <div class="accordion-body p-3 bg-white">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted">Paragraf 1 (Pembuka, Waktu &amp; Tempat)</label>
                                        <textarea name="paragraf_a1" id="lap_paragraf_a1" rows="2" class="form-control form-control-sm"></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted">Paragraf 2 (Maksud Sidang TPP)</label>
                                        <textarea name="paragraf_a2" id="lap_paragraf_a2" rows="2" class="form-control form-control-sm"></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted">Paragraf 3 (Kehadiran Perwakilan Kanwil &amp; Pengawasan)</label>
                                        <textarea name="paragraf_a3" id="lap_paragraf_a3" rows="2" class="form-control form-control-sm"></textarea>
                                    </div>
                                    
                                    <!-- Materi Sidang Items Dinamis -->
                                    <div class="mb-2 p-2.5 rounded bg-light border">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <label class="form-label small fw-bold mb-0 text-dark">
                                                <i class="fas fa-list-ul text-primary me-1"></i>Rincian Butir Materi Sidang (Jumlah Usulan Faktual)
                                            </label>
                                            <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="tambahBarisMateri()">
                                                <i class="fas fa-plus me-1"></i>Tambah Baris Usulan
                                            </button>
                                        </div>
                                        <div id="wrapperMateriItems"></div>
                                    </div>

                                    <div>
                                        <label class="form-label small fw-semibold text-muted">Paragraf 4 (Pencermatan Berkas &amp; SDP)</label>
                                        <textarea name="paragraf_a4" id="lap_paragraf_a4" rows="2" class="form-control form-control-sm"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN B -->
                        <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2.5 px-3 fw-bold bg-white text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseB">
                                    <i class="fas fa-clipboard-check text-success me-2"></i>B. HASIL YANG DICAPAI
                                </button>
                            </h2>
                            <div id="collapseB" class="accordion-collapse collapse">
                                <div class="accordion-body p-3 bg-white">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted">Paragraf Pembuka Verifikasi</label>
                                        <textarea name="paragraf_b1" id="lap_paragraf_b1" rows="2" class="form-control form-control-sm"></textarea>
                                    </div>
                                    <div class="p-2.5 rounded bg-light border">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <label class="form-label small fw-bold mb-0 text-dark">
                                                <i class="fas fa-check-double text-success me-1"></i>Poin Pendapat &amp; Rekomendasi Kanwil
                                            </label>
                                            <button type="button" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="tambahPoin('wrapperB2Poin', 'paragraf_b2_poin[]')">
                                                <i class="fas fa-plus me-1"></i>Tambah Poin
                                            </button>
                                        </div>
                                        <div id="wrapperB2Poin"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN C -->
                        <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2.5 px-3 fw-bold bg-white text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseC">
                                    <i class="fas fa-lightbulb text-warning me-2"></i>C. SIMPULAN DAN SARAN
                                </button>
                            </h2>
                            <div id="collapseC" class="accordion-collapse collapse">
                                <div class="accordion-body p-3 bg-white">
                                    <!-- Simpulan -->
                                    <div class="mb-3 p-2.5 rounded bg-light border">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <label class="form-label small fw-bold mb-0 text-dark">Poin Simpulan</label>
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="tambahPoin('wrapperSimpulan', 'simpulan_poin[]')">
                                                <i class="fas fa-plus me-1"></i>Tambah Simpulan
                                            </button>
                                        </div>
                                        <div id="wrapperSimpulan"></div>
                                    </div>

                                    <!-- Saran -->
                                    <div class="p-2.5 rounded bg-light border">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <label class="form-label small fw-bold mb-0 text-dark">Poin Saran Pembinaan untuk UPT</label>
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="tambahPoin('wrapperSaran', 'saran_poin[]')">
                                                <i class="fas fa-plus me-1"></i>Tambah Saran
                                            </button>
                                        </div>
                                        <div id="wrapperSaran"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN D -->
                        <div class="accordion-item border-0 shadow-sm rounded overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2.5 px-3 fw-bold bg-white text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseD">
                                    <i class="fas fa-flag-checkered text-secondary me-2"></i>D. PENUTUP
                                </button>
                            </h2>
                            <div id="collapseD" class="accordion-collapse collapse">
                                <div class="accordion-body p-3 bg-white">
                                    <textarea name="penutup" id="lap_penutup" rows="2" class="form-control form-control-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pejabat Penandatangan & Metode Otentifikasi -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2 px-3 fw-bold small text-dark border-bottom">
                            <i class="fas fa-signature text-primary me-1"></i> Tanda Tangan &amp; Otentifikasi Dokumen
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6 border-end pe-md-3">
                                    <h6 class="fw-bold small text-muted mb-2">Perwakilan Kantor Wilayah (Sisi Kiri)</h6>
                                    <div class="mb-2">
                                        <label for="lap_pengawas_nama" class="form-label small fw-semibold">Nama Pegawai Kanwil yang Hadir <span class="text-danger">*</span></label>
                                        <input type="text" name="pengawas_nama" id="lap_pengawas_nama" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-1">
                                        <label for="lap_pengawas_jabatan" class="form-label small fw-semibold">Keterangan / Tim</label>
                                        <input type="text" name="pengawas_jabatan" id="lap_pengawas_jabatan" class="form-control form-control-sm" value="Tim Pembinaan Narapidana dan Anak Binaan">
                                    </div>
                                </div>
                                <div class="col-md-6 ps-md-3">
                                    <h6 class="fw-bold small text-muted mb-2">Pejabat Pengesah / Pimpinan (Sisi Kanan)</h6>
                                    <div class="mb-2">
                                        <label for="lap_penandatangan_nama" class="form-label small fw-semibold">Nama Pejabat <span class="text-danger">*</span></label>
                                        <input type="text" name="penandatangan_nama" id="lap_penandatangan_nama" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="lap_penandatangan_jabatan" class="form-label small fw-semibold">Jabatan Pejabat <span class="text-danger">*</span></label>
                                        <input type="text" name="penandatangan_jabatan" id="lap_penandatangan_jabatan" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-8">
                                            <label for="lap_penandatangan_nip" class="form-label small fw-semibold">NIP Pejabat</label>
                                            <input type="text" name="penandatangan_nip" id="lap_penandatangan_nip" class="form-control form-control-sm" placeholder="1980...">
                                        </div>
                                        <div class="col-4 d-flex align-items-end">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" name="tampilkan_nip" id="lap_tampilkan_nip" value="1">
                                                <label class="form-check-label small" for="lap_tampilkan_nip" style="font-size: 11px;">Tampilkan NIP</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pilihan Metode Tanda Tangan -->
                            <div class="p-2.5 rounded bg-light border">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    <i class="fas fa-qrcode text-primary me-1"></i>Pilih Metode Pengesahan / Otentifikasi:
                                </label>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metode_ttd" id="ttd_digital" value="digital_qr" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="ttd_digital">
                                            Digital QR Code (Direkomendasikan &bull; Otentifikasi Terverifikasi Publik via Kamera HP)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metode_ttd" id="ttd_manual" value="manual">
                                        <label class="form-check-label small fw-semibold text-dark" for="ttd_manual">
                                            Manual (Cetak &amp; Tanda Tangan Basah / Cap Fisik)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="format_output" value="word" class="btn btn-outline-primary btn-sm px-3 shadow-sm fw-semibold">
                            <i class="fas fa-file-word me-1 text-primary"></i>Unduh Word (.doc)
                        </button>
                        <button type="submit" name="format_output" value="pdf" class="btn btn-primary btn-sm px-3 shadow-sm fw-bold">
                            <i class="fas fa-file-pdf me-1 text-white"></i>Terbitkan PDF Resmi (QR Code)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
