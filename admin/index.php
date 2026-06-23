<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Ambil statistik
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

// Ambil pengguna terbaru
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Ambil materi terbaru
$materi = $pdo->query("SELECT * FROM materi ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #1B2A4A;
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-brand h3 {
            color: white;
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar-brand h3 span {
            color: #F4B41A;
        }
        
        .sidebar-brand .subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            margin-top: 5px;
        }
        
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-user .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(244, 180, 26, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            border: 3px solid #F4B41A;
        }
        
        .sidebar-user .avatar i {
            font-size: 28px;
            color: #F4B41A;
        }
        
        .sidebar-user .name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .sidebar-user .role {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
        }
        
        .sidebar-nav {
            padding: 15px 0;
        }
        
        .sidebar-nav .nav-label {
            padding: 10px 25px;
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            letter-spacing: 1px;
        }
        
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: white;
            border-left-color: #F4B41A;
        }
        
        .sidebar-nav .nav-link.active {
            background: rgba(244, 180, 26, 0.1);
            color: #F4B41A;
            border-left-color: #F4B41A;
        }
        
        .sidebar-nav .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .sidebar-nav .nav-link .badge {
            margin-left: auto;
            background: #F4B41A;
            color: #1B2A4A;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 20px 30px;
            min-height: 100vh;
        }
        
        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 25px;
        }
        
        .top-bar .page-title h4 {
            color: #1B2A4A;
            font-weight: 700;
            margin: 0;
        }
        
        .top-bar .page-title p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }
        
        .top-bar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .top-bar .user-info .date {
            color: #666;
            font-size: 14px;
        }
        
        .top-bar .user-info .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .top-bar .user-info .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid #F4B41A;
            margin-bottom: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-icon {
            font-size: 35px;
            color: #F4B41A;
            margin-bottom: 10px;
        }
        
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1B2A4A;
        }
        
        .stat-card .stat-label {
            color: #999;
            font-size: 14px;
        }
        
        .stat-card.blue {
            border-left-color: #1B2A4A;
        }
        
        .stat-card.blue .stat-icon {
            color: #1B2A4A;
        }
        
        .stat-card.green {
            border-left-color: #28a745;
        }
        
        .stat-card.green .stat-icon {
            color: #28a745;
        }
        
        .stat-card.red {
            border-left-color: #dc3545;
        }
        
        .stat-card.red .stat-icon {
            color: #dc3545;
        }
        
        /* Activity Card */
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .activity-card .card-title {
            color: #1B2A4A;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-item .icon-box.blue {
            background: rgba(27, 42, 74, 0.1);
            color: #1B2A4A;
        }
        
        .activity-item .icon-box.gold {
            background: rgba(244, 180, 26, 0.1);
            color: #F4B41A;
        }
        
        .activity-item .icon-box.green {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .activity-item .icon-box.red {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .activity-item .content {
            flex: 1;
        }
        
        .activity-item .content .title {
            font-weight: 600;
            color: #1B2A4A;
            font-size: 14px;
        }
        
        .activity-item .content .time {
            color: #999;
            font-size: 12px;
        }
        
        /* Toggle Sidebar */
        .sidebar-toggle {
            display: none;
            background: #1B2A4A;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 20px;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay untuk mobile -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h3>English <span>Course</span></h3>
                <div class="subtitle">Admin Panel</div>
            </div>
            
            <div class="sidebar-user">
                <div class="avatar">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
                <div class="role">Administrator</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-label">Main Menu</div>
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="materi.php" class="nav-link">
                    <i class="fas fa-book"></i> Materi
                </a>
                <a href="soal.php" class="nav-link">
                    <i class="fas fa-question-circle"></i> Soal Latihan
                </a>
                <a href="toefl.php" class="nav-link">
                    <i class="fas fa-graduation-cap"></i> TOEFL
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Pengguna
                    <span class="badge"><?php echo $total_users; ?></span>
                </a>
                
                <div class="nav-label mt-3">Lainnya</div>
                <a href="../logout.php" class="nav-link" style="color: #dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <div class="d-flex align-items-center">
                        <button class="sidebar-toggle me-3" id="sidebarToggle" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h4><i class="fas fa-tachometer-alt" style="color: #F4B41A;"></i> Dashboard</h4>
                            <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="row">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-number"><?php echo $total_materi; ?></div>
                        <div class="stat-label">Total Materi</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Pengguna</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
                        <div class="stat-number"><?php echo $total_soal; ?></div>
                        <div class="stat-label">Total Soal</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="stat-number"><?php echo $total_toefl; ?></div>
                        <div class="stat-label">Total TOEFL</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="activity-card">
                        <h6 class="card-title"><i class="fas fa-users" style="color: #F4B41A;"></i> Pengguna Terbaru</h6>
                        <?php if(count($users) > 0): ?>
                            <?php foreach($users as $user): ?>
                            <div class="activity-item">
                                <div class="icon-box blue">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="content">
                                    <div class="title"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                    <div class="time"><?php echo htmlspecialchars($user['username']); ?> • <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                                </div>
                                <span class="badge" style="background: <?php echo $user['role'] == 'admin' ? '#F4B41A' : '#28a745'; ?>; color: white;">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999; text-align: center; padding: 20px 0;">Belum ada pengguna</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="activity-card">
                        <h6 class="card-title"><i class="fas fa-book" style="color: #F4B41A;"></i> Materi Terbaru</h6>
                        <?php if(count($materi) > 0): ?>
                            <?php foreach($materi as $m): ?>
                            <div class="activity-item">
                                <div class="icon-box gold">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="content">
                                    <div class="title"><?php echo htmlspecialchars($m['judul']); ?></div>
                                    <div class="time"><?php echo htmlspecialchars($m['kategori']); ?> • <?php echo date('d/m/Y', strtotime($m['created_at'])); ?></div>
                                </div>
                                <span class="badge" style="background: #1B2A4A; color: white;">
                                    <?php echo htmlspecialchars($m['tingkat']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999; text-align: center; padding: 20px 0;">Belum ada materi</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="activity-card">
                        <h6 class="card-title"><i class="fas fa-bolt" style="color: #F4B41A;"></i> Aksi Cepat</h6>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-2">
                                <a href="tambah_materi.php" class="btn w-100" style="background: #1B2A4A; color: white; border-radius: 10px; padding: 15px;">
                                    <i class="fas fa-plus-circle me-2"></i> Tambah Materi
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="tambah_soal.php" class="btn w-100" style="background: #F4B41A; color: #1B2A4A; border-radius: 10px; padding: 15px;">
                                    <i class="fas fa-plus-circle me-2"></i> Tambah Soal
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="tambah_toefl.php" class="btn w-100" style="background: #17a2b8; color: white; border-radius: 10px; padding: 15px;">
                                    <i class="fas fa-plus-circle me-2"></i> Tambah TOEFL
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="users.php" class="btn w-100" style="background: #28a745; color: white; border-radius: 10px; padding: 15px;">
                                    <i class="fas fa-users me-2"></i> Kelola Pengguna
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('overlay').classList.remove('active');
            }
        });
    </script>
</body>
</html>