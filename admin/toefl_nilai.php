<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$kuis_id = isset($_GET['toefl_id']) ? (int)$_GET['toefl_id'] : 0;
if ($kuis_id == 0) {
    redirect('toefl.php');
}

// Statistik untuk sidebar
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

// Ambil data TOEFL
$stmt = $pdo->prepare("SELECT * FROM toefl_tests WHERE id = ?");
$stmt->execute([$kuis_id]);
$toefl = $stmt->fetch();

if (!$toefl) {
    redirect('toefl.php');
}

// Ambil semua nilai peserta
$stmt = $pdo->prepare("SELECT h.*, u.full_name, u.username 
                       FROM toefl_results h 
                       LEFT JOIN users u ON h.user_id = u.id 
                       WHERE h.toefl_id = ? 
                       ORDER BY h.total_skor DESC");
$stmt->execute([$kuis_id]);
$nilai_list = $stmt->fetchAll();

// Hitung statistik
$total_peserta = count($nilai_list);
$total_lulus = 0;
$total_tidak_lulus = 0;
$total_nilai = 0;
$nilai_tertinggi = 0;

foreach($nilai_list as $n) {
    $is_lulus = $n['total_skor'] >= $toefl['passing_grade'];
    if ($is_lulus) {
        $total_lulus++;
    } else {
        $total_tidak_lulus++;
    }
    $total_nilai += $n['total_skor'];
    if ($n['total_skor'] > $nilai_tertinggi) {
        $nilai_tertinggi = $n['total_skor'];
    }
}

$rata_rata = $total_peserta > 0 ? round($total_nilai / $total_peserta, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai TOEFL - English Course</title>
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
        .stats-card { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stats-card .number { font-size: 32px; font-weight: 700; color: #1B2A4A; }
        .stats-card .label { color: #666; font-size: 14px; }
        .badge-lulus { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-tidak-lulus { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
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
                            <h4><i class="fas fa-chart-bar" style="color: #F4B41A;"></i> Nilai TOEFL: <?php echo htmlspecialchars($toefl['judul']); ?></h4>
                            <p>Passing Grade: <strong><?php echo $toefl['passing_grade']; ?></strong> | Skala 0-120</p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
            
            <!-- Statistik -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="number"><?php echo $total_peserta; ?></div>
                        <div class="label">Total Peserta</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="number" style="color: #28a745;"><?php echo $total_lulus; ?></div>
                        <div class="label">Lulus</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="number" style="color: #dc3545;"><?php echo $total_tidak_lulus; ?></div>
                        <div class="label">Tidak Lulus</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="number" style="color: #F4B41A;"><?php echo $rata_rata; ?></div>
                        <div class="label">Rata-rata Nilai</div>
                    </div>
                </div>
            </div>
            
            <!-- Tabel Nilai -->
            <div class="table-custom">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Username</th>
                            <th>Benar</th>
                            <th>Salah</th>
                            <th>Skor</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($nilai_list) > 0): ?>
                            <?php $no = 1; foreach($nilai_list as $n): 
                                $is_lulus = $n['total_skor'] >= $toefl['passing_grade'];
                                // Format skor dengan 1 desimal
                                $skor = number_format($n['total_skor'], 1);
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($n['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($n['username']); ?></td>
                                <td><span style="color: #28a745;"><?php echo $n['total_benar']; ?></span></td>
                                <td><span style="color: #dc3545;"><?php echo $n['total_salah']; ?></span></td>
                                <td><strong><?php echo $skor; ?></strong></td>
                                <td>
                                    <?php if($is_lulus): ?>
                                        <span class="badge-lulus">✅ Lulus</span>
                                    <?php else: ?>
                                        <span class="badge-tidak-lulus">❌ Tidak Lulus</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($n['waktu_selesai'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">Belum ada peserta yang mengerjakan TOEFL ini</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
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