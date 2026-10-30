<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiNarapidanaTujuan extends Model
{
    use HasFactory;

    protected $table = 'mutasi_narapidana_tujuan';

    protected $fillable = [
        'mutasi_id',
        'upt_tujuan_id',
        'jumlah_disetujui_narkotika',
        'jumlah_disetujui_pidum',
        'jumlah_disetujui_korupsi',
        'total_disetujui',
        'file_laporan_pemindahan',
        'tanggal_pemindahan',
        'file_laporan_penerimaan',
        'tanggal_penerimaan',
        'file_bast',
        'nomor_bast',
        'tanggal_bast',
        'tanggal_pelaksanaan',
        'catatan_pelaksanaan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pemindahan' => 'date',
            'tanggal_penerimaan' => 'date',
            'tanggal_bast' => 'date',
            'tanggal_pelaksanaan' => 'date',
            'jumlah_disetujui_narkotika' => 'integer',
            'jumlah_disetujui_pidum' => 'integer',
            'jumlah_disetujui_korupsi' => 'integer',
            'total_disetujui' => 'integer',
        ];
    }

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(MutasiNarapidana::class, 'mutasi_id');
    }

    public function uptTujuan(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_tujuan_id');
    }

    public function hasLaporanPemindahan(): bool
    {
        return ! empty($this->file_laporan_pemindahan);
    }

    public function hasLaporanPenerimaan(): bool
    {
        return ! empty($this->file_laporan_penerimaan);
    }

    public function hasBast(): bool
    {
        return ! empty($this->file_bast);
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai' || $this->hasBast();
    }
}
