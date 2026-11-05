@php
    $bannerUser = auth()->user();
    $pendingMutasiTasks = null;
    if ($bannerUser && ! $bannerUser->isKanwil() && ! empty($bannerUser->upt_id)) {
        try {
            $pendingMutasiTasks = \App\Models\MutasiNarapidana::getPendingTasksForUpt((int) $bannerUser->upt_id);
        } catch (\Throwable $e) {
            $pendingMutasiTasks = null;
        }
    }
@endphp

@if($pendingMutasiTasks && $pendingMutasiTasks['total'] > 0)
<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 p-3 position-relative" role="alert" style="background: linear-gradient(135deg, rgba(254, 243, 199, 0.95) 0%, rgba(253, 230, 138, 0.85) 100%); border-left: 5px solid #f59e0b !important;">
    <div class="d-flex align-items-start justify-content-between mb-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark p-2" style="width: 34px; height: 34px;">
                <i class="fas fa-bell fa-sm"></i>
            </span>
            <div>
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    Pengingat Tindak Lanjut Mutasi Narapidana
                    <span class="badge bg-danger rounded-pill px-2 py-0.5" style="font-size: 11px;">
                        {{ $pendingMutasiTasks['total'] }} Tindakan
                    </span>
                </h6>
                <small class="text-secondary" style="font-size: 11px;">
                    Satuan Kerja: <strong>{{ $bannerUser->upt?->nama_upt }}</strong> &bull; Harap segera lengkapi dokumen pelaksanaan mutasi yang telah disetujui Kanwil.
                </small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>

    <div class="row g-2 mt-1">
        <!-- 1. PENGIRIM: LAPORAN PEMINDAHAN -->
        @if(count($pendingMutasiTasks['laporan_pemindahan']) > 0)
        <div class="col-12 {{ count($pendingMutasiTasks['laporan_penerimaan']) > 0 || count($pendingMutasiTasks['bast']) > 0 ? 'col-lg-4' : '' }}">
            <div class="card border-0 shadow-xs rounded-2 bg-white p-2.5 h-100">
                <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom">
                    <span class="fw-bold small text-primary d-flex align-items-center gap-1">
                        <i class="fas fa-truck-departure"></i> 1. Lap. Pemindahan (Pengirim)
                    </span>
                    <span class="badge bg-primary rounded-pill">{{ count($pendingMutasiTasks['laporan_pemindahan']) }}</span>
                </div>
                <div class="list-group list-group-flush" style="font-size: 11.5px; max-height: 180px; overflow-y: auto;">
                    @foreach($pendingMutasiTasks['laporan_pemindahan'] as $task)
                    <div class="list-group-item px-1 py-1.5 border-0 border-bottom bg-transparent d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate text-dark font-monospace" style="font-size: 11px;">
                                {{ $task['mutasi']->nomor_surat }}
                            </div>
                            <small class="text-muted d-block text-truncate">
                                Tujuan: <strong>{{ $task['nama_tujuan'] }}</strong> ({{ $task['total_wbp'] }} WBP)
                            </small>
                        </div>
                        <a href="{{ route('mutasi.show', $task['mutasi']->id) }}" class="btn btn-primary btn-xs flex-shrink-0 fw-semibold" style="font-size: 10.5px; padding: 2px 7px;">
                            Unggah <i class="fas fa-arrow-right ms-0.5"></i>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- 2. PENERIMA: LAPORAN PENERIMAAN -->
        @if(count($pendingMutasiTasks['laporan_penerimaan']) > 0)
        <div class="col-12 {{ count($pendingMutasiTasks['laporan_pemindahan']) > 0 || count($pendingMutasiTasks['bast']) > 0 ? 'col-lg-4' : '' }}">
            <div class="card border-0 shadow-xs rounded-2 bg-white p-2.5 h-100">
                <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom">
                    <span class="fw-bold small text-info d-flex align-items-center gap-1">
                        <i class="fas fa-building"></i> 2. Lap. Penerimaan (Penerima)
                    </span>
                    <span class="badge bg-info text-white rounded-pill">{{ count($pendingMutasiTasks['laporan_penerimaan']) }}</span>
                </div>
                <div class="list-group list-group-flush" style="font-size: 11.5px; max-height: 180px; overflow-y: auto;">
                    @foreach($pendingMutasiTasks['laporan_penerimaan'] as $task)
                    <div class="list-group-item px-1 py-1.5 border-0 border-bottom bg-transparent d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate text-dark font-monospace" style="font-size: 11px;">
                                {{ $task['mutasi']->nomor_surat }}
                            </div>
                            <small class="text-muted d-block text-truncate">
                                Dari: <strong>{{ $task['nama_pengirim'] }}</strong> ({{ $task['total_wbp'] }} WBP)
                            </small>
                        </div>
                        <a href="{{ route('mutasi.show', $task['mutasi']->id) }}" class="btn btn-info btn-xs text-white flex-shrink-0 fw-semibold" style="font-size: 10.5px; padding: 2px 7px;">
                            Unggah <i class="fas fa-arrow-right ms-0.5"></i>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- 3. BERSAMA: BAST FISIK -->
        @if(count($pendingMutasiTasks['bast']) > 0)
        <div class="col-12 {{ count($pendingMutasiTasks['laporan_pemindahan']) > 0 || count($pendingMutasiTasks['laporan_penerimaan']) > 0 ? 'col-lg-4' : '' }}">
            <div class="card border-0 shadow-xs rounded-2 bg-white p-2.5 h-100">
                <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom">
                    <span class="fw-bold small text-success d-flex align-items-center gap-1">
                        <i class="fas fa-file-signature"></i> 3. BAST Fisik (Bersama)
                    </span>
                    <span class="badge bg-success rounded-pill">{{ count($pendingMutasiTasks['bast']) }}</span>
                </div>
                <div class="list-group list-group-flush" style="font-size: 11.5px; max-height: 180px; overflow-y: auto;">
                    @foreach($pendingMutasiTasks['bast'] as $task)
                    <div class="list-group-item px-1 py-1.5 border-0 border-bottom bg-transparent d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate text-dark font-monospace" style="font-size: 11px;">
                                {{ $task['mutasi']->nomor_surat }}
                            </div>
                            <small class="text-muted d-block text-truncate">
                                Mitra: <strong>{{ $task['mitra_upt'] }}</strong> ({{ $task['total_wbp'] }} WBP)
                            </small>
                        </div>
                        <a href="{{ route('mutasi.show', $task['mutasi']->id) }}" class="btn btn-success btn-xs flex-shrink-0 fw-semibold" style="font-size: 10.5px; padding: 2px 7px;">
                            Unggah <i class="fas fa-arrow-right ms-0.5"></i>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif
