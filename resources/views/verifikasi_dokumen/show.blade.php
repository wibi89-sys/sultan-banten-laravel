<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Keabsahan Dokumen Digital - SULTAN BANTEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .cert-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            max-width: 650px;
            width: 100%;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .cert-header {
            background: linear-gradient(135deg, #0F4761 0%, #1e698d 100%);
            color: white;
            padding: 30px 24px 20px;
            text-align: center;
            position: relative;
        }
        .cert-badge-valid {
            display: inline-flex;
            align-items: center;
            background: #10b981;
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            margin-top: 15px;
        }
        .cert-badge-invalid {
            display: inline-flex;
            align-items: center;
            background: #ef4444;
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
            margin-top: 15px;
        }
        .meta-table th {
            width: 38%;
            font-size: 12.5px;
            color: #64748b;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .meta-table td {
            font-size: 13px;
            color: #1e293b;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

<div class="cert-card">
    @if($naskah)
        <div class="cert-header">
            <div class="d-flex justify-content-center align-items-center mb-2">
                @if(!empty($instansi->logo_kop) && file_exists(public_path($instansi->logo_kop)))
                    <img src="{{ asset($instansi->logo_kop) }}" alt="Logo" style="height: 48px; max-width: 120px;" class="me-2">
                @endif
                <div class="text-start">
                    <div class="small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px; opacity: 0.9;">Kementerian Imigrasi &amp; Pemasyarakatan RI</div>
                    <div class="fw-bold" style="font-size: 14px;">KANTOR WILAYAH BANTEN</div>
                </div>
            </div>
            <h5 class="mb-0 fw-bold mt-2">LEMBAR OTENTIFIKASI DOKUMEN DIGITAL</h5>
            <div class="cert-badge-valid">
                <i class="fas fa-check-circle me-1.5"></i> DOKUMEN RESMI TERVERIFIKASI
            </div>
        </div>

        <div class="p-4">
            <div class="alert alert-light border py-2.5 px-3 rounded-3 small mb-3 text-secondary d-flex align-items-center">
                <i class="fas fa-shield-alt fa-lg me-2.5 text-success"></i>
                <div style="font-size: 12px; line-height: 1.4;">
                    Dokumen ini sah dan terdaftar resmi pada Sistem Informasi <b>SULTAN BANTEN</b>. Informasi di bawah ini sesuai dengan arsip digital asli yang diterbitkan.
                </div>
            </div>

            <table class="table meta-table mb-3">
                <tbody>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <td>
                            @if($naskah->jenis_dokumen === 'laporan_tpp')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Laporan Pelaksanaan Sidang TPP Kanwil</span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Nota Dinas Permohonan Surat Tugas</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Nomor Dokumen</th>
                        <td class="text-primary">{{ $naskah->nomor_dokumen ?: '-' }}</td>
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
                        <td class="small">{{ $naskah->materi_agenda ?: ($naskah->sidangTpp->agenda_kategori ?? 'Sidang Tim Pengamat Pemasyarakatan') }}</td>
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
                        <td><code>{{ $naskah->kode_verifikasi }}</code></td>
                    </tr>
                </tbody>
            </table>

            <div class="text-center pt-2 border-top">
                @if(!empty($naskah->file_pdf) && file_exists(public_path($naskah->file_pdf)))
                    <a href="{{ asset($naskah->file_pdf) }}" target="_blank" class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-semibold me-2">
                        <i class="fas fa-file-pdf me-1 text-danger"></i>Lihat Berkas Resmi (PDF)
                    </a>
                @endif
                @if(!empty($naskah->file_signed) && file_exists(public_path($naskah->file_signed)))
                    <a href="{{ asset($naskah->file_signed) }}" target="_blank" class="btn btn-outline-success btn-sm px-3 rounded-pill fw-semibold">
                        <i class="fas fa-file-signature me-1"></i>Lihat Hasil Scan TTD Basah
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="cert-header bg-danger">
            <h5 class="mb-0 fw-bold">DOKUMEN TIDAK DITEMUKAN</h5>
            <div class="cert-badge-invalid">
                <i class="fas fa-times-circle me-1.5"></i> KODE VERIFIKASI TIDAK VALID
            </div>
        </div>
        <div class="p-4 text-center">
            <p class="text-muted mb-3">Kode otentifikasi <code>{{ $kode }}</code> tidak terdaftar dalam pangkalan data naskah resmi Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten.</p>
            <a href="{{ url('/') }}" class="btn btn-secondary btn-sm px-4 rounded-pill">Kembali ke Beranda</a>
        </div>
    @endif
    <div class="card-footer bg-light text-center py-2.5 text-muted small" style="font-size: 11px;">
        &copy; {{ date('Y') }} SULTAN BANTEN &bull; Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten
    </div>
</div>

</body>
</html>

