<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TppNaskahKanwil extends Model
{
    use HasFactory;

    protected $table = 'tpp_naskah_kanwil';

    protected $fillable = [
        'sidang_tpp_id',
        'jenis_dokumen',
        'nomor_dokumen',
        'tanggal_dokumen',
        'judul_naskah',
        'materi_agenda',
        'konten_data',
        'pengawas_nama',
        'pengawas_nip',
        'pengawas_jabatan',
        'penandatangan_nama',
        'penandatangan_nip',
        'penandatangan_jabatan',
        'tampilkan_nip',
        'metode_ttd',
        'kode_verifikasi',
        'file_word',
        'file_pdf',
        'file_signed',
        'status',
        'created_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'konten_data' => 'array',
            'tampilkan_nip' => 'boolean',
            'tanggal_dokumen' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function sidangTpp(): BelongsTo
    {
        return $this->belongsTo(SidangTpp::class, 'sidang_tpp_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate kode verifikasi unik (32 karakter acak).
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = 'SB-'.strtoupper(Str::random(12));
        } while (static::where('kode_verifikasi', $code)->exists());

        return $code;
    }

    /**
     * Dapatkan URL verifikasi publik untuk QR Code.
     */
    public function getPublicVerificationUrl(): string
    {
        return route('verifikasi-dokumen.show', $this->kode_verifikasi);
    }
}
