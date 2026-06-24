<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$toefl_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

// Ambil semua TOEFL yang aktif
$stmt = $pdo->query("SELECT t.*, 
                     (SELECT COUNT(*) FROM toefl_sections WHERE toefl_id = t.id) as total_sections,
                     (SELECT COUNT(*) FROM toefl_results WHERE toefl_id = t.id AND user_id = $user_id) as sudah_dikerjakan
                     FROM toefl_tests t 
                     WHERE t.is_active = 1 
                     ORDER BY t.created_at DESC");
$toefl_list = $stmt->fetchAll();

if ($toefl_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toefl_tests WHERE id = ? AND is_active = 1");
    $stmt->execute([$toefl_id]);
    $toefl = $stmt->fetch();
    
    if ($toefl) {
        $stmt = $pdo->prepare("SELECT * FROM toefl_sections WHERE toefl_id = ? ORDER BY urutan");
        $stmt->execute([$toefl_id]);
        $sections = $stmt->fetchAll();
        
        if ($start == 1 && count($sections) > 0) {
            $first_section = $sections[0];
            echo '<meta http-equiv="refresh" content="0;url=toefl.php?id=' . $toefl_id . '&section=' . $first_section['id'] . '">';
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT * FROM toefl_results WHERE user_id = ? AND toefl_id = ?");
        $stmt->execute([$user_id, $toefl_id]);
        $result = $stmt->fetch();
        
        if ($section_id > 0) {
            // ===== AMBIL SOAL =====
            $stmt = $pdo->prepare("SELECT q.*, 
                                   ag.judul as audio_judul, ag.media_file as audio_file, ag.media_url as audio_url,
                                   rp.id as passage_id,
                                   rp.judul as passage_judul, 
                                   rp.teks as passage_teks
                                   FROM toefl_questions q 
                                   LEFT JOIN toefl_audio_groups ag ON q.audio_group_id = ag.id
                                   LEFT JOIN toefl_reading_passages rp ON q.passage_id = rp.id
                                   WHERE q.section_id = ? 
                                   ORDER BY q.passage_id, q.audio_group_id, q.id");
            $stmt->execute([$section_id]);
            $questions = $stmt->fetchAll();
            
            // ===== AMBIL DATA PASSAGE UNIK =====
            $passages = [];
            foreach ($questions as $q) {
                if ($q['passage_id'] > 0 && !isset($passages[$q['passage_id']])) {
                    $passages[$q['passage_id']] = [
                        'id' => $q['passage_id'],
                        'judul' => $q['passage_judul'],
                        'teks' => $q['passage_teks']
                    ];
                }
            }
            
            $stmt = $pdo->prepare("SELECT * FROM toefl_sections WHERE id = ?");
            $stmt->execute([$section_id]);
            $current_section = $stmt->fetch();
        } else {
            $questions = [];
            $passages = [];
            $current_section = null;
        }
        
        // ============================================================
        // PROSES SIMPAN JAWABAN (AJAX) - REAL TIME
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_answer'])) {
            $soal_id = (int)$_POST['soal_id'];
            $jawaban_user = $_POST['jawaban'] ?? '';
            
            // Inisialisasi session answers
            if (!isset($_SESSION['toefl_answers'])) {
                $_SESSION['toefl_answers'] = [];
            }
            if (!isset($_SESSION['toefl_answers'][$toefl_id])) {
                $_SESSION['toefl_answers'][$toefl_id] = [];
            }
            if (!isset($_SESSION['toefl_answers'][$toefl_id][$section_id])) {
                $_SESSION['toefl_answers'][$toefl_id][$section_id] = [];
            }
            $_SESSION['toefl_answers'][$toefl_id][$section_id][$soal_id] = $jawaban_user;
            
            echo 'success';
            exit();
        }
        
        // ============================================================
        // PROSES TOGGLE FLAG
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_flag'])) {
            $soal_id = (int)$_POST['soal_id'];
            $flag = $_POST['flag'] == 'true' ? true : false;
            
            if (!isset($_SESSION['toefl_flagged'])) {
                $_SESSION['toefl_flagged'] = [];
            }
            if (!isset($_SESSION['toefl_flagged'][$toefl_id])) {
                $_SESSION['toefl_flagged'][$toefl_id] = [];
            }
            if (!isset($_SESSION['toefl_flagged'][$toefl_id][$section_id])) {
                $_SESSION['toefl_flagged'][$toefl_id][$section_id] = [];
            }
            $_SESSION['toefl_flagged'][$toefl_id][$section_id][$soal_id] = $flag;
            
            echo 'success';
            exit();
        }
        
        // ============================================================
        // PROSES SUBMIT JAWABAN - AMBIL DARI SESSION
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_toefl']) && !$result) {
            // Ambil semua jawaban dari session
            $all_jawaban = [];
            if (isset($_SESSION['toefl_answers'][$toefl_id])) {
                foreach ($_SESSION['toefl_answers'][$toefl_id] as $sec_id => $answers) {
                    foreach ($answers as $soal_id => $jawaban_user) {
                        $all_jawaban[$soal_id] = $jawaban_user;
                    }
                }
            }
            
            // Inisialisasi counter per tipe soal
            $section_benar = [
                'listening' => 0,
                'structure' => 0,
                'reading' => 0,
                'writing' => 0
            ];
            $section_total = [
                'listening' => 0,
                'structure' => 0,
                'reading' => 0,
                'writing' => 0
            ];
            
            // Ambil semua soal dari semua section
            foreach ($sections as $sec) {
                $stmt = $pdo->prepare("SELECT * FROM toefl_questions WHERE section_id = ?");
                $stmt->execute([$sec['id']]);
                $sec_questions = $stmt->fetchAll();
                
                foreach ($sec_questions as $soal) {
                    $type = $soal['type'];
                    $soal_id = $soal['id'];
                    $jawaban_user = $all_jawaban[$soal_id] ?? '';
                    $jawaban_benar = $soal['jawaban_benar'];
                    
                    $section_total[$type]++;
                    
                    if (!empty($jawaban_user) && $jawaban_user == $jawaban_benar) {
                        $section_benar[$type]++;
                    }
                }
            }
            
            // Fungsi konversi ke skala 0-30
            function calculateToeflScore($benar, $total) {
                if ($total == 0) return 0;
                return round(($benar / $total) * 30, 1);
            }
            
            $listening_skor = calculateToeflScore($section_benar['listening'], $section_total['listening']);
            $structure_skor = calculateToeflScore($section_benar['structure'], $section_total['structure']);
            $reading_skor = calculateToeflScore($section_benar['reading'], $section_total['reading']);
            $writing_skor = calculateToeflScore($section_benar['writing'], $section_total['writing']);
            $total_skor = round($listening_skor + $structure_skor + $reading_skor + $writing_skor, 1);
            
            $total_benar = array_sum($section_benar);
            $total_soal = array_sum($section_total);
            
            // Simpan hasil
            $stmt = $pdo->prepare("INSERT INTO toefl_results (user_id, toefl_id, total_skor, listening_skor, structure_skor, reading_skor, writing_skor, total_benar, total_salah, total_soal, jawaban, waktu_selesai, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'selesai')");
            $stmt->execute([
                $user_id, 
                $toefl_id, 
                $total_skor, 
                $listening_skor, 
                $structure_skor, 
                $reading_skor, 
                $writing_skor, 
                $total_benar, 
                $total_soal - $total_benar, 
                $total_soal, 
                json_encode($all_jawaban)
            ]);
            
            // Hapus session jawaban
            unset($_SESSION['toefl_answers'][$toefl_id]);
            unset($_SESSION['toefl_flagged'][$toefl_id]);
            
            echo '<meta http-equiv="refresh" content="0;url=toefl.php?id=' . $toefl_id . '">';
            exit();
        }
    }
}


