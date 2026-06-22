<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil hasil latihan terbaru
$stmt = $pdo->prepare("SELECT h.*, m.judul FROM hasil_latihan h 
                       LEFT JOIN materi m ON h.materi_id = m.id 
                       WHERE h.user_id = ? 
                       ORDER BY h.waktu_selesai DESC LIMIT 10");
$stmt->execute([$user_id]);
$hasil = $stmt->fetchAll();

include '../includes/header.php';
?>
<!-- Sisa HTML sama seperti sebelumnya -->

<style>
    .hasil-page {
        padding: 50px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .hasil-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .hasil-item {
        border-bottom: 1px solid #f0f0f0;
        padding: 15px 0;
    }
    
    .hasil-item:last-child {
        border-bottom: none;
    }
    
    .score-high {
        color: #28a745;
        font-weight: 700;
    }
    
    .score-medium {
        color: #ffc107;
        font-weight: 700;
    }
    
    .score-low {
        color: #dc3545;
        font-weight: 700;
    }
</style>

<div class="hasil-page">
    <div class="container">
        <h2 style="color: #1B2A4A; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-chart-bar" style="color: #F4B41A;"></i> 
            Hasil <span style="color: #F4B41A;">Latihan</span>
        </h2>
        
        <div class="hasil-container">
            <?php if (count($hasil) > 0): ?>
                <?php foreach($hasil as $h): ?>
                <div class="hasil-item">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <h6 style="color: #1B2A4A; font-weight: 600;">
                                <?php echo htmlspecialchars($h['judul']); ?>
                            </h6>
                            <small style="color: #999;">
                                <?php echo date('d/m/Y H:i', strtotime($h['waktu_selesai'])); ?>
                            </small>
                        </div>
                        <div class="col-md-3">
                            <span>Jawaban: <?php echo $h['jawaban_benar']; ?>/<?php echo $h['total_soal']; ?></span>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="<?php echo $h['skor'] >= 70 ? 'score-high' : ($h['skor'] >= 50 ? 'score-medium' : 'score-low'); ?>" 
                                  style="font-size: 24px;">
                                <?php echo round($h['skor'], 1); ?>%
                            </span>
                            <?php if ($h['skor'] >= 70): ?>
                                <span class="badge" style="background: #28a745; color: white;">Lulus</span>
                            <?php else: ?>
                                <span class="badge" style="background: #dc3545; color: white;">Perlu Belajar Lagi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 0;">
                    <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                    <h5 style="color: #999; margin-top: 20px;">Belum ada hasil latihan</h5>
                    <p style="color: #bbb;">Kerjakan latihan soal untuk melihat hasil Anda</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>