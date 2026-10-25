@extends('layouts.app')

@section('title', 'Rekap & Download Dokumen - Sidang TPP')
@section('page_title')
    <i class="fas fa-file-download me-2 text-primary"></i>Sidang TPP &bull; Rekapitulasi &amp; Unduhan Dokumen Resmi
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-archive text-primary me-2"></i>Pusat Arsip &amp; Unduhan Dokumen Sidang TPP</h5>
                    <p class="text-muted small mb-0">Arsip sidang terverifikasi, pelacakan usulan hak bersyarat Warga Binaan, dan pusat download Laporan Ber-TTD Ka. Kanwil untuk UPT.</p>
                </div>
                <div>
                    <a href="{{ route('sidang-tpp.export-excel', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm" id="btnExportRekapExcel">
                        <i class="fas fa-file-excel me-1"></i>Ekspor Rekap Excel
                    </a>
                </div>
            </div>

            <!-- Filter & Search Form -->
            <form method="GET" action="{{ route('sidang-tpp.rekapitulasi') }}" class="row g-2 align-items-end pt-2 border-top">
                @if(!$userUptFilter)
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filter UPT</label>
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Seluruh UPT Se-Banten --</option>
                        @foreach($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cari Warga Binaan (Nama / Register)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search_wbp" class="form-control" placeholder="Nama Warga Binaan atau no. register..." value="{{ $searchWbp }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" class="form-select form-select-sm" value="{{ $tanggalMulai }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="tanggal_sampai" class="form-select form-select-sm" value="{{ $tanggalSampai }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-filter me-1"></i>Filter</button>
                    <a href="{{ route('sidang-tpp.rekapitulasi') }}" class="btn btn-light btn-sm text-secondary border"><i class="fas fa-undo me-1"></i>Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Rekapitulasi & Dokumen -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableRekapDokumen">
                <thead class="table-light small fw-bold text-secondary">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th>Jadwal Sidang</th>
                        <th>Unit Pelaksana Teknis</th>
                        <th>Agenda Sidang</th>
                        <th class="text-center">Hasil Keputusan</th>
                        <th class="text-center">Surat Undangan</th>
                        <th class="text-center">Berita Acara UPT</th>
                        <th class="text-center" style="background-color: #ecfdf5; color: #065f46;">Laporan TTD Ka. Kanwil</th>
                        <th style="width: 100px;" class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidangList as $index => $s)
                    <tr>
                        <td class="text-center text-muted small">{{ $sidangList->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold">{{ $s->tanggal_sidang->format('d/m/Y') }}</div>
                            <small class="text-muted"><i class="far fa-clock me-1"></i>{{ substr((string)$s->jam_mulai, 0, 5) }} WIB</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $s->upt?->nama_upt ?? '-' }}</div>
                            <small class="text-muted">Ketua: {{ $s->ketua_tpp }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $s->agenda_sidang ?? $s->agenda_label }}</div>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-primary mb-1">{{ $s->total_usulan }} Warga Binaan</span>
                            <div>
                                <span class="text-success fw-bold">{{ $s->total_setuju }} Setuju</span> &bull; 
                                <span class="text-warning fw-bold">{{ $s->total_tunda }} Tunda</span> &bull; 
                                <span class="text-danger fw-bold">{{ $s->total_tidak_setuju }} Tidak Setuju</span>
                            </div>
                        </td>
                        <td class="text-center">
                            @if(!empty($s->file_undangan))
                            <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'undangan']) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="Unduh Surat Undangan Kanwil">
                                <i class="fas fa-file-pdf text-danger me-1"></i>Unduh
                            </a>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!empty($s->file_berita_acara))
                            <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'berita_acara']) }}" class="btn btn-outline-success btn-sm py-1 px-2" title="Unduh Berita Acara UPT">
                                <i class="fas fa-file-pdf text-danger me-1"></i>Unduh BA
                            </a>
                            @else
                            <span class="badge bg-light text-secondary border">Belum Ada</span>
                            @endif
                        </td>
                        <td class="text-center" style="background-color: #f0fdf4;">
                            @if(!empty($s->file_laporan_kakanwil))
                            <a href="{{ route('sidang-tpp.download-laporan-kanwil', $s->id) }}" class="btn btn-success btn-sm py-1 px-3 fw-bold shadow-sm" title="Unduh Laporan Sidang TPP Kantor Wilayah (Dasar Usulan Hak Bersyarat)">
                                <i class="fas fa-file-signature me-1"></i>Unduh Laporan Kanwil
                            </a>
                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                No: <b>{{ $s->nomor_laporan_kanwil ?: '-' }}</b>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size: 10px;">
                                <i class="fas fa-check-circle me-1"></i>Dasar Usulan Hak Bersyarat
                            </span>
                            @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle text-wrap text-start p-2" style="font-size: 11px;">
                                <i class="fas fa-clock me-1"></i>Menunggu Upload Laporan dari Kanwil
                            </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('sidang-tpp.show', $s->id) }}" class="btn btn-outline-info btn-sm" title="Lihat Rincian Sidang & Usulan Warga Binaan">
                                <i class="fas fa-eye me-1"></i>Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6>Tidak Ada Data Rekapitulasi Sidang TPP</h6>
                            <p class="small mb-0">Sesuaikan filter atau pastikan data sidang telah terdaftar pada sistem.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sidangList->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $sidangList->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
