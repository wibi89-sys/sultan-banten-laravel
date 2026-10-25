@extends('layouts.app')

@section('title', 'Hasil Sidang TPP (Tingkat UPT) - Sultan Banten')
@section('page_title')
    <i class="fas fa-clipboard-check me-2 text-primary"></i>Sidang TPP &bull; Hasil &amp; Keputusan UPT
@endsection

@section('content')
<div class="container-fluid px-0">
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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-clipboard-check text-success me-2"></i>Penetapan Putusan Sidang &amp; Unggah Berita Acara (BA) UPT</h5>
                    <p class="text-muted small mb-0">Input penetapan keputusan per-Warga Binaan (Setuju / Tidak Setuju / Tunda) serta unggah Berita Acara untuk diajukan ke Kantor Wilayah. <i>(Kewenangan Khusus Petugas UPT)</i></p>
                </div>
            </div>

            <!-- Filter UPT jika Admin / Kanwil -->
            @if(!$userUptFilter)
            <form method="GET" action="{{ route('sidang-tpp.hasil') }}" class="row g-2 align-items-end pt-2 border-top">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Filter Berdasarkan UPT</label>
                    <select name="upt_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Seluruh UPT Pemasyarakatan --</option>
                        @foreach($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 text-md-end">
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle small p-2">
                        <i class="fas fa-info-circle me-1 text-warning"></i>User Kanwil berada dalam mode <b>Monitoring / Lihat Data</b>. Pengisian hasil sidang dilakukan oleh user UPT terkait.
                    </span>
                </div>
            </form>
            @endif
        </div>
    </div>

    <!-- Daftar Sidang untuk Input Hasil -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableHasilSidang">
                <thead class="table-light small fw-bold text-secondary">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th>Jadwal Sidang</th>
                        <th>Unit Pelaksana Teknis</th>
                        <th>Agenda Sidang</th>
                        <th>Nomor BA UPT</th>
                        <th class="text-center">Usulan Warga Binaan</th>
                        <th class="text-center">Rincian Keputusan</th>
                        <th class="text-center">Verifikasi Kesesuaian Dokumen</th>
                        <th style="width: 170px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidangList as $index => $s)
                    <tr>
                        <td class="text-center text-muted small">{{ $sidangList->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold">{{ $s->tanggal_sidang->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ substr((string)$s->jam_mulai, 0, 5) }} WIB</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $s->upt?->nama_upt ?? '-' }}</div>
                            <small class="text-muted">Undangan: {{ $s->nomor_undangan }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $s->agenda_sidang ?? $s->agenda_label }}</div>
                        </td>
                        <td>
                            @if(!empty($s->nomor_berita_acara))
                            <div class="fw-semibold small text-dark">{{ $s->nomor_berita_acara }}</div>
                            @if(!empty($s->file_berita_acara))
                            <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'berita_acara']) }}" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none">
                                <i class="fas fa-file-pdf me-1"></i>Unduh BA
                            </a>
                            @endif
                            @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Belum Ada BA</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6">{{ $s->total_usulan }}</span>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-success text-white" title="Setuju">{{ $s->total_setuju }} Setuju</span>
                            <span class="badge bg-warning text-dark" title="Tunda">{{ $s->total_tunda }} Tunda</span>
                            <span class="badge bg-danger text-white" title="Tidak Setuju">{{ $s->total_tidak_setuju }} Tidak Setuju</span>
                        </td>
                        <td class="text-center">
                            {!! $s->verifikasi_badge !!}
                        </td>
                        <td class="text-center">
                            @if(!empty(auth()->user()->upt_id) && auth()->user()->upt_id == $s->upt_id)
                                <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="openModalHasil({{ $s->id }})">
                                    <i class="fas fa-sliders-h me-1"></i>Kontrol Sidang
                                </button>
                            @elseif(empty(auth()->user()->upt_id))
                                <a href="{{ route('sidang-tpp.show', $s->id) }}" class="btn btn-sm btn-outline-secondary" title="Kanwil hanya memiliki hak verifikasi & melihat rincian">
                                    <i class="fas fa-eye me-1"></i>Lihat Rincian
                                </a>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Hanya UPT Terkait</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6>Belum Ada Sidang yang Memerlukan Input Hasil</h6>
                            <p class="small mb-0">Seluruh jadwal sidang telah terisi hasil keputusannya atau belum ada agenda sidang terdaftar.</p>
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

