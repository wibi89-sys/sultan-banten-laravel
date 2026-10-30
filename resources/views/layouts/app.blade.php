@php
    $instansi = \App\Models\Instansi::getFirst();
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ !empty($instansi->favicon) ? asset($instansi->favicon) : asset('assets/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Pelayanan Terpadu Pemasyarakatan Kantor Wilayah Kementerian Hukum dan HAM Banten (SULTAN Banten)">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'Sultan Banten - Kemenkumham Banten')</title>
    <!-- Preconnect to external CDNs for faster DNS and TLS negotiation -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Non-blocking FontAwesome stylesheet -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <!-- Google Fonts: Streamlined & Non-blocking Preload with Instant System Fallback -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Rajdhani:wght@600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Rajdhani:wght@600;700&display=swap"></noscript>
    <link href="{{ asset('assets/css/modern-theme.css') }}" rel="stylesheet">
    <script>
        // Set tema seawal mungkin agar tidak berkedip
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>
        /* Dark Mode Switch Styles */
        .dark-mode-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 15px;
        }
        .dark-mode-switch .switch-label {
            font-size: 13px;
            color: var(--text-secondary, #6c757d);
        }
        .dark-mode-switch .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
            margin-bottom: 0;
        }
        .dark-mode-switch .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .dark-mode-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 24px;
        }
        .dark-mode-switch .slider:before {
            position: absolute;
            content: "\f185";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 11px;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .dark-mode-switch input:checked + .slider {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .dark-mode-switch input:checked + .slider:before {
            transform: translateX(24px);
            content: "\f186";
            color: #818cf8;
        }

        /* No Sidebar Mode - for public view */
        .main-content.no-sidebar {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .main-content.no-sidebar .topbar {
            background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .main-content.no-sidebar .topbar .public-logo {
            height: 45px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            animation: fadeInLeft 0.5s ease-out;
        }
        .main-content.no-sidebar .topbar .public-title {
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: fadeInLeft 0.5s ease-out 0.1s both;
        }
        .main-content.no-sidebar .topbar .public-subtitle {
            color: rgba(255,255,255,0.9);
            text-shadow: 0 1px 2px rgba(0,0,0,0.15);
            animation: fadeInLeft 0.5s ease-out 0.2s both;
        }
        .main-content.no-sidebar .topbar .btn-login-public {
            background: rgba(255,255,255,0.95);
            color: var(--primary-color, #667eea) !important;
            border: none;
            padding: 8px 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-radius: 20px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .main-content.no-sidebar .topbar .btn-login-public:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            color: var(--primary-color, #667eea) !important;
        }
        .main-content.no-sidebar .topbar .dark-mode-switch .switch-label {
            color: rgba(255,255,255,0.8);
        }
        .main-content.no-sidebar .topbar .dark-mode-switch .slider {
            background-color: rgba(255,255,255,0.3);
        }
        .main-content.no-sidebar .topbar .dark-mode-switch .slider:before {
            background-color: white;
        }

        /* Marquee styles */
        .marquee-container {
            position: fixed;
            bottom: 0;
            left: 260px;
            right: 0;
            height: 38px;
            background: var(--bg-card, #ffffff);
            border-top: 1px solid var(--border-color, #e2e8f0);
            display: flex;
            align-items: center;
            overflow: hidden;
            z-index: 1020;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        .marquee-container.full-width {
            left: 0 !important;
        }
        .marquee-label {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 1px;
            padding: 0 16px;
            height: 100%;
            display: flex;
            align-items: center;
            white-space: nowrap;
            z-index: 2;
        }
        .marquee-content {
            display: flex;
            align-items: center;
            white-space: nowrap;
            animation: marqueeScroll 35s linear infinite;
        }
        .marquee-container:hover .marquee-content {
            animation-play-state: paused;
        }
        .marquee-item {
            display: inline-flex;
            align-items: center;
            margin-right: 40px;
            font-size: 13px;
        }
        .global-greeting {
            color: #1e40af;
            font-weight: 700;
            text-transform: uppercase;
        }
        [data-theme="dark"] .global-greeting {
            color: #60a5fa;
        }
        .kategori-pengumuman { background: var(--primary-500, #6366f1); color: white; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .kategori-berita { background: var(--success-500, #22c55e); color: white; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .kategori-urgent { background: var(--danger-500, #ef4444); color: white; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .kategori-info { background: var(--accent-500, #06b6d4); color: #000; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        @media (max-width: 991.98px) {
            .marquee-container {
                left: 0 !important;
            }
        }

        /* Topbar & Dropdown Stacking Context Fix */
        .topbar {
            position: sticky !important;
            top: 0 !important;
            z-index: 1050 !important;
        }

        /* Topbar User Dropdown Styles (matches screenshot design) */
        .user-dropdown-toggle {
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 24px;
            transition: all 0.2s ease;
        }
        .user-dropdown-toggle:hover, .user-dropdown-toggle:focus {
            background: rgba(0, 0, 0, 0.05);
        }
        .topbar-user-menu {
            border-radius: 18px !important;
            box-shadow: 0 16px 40px -8px rgba(15, 23, 42, 0.16), 0 4px 12px -2px rgba(15, 23, 42, 0.08) !important;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            min-width: 260px !important;
            padding: 18px 20px 14px 20px !important;
            z-index: 1060 !important;
        }
        .topbar-user-menu .dropdown-item {
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .topbar-user-menu .dropdown-item:hover {
            background-color: #f1f5f9;
        }
        .topbar-user-menu .dropdown-item.text-danger:hover {
            background-color: #fef2f2;
            color: #dc2626 !important;
        }
        [data-theme="dark"] .user-dropdown-toggle {
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .user-dropdown-toggle .profile-name-text {
            color: #f1f5f9;
        }
        [data-theme="dark"] .user-dropdown-toggle:hover, [data-theme="dark"] .user-dropdown-toggle:focus {
            background: rgba(255, 255, 255, 0.08);
        }
        [data-theme="dark"] .topbar-user-menu {
            background-color: #1e293b !important;
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 16px 40px -8px rgba(0, 0, 0, 0.5) !important;
        }
        [data-theme="dark"] .topbar-user-menu .dropdown-item {
            color: #cbd5e1;
        }
        [data-theme="dark"] .topbar-user-menu .dropdown-item:hover {
            background-color: #334155;
            color: #ffffff;
        }
        [data-theme="dark"] .topbar-user-menu .dropdown-item.text-danger:hover {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171 !important;
        }
        [data-theme="dark"] .topbar-user-menu .text-dark {
            color: #f8fafc !important;
        }
        [data-theme="dark"] .topbar-user-menu .text-muted {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .topbar-user-menu hr {
            border-color: #334155 !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    @if($user)
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            @if(!empty($instansi->logo))
                @php
                    $sideLogoExt = pathinfo($instansi->logo, PATHINFO_EXTENSION);
                    $sideThumbLogo = preg_replace('/\.' . preg_quote($sideLogoExt, '/') . '$/i', '_thumb.webp', $instansi->logo);
                    $sideWebpLogo = preg_replace('/\.' . preg_quote($sideLogoExt, '/') . '$/i', '.webp', $instansi->logo);
                    $hasSideThumbLogo = file_exists(public_path($sideThumbLogo));
                    $hasSideWebpLogo = file_exists(public_path($sideWebpLogo));
                    $chosenSideLogo = $hasSideThumbLogo ? $sideThumbLogo : ($hasSideWebpLogo ? $sideWebpLogo : $instansi->logo);
                    $isSideWebp = $hasSideThumbLogo || $hasSideWebpLogo;
                @endphp
                @if($isSideWebp)
                <picture>
                    <source srcset="{{ asset($chosenSideLogo) }}" type="image/webp">
                    <img src="{{ asset($instansi->logo) }}" alt="Logo {{ $instansi->nama_instansi ?? 'Sultan Banten' }}" width="60" height="60" style="object-fit: contain;" loading="lazy">
                </picture>
                @else
                <img src="{{ asset($instansi->logo) }}" alt="Logo {{ $instansi->nama_instansi ?? 'Sultan Banten' }}" width="60" height="60" style="object-fit: contain;" loading="lazy">
                @endif
            @else
                <i class="fas fa-building fa-3x text-white mb-2"></i>
            @endif
            <h5>{{ $instansi->nama_instansi ?? 'Sultan Banten' }}</h5>
        </div>
        <div class="sidebar-menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            
            @if($user->hasAccess('penghuni') || $user->hasAccess('overstaying') || $user->hasAccess('integrasi') || $user->hasAccess('bebas_harian'))
            <div class="menu-parent {{ request()->routeIs('penghuni.*') || request()->routeIs('overstaying.*') || request()->routeIs('integrasi.*') || request()->routeIs('bebas-harian.*') || request()->routeIs('kroscek.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuInputData">
                <i class="fas fa-edit"></i>
                <span>Input Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('penghuni.*') || request()->routeIs('overstaying.*') || request()->routeIs('integrasi.*') || request()->routeIs('bebas-harian.*') || request()->routeIs('kroscek.*') ? 'show' : '' }}" id="menuInputData">
                <div class="sidebar-submenu">
                    @if($user->hasAccess('penghuni'))
                    <a href="{{ route('penghuni.index') }}" class="{{ request()->routeIs('penghuni.*') ? 'active' : '' }}"><i class="fas fa-users"></i><span>Data Penghuni</span></a>
                    @endif
                    @if($user->hasAccess('overstaying'))
                    <a href="{{ route('overstaying.index') }}" class="{{ request()->routeIs('overstaying.*') ? 'active' : '' }}"><i class="fas fa-clock"></i><span>Data Overstaying</span></a>
                    @endif
                    @if($user->hasAccess('integrasi'))
                    <a href="{{ route('integrasi.index') }}" class="{{ request()->routeIs('integrasi.*') ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i><span>Data Integrasi</span></a>
                    @endif
                    @if($user->hasAccess('bebas_harian'))
                    <a href="{{ route('bebas-harian.index') }}" class="{{ request()->routeIs('bebas-harian.*') ? 'active' : '' }}"><i class="fas fa-door-open"></i><span>Bebas Harian</span></a>
                    @endif
                    <a href="{{ route('kroscek.index') }}" class="{{ request()->routeIs('kroscek.*') ? 'active' : '' }}">
                        <i class="fas fa-check-double"></i><span>Kroscek Data</span>
                    </a>
                </div>
            </div>
            @endif

            @if($user->hasAccess('sidang_tpp'))
            <div class="menu-parent {{ request()->routeIs('sidang-tpp.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuSidangTPP">
                <i class="fas fa-gavel"></i>
                <span>Sidang TPP</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('sidang-tpp.*') ? 'show' : '' }}" id="menuSidangTPP">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('sidang-tpp.index') ? route('sidang-tpp.index') : url('/sidang-tpp') }}" class="{{ request()->routeIs('sidang-tpp.index') || request()->routeIs('sidang-tpp.create') || request()->routeIs('sidang-tpp.show') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i><span>1. Jadwal &amp; Undangan</span>
                    </a>
                    <a href="{{ Route::has('sidang-tpp.hasil') ? route('sidang-tpp.hasil') : url('/sidang-tpp/hasil') }}" class="{{ request()->routeIs('sidang-tpp.hasil*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-check"></i><span>2. Hasil Sidang UPT</span>
                    </a>
                    <a href="{{ Route::has('sidang-tpp.kanwil') ? route('sidang-tpp.kanwil') : url('/sidang-tpp/kanwil') }}" class="{{ request()->routeIs('sidang-tpp.kanwil*') ? 'active' : '' }}">
                        <i class="fas fa-stamp"></i><span>3. Verifikasi &amp; TTD Kanwil</span>
                    </a>
                    <a href="{{ Route::has('sidang-tpp.rekapitulasi') ? route('sidang-tpp.rekapitulasi') : url('/sidang-tpp/rekapitulasi') }}" class="{{ request()->routeIs('sidang-tpp.rekapitulasi*') ? 'active' : '' }}">
                        <i class="fas fa-file-download"></i><span>4. Rekap &amp; Download Dokumen</span>
                    </a>
                </div>
            </div>
            @endif

            @if($user->hasAccess('mutasi'))
            <div class="menu-parent {{ request()->routeIs('mutasi.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuMutasi">
                <i class="fas fa-exchange-alt"></i>
                <span>Mutasi Narapidana</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('mutasi.*') ? 'show' : '' }}" id="menuMutasi">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('mutasi.index') ? route('mutasi.index') : url('/mutasi') }}" class="{{ request()->routeIs('mutasi.index') || request()->routeIs('mutasi.show') ? 'active' : '' }}">
                        <i class="fas fa-list-alt"></i><span>1. Daftar Usulan Mutasi</span>
                    </a>
                    @if(! empty($user->upt_id))
                    <a href="{{ Route::has('mutasi.create') ? route('mutasi.create') : url('/mutasi/create') }}" class="{{ request()->routeIs('mutasi.create') ? 'active' : '' }}">
                        <i class="fas fa-plus-circle"></i><span>2. Buat Usulan Baru</span>
                    </a>
                    @endif
                    <a href="{{ Route::has('mutasi.kanwil') ? route('mutasi.kanwil') : url('/mutasi/kanwil') }}" class="{{ request()->routeIs('mutasi.kanwil*') ? 'active' : '' }}">
                        <i class="fas fa-stamp"></i><span>3. Persetujuan Kanwil</span>
                    </a>
                    <a href="{{ Route::has('mutasi.pelaksanaan') ? route('mutasi.pelaksanaan') : url('/mutasi/pelaksanaan') }}" class="{{ request()->routeIs('mutasi.pelaksanaan*') ? 'active' : '' }}">
                        <i class="fas fa-truck-moving"></i><span>4. Laporan Pemindahan &amp; BAST</span>
                    </a>
                    <a href="{{ Route::has('mutasi.rekapitulasi') ? route('mutasi.rekapitulasi') : url('/mutasi/rekapitulasi') }}" class="{{ request()->routeIs('mutasi.rekapitulasi*') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i><span>5. Rekapitulasi &amp; Ekspor</span>
                    </a>
                </div>
            </div>
            @endif

            @if($user->hasAccess('laporan'))
            <div class="menu-parent {{ request()->routeIs('laporan.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuLaporan">
                <i class="fas fa-print"></i>
                <span>Cetak Laporan</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('laporan.*') ? 'show' : '' }}" id="menuLaporan">
                <div class="sidebar-submenu">
                    <a href="{{ route('laporan.index', ['type' => 'penghuni']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'penghuni' ? 'active' : '' }}"><i class="fas fa-users"></i><span>Lap. Penghuni</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'overstaying']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'overstaying' ? 'active' : '' }}"><i class="fas fa-clock"></i><span>Lap. Overstaying</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'integrasi']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'integrasi' ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i><span>Lap. Integrasi</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'bebas_harian']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'bebas_harian' ? 'active' : '' }}"><i class="fas fa-door-open"></i><span>Lap. Bebas Harian</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'rekapitulasi']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'rekapitulasi' ? 'active' : '' }}"><i class="fas fa-chart-bar"></i><span>Rekapitulasi</span></a>
                </div>
            </div>
            @endif

            <a href="{{ route('telram.index') }}" class="{{ request()->routeIs('telram.*') ? 'active' : '' }}">
                <i class="fas fa-calculator"></i><span>Kalkulator Telram</span>
            </a>

            <div class="menu-parent {{ request()->routeIs('upt.*') || request()->routeIs('users.*') || request()->routeIs('instansi.*') || request()->routeIs('pengumuman.*') || request()->routeIs('activity-log.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuMaster">
                <i class="fas fa-database"></i>
                <span>Master Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('upt.*') || request()->routeIs('users.*') || request()->routeIs('instansi.*') || request()->routeIs('pengumuman.*') || request()->routeIs('activity-log.*') ? 'show' : '' }}" id="menuMaster">
                <div class="sidebar-submenu">
                    @if($user->hasAccess('upt'))
                    <a href="{{ route('upt.index') }}" class="{{ request()->routeIs('upt.*') ? 'active' : '' }}"><i class="fas fa-building"></i><span>Data UPT</span></a>
                    @endif
                    @if($user->hasAccess('user'))
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fas fa-user-cog"></i><span>Manajemen User</span></a>
                    @endif
                    @if($user->hasAccess('instansi'))
                    <a href="{{ route('instansi.index') }}" class="{{ request()->routeIs('instansi.*') ? 'active' : '' }}"><i class="fas fa-cog"></i><span>Pengaturan Instansi</span></a>
                    @endif
                    @if($user->hasAccess('pengumuman'))
                    <a href="{{ route('pengumuman.index') }}" class="{{ request()->routeIs('pengumuman.*') ? 'active' : '' }}"><i class="fas fa-bullhorn"></i><span>Pengumuman</span></a>
                    @endif
                    @if($user->hasAccess('activity_log'))
                    <a href="{{ route('activity-log.index') }}" class="{{ request()->routeIs('activity-log.*') ? 'active' : '' }}"><i class="fas fa-history"></i><span>Activity Log</span></a>
                    @endif
                </div>
            </div>
            
            @if($user->hasAccess('wa_settings') || $user->hasAccess('wa_schedule'))
            <div class="menu-parent {{ request()->routeIs('wa.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuWA">
                <i class="fab fa-whatsapp text-success"></i>
                <span>Otomatisasi WhatsApp</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('wa.*') ? 'show' : '' }}" id="menuWA">
                <div class="sidebar-submenu">
                    @if($user->hasAccess('wa_settings'))
                    <a href="{{ route('wa.gateway') }}" class="{{ request()->routeIs('wa.gateway*') || request()->routeIs('wa.settings*') ? 'active' : '' }}">
                        <i class="fas fa-plug text-primary"></i><span>1. Gateway &amp; Koneksi</span>
                    </a>
                    @endif
                    @if($user->hasAccess('wa_schedule') || $user->hasAccess('wa_settings'))
                    <a href="{{ route('wa.contacts') }}" class="{{ request()->routeIs('wa.contacts*') || request()->routeIs('wa.pimpinan*') || request()->routeIs('wa.groups*') ? 'active' : '' }}">
                        <i class="fas fa-address-book text-info"></i><span>2. Kontak &amp; Target</span>
                    </a>
                    @endif
                    @if($user->hasAccess('wa_schedule'))
                    <a href="{{ route('wa.schedules') }}" class="{{ request()->routeIs('wa.schedules*') || request()->routeIs('wa.schedule*') ? 'active' : '' }}">
                        <i class="fas fa-clock text-warning"></i><span>3. Jadwal &amp; Template</span>
                    </a>
                    @endif
                    @if($user->hasAccess('wa_settings') || $user->hasAccess('wa_schedule'))
                    <a href="{{ route('wa.logs') }}" class="{{ request()->routeIs('wa.logs*') ? 'active' : '' }}">
                        <i class="fas fa-history text-success"></i><span>4. Riwayat &amp; Log</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="fas fa-user-circle"></i><span>Profil Saya</span></a>
            <form action="{{ route('logout') }}" method="POST" id="logoutForm" class="d-inline">
                @csrf
                <a href="javascript:void(0)" onclick="document.getElementById('logoutForm').submit();">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </form>
        </div>
    </nav>
    @endif

    <!-- Main Content -->
    <main id="main-content" role="main" class="main-content {{ !$user ? 'no-sidebar' : '' }}">
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                @if($user)
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar" aria-label="Toggle Menu Sidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0">@yield('page_title', 'Dashboard')</h4>
                @else
                <!-- Public Header with Logo -->
                @if(!empty($instansi->logo))
                @php
                    $logoExt = pathinfo($instansi->logo, PATHINFO_EXTENSION);
                    $thumbLogo = preg_replace('/\.' . preg_quote($logoExt, '/') . '$/i', '_thumb.webp', $instansi->logo);
                    $webpLogo = preg_replace('/\.' . preg_quote($logoExt, '/') . '$/i', '.webp', $instansi->logo);
                    $hasThumbLogo = file_exists(public_path($thumbLogo));
                    $hasWebpLogo = file_exists(public_path($webpLogo));
                    $chosenLogo = $hasThumbLogo ? $thumbLogo : ($hasWebpLogo ? $webpLogo : $instansi->logo);
                    $isWebp = $hasThumbLogo || $hasWebpLogo;
                @endphp
                @if($isWebp)
                <picture>
                    <source srcset="{{ asset($chosenLogo) }}" type="image/webp">
                    <img src="{{ asset($instansi->logo) }}" alt="Logo {{ $instansi->nama_instansi ?? 'Sultan Banten' }}" class="public-logo me-3" width="45" height="45" fetchpriority="high">
                </picture>
                @else
                <img src="{{ asset($instansi->logo) }}" alt="Logo {{ $instansi->nama_instansi ?? 'Sultan Banten' }}" class="public-logo me-3" width="45" height="45" fetchpriority="high">
                @endif
                @endif
                <div>
                    <h5 class="mb-0 fw-bold public-title">{{ $instansi->nama_instansi ?? 'Sultan Banten' }}</h5>
                    <small class="public-subtitle"><i class="fas fa-chart-line me-1"></i>Dashboard Sistem Update Laporan Terpadu Harian</small>
                </div>
                @endif
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <!-- Dark Mode Switch -->
                <div class="dark-mode-switch">
                    <span class="switch-label d-none d-md-inline"><i class="fas fa-sun text-warning"></i></span>
                    <label class="switch" for="darkModeToggle">
                        <input type="checkbox" id="darkModeToggle" aria-label="Aktifkan Mode Gelap">
                        <span class="slider"></span>
                    </label>
                    <span class="switch-label d-none d-md-inline"><i class="fas fa-moon text-primary"></i></span>
                </div>

                @if($user)
                <div class="dropdown">
                    <a href="javascript:void(0)" class="d-flex align-items-center text-decoration-none dropdown-toggle user-dropdown-toggle text-dark" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="me-2">{!! $user->getAvatarHtml(32) !!}</div>
                        <span class="d-none d-sm-inline me-2 fw-semibold profile-name-text">{{ $user->nama_lengkap ?? $user->username }}</span>
                        <span class="badge rounded-pill text-white px-2.5 py-1 me-1" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); font-size: 11px; font-weight: 600;">{{ ucfirst($user->role) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end topbar-user-menu shadow-lg border-0 mt-2" aria-labelledby="userProfileDropdown">
                        <li class="mb-2">
                            <div class="fw-bold text-dark text-truncate" style="font-size: 15px; letter-spacing: -0.2px;">{{ $user->nama_lengkap ?? $user->username }}</div>
                            <div class="text-muted text-truncate mt-1 mb-2" style="font-size: 12px; font-weight: 500;">{{ $user->upt?->nama_upt ?? ($instansi->nama_instansi ?? 'Kantor Wilayah') }}</div>
                            <span class="badge rounded-pill px-2.5 py-1" style="background-color: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 700;">{{ ucfirst($user->role) }}</span>
                        </li>
                        <li><hr class="my-2" style="border-color: #f1f5f9; opacity: 1;"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.index') }}">
                                <i class="fas fa-user-circle me-2 text-primary" style="font-size: 16px;"></i>
                                <span>Profil Saya</span>
                            </a>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" id="topbarLogoutForm">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center w-100 border-0 bg-transparent">
                                    <i class="fas fa-sign-out-alt me-2 text-danger" style="font-size: 15px;"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @else
                <a href="{{ route('login') }}" class="btn btn-login-public">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup notifikasi"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup notifikasi"></button>
            </div>
        @elseif($errors->any())
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup notifikasi"></button>
            </div>
        @endif

        @yield('content')

        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten {{ date('Y') }}
        </footer>
    </main>

    @php
        $marqueeAnnouncements = \App\Models\Pengumuman::where('aktif', 1)
            ->where('tampilkan_marquee', 1)
            ->where(function($q) {
                $today = date('Y-m-d');
                $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today);
            })
            ->where(function($q) {
                $today = date('Y-m-d');
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today);
            })
            ->where(function($q) use ($user) {
                $uptId = $user ? $user->upt_id : null;
                $q->where('tipe', 'Global');
                if ($uptId) {
                    $q->orWhere('upt_id', $uptId);
                }
            })
            ->orderByDesc('created_at')
            ->get();
        $globalRunningText = $instansi->running_text ?? '';
    @endphp

    @if($marqueeAnnouncements->count() > 0 || !empty($globalRunningText))
    <div class="marquee-container {{ !$user ? 'full-width' : '' }}">
        @if($marqueeAnnouncements->count() > 0)
        <div class="marquee-label">
            <i class="fas fa-bullhorn me-2"></i> INFO
        </div>
        @endif
        <div class="marquee-content">
            @if(!empty($globalRunningText))
            <div class="marquee-item global-greeting">
                <i class="fas fa-star me-2 text-warning"></i>
                <strong>{{ $globalRunningText }}</strong>
                <i class="fas fa-star ms-2 text-warning"></i>
            </div>
            @endif

            @foreach ($marqueeAnnouncements as $m)
            <div class="marquee-item">
                <span class="kategori kategori-{{ strtolower($m->kategori) }}">{{ $m->kategori }}</span>
                <strong>{{ $m->judul }}:</strong>
                <span class="ms-2">{{ strip_tags($m->isi) }}</span>
            </div>
            @endforeach

            @if(!empty($globalRunningText))
            <div class="marquee-item global-greeting">
                <i class="fas fa-star me-2 text-warning"></i>
                <strong>{{ $globalRunningText }}</strong>
                <i class="fas fa-star ms-2 text-warning"></i>
            </div>
            @endif

            @foreach ($marqueeAnnouncements as $m)
            <div class="marquee-item">
                <span class="kategori kategori-{{ strtolower($m->kategori) }}">{{ $m->kategori }}</span>
                <strong>{{ $m->judul }}:</strong>
                <span class="ms-2">{{ strip_tags($m->isi) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        function toggleSidebar() {
            sidebar?.classList.toggle('show');
            overlay?.classList.toggle('show');
        }
        
        toggleBtn?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);

        // Dark Mode Logic
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            if (darkModeToggle) darkModeToggle.checked = true;
        }

        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
