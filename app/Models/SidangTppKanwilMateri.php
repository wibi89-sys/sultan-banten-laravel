<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SidangTppKanwilMateri extends Model
{
    use HasFactory;

    protected $table = 'sidang_tpp_kanwil_materi';

    protected $fillable = [
        'sidang_tpp_kanwil_id',
        'urutan',
        'kategori_materi',
        'judul_materi',
        'deskripsi_materi',
        'mutasi_id',
        'upt_tujuan_rekomendasi_id',
        'narapidana_data',
        'klien_data',
        'file_bahan_sidang',
        'dokumen_tambahan',
        'rekomendasi_sidang',
        'catatan_rekomendasi',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'narapidana_data' => 'array',
            'klien_data' => 'array',
            'dokumen_tambahan' => 'array',
        ];
    }

    public function sidangKanwil(): BelongsTo
    {
        return $this->belongsTo(SidangTppKanwil::class, 'sidang_tpp_kanwil_id');
    }

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(MutasiNarapidana::class, 'mutasi_id');
    }

    public function uptTujuanRekomendasi(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_tujuan_rekomendasi_id');
    }

    public function pendapats(): HasMany
    {
        return $this->hasMany(SidangTppKanwilMateriPendapat::class, 'sidang_tpp_kanwil_materi_id');
    }

    public function isMutasiNarapidana(): bool
    {
        return $this->kategori_materi === 'mutasi_narapidana'
            || $this->kategori_materi === 'mutasi_keinginan_sendiri'
            || str_contains(strtolower($this->kategori_materi), 'mutasi')
            || ! empty($this->mutasi_id);
    }

    public function isMutasiKeinginanSendiri(): bool
    {
        return $this->kategori_materi === 'mutasi_keinginan_sendiri'
            || str_contains(strtolower($this->kategori_materi), 'sendiri')
            || str_contains(strtolower($this->kategori_materi), 'pribadi');
    }

    public function isIzinBerobat(): bool
    {
        return $this->kategori_materi === 'izin_berobat'
            || str_contains(strtolower($this->kategori_materi), 'berobat')
            || ! empty($this->narapidana_data['diagnosa_medis']);
    }

    public function isKlienBapas(): bool
    {
        return $this->kategori_materi === 'klien_bapas'
            || $this->kategori_materi === 'klien_umroh'
            || ! empty($this->klien_data);
    }

    public function getKategoriBadgeAttribute(): string
    {
        return match ($this->kategori_materi) {
            'mutasi_keinginan_sendiri' => '<span class="badge" style="background: rgba(111, 66, 193, 0.12); color: #6f42c1; border: 1px solid rgba(111, 66, 193, 0.3);"><i class="fas fa-user-friends me-1"></i>Mutasi Permintaan Sendiri</span>',
            'mutasi_narapidana' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-exchange-alt me-1"></i>Mutasi Narapidana</span>',
            'izin_berobat' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-notes-medical me-1"></i>Izin Berobat</span>',
            'klien_bapas', 'klien_umroh' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fas fa-passport me-1"></i>Klien Bapas</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-file-alt me-1"></i>Lainnya</span>',
        };
    }
}
