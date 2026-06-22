<?php
require_once 'config/database.php';
include 'includes/header.php';

// Ambil 6 materi terbaru untuk ditampilkan
$stmt = $pdo->query("SELECT * FROM materi ORDER BY created_at DESC LIMIT 6");
$materi_terbaru = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero-section" style="background: linear-gradient(135deg, #1B2A4A 0%, #2C4066 100%); padding: 100px 0;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-white">
                <h1 style="font-size: 52px; font-weight: 700; margin-bottom: 20px; line-height: 1.2;">
                    English Course
                    <span style="color: #F4B41A; display: block;">Learn English Easily</span>
                </h1>
                <p style="font-size: 18px; line-height: 1.8; opacity: 0.9; margin-bottom: 30px;">
                    Platform belajar bahasa Inggris dengan materi berkualitas, latihan interaktif, 
                    dan kuis untuk menguji pemahaman Anda. Mulai perjalanan belajar bahasa Inggris Anda sekarang!
                </p>
                <div>
                    <?php if(!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-lg me-3" 
                           style="background: #F4B41A; border: none; padding: 14px 40px; border-radius: 50px; font-weight: 600; color: #1B2A4A; transition: all 0.3s;">
                            <i class="fas fa-rocket me-2"></i>Mulai Belajar
                        </a>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-light btn-lg" 
                           style="border: 2px solid white; padding: 14px 40px; border-radius: 50px; font-weight: 600; transition: all 0.3s;">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>pages/materi.php" class="btn btn-primary btn-lg" 
                           style="background: #F4B41A; border: none; padding: 14px 45px; border-radius: 50px; font-weight: 600; color: #1B2A4A; transition: all 0.3s;">
                            <i class="fas fa-book-open me-2"></i>Lihat Materi
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Statistik -->
                <div class="row mt-5">
                    <div class="col-4">
                        <h3 style="color: #F4B41A; font-weight: 700;">12+</h3>
                        <p style="opacity: 0.8;">Materi Lengkap</p>
                    </div>
                    <div class="col-4">
                        <h3 style="color: #F4B41A; font-weight: 700;">100+</h3>
                        <p style="opacity: 0.8;">Soal Latihan</p>
                    </div>
                    <div class="col-4">
                        <h3 style="color: #F4B41A; font-weight: 700;">500+</h3>
                        <p style="opacity: 0.8;">Siswa Aktif</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="<?php echo BASE_URL; ?>assets/images/logo.jpg" alt="Learn English" 
                     style="width: 100%; max-width: 500px; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.2));">
            </div>
        </div>
    </div>
</section>

<!-- Fitur Section -->
<section style="padding: 80px 0; background: #f8f9fa;">
    <div class="container">
        <h2 style="text-align: center; color: #1B2A4A; margin-bottom: 15px; font-weight: 700; font-size: 36px;">
            Kenapa Belajar di <span style="color: #F4B41A;">English Course?</span>
        </h2>
        <p style="text-align: center; color: #666; margin-bottom: 50px; font-size: 18px;">
            Belajar bahasa Inggris dengan cara yang menyenangkan dan efektif
        </p>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; transition: transform 0.3s;">
                    <div class="card-body text-center" style="padding: 40px 30px;">
                        <div style="background: #1B2A4A; width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-book-open" style="color: #F4B41A; font-size: 35px;"></i>
                        </div>
                        <h5 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">12 Materi Lengkap</h5>
                        <p style="color: #666;">Dari Grammar, Vocabulary, Speaking, hingga persiapan TOEFL</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; transition: transform 0.3s;">
                    <div class="card-body text-center" style="padding: 40px 30px;">
                        <div style="background: #1B2A4A; width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-puzzle-piece" style="color: #F4B41A; font-size: 35px;"></i>
                        </div>
                        <h5 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">Latihan Interaktif</h5>
                        <p style="color: #666;">Soal-soal latihan dan kuis untuk menguji pemahaman Anda</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; transition: transform 0.3s;">
                    <div class="card-body text-center" style="padding: 40px 30px;">
                        <div style="background: #1B2A4A; width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trophy" style="color: #F4B41A; font-size: 35px;"></i>
                        </div>
                        <h5 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">Sertifikat</h5>
                        <p style="color: #666;">Dapatkan sertifikat setelah menyelesaikan semua materi</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Materi Terbaru Section -->
<section style="padding: 80px 0;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #1B2A4A; font-weight: 700; font-size: 32px;">
                Materi <span style="color: #F4B41A;">Terbaru</span>
            </h2>
            <a href="<?php echo BASE_URL; ?>pages/materi.php" style="color: #F4B41A; text-decoration: none; font-weight: 600;">
                Lihat Semua <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row">
            <?php foreach($materi_terbaru as $materi): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; transition: transform 0.3s; cursor: pointer;">
                    <div class="card-body" style="padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <span style="background: #1B2A4A; color: #F4B41A; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?php echo htmlspecialchars($materi['kategori']); ?>
                            </span>
                            <span style="background: #F4B41A20; color: #1B2A4A; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?php echo htmlspecialchars($materi['tingkat']); ?>
                            </span>
                        </div>
                        <h5 style="color: #1B2A4A; font-weight: 600; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($materi['judul']); ?>
                        </h5>
                        <p style="color: #666; font-size: 14px;">
                            <?php echo substr(htmlspecialchars($materi['deskripsi']), 0, 100) . '...'; ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                            <span style="color: #1B2A4A; font-size: 14px;">
                                <i class="far fa-clock" style="color: #F4B41A;"></i> <?php echo htmlspecialchars($materi['durasi']); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>pages/detail_materi.php?id=<?php echo $materi['id']; ?>" 
                               style="background: #F4B41A; color: #1B2A4A; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
                                Pelajari
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="background: linear-gradient(135deg, #1B2A4A 0%, #2C4066 100%); padding: 60px 0; margin-top: 40px;">
    <div class="container text-center text-white">
        <h2 style="font-size: 36px; font-weight: 700; margin-bottom: 20px;">
            Siap Meningkatkan Kemampuan <span style="color: #F4B41A;">Bahasa Inggris</span> Anda?
        </h2>
        <p style="font-size: 18px; opacity: 0.9; margin-bottom: 30px;">
            Bergabunglah sekarang dan mulai belajar bahasa Inggris dengan cara yang menyenangkan
        </p>
        <?php if(!isLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>register.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 15px 50px; border-radius: 50px; font-weight: 700; font-size: 18px; transition: all 0.3s;">
                Daftar Sekarang <i class="fas fa-arrow-right ms-2"></i>
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>pages/materi.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 15px 50px; border-radius: 50px; font-weight: 700; font-size: 18px; transition: all 0.3s;">
                Mulai Belajar <i class="fas fa-arrow-right ms-2"></i>
            </a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>