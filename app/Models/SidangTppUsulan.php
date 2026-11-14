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
        'subjek_tipe',
        'upt_tujuan_id',
        'alasan_pemindahan',
        'penjamin_nama',
        'penjamin_hubungan',
        'penjamin_alamat',
        'diagnosa_medis',
        'dokter_penanggung_jawab',
        'rs_rujukan_nama',
        'rs_rujukan_kota',
        'berkas_khusus',
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
            'berkas_khusus' => 'array',
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

    public function uptTujuan(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_tujuan_id');
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

    public function getJenisUsulanBadgeAttribute(): string
    {
        $jenis = (string) ($this->jenis_usulan ?: 'PB');
        if (str_contains(strtoupper($jenis), 'BEROBAT')) {
            return '<span class="badge bg-danger text-white"><i class="fas fa-notes-medical me-1"></i>Izin Berobat Luar Kota</span>';
        }
        if (str_contains(strtoupper($jenis), 'MUTASI') || str_contains(strtoupper($jenis), 'PENEMPATAN') || str_contains(strtoupper($jenis), 'PINDAH')) {
            return '<span class="badge bg-info text-white"><i class="fas fa-exchange-alt me-1"></i>Mutasi (Pindah UPT)</span>';
        }
        if (strtoupper($jenis) === 'REGISTER F') {
            return '<span class="badge bg-dark text-white"><i class="fas fa-file-contract me-1"></i>Register F</span>';
        }
        if (strtoupper($jenis) === 'REMISI') {
            return '<span class="badge bg-warning text-dark"><i class="fas fa-award me-1"></i>Remisi</span>';
        }
        if (strtoupper($jenis) === 'ASIMILASI') {
            return '<span class="badge bg-secondary text-white">Asimilasi</span>';
        }

        return '<span class="badge bg-primary">'.e($jenis).'</span>';
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

    public function isMutasiNarapidana(): bool
    {
        return $this->subjek_tipe === 'mutasi_narapidana'
            || str_contains(strtolower((string) $this->jenis_usulan), 'mutasi')
            || str_contains(strtolower((string) $this->jenis_usulan), 'penempatan')
            || str_contains(strtolower((string) $this->jenis_usulan), 'pindah');
    }

    public function isIzinBerobat(): bool
    {
        return $this->subjek_tipe === 'izin_berobat'
            || str_contains(strtolower((string) $this->jenis_usulan), 'berobat');
    }
}
