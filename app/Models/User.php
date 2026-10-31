<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'foto',
        'no_wa',
        'role',
        'upt_id',
        'akses_dashboard',
        'akses_penghuni',
        'akses_overstaying',
        'akses_integrasi',
        'akses_bebas_harian',
        'akses_sidang_tpp',
        'akses_mutasi',
        'akses_upt',
        'akses_user',
        'akses_instansi',
        'akses_laporan',
        'akses_pengumuman',
        'akses_wa_settings',
        'akses_wa_schedule',
        'akses_activity_log',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperatorWilayah(): bool
    {
        return $this->role === 'operator_wilayah';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isOperatorUpt(): bool
    {
        return $this->role === 'operator';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isKanwil(): bool
    {
        return $this->isAdmin() || $this->role === 'operator_wilayah' || empty($this->upt_id);
    }

    /**
     * Dapatkan level akses modul: 'none', 'view' (lihat saja), atau 'full' (akses penuh).
     */
    public function getAccessLevel(string $menu): string
    {
        if ($this->isAdmin()) {
            return 'full';
        }

        // Dashboard bersifat terbuka otomatis untuk semua pengguna (minimal view)
        if ($menu === 'dashboard') {
            return 'view';
        }

        $property = 'akses_'.$menu;
        $val = $this->{$property} ?? 'none';

        if ($val === true || $val === 1 || $val === '1' || $val === 'full') {
            return 'full';
        }

        if ($val === 'view' || $val === 'lihat') {
            return 'view';
        }

        return 'none';
    }

    /**
     * Cek apakah user memiliki akses ke modul tertentu.
     * $requiredLevel: 'view' (default) atau 'full'
     */
    public function hasAccess(string $menu, string $requiredLevel = 'view'): bool
    {
        // Admin selalu memiliki akses penuh ke seluruh modul, dashboard selalu terbuka
        if ($this->isAdmin() || $menu === 'dashboard') {
            return true;
        }

        $level = $this->getAccessLevel($menu);

        if ($requiredLevel === 'full') {
            return $level === 'full';
        }

        return $level === 'view' || $level === 'full';
    }

    /**
     * Apakah user boleh melihat (view/read-only) modul ini?
     */
    public function canView(string $menu): bool
    {
        return $this->hasAccess($menu, 'view');
    }

    /**
     * Apakah user memiliki hak penuh (create, update, delete) pada modul ini?
     */
    public function canManage(string $menu): bool
    {
        return $this->hasAccess($menu, 'full');
    }

    public function getAvatarHtml(int $size = 32): string
    {
        $name = htmlspecialchars($this->nama_lengkap ?? $this->username ?? 'User');
        $initial = strtoupper(substr($name, 0, 1));
        $fontSize = (int) floor($size * 0.45);

        if (! empty($this->foto) && file_exists(public_path($this->foto))) {
            return '<img src="'.asset($this->foto).'" alt="'.$name.'" style="width: '.$size.'px; height: '.$size.'px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        }

        return '<div style="width: '.$size.'px; height: '.$size.'px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: '.$fontSize.'px; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-shadow: 0 1px 2px rgba(0,0,0,0.2);">'.$initial.'</div>';
    }

    public function getUserUptFilter(): ?int
    {
        if ($this->isAdmin() || $this->isOperatorWilayah() || empty($this->upt_id)) {
            return null;
        }

        return (int) $this->upt_id;
    }
}
