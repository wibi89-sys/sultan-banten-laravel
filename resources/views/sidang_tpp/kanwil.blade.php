@extends('layouts.app')

@section('title', 'Verifikasi Kesesuaian Dokumen - Sidang TPP')
@section('page_title')
    <i class="fas fa-stamp me-2 text-primary"></i>Sidang TPP &bull; Verifikasi Kesesuaian Dokumen (Kanwil)
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

    <!-- Alert Edukasi SE PAS-38.OT.02.02 Tahun 2026 -->
    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="fas fa-shield-alt fa-2x me-3 text-primary opacity-75"></i>
        <div>
            <h6 class="fw-bold mb-1">Penguatan Bintorwasdal Kantor Wilayah Banten (SE No: PAS-38.OT.02.02 Tahun 2026)</h6>
            <p class="small mb-0 text-muted">
                Divisi Pemasyarakatan Kantor Wilayah Banten melakukan <b>Verifikasi Kesesuaian Dokumen</b> (Sesuai / Tidak Sesuai / Perbaikan) terhadap usulan hak bersyarat UPT, serta <b>mengunggah Laporan Sidang TPP Kantor Wilayah yang telah ditandatangani Kepala Kantor Wilayah (Ka. Kanwil)</b> sebagai hasil akhir resmi yang diunduh UPT.
            </p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sidang-tpp.kanwil') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Filter UPT</label>
                    <select name="upt_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Seluruh UPT Se-Banten --</option>
                        @foreach($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Verifikasi Kesesuaian Dokumen</label>
                    <select name="status_verifikasi" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Status Kesesuaian --</option>
                        <option value="sesuai" {{ in_array($statusVerif, ['sesuai', 'terverifikasi']) ? 'selected' : '' }}>🟢 Sesuai</option>
                        <option value="perbaikan" {{ in_array($statusVerif, ['perbaikan', 'perlu_perbaikan']) ? 'selected' : '' }}>🟡 Perbaikan</option>
                        <option value="tidak_sesuai" {{ in_array($statusVerif, ['tidak_sesuai', 'ditolak']) ? 'selected' : '' }}>🔴 Tidak Sesuai</option>
                        <option value="menunggu_verifikasi" {{ $statusVerif == 'menunggu_verifikasi' ? 'selected' : '' }}>⏳ Menunggu Verifikasi</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-filter me-1"></i>Terapkan</button>
                    <a href="{{ route('sidang-tpp.kanwil') }}" class="btn btn-light btn-sm text-secondary border"><i class="fas fa-undo me-1"></i>Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Verifikasi Kanwil -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableKanwilSidang">
                <thead class="table-light small fw-bold text-secondary">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th>Jadwal Sidang</th>
                        <th>UPT Pelaksana</th>
                        <th>Berita Acara (BA) UPT</th>
                        <th class="text-center">Usulan Warga Binaan</th>
                        <th class="text-center">Verifikasi Kesesuaian Dokumen</th>
                        <th style="min-width: 200px;">Laporan Sidang TPP Kanwil (TTD Ka. Kanwil)</th>
                        <th style="width: 220px;" class="text-center">Aksi Kanwil</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidangList as $index => $s)
                    @php
                        $atensiUsulan = $s->usulan->where('perlu_perhatian', true);
                        $atensiCount = $atensiUsulan->count();
                        $atensiData = $atensiUsulan->map(function($u) {
                            return [
                                'nama' => $u->nama_wbp,
                                'register' => $u->nomor_register,
                                'jenis' => $u->jenis_usulan,
                                'catatan' => $u->catatan_perhatian ?: 'Perlu perhatian khusus verifikator Kanwil',
                            ];
                        })->values();

                        $wbpItems = $s->usulan->map(function($u) {
                            return [
                                'id' => $u->id,
                                'nama_wbp' => $u->nama_wbp,
                                'nomor_register' => $u->nomor_register,
                                'perkara_pasal' => $u->perkara_pasal ?: '-',
                                'lama_pidana' => $u->lama_pidana ?: '-',
                                'tanggal_ekspirasi' => $u->tanggal_ekspirasi ? $u->tanggal_ekspirasi->format('d/m/Y') : '-',
                                'tanggal_setengah_pidana' => $u->tanggal_setengah_pidana ? $u->tanggal_setengah_pidana->format('d/m/Y') : '-',
                                'tanggal_dua_pertiga' => $u->tanggal_dua_pertiga ? $u->tanggal_dua_pertiga->format('d/m/Y') : '-',
                                'jenis_usulan' => $u->jenis_usulan,
                                'skor_sppn' => $u->skor_sppn !== null ? number_format($u->skor_sppn, 2) : '-',
                                'status_litmas' => $u->status_litmas,
                                'bebas_register_f' => (bool) $u->bebas_register_f,
                                'perlu_perhatian' => (bool) $u->perlu_perhatian,
                                'catatan_perhatian' => $u->catatan_perhatian ?: '',
                                'keputusan' => $u->keputusan ?: 'belum_putus',
                                'alasan_keterangan' => $u->alasan_keterangan ?: '-',
                            ];
                        })->values();

                        $sidangPayload = [
                            'id' => $s->id,
                            'upt_nama' => $s->upt?->nama_upt ?? '-',
                            'nomor_undangan' => $s->nomor_undangan,
                            'tanggal_sidang' => $s->tanggal_sidang->format('d F Y'),
                            'jam_mulai' => substr((string)$s->jam_mulai, 0, 5),
                            'nomor_berita_acara' => $s->nomor_berita_acara ?: 'Belum Ada Berita Acara',
                            'agenda_sidang' => $s->agenda_sidang ?: ($s->agenda_deskripsi ?: 'Sidang TPP'),
                            'total_usulan' => $s->total_usulan,
                            'total_setuju' => $s->total_setuju,
                            'total_tunda' => $s->total_tunda,
                            'total_tidak_setuju' => $s->total_tidak_setuju,
                            'status_verifikasi_kanwil' => $s->status_verifikasi_kanwil,
                            'nomor_laporan_kanwil' => $s->nomor_laporan_kanwil ?? '',
                            'tanggal_ttd_kakanwil' => $s->tanggal_ttd_kakanwil ? $s->tanggal_ttd_kakanwil->format('Y-m-d') : '',
                            'nama_pengawas_kanwil' => $s->nama_pengawas_kanwil ?? '',
                            'kanwil_hadir' => $s->kanwil_hadir ? 1 : 0,
                            'catatan_bintorwasdal' => $s->catatan_bintorwasdal ?? '',
                            'atensi_list' => $atensiData,
                            'usulan' => $wbpItems,
                        ];
                    @endphp
                    <script type="application/json" id="sidang-data-{{ $s->id }}">{!! json_encode($sidangPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                    <tr class="{{ $atensiCount > 0 ? 'table-danger bg-opacity-10' : '' }}">
                        <td class="text-center text-muted small">{{ $sidangList->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold">{{ $s->tanggal_sidang->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ substr((string)$s->jam_mulai, 0, 5) }} WIB</small>
                            @if(!empty($s->link_daring))
                            <div>
                                <a href="{{ $s->link_daring }}" target="_blank" rel="noopener noreferrer" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none mt-1">
                                    <i class="fas fa-video me-1"></i>Link Zoom
                                </a>
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $s->upt?->nama_upt ?? '-' }}</div>
                            <small class="text-muted">Undangan: {{ $s->nomor_undangan }}</small>
                        </td>
                        <td>
                            @if(!empty($s->nomor_berita_acara))
                            <div class="small fw-semibold text-dark">{{ $s->nomor_berita_acara }}</div>
                            @if(!empty($s->file_berita_acara))
                            <a href="{{ route('sidang-tpp.download-file', ['id' => $s->id, 'type' => 'berita_acara']) }}" class="badge bg-light text-primary border text-decoration-none mt-1 d-inline-block">
                                <i class="fas fa-file-pdf text-danger me-1"></i>Unduh BA UPT
                            </a>
                            @endif
                            @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Belum Ada BA</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary px-3 py-1 shadow-sm rounded-pill fw-bold" onclick="openModalRincianWbp({{ $s->id }})" title="Klik untuk membuka pop-up rincian seluruh Warga Binaan &amp; hasil sidangnya">
                                <i class="fas fa-users me-1"></i>{{ $s->total_usulan }} WBP
                            </button>
                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                Setuju: <b class="text-success">{{ $s->total_setuju }}</b>
                                @if($s->total_tunda > 0)
                                    | Tunda: <b class="text-warning">{{ $s->total_tunda }}</b>
                                @endif
                                @if($s->total_tidak_setuju > 0)
                                    | Tolak: <b class="text-danger">{{ $s->total_tidak_setuju }}</b>
                                @endif
                            </div>
                            @if($atensiCount > 0)
                            <div class="mt-1">
                                <button type="button" class="badge bg-danger text-white border-0 fw-bold shadow-sm" style="font-size: 10px; cursor: pointer;" onclick="openModalRincianWbp({{ $s->id }}, 'atensi')" title="Terdapat {{ $atensiCount }} usulan yang ditandai menarik perhatian kanwil. Klik untuk membuka pop-up.">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $atensiCount }} MENARIK PERHATIAN
                                </button>
                            </div>
                            @endif
                        </td>
                        <td class="text-center">
                            {!! $s->verifikasi_badge !!}
                        </td>
                        <td>
                            @if(!empty($s->file_laporan_kakanwil))
                            <div class="d-flex align-items-center gap-1 text-success fw-bold small">
                                <i class="fas fa-check-circle"></i> Sudah Diunggah
                            </div>
                            <div class="small text-muted">No: <b>{{ $s->nomor_laporan_kanwil ?: '-' }}</b></div>
                            <div class="small text-muted">Tgl TTD: {{ $s->tanggal_ttd_kakanwil ? $s->tanggal_ttd_kakanwil->format('d/m/Y') : '-' }}</div>
                            <a href="{{ route('sidang-tpp.download-laporan-kanwil', $s->id) }}" class="btn btn-outline-success btn-sm py-0 px-2 mt-1 fw-semibold">
                                <i class="fas fa-file-pdf me-1"></i>Unduh Laporan
                            </a>
                            @else
                            <div class="badge bg-warning-subtle text-warning border border-warning-subtle p-2 text-wrap text-start">
                                <i class="fas fa-exclamation-circle me-1"></i>Belum Diunggah Kanwil
                            </div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(empty(auth()->user()->upt_id))
                            <div class="d-flex flex-column gap-1">
                                <button type="button" class="btn btn-sm btn-success shadow-sm fw-bold" onclick="openModalUploadLaporan({{ $s->id }}, '{{ addslashes($s->upt?->nama_upt) }}', '{{ addslashes($s->nomor_laporan_kanwil ?? '') }}', '{{ $s->tanggal_ttd_kakanwil?->format('Y-m-d') ?? date('Y-m-d') }}')">
                                    <i class="fas fa-file-upload me-1"></i>Upload Laporan Kanwil
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='openModalVerifikasi({{ $s->id }}, @json($s->upt?->nama_upt), @json($s->status_verifikasi_kanwil), @json($s->nomor_laporan_kanwil ?? ""), @json($s->tanggal_ttd_kakanwil?->format("Y-m-d") ?? ""), @json($s->nama_pengawas_kanwil ?? ""), {{ $s->kanwil_hadir ? 1 : 0 }}, @json($s->catatan_bintorwasdal ?? ""), @json($atensiData))'>
                                    <i class="fas fa-stamp me-1"></i>Verifikasi Dokumen
                                </button>
                                <button type="button" class="btn btn-sm btn-info text-white shadow-sm fw-semibold" onclick="openModalRincianWbp({{ $s->id }})" title="Lihat rincian berkas dan seluruh Warga Binaan beserta hasil sidangnya (Pop-up)">
                                    <i class="fas fa-id-card-alt me-1"></i>Lihat Rincian Warga Binaan
                                </button>
                            </div>
                            @else
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="openModalRincianWbp({{ $s->id }})" title="Lihat rincian Warga Binaan dan hasil sidang (Pop-up)">
                                <i class="fas fa-id-card-alt me-1"></i>Lihat Rincian Warga Binaan
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-stamp fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6>Tidak Ada Pengajuan Sidang yang Memerlukan Verifikasi Kanwil</h6>
                            <p class="small mb-0">Belum ada pengajuan baru dari UPT Pemasyarakatan se-Banten.</p>
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

