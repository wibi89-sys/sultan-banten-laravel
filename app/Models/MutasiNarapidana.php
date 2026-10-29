<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiNarapidana extends Model
{
    use HasFactory;

    protected $table = 'mutasi_narapidana';

    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'upt_asal_id',
        'kategori_mutasi',
        'keterangan',
        'jumlah_narkotika',
        'jumlah_pidum',
        'jumlah_korupsi',
        'total_usulan',
        'file_surat_usulan',
        'file_asesmen',
        'file_bap',
        'file_reg_f',
        'file_lampiran_wbp',
        'status',
        'upt_tujuan_id',
        'nomor_surat_kanwil',
        'tanggal_surat_kanwil',
        'file_persetujuan_kanwil',
        'catatan_kanwil',
        'jumlah_disetujui_narkotika',
        'jumlah_disetujui_pidum',
        'jumlah_disetujui_korupsi',
        'total_disetujui',
        'diverifikasi_oleh',
        'tanggal_verifikasi',
        'tanggal_pelaksanaan',
        'file_bast',
        'catatan_pelaksanaan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_surat_kanwil' => 'date',
            'tanggal_pelaksanaan' => 'date',
            'tanggal_verifikasi' => 'datetime',
            'jumlah_narkotika' => 'integer',
            'jumlah_pidum' => 'integer',
            'jumlah_korupsi' => 'integer',
            'total_usulan' => 'integer',
            'jumlah_disetujui_narkotika' => 'integer',
            'jumlah_disetujui_pidum' => 'integer',
            'jumlah_disetujui_korupsi' => 'integer',
            'total_disetujui' => 'integer',
        ];
    }

    public function uptAsal(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_asal_id');
    }

    public function uptTujuan(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_tujuan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'diajukan' => 'Menunggu Verifikasi Kanwil',
            'disetujui' => 'Disetujui Kanwil',
            'perlu_perbaikan' => 'Perlu Perbaikan',
            'ditolak' => 'Ditolak Kanwil',
            'selesai' => 'Selesai (BAST Terbit)',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'diajukan' => 'bg-warning text-dark',
            'disetujui' => 'bg-primary text-white',
            'perlu_perbaikan' => 'bg-info text-white',
            'ditolak' => 'bg-danger text-white',
            'selesai' => 'bg-success text-white',
            default => 'bg-secondary text-white',
        };
    }

    public function getKategoriBadgeClassAttribute(): string
    {
        return match ($this->kategori_mutasi) {
            'Keamanan' => 'badge-danger bg-danger',
            'Pembinaan' => 'badge-info bg-info text-dark',
            'Overkapasitas' => 'badge-warning bg-warning text-dark',
            default => 'badge-secondary bg-secondary',
        };
    }
}
