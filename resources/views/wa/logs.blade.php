@extends('layouts.app')

@section('title', 'Riwayat & Monitoring Log WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-history text-success me-2"></i>Riwayat &amp; Monitoring Log WhatsApp
@endsection

@section('content')
<!-- Header Quick Navigation Tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.gateway') }}">
                    <i class="fas fa-plug me-1 text-primary"></i>1. Gateway & Koneksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.contacts') }}">
                    <i class="fas fa-address-book me-1 text-info"></i>2. Kontak & Target
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.schedules') }}">
                    <i class="fas fa-clock me-1 text-warning"></i>3. Jadwal & Template
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active py-1 px-3 fw-semibold" href="{{ route('wa.logs') }}">
                    <i class="fas fa-history me-1 text-success"></i>4. Riwayat & Log
                </a>
            </li>
        </ul>
        <span class="badge bg-light text-dark border px-3 py-2 small">
            <i class="fas fa-clock me-1 text-primary"></i>Waktu Server: <strong>{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</strong>
        </span>
    </div>
</div>

<!-- ================================= KARTU STATUS HARI INI ================================= -->
<div class="row g-3 mb-4">
    <!-- Status Pagi -->
    <div class="col-md-4">
        <div class="content-card h-100 p-3 border-start border-4 border-warning shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-muted fw-semibold text-uppercase">Reminder Pagi</span>
                <i class="fas fa-sun text-warning fa-lg"></i>
            </div>
            <div class="h5 fw-bold mb-1">
                @if($morningStatus['sent'])
                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Sudah Terkirim</span>
                @else
                    <span class="text-secondary"><i class="fas fa-clock me-1"></i>Belum / Siap</span>
                @endif
            </div>
            <small class="text-muted" style="font-size: 11px;">
                Jadwal: {{ substr($waSettings->jam_reminder_pagi ?? '07:30', 0, 5) }} WIB &bull; 
                {{ $morningStatus['count'] }} log tercatat hari ini
            </small>
        </div>
    </div>

    <!-- Status Siang -->
    <div class="col-md-4">
        <div class="content-card h-100 p-3 border-start border-4 border-info shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-muted fw-semibold text-uppercase">Monitoring Siang</span>
                <i class="fas fa-clipboard-check text-info fa-lg"></i>
            </div>
            <div class="h5 fw-bold mb-1">
                @if($afternoonStatus['sent'])
                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Sudah Terkirim</span>
                @else
                    <span class="text-secondary"><i class="fas fa-clock me-1"></i>Belum / Siap</span>
                @endif
            </div>
            <small class="text-muted" style="font-size: 11px;">
                Jadwal: {{ substr($waSettings->jam_reminder_siang ?? '12:00', 0, 5) }} WIB &bull; 
                Target: {{ Str::limit($waSettings->group_id ?? '-', 20) }}
            </small>
        </div>
    </div>

    <!-- Status Gateway Master -->
    <div class="col-md-4">
        <div class="content-card h-100 p-3 border-start border-4 {{ !empty($waSettings->aktif) ? 'border-success' : 'border-danger' }} shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-muted fw-semibold text-uppercase">Status Master Otomatisasi</span>
                <i class="fas fa-power-off {{ !empty($waSettings->aktif) ? 'text-success' : 'text-danger' }} fa-lg"></i>
            </div>
            <div class="h5 fw-bold mb-1">
                @if(!empty($waSettings->aktif))
                    <span class="text-success"><i class="fas fa-toggle-on me-1"></i>Aktif Berjalan</span>
                @else
                    <span class="text-danger"><i class="fas fa-toggle-off me-1"></i>Nonaktif / Jeda</span>
                @endif
            </div>
            <small class="text-muted" style="font-size: 11px;">
                Provider: {{ ucfirst($waSettings->gateway_name ?? 'Fonnte') }} &bull; 
                Anti-Loop Lock: <strong>Aktif</strong>
            </small>
        </div>
    </div>
</div>

<!-- ================================= TABEL UNIFIED LOGS ================================= -->
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold fs-6 mb-1">
                <i class="fas fa-list text-primary me-2"></i>Catatan Riwayat Pengiriman Pesan
            </h5>
            <small class="text-muted">Menampilkan seluruh riwayat pengiriman notifikasi, pengingat, dan laporan terjadwal.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('wa.logs') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-sync-alt me-1"></i>Refresh Log
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form action="{{ route('wa.logs') }}" method="GET" class="row g-2 mb-3 p-2 bg-light rounded align-items-center">
        <div class="col-md-4">
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Semua Jenis Pesan --</option>
                <option value="reminder_pagi" {{ request('type') == 'reminder_pagi' ? 'selected' : '' }}>Reminder Pagi</option>
                <option value="reminder_siang" {{ request('type') == 'reminder_siang' ? 'selected' : '' }}>Monitoring Siang</option>
                <option value="scheduled" {{ request('type') == 'scheduled' ? 'selected' : '' }}>Jadwal Laporan Terpadu</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Semua Status --</option>
                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Hanya Sukses</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Hanya Gagal</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-2">
            @if(request('type') || request('status') || request('date'))
                <a href="{{ route('wa.logs') }}" class="btn btn-outline-danger btn-sm w-100">
                    <i class="fas fa-times me-1"></i>Reset
                </a>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 150px;">Waktu Kirim</th>
                    <th>Jenis Tugas</th>
                    <th>Target Penerima</th>
                    <th class="text-center" style="width: 100px;">Status</th>
                    <th>Cuplikan Isi Pesan</th>
                    <th>Respons Gateway</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="small text-muted font-monospace">
                        {{ date('d/m/Y H:i', strtotime($log->tanggal_kirim ?? 'now')) }}
                    </td>
                    <td>
                        @if($log->log_source === 'scheduled')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="fas fa-calendar-alt me-1"></i>{{ $log->nama_laporan ?? 'Laporan Terjadwal' }}
                            </span>
                        @elseif($log->tipe === 'reminder_pagi')
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                <i class="fas fa-sun me-1"></i>Reminder Pagi
                            </span>
                        @elseif($log->tipe === 'reminder_siang' || $log->tipe === 'laporan_siang')
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                <i class="fas fa-clipboard-check me-1"></i>Monitoring Siang
                            </span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($log->tipe ?? 'Pesan') }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="small font-monospace text-dark">{{ Str::limit($log->target ?? '-', 25) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $log->status === 'success' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td class="small text-muted" title="{{ $log->pesan ?? '' }}">
                        {{ Str::limit($log->pesan ?? '-', 65) }}
                    </td>
                    <td class="small text-muted" title="{{ $log->response ?? '' }}">
                        {{ Str::limit($log->response ?? 'OK', 40) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pengiriman pesan yang sesuai filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