<!-- MODAL 1: Upload Laporan Sidang TPP Kantor Wilayah (TTD Ka. Kanwil) -->
<div class="modal fade" id="modalUploadLaporanKanwil" tabindex="-1" aria-labelledby="modalUploadLaporanTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formUploadLaporanKanwil" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalUploadLaporanTitle">
                        <i class="fas fa-file-signature me-2"></i>Upload Laporan Sidang TPP Kantor Wilayah (TTD Ka. Kanwil)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-3">
                        <i class="fas fa-check-double fa-2x me-3 text-success"></i>
                        <div>
                            <b>Dokumen Hasil Akhir untuk UPT</b><br>
                            UPT: <b id="ul_upt_nama" class="text-dark">-</b>. Berkas Laporan Sidang TPP Kantor Wilayah yang diunggah di sini <b>akan diunduh oleh UPT pelaksana sebagai dasar resmi usulan hak bersyarat (PB/CB/CMB/Asimilasi) ke SDP Ditjenpas</b>.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="ul_nomor_laporan_kanwil" class="form-label small fw-semibold">Nomor Laporan / Surat Kanwil <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_laporan_kanwil" id="ul_nomor_laporan_kanwil" class="form-control" placeholder="Contoh: W12.PK.02.02-895/2026" required>
                        </div>

                        <div class="col-md-6">
                            <label for="ul_tanggal_ttd_kakanwil" class="form-label small fw-semibold">Tanggal Tanda Tangan Ka. Kanwil <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_ttd_kakanwil" id="ul_tanggal_ttd_kakanwil" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-12">
                            <label for="ul_file_laporan_kakanwil" class="form-label small fw-semibold">Pilih Berkas PDF Laporan Sidang TPP Ber-TTD Ka. Kanwil <span class="text-danger">*</span></label>
                            <input type="file" name="file_laporan_kakanwil" id="ul_file_laporan_kakanwil" class="form-control" accept=".pdf" required>
                            <small class="text-muted" style="font-size: 11px;">Format berkas: PDF scan resmi lengkap tanda tangan Kepala Kantor Wilayah Banten (Maks. 20 MB)</small>
                        </div>

                        <div class="col-md-12">
                            <label for="ul_catatan_bintorwasdal" class="form-label small fw-semibold">Catatan / Arahan Bintorwasdal Kanwil untuk UPT (Opsional)</label>
                            <textarea name="catatan_bintorwasdal" id="ul_catatan_bintorwasdal" rows="3" class="form-control" placeholder="Tambahkan catatan tindak lanjut atau rekomendasi untuk UPT..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Unggah &amp; Terbitkan Laporan untuk UPT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Verifikasi Kesesuaian Dokumen Kanwil -->
