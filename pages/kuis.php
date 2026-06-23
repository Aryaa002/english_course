<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$kuis_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika ada kuis_id, tampilkan detail kuis
if ($kuis_id > 0) {
    // Ambil data kuis
    $stmt = $pdo->prepare("SELECT k.*, m.judul as materi_judul FROM kuis k 
                           LEFT JOIN materi m ON k.materi_id = m.id 
                           WHERE k.id = ? AND k.is_active = 1");
    $stmt->execute([$kuis_id]);
    $kuis = $stmt->fetch();
    
    if (!$kuis) {
        redirect('kuis.php');
    }
    
    // Cek apakah user sudah mengerjakan kuis ini
    $stmt = $pdo->prepare("SELECT * FROM hasil_kuis WHERE user_id = ? AND kuis_id = ?");
    $stmt->execute([$user_id, $kuis_id]);
    $hasil = $stmt->fetch();
    
    if ($hasil) {
        // Jika sudah mengerjakan, tampilkan hasil
        $show_result = true;
    } else {
        // Ambil soal kuis
        $query = "SELECT * FROM pertanyaan_kuis WHERE kuis_id = ?";
        if ($kuis['is_acak']) {
            $query .= " ORDER BY RAND()";
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute([$kuis_id]);
        $soal_list = $stmt->fetchAll();
        $show_result = false;
    }
    
    // Proses submit jawaban
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kuis']) && !$hasil) {
        $jawaban = $_POST['jawaban'] ?? [];
        $total_soal = count($soal_list);
        $total_benar = 0;
        $total_salah = 0;
        $skor = 0;
        
        // Hitung jawaban
        foreach ($soal_list as $soal) {
            $jawaban_user = $jawaban[$soal['id']] ?? '';
            if ($jawaban_user == $soal['jawaban_benar']) {
                $total_benar++;
                $skor += $soal['poin'];
            } else {
                $total_salah++;
            }
        }
        
        // Hitung total poin maksimal
        $total_poin = array_sum(array_column($soal_list, 'poin'));
        
        // Cek lulus/tidak
        $persentase = $total_poin > 0 ? ($skor / $total_poin) * 100 : 0;
        $is_lulus = $persentase >= $kuis['passing_grade'];
        
        // Simpan hasil
        $stmt = $pdo->prepare("INSERT INTO hasil_kuis (user_id, kuis_id, skor, total_poin, total_benar, total_salah, total_soal, jawaban, waktu_selesai, status, is_lulus) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'selesai', ?)");
        $stmt->execute([
            $user_id, 
            $kuis_id, 
            $skor,
            $total_poin,
            $total_benar, 
            $total_salah, 
            $total_soal, 
            json_encode($jawaban),
            $is_lulus
        ]);
        
        // Redirect untuk menampilkan hasil
        echo '<meta http-equiv="refresh" content="0;url=kuis.php?id=' . $kuis_id . '">';
        exit();
    }
}

// Ambil semua kuis yang aktif
$stmt = $pdo->query("SELECT k.*, m.judul as materi_judul, 
                     (SELECT COUNT(*) FROM pertanyaan_kuis WHERE kuis_id = k.id) as total_soal,
                     (SELECT COUNT(*) FROM hasil_kuis WHERE kuis_id = k.id AND user_id = " . ($_SESSION['user_id'] ?? 0) . ") as sudah_dikerjakan
                     FROM kuis k 
                     LEFT JOIN materi m ON k.materi_id = m.id 
                     WHERE k.is_active = 1 
                     ORDER BY k.created_at DESC");
$kuis_list = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
    .kuis-page {
        padding: 50px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .kuis-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .kuis-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
        cursor: pointer;
        border-left: 4px solid #F4B41A;
    }
    
    .kuis-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .kuis-card .done {
        background: #d4edda;
        color: #155724;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .soal-item {
        border-bottom: 1px solid #e0e0e0;
        padding: 20px 0;
    }
    
    .soal-item:last-child {
        border-bottom: none;
    }
    
    .soal-number {
        display: inline-block;
        width: 30px;
        height: 30px;
        background: #1B2A4A;
        color: white;
        border-radius: 50%;
        text-align: center;
        line-height: 30px;
        font-weight: 700;
        font-size: 14px;
        margin-right: 10px;
        flex-shrink: 0;
    }
    
    .option-item {
        padding: 10px 15px;
        margin: 5px 0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .option-item:hover {
        background: rgba(244, 180, 26, 0.05);
        border-color: #F4B41A;
    }
    
    .option-item input[type="radio"] {
        margin-right: 10px;
        accent-color: #F4B41A;
    }
    
    .option-item.selected {
        background: rgba(244, 180, 26, 0.1);
        border-color: #F4B41A;
    }
    
    .btn-submit {
        background: #F4B41A;
        color: #1B2A4A;
        padding: 12px 40px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 16px;
        border: none;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4);
        color: #1B2A4A;
    }
    
    .timer {
        background: #1B2A4A;
        color: #F4B41A;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 20px;
        font-weight: 700;
        text-align: center;
    }
    
    .timer .time {
        font-size: 28px;
    }
    
    .result-box {
        background: linear-gradient(135deg, #1B2A4A, #2C4066);
        color: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
    }
    
    .result-box .score {
        font-size: 48px;
        font-weight: 700;
        color: #F4B41A;
    }
    
    .result-box .status-lulus {
        color: #28a745;
        font-size: 24px;
        font-weight: 700;
    }
    
    .result-box .status-tidak-lulus {
        color: #dc3545;
        font-size: 24px;
        font-weight: 700;
    }
</style>

<div class="kuis-page">
    <div class="container">
        <h2 style="color: #1B2A4A; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> 
            Kuis <span style="color: #F4B41A;">Bahasa Inggris</span>
        </h2>
        
        <?php if (isset($kuis_id) && $kuis_id > 0 && isset($kuis)): ?>
            <!-- Detail Kuis -->
            <div class="kuis-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 style="color: #1B2A4A; font-weight: 700;">
                            <?php echo htmlspecialchars($kuis['judul']); ?>
                        </h4>
                        <p style="color: #666; margin: 0;">
                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($kuis['materi_judul']); ?>
                            <span class="ms-3"><i class="fas fa-clock"></i> <?php echo $kuis['waktu']; ?> menit</span>
                            <span class="ms-3"><i class="fas fa-question-circle"></i> <?php echo count($soal_list ?? []); ?> soal</span>
                            <span class="ms-3"><i class="fas fa-check-circle"></i> Passing Grade: <?php echo $kuis['passing_grade']; ?>%</span>
                        </p>
                    </div>
                    <a href="kuis.php" class="btn" style="background: #1B2A4A; color: white; padding: 8px 20px; border-radius: 25px;">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
                
                <?php if (isset($show_result) && $show_result): ?>
                    <!-- Hasil Kuis -->
                    <div class="result-box">
                        <div class="score"><?php echo $hasil['skor']; ?> / <?php echo $hasil['total_poin']; ?></div>
                        <div style="font-size: 18px; margin: 10px 0;">
                            <?php echo $hasil['total_benar']; ?> Jawaban Benar dari <?php echo $hasil['total_soal']; ?> Soal
                        </div>
                        <div>
                            <?php if($hasil['is_lulus']): ?>
                                <span class="status-lulus">✅ SELAMAT! ANDA LULUS</span>
                                <p style="margin-top: 10px; opacity: 0.8;">Anda berhasil mencapai passing grade <?php echo $kuis['passing_grade']; ?>%</p>
                            <?php else: ?>
                                <span class="status-tidak-lulus">❌ ANDA TIDAK LULUS</span>
                                <p style="margin-top: 10px; opacity: 0.8;">Anda perlu mencapai <?php echo $kuis['passing_grade']; ?>% untuk lulus</p>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 20px;">
                            <a href="kuis.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 30px; border-radius: 25px; font-weight: 600;">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Kuis
                            </a>
                        </div>
                    </div>
                <?php elseif (isset($soal_list) && count($soal_list) > 0): ?>
                    <!-- Form Kuis -->
                    <div class="timer mb-4">
                        <i class="fas fa-hourglass-half"></i> 
                        Waktu Tersisa: <span class="time" id="timer"><?php echo $kuis['waktu'] * 60; ?></span> detik
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Perhatian:</strong> Kuis ini hanya bisa dikerjakan <strong>SATU KALI</strong>. Pastikan Anda siap sebelum memulai.
                    </div>
                    
                    <form method="POST" action="" id="formKuis">
                        <?php $no = 1; foreach($soal_list as $soal): ?>
                        <div class="soal-item">
                            <div class="d-flex">
                                <span class="soal-number"><?php echo $no; ?></span>
                                <div style="flex: 1;">
                                    <p style="font-weight: 600; margin-bottom: 15px; color: #1B2A4A;">
                                        <?php echo htmlspecialchars($soal['pertanyaan']); ?>
                                        <span class="badge" style="background: #F4B41A; color: #1B2A4A; font-size: 11px; margin-left: 10px;">
                                            Poin: <?php echo $soal['poin']; ?>
                                        </span>
                                    </p>
                                    <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_a')">
                                        <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_a" value="A">
                                        <label for="soal_<?php echo $soal['id']; ?>_a">A. <?php echo htmlspecialchars($soal['pilihan_a']); ?></label>
                                    </div>
                                    <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_b')">
                                        <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_b" value="B">
                                        <label for="soal_<?php echo $soal['id']; ?>_b">B. <?php echo htmlspecialchars($soal['pilihan_b']); ?></label>
                                    </div>
                                    <?php if(!empty($soal['pilihan_c'])): ?>
                                    <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_c')">
                                        <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_c" value="C">
                                        <label for="soal_<?php echo $soal['id']; ?>_c">C. <?php echo htmlspecialchars($soal['pilihan_c']); ?></label>
                                    </div>
                                    <?php endif; ?>
                                    <?php if(!empty($soal['pilihan_d'])): ?>
                                    <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_d')">
                                        <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_d" value="D">
                                        <label for="soal_<?php echo $soal['id']; ?>_d">D. <?php echo htmlspecialchars($soal['pilihan_d']); ?></label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php $no++; endforeach; ?>
                        
                        <div class="text-center mt-4">
                            <button type="submit" name="submit_kuis" class="btn-submit" id="submitBtn" onclick="return confirm('Apakah Anda yakin ingin mengirim jawaban? Kuis ini hanya bisa dikerjakan SEKALI!')">
                                <i class="fas fa-check-circle me-2"></i>Selesai & Kirim Jawaban
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center" style="padding: 60px 0;">
                        <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                        <h5 style="color: #999; margin-top: 20px;">Belum ada soal untuk kuis ini</h5>
                        <p style="color: #bbb;">Soal akan segera ditambahkan</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Daftar Kuis -->
            <div class="row">
                <?php if(count($kuis_list) > 0): ?>
                    <?php foreach($kuis_list as $kuis): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="kuis-card" onclick="window.location.href='kuis.php?id=<?php echo $kuis['id']; ?>'">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge" style="background: #1B2A4A; color: white;">
                                    <?php echo htmlspecialchars($kuis['materi_judul']); ?>
                                </span>
                                <?php if($kuis['sudah_dikerjakan'] > 0): ?>
                                    <span class="done">✅ Sudah Dikerjakan</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #F4B41A; color: #1B2A4A;">
                                        <?php echo $kuis['total_soal']; ?> Soal
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h5 style="color: #1B2A4A; font-weight: 600;">
                                <?php echo htmlspecialchars($kuis['judul']); ?>
                            </h5>
                            <p style="color: #666; font-size: 14px; margin: 10px 0;">
                                <?php echo substr(htmlspecialchars($kuis['deskripsi']), 0, 100) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span style="color: #999; font-size: 12px;">
                                        <i class="fas fa-clock"></i> <?php echo $kuis['waktu']; ?> menit
                                    </span>
                                    <span style="color: #999; font-size: 12px; margin-left: 10px;">
                                        <i class="fas fa-check-circle"></i> PG: <?php echo $kuis['passing_grade']; ?>%
                                    </span>
                                </div>
                                <?php if($kuis['sudah_dikerjakan'] > 0): ?>
                                    <span style="color: #28a745; font-weight: 600;">
                                        Lihat Hasil <i class="fas fa-arrow-right ms-1"></i>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #F4B41A; font-weight: 600;">
                                        Mulai <i class="fas fa-arrow-right ms-1"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center" style="padding: 80px 0; background: white; border-radius: 15px;">
                            <i class="fas fa-inbox" style="font-size: 60px; color: #ddd;"></i>
                            <h4 style="color: #999; margin-top: 20px;">Belum ada kuis tersedia</h4>
                            <p style="color: #bbb;">Kuis akan segera ditambahkan</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function selectOption(element, inputId) {
    const parent = element.closest('.soal-item');
    parent.querySelectorAll('.option-item').forEach(opt => {
        opt.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById(inputId).checked = true;
}

<?php if(isset($kuis_id) && $kuis_id > 0 && isset($kuis) && !isset($show_result) && isset($soal_list) && count($soal_list) > 0): ?>
let timeLeft = <?php echo $kuis['waktu'] * 60; ?>;
const timerElement = document.getElementById('timer');

const timerInterval = setInterval(function() {
    timeLeft--;
    timerElement.textContent = timeLeft;
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        if (confirm('Waktu habis! Apakah Anda ingin mengirim jawaban?')) {
            document.getElementById('formKuis').submit();
        }
    }
}, 1000);
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>