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

// Ambil daftar materi untuk filter
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

// Filter berdasarkan materi
$materi_id = isset($_GET['materi_id']) ? (int)$_GET['materi_id'] : 0;

$query = "SELECT s.*, m.judul as materi_judul FROM soal_latihan s 
          LEFT JOIN materi m ON s.materi_id = m.id";
$params = [];

if ($materi_id > 0) {
    $query .= " WHERE s.materi_id = ?";
    $params[] = $materi_id;
}

$query .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$soal_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Soal - English Course</title>
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
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-mudah { background: #d4edda; color: #155724; }
        .status-sedang { background: #fff3cd; color: #856404; }
        .status-sulit { background: #f8d7da; color: #721c24; }
        
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
                <a href="soal.php" class="nav-link active">
                    <i class="fas fa-question-circle"></i> Soal Latihan
                    <span class="badge"><?php echo $total_soal; ?></span>
                </a>
                <a href="toefl.php" class="nav-link">
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
                            <h4><i class="fas fa-question-circle" style="color: #F4B41A;"></i> Manajemen Soal Latihan</h4>
                            <p>Kelola semua soal latihan</p>
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
                    <span style="color: #666;">Total Soal: <strong><?php echo count($soal_list); ?></strong></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="tambah_soal_bulk.php" class="btn" style="background: #28a745; color: white; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-layer-group me-2"></i>Tambah Soal
                    </a>     
                </div>
            </div>
            
            <!-- Filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" action="" class="d-flex gap-2">
                        <select name="materi_id" class="form-control" style="border-radius: 25px;">
                            <option value="0">Semua Materi</option>
                            <?php foreach($materi_list as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $materi_id == $m['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['judul']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn" style="background: #1B2A4A; color: white; border-radius: 25px;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Table -->
            <div class="table-custom">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 30%;">Pertanyaan</th>
                            <th style="width: 20%;">Materi</th>
                            <th style="width: 15%;">Pilihan</th>
                            <th style="width: 10%;">Jawaban</th>
                            <th style="width: 10%;">Tingkat</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($soal_list) > 0): ?>
                            <?php foreach($soal_list as $soal): ?>
                            <tr>
                                <td><?php echo $soal['id']; ?></td>
                                <td><?php echo substr(htmlspecialchars($soal['pertanyaan']), 0, 60) . '...'; ?></td>
                                <td><span class="status-badge status-mudah"><?php echo htmlspecialchars($soal['materi_judul']); ?></span></td>
                                <td>
                                    <span style="font-size: 12px;">
                                        A: <?php echo htmlspecialchars($soal['pilihan_a']); ?><br>
                                        B: <?php echo htmlspecialchars($soal['pilihan_b']); ?>
                                    </span>
                                </td>
                                <td><strong style="color: #28a745;"><?php echo $soal['jawaban_benar']; ?></strong></td>
                                <td>
                                    <span class="status-badge <?php echo 'status-' . $soal['tingkat_kesulitan']; ?>">
                                        <?php echo ucfirst($soal['tingkat_kesulitan']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_soal.php?id=<?php echo $soal['id']; ?>" class="btn btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="hapus_soal.php?id=<?php echo $soal['id']; ?>" class="btn btn-action btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus soal ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">Belum ada soal latihan</p>
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