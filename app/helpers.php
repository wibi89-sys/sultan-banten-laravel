<?php

use App\Models\Instansi;

if (! function_exists('getOvercrowdingStatus')) {
    /**
     * Klasifikasi Tingkat Hunian dan Overcrowding Terkalibrasi (Banten Context)
     * - Tidak Overcrowding : <= 100%
     * - Overcrowding       : 100.1% - 150%
     * - Critical           : 150.1% - 299.9%
     * - Extreme            : >= 300%
     *
     * @return array{
     *     tingkat_hunian: float,
     *     persen_over: float,
     *     selisih: int,
     *     status: string,
     *     level: string,
     *     badge_class: string,
     *     badge_color: string,
     *     icon: string,
     *     keterangan: string
     * }
     */
    function getOvercrowdingStatus(int $isi, int $kapasitas): array
    {
        if ($kapasitas <= 0) {
            return [
                'tingkat_hunian' => 0.0,
                'persen_over' => 0.0,
                'selisih' => $isi,
                'status' => 'Belum Diatur',
                'level' => 'unknown',
                'badge_class' => 'bg-secondary text-white',
                'badge_color' => '#64748b',
                'icon' => 'fas fa-question-circle',
                'keterangan' => 'Kapasitas UPT belum ditentukan',
            ];
        }

        $tingkatHunian = round(($isi / $kapasitas) * 100, 1);
        $selisih = $isi - $kapasitas;
        $persenOver = max(0, round(($selisih / $kapasitas) * 100, 1));

        if ($tingkatHunian <= 100.0) {
            return [
                'tingkat_hunian' => $tingkatHunian,
                'persen_over' => 0.0,
                'selisih' => $selisih,
                'status' => 'Tidak Overcrowding',
                'level' => 'normal',
                'badge_class' => 'badge-overcrowding-normal',
                'badge_color' => '#10b981',
                'icon' => 'fas fa-check-circle',
                'keterangan' => $selisih < 0 ? 'Tersedia '.abs($selisih).' tempat' : 'Sesuai Kapasitas (100%)',
            ];
        }

        if ($tingkatHunian <= 150.0) {
            return [
                'tingkat_hunian' => $tingkatHunian,
                'persen_over' => $persenOver,
                'selisih' => $selisih,
                'status' => 'Overcrowding',
                'level' => 'overcrowding',
                'badge_class' => 'badge-overcrowding-warning',
                'badge_color' => '#f59e0b',
                'icon' => 'fas fa-exclamation-circle',
                'keterangan' => 'Overkapasitas +'.$selisih.' orang (+'.$persenOver.'%)',
            ];
        }

        if ($tingkatHunian < 300.0) {
            return [
                'tingkat_hunian' => $tingkatHunian,
                'persen_over' => $persenOver,
                'selisih' => $selisih,
                'status' => 'Critical Overcrowding',
                'level' => 'critical',
                'badge_class' => 'badge-overcrowding-critical',
                'badge_color' => '#ea580c',
                'icon' => 'fas fa-exclamation-triangle',
                'keterangan' => 'Kepadatan Kritis +'.$selisih.' orang (+'.$persenOver.'%)',
            ];
        }

        // >= 300%
        return [
            'tingkat_hunian' => $tingkatHunian,
            'persen_over' => $persenOver,
            'selisih' => $selisih,
            'status' => 'Extreme Overcrowding',
            'level' => 'extreme',
            'badge_class' => 'badge-overcrowding-extreme',
            'badge_color' => '#991b1b',
            'icon' => 'fas fa-fire-alt',
            'keterangan' => 'Kepadatan Ekstrem +'.$selisih.' orang (+'.$persenOver.'%)',
        ];
    }
}

