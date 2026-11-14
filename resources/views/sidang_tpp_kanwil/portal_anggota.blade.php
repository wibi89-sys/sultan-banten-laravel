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

            <!-- Card 2: Lembar Pendapat & Catatan Khusus Per Materi Sidang -->
            @if($sidang->materis && $sidang->materis->count() > 0)
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-layer-group text-primary me-2"></i>2. Pendapat &amp; Catatan Khusus Per Materi Sidang
                    </h6>
                    <span class="badge bg-primary text-white" style="font-size: 11px;">{{ $sidang->materis->count() }} Agenda</span>
                </div>
                <p class="text-muted small mb-3">Mohon telaah berkas dan tentukan sikap serta catatan khusus Anda untuk masing-masing materi sidang di bawah ini:</p>

                @foreach($sidang->materis as $m)
                @php
                    $pMateri = $anggota->getPendapatMateri($m->id);
                    $mStatus = old("materi_pendapat.{$m->id}.status", $pMateri?->status_pendapat ?? ($loop->first && empty($pMateri) ? $anggota->status_pendapat : ''));
                    $mCatatan = old("materi_pendapat.{$m->id}.catatan", $pMateri?->catatan_pendapat ?? '');
                @endphp
                <div class="bento-card mb-3 border-start border-4 border-primary" style="background: #ffffff;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-dark text-white font-mono-num" style="font-size: 11px;">Materi #{{ $m->urutan }}</span>
                            {!! $m->kategori_badge !!}
                        </div>
                        @if($pMateri && $pMateri->status_pendapat !== 'belum_mengisi')
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">
                            <i class="fas fa-check me-1"></i>Sudah Tersimpan: {{ strtoupper(str_replace('_', ' ', $pMateri->status_pendapat)) }}
                        </span>
                        @endif
                    </div>

                    <h6 class="fw-bold text-dark mb-2" style="font-size: 15px; line-height: 1.4;">
                        {{ $m->judul_materi }}
                    </h6>

                    @if($m->deskripsi_materi)
                    <p class="text-secondary small mb-3" style="font-size: 12px;">{{ $m->deskripsi_materi }}</p>
                    @endif

                    <!-- Detail Substantif & Berkas Berdasarkan Kategori Materi -->
                    @if($m->isMutasiKeinginanSendiri() && !empty($m->narapidana_data))
                    @php 
                        $ndm = $m->narapidana_data; 
                        $bm = $ndm['berkas'] ?? [];
                    @endphp
                    <div class="p-2.5 rounded border mb-3 small" style="background-color: #fbf7ff; border-color: #6f42c1 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-bold" style="color: #6f42c1;"><i class="fas fa-user-friends me-1"></i>Rincian Mutasi Permintaan Sendiri (Mendekati Keluarga):</div>
                            <span class="badge text-white" style="background-color: #6f42c1 !important; font-size: 9.5px;">Biaya Mandiri &amp; Litmas</span>
                        </div>
                        <div class="row g-1 text-dark" style="font-size: 11.5px;">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">Nama Narapidana:</span> <b>{{ $ndm['nama'] ?? '-' }}</b><br>
                                <span class="text-muted">Register:</span> <code>{{ $ndm['nomor_register'] ?? '-' }}</code><br>
                                <span class="text-muted">Perkara/Pidana:</span> {{ $ndm['tindak_pidana'] ?? '-' }} ({{ $ndm['lama_pidana'] ?? '-' }})<br>
                                <span class="text-muted">UPT Asal:</span> <b>{{ $ndm['upt_asal_nama'] ?? '-' }}</b><br>
                                <span class="text-muted">Rekomendasi Tujuan:</span> <b class="text-primary">{{ $m->uptTujuanRekomendasi?->nama_upt ?? ($ndm['upt_tujuan_nama'] ?? 'Ditentukan Sidang') }}</b><br>
                                <span class="text-muted">Alasan:</span> <span class="fst-italic">{{ $ndm['alasan_pemindahan'] ?? '-' }}</span>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="p-1.5 bg-white rounded border mb-1" style="font-size: 11px;">
                                    <div class="fw-bold text-primary"><i class="fas fa-balance-scale me-1"></i>PK Bapas: <b>{{ $ndm['pk_bapas']['nama'] ?? '-' }}</b> ({{ $ndm['pk_bapas']['bapas'] ?? '-' }})</div>
                                    <div class="text-muted fst-italic">Litmas: {{ $ndm['pk_bapas']['rekomendasi'] ?? '-' }}</div>
                                </div>
                                <div class="p-1.5 bg-white rounded border mb-1" style="font-size: 11px;">
                                    <div class="fw-bold text-warning-emphasis"><i class="fas fa-gavel me-1"></i>BA Sidang UPT: <b>{{ $ndm['tpp_upt']['nomor_ba'] ?? '-' }}</b> ({{ !empty($ndm['tpp_upt']['tanggal_ba']) ? \Carbon\Carbon::parse($ndm['tpp_upt']['tanggal_ba'])->format('d/m/Y') : '-' }})</div>
                                    <div class="text-muted">Hasil: {{ $ndm['tpp_upt']['rekomendasi'] ?? '-' }}</div>
                                </div>
                                <div class="p-1.5 bg-white rounded border" style="font-size: 11px;">
                                    <div class="fw-bold text-success"><i class="fas fa-home me-1"></i>Penjamin: <b>{{ $ndm['penjamin']['nama'] ?? '-' }}</b> ({{ $ndm['penjamin']['hubungan'] ?? '-' }})</div>
                                    <div class="text-muted">NIK: <code>{{ $ndm['penjamin']['nik'] ?? '-' }}</code> &bull; WA: {{ $ndm['penjamin']['telepon'] ?? '-' }}</div>
                                    <div class="text-muted text-truncate" title="{{ $ndm['penjamin']['alamat'] ?? '-' }}">Alamat: {{ $ndm['penjamin']['alamat'] ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                            @if(!empty($m->file_bahan_sidang))
                            <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-file-download me-1"></i>Bahan Sidang
                            </a>
                            @endif
                            @if(!empty($bm['pernyataan_pribadi']))
                            <a href="{{ asset($bm['pernyataan_pribadi']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-file-signature me-1"></i>Surat Pernyataan Mandiri
                            </a>
                            @endif
                            @if(!empty($bm['ba_upt']))
                            <a href="{{ asset($bm['ba_upt']) }}" target="_blank" class="btn btn-outline-warning btn-xs py-0.5 px-2 text-dark" style="font-size: 10px;">
                                <i class="fas fa-file-contract me-1"></i>BA TPP UPT
                            </a>
                            @endif
                            @if(!empty($bm['litmas']))
                            <a href="{{ asset($bm['litmas']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-file-pdf me-1"></i>Litmas
                            </a>
                            @endif
                            @if(!empty($bm['surat_jaminan']))
                            <a href="{{ asset($bm['surat_jaminan']) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-shield-alt me-1"></i>Jaminan
                            </a>
                            @endif
                            @if(!empty($bm['ktp_penjamin']))
                            <a href="{{ asset($bm['ktp_penjamin']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-id-card me-1"></i>KTP Penjamin
                            </a>
                            @endif
                            @if(!empty($bm['register_f']))
                            <a href="{{ asset($bm['register_f']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-book me-1"></i>Reg F
                            </a>
                            @endif
                            @if(!empty($bm['kesehatan']))
                            <a href="{{ asset($bm['kesehatan']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-heartbeat me-1"></i>Surat Dokter
                            </a>
                            @endif
                            @if(!empty($m->dokumen_tambahan['file']))
                            <a href="{{ asset($m->dokumen_tambahan['file']) }}" target="_blank" class="btn btn-outline-dark btn-xs py-0.5 px-2" style="font-size: 10px;">
                                <i class="fas fa-paperclip me-1"></i>{{ $m->dokumen_tambahan['nama'] ?? 'Dokumen Tambahan' }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @elseif($m->isMutasiNarapidana() && !empty($m->narapidana_data))
                    @php 
                        $ndm = $m->narapidana_data; 
                        $bm = $ndm['berkas'] ?? [];
                    @endphp
                    <div class="p-2.5 bg-warning-subtle bg-opacity-25 rounded border border-warning border-opacity-50 mb-3 small">
                        <div class="fw-bold text-dark mb-1"><i class="fas fa-exchange-alt text-warning-emphasis me-1"></i>Rincian Usulan Mutasi Narapidana:</div>
                        <div class="row g-1 text-dark" style="font-size: 11.5px;">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">Nama Narapidana:</span> <b>{{ $ndm['nama'] ?? '-' }}</b><br>
                                <span class="text-muted">Register:</span> <code>{{ $ndm['nomor_register'] ?? '-' }}</code><br>
                                <span class="text-muted">Perkara:</span> {{ $ndm['tindak_pidana'] ?? '-' }}
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">UPT Asal:</span> <b>{{ $ndm['upt_asal_nama'] ?? '-' }}</b><br>
                                <span class="text-muted">Rekomendasi Tujuan:</span> <b class="text-primary">{{ $m->uptTujuanRekomendasi?->nama_upt ?? ($ndm['upt_tujuan_nama'] ?? 'Ditentukan Sidang') }}</b><br>
                                <span class="text-muted">Alasan:</span> <span class="fst-italic">{{ $ndm['alasan_pemindahan'] ?? '-' }}</span>
                            </div>
                        </div>
                        @if(!empty($bm['file_litmas']) || !empty($bm['file_permohonan']) || !empty($bm['file_jaminan']) || !empty($bm['file_reg_f']) || !empty($bm['file_asesmen']) || !empty($bm['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                            @if(!empty($m->file_bahan_sidang))
                            <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-download me-1"></i>Bahan Sidang
                            </a>
                            @endif
                            @if(!empty($bm['file_litmas']))
                            <a href="{{ asset($bm['file_litmas']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-pdf me-1"></i>Litmas
                            </a>
                            @endif
                            @if(!empty($bm['file_permohonan']))
                            <a href="{{ asset($bm['file_permohonan']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-alt me-1"></i>Permohonan
                            </a>
                            @endif
                            @if(!empty($bm['file_jaminan']))
                            <a href="{{ asset($bm['file_jaminan']) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-shield-alt me-1"></i>Jaminan
                            </a>
                            @endif
                            @if(!empty($bm['file_reg_f']))
                            <a href="{{ asset($bm['file_reg_f']) }}" target="_blank" class="btn btn-outline-dark btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-book me-1"></i>Reg F
                            </a>
                            @endif
                            @if(!empty($bm['dokumen_tambahan']['file']))
                            <a href="{{ asset($bm['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-paperclip me-1"></i>{{ $bm['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                            </a>
                            @endif
                        </div>
                        @endif
                    </div>
                    @elseif($m->isIzinBerobat() && !empty($m->narapidana_data))
                    @php 
                        $ndm = $m->narapidana_data; 
                        $bmed = $ndm['berkas'] ?? [];
                    @endphp
                    <div class="p-2.5 bg-danger-subtle bg-opacity-25 rounded border border-danger border-opacity-50 mb-3 small">
                        <div class="fw-bold text-danger mb-1"><i class="fas fa-heartbeat me-1"></i>Rincian Usulan Izin Berobat Keluar Kota:</div>
                        <div class="row g-1 text-dark" style="font-size: 11.5px;">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">Nama Pasien:</span> <b>{{ $ndm['nama'] ?? '-' }}</b><br>
                                <span class="text-muted">Register:</span> <code>{{ $ndm['nomor_register'] ?? '-' }}</code><br>
                                <span class="text-muted">UPT Asal:</span> {{ $ndm['upt_asal_nama'] ?? '-' }}
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">Diagnosa Medis:</span> <b class="text-danger">{{ $ndm['diagnosa_medis'] ?? '-' }}</b><br>
                                <span class="text-muted">Dokter / RS Rujukan:</span> <b>{{ $ndm['rs_rujukan_nama'] ?? '-' }} ({{ $ndm['rs_rujukan_kota'] ?? '-' }})</b>
                            </div>
                        </div>
                        @if(!empty($bmed['file_rekam_medis']) || !empty($bmed['file_rujukan']) || !empty($bmed['file_ba_upt']) || !empty($bmed['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                            @if(!empty($m->file_bahan_sidang))
                            <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-download me-1"></i>Bahan Sidang
                            </a>
                            @endif
                            @if(!empty($bmed['file_rekam_medis']))
                            <a href="{{ asset($bmed['file_rekam_medis']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-medical me-1"></i>Rekam Medis
                            </a>
                            @endif
                            @if(!empty($bmed['file_rujukan']))
                            <a href="{{ asset($bmed['file_rujukan']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-hospital me-1"></i>Surat Rujukan
                            </a>
                            @endif
                            @if(!empty($bmed['file_ba_upt']))
                            <a href="{{ asset($bmed['file_ba_upt']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-gavel me-1"></i>BA Sidang UPT
                            </a>
                            @endif
                            @if(!empty($bmed['dokumen_tambahan']['file']))
                            <a href="{{ asset($bmed['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-paperclip me-1"></i>{{ $bmed['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                            </a>
                            @endif
                        </div>
                        @endif
                    </div>
                    @elseif($m->isKlienBapas() && !empty($m->klien_data))
                    @php 
                        $kdm = $m->klien_data; 
                    @endphp
                    <div class="p-2.5 bg-primary-subtle bg-opacity-25 rounded border border-primary border-opacity-50 mb-3 small">
                        <div class="fw-bold text-primary mb-1"><i class="fas fa-passport me-1"></i>Rincian Usulan Klien Bapas:</div>
                        <div class="row g-1 text-dark" style="font-size: 11.5px;">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">Nama Klien:</span> <b>{{ $kdm['nama_klien'] ?? '-' }}</b><br>
                                <span class="text-muted">Bapas:</span> {{ $kdm['bapas_nama'] ?? '-' }}<br>
                                <span class="text-muted">Program:</span> {{ $kdm['program_integrasi'] ?? 'PB' }}
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted">Keperluan LN:</span> <b>{{ $kdm['keperluan_ln'] ?? '-' }}</b><br>
                                <span class="text-muted">Negara Tujuan:</span> <b>{{ $kdm['negara_tujuan'] ?? '-' }}</b><br>
                                <span class="text-muted">Penjamin:</span> {{ $kdm['penjamin_nama'] ?? '-' }}
                            </div>
                        </div>
                        @if(!empty($kdm['file_litmas_bapas']) || !empty($kdm['file_surat_bapas']) || !empty($kdm['file_bukti_travel']) || !empty($kdm['file_surat_jaminan']) || !empty($kdm['dokumen_tambahan']['file']) || !empty($m->file_bahan_sidang))
                        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
                            @if(!empty($m->file_bahan_sidang))
                            <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-download me-1"></i>Bahan Sidang
                            </a>
                            @endif
                            @if(!empty($kdm['file_litmas_bapas']))
                            <a href="{{ asset($kdm['file_litmas_bapas']) }}" target="_blank" class="btn btn-outline-danger btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-pdf me-1"></i>Litmas
                            </a>
                            @endif
                            @if(!empty($kdm['file_bukti_travel']))
                            <a href="{{ asset($kdm['file_bukti_travel']) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-ticket-alt me-1"></i>Tiket / Travel
                            </a>
                            @endif
                            @if(!empty($kdm['dokumen_tambahan']['file']))
                            <a href="{{ asset($kdm['dokumen_tambahan']['file']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-paperclip me-1"></i>{{ $kdm['dokumen_tambahan']['nama'] ?? 'Dokumen Tambahan' }}
                            </a>
                            @endif
                        </div>
                        @endif
                    </div>
                    @else
                        @if(!empty($m->file_bahan_sidang) || !empty($m->dokumen_tambahan['file']))
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            @if(!empty($m->file_bahan_sidang))
                            <a href="{{ asset($m->file_bahan_sidang) }}" target="_blank" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-file-download me-1"></i>Unduh Bahan Sidang
                            </a>
                            @endif
                            @if(!empty($m->dokumen_tambahan['file']))
                            <a href="{{ asset($m->dokumen_tambahan['file']) }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 10.5px;">
                                <i class="fas fa-paperclip me-1"></i>{{ $m->dokumen_tambahan['nama'] ?? 'Dokumen Tambahan' }}
                            </a>
                            @endif
                        </div>
                        @endif
                    @endif

                    <!-- Pilihan Sikap Per Materi Ini -->
                    <div class="pendapat-options-container">
                        <label class="form-label small fw-bold text-dark mb-1">Sikap Anda untuk Materi #{{ $m->urutan }}:</label>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="choice-pill choice-pill-sm py-1.5 px-2 {{ $mStatus == 'setuju' ? 'active-setuju' : '' }}">
                                    <input type="radio" name="materi_pendapat[{{ $m->id }}][status]" value="setuju" {{ $mStatus == 'setuju' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-setuju')">
                                    <div>
                                        <div class="fw-bold text-success" style="font-size: 11.5px;"><i class="fas fa-check-circle me-1"></i>Setuju</div>
                                        <div class="text-muted" style="font-size: 9.5px;">Tanpa catatan</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="choice-pill choice-pill-sm py-1.5 px-2 {{ $mStatus == 'setuju_catatan' ? 'active-catatan' : '' }}">
                                    <input type="radio" name="materi_pendapat[{{ $m->id }}][status]" value="setuju_catatan" {{ $mStatus == 'setuju_catatan' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-catatan')">
                                    <div>
                                        <div class="fw-bold text-info" style="font-size: 11.5px;"><i class="fas fa-clipboard-check me-1"></i>Setuju Catatan</div>
                                        <div class="text-muted" style="font-size: 9.5px;">Ada arahan/syarat</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="choice-pill choice-pill-sm py-1.5 px-2 {{ $mStatus == 'tunda' ? 'active-tunda' : '' }}">
                                    <input type="radio" name="materi_pendapat[{{ $m->id }}][status]" value="tunda" {{ $mStatus == 'tunda' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-tunda')">
                                    <div>
                                        <div class="fw-bold text-warning-emphasis" style="font-size: 11.5px;"><i class="fas fa-pause-circle me-1"></i>Tunda</div>
                                        <div class="text-muted" style="font-size: 9.5px;">Perlu data tambahan</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="choice-pill choice-pill-sm py-1.5 px-2 {{ $mStatus == 'tolak' ? 'active-tolak' : '' }}">
                                    <input type="radio" name="materi_pendapat[{{ $m->id }}][status]" value="tolak" {{ $mStatus == 'tolak' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-tolak')">
                                    <div>
                                        <div class="fw-bold text-danger" style="font-size: 11.5px;"><i class="fas fa-times-circle me-1"></i>Tolak</div>
                                        <div class="text-muted" style="font-size: 9.5px;">Tidak penuhi syarat</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Catatan Khusus Per Materi -->
                        <div>
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-pen-alt text-primary me-1"></i>Catatan Khusus untuk Materi #{{ $m->urutan }}:
                            </label>
                            <textarea name="materi_pendapat[{{ $m->id }}][catatan]" class="form-control form-control-sm" rows="2" placeholder="Tuliskan pertimbangan spesifik, alasan, atau arahan khusus Anda untuk materi #{{ $m->urutan }}...">{{ $mCatatan }}</textarea>
                            <small class="text-muted" style="font-size: 10px;">Catatan ini akan tersimpan khusus dan tercetak pada naskah Risalah untuk materi ini.</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Card 3: Lembar Pendapat & Catatan Umum Keseluruhan Sidang -->
            <div class="bento-card">
                <h6 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-gavel text-primary me-2"></i>{{ ($sidang->materis && $sidang->materis->count() > 0) ? '3.' : '2.' }} Sikap &amp; Rekomendasi Umum Sidang
                </h6>
                <p class="text-muted small mb-3">Tentukan sikap/keputusan umum Anda terhadap keseluruhan agenda persidangan:</p>

                <!-- 4 Pilihan Sikap -->
                <div class="pendapat-options-container">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju' ? 'active-setuju' : '' }}">
                                <input type="radio" name="status_pendapat" value="setuju" {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-setuju')">
                                <div>
                                    <div class="fw-bold text-success" style="font-size: 13px;"><i class="fas fa-check-circle me-1"></i>Setuju Umum</div>
                                    <div class="text-muted" style="font-size: 10.5px;">Tanpa catatan</div>
                                </div>
                            </label>
                        </div>

                        <div class="col-6">
                            <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju_catatan' ? 'active-catatan' : '' }}">
                                <input type="radio" name="status_pendapat" value="setuju_catatan" {{ old('status_pendapat', $anggota->status_pendapat) == 'setuju_catatan' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-catatan')">
                                <div>
                                    <div class="fw-bold text-info" style="font-size: 13px;"><i class="fas fa-clipboard-check me-1"></i>Setuju Catatan</div>
                                    <div class="text-muted" style="font-size: 10.5px;">Ada syarat/arahan</div>
                                </div>
                            </label>
                        </div>

                        <div class="col-6">
                            <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'tunda' ? 'active-tunda' : '' }}">
                                <input type="radio" name="status_pendapat" value="tunda" {{ old('status_pendapat', $anggota->status_pendapat) == 'tunda' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-tunda')">
                                <div>
                                    <div class="fw-bold text-warning-emphasis" style="font-size: 13px;"><i class="fas fa-pause-circle me-1"></i>Tunda</div>
                                    <div class="text-muted" style="font-size: 10.5px;">Perlu litmas/data</div>
                                </div>
                            </label>
                        </div>

                        <div class="col-6">
                            <label class="choice-pill {{ old('status_pendapat', $anggota->status_pendapat) == 'tolak' ? 'active-tolak' : '' }}">
                                <input type="radio" name="status_pendapat" value="tolak" {{ old('status_pendapat', $anggota->status_pendapat) == 'tolak' ? 'checked' : '' }} onchange="highlightPendapatItem(this, 'active-tolak')">
                                <div>
                                    <div class="fw-bold text-danger" style="font-size: 13px;"><i class="fas fa-times-circle me-1"></i>Tolak</div>
                                    <div class="text-muted" style="font-size: 10.5px;">Tidak penuhi syarat</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">Uraian Catatan Umum &amp; Pertimbangan Keseluruhan Sidang</label>
                    <textarea name="catatan_pendapat" class="form-control form-control-sm" rows="3" placeholder="Tuliskan telaahan pertimbangan substantif, administratif, keamanan, atau catatan rekomendasi umum Anda...">{{ old('catatan_pendapat', $anggota->catatan_pendapat) }}</textarea>
                    <small class="text-muted" style="font-size: 11px;">Catatan umum ini akan dicetak dalam Berita Acara Sidang TPP Kantor Wilayah.</small>
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

        function highlightPendapatItem(radio, activeClass) {
            const container = radio.closest('.pendapat-options-container');
            if (container) {
                container.querySelectorAll('.choice-pill').forEach(r => {
                    r.classList.remove('active-setuju', 'active-catatan', 'active-tunda', 'active-tolak');
                });
                if (radio.checked) {
                    radio.closest('.choice-pill').classList.add(activeClass);
                }
            } else {
                highlightPendapat(radio, activeClass);
            }
        }
    </script>
</body>
</html>
