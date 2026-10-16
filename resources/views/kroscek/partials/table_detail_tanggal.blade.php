{{-- 4 Kartu KPI Ringkasan UPT Terpilih dalam Rentang Tanggal --}}
<div class="row g-3 mb-4">
    {{-- KPI 1: Hari Sinkron --}}
    <div class="col-6 col-lg-3">
        <div class="p-3 kpi-card h-100" style="border-top: 4px solid #10b981 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                    Hari Kroscek Sinkron
                </span>
                <div class="icon-box bg-success bg-opacity-10 text-success">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
            <div class="d-flex align-items-baseline gap-1 mb-1">
                <h3 class="mb-0 fw-bold text-success font-monospace">{{ $detailStats['sinkron'] }}</h3>
                <span class="text-muted small">/ {{ $detailStats['total_hari'] }} Hari</span>
            </div>
            <div class="progress mb-2" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $detailStats['pct_sinkron'] }}%;" aria-valuenow="{{ $detailStats['pct_sinkron'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 10px;">
                    {{ $detailStats['pct_sinkron'] }}% Sinkron
                </span>
                <span class="badge {{ $detailStats['selisih'] > 0 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-light text-muted border' }}" style="font-size: 10px;">
                    {{ $detailStats['selisih'] }} Hari Selisih
                </span>
            </div>
        </div>
    </div>

    {{-- KPI 2: Hari 4/4 Lengkap --}}
    <div class="col-6 col-lg-3">
        <div class="p-3 kpi-card h-100" style="border-top: 4px solid #2563eb !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                    Hari 4/4 Lengkap
                </span>
                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
            <div class="d-flex align-items-baseline gap-1 mb-1">
                <h3 class="mb-0 fw-bold text-primary font-monospace">{{ $detailStats['lengkap_4'] }}</h3>
                <span class="text-muted small">/ {{ $detailStats['total_hari'] }} Hari</span>
            </div>
            <div class="progress mb-2" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $detailStats['pct_lengkap'] }}%;" aria-valuenow="{{ $detailStats['pct_lengkap'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10px;">
                    {{ $detailStats['pct_lengkap'] }}% Lengkap
                </span>
                <span class="badge {{ $detailStats['belum_lengkap'] > 0 ? 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25' : 'bg-light text-muted border' }}" style="font-size: 10px;">
                    {{ $detailStats['belum_lengkap'] }} Hari Belum
                </span>
            </div>
        </div>
    </div>

    {{-- KPI 3: UPT Terpilih --}}
    <div class="col-6 col-lg-3">
        <div class="p-3 kpi-card h-100" style="border-top: 4px solid #0284c7 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                    {{ $isOperator ? 'UPT Anda' : 'UPT Terpilih' }}
                </span>
                <div class="icon-box bg-info bg-opacity-10 text-info">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            <h6 class="mb-1 fw-bold text-dark text-truncate" title="{{ $selectedDetailUpt->nama_upt ?? '-' }}">
                {{ $selectedDetailUpt->nama_upt ?? 'Tidak ada UPT' }}
            </h6>
            <small class="text-muted d-block mt-2">
                Batas Waktu: <strong>{{ substr($jamSelesaiStr, 0, 5) }} WIB</strong>
            </small>
        </div>
    </div>

    {{-- KPI 4: Rentang Evaluasi --}}
    <div class="col-6 col-lg-3">
        <div class="p-3 kpi-card h-100" style="border-top: 4px solid #6366f1 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                    Rentang Evaluasi
                </span>
                <div class="icon-box" style="background-color: rgba(99, 102, 241, 0.1); color: #6366f1;">
                    <i class="fas fa-calendar-week"></i>
                </div>
            </div>
            <div class="fw-bold text-dark font-monospace" style="font-size: 13px;">
                {{ \Carbon\Carbon::parse($detailTanggalMulai)->translatedFormat('d M Y') }}
            </div>
            <div class="text-muted small">s/d {{ \Carbon\Carbon::parse($detailTanggalAkhir)->translatedFormat('d M Y') }}</div>
            <div class="mt-2 text-primary small fw-semibold">
                <i class="fas fa-history me-1"></i> {{ $detailStats['total_hari'] }} Hari Kalender
            </div>
        </div>
    </div>
</div>

{{-- Toolbar Pencarian & Quick Filter Tabel 2 --}}
<div class="card mb-4 bg-light shadow-sm rounded-3" style="border: 1.5px solid #cbd5e1 !important;">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0" style="border-color: #cbd5e1;"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchTanggal" class="form-control form-control-sm border-start-0" style="border-color: #cbd5e1;" placeholder="Cari tanggal atau hari (contoh: Senin / 16)..." aria-label="Cari tanggal atau hari">
                </div>
            </div>
            <div class="col-md-7">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <button type="button" class="btn-quick-filter-detail active" data-filter="all">
                        Semua Hari ({{ $detailStats['total_hari'] }})
                    </button>
                    <button type="button" class="btn-quick-filter-detail text-danger" data-filter="masalah">
                        <i class="fas fa-exclamation-triangle me-1"></i>Butuh Perhatian ({{ $detailStats['selisih'] + $detailStats['belum_lengkap'] }})
                    </button>
                    <button type="button" class="btn-quick-filter-detail text-success" data-filter="sinkron">
                        <i class="fas fa-check-double me-1"></i>Tahanan Sinkron ({{ $detailStats['sinkron'] }})
                    </button>
                    <button type="button" class="btn-quick-filter-detail text-primary" data-filter="lengkap">
                        <i class="fas fa-check-circle me-1"></i>4/4 Lengkap ({{ $detailStats['lengkap_4'] }})
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TABEL KELENGKAPAN DETAIL PER TANGGAL (Kolom UPT Diganti dengan Tanggal) --}}
<div class="table-responsive rounded-3 shadow-sm mb-4">
    <table class="table table-bordered table-hover align-middle mb-0 kroscek-table-grid">
        <thead>
            <tr class="text-center align-middle">
                <th rowspan="2" class="text-center" style="width: 32px;">No</th>
                <th rowspan="2" class="text-start" style="width: 16%;">Tanggal</th>
                <th colspan="3" class="text-center th-group-kroscek">
                    <i class="fas fa-balance-scale me-1"></i> Kroscek Tahanan
                </th>
                <th colspan="4" class="text-center th-group-modul">
                    <i class="fas fa-clock me-1"></i> Status & Jam Input 4 Modul Laporan
                </th>
                <th rowspan="2" class="text-center" style="width: 13%;">Populasi & Dinamika</th>
                <th rowspan="2" class="text-center" style="width: 6%;">Aksi</th>
            </tr>
            <tr class="text-center align-middle">
                {{-- Sub-header Kroscek Tahanan --}}
                <th class="th-sub-kroscek" style="width: 5.5%;">Penghuni</th>
                <th class="th-sub-kroscek" style="width: 5.5%;">Overstaying</th>
                <th class="th-sub-kroscek" style="width: 7.5%;">Status</th>
                
                {{-- Sub-header 4 Modul Laporan --}}
                <th class="th-sub-modul" style="width: 7.5%;">Penghuni</th>
                <th class="th-sub-modul" style="width: 7.5%;">Overstaying</th>
                <th class="th-sub-modul" style="width: 7.5%;">Integrasi</th>
                <th class="th-sub-modul" style="width: 7.5%;">Bebas Harian</th>
            </tr>
        </thead>
        <tbody id="detailKroscekTableBody">
            @forelse($detailKroscekList as $row)
                @php
                    $rowIsSinkron = ($row['status_kroscek'] === 'sinkron');
                    $rowIsSelisih = ($row['status_kroscek'] === 'selisih');
                    $rowIsLengkap = $row['is_all_completed'];
                    $rowHasIssues = (!$rowIsSinkron || !$rowIsLengkap);
                @endphp
                <tr data-tanggal="{{ strtolower($row['tanggal']) }} {{ strtolower($row['hari']) }} {{ strtolower($row['tanggal_formatted']) }}"
                    data-sinkron="{{ $rowIsSinkron ? '1' : '0' }}"
                    data-selisih="{{ $rowIsSelisih ? '1' : '0' }}"
                    data-lengkap="{{ $rowIsLengkap ? '1' : '0' }}"
                    data-masalah="{{ $rowHasIssues ? '1' : '0' }}">
                    
                    {{-- No --}}
                    <td class="text-center text-muted fw-semibold" style="font-size: 11px;">{{ $loop->iteration }}</td>

                    {{-- Tanggal (Menggantikan Kolom Nama UPT) --}}
                    <td class="text-start">
                        <div class="fw-bold text-dark" style="font-size: 11.5px; line-height: 1.2;">
                            <i class="far fa-calendar-alt text-primary me-1 opacity-75"></i>
                            {{ $row['hari'] }}, {{ $row['tanggal_formatted'] }}
                        </div>
                        <div class="mt-0.5">
                            @if($rowIsLengkap)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 9px; padding: 1px 5px;">
                                    <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                </span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size: 9px; padding: 1px 5px;">
                                    <i class="fas fa-clock me-1"></i> {{ $row['completed_count'] }}/4 Terisi
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Kroscek Tahanan: Penghuni --}}
                    <td class="text-center font-monospace fw-bold {{ $row['tahanan_penghuni'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 11.5px;">
                        {{ $row['tahanan_penghuni'] !== null ? number_format($row['tahanan_penghuni']) : '-' }}
                    </td>

                    {{-- Kroscek Tahanan: Overstaying --}}
                    <td class="text-center font-monospace fw-bold {{ $row['tahanan_overstaying'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 11.5px;">
                        {{ $row['tahanan_overstaying'] !== null ? number_format($row['tahanan_overstaying']) : '-' }}
                    </td>

                    {{-- Kroscek Tahanan: Status --}}
                    <td class="text-center">
                        @if($rowIsSinkron)
                            <span class="status-pill status-pill-success">
                                <i class="fas fa-check-circle"></i> Sinkron
                            </span>
                        @elseif($rowIsSelisih)
                            <span class="status-pill status-pill-danger">
                                <i class="fas fa-times-circle"></i> Selisih {{ $row['selisih'] }}
                            </span>
                        @else
                            <span class="status-pill status-pill-secondary">
                                <i class="fas fa-minus-circle"></i> Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 1: Penghuni --}}
                    <td class="text-center">
                        @if($row['penghuni']['exists'])
                            <div class="fw-bold text-dark font-monospace" style="font-size: 11px;">{{ $row['penghuni']['jam_str'] }}</div>
                            @if($row['penghuni']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 2: Overstaying --}}
                    <td class="text-center">
                        @if($row['overstaying']['exists'])
                            <div class="fw-bold text-dark font-monospace" style="font-size: 11px;">{{ $row['overstaying']['jam_str'] }}</div>
                            @if($row['overstaying']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 3: Integrasi --}}
                    <td class="text-center">
                        @if($row['integrasi']['exists'])
                            <div class="fw-bold text-dark font-monospace" style="font-size: 11px;">{{ $row['integrasi']['jam_str'] }}</div>
                            @if($row['integrasi']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 4: Bebas Harian --}}
                    <td class="text-center">
                        @if($row['bebas']['exists'])
                            <div class="fw-bold text-dark font-monospace" style="font-size: 11px;">{{ $row['bebas']['jam_str'] }}</div>
                            @if($row['bebas']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 8.5px; padding: 1px 4px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Populasi & Dinamika --}}
                    <td class="text-center" style="font-size: 11px; white-space: nowrap;">
                        <div class="text-dark fw-bold">
                            {{ number_format($row['penghuni']['total_penghuni']) }} <span class="fw-normal text-muted" style="font-size: 9.5px;">WBP</span>
                        </div>
                        <div class="text-muted" style="font-size: 10px;">
                            Bbs: <strong>{{ $row['bebas']['total_bebas'] }}</strong> · Ost: <strong>{{ $row['overstaying']['total_overstaying'] }}</strong>
                        </div>
                    </td>

                    {{-- Aksi: Lihat Detail Modal --}}
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2" style="font-size: 10.5px;" onclick="showDetailModal({{ json_encode($row) }})" title="Lihat Rincian Lengkap Tanggal Ini">
                            <i class="fas fa-eye me-1"></i> Rincian
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">Belum ada data pada rentang tanggal yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
