<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DataBebasHarian extends Model
{
    use HasFactory;

    protected $table = 'data_bebas_harian';

    protected $fillable = [
        'upt_id',
        'tanggal',
        'klasifikasi_pidana',
        'bebas_murni',
        'pb',
        'cb',
        'cmb',
        'asimilasi',
        'bdh',
        'mutasi_keluar',
        'ket_mutasi',
        'lainnya',
        'ket_lainnya',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'bebas_murni' => 'integer',
            'pb' => 'integer',
            'cb' => 'integer',
            'cmb' => 'integer',
            'asimilasi' => 'integer',
            'bdh' => 'integer',
            'mutasi_keluar' => 'integer',
            'lainnya' => 'integer',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function getTotalBebasAttribute(): int
    {
        return $this->bebas_murni + $this->pb + $this->cb + $this->cmb + $this->asimilasi + $this->bdh + $this->lainnya;
    }

    public function getTotalPengurangAttribute(): int
    {
        return $this->total_bebas + ($this->mutasi_keluar ?? 0);
    }

    /**
     * Memastikan kolom mutasi_keluar dan ket_mutasi tersedia di database (auto-heal).
     */
    public static function ensureMutasiColumnsExist(): void
    {
        try {
            if (! Schema::hasColumn('data_bebas_harian', 'mutasi_keluar')) {
                Schema::table('data_bebas_harian', function (Blueprint $table) {
                    $table->integer('mutasi_keluar')->default(0)->nullable()->after('bdh');
                });
            }
            if (! Schema::hasColumn('data_bebas_harian', 'ket_mutasi')) {
                Schema::table('data_bebas_harian', function (Blueprint $table) {
                    $table->string('ket_mutasi', 255)->nullable()->after('mutasi_keluar');
                });
            }
        } catch (\Throwable $e) {
            // Ignore error
        }
    }
}
