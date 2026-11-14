<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KanwilPegawai extends Model
{
    use HasFactory;

    protected $table = 'kanwil_pegawai';

    protected $fillable = [
        'nama',
        'nip',
        'jabatan',
        'no_wa',
        'peran_tpp',
        'pangkat_golongan',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'urutan' => 'integer',
        ];
    }
}
