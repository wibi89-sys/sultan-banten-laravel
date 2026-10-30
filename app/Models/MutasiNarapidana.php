<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'file_laporan_pemindahan',
        'tanggal_pemindahan',
        'catatan_pemindahan',
        'file_laporan_penerimaan',
        'tanggal_penerimaan',
        'catatan_penerimaan',
        'nomor_bast',
        'tanggal_bast',
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
            'tanggal_pemindahan' => 'date',
            'tanggal_penerimaan' => 'date',
            'tanggal_bast' => 'date',
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

    public function tujuans(): HasMany
    {
        return $this->hasMany(MutasiNarapidanaTujuan::class, 'mutasi_id');
    }

    public function isMultiTujuan(): bool
    {
        return $this->tujuans->count() > 1;
    }

    public function getDaftarTujuanTextAttribute(): string
    {
        if ($this->tujuans->isNotEmpty()) {
            return $this->tujuans->map(function ($t) {
                $nama = $t->uptTujuan?->nama_upt ?? 'UPT';

                return "{$nama} ({$t->total_disetujui} Orang)";
            })->implode(', ');
        }

        return $this->uptTujuan?->nama_upt ?? '-';
    }

    public function isUserRelated(User $user): bool
    {
        if ($user->isAdmin() || empty($user->upt_id)) {
            return true;
        }

        $uptId = (int) $user->upt_id;
        if ((int) $this->upt_asal_id === $uptId || (int) $this->upt_tujuan_id === $uptId) {
            return true;
        }

        return $this->tujuans->contains('upt_tujuan_id', $uptId);
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
        if ($this->status === 'disetujui' && $this->tujuans->count() > 1) {
            $selesaiCount = $this->tujuans->filter(fn ($t) => $t->isSelesai())->count();
            if ($selesaiCount > 0 && $selesaiCount < $this->tujuans->count()) {
                return "Sebagian Selesai ({$selesaiCount}/{$this->tujuans->count()} BAST)";
            }
        }

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

    public function hasLaporanPemindahan(?int $tujuanId = null): bool
    {
        if ($tujuanId) {
            $t = $this->tujuans->firstWhere('id', $tujuanId);

            return $t ? $t->hasLaporanPemindahan() : false;
        }

        return ! empty($this->file_laporan_pemindahan) || $this->tujuans->contains(fn ($t) => $t->hasLaporanPemindahan());
    }

    public function hasLaporanPenerimaan(?int $tujuanId = null): bool
    {
        if ($tujuanId) {
            $t = $this->tujuans->firstWhere('id', $tujuanId);

            return $t ? $t->hasLaporanPenerimaan() : false;
        }

        return ! empty($this->file_laporan_penerimaan) || $this->tujuans->contains(fn ($t) => $t->hasLaporanPenerimaan());
    }

    public function hasBast(?int $tujuanId = null): bool
    {
        if ($tujuanId) {
            $t = $this->tujuans->firstWhere('id', $tujuanId);

            return $t ? $t->hasBast() : false;
        }

        return ! empty($this->file_bast) || $this->tujuans->contains(fn ($t) => $t->hasBast());
    }
}
