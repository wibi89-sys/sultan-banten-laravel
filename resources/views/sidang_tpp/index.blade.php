@extends('layouts.app')

@section('title', 'Jadwal & Undangan Daring - Sidang TPP')
@section('page_title')
    <i class="fas fa-gavel me-2 text-primary"></i>Sidang TPP &bull; Jadwal &amp; Undangan Daring
@endsection

@push('styles')
<style>
    .stat-card-tpp {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        position: relative;
    }
    .stat-card-tpp:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .stat-card-tpp .stat-icon {
        position: absolute;
        right: 15px;
        bottom: 12px;
        font-size: 44px;
        opacity: 0.18;
    }
    .link-zoom-btn {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white !important;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(37,99,235,0.25);
        transition: all 0.2s ease;
    }
    .link-zoom-btn:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-1px);
        color: white !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Flash Message -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-check-circle fa-lg me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- 4 Kartu Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card-tpp bg-primary text-white p-3">
                <div class="small fw-semibold text-white-50">Sidang Bulan Ini</div>
                <div class="h3 fw-bold mb-0 mt-1">{{ number_format($totalSidangBulanIni) }}</div>
                <div class="small mt-1 text-white-50"><i class="fas fa-calendar-alt me-1"></i>Periode {{ date('F Y') }}</div>
                <i class="fas fa-calendar-check stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card-tpp bg-warning text-dark p-3">
                <div class="small fw-semibold text-dark-50">Total Usulan Warga Binaan</div>
                <div class="h3 fw-bold mb-0 mt-1">{{ number_format($totalUsulanBulanIni) }} <span class="fs-6 fw-normal">Orang</span></div>
                <div class="small mt-1 text-dark-50"><i class="fas fa-users me-1"></i>Usulan hak bersyarat</div>
                <i class="fas fa-user-friends stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card-tpp bg-info text-white p-3">
                <div class="small fw-semibold text-white-50">Menunggu Verifikasi Kanwil</div>
                <div class="h3 fw-bold mb-0 mt-1">{{ number_format($totalMenungguVerifikasi) }}</div>
                <div class="small mt-1 text-white-50"><i class="fas fa-hourglass-half me-1"></i>Bintorwasdal Kanwil</div>
                <i class="fas fa-stamp stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card-tpp bg-success text-white p-3">
                <div class="small fw-semibold text-white-50">Selesai &amp; TTD Ka. Kanwil</div>
                <div class="h3 fw-bold mb-0 mt-1">{{ number_format($totalSelesaiTtd) }}</div>
                <div class="small mt-1 text-white-50"><i class="fas fa-file-signature me-1"></i>Dokumen siap diunduh UPT</div>
                <i class="fas fa-award stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Toolbar & Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-calendar-alt text-primary me-2"></i>Daftar Agenda &amp; Undangan Sidang TPP</h5>
                    <p class="text-muted small mb-0">Implementasi Bintorwasdal sesuai Surat Edaran Direktur Jenderal Pemasyarakatan No: PAS-38.OT.02.02 Tahun 2026</p>
                </div>
                <div class="d-flex gap-2">
                    @if(auth()->user()->canManage('sidang_tpp') && !empty(auth()->user()->upt_id))
                    <a href="{{ route('sidang-tpp.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm" id="btnJadwalkanSidang">
                        <i class="fas fa-plus-circle me-1"></i>Jadwalkan Sidang Baru
                    </a>
                    @elseif(!empty(auth()->user()->upt_id))
                    <span class="badge bg-secondary py-2 px-3 small d-flex align-items-center">
                        <i class="fas fa-eye me-1"></i>Mode Lihat Saja
                    </span>
                    @else
                    <span class="badge bg-light text-muted border py-2 px-3 small d-flex align-items-center">
                        <i class="fas fa-eye me-1 text-primary"></i>Mode Monitoring Kanwil
                    </span>
                    @endif
                </div>
            </div>

            <!-- Form Filter -->
            <form method="GET" action="{{ route('sidang-tpp.index') }}" class="row g-2 align-items-end pt-2 border-top">
                @if(!$userUptFilter)
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filter UPT</label>
                    <select name="upt_id" class="form-select form-select-sm" id="filter_upt_id">
                        <option value="">-- Seluruh UPT Se-Banten --</option>
                        @foreach($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status Sidang</label>
                    <select name="status" class="form-select form-select-sm" id="filter_status">
                        <option value="">-- Semua Status --</option>
                        <option value="dijadwalkan" {{ $statusSidang == 'dijadwalkan' ? 'selected' : '' }}>Dijadwalkan</option>
                        <option value="menunggu_hasil_upt" {{ $statusSidang == 'menunggu_hasil_upt' ? 'selected' : '' }}>Menunggu Hasil UPT</option>
                        <option value="menunggu_verifikasi_kanwil" {{ $statusSidang == 'menunggu_verifikasi_kanwil' ? 'selected' : '' }}>Verifikasi Kanwil</option>
                        <option value="selesai_ttd_kakanwil" {{ $statusSidang == 'selesai_ttd_kakanwil' ? 'selected' : '' }}>Selesai &amp; TTD Ka. Kanwil</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" class="form-select form-select-sm" value="{{ $tanggalMulai }}" id="filter_tgl_mulai">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="tanggal_sampai" class="form-select form-select-sm" value="{{ $tanggalSampai }}" id="filter_tgl_sampai">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnFilterApply">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('sidang-tpp.index') }}" class="btn btn-light btn-sm text-secondary border" id="btnFilterReset">
                        <i class="fas fa-undo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Sidang TPP -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableSidangTpp">
                <thead class="table-light text-secondary small fw-bold">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th>Jadwal Sidang</th>
                        <th>Unit Pelaksana Teknis (UPT)</th>
                        <th>Undangan Kanwil</th>
                        <th>Agenda Sidang</th>
                        <th>Metode &amp; Akses Daring</th>
                        <th class="text-center">Usulan Warga Binaan</th>
                        <th class="text-center">Status Sidang</th>
                        <th class="text-center">Verifikasi Kesesuaian Dokumen</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidangList as $index => $s)
                    <tr>
                        <td class="text-center text-muted fw-semibold small">{{ $sidangList->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $s->tanggal_sidang->format('d/m/Y') }}</div>
                            <div class="small text-muted"><i class="far fa-clock me-1"></i>{{ substr((string)$s->jam_mulai, 0, 5) }} WIB</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $s->upt?->nama_upt ?? '-' }}</div>
                            <small class="text-muted"><i class="fas fa-user-tie me-1"></i>Ketua: {{ $s->ketua_tpp }}</small>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $s->nomor_undangan }}</div>
                            <div class="small text-muted">Tgl Surat: {{ $s->tanggal_surat->format('d/m/Y') }}</div>
                            @if($s->file_undangan)
                            <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'undangan']) }}" class="badge bg-light text-primary border text-decoration-none mt-1 d-inline-block">
                                <i class="fas fa-file-pdf text-danger me-1"></i>Unduhan Surat
                            </a>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $s->agenda_sidang ?? $s->agenda_label }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold mb-1">{{ $s->metode_label }}</div>
                            @if(!empty($s->link_daring))
                            <a href="{{ $s->link_daring }}" target="_blank" rel="noopener noreferrer" class="link-zoom-btn" title="Klik untuk langsung bergabung ke Sidang TPP Daring">
                                <i class="fas fa-video"></i>Masuk Zoom / Daring
                            </a>
                            @if(!empty($s->meeting_id))
                            <div class="small text-muted mt-1" style="font-size: 11px;">ID: {{ $s->meeting_id }}</div>
                            @endif
                            @else
                            <span class="text-muted small">Tatap Muka di UPT</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6">{{ $s->total_usulan }}</span>
                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                Setuju: <b class="text-success">{{ $s->total_setuju }}</b>
                            </div>
                        </td>
                        <td class="text-center">
                            {!! $s->status_badge !!}
                        </td>
                        <td class="text-center">
                            {!! $s->verifikasi_badge !!}
                            @if(!empty($s->file_laporan_kakanwil))
                            <div class="mt-1">
                                <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'laporan_kakanwil']) }}" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none">
                                    <i class="fas fa-file-download me-1"></i>TTD Ka. Kanwil
                                </a>
                            </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('sidang-tpp.show', $s->id) }}" class="btn btn-outline-info" title="Detail Lengkap Sidang">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->canManage('sidang_tpp') && !empty(auth()->user()->upt_id) && auth()->user()->upt_id == $s->upt_id && !in_array($s->status_verifikasi_kanwil, ['sesuai', 'terverifikasi']))
                                <form action="{{ route('sidang-tpp.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda sidang ini? Seluruh data usulan dan berkas terkait akan terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Hapus Sidang">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="fas fa-gavel fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6>Belum Ada Data Sidang TPP</h6>
                            <p class="small mb-3">Jadwalkan sidang TPP UPT dengan mengunggah undangan ke Kanwil &amp; menginput/impor usulan Warga Binaan via Excel.</p>
                            @if(auth()->user()->canManage('sidang_tpp') && !empty(auth()->user()->upt_id))
                            <a href="{{ route('sidang-tpp.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus-circle me-1"></i>Mulai Daftarkan Sidang
                            </a>
                            @endif
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