if (! function_exists('renderOvercrowdingBadge')) {
    function renderOvercrowdingBadge(int $isi, int $kapasitas, bool $showPersen = true): string
    {
        $status = getOvercrowdingStatus($isi, $kapasitas);
        $persenText = $showPersen ? ' ('.number_format($status['tingkat_hunian'], 1, ',', '.').'%)' : '';

        return '<span class="badge '.$status['badge_class'].' px-2 py-1 shadow-xs">'
            .'<i class="'.$status['icon'].' me-1"></i>'
            .e($status['status']).$persenText
            .'</span>';
    }
}

if (! function_exists('getOverkapasitasColorClass')) {
    function getOverkapasitasColorClass($persen)
    {
        if ($persen < -50) {
            return 'bg-success text-white';
        }           // Sangat longgar
        if ($persen < -25) {
            return 'bg-success text-white';
        }           // Longgar
        if ($persen < 0) {
            return 'bg-info text-white';
        }                // Masih ada ruang
        if ($persen == 0) {
            return 'bg-warning text-dark';
        }             // Pas penuh
        if ($persen <= 20) {
            return 'bg-orange text-white';
        }            // Sedikit over
        if ($persen <= 50) {
            return 'bg-warning text-dark';
        }            // Over sedang
        if ($persen <= 100) {
            return 'bg-danger text-white';
        }           // Over tinggi

        return 'bg-dark-red text-white';                             // Sangat over
    }
}

if (! function_exists('formatIndonesianDate')) {
    function formatIndonesianDate($date, $includeDay = true)
    {
        if (! $date) {
            return '-';
        }
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        $ts = strtotime($date);
        $dayName = $days[(int) date('w', $ts)];
        $day = date('d', $ts);
        $month = $months[(int) date('m', $ts)];
        $year = date('Y', $ts);

        return $includeDay ? "$dayName, $day $month $year" : "$day $month $year";
    }
}

if (! function_exists('indonesianDate')) {
    function indonesianDate($date)
    {
        return formatIndonesianDate($date, false);
    }
}

if (! function_exists('formatDate')) {
    function formatDate($date)
    {
        if (empty($date)) {
            return '-';
        }
        $timestamp = strtotime($date);

        return date('d/m/Y', $timestamp);
    }
}

if (! function_exists('getUserUptFilter')) {
    function getUserUptFilter()
    {
        $user = auth()->user();

        return $user ? $user->getUserUptFilter() : null;
    }
}

if (! function_exists('getTimeRestriction')) {
    function getTimeRestriction()
    {
        $instansi = Instansi::getFirst();
        if (! $instansi) {
            return [
                'active' => false,
                'jam_mulai' => '08:00',
                'jam_selesai' => '11:00',
            ];
        }

        return [
            'active' => (bool) ($instansi->batas_jam_aktif ?? false),
            'jam_mulai' => substr($instansi->jam_mulai ?? '08:00:00', 0, 5),
            'jam_selesai' => substr($instansi->jam_selesai ?? '11:00:00', 0, 5),
        ];
    }
}

if (! function_exists('isInputTimeAllowed')) {
    function isInputTimeAllowed()
    {
        $user = auth()->user();
        if ($user && $user->role === 'admin') {
            return ['allowed' => true, 'message' => ''];
        }

        $instansi = Instansi::getFirst();
        if (! $instansi || empty($instansi->batas_jam_aktif)) {
            return ['allowed' => true, 'message' => ''];
        }

        $jamMulai = $instansi->jam_mulai ?? '08:00:00';
        $jamSelesai = $instansi->jam_selesai ?? '11:00:00';
        $currentTime = date('H:i:s');

        if ($currentTime >= $jamMulai && $currentTime <= $jamSelesai) {
            return ['allowed' => true, 'message' => ''];
        }

        $jamMulaiDisplay = date('H:i', strtotime($jamMulai));
        $jamSelesaiDisplay = date('H:i', strtotime($jamSelesai));

        return [
            'allowed' => false,
            'message' => "Input data hanya dapat dilakukan pada pukul {$jamMulaiDisplay} - {$jamSelesaiDisplay}. Saat ini pukul ".date('H:i'),
        ];
    }
}
