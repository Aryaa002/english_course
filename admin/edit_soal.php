<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    redirect('soal.php');
}

// Ambil data soal
$stmt = $pdo->prepare("SELECT * FROM soal_latihan WHERE id = ?");
$stmt->execute([$id]);
$soal = $stmt->fetch();

if (!$soal) {
    redirect('soal.php');
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
    
    // Upload file media baru jika ada
    $media_file = $soal['media_file'];
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $allowed_video = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
        $allowed_audio = ['mp3', 'wav', 'm4a', 'ogg', 'wma'];
        
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        
        if ($media_type == 'video' && in_array($ext, $allowed_video)) {
            $media_name = time() . '_video_' . uniqid() . '.' . $ext;
            $media_file = 'uploads/videos/' . $media_name;
            move_uploaded_file($_FILES['media_file']['tmp_name'], "../" . $media_file);
            
            // Hapus file lama jika ada
            if ($soal['media_file'] && file_exists('../' . $soal['media_file'])) {
                unlink('../' . $soal['media_file']);
            }
        } elseif ($media_type == 'audio' && in_array($ext, $allowed_audio)) {
            $media_name = time() . '_audio_' . uniqid() . '.' . $ext;
            $media_file = 'uploads/audios/' . $media_name;
            move_uploaded_file($_FILES['media_file']['tmp_name'], "../" . $media_file);
            
            // Hapus file lama jika ada
            if ($soal['media_file'] && file_exists('../' . $soal['media_file'])) {
                unlink('../' . $soal['media_file']);
            }
        } else {
            $error = 'Format file tidak didukung untuk tipe media yang dipilih!';
        }
    }
    
    if (empty($error)) {
        if (empty($materi_id) || empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($jawaban_benar)) {
            $error = 'Field wajib harus diisi!';
        } else {
            $stmt = $pdo->prepare("UPDATE soal_latihan SET 
                                  materi_id = ?, pertanyaan = ?, pilihan_a = ?, pilihan_b = ?, 
                                  pilihan_c = ?, pilihan_d = ?, jawaban_benar = ?, tingkat_kesulitan = ?,
                                  media_type = ?, media_url = ?, media_file = ?
                                  WHERE id = ?");
            if ($stmt->execute([$materi_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, 
                               $jawaban_benar, $tingkat_kesulitan, $media_type, $media_url, $media_file, $id])) {
                $success = 'Soal berhasil diupdate!';
                echo '<meta http-equiv="refresh" content="2;url=soal.php">';
            } else {
                $error = 'Gagal mengupdate soal!';
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
    
    .option-group {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 3px solid transparent;
    }
    
    .option-group:hover {
        border-left-color: #F4B41A;
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
    
    .current-media {
        background: #e3f2fd;
        padding: 8px 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        color: #1565c0;
    }
    
    .current-media i {
        margin-right: 8px;
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
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-edit" style="color: #F4B41A;"></i> Edit Soal Latihan
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
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="materi_id">Pilih Materi <span style="color: red;">*</span></label>
                        <select class="form-control" id="materi_id" name="materi_id" required>
                            <option value="">-- Pilih Materi --</option>
                            <?php foreach($materi_list as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $soal['materi_id'] == $m['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['judul']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="pertanyaan">Pertanyaan <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="pertanyaan" name="pertanyaan" rows="3" required><?php echo htmlspecialchars($soal['pertanyaan']); ?></textarea>
                    </div>
                    
                    <div class="col-md-12">
                        <label>Pilihan Jawaban <span style="color: red;">*</span></label>
                        
                        <div class="option-group">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <span class="option-letter">A.</span>
                                </div>
                                <div class="col-md-11">
                                    <input type="text" class="form-control" name="pilihan_a" 
                                           value="<?php echo htmlspecialchars($soal['pilihan_a']); ?>" required>
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
                                           value="<?php echo htmlspecialchars($soal['pilihan_b']); ?>" required>
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
                                           value="<?php echo htmlspecialchars($soal['pilihan_c']); ?>">
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
                                           value="<?php echo htmlspecialchars($soal['pilihan_d']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="jawaban_benar">Jawaban Benar <span style="color: red;">*</span></label>
                        <select class="form-control" id="jawaban_benar" name="jawaban_benar" required>
                            <option value="">Pilih Jawaban Benar</option>
                            <option value="A" <?php echo $soal['jawaban_benar'] == 'A' ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo $soal['jawaban_benar'] == 'B' ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo $soal['jawaban_benar'] == 'C' ? 'selected' : ''; ?>>C</option>
                            <option value="D" <?php echo $soal['jawaban_benar'] == 'D' ? 'selected' : ''; ?>>D</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="tingkat_kesulitan">Tingkat Kesulitan</label>
                        <select class="form-control" id="tingkat_kesulitan" name="tingkat_kesulitan">
                            <option value="mudah" <?php echo $soal['tingkat_kesulitan'] == 'mudah' ? 'selected' : ''; ?>>Mudah</option>
                            <option value="sedang" <?php echo $soal['tingkat_kesulitan'] == 'sedang' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="sulit" <?php echo $soal['tingkat_kesulitan'] == 'sulit' ? 'selected' : ''; ?>>Sulit</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="media_type">Tipe Media Pendukung</label>
                        <select class="form-control" id="media_type" name="media_type" onchange="toggleMediaSection()">
                            <option value="none" <?php echo $soal['media_type'] == 'none' ? 'selected' : ''; ?>>Tidak Ada</option>
                            <option value="audio" <?php echo $soal['media_type'] == 'audio' ? 'selected' : ''; ?>>🎧 Audio (Listening)</option>
                            <option value="video" <?php echo $soal['media_type'] == 'video' ? 'selected' : ''; ?>>🎬 Video</option>
                        </select>
                    </div>
                    
                    <!-- Media Upload Section -->
                    <div class="col-md-12 mb-3">
                        <div class="media-section" id="mediaSection">
                            <div class="section-title" style="font-weight: 600; color: #1B2A4A; margin-bottom: 15px;">
                                <i class="fas fa-upload" style="color: #F4B41A;"></i> Upload Media
                            </div>
                            
                            <?php if($soal['media_file'] && file_exists('../' . $soal['media_file'])): ?>
                            <div class="current-media">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                File saat ini: <?php echo basename($soal['media_file']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="upload-box" onclick="document.getElementById('media_file').click()">
                                <i class="fas fa-<?php echo $soal['media_type'] == 'video' ? 'video' : 'music'; ?>" style="color: #F4B41A;"></i>
                                <p><strong>Klik untuk upload <?php echo $soal['media_type'] == 'video' ? 'video' : 'audio'; ?> baru</strong></p>
                                <small id="mediaInfo">Support: <?php echo $soal['media_type'] == 'video' ? 'MP4, AVI, MOV, MKV, WEBM (Max 50MB)' : 'MP3, WAV, M4A, OGG, WMA (Max 20MB)'; ?></small>
                                <input type="file" class="d-none" id="media_file" name="media_file" accept="<?php echo $soal['media_type'] == 'video' ? 'video/*' : 'audio/*'; ?>" onchange="showFileInfo(this)">
                                <div id="media_file_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="file_name"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Atau masukkan URL media</label>
                                <input type="text" class="form-control" id="media_url" name="media_url" 
                                       value="<?php echo htmlspecialchars($soal['media_url'] ?? ''); ?>"
                                       placeholder="https://example.com/media.mp3 atau https://www.youtube.com/watch?v=...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i>Update Soal
                        </button>
                    </div>
                </div>
            </form>
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
        } else {
            infoText.textContent = 'Support: MP3, WAV, M4A, OGG, WMA (Max 20MB)';
            fileInput.accept = 'audio/*';
            document.querySelector('.upload-box i').className = 'fas fa-music';
        }
    }
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

// Inisialisasi
document.addEventListener('DOMContentLoaded', function() {
    toggleMediaSection();
});
</script>

<?php include '../includes/footer.php'; ?>