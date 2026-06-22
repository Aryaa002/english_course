<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Ambil daftar materi
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

// Buat folder uploads jika belum ada
$folders = ['../uploads/', '../uploads/videos/', '../uploads/audios/', '../uploads/images/'];
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $materi_id = (int)$_POST['materi_id'];
    $pertanyaan = trim($_POST['pertanyaan']);
    $pilihan_a = trim($_POST['pilihan_a']);
    $pilihan_b = trim($_POST['pilihan_b']);
    $pilihan_c = trim($_POST['pilihan_c']);
    $pilihan_d = trim($_POST['pilihan_d']);
    $jawaban_benar = $_POST['jawaban_benar'];
    $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
    $media_type = $_POST['media_type'];
    $media_url = trim($_POST['media_url']);
    
    // Upload file media
    $media_file = '';
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $allowed_video = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
        $allowed_audio = ['mp3', 'wav', 'm4a', 'ogg', 'wma'];
        
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        
        if ($media_type == 'video' && in_array($ext, $allowed_video)) {
            $media_name = time() . '_video_' . uniqid() . '.' . $ext;
            $media_file = 'uploads/videos/' . $media_name;
            move_uploaded_file($_FILES['media_file']['tmp_name'], "../" . $media_file);
        } elseif ($media_type == 'audio' && in_array($ext, $allowed_audio)) {
            $media_name = time() . '_audio_' . uniqid() . '.' . $ext;
            $media_file = 'uploads/audios/' . $media_name;
            move_uploaded_file($_FILES['media_file']['tmp_name'], "../" . $media_file);
        } else {
            $error = 'Format file tidak didukung untuk tipe media yang dipilih!';
        }
    }
    
    if (empty($error)) {
        if (empty($materi_id) || empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($jawaban_benar)) {
            $error = 'Field wajib harus diisi!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO soal_latihan (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, tingkat_kesulitan, media_type, media_url, media_file) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$materi_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $jawaban_benar, $tingkat_kesulitan, $media_type, $media_url, $media_file])) {
                $success = 'Soal berhasil ditambahkan!';
                $_POST = array();
                echo '<meta http-equiv="refresh" content="2;url=soal.php">';
            } else {
                $error = 'Gagal menambahkan soal!';
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
    .admin-content {
        padding: 30px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .form-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .form-container label {
        font-weight: 600;
        color: #1B2A4A;
    }
    
    .form-container .form-control {
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        padding: 10px 15px;
    }
    
    .form-container .form-control:focus {
        border-color: #F4B41A;
        box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25);
    }
    
    .form-container textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .option-group {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }
    
    .option-group:hover {
        border-left-color: #F4B41A;
        background: #f0f0f0;
    }
    
    .option-group .option-letter {
        font-weight: 700;
        color: #1B2A4A;
        font-size: 18px;
        display: inline-block;
        width: 30px;
    }
    
    .upload-box {
        border: 2px dashed #d0d0d0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        background: #fafafa;
    }
    
    .upload-box:hover {
        border-color: #F4B41A;
        background: rgba(244, 180, 26, 0.05);
    }
    
    .upload-box i {
        font-size: 40px;
        color: #F4B41A;
        display: block;
        margin-bottom: 10px;
    }
    
    .upload-box small {
        color: #999;
        font-size: 12px;
    }
    
    .file-info {
        background: #e8f5e9;
        border-radius: 8px;
        padding: 10px 15px;
        margin-top: 10px;
        display: none;
        color: #2e7d32;
        font-weight: 500;
    }
    
    .file-info.show {
        display: block;
    }
    
    .media-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #F4B41A;
        display: none;
    }
    
    .media-section.show {
        display: block;
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
        width: 100%;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4);
        color: #1B2A4A;
    }
    
    .btn-back {
        background: #1B2A4A;
        color: white;
        padding: 8px 20px;
        border-radius: 25px;
        border: none;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-back:hover {
        background: #2C4066;
        color: white;
    }
    
    .preview-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        display: none;
    }
    
    .preview-box.show {
        display: block;
        animation: fadeIn 0.5s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .preview-box .question {
        font-weight: 600;
        color: #1B2A4A;
        margin-bottom: 10px;
    }
    
    .preview-box .option {
        padding: 5px 10px;
        margin: 3px 0;
        border-radius: 5px;
    }
    
    .preview-box .option.correct {
        background: #d4edda;
        border-left: 3px solid #28a745;
    }
    
    .badge-correct {
        background: #28a745;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
    }
    
    .badge-difficulty {
        padding: 4px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-mudah {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-sedang {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-sulit {
        background: #f8d7da;
        color: #721c24;
    }
    
    .media-preview {
        background: #1B2A4A;
        border-radius: 10px;
        padding: 15px;
        margin: 10px 0;
        color: white;
    }
    
    .media-preview audio,
    .media-preview video {
        width: 100%;
        border-radius: 8px;
    }
    
    .media-preview audio {
        height: 50px;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Soal Latihan
            </h4>
            <a href="soal.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data" id="formSoal">
                <div class="row">
                    <!-- Pilih Materi -->
                    <div class="col-md-12 mb-3">
                        <label for="materi_id">Pilih Materi <span style="color: red;">*</span></label>
                        <select class="form-control" id="materi_id" name="materi_id" required>
                            <option value="">-- Pilih Materi --</option>
                            <?php foreach($materi_list as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo (isset($_POST['materi_id']) && $_POST['materi_id'] == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['judul']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Pertanyaan -->
                    <div class="col-md-12 mb-3">
                        <label for="pertanyaan">Pertanyaan <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="pertanyaan" name="pertanyaan" rows="3" 
                                  placeholder="Masukkan pertanyaan..." required
                                  onkeyup="previewQuestion()"><?php echo isset($_POST['pertanyaan']) ? htmlspecialchars($_POST['pertanyaan']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Pilihan Jawaban -->
                    <div class="col-md-12">
                        <label>Pilihan Jawaban <span style="color: red;">*</span></label>
                        
                        <div class="option-group">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <span class="option-letter">A.</span>
                                </div>
                                <div class="col-md-11">
                                    <input type="text" class="form-control" name="pilihan_a" 
                                           value="<?php echo isset($_POST['pilihan_a']) ? htmlspecialchars($_POST['pilihan_a']) : ''; ?>"
                                           placeholder="Masukkan pilihan A" required
                                           onkeyup="previewQuestion()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="option-group">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <span class="option-letter">B.</span>
                                </div>
                                <div class="col-md-11">
                                    <input type="text" class="form-control" name="pilihan_b" 
                                           value="<?php echo isset($_POST['pilihan_b']) ? htmlspecialchars($_POST['pilihan_b']) : ''; ?>"
                                           placeholder="Masukkan pilihan B" required
                                           onkeyup="previewQuestion()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="option-group">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <span class="option-letter">C.</span>
                                </div>
                                <div class="col-md-11">
                                    <input type="text" class="form-control" name="pilihan_c" 
                                           value="<?php echo isset($_POST['pilihan_c']) ? htmlspecialchars($_POST['pilihan_c']) : ''; ?>"
                                           placeholder="Masukkan pilihan C (opsional)"
                                           onkeyup="previewQuestion()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="option-group">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <span class="option-letter">D.</span>
                                </div>
                                <div class="col-md-11">
                                    <input type="text" class="form-control" name="pilihan_d" 
                                           value="<?php echo isset($_POST['pilihan_d']) ? htmlspecialchars($_POST['pilihan_d']) : ''; ?>"
                                           placeholder="Masukkan pilihan D (opsional)"
                                           onkeyup="previewQuestion()">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jawaban Benar & Tingkat Kesulitan -->
                    <div class="col-md-4 mb-3">
                        <label for="jawaban_benar">Jawaban Benar <span style="color: red;">*</span></label>
                        <select class="form-control" id="jawaban_benar" name="jawaban_benar" required onchange="previewQuestion()">
                            <option value="">Pilih Jawaban Benar</option>
                            <option value="A" <?php echo (isset($_POST['jawaban_benar']) && $_POST['jawaban_benar'] == 'A') ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo (isset($_POST['jawaban_benar']) && $_POST['jawaban_benar'] == 'B') ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo (isset($_POST['jawaban_benar']) && $_POST['jawaban_benar'] == 'C') ? 'selected' : ''; ?>>C</option>
                            <option value="D" <?php echo (isset($_POST['jawaban_benar']) && $_POST['jawaban_benar'] == 'D') ? 'selected' : ''; ?>>D</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="tingkat_kesulitan">Tingkat Kesulitan</label>
                        <select class="form-control" id="tingkat_kesulitan" name="tingkat_kesulitan" onchange="previewQuestion()">
                            <option value="mudah" <?php echo (isset($_POST['tingkat_kesulitan']) && $_POST['tingkat_kesulitan'] == 'mudah') ? 'selected' : ''; ?>>Mudah</option>
                            <option value="sedang" <?php echo (isset($_POST['tingkat_kesulitan']) && $_POST['tingkat_kesulitan'] == 'sedang') ? 'selected' : ''; ?>>Sedang</option>
                            <option value="sulit" <?php echo (isset($_POST['tingkat_kesulitan']) && $_POST['tingkat_kesulitan'] == 'sulit') ? 'selected' : ''; ?>>Sulit</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="media_type">Tipe Media Pendukung</label>
                        <select class="form-control" id="media_type" name="media_type" onchange="toggleMediaSection()">
                            <option value="none">Tidak Ada</option>
                            <option value="audio" <?php echo (isset($_POST['media_type']) && $_POST['media_type'] == 'audio') ? 'selected' : ''; ?>>🎧 Audio (Listening)</option>
                            <option value="video" <?php echo (isset($_POST['media_type']) && $_POST['media_type'] == 'video') ? 'selected' : ''; ?>>🎬 Video</option>
                        </select>
                    </div>
                    
                    <!-- Media Upload Section -->
                    <div class="col-md-12 mb-3">
                        <div class="media-section" id="mediaSection">
                            <div class="section-title" style="font-weight: 600; color: #1B2A4A; margin-bottom: 15px;">
                                <i class="fas fa-upload" style="color: #F4B41A;"></i> Upload Media
                            </div>
                            <div class="upload-box" onclick="document.getElementById('media_file').click()">
                                <i class="fas fa-<?php echo (isset($_POST['media_type']) && $_POST['media_type'] == 'video') ? 'video' : 'music'; ?>" style="color: #F4B41A;"></i>
                                <p><strong>Klik untuk upload <?php echo (isset($_POST['media_type']) && $_POST['media_type'] == 'video') ? 'video' : 'audio'; ?></strong></p>
                                <small id="mediaInfo">Support: <?php echo (isset($_POST['media_type']) && $_POST['media_type'] == 'video') ? 'MP4, AVI, MOV, MKV, WEBM (Max 50MB)' : 'MP3, WAV, M4A, OGG, WMA (Max 20MB)'; ?></small>
                                <input type="file" class="d-none" id="media_file" name="media_file" accept="<?php echo (isset($_POST['media_type']) && $_POST['media_type'] == 'video') ? 'video/*' : 'audio/*'; ?>" onchange="showFileInfo(this)">
                                <div id="media_file_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="file_name"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Atau masukkan URL media</label>
                                <input type="text" class="form-control" id="media_url" name="media_url" 
                                       value="<?php echo isset($_POST['media_url']) ? htmlspecialchars($_POST['media_url']) : ''; ?>"
                                       placeholder="https://example.com/media.mp3 atau https://www.youtube.com/watch?v=...">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tombol Submit -->
                    <div class="col-md-12">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i>Simpan Soal
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Preview Soal -->
            <div class="preview-box" id="previewBox">
                <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-eye" style="color: #F4B41A;"></i> Preview Soal
                </h6>
                <div id="previewContent">
                    <div class="question" id="previewQuestion">Pertanyaan akan muncul di sini</div>
                    <div class="option" id="previewA">A. Pilihan A</div>
                    <div class="option" id="previewB">B. Pilihan B</div>
                    <div class="option" id="previewC">C. Pilihan C</div>
                    <div class="option" id="previewD">D. Pilihan D</div>
                    <div class="mt-2">
                        <span class="badge-correct" id="previewAnswer">Jawaban: A</span>
                        <span class="badge-difficulty badge-mudah" id="previewDifficulty">Mudah</span>
                        <span class="badge-difficulty" id="previewMedia" style="background: #17a2b8; color: white;">Tidak Ada Media</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMediaSection() {
    var mediaType = document.getElementById('media_type').value;
    var section = document.getElementById('mediaSection');
    var fileInput = document.getElementById('media_file');
    var infoText = document.getElementById('mediaInfo');
    
    if (mediaType == 'none') {
        section.classList.remove('show');
        fileInput.removeAttribute('required');
    } else {
        section.classList.add('show');
        fileInput.setAttribute('required', 'required');
        
        if (mediaType == 'video') {
            infoText.textContent = 'Support: MP4, AVI, MOV, MKV, WEBM (Max 50MB)';
            fileInput.accept = 'video/*';
            document.querySelector('.upload-box i').className = 'fas fa-video';
            document.querySelector('.upload-box i').style.color = '#F4B41A';
        } else {
            infoText.textContent = 'Support: MP3, WAV, M4A, OGG, WMA (Max 20MB)';
            fileInput.accept = 'audio/*';
            document.querySelector('.upload-box i').className = 'fas fa-music';
            document.querySelector('.upload-box i').style.color = '#F4B41A';
        }
    }
    
    previewQuestion();
}

function showFileInfo(input) {
    var file = input.files[0];
    var info = document.getElementById('media_file_info');
    var nameSpan = document.getElementById('file_name');
    
    if (file) {
        var fileSize = (file.size / (1024 * 1024)).toFixed(2);
        nameSpan.textContent = file.name + ' (' + fileSize + ' MB)';
        info.classList.add('show');
    } else {
        info.classList.remove('show');
    }
}

function previewQuestion() {
    var pertanyaan = document.getElementById('pertanyaan').value;
    var pilihanA = document.querySelector('input[name="pilihan_a"]').value;
    var pilihanB = document.querySelector('input[name="pilihan_b"]').value;
    var pilihanC = document.querySelector('input[name="pilihan_c"]').value;
    var pilihanD = document.querySelector('input[name="pilihan_d"]').value;
    var jawaban = document.getElementById('jawaban_benar').value;
    var tingkat = document.getElementById('tingkat_kesulitan').value;
    var mediaType = document.getElementById('media_type').value;
    
    var previewBox = document.getElementById('previewBox');
    var hasContent = pertanyaan || pilihanA || pilihanB || pilihanC || pilihanD || jawaban;
    
    if (hasContent) {
        previewBox.classList.add('show');
        
        document.getElementById('previewQuestion').textContent = pertanyaan || 'Pertanyaan akan muncul di sini';
        document.getElementById('previewA').textContent = 'A. ' + (pilihanA || 'Pilihan A');
        document.getElementById('previewB').textContent = 'B. ' + (pilihanB || 'Pilihan B');
        document.getElementById('previewC').textContent = 'C. ' + (pilihanC || 'Pilihan C');
        document.getElementById('previewD').textContent = 'D. ' + (pilihanD || 'Pilihan D');
        
        // Tandai jawaban benar
        document.querySelectorAll('#previewContent .option').forEach(el => {
            el.classList.remove('correct');
        });
        
        if (jawaban) {
            var answerMap = {'A': 'previewA', 'B': 'previewB', 'C': 'previewC', 'D': 'previewD'};
            if (answerMap[jawaban]) {
                document.getElementById(answerMap[jawaban]).classList.add('correct');
            }
            document.getElementById('previewAnswer').textContent = 'Jawaban: ' + jawaban;
        } else {
            document.getElementById('previewAnswer').textContent = 'Jawaban: Belum dipilih';
        }
        
        // Update tingkat kesulitan
        var diffEl = document.getElementById('previewDifficulty');
        diffEl.textContent = tingkat.charAt(0).toUpperCase() + tingkat.slice(1);
        diffEl.className = 'badge-difficulty badge-' + tingkat;
        
        // Update media info
        var mediaEl = document.getElementById('previewMedia');
        if (mediaType == 'video') {
            mediaEl.textContent = '🎬 Dengan Video';
            mediaEl.style.background = '#17a2b8';
            mediaEl.style.color = 'white';
        } else if (mediaType == 'audio') {
            mediaEl.textContent = '🎧 Dengan Audio';
            mediaEl.style.background = '#17a2b8';
            mediaEl.style.color = 'white';
        } else {
            mediaEl.textContent = '📝 Tanpa Media';
            mediaEl.style.background = '#6c757d';
            mediaEl.style.color = 'white';
        }
    } else {
        previewBox.classList.remove('show');
    }
}

function validateForm() {
    var pertanyaan = document.getElementById('pertanyaan').value.trim();
    var pilihanA = document.querySelector('input[name="pilihan_a"]').value.trim();
    var pilihanB = document.querySelector('input[name="pilihan_b"]').value.trim();
    var jawaban = document.getElementById('jawaban_benar').value;
    var materi = document.getElementById('materi_id').value;
    var mediaType = document.getElementById('media_type').value;
    var mediaFile = document.getElementById('media_file').files[0];
    var mediaUrl = document.getElementById('media_url').value.trim();
    
    if (!materi) {
        alert('Silakan pilih materi terlebih dahulu!');
        document.getElementById('materi_id').focus();
        return false;
    }
    
    if (!pertanyaan) {
        alert('Silakan masukkan pertanyaan!');
        document.getElementById('pertanyaan').focus();
        return false;
    }
    
    if (!pilihanA || !pilihanB) {
        alert('Pilihan A dan B wajib diisi!');
        return false;
    }
    
    if (!jawaban) {
        alert('Silakan pilih jawaban yang benar!');
        document.getElementById('jawaban_benar').focus();
        return false;
    }
    
    if (mediaType != 'none') {
        if (!mediaFile && !mediaUrl) {
            alert('Silakan upload file atau masukkan URL media!');
            return false;
        }
    }
    
    return true;
}

// Inisialisasi
document.addEventListener('DOMContentLoaded', function() {
    toggleMediaSection();
    previewQuestion();
});
</script>

<?php include '../includes/footer.php'; ?>