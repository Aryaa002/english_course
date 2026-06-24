<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Filter berdasarkan kategori dan tipe
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT * FROM materi WHERE 1=1";
$params = [];

if ($kategori) {
    $query .= " AND kategori = ?";
    $params[] = $kategori;
}

if ($search) {
    $query .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materi_list = $stmt->fetchAll();

// Ambil semua kategori untuk filter
$kategori_list = $pdo->query("SELECT DISTINCT kategori FROM materi ORDER BY kategori")->fetchAll();

include '../includes/header.php';
?>

<style>
    .materi-page {
        padding: 50px 0 80px;
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        min-height: 100vh;
    }
    
    /* ===== HEADER SECTION ===== */
    .materi-header {
        text-align: center;
        margin-bottom: 40px;
        animation: fadeInDown 0.8s ease-out;
    }
    
    .materi-header .badge-header {
        display: inline-block;
        background: rgba(244, 180, 26, 0.1);
        color: #F4B41A;
        padding: 6px 20px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    
    .materi-header h2 {
        font-size: 38px;
        font-weight: 700;
        color: #1B2A4A;
        margin: 0 0 10px;
    }
    
    .materi-header h2 span {
        color: #F4B41A;
        position: relative;
    }
    
    .materi-header h2 span::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 0;
        right: 0;
        height: 6px;
        background: rgba(244, 180, 26, 0.2);
        border-radius: 4px;
    }
    
    .materi-header p {
        color: #666;
        font-size: 17px;
        max-width: 550px;
        margin: 0 auto;
    }
    
    /* ===== SEARCH & FILTER ===== */
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 20px 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        margin-bottom: 30px;
        animation: fadeInUp 0.8s ease-out 0.2s both;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .filter-section .search-wrapper {
        position: relative;
    }
    
    .filter-section .search-wrapper input {
        border-radius: 30px;
        padding: 12px 20px 12px 45px;
        border: 2px solid #e8e8e8;
        transition: all 0.3s;
        font-size: 14px;
    }
    
    .filter-section .search-wrapper input:focus {
        border-color: #F4B41A;
        box-shadow: 0 0 0 4px rgba(244, 180, 26, 0.1);
    }
    
    .filter-section .search-wrapper .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    .filter-section .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    
    .filter-section .filter-group .btn-filter {
        padding: 6px 20px;
        border-radius: 25px;
        border: 2px solid #e8e8e8;
        background: white;
        color: #666;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    
    .filter-section .filter-group .btn-filter:hover {
        border-color: #F4B41A;
        color: #1B2A4A;
        transform: translateY(-2px);
    }
    
    .filter-section .filter-group .btn-filter.active {
        background: #1B2A4A;
        border-color: #1B2A4A;
        color: white;
    }
    
    /* ===== MATERI CARDS ===== */
    .materi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }
    
    .materi-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(0,0,0,0.03);
        animation: fadeInUp 0.8s ease-out both;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    
    .materi-card:nth-child(1) { animation-delay: 0.1s; }
    .materi-card:nth-child(2) { animation-delay: 0.2s; }
    .materi-card:nth-child(3) { animation-delay: 0.3s; }
    .materi-card:nth-child(4) { animation-delay: 0.4s; }
    .materi-card:nth-child(5) { animation-delay: 0.5s; }
    .materi-card:nth-child(6) { animation-delay: 0.6s; }
    
    .materi-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 50px rgba(0,0,0,0.12);
    }
    
    /* ===== CARD HEADER ===== */
    .materi-card .card-header {
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #1B2A4A, #2C4066);
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
        min-height: 52px;
    }
    
    .materi-card .card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 100px;
        height: 100px;
        background: rgba(244, 180, 26, 0.05);
        border-radius: 50%;
        animation: pulseGlow 4s ease-in-out infinite;
    }
    
    @keyframes pulseGlow {
        0%, 100% { transform: scale(1); opacity: 0.3; }
        50% { transform: scale(1.5); opacity: 1; }
    }
    
    .materi-card .card-header .kategori {
        background: rgba(244, 180, 26, 0.2);
        color: #F4B41A;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        position: relative;
        z-index: 1;
        white-space: nowrap;
    }
    
    .materi-card .card-header .badge-video {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        position: relative;
        z-index: 1;
        white-space: nowrap;
    }
    
    /* ===== CARD BODY ===== */
    .materi-card .card-body {
        padding: 20px 20px 15px;
        background: white;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .materi-card .card-body .materi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 12px;
        transition: all 0.4s;
        background: rgba(244, 180, 26, 0.08);
        color: #F4B41A;
        flex-shrink: 0;
    }
    
    .materi-card:hover .card-body .materi-icon {
        transform: rotate(-10deg) scale(1.1);
        background: #F4B41A;
        color: white;
    }
    
    .materi-card .card-body .materi-icon.video {
        background: rgba(23, 162, 184, 0.08);
        color: #17a2b8;
    }
    
    .materi-card:hover .card-body .materi-icon.video {
        background: #17a2b8;
        color: white;
    }
    
    .materi-card .card-body .materi-icon.audio {
        background: rgba(40, 167, 69, 0.08);
        color: #28a745;
    }
    
    .materi-card:hover .card-body .materi-icon.audio {
        background: #28a745;
        color: white;
    }
    
    .materi-card .card-body h5 {
        color: #1B2A4A;
        font-weight: 700;
        font-size: 17px;
        margin-bottom: 8px;
        transition: color 0.3s;
        line-height: 1.3;
    }
    
    .materi-card:hover .card-body h5 {
        color: #F4B41A;
    }
    
    .materi-card .card-body .deskripsi {
        color: #666;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }
    
    /* ===== CARD FOOTER ===== */
    .materi-card .card-footer {
        padding: 12px 20px;
        background: #f8f9fa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        flex-shrink: 0;
        gap: 10px;
        min-height: 52px;
    }
    
    .materi-card .card-footer .tingkat {
        font-size: 12px;
        color: #999;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    .materi-card .card-footer .tingkat i {
        color: #F4B41A;
        font-size: 12px;
    }
    
    .materi-card .card-footer .btn-learn {
        background: #1B2A4A;
        color: white;
        padding: 7px 20px;
        border-radius: 25px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    
    .materi-card .card-footer .btn-learn i {
        font-size: 12px;
        transition: transform 0.3s;
    }
    
    .materi-card .card-footer .btn-learn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }
    
    .materi-card .card-footer .btn-learn:hover {
        background: #F4B41A;
        color: #1B2A4A;
    }
    
    .materi-card .card-footer .btn-learn:hover i {
        transform: translateX(3px);
    }
    
    .materi-card .card-footer .btn-learn:hover::before {
        left: 100%;
    }
    
    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 80px 0;
        animation: fadeInUp 0.8s ease-out;
    }
    
    .empty-state i {
        font-size: 70px;
        color: #ddd;
        margin-bottom: 20px;
        animation: floatIcon 3s ease-in-out infinite;
    }
    
    @keyframes floatIcon {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    
    .empty-state h4 {
        color: #999;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #bbb;
    }
    
    /* ===== COUNTER ===== */
    .materi-counter {
        text-align: center;
        margin-top: 40px;
        color: #999;
        font-size: 14px;
        animation: fadeInUp 0.8s ease-out;
        padding-top: 10px;
        border-top: 1px solid #f0f0f0;
    }
    
    .materi-counter strong {
        color: #1B2A4A;
    }
    
    /* ===== ANIMATIONS ===== */
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .materi-header h2 {
            font-size: 28px;
        }
        .materi-grid {
            grid-template-columns: 1fr;
        }
        .filter-section {
            padding: 15px;
        }
        .filter-section .filter-group .btn-filter {
            font-size: 12px;
            padding: 5px 14px;
        }
        .materi-card .card-footer {
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
        }
        .materi-card .card-footer .btn-learn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (min-width: 769px) and (max-width: 992px) {
        .materi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="materi-page">
    <div class="container">
        
        <!-- ===== HEADER ===== -->
        <div class="materi-header">
            <div class="badge-header"><i class="fas fa-sparkles"></i> Materi Pembelajaran</div>
            <h2>Tingkatkan <span>Bahasa Inggris</span> Anda</h2>
            <p>Pilih materi yang sesuai dengan kebutuhan dan tingkat kemampuan Anda</p>
        </div>
        
        <!-- ===== SEARCH & FILTER ===== -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <form method="GET" action="">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Cari materi..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="filter-group">
                        <a href="materi.php" class="btn-filter <?php echo !$kategori && !$search ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i> Semua
                        </a>
                        <?php foreach($kategori_list as $k): ?>
                        <a href="materi.php?kategori=<?php echo urlencode($k['kategori']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="btn-filter <?php echo $kategori == $k['kategori'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($k['kategori']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===== MATERI LIST ===== -->
        <?php if (count($materi_list) > 0): ?>
            <div class="materi-grid">
                <?php foreach($materi_list as $materi): 
                    $icon_class = 'book';
                    $icon_color = '';
                    if (!empty($materi['video_pembelajaran'])) {
                        $icon_class = 'video';
                        $icon_color = 'video';
                    }
                    if (!empty($materi['audio_url'])) {
                        $icon_class = 'audio';
                        $icon_color = 'audio';
                    }
                ?>
                <div class="materi-card">
                    <!-- HEADER -->
                    <div class="card-header">
                        <span class="kategori"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($materi['kategori']); ?></span>
                        <?php if(!empty($materi['video_pembelajaran'])): ?>
                            <span class="badge-video"><i class="fas fa-video"></i> Ada Video</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- BODY -->
                    <div class="card-body">
                        <div class="materi-icon <?php echo $icon_color; ?>">
                            <i class="fas fa-<?php echo $icon_class; ?>"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($materi['judul']); ?></h5>
                        <p class="deskripsi"><?php echo htmlspecialchars(substr($materi['deskripsi'], 0, 120)) . '...'; ?></p>
                    </div>
                    
                    <!-- FOOTER -->
                    <div class="card-footer">
                        <span class="tingkat">
                            <i class="fas fa-signal"></i> <?php echo htmlspecialchars($materi['tingkat']); ?>
                        </span>
                        <a href="detail_materi.php?id=<?php echo $materi['id']; ?>" class="btn-learn">
                            Pelajari <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- COUNTER -->
            <div class="materi-counter">
                <i class="fas fa-book-open"></i> Menampilkan <strong><?php echo count($materi_list); ?></strong> materi
            </div>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>Belum ada materi</h4>
                <p>Materi akan segera ditambahkan</p>
                <?php if($search || $kategori): ?>
                    <a href="materi.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 30px; border-radius: 25px; font-weight: 600; margin-top: 15px;">
                        <i class="fas fa-arrow-left me-2"></i>Lihat Semua Materi
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php include '../includes/footer.php'; ?>