<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Ambil statistik
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_kuis = $pdo->query("SELECT COUNT(*) FROM kuis")->fetchColumn();

include '../includes/header.php';
?>
<!-- Sisa HTML sama seperti sebelumnya -->

<style>
    .admin-wrapper {
        padding: 30px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s;
        border-left: 4px solid #F4B41A;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card .icon {
        font-size: 35px;
        color: #F4B41A;
        margin-bottom: 10px;
    }
    
    .stat-card .number {
        font-size: 32px;
        font-weight: 700;
        color: #1B2A4A;
    }
    
    .stat-card .label {
        color: #666;
        font-size: 14px;
    }
    
    .sidebar {
        background: #1B2A4A;
        border-radius: 15px;
        padding: 20px;
    }
    
    .sidebar .nav-link {
        color: rgba(255,255,255,0.7);
        padding: 12px 20px;
        border-radius: 10px;
        transition: all 0.3s;
        margin-bottom: 5px;
    }
    
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        color: white;
        background: rgba(244, 180, 26, 0.2);
    }
    
    .sidebar .nav-link i {
        width: 25px;
        color: #F4B41A;
    }
</style>

<div class="admin-wrapper">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="sidebar">
                    <div class="text-center mb-4">
                        <div style="width: 70px; height: 70px; background: rgba(244, 180, 26, 0.2); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-cog" style="color: #F4B41A; font-size: 30px;"></i>
                        </div>
                        <h5 style="color: white; margin-top: 10px;">Admin Panel</h5>
                        <p style="color: rgba(255,255,255,0.7); font-size: 12px;">
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </p>
                    </div>
                    <hr style="border-color: rgba(255,255,255,0.1);">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="materi.php">
                            <i class="fas fa-book"></i> Materi
                        </a>
                        <a class="nav-link" href="soal.php">
                            <i class="fas fa-question-circle"></i> Soal Latihan
                        </a>
                        <a class="nav-link" href="kuis.php">
                            <i class="fas fa-puzzle-piece"></i> Kuis
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Pengguna
                        </a>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home"></i> Lihat Website
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 style="color: #1B2A4A; font-weight: 700;">
                        <i class="fas fa-tachometer-alt" style="color: #F4B41A;"></i> Dashboard
                    </h4>
                    <span style="color: #666; font-size: 14px;">
                        <i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
                    </span>
                </div>
                
                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-book"></i></div>
                            <div class="number"><?php echo $total_materi; ?></div>
                            <div class="label">Total Materi</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card" style="border-left-color: #1B2A4A;">
                            <div class="icon"><i class="fas fa-users" style="color: #1B2A4A;"></i></div>
                            <div class="number"><?php echo $total_users; ?></div>
                            <div class="label">Total Pengguna</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card" style="border-left-color: #28a745;">
                            <div class="icon"><i class="fas fa-question-circle" style="color: #28a745;"></i></div>
                            <div class="number"><?php echo $total_soal; ?></div>
                            <div class="label">Total Soal</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card" style="border-left-color: #dc3545;">
                            <div class="icon"><i class="fas fa-puzzle-piece" style="color: #dc3545;"></i></div>
                            <div class="number"><?php echo $total_kuis; ?></div>
                            <div class="label">Total Kuis</div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-header" style="background: white; border-radius: 15px 15px 0 0; border-bottom: 2px solid #f0f0f0; font-weight: 700; color: #1B2A4A;">
                                <i class="fas fa-clock" style="color: #F4B41A;"></i> Aktivitas Terbaru
                            </div>
                            <div class="card-body">
                                <div class="d-flex mb-3">
                                    <div style="width: 40px; height: 40px; background: #1B2A4A; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                        <i class="fas fa-user-plus" style="color: #F4B41A;"></i>
                                    </div>
                                    <div>
                                        <p style="margin: 0; font-weight: 600;">Pengguna baru mendaftar</p>
                                        <small style="color: #666;"><?php echo date('H:i'); ?> hari ini</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div style="width: 40px; height: 40px; background: #F4B41A; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                        <i class="fas fa-plus" style="color: #1B2A4A;"></i>
                                    </div>
                                    <div>
                                        <p style="margin: 0; font-weight: 600;">Materi baru ditambahkan</p>
                                        <small style="color: #666;"><?php echo date('H:i'); ?> hari ini</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-header" style="background: white; border-radius: 15px 15px 0 0; border-bottom: 2px solid #f0f0f0; font-weight: 700; color: #1B2A4A;">
                                <i class="fas fa-chart-bar" style="color: #F4B41A;"></i> Statistik Cepat
                            </div>
                            <div class="card-body">
                                <div style="margin-bottom: 15px;">
                                    <div class="d-flex justify-content-between">
                                        <span>Materi Aktif</span>
                                        <span style="font-weight: 600; color: #1B2A4A;"><?php echo $total_materi; ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: 100%; background: #1B2A4A;"></div>
                                    </div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <div class="d-flex justify-content-between">
                                        <span>Soal Tersedia</span>
                                        <span style="font-weight: 600; color: #1B2A4A;"><?php echo $total_soal; ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: 100%; background: #F4B41A;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>