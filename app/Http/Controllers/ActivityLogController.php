<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\UptRankingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    private $aksiLabels = [
        'login' => ['label' => 'Login',   'icon' => 'fa-sign-in-alt',        'color' => 'success'],
        'logout' => ['label' => 'Logout',  'icon' => 'fa-sign-out-alt',       'color' => 'secondary'],
        'tambah' => ['label' => 'Tambah',  'icon' => 'fa-plus',               'color' => 'primary'],
        'edit' => ['label' => 'Edit',    'icon' => 'fa-edit',               'color' => 'warning'],
        'hapus' => ['label' => 'Hapus',   'icon' => 'fa-trash',              'color' => 'danger'],
        'view' => ['label' => 'Lihat',   'icon' => 'fa-eye',                'color' => 'info'],
        'export' => ['label' => 'Export',  'icon' => 'fa-download',           'color' => 'success'],
        'import' => ['label' => 'Import',  'icon' => 'fa-upload',             'color' => 'info'],
        'setting' => ['label' => 'Setting', 'icon' => 'fa-cog',                'color' => 'secondary'],
        'error' => ['label' => 'Error',   'icon' => 'fa-exclamation-triangle', 'color' => 'danger'],
    ];

    private $modulLabels = [
        'auth' => 'Autentikasi',
        'penghuni' => 'Data Penghuni',
        'overstaying' => 'Data Overstaying',
        'integrasi' => 'Data Integrasi',
        'bebas_harian' => 'Bebas Harian',
        'upt' => 'Data UPT',
        'user' => 'Manajemen User',
        'instansi' => 'Pengaturan Instansi',
        'pengumuman' => 'Pengumuman',
        'laporan' => 'Laporan',
        'profil' => 'Profil',
        'wa' => 'WhatsApp',
        'wa_pimpinan' => 'WA Pimpinan',
        'system' => 'Sistem',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('activity_log')) {
            abort(403, 'Akses ditolak.');
        }

        $filterAksi = $request->query('aksi', '');
        $filterModul = $request->query('modul', '');
        $filterUserId = $request->query('user_id', '');
        $filterTanggalMulai = $request->query('tanggal_mulai', '');
        $filterTanggalAkhir = $request->query('tanggal_akhir', '');
        $filterSearch = $request->query('search', '');

        $query = ActivityLog::orderByDesc('created_at');

        if ($filterAksi) {
            $query->where('aksi', $filterAksi);
        }
        if ($filterModul) {
            $query->where('modul', $filterModul);
        }
        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        }
        if ($filterTanggalMulai) {
            $query->whereDate('created_at', '>=', $filterTanggalMulai);
        }
        if ($filterTanggalAkhir) {
            $query->whereDate('created_at', '<=', $filterTanggalAkhir);
        }
        if ($filterSearch) {
            $query->where(function ($q) use ($filterSearch) {
                $q->where('deskripsi', 'like', "%{$filterSearch}%")
                    ->orWhere('user_name', 'like', "%{$filterSearch}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        // Statistics
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $totalLogs = ActivityLog::count();

        $topUsersData = ActivityLog::select('user_name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('user_name')
            ->groupBy('user_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($totalLogs) {
                return [
                    'user_name' => $item->user_name,
                    'total' => (int) $item->total,
                    'percentage' => $totalLogs > 0 ? round(($item->total / $totalLogs) * 100, 1) : 0,
                ];
            })
            ->toArray();

        $byActionData = ActivityLog::select('aksi', DB::raw('COUNT(*) as total'))
            ->groupBy('aksi')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($totalLogs) {
                return [
                    'aksi' => $item->aksi,
                    'total' => (int) $item->total,
                    'percentage' => $totalLogs > 0 ? round(($item->total / $totalLogs) * 100, 1) : 0,
                ];
            })
            ->toArray();

        $stats = [
            'total' => $totalLogs,
            'today' => ActivityLog::whereDate('created_at', $today)->count(),
            'this_week' => ActivityLog::where('created_at', '>=', $startOfWeek)->count(),
            'total_users_active' => ActivityLog::whereNotNull('user_name')->distinct()->count('user_name'),
            'top_user' => $topUsersData[0] ?? null,
            'top_users' => $topUsersData,
            'by_action' => $byActionData,
        ];

        $allUsers = User::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'username']);

        return view('activity_log.index', [
            'logs' => $logs,
            'stats' => $stats,
            'allUsers' => $allUsers,
            'filterAksi' => $filterAksi,
            'filterModul' => $filterModul,
            'filterUserId' => $filterUserId,
            'filterTanggalMulai' => $filterTanggalMulai,
            'filterTanggalAkhir' => $filterTanggalAkhir,
            'filterSearch' => $filterSearch,
            'aksiLabels' => $this->aksiLabels,
            'modulLabels' => $this->modulLabels,
        ]);
    }

    public function ranking(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('activity_log')) {
            abort(403, 'Akses ditolak.');
        }

        $preset = $request->query('preset', 'this_month');
        $startDate = $request->query('tanggal_mulai');
        $endDate = $request->query('tanggal_akhir');

        // Preset handling jika tombol cepat dipilih atau default
        if ($preset === 'today') {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        } elseif ($preset === 'this_week') {
            $startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
            $endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
        } elseif ($preset === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($preset === 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        } else {
            // Custom date range
            $startDate = $startDate ?: date('Y-m-01');
            $endDate = $endDate ?: date('Y-m-t');
            $preset = 'custom';
        }

        $rankingData = UptRankingService::calculateRanking($startDate, $endDate);

        return view('activity_log.ranking', [
            'rankingData' => $rankingData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'preset' => $preset,
        ]);
    }

    public function exportRankingExcel(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('activity_log')) {
            abort(403, 'Akses ditolak.');
        }

        $startDate = $request->query('tanggal_mulai', date('Y-m-01'));
        $endDate = $request->query('tanggal_akhir', date('Y-m-t'));

        $data = UptRankingService::calculateRanking($startDate, $endDate);
        $rankings = $data['rankings'];
        $period = $data['period'];
        $summary = $data['summary'];

        $filename = 'Rekap_Leaderboard_Kedisiplinan_UPT_'.$period['start_date'].'_sd_'.$period['end_date'].'.xls';

        return response()->stream(function () use ($rankings, $period, $summary) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8"><title>Rekap Leaderboard Kedisiplinan UPT</title></head><body>';
            echo '<table border="1" style="font-family: Arial, sans-serif; font-size: 12px; border-collapse: collapse;">';

            // Header Dokumen
            echo '<tr><td colspan="12" align="center" style="font-size: 15px; font-weight: bold; background-color: #f8fafc;">REKAPITULASI LEADERBOARD &amp; PERINGKAT KEDISIPLINAN UPT</td></tr>';
            echo '<tr><td colspan="12" align="center" style="font-size: 12px; font-weight: bold; background-color: #f8fafc;">KANTOR WILAYAH KEMENTERIAN HUKUM DAN HAM / DITJENPAS BANTEN</td></tr>';
            echo '<tr><td colspan="12" align="center" style="font-size: 11px;">Periode Evaluasi: '.date('d/m/Y', strtotime($period['start_date'])).' s/d '.date('d/m/Y', strtotime($period['end_date'])).' ('.$period['total_days'].' Hari Efektif) | Batas Jam Input: '.$period['jam_selesai'].' WIB (100 Poin/Hari)</td></tr>';
            echo '<tr><td colspan="12"></td></tr>';

            // Header Tabel
            echo '<tr style="background-color: #1e3a8a; color: #ffffff; font-weight: bold; text-align: center;">';
            echo '<th style="padding: 8px;">Peringkat</th>';
            echo '<th style="padding: 8px; text-align: left;">Nama UPT</th>';
            echo '<th style="padding: 8px;">Total Poin</th>';
            echo '<th style="padding: 8px;">Maksimal Poin</th>';
            echo '<th style="padding: 8px;">Kepatuhan (%)</th>';
            echo '<th style="padding: 8px;">Hari Sempurna (100 Poin)</th>';
            echo '<th style="padding: 8px;">Modul Tepat Waktu</th>';
            echo '<th style="padding: 8px;">Modul Terlambat</th>';
            echo '<th style="padding: 8px;">Modul Belum Diisi</th>';
            echo '<th style="padding: 8px;">Kroscek Tahanan Sesuai</th>';
            echo '<th style="padding: 8px;">Kroscek Tahanan Selisih</th>';
            echo '<th style="padding: 8px;">Rata-rata Jam Selesai Input</th>';
            echo '</tr>';

            // Data Rows
            foreach ($rankings as $row) {
                $bg = '#ffffff';
                if ($row['rank'] === 1) {
                    $bg = '#fef3c7'; // Gold
                } elseif ($row['rank'] === 2) {
                    $bg = '#f1f5f9'; // Silver
                } elseif ($row['rank'] === 3) {
                    $bg = '#ffedd5'; // Bronze
                }

                echo '<tr style="background-color: '.$bg.';">';
                echo '<td align="center" style="font-weight: bold;">#'.$row['rank'].'</td>';
                echo '<td style="font-weight: 600;">'.htmlspecialchars($row['nama_upt']).'</td>';
                echo '<td align="center" style="font-weight: bold; color: #1e3a8a;">'.number_format($row['total_points']).'</td>';
                echo '<td align="center">'.number_format($row['max_points']).'</td>';
                echo '<td align="center" style="font-weight: bold;">'.$row['persentase_kepatuhan'].'%</td>';
                echo '<td align="center">'.$row['count_hari_sempurna'].' Hari</td>';
                echo '<td align="center" style="color: #047857;">'.$row['count_modul_tepat'].'</td>';
                echo '<td align="center" style="color: #b45309;">'.$row['count_modul_terlambat'].'</td>';
                echo '<td align="center" style="color: #b91c1c;">'.$row['count_modul_kosong'].'</td>';
                echo '<td align="center" style="color: #047857; font-weight: bold;">'.$row['count_kroscek_sesuai'].'x</td>';
                echo '<td align="center" style="color: #b91c1c;">'.$row['count_kroscek_selisih'].'x</td>';
                echo '<td align="center">'.$row['rata_rata_jam_input'].'</td>';
                echo '</tr>';
            }

            // Summary Footer
            echo '<tr style="background-color: #334155; color: #ffffff; font-weight: bold; text-align: center;">';
            echo '<td colspan="2" align="center">RATA-RATA / TOTAL WILAYAH</td>';
            echo '<td colspan="2" align="center">-</td>';
            echo '<td align="center">'.$summary['avg_compliance'].'%</td>';
            echo '<td colspan="4" align="center">-</td>';
            echo '<td align="center">'.$summary['total_sync_kroscek'].'x Sinkron</td>';
            echo '<td colspan="2" align="center">-</td>';
            echo '</tr>';

            echo '</table>';
            echo '</body></html>';
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
