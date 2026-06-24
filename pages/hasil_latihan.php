<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil semua hasil latihan user
$stmt = $pdo->prepare("SELECT h.*, m.judul as materi_judul 
                       FROM hasil_latihan h 
                       LEFT JOIN materi m ON h.materi_id = m.id 
                       WHERE h.user_id = ? 
                       ORDER BY h.waktu_selesai DESC");
$stmt->execute([$user_id]);
$hasil_list = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
    .hasil-page {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .hasil-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        max-width: 900px;
        margin: 0 auto;
    }
    
    .hasil-item {
        border-bottom: 1px solid #f0f0f0;
        padding: 20px 0;
        transition: all 0.3s;
    }
    
    .hasil-item:last-child {
        border-bottom: none;
    }
    
    .hasil-item:hover {
        background: #f8f9fa;
        padding-left: 15px;
        padding-right: 15px;
        border-radius: 10px;
    }
    
    .hasil-item .judul {
        font-weight: 600;
        color: #1B2A4A;
        font-size: 16px;
    }
    
    .hasil-item .detail {
        color: #666;
        font-size: 14px;
    }
    
    .hasil-item .skor {
        font-size: 24px;
        font-weight: 700;
    }
    
    .skor-lulus {
        color: #28a745;
    }
    
    .skor-tidak-lulus {
        color: #dc3545;
    }
    
    .badge-lulus {
        background: #d4edda;
        color: #155724;
        padding: 4px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-tidak-lulus {
        background: #f8d7da;
        color: #721c24;
        padding: 4px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .empty-state {
        padding: 60px 0;
        text-align: center;
    }
    
    .empty-state i {
        font-size: 60px;
        color: #ddd;
    }
    
    .empty-state h5 {
        color: #999;
        margin-top: 20px;
    }
    
    .empty-state p {
        color: #bbb;
    }
</style>

<div class="hasil-page">
    <div class="container">
        <div class="hasil-container">
            <h4 style="color: #1B2A4A; font-weight: 700; margin-bottom: 25px;">
                <i class="fas fa-chart-bar" style="color: #F4B41A;"></i> Riwayat Hasil Latihan
            </h4>
            
            <?php if (count($hasil_list) > 0): ?>
                <?php foreach($hasil_list as $h): 
                    $is_lulus = $h['skor'] >= 70;
                ?>
                <div class="hasil-item">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <div class="judul">
                                <?php echo htmlspecialchars($h['materi_judul'] ?? 'Materi tidak ditemukan'); ?>
                            </div>
                            <div class="detail">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($h['waktu_selesai'])); ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail">
                                <?php echo $h['jawaban_benar']; ?> Benar / <?php echo $h['total_soal']; ?> Soal
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="skor <?php echo $is_lulus ? 'skor-lulus' : 'skor-tidak-lulus'; ?>">
                                <?php echo round($h['skor'], 1); ?>%
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <?php if ($is_lulus): ?>
                                <span class="badge-lulus">✅ Lulus</span>
                            <?php else: ?>
                                <span class="badge-tidak-lulus">❌ Tidak Lulus</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>Belum ada hasil latihan</h5>
                    <p>Kerjakan latihan soal untuk melihat hasil Anda di sini</p>
                    <a href="latihan_soal.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 30px; border-radius: 25px; font-weight: 600; margin-top: 15px;">
                        <i class="fas fa-puzzle-piece me-2"></i>Mulai Latihan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>