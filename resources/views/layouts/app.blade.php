<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '{{ app_name() }}') - {{ setting('app_tagline', 'Sistem Ujian Online') }}</title>
    <meta name="description" content="{{ setting('app_description', 'Computer Based Test (CBT) untuk SMK - Sistem Ujian Online Modern') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom iOS 16 Style -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon">
                        @if(school_logo())
                            <img src="{{ school_logo() }}" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                        @else
                            <i class="bi bi-mortarboard-fill"></i>
                        @endif
                    </div>
                    <div class="logo-text">
                        <h5>{{ app_name() }}</h5>
                        <small>{{ setting('app_tagline', 'Ujian Online') }}</small>
                    </div>
                </div>
                <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="sidebar-menu">
                @if(auth()->user()->role === 'superadmin')
                    <div class="menu-label">DASHBOARD</div>
                    <a href="{{ route('superadmin.dashboard') }}" class="menu-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="menu-label">MANAJEMEN SISWA</div>
                    <a href="{{ route('admin.siswa.index') }}" class="menu-item {{ request()->routeIs('admin.siswa.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>
                        <span>Daftar Siswa</span>
                    </a>
                    <a href="{{ route('admin.siswa.create') }}" class="menu-item {{ request()->routeIs('admin.siswa.create') ? 'active' : '' }}">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Tambah Siswa</span>
                    </a>
                    <a href="{{ route('admin.import-siswa.index') }}" class="menu-item {{ request()->routeIs('admin.import-siswa.*') ? 'active' : '' }}">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                        <span>Import Siswa</span>
                    </a>

                    <div class="menu-label">MANAJEMEN</div>
                    <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i>
                        <span>Pengguna</span>
                    </a>
                    <a href="{{ route('admin.jurusan.index') }}" class="menu-item {{ request()->routeIs('admin.jurusan.*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>
                        <span>Jurusan</span>
                    </a>
                    <a href="{{ route('admin.kelas.index') }}" class="menu-item {{ request()->routeIs('admin.kelas.*') ? 'active' : '' }}">
                        <i class="bi bi-door-open-fill"></i>
                        <span>Kelas</span>
                    </a>
                    <a href="{{ route('admin.mapel.index') }}" class="menu-item {{ request()->routeIs('admin.mapel.*') ? 'active' : '' }}">
                        <i class="bi bi-book-fill"></i>
                        <span>Mata Pelajaran</span>
                    </a>
                    <a href="{{ route('admin.sesi.index') }}" class="menu-item {{ request()->routeIs('admin.sesi.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-fill"></i>
                        <span>Sesi Ujian</span>
                    </a>
                    <a href="{{ route('admin.guru.index') }}" class="menu-item {{ request()->routeIs('admin.guru.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Data Guru</span>
                    </a>

                    <div class="menu-label">UJIAN</div>
                    <a href="{{ route('banksoal.index') }}" class="menu-item {{ request()->routeIs('banksoal.*') ? 'active' : '' }}">
                        <i class="bi bi-database-fill"></i>
                        <span>Bank Soal</span>
                    </a>
                    <a href="{{ route('admin.import-banksoal.index') }}" class="menu-item {{ request()->routeIs('admin.import-banksoal.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-arrow-up-fill"></i>
                        <span>Import Soal</span>
                    </a>
                    <a href="{{ route('ujian.index') }}" class="menu-item {{ request()->routeIs('ujian.*') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Ujian</span>
                    </a>
                    <a href="{{ route('kartu-peserta.index') }}" class="menu-item {{ request()->routeIs('kartu-peserta.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                        <span>Cetak Kartu</span>
                    </a>
                    <a href="{{ route('status-peserta.index') }}" class="menu-item {{ request()->routeIs('status-peserta.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check-fill"></i>
                        <span>Status Peserta</span>
                    </a>
                    <a href="{{ route('admin.anti-cheat.index') }}" class="menu-item {{ request()->routeIs('admin.anti-cheat.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation"></i>
                        <span>Anti-Cheat Log</span>
                    </a>

                    <div class="menu-label">SISTEM</div>
                    <a href="{{ route('admin.monitoring.index') }}" class="menu-item {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span>Monitoring Sistem</span>
                    </a>
                    <a href="{{ route('superadmin.backup.index') }}" class="menu-item {{ request()->routeIs('superadmin.backup.*') ? 'active' : '' }}">
                        <i class="bi bi-database-fill-down"></i>
                        <span>Backup Database</span>
                    </a>
                    <a href="{{ route('superadmin.settings.index') }}" class="menu-item {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i>
                        <span>Pengaturan Sistem</span>
                    </a>

                @elseif(auth()->user()->role === 'admin')
                    <div class="menu-label">DASHBOARD</div>
                    <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="menu-label">MANAJEMEN SISWA</div>
                    <a href="{{ route('admin.siswa.index') }}" class="menu-item {{ request()->routeIs('admin.siswa.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>
                        <span>Daftar Siswa</span>
                    </a>
                    <a href="{{ route('admin.siswa.create') }}" class="menu-item {{ request()->routeIs('admin.siswa.create') ? 'active' : '' }}">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Tambah Siswa</span>
                    </a>
                    <a href="{{ route('admin.import-siswa.index') }}" class="menu-item {{ request()->routeIs('admin.import-siswa.*') ? 'active' : '' }}">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                        <span>Import Siswa</span>
                    </a>

                    <div class="menu-label">MANAJEMEN</div>
                    <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i>
                        <span>Pengguna</span>
                    </a>
                    <a href="{{ route('admin.jurusan.index') }}" class="menu-item {{ request()->routeIs('admin.jurusan.*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>
                        <span>Jurusan</span>
                    </a>
                    <a href="{{ route('admin.kelas.index') }}" class="menu-item {{ request()->routeIs('admin.kelas.*') ? 'active' : '' }}">
                        <i class="bi bi-door-open-fill"></i>
                        <span>Kelas</span>
                    </a>
                    <a href="{{ route('admin.mapel.index') }}" class="menu-item {{ request()->routeIs('admin.mapel.*') ? 'active' : '' }}">
                        <i class="bi bi-book-fill"></i>
                        <span>Mata Pelajaran</span>
                    </a>
                    <a href="{{ route('admin.sesi.index') }}" class="menu-item {{ request()->routeIs('admin.sesi.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-fill"></i>
                        <span>Sesi Ujian</span>
                    </a>
                    <a href="{{ route('admin.guru.index') }}" class="menu-item {{ request()->routeIs('admin.guru.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Data Guru</span>
                    </a>

                    <div class="menu-label">UJIAN</div>
                    <a href="{{ route('banksoal.index') }}" class="menu-item {{ request()->routeIs('banksoal.*') ? 'active' : '' }}">
                        <i class="bi bi-database-fill"></i>
                        <span>Bank Soal</span>
                    </a>
                    <a href="{{ route('admin.import-banksoal.index') }}" class="menu-item {{ request()->routeIs('admin.import-banksoal.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-arrow-up-fill"></i>
                        <span>Import Soal</span>
                    </a>
                    <a href="{{ route('ujian.index') }}" class="menu-item {{ request()->routeIs('ujian.*') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Ujian</span>
                    </a>
                    <a href="{{ route('kartu-peserta.index') }}" class="menu-item {{ request()->routeIs('kartu-peserta.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                        <span>Cetak Kartu</span>
                    </a>
                    <a href="{{ route('status-peserta.index') }}" class="menu-item {{ request()->routeIs('status-peserta.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check-fill"></i>
                        <span>Status Peserta</span>
                    </a>
                    <a href="{{ route('admin.anti-cheat.index') }}" class="menu-item {{ request()->routeIs('admin.anti-cheat.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation"></i>
                        <span>Anti-Cheat Log</span>
                    </a>

                    <div class="menu-label">SISTEM</div>
                    <a href="{{ route('admin.monitoring.index') }}" class="menu-item {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span>Monitoring Sistem</span>
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i>
                        <span>Pengaturan Sistem</span>
                    </a>

                @elseif(auth()->user()->role === 'guru')
                    <div class="menu-label">DASHBOARD</div>
                    <a href="{{ route('guru.dashboard') }}" class="menu-item {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="menu-label">UJIAN</div>
                    <a href="{{ route('banksoal.index') }}" class="menu-item {{ request()->routeIs('banksoal.*') ? 'active' : '' }}">
                        <i class="bi bi-database-fill"></i>
                        <span>Bank Soal</span>
                    </a>

                    <div class="menu-label">AKUN</div>
                    <a href="{{ route('guru.profil') }}" class="menu-item {{ request()->routeIs('guru.profil') ? 'active' : '' }}">
                        <i class="bi bi-person-circle"></i>
                        <span>Profil</span>
                    </a>

                @elseif(auth()->user()->role === 'siswa')
                    <div class="menu-label">MENU</div>
                    <a href="{{ route('siswa.dashboard') }}" class="menu-item {{ request()->routeIs('siswa.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
            </div>

            <div class="sidebar-footer">
                <div class="user-card">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="user-info">
                        <h6>{{ auth()->user()->name }}</h6>
                        <small>{{ ucfirst(auth()->user()->role) }}</small>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-logout" title="Logout">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="page-title">
                        <h4>@yield('page-title', 'Dashboard')</h4>
                        <p class="text-muted mb-0">@yield('page-subtitle', '')</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <i class="bi bi-calendar3"></i>
                        <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                @if(session('success'))
                    <div class="alert alert-success alert-ios fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-ios fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-ios fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.querySelector('.sidebar-overlay').classList.toggle('show');
        }
    </script>

    @stack('scripts')
</body>
</html>
