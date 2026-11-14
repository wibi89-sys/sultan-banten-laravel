<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SidangTppKanwilMateriPendapat extends Model
{
    use HasFactory;

    protected $table = 'sidang_tpp_kanwil_materi_pendapat';

    protected $fillable = [
        'sidang_tpp_kanwil_materi_id',
        'sidang_tpp_kanwil_anggota_id',
        'status_pendapat',
        'catatan_pendapat',
    ];

    public function materi(): BelongsTo
    {
        return $this->belongsTo(SidangTppKanwilMateri::class, 'sidang_tpp_kanwil_materi_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(SidangTppKanwilAnggota::class, 'sidang_tpp_kanwil_anggota_id');
    }

    public function getPendapatBadgeAttribute(): string
    {
        return match ($this->status_pendapat) {
            'setuju' => '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>Setuju</span>',
            'setuju_catatan' => '<span class="badge bg-info text-white"><i class="fas fa-comment-dots me-1"></i>Setuju Catatan</span>',
            'tolak' => '<span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>Tolak</span>',
            'tunda' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Tunda</span>',
            default => '<span class="badge bg-secondary text-white"><i class="fas fa-hourglass-start me-1"></i>Belum Mengisi</span>',
        };
    }
}
