<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$materi_id = isset($_GET['materi_id']) ? (int)$_GET['materi_id'] : 0;

// Ambil semua materi untuk dropdown
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

// Ambil soal berdasarkan materi
$soal_list = [];
$materi = null;

if ($materi_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM materi WHERE id = ?");
    $stmt->execute([$materi_id]);
    $materi = $stmt->fetch();
    
    // Cek apakah kolom tingkat_kesulitan ada
    try {
        $stmt = $pdo->prepare("SELECT * FROM soal_latihan WHERE materi_id = ? ORDER BY tingkat_kesulitan, RAND()");
        $stmt->execute([$materi_id]);
        $soal_list = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Jika kolom belum ada, gunakan ORDER BY biasa
        $stmt = $pdo->prepare("SELECT * FROM soal_latihan WHERE materi_id = ? ORDER BY RAND()");
        $stmt->execute([$materi_id]);
        $soal_list = $stmt->fetchAll();
    }
}

// Proses submit jawaban
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_jawaban'])) {
    $jawaban = $_POST['jawaban'] ?? [];
    $total_soal = count($jawaban);
    $jawaban_benar = 0;
    
    // Simpan jawaban user
    foreach ($jawaban as $soal_id => $jawaban_user) {
        $stmt = $pdo->prepare("SELECT jawaban_benar FROM soal_latihan WHERE id = ?");
        $stmt->execute([$soal_id]);
        $soal = $stmt->fetch();
        
        if ($soal) {
            $is_benar = ($jawaban_user == $soal['jawaban_benar']);
            if ($is_benar) $jawaban_benar++;
            
            // Simpan ke tabel jawaban_user
            try {
                $stmt = $pdo->prepare("INSERT INTO jawaban_user (user_id, soal_id, jawaban, is_benar) 
                                       VALUES (?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE jawaban = ?, is_benar = ?");
                $stmt->execute([$user_id, $soal_id, $jawaban_user, $is_benar, $jawaban_user, $is_benar]);
            } catch (PDOException $e) {
                // Skip jika error
            }
        }
    }
    
    // Simpan hasil latihan
    $skor = ($total_soal > 0) ? ($jawaban_benar / $total_soal) * 100 : 0;
    try {
        $stmt = $pdo->prepare("INSERT INTO hasil_latihan (user_id, materi_id, total_soal, jawaban_benar, skor) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $materi_id, $total_soal, $jawaban_benar, $skor]);
    } catch (PDOException $e) {
        // Skip jika error
    }
    
    $message = "Selamat! Anda menjawab $jawaban_benar dari $total_soal soal dengan benar. Skor: " . round($skor, 1) . "%";
    $message_type = $skor >= 70 ? 'success' : 'warning';
}

include '../includes/header.php';
?>
<!-- Sisa HTML sama seperti sebelumnya -->

<style>
    .latihan-page {
        padding: 50px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .soal-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
    
    .btn-submit:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .result-box {
        background: linear-gradient(135deg, #1B2A4A, #2C4066);
        color: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .result-box .score {
        font-size: 48px;
        font-weight: 700;
        color: #F4B41A;
    }
    
    .result-box .label {
        font-size: 18px;
        opacity: 0.8;
    }
</style>

<div class="latihan-page">
    <div class="container">
        <h2 style="color: #1B2A4A; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-puzzle-piece" style="color: #F4B41A;"></i> 
            Latihan <span style="color: #F4B41A;">Soal</span>
        </h2>
        
        <!-- Pilih Materi -->
        <div class="soal-container mb-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label style="font-weight: 600; color: #1B2A4A;">Pilih Materi:</label>
                    <select name="materi_id" class="form-control" style="border-radius: 10px; border: 2px solid #e0e0e0;">
                        <option value="0">-- Pilih Materi --</option>
                        <?php foreach($materi_list as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $materi_id == $m['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['judul']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn w-100" style="background: #1B2A4A; color: white; padding: 10px; border-radius: 10px;">
                        <i class="fas fa-search me-2"></i>Tampilkan Soal
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($message): ?>
        <div class="result-box">
            <div class="label">Hasil Latihan</div>
            <div class="score"><?php echo round($skor ?? 0, 1); ?>%</div>
            <p style="margin-top: 10px;"><?php echo $message; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($materi_id > 0 && $materi): ?>
        <div class="soal-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 style="color: #1B2A4A; font-weight: 700;">
                    <i class="fas fa-book" style="color: #F4B41A;"></i> 
                    <?php echo htmlspecialchars($materi['judul']); ?>
                </h5>
                <span style="color: #666;">
                    <i class="fas fa-question-circle"></i> Total: <?php echo count($soal_list); ?> soal
                </span>
            </div>
            
            <?php if (count($soal_list) > 0): ?>
                <form method="POST" action="" id="formLatihan">
                    <?php $no = 1; foreach($soal_list as $soal): ?>
                    <div class="soal-item">
                        <div class="d-flex">
                            <span class="soal-number"><?php echo $no; ?></span>
                            <div style="flex: 1;">
                                <p style="font-weight: 600; margin-bottom: 15px; color: #1B2A4A;">
                                    <?php echo htmlspecialchars($soal['pertanyaan']); ?>
                                </p>
                                <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_a')">
                                    <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_a" value="A">
                                    <label for="soal_<?php echo $soal['id']; ?>_a">A. <?php echo htmlspecialchars($soal['pilihan_a']); ?></label>
                                </div>
                                <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_b')">
                                    <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_b" value="B">
                                    <label for="soal_<?php echo $soal['id']; ?>_b">B. <?php echo htmlspecialchars($soal['pilihan_b']); ?></label>
                                </div>
                                <?php if (!empty($soal['pilihan_c'])): ?>
                                <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_c')">
                                    <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_c" value="C">
                                    <label for="soal_<?php echo $soal['id']; ?>_c">C. <?php echo htmlspecialchars($soal['pilihan_c']); ?></label>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($soal['pilihan_d'])): ?>
                                <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_d')">
                                    <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" id="soal_<?php echo $soal['id']; ?>_d" value="D">
                                    <label for="soal_<?php echo $soal['id']; ?>_d">D. <?php echo htmlspecialchars($soal['pilihan_d']); ?></label>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    // Di bagian menampilkan soal, tambahkan:
                    <?php if($soal['media_type'] != 'none' && !empty($soal['media_url'])): ?>
                        <div class="media-preview mb-3">
                            <?php if($soal['media_type'] == 'video'): ?>
                                <?php if(strpos($soal['media_url'], 'youtube.com') !== false): 
                                    parse_str(parse_url($soal['media_url'], PHP_URL_QUERY), $query);
                                    $youtube_id = $query['v'] ?? '';
                                ?>
                                    <div style="position: relative; padding-bottom: 56.25%; height: 0;">
                                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px;" 
                                                src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" 
                                                frameborder="0" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <video controls style="width: 100%; border-radius: 10px;">
                                        <source src="<?php echo $soal['media_url']; ?>" type="video/mp4">
                                        Browser Anda tidak mendukung video tag.
                                    </video>
                                <?php endif; ?>
                            <?php elseif($soal['media_type'] == 'audio'): ?>
                                <audio controls style="width: 100%; border-radius: 10px;">
                                    <source src="<?php echo $soal['media_url']; ?>" type="audio/mpeg">
                                    Browser Anda tidak mendukung audio tag.
                                </audio>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    // Jika media berupa file yang diupload
                    <?php if($soal['media_type'] != 'none' && !empty($soal['media_file']) && file_exists('../' . $soal['media_file'])): ?>
                        <div class="media-preview mb-3">
                            <?php if($soal['media_type'] == 'video'): ?>
                                <video controls style="width: 100%; border-radius: 10px;">
                                    <source src="../<?php echo $soal['media_file']; ?>" type="video/mp4">
                                    Browser Anda tidak mendukung video tag.
                                </video>
                            <?php elseif($soal['media_type'] == 'audio'): ?>
                                <audio controls style="width: 100%; border-radius: 10px;">
                                    <source src="../<?php echo $soal['media_file']; ?>" type="audio/mpeg">
                                    Browser Anda tidak mendukung audio tag.
                                </audio>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php $no++; endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" name="submit_jawaban" class="btn-submit">
                            <i class="fas fa-check-circle me-2"></i>Kirim Jawaban
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 0;">
                    <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                    <h5 style="color: #999; margin-top: 20px;">Belum ada soal untuk materi ini</h5>
                    <p style="color: #bbb;">Soal akan segera ditambahkan</p>
                </div>
            <?php endif; ?>
        </div>
        <?php elseif ($materi_id > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Materi tidak ditemukan.
        </div>
        <?php else: ?>
        <div class="soal-container text-center" style="padding: 60px 0;">
            <i class="fas fa-hand-pointer" style="font-size: 50px; color: #ddd;"></i>
            <h5 style="color: #999; margin-top: 20px;">Pilih materi di atas untuk mulai latihan</h5>
            <p style="color: #bbb;">Pilih materi yang ingin Anda pelajari</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function selectOption(element, inputId) {
    // Hapus selection dari semua option di parent
    const parent = element.closest('.soal-item');
    parent.querySelectorAll('.option-item').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Tandai yang dipilih
    element.classList.add('selected');
    
    // Check radio button
    document.getElementById(inputId).checked = true;
}

// Animasi smooth
document.querySelectorAll('.option-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
    });
    item.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});
</script>

<?php include '../includes/footer.php'; ?>