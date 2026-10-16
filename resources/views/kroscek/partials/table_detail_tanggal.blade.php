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

{{-- Switcher Format Tampilan Khusus Mobile: Tabel Matriks vs Kartu Ringkas --}}
<div class="d-flex d-md-none align-items-center justify-content-between mb-3 p-1.5 bg-light rounded-pill border">
    <span class="text-muted small ps-2 fw-semibold" style="font-size: 11.5px;">
        <i class="fas fa-sliders-h text-primary me-1"></i> Format Tampilan:
    </span>
    <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1 fw-semibold active" id="btnShowDetailTable">
            <i class="fas fa-table me-1"></i> Tabel
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold" id="btnShowDetailCards">
            <i class="fas fa-th-large me-1"></i> Kartu
        </button>
    </div>
</div>

{{-- Petunjuk Geser Tabel pada Layar HP / Mobile --}}
<div class="d-md-none alert alert-light border py-2 px-3 mb-2 small text-muted d-flex align-items-center justify-content-between rounded-3 shadow-xs" id="detailScrollHint">
    <span class="small fw-medium"><i class="fas fa-arrows-alt-h text-primary me-1"></i> Geser tabel ke samping untuk melihat detail modul & kroscek</span>
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10px;">11 Kolom</span>
</div>

{{-- TABEL KELENGKAPAN DETAIL PER TANGGAL (Kolom UPT Diganti dengan Tanggal) --}}
<div class="table-responsive table-responsive-kroscek shadow-sm mb-4" id="detailTableWrapper">
    <table class="table table-bordered table-hover align-middle mb-0 kroscek-table-grid">
        <colgroup>
            <col class="col-no" style="width: 3.5%;">
            <col style="width: 19.5%;">
            <col style="width: 6.5%;">
            <col style="width: 6.5%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 17%;">
            <col style="width: 7%;">
        </colgroup>
        <thead>
            <tr class="text-center align-middle">
                <th rowspan="2" class="text-center col-no">No</th>
                <th rowspan="2" class="text-start">Tanggal</th>
                <th colspan="3" class="text-center th-group-kroscek">
                    <i class="fas fa-balance-scale me-1"></i> Kroscek Tahanan
                </th>
                <th colspan="4" class="text-center th-group-modul">
                    <i class="fas fa-clock me-1"></i> Status & Jam Input 4 Modul Laporan
                </th>
                <th rowspan="2" class="text-center">Populasi & Dinamika</th>
                <th rowspan="2" class="text-center">Aksi</th>
            </tr>
            <tr class="text-center align-middle">
                {{-- Sub-header Kroscek Tahanan --}}
                <th class="th-sub-kroscek">Penghuni</th>
                <th class="th-sub-kroscek">Overstay</th>
                <th class="th-sub-kroscek">Status</th>
                
                {{-- Sub-header 4 Modul Laporan --}}
                <th class="th-sub-modul">Penghuni</th>
                <th class="th-sub-modul">Overstay</th>
                <th class="th-sub-modul">Integrasi</th>
                <th class="th-sub-modul">Bebas Harian</th>
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
                    <td class="text-center text-muted col-no" style="font-size: 12px;">{{ $loop->iteration }}</td>

                    {{-- Tanggal (Menggantikan Kolom Nama UPT) --}}
                    <td class="text-start">
                        <div class="fw-semibold text-dark" style="font-size: 12.5px; line-height: 1.35;">
                            <i class="far fa-calendar-alt text-primary me-1 opacity-75"></i>
                            {{ $row['hari'] }}, {{ $row['tanggal_formatted'] }}
                        </div>
                        <div class="mt-1">
                            @if($rowIsLengkap)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                    <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                </span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                    <i class="fas fa-clock me-1"></i> {{ $row['completed_count'] }}/4 Terisi
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Kroscek Tahanan: Penghuni --}}
                    <td class="text-center font-monospace {{ $row['tahanan_penghuni'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 12.5px;">
                        {{ $row['tahanan_penghuni'] !== null ? number_format($row['tahanan_penghuni']) : '-' }}
                    </td>

                    {{-- Kroscek Tahanan: Overstaying --}}
                    <td class="text-center font-monospace {{ $row['tahanan_overstaying'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 12.5px;">
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
                            <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['penghuni']['jam_str'] }}</div>
                            @if($row['penghuni']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 2: Overstaying --}}
                    <td class="text-center">
                        @if($row['overstaying']['exists'])
                            <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['overstaying']['jam_str'] }}</div>
                            @if($row['overstaying']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 3: Integrasi --}}
                    <td class="text-center">
                        @if($row['integrasi']['exists'])
                            <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['integrasi']['jam_str'] }}</div>
                            @if($row['integrasi']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Modul 4: Bebas Harian --}}
                    <td class="text-center">
                        @if($row['bebas']['exists'])
                            <div class="text-secondary font-monospace" style="font-size: 12px;">{{ $row['bebas']['jam_str'] }}</div>
                            @if($row['bebas']['is_tepat'])
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Tepat</span>
                            @else
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">Terlambat</span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-medium" style="font-size: 9.5px; padding: 1.5px 6px;">
                                <i class="fas fa-times me-0.5"></i>Belum
                            </span>
                        @endif
                    </td>

                    {{-- Populasi & Dinamika --}}
                    <td class="text-center" style="font-size: 12px; white-space: nowrap;">
                        <div class="text-dark fw-semibold">
                            {{ number_format($row['penghuni']['total_penghuni']) }} <span class="fw-normal text-muted" style="font-size: 11px;">WBP</span>
                        </div>
                        <div class="text-muted" style="font-size: 11px;">
                            Bbs: {{ $row['bebas']['total_bebas'] }} · Ost: {{ $row['overstaying']['total_overstaying'] }}
                        </div>
                    </td>

                    {{-- Aksi: Lihat Detail Modal --}}
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-medium" style="font-size: 11.5px;" onclick="showDetailModal({{ json_encode($row) }})" title="Lihat Rincian Lengkap Tanggal Ini">
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

