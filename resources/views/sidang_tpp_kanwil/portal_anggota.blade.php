<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Sidang TPP Kanwil - {{ $anggota->nama }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-sub: #64748b;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 40px;
        }
        .portal-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 30px 15px 45px 15px;
            border-bottom-left-radius: 28px;
            border-bottom-right-radius: 28px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
        }
        .portal-container {
            max-width: 680px;
            margin: -30px auto 0 auto;
            padding: 0 15px;
        }
        .bento-card {
            background: var(--card-bg);
            border-radius: 18px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            padding: 24px;
            margin-bottom: 20px;
        }
        .choice-pill {
            cursor: pointer;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            user-select: none;
        }
        .choice-pill:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }
        .choice-pill input[type="radio"] {
            margin: 0;
            cursor: pointer;
        }
        .choice-pill.active-setuju {
            border-color: #10b981;
            background-color: rgba(16, 185, 129, 0.06);
            color: #065f46;
            font-weight: 700;
        }
        .choice-pill.active-catatan {
            border-color: #06b6d4;
            background-color: rgba(6, 182, 212, 0.06);
            color: #0e7490;
            font-weight: 700;
        }
        .choice-pill.active-tunda {
            border-color: #f59e0b;
            background-color: rgba(245, 158, 11, 0.06);
            color: #b45309;
            font-weight: 700;
        }
        .choice-pill.active-tolak {
            border-color: #ef4444;
            background-color: rgba(239, 68, 68, 0.06);
            color: #b91c1c;
            font-weight: 700;
        }
        .choice-pill.active-hadir {
            border-color: #0284c7;
            background-color: rgba(2, 132, 199, 0.06);
            color: #0369a1;
            font-weight: 700;
        }
        .font-mono-num {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>

    <!-- Header Portal -->
    <header class="portal-header text-center">
        <div style="font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: #38bdf8; font-weight: 700;">
            KEMENTERIAN HUKUM DAN HAM RI &bull; KANTOR WILAYAH BANTEN
        </div>
        <h4 class="fw-bold mt-1 mb-1" style="font-size: 19px;">
            <i class="fas fa-university me-2 text-info"></i>Sidang TPP Tingkat Kantor Wilayah
        </h4>
        <p class="small text-white-50 mb-0">
            Formulir Presensi Kehadiran &amp; Lembar Pendapat Deliberasi Tim Sidang
        </p>
    </header>

    <div class="portal-container">

        <!-- Notifikasi Sukses -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 14px;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-check-circle fa-2x text-success"></i>
                <div>
                    <div class="fw-bold">{{ session('success') }}</div>
                    <small class="text-success-emphasis">Data presensi dan pertimbangan Anda telah tercatat pada Berita Acara Sidang TPP Kanwil.</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 14px;">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Mohon lengkapi isian:</div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Card Identitas Anggota -->
        <div class="bento-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-primary-subtle text-primary py-1 px-2.5 fw-bold" style="font-size: 11px;">
                    <i class="fas fa-user-shield me-1"></i>{{ $anggota->peran_sidang }} Tim TPP
                </span>
                <span class="text-muted small" style="font-size: 11px;">
                    <i class="fab fa-whatsapp text-success me-1"></i>{{ $anggota->no_wa ?: 'No WA Terverifikasi' }}
                </span>
            </div>
            <h5 class="fw-bold mb-0 text-dark">{{ $anggota->nama }}</h5>
            @if($anggota->nip)
            <div class="text-muted small mb-1" style="font-size: 12px;">NIP. {{ $anggota->nip }}</div>
            @endif
            <div class="text-secondary small">{{ $anggota->jabatan }}</div>

            @if($anggota->waktu_pendapat)
            <div class="mt-3 p-2 bg-success-subtle text-success rounded small d-flex align-items-center gap-2" style="font-size: 11.5px;">
                <i class="fas fa-check-circle"></i>
                <span>Telah mengisi pendapat pada <b>{{ $anggota->waktu_pendapat->format('d/m/Y H:i') }} WIB</b>. Anda dapat memperbarui isian di bawah kapan saja sebelum sidang ditutup.</span>
            </div>
            @endif
        </div>

        <!-- Card Detail Sidang & Agenda -->
        <div class="bento-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-secondary-subtle text-secondary py-1 px-2" style="font-size: 10.5px;">
                    {{ $sidang->nomor_sidang }}
                </span>
                <span class="badge bg-light text-dark border" style="font-size: 10.5px;">
                    <i class="fas fa-map-marker-alt text-danger me-1"></i>{{ ucfirst($sidang->metode) }}
                </span>
            </div>

            <div class="badge bg-primary-subtle text-primary mb-1" style="font-size: 11px;">
                {{ $sidang->agenda_kategori }}
            </div>
            <h5 class="fw-bold mb-2 text-dark" style="font-size: 17px; line-height: 1.35;">
                {{ $sidang->agenda_judul }}
            </h5>

            @if($sidang->agenda_deskripsi)
            <p class="text-muted small mb-3" style="line-height: 1.5;">
                {{ $sidang->agenda_deskripsi }}
            </p>
            @endif

            <div class="p-3 bg-light rounded border mb-3 small" style="line-height: 1.5;">
                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <span class="text-muted"><i class="fas fa-calendar-alt text-primary me-1"></i>Hari, Tanggal:</span>
                        <div class="fw-bold">{{ $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-' }}</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted"><i class="fas fa-clock text-info me-1"></i>Waktu:</span>
                        <div class="fw-bold">{{ substr($sidang->jam_mulai, 0, 5) }} s/d {{ substr($sidang->jam_selesai, 0, 5) }} WIB</div>
                    </div>
                    <div class="col-12">
                        <span class="text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i>Tempat / Pelaksanaan:</span>
                        <div class="fw-bold">{{ $sidang->tempat }}</div>
                    </div>
                </div>
            </div>

            <!-- Jika Sidang Daring / Hybrid: Tampilkan Link Zoom -->
            @if(!empty($sidang->link_daring))
            <div class="p-3 bg-info-subtle border border-info rounded mb-3">
                <div class="fw-bold text-dark small mb-1"><i class="fas fa-video text-info me-1"></i>Tautan Rapat Daring:</div>
                <a href="{{ $sidang->link_daring }}" target="_blank" class="btn btn-primary btn-sm fw-bold w-100 mb-2 shadow-sm">
                    <i class="fas fa-external-link-alt me-1"></i> Masuk Ruang Sidang Daring
                </a>
                @if(!empty($sidang->meeting_id) || !empty($sidang->passcode))
                <div class="small text-secondary" style="font-size: 11.5px;">
                    Meeting ID: <b>{{ $sidang->meeting_id ?: '-' }}</b> | Passcode: <b>{{ $sidang->passcode ?: '-' }}</b>
                </div>
                @endif
            </div>
            @endif

            <!-- Berkas Bahan Sidang -->
            @if(!empty($sidang->file_bahan_sidang))
            <div>
                <a href="{{ asset($sidang->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-secondary btn-sm w-100 py-2">
                    <i class="fas fa-file-download text-primary me-1"></i> Unduh Berkas Bahan Sidang
                </a>
            </div>
            @endif
        </div>

        <!-- FORM ISIAN PRESENSI & PENDAPAT -->
        <form action="{{ route('sidang-tpp-kanwil.portal-anggota.submit', $anggota->token_akses) }}" method="POST">
            @csrf

            <!-- Card 1: Presensi Kehadiran -->
            <div class="bento-card">
                <h6 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-user-check text-primary me-2"></i>1. Konfirmasi Kehadiran Anda
                </h6>
                <p class="text-muted small mb-3">Pilih status kehadiran Anda pada pelaksanaan Sidang TPP Kanwil ini:</p>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="choice-pill {{ old('status_kehadiran', $anggota->status_kehadiran) == 'hadir_luring' ? 'active-hadir' : '' }}">
                            <input type="radio" name="status_kehadiran" value="hadir_luring" {{ old('status_kehadiran', $anggota->status_kehadiran) == 'hadir_luring' ? 'checked' : '' }} required onchange="highlightPill(this)">
                            <div>
                                <div class="fw-bold" style="font-size: 12.5px;"><i class="fas fa-user-check text-success me-1"></i>Hadir Luring</div>
                                <div class="text-muted" style="font-size: 10.5px;">Tatap Muka di Kanwil</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="choice-pill {{ old('status_kehadiran', $anggota->status_kehadiran) == 'hadir_daring' ? 'active-hadir' : '' }}">
                            <input type="radio" name="status_kehadiran" value="hadir_daring" {{ old('status_kehadiran', $anggota->status_kehadiran) == 'hadir_daring' ? 'checked' : '' }} onchange="highlightPill(this)">
                            <div>
                                <div class="fw-bold" style="font-size: 12.5px;"><i class="fas fa-video text-info me-1"></i>Hadir Daring</div>
                                <div class="text-muted" style="font-size: 10.5px;">Virtual Zoom / Meet</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="choice-pill {{ old('status_kehadiran', $anggota->status_kehadiran) == 'izin' ? 'active-hadir' : '' }}">
                            <input type="radio" name="status_kehadiran" value="izin" {{ old('status_kehadiran', $anggota->status_kehadiran) == 'izin' ? 'checked' : '' }} onchange="highlightPill(this)">
                            <div>
                                <div class="fw-bold" style="font-size: 12.5px;"><i class="fas fa-envelope text-warning me-1"></i>Izin</div>
                                <div class="text-muted" style="font-size: 10.5px;">Ada Tugas Lain / Halangan</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="choice-pill {{ old('status_kehadiran', $anggota->status_kehadiran) == 'sakit' ? 'active-hadir' : '' }}">
                            <input type="radio" name="status_kehadiran" value="sakit" {{ old('status_kehadiran', $anggota->status_kehadiran) == 'sakit' ? 'checked' : '' }} onchange="highlightPill(this)">
                            <div>
                                <div class="fw-bold" style="font-size: 12.5px;"><i class="fas fa-hospital text-danger me-1"></i>Sakit</div>
                                <div class="text-muted" style="font-size: 10.5px;">Kondisi Kesehatan</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mt-2">
                    <label class="form-label small text-muted mb-1">Keterangan Tambahan Presensi (Opsional)</label>
                    <input type="text" name="keterangan_kehadiran" class="form-control form-control-sm" placeholder="Contoh: Mengikuti via daring karena ada monitoring lapangan" value="{{ old('keterangan_kehadiran', $anggota->keterangan_kehadiran) }}">
                </div>
            </div>

            <!-- Card 2: Lembar Pendapat / Rekomendasi Sidang -->
            <div class="bento-card">
                <h6 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-gavel text-primary me-2"></i>2. Pendapat &amp; Rekomendasi Sidang
                </h6>
                <p class="text-muted small mb-3">Tentukan sikap/keputusan Anda terhadap pokok agenda yang dibahas:</p>

                <!-- 4 Pilihan Sikap -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju' ? 'active-setuju' : '' }}">
                            <input type="radio" name="status_pendapat" value="setuju" {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju' ? 'checked' : '' }} required onchange="highlightPendapat(this, 'active-setuju')">
                            <div>
                                <div class="fw-bold text-success" style="font-size: 13px;"><i class="fas fa-check-circle me-1"></i>Setuju</div>
                                <div class="text-muted" style="font-size: 10.5px;">Tanpa catatan</div>
                            </div>
                        </label>
                    </div>

                    <div class="col-6">
                        <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju_catatan' ? 'active-catatan' : '' }}">
                            <input type="radio" name="status_pendapat" value="setuju_catatan" {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju_catatan' ? 'checked' : '' }} onchange="highlightPendapat(this, 'active-catatan')">
                            <div>
                                <div class="fw-bold text-info" style="font-size: 13px;"><i class="fas fa-clipboard-check me-1"></i>Setuju Catatan</div>
                                <div class="text-muted" style="font-size: 10.5px;">Ada syarat/arahan</div>
                            </div>
                        </label>
                    </div>

                    <div class="col-6">
                        <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'tunda' ? 'active-tunda' : '' }}">
                            <input type="radio" name="status_pendapat" value="tunda" {{ old('status_pendapat', $anggota->status_pendapat) == 'tunda' ? 'checked' : '' }} onchange="highlightPendapat(this, 'active-tunda')">
                            <div>
                                <div class="fw-bold text-warning-emphasis" style="font-size: 13px;"><i class="fas fa-pause-circle me-1"></i>Tunda</div>
                                <div class="text-muted" style="font-size: 10.5px;">Perlu litmas/data</div>
                            </div>
                        </label>
                    </div>

                    <div class="col-6">
                        <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'tolak' ? 'active-tolak' : '' }}">
                            <input type="radio" name="status_pendapat" value="tolak" {{ old('status_pendapat', $anggota->status_pendapat) == 'tolak' ? 'checked' : '' }} onchange="highlightPendapat(this, 'active-tolak')">
                            <div>
                                <div class="fw-bold text-danger" style="font-size: 13px;"><i class="fas fa-times-circle me-1"></i>Tolak</div>
                                <div class="text-muted" style="font-size: 10.5px;">Tidak penuhi syarat</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">Uraian Catatan &amp; Pertimbangan Anggota Sidang</label>
                    <textarea name="catatan_pendapat" class="form-control form-control-sm" rows="4" placeholder="Tuliskan telaahan pertimbangan substantif, administratif, keamanan, atau catatan rekomendasi Anda...">{{ old('catatan_pendapat', $anggota->catatan_pendapat) }}</textarea>
                    <small class="text-muted" style="font-size: 11px;">Catatan ini akan dicetak dalam lampiran Berita Acara Sidang TPP Kantor Wilayah.</small>
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg shadow fw-bold py-3" style="border-radius: 14px; font-size: 16px;">
                    <i class="fas fa-paper-plane me-2"></i>Kirim Konfirmasi Presensi &amp; Pendapat
                </button>
            </div>
        </form>

        <div class="text-center text-muted small mt-4" style="font-size: 11px;">
            &copy; {{ date('Y') }} Sistem Informasi Manajemen Pemasyarakatan &bull; Sultan Banten<br>
            Kantor Wilayah Kementerian Hukum dan HAM Banten
        </div>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function highlightPill(radio) {
            document.querySelectorAll('input[name="status_kehadiran"]').forEach(r => {
                r.closest('.choice-pill').classList.remove('active-hadir');
            });
            if (radio.checked) {
                radio.closest('.choice-pill').classList.add('active-hadir');
            }
        }

        function highlightPendapat(radio, activeClass) {
            document.querySelectorAll('input[name="status_pendapat"]').forEach(r => {
                r.closest('.choice-pill').classList.remove('active-setuju', 'active-catatan', 'active-tunda', 'active-tolak');
            });
            if (radio.checked) {
                radio.closest('.choice-pill').classList.add(activeClass);
            }
        }
    </script>
</body>
</html>
