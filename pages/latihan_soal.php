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
$hasil = null;
$show_review = false;

if ($materi_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM materi WHERE id = ?");
    $stmt->execute([$materi_id]);
    $materi = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM soal_latihan WHERE materi_id = ? ORDER BY RAND()");
    $stmt->execute([$materi_id]);
    $soal_list = $stmt->fetchAll();
}

// Proses submit jawaban
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_jawaban'])) {
    $jawaban = $_POST['jawaban'] ?? [];
    $total_soal = count($soal_list);
    $jawaban_benar = 0;
    $detail_jawaban = [];
    
    // Simpan jawaban user dan hitung
    foreach ($soal_list as $soal) {
        $soal_id = $soal['id'];
        $jawaban_user = $jawaban[$soal_id] ?? '';
        $is_benar = ($jawaban_user == $soal['jawaban_benar']);
        if ($is_benar) $jawaban_benar++;
        
        $detail_jawaban[$soal_id] = [
            'jawaban_user' => $jawaban_user,
            'jawaban_benar' => $soal['jawaban_benar'],
            'is_benar' => $is_benar
        ];
        
        // Simpan ke tabel jawaban_latihan
        try {
            $stmt = $pdo->prepare("INSERT INTO jawaban_latihan (user_id, soal_id, jawaban, is_benar) 
                                   VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE jawaban = ?, is_benar = ?");
            $stmt->execute([$user_id, $soal_id, $jawaban_user, $is_benar, $jawaban_user, $is_benar]);
        } catch (PDOException $e) {
            // Skip jika error
        }
    }
    
    // Simpan hasil latihan
    $skor = ($total_soal > 0) ? ($jawaban_benar / $total_soal) * 100 : 0;
    try {
        $stmt = $pdo->prepare("INSERT INTO hasil_latihan (user_id, materi_id, total_soal, jawaban_benar, skor, detail_jawaban) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $materi_id, $total_soal, $jawaban_benar, $skor, json_encode($detail_jawaban)]);
    } catch (PDOException $e) {
        // Jika tabel belum ada kolom detail_jawaban, insert tanpa kolom tersebut
        $stmt = $pdo->prepare("INSERT INTO hasil_latihan (user_id, materi_id, total_soal, jawaban_benar, skor) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $materi_id, $total_soal, $jawaban_benar, $skor]);
    }
    
    $show_review = true;
    $hasil = [
        'total_soal' => $total_soal,
        'jawaban_benar' => $jawaban_benar,
        'skor' => $skor,
        'detail_jawaban' => $detail_jawaban
    ];
}

include '../includes/header.php';
?>

<style>
    /* Sama seperti sebelumnya, tidak diubah */
    .latihan-page { padding: 30px 0; background: #f8f9fa; min-height: 100vh; }
    .latihan-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); max-width: 900px; margin: 0 auto; }
    .result-box { background: linear-gradient(135deg, #1B2A4A, #2C4066); color: white; border-radius: 15px; padding: 25px 30px; margin-bottom: 30px; text-align: center; }
    .result-box .score { font-size: 48px; font-weight: 700; color: #F4B41A; }
    .result-box .detail { margin-top: 10px; font-size: 16px; opacity: 0.9; }
    .result-box .status { margin-top: 10px; font-weight: 600; font-size: 18px; }
    .result-box .status.lulus { color: #28a745; }
    .result-box .status.tidak-lulus { color: #dc3545; }
    .soal-item { border-bottom: 1px solid #f0f0f0; padding: 20px 0; }
    .soal-item:last-child { border-bottom: none; }
    .soal-number { display: inline-block; width: 32px; height: 32px; background: #1B2A4A; color: white; border-radius: 50%; text-align: center; line-height: 32px; font-weight: 700; font-size: 14px; margin-right: 12px; flex-shrink: 0; }
    .soal-text { font-weight: 600; color: #1B2A4A; margin-bottom: 15px; font-size: 15px; }
    .option-item { padding: 8px 15px; margin: 4px 0; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; display: flex; align-items: center; gap: 10px; }
    .option-item:hover { background: rgba(244, 180, 26, 0.05); border-color: #F4B41A; }
    .option-item input[type="radio"] { accent-color: #F4B41A; width: 18px; height: 18px; flex-shrink: 0; }
    .option-item.selected { background: rgba(244, 180, 26, 0.1); border-color: #F4B41A; }
    .option-item .option-label { font-weight: 600; color: #1B2A4A; min-width: 25px; }
    .review-correct { background: #d4edda; border-left: 4px solid #28a745; padding-left: 15px; }
    .review-wrong { background: #f8d7da; border-left: 4px solid #dc3545; padding-left: 15px; }
    .review-correct .option-item, .review-wrong .option-item { cursor: default; }
    .review-correct .option-item:hover, .review-wrong .option-item:hover { background: transparent; border-color: transparent; }
    .badge-benar { background: #28a745; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 10px; }
    .badge-salah { background: #dc3545; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 10px; }
    .badge-jawaban-benar { background: #28a745; color: white; padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .btn-submit-latihan { background: #F4B41A; color: #1B2A4A; padding: 12px 40px; border-radius: 30px; font-weight: 700; font-size: 16px; border: none; transition: all 0.3s; }
    .btn-submit-latihan:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); color: #1B2A4A; }
    .btn-ulangi { background: #1B2A4A; color: white; padding: 10px 30px; border-radius: 30px; font-weight: 600; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; }
    .btn-ulangi:hover { background: #2C4066; color: white; }
    @media (max-width: 768px) { .latihan-container { padding: 15px; } .result-box .score { font-size: 36px; } .soal-text { font-size: 14px; } .option-item { padding: 6px 12px; font-size: 14px; } }
</style>

<div class="latihan-page">
    <div class="container">
        <div class="latihan-container">
            
            <!-- Pilih Materi -->
            <form method="GET" action="" class="row g-3 align-items-end mb-4">
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
            
            <?php if ($materi_id > 0 && $materi): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 style="color: #1B2A4A; font-weight: 700;">
                        <i class="fas fa-book" style="color: #F4B41A;"></i> 
                        <?php echo htmlspecialchars($materi['judul']); ?>
                    </h5>
                    <span style="color: #666;">
                        <i class="fas fa-question-circle"></i> Total: <?php echo count($soal_list); ?> soal
                    </span>
                </div>
                
                <?php if (count($soal_list) > 0): ?>
                    
                    <!-- ===== HASIL REVIEW ===== -->
                    <?php if ($show_review && $hasil): ?>
                    <div class="result-box">
                        <div class="score"><?php echo round($hasil['skor'], 1); ?>%</div>
                        <div class="detail">
                            <?php echo $hasil['jawaban_benar']; ?> Jawaban Benar dari <?php echo $hasil['total_soal']; ?> Soal
                        </div>
                        <div class="status <?php echo $hasil['skor'] >= 70 ? 'lulus' : 'tidak-lulus'; ?>">
                            <?php if ($hasil['skor'] >= 70): ?>
                                ✅ Lulus! Anda memahami materi ini dengan baik.
                            <?php else: ?>
                                ❌ Perlu belajar lagi. Coba ulangi latihan.
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                            <a href="latihan_soal.php?materi_id=<?php echo $materi_id; ?>" class="btn-ulangi">
                                <i class="fas fa-redo me-2"></i>Ulangi Latihan
                            </a>
                            <a href="hasil_latihan.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 30px; border-radius: 30px; font-weight: 600; text-decoration: none;">
                                <i class="fas fa-chart-bar me-2"></i>Lihat Riwayat
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== FORM SOAL ===== -->
                    <form method="POST" action="" id="formLatihan">
                        <?php 
                        $no = 1; 
                        foreach($soal_list as $soal):
                            $soal_id = $soal['id'];
                            $jawaban_user = '';
                            $is_benar = null;
                            
                            if ($show_review && $hasil) {
                                $detail = $hasil['detail_jawaban'][$soal_id] ?? null;
                                if ($detail) {
                                    $jawaban_user = $detail['jawaban_user'];
                                    $is_benar = $detail['is_benar'];
                                }
                            }
                            
                            $review_class = '';
                            if ($show_review) {
                                if ($is_benar === true) $review_class = 'review-correct';
                                elseif ($is_benar === false) $review_class = 'review-wrong';
                            }
                        ?>
                        <div class="soal-item <?php echo $review_class; ?>">
                            <div class="d-flex">
                                <span class="soal-number"><?php echo $no; ?></span>
                                <div style="flex: 1;">
                                    <div class="soal-text">
                                        <?php echo htmlspecialchars($soal['pertanyaan']); ?>
                                        <?php if ($show_review): ?>
                                            <?php if ($is_benar === true): ?>
                                                <span class="badge-benar"><i class="fas fa-check"></i> Benar</span>
                                            <?php elseif ($is_benar === false): ?>
                                                <span class="badge-salah"><i class="fas fa-times"></i> Salah</span>
                                                <span class="badge-jawaban-benar ms-2">
                                                    Jawaban: <?php echo $soal['jawaban_benar']; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php 
                                    $options = ['A' => $soal['pilihan_a'], 'B' => $soal['pilihan_b'], 
                                                'C' => $soal['pilihan_c'], 'D' => $soal['pilihan_d']];
                                    foreach($options as $key => $value):
                                        if(empty($value)) continue;
                                        $checked = ($jawaban_user == $key) ? 'checked' : '';
                                        $disabled = $show_review ? 'disabled' : '';
                                    ?>
                                    <div class="option-item <?php echo ($jawaban_user == $key) ? 'selected' : ''; ?>" 
                                         onclick="<?php echo $show_review ? '' : "selectOption(this, 'soal_{$soal_id}_{$key}')"; ?>">
                                        <input type="radio" name="jawaban[<?php echo $soal_id; ?>]" 
                                               id="soal_<?php echo $soal_id; ?>_<?php echo $key; ?>" 
                                               value="<?php echo $key; ?>" <?php echo $checked . ' ' . $disabled; ?>>
                                        <span class="option-label"><?php echo $key; ?>.</span>
                                        <span><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php $no++; endforeach; ?>
                        
                        <?php if (!$show_review): ?>
                        <div class="text-center mt-4">
                            <button type="submit" name="submit_jawaban" class="btn-submit-latihan">
                                <i class="fas fa-check-circle me-2"></i>Kirim Jawaban
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                    
                <?php else: ?>
                    <div class="text-center" style="padding: 60px 0;">
                        <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                        <h5 style="color: #999; margin-top: 20px;">Belum ada soal untuk materi ini</h5>
                        <p style="color: #bbb;">Soal akan segera ditambahkan</p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($materi_id > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Materi tidak ditemukan.
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 0;">
                    <i class="fas fa-hand-pointer" style="font-size: 50px; color: #ddd;"></i>
                    <h5 style="color: #999; margin-top: 20px;">Pilih materi di atas untuk mulai latihan</h5>
                    <p style="color: #bbb;">Pilih materi yang ingin Anda pelajari</p>
                </div>
            <?php endif; ?>
            
        </div>
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

// Auto select jika radio sudah dipilih
document.querySelectorAll('.option-item input[type="radio"]').forEach(radio => {
    if (radio.checked) {
        radio.closest('.option-item').classList.add('selected');
    }
});
</script>

<?php include '../includes/footer.php'; ?>