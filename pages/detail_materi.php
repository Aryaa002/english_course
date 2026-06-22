<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
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
<!-- Sisa HTML sama seperti sebelumnya -->

<style>
    .materi-detail {
        padding: 50px 0;
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
    
    .media-player {
        background: #1B2A4A;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .media-player video,
    .media-player audio {
        width: 100%;
        border-radius: 10px;
    }
    
    .media-player audio {
        height: 60px;
    }
    
    .transcript-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
    }
    
    .transcript-section h5 {
        color: #1B2A4A;
        border-bottom: 2px solid #F4B41A;
        padding-bottom: 10px;
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
            <!-- Header -->
            <div class="materi-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h2 style="color: #1B2A4A; font-weight: 700;"><?php echo htmlspecialchars($materi['judul']); ?></h2>
                        <div class="mt-2">
                            <span class="badge" style="background: #1B2A4A; color: white;"><?php echo htmlspecialchars($materi['kategori']); ?></span>
                            <span class="badge" style="background: #F4B41A; color: #1B2A4A;"><?php echo htmlspecialchars($materi['tingkat']); ?></span>
                            <span class="badge" style="background: #e0e0e0; color: #333;">
                                <i class="far fa-clock"></i> <?php echo htmlspecialchars($materi['durasi']); ?>
                            </span>
                            <span class="badge" style="background: #d4edda; color: #155724;">
                                <i class="fas fa-<?php echo $materi['tipe_materi'] == 'video' ? 'video' : ($materi['tipe_materi'] == 'audio' ? 'music' : 'book'); ?>"></i>
                                <?php echo ucfirst($materi['tipe_materi']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="latihan_soal.php?materi_id=<?php echo $materi['id']; ?>" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                            <i class="fas fa-puzzle-piece me-2"></i>Latihan Soal
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Deskripsi -->
            <div class="mb-4">
                <p style="font-size: 16px; color: #555;"><?php echo nl2br(htmlspecialchars($materi['deskripsi'])); ?></p>
            </div>
            
            <!-- Media Player untuk Video/Audio -->
            <?php if ($materi['tipe_materi'] == 'video'): ?>
            <div class="media-player">
                <?php if (!empty($materi['video_url']) && strpos($materi['video_url'], 'youtube.com') !== false): 
                    // YouTube embed
                    parse_str(parse_url($materi['video_url'], PHP_URL_QUERY), $query);
                    $youtube_id = $query['v'] ?? '';
                ?>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px;" 
                                src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php elseif (!empty($materi['video_url']) && strpos($materi['video_url'], 'vimeo.com') !== false): ?>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px;" 
                                src="<?php echo $materi['video_url']; ?>" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php elseif (!empty($materi['video_url']) && file_exists('../' . $materi['video_url'])): ?>
                    <video controls>
                        <source src="../<?php echo $materi['video_url']; ?>" type="video/mp4">
                        Browser Anda tidak mendukung video tag.
                    </video>
                <?php else: ?>
                    <p style="color: white; text-align: center; padding: 40px 0;">
                        <i class="fas fa-video" style="font-size: 50px; opacity: 0.5;"></i>
                        <br>Video belum tersedia
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Audio Player untuk Listening -->
            <?php if ($materi['tipe_materi'] == 'audio'): ?>
            <div class="media-player">
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-headphones" style="color: #F4B41A; font-size: 40px;"></i>
                    <h4 style="color: white; margin-top: 10px;">Listening Exercise</h4>
                    <p style="color: rgba(255,255,255,0.7);">Dengarkan audio dengan seksama, lalu kerjakan soal latihan</p>
                </div>
                <?php if (!empty($materi['audio_url']) && file_exists('../' . $materi['audio_url'])): ?>
                    <audio controls style="width: 100%;">
                        <source src="../<?php echo $materi['audio_url']; ?>" type="audio/mpeg">
                        Browser Anda tidak mendukung audio tag.
                    </audio>
                <?php elseif (!empty($materi['audio_url']) && filter_var($materi['audio_url'], FILTER_VALIDATE_URL)): ?>
                    <audio controls style="width: 100%;">
                        <source src="<?php echo $materi['audio_url']; ?>" type="audio/mpeg">
                        Browser Anda tidak mendukung audio tag.
                    </audio>
                <?php else: ?>
                    <p style="color: white; text-align: center; padding: 40px 0;">
                        <i class="fas fa-music" style="font-size: 50px; opacity: 0.5;"></i>
                        <br>Audio belum tersedia
                    </p>
                <?php endif; ?>
                
                <div class="transcript-section mt-3">
                    <h5><i class="fas fa-file-alt" style="color: #F4B41A;"></i> Transcript (Script)</h5>
                    <div style="max-height: 300px; overflow-y: auto; padding: 15px; background: white; border-radius: 8px;">
                        <?php 
                        // Cari transcript dari konten atau gunakan konten sebagai transcript
                        $transcript = $materi['konten'] ?: 'Transcript akan segera ditambahkan.';
                        echo nl2br(htmlspecialchars($transcript)); 
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Konten Teks -->
            <?php if ($materi['tipe_materi'] == 'teks' || $materi['tipe_materi'] == 'interaktif'): ?>
            <div class="konten-materi mt-4" style="line-height: 1.8; font-size: 16px; color: #333;">
                <?php echo $materi['konten']; ?>
            </div>
            <?php endif; ?>
            
            <!-- Tombol Navigasi -->
            <div class="mt-5 pt-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php
                        // Ambil materi sebelumnya dan selanjutnya
                        $prev = $pdo->prepare("SELECT id, judul FROM materi WHERE id < ? ORDER BY id DESC LIMIT 1");
                        $prev->execute([$materi['id']]);
                        $prev_materi = $prev->fetch();
                        
                        $next = $pdo->prepare("SELECT id, judul FROM materi WHERE id > ? ORDER BY id ASC LIMIT 1");
                        $next->execute([$materi['id']]);
                        $next_materi = $next->fetch();
                        ?>
                        <?php if ($prev_materi): ?>
                        <a href="detail_materi.php?id=<?php echo $prev_materi['id']; ?>" class="btn btn-outline-secondary" style="border-radius: 25px;">
                            <i class="fas fa-arrow-left me-2"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($next_materi): ?>
                        <a href="detail_materi.php?id=<?php echo $next_materi['id']; ?>" class="btn" style="background: #F4B41A; color: #1B2A4A; border-radius: 25px; font-weight: 600;">
                            Selanjutnya <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>  