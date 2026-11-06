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
                    <p class="text-muted small mb-0">Input penetapan keputusan per-Warga Binaan (Setuju / Tidak Setuju / Tunda) serta unggah Berita Acara untuk diajukan ke Kantor Wilayah. <i>(Sidang masa lalu tetap dapat diunggah kapan saja)</i></p>
                </div>
                <div>
                    <span class="badge bg-light text-dark border p-2">
                        <i class="fas fa-calendar-alt text-primary me-1"></i>Total: <b>{{ $sidangList->total() }}</b> Sidang Terdata
                    </span>
                </div>
            </div>

            <!-- Form Filter & Pencarian -->
            <form method="GET" action="{{ route('sidang-tpp.hasil') }}" class="row g-2 align-items-end pt-2 border-top">
                @if(!$userUptFilter)
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filter UPT</label>
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Seluruh UPT Pemasyarakatan --</option>
                        @foreach($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status Berita Acara</label>
                    <select name="status_ba" class="form-select form-select-sm">
                        <option value="">-- Semua Status BA --</option>
                        <option value="belum_upload" {{ $statusBa == 'belum_upload' ? 'selected' : '' }}>⚠️ Belum Upload BA</option>
                        <option value="sudah_upload" {{ $statusBa == 'sudah_upload' ? 'selected' : '' }}>✅ Sudah Upload BA</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $tanggalMulai }}" placeholder="Mulai">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ $tanggalSampai }}" placeholder="Sampai">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="No. Undangan / BA / Agenda" value="{{ $search }}">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        <a href="{{ route('sidang-tpp.hasil') }}" class="btn btn-light border text-secondary" title="Reset Filter"><i class="fas fa-undo"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($totalBelumUploadBa > 0 && empty($statusBa))
    <div class="alert alert-warning py-2.5 px-3 small d-flex flex-wrap align-items-center justify-content-between mb-3 shadow-sm">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg me-2 text-warning"></i>
            <div>
                Terdapat <b>{{ $totalBelumUploadBa }}</b> sidang TPP yang belum mengunggah berkas Berita Acara (BA). Sidang yang telah lewat tetap dapat diunggah Berita Acaranya kapan saja.
            </div>
        </div>
        <a href="{{ route('sidang-tpp.hasil', array_merge(request()->query(), ['status_ba' => 'belum_upload'])) }}" class="btn btn-warning btn-sm fw-bold mt-1 mt-md-0 shadow-sm">
            <i class="fas fa-filter me-1"></i>Tampilkan Sidang Belum Upload BA
        </a>
    </div>
    @endif

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
                        <th style="width: 200px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidangList as $index => $s)
                    @php
                        $isPast = $s->tanggal_sidang->isPast();
                        $canManageHasil = auth()->user()->isAdmin() || (!empty(auth()->user()->upt_id) && (int) auth()->user()->upt_id === (int) $s->upt_id);
                    @endphp
                    <tr>
                        <td class="text-center text-muted small">{{ $sidangList->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold">{{ $s->tanggal_sidang->format('d/m/Y') }}</div>
                            <small class="text-muted"><i class="far fa-clock me-1"></i>{{ substr((string)$s->jam_mulai, 0, 5) }} WIB</small>
                            @if($isPast)
                            <div><span class="badge bg-secondary-subtle text-secondary border mt-1" style="font-size: 10px;"><i class="fas fa-history me-1"></i>Sidang Lalu</span></div>
                            @endif
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
                            <div class="d-inline-flex align-items-center gap-1 mt-1">
                                <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'berita_acara', 'preview' => 1]) }}" target="_blank" rel="noopener noreferrer" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none py-1 px-1.5" title="Buka Berita Acara di Tab Baru">
                                    <i class="fas fa-external-link-alt me-1"></i>Lihat BA
                                </a>
                                <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'berita_acara']) }}" class="badge bg-light text-secondary border text-decoration-none py-1 px-1" title="Unduh Berkas Asli">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
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
                            <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">
                                @if($canManageHasil)
                                    @if(empty($s->file_berita_acara))
                                    <button type="button" class="btn btn-sm btn-primary shadow-sm text-nowrap" onclick="openModalHasil({{ $s->id }})" title="Unggah Berita Acara dan Hasil Putusan Sidang">
                                        <i class="fas fa-file-upload me-1"></i>Upload BA &amp; Hasil
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm text-nowrap" onclick="openModalHasil({{ $s->id }})" title="Perbarui Berita Acara atau Hasil Keputusan Sidang">
                                        <i class="fas fa-edit me-1"></i>Edit Hasil &amp; BA
                                    </button>
                                    @endif
                                @endif
                                <a href="{{ route('sidang-tpp.show', $s->id) }}" class="btn btn-sm btn-light border text-secondary" title="Lihat Detail Lengkap Sidang">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6>Tidak Ada Data Sidang TPP Sesuai Kriteria Filter</h6>
                            <p class="small mb-0">Silakan ubah filter pencarian atau pastikan agenda sidang telah didaftarkan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sidangList->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $sidangList->links('pagination::bootstrap-5') }}
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
                            <div id="lblPastNoticeModal" class="alert alert-info py-1.5 px-2.5 small d-none mt-2 mb-0">
                                <i class="fas fa-history me-1"></i><b>Sidang Telah Terlaksana / Masa Lalu:</b> Berita Acara (BA), foto dokumentasi, dan penetapan hasil sidang tetap dapat diunggah dan disimpan tanpa batasan tanggal.
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
                                <div id="lblCurrentBaFile" class="form-text small mt-1">
                                    <span class="text-muted" style="font-size: 11px;">Scan PDF BA TPP lengkap dengan tanda tangan Ketua &amp; Anggota Tim (Maks. 15 MB)</span>
                                </div>
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

                // Tampilkan banner sidang lalu jika tanggal sudah lewat
                const pastNotice = document.getElementById('lblPastNoticeModal');
                if (pastNotice) {
                    if (s.tanggal_sidang && new Date(s.tanggal_sidang) < new Date()) {
                        pastNotice.classList.remove('d-none');
                    } else {
                        pastNotice.classList.add('d-none');
                    }
                }

                if (s.nomor_berita_acara) {
                    document.getElementById('nomor_berita_acara').value = s.nomor_berita_acara;
                } else {
                    document.getElementById('nomor_berita_acara').value = '';
                }

                // Status berkas BA yang sudah diunggah
                const lblBa = document.getElementById('lblCurrentBaFile');
                if (lblBa) {
                    if (s.file_berita_acara) {
                        lblBa.innerHTML = `<span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Berkas saat ini terunggah:</span> <a href="/sidang-tpp/${s.id}/download/berita_acara?preview=1" target="_blank" class="fw-bold text-decoration-none">Lihat BA (PDF)</a> <span class="text-muted">(Pilih file baru jika ingin mengganti)</span>`;
                    } else {
                        lblBa.innerHTML = `<span class="text-muted" style="font-size: 11px;">Scan PDF BA TPP lengkap dengan tanda tangan Ketua &amp; Anggota Tim (Maks. 15 MB)</span>`;
                    }
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

                    const isMutasi = (item.jenis_usulan || '').toLowerCase().includes('mutasi') || (item.jenis_usulan || '').toLowerCase().includes('penempatan');
                    const badgeJenis = isMutasi
                        ? `<span class="badge bg-info text-white"><i class="fas fa-exchange-alt me-1"></i>${item.jenis_usulan}</span>`
                        : `<span class="badge bg-primary">${item.jenis_usulan || 'PB'}</span>`;

                    tr.innerHTML = `
                        <td class="text-center text-muted">${idx + 1}</td>
                        <td><b>${item.nama_wbp || '-'}</b><br><small class="text-muted"><code>${item.nomor_register || '-'}</code></small></td>
                        <td><small>${item.perkara_pasal || '-'}</small><br><small class="text-muted">${item.lama_pidana || ''}</small></td>
                        <td class="text-center">${badgeJenis}</td>
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
