@extends('layouts.app')

@section('title', 'Jadwal Laporan WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-clock text-primary me-2"></i>Jadwal Laporan WhatsApp
@endsection

@section('content')
<!-- Cron Jobs Configuration Guide -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc; border-radius: 12px;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(59, 130, 246, 0.2); color: #60a5fa;">
                    <i class="fas fa-terminal fa-lg"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-white">Panduan Otomatisasi Cron Jobs Server (cPanel / VPS)</h6>
                    <small class="text-secondary">Agar pengiriman laporan terjadwal &amp; reminder WA harian berjalan otomatis tepat waktu</small>
                </div>
            </div>
            <span class="badge bg-primary px-3 py-2 rounded-pill"><i class="fas fa-clock me-1"></i>Frekuensi: Tiap Menit (* * * * *)</span>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 rounded h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-info"><i class="fas fa-star me-1"></i>Opsi 1: Standar Laravel Scheduler (Rekomendasi Utama)</span>
                    </div>
                    <small class="text-muted d-block mb-2">Memanggil scheduler Laravel terpadu via crontab server hosting.</small>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace bg-dark text-warning border-secondary" readonly value="* * * * * cd {{ base_path() }} &amp;&amp; /usr/local/bin/php artisan schedule:run >> /dev/null 2>&amp;1" id="cmdCron1">
                        <button class="btn btn-outline-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cmdCron1').value); alert('Perintah disalin ke clipboard!');"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-success"><i class="fas fa-bolt me-1"></i>Opsi 2: Langsung Command Artisan wa:cron</span>
                    </div>
                    <small class="text-muted d-block mb-2">Mengeksekusi langsung command `wa:cron run` tanpa task scheduler.</small>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace bg-dark text-warning border-secondary" readonly value="* * * * * /usr/local/bin/php {{ base_path('artisan') }} wa:cron run >> /dev/null 2>&amp;1" id="cmdCron2">
                        <button class="btn btn-outline-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cmdCron2').value); alert('Perintah disalin ke clipboard!');"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-warning"><i class="fas fa-link me-1"></i>Opsi 3: Script Bridge cron-wa.php (Kompatibel cPanel)</span>
                    </div>
                    <small class="text-muted d-block mb-2">Mengeksekusi file bridge `cron-wa.php run` di direktori proyek.</small>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace bg-dark text-warning border-secondary" readonly value="* * * * * /usr/local/bin/php {{ base_path('cron-wa.php') }} run >> /dev/null 2>&amp;1" id="cmdCron3">
                        <button class="btn btn-outline-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cmdCron3').value); alert('Perintah disalin ke clipboard!');"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-danger"><i class="fas fa-globe me-1"></i>Opsi 4: Web Cron / cURL URL</span>
                    </div>
                    <small class="text-muted d-block mb-2">Picu otomatis via HTTP URL (dengan parameter rahasia key &amp; action).</small>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace bg-dark text-warning border-secondary" readonly value="* * * * * curl -s &quot;{{ url('/cron-wa') }}?key=ditjenpas_banten_2026_secret&amp;action=run&quot; > /dev/null 2>&amp;1" id="cmdCron4">
                        <button class="btn btn-outline-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cmdCron4').value); alert('Perintah disalin ke clipboard!');"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 pt-3 border-top border-secondary text-secondary small d-flex align-items-center gap-2">
            <i class="fas fa-info-circle text-info"></i>
            <span><strong>Catatan Penting:</strong> Pastikan toggle master <strong>&quot;Aktifkan Notifikasi WhatsApp&quot;</strong> di menu <a href="{{ route('wa.settings') }}" class="text-info text-decoration-underline">Pengaturan WA</a> dalam posisi <strong>Aktif</strong> dan API Key Fonnte terisi valid agar proses pengiriman berjalan lancar.</span>
        </div>
    </div>
</div>

<div class="content-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i>Daftar Jadwal Pengiriman Laporan</h5>
            <p class="text-muted small mb-0">Kelola jadwal laporan otomatis ke grup WhatsApp atau daftar pimpinan</p>
        </div>
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddSchedule">
            <i class="fas fa-plus me-1"></i> Tambah Jadwal
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th>Nama Laporan</th>
                    <th>Target</th>
                    <th>Jam Kirim</th>
                    <th>Frekuensi</th>
                    <th>Pimpinan</th>
                    <th>Terakhir Dikirim</th>
                    <th>Status</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schedules as $i => $s)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $s->nama_laporan }}</td>
                    <td><code>{{ $s->group_id }}</code></td>
                    <td><span class="badge bg-light text-dark border">{{ substr($s->jam_kirim, 0, 5) }} WIB</span></td>
                    <td>
                        @php
                            $hariLabels = [
                                'daily' => 'Setiap Hari',
                                'weekdays' => 'Hari Kerja (Sen-Jum)',
                                'monday' => 'Senin Saja',
                                'tuesday' => 'Selasa Saja',
                                'wednesday' => 'Rabu Saja',
                                'thursday' => 'Kamis Saja',
                                'friday' => 'Jumat Saja',
                                'saturday' => 'Sabtu Saja',
                                'sunday' => 'Minggu Saja',
                            ];
                        @endphp
                        <span class="badge bg-info text-dark">{{ $hariLabels[strtolower($s->hari)] ?? ucfirst($s->hari) }}</span>
                    </td>
                    <td>
                        @if($s->kirim_ke_pimpinan)
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ya</span>
                        @else
                            <span class="badge bg-secondary">Tidak</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $s->last_sent ? $s->last_sent->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <form action="{{ route('wa.schedule.toggle', $s->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs py-0 px-2 badge {{ $s->aktif ? 'bg-success' : 'bg-secondary' }}" style="border:none; cursor:pointer;" title="Klik untuk ubah status" aria-label="Ubah Status Jadwal {{ $s->nama_laporan }}">
                                {{ $s->aktif ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <!-- Send Now -->
                            <form action="{{ route('wa.schedule.sendNow', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim laporan ini sekarang?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm" title="Kirim Sekarang" aria-label="Kirim Laporan {{ $s->nama_laporan }} Sekarang">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            <!-- Edit -->
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $s->id }}" title="Edit" aria-label="Edit Jadwal {{ $s->nama_laporan }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- Delete -->
                            <form action="{{ route('wa.schedule.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal laporan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus" aria-label="Hapus Jadwal {{ $s->nama_laporan }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit{{ $s->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered text-start">
                                <div class="modal-content">
                                    <div class="modal-header py-2 bg-primary text-white">
                                        <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Jadwal Laporan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
                                    </div>
                                    <form action="{{ route('wa.schedule.update', $s->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label for="edit_nama_laporan_{{ $s->id }}" class="form-label small fw-semibold">Nama Laporan <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_laporan" id="edit_nama_laporan_{{ $s->id }}" class="form-control form-control-sm" value="{{ $s->nama_laporan }}" aria-label="Nama Laporan" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit_target_{{ $s->id }}" class="form-label small fw-semibold">Target Penerima</label>
                                                    <select name="target_penerima" id="edit_target_{{ $s->id }}" class="form-select form-select-sm" aria-label="Target Penerima">
                                                        <option value="grup" {{ !$s->kirim_ke_pimpinan && $s->group_id !== '-' ? 'selected' : '' }}>Hanya Grup</option>
                                                        <option value="pimpinan" {{ $s->kirim_ke_pimpinan && $s->group_id === '-' ? 'selected' : '' }}>Hanya Pimpinan</option>
                                                        <option value="keduanya" {{ $s->kirim_ke_pimpinan && $s->group_id !== '-' ? 'selected' : '' }}>Grup & Pimpinan</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit_group_id_{{ $s->id }}" class="form-label small fw-semibold">Group ID</label>
                                                    <input type="text" name="group_id" id="edit_group_id_{{ $s->id }}" class="form-control form-control-sm" value="{{ $s->group_id }}" aria-label="Group ID">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="edit_jam_kirim_{{ $s->id }}" class="form-label small fw-semibold">Jam Kirim <span class="text-danger">*</span></label>
                                                    <input type="time" name="jam_kirim" id="edit_jam_kirim_{{ $s->id }}" class="form-control form-control-sm" value="{{ substr($s->jam_kirim, 0, 5) }}" aria-label="Jam Kirim" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="edit_hari_{{ $s->id }}" class="form-label small fw-semibold">Hari / Frekuensi</label>
                                                    <select name="hari" id="edit_hari_{{ $s->id }}" class="form-select form-select-sm" aria-label="Hari / Frekuensi">
                                                        <option value="daily" {{ $s->hari === 'daily' ? 'selected' : '' }}>Setiap Hari (Daily)</option>
                                                        <option value="weekdays" {{ $s->hari === 'weekdays' ? 'selected' : '' }}>Hari Kerja (Senin-Jumat)</option>
                                                        <option value="monday" {{ $s->hari === 'monday' ? 'selected' : '' }}>Senin Saja</option>
                                                        <option value="tuesday" {{ $s->hari === 'tuesday' ? 'selected' : '' }}>Selasa Saja</option>
                                                        <option value="wednesday" {{ $s->hari === 'wednesday' ? 'selected' : '' }}>Rabu Saja</option>
                                                        <option value="thursday" {{ $s->hari === 'thursday' ? 'selected' : '' }}>Kamis Saja</option>
                                                        <option value="friday" {{ $s->hari === 'friday' ? 'selected' : '' }}>Jumat Saja</option>
                                                        <option value="saturday" {{ $s->hari === 'saturday' ? 'selected' : '' }}>Sabtu Saja</option>
                                                        <option value="sunday" {{ $s->hari === 'sunday' ? 'selected' : '' }}>Minggu Saja</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_template_{{ $s->id }}" class="form-label small fw-semibold">Template Pesan</label>
                                                <textarea name="template_pesan" id="edit_template_{{ $s->id }}" class="form-control form-control-sm" rows="8" aria-label="Template Pesan">{{ $s->template_pesan }}</textarea>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="aktif" id="aktifEdit{{ $s->id }}" {{ $s->aktif ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="aktifEdit{{ $s->id }}">Jadwal Aktif</label>
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
                    <td colspan="9" class="text-center text-muted py-4">Belum ada jadwal laporan</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Available Variables Helper -->
<div class="content-card mb-4">
    <h6 class="fw-bold mb-3"><i class="fas fa-tags text-primary me-2"></i>Daftar Tag Variabel untuk Template Pesan</h6>
    <div class="row g-2">
        @foreach ($variables as $tag => $desc)
        <div class="col-md-3 col-sm-6">
            <div class="p-2 border rounded bg-light">
                <code class="fw-bold text-primary">&#123;{{ $tag }}&#125;</code>
                <div class="small text-muted" style="font-size: 11px;">{{ $desc }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Add Schedule -->
<div class="modal fade" id="modalAddSchedule" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Jadwal Laporan WhatsApp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
            </div>
            <form action="{{ route('wa.schedule.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add_nama_laporan" class="form-label small fw-semibold">Nama Laporan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_laporan" id="add_nama_laporan" class="form-control form-control-sm" placeholder="Contoh: Laporan Sore Harian" aria-label="Nama Laporan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="add_target_penerima" class="form-label small fw-semibold">Target Penerima</label>
                            <select name="target_penerima" id="add_target_penerima" class="form-select form-select-sm" aria-label="Target Penerima">
                                <option value="grup">Hanya Grup WhatsApp</option>
                                <option value="pimpinan">Hanya Daftar Pimpinan</option>
                                <option value="keduanya">Keduanya (Grup & Pimpinan)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_group_id" class="form-label small fw-semibold">Group ID</label>
                            <input type="text" name="group_id" id="add_group_id" class="form-control form-control-sm" placeholder="ID grup WhatsApp (jika kirim ke grup)" aria-label="Group ID">
                        </div>
                        <div class="col-md-3">
                            <label for="add_jam_kirim" class="form-label small fw-semibold">Jam Kirim <span class="text-danger">*</span></label>
                            <input type="time" name="jam_kirim" id="add_jam_kirim" class="form-control form-control-sm" value="16:00" aria-label="Jam Kirim" required>
                        </div>
                        <div class="col-md-3">
                            <label for="add_hari" class="form-label small fw-semibold">Hari / Frekuensi</label>
                            <select name="hari" id="add_hari" class="form-select form-select-sm" aria-label="Hari / Frekuensi">
                                <option value="daily">Setiap Hari (Daily)</option>
                                <option value="weekdays">Hari Kerja (Senin-Jumat)</option>
                                <option value="monday">Senin Saja</option>
                                <option value="tuesday">Selasa Saja</option>
                                <option value="wednesday">Rabu Saja</option>
                                <option value="thursday">Kamis Saja</option>
                                <option value="friday">Jumat Saja</option>
                                <option value="saturday">Sabtu Saja</option>
                                <option value="sunday">Minggu Saja</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_template_pesan" class="form-label small fw-semibold">Template Pesan</label>
                        <textarea name="template_pesan" id="add_template_pesan" class="form-control form-control-sm" rows="8" aria-label="Template Pesan">{{ $defaultTemplate }}</textarea>
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

<!-- Execution Logs -->
<div class="content-card">
    <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-history text-secondary me-2"></i>Log Pengiriman Jadwal Terakhir</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Jadwal Laporan</th>
                    <th>Status</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                <tr>
                    <td class="small text-muted">{{ date('d/m/Y H:i', strtotime($log->tanggal_kirim ?? 'now')) }}</td>
                    <td class="fw-semibold">{{ $log->nama_laporan }}</td>
                    <td>
                        <span class="badge {{ $log->status === 'success' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td class="small text-muted">{{ Str::limit($log->response, 80) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">Belum ada riwayat pengiriman jadwal</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