<div class="modal fade" id="modalVerifikasiKanwil" tabindex="-1" aria-labelledby="modalVerifTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formVerifikasiKanwil" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalVerifTitle">
                        <i class="fas fa-stamp me-2"></i>Verifikasi Kesesuaian Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle fa-lg me-2"></i>
                        <div>
                            UPT: <b id="v_upt_nama">-</b>. Verifikasi kesesuaian administrasi dan kelayakan syarat substantif usulan hak bersyarat Warga Binaan.
                        </div>
                    </div>

                    <!-- Tombol Buka Pop-up Rincian Warga Binaan & Putusannya -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-info btn-sm w-100 fw-bold shadow-sm d-flex justify-content-between align-items-center py-2 px-3" onclick="openRincianFromVerif()">
                            <span><i class="fas fa-id-card-alt text-primary me-2"></i>Lihat Rincian Warga Binaan &amp; Hasil Putusan Sidang Tiap Orang (Pop-up)</span>
                            <span class="badge bg-info text-white"><i class="fas fa-window-restore me-1"></i>Buka Pop-up</span>
                        </button>
                    </div>

                    <!-- ATENSI KHUSUS VERIFIKATOR KANWIL (LABEL MERAH) -->
                    <div id="v_atensi_box" class="alert alert-danger border-0 shadow-sm d-none mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-exclamation-triangle fa-lg me-2 text-danger"></i>
                            <h6 class="fw-bold mb-0 text-danger">PERHATIAN VERIFIKATOR KANWIL</h6>
                        </div>
                        <p class="small text-dark mb-2">UPT menandai usulan Warga Binaan berikut untuk <b>mendapatkan perhatian khusus</b> saat pemeriksaan kesesuaian dokumen:</p>
                        <div id="v_atensi_items" class="d-flex flex-column gap-2"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="status_verifikasi_kanwil" class="form-label small fw-semibold">Verifikasi Kesesuaian Dokumen <span class="text-danger">*</span></label>
                            <select name="status_verifikasi_kanwil" id="status_verifikasi_kanwil" class="form-select" required>
                                <option value="sesuai" class="text-success fw-bold">🟢 Sesuai</option>
                                <option value="tidak_sesuai" class="text-danger fw-bold">🔴 Tidak Sesuai</option>
                                <option value="perbaikan" class="text-warning fw-bold">🟡 Perbaikan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold d-block">Pengawasan Sidang</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="kanwil_hadir" id="kanwil_hadir" value="1">
                                <label class="form-check-label small" for="kanwil_hadir">
                                    Perwakilan Kanwil Hadir (Fisik / Virtual Zoom)
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label for="nama_pengawas_kanwil" class="form-label small fw-semibold">Nama Pejabat / Pengawas Pembina Kanwil</label>
                            <input type="text" name="nama_pengawas_kanwil" id="nama_pengawas_kanwil" class="form-control" placeholder="Contoh: Tim Bintorwasdal Divisi Pemasyarakatan Banten">
                        </div>

                        <div class="col-md-12">
                            <label for="catatan_bintorwasdal" class="form-label small fw-semibold">Catatan Kesesuaian / Rekomendasi Kanwil</label>
                            <textarea name="catatan_bintorwasdal" id="catatan_bintorwasdal" rows="3" class="form-control" placeholder="Tuliskan catatan pemeriksaan syarat substantif/administratif atau hal yang perlu perbaikan jika ada..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSimpanVerifikasi">
                        <i class="fas fa-check-circle me-1"></i>Simpan Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: Pop-up Rincian Warga Binaan & Hasil Putusan Sidang TPP -->
