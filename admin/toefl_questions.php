<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$audio_group_id = isset($_GET['audio_group_id']) ? (int)$_GET['audio_group_id'] : 0;
$passage_id = isset($_GET['passage_id']) ? (int)$_GET['passage_id'] : 0;

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

// Ambil audio groups untuk dropdown
$stmt = $pdo->prepare("SELECT * FROM toefl_audio_groups WHERE section_id = ?");
$stmt->execute([$section_id]);
$audio_groups = $stmt->fetchAll();

// Ambil reading passages untuk dropdown
$stmt = $pdo->prepare("SELECT * FROM toefl_reading_passages WHERE section_id = ?");
$stmt->execute([$section_id]);
$passages = $stmt->fetchAll();

// Ambil soal dengan filter
$query = "SELECT q.*, 
          ag.judul as audio_judul,
          rp.judul as passage_judul
          FROM toefl_questions q 
          LEFT JOIN toefl_audio_groups ag ON q.audio_group_id = ag.id
          LEFT JOIN toefl_reading_passages rp ON q.passage_id = rp.id
          WHERE q.section_id = ?";
$params = [$section_id];

if ($audio_group_id > 0) {
    $query .= " AND q.audio_group_id = ?";
    $params[] = $audio_group_id;
}

if ($passage_id > 0) {
    $query .= " AND q.passage_id = ?";
    $params[] = $passage_id;
}

$query .= " ORDER BY q.id";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$questions = $stmt->fetchAll();

