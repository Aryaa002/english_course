<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$toefl_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

// Ambil semua TOEFL yang aktif
$stmt = $pdo->query("SELECT t.*, 
                     (SELECT COUNT(*) FROM toefl_sections WHERE toefl_id = t.id) as total_sections,
                     (SELECT COUNT(*) FROM toefl_results WHERE toefl_id = t.id AND user_id = $user_id) as sudah_dikerjakan
                     FROM toefl_tests t 
                     WHERE t.is_active = 1 
                     ORDER BY t.created_at DESC");
$toefl_list = $stmt->fetchAll();

// Jika ada toefl_id, tampilkan detail
if ($toefl_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toefl_tests WHERE id = ? AND is_active = 1");
    $stmt->execute([$toefl_id]);
    $toefl = $stmt->fetch();
    
    if ($toefl) {
        // Ambil sections
        $stmt = $pdo->prepare("SELECT * FROM toefl_sections WHERE toefl_id = ? ORDER BY urutan");
        $stmt->execute([$toefl_id]);
        $sections = $stmt->fetchAll();
        
        // Cek hasil
        $stmt = $pdo->prepare("SELECT * FROM toefl_results WHERE user_id = ? AND toefl_id = ?");
        $stmt->execute([$user_id, $toefl_id]);
        $result = $stmt->fetch();
        
        // Ambil soal untuk section tertentu atau semua
        if ($section_id > 0) {
            $stmt = $pdo->prepare("SELECT q.*, 
                                   ag.judul as audio_judul, ag.media_file as audio_file, ag.media_url as audio_url,
                                   rp.judul as passage_judul, rp.teks as passage_teks
                                   FROM toefl_questions q 
                                   LEFT JOIN toefl_audio_groups ag ON q.audio_group_id = ag.id
                                   LEFT JOIN toefl_reading_passages rp ON q.passage_id = rp.id
                                   WHERE q.section_id = ? 
                                   ORDER BY q.audio_group_id, q.passage_id, q.id");
            $stmt->execute([$section_id]);
            $questions = $stmt->fetchAll();
            
            // Ambil section info
            $stmt = $pdo->prepare("SELECT * FROM toefl_sections WHERE id = ?");
            $stmt->execute([$section_id]);
            $current_section = $stmt->fetch();
        } else {
            $questions = [];
            $current_section = null;
        }
        
        // Proses submit jawaban
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_toefl']) && !$result) {
            $jawaban = $_POST['jawaban'] ?? [];
            $total_benar = 0;
            $total_soal = 0;
            $total_poin = 0;
            $listening_skor = 0;
            $structure_skor = 0;
            $reading_skor = 0;
            $writing_skor = 0;
            
            foreach ($jawaban as $soal_id => $jawaban_user) {
                $stmt = $pdo->prepare("SELECT * FROM toefl_questions WHERE id = ?");
                $stmt->execute([$soal_id]);
                $soal = $stmt->fetch();
                
                if ($soal) {
                    $total_soal++;
                    if ($jawaban_user == $soal['jawaban_benar']) {
                        $total_benar++;
                        $total_poin += $soal['poin'];
                        
                        switch ($soal['type']) {
                            case 'listening': $listening_skor += $soal['poin']; break;
                            case 'structure': $structure_skor += $soal['poin']; break;
                            case 'reading': $reading_skor += $soal['poin']; break;
                            case 'writing': $writing_skor += $soal['poin']; break;
                        }
                    }
                }
            }
            
            // Simpan hasil
            $stmt = $pdo->prepare("INSERT INTO toefl_results (user_id, toefl_id, total_skor, listening_skor, structure_skor, reading_skor, writing_skor, total_benar, total_salah, total_soal, jawaban, waktu_selesai, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'selesai')");
            $stmt->execute([
                $user_id, $toefl_id, $total_poin, $listening_skor, $structure_skor, 
                $reading_skor, $writing_skor, $total_benar, $total_soal - $total_benar, 
                $total_soal, json_encode($jawaban)
            ]);
            
            echo '<meta http-equiv="refresh" content="0;url=toefl.php?id=' . $toefl_id . '">';
            exit();
        }
    }
}

include '../includes/header.php';
?>

<style>
    .toefl-page {
        padding: 30px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .toefl-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.08);
    }
    
    /* ===== TOEFL CARD ===== */
    .toefl-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
        cursor: pointer;
        border-left: 4px solid #F4B41A;
        margin-bottom: 20px;
    }
    
    .toefl-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    /* ===== TOEFL HEADER ===== */
    .toefl-header {
        background: linear-gradient(135deg, #1B2A4A, #2C4066);
        color: white;
        border-radius: 15px;
        padding: 25px 30px;
        margin-bottom: 25px;
    }
    
    .toefl-header h3 {
        font-weight: 700;
        margin: 0;
    }
    
    .toefl-header p {
        opacity: 0.8;
        margin: 5px 0 0;
    }
    
    /* ===== SECTION TABS ===== */
    .section-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        flex-wrap: wrap;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 15px;
    }
    
    .section-tabs .tab-btn {
        padding: 10px 25px;
        border-radius: 30px;
        border: 2px solid #e0e0e0;
        background: white;
        color: #666;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
        cursor: pointer;
        text-decoration: none;
    }
    
    .section-tabs .tab-btn:hover {
        border-color: #F4B41A;
        color: #1B2A4A;
    }
    
    .section-tabs .tab-btn.active {
        background: #1B2A4A;
        border-color: #1B2A4A;
        color: white;
    }
    
    .section-tabs .tab-btn .badge-count {
        background: rgba(255,255,255,0.2);
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 11px;
        margin-left: 8px;
    }
    
    .section-tabs .tab-btn.active .badge-count {
        background: rgba(255,255,255,0.2);
    }
    
    /* ===== READING PASSAGE ===== */
    .reading-passage {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        border-left: 4px solid #fd7e14;
    }
    
    .reading-passage .passage-title {
        color: #1B2A4A;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 10px;
    }
    
    .reading-passage .passage-title i {
        color: #fd7e14;
        margin-right: 10px;
    }
    
    .reading-passage .passage-text {
        max-height: 350px;
        overflow-y: auto;
        font-size: 15px;
        line-height: 1.8;
        color: #333;
        padding-right: 10px;
    }
    
    .reading-passage .passage-text::-webkit-scrollbar {
        width: 6px;
    }
    
    .reading-passage .passage-text::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }
    
    .reading-passage .passage-text::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .reading-passage .passage-source {
        color: #999;
        font-size: 13px;
        margin-top: 10px;
        font-style: italic;
    }
    
    /* ===== AUDIO PLAYER ===== */
    .audio-player {
        background: #1B2A4A;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
    }
    
    .audio-player .audio-title {
        color: #F4B41A;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .audio-player audio,
    .audio-player video {
        width: 100%;
        border-radius: 8px;
    }
    
    .audio-player audio {
        height: 50px;
    }
    
    /* ===== SOAL ITEM ===== */
    .soal-item {
        border-bottom: 1px solid #f0f0f0;
        padding: 20px 0;
    }
    
    .soal-item:last-child {
        border-bottom: none;
    }
    
    .soal-item .soal-number {
        display: inline-block;
        width: 32px;
        height: 32px;
        background: #1B2A4A;
        color: white;
        border-radius: 50%;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        font-size: 14px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    
    .soal-item .soal-text {
        font-weight: 600;
        color: #1B2A4A;
        margin-bottom: 15px;
        font-size: 15px;
    }
    
    .option-item {
        padding: 10px 15px;
        margin: 4px 0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .option-item:hover {
        background: rgba(244, 180, 26, 0.05);
        border-color: #F4B41A;
    }
    
    .option-item input[type="radio"] {
        accent-color: #F4B41A;
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }
    
    .option-item.selected {
        background: rgba(244, 180, 26, 0.1);
        border-color: #F4B41A;
    }
    
    .option-item .option-label {
        font-weight: 600;
        color: #1B2A4A;
        min-width: 25px;
    }
    
    .option-item .option-text {
        color: #333;
    }
    
    /* ===== BUTTONS ===== */
    .btn-submit-toefl {
        background: #F4B41A;
        color: #1B2A4A;
        padding: 14px 50px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 17px;
        border: none;
        transition: all 0.3s;
    }
    
    .btn-submit-toefl:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(244, 180, 26, 0.4);
        color: #1B2A4A;
    }
    
    .btn-nav {
        padding: 10px 30px;
        border-radius: 25px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-nav-prev {
        background: #e9ecef;
        color: #495057;
    }
    
    .btn-nav-prev:hover {
        background: #dee2e6;
        color: #495057;
    }
    
    .btn-nav-next {
        background: #1B2A4A;
        color: white;
    }
    
    .btn-nav-next:hover {
        background: #2C4066;
        color: white;
    }
    
    /* ===== RESULT BOX ===== */
    .result-box {
        background: linear-gradient(135deg, #1B2A4A, #2C4066);
        color: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .result-box .score {
        font-size: 56px;
        font-weight: 700;
        color: #F4B41A;
    }
    
    .result-box .sub-score {
        display: inline-block;
        padding: 8px 20px;
        margin: 5px;
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
    }
    
    .result-box .sub-score .label {
        font-size: 12px;
        opacity: 0.7;
        display: block;
    }
    
    .result-box .sub-score .value {
        font-size: 20px;
        font-weight: 700;
        color: #F4B41A;
    }
    
    .status-lulus {
        color: #28a745;
        font-size: 24px;
        font-weight: 700;
    }
    
    .status-tidak-lulus {
        color: #dc3545;
        font-size: 24px;
        font-weight: 700;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .toefl-container {
            padding: 15px;
        }
        .section-tabs .tab-btn {
            padding: 8px 16px;
            font-size: 12px;
        }
        .toefl-header h3 {
            font-size: 20px;
        }
        .result-box .score {
            font-size: 40px;
        }
        .reading-passage .passage-text {
            max-height: 200px;
            font-size: 14px;
        }
        .option-item {
            padding: 8px 12px;
            font-size: 14px;
        }
    }
</style>

<div class="toefl-page">
    <div class="container">
        <div class="toefl-container">
            
            <?php if (isset($toefl) && $toefl): ?>
                <!-- ===== TOEFL HEADER ===== -->
                <div class="toefl-header">
                    <h3><i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> <?php echo htmlspecialchars($toefl['judul']); ?></h3>
                    <p><?php echo htmlspecialchars($toefl['deskripsi']); ?></p>
                    <div class="mt-2">
                        <span class="badge" style="background: #F4B41A; color: #1B2A4A;">
                            <i class="fas fa-clock"></i> <?php echo $toefl['waktu']; ?> menit
                        </span>
                        <span class="badge" style="background: rgba(255,255,255,0.2); color: white;">
                            <i class="fas fa-check-circle"></i> Passing Grade: <?php echo $toefl['passing_grade']; ?>
                        </span>
                    </div>
                </div>
                
                <?php if (isset($result) && $result): ?>
                    <!-- ===== RESULT ===== -->
                    <div class="result-box">
                        <h5 style="color: #F4B41A; margin-bottom: 10px;">Hasil TOEFL</h5>
                        <div class="score"><?php echo $result['total_skor']; ?></div>
                        <div style="font-size: 16px; margin: 10px 0;">
                            <?php echo $result['total_benar']; ?> Jawaban Benar dari <?php echo $result['total_soal']; ?> Soal
                        </div>
                        <div class="mt-3">
                            <div class="sub-score">
                                <span class="label">Listening</span>
                                <span class="value"><?php echo $result['listening_skor']; ?></span>
                            </div>
                            <div class="sub-score">
                                <span class="label">Structure</span>
                                <span class="value"><?php echo $result['structure_skor']; ?></span>
                            </div>
                            <div class="sub-score">
                                <span class="label">Reading</span>
                                <span class="value"><?php echo $result['reading_skor']; ?></span>
                            </div>
                            <div class="sub-score">
                                <span class="label">Writing</span>
                                <span class="value"><?php echo $result['writing_skor']; ?></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <?php if($result['total_skor'] >= $toefl['passing_grade']): ?>
                                <span class="status-lulus">✅ SELAMAT! ANDA LULUS</span>
                            <?php else: ?>
                                <span class="status-tidak-lulus">❌ ANDA TIDAK LULUS</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="toefl.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 12px 40px; border-radius: 25px; font-weight: 600;">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar TOEFL
                        </a>
                    </div>
                    
                <?php elseif (count($sections) > 0): ?>
                    
                    <!-- ===== SECTION TABS ===== -->
                    <div class="section-tabs">
                        <?php foreach($sections as $index => $sec): 
                            $is_active = ($section_id == $sec['id']) || ($section_id == 0 && $index == 0);
                            if ($section_id == 0 && $index == 0) {
                                $section_id = $sec['id'];
                                // Redirect ke section pertama
                                echo '<meta http-equiv="refresh" content="0;url=toefl.php?id=' . $toefl_id . '&section=' . $sec['id'] . '">';
                            }
                        ?>
                            <a href="toefl.php?id=<?php echo $toefl_id; ?>&section=<?php echo $sec['id']; ?>" 
                               class="tab-btn <?php echo ($section_id == $sec['id']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($sec['nama']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- ===== QUESTIONS ===== -->
                    <?php if (isset($current_section) && $current_section): ?>
                        <form method="POST" action="" id="formToefl">
                            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
                            
                            <h5 style="color: #1B2A4A; font-weight: 700; margin-bottom: 20px;">
                                <i class="fas fa-question-circle" style="color: #F4B41A;"></i> 
                                <?php echo htmlspecialchars($current_section['nama']); ?>
                            </h5>
                            
                            <?php 
                            $current_audio_group = 0;
                            $current_passage = 0;
                            $no = 0;
                            
                            foreach($questions as $soal):
                                // ===== TAMPILKAN AUDIO GROUP =====
                                if ($soal['audio_group_id'] > 0 && $soal['audio_group_id'] != $current_audio_group) {
                                    $current_audio_group = $soal['audio_group_id'];
                                    $audio = $soal; // audio data sudah di-join
                            ?>
                                <div class="audio-player">
                                    <div class="audio-title">
                                        <i class="fas fa-headphones"></i> 
                                        <?php echo htmlspecialchars($soal['audio_judul']); ?>
                                    </div>
                                    <?php if(!empty($soal['audio_file']) && file_exists('../' . $soal['audio_file'])): ?>
                                        <audio controls>
                                            <source src="../<?php echo $soal['audio_file']; ?>" type="audio/mpeg">
                                            Browser tidak mendukung audio.
                                        </audio>
                                    <?php elseif(!empty($soal['audio_url'])): ?>
                                        <audio controls>
                                            <source src="<?php echo $soal['audio_url']; ?>" type="audio/mpeg">
                                            Browser tidak mendukung audio.
                                        </audio>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                }
                                
                                // ===== TAMPILKAN READING PASSAGE =====
                                if ($soal['passage_id'] > 0 && $soal['passage_id'] != $current_passage) {
                                    $current_passage = $soal['passage_id'];
                            ?>
                                <div class="reading-passage">
                                    <div class="passage-title">
                                        <i class="fas fa-file-alt"></i> 
                                        <?php echo htmlspecialchars($soal['passage_judul']); ?>
                                    </div>
                                    <div class="passage-text">
                                        <?php echo nl2br(htmlspecialchars($soal['passage_teks'])); ?>
                                    </div>
                                    <?php if(!empty($soal['passage_source'])): ?>
                                        <div class="passage-source">Sumber: <?php echo htmlspecialchars($soal['passage_source']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                }
                                
                                // ===== TAMPILKAN SOAL =====
                                $no++;
                            ?>
                            <div class="soal-item">
                                <div class="d-flex">
                                    <span class="soal-number"><?php echo $no; ?></span>
                                    <div style="flex: 1;">
                                        <div class="soal-text"><?php echo htmlspecialchars($soal['pertanyaan']); ?></div>
                                        
                                        <?php 
                                        $options = [
                                            'A' => $soal['pilihan_a'],
                                            'B' => $soal['pilihan_b'],
                                            'C' => $soal['pilihan_c'],
                                            'D' => $soal['pilihan_d'],
                                            'E' => $soal['pilihan_e']
                                        ];
                                        foreach($options as $key => $value): 
                                            if(empty($value)) continue;
                                        ?>
                                        <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_<?php echo strtolower($key); ?>')">
                                            <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" 
                                                   id="soal_<?php echo $soal['id']; ?>_<?php echo strtolower($key); ?>" 
                                                   value="<?php echo $key; ?>">
                                            <span class="option-label"><?php echo $key; ?>.</span>
                                            <span class="option-text"><?php echo htmlspecialchars($value); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- ===== BUTTONS ===== -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <?php 
                                    // Cari section sebelumnya
                                    $prev_section = null;
                                    foreach($sections as $idx => $sec) {
                                        if ($sec['id'] == $section_id && $idx > 0) {
                                            $prev_section = $sections[$idx - 1];
                                        }
                                    }
                                    if ($prev_section): 
                                    ?>
                                    <a href="toefl.php?id=<?php echo $toefl_id; ?>&section=<?php echo $prev_section['id']; ?>" 
                                       class="btn-nav btn-nav-prev">
                                        <i class="fas fa-arrow-left me-2"></i>Sebelumnya
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php 
                                    // Cari section berikutnya
                                    $next_section = null;
                                    foreach($sections as $idx => $sec) {
                                        if ($sec['id'] == $section_id && $idx < count($sections) - 1) {
                                            $next_section = $sections[$idx + 1];
                                        }
                                    }
                                    if ($next_section): 
                                    ?>
                                    <a href="toefl.php?id=<?php echo $toefl_id; ?>&section=<?php echo $next_section['id']; ?>" 
                                       class="btn-nav btn-nav-next">
                                        Selanjutnya <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                    <?php else: ?>
                                    <button type="submit" name="submit_toefl" class="btn-submit-toefl" 
                                            onclick="return confirm('Apakah Anda yakin ingin mengirim semua jawaban? TOEFL hanya bisa dikerjakan SEKALI!')">
                                        <i class="fas fa-check-circle me-2"></i>Selesai & Kirim Jawaban
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px 0;">
                            <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                            <p style="color: #999; margin-top: 15px;">Belum ada soal untuk section ini</p>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center" style="padding: 40px 0;">
                        <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                        <p style="color: #999; margin-top: 15px;">Belum ada sections untuk TOEFL ini</p>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- ===== DAFTAR TOEFL ===== -->
                <h4 style="color: #1B2A4A; font-weight: 700; margin-bottom: 25px;">
                    <i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> 
                    Pilih Test TOEFL
                </h4>
                
                <?php if(count($toefl_list) > 0): ?>
                    <?php foreach($toefl_list as $t): ?>
                    <div class="toefl-card" onclick="window.location.href='toefl.php?id=<?php echo $t['id']; ?>'">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 style="color: #1B2A4A; font-weight: 700;">
                                <?php echo htmlspecialchars($t['judul']); ?>
                            </h5>
                            <?php if($t['sudah_dikerjakan'] > 0): ?>
                                <span class="badge" style="background: #28a745; color: white;">✅ Selesai</span>
                            <?php else: ?>
                                <span class="badge" style="background: #F4B41A; color: #1B2A4A;">
                                    <i class="fas fa-clock"></i> <?php echo $t['waktu']; ?> menit
                                </span>
                            <?php endif; ?>
                        </div>
                        <p style="color: #666; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($t['deskripsi']); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge" style="background: #1B2A4A; color: white;">
                                    <i class="fas fa-layer-group"></i> <?php echo $t['total_sections']; ?> Sections
                                </span>
                                <span class="badge" style="background: #F4B41A; color: #1B2A4A; margin-left: 5px;">
                                    <i class="fas fa-check-circle"></i> Passing: <?php echo $t['passing_grade']; ?>
                                </span>
                            </div>
                            <?php if($t['sudah_dikerjakan'] > 0): ?>
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center" style="padding: 60px 0;">
                        <i class="fas fa-inbox" style="font-size: 60px; color: #ddd;"></i>
                        <h5 style="color: #999; margin-top: 20px;">Belum ada TOEFL tersedia</h5>
                        <p style="color: #bbb;">TOEFL akan segera ditambahkan</p>
                    </div>
                <?php endif; ?>
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