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
    /* ===== RESET & BASE ===== */
    .materi-detail {
        padding: 30px 0 50px;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .materi-content {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        max-width: 100%;
        overflow: hidden;
    }
    
    /* ===== BREADCRUMB ===== */
    .breadcrumb {
        background: transparent;
        padding: 0 0 15px 0;
        margin: 0;
        font-size: 14px;
        flex-wrap: wrap;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 18px;
        color: #999;
    }
    
    .breadcrumb-item a {
        color: #1B2A4A;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .breadcrumb-item a:hover {
        color: #F4B41A;
    }
    
    .breadcrumb-item.active {
        color: #F4B41A;
        font-weight: 600;
    }
    
    /* ===== HEADER ===== */
    .materi-header {
        border-bottom: 3px solid #F4B41A;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    
    .materi-header .header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .materi-header h2 {
        color: #1B2A4A;
        font-weight: 700;
        font-size: 28px;
        margin: 0 0 12px 0;
        line-height: 1.3;
        word-break: break-word;
    }
    
    .materi-header .badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 5px;
    }
    
    .materi-header .badge {
        font-size: 13px;
        padding: 6px 16px;
        border-radius: 25px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    .materi-header .badge-kategori {
        background: #1B2A4A;
        color: white;
    }
    
    .materi-header .badge-tingkat {
        background: #F4B41A;
        color: #1B2A4A;
    }
    
    .materi-header .badge-video {
        background: #28a745;
        color: white;
    }
    
    .materi-header .badge-video i {
        color: white;
    }
    
    .materi-header .btn-latihan {
        background: #1B2A4A;
        color: white;
        padding: 10px 25px;
        border-radius: 25px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .materi-header .btn-latihan:hover {
        background: #2C4066;
        color: white;
        transform: translateY(-2px);
    }
    
    .materi-header .deskripsi {
        color: #666;
        margin-top: 15px;
        font-size: 16px;
        line-height: 1.7;
        word-break: break-word;
    }
    
    /* ===== KONTEN MATERI ===== */
    .konten-materi {
        line-height: 1.8;
        font-size: 16px;
        color: #333;
        word-wrap: break-word;
        overflow-wrap: break-word;
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
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    .konten-materi table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .konten-materi table th,
    .konten-materi table td {
        padding: 10px 15px;
        border: 1px solid #ddd;
        text-align: left;
        word-break: break-word;
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
    
    .konten-materi .example-title {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .konten-materi img,
    .konten-materi iframe {
        max-width: 100%;
        height: auto;
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
        border: none;
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
    
    /* ===== NAVIGASI ===== */
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
        border: none;
        cursor: pointer;
        font-size: 14px;
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
    
    /* ============================================================ */
    /* ===== RESPONSIVE ===== */
    /* ============================================================ */
    
    /* Tablet Landscape */
    @media (max-width: 992px) {
        .materi-content {
            padding: 30px;
        }
        
        .materi-header h2 {
            font-size: 24px;
        }
        
        .konten-materi h2 {
            font-size: 24px;
        }
        
        .konten-materi h3 {
            font-size: 20px;
        }
        
        .video-wrapper iframe {
            min-height: 300px;
        }
    }
    
    /* Tablet Portrait & Mobile Landscape */
    @media (max-width: 768px) {
        .materi-detail {
            padding: 15px 0 30px;
        }
        
        .materi-content {
            padding: 20px;
            border-radius: 10px;
        }
        
        .breadcrumb {
            font-size: 13px;
        }
        
        .materi-header .header-top {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        
        .materi-header h2 {
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .materi-header .badge-group {
            gap: 6px;
        }
        
        .materi-header .badge {
            font-size: 11px;
            padding: 4px 12px;
        }
        
        .materi-header .btn-latihan {
            padding: 8px 20px;
            font-size: 13px;
            justify-content: center;
            width: 100%;
        }
        
        .materi-header .deskripsi {
            font-size: 14px;
            margin-top: 12px;
        }
        
        .konten-materi {
            font-size: 15px;
            line-height: 1.7;
        }
        
        .konten-materi h2 {
            font-size: 20px;
            margin-top: 25px;
            padding-bottom: 8px;
        }
        
        .konten-materi h3 {
            font-size: 18px;
            margin-top: 20px;
        }
        
        .konten-materi h4 {
            font-size: 16px;
        }
        
        .konten-materi pre {
            font-size: 13px;
            padding: 12px 15px;
        }
        
        .konten-materi table {
            font-size: 13px;
        }
        
        .konten-materi table th,
        .konten-materi table td {
            padding: 8px 10px;
        }
        
        .konten-materi .highlight-box {
            padding: 15px 18px;
        }
        
        .konten-materi .highlight-box h4 {
            font-size: 16px;
        }
        
        .video-section {
            margin-top: 30px;
            padding-top: 20px;
        }
        
        .video-section .video-title {
            font-size: 17px;
        }
        
        .video-wrapper {
            padding: 10px;
            border-radius: 10px;
        }
        
        .video-wrapper iframe {
            min-height: 200px;
        }
        
        .video-wrapper video {
            max-height: 300px;
        }
        
        .video-wrapper .video-caption {
            font-size: 12px;
        }
        
        .nav-buttons {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            margin-top: 20px;
            padding-top: 15px;
        }
        
        .btn-nav {
            justify-content: center;
            padding: 10px 20px;
            font-size: 14px;
            width: 100%;
        }
        
        .btn-nav-prev {
            order: 2;
        }
        
        .btn-nav-next {
            order: 1;
        }
    }
    
    /* Mobile Portrait */
    @media (max-width: 480px) {
        .materi-detail {
            padding: 10px 0 20px;
        }
        
        .materi-content {
            padding: 15px;
            border-radius: 8px;
        }
        
        .breadcrumb {
            font-size: 12px;
            padding-bottom: 10px;
        }
        
        .materi-header h2 {
            font-size: 17px;
        }
        
        .materi-header .badge {
            font-size: 10px;
            padding: 3px 10px;
        }
        
        .materi-header .btn-latihan {
            font-size: 12px;
            padding: 8px 16px;
        }
        
        .materi-header .deskripsi {
            font-size: 13px;
        }
        
        .konten-materi {
            font-size: 14px;
        }
        
        .konten-materi h2 {
            font-size: 17px;
            margin-top: 18px;
        }
        
        .konten-materi h3 {
            font-size: 15px;
            margin-top: 15px;
        }
        
        .konten-materi h4 {
            font-size: 14px;
        }
        
        .konten-materi ul,
        .konten-materi ol {
            padding-left: 18px;
        }
        
        .konten-materi pre {
            font-size: 12px;
            padding: 10px 12px;
            border-left-width: 3px;
        }
        
        .konten-materi table {
            font-size: 12px;
        }
        
        .konten-materi table th,
        .konten-materi table td {
            padding: 6px 8px;
            font-size: 11px;
        }
        
        .video-wrapper iframe {
            min-height: 150px;
        }
        
        .video-wrapper video {
            max-height: 200px;
        }
        
        .btn-nav {
            font-size: 13px;
            padding: 8px 16px;
        }
    }
    
    /* Small Mobile */
    @media (max-width: 360px) {
        .materi-content {
            padding: 10px;
        }
        
        .materi-header h2 {
            font-size: 15px;
        }
        
        .materi-header .badge {
            font-size: 9px;
            padding: 2px 8px;
        }
        
        .konten-materi {
            font-size: 13px;
        }
        
        .konten-materi h2 {
            font-size: 15px;
        }
        
        .video-wrapper iframe {
            min-height: 120px;
        }
    }
    
    /* Landscape Phone */
    @media (max-height: 600px) and (orientation: landscape) {
        .materi-detail {
            padding: 10px 0 20px;
        }
        
        .materi-content {
            padding: 15px;
        }
        
        .video-wrapper iframe {
            min-height: 150px;
        }
        
        .video-wrapper video {
            max-height: 180px;
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
            <!-- ===== HEADER ===== -->
            <div class="materi-header">
                <div class="header-top">
                    <div style="flex: 1; min-width: 0;">
                        <h2><?php echo htmlspecialchars($materi['judul']); ?></h2>
                        <div class="badge-group">
                            <span class="badge badge-kategori">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($materi['kategori']); ?>
                            </span>
                            <span class="badge badge-tingkat">
                                <i class="fas fa-signal"></i> <?php echo htmlspecialchars($materi['tingkat']); ?>
                            </span>
                            <?php if(!empty($materi['video_pembelajaran'])): ?>
                                <span class="badge badge-video">
                                    <i class="fas fa-video"></i> Ada Video
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="latihan_soal.php?materi_id=<?php echo $materi['id']; ?>" class="btn-latihan">
                            <i class="fas fa-puzzle-piece"></i> Latihan Soal
                        </a>
                    </div>
                </div>
                
                <!-- Deskripsi -->
                <div class="deskripsi">
                    <?php echo nl2br(htmlspecialchars($materi['deskripsi'])); ?>
                </div>
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