<div class="modal fade" id="modalRincianWbpKanwil" tabindex="-1" aria-labelledby="modalRincianWbpTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="modalRincianWbpTitle">
                        <i class="fas fa-id-card-alt text-info me-2"></i>Rincian Warga Binaan &amp; Hasil Putusan Sidang TPP
                    </h5>
                    <div class="small text-white-50 mt-1" id="rw_header_subtitle">
                        UPT: <b id="rw_upt_nama" class="text-white">-</b> &bull; Sidang: <span id="rw_tgl_sidang">-</span> &bull; No. BA: <span id="rw_no_ba">-</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <!-- Banner Agenda & Ringkasan Hasil Sidang -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">Agenda Sidang TPP</span>
                                <span class="fw-semibold text-dark small" id="rw_agenda_sidang">-</span>
                            </div>
                            <div class="small text-muted" id="rw_undangan_info">
                                No. Undangan: -
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center pt-1">
                            <span class="small fw-bold text-secondary me-1">Ringkasan Putusan:</span>
                            <span class="badge bg-primary fs-6 px-3 py-1.5" id="rw_stat_total">0 Usulan</span>
                            <span class="badge bg-success fs-6 px-3 py-1.5" id="rw_stat_setuju"><i class="fas fa-check-circle me-1"></i>0 Setuju</span>
                            <span class="badge bg-warning text-dark fs-6 px-3 py-1.5" id="rw_stat_tunda"><i class="fas fa-pause-circle me-1"></i>0 Tunda</span>
                            <span class="badge bg-danger fs-6 px-3 py-1.5" id="rw_stat_tolak"><i class="fas fa-times-circle me-1"></i>0 Ditolak</span>
                            <span class="badge bg-secondary fs-6 px-3 py-1.5" id="rw_stat_belum"><i class="fas fa-question-circle me-1"></i>0 Belum Putus</span>
                            <span class="badge bg-danger text-white fs-6 px-3 py-1.5 d-none" id="rw_stat_atensi"><i class="fas fa-exclamation-triangle me-1"></i><span id="rw_atensi_count">0</span> Perlu Atensi</span>
                        </div>
                    </div>
                </div>

                <!-- Toolbar Pencarian & Filter Keputusan Realtime -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-7">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="rw_search_input" class="form-control border-start-0" placeholder="Ketik nama Warga Binaan, no. register, perkara / pasal..." oninput="filterRincianTable()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('rw_search_input').value=''; filterRincianTable();" title="Reset pencarian">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <select id="rw_filter_keputusan" class="form-select form-select-sm" onchange="filterRincianTable()">
                                    <option value="all">-- Semua Hasil Putusan (Semua WBP) --</option>
                                    <option value="setuju">🟢 Putusan: Setuju / Disetujui</option>
                                    <option value="tunda">🟡 Putusan: Ditunda / Tunda</option>
                                    <option value="tidak_setuju">🔴 Putusan: Tidak Setuju / Ditolak</option>
                                    <option value="belum_putus">⚪ Putusan: Belum Putus</option>
                                    <option value="atensi">⚠️ Khusus Menarik Perhatian Kanwil</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Rincian Warga Binaan & Hasil Putusan Sidang -->
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive" style="max-height: 480px;">
                        <table class="table table-hover align-middle mb-0" id="rw_table_wbp">
                            <thead class="table-light sticky-top small fw-bold text-secondary">
                                <tr>
                                    <th style="width: 40px;" class="text-center">No</th>
                                    <th style="min-width: 220px;">Warga Binaan &amp; Identitas</th>
                                    <th style="min-width: 150px;">Usulan &amp; Syarat</th>
                                    <th style="min-width: 150px;" class="text-center">Atensi Kanwil</th>
                                    <th style="min-width: 150px;" class="text-center">Hasil Putusan Sidang</th>
                                    <th style="min-width: 260px;">Rincian / Alasan Putusan Sidang UPT</th>
                                </tr>
                            </thead>
                            <tbody id="rw_table_body">
                                <!-- Populated dynamically by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-white d-flex justify-content-between align-items-center">
                <div class="small text-muted" id="rw_count_info">
                    Menampilkan 0 Warga Binaan
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                    @if(empty(auth()->user()->upt_id))
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" id="rw_btn_ke_verifikasi" onclick="lanjutVerifikasiDariRincian()">
                        <i class="fas fa-stamp me-1"></i>Lanjut Verifikasi Dokumen
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const modalVerif = new bootstrap.Modal(document.getElementById('modalVerifikasiKanwil'));
    const formVerif = document.getElementById('formVerifikasiKanwil');
    const modalUploadLaporan = new bootstrap.Modal(document.getElementById('modalUploadLaporanKanwil'));
    const formUploadLaporan = document.getElementById('formUploadLaporanKanwil');
    const modalRincianWbp = new bootstrap.Modal(document.getElementById('modalRincianWbpKanwil'));

    let currentSidangRincian = null;
    let currentSidangIdVerif = null;

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openModalUploadLaporan(id, uptNama, noLaporan, tglTtd) {
        formUploadLaporan.action = '/sidang-tpp/' + id + '/upload-laporan-kanwil';
        document.getElementById('ul_upt_nama').innerText = uptNama;
        document.getElementById('ul_nomor_laporan_kanwil').value = noLaporan;
        if (tglTtd) {
            document.getElementById('ul_tanggal_ttd_kakanwil').value = tglTtd;
        }
        modalUploadLaporan.show();
    }

    function openModalVerifikasi(id, uptNama, statusVerif, noLaporan, tglTtd, pengawas, kanwilHadir, catatan, atensiList) {
        currentSidangIdVerif = id;
        formVerif.action = '/sidang-tpp/' + id + '/kanwil-verifikasi';
        document.getElementById('v_upt_nama').innerText = uptNama;
        let vStatus = statusVerif || 'sesuai';
        if (vStatus === 'terverifikasi') vStatus = 'sesuai';
        if (vStatus === 'ditolak') vStatus = 'tidak_sesuai';
        if (vStatus === 'perlu_perbaikan') vStatus = 'perbaikan';
        if (vStatus === 'menunggu_verifikasi' || vStatus === 'menunggu_sidang') vStatus = 'sesuai';
        document.getElementById('status_verifikasi_kanwil').value = vStatus;
        document.getElementById('nama_pengawas_kanwil').value = pengawas || '';
        document.getElementById('kanwil_hadir').checked = (kanwilHadir === 1);
        document.getElementById('catatan_bintorwasdal').value = catatan || '';

        // Render atensi Kanwil
        const atensiBox = document.getElementById('v_atensi_box');
        const atensiItems = document.getElementById('v_atensi_items');
        atensiItems.innerHTML = '';
        if (atensiList && atensiList.length > 0) {
            atensiBox.classList.remove('d-none');
            atensiList.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'p-2 bg-white rounded border border-danger-subtle shadow-sm small';
                itemDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-danger text-white fw-bold"><i class="fas fa-exclamation-circle me-1"></i>MENARIK PERHATIAN</span>
                        <span class="badge bg-primary">${item.jenis}</span>
                    </div>
                    <div class="fw-bold text-dark">${item.nama} <code class="text-secondary">(${item.register})</code></div>
                    <div class="text-danger fw-semibold mt-1"><i class="fas fa-angle-right me-1"></i>Catatan: ${item.catatan}</div>
                `;
                atensiItems.appendChild(itemDiv);
            });
        } else {
            atensiBox.classList.add('d-none');
        }

        modalVerif.show();
    }

    function openRincianFromVerif() {
        if (!currentSidangIdVerif) return;
        modalVerif.hide();
        openModalRincianWbp(currentSidangIdVerif);
    }

    function lanjutVerifikasiDariRincian() {
        if (!currentSidangRincian) return;
        const s = currentSidangRincian;
        modalRincianWbp.hide();
        openModalVerifikasi(
            s.id,
            s.upt_nama,
            s.status_verifikasi_kanwil,
            s.nomor_laporan_kanwil,
            s.tanggal_ttd_kakanwil,
            s.nama_pengawas_kanwil,
            s.kanwil_hadir,
            s.catatan_bintorwasdal,
            s.atensi_list
        );
    }

    function openModalRincianWbp(id, filterType) {
        const el = document.getElementById('sidang-data-' + id);
        if (!el) return;

        try {
            currentSidangRincian = JSON.parse(el.textContent);
        } catch (e) {
            console.error('Error parsing sidang payload', e);
            return;
        }

        const s = currentSidangRincian;
        currentSidangIdVerif = s.id;

        document.getElementById('rw_upt_nama').innerText = s.upt_nama;
        document.getElementById('rw_tgl_sidang').innerText = s.tanggal_sidang + ' (' + s.jam_mulai + ' WIB)';
        document.getElementById('rw_no_ba').innerText = s.nomor_berita_acara;
        document.getElementById('rw_agenda_sidang').innerText = s.agenda_sidang;
        document.getElementById('rw_undangan_info').innerText = 'No. Undangan: ' + s.nomor_undangan;

        // Stat Badges
        document.getElementById('rw_stat_total').innerText = s.total_usulan + ' Usulan';
        document.getElementById('rw_stat_setuju').innerHTML = '<i class="fas fa-check-circle me-1"></i>' + s.total_setuju + ' Setuju';
        document.getElementById('rw_stat_tunda').innerHTML = '<i class="fas fa-pause-circle me-1"></i>' + s.total_tunda + ' Tunda';
        document.getElementById('rw_stat_tolak').innerHTML = '<i class="fas fa-times-circle me-1"></i>' + s.total_tidak_setuju + ' Ditolak';

        // Belum putus
        const usulanArr = s.usulan || [];
        const belumPutusCount = usulanArr.filter(u => !['setuju', 'disetujui', 'tunda', 'ditunda', 'tidak_setuju', 'ditolak'].includes((u.keputusan || '').toLowerCase())).length;
        document.getElementById('rw_stat_belum').innerHTML = '<i class="fas fa-question-circle me-1"></i>' + belumPutusCount + ' Belum Putus';

        // Atensi
        const atensiBadge = document.getElementById('rw_stat_atensi');
        const atensiCount = usulanArr.filter(u => Boolean(u.perlu_perhatian)).length;
        if (atensiCount > 0) {
            atensiBadge.classList.remove('d-none');
            document.getElementById('rw_atensi_count').innerText = atensiCount;
        } else {
            atensiBadge.classList.add('d-none');
        }

        // Reset filter & search
        document.getElementById('rw_search_input').value = '';
        const selFilter = document.getElementById('rw_filter_keputusan');
        selFilter.value = (filterType === 'atensi') ? 'atensi' : 'all';

        filterRincianTable();
        modalRincianWbp.show();
    }

    function filterRincianTable() {
        if (!currentSidangRincian || !currentSidangRincian.usulan) return;

        const query = (document.getElementById('rw_search_input').value || '').toLowerCase().trim();
        const filterKeputusan = document.getElementById('rw_filter_keputusan').value;

        const filtered = currentSidangRincian.usulan.filter(u => {
            // Search query
            if (query) {
                const matchName = (u.nama_wbp || '').toLowerCase().includes(query);
                const matchReg = (u.nomor_register || '').toLowerCase().includes(query);
                const matchPasal = (u.perkara_pasal || '').toLowerCase().includes(query);
                const matchJenis = (u.jenis_usulan || '').toLowerCase().includes(query);
                const matchAlasan = (u.alasan_keterangan || '').toLowerCase().includes(query);
                if (!matchName && !matchReg && !matchPasal && !matchJenis && !matchAlasan) {
                    return false;
                }
            }

            // Keputusan filter
            const kep = (u.keputusan || '').toLowerCase();
            if (filterKeputusan === 'setuju') {
                return kep === 'setuju' || kep === 'disetujui';
            } else if (filterKeputusan === 'tunda') {
                return kep === 'tunda' || kep === 'ditunda';
            } else if (filterKeputusan === 'tidak_setuju') {
                return kep === 'tidak_setuju' || kep === 'ditolak';
            } else if (filterKeputusan === 'belum_putus') {
                return !['setuju', 'disetujui', 'tunda', 'ditunda', 'tidak_setuju', 'ditolak'].includes(kep);
            } else if (filterKeputusan === 'atensi') {
                return Boolean(u.perlu_perhatian);
            }

            return true;
        });

        renderRincianWbpTable(filtered);
    }

    function renderRincianWbpTable(items) {
        const tbody = document.getElementById('rw_table_body');
        tbody.innerHTML = '';

        const countInfo = document.getElementById('rw_count_info');
        const totalAll = (currentSidangRincian.usulan || []).length;
        countInfo.innerHTML = `Menampilkan <b>${items.length}</b> dari <b>${totalAll}</b> Warga Binaan`;

        if (!items || items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                        <div>Tidak ada data Warga Binaan yang cocok dengan filter atau kata kunci pencarian.</div>
                    </td>
                </tr>
            `;
            return;
        }

        items.forEach((u, index) => {
            const tr = document.createElement('tr');
            if (u.perlu_perhatian) {
                tr.className = 'table-danger bg-opacity-25';
            }

            // Keputusan badge
            const kep = (u.keputusan || '').toLowerCase();
            let kepBadge = '';
            if (kep === 'setuju' || kep === 'disetujui') {
                kepBadge = '<span class="badge bg-success text-white py-1.5 px-2.5 fw-bold shadow-sm"><i class="fas fa-check-circle me-1"></i>Setuju / Disetujui</span>';
            } else if (kep === 'tunda' || kep === 'ditunda') {
                kepBadge = '<span class="badge bg-warning text-dark py-1.5 px-2.5 fw-bold shadow-sm"><i class="fas fa-pause-circle me-1"></i>Ditunda / Tunda</span>';
            } else if (kep === 'tidak_setuju' || kep === 'ditolak') {
                kepBadge = '<span class="badge bg-danger text-white py-1.5 px-2.5 fw-bold shadow-sm"><i class="fas fa-times-circle me-1"></i>Tidak Setuju / Ditolak</span>';
            } else {
                kepBadge = '<span class="badge bg-secondary text-white py-1.5 px-2.5"><i class="fas fa-question-circle me-1"></i>Belum Putus</span>';
            }

            // Atensi badge
            let atensiHtml = '';
            if (u.perlu_perhatian) {
                atensiHtml = `
                    <span class="badge bg-danger text-white fw-bold shadow-sm" style="font-size: 11px;">
                        <i class="fas fa-exclamation-circle me-1"></i>MENARIK PERHATIAN
                    </span>
                    ${u.catatan_perhatian ? `<div class="small text-danger fw-semibold mt-1" style="font-size: 11px;"><i class="fas fa-angle-right me-1"></i>${escapeHtml(u.catatan_perhatian)}</div>` : ''}
                `;
            } else {
                atensiHtml = '<span class="badge bg-light text-muted border">Normal</span>';
            }

            // Litmas badge
            let litmasBadge = '';
            if (u.status_litmas === 'memenuhi_syarat') {
                litmasBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Litmas: Memenuhi</span>';
            } else if (u.status_litmas === 'tidak_memenuhi_syarat') {
                litmasBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10px;">Litmas: Tidak Memenuhi</span>';
            } else if (u.status_litmas === 'proses') {
                litmasBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 10px;">Litmas: Proses</span>';
            } else {
                litmasBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 10px;">Litmas: -</span>';
            }

            // Register F
            const regFBadge = u.bebas_register_f 
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;"><i class="fas fa-check me-1"></i>Bebas Reg. F</span>'
                : '<span class="badge bg-danger text-white" style="font-size: 10px;"><i class="fas fa-ban me-1"></i>Catatan Reg. F Aktif</span>';

            // Keterangan / alasan putusan
            const alasanText = (u.alasan_keterangan && u.alasan_keterangan !== '-')
                ? escapeHtml(u.alasan_keterangan)
                : '<span class="text-muted fst-italic">Belum ada catatan alasan putusan UPT</span>';

            tr.innerHTML = `
                <td class="text-center text-muted fw-semibold">${index + 1}</td>
                <td>
                    <div class="fw-bold text-dark fs-6">${escapeHtml(u.nama_wbp)}</div>
                    <div class="small mt-1"><code class="text-primary bg-white px-1.5 py-0.5 rounded border border-secondary-subtle">${escapeHtml(u.nomor_register)}</code></div>
                    <div class="small text-muted mt-1"><i class="fas fa-balance-scale me-1 text-secondary"></i><b>${escapeHtml(u.perkara_pasal)}</b> &bull; Pidana: ${escapeHtml(u.lama_pidana)}</div>
                    <div class="small text-muted mt-0.5" style="font-size: 11px;">
                        <span class="me-2"><i class="far fa-calendar-check me-1 text-primary"></i>2/3 Pidana: <b>${escapeHtml(u.tanggal_dua_pertiga)}</b></span>
                        <span><i class="far fa-calendar-times me-1 text-danger"></i>Ekspirasi: ${escapeHtml(u.tanggal_ekspirasi)}</span>
                    </div>
                </td>
                <td>
                    <span class="badge bg-primary px-2 py-1 fs-6">${escapeHtml(u.jenis_usulan)}</span>
                    <div class="small mt-1 text-muted">SPPN: <b class="text-dark">${escapeHtml(u.skor_sppn)}</b></div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        ${litmasBadge}
                        ${regFBadge}
                    </div>
                </td>
                <td class="text-center">
                    ${atensiHtml}
                </td>
                <td class="text-center">
                    ${kepBadge}
                </td>
                <td>
                    <div class="p-2 bg-white rounded border border-light-subtle shadow-sm small text-dark">
                        <div class="fw-semibold text-secondary mb-1" style="font-size: 11px;"><i class="fas fa-file-alt me-1 text-primary"></i>Catatan / Alasan Putusan UPT:</div>
                        <div>${alasanText}</div>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
</script>
@endpush
