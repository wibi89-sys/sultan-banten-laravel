<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SidangTppKanwilAnggota extends Model
{
    use HasFactory;

    protected $table = 'sidang_tpp_kanwil_anggota';

    protected $fillable = [
        'sidang_tpp_kanwil_id',
        'pegawai_id',
        'user_id',
        'token_akses',
        'nama',
        'nip',
        'jabatan',
        'no_wa',
        'peran_sidang',
        'status_kehadiran',
        'keterangan_kehadiran',
        'waktu_kehadiran',
        'status_pendapat',
        'catatan_pendapat',
        'waktu_pendapat',
        'wa_sent_status',
        'wa_sent_at',
        'wa_error_message',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kehadiran' => 'datetime',
            'waktu_pendapat' => 'datetime',
            'wa_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SidangTppKanwilAnggota $anggota) {
            if (empty($anggota->token_akses)) {
                $anggota->token_akses = Str::random(40);
            }
        });
    }

    public function sidangKanwil(): BelongsTo
    {
        return $this->belongsTo(SidangTppKanwil::class, 'sidang_tpp_kanwil_id');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(KanwilPegawai::class, 'pegawai_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Dapatkan badge status kehadiran anggota.
     */
    public function getKehadiranBadgeAttribute(): string
    {
        return match ($this->status_kehadiran) {
            'hadir_luring' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-user-check me-1"></i>Hadir Luring</span>',
            'hadir_daring' => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fas fa-video me-1"></i>Hadir Daring</span>',
            'izin' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-envelope me-1"></i>Izin</span>',
            'sakit' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-hospital me-1"></i>Sakit</span>',
            'tidak_hadir' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-times me-1"></i>Tidak Hadir</span>',
            default => '<span class="badge bg-light text-muted border"><i class="fas fa-clock me-1"></i>Belum Konfirmasi</span>',
        };
    }

    /**
     * Dapatkan badge status pendapat sidang.
     */
    public function getPendapatBadgeAttribute(): string
    {
        return match ($this->status_pendapat) {
            'setuju' => '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Setuju</span>',
            'setuju_catatan' => '<span class="badge bg-info text-white"><i class="fas fa-check-double me-1"></i>Setuju dg Catatan</span>',
            'tolak' => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Tolak</span>',
            'tunda' => '<span class="badge bg-warning text-dark"><i class="fas fa-pause me-1"></i>Tunda</span>',
            default => '<span class="badge bg-secondary opacity-75"><i class="fas fa-minus me-1"></i>Belum Mengisi</span>',
        };
    }

    /**
     * Dapatkan badge status pengiriman WhatsApp.
     */
    public function getWaBadgeAttribute(): string
    {
        return match ($this->wa_sent_status) {
            'terkirim' => '<span class="badge bg-success" title="Terkirim: '.($this->wa_sent_at ? $this->wa_sent_at->format('d/m/Y H:i') : '-').'"><i class="fab fa-whatsapp me-1"></i>Terkirim</span>',
            'gagal' => '<span class="badge bg-danger" title="'.htmlspecialchars($this->wa_error_message ?? 'Gagal').'"><i class="fas fa-exclamation-circle me-1"></i>Gagal</span>',
            default => '<span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Belum Dikirim</span>',
        };
    }
}
