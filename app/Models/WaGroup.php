<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaGroup extends Model
{
    protected $table = 'wa_groups';

    protected $fillable = [
        'nama_grup',
        'group_id',
        'keterangan',
        'aktif',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
