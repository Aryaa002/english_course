<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Statistik untuk sidebar
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

// Ambil semua TOEFL
$stmt = $pdo->query("SELECT t.*, 
                     (SELECT COUNT(*) FROM toefl_sections WHERE toefl_id = t.id) as total_sections,
                     (SELECT COUNT(*) FROM toefl_results WHERE toefl_id = t.id) as total_peserta
                     FROM toefl_tests t 
                     ORDER BY t.created_at DESC");
$toefl_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen TOEFL - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        .sidebar { width: 280px; background: #1B2A4A; color: white; padding: 0; position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; transition: all 0.3s; }
        .sidebar-brand { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-brand h3 { color: white; font-weight: 700; margin: 0; }
        .sidebar-brand h3 span { color: #F4B41A; }
        .sidebar-brand .subtitle { color: rgba(255,255,255,0.6); font-size: 13px; margin-top: 5px; }
        .sidebar-user { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-user .avatar { width: 60px; height: 60px; border-radius: 50%; background: rgba(244, 180, 26, 0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; border: 3px solid #F4B41A; }
        .sidebar-user .avatar i { font-size: 28px; color: #F4B41A; }
        .sidebar-user .name { font-weight: 600; font-size: 16px; }
        .sidebar-user .role { color: rgba(255,255,255,0.6); font-size: 12px; }
        .sidebar-nav { padding: 15px 0; }
        .sidebar-nav .nav-label { padding: 10px 25px; font-size: 11px; text-transform: uppercase; color: rgba(255,255,255,0.4); letter-spacing: 1px; }
        .sidebar-nav .nav-link { display: flex; align-items: center; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-nav .nav-link:hover { background: rgba(255,255,255,0.05); color: white; border-left-color: #F4B41A; }
        .sidebar-nav .nav-link.active { background: rgba(244, 180, 26, 0.1); color: #F4B41A; border-left-color: #F4B41A; }
        .sidebar-nav .nav-link i { width: 24px; margin-right: 12px; font-size: 16px; }
        .sidebar-nav .nav-link .badge { margin-left: auto; background: #F4B41A; color: #1B2A4A; }
        
        .main-content { margin-left: 280px; flex: 1; padding: 20px 30px; min-height: 100vh; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #e0e0e0; margin-bottom: 25px; }
        .top-bar .page-title h4 { color: #1B2A4A; font-weight: 700; margin: 0; }
        .top-bar .page-title p { color: #999; font-size: 14px; margin: 0; }
        .top-bar .user-info { display: flex; align-items: center; gap: 15px; }
        .top-bar .user-info .date { color: #666; font-size: 14px; }
        .top-bar .user-info .logout-btn { background: #dc3545; color: white; border: none; padding: 8px 20px; border-radius: 25px; font-weight: 600; text-decoration: none; transition: all 0.3s; }
        .top-bar .user-info .logout-btn:hover { background: #c82333; transform: translateY(-2px); }
        
        .table-custom { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .table-custom thead { background: #1B2A4A; color: white; }
        .table-custom tbody tr:hover { background: rgba(244, 180, 26, 0.05); }
        
        .btn-action { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin: 0 3px; }
        .btn-edit { background: #F4B41A; color: #1B2A4A; }
        .btn-edit:hover { background: #d4a015; color: #1B2A4A; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; color: white; }
        .btn-section { background: #17a2b8; color: white; }
        .btn-section:hover { background: #138496; color: white; }
        .btn-nilai { background: #28a745; color: white; }
        .btn-nilai:hover { background: #218838; color: white; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .sidebar-toggle { display: none; background: #1B2A4A; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-size: 20px; cursor: pointer; }
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .overlay.active { display: block; }
        
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); width: 280px; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 15px; } .sidebar-toggle { display: block; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h3>English <span>Course</span></h3>
                <div class="subtitle">Admin Panel</div>
            </div>
            <div class="sidebar-user">
                <div class="avatar"><i class="fas fa-user-cog"></i></div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
                <div class="role">Administrator</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main Menu</div>
                <a href="index.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="materi.php" class="nav-link">
                    <i class="fas fa-book"></i> Materi
                    <span class="badge"><?php echo $total_materi; ?></span>
                </a>
                <a href="soal.php" class="nav-link">
                    <i class="fas fa-question-circle"></i> Soal Latihan
                    <span class="badge"><?php echo $total_soal; ?></span>
                </a>
                <a href="toefl.php" class="nav-link active">
                    <i class="fas fa-graduation-cap"></i> TOEFL
                    <span class="badge"><?php echo $total_toefl; ?></span>
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Pengguna
                    <span class="badge"><?php echo $total_users; ?></span>
                </a>
                <div class="nav-label mt-3">Lainnya</div>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> Lihat Website
                </a>
                <a href="../logout.php" class="nav-link" style="color: #dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <div class="d-flex align-items-center">
                        <button class="sidebar-toggle me-3" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h4><i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> Manajemen TOEFL</h4>
                            <p>Kelola semua test TOEFL</p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span style="color: #666;">Total TOEFL: <strong><?php echo count($toefl_list); ?></strong></span>
                </div>
                <a href="tambah_toefl.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                    <i class="fas fa-plus me-2"></i>Tambah TOEFL
                </a>
            </div>
            
            <!-- Table -->
            <div class="table-custom">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 20%;">Judul</th>
                            <th style="width: 15%;">Sections</th>
                            <th style="width: 10%;">Waktu</th>
                            <th style="width: 10%;">Passing</th>
                            <th style="width: 10%;">Peserta</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($toefl_list) > 0): ?>
                            <?php foreach($toefl_list as $toefl): ?>
                            <tr>
                                <td><?php echo $toefl['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($toefl['judul']); ?></strong></td>
                                <td><?php echo $toefl['total_sections']; ?></td>
                                <td><?php echo $toefl['waktu']; ?> menit</td>
                                <td><span class="badge" style="background: #F4B41A; color: #1B2A4A;"><?php echo $toefl['passing_grade']; ?></span></td>
                                <td><?php echo $toefl['total_peserta']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $toefl['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $toefl['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_toefl.php?id=<?php echo $toefl['id']; ?>" class="btn btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="toefl_sections.php?toefl_id=<?php echo $toefl['id']; ?>" class="btn btn-action btn-section">
                                        <i class="fas fa-layer-group"></i>
                                    </a>
                                    <a href="toefl_nilai.php?toefl_id=<?php echo $toefl['id']; ?>" class="btn btn-action btn-nilai">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <a href="hapus_toefl.php?id=<?php echo $toefl['id']; ?>" class="btn btn-action btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus TOEFL ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">Belum ada TOEFL</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('overlay').classList.remove('active');
            }
        });
    </script>
</body>
</html>