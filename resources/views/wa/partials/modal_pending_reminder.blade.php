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
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="rem_message" class="form-label small fw-semibold mb-0" style="font-size: 11.5px;">
                            Isi Pesan Pengingat WhatsApp (Dapat Disunting Bebas): <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-1">
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
                    <textarea id="rem_message" class="form-control font-monospace" rows="8" style="font-size: 12px; line-height: 1.45;" placeholder="Tuliskan pesan pengingat bebas di sini..."></textarea>
                    <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                        <i class="fas fa-magic text-warning me-1"></i>Pesan telah disusun otomatis sesuai data laporan, Anda bebas menambah arahan atau catatan khusus sebelum mengirim.
                    </small>
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

        // Tentukan teks nama berkas
        let namaBerkas = 'Laporan Pelaksanaan';
        if (tipeKonteks === 'mutasi_pemindahan') {
            namaBerkas = 'Laporan Pemindahan Mutasi Narapidana (UPT Pengirim)';
        } else if (tipeKonteks === 'mutasi_penerimaan') {
            namaBerkas = 'Laporan Penerimaan Mutasi Narapidana (UPT Penerima)';
        } else if (tipeKonteks === 'mutasi_bast') {
            namaBerkas = 'Berita Acara Serah Terima (BAST Bersama)';
        } else if (tipeKonteks === 'sidang_ba') {
            namaBerkas = 'Berita Acara (BA) Pelaksanaan Sidang TPP UPT';
        }
        document.getElementById('rem_label_berkas').innerText = namaBerkas;

        // Load operators dari server via AJAX
        const selectOp = document.getElementById('rem_operator_select');
        selectOp.innerHTML = '<option value="">Memuat operator UPT...</option>';

        fetch('/wa/upt-operators/' + uptId)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.operators) {
                    currentReminderOperators = data.operators;
                    document.getElementById('rem_label_upt').innerText = data.upt_nama || 'UPT';

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
                    susunDrafPesanPengingat(tipeKonteks, data.upt_nama, namaBerkas, refId, detailData);
                }
            })
            .catch(err => {
                console.error(err);
                selectOp.innerHTML = '<option value="">Gagal memuat operator</option>';
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

    function susunDrafPesanPengingat(tipe, namaUpt, namaBerkas, refId, detail) {
        const textarea = document.getElementById('rem_message');
        if (!textarea) return;

        const appUrl = window.location.origin;
        let template = '';

        if (tipe.startsWith('mutasi')) {
            const noSurat = detail.nomor_surat || '(Sesuai Surat Persetujuan Kanwil)';
            const totalWbp = detail.total_wbp ? `${detail.total_wbp} Orang WBP` : 'WBP Terkait';
            const linkMutasi = `${appUrl}/mutasi/${refId}`;

            template = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                       `*Divisi Pemasyarakatan*\n` +
                       `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                       `Hal: *Pengingat Unggah ${namaBerkas}*\n\n` +
                       `Yth. Rekan Operator *${namaUpt}*,\n\n` +
                       `Sehubungan dengan pelaksanaan mutasi narapidana berikut:\n` +
                       `• *No. Surat Kanwil:* ${noSurat}\n` +
                       `• *Jumlah Narapidana:* ${totalWbp}\n` +
                       `• *Status:* Memerlukan kelengkapan berkas pelaksanaan.\n\n` +
                       `Mohon bantuan rekan operator untuk segera mengunggah berkas *${namaBerkas}* ke dalam Sistem SULTAN BANTEN agar proses verifikasi dan laporan rekapitulasi dapat dituntaskan.\n\n` +
                       `🔗 *Tautan Unggah Dokumen:* \n👉 ${linkMutasi}\n\n` +
                       `Terima kasih atas dedikasi dan kerja sama yang baik.\n` +
                       `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                       `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
        } else if (tipe === 'sidang_ba') {
            const noUndangan = detail.nomor_undangan || '-';
            const tglSidang = detail.tanggal_sidang || 'Jadwal Terdaftar';
            const linkSidang = `${appUrl}/sidang-tpp/${refId}`;

            template = `📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n` +
                       `*Divisi Pemasyarakatan*\n` +
                       `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                       `Hal: *Pengingat Unggah Berita Acara (BA) Sidang TPP*\n\n` +
                       `Yth. Rekan Operator & Tim TPP *${namaUpt}*,\n\n` +
                       `Berdasarkan jadwal pelaksanaan Sidang TPP UPT:\n` +
                       `• *No. Undangan Sidang:* ${noUndangan}\n` +
                       `• *Tanggal Pelaksanaan:* ${tglSidang}\n\n` +
                       `Kami memohon kesediaan rekan operator untuk segera mengunggah berkas *Berita Acara (BA) Hasil Sidang TPP* ke dalam sistem SULTAN BANTEN guna penyusunan Surat Tugas dan Laporan Pembinaan Sidang Tingkat Wilayah.\n\n` +
                       `🔗 *Tautan Unggah BA:* \n👉 ${linkSidang}\n\n` +
                       `Atas perhatian dan kerja samanya, diucapkan terima kasih.\n` +
                       `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                       `_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._`;
        }

        textarea.value = template;
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

        fetch('/wa/send-pending-reminder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                target_phone: phone,
                message: msg,
                recipient_name: recipientName,
                upt_name: uptName,
                context_type: contextType
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1.5"></i>Kirim via Gateway';

            alertArea.classList.remove('d-none');
            if (data.success) {
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
                        <div><b>Pemberitahuan Gateway:</b> ${data.message || 'Gagal mengirim pesan.'}</div>
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

