@php
    $aiSetting = \App\Models\AiSetting::getFirst();
    $pegawaiKanwil = \App\Models\KanwilPegawai::where('is_active', true)->orderBy('urutan')->orderBy('nama')->get();
    $currentSidangId = isset($sidang) && $sidang ? $sidang->id : 0;
    $hasPdfUndangan = isset($sidang) && $sidang && !empty($sidang->file_undangan);
@endphp

<!-- MODAL: Generate Nota Dinas Permohonan Surat Tugas -->
<div class="modal fade" id="modalNotaDinas" tabindex="-1" aria-labelledby="modalNotaDinasTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ $currentSidangId ? route('sidang-tpp.nota-dinas.generate', $currentSidangId) : '#' }}" method="POST" id="formNotaDinasModal" data-sidang-id="{{ $currentSidangId }}">
                @csrf
                <div class="modal-header bg-primary text-white py-2.5">
                    <h5 class="modal-title fw-bold" id="modalNotaDinasTitle" style="font-size: 15px;">
                        <i class="fas fa-file-signature me-2"></i>Buat Nota Dinas Permohonan Surat Tugas Sidang TPP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Banner AI / Manual Input -->
                    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-left: 4px solid #0284c7 !important;">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $aiSetting->is_active && !empty($aiSetting->api_key) ? 'bg-success' : 'bg-secondary' }}">
                                            <i class="fas fa-robot me-1"></i>{{ $aiSetting->is_active && !empty($aiSetting->api_key) ? 'Fitur AI Aktif' : 'Mode Manual (AI Nonaktif)' }}
                                        </span>
                                        <h6 class="fw-bold mb-0 text-dark">Ekstraksi Cerdas &amp; Form Naskah Dinas</h6>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Sistem dapat menganalisis berkas PDF surat undangan dari UPT untuk mengisi rincian di bawah secara otomatis, atau Anda dapat mengisi/mengoreksinya secara manual kapan saja.
                                    </small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" id="btnEkstrakAiModal" onclick="ekstrakUndanganAiModal({{ $currentSidangId }})">
                                        <i class="fas fa-magic me-1"></i>Analisis Undangan via AI
                                    </button>
                                    @elseif(!$hasPdfUndangan)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-1.5 px-2">
                                        <i class="fas fa-exclamation-circle me-1"></i>Belum Ada Berkas PDF Undangan (Gunakan Input Manual)
                                    </span>
                                    @else
                                    <span class="badge bg-light text-secondary border py-1.5 px-2">
                                        <i class="fas fa-keyboard me-1"></i>Input Manual Siap Digunakan
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div id="aiExtractionAlert" class="mt-2 d-none"></div>
                        </div>
                    </div>

                    <!-- 1. Metadata Nota Dinas -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fas fa-file-alt text-primary me-2"></i>1. Identitas Nota Dinas
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="nd_nomor" class="form-label small fw-semibold">Nomor Nota Dinas <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_nota_dinas" id="nd_nomor" class="form-control form-control-sm" value="{{ $aiSetting->nomor_surat_prefix }}" placeholder="Contoh: WP.12-UM.01.01-..." required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_tanggal" class="form-label small fw-semibold">Tanggal Nota Dinas <span class="text-danger">*</span></label>
                            <input type="text" name="tanggal_nota_dinas" id="nd_tanggal" class="form-control form-control-sm" value="{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_yth" class="form-label small fw-semibold">Yth. (Tujuan) <span class="text-danger">*</span></label>
                            <input type="text" name="yth_tujuan" id="nd_yth" class="form-control form-control-sm" value="{{ $aiSetting->yth_tujuan }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_dari" class="form-label small fw-semibold">Dari (Pengirim) <span class="text-danger">*</span></label>
                            <input type="text" name="dari_pengirim" id="nd_dari" class="form-control form-control-sm" value="{{ $aiSetting->dari_pengirim }}" required>
                        </div>
                        <div class="col-md-8">
                            <label for="nd_hal" class="form-label small fw-semibold">Hal / Perihal <span class="text-danger">*</span></label>
                            <input type="text" name="hal_surat" id="nd_hal" class="form-control form-control-sm" value="Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di {{ $sidang->upt?->nama_upt }}" required>
                        </div>
                    </div>

                    <!-- 2. Rujukan Surat Undangan UPT -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fas fa-envelope-open-text text-primary me-2"></i>2. Rujukan Surat Undangan UPT
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="nd_upt_nama" class="form-label small fw-semibold">Nama UPT Pengirim <span class="text-danger">*</span></label>
                            <input type="text" name="upt_nama" id="nd_upt_nama" class="form-control form-control-sm" value="{{ $sidang->upt?->nama_upt }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_nomor_undangan" class="form-label small fw-semibold">Nomor Surat Undangan UPT <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_undangan" id="nd_nomor_undangan" class="form-control form-control-sm" value="{{ $sidang->nomor_undangan }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_tanggal_surat_upt" class="form-label small fw-semibold">Tanggal Surat Undangan <span class="text-danger">*</span></label>
                            <input type="text" name="tanggal_surat_upt" id="nd_tanggal_surat_upt" class="form-control form-control-sm" value="{{ $sidang->tanggal_surat ? $sidang->tanggal_surat->translatedFormat('d F Y') : '-' }}" required>
                        </div>
                        <div class="col-md-12">
                            <label for="nd_hal_undangan" class="form-label small fw-semibold">Perihal Surat Undangan</label>
                            <input type="text" name="hal_undangan" id="nd_hal_undangan" class="form-control form-control-sm" value="Undangan Sidang TPP" required>
                        </div>
                    </div>

                    <!-- 3. Rincian Pelaksanaan Sidang -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fas fa-calendar-check text-primary me-2"></i>3. Jadwal &amp; Tempat Pelaksanaan
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="nd_hari_tanggal" class="form-label small fw-semibold">Hari / Tanggal Sidang <span class="text-danger">*</span></label>
                            <input type="text" name="hari_tanggal_pelaksanaan" id="nd_hari_tanggal" class="form-control form-control-sm" value="{{ $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_jam" class="form-label small fw-semibold">Pukul / Jam Sidang <span class="text-danger">*</span></label>
                            <input type="text" name="jam_pelaksanaan" id="nd_jam" class="form-control form-control-sm" value="{{ substr((string)$sidang->jam_mulai, 0, 5) }} s.d selesai" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_tempat" class="form-label small fw-semibold">Tempat / Ruangan Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="text" name="tempat_pelaksanaan" id="nd_tempat" class="form-control form-control-sm" value="{{ !empty($sidang->link_daring) ? 'Daring / Zoom Cloud Meeting' : 'Ruang Sidang TPP ' . $sidang->upt?->nama_upt }}" required>
                        </div>
                    </div>

                    <!-- 4. Pilih Pejabat / Pegawai Kanwil yang Ditugaskan -->
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-users text-primary me-2"></i>4. Pejabat / Pegawai Kanwil yang Ditugaskan
                        </h6>
                        <a href="{{ route('ai-setting.index') }}" target="_blank" class="small text-decoration-none">
                            <i class="fas fa-cog me-1"></i>Kelola Master Pegawai
                        </a>
                    </div>

                    <div class="card bg-light border-0 mb-3 p-3">
                        <label class="form-label small fw-bold text-dark mb-2">Pilih dari Master Personil Pegawai Kanwil:</label>
                        <div class="row g-2">
                            @forelse($pegawaiKanwil as $peg)
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-white rounded border">
                                    <input class="form-check-input ms-1 me-2" type="checkbox" name="pegawai_ids[]" value="{{ $peg->id }}" id="chk_peg_{{ $peg->id }}">
                                    <label class="form-check-label small" for="chk_peg_{{ $peg->id }}">
                                        <b>{{ $peg->nama }}</b>
                                        <div class="text-muted" style="font-size: 11px;">
                                            NIP. {{ $peg->nip ?: '-' }} &bull; <i>{{ $peg->jabatan }}</i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 text-muted small">
                                <em>Belum ada master personil pegawai Kanwil. Silakan isi personil tambahan di bawah atau daftarkan di menu Master Data.</em>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Tambahan Personil Manual -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-secondary mb-0">Atau Tambah Personil Tambahan Secara Manual (Opsional):</label>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="tambahBarisManualPegawai()" style="font-size: 11px;">
                                <i class="fas fa-plus me-1"></i>Tambah Baris Pegawai
                            </button>
                        </div>
                        <div id="containerManualPegawai" class="row g-2 mt-1">
                            <!-- Baris manual di-append di sini -->
                        </div>
                    </div>

                    <!-- 5. Pejabat Penandatangan & Toggle NIP -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fas fa-signature text-primary me-2"></i>5. Tanda Tangan Nota Dinas
                    </h6>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="nd_ttd_nama" class="form-label small fw-semibold">Nama Penandatangan <span class="text-danger">*</span></label>
                            <input type="text" name="penandatangan_nama" id="nd_ttd_nama" class="form-control form-control-sm" value="{{ $aiSetting->penandatangan_nama }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_ttd_jabatan" class="form-label small fw-semibold">Jabatan Penandatangan <span class="text-danger">*</span></label>
                            <input type="text" name="penandatangan_jabatan" id="nd_ttd_jabatan" class="form-control form-control-sm" value="{{ $aiSetting->penandatangan_jabatan }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nd_ttd_nip" class="form-label small fw-semibold">NIP Penandatangan (Opsional)</label>
                            <input type="text" name="penandatangan_nip" id="nd_ttd_nip" class="form-control form-control-sm" value="{{ $aiSetting->penandatangan_nip }}">
                        </div>
                        <div class="col-12">
                            <div class="card bg-light border p-2.5">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="tampilkan_nip" id="nd_tampilkan_nip" value="1" {{ $aiSetting->tampilkan_nip ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-dark" for="nd_tampilkan_nip">
                                        Sertakan / Tampilkan NIP Penandatangan pada Dokumen
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    Saat ini NIP penandatangan <b>dinonaktifkan / tidak digunakan</b> pada format tanda tangan. Aktifkan saklar ini jika dokumen ingin menampilkan NIP di bawah nama.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="preview" value="1" formtarget="_blank" class="btn btn-outline-primary btn-sm fw-semibold">
                            <i class="fas fa-eye me-1"></i>Pratinjau Cetak
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                            <i class="fas fa-file-word me-1"></i>Unduh Word (.doc)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModalNotaDinas(sidangId) {
        const form = document.getElementById('formNotaDinasModal');
        if (form) {
            form.action = '/sidang-tpp/' + sidangId + '/nota-dinas/generate';
            form.dataset.sidangId = sidangId;
        }
        const btnAi = document.getElementById('btnEkstrakAiModal');
        if (btnAi) {
            btnAi.onclick = function() { ekstrakUndanganAiModal(sidangId); };
        }

        if (typeof getSidangById === 'function') {
            const s = getSidangById(sidangId);
            if (s) {
                if (s.nomor_undangan) document.getElementById('nd_nomor_undangan').value = s.nomor_undangan;
                if (s.tanggal_surat) document.getElementById('nd_tanggal_surat_upt').value = s.tanggal_surat;
                if (s.upt) {
                    document.getElementById('nd_upt_nama').value = s.upt.nama_upt;
                    document.getElementById('nd_hal').value = 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di ' + s.upt.nama_upt;
                }
                if (s.tanggal_sidang) {
                    document.getElementById('nd_hari_tanggal').value = new Date(s.tanggal_sidang).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                }
                if (s.jam_mulai) {
                    document.getElementById('nd_jam').value = s.jam_mulai.substring(0, 5) + ' WIB s.d selesai';
                }
                if (s.metode_sidang === 'daring') {
                    document.getElementById('nd_tempat').value = 'Virtual / Zoom Cloud Meeting (' + (s.upt ? s.upt.nama_upt : '') + ')';
                } else if (s.upt) {
                    document.getElementById('nd_tempat').value = 'Ruang Sidang TPP ' + s.upt.nama_upt;
                }
            }
        }

        const modalEl = document.getElementById('modalNotaDinas');
        if (modalEl) {
            const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            m.show();
        }
    }

    function ekstrakUndanganAiModal(sidangId) {
        const targetSidangId = sidangId || (document.getElementById('formNotaDinasModal') ? document.getElementById('formNotaDinasModal').dataset.sidangId : 0);
        const btn = document.getElementById('btnEkstrakAiModal');
        const alertArea = document.getElementById('aiExtractionAlert');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menganalisis PDF...';
        alertArea.classList.add('d-none');

        fetch('/sidang-tpp/' + targetSidangId + '/nota-dinas/parse-ai', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-1"></i>Analisis Undangan via AI';
            alertArea.classList.remove('d-none');

            if (res.success && res.data) {
                const d = res.data;
                if (d.nomor_undangan) document.getElementById('nd_nomor_undangan').value = d.nomor_undangan;
                if (d.tanggal_surat) document.getElementById('nd_tanggal_surat_upt').value = d.tanggal_surat;
                if (d.hal) document.getElementById('nd_hal_undangan').value = d.hal;
                if (d.asal_upt) {
                    document.getElementById('nd_upt_nama').value = d.asal_upt;
                    document.getElementById('nd_hal').value = 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di ' + d.asal_upt;
                }
                if (d.hari_tanggal_sidang) document.getElementById('nd_hari_tanggal').value = d.hari_tanggal_sidang;
                if (d.jam_mulai) document.getElementById('nd_jam').value = d.jam_mulai;
                if (d.tempat) document.getElementById('nd_tempat').value = d.tempat;

                alertArea.innerHTML = `
                    <div class="alert alert-success py-1.5 px-2.5 small mb-0 d-flex align-items-center">
                        <i class="fas fa-check-circle me-2 text-success"></i>
                        <div><b>Ekstraksi AI Berhasil:</b> Rincian surat undangan berhasil diisi secara otomatis. Anda dapat meneliti atau mengubahnya jika diperlukan.</div>
                    </div>
                `;
            } else {
                alertArea.innerHTML = `
                    <div class="alert alert-warning py-1.5 px-2.5 small mb-0 d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                        <div>${res.message || 'Tidak dapat mengekstrak berkas. Silakan lanjutkan dengan pengisian manual.'}</div>
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-1"></i>Analisis Undangan via AI';
            alertArea.classList.remove('d-none');
            alertArea.innerHTML = `
                <div class="alert alert-danger py-1.5 px-2.5 small mb-0 d-flex align-items-center">
                    <i class="fas fa-times-circle me-2 text-danger"></i>
                    <div><b>Terjadi Kesalahan:</b> ${err.message}. Silakan isi form rujukan secara manual.</div>
                </div>
            `;
        });
    }

    let manualPegawaiCount = 0;
    function tambahBarisManualPegawai() {
        manualPegawaiCount++;
        const container = document.getElementById('containerManualPegawai');
        const row = document.createElement('div');
        row.className = 'col-12 border rounded p-2 bg-white shadow-sm mb-2';
        row.id = 'manual_row_' + manualPegawaiCount;
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted" style="font-size: 11px;">Nama Lengkap & Gelar</label>
                    <input type="text" name="manual_nama[]" class="form-control form-control-sm" placeholder="Contoh: Nama Pegawai, S.H" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted" style="font-size: 11px;">NIP</label>
                    <input type="text" name="manual_nip[]" class="form-control form-control-sm" placeholder="Contoh: 199...">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" style="font-size: 11px;">Jabatan</label>
                    <input type="text" name="manual_jabatan[]" class="form-control form-control-sm" placeholder="Contoh: Staf Bintorwasdal" required>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="hapusBarisManualPegawai(${manualPegawaiCount})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(row);
    }

    function hapusBarisManualPegawai(id) {
        const el = document.getElementById('manual_row_' + id);
        if (el) el.remove();
    }
</script>

