<?php
require_once '../config/database.php';

// Cek login - gunakan requireLogin()
if (!isLoggedIn()) {
    redirect('login.php');
}

// Filter berdasarkan kategori dan tipe
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';

$query = "SELECT * FROM materi WHERE 1=1";
$params = [];

if ($kategori) {
    $query .= " AND kategori = ?";
    $params[] = $kategori;
}

if ($tipe) {
    $query .= " AND tipe_materi = ?";
    $params[] = $tipe;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materi_list = $stmt->fetchAll();

// Ambil semua kategori untuk filter
$kategori_list = $pdo->query("SELECT DISTINCT kategori FROM materi ORDER BY kategori")->fetchAll();

include '../includes/header.php';
?>
<!-- Sisa HTML sama seperti sebelumnya -->

<style>
    .materi-page {
        padding: 50px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .filter-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .filter-section .btn-filter {
        border-radius: 25px;
        padding: 8px 20px;
        margin: 3px;
        transition: all 0.3s;
    }
    
    .filter-section .btn-filter.active {
        background: #1B2A4A;
        color: white;
    }
    
    .filter-section .btn-filter:hover {
        transform: translateY(-2px);
    }
    
    .materi-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
        height: 100%;
    }
    
    .materi-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .materi-card .card-body {
        padding: 25px;
    }
    
    .materi-card .card-header-media {
        background: #1B2A4A;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .media-icon {
        font-size: 24px;
    }
    
    .materi-card .badge-tipe {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .btn-outline-primary-custom {
        border: 2px solid #1B2A4A;
        color: #1B2A4A;
        border-radius: 25px;
        padding: 8px 25px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-outline-primary-custom:hover {
        background: #1B2A4A;
        color: white;
    }
</style>

<div class="materi-page">
    <div class="container">
        <h2 style="color: #1B2A4A; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-book-open" style="color: #F4B41A;"></i> 
            Materi <span style="color: #F4B41A;">Pembelajaran</span>
        </h2>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <strong style="color: #1B2A4A;"><i class="fas fa-filter"></i> Filter:</strong>
                </div>
                <div class="col-md-9">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="materi.php" class="btn btn-filter <?php echo !$kategori && !$tipe ? 'active' : 'btn-outline-secondary'; ?>">
                            Semua
                        </a>
                        <?php foreach($kategori_list as $k): ?>
                        <a href="materi.php?kategori=<?php echo urlencode($k['kategori']); ?>" 
                           class="btn btn-filter <?php echo $kategori == $k['kategori'] ? 'active' : 'btn-outline-secondary'; ?>">
                            <?php echo htmlspecialchars($k['kategori']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <a href="materi.php?tipe=teks" class="btn btn-filter <?php echo $tipe == 'teks' ? 'active' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-book"></i> Teks
                        </a>
                        <a href="materi.php?tipe=video" class="btn btn-filter <?php echo $tipe == 'video' ? 'active' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-video"></i> Video
                        </a>
                        <a href="materi.php?tipe=audio" class="btn btn-filter <?php echo $tipe == 'audio' ? 'active' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-headphones"></i> Listening
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Materi List -->
        <?php if (count($materi_list) > 0): ?>
        <div class="row">
            <?php foreach($materi_list as $materi): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="materi-card">
                    <div class="card-header-media">
                        <span>
                            <?php if ($materi['tipe_materi'] == 'video'): ?>
                                <i class="fas fa-video media-icon"></i>
                            <?php elseif ($materi['tipe_materi'] == 'audio'): ?>
                                <i class="fas fa-headphones media-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-book media-icon"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($materi['kategori']); ?>
                        </span>
                        <span class="badge-tipe" style="background: rgba(244, 180, 26, 0.2); color: #F4B41A;">
                            <?php echo ucfirst($materi['tipe_materi']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 style="color: #1B2A4A; font-weight: 600; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($materi['judul']); ?>
                        </h5>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                            <?php echo substr(htmlspecialchars($materi['deskripsi']), 0, 100) . '...'; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span style="font-size: 12px; color: #999;">
                                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($materi['durasi']); ?>
                                </span>
                                <span style="font-size: 12px; color: #999; margin-left: 10px;">
                                    <i class="fas fa-signal"></i> <?php echo htmlspecialchars($materi['tingkat']); ?>
                                </span>
                            </div>
                            <a href="detail_materi.php?id=<?php echo $materi['id']; ?>" 
                               class="btn btn-outline-primary-custom" style="padding: 5px 20px; font-size: 13px;">
                                Pelajari <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center" style="padding: 80px 0;">
            <i class="fas fa-inbox" style="font-size: 60px; color: #ddd;"></i>
            <h4 style="color: #999; margin-top: 20px;">Belum ada materi</h4>
            <p style="color: #bbb;">Materi akan segera ditambahkan</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>