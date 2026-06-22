    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 style="color: var(--secondary); font-weight: 700;">English Course</h5>
                    <p style="opacity: 0.8;">Belajar bahasa Inggris dengan mudah dan menyenangkan. Platform terbaik untuk meningkatkan kemampuan bahasa Inggris Anda.</p>
                    <div class="social-icons mt-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 style="color: var(--secondary); font-weight: 600;">Menu</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>index.php">Beranda</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/materi.php">Materi</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/latihan_soal.php">Latihan</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/hasil_latihan.php">Hasil</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 style="color: var(--secondary); font-weight: 600;">Materi</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>pages/materi.php?kategori=Grammar">Grammar</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/materi.php?kategori=Vocabulary">Vocabulary</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/materi.php?kategori=Speaking">Speaking</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/materi.php?kategori=Test%20Preparation">TOEFL</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/kuis.php">Kuis</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 style="color: var(--secondary); font-weight: 600;">Kontak</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope"></i> info@englishcourse.com</li>
                        <li><i class="fas fa-phone"></i> +62 812-3456-7890</li>
                        <li><i class="fas fa-map-marker-alt"></i> Jakarta, Indonesia</li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center" style="opacity: 0.7; font-size: 14px;">
                &copy; <?php echo date('Y'); ?> English Course. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animasi scroll smooth
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>