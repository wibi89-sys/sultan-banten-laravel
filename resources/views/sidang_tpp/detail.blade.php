@extends('layouts.app')

@section('title', 'Detail Sidang TPP - Sultan Banten')
@section('page_title')
    <i class="fas fa-gavel me-2 text-primary"></i>Detail Sidang TPP &bull; {{ $sidang->upt?->nama_upt }}
@endsection

@push('styles')
<style>
    .photo-card {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .photo-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    .photo-thumb {
        width: 100%;
        height: 180px;
        object-fit: cover;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('sidang-tpp.index') }}" class="text-decoration-none">Sidang TPP</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail Sidang #{{ $sidang->id }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">Sidang TPP: {{ $sidang->upt?->nama_upt }} ({{ $sidang->tanggal_sidang->translatedFormat('d F Y') }})</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sidang-tpp.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
            @if($sidang->hasLaporanKanwil())
            <a href="{{ route('sidang-tpp.download-laporan-kanwil', $sidang->id) }}" class="btn btn-success btn-sm shadow-sm fw-bold">
                <i class="fas fa-download me-1"></i>Download Laporan Sidang TPP Kanwil
            </a>
            @endif
        </div>
    </div>

    <!-- BANNER KHUSUS: Laporan Sidang TPP Kantor Wilayah (Hasil Akhir untuk Usulan Hak Bersyarat) -->
    @if($sidang->hasLaporanKanwil())
    <div class="card border-0 text-white shadow mb-4 p-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; border-radius: 12px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-file-signature fa-3x text-white-50"></i>
                <div>
                    <span class="badge bg-white text-success fw-bold px-2 py-1 mb-1">DOKUMEN HASIL AKHIR TERSEDIA</span>
                    <h5 class="fw-bold mb-1">Laporan Sidang TPP Kantor Wilayah (TTD Ka. Kanwil)</h5>
                    <div class="small text-white-50">
                        Nomor Surat Kanwil: <b class="text-white">{{ $sidang->nomor_laporan_kanwil }}</b> &bull; Tanggal Pengesahan: <b class="text-white">{{ $sidang->tanggal_ttd_kakanwil ? $sidang->tanggal_ttd_kakanwil->format('d/m/Y') : '-' }}</b>
                    </div>
                    <div class="small mt-1 text-white opacity-90">
                        Dokumen resmi ini merupakan hasil akhir yang diunduh UPT sebagai dasar pengusulan hak bersyarat (PB/CB/CMB/Asimilasi/Remisi) pada SDP Ditjenpas.
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('sidang-tpp.download-laporan-kanwil', $sidang->id) }}" class="btn btn-light btn-lg px-4 text-success fw-bold shadow">
                    <i class="fas fa-download me-2"></i>Download Laporan Kanwil (PDF)
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="card border-0 bg-light border-start border-4 border-warning shadow-sm mb-4 p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-hourglass-half fa-2x text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Laporan Sidang TPP Kantor Wilayah Belum Diunggah</h6>
                    <p class="small text-muted mb-0">Menunggu Kantor Wilayah memverifikasi dan mengunggah dokumen Laporan Hasil Sidang bertanda tangan Ka. Kanwil untuk diunduh UPT.</p>
                </div>
            </div>
            @if(auth()->user()->isAdmin() || !auth()->user()->upt_id)
            <a href="{{ route('sidang-tpp.kanwil') }}" class="btn btn-success btn-sm px-3 fw-bold">
                <i class="fas fa-file-upload me-1"></i>Buka Menu Verifikasi &amp; Upload Laporan Kanwil
            </a>
            @endif
        </div>
    </div>
    @endif

    <!-- Status Banner Ringkasan -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block">Status Pelaksanaan</small>
                    <div class="mt-1">{!! $sidang->status_badge !!}</div>
                </div>
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block">Verifikasi Kesesuaian Dokumen</small>
                    <div class="mt-1">{!! $sidang->verifikasi_badge !!}</div>
                </div>
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block">Total Usulan Warga Binaan</small>
                    <div class="fw-bold fs-5 text-dark mt-1">
                        {{ $sidang->total_usulan }} <span class="fs-6 text-muted font-normal">Orang</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Rasio Keputusan</small>
                    <div class="small fw-semibold mt-1">
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $sidang->total_setuju }} Setuju</span> &bull; 
                        <span class="text-warning"><i class="fas fa-pause-circle me-1"></i>{{ $sidang->total_tunda }} Tunda</span> &bull; 
                        <span class="text-danger"><i class="fas fa-times-circle me-1"></i>{{ $sidang->total_tidak_setuju }} Tidak Setuju</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Detail Grid -->
    <div class="row g-4 mb-4">
        <!-- Kolom Kiri: Undangan & Susunan Tim -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-envelope-open-text me-2"></i>1. Surat Undangan Ke Kantor Wilayah</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 170px;">Nomor Undangan</td>
                            <td class="fw-bold text-dark">: {{ $sidang->nomor_undangan }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Surat</td>
                            <td>: {{ $sidang->tanggal_surat->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jadwal Pelaksanaan</td>
                            <td>: {{ $sidang->tanggal_sidang->format('d/m/Y') }} (Pukul {{ substr((string)$sidang->jam_mulai, 0, 5) }} WIB)</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Agenda Sidang</td>
                            <td class="fw-semibold text-dark">: {{ $sidang->agenda_sidang ?? $sidang->agenda_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ketua &amp; Sekretaris TPP</td>
                            <td>: {{ $sidang->ketua_tpp }} &amp; {{ $sidang->sekretaris_tpp }}</td>
                        </tr>
                        @if(!empty($sidang->nama_pk_bapas))
                        <tr>
                            <td class="text-muted">PK Bapas Pendamping</td>
                            <td>: {{ $sidang->nama_pk_bapas }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Berkas Undangan (PDF)</td>
                            <td>: 
                                @if(!empty($sidang->file_undangan))
                                <a href="{{ route('sidang-tpp.download-file', ['id' => $sidang->id, 'type' => 'undangan']) }}" class="btn btn-outline-primary btn-sm py-0 px-2">
                                    <i class="fas fa-file-pdf text-danger me-1"></i>Unduh Surat Undangan
                                </a>
                                @else
                                <span class="text-muted small">Tidak ada lampiran PDF</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Fasilitasi Daring & Bintorwasdal Kanwil -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-video me-2"></i>2. Fasilitasi Sidang Daring &amp; Bintorwasdal Kanwil</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 170px;">Metode Sidang</td>
                            <td class="fw-bold">: {{ $sidang->metode_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tautan / Link Daring</td>
                            <td>: 
                                @if(!empty($sidang->link_daring))
                                <a href="{{ $sidang->link_daring }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm py-0 px-2 fw-semibold">
                                    <i class="fas fa-video me-1"></i>Buka Ruang Zoom / Meet
                                </a>
                                @else
                                <span class="text-muted small">Tatap Muka di Lapas/Rutan</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Meeting ID / Passcode</td>
                            <td>: {{ $sidang->meeting_id ?: '-' }} / {{ $sidang->passcode ?: '-' }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted pt-2">Pengawas Kanwil Hadir</td>
                            <td class="pt-2">: {{ $sidang->kanwil_hadir ? 'Ya (Hadir/Memantau)' : 'Belum/Tidak Hadir Langsung' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama Pengawas Kanwil</td>
                            <td>: {{ $sidang->nama_pengawas_kanwil ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Catatan Bintorwasdal</td>
                            <td>: <em>{{ $sidang->catatan_bintorwasdal ?: 'Belum ada catatan dari pembina Kanwil.' }}</em></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nomor Berita Acara (BA) UPT</td>
                            <td>: 
                                @if(!empty($sidang->nomor_berita_acara))
                                <b>{{ $sidang->nomor_berita_acara }}</b>
                                @if(!empty($sidang->file_berita_acara))
                                <a href="{{ route('sidang-tpp.download-file', ['id' => $sidang->id, 'type' => 'berita_acara']) }}" class="badge bg-light text-primary border text-decoration-none ms-1">
                                    <i class="fas fa-file-pdf text-danger me-1"></i>Unduh BA
                                </a>
                                @endif
                                @else
                                <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DOKUMENTASI FOTO SIDANG TPP (KOMPRESI JPG <= 500 KB) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-camera text-primary me-2"></i>Foto Dokumentasi Sidang TPP (Format JPG &bull; Maks. 500 KB / Foto)</h6>
                <small class="text-muted">Foto kegiatan sidang yang telah dikompresi otomatis oleh sistem agar hemat kapasitas penyimpanan.</small>
            </div>
            <span class="badge bg-light text-dark border">{{ count($sidang->dokumentasi_list) }} Foto Dokumentasi</span>
        </div>
        <div class="card-body p-3">
            @if(count($sidang->dokumentasi_list) > 0)
            <div class="row g-3">
                @foreach($sidang->dokumentasi_list as $idx => $fotoPath)
                @php
                    $fullPath = public_path($fotoPath);
                    $exists = file_exists($fullPath);
                    $fileKb = $exists ? round(filesize($fullPath) / 1024, 1) : 0;
                @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="photo-card shadow-sm">
                        @if($exists)
                        <a href="{{ asset($fotoPath) }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset($fotoPath) }}" alt="Dokumentasi Sidang {{ $idx + 1 }}" class="photo-thumb" loading="lazy">
                        </a>
                        <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Foto {{ $idx + 1 }} (.JPG)</span>
                            <span class="small fw-semibold {{ $fileKb <= 500 ? 'text-success' : 'text-danger' }}" style="font-size: 11px;">
                                <i class="fas fa-weight-hanging me-1"></i>{{ $fileKb }} KB
                            </span>
                        </div>
                        @else
                        <div class="photo-thumb d-flex align-items-center justify-content-center bg-light text-muted">
                            <small>Berkas tidak ditemukan</small>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-camera fa-2x mb-2 text-secondary opacity-50"></i>
                <p class="small mb-0">Belum ada foto dokumentasi yang diunggah untuk sidang ini.</p>
                <small class="text-muted">UPT dapat mengunggah foto melalui menu <b>2. Hasil Sidang UPT</b>.</small>
            </div>
            @endif
        </div>
    </div>

    <!-- Tabel Daftar Usulan Warga Binaan -->
    @php
        $currentUser = auth()->user();
        $canManagePeserta = !empty($currentUser->upt_id) && ((int) $currentUser->upt_id === (int) $sidang->upt_id) && $sidang->status_sidang !== 'selesai_ttd_kakanwil';
    @endphp

    <!-- Tabel Daftar Usulan Warga Binaan -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users text-primary me-2"></i>Daftar Warga Binaan Pemasyarakatan yang Disidangkan ({{ $sidang->usulan->count() }} Orang)</h6>
            <div>
                @if($canManagePeserta)
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" onclick="bukaModalTambahPesertaDetail()">
                    <i class="fas fa-user-plus me-1"></i>Tambah Peserta Sidang
                </button>
                @elseif(empty($currentUser->upt_id))
                <span class="badge bg-light text-secondary border py-1 px-2 small">
                    <i class="fas fa-shield-alt text-primary me-1"></i>Akses Kanwil: Pengawas &amp; Verifikator (Read Only)
                </span>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light small fw-bold text-secondary">
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th>Nama Warga Binaan</th>
                        <th>No. Register</th>
                        <th>Tindak Pidana / Pasal</th>
                        <th>Pidana</th>
                        <th class="text-center">Tgl 2/3</th>
                        <th class="text-center">Jenis Usulan</th>
                        <th class="text-center" style="width: 170px;">Menarik Perhatian</th>
                        <th class="text-center">Hasil Keputusan</th>
                        <th>Catatan / Alasan</th>
                        @if($canManagePeserta)
                        <th style="width: 100px;" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($sidang->usulan as $idx => $u)
                    <tr class="{{ $u->perlu_perhatian ? 'table-danger bg-opacity-25' : '' }}">
                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                        <td class="fw-bold text-dark">
                            {{ $u->nama_wbp }}
                            @if($u->perlu_perhatian)
                                <span class="badge bg-danger text-white ms-1" title="Atensi Kanwil (Menarik Perhatian)"><i class="fas fa-exclamation-triangle"></i></span>
                            @endif
                        </td>
                        <td><code>{{ $u->nomor_register }}</code></td>
                        <td>{{ $u->perkara_pasal }}</td>
                        <td>{{ $u->lama_pidana }}</td>
                        <td class="text-center">{{ $u->tanggal_dua_pertiga ? $u->tanggal_dua_pertiga->format('d/m/Y') : '-' }}</td>
                        <td class="text-center"><span class="badge bg-primary">{{ $u->jenis_usulan }}</span></td>
                        <td class="text-center">
                            @if($u->perlu_perhatian)
                                <span class="badge bg-danger text-white fw-bold shadow-sm"><i class="fas fa-exclamation-circle me-1"></i>MENARIK PERHATIAN</span>
                                @if($u->catatan_perhatian)
                                    <div class="small text-danger fw-semibold mt-1">{{ $u->catatan_perhatian }}</div>
                                @endif
                            @else
                                <span class="badge bg-light text-muted border">Normal</span>
                            @endif
                        </td>
                        <td class="text-center">{!! $u->keputusan_badge !!}</td>
                        <td class="small text-muted">{{ $u->alasan_keterangan ?: '-' }}</td>
                        @if($canManagePeserta)
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2" title="Edit Peserta Ini" onclick='editPesertaDetail(@json($u))'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('sidang-tpp.peserta.destroy', ['id' => $sidang->id, 'peserta_id' => $u->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus Warga Binaan {{ addslashes($u->nama_wbp) }} dari daftar sidang ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Hapus Peserta">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canManagePeserta ? '11' : '10' }}" class="text-center py-4 text-muted">Belum ada rincian data Warga Binaan dalam sidang ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canManagePeserta)
<!-- MODAL: Tambah / Edit Peserta Sidang pada Detail -->
<div class="modal fade" id="modalPesertaDetail" tabindex="-1" aria-labelledby="modalPesertaDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formPesertaDetail" method="POST">
                @csrf
                <div id="methodField"></div>
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalPesertaDetailTitle">
                        <i class="fas fa-user-plus me-2"></i>Tambah Peserta Sidang TPP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="pd_nama_wbp" class="form-label small fw-semibold">Nama Lengkap Warga Binaan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_wbp" id="pd_nama_wbp" class="form-control form-control-sm" placeholder="Contoh: Ahmad Sulaeman bin Dahlan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pd_nomor_register" class="form-label small fw-semibold">Nomor Register Lapas/Rutan <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_register" id="pd_nomor_register" class="form-control form-control-sm" placeholder="Contoh: BI.124/2024" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pd_perkara_pasal" class="form-label small fw-semibold">Perkara / Pasal Tindak Pidana</label>
                            <input type="text" name="perkara_pasal" id="pd_perkara_pasal" class="form-control form-control-sm" placeholder="Contoh: Narkotika Ps. 114 ayat (1) UU 35/2009">
                        </div>
                        <div class="col-md-6">
                            <label for="pd_lama_pidana" class="form-label small fw-semibold">Lama Pidana (Vonis)</label>
                            <input type="text" name="lama_pidana" id="pd_lama_pidana" class="form-control form-control-sm" placeholder="Contoh: 5 Tahun Penjara">
                        </div>
                        <div class="col-md-4">
                            <label for="pd_tanggal_ekspirasi" class="form-label small fw-semibold">Tanggal Ekspirasi Bebas</label>
                            <input type="date" name="tanggal_ekspirasi" id="pd_tanggal_ekspirasi" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="pd_tanggal_setengah" class="form-label small fw-semibold">Tanggal 1/2 Pidana</label>
                            <input type="date" name="tanggal_setengah_pidana" id="pd_tanggal_setengah" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="pd_tanggal_dua_pertiga" class="form-label small fw-semibold">Tanggal 2/3 Pidana</label>
                            <input type="date" name="tanggal_dua_pertiga" id="pd_tanggal_dua_pertiga" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label for="pd_jenis_usulan" class="form-label small fw-semibold">Jenis Usulan <span class="text-danger">*</span></label>
                            <select name="jenis_usulan" id="pd_jenis_usulan" class="form-select form-select-sm" required>
                                <option value="PB">PB (Pembebasan Bersyarat)</option>
                                <option value="CB">CB (Cuti Bersyarat)</option>
                                <option value="CMB">CMB (Cuti Menjelang Bebas)</option>
                                <option value="Asimilasi">Asimilasi</option>
                                <option value="Remisi">Remisi</option>
                                <option value="Register F">Register F</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="pd_keterangan" class="form-label small fw-semibold">Keterangan Tambahan</label>
                            <input type="text" name="alasan_keterangan" id="pd_keterangan" class="form-control form-control-sm" placeholder="Catatan syarat, penjamin, dll">
                        </div>

                        <!-- CENTANG MENARIK PERHATIAN DENGAN LABEL MERAH UNTUK KANWIL -->
                        <div class="col-md-12">
                            <div class="p-3 rounded border border-danger-subtle bg-danger-subtle bg-opacity-25 mt-2">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="perlu_perhatian" id="pd_perlu_perhatian" value="1" style="cursor: pointer; width: 2.5em; height: 1.3em;">
                                    <label class="form-check-label fw-bold text-danger ms-2" for="pd_perlu_perhatian" style="cursor: pointer;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Tandai "Menarik Perhatian (Beri Label Merah)"
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-2">
                                    Beri tanda centang jika Warga Binaan ini memiliki perkara menonjol, atensi pimpinan, residivis/tipikor, memiliki catatan khusus, atau membutuhkan verifikasi ekstra oleh Kantor Wilayah.
                                </small>
                                <div>
                                    <label for="pd_catatan_perhatian" class="form-label small fw-semibold text-dark mb-1">Catatan / Alasan Perhatian Khusus (Opsional):</label>
                                    <input type="text" name="catatan_perhatian" id="pd_catatan_perhatian" class="form-control form-control-sm" placeholder="Contoh: Perkara Tipikor / Atensi Pimpinan, mohon verifikasi ketelitian pembayaran denda & UP">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold" id="btnSimpanPesertaDetail">
                        <i class="fas fa-save me-1"></i>Simpan Peserta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const modalPesertaDetail = new bootstrap.Modal(document.getElementById('modalPesertaDetail'));
    const formPesertaDetail = document.getElementById('formPesertaDetail');
    const methodField = document.getElementById('methodField');

    function bukaModalTambahPesertaDetail() {
        formPesertaDetail.action = "{{ route('sidang-tpp.peserta.store', $sidang->id) }}";
        methodField.innerHTML = '';
        document.getElementById('modalPesertaDetailTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Tambah Peserta Sidang TPP';
        formPesertaDetail.reset();
        document.getElementById('pd_perlu_perhatian').checked = false;
        document.getElementById('pd_jenis_usulan').value = 'PB';
        modalPesertaDetail.show();
    }

    function editPesertaDetail(u) {
        formPesertaDetail.action = "/sidang-tpp/{{ $sidang->id }}/peserta/" + u.id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('modalPesertaDetailTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit Data Peserta: ' + (u.nama_wbp || '');

        document.getElementById('pd_nama_wbp').value = u.nama_wbp || '';
        document.getElementById('pd_nomor_register').value = u.nomor_register || '';
        document.getElementById('pd_perkara_pasal').value = u.perkara_pasal || '';
        document.getElementById('pd_lama_pidana').value = u.lama_pidana || '';
        document.getElementById('pd_tanggal_ekspirasi').value = u.tanggal_ekspirasi ? u.tanggal_ekspirasi.substring(0, 10) : '';
        document.getElementById('pd_tanggal_setengah').value = u.tanggal_setengah_pidana ? u.tanggal_setengah_pidana.substring(0, 10) : '';
        document.getElementById('pd_tanggal_dua_pertiga').value = u.tanggal_dua_pertiga ? u.tanggal_dua_pertiga.substring(0, 10) : '';
        document.getElementById('pd_jenis_usulan').value = u.jenis_usulan || 'PB';
        document.getElementById('pd_perlu_perhatian').checked = !!u.perlu_perhatian;
        document.getElementById('pd_catatan_perhatian').value = u.catatan_perhatian || '';
        document.getElementById('pd_keterangan').value = u.alasan_keterangan || '';

        modalPesertaDetail.show();
    }
</script>
@endpush
@endif
@endsection