{{-- Mobile Cards Container untuk Layar Ponsel (Detail per Tanggal) --}}
<div id="detailCardsContainer" class="d-none d-md-none mb-4">
    @forelse($detailKroscekList as $row)
        @php
            $rowIsSinkron = ($row['status_kroscek'] === 'sinkron');
            $rowIsSelisih = ($row['status_kroscek'] === 'selisih');
            $rowIsLengkap = $row['is_all_completed'];
            $rowHasIssues = (!$rowIsSinkron || !$rowIsLengkap);
        @endphp
        <div class="card mb-3 shadow-sm border rounded-3 kroscek-mobile-card"
             data-tanggal="{{ strtolower($row['tanggal']) }} {{ strtolower($row['hari']) }} {{ strtolower($row['tanggal_formatted']) }}"
             data-sinkron="{{ $rowIsSinkron ? '1' : '0' }}"
             data-selisih="{{ $rowIsSelisih ? '1' : '0' }}"
             data-lengkap="{{ $rowIsLengkap ? '1' : '0' }}"
             data-masalah="{{ $rowHasIssues ? '1' : '0' }}">
            <div class="card-body p-3">
                {{-- Header Card: No, Tanggal, Status Badge --}}
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill mt-0.5" style="font-size: 11px;">#{{ $loop->iteration }}</span>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 13.5px; line-height: 1.35;">
                                <i class="far fa-calendar-alt text-primary me-1"></i> {{ $row['hari'] }}, {{ $row['tanggal_formatted'] }}
                            </h6>
                            <div>
                                @if($rowIsLengkap)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                        <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 fw-medium" style="font-size: 10px; padding: 2px 7px;">
                                        <i class="fas fa-clock me-1"></i> {{ $row['completed_count'] }}/4 Terisi
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
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
                    </div>
                </div>

                {{-- Kroscek Data Tahanan --}}
                <div class="bg-light p-2.5 rounded-3 mb-2.5 border">
                    <div class="d-flex justify-content-between align-items-center small text-muted mb-1 pb-1 border-bottom">
                        <span class="fw-semibold text-dark"><i class="fas fa-balance-scale text-primary me-1"></i> Kroscek Tahanan:</span>
                        <span class="font-monospace fw-bold {{ $rowIsSinkron ? 'text-success' : ($rowIsSelisih ? 'text-danger' : 'text-muted') }}" style="font-size: 11.5px;">
                            {{ $rowIsSinkron ? 'SINKRON' : ($rowIsSelisih ? 'SELISIH '.$row['selisih'] : 'BELUM LENGKAP') }}
                        </span>
                    </div>
                    <div class="row text-center g-2 pt-1">
                        <div class="col-6 border-end">
                            <div class="text-muted" style="font-size: 11px;">Penghuni</div>
                            <div class="fw-bold font-monospace {{ $row['tahanan_penghuni'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 14px;">
                                {{ $row['tahanan_penghuni'] !== null ? number_format($row['tahanan_penghuni']) : '-' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size: 11px;">Overstaying</div>
                            <div class="fw-bold font-monospace {{ $row['tahanan_overstaying'] !== null ? 'text-dark' : 'text-muted' }}" style="font-size: 14px;">
                                {{ $row['tahanan_overstaying'] !== null ? number_format($row['tahanan_overstaying']) : '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Jam Input 4 Modul --}}
                <div class="mb-2.5">
                    <div class="small fw-semibold text-muted mb-1.5"><i class="fas fa-clock text-primary me-1"></i> Jam Input 4 Modul:</div>
                    <div class="row g-1.5 text-center">
                        <div class="col-3">
                            <div class="p-1.5 rounded-2 bg-light border">
                                <div class="text-muted" style="font-size: 10px;">Penghuni</div>
                                @if($row['penghuni']['exists'])
                                    <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['penghuni']['jam_str'] }}</div>
                                    <span class="badge {{ $row['penghuni']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['penghuni']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['penghuni']['is_tepat'] ? 'Tepat' : 'Telat' }}</span>
                                @else
                                    <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-1.5 rounded-2 bg-light border">
                                <div class="text-muted" style="font-size: 10px;">Overstay</div>
                                @if($row['overstaying']['exists'])
                                    <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['overstaying']['jam_str'] }}</div>
                                    <span class="badge {{ $row['overstaying']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['overstaying']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['overstaying']['is_tepat'] ? 'Tepat' : 'Telat' }}</span>
                                @else
                                    <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-1.5 rounded-2 bg-light border">
                                <div class="text-muted" style="font-size: 10px;">Integrasi</div>
                                @if($row['integrasi']['exists'])
                                    <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['integrasi']['jam_str'] }}</div>
                                    <span class="badge {{ $row['integrasi']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['integrasi']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['integrasi']['is_tepat'] ? 'Tepat' : 'Telat' }}</span>
                                @else
                                    <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-1.5 rounded-2 bg-light border">
                                <div class="text-muted" style="font-size: 10px;">Bebas</div>
                                @if($row['bebas']['exists'])
                                    <div class="font-monospace fw-semibold text-dark" style="font-size: 11px;">{{ $row['bebas']['jam_str'] }}</div>
                                    <span class="badge {{ $row['bebas']['is_tepat'] ? 'bg-success text-success' : 'bg-warning text-dark' }} bg-opacity-15 border border-{{ $row['bebas']['is_tepat'] ? 'success' : 'warning' }} border-opacity-25" style="font-size: 9px; padding: 1px 4px;">{{ $row['bebas']['is_tepat'] ? 'Tepat' : 'Telat' }}</span>
                                @else
                                    <div class="text-muted font-monospace" style="font-size: 11px;">-</div>
                                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25" style="font-size: 9px; padding: 1px 4px;">Belum</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Populasi & Tombol Aksi --}}
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 12.5px;">{{ number_format($row['penghuni']['total_penghuni']) }} WBP</div>
                        <div class="text-muted" style="font-size: 11px;">Bbs: {{ $row['bebas']['total_bebas'] }} · Ost: {{ $row['overstaying']['total_overstaying'] }}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 fw-medium rounded-pill shadow-xs" style="font-size: 11.5px;" onclick="showDetailModal({{ json_encode($row) }})">
                        <i class="fas fa-eye me-1"></i> Rincian
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="card p-4 text-center text-muted">Belum ada data pada rentang tanggal yang dipilih.</div>
    @endforelse
</div>
