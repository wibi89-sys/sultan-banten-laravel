<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $table = 'ai_settings';

    protected $fillable = [
        'provider',
        'api_key',
        'model',
        'is_active',
        'nomor_surat_prefix',
        'yth_tujuan',
        'dari_pengirim',
        'hal_surat',
        'penandatangan_nama',
        'penandatangan_nip',
        'penandatangan_jabatan',
        'tampilkan_nip',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tampilkan_nip' => 'boolean',
        ];
    }

    public static function getFirst(): self
    {
        return static::first() ?? static::create([
            'provider' => 'gemini',
            'api_key' => null,
            'model' => 'gemini-1.5-flash',
            'is_active' => false,
            'nomor_surat_prefix' => 'WP.12-UM.01.01-',
            'yth_tujuan' => 'Kepala Bagian Tata Usaha dan Umum',
            'dari_pengirim' => 'Kepala Bidang Pelayanan dan Pembinaan',
            'hal_surat' => 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP',
            'penandatangan_nama' => 'Surya Dharma',
            'penandatangan_nip' => null,
            'penandatangan_jabatan' => 'Kepala Bidang Pelayanan dan Pembinaan',
            'tampilkan_nip' => false,
        ]);
    }
}
