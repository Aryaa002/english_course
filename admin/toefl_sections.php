<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$toefl_id = isset($_GET['toefl_id']) ? (int)$_GET['toefl_id'] : 0;
if ($toefl_id == 0) {
    redirect('toefl.php');
}

// Statistik untuk sidebar
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM toefl_tests WHERE id = ?");
$stmt->execute([$toefl_id]);
$toefl = $stmt->fetch();

if (!$toefl) {
    redirect('toefl.php');
}

// Ambil sections
$stmt = $pdo->prepare("SELECT * FROM toefl_sections WHERE toefl_id = ? ORDER BY urutan");
$stmt->execute([$toefl_id]);
$sections = $stmt->fetchAll();

// Proses tambah section
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_section'])) {
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $urutan = (int)$_POST['urutan'];
    
    if (empty($nama)) {
        $error = 'Nama section harus diisi!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO toefl_sections (toefl_id, nama, deskripsi, urutan) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$toefl_id, $nama, $deskripsi, $urutan])) {
            $success = 'Section berhasil ditambahkan!';
            echo '<meta http-equiv="refresh" content="1">';
        } else {
            $error = 'Gagal menambahkan section!';
        }
    }
}

// Proses hapus section
if (isset($_GET['hapus'])) {
    $section_id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM toefl_sections WHERE id = ? AND toefl_id = ?");
    $stmt->execute([$section_id, $toefl_id]);
    redirect('toefl_sections.php?toefl_id=' . $toefl_id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOEFL Sections - English Course</title>
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
        
        .section-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #F4B41A; transition: all 0.3s; }
        .section-card:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.12); }
        
        .btn-soal { background: #17a2b8; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; }
        .btn-soal:hover { background: #138496; color: white; }
        .btn-audio { background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; }
        .btn-audio:hover { background: #218838; color: white; }
        .btn-passage { background: #fd7e14; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; }
        .btn-passage:hover { background: #e06b0a; color: white; }
        
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-container label { font-weight: 600; color: #1B2A4A; }
        .form-container .form-control { border-radius: 10px; border: 2px solid #e0e0e0; padding: 10px 15px; }
        .form-container .form-control:focus { border-color: #F4B41A; box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25); }
        .btn-submit { background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600; border: none; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); color: #1B2A4A; }
        
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
                            <h4><i class="fas fa-layer-group" style="color: #F4B41A;"></i> Sections: <?php echo htmlspecialchars($toefl['judul']); ?></h4>
                            <p>Total Sections: <?php echo count($sections); ?></p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Form Tambah Section -->
            <div class="form-container mb-4">
                <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Section
                </h6>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Nama Section *</label>
                            <input type="text" class="form-control" name="nama" placeholder="Contoh: Listening Comprehension" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Deskripsi</label>
                            <input type="text" class="form-control" name="deskripsi" placeholder="Deskripsi section">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Urutan</label>
                            <input type="number" class="form-control" name="urutan" value="<?php echo count($sections) + 1; ?>" min="1">
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" name="tambah_section" class="btn-submit w-100">
                                <i class="fas fa-save me-2"></i>Tambah
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Daftar Sections -->
            <?php if(count($sections) > 0): ?>
                <?php foreach($sections as $section): ?>
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 style="color: #1B2A4A; font-weight: 600;">
                                <span class="badge" style="background: #F4B41A; color: #1B2A4A;"><?php echo $section['urutan']; ?></span>
                                <?php echo htmlspecialchars($section['nama']); ?>
                            </h5>
                            <p style="color: #666; margin: 0; font-size: 14px;"><?php echo htmlspecialchars($section['deskripsi']); ?></p>
                        </div>
                        <div>
                            <a href="toefl_questions.php?section_id=<?php echo $section['id']; ?>" class="btn-soal me-2">
                                <i class="fas fa-question-circle"></i> Soal
                            </a>
                            <a href="toefl_audio_groups.php?section_id=<?php echo $section['id']; ?>" class="btn-audio me-2">
                                <i class="fas fa-music"></i> Audio
                            </a>
                            <a href="toefl_reading_passages.php?section_id=<?php echo $section['id']; ?>" class="btn-passage me-2">
                                <i class="fas fa-file-alt"></i> Reading
                            </a>
                            <a href="toefl_sections.php?toefl_id=<?php echo $toefl_id; ?>&hapus=<?php echo $section['id']; ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus section ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 40px; background: white; border-radius: 15px;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                    <p style="color: #999; margin-top: 10px;">Belum ada section</p>
                    <p class="text-muted">Tambahkan section untuk TOEFL ini</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="toefl.php" class="btn" style="background: #1B2A4A; color: white; padding: 10px 25px; border-radius: 25px;">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke TOEFL
                </a>
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