// Buat folder uploads
$folders = ['../uploads/', '../uploads/videos/', '../uploads/audios/'];
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_soal'])) {
    $type = $_POST['type'];
    $audio_group_id_val = (int)$_POST['audio_group_id'];
    $passage_id_val = (int)$_POST['passage_id'];
    $pertanyaan = trim($_POST['pertanyaan']);
    $pilihan_a = trim($_POST['pilihan_a']);
    $pilihan_b = trim($_POST['pilihan_b']);
    $pilihan_c = trim($_POST['pilihan_c']);
    $pilihan_d = trim($_POST['pilihan_d']);
    $pilihan_e = trim($_POST['pilihan_e']);
    $jawaban_benar = trim($_POST['jawaban_benar']);
    $poin = (int)$_POST['poin'];
    $media_type = $_POST['media_type'];
    $media_url = trim($_POST['media_url']);
    
    // Upload media
    $media_file = '';
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $allowed = ['mp3', 'wav', 'm4a', 'ogg', 'mp4', 'webm', 'avi', 'mov'];
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
        if (empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($jawaban_benar)) {
            $error = 'Field wajib harus diisi!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO toefl_questions (section_id, audio_group_id, passage_id, type, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, poin, media_type, media_url, media_file) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$section_id, $audio_group_id_val, $passage_id_val, $type, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $pilihan_e, $jawaban_benar, $poin, $media_type, $media_url, $media_file])) {
                $success = 'Soal berhasil ditambahkan!';
                echo '<meta http-equiv="refresh" content="1">';
            } else {
                $error = 'Gagal menambahkan soal!';
            }
        }
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM toefl_questions WHERE id = ? AND section_id = ?");
    $stmt->execute([$id, $section_id]);
    
    // Redirect dengan parameter yang sama
    $redirect_url = 'toefl_questions.php?section_id=' . $section_id;
    if ($audio_group_id > 0) {
        $redirect_url .= '&audio_group_id=' . $audio_group_id;
    }
    if ($passage_id > 0) {
        $redirect_url .= '&passage_id=' . $passage_id;
    }
    redirect($redirect_url);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal TOEFL - English Course</title>
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
        
        .question-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .question-card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.12); }
        
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-container label { font-weight: 600; color: #1B2A4A; }
        .form-container .form-control { border-radius: 10px; border: 2px solid #e0e0e0; padding: 10px 15px; }
        .form-container .form-control:focus { border-color: #F4B41A; box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25); }
        .form-container textarea { min-height: 80px; resize: vertical; }
        
        .option-group { background: #f8f9fa; border-radius: 10px; padding: 10px 15px; margin-bottom: 8px; }
        .option-correct { border-left: 3px solid #28a745; background: #d4edda; }
        
        .btn-tambah { background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600; border: none; transition: all 0.3s; }
        .btn-tambah:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); color: #1B2A4A; }
        
        .upload-box { border: 2px dashed #d0d0d0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; cursor: pointer; background: #fafafa; }
        .upload-box:hover { border-color: #F4B41A; background: rgba(244, 180, 26, 0.05); }
        .upload-box i { font-size: 40px; color: #F4B41A; display: block; margin-bottom: 10px; }
        .file-info { background: #e8f5e9; border-radius: 8px; padding: 10px 15px; margin-top: 10px; display: none; color: #2e7d32; }
        .file-info.show { display: block; }
        
        .filter-card { background: white; border-radius: 15px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .filter-card .btn-filter { border-radius: 20px; padding: 5px 15px; font-size: 13px; }
        
        .badge-audio { background: #17a2b8; color: white; }
        .badge-passage { background: #fd7e14; color: white; }
        
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
                            <h4><i class="fas fa-question-circle" style="color: #F4B41A;"></i> Soal: <?php echo htmlspecialchars($section['nama']); ?></h4>
                            <p>
                                TOEFL: <?php echo htmlspecialchars($section['toefl_judul']); ?>
                                <?php if($audio_group_id > 0): ?>
                                    | Audio Group ID: <?php echo $audio_group_id; ?>
                                <?php endif; ?>
                                <?php if($passage_id > 0): ?>
                                    | Passage ID: <?php echo $passage_id; ?>
                                <?php endif; ?>
                            </p>
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
            
            <!-- Form Tambah Soal -->
            <div class="form-container mb-4">
                <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Soal
                </h6>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Tipe Soal *</label>
                            <select class="form-control" name="type" required>
                                <option value="listening">Listening</option>
                                <option value="structure">Structure</option>
                                <option value="reading">Reading</option>
                                <option value="writing">Writing</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Audio Group</label>
                            <select class="form-control" name="audio_group_id">
                                <option value="0">Tidak Ada</option>
                                <?php foreach($audio_groups as $ag): ?>
                                <option value="<?php echo $ag['id']; ?>" <?php echo $audio_group_id == $ag['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ag['judul']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Reading Passage</label>
                            <select class="form-control" name="passage_id">
                                <option value="0">Tidak Ada</option>
                                <?php foreach($passages as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $passage_id == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['judul']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Poin</label>
                            <input type="number" class="form-control" name="poin" value="1" min="1">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label>Pertanyaan *</label>
                            <textarea class="form-control" name="pertanyaan" rows="2" required></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="option-group">
                                <label>A. *</label>
                                <input type="text" class="form-control" name="pilihan_a" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="option-group">
                                <label>B. *</label>
                                <input type="text" class="form-control" name="pilihan_b" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="option-group">
                                <label>C.</label>
                                <input type="text" class="form-control" name="pilihan_c">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="option-group">
                                <label>D.</label>
                                <input type="text" class="form-control" name="pilihan_d">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="option-group">
                                <label>E.</label>
                                <input type="text" class="form-control" name="pilihan_e">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Jawaban Benar *</label>
                            <select class="form-control" name="jawaban_benar" required>
                                <option value="">Pilih Jawaban</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Tipe Media</label>
                            <select class="form-control" name="media_type">
                                <option value="none">Tidak Ada</option>
                                <option value="audio">Audio</option>
                                <option value="video">Video</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label>URL Media (Opsional)</label>
                            <input type="text" class="form-control" name="media_url" placeholder="https://example.com/media.mp3">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="upload-box" onclick="document.getElementById('media_file').click()">
                                <i class="fas fa-upload"></i>
                                <p><strong>Klik untuk upload media</strong></p>
                                <small>Support: MP3, WAV, M4A, OGG, MP4, WEBM (Max 50MB)</small>
                                <input type="file" class="d-none" id="media_file" name="media_file" accept="audio/*,video/*" onchange="showFileInfo(this)">
                                <div id="media_file_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="file_name"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" name="tambah_soal" class="btn-tambah w-100">
                                <i class="fas fa-save me-2"></i>Simpan Soal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Filter Info -->
            <?php if($audio_group_id > 0 || $passage_id > 0): ?>
            <div class="filter-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary">Filter Aktif:</span>
                        <?php if($audio_group_id > 0): ?>
                            <span class="badge badge-audio me-2">
                                <i class="fas fa-music"></i> Audio Group ID: <?php echo $audio_group_id; ?>
                            </span>
                        <?php endif; ?>
                        <?php if($passage_id > 0): ?>
                            <span class="badge badge-passage">
                                <i class="fas fa-file-alt"></i> Passage ID: <?php echo $passage_id; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <a href="toefl_questions.php?section_id=<?php echo $section_id; ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Hapus Filter
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Daftar Soal -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span style="color: #666;">Total Soal: <strong><?php echo count($questions); ?></strong></span>
                </div>
            </div>
            
            <?php if(count($questions) > 0): ?>
                <?php $no = 1; foreach($questions as $q): ?>
                <div class="question-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="mb-2">
                                <span class="badge" style="background: #1B2A4A; color: white;">Soal <?php echo $no++; ?></span>
                                <span class="badge" style="background: #F4B41A; color: #1B2A4A;"><?php echo ucfirst($q['type']); ?></span>
                                <?php if($q['audio_group_id'] > 0): ?>
                                    <span class="badge badge-audio">
                                        <i class="fas fa-music"></i> <?php echo htmlspecialchars($q['audio_judul']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if($q['passage_id'] > 0): ?>
                                    <span class="badge badge-passage">
                                        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($q['passage_judul']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge" style="background: #28a745; color: white;">Poin: <?php echo $q['poin']; ?></span>
                                <?php if($q['media_type'] != 'none'): ?>
                                    <span class="badge" style="background: #17a2b8; color: white;">
                                        <i class="fas fa-<?php echo $q['media_type'] == 'video' ? 'video' : 'music'; ?>"></i>
                                        <?php echo ucfirst($q['media_type']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p style="font-weight: 600; margin-bottom: 10px; color: #1B2A4A;">
                                <?php echo htmlspecialchars($q['pertanyaan']); ?>
                            </p>
                            <div style="font-size: 14px;">
                                <div class="option-group <?php echo $q['jawaban_benar'] == 'A' ? 'option-correct' : ''; ?>">
                                    A. <?php echo htmlspecialchars($q['pilihan_a']); ?>
                                </div>
                                <div class="option-group <?php echo $q['jawaban_benar'] == 'B' ? 'option-correct' : ''; ?>">
                                    B. <?php echo htmlspecialchars($q['pilihan_b']); ?>
                                </div>
                                <?php if($q['pilihan_c']): ?>
                                <div class="option-group <?php echo $q['jawaban_benar'] == 'C' ? 'option-correct' : ''; ?>">
                                    C. <?php echo htmlspecialchars($q['pilihan_c']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if($q['pilihan_d']): ?>
                                <div class="option-group <?php echo $q['jawaban_benar'] == 'D' ? 'option-correct' : ''; ?>">
                                    D. <?php echo htmlspecialchars($q['pilihan_d']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if($q['pilihan_e']): ?>
                                <div class="option-group <?php echo $q['jawaban_benar'] == 'E' ? 'option-correct' : ''; ?>">
                                    E. <?php echo htmlspecialchars($q['pilihan_e']); ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <strong style="color: #28a745;">Jawaban Benar: <?php echo $q['jawaban_benar']; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="toefl_questions.php?section_id=<?php echo $section_id; ?>&hapus=<?php echo $q['id']; ?><?php echo $audio_group_id > 0 ? '&audio_group_id=' . $audio_group_id : ''; ?><?php echo $passage_id > 0 ? '&passage_id=' . $passage_id : ''; ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus soal ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 40px; background: white; border-radius: 15px;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                    <p style="color: #999; margin-top: 10px;">Belum ada soal</p>
                    <p class="text-muted">Tambahkan soal untuk section ini</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="toefl_sections.php?toefl_id=<?php echo $section['toefl_id']; ?>" class="btn" style="background: #1B2A4A; color: white; padding: 10px 25px; border-radius: 25px;">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Sections
                </a>
                <?php if($audio_group_id > 0): ?>
                <a href="toefl_audio_groups.php?section_id=<?php echo $section_id; ?>" class="btn" style="background: #17a2b8; color: white; padding: 10px 25px; border-radius: 25px;">
                    <i class="fas fa-music me-2"></i>Kembali ke Audio Groups
                </a>
                <?php endif; ?>
                <?php if($passage_id > 0): ?>
                <a href="toefl_reading_passages.php?section_id=<?php echo $section_id; ?>" class="btn" style="background: #fd7e14; color: white; padding: 10px 25px; border-radius: 25px;">
                    <i class="fas fa-file-alt me-2"></i>Kembali ke Reading Passages
                </a>
                <?php endif; ?>
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
    </script>
</body>
</html>