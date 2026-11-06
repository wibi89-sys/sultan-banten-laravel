<!-- Modal Interaktif Kirim Pengingat WhatsApp Langsung untuk Laporan Pending -->
<div class="modal fade" id="modalWaPengingat" tabindex="-1" aria-labelledby="modalWaPengingatTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fab fa-whatsapp fa-lg text-white"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalWaPengingatTitle" style="font-size: 14.5px;">
                            Kirim Pengingat WhatsApp ke Operator UPT
                        </h6>
                        <small class="text-white-50" style="font-size: 11px;">
                            Kirim pesan pengingat resmi langsung ke WhatsApp operator terkait laporan yang belum diunggah
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">
                <div id="rem_alert_area" class="d-none mb-3"></div>

                <!-- Info UPT & Pilihan Operator Tujuan -->
                <div class="card border border-success-subtle bg-success-subtle bg-opacity-10 mb-3">
                    <div class="card-body p-2.5">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <span class="small text-muted d-block" style="font-size: 10.5px;">Satuan Kerja (UPT) Tujuan:</span>
                                <div class="fw-bold text-dark fs-6" id="rem_label_upt">-</div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" id="rem_label_peran" style="font-size: 10px;">
                                    UPT Terkait
                                </span>
                            </div>
                            <div class="col-md-6">
                                <label for="rem_operator_select" class="form-label small fw-semibold mb-1" style="font-size: 11px;">
                                    Pilih Operator Penerima di UPT ini:
                                </label>
                                <select id="rem_operator_select" class="form-select form-select-sm" style="font-size: 12px;" onchange="handleOperatorReminderSelect(this)">
                                    <option value="">-- Memuat daftar operator... --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Nomor WhatsApp & Konteks Berkas -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="rem_target_phone" class="form-label small fw-semibold mb-1" style="font-size: 11.5px;">
                            Nomor WhatsApp Tujuan: <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fas fa-phone-alt"></i></span>
                            <input type="text" id="rem_target_phone" class="form-control form-control-sm font-monospace" placeholder="Contoh: 081234567890 atau 628..." required>
                        </div>
                        <small class="text-muted" style="font-size: 10.5px;">Nomor otomatis terisi dari akun profil operator UPT.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1" style="font-size: 11.5px;">Kategori Berkas Yang Belum Selesai:</label>
                        <div class="p-1.5 rounded bg-light border text-dark fw-semibold small text-truncate" id="rem_label_berkas" style="font-size: 12px;">
                            -
                        </div>
                    </div>
                </div>

                <!-- Editor Pesan WhatsApp Bebas (Bisa Di-edit Bebas) -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                        <label for="rem_message" class="form-label small fw-semibold mb-0" style="font-size: 11.5px;">
                            Isi Pesan Pengingat WhatsApp (Dapat Disunting Bebas): <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-2 fw-semibold" id="btnSimpanTemplateDefault" onclick="simpanSebagaiTemplateDefault()" title="Simpan isi pesan saat ini sebagai format default ke depan" style="font-size: 11px;">
                                <i class="fas fa-save me-1"></i>Simpan sbg Template Default
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="resetDrafPengingatKeBaku()" title="Kembalikan isi pesan ke template sistem" style="font-size: 11px;">
                                <i class="fas fa-undo me-1"></i>Reset Pesan
                            </button>
                            <div class="vr mx-1"></div>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertFormatReminder('*', '*')" title="Format Tebal (*teks*)">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertFormatReminder('_', '_')" title="Format Miring (_teks_)">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-1.5" onclick="insertFormatReminder('~', '~')" title="Format Coret (~teks~)">
                                <i class="fas fa-strikethrough"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Keterangan Tag Dinamis (Klik untuk menyisipkan ke kursor) -->
                    <div class="p-2 mb-2 rounded bg-light border border-dashed" id="rem_tags_container">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="badge bg-primary text-white" style="font-size: 10px;">
                                    <i class="fas fa-tags me-1"></i>Tag Dinamis
                                </span>
                                <small class="fw-semibold text-secondary" style="font-size: 11px;">
                                    Klik tag di bawah untuk menyisipkan otomatis ke teks:
                                </small>
                            </div>
                            <small class="text-muted fst-italic" style="font-size: 10px;">Otomatis diganti data riil saat terkirim</small>
                        </div>
                        <div class="d-flex flex-wrap gap-1" id="rem_tags_list">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <textarea id="rem_message" class="form-control font-monospace" rows="8" style="font-size: 12px; line-height: 1.45;" placeholder="Tuliskan pesan pengingat bebas di sini..."></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-1">
                        <small class="text-muted" style="font-size: 10.5px;">
                            <i class="fas fa-magic text-warning me-1"></i>Pesan disusun otomatis dari template pengingat dokumen ini. Anda bebas mengedit sebelum mengirim, atau klik <b>"Simpan sbg Template Default"</b> agar perubahan tersimpan khusus untuk jenis dokumen ini.
                        </small>
                    </div>
                </div>

                <input type="hidden" id="rem_recipient_name" value="">
                <input type="hidden" id="rem_context_type" value="laporan_pending">
            </div>

            <div class="modal-footer bg-light py-2 px-3 justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
                <div class="d-flex gap-2">
                    <!-- OPSI 1: BUKA VIA WHATSAPP WEB / APLIKASI -->
                    <button type="button" class="btn btn-outline-success btn-sm fw-semibold shadow-sm" onclick="kirimViaWaWeb()">
                        <i class="fab fa-whatsapp me-1 text-success"></i>Buka WhatsApp Web (wa.me)
                    </button>
                    <!-- OPSI 2: KIRIM OTOMATIS VIA GATEWAY RESMI SISTEM -->
                    <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm px-3" id="btnKirimGateway" onclick="kirimViaGateway()">
                        <i class="fas fa-paper-plane me-1.5"></i>Kirim via Gateway
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentReminderOperators = [];
    let currentReminderContext = {
        tipeKonteks: '',
        refId: null,
        uptId: null,
        peranUpt: '',
        detailData: {},
        namaBerkas: '',
        namaUpt: '',
        template_sidang_ba: '',
        template_mutasi: '',
        template_mutasi_pemindahan: '',
        template_mutasi_penerimaan: '',
        template_mutasi_bast: ''
    };

    /**
     * Tampilkan tombol-tombol tag dinamis yang relevan sesuai konteks pengingat.
     */
    function updateTagsList(tipeKonteks) {
        const container = document.getElementById('rem_tags_list');
        if (!container) return;

        let tags = [];
        if (tipeKonteks === 'sidang_ba') {
            tags = [
                { tag: '{nama_upt}', desc: 'Nama UPT' },
                { tag: '{nomor_undangan}', desc: 'No. Undangan Sidang' },
                { tag: '{tanggal_sidang}', desc: 'Tanggal Sidang' },
                { tag: '{link_sidang}', desc: 'Tautan Unggah BA' },
            ];
        } else {
            tags = [
                { tag: '{nama_berkas}', desc: 'Nama Berkas' },
                { tag: '{nama_upt}', desc: 'Nama UPT' },
                { tag: '{nomor_surat}', desc: 'No. Surat Kanwil' },
                { tag: '{total_wbp}', desc: 'Jumlah Narapidana' },
                { tag: '{link_mutasi}', desc: 'Tautan Unggah Berkas' },
            ];
        }

        container.innerHTML = tags.map(t => `
            <button type="button" class="btn btn-white btn-xs border py-0.5 px-2 shadow-2xs text-start text-dark d-inline-flex align-items-center gap-1" 
                    onclick="insertWaTag('${t.tag}')" title="Klik untuk menyisipkan ${t.tag} ke posisi kursor editor">
                <code class="text-primary fw-bold" style="font-size: 11px;">${t.tag}</code>
                <span class="text-muted" style="font-size: 10px;">(${t.desc})</span>
            </button>
        `).join('');
    }

    /**
     * Sisipkan tag variabel ke posisi kursor teks editor.
     */
    function insertWaTag(tag) {
        const area = document.getElementById('rem_message');
        if (!area) return;

        const start = area.selectionStart;
        const end = area.selectionEnd;
        const text = area.value;

        area.value = text.substring(0, start) + tag + text.substring(end);
        area.focus();
        area.setSelectionRange(start + tag.length, start + tag.length);
    }

    /**
     * Buka modal pengingat WhatsApp dengan auto-detect operator dan pre-filled message kontekstual.
     * 
     * @param {string} tipeKonteks - 'mutasi_pemindahan', 'mutasi_penerimaan', 'mutasi_bast', 'sidang_ba'
     * @param {number} refId - ID usulan mutasi atau ID sidang TPP
     * @param {number} uptId - ID Satker UPT yang bertanggung jawab
     * @param {string} peranUpt - 'UPT Pengirim / Asal', 'UPT Penerima / Tujuan', dll
     * @param {object} detailData - Objek info tambahan (nomor_surat, total_wbp, agenda, dll)
     */
    function bukaModalWaPengingat(tipeKonteks, refId, uptId, peranUpt, detailData = {}) {
        const modalEl = document.getElementById('modalWaPengingat');
        if (!modalEl) return;

        // Reset alert & inputs
        const alertArea = document.getElementById('rem_alert_area');
        if (alertArea) {
            alertArea.classList.add('d-none');
            alertArea.innerHTML = '';
        }

        document.getElementById('rem_label_peran').innerText = peranUpt || 'Satker Bertanggung Jawab';
        document.getElementById('rem_context_type').value = tipeKonteks;
        document.getElementById('rem_target_phone').value = '';
        document.getElementById('rem_recipient_name').value = '';

        // Tampilkan daftar tag yang sesuai konteks
        updateTagsList(tipeKonteks);

        // Tentukan teks nama berkas
        let namaBerkas = 'Laporan Pelaksanaan';
        if (tipeKonteks === 'mutasi_pemindahan') {
            namaBerkas = 'Laporan Pemindahan Mutasi Narapidana (UPT Pengirim)';
        } else if (tipeKonteks === 'mutasi_penerimaan') {
            namaBerkas = 'Laporan Penerimaan Mutasi Narapidana (UPT Penerima)';
        } else if (tipeKonteks === 'mutasi_bast') {
            namaBerkas = 'Berita Acara Serah Terima (BAST)';
        } else if (tipeKonteks === 'sidang_ba') {
            namaBerkas = 'Berita Acara (BA) Pelaksanaan Sidang TPP UPT';
        }
        document.getElementById('rem_label_berkas').innerText = namaBerkas;

        // Simpan state kontekstual
        currentReminderContext.tipeKonteks = tipeKonteks;
        currentReminderContext.refId = refId;
        currentReminderContext.uptId = uptId;
        currentReminderContext.peranUpt = peranUpt;
        currentReminderContext.detailData = detailData || {};
        currentReminderContext.namaBerkas = namaBerkas;

        // Load operators dari server via AJAX
        const selectOp = document.getElementById('rem_operator_select');
        selectOp.innerHTML = '<option value="">Memuat operator UPT...</option>';

        const uptUrl = @json(url('/wa/upt-operators')) + '/' + uptId;
        fetch(uptUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(async res => {
                const text = await res.text();
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error(`Gagal memuat operator UPT (status HTTP ${res.status}).`);
                }
                return data;
            })
            .then(data => {
                if (data && data.success && data.operators) {
                    currentReminderOperators = data.operators;
                    currentReminderContext.namaUpt = data.upt_nama || 'UPT Terkait';
                    currentReminderContext.template_sidang_ba = data.template_sidang_ba || '';
                    currentReminderContext.template_mutasi = data.template_mutasi || '';
                    currentReminderContext.template_mutasi_pemindahan = data.template_mutasi_pemindahan || '';
                    currentReminderContext.template_mutasi_penerimaan = data.template_mutasi_penerimaan || '';
                    currentReminderContext.template_mutasi_bast = data.template_mutasi_bast || '';

                    document.getElementById('rem_label_upt').innerText = currentReminderContext.namaUpt;

                    selectOp.innerHTML = '';
                    if (data.operators.length === 0) {
                        selectOp.innerHTML = '<option value="">Belum ada operator terdaftar di UPT ini</option>';
                    } else {
                        data.operators.forEach((op, idx) => {
                            const opt = document.createElement('option');
                            opt.value = idx;
                            opt.text = `${op.nama} (${op.role}) - ${op.no_wa}`;
                            selectOp.appendChild(opt);
                        });
                        // Pilih operator pertama otomatis
                        handleOperatorReminderSelect(selectOp);
                    }

                    // Susun draf pesan kontekstual
                    susunDrafPesanPengingat();
                } else {
                    selectOp.innerHTML = '<option value="">' + (data?.message || 'Data operator tidak ditemukan') + '</option>';
                }
            })
            .catch(err => {
                console.error(err);
                selectOp.innerHTML = '<option value="">Gagal memuat operator: ' + err.message + '</option>';
            });

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function handleOperatorReminderSelect(selectEl) {
        const idx = selectEl.value;
        if (idx !== '' && currentReminderOperators[idx]) {
            const op = currentReminderOperators[idx];
            document.getElementById('rem_target_phone').value = op.no_wa !== '-' ? op.no_wa : '';
            document.getElementById('rem_recipient_name').value = op.nama;
        }
    }

    function susunDrafPesanPengingat() {
        const textarea = document.getElementById('rem_message');
        if (!textarea) return;

        const ctx = currentReminderContext;
        const appUrl = window.location.origin;

        if (ctx.tipeKonteks === 'sidang_ba') {
            let tpl = ctx.template_sidang_ba || '';
            if (!tpl) {
                tpl = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                      `*Divisi Pemasyarakatan*\n` +
                      `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                      `Hal: *Pengingat Unggah Berita Acara (BA) Sidang TPP*\n\n` +
                      `Yth. Rekan Operator & Tim TPP *{nama_upt}*,\n\n` +
                      `Berdasarkan jadwal pelaksanaan Sidang TPP UPT:\n` +
                      `• *No. Undangan Sidang:* {nomor_undangan}\n` +
                      `• *Tanggal Pelaksanaan:* {tanggal_sidang}\n\n` +
                      `Kami memohon kesediaan rekan operator untuk segera mengunggah berkas *Berita Acara (BA) Hasil Sidang TPP* ke dalam sistem SULTAN BANTEN guna penyusunan Surat Tugas dan Laporan Pembinaan Sidang Tingkat Wilayah.\n\n` +
                      `🔗 *Tautan Unggah BA:* \n👉 {link_sidang}\n\n` +
                      `Atas perhatian dan kerja samanya, diucapkan terima kasih.\n` +
                      `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                      `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
            }

            const noUndangan = ctx.detailData.nomor_undangan || '-';
            const tglSidang = ctx.detailData.tanggal_sidang || 'Jadwal Terdaftar';
            const linkSidang = `${appUrl}/sidang-tpp/${ctx.refId}`;

            let text = tpl
                .replace(/{nama_upt}/g, ctx.namaUpt)
                .replace(/{nomor_undangan}/g, noUndangan)
                .replace(/{tanggal_sidang}/g, tglSidang)
                .replace(/{link_sidang}/g, linkSidang);

            textarea.value = text;
        } else {
            let tpl = '';
            if (ctx.tipeKonteks === 'mutasi_pemindahan') {
                tpl = ctx.template_mutasi_pemindahan || '';
                if (!tpl) {
                    tpl = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                          `*Divisi Pemasyarakatan*\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `Hal: *Pengingat Unggah Laporan Pemindahan Narapidana*\n\n` +
                          `Yth. Rekan Operator & Tim Mutasi *{nama_upt}*,\n\n` +
                          `Sehubungan dengan pelaksanaan mutasi narapidana berikut:\n` +
                          `• *No. Surat Kanwil:* {nomor_surat}\n` +
                          `• *Jumlah Narapidana:* {total_wbp}\n` +
                          `• *Status:* Menunggu unggah Laporan Pemindahan dari UPT Pengirim.\n\n` +
                          `Mohon bantuan rekan operator untuk segera mengunggah berkas *Laporan Pemindahan* ke dalam Sistem SULTAN BANTEN agar proses pemindahan terpantau secara real-time.\n\n` +
                          `🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n` +
                          `Terima kasih atas dedikasi dan kerja sama yang baik.\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
                }
            } else if (ctx.tipeKonteks === 'mutasi_penerimaan') {
                tpl = ctx.template_mutasi_penerimaan || '';
                if (!tpl) {
                    tpl = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                          `*Divisi Pemasyarakatan*\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `Hal: *Pengingat Unggah Laporan Penerimaan Narapidana*\n\n` +
                          `Yth. Rekan Operator & Tim Mutasi *{nama_upt}*,\n\n` +
                          `Sehubungan dengan penerimaan mutasi narapidana berikut:\n` +
                          `• *No. Surat Kanwil:* {nomor_surat}\n` +
                          `• *Jumlah Narapidana:* {total_wbp}\n` +
                          `• *Status:* Menunggu unggah Laporan Penerimaan dari UPT Tujuan.\n\n` +
                          `Mohon bantuan rekan operator untuk segera mengunggah berkas *Laporan Penerimaan* ke dalam Sistem SULTAN BANTEN sebagai bukti fisik narapidana telah diterima dengan aman.\n\n` +
                          `🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n` +
                          `Terima kasih atas perhatian dan kerja sama yang baik.\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
                }
            } else if (ctx.tipeKonteks === 'mutasi_bast') {
                tpl = ctx.template_mutasi_bast || '';
                if (!tpl) {
                    tpl = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                          `*Divisi Pemasyarakatan*\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `Hal: *Pengingat Unggah Berita Acara Serah Terima (BAST)*\n\n` +
                          `Yth. Rekan Operator & Tim Mutasi *{nama_upt}*,\n\n` +
                          `Sehubungan dengan penyelesaian mutasi narapidana berikut:\n` +
                          `• *No. Surat Kanwil:* {nomor_surat}\n` +
                          `• *Jumlah Narapidana:* {total_wbp}\n` +
                          `• *Status:* Menunggu unggah Berita Acara Serah Terima (BAST).\n\n` +
                          `Mohon kerja sama rekan operator untuk segera mengunggah dokumen *BAST* yang telah ditandatangani kedua belah pihak ke dalam Sistem SULTAN BANTEN untuk menuntaskan status mutasi narapidana.\n\n` +
                          `🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n` +
                          `Terima kasih atas kerja sama yang baik.\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
                }
            } else {
                tpl = ctx.template_mutasi || '';
                if (!tpl) {
                    tpl = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                          `*Divisi Pemasyarakatan*\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `Hal: *Pengingat Unggah {nama_berkas}*\n\n` +
                          `Yth. Rekan Operator *{nama_upt}*,\n\n` +
                          `Sehubungan dengan pelaksanaan mutasi narapidana berikut:\n` +
                          `• *No. Surat Kanwil:* {nomor_surat}\n` +
                          `• *Jumlah Narapidana:* {total_wbp}\n` +
                          `• *Status:* Memerlukan kelengkapan berkas pelaksanaan.\n\n` +
                          `Mohon bantuan rekan operator untuk segera mengunggah berkas *{nama_berkas}* ke dalam Sistem SULTAN BANTEN agar proses verifikasi dan laporan rekapitulasi dapat dituntaskan.\n\n` +
                          `🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n` +
                          `Terima kasih atas dedikasi dan kerja sama yang baik.\n` +
                          `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                          `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
                }
            }

            const noSurat = ctx.detailData.nomor_surat || '(Sesuai Surat Persetujuan Kanwil)';
            const totalWbp = ctx.detailData.total_wbp ? `${ctx.detailData.total_wbp} Orang WBP` : 'WBP Terkait';
            const linkMutasi = `${appUrl}/mutasi/${ctx.refId}`;

            let text = tpl
                .replace(/{nama_berkas}/g, ctx.namaBerkas)
                .replace(/{nama_upt}/g, ctx.namaUpt)
                .replace(/{nomor_surat}/g, noSurat)
                .replace(/{total_wbp}/g, totalWbp)
                .replace(/{link_mutasi}/g, linkMutasi);

            textarea.value = text;
        }
    }

    function resetDrafPengingatKeBaku() {
        if (confirm('Kembalikan teks pesan pengingat ke format template sistem?')) {
            susunDrafPesanPengingat();
        }
    }

    function simpanSebagaiTemplateDefault() {
        const textarea = document.getElementById('rem_message');
        const msg = textarea ? textarea.value.trim() : '';
        if (!msg) {
            alert('Isi pesan tidak boleh kosong.');
            return;
        }

        const ctx = currentReminderContext;
        const type = ctx.tipeKonteks || 'mutasi';
        const isSidang = (type === 'sidang_ba');
        const appUrl = window.location.origin;

        // Ubah nilai-nilai dinamis kembali menjadi tag variabel jika ada di dalam teks
        let tplToSave = msg;

        if (isSidang) {
            const linkSidang = `${appUrl}/sidang-tpp/${ctx.refId}`;
            const noUndangan = ctx.detailData.nomor_undangan || '';
            const tglSidang = ctx.detailData.tanggal_sidang || '';

            if (ctx.namaUpt) {
                tplToSave = tplToSave.split(ctx.namaUpt).join('{nama_upt}');
            }
            if (linkSidang) {
                tplToSave = tplToSave.split(linkSidang).join('{link_sidang}');
            }
            if (noUndangan && noUndangan !== '-') {
                tplToSave = tplToSave.split(noUndangan).join('{nomor_undangan}');
            }
            if (tglSidang && tglSidang !== 'Jadwal Terdaftar') {
                tplToSave = tplToSave.split(tglSidang).join('{tanggal_sidang}');
            }
        } else {
            const linkMutasi = `${appUrl}/mutasi/${ctx.refId}`;
            const noSurat = ctx.detailData.nomor_surat || '';
            const totalWbp = ctx.detailData.total_wbp ? `${ctx.detailData.total_wbp} Orang WBP` : '';

            if (ctx.namaBerkas) {
                tplToSave = tplToSave.split(ctx.namaBerkas).join('{nama_berkas}');
            }
            if (ctx.namaUpt) {
                tplToSave = tplToSave.split(ctx.namaUpt).join('{nama_upt}');
            }
            if (linkMutasi) {
                tplToSave = tplToSave.split(linkMutasi).join('{link_mutasi}');
            }
            if (noSurat && noSurat !== '(Sesuai Surat Persetujuan Kanwil)') {
                tplToSave = tplToSave.split(noSurat).join('{nomor_surat}');
            }
            if (totalWbp) {
                tplToSave = tplToSave.split(totalWbp).join('{total_wbp}');
            }
        }

        let confirmMsg = 'Simpan isi pesan saat ini sebagai Template Default Pengingat?';
        if (type === 'sidang_ba') {
            confirmMsg = 'Simpan isi pesan saat ini sebagai Template Default Pengingat Upload Berita Acara (BA) Sidang TPP?';
        } else if (type === 'mutasi_pemindahan') {
            confirmMsg = 'Simpan isi pesan saat ini sebagai Template Default Pengingat Laporan Pemindahan (UPT Pengirim)?';
        } else if (type === 'mutasi_penerimaan') {
            confirmMsg = 'Simpan isi pesan saat ini sebagai Template Default Pengingat Laporan Penerimaan (UPT Penerima)?';
        } else if (type === 'mutasi_bast') {
            confirmMsg = 'Simpan isi pesan saat ini sebagai Template Default Pengingat Berita Acara Serah Terima (BAST)?';
        }

        if (!confirm(confirmMsg)) {
            return;
        }

        const btn = document.getElementById('btnSimpanTemplateDefault');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan...';
        }

        const alertArea = document.getElementById('rem_alert_area');
        const saveUrl = @json(route('wa.save-reminder-template'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                type: type,
                template: tplToSave
            })
        })
        .then(async res => {
            const text = await res.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                if (res.status === 419) {
                    throw new Error('Sesi halaman telah kedaluwarsa (419 CSRF Token Expired). Silakan refresh/muat ulang halaman dan coba lagi.');
                }
                if (res.status === 404) {
                    throw new Error('Alamat rute penyimpanan template belum terdaftar di cache (404). Silakan jalankan `php artisan optimize:clear` di hosting.');
                }
                if (res.status === 403) {
                    throw new Error('Akses ditolak (403 Forbidden). Akun Anda tidak memiliki hak izin untuk mengubah template ini.');
                }
                throw new Error(`Server mengembalikan error status ${res.status}.`);
            }

            if (!res.ok) {
                throw new Error((data && data.message) ? data.message : `Gagal menyimpan template (status HTTP ${res.status})`);
            }

            return data;
        })
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan sbg Template Default';
            }

            if (data && data.success) {
                if (type === 'sidang_ba') {
                    ctx.template_sidang_ba = tplToSave;
                } else if (type === 'mutasi_pemindahan') {
                    ctx.template_mutasi_pemindahan = tplToSave;
                } else if (type === 'mutasi_penerimaan') {
                    ctx.template_mutasi_penerimaan = tplToSave;
                } else if (type === 'mutasi_bast') {
                    ctx.template_mutasi_bast = tplToSave;
                } else {
                    ctx.template_mutasi = tplToSave;
                }

                if (alertArea) {
                    alertArea.classList.remove('d-none');
                    alertArea.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-0 d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
                            <div><b>Berhasil Disimpan:</b> ${data.message}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                } else {
                    alert(data.message);
                }
            } else {
                alert('Gagal menyimpan template: ' + (data?.message || 'Terjadi kesalahan sistem.'));
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan sbg Template Default';
            }
            alert('Gagal menyimpan template: ' + err.message);
        });
    }

    function insertFormatReminder(startTag, endTag) {
        const area = document.getElementById('rem_message');
        if (!area) return;

        const start = area.selectionStart;
        const end = area.selectionEnd;
        const text = area.value;
        const selected = text.substring(start, end);

        area.value = text.substring(0, start) + startTag + selected + endTag + text.substring(end);
        area.focus();
        area.setSelectionRange(start + startTag.length, end + startTag.length);
    }

    function kirimViaWaWeb() {
        const phone = document.getElementById('rem_target_phone').value.trim();
        const msg = document.getElementById('rem_message').value.trim();

        if (!phone) {
            alert('Silakan masukkan nomor WhatsApp tujuan.');
            return;
        }

        let clean = phone.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) {
            clean = '62' + clean.substring(1);
        }

        const url = 'https://wa.me/' + clean + '?text=' + encodeURIComponent(msg);
        window.open(url, '_blank');
    }

    function kirimViaGateway() {
        const phone = document.getElementById('rem_target_phone').value.trim();
        const msg = document.getElementById('rem_message').value.trim();
        const recipientName = document.getElementById('rem_recipient_name').value.trim();
        const uptName = document.getElementById('rem_label_upt').innerText.trim();
        const contextType = document.getElementById('rem_context_type').value;

        if (!phone) {
            alert('Silakan masukkan nomor WhatsApp tujuan.');
            return;
        }
        if (!msg) {
            alert('Pesan pengingat tidak boleh kosong.');
            return;
        }

        const btn = document.getElementById('btnKirimGateway');
        const alertArea = document.getElementById('rem_alert_area');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Mengirim Pesan...';

        const sendUrl = @json(route('wa.send-pending-reminder'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        fetch(sendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                target_phone: phone,
                message: msg,
                recipient_name: recipientName,
                upt_name: uptName,
                context_type: contextType
            })
        })
        .then(async res => {
            const text = await res.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                if (res.status === 419) {
                    throw new Error('Sesi halaman telah kedaluwarsa (419). Silakan refresh/muat ulang halaman.');
                }
                throw new Error(`Server mengembalikan error status ${res.status}.`);
            }
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1.5"></i>Kirim via Gateway';

            alertArea.classList.remove('d-none');
            if (data && data.success) {
                alertArea.innerHTML = `
                    <div class="alert alert-success py-2 px-3 small mb-0 d-flex align-items-center">
                        <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
                        <div><b>Berhasil Terkirim:</b> ${data.message}</div>
                    </div>
                `;
            } else {
                alertArea.innerHTML = `
                    <div class="alert alert-warning py-2 px-3 small mb-0 d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-lg me-2 text-warning"></i>
                        <div><b>Pemberitahuan Gateway:</b> ${data?.message || 'Gagal mengirim pesan.'}</div>
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1.5"></i>Kirim via Gateway';
            alertArea.classList.remove('d-none');
            alertArea.innerHTML = `
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center">
                    <i class="fas fa-times-circle fa-lg me-2 text-danger"></i>
                    <div><b>Kesalahan Sistem:</b> ${err.message}</div>
                </div>
            `;
        });
    }
</script>

