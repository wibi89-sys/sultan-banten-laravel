@php
    $instansi = \App\Models\Instansi::getFirst();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('assets/favicon.ico') }}">
    <title>Undangan Sidang TPP - {{ $sidang->upt?->nama_upt ?? 'UPT Pemasyarakatan' }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 12px;
        }
        .card-undangan {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 620px;
            width: 100%;
            background: #ffffff;
        }
        .header-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 24px 20px;
            text-align: center;
        }
        .info-table td {
            padding: 8px 6px;
            vertical-align: top;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="card-undangan">
    <!-- Header -->
    <div class="header-gradient">
        <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-20 rounded-circle mb-2" style="width: 56px; height: 56px;">
            <i class="fas fa-file-contract fa-2x text-white"></i>
        </div>
        <h5 class="fw-bold mb-1">Undangan Sidang TPP</h5>
        <div class="small opacity-75">SULTAN BANTEN - Sistem Monitoring &amp; Supervisi TPP</div>
    </div>

    <!-- Body -->
    <div class="p-4">
        <!-- Status Alert -->
        <div class="alert alert-warning d-flex align-items-start mb-4 border-0 shadow-xs" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg me-3 mt-1 text-warning"></i>
            <div>
                <strong class="d-block mb-1">Berkas Fisik PDF Belum Tersedia</strong>
                <span class="small text-secondary">
                    Berkas pindaian (scan) surat undangan resmi belum diunggah atau sedang dalam proses pembaruan oleh sekretariat TPP UPT. Rincian jadwal dan akses sidang tetap tercatat resmi di bawah ini.
                </span>
            </div>
        </div>

        <!-- Detail Sidang TPP -->
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="fas fa-info-circle text-primary me-2"></i>Rincian Agenda Sidang TPP
        </h6>

        <table class="table table-sm table-borderless info-table mb-4">
            <tbody>
                <tr>
                    <td class="text-muted" style="width: 38%;">Unit Pelaksana Teknis</td>
                    <td class="fw-bold text-dark">: {{ $sidang->upt?->nama_upt ?? 'UPT Pemasyarakatan' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Nomor Surat Undangan</td>
                    <td class="fw-bold font-monospace text-primary">: {{ $sidang->nomor_undangan }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Jadwal Sidang</td>
                    <td class="fw-semibold text-dark">
                        : {{ $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Waktu Pelaksanaan</td>
                    <td>
                        : <span class="badge bg-primary px-2 py-1">{{ substr($sidang->jam_mulai, 0, 5) }} WIB</span> s.d. {{ !empty($sidang->jam_selesai) ? substr($sidang->jam_selesai, 0, 5) . ' WIB' : 'Selesai' }}
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Agenda Sidang</td>
                    <td>: {{ $sidang->agenda_sidang ?: $sidang->agenda_label }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Metode Pelaksanaan</td>
                    <td>: <span class="badge bg-secondary">{{ $sidang->metode_label }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted">Ketua / Sekretaris TPP</td>
                    <td>: {{ $sidang->ketua_tpp }} / {{ $sidang->sekretaris_tpp }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Blok Online / Daring jika tersedia -->
        @if(!empty($sidang->link_daring))
        <div class="card bg-light border-0 rounded-3 p-3 mb-4">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-video text-primary me-2"></i>
                <h6 class="mb-0 fw-bold text-dark">Fasilitas Sidang Virtual / Daring</h6>
            </div>
            <p class="small text-muted mb-2">Anda dapat bergabung ke ruang sidang online secara langsung melalui tautan di bawah ini:</p>
            <a href="{{ $sidang->link_daring }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm mb-2">
                <i class="fas fa-external-link-alt me-1"></i> Buka Link Sidang Daring (Zoom / Meet)
            </a>
            @if(!empty($sidang->meeting_id) || !empty($sidang->passcode))
            <div class="small text-secondary font-monospace bg-white p-2 rounded border">
                @if(!empty($sidang->meeting_id))
                    Meeting ID: <strong>{{ $sidang->meeting_id }}</strong><br>
                @endif
                @if(!empty($sidang->passcode))
                    Passcode: <strong>{{ $sidang->passcode }}</strong>
                @endif
            </div>
            @endif
        </div>
        @endif

        <!-- Footer Action -->
        <div class="text-center pt-2">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm px-4">
                <i class="fas fa-arrow-left me-1"></i> Ke Beranda Sultan Banten
            </a>
        </div>
    </div>

    <!-- Small footer -->
    <div class="bg-light text-center py-2 border-top">
        <small class="text-muted" style="font-size: 11px;">
            &copy; {{ date('Y') }} Divisi Pemasyarakatan Kantor Wilayah Kemenkumham Banten
        </small>
    </div>
</div>

</body>
</html>
