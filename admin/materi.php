<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query
$query = "SELECT * FROM materi";
$params = [];

if ($search) {
    $query .= " WHERE judul LIKE ? OR kategori LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materi = $stmt->fetchAll();

// Total data untuk pagination
$countQuery = "SELECT COUNT(*) FROM materi";
if ($search) {
    $countQuery .= " WHERE judul LIKE ? OR kategori LIKE ?";
}
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Ambil statistik
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Materi - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Sertakan CSS yang sama seperti dashboard admin -->
    <style>
        /* Copy semua style dari admin/index.php */
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
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #d4edda; color: #155724; }
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
                <a href="materi.php" class="nav-link active">
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
            <div class="top-bar">
                <div class="page-title">
                    <div class="d-flex align-items-center">
                        <button class="sidebar-toggle me-3" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h4><i class="fas fa-book" style="color: #F4B41A;"></i> Manajemen Materi</h4>
                            <p>Kelola semua materi pembelajaran</p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
            
            <!-- Konten Materi -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span style="color: #666;">Total Materi: <strong><?php echo $total; ?></strong></span>
                </div>
                <a href="tambah_materi.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                    <i class="fas fa-plus me-2"></i>Tambah Materi
                </a>
            </div>
            
            <!-- Search -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" action="">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Cari materi..." 
                                   value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 25px 0 0 25px;">
                            <button class="btn" type="submit" style="background: #1B2A4A; color: white; border-radius: 0 25px 25px 0;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Table -->
            <div class="table-custom">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 25%;">Judul</th>
                            <th style="width: 20%;">Kategori</th>
                            <th style="width: 15%;">Tingkat</th>
                            <th style="width: 15%;">Durasi</th>
                            <th style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($materi) > 0): ?>
                            <?php foreach($materi as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($m['judul']); ?></strong></td>
                                <td><span class="status-badge"><?php echo htmlspecialchars($m['kategori']); ?></span></td>
                                <td><?php echo htmlspecialchars($m['tingkat']); ?></td>
                                <td><?php echo htmlspecialchars($m['durasi']); ?></td>
                                <td>
                                    <a href="edit_materi.php?id=<?php echo $m['id']; ?>" class="btn btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="hapus_materi.php?id=<?php echo $m['id']; ?>" class="btn btn-action btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus materi ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">Belum ada materi</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
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