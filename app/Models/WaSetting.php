<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WaSetting extends Model
{
    protected $table = 'wa_settings';

    protected $fillable = [
        'gateway_name',
        'api_key',
        'api_url',
        'account_sid',
        'sender_number',
        'group_id',
        'group_id_laporan',
        'jam_reminder_pagi',
        'jam_reminder_siang',
        'aktif',
        'jeda_kirim',
        'daftar_salam',
        'template_pagi',
        'template_siang',
        'template_sidang_tpp',
        'group_id_tpp',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public static function getFirst(): ?self
    {
        return static::find(1) ?? static::first();
    }

    /**
     * Memastikan kolom template_sidang_tpp dan group_id_tpp tersedia di database
     * untuk mencegah error jika migrasi belum sempat dieksekusi di server hosting.
     */
    public static function ensureTppColumnsExist(): void
    {
        try {
            if (Schema::hasTable('wa_settings')) {
                if (! Schema::hasColumn('wa_settings', 'template_sidang_tpp')) {
                    Schema::table('wa_settings', function (Blueprint $table) {
                        if (! Schema::hasColumn('wa_settings', 'template_sidang_tpp')) {
                            $table->text('template_sidang_tpp')->nullable();
                        }
                        if (! Schema::hasColumn('wa_settings', 'group_id_tpp')) {
                            $table->string('group_id_tpp', 100)->nullable();
                        }
                    });
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WaSetting auto-migration warning: '.$e->getMessage());
        }
    }
}
