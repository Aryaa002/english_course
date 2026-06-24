<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    redirect('materi.php');
}

$stmt = $pdo->prepare("SELECT * FROM materi WHERE id = ?");
$stmt->execute([$id]);
$materi = $stmt->fetch();

if (!$materi) {
    redirect('materi.php');
}

include '../includes/header.php';
?>

<style>
    .materi-detail {
        padding: 40px 0 60px;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .materi-content {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .materi-header {
        border-bottom: 3px solid #F4B41A;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    
    .materi-header .badge {
        font-size: 14px;
        padding: 8px 20px;
        border-radius: 25px;
    }
    
    .konten-materi {
        line-height: 1.8;
        font-size: 16px;
        color: #333;
    }
    
    .konten-materi h2 {
        color: #1B2A4A;
        font-weight: 700;
        margin-top: 30px;
        margin-bottom: 15px;
        font-size: 28px;
        border-bottom: 2px solid #F4B41A;
        padding-bottom: 10px;
    }
    
    .konten-materi h3 {
        color: #1B2A4A;
        font-weight: 600;
        margin-top: 25px;
        margin-bottom: 12px;
        font-size: 22px;
    }
    
    .konten-materi h4 {
        color: #1B2A4A;
        font-weight: 600;
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 18px;
    }
    
    .konten-materi p {
        margin-bottom: 15px;
    }
    
    .konten-materi ul, 
    .konten-materi ol {
        padding-left: 25px;
        margin-bottom: 15px;
    }
    
    .konten-materi ul li,
    .konten-materi ol li {
        margin-bottom: 8px;
    }
    
    .konten-materi pre {
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 10px;
        border-left: 4px solid #F4B41A;
        overflow-x: auto;
        font-size: 15px;
        line-height: 1.6;
        margin: 15px 0;
    }
    
    .konten-materi table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    
    .konten-materi table th,
    .konten-materi table td {
        padding: 10px 15px;
        border: 1px solid #ddd;
    }
    
    .konten-materi table th {
        background: #1B2A4A;
        color: white;
    }
    
    .konten-materi table tr:nth-child(even) {
        background: #f8f9fa;
    }
    
    .konten-materi hr {
        margin: 30px 0;
        border: 0;
        border-top: 2px solid #f0f0f0;
    }
    
    .konten-materi .highlight-box {
        background: #1B2A4A;
        color: white;
        padding: 20px 25px;
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .konten-materi .highlight-box h4 {
        color: #F4B41A;
        margin-top: 0;
    }
    
    .konten-materi .highlight-box ul {
        margin-bottom: 0;
    }
    
    .konten-materi .highlight-box ul li {
        color: rgba(255,255,255,0.9);
    }
    
    .konten-materi .example-wrong {
        background: #f8d7da;
        padding: 15px 20px;
        border-radius: 10px;
        border-left: 4px solid #dc3545;
        margin: 15px 0;
        color: #721c24;
    }
    
    .konten-materi .example-correct {
        background: #d4edda;
        padding: 15px 20px;
        border-radius: 10px;
        border-left: 4px solid #28a745;
        margin: 15px 0;
        color: #155724;
    }
    
    /* ===== VIDEO PEMBELAJARAN ===== */
    .video-section {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 3px solid #F4B41A;
    }
    
    .video-section .video-title {
        color: #1B2A4A;
        font-weight: 700;
        font-size: 20px;
        margin-bottom: 15px;
    }
    
    .video-section .video-title i {
        color: #F4B41A;
        margin-right: 10px;
    }
    
    .video-wrapper {
        background: #1B2A4A;
        border-radius: 15px;
        padding: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .video-wrapper iframe,
    .video-wrapper video {
        width: 100%;
        border-radius: 10px;
        display: block;
    }
    
    .video-wrapper iframe {
        min-height: 400px;
    }
    
    .video-wrapper video {
        max-height: 500px;
    }
    
    .video-wrapper .video-caption {
        color: rgba(255,255,255,0.7);
        text-align: center;
        margin-top: 12px;
        font-size: 14px;
    }
    
    .nav-buttons {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .btn-nav {
        padding: 10px 30px;
        border-radius: 25px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-nav-prev {
        background: #e9ecef;
        color: #495057;
    }
    
    .btn-nav-prev:hover {
        background: #dee2e6;
        color: #495057;
        transform: translateX(-3px);
    }
    
    .btn-nav-next {
        background: #F4B41A;
        color: #1B2A4A;
    }
    
    .btn-nav-next:hover {
        background: #d4a015;
        color: #1B2A4A;
        transform: translateX(3px);
    }
    
    .btn-latihan {
        background: #1B2A4A;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-latihan:hover {
        background: #2C4066;
        color: white;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .materi-content {
            padding: 20px;
        }
        .konten-materi h2 {
            font-size: 22px;
        }
        .konten-materi h3 {
            font-size: 18px;
        }
        .video-wrapper iframe {
            min-height: 200px;
        }
        .nav-buttons {
            flex-direction: column;
            align-items: stretch;
        }
        .btn-nav {
            justify-content: center;
        }
    }
</style>

<div class="materi-detail">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="materi.php" style="color: #1B2A4A;">Materi</a></li>
                <li class="breadcrumb-item active" style="color: #F4B41A;"><?php echo htmlspecialchars($materi['judul']); ?></li>
            </ol>
        </nav>
        
        <div class="materi-content">
            <!-- HEADER -->
            <div class="materi-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h2 style="color: #1B2A4A; font-weight: 700; font-size: 28px;">
                            <?php echo htmlspecialchars($materi['judul']); ?>
                        </h2>
                        <div class="mt-2">
                            <span class="badge" style="background: #1B2A4A; color: white;"><?php echo htmlspecialchars($materi['kategori']); ?></span>
                            <span class="badge" style="background: #F4B41A; color: #1B2A4A;"><?php echo htmlspecialchars($materi['tingkat']); ?></span>
                            <?php if(!empty($materi['video_pembelajaran'])): ?>
                                <span class="badge" style="background: #28a745; color: white;">
                                    <i class="fas fa-video"></i> Ada Video
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="latihan_soal.php?materi_id=<?php echo $materi['id']; ?>" class="btn-latihan">
                            <i class="fas fa-puzzle-piece"></i> Latihan Soal
                        </a>
                    </div>
                </div>
                
                <p style="color: #666; margin-top: 15px; font-size: 16px;">
                    <?php echo nl2br(htmlspecialchars($materi['deskripsi'])); ?>
                </p>
            </div>
            
            <!-- KONTEN MATERI -->
            <div class="konten-materi">
                <?php echo $materi['konten']; ?>
            </div>
            
            <!-- VIDEO PEMBELAJARAN -->
            <?php if (!empty($materi['video_pembelajaran'])): ?>
            <div class="video-section">
                <div class="video-title">
                    <i class="fas fa-video"></i> Video Pembelajaran
                </div>
                <div class="video-wrapper">
                    <?php 
                    $video_pembelajaran = $materi['video_pembelajaran'];
                    
                    if (strpos($video_pembelajaran, 'youtube.com/watch?v=') !== false || 
                        strpos($video_pembelajaran, 'youtu.be/') !== false):
                        
                        if (strpos($video_pembelajaran, 'youtu.be/') !== false) {
                            $video_id = substr($video_pembelajaran, strpos($video_pembelajaran, 'youtu.be/') + 9);
                        } else {
                            parse_str(parse_url($video_pembelajaran, PHP_URL_QUERY), $query);
                            $video_id = $query['v'] ?? '';
                        }
                    ?>
                        <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen></iframe>
                    <?php 
                    elseif (file_exists('../' . $video_pembelajaran)): 
                    ?>
                        <video controls>
                            <source src="../<?php echo $video_pembelajaran; ?>" type="video/mp4">
                            Browser Anda tidak mendukung video tag.
                        </video>
                    <?php 
                    elseif (filter_var($video_pembelajaran, FILTER_VALIDATE_URL)): 
                    ?>
                        <video controls>
                            <source src="<?php echo $video_pembelajaran; ?>" type="video/mp4">
                            Browser Anda tidak mendukung video tag.
                        </video>
                    <?php else: ?>
                        <p style="color: white; text-align: center; padding: 40px 0;">
                            <i class="fas fa-video" style="font-size: 40px; opacity: 0.5;"></i>
                            <br>Video pembelajaran belum tersedia
                        </p>
                    <?php endif; ?>
                    
                    <div class="video-caption">
                        <i class="fas fa-info-circle"></i> Tonton video ini untuk memahami materi dengan lebih baik
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- NAVIGASI -->
            <div class="nav-buttons">
                <div>
                    <?php
                    $prev = $pdo->prepare("SELECT id, judul FROM materi WHERE id < ? ORDER BY id DESC LIMIT 1");
                    $prev->execute([$materi['id']]);
                    $prev_materi = $prev->fetch();
                    ?>
                    <?php if ($prev_materi): ?>
                    <a href="detail_materi.php?id=<?php echo $prev_materi['id']; ?>" class="btn-nav btn-nav-prev">
                        <i class="fas fa-arrow-left"></i> Sebelumnya
                    </a>
                    <?php endif; ?>
                </div>
                <div>
                    <?php
                    $next = $pdo->prepare("SELECT id, judul FROM materi WHERE id > ? ORDER BY id ASC LIMIT 1");
                    $next->execute([$materi['id']]);
                    $next_materi = $next->fetch();
                    ?>
                    <?php if ($next_materi): ?>
                    <a href="detail_materi.php?id=<?php echo $next_materi['id']; ?>" class="btn-nav btn-nav-next">
                        Selanjutnya <i class="fas fa-arrow-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>