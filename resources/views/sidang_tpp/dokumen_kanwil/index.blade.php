@extends('layouts.app')

@section('title', 'Naskah & Laporan Sidang TPP Kanwil - SULTAN BANTEN')

@section('content')
<div class="container-fluid py-3 px-3 px-md-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sidang-tpp.index') }}" class="text-decoration-none">Sidang TPP</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Naskah &amp; Laporan Kanwil</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-file-signature text-primary me-2"></i>Naskah &amp; Laporan Sidang TPP Kantor Wilayah
            </h4>
            <p class="text-muted small mb-0">Pusat penerbitan Nota Dinas Permohonan Surat Tugas, Laporan Pelaksanaan Sidang TPP Kanwil (berbasis Berita Acara &amp; Rekap Usulan), serta Otentifikasi Dokumen (Tanda Tangan Manual &amp; Digital QR Code).</p>
        </div>
        <div class="mt-2 mt-md-0 d-flex gap-2">
            <a href="{{ route('ai-setting.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="fas fa-robot me-1 text-warning"></i>Pengaturan AI &amp; Personil
            </a>
            <a href="{{ route('sidang-tpp.kanwil') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                <i class="fas fa-stamp me-1"></i>Verifikasi &amp; Bintorwasdal
            </a>
        </div>
    </div>

    <!-- Alert Sukses / Error -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2.5 px-3 small shadow-sm d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2.5 px-3 small shadow-sm d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg me-2 text-danger"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Card Filter & Pencarian -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2.5 px-3">
            <form action="{{ route('sidang-tpp.dokumen-kanwil.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4 col-lg-3">
                    <label for="upt_id" class="form-label small fw-semibold mb-1 text-muted">Satuan Kerja UPT</label>
                    <select name="upt_id" id="upt_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Satker UPT</option>
                        @foreach($uptList as $u)
                            <option value="{{ $u->id }}" {{ $filterUpt == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label for="status" class="form-label small fw-semibold mb-1 text-muted">Status Sidang</label>
                    <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="dijadwalkan" {{ $filterStatus == 'dijadwalkan' ? 'selected' : '' }}>Dijadwalkan</option>
                        <option value="menunggu_hasil_upt" {{ $filterStatus == 'menunggu_hasil_upt' ? 'selected' : '' }}>Menunggu Hasil UPT (BA)</option>
                        <option value="menunggu_verifikasi_kanwil" {{ $filterStatus == 'menunggu_verifikasi_kanwil' ? 'selected' : '' }}>Menunggu Verifikasi Kanwil</option>
                        <option value="selesai_ttd_kakanwil" {{ $filterStatus == 'selesai_ttd_kakanwil' ? 'selected' : '' }}>Selesai TTD Kakanwil</option>
                    </select>
                </div>
                <div class="col-md-5 col-lg-4">
                    <label for="search" class="form-label small fw-semibold mb-1 text-muted">Cari Dokumen / Agenda</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" id="search" class="form-control" placeholder="Nomor surat, UPT, agenda..." value="{{ $search }}">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                    </div>
                </div>
                <div class="col-md-12 col-lg-2 text-md-end mt-md-4">
                    <a href="{{ route('sidang-tpp.dokumen-kanwil.index') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo me-1"></i>Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Naskah & Laporan Kanwil -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light text-secondary text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                        <tr>
                            <th class="text-center py-3" style="width: 4%;">No</th>
                            <th style="width: 20%;">Satker UPT &amp; Jadwal</th>
                            <th style="width: 25%;">1. Nota Dinas Permohonan ST</th>
                            <th style="width: 32%;">2. Laporan Sidang TPP Kanwil</th>
                            <th class="text-center" style="width: 19%;">Aksi / Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sidangList as $idx => $s)
                            @php
                                $notaDinas = $s->naskahKanwil->where('jenis_dokumen', 'nota_dinas')->first();
                                $lapKanwil = $s->naskahKanwil->where('jenis_dokumen', 'laporan_tpp')->first();
                                $hasBa = !empty($s->file_berita_acara) && file_exists(public_path($s->file_berita_acara));
                                $hasUndangan = !empty($s->file_undangan) && file_exists(public_path($s->file_undangan));
                            @endphp
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $sidangList->firstItem() + $idx }}</td>
                                
                                <!-- Kolom Satker & Jadwal -->
                                <td>
                                    <div class="fw-bold text-dark">{{ $s->upt?->nama_upt }}</div>
                                    <div class="text-muted small">
                                        <i class="far fa-calendar-alt text-primary me-1"></i>{{ $s->tanggal_sidang ? $s->tanggal_sidang->translatedFormat('d M Y') : '-' }}
                                    </div>
                                    <div class="mt-1">
                                        @if($s->metode_sidang === 'daring')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fas fa-video me-1"></i>Virtual Zoom</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fas fa-users me-1"></i>Tatap Muka</span>
                                        @endif
                                        <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 10px;">{{ $s->total_usulan ?: $s->usulan->count() }} Usulan</span>
                                    </div>
                                    <div class="mt-1" style="font-size: 10.5px;">
                                        @if($hasUndangan)
                                            <a href="{{ route('sidang-tpp.public-undangan', $s->id) }}" target="_blank" class="text-decoration-none text-primary" title="Buka Surat Undangan UPT">
                                                <i class="fas fa-envelope-open-text me-1"></i>Undangan: {{ Str::limit($s->nomor_undangan ?: 'Ada', 18) }}
                                            </a>
                                        @else
                                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Belum ada undangan</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Dokumen 1: Nota Dinas Permohonan ST -->
                                <td>
                                    @if($notaDinas)
                                        <div class="d-flex align-items-center mb-1">
                                            @if($notaDinas->status === 'signed')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 10.5px;">
                                                    <i class="fas fa-check-circle me-1"></i>Ditandatangani Basah
                                                </span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 10.5px;">
                                                    <i class="fas fa-file-word me-1"></i>Draf Terbit (Word)
                                                </span>
                                            @endif
                                        </div>
                                        <div class="small fw-semibold text-truncate" style="max-width: 220px;" title="{{ $notaDinas->nomor_dokumen }}">
                                            No: {{ $notaDinas->nomor_dokumen ?: '-' }}
                                        </div>
                                        <div class="mt-1.5 d-flex gap-1 flex-wrap">
                                            <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="bukaModalNotaDinas({{ $s->id }})" title="Edit / Regenerate Nota Dinas">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            @if(!empty($notaDinas->file_signed))
                                                <a href="{{ asset($notaDinas->file_signed) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;" title="Lihat Scan PDF TTD Basah">
                                                    <i class="fas fa-file-pdf me-1"></i>Scan TTD
                                                </a>
                                            @endif
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="bukaModalUploadSigned({{ $s->id }}, 'nota_dinas', '{{ $notaDinas->nomor_dokumen }}')" title="Upload berkas yang sudah ditandatangani basah">
                                                <i class="fas fa-upload me-1"></i>Upload TTD
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-muted small mb-1">
                                            <i class="fas fa-clock me-1 text-warning"></i>Belum dibuat
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm py-1 px-2.5 shadow-sm fw-semibold" style="font-size: 11.5px;" onclick="bukaModalNotaDinas({{ $s->id }})" title="Buat Nota Dinas Permohonan Surat Tugas dari surat undangan UPT">
                                            <i class="fas fa-magic me-1"></i>Buat Nota Dinas ST
                                        </button>
                                    @endif
                                </td>

                                <!-- Kolom Dokumen 2: Laporan Sidang TPP Kanwil -->
                                <td>
                                    @if($lapKanwil)
                                        <div class="d-flex align-items-center mb-1">
                                            @if($lapKanwil->status === 'signed' || !empty($s->file_laporan_kakanwil))
                                                <span class="badge bg-success text-white px-2 py-0.5" style="font-size: 10.5px;">
                                                    <i class="fas fa-check-double me-1"></i>Selesai TTD
                                                </span>
                                            @elseif($lapKanwil->metode_ttd === 'digital_qr')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5" style="font-size: 10.5px;">
                                                    <i class="fas fa-qrcode me-1"></i>Digital QR Terverifikasi
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 10.5px;">
                                                    <i class="fas fa-file-signature me-1"></i>Siap TTD Manual
                                                </span>
                                            @endif
                                        </div>
                                        <div class="small fw-semibold text-truncate" style="max-width: 250px;" title="{{ $lapKanwil->nomor_dokumen }}">
                                            No: {{ $lapKanwil->nomor_dokumen ?: ($s->nomor_laporan_kanwil ?: 'Belum bernomor') }}
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            {{ $lapKanwil->materi_agenda ? Str::limit($lapKanwil->materi_agenda, 38) : 'Materi Sidang TPP' }}
                                        </div>
                                        <div class="mt-1.5 d-flex gap-1 flex-wrap">
                                            <button type="button" class="btn btn-outline-primary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="bukaModalLaporanTpp({{ $s->id }})" title="Buka editor / perbarui Laporan Sidang TPP">
                                                <i class="fas fa-edit me-1"></i>Edit Laporan
                                            </button>
                                            @if(!empty($s->file_laporan_kakanwil))
                                                <a href="{{ asset($s->file_laporan_kakanwil) }}" target="_blank" class="btn btn-outline-success btn-xs py-0.5 px-2" style="font-size: 11px;" title="Lihat Laporan Final">
                                                    <i class="fas fa-file-pdf me-1"></i>Laporan Final
                                                </a>
                                            @endif
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 11px;" onclick="bukaModalUploadSigned({{ $s->id }}, 'laporan_tpp', '{{ $lapKanwil->nomor_dokumen }}')" title="Upload laporan yang telah ditandatangani basah">
                                                <i class="fas fa-upload me-1"></i>Upload TTD
                                            </button>
                                        </div>
                                    @else
                                        @if($hasBa)
                                            <div class="text-success small mb-1">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>BA UPT Tersedia
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm py-1 px-2.5 shadow-sm fw-semibold" style="font-size: 11.5px;" onclick="bukaModalLaporanTpp({{ $s->id }})" title="Generate Laporan Pelaksanaan Sidang TPP Kanwil dari Berita Acara UPT">
                                                <i class="fas fa-bolt me-1"></i>Buat Laporan TPP (AI/Manual)
                                            </button>
                                        @else
                                            <div class="text-muted small mb-1" style="font-size: 11px;">
                                                <i class="fas fa-hourglass-half me-1 text-warning"></i>Menunggu UPT Upload BA
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11px;" onclick="bukaModalLaporanTpp({{ $s->id }})" title="Buat Laporan secara manual tanpa BA">
                                                <i class="fas fa-pen me-1"></i>Buat Manual
                                            </button>
                                        @endif
                                    @endif
                                </td>

                                <!-- Kolom Aksi & Otentifikasi -->
                                <td class="text-center">
                                    @if($lapKanwil)
                                        <a href="{{ $lapKanwil->getPublicVerificationUrl() }}" target="_blank" class="btn btn-outline-info btn-xs py-1 px-2 mb-1 w-100 fw-semibold" style="font-size: 11px;" title="Buka Halaman Otentifikasi QR Code">
                                            <i class="fas fa-qrcode me-1"></i>Cek Otentifikasi QR
                                        </a>
                                    @endif
                                    <div class="d-flex justify-content-center gap-1 mt-1">
                                        <a href="{{ route('sidang-tpp.show', $s->id) }}" class="btn btn-light btn-sm py-0.5 px-2 border" title="Lihat Rincian Lengkap Sidang &amp; Usulan Narapidana">
                                            <i class="fas fa-eye text-primary"></i> Rincian
                                        </a>
                                        @if($hasBa)
                                            <a href="{{ asset($s->file_berita_acara) }}" target="_blank" class="btn btn-light btn-sm py-0.5 px-2 border text-danger" title="Lihat Berita Acara UPT">
                                                <i class="fas fa-file-pdf"></i> BA
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <div>Belum ada jadwal sidang TPP yang sesuai dengan kriteria filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sidangList->hasPages())
            <div class="card-footer bg-white py-2 px-3 border-top">
                {{ $sidangList->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Generator Laporan Sidang TPP Kanwil -->
@include('sidang_tpp.dokumen_kanwil.partials.modal_laporan_tpp')

<!-- Modal Upload Scan Berkas TTD Basah -->
@include('sidang_tpp.dokumen_kanwil.partials.modal_upload_signed')

<!-- Modal Generator Nota Dinas Permohonan Surat Tugas -->
@include('sidang_tpp.partials.modal_nota_dinas')

@endsection

@push('scripts')
<script>
    const modalLaporanTppEl = document.getElementById('modalLaporanTpp');
    const modalLaporanTpp = new bootstrap.Modal(modalLaporanTppEl);

    const modalUploadSignedEl = document.getElementById('modalUploadSigned');
    const modalUploadSigned = new bootstrap.Modal(modalUploadSignedEl);

    let activeSidangData = @json($sidangList->items());

    function getSidangById(id) {
        return activeSidangData.find(s => s.id === id);
    }

    function bukaModalLaporanTpp(sidangId) {
        const s = getSidangById(sidangId);
        if (!s) return;

        document.getElementById('formLaporanTpp').action = '/sidang-tpp/' + sidangId + '/dokumen-kanwil/laporan-tpp/generate';
        document.getElementById('lap_sidang_id').value = sidangId;
        document.getElementById('lap_upt_nama').innerText = s.upt ? s.upt.nama_upt : 'UPT Pemasyarakatan';
        document.getElementById('lap_tgl_sidang').innerText = s.tanggal_sidang ? new Date(s.tanggal_sidang).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '-';

        // Cek apakah ada record naskah existing
        const lapKanwil = s.naskah_kanwil ? s.naskah_kanwil.find(n => n.jenis_dokumen === 'laporan_tpp') : null;

        if (lapKanwil && lapKanwil.konten_data) {
            const kd = lapKanwil.konten_data;
            document.getElementById('lap_nomor_dokumen').value = lapKanwil.nomor_dokumen || s.nomor_laporan_kanwil || '';
            document.getElementById('lap_topik_sidang').value = kd.topik_sidang || s.agenda_kategori || '';
            document.getElementById('lap_paragraf_a1').value = kd.paragraf_a1 || '';
            document.getElementById('lap_paragraf_a2').value = kd.paragraf_a2 || '';
            document.getElementById('lap_paragraf_a3').value = kd.paragraf_a3 || '';
            document.getElementById('lap_paragraf_a4').value = kd.paragraf_a4 || '';
            document.getElementById('lap_paragraf_b1').value = kd.paragraf_b1 || '';
            document.getElementById('lap_penutup').value = kd.penutup || '';
            document.getElementById('lap_pengawas_nama').value = lapKanwil.pengawas_nama || s.nama_pengawas_kanwil || 'Gunawan Wibisono';
            document.getElementById('lap_penandatangan_nama').value = lapKanwil.penandatangan_nama || '{{ $setting->penandatangan_nama }}';
            document.getElementById('lap_penandatangan_jabatan').value = lapKanwil.penandatangan_jabatan || '{{ $setting->penandatangan_jabatan }}';
            document.getElementById('lap_penandatangan_nip').value = lapKanwil.penandatangan_nip || '{{ $setting->penandatangan_nip }}';
            document.getElementById('lap_tampilkan_nip').checked = !!lapKanwil.tampilkan_nip;

            // Render materi items
            renderMateriInput(kd.materi_sidang_items || []);
            renderPoinList('wrapperB2Poin', kd.paragraf_b2_poin || [], 'paragraf_b2_poin[]');
            renderPoinList('wrapperSimpulan', kd.simpulan_poin || [], 'simpulan_poin[]');
            renderPoinList('wrapperSaran', kd.saran_poin || [], 'saran_poin[]');
        } else {
            // Draf default awal
            document.getElementById('lap_nomor_dokumen').value = s.nomor_laporan_kanwil || '';
            document.getElementById('lap_topik_sidang').value = s.agenda_kategori || 'Pembahasan Usulan Hak Integrasi dan Remisi';
            document.getElementById('lap_pengawas_nama').value = s.nama_pengawas_kanwil || 'Gunawan Wibisono';
            document.getElementById('lap_penandatangan_nama').value = '{{ $setting->penandatangan_nama }}';
            document.getElementById('lap_penandatangan_jabatan').value = '{{ $setting->penandatangan_jabatan }}';
            document.getElementById('lap_penandatangan_nip').value = '{{ $setting->penandatangan_nip }}';
            document.getElementById('lap_tampilkan_nip').checked = {{ $setting->tampilkan_nip ? 'true' : 'false' }};

            const tglStr = s.tanggal_sidang ? new Date(s.tanggal_sidang).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '-';
            const upt = s.upt ? s.upt.nama_upt : 'UPT Pemasyarakatan';
            const metodeStr = s.metode_sidang === 'daring' ? 'secara virtual melalui media Zoom Meeting' : 'secara tatap muka langsung';

            document.getElementById('lap_paragraf_a1').value = `Pada hari ${tglStr}, Kantor Wilayah Direktorat Jenderal Pemasyarakatan Banten mengikuti pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) ${metodeStr} di ${upt}.`;
            document.getElementById('lap_paragraf_a2').value = `Sidang TPP membahas usulan ${document.getElementById('lap_topik_sidang').value} terhadap narapidana yang telah diusulkan oleh ${upt}.`;
            document.getElementById('lap_paragraf_a3').value = `Perwakilan Kantor Wilayah hadir sebagai unsur pembina dan pengawas untuk melakukan verifikasi terhadap pemenuhan persyaratan substantif maupun administratif sesuai ketentuan peraturan perundang-undangan. Adapun Materi sidang hari ini adalah sebagai berikut:`;
            document.getElementById('lap_paragraf_a4').value = `Perwakilan Kantor Wilayah melakukan pencermatan terhadap kelengkapan dokumen usulan, data registrasi narapidana, perhitungan masa pidana, laporan perkembangan pembinaan, penilaian risiko, serta dokumen pendukung lainnya yang dipersyaratkan dalam Sistem Database Pemasyarakatan (SDP).`;
            document.getElementById('lap_paragraf_b1').value = `Berdasarkan hasil pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) ${upt} pada tanggal ${tglStr}, Tim Kantor Wilayah melakukan verifikasi terhadap usulan materi sidang.`;
            document.getElementById('lap_penutup').value = `Demikian laporan pelaksanaan Sidang Tim Pengamat Pemasyarakatan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tugas serta sebagai bahan informasi bagi pimpinan dalam rangka pembinaan dan pengawasan penyelenggaraan tugas di lingkungan Pemasyarakatan.`;

            // Default Materi Sidang jika ada usulan
            const defaultMateri = [
                `Pembebasan Bersyarat (PB): ${s.total_usulan || 0} orang narapidana`,
            ];
            renderMateriInput(defaultMateri);

            renderPoinList('wrapperB2Poin', [
                'Seluruh Narapidana yang dibahas telah memenuhi persyaratan substantif sebagaimana diatur dalam ketentuan peraturan perundang-undangan.',
                'Seluruh persyaratan administratif telah lengkap dan sesuai dengan ketentuan serta telah tersedia dalam Sistem Database Pemasyarakatan (SDP).',
                'Perhitungan masa pidana, tanggal 2/3 masa pidana, dan tanggal ekspirasi telah sesuai dengan data register narapidana.',
                'Dengan terpenuhinya persyaratan substantif dan administratif, usulan tersebut direkomendasikan untuk diproses lebih lanjut sesuai mekanisme yang berlaku.'
            ], 'paragraf_b2_poin[]');

            renderPoinList('wrapperSimpulan', [
                'Pelaksanaan Sidang Tim Pengamat Pemasyarakatan berjalan dengan tertib, lancar, dan sesuai dengan ketentuan yang berlaku.',
                'Pembahasan dilakukan secara objektif dengan mempertimbangkan aspek administratif, substantif, keamanan, serta hasil pembinaan terhadap masing-masing narapidana.'
            ], 'simpulan_poin[]');

            renderPoinList('wrapperSaran', [
                `${upt} agar tetap meningkatkan ketelitian dalam melakukan verifikasi administrasi sebelum usulan diajukan kepada Kantor Wilayah.`,
                'Seluruh dokumen pendukung agar dipastikan telah lengkap, valid, dan diunggah ke dalam Sistem Database Pemasyarakatan (SDP) sebelum proses pengusulan dilanjutkan.',
                'Jika ada kendala, agar segera berkoordinasi dengan Kantor Wilayah.',
                'Monitoring terhadap pelaksanaan usulan agar terus dilakukan sesuai ketentuan peraturan perundang-undangan guna menjamin tercapainya tujuan Sistem Pemasyarakatan.'
            ], 'saran_poin[]');
        }

        // Tampilkan peringatan jika tidak ada berkas BA
        const bannerNoBa = document.getElementById('bannerNoBa');
        const btnAiBa = document.getElementById('btnAnalisisBaAi');
        if (s.file_berita_acara) {
            bannerNoBa.classList.add('d-none');
            btnAiBa.disabled = false;
        } else {
            bannerNoBa.classList.remove('d-none');
            btnAiBa.disabled = true;
        }

        // Muat foto dokumentasi yang sudah ada (dari laporan kanwil tersimpan atau dari berkas dokumentasi sidang UPT)
        let existingPhotos = [];
        if (lapKanwil && lapKanwil.konten_data && lapKanwil.konten_data.foto_dokumentasi && lapKanwil.konten_data.foto_dokumentasi.length > 0) {
            existingPhotos = lapKanwil.konten_data.foto_dokumentasi;
        } else if (s.file_dokumentasi) {
            try {
                existingPhotos = typeof s.file_dokumentasi === 'string' ? JSON.parse(s.file_dokumentasi) : s.file_dokumentasi;
            } catch(e) {
                existingPhotos = [s.file_dokumentasi];
            }
        }
        if (!Array.isArray(existingPhotos)) existingPhotos = [existingPhotos];

        // Reset input file
        if (document.getElementById('lap_foto_1')) document.getElementById('lap_foto_1').value = '';
        if (document.getElementById('lap_foto_2')) document.getElementById('lap_foto_2').value = '';

        // Tampilkan Foto 1
        if (existingPhotos[0]) {
            const path1 = existingPhotos[0].startsWith('http') ? existingPhotos[0] : '/' + existingPhotos[0].replace(/^\//, '');
            document.getElementById('lap_existing_foto_1').value = existingPhotos[0];
            document.getElementById('preview_foto_1').src = path1;
            document.getElementById('wrapper_preview_1').classList.remove('d-none');
        } else {
            document.getElementById('lap_existing_foto_1').value = '';
            document.getElementById('preview_foto_1').src = '';
            document.getElementById('wrapper_preview_1').classList.add('d-none');
        }

        // Tampilkan Foto 2
        if (existingPhotos[1]) {
            const path2 = existingPhotos[1].startsWith('http') ? existingPhotos[1] : '/' + existingPhotos[1].replace(/^\//, '');
            document.getElementById('lap_existing_foto_2').value = existingPhotos[1];
            document.getElementById('preview_foto_2').src = path2;
            document.getElementById('wrapper_preview_2').classList.remove('d-none');
        } else {
            document.getElementById('lap_existing_foto_2').value = '';
            document.getElementById('preview_foto_2').src = '';
            document.getElementById('wrapper_preview_2').classList.add('d-none');
        }

        modalLaporanTpp.show();
    }

    function renderMateriInput(items) {
        const wrapper = document.getElementById('wrapperMateriItems');
        wrapper.innerHTML = '';
        if (!items || items.length === 0) items = ['Pembebasan Bersyarat (PB): -'];

        items.forEach((item, idx) => {
            const div = document.createElement('div');
            div.className = 'input-group input-group-sm mb-1.5 item-materi-row';
            div.innerHTML = `
                <span class="input-group-text bg-light text-muted fw-bold" style="font-size:11px;">&bull;</span>
                <input type="text" name="materi_items[]" class="form-control form-control-sm" value="${item}" placeholder="Contoh: Pembebasan Bersyarat (PB): 33 orang narapidana" required>
                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.item-materi-row').remove()"><i class="fas fa-trash-alt"></i></button>
            `;
            wrapper.appendChild(div);
        });
    }

    function previewFotoLaporan(input, imgId, wrapperId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(imgId);
                const wrapper = document.getElementById(wrapperId);
                if (img) img.src = e.target.result;
                if (wrapper) wrapper.classList.remove('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function hapusFotoDokumentasi(num) {
        const input = document.getElementById('lap_foto_' + num);
        const existing = document.getElementById('lap_existing_foto_' + num);
        const img = document.getElementById('preview_foto_' + num);
        const wrapper = document.getElementById('wrapper_preview_' + num);

        if (input) input.value = '';
        if (existing) existing.value = '';
        if (img) img.src = '';
        if (wrapper) wrapper.classList.add('d-none');
    }

    function tambahBarisMateri() {
        const wrapper = document.getElementById('wrapperMateriItems');
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1.5 item-materi-row';
        div.innerHTML = `
            <span class="input-group-text bg-light text-muted fw-bold" style="font-size:11px;">&bull;</span>
            <input type="text" name="materi_items[]" class="form-control form-control-sm" placeholder="Contoh: Cuti Bersyarat (CB): 5 orang narapidana" required>
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.item-materi-row').remove()"><i class="fas fa-trash-alt"></i></button>
        `;
        wrapper.appendChild(div);
    }

    function renderPoinList(wrapperId, items, inputName) {
        const wrapper = document.getElementById(wrapperId);
        wrapper.innerHTML = '';
        if (!items || items.length === 0) return;

        items.forEach((it, i) => {
            const div = document.createElement('div');
            div.className = 'input-group input-group-sm mb-1.5 poin-item-row';
            div.innerHTML = `
                <span class="input-group-text bg-light text-muted" style="font-size:11px;">${i+1}</span>
                <input type="text" name="${inputName}" class="form-control form-control-sm" value="${it}">
                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.poin-item-row').remove()"><i class="fas fa-trash-alt"></i></button>
            `;
            wrapper.appendChild(div);
        });
    }

    function tambahPoin(wrapperId, inputName) {
        const wrapper = document.getElementById(wrapperId);
        const count = wrapper.children.length + 1;
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1.5 poin-item-row';
        div.innerHTML = `
            <span class="input-group-text bg-light text-muted" style="font-size:11px;">${count}</span>
            <input type="text" name="${inputName}" class="form-control form-control-sm" placeholder="Ketik butir isi...">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.poin-item-row').remove()"><i class="fas fa-trash-alt"></i></button>
        `;
        wrapper.appendChild(div);
    }

    function triggerAnalisisBaAi() {
        const sidangId = document.getElementById('lap_sidang_id').value;
        const btn = document.getElementById('btnAnalisisBaAi');
        const alertArea = document.getElementById('lapAiAlertArea');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>AI Sedang Menganalisis Berita Acara PDF...';
        alertArea.classList.add('d-none');

        fetch('/sidang-tpp/' + sidangId + '/dokumen-kanwil/analyze-ba-ai', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-robot me-1 text-warning"></i>Analisis Ulang Berita Acara (BA) dengan AI';

            if (res.success && res.data) {
                const d = res.data;
                if (d.topik_sidang) document.getElementById('lap_topik_sidang').value = d.topik_sidang;
                if (d.materi_sidang_items && d.materi_sidang_items.length > 0) renderMateriInput(d.materi_sidang_items);
                if (d.paragraf_a1) document.getElementById('lap_paragraf_a1').value = d.paragraf_a1;
                if (d.paragraf_a2) document.getElementById('lap_paragraf_a2').value = d.paragraf_a2;
                if (d.paragraf_a3) document.getElementById('lap_paragraf_a3').value = d.paragraf_a3;
                if (d.paragraf_a4) document.getElementById('lap_paragraf_a4').value = d.paragraf_a4;
                if (d.paragraf_b1) document.getElementById('lap_paragraf_b1').value = d.paragraf_b1;
                if (d.paragraf_b2_poin && d.paragraf_b2_poin.length > 0) renderPoinList('wrapperB2Poin', d.paragraf_b2_poin, 'paragraf_b2_poin[]');
                if (d.simpulan_poin && d.simpulan_poin.length > 0) renderPoinList('wrapperSimpulan', d.simpulan_poin, 'simpulan_poin[]');
                if (d.saran_poin && d.saran_poin.length > 0) renderPoinList('wrapperSaran', d.saran_poin, 'saran_poin[]');
                if (d.penutup) document.getElementById('lap_penutup').value = d.penutup;

                alertArea.classList.remove('d-none');
                alertArea.innerHTML = `
                    <div class="alert alert-success py-2 px-3 small mb-3 d-flex align-items-center">
                        <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
                        <div><b>Berhasil Dianalisis AI:</b> Seluruh bagian laporan telah disesuaikan secara cerdas dengan isi Berita Acara sidang. Silakan periksa kembali sebelum mengunduh.</div>
                    </div>
                `;
            } else {
                alertArea.classList.remove('d-none');
                alertArea.innerHTML = `
                    <div class="alert alert-warning py-2 px-3 small mb-3 d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-lg me-2 text-warning"></i>
                        <div><b>Catatan AI:</b> ${res.message || 'Gagal membaca berkas.'}</div>
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-robot me-1 text-warning"></i>Analisis Berita Acara (BA) dengan AI';
            alertArea.classList.remove('d-none');
            alertArea.innerHTML = `
                <div class="alert alert-danger py-2 px-3 small mb-3 d-flex align-items-center">
                    <i class="fas fa-times-circle fa-lg me-2 text-danger"></i>
                    <div><b>Kesalahan Sistem:</b> ${err.message}</div>
                </div>
            `;
        });
    }

    function bukaModalUploadSigned(sidangId, jenis, noDokumen) {
        document.getElementById('formUploadSigned').action = '/sidang-tpp/' + sidangId + '/dokumen-kanwil/upload-signed';
        document.getElementById('up_jenis_dokumen').value = jenis;
        document.getElementById('up_nomor_dokumen').value = noDokumen || '';
        document.getElementById('up_jenis_label').innerText = jenis === 'laporan_tpp' ? 'Laporan Pelaksanaan Sidang TPP Kanwil' : 'Nota Dinas Permohonan Surat Tugas';
        modalUploadSigned.show();
    }

    function bukaModalNotaDinas(sidangId) {
        const s = getSidangById(sidangId);
        if (!s) return;

        const form = document.getElementById('formNotaDinasModal');
        if (form) {
            form.action = '/sidang-tpp/' + sidangId + '/nota-dinas/generate';
            form.dataset.sidangId = sidangId;
        }

        const uptNama = s.upt ? s.upt.nama_upt : '';
        if (document.getElementById('nd_upt_nama')) document.getElementById('nd_upt_nama').value = uptNama;
        if (document.getElementById('nd_hal')) document.getElementById('nd_hal').value = 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di ' + uptNama;
        if (document.getElementById('nd_nomor_undangan')) document.getElementById('nd_nomor_undangan').value = s.nomor_undangan || '';

        if (document.getElementById('nd_tanggal_surat_upt')) {
            document.getElementById('nd_tanggal_surat_upt').value = s.tanggal_surat ? new Date(s.tanggal_surat).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
        }
        if (document.getElementById('nd_hari_tanggal')) {
            document.getElementById('nd_hari_tanggal').value = s.tanggal_sidang ? new Date(s.tanggal_sidang).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
        }
        if (document.getElementById('nd_jam')) {
            document.getElementById('nd_jam').value = s.jam_mulai ? (s.jam_mulai.substring(0, 5) + ' s.d selesai') : '09:00 s.d selesai';
        }
        if (document.getElementById('nd_tempat')) {
            document.getElementById('nd_tempat').value = s.link_daring ? 'Daring / Zoom Cloud Meeting' : ('Ruang Sidang TPP ' + uptNama);
        }

        const notaDinas = s.naskah_kanwil ? s.naskah_kanwil.find(n => n.jenis_dokumen === 'nota_dinas') : null;
        if (notaDinas && notaDinas.nomor_dokumen) {
            if (document.getElementById('nd_nomor')) document.getElementById('nd_nomor').value = notaDinas.nomor_dokumen;
        }

        const alertArea = document.getElementById('aiExtractionAlert');
        if (alertArea) alertArea.classList.add('d-none');

        const modalEl = document.getElementById('modalNotaDinas');
        if (modalEl) {
            const m = bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        }
    }
</script>
@endpush

