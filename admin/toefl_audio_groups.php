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

// Buat folder uploads
$folders = ['../uploads/', '../uploads/videos/', '../uploads/audios/'];
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
}

// Ambil audio groups
$stmt = $pdo->prepare("SELECT * FROM toefl_audio_groups WHERE section_id = ? ORDER BY id");
$stmt->execute([$section_id]);
$audio_groups = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_audio'])) {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $audio_type = $_POST['audio_type'];
    $media_type = $_POST['media_type'];
    $durasi = (int)$_POST['durasi'];
    $media_url = trim($_POST['media_url']);
    
    // Upload file
    $media_file = '';
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $allowed = ['mp3', 'wav', 'm4a', 'ogg', 'mp4', 'webm'];
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $media_name = time() . '_audio_' . uniqid() . '.' . $ext;
            $subdir = ($media_type == 'video') ? 'videos/' : 'audios/';
            $media_file = 'uploads/' . $subdir . $media_name;
            move_uploaded_file($_FILES['media_file']['tmp_name'], "../" . $media_file);
        } else {
            $error = 'Format file tidak didukung!';
        }
    }
    
    if (empty($error)) {
        if (empty($judul)) {
            $error = 'Judul harus diisi!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO toefl_audio_groups (section_id, judul, deskripsi, audio_type, media_type, media_url, media_file, durasi) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$section_id, $judul, $deskripsi, $audio_type, $media_type, $media_url, $media_file, $durasi])) {
                $success = 'Audio group berhasil ditambahkan!';
                echo '<meta http-equiv="refresh" content="1">';
            } else {
                $error = 'Gagal menambahkan audio group!';
            }
        }
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM toefl_audio_groups WHERE id = ? AND section_id = ?");
    $stmt->execute([$id, $section_id]);
    redirect('toefl_audio_groups.php?section_id=' . $section_id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Groups TOEFL - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copy semua style dari toefl_sections.php */
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
        .audio-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #17a2b8; }
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-container label { font-weight: 600; color: #1B2A4A; }
        .form-container .form-control { border-radius: 10px; border: 2px solid #e0e0e0; padding: 10px 15px; }
        .form-container .form-control:focus { border-color: #F4B41A; box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25); }
        .upload-box { border: 2px dashed #d0d0d0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; cursor: pointer; background: #fafafa; }
        .upload-box:hover { border-color: #F4B41A; background: rgba(244, 180, 26, 0.05); }
        .upload-box i { font-size: 40px; color: #F4B41A; display: block; margin-bottom: 10px; }
        .file-info { background: #e8f5e9; border-radius: 8px; padding: 10px 15px; margin-top: 10px; display: none; color: #2e7d32; }
        .file-info.show { display: block; }
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
                            <h4><i class="fas fa-music" style="color: #F4B41A;"></i> Audio Groups: <?php echo htmlspecialchars($section['nama']); ?></h4>
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
            
            <!-- Form Tambah Audio -->
            <div class="form-container mb-4">
                <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Audio Group
                </h6>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Judul Audio *</label>
                            <input type="text" class="form-control" name="judul" placeholder="Contoh: Conversation 1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Deskripsi</label>
                            <input type="text" class="form-control" name="deskripsi" placeholder="Deskripsi audio">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Jenis Audio</label>
                            <select class="form-control" name="audio_type">
                                <option value="dialogue">Dialogue</option>
                                <option value="lecture">Lecture</option>
                                <option value="conversation">Conversation</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Tipe Media</label>
                            <select class="form-control" name="media_type">
                                <option value="audio">Audio</option>
                                <option value="video">Video</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Durasi (detik)</label>
                            <input type="number" class="form-control" name="durasi" placeholder="120" value="120">
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <button type="submit" name="tambah_audio" class="btn-submit w-100">
                                <i class="fas fa-save me-2"></i>Tambah
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="upload-box" onclick="document.getElementById('media_file').click()">
                                <i class="fas fa-upload"></i>
                                <p><strong>Klik untuk upload audio/video</strong></p>
                                <small>Support: MP3, WAV, M4A, MP4, WEBM (Max 50MB)</small>
                                <input type="file" class="d-none" id="media_file" name="media_file" accept="audio/*,video/*" onchange="showFileInfo(this)">
                                <div id="media_file_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="file_name"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Atau masukkan URL media</label>
                            <input type="text" class="form-control" name="media_url" placeholder="https://example.com/audio.mp3">
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Daftar Audio Groups -->
            <?php if(count($audio_groups) > 0): ?>
                <?php foreach($audio_groups as $audio): ?>
                <div class="audio-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 style="color: #1B2A4A; font-weight: 600;">
                                <?php echo htmlspecialchars($audio['judul']); ?>
                                <span class="badge" style="background: #17a2b8; color: white; font-size: 11px;">
                                    <?php echo ucfirst($audio['audio_type']); ?>
                                </span>
                            </h6>
                            <p style="color: #666; margin: 0; font-size: 13px;">
                                <?php echo htmlspecialchars($audio['deskripsi']); ?>
                                <?php if($audio['durasi']): ?>
                                    <span class="ms-2"><i class="fas fa-clock"></i> <?php echo $audio['durasi']; ?> detik</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <a href="toefl_questions.php?section_id=<?php echo $section_id; ?>&audio_group_id=<?php echo $audio['id']; ?>" 
                               class="btn btn-sm" style="background: #28a745; color: white;">
                                <i class="fas fa-plus"></i> Tambah Soal
                            </a>
                            <a href="toefl_audio_groups.php?section_id=<?php echo $section_id; ?>&hapus=<?php echo $audio['id']; ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 40px; background: white; border-radius: 15px;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                    <p style="color: #999; margin-top: 10px;">Belum ada audio group</p>
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
        
        function showFileInfo(input) {
            var file = input.files[0];
            var info = document.getElementById('media_file_info');
            var nameSpan = document.getElementById('file_name');
            
            if (file) {
                var fileSize = (file.size / (1024 * 1024)).toFixed(2);
                nameSpan.textContent = file.name + ' (' + fileSize + ' MB)';
                info.classList.add('show');
            } else {
                info.classList.remove('show');
            }
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