if ($toefl_id == 0 || !isset($toefl) || !$toefl) {
    include '../includes/header.php';
    ?>
    <style>
        .toefl-page { padding: 40px 0; background: #f8f9fa; min-height: 100vh; }
        .toefl-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 30px rgba(0,0,0,0.08); }
        .toefl-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: all 0.3s; cursor: pointer; border-left: 4px solid #F4B41A; margin-bottom: 20px; }
        .toefl-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
    </style>
    <div class="toefl-page">
        <div class="container">
            <div class="toefl-container">
                <h4 style="color: #1B2A4A; font-weight: 700; margin-bottom: 25px;">
                    <i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> Pilih Test TOEFL
                </h4>
                <?php if(count($toefl_list) > 0): ?>
                    <?php foreach($toefl_list as $t): ?>
                    <div class="toefl-card" onclick="window.location.href='toefl.php?id=<?php echo $t['id']; ?>&start=1'">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 style="color: #1B2A4A; font-weight: 700;"><?php echo htmlspecialchars($t['judul']); ?></h5>
                            <?php if($t['sudah_dikerjakan'] > 0): ?>
                                <span class="badge" style="background: #28a745; color: white;">✅ Selesai</span>
                            <?php else: ?>
                                <span class="badge" style="background: #F4B41A; color: #1B2A4A;">
                                    <i class="fas fa-clock"></i> <?php echo $t['waktu']; ?> menit
                                </span>
                            <?php endif; ?>
                        </div>
                        <p style="color: #666; margin-bottom: 10px;"><?php echo htmlspecialchars($t['deskripsi']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge" style="background: #1B2A4A; color: white;">
                                    <i class="fas fa-layer-group"></i> <?php echo $t['total_sections']; ?> Sections
                                </span>
                                <span class="badge" style="background: #F4B41A; color: #1B2A4A; margin-left: 5px;">
                                    <i class="fas fa-check-circle"></i> Passing: <?php echo $t['passing_grade']; ?>
                                </span>
                            </div>
                            <?php if($t['sudah_dikerjakan'] > 0): 
                                // Ambil skor terakhir
                                $stmt = $pdo->prepare("SELECT total_skor FROM toefl_results WHERE user_id = ? AND toefl_id = ? ORDER BY id DESC LIMIT 1");
                                $stmt->execute([$user_id, $t['id']]);
                                $last = $stmt->fetch();
                                $skor = $last ? number_format($last['total_skor'], 1) : '?';
                            ?>
                                <span style="color: #28a745; font-weight: 600;">
                                    Skor: <?php echo $skor; ?> <i class="fas fa-arrow-right ms-1"></i>
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
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; exit(); } ?>

<?php
if (isset($result) && $result) {
    include '../includes/header.php';
    ?>
    <style>
        .toefl-page { padding: 40px 0; background: #f8f9fa; min-height: 100vh; }
        .toefl-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 30px rgba(0,0,0,0.08); }
        .result-box { background: linear-gradient(135deg, #1B2A4A, #2C4066); color: white; border-radius: 15px; padding: 30px; text-align: center; margin-bottom: 30px; }
        .result-box .score { font-size: 56px; font-weight: 700; color: #F4B41A; }
        .result-box .sub-score { display: inline-block; padding: 8px 20px; margin: 5px; background: rgba(255,255,255,0.1); border-radius: 10px; }
        .result-box .sub-score .label { font-size: 12px; opacity: 0.7; display: block; }
        .result-box .sub-score .value { font-size: 20px; font-weight: 700; color: #F4B41A; }
        .status-lulus { color: #28a745; font-size: 24px; font-weight: 700; }
        .status-tidak-lulus { color: #dc3545; font-size: 24px; font-weight: 700; }
    </style>
    <div class="toefl-page">
        <div class="container">
            <div class="toefl-container">
                <div class="result-box">
                    <h5 style="color: #F4B41A; margin-bottom: 10px;">Hasil TOEFL</h5>
                    <div class="score"><?php echo number_format($result['total_skor'], 1); ?></div>
                    <div style="font-size: 16px; margin: 10px 0;">
                        Skala TOEFL iBT: 0 – 120
                    </div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.8); margin-bottom: 15px;">
                        <?php echo $result['total_benar']; ?> Jawaban Benar dari <?php echo $result['total_soal']; ?> Soal
                    </div>
                    <div class="mt-3">
                        <div class="sub-score">
                            <span class="label">Listening</span>
                            <span class="value"><?php echo number_format($result['listening_skor'], 1); ?></span>
                        </div>
                        <div class="sub-score">
                            <span class="label">Structure</span>
                            <span class="value"><?php echo number_format($result['structure_skor'], 1); ?></span>
                        </div>
                        <div class="sub-score">
                            <span class="label">Reading</span>
                            <span class="value"><?php echo number_format($result['reading_skor'], 1); ?></span>
                        </div>
                        <div class="sub-score">
                            <span class="label">Writing</span>
                            <span class="value"><?php echo number_format($result['writing_skor'], 1); ?></span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php 
                        $passing_grade = $toefl['passing_grade'] ?? 80;
                        $is_lulus = $result['total_skor'] >= $passing_grade;
                        ?>
                        <?php if($is_lulus): ?>
                            <span class="status-lulus">✅ SELAMAT! ANDA LULUS</span>
                            <div style="font-size: 14px; opacity: 0.7; margin-top: 5px;">
                                Skor Anda <?php echo number_format($result['total_skor'], 1); ?> ≥ <?php echo $passing_grade; ?>
                            </div>
                        <?php else: ?>
                            <span class="status-tidak-lulus">❌ ANDA TIDAK LULUS</span>
                            <div style="font-size: 14px; opacity: 0.7; margin-top: 5px;">
                                Skor Anda <?php echo number_format($result['total_skor'], 1); ?> &lt; <?php echo $passing_grade; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center">
                    <a href="toefl.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 12px 40px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar TOEFL
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; exit(); } ?>

<?php
$current_index = 0;
foreach($sections as $idx => $sec) {
    if ($sec['id'] == $section_id) { $current_index = $idx; break; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOEFL - <?php echo htmlspecialchars($toefl['judul'] ?? 'Practice Test'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; min-height: 100vh; padding-top: 20px; }
        .toefl-wrapper { max-width: 1200px; margin: 0 auto; padding: 0 20px 40px; }
        .toefl-header { background: linear-gradient(135deg, #1B2A4A, #2C4066); color: white; border-radius: 15px; padding: 20px 25px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .toefl-header h3 { font-weight: 700; margin: 0; font-size: 20px; }
        .toefl-header p { opacity: 0.8; margin: 0; font-size: 14px; }
        .toefl-header .header-right { text-align: right; }
        .toefl-header .header-right .badge { font-size: 13px; padding: 6px 15px; }
        .toefl-body { display: flex; gap: 20px; }
        .toefl-sidebar { width: 260px; flex-shrink: 0; position: sticky; top: 20px; height: calc(100vh - 40px); overflow-y: auto; }
        
        .section-tabs { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 15px; }
        .section-tabs .tab-btn { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; border-radius: 8px; border: 2px solid transparent; background: transparent; color: #666; font-weight: 600; font-size: 14px; margin-bottom: 5px; cursor: default; user-select: none; }
        .section-tabs .tab-btn.active { background: #1B2A4A; border-color: #1B2A4A; color: white; }
        .section-tabs .tab-btn.completed { border-color: #28a745; color: #28a745; }
        .section-tabs .tab-btn.completed::after { content: ' ✓'; }
        .section-tabs .tab-btn .badge-count { background: rgba(0,0,0,0.08); padding: 2px 10px; border-radius: 20px; font-size: 11px; min-width: 32px; text-align: center; white-space: nowrap; }
        .section-tabs .tab-btn.active .badge-count { background: rgba(255,255,255,0.2); }
        .section-tabs .tab-btn .tab-name { flex: 1; margin-right: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        .timer-box { background: white; border-radius: 12px; padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 15px; text-align: center; }
        .timer-box .timer-label { color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .timer-box .timer-display { font-size: 36px; font-weight: 700; color: #1B2A4A; font-family: 'Courier New', monospace; }
        .timer-box .timer-display.warning { color: #dc3545; animation: blink 1s infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .timer-box .section-name { color: #666; font-size: 13px; margin-top: 5px; }
        
        .filter-box { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 15px; }
        .filter-box .filter-title { font-weight: 600; color: #1B2A4A; font-size: 14px; margin-bottom: 10px; }
        .filter-box .filter-item { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin: 3px 3px 3px 0; border: 2px solid transparent; }
        .filter-box .filter-item:hover { transform: scale(1.05); }
        .filter-box .filter-item.all { background: #1B2A4A; color: white; }
        .filter-box .filter-item.unanswered { background: #f8d7da; color: #721c24; }
        .filter-box .filter-item.answered { background: #d4edda; color: #155724; }
        .filter-box .filter-item.flagged { background: #fff3cd; color: #856404; }
        .filter-box .filter-item.active { border-color: #F4B41A; transform: scale(1.05); }
        
        .question-nav { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); display: flex; flex-wrap: wrap; gap: 6px; }
        .question-nav .q-btn { width: 38px; height: 38px; border-radius: 8px; border: 2px solid #e0e0e0; background: white; color: #333; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; }
        .question-nav .q-btn:hover { border-color: #F4B41A; }
        .question-nav .q-btn.active { background: #1B2A4A; border-color: #1B2A4A; color: white; }
        .question-nav .q-btn.answered { background: #d4edda; border-color: #28a745; color: #155724; }
        .question-nav .q-btn.flagged { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .question-nav .q-btn.unanswered { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        
        .toefl-main { flex: 1; background: white; border-radius: 12px; padding: 25px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); min-height: 500px; }
        
        /* ===== READING PASSAGE ===== */
        .reading-passage { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #fd7e14; }
        .reading-passage .passage-title { color: #1B2A4A; font-weight: 700; font-size: 16px; margin-bottom: 10px; }
        .reading-passage .passage-text { max-height: 250px; overflow-y: auto; font-size: 14px; line-height: 1.8; color: #333; padding-right: 10px; }
        .reading-passage .passage-text::-webkit-scrollbar { width: 6px; }
        .reading-passage .passage-text::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        .reading-passage .passage-text::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        
        .audio-player { background: #1B2A4A; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px; }
        .audio-player .audio-title { color: #F4B41A; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .audio-player audio, .audio-player video { width: 100%; border-radius: 6px; }
        .audio-player audio { height: 45px; }
        
        .question-item { margin-bottom: 25px; }
        .question-item .question-number { font-weight: 700; color: #1B2A4A; font-size: 16px; margin-bottom: 10px; }
        .question-item .question-text { font-size: 15px; color: #333; margin-bottom: 15px; line-height: 1.7; }
        .option-item { padding: 10px 15px; margin: 4px 0; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; display: flex; align-items: center; gap: 10px; }
        .option-item:hover { background: rgba(244, 180, 26, 0.05); border-color: #F4B41A; }
        .option-item input[type="radio"] { accent-color: #F4B41A; width: 18px; height: 18px; flex-shrink: 0; }
        .option-item.selected { background: rgba(244, 180, 26, 0.1); border-color: #F4B41A; }
        .option-item .option-label { font-weight: 600; color: #1B2A4A; min-width: 25px; }
        
        .btn-flag { background: transparent; border: 2px solid #ffc107; color: #ffc107; padding: 5px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-flag:hover { background: #ffc107; color: #1B2A4A; }
        .btn-flag.active { background: #ffc107; color: #1B2A4A; }
        
        .nav-buttons { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0; flex-wrap: wrap; gap: 10px; }
        .btn-nav { padding: 10px 30px; border-radius: 25px; font-weight: 600; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn-nav-prev { background: #e9ecef; color: #495057; }
        .btn-nav-prev:hover { background: #dee2e6; }
        .btn-nav-next { background: #1B2A4A; color: white; }
        .btn-nav-next:hover { background: #2C4066; }
        .btn-nav-next.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .btn-submit-toefl { background: #F4B41A; color: #1B2A4A; padding: 12px 40px; border-radius: 30px; font-weight: 700; font-size: 16px; border: none; transition: all 0.3s; cursor: pointer; }
        .btn-submit-toefl:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); }
        
        @media (max-width: 992px) { .toefl-body { flex-direction: column; } .toefl-sidebar { width: 100%; position: static; height: auto; } .toefl-main { padding: 20px; } }
        @media (max-width: 576px) { .toefl-header { flex-direction: column; text-align: center; } .toefl-header .header-right { text-align: center; } .question-nav .q-btn { width: 34px; height: 34px; font-size: 12px; } .nav-buttons { flex-direction: column; align-items: stretch; } .btn-nav { text-align: center; } }
    </style>
</head>
<body>
    <div class="toefl-wrapper">
        
        <!-- ===== HEADER ===== -->
        <div class="toefl-header">
            <div>
                <h3><i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> <?php echo htmlspecialchars($toefl['judul']); ?></h3>
                <p><?php echo htmlspecialchars($toefl['deskripsi']); ?></p>
            </div>
            <div class="header-right">
                <span class="badge" style="background: #F4B41A; color: #1B2A4A;">
                    <i class="fas fa-clock"></i> <?php echo $toefl['waktu']; ?> menit
                </span>
                <span class="badge" style="background: rgba(255,255,255,0.2); color: white; margin-left: 5px;">
                    <i class="fas fa-check-circle"></i> PG: <?php echo $toefl['passing_grade']; ?>
                </span>
                <a href="<?php echo BASE_URL; ?>pages/toefl.php" class="btn btn-sm" style="background: rgba(255,255,255,0.15); color: white; margin-left: 10px; border-radius: 20px; text-decoration: none;">
                    <i class="fas fa-times"></i> Keluar
                </a>
            </div>
        </div>
        
        <!-- ===== BODY ===== -->
        <div class="toefl-body">
            
            <!-- ===== SIDEBAR KIRI ===== -->
            <div class="toefl-sidebar">
                
                <!-- Section Tabs - HANYA INDIKATOR -->
                <div class="section-tabs" id="sectionTabs">
                    <?php foreach($sections as $index => $sec): 
                        $is_active = ($section_id == $sec['id']);
                        $is_completed = ($index < $current_index);
                    ?>
                        <div class="tab-btn <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>">
                            <span class="tab-name"><?php echo htmlspecialchars($sec['nama']); ?></span>
                            <?php if($sec['waktu'] > 0): ?>
                                <span class="badge-count"><?php echo $sec['waktu']; ?>m</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Timer -->
                <div class="timer-box">
                    <div class="timer-label"><i class="fas fa-hourglass-half"></i> Waktu Tersisa</div>
                    <div class="timer-display" id="timerDisplay">--:--</div>
                    <div class="section-name" id="sectionName">
                        <?php if ($current_section): ?>
                            <?php echo htmlspecialchars($current_section['nama']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Filter Soal -->
                <div class="filter-box">
                    <div class="filter-title"><i class="fas fa-filter"></i> Filter Soal</div>
                    <div>
                        <span class="filter-item all active" data-filter="all" onclick="applyFilter('all')">
                            Semua (<span id="totalAll">0</span>)
                        </span>
                        <span class="filter-item unanswered" data-filter="unanswered" onclick="applyFilter('unanswered')">
                            Belum Dijawab (<span id="totalUnanswered">0</span>)
                        </span>
                        <span class="filter-item answered" data-filter="answered" onclick="applyFilter('answered')">
                            Sudah Dijawab (<span id="totalAnswered">0</span>)
                        </span>
                        <span class="filter-item flagged" data-filter="flagged" onclick="applyFilter('flagged')">
                            Ditandai (<span id="totalFlagged">0</span>)
                        </span>
                    </div>
                </div>
                
                <!-- Navigasi Soal -->
                <div class="question-nav" id="questionNav">
                    <?php 
                    $soal_count = count($questions);
                    for($i = 0; $i < $soal_count; $i++): 
                        $q = $questions[$i];
                    ?>
                        <button class="q-btn" data-index="<?php echo $i; ?>" data-soal-id="<?php echo $q['id']; ?>" onclick="goToQuestion(<?php echo $i; ?>)">
                            <?php echo $i + 1; ?>
                        </button>
                    <?php endfor; ?>
                </div>
                
            </div>
            
            <!-- ===== MAIN CONTENT ===== -->
            <div class="toefl-main" id="mainContent">
                
                <?php if (count($questions) > 0): ?>
                    
                    <!-- ===== FORM ===== -->
                    <form method="POST" action="" id="formToefl">
                        <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
                        
                        <?php 
                        // ===== KELOMPOKKAN SOAL BERDASARKAN PASSAGE_ID =====
                        $grouped_questions = [];
                        foreach ($questions as $soal) {
                            $passage_id = $soal['passage_id'] ?? 0;
                            if (!isset($grouped_questions[$passage_id])) {
                                $grouped_questions[$passage_id] = [];
                            }
                            $grouped_questions[$passage_id][] = $soal;
                        }
                        
                        // Urutkan: group dengan passage_id > 0 dulu
                        uksort($grouped_questions, function($a, $b) {
                            $a_is_passage = is_numeric($a) && $a > 0;
                            $b_is_passage = is_numeric($b) && $b > 0;
                            if ($a_is_passage && !$b_is_passage) return -1;
                            if (!$a_is_passage && $b_is_passage) return 1;
                            return 0;
                        });
                        
                        $displayed_audio_groups = [];
                        $no = 0;
                        
                        foreach ($grouped_questions as $passage_id => $group_soals):
                            // Tampilkan passage sekali untuk group ini
                            if ($passage_id > 0) {
                                $first_soal = $group_soals[0];
                                ?>
                                <div class="reading-passage">
                                    <div class="passage-title">
                                        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($first_soal['passage_judul']); ?>
                                    </div>
                                    <div class="passage-text">
                                        <?php echo nl2br(htmlspecialchars($first_soal['passage_teks'])); ?>
                                    </div>
                                </div>
                                <?php
                            }
                            
                            // Tampilkan soal-soal dalam group
                            foreach ($group_soals as $soal):
                                $no++;
                                // Tampilkan audio group jika ada (hanya sekali)
                                if ($soal['audio_group_id'] > 0 && !in_array($soal['audio_group_id'], $displayed_audio_groups)) {
                                    $displayed_audio_groups[] = $soal['audio_group_id'];
                                    ?>
                                    <div class="audio-player">
                                        <div class="audio-title">
                                            <i class="fas fa-headphones"></i> <?php echo htmlspecialchars($soal['audio_judul']); ?>
                                        </div>
                                        <?php if(!empty($soal['audio_file']) && file_exists('../' . $soal['audio_file'])): ?>
                                            <audio controls><source src="../<?php echo $soal['audio_file']; ?>" type="audio/mpeg"></audio>
                                        <?php elseif(!empty($soal['audio_url'])): ?>
                                            <audio controls><source src="<?php echo $soal['audio_url']; ?>" type="audio/mpeg"></audio>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                                <!-- ===== SOAL ===== -->
                                <div class="question-item" data-soal-id="<?php echo $soal['id']; ?>" data-index="<?php echo $no - 1; ?>">
                                    <div class="question-number">
                                        Soal <?php echo $no; ?>
                                        <button type="button" class="btn-flag" onclick="toggleFlag(this, <?php echo $soal['id']; ?>)">
                                            <i class="fas fa-flag"></i> Tandai
                                        </button>
                                    </div>
                                    <div class="question-text"><?php echo htmlspecialchars($soal['pertanyaan']); ?></div>
                                    
                                    <?php 
                                    $options = [
                                        'A' => $soal['pilihan_a'],
                                        'B' => $soal['pilihan_b'],
                                        'C' => $soal['pilihan_c'],
                                        'D' => $soal['pilihan_d'],
                                        'E' => $soal['pilihan_e']
                                    ];
                                    foreach($options as $opt_key => $value): 
                                        if(empty($value)) continue;
                                    ?>
                                    <div class="option-item" onclick="selectOption(this, 'soal_<?php echo $soal['id']; ?>_<?php echo strtolower($opt_key); ?>')">
                                        <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" 
                                               id="soal_<?php echo $soal['id']; ?>_<?php echo strtolower($opt_key); ?>" 
                                               value="<?php echo $opt_key; ?>">
                                        <span class="option-label"><?php echo $opt_key; ?>.</span>
                                        <span><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        
                        <!-- ===== NAVIGASI ===== -->
                        <div class="nav-buttons">
                            <div>
                                <?php 
                                $prev_section = null;
                                foreach($sections as $idx => $sec) {
                                    if ($sec['id'] == $section_id && $idx > 0) {
                                        $prev_section = $sections[$idx - 1];
                                    }
                                }
                                if ($prev_section): 
                                ?>
                                <a href="#" class="btn-nav btn-nav-prev" id="btnPrevSection" style="display:none;">
                                    <i class="fas fa-arrow-left me-2"></i>Sebelumnya
                                </a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php 
                                $next_section = null;
                                foreach($sections as $idx => $sec) {
                                    if ($sec['id'] == $section_id && $idx < count($sections) - 1) {
                                        $next_section = $sections[$idx + 1];
                                    }
                                }
                                if ($next_section): 
                                ?>
                                <button type="button" class="btn-nav btn-nav-next" id="btnNextSection">
                                    Selanjutnya <i class="fas fa-arrow-right ms-2"></i>
                                </button>
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
                    <div class="text-center" style="padding: 60px 0;">
                        <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                        <p style="color: #999; margin-top: 15px;">Belum ada soal untuk section ini</p>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <script>
        // =============================================
        // DATA
        // =============================================
        const totalQuestions = <?php echo count($questions); ?>;
        const sectionId = <?php echo $section_id; ?>;
        const toeflId = <?php echo $toefl_id; ?>;
        const currentSectionIndex = <?php echo $current_index; ?>;
        const totalSections = <?php echo count($sections); ?>;
        const sectionIds = <?php echo json_encode(array_column($sections, 'id')); ?>;
        
        let answeredStatus = {};
        let flaggedStatus = {};
        let currentFilter = 'all';
        let currentQuestionIndex = 0;
        let isSectionCompleted = false;
        
        // =============================================
        // SELECT OPTION
        // =============================================
        function selectOption(element, inputId) {
            const parent = element.closest('.question-item');
            parent.querySelectorAll('.option-item').forEach(opt => {
                opt.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById(inputId).checked = true;
            
            const soalId = parent.dataset.soalId;
            answeredStatus[soalId] = true;
            updateQuestionStatus();
            updateFilterCounts();
            updateNavButtons();
            checkAllAnswered();
        }
        
        // =============================================
        // SAVE ANSWER (AJAX)
        // =============================================
        function saveAnswer(soalId, jawaban) {
            if (!soalId) return;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `save_answer=1&soal_id=${soalId}&jawaban=${jawaban}`
            })
            .then(response => response.text())
            .then(data => {
                // Update status di sidebar
                const qNum = document.querySelector(`.q-btn[data-soal-id="${soalId}"]`);
                if (qNum) {
                    qNum.classList.remove('unanswered', 'answered', 'flagged');
                    qNum.classList.add('answered');
                    qNum.dataset.status = 'answered';
                    updateCounts();
                }
                checkAllAnswered();
            })
            .catch(error => console.error('Error:', error));
        }
        // =============================================
        // TOGGLE FLAG
        // =============================================
        function toggleFlag(btn, soalId) {
            btn.classList.toggle('active');
            flaggedStatus[soalId] = btn.classList.contains('active');
            updateFilterCounts();
            updateNavButtons();
        }
        
        // =============================================
        // UPDATE STATUS
        // =============================================
        function updateQuestionStatus() {
            document.querySelectorAll('.question-item').forEach(item => {
                const soalId = item.dataset.soalId;
                const radio = item.querySelector('input[type="radio"]:checked');
                if (radio) {
                    answeredStatus[soalId] = true;
                }
            });
        }
        
        function updateNavButtons() {
            document.querySelectorAll('.q-btn').forEach(btn => {
                const soalId = btn.dataset.soalId;
                const isAnswered = answeredStatus[soalId] || false;
                const isFlagged = flaggedStatus[soalId] || false;
                
                btn.classList.remove('answered', 'unanswered', 'flagged', 'active');
                if (isAnswered) btn.classList.add('answered');
                else btn.classList.add('unanswered');
                if (isFlagged) btn.classList.add('flagged');
            });
        }
        
        function updateFilterCounts() {
            let total = 0, answered = 0, unanswered = 0, flagged = 0;
            document.querySelectorAll('.question-item').forEach(item => {
                const soalId = item.dataset.soalId;
                const isAnswered = answeredStatus[soalId] || false;
                const isFlagged = flaggedStatus[soalId] || false;
                total++;
                if (isAnswered) answered++;
                else unanswered++;
                if (isFlagged) flagged++;
            });
            document.getElementById('totalAll').textContent = total;
            document.getElementById('totalAnswered').textContent = answered;
            document.getElementById('totalUnanswered').textContent = unanswered;
            document.getElementById('totalFlagged').textContent = flagged;
        }
        
        // =============================================
        // FILTER
        // =============================================
        function applyFilter(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-item').forEach(el => {
                el.classList.remove('active');
                if (el.dataset.filter === filter) el.classList.add('active');
            });
            document.querySelectorAll('.question-item').forEach(item => {
                const soalId = item.dataset.soalId;
                const isAnswered = answeredStatus[soalId] || false;
                const isFlagged = flaggedStatus[soalId] || false;
                let show = true;
                if (filter === 'unanswered') show = !isAnswered;
                else if (filter === 'answered') show = isAnswered;
                else if (filter === 'flagged') show = isFlagged;
                item.style.display = show ? 'block' : 'none';
            });
        }
        
        // =============================================
        // GO TO QUESTION
        // =============================================
        function goToQuestion(index) {
            const items = document.querySelectorAll('.question-item');

            if (items[index]) {
                items[index].scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            document.querySelectorAll('.q-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            document.querySelector(`.q-btn[data-index="${index}"]`)
                ?.classList.add('active');
        }
        
        // =============================================
        // CEK SEMUA SOAL DIJAWAB
        // =============================================
        function checkAllAnswered() {
            let allAnswered = true;
            document.querySelectorAll('.question-item').forEach(item => {
                const radio = item.querySelector('input[type="radio"]:checked');
                if (!radio) allAnswered = false;
            });
            return allAnswered;
        }
        
        // =============================================
        // TIMER
        // =============================================
        <?php 
        $waktu_menit = $current_section['waktu'] > 0 ? $current_section['waktu'] : 30;
        if ($waktu_menit == 0 && isset($toefl['waktu']) && count($sections) > 0) {
            $waktu_menit = floor($toefl['waktu'] / count($sections));
        }
        if ($waktu_menit <= 0) $waktu_menit = 30;
        ?>
        
        let timerInterval = null;
        let timeLeft = <?php echo $waktu_menit * 60; ?>;
        const timerDisplay = document.getElementById('timerDisplay');
        let isTimeUp = false;
        let isNavigating = false;
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (timeLeft <= 300 && timeLeft > 0) {
                timerDisplay.classList.add('warning');
            } else {
                timerDisplay.classList.remove('warning');
            }
        }
        
        function startTimer() {
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(function() {
                if (isNavigating) return;
                timeLeft--;
                updateTimerDisplay();
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerDisplay.textContent = '00:00';
                    isTimeUp = true;
                    isSectionCompleted = true;
                    if (currentSectionIndex < totalSections - 1) {
                        alert('Waktu habis! Anda akan diarahkan ke section berikutnya.');
                        goToSection(currentSectionIndex + 1);
                    } else {
                        if (confirm('Waktu sudah habis! Apakah Anda ingin mengirim jawaban?')) {
                            document.getElementById('formToefl').submit();
                        }
                    }
                }
            }, 1000);
        }
        
        // =============================================
        // NAVIGASI SECTION
        // =============================================
        function goToSection(index) {
            if (index < 0 || index >= totalSections) return;
            if (index === currentSectionIndex) return;
            if (!isTimeUp && !isSectionCompleted) {
                const allAnswered = checkAllAnswered();
                if (!allAnswered) {
                    alert('Anda belum menjawab semua soal di section ini!');
                    return;
                }
                isSectionCompleted = true;
            }
            if (index > currentSectionIndex + 1) {
                alert('Anda belum menyelesaikan section sebelumnya!');
                return;
            }
            if (index < currentSectionIndex) {
                alert('Anda tidak bisa kembali ke section yang sudah selesai!');
                return;
            }
            isNavigating = true;
            clearInterval(timerInterval);
            window.location.href = `toefl.php?id=${toeflId}&section=${sectionIds[index]}`;
        }
        
        // =============================================
        // EVENT LISTENERS
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            updateQuestionStatus();
            updateNavButtons();
            updateFilterCounts();
            updateTimerDisplay();
            startTimer();
            
            const btnNext = document.getElementById('btnNextSection');
            if (btnNext) {
                btnNext.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentSectionIndex < totalSections - 1) {
                        const allAnswered = checkAllAnswered();
                        if (!allAnswered && !isTimeUp) {
                            alert('Anda harus menjawab semua soal sebelum melanjutkan ke section berikutnya!');
                            return;
                        }
                        isSectionCompleted = true;
                        goToSection(currentSectionIndex + 1);
                    }
                });
            }
            
            const btnPrev = document.getElementById('btnPrevSection');
            if (btnPrev) {
                btnPrev.style.display = 'none';
            }
        });
    </script>

</body>
</html>