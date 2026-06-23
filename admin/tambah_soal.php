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

// Ambil daftar materi
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $materi_id = (int)$_POST['materi_id'];
    $pertanyaan = trim($_POST['pertanyaan']);
    $pilihan_a = trim($_POST['pilihan_a']);
    $pilihan_b = trim($_POST['pilihan_b']);
    $pilihan_c = trim($_POST['pilihan_c']);
    $pilihan_d = trim($_POST['pilihan_d']);
    $jawaban_benar = $_POST['jawaban_benar'];
    $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
    $media_type = $_POST['media_type'];
    $media_url = trim($_POST['media_url']);
    
    // Upload file media
    $media_file = '';
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $allowed = ['mp3', 'wav', 'm4a', 'ogg', 'mp4', 'webm', 'mp4', 'avi', 'mov'];
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $media_name = time() . '_media_' . uniqid() . '.' . $ext;
            $subdir = ($media_type == 'video') ? 'videos/' : 'audios/';
            $media_file = 'uploads/' . $subdir . $media_name;
            move_uploaded_file($_FILES['media_file']['tmp_name'], "../" . $media_file);
        } else {
            $error = 'Format file tidak didukung!';
        }
    }
    
    if (empty($error)) {
        if (empty($materi_id) || empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($jawaban_benar)) {
            $error = 'Field wajib harus diisi!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO soal_latihan (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, tingkat_kesulitan, media_type, media_url, media_file) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$materi_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $jawaban_benar, $tingkat_kesulitan, $media_type, $media_url, $media_file])) {
                $success = 'Soal berhasil ditambahkan!';
                $_POST = array();
                echo '<meta http-equiv="refresh" content="2;url=soal.php">';
            } else {
                $error = 'Gagal menambahkan soal!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Soal - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Sertakan style yang sama seperti tambah_materi.php -->
    <style>
        /* Copy semua style dari tambah_materi.php */
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
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-container label { font-weight: 600; color: #1B2A4A; }
        .form-container .form-control { border-radius: 10px; border: 2px solid #e0e0e0; padding: 10px 15px; }
        .form-container .form-control:focus { border-color: #F4B41A; box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25); }
        .option-group { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 10px; border-left: 3px solid transparent; }
        .option-group:hover { border-left-color: #F4B41A; }
        .btn-submit { background: #F4B41A; color: #1B2A4A; padding: 12px 40px; border-radius: 30px; font-weight: 700; font-size: 16px; border: none; transition: all 0.3s; width: 100%; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); color: #1B2A4A; }
        .upload-box { border: 2px dashed #d0d0d0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; cursor: pointer; background: #fafafa; }
        .upload-box:hover { border-color: #F4B41A; background: rgba(244, 180, 26, 0.05); }
        .upload-box i { font-size: 40px; color: #F4B41A; display: block; margin-bottom: 10px; }
        .file-info { background: #e8f5e9; border-radius: 8px; padding: 10px 15px; margin-top: 10px; display: none; color: #2e7d32; }
        .file-info.show { display: block; }
        .media-section { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #F4B41A; display: none; }
        .media-section.show { display: block; }
        .sidebar-toggle { display: none; background: #1B2A4A; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-size: 20px; cursor: pointer; }
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .overlay.active { display: block; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); width: 280px; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 15px; } .sidebar-toggle { display: block; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <!-- Sidebar Sama Seperti Tambah Materi -->
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
                            <h4><i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Soal Latihan</h4>
                            <p>Tambah soal latihan baru</p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
            
            <!-- Form Tambah Soal -->
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="materi_id">Pilih Materi <span style="color: red;">*</span></label>
                            <select class="form-control" id="materi_id" name="materi_id" required>
                                <option value="">-- Pilih Materi --</option>
                                <?php foreach($materi_list as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['judul']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="pertanyaan">Pertanyaan <span style="color: red;">*</span></label>
                            <textarea class="form-control" id="pertanyaan" name="pertanyaan" rows="3" placeholder="Masukkan pertanyaan..." required></textarea>
                        </div>
                        
                        <div class="col-md-12">
                            <label>Pilihan Jawaban <span style="color: red;">*</span></label>
                            <div class="option-group">
                                <div class="row align-items-center">
                                    <div class="col-md-1"><span style="font-weight: 700;">A.</span></div>
                                    <div class="col-md-11"><input type="text" class="form-control" name="pilihan_a" required></div>
                                </div>
                            </div>
                            <div class="option-group">
                                <div class="row align-items-center">
                                    <div class="col-md-1"><span style="font-weight: 700;">B.</span></div>
                                    <div class="col-md-11"><input type="text" class="form-control" name="pilihan_b" required></div>
                                </div>
                            </div>
                            <div class="option-group">
                                <div class="row align-items-center">
                                    <div class="col-md-1"><span style="font-weight: 700;">C.</span></div>
                                    <div class="col-md-11"><input type="text" class="form-control" name="pilihan_c" placeholder="Opsional"></div>
                                </div>
                            </div>
                            <div class="option-group">
                                <div class="row align-items-center">
                                    <div class="col-md-1"><span style="font-weight: 700;">D.</span></div>
                                    <div class="col-md-11"><input type="text" class="form-control" name="pilihan_d" placeholder="Opsional"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="jawaban_benar">Jawaban Benar <span style="color: red;">*</span></label>
                            <select class="form-control" id="jawaban_benar" name="jawaban_benar" required>
                                <option value="">Pilih Jawaban</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="tingkat_kesulitan">Tingkat Kesulitan</label>
                            <select class="form-control" id="tingkat_kesulitan" name="tingkat_kesulitan">
                                <option value="mudah">Mudah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="media_type">Tipe Media</label>
                            <select class="form-control" id="media_type" name="media_type">
                                <option value="none">Tidak Ada</option>
                                <option value="audio">🎧 Audio</option>
                                <option value="video">🎬 Video</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save me-2"></i>Simpan Soal
                            </button>
                        </div>
                    </div>
                </form>
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