<!-- Modal Form Input Hasil Sidang & Upload BA -->
<div class="modal fade" id="modalInputHasil" tabindex="-1" aria-labelledby="modalHasilTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formHasilSidang" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalHasilTitle">
                        <i class="fas fa-file-upload me-2"></i>Upload Berita Acara dan Dokumentasi Sidang
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="small text-muted mt-2">Memuat rincian usulan Warga Binaan...</p>
                    </div>

                    <div id="modalContentArea" class="d-none">
                        <!-- Card Header Info -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <div class="row g-2 small">
                                <div class="col-md-4">
                                    <span class="text-muted">UPT Pelaksana:</span> <b id="lblNamaUpt" class="text-primary">-</b>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted">Tanggal Sidang:</span> <b id="lblTanggalSidang">-</b>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted">Total Usulan Warga Binaan:</span> <b id="lblTotalWbp">-</b> Orang
                                </div>
                            </div>
                        </div>

                        <!-- Data BA & Upload Dokumen -->
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Berita Acara &amp; Dokumentasi Sidang</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="nomor_berita_acara" class="form-label small fw-semibold">Nomor Berita Acara (BA) Sidang TPP <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_berita_acara" id="nomor_berita_acara" class="form-control" placeholder="Contoh: W12.PAS.PAS.1-BA.01-085/2026" required>
                            </div>
                            <div class="col-md-6">
                                <label for="file_berita_acara" class="form-label small fw-semibold">Unggah Berkas Berita Acara TPP Bertanda Tangan (PDF)</label>
                                <input type="file" name="file_berita_acara" id="file_berita_acara" class="form-control" accept=".pdf">
                                <small class="text-muted" style="font-size: 11px;">Scan PDF BA TPP lengkap dengan tanda tangan Ketua &amp; Anggota Tim</small>
                            </div>
                            <div class="col-md-12">
                                <label for="dokumentasi" class="form-label small fw-semibold">
                                    <i class="fas fa-camera text-primary me-1"></i>Unggah Foto Dokumentasi Sidang TPP (Bisa Pilih Beberapa Foto)
                                </label>
                                <input type="file" name="dokumentasi[]" id="dokumentasi" class="form-control" accept="image/*" multiple>
                                <div class="form-text text-muted" style="font-size: 11px;">
                                    <i class="fas fa-magic text-success me-1"></i>Sistem secara otomatis mengonversi ke format <b>JPG</b> dan mengompresi resolusi agar ukuran file <b>maksimal 500 KB per foto dokumentasi</b>.
                                </div>
                            </div>
                        </div>

                        <!-- Putusan per Warga Binaan -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold text-dark mb-0">2. Hasil Sidang</h6>
                            <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm px-3" onclick="setSemuaSetuju()" title="Otomatis pilih hasil 'Setuju' untuk seluruh Warga Binaan dalam sidang ini">
                                <i class="fas fa-check-double me-1.5"></i>Pilih "Setuju" Untuk Semua
                            </button>
                        </div>

                        <div class="table-responsive border rounded" style="max-height: 380px;">
                            <table class="table table-sm table-hover align-middle mb-0" id="tableModalUsulan" style="font-size: 12px;">
                                <thead class="table-dark sticky-top small">
                                    <tr>
                                        <th style="width: 35px;" class="text-center">No</th>
                                        <th>Nama Warga Binaan &amp; Register</th>
                                        <th>Perkara / Pidana</th>
                                        <th class="text-center">Jenis Usulan</th>
                                        <th class="text-center" style="width: 160px;">Atensi / Perhatian</th>
                                        <th style="width: 170px;" class="text-center">Keputusan Sidang</th>
                                        <th>Alasan / Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="modalUsulanTbody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSimpanHasil">
                        <i class="fas fa-paper-plane me-1"></i>Simpan Hasil &amp; Ajukan ke Kanwil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const modalHasil = new bootstrap.Modal(document.getElementById('modalInputHasil'));
    const modalLoading = document.getElementById('modalLoading');
    const modalContent = document.getElementById('modalContentArea');
    const formHasil = document.getElementById('formHasilSidang');
    const tbody = document.getElementById('modalUsulanTbody');

    function openModalHasil(sidangId) {
        modalLoading.classList.remove('d-none');
        modalContent.classList.add('d-none');
        tbody.innerHTML = '';
        formHasil.action = '/sidang-tpp/' + sidangId + '/hasil';
        modalHasil.show();

        fetch('/sidang-tpp/' + sidangId, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Data tidak ditemukan');
                }
                const s = data.sidang;
                const usulanList = data.usulan || [];

                document.getElementById('lblNamaUpt').innerText = s.upt?.nama_upt || '-';
                document.getElementById('lblTanggalSidang').innerText = s.tanggal_sidang ? new Date(s.tanggal_sidang).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-';
                document.getElementById('lblTotalWbp').innerText = usulanList.length;

                if (s.nomor_berita_acara) {
                    document.getElementById('nomor_berita_acara').value = s.nomor_berita_acara;
                } else {
                    document.getElementById('nomor_berita_acara').value = '';
                }

                tbody.innerHTML = '';
                usulanList.forEach((item, idx) => {
                    const tr = document.createElement('tr');
                    if (item.perlu_perhatian) {
                        tr.classList.add('table-danger', 'bg-opacity-25');
                    }
                    let curKeputusan = item.keputusan || 'setuju';
                    if (curKeputusan === 'disetujui') curKeputusan = 'setuju';
                    if (curKeputusan === 'ditolak') curKeputusan = 'tidak_setuju';
                    if (curKeputusan === 'ditunda') curKeputusan = 'tunda';

                    const badgeAtensi = item.perlu_perhatian
                        ? `<span class="badge bg-danger text-white fw-bold shadow-sm"><i class="fas fa-exclamation-triangle me-1"></i>MENARIK PERHATIAN</span>${item.catatan_perhatian ? '<br><small class="text-danger fw-semibold">' + item.catatan_perhatian + '</small>' : ''}`
                        : `<span class="badge bg-light text-muted border">Normal</span>`;

                    tr.innerHTML = `
                        <td class="text-center text-muted">${idx + 1}</td>
                        <td><b>${item.nama_wbp || '-'}</b><br><small class="text-muted"><code>${item.nomor_register || '-'}</code></small></td>
                        <td><small>${item.perkara_pasal || '-'}</small><br><small class="text-muted">${item.lama_pidana || ''}</small></td>
                        <td class="text-center"><span class="badge bg-primary">${item.jenis_usulan || 'PB'}</span></td>
                        <td class="text-center">${badgeAtensi}</td>
                        <td>
                            <select name="keputusan[${item.id}]" class="form-select form-select-sm keputusan-select fw-semibold">
                                <option value="setuju" ${curKeputusan === 'setuju' ? 'selected' : ''} class="text-success fw-bold">🟢 Setuju</option>
                                <option value="tidak_setuju" ${curKeputusan === 'tidak_setuju' ? 'selected' : ''} class="text-danger fw-bold">🔴 Tidak Setuju</option>
                                <option value="tunda" ${curKeputusan === 'tunda' ? 'selected' : ''} class="text-warning fw-bold">🟡 Tunda</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="alasan_keterangan[${item.id}]" value="${item.alasan_keterangan ? item.alasan_keterangan.replace(/"/g, '&quot;') : ''}" class="form-control form-control-sm" placeholder="Catatan syarat / perbaikan jika ada">
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                modalLoading.classList.add('d-none');
                modalContent.classList.remove('d-none');
            })
            .catch(err => {
                console.error(err);
                alert('Gagal memuat rincian sidang TPP.');
                modalHasil.hide();
            });
    }

    function setSemuaSetuju() {
        document.querySelectorAll('.keputusan-select').forEach(el => {
            el.value = 'setuju';
        });
    }
</script>
@endpush
