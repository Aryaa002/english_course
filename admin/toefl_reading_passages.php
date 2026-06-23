<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
if ($section_id == 0) {
    redirect('toefl.php');
}

// Statistik untuk sidebar
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

// Ambil data section
$stmt = $pdo->prepare("SELECT s.*, t.judul as toefl_judul FROM toefl_sections s 
                       LEFT JOIN toefl_tests t ON s.toefl_id = t.id 
                       WHERE s.id = ?");
$stmt->execute([$section_id]);
$section = $stmt->fetch();

if (!$section) {
    redirect('toefl.php');
}

// Ambil reading passages
$stmt = $pdo->prepare("SELECT * FROM toefl_reading_passages WHERE section_id = ? ORDER BY id");
$stmt->execute([$section_id]);
$passages = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_passage'])) {
    $judul = trim($_POST['judul']);
    $teks = trim($_POST['teks']);
    $sumber = trim($_POST['sumber']);
    
    if (empty($judul) || empty($teks)) {
        $error = 'Judul dan teks wajib diisi!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO toefl_reading_passages (section_id, judul, teks, sumber) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$section_id, $judul, $teks, $sumber])) {
            $success = 'Reading passage berhasil ditambahkan!';
            echo '<meta http-equiv="refresh" content="1">';
        } else {
            $error = 'Gagal menambahkan reading passage!';
        }
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM toefl_reading_passages WHERE id = ? AND section_id = ?");
    $stmt->execute([$id, $section_id]);
    redirect('toefl_reading_passages.php?section_id=' . $section_id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Passages TOEFL - English Course</title>
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
        
        .passage-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #28a745; }
        .passage-card:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.12); }
        
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-container label { font-weight: 600; color: #1B2A4A; }
        .form-container .form-control { border-radius: 10px; border: 2px solid #e0e0e0; padding: 10px 15px; }
        .form-container .form-control:focus { border-color: #F4B41A; box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25); }
        .form-container textarea { min-height: 150px; resize: vertical; }
        
        .btn-submit { background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600; border: none; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); color: #1B2A4A; }
        
        .btn-soal { background: #17a2b8; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; }
        .btn-soal:hover { background: #138496; color: white; }
        
        .passage-text { background: #f8f9fa; padding: 15px; border-radius: 10px; max-height: 150px; overflow-y: auto; font-size: 14px; line-height: 1.6; color: #444; }
        
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
                            <h4><i class="fas fa-file-alt" style="color: #28a745;"></i> Reading Passages: <?php echo htmlspecialchars($section['nama']); ?></h4>
                            <p>TOEFL: <?php echo htmlspecialchars($section['toefl_judul']); ?></p>
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
            
            <!-- Form Tambah Reading Passage -->
            <div class="form-container mb-4">
                <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-plus-circle" style="color: #28a745;"></i> Tambah Reading Passage
                </h6>
                <p class="text-muted small">Teks bacaan yang akan digunakan untuk beberapa soal sekaligus</p>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label>Judul Passage *</label>
                            <input type="text" class="form-control" name="judul" placeholder="Contoh: The History of Modern Art" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Teks Bacaan *</label>
                            <textarea class="form-control" name="teks" rows="6" placeholder="Masukkan teks bacaan lengkap..." required></textarea>
                            <small class="text-muted">Teks ini akan ditampilkan bersama soal-soal yang terkait</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Sumber (Opsional)</label>
                            <input type="text" class="form-control" name="sumber" placeholder="Contoh: National Geographic, 2024">
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="tambah_passage" class="btn-submit">
                                <i class="fas fa-save me-2"></i>Simpan Passage
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Daftar Reading Passages -->
            <?php if(count($passages) > 0): ?>
                <?php foreach($passages as $passage): ?>
                <div class="passage-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 style="color: #1B2A4A; font-weight: 600;">
                                <?php echo htmlspecialchars($passage['judul']); ?>
                                <?php if($passage['sumber']): ?>
                                    <span class="badge" style="background: #6c757d; color: white; font-size: 11px;">
                                        <?php echo htmlspecialchars($passage['sumber']); ?>
                                    </span>
                                <?php endif; ?>
                            </h6>
                            <div class="passage-text">
                                <?php echo nl2br(htmlspecialchars(substr($passage['teks'], 0, 300))); ?>
                                <?php if(strlen($passage['teks']) > 300): ?>
                                    <span class="text-muted">...</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ms-3">
                            <a href="toefl_questions.php?section_id=<?php echo $section_id; ?>&passage_id=<?php echo $passage['id']; ?>" class="btn-soal me-2" style="background: #28a745;">
                                <i class="fas fa-plus"></i> Tambah Soal
                            </a>
                            <a href="toefl_reading_passages.php?section_id=<?php echo $section_id; ?>&hapus=<?php echo $passage['id']; ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus passage ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 40px; background: white; border-radius: 15px;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                    <p style="color: #999; margin-top: 10px;">Belum ada reading passage</p>
                    <p class="text-muted">Tambahkan teks bacaan untuk soal reading comprehension</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="toefl_sections.php?toefl_id=<?php echo $section['toefl_id']; ?>" class="btn" style="background: #1B2A4A; color: white; padding: 10px 25px; border-radius: 25px;">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Sections
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