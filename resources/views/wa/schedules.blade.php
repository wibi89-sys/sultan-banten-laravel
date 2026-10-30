@extends('layouts.app')

@section('title', 'Jadwal & Template Pesan WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-clock text-warning me-2"></i>Jadwal &amp; Template Pesan WhatsApp
@endsection

@section('content')
<!-- Header Quick Navigation Tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.gateway') }}">
                    <i class="fas fa-plug me-1 text-primary"></i>1. Gateway & Koneksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.contacts') }}">
                    <i class="fas fa-address-book me-1 text-info"></i>2. Kontak & Target
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active py-1 px-3 fw-semibold" href="{{ route('wa.schedules') }}">
                    <i class="fas fa-clock me-1 text-warning"></i>3. Jadwal & Template
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 text-secondary" href="{{ route('wa.logs') }}">
                    <i class="fas fa-history me-1 text-success"></i>4. Riwayat & Log
                </a>
            </li>
        </ul>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-dark btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalCronGuide">
                <i class="fas fa-terminal me-1 text-primary"></i>Panduan Cron Server
            </button>
            <span class="badge bg-light text-dark border px-3 py-2 small">
                Waktu Server: <strong>{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</strong>
            </span>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
    <i class="fas fa-exclamation-triangle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- ================================= BAGIAN 1: TUGAS RUTIN BAWAAN SISTEM ================================= -->
<div class="row g-4 mb-4">
    <!-- Kartu 1: Reminder Pagi -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-sun text-warning me-2"></i>1. Reminder Pagi (Pengingat Input Data)
                    </h6>
                    <small class="text-muted">Dikirim otomatis ke operator UPT yang belum mengisi data hari ini.</small>
                </div>
                <span class="badge bg-warning-subtle text-warning border px-2 py-1">Setiap Hari Kerja</span>
            </div>

            <form action="{{ route('wa.schedules.builtin.update') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="morning">
                
                <div class="row g-2 mb-2 align-items-center">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-0">Jam Kirim Otomatis</label>
                    </div>
                    <div class="col-sm-7">
                        <input type="time" name="jam_reminder_pagi" class="form-control form-control-sm"
                            value="{{ substr($waSettings->jam_reminder_pagi ?? '07:30:00', 0, 5) }}" required>
                        <small class="text-muted" style="font-size: 11px;">Jendela otomatis: Jam kirim s/d 11:30 WIB</small>
                    </div>
                </div>

                <div class="row g-2 mb-2 align-items-center">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-0">
                            Jeda Antar Kontak
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 10px;">Anti-Spam</span>
                        </label>
                    </div>
                    <div class="col-sm-7">
                        <div class="input-group input-group-sm">
                            <input type="number" name="jeda_kirim" class="form-control" min="1" max="30"
                                value="{{ $waSettings->jeda_kirim ?? 4 }}" required>
                            <span class="input-group-text">detik</span>
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Jeda acak (&plusmn;1 dtk) antar operator agar aman dari banned WA.</small>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-0">
                            Variasi Salam <code>{salam}</code>
                        </label>
                    </div>
                    <div class="col-sm-7">
                        <input type="text" name="daftar_salam" class="form-control form-control-sm"
                            value="{{ $waSettings->daftar_salam ?? 'Selamat pagi, Semangat pagi, Yth., Halo Rekan, Salam hangat' }}"
                            placeholder="Contoh: Selamat pagi, Semangat pagi, Yth.">
                        <small class="text-muted" style="font-size: 11px;">Diacak otomatis untuk tag <code>{salam}</code>. Pisahkan koma.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-semibold mb-0">Template Pesan Pengingat</label>
                        <span class="badge bg-light text-primary border" style="font-size: 10px;" title="Mendukung Spintax acak">
                            <i class="fas fa-magic me-1"></i>Spintax Support
                        </span>
                    </div>
                    <textarea name="template_pagi" class="form-control form-control-sm font-monospace" rows="4" required>{{ $waSettings->template_pagi ?: "🌅 *PENGINGAT INPUT DATA*\n\n{salam} {nama}\nUPT: {upt}\n\n⚠️ *Belum Input:* {list_missing}\n\nMohon segera lengkapi data hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_" }}</textarea>
                    <div class="small text-muted mt-1" style="font-size: 11px;">
                        Variabel: <code>{salam}</code>, <code>{nama}</code>, <code>{upt}</code>, <code>{jam_mulai}</code>, <code>{jam_selesai}</code>, <code>{list_missing}</code>
                        <div class="text-primary mt-1">
                            <i class="fas fa-info-circle me-1"></i>Tersedia fitur Spintax acak: <code>{Opsi 1|Opsi 2|Opsi 3}</code> untuk variasi kalimat di bagian mana saja.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button type="submit" formaction="{{ route('wa.gateway.test') }}" name="action" value="send_morning" class="btn btn-outline-warning btn-sm" onclick="return confirm('Jalankan pengiriman reminder pagi ke operator sekarang?');">
                        <i class="fas fa-paper-plane me-1"></i>Kirim Sekarang
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-save me-1"></i>Simpan Aturan Pagi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kartu 2: Monitoring Siang -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-clipboard-check text-info me-2"></i>2. Monitoring Siang (Rekap Progres UPT)
                    </h6>
                    <small class="text-muted">Dikirim ke grup WhatsApp untuk memantau UPT yang belum melengkapi data. <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Otomatis dilewati jika semua UPT sudah lengkap.</span></small>
                </div>
                <span class="badge bg-info-subtle text-info border px-2 py-1">Setiap Hari</span>
            </div>

            <form action="{{ route('wa.schedules.builtin.update') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="afternoon">
                
                <div class="row g-2 mb-2 align-items-center">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-0">Jam Kirim Otomatis</label>
                    </div>
                    <div class="col-sm-7">
                        <input type="time" name="jam_reminder_siang" class="form-control form-control-sm"
                            value="{{ substr($waSettings->jam_reminder_siang ?? '12:00:00', 0, 5) }}" required>
                        <small class="text-muted" style="font-size: 11px;">Jendela otomatis: Jam kirim s/d 17:30 WIB</small>
                    </div>
                </div>

                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-0">Target Grup Tujuan</label>
                    </div>
                    <div class="col-sm-7">
                        <select name="group_id" class="form-select form-select-sm" required>
                            @forelse($groups as $grp)
                                <option value="{{ $grp->group_id }}" {{ ($waSettings->group_id ?? '') == $grp->group_id ? 'selected' : '' }}>
                                    {{ $grp->nama_grup }} ({{ Str::limit($grp->group_id, 20) }})
                                </option>
                            @empty
                                <option value="{{ $waSettings->group_id ?? '' }}">Grup Utama ({{ $waSettings->group_id ?? 'Belum ada grup' }})</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Template Pesan Rekap UPT</label>
                    <textarea name="template_siang" class="form-control form-control-sm font-monospace" rows="4" required>{{ $waSettings->template_siang ?: "📊 *LAPORAN MONITORING INPUT DATA*\nTanggal: {tanggal}\nWaktu: {waktu} WIB\n\n{list_belum_input}\n\n_Sultan Banten Ditjenpas_" }}</textarea>
                    <div class="small text-muted mt-1" style="font-size: 11px;">
                        Variabel khusus: <code>{tanggal}</code>, <code>{waktu}</code>, <code>{list_belum_input}</code>, <code>{total_belum}</code>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button type="submit" formaction="{{ route('wa.gateway.test') }}" name="action" value="send_afternoon" class="btn btn-outline-info btn-sm" onclick="return confirm('Kirim rekap laporan siang ke grup sekarang?');">
                        <i class="fas fa-paper-plane me-1"></i>Kirim Sekarang
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-save me-1"></i>Simpan Aturan Siang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================= BAGIAN 2: JADWAL LAPORAN TERPADU & KUSTOM ================================= -->
<div class="content-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold fs-6 mb-1">
                <i class="fas fa-calendar-alt text-primary me-2"></i>3. Jadwal Laporan Terpadu Harian &amp; Kustom
            </h5>
            <small class="text-muted">Pengiriman laporan komprehensif harian (penghuni, overstaying, integrasi, rincian bebas harian) ke grup dan pimpinan.</small>
        </div>
        @if(auth()->user()->canManage('wa_schedule'))
        <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddSchedule">
            <i class="fas fa-plus-circle me-1"></i>Tambah Jadwal Laporan
        </button>
        @else
        <span class="badge bg-secondary px-3 py-2">
            <i class="fas fa-eye me-1"></i> Mode Lihat Saja
        </span>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Nama Laporan</th>
                    <th>Target Pengiriman</th>
                    <th>Jam Kirim</th>
                    <th>Frekuensi</th>
                    <th>Terakhir Dikirim</th>
                    <th class="text-center" style="width: 100px;">Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schedules as $i => $s)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">
                        {{ $s->nama_laporan }}
                        @if($s->kirim_ke_pimpinan)
                            <span class="badge bg-success-subtle text-success border ms-1" style="font-size: 10px;"><i class="fas fa-user-tie me-1"></i>Ke Pimpinan</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($s->group_id) && $s->group_id !== '-')
                            <code class="small text-primary">{{ Str::limit($s->group_id, 25) }}</code>
                        @else
                            <span class="text-muted small">Hanya Pimpinan</span>
                        @endif
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ substr($s->jam_kirim, 0, 5) }} WIB</span></td>
                    <td>
                        @php
                            $hariLabels = [
                                'daily' => 'Setiap Hari',
                                'weekdays' => 'Hari Kerja (Sen-Jum)',
                                'monday' => 'Senin',
                                'tuesday' => 'Selasa',
                                'wednesday' => 'Rabu',
                                'thursday' => 'Kamis',
                                'friday' => 'Jumat',
                                'saturday' => 'Sabtu',
                                'sunday' => 'Minggu',
                            ];
                        @endphp
                        <span class="badge bg-info text-dark">{{ $hariLabels[strtolower($s->hari)] ?? ucfirst($s->hari) }}</span>
                    </td>
                    <td class="small text-muted">{{ $s->last_sent ? $s->last_sent->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-center">
                        @if(auth()->user()->canManage('wa_schedule'))
                        <form action="{{ route('wa.schedule.toggle', $s->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs py-0 px-2 badge {{ $s->aktif ? 'bg-success' : 'bg-secondary' }}" style="border:none;" title="Klik untuk ubah status">
                                {{ $s->aktif ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                        @else
                        <span class="badge {{ $s->aktif ? 'bg-success' : 'bg-secondary' }}">{{ $s->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(auth()->user()->canManage('wa_schedule'))
                        <div class="btn-group btn-group-sm">
                            <!-- Send Now -->
                            <form action="{{ route('wa.schedule.sendNow', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim laporan ini sekarang?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm" title="Kirim Sekarang">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            <!-- Edit -->
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $s->id }}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- Delete -->
                            <form action="{{ route('wa.schedule.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal laporan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        @else
                        <span class="text-muted small"><i class="fas fa-lock text-secondary"></i></span>
                        @endif

                        <!-- Modal Edit Schedule -->
                        <div class="modal fade" id="modalEdit{{ $s->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered text-start">
                                <div class="modal-content">
                                    <div class="modal-header py-2 bg-primary text-white">
                                        <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Jadwal Laporan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('wa.schedule.update', $s->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Nama Laporan <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_laporan" class="form-control form-control-sm" value="{{ $s->nama_laporan }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Grup Tujuan WhatsApp</label>
                                                    <select name="group_id" class="form-select form-select-sm">
                                                        <option value="-">-- Tidak Kirim ke Grup --</option>
                                                        @foreach($groups as $grp)
                                                            <option value="{{ $grp->group_id }}" {{ $s->group_id == $grp->group_id ? 'selected' : '' }}>
                                                                {{ $grp->nama_grup }} ({{ Str::limit($grp->group_id, 25) }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-semibold">Jam Kirim <span class="text-danger">*</span></label>
                                                    <input type="time" name="jam_kirim" class="form-control form-control-sm" value="{{ substr($s->jam_kirim, 0, 5) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-semibold">Frekuensi Hari</label>
                                                    <select name="hari" class="form-select form-select-sm">
                                                        <option value="daily" {{ $s->hari == 'daily' ? 'selected' : '' }}>Setiap Hari (Daily)</option>
                                                        <option value="weekdays" {{ $s->hari == 'weekdays' ? 'selected' : '' }}>Hari Kerja (Senin-Jumat)</option>
                                                        <option value="monday" {{ $s->hari == 'monday' ? 'selected' : '' }}>Senin Saja</option>
                                                        <option value="friday" {{ $s->hari == 'friday' ? 'selected' : '' }}>Jumat Saja</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 d-flex align-items-center">
                                                    <div class="form-check mt-3">
                                                        <input class="form-check-input" type="checkbox" name="kirim_ke_pimpinan" id="edit_pimpinan_{{ $s->id }}" {{ $s->kirim_ke_pimpinan ? 'checked' : '' }}>
                                                        <label class="form-check-label small fw-semibold" for="edit_pimpinan_{{ $s->id }}">
                                                            Kirim juga ke Pimpinan
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Template Pesan Laporan</label>
                                                <textarea name="template_pesan" class="form-control form-control-sm font-monospace" rows="8">{{ $s->template_pesan }}</textarea>
                                                <small class="text-muted" style="font-size: 11px;">Gunakan tag dari daftar tag variabel di bawah ini.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer py-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Belum ada jadwal laporan yang dibuat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ================================= BAGIAN 3: KATALOG TAG VARIABEL INTERAKTIF ================================= -->
<div class="content-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-2 border-bottom" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#katalogTagCollapse" aria-expanded="false" aria-controls="katalogTagCollapse">
        <div>
            <h6 class="fw-bold mb-1">
                <i class="fas fa-tags text-primary me-2"></i>Daftar Tag Variabel untuk Template Pesan
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" style="font-size: 11px;">
                    {{ isset($variables) ? count($variables) : '60+' }} Tag
                </span>
            </h6>
            <small class="text-muted">Gunakan tag ini di dalam template pesan Anda. Klik untuk membuka atau menutup daftar lengkap.</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-primary border px-3 py-2 d-none d-md-inline-block">
                <i class="fas fa-mouse-pointer me-1"></i>Klik kotak tag untuk menyalin
            </span>
            <button class="btn btn-sm btn-outline-primary px-3" type="button" data-bs-toggle="collapse" data-bs-target="#katalogTagCollapse" aria-expanded="false" aria-controls="katalogTagCollapse">
                <i class="fas fa-chevron-down me-1" id="iconKatalogTag"></i>
                <span id="textKatalogTag">Buka Daftar Tag</span>
            </button>
        </div>
    </div>

    <div class="collapse" id="katalogTagCollapse">
        <div class="pt-3">
            @if (isset($groupedVariables) && is_array($groupedVariables))
                @foreach ($groupedVariables as $category => $items)
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                        <span class="fw-bold text-dark small">{{ $category }}</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-2" style="font-size: 10px;">{{ count($items) }} variabel</span>
                    </div>
                    <div class="row g-2">
                        @foreach ($items as $tag => $desc)
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="p-2 border rounded bg-light h-100 d-flex flex-column justify-content-between shadow-sm-hover" style="cursor: pointer; transition: all 0.2s;" onclick="copyWaTag('{{ $tag }}')" title="Klik untuk salin tag &#123;{{ $tag }}&#125;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <code class="fw-bold text-primary">&#123;{{ $tag }}&#125;</code>
                                    <i class="far fa-copy text-muted small"></i>
                                </div>
                                <div class="small text-muted mt-1" style="font-size: 11px; line-height: 1.3;">{{ $desc }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @else
                <div class="row g-2">
                    @foreach ($variables as $tag => $desc)
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="p-2 border rounded bg-light h-100 d-flex flex-column justify-content-between shadow-sm-hover" style="cursor: pointer; transition: all 0.2s;" onclick="copyWaTag('{{ $tag }}')" title="Klik untuk salin tag &#123;{{ $tag }}&#125;">
                            <div class="d-flex justify-content-between align-items-center">
                                <code class="fw-bold text-primary">&#123;{{ $tag }}&#125;</code>
                                <i class="far fa-copy text-muted small"></i>
                            </div>
                            <div class="small text-muted mt-1" style="font-size: 11px; line-height: 1.3;">{{ $desc }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Add Schedule -->
<div class="modal fade" id="modalAddSchedule" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Jadwal Laporan WhatsApp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('wa.schedule.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Laporan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_laporan" class="form-control form-control-sm" placeholder="Contoh: Laporan Terpadu Sore" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Grup Tujuan</label>
                            <select name="group_id" class="form-select form-select-sm">
                                <option value="-">-- Tidak Kirim ke Grup --</option>
                                @foreach($groups as $grp)
                                    <option value="{{ $grp->group_id }}" {{ $grp->is_default ? 'selected' : '' }}>
                                        {{ $grp->nama_grup }} ({{ Str::limit($grp->group_id, 25) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jam Kirim <span class="text-danger">*</span></label>
                            <input type="time" name="jam_kirim" class="form-control form-control-sm" value="16:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Frekuensi Hari</label>
                            <select name="hari" class="form-select form-select-sm">
                                <option value="daily">Setiap Hari (Daily)</option>
                                <option value="weekdays">Hari Kerja (Senin-Jumat)</option>
                                <option value="monday">Senin Saja</option>
                                <option value="friday">Jumat Saja</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="kirim_ke_pimpinan" id="add_kirim_pimpinan" checked>
                                <label class="form-check-label small fw-semibold" for="add_kirim_pimpinan">
                                    Kirim juga ke Pimpinan
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Template Pesan</label>
                        <textarea name="template_pesan" class="form-control form-control-sm font-monospace" rows="8">{{ $defaultTemplate }}</textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Tambah Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Panduan Cron Server -->
<div class="modal fade" id="modalCronGuide" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-terminal me-2"></i>Panduan Baris Cron Jobs Server (cPanel / VPS)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Agar pengiriman laporan berjalan otomatis tepat waktu, pastikan baris perintah berikut aktif di menu <strong>Cron Jobs</strong> cPanel server Anda:
                </p>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Perintah Crontab cPanel (Frekuensi: * * * * *):</label>
                    <div class="p-2 bg-dark text-light rounded font-monospace small d-flex justify-content-between align-items-center">
                        <code>cd /home/ditjenpa/sultan_app &amp;&amp; /usr/local/bin/php artisan wa:cron run &gt; /dev/null 2&gt;&amp;1</code>
                        <button class="btn btn-sm btn-outline-light py-0 px-2" onclick="navigator.clipboard.writeText('cd /home/ditjenpa/sultan_app && /usr/local/bin/php artisan wa:cron run > /dev/null 2>&1'); alert('Perintah Cron disalin!');">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>Eksekusi otomatis dilengkapi <strong>Anti-Loop Protection</strong> dan <strong>Cache Lock</strong> sehingga laporan dijamin tidak akan terkirim ganda dalam 1 hari.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="copyToast" class="toast align-items-center text-bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body py-2">
                <i class="fas fa-check-circle text-success me-2"></i>Tag <code class="text-warning" id="copiedTagText"></code> disalin ke clipboard!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
function copyWaTag(tag) {
    const textToCopy = '{' + tag + '}';
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textToCopy);
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = textToCopy;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Gagal menyalin: ', err);
        }
        document.body.removeChild(textarea);
    }

    const toastEl = document.getElementById('copyToast');
    if (toastEl && typeof bootstrap !== 'undefined') {
        const tagTextEl = document.getElementById('copiedTagText');
        if (tagTextEl) {
            tagTextEl.textContent = textToCopy;
        }
        const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
        toast.show();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const katalogTagEl = document.getElementById('katalogTagCollapse');
    if (katalogTagEl) {
        katalogTagEl.addEventListener('show.bs.collapse', function () {
            const icon = document.getElementById('iconKatalogTag');
            const text = document.getElementById('textKatalogTag');
            if (icon) icon.className = 'fas fa-chevron-up me-1';
            if (text) text.textContent = 'Tutup Daftar Tag';
        });
        katalogTagEl.addEventListener('hide.bs.collapse', function () {
            const icon = document.getElementById('iconKatalogTag');
            const text = document.getElementById('textKatalogTag');
            if (icon) icon.className = 'fas fa-chevron-down me-1';
            if (text) text.textContent = 'Buka Daftar Tag';
        });
    }
});
</script>
@endsection
