@extends('layouts.app')

@section('title', 'Backup & Ekspor Database - Sultan Banten')
@section('page_title')
    <i class="fas fa-database me-2 text-warning"></i>Backup &amp; Ekspor Database
@endsection

@push('styles')
<style>
    .backup-stat-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .backup-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .backup-action-box {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
        border: 1px dashed var(--border-color, #cbd5e1);
        border-radius: 14px;
        padding: 1.5rem;
    }
    .btn-download-hero {
        padding: 0.85rem 1.4rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
    }
    .btn-download-hero:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .table-backup td, .table-backup th {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">

    {{-- Breadcrumb & Subtitle --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item">Master Data</li>
                    <li class="breadcrumb-item active" aria-current="page">Backup Database</li>
                </ol>
            </nav>
            <p class="text-muted small mb-0">
                Cadangkan seluruh struktur skema dan data aplikasi secara mandiri dalam hitungan detik tanpa perlu masuk ke cPanel hosting.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="fas fa-check-circle me-1"></i> Koneksi Database Aktif ({{ $stats['driver'] }})
            </span>
        </div>
    </div>

    {{-- Alert Notifikasi --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="fas fa-check-circle fa-lg me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Kartu Metrik Database Saat Ini --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="backup-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Ukuran Database</span>
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                        <i class="fas fa-hdd"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ $stats['total_size_mb'] }} <span class="fs-6 fw-normal text-muted">MB</span></div>
                <div class="text-muted small mt-1">Total alokasi data &amp; index</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="backup-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Jumlah Tabel</span>
                    <span class="badge bg-info-subtle text-info p-2 rounded-circle">
                        <i class="fas fa-table"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ $stats['total_tables'] }} <span class="fs-6 fw-normal text-muted">Tabel</span></div>
                <div class="text-muted small mt-1">Seluruh tabel sistem aktif</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="backup-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Total Baris Data</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-circle">
                        <i class="fas fa-layer-group"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-dark">± {{ number_format($stats['total_rows'], 0, ',', '.') }}</div>
                <div class="text-muted small mt-1">Baris data narapidana &amp; log</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="backup-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Nama Database</span>
                    <span class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                        <i class="fas fa-server"></i>
                    </span>
                </div>
                <div class="fs-5 fw-bold text-dark text-truncate" title="{{ $stats['database_name'] }}">
                    {{ $stats['database_name'] }}
                </div>
                <div class="text-muted small mt-1">Versi: {{ Str::limit($stats['server_version'] ?? 'MySQL', 15) }}</div>
            </div>
        </div>
    </div>

    {{-- Kartu Aksi Cepat: 1-Klik Unduh Cadangan --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle bg-warning-subtle text-warning">
                        <i class="fas fa-cloud-download-alt fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">1-Klik Unduh Database (Tanpa cPanel)</h5>
                        <p class="text-muted small mb-0">Pilih format cadangan yang ingin langsung diunduh ke laptop atau ponsel Anda.</p>
                    </div>
                </div>
            </div>

            <div class="backup-action-box mb-3">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8">
                        <h6 class="fw-bold text-primary mb-1">
                            <i class="fas fa-bolt me-1 text-warning"></i> Unduh Salinan Lengkap Sekarang
                        </h6>
                        <p class="text-muted small mb-0">
                            Cadangan mencakup seluruh <strong>34 tabel</strong>, relasi kunci, dan semua data riwayat narapidana, mutasi, sidang TPP, hingga log aktivitas. Sistem mengekspor secara instan tanpa mengganggu pengguna lain yang sedang bertugas.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <form action="{{ route('backup.generate') }}" method="POST" class="d-inline" id="formSimpanServer">
                            @csrf
                            <input type="hidden" name="format" value="gz">
                            <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Simpan salinan cadangan baru ke folder server?')">
                                <i class="fas fa-save me-1"></i> Simpan ke Server
                            </button>
                        </form>
                    </div>
                </div>

                <hr class="my-3 text-secondary opacity-25">

                <div class="d-flex flex-wrap gap-3 align-items-center">
                    {{-- Tombol Terkompresi .sql.gz (Disarankan) --}}
                    <a href="{{ route('backup.download', ['format' => 'gz']) }}" class="btn btn-success btn-download-hero shadow-sm">
                        <i class="fas fa-file-archive fa-lg"></i>
                        <span>Unduh Database (.sql.gz)</span>
                        <span class="badge bg-light text-success ms-1">Disarankan: ± 2-3 MB</span>
                    </a>

                    {{-- Tombol Mentah .sql --}}
                    <a href="{{ route('backup.download', ['format' => 'sql']) }}" class="btn btn-outline-primary btn-download-hero">
                        <i class="fas fa-file-code fa-lg"></i>
                        <span>Unduh SQL Mentah (.sql)</span>
                        <span class="badge bg-primary text-white ms-1">± {{ $stats['total_size_mb'] }} MB</span>
                    </a>
                </div>
            </div>

            {{-- Jaminan Keamanan --}}
            <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light border border-light-subtle">
                <i class="fas fa-shield-alt text-success fa-2x mt-1"></i>
                <div class="small text-muted">
                    <strong class="text-dark d-block mb-1">100% Aman &amp; Bersifat Read-Only</strong>
                    Operasi ekspor ini murni membaca struktur dan baris data aplikasi (<code>SELECT</code> &amp; <code>SHOW TABLES</code>). Sama sekali tidak mengubah, mereset, atau menghapus data database hidup. Anda dapat mengunduh cadangan kapan saja secara bebas sebelum melakukan update script aplikasi.
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat File Cadangan di Server --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-1"><i class="fas fa-history me-2 text-primary"></i>Riwayat File Cadangan di Server</h5>
                <p class="text-muted small mb-0">File cadangan tersimpan di direktori aman <code>storage/app/backups/</code> (terlindungi dari akses publik).</p>
            </div>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                Total: {{ count($files) }} File Tersimpan
            </span>
        </div>

        <div class="card-body p-4 pt-2">
            @if(count($files) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-backup align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th style="width: 4%;">#</th>
                            <th style="width: 40%;">Nama File Cadangan</th>
                            <th style="width: 22%;">Waktu Pembuatan</th>
                            <th style="width: 14%;">Ukuran File</th>
                            <th style="width: 20%;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $index => $file)
                        <tr>
                            <td class="text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($file['is_compressed'])
                                        <span class="badge bg-success-subtle text-success p-2 rounded">
                                            <i class="fas fa-file-archive fa-lg"></i>
                                        </span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary p-2 rounded">
                                            <i class="fas fa-file-code fa-lg"></i>
                                        </span>
                                    @endif
                                    <div>
                                        <span class="fw-bold font-monospace d-block text-dark">{{ $file['filename'] }}</span>
                                        <span class="badge {{ $file['is_compressed'] ? 'bg-success text-white' : 'bg-primary text-white' }}" style="font-size: 0.7rem;">
                                            {{ $file['is_compressed'] ? 'GZIP Compressed (.gz)' : 'Plain Text SQL' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-medium">{{ $file['created_at'] }}</div>
                                <span class="text-muted small">Tersimpan di server</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 fw-bold font-monospace">
                                    {{ $file['size_formatted'] }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    {{-- Tombol Unduh --}}
                                    <a href="{{ route('backup.download-file', $file['filename']) }}" class="btn btn-sm btn-primary" title="Unduh file cadangan ini">
                                        <i class="fas fa-download me-1"></i> Unduh
                                    </a>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('backup.destroy-file', $file['filename']) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file cadangan {{ $file['filename'] }} dari server? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus file ini dari server">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 my-2">
                <div class="p-3 bg-light rounded-circle d-inline-block text-muted mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-folder-open fa-2x mt-2"></i>
                </div>
                <h6 class="fw-bold text-dark">Belum Ada File Cadangan di Server</h6>
                <p class="text-muted small mb-3">
                    Anda dapat langsung mengunduh database menggunakan tombol di atas, atau menekan tombol <strong>Simpan ke Server</strong> untuk menyimpan arsip file di server.
                </p>
                <form action="{{ route('backup.generate') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="format" value="gz">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-save me-1"></i> Buat Cadangan Pertama Sekarang
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    {{-- Panduan Singkat Pemulihan (Restore) --}}
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h6 class="fw-bold mb-1 text-dark">
                <i class="fas fa-info-circle me-2 text-info"></i>Petunjuk Singkat Pemulihan Database (Restore Guide)
            </h6>
            <p class="text-muted small mb-0">Bagaimana cara mengembalikan data jika suatu saat terjadi kendala pada server?</p>
        </div>
        <div class="card-body p-4 pt-2">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="fas fa-window-maximize me-1"></i> Cara 1: Melalui phpMyAdmin (cPanel)
                        </h6>
                        <ol class="small text-muted ps-3 mb-0">
                            <li>Buka <strong>phpMyAdmin</strong> pada cPanel hosting Anda.</li>
                            <li>Pilih database <code>{{ $stats['database_name'] }}</code> pada bilah sisi kiri.</li>
                            <li>Klik menu tab <strong>Import</strong> di bagian atas.</li>
                            <li>Pilih file cadangan hasil unduhan (file <code>.sql</code> maupun <code>.sql.gz</code> didukung langsung).</li>
                            <li>Klik tombol <strong>Kirim / Go</strong> di bagian bawah. Selesai!</li>
                        </ol>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <h6 class="fw-bold text-success mb-2">
                            <i class="fas fa-terminal me-1"></i> Cara 2: Melalui Terminal SSH / Command Line
                        </h6>
                        <p class="small text-muted mb-2">Jika Anda memiliki akses SSH ke server VPS/Hosting, jalankan perintah:</p>
                        <div class="p-2 bg-dark text-white rounded font-monospace small mb-2">
                            mysql -u [username] -p {{ $stats['database_name'] }} &lt; backup.sql
                        </div>
                        <p class="small text-muted mb-1">Untuk file terkompresi <code>.sql.gz</code> tanpa perlu diekstrak terlebih dahulu:</p>
                        <div class="p-2 bg-dark text-white rounded font-monospace small mb-0">
                            gunzip &lt; backup.sql.gz | mysql -u [username] -p {{ $stats['database_name'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

