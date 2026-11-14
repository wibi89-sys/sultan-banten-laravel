<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TppKanwilSetting extends Model
{
    use HasFactory;

    protected $table = 'tpp_kanwil_setting';

    protected $fillable = [
        'kakanwil_nama',
        'kakanwil_nip',
        'kakanwil_pangkat',
        'kakanwil_jabatan',
        'kakanwil_ttd_image',
        'kanwil_stempel_image',
        'auto_embed_ttd',
        'nomor_nota_prefix',
        'nomor_risalah_prefix',
        'nomor_laporan_prefix',
        'tempat_sidang_default',
        'link_daring_default',
        'meeting_id_default',
        'passcode_default',
        'dasar_hukum_json',
    ];

    protected function casts(): array
    {
        return [
            'auto_embed_ttd' => 'boolean',
            'dasar_hukum_json' => 'array',
        ];
    }

    public static function defaultDasarHukum(): array
    {
        return [
            '1. Undang-Undang Nomor 22 Tahun 2022 tentang Pemasyarakatan Pasal 16;',
            '2. Peraturan Pemerintah Nomor 31 Tahun 1999 tentang Pembinaan Warga Binaan Pemasyarakatan;',
            '3. Keputusan Presiden Republik Indonesia Nomor 174 Tahun 1999 tentang Remisi Pasal 9;',
            '4. Peraturan Menteri Hukum dan HAM Republik Indonesia Nomor 35 Tahun 2018 tentang Revitalisasi Penyelenggaraan Pemasyarakatan;',
            '5. Keputusan Menteri Kehakiman Republik Indonesia Nomor M.01-PK.02.01 Tahun 1991 tentang Petunjuk Teknis Pemindahan Narapidana, Anak Didik, dan Tahanan;',
            '6. Peraturan Pemerintah Nomor 32 Tahun 1999 tentang Syarat dan Tata Cara Pelaksanaan Hak Warga Binaan Pemasyarakatan Pasal 17 ayat (1) dan (2);',
            '7. Keputusan Direktorat Jenderal Pemasyarakatan Nomor PAS-35.OT.02.02 Tahun 2018 tentang Standar Perawatan Kesehatan Rujukan bagi Narapidana, Tahanan dan Anak di UPT Pemasyarakatan;',
            '8. Undang-Undang Nomor 22 Tahun 2022 tentang Pemasyarakatan Pasal 57 dan Pasal 58 tentang Bimbingan Klien Pemasyarakatan;',
            '9. Peraturan Menteri Hukum dan HAM Republik Indonesia Nomor 7 Tahun 2022 tentang Syarat dan Tata Cara Pemberian Remisi, Asimilasi, CMK, PB, CMB, dan CB.',
        ];
    }

    public static function getFirst(): self
    {
        $setting = static::first();
        if ($setting) {
            return $setting;
        }

        // Cek apakah ada stempel dari instansi eksisting
        $instansi = Instansi::first();
        $stempel = $instansi?->stempel_image;
        $ttd = $instansi?->ttd_image;

        return static::create([
            'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
            'kakanwil_nip' => '197205141994031001',
            'kakanwil_pangkat' => 'Pembina Utama Madya (IV/d)',
            'kakanwil_jabatan' => 'Kepala Kantor Wilayah',
            'kakanwil_ttd_image' => $ttd,
            'kanwil_stempel_image' => $stempel,
            'auto_embed_ttd' => true,
            'nomor_nota_prefix' => 'WP.12.UM.01.01-',
            'nomor_risalah_prefix' => 'WP.12.PK.06.05-',
            'nomor_laporan_prefix' => 'WP.12.PK.06.06-',
            'tempat_sidang_default' => 'Ruang Rapat Utama Divisi Pemasyarakatan Kantor Wilayah Banten',
            'link_daring_default' => 'https://meet.google.com/tpp-kanwil-banten',
            'meeting_id_default' => '892 4102 7711',
            'passcode_default' => 'KANWIL2026',
            'dasar_hukum_json' => self::defaultDasarHukum(),
        ]);
    }
}
