<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SidangTppUsulan extends Model
{
    use HasFactory;

    protected $table = 'sidang_tpp_usulan';

    protected $fillable = [
        'sidang_tpp_id',
        'nama_wbp',
        'nomor_register',
        'perkara_pasal',
        'lama_pidana',
        'tanggal_ekspirasi',
        'tanggal_setengah_pidana',
        'tanggal_dua_pertiga',
        'jenis_usulan',
        'perlu_perhatian',
        'catatan_perhatian',
        'skor_sppn',
        'status_litmas',
        'bebas_register_f',
        'keputusan',
        'alasan_keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_ekspirasi' => 'date',
            'tanggal_setengah_pidana' => 'date',
            'tanggal_dua_pertiga' => 'date',
            'perlu_perhatian' => 'boolean',
            'bebas_register_f' => 'boolean',
            'skor_sppn' => 'float',
        ];
    }

    public function getPerhatianBadgeAttribute(): string
    {
        if ($this->perlu_perhatian) {
            $note = ! empty($this->catatan_perhatian) ? ' &bull; '.e($this->catatan_perhatian) : '';

            return '<span class="badge bg-danger text-white fw-bold shadow-sm" style="font-size: 11px;"><i class="fas fa-exclamation-circle me-1"></i>MENARIK PERHATIAN'.$note.'</span>';
        }

        return '';
    }

    public function sidangTpp(): BelongsTo
    {
        return $this->belongsTo(SidangTpp::class, 'sidang_tpp_id');
    }

    public function getKeputusanBadgeAttribute(): string
    {
        return match ($this->keputusan) {
            'setuju', 'disetujui' => '<span class="badge bg-success text-white"><i class="fas fa-check me-1"></i>Setuju</span>',
            'tidak_setuju', 'ditolak' => '<span class="badge bg-danger text-white"><i class="fas fa-times me-1"></i>Tidak Setuju</span>',
            'tunda', 'ditunda' => '<span class="badge bg-warning text-dark"><i class="fas fa-pause me-1"></i>Tunda</span>',
            default => '<span class="badge bg-secondary text-white"><i class="fas fa-question me-1"></i>Belum Putus</span>',
        };
    }

    public function getLitmasBadgeAttribute(): string
    {
        return match ($this->status_litmas) {
            'memenuhi_syarat' => '<span class="badge bg-success-subtle text-success border border-success-subtle">Memenuhi Syarat</span>',
            'tidak_memenuhi_syarat' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Tidak Memenuhi</span>',
            'proses' => '<span class="badge bg-info-subtle text-info border border-info-subtle">Dalam Proses</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Tidak Perlu</span>',
        };
    }
}
