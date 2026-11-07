<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#0F4761">
    <title>Verifikasi Keabsahan Dokumen Digital - SULTAN BANTEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0F4761;
            --primary-hover: #1e698d;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            margin: 0;
        }
        .cert-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            max-width: 620px;
            width: 100%;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .cert-header {
            background: linear-gradient(135deg, #0F4761 0%, #175d7e 100%);
            color: white;
            padding: 28px 24px 22px;
            text-align: center;
            position: relative;
        }
        .cert-logo {
            height: 46px;
            max-width: 110px;
            object-fit: contain;
        }
        .cert-title {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 10px;
            margin-bottom: 0;
            line-height: 1.35;
        }
        .cert-badge-valid {
            display: inline-flex;
            align-items: center;
            background: #10b981;
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            margin-top: 12px;
        }
        .cert-badge-invalid {
            display: inline-flex;
            align-items: center;
            background: #ef4444;
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
            margin-top: 12px;
        }
        .cert-body {
            padding: 24px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        .meta-table th {
            width: 36%;
            font-size: 12.5px;
            color: #64748b;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .meta-table td {
            font-size: 13.5px;
            color: #0f172a;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .auth-code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            background: #f1f5f9;
            color: #0f4761;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            display: inline-block;
            word-break: break-all;
        }

        /* Penyesuaian Tampilan Khusus Mobile / Layar Sempit (< 576px) */
        @media (max-width: 576px) {
            body {
                padding: 12px 10px;
                align-items: flex-start;
            }
            .cert-card {
                border-radius: 14px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            }
            .cert-header {
                padding: 20px 14px 16px;
            }
            .cert-logo {
                height: 38px;
                max-width: 85px;
            }
            .instansi-sub {
                font-size: 9.5px !important;
            }
            .instansi-name {
                font-size: 12.5px !important;
            }
            .cert-title {
                font-size: 13.5px;
                margin-top: 8px;
            }
            .cert-badge-valid,
            .cert-badge-invalid {
                font-size: 11px;
                padding: 5px 12px;
                margin-top: 10px;
            }
            .cert-body {
                padding: 16px 14px;
            }
            .security-banner {
                padding: 8px 10px !important;
                font-size: 11.5px !important;
                margin-bottom: 12px !important;
            }
            .meta-table, 
            .meta-table tbody {
                display: block;
                width: 100%;
            }
            .meta-table tr {
                display: flex;
                flex-direction: column;
                padding: 9px 0;
                border-bottom: 1px solid #f1f5f9;
            }
            .meta-table tr:last-child {
                border-bottom: none;
            }
            .meta-table th {
                display: block;
                width: 100% !important;
                padding: 0 0 3px 0 !important;
                font-size: 11px !important;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                color: #64748b;
                font-weight: 700;
                border-bottom: none !important;
            }
            .meta-table td {
                display: block;
                width: 100% !important;
                padding: 0 !important;
                font-size: 13px !important;
                color: #0f172a;
                font-weight: 600;
                border-bottom: none !important;
                word-break: break-word;
            }
            .meta-table td small {
                font-size: 11.5px !important;
            }
            .auth-code {
                font-size: 11.5px;
                padding: 3px 7px;
            }
            .cert-footer {
                font-size: 10.5px !important;
                padding: 10px 12px !important;
            }
        }
    </style>
</head>
<body>

<div class="cert-card">
    @if($naskah)
        @php
            $logoPath = !empty($instansi?->logo_kop) ? $instansi->logo_kop : (!empty($instansi?->logo) ? $instansi->logo : null);
        @endphp
        <div class="cert-header">
            <div class="d-flex justify-content-center align-items-center mb-1">
                @if(!empty($logoPath) && file_exists(public_path($logoPath)))
                    <img src="{{ asset($logoPath) }}" alt="Logo" class="cert-logo me-2.5">
                @endif
                <div class="text-start">
                    <div class="small fw-bold text-uppercase instansi-sub" style="letter-spacing: 0.5px; font-size: 10.5px; opacity: 0.9;">Kementerian Imigrasi &amp; Pemasyarakatan RI</div>
                    <div class="fw-bold instansi-name" style="font-size: 13.5px; line-height: 1.25;">KANTOR WILAYAH BANTEN</div>
                </div>
            </div>
            <h5 class="cert-title">LEMBAR OTENTIFIKASI DOKUMEN DIGITAL</h5>
            <div class="cert-badge-valid">
                <i class="fas fa-check-circle me-1.5"></i> DOKUMEN RESMI TERVERIFIKASI
            </div>
        </div>

        <div class="cert-body">
            <div class="alert alert-light border py-2 px-3 rounded-3 small mb-3 text-secondary d-flex align-items-center security-banner">
                <i class="fas fa-shield-alt fa-lg me-2.5 text-success flex-shrink-0"></i>
                <div style="font-size: 12px; line-height: 1.4;">
                    Dokumen ini sah dan terdaftar resmi pada Sistem Informasi <b>SULTAN BANTEN</b>. Informasi di bawah ini sesuai dengan arsip digital asli yang diterbitkan.
                </div>
            </div>

            <table class="table meta-table mb-0">
                <tbody>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <td>
                            @if($naskah->jenis_dokumen === 'laporan_tpp')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Laporan Pelaksanaan Sidang TPP Kanwil</span>
                            @elseif($naskah->jenis_dokumen === 'surat_tugas')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Surat Tugas Tim Kanwil</span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Nota Dinas Permohonan Surat Tugas</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Nomor Dokumen</th>
                        <td class="text-primary font-monospace">{{ $naskah->nomor_dokumen ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Satuan Kerja Terkait</th>
                        <td>{{ $naskah->sidangTpp->upt->nama_upt ?? 'UPT Pemasyarakatan' }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Pelaksanaan</th>
                        <td>{{ $naskah->sidangTpp ? date('d F Y', strtotime($naskah->sidangTpp->tanggal_sidang)) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Materi / Agenda</th>
                        <td>{{ $naskah->materi_agenda ?: ($naskah->sidangTpp->agenda_kategori ?? 'Sidang Tim Pengamat Pemasyarakatan') }}</td>
                    </tr>
                    <tr>
                        <th>Pejabat Penandatangan</th>
                        <td>
                            <div>{{ $naskah->penandatangan_nama ?: 'Pejabat Berwenang' }}</div>
                            <small class="text-muted d-block fw-normal">{{ $naskah->penandatangan_jabatan ?: 'Kepala Kantor Wilayah / Kepala Bidang' }}</small>
                            @if($naskah->tampilkan_nip && !empty($naskah->penandatangan_nip))
                                <small class="text-muted d-block fw-normal">NIP. {{ $naskah->penandatangan_nip }}</small>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($naskah->pengawas_nama))
                    <tr>
                        <th>Perwakilan Kanwil</th>
                        <td>
                            <div>{{ $naskah->pengawas_nama }}</div>
                            <small class="text-muted d-block fw-normal">{{ $naskah->pengawas_jabatan ?: 'Tim Pembinaan Narapidana' }}</small>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>Metode Pengesahan</th>
                        <td>
                            @if($naskah->metode_ttd === 'digital_qr')
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-qrcode me-1"></i>Digital QR Code</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-signature me-1"></i>Tanda Tangan Manual &amp; Stempel</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Kode Otentifikasi</th>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1.5 flex-wrap">
                                <code class="auth-code">{{ $naskah->kode_verifikasi }}</code>
                                <button type="button" class="btn btn-light btn-xs border text-secondary py-0.5 px-2" style="font-size: 11px;" onclick="copyAuthCode('{{ $naskah->kode_verifikasi }}')" title="Salin Kode">
                                    <i class="far fa-copy me-1"></i><span id="btnCopyText">Salin</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            @if(!empty($naskah->file_pdf) && file_exists(public_path($naskah->file_pdf)))
            <div class="text-center pt-3 mt-3 border-top">
                <a href="{{ asset($naskah->file_pdf) }}" target="_blank" class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-semibold">
                    <i class="fas fa-file-pdf me-1 text-danger"></i>Lihat Berkas Resmi (PDF)
                </a>
            </div>
            @endif
        </div>
    @else
        <div class="cert-header bg-danger">
            <h5 class="mb-0 fw-bold">DOKUMEN TIDAK DITEMUKAN</h5>
            <div class="cert-badge-invalid">
                <i class="fas fa-times-circle me-1.5"></i> KODE VERIFIKASI TIDAK VALID
            </div>
        </div>
        <div class="cert-body text-center py-4">
            <p class="text-muted mb-3" style="font-size: 13px;">Kode otentifikasi <code>{{ $kode }}</code> tidak terdaftar dalam pangkalan data naskah resmi Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten.</p>
            <a href="{{ url('/') }}" class="btn btn-secondary btn-sm px-4 rounded-pill">Kembali ke Beranda</a>
        </div>
    @endif
    <div class="card-footer bg-light text-center py-2.5 text-muted small cert-footer" style="font-size: 11px;">
        &copy; {{ date('Y') }} SULTAN BANTEN &bull; Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten
    </div>
</div>

<script>
    function copyAuthCode(code) {
        if (!navigator.clipboard) {
            const temp = document.createElement('textarea');
            temp.value = code;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
        } else {
            navigator.clipboard.writeText(code);
        }
        const txt = document.getElementById('btnCopyText');
        if (txt) {
            txt.innerText = 'Tersalin!';
            setTimeout(() => { txt.innerText = 'Salin'; }, 2000);
        }
    }
</script>

</body>
</html>

