<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SidangTpp extends Model
{
    use HasFactory;

    protected $table = 'sidang_tpp';

    protected $fillable = [
        'upt_id',
        'nomor_undangan',
        'tanggal_surat',
        'tanggal_sidang',
        'jam_mulai',
        'jam_selesai',
        'file_undangan',
        'agenda_kategori',
        'agenda_deskripsi',
        'agenda_sidang',
        'metode_sidang',
        'link_daring',
        'meeting_id',
        'passcode',
        'ketua_tpp',
        'sekretaris_tpp',
        'nama_pk_bapas',
        'anggota_tpp',
        'nomor_berita_acara',
        'file_berita_acara',
        'file_dokumentasi',
        'kanwil_hadir',
        'nama_pengawas_kanwil',
        'catatan_bintorwasdal',
        'nomor_laporan_kanwil',
        'tanggal_ttd_kakanwil',
        'file_laporan_kakanwil',
        'status_verifikasi_kanwil',
        'verified_by',
        'verified_at',
        'total_usulan',
        'total_setuju',
        'total_tunda',
        'total_tidak_setuju',
        'total_disetujui',
        'total_ditunda',
        'total_ditolak',
        'status_sidang',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_sidang' => 'date',
            'tanggal_ttd_kakanwil' => 'date',
            'verified_at' => 'datetime',
            'kanwil_hadir' => 'boolean',
            'anggota_tpp' => 'array',
            'total_usulan' => 'integer',
            'total_setuju' => 'integer',
            'total_tidak_setuju' => 'integer',
            'total_disetujui' => 'integer',
            'total_ditunda' => 'integer',
            'total_ditolak' => 'integer',
        ];
    }

    public function upt(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function usulan(): HasMany
    {
        return $this->hasMany(SidangTppUsulan::class, 'sidang_tpp_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function recalculateCounters(): void
    {
        $this->total_usulan = $this->usulan()->count();
        $this->total_setuju = $this->usulan()->whereIn('keputusan', ['setuju', 'disetujui'])->count();
        $this->total_tidak_setuju = $this->usulan()->whereIn('keputusan', ['tidak_setuju', 'ditolak'])->count();
        $this->total_tunda = $this->usulan()->whereIn('keputusan', ['tunda', 'ditunda'])->count();

        // backward compatibility aliases
        $this->total_disetujui = $this->total_setuju;
        $this->total_ditolak = $this->total_tidak_setuju;
        $this->total_ditunda = $this->total_tunda;
        $this->save();
    }

    public function getAgendaLabelAttribute(): string
    {
        return $this->agenda_sidang ?: ($this->agenda_deskripsi ?: ($this->agenda_kategori ?? 'Agenda Sidang TPP'));
    }

    public function getMetodeLabelAttribute(): string
    {
        return match ($this->metode_sidang) {
            'daring' => 'Daring Penuh (Virtual)',
            'hybrid' => 'Hybrid (Tatap Muka & Daring)',
            default => 'Tatap Muka',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status_sidang) {
            'dijadwalkan' => '<span class="badge bg-primary text-white"><i class="fas fa-calendar-check me-1"></i>Dijadwalkan</span>',
            'menunggu_hasil_upt' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Menunggu Input Hasil</span>',
            'menunggu_verifikasi_kanwil' => '<span class="badge bg-info text-white"><i class="fas fa-hourglass-half me-1"></i>Verifikasi Kanwil</span>',
            'selesai_ttd_kakanwil' => '<span class="badge bg-success text-white"><i class="fas fa-check-double me-1"></i>Selesai &amp; TTD Ka. Kanwil</span>',
            'dibatalkan' => '<span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>Dibatalkan</span>',
            default => '<span class="badge bg-secondary text-white">'.htmlspecialchars((string) $this->status_sidang).'</span>',
        };
    }

    public function getVerifikasiBadgeAttribute(): string
    {
        return match ($this->status_verifikasi_kanwil) {
            'sesuai', 'terverifikasi' => '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>Sesuai</span>',
            'tidak_sesuai', 'ditolak' => '<span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>Tidak Sesuai</span>',
            'perbaikan', 'perlu_perbaikan' => '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Perbaikan</span>',
            'menunggu_verifikasi' => '<span class="badge bg-primary text-white"><i class="fas fa-hourglass-half me-1"></i>Menunggu Verifikasi</span>',
            default => '<span class="badge bg-secondary text-white"><i class="fas fa-minus me-1"></i>Menunggu Sidang</span>',
        };
    }

    /**
     * Dapatkan daftar path foto dokumentasi JPG sidang TPP.
     *
     * @return array<string>
     */
    public function getDokumentasiListAttribute(): array
    {
        if (empty($this->file_dokumentasi)) {
            return [];
        }

        if (is_array($this->file_dokumentasi)) {
            return $this->file_dokumentasi;
        }

        $decoded = json_decode((string) $this->file_dokumentasi, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [trim((string) $this->file_dokumentasi)];
    }

    public function hasLaporanKanwil(): bool
    {
        return ! empty($this->file_laporan_kakanwil) && file_exists(public_path($this->file_laporan_kakanwil));
    }
}
