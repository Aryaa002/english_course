<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    redirect('materi.php');
}

// Ambil data materi
$stmt = $pdo->prepare("SELECT * FROM materi WHERE id = ?");
$stmt->execute([$id]);
$materi = $stmt->fetch();

if (!$materi) {
    redirect('materi.php');
}

// Statistik untuk sidebar
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

$error = '';
$success = '';


// Buat folder uploads jika belum ada
$folders = ['../uploads/', '../uploads/videos/', '../uploads/audios/', '../uploads/images/'];
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $konten = trim($_POST['konten']);
    $kategori = trim($_POST['kategori']);
    $tingkat = trim($_POST['tingkat']);
    $durasi = trim($_POST['durasi']);
    $tipe_materi = $_POST['tipe_materi'];
    $video_url = trim($_POST['video_url']);
    $audio_url = trim($_POST['audio_url']);
    
    if (empty($judul) || empty($deskripsi) || empty($kategori)) {
        $error = 'Field wajib harus diisi!';
    } else {
        // Upload video baru jika ada
        $video_path = $materi['video_url'];
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $allowed = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $video_name = time() . '_video_' . uniqid() . '.' . $ext;
                $video_path = 'uploads/videos/' . $video_name;
                move_uploaded_file($_FILES['video_file']['tmp_name'], "../" . $video_path);
            }
        }
        
        // Upload audio baru jika ada
        $audio_path = $materi['audio_url'];
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
            $allowed = ['mp3', 'wav', 'm4a', 'ogg', 'wma'];
            $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $audio_name = time() . '_audio_' . uniqid() . '.' . $ext;
                $audio_path = 'uploads/audios/' . $audio_name;
                move_uploaded_file($_FILES['audio_file']['tmp_name'], "../" . $audio_path);
            }
        }
        
        $video_final = !empty($video_url) ? $video_url : $video_path;
        $audio_final = !empty($audio_url) ? $audio_url : $audio_path;
        
        $stmt = $pdo->prepare("UPDATE materi SET 
                              judul = ?, deskripsi = ?, konten = ?, kategori = ?, 
                              tingkat = ?, durasi = ?, tipe_materi = ?, 
                              video_url = ?, audio_url = ? 
                              WHERE id = ?");
        
        if ($stmt->execute([$judul, $deskripsi, $konten, $kategori, $tingkat, $durasi, 
                           $tipe_materi, $video_final, $audio_final, $id])) {
            $success = 'Materi berhasil diupdate!';
            echo '<meta http-equiv="refresh" content="2;url=materi.php">';
        } else {
            $error = 'Gagal mengupdate materi!';
        }
    }
}

include '../includes/header.php';
?>

<style>
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
    
    .upload-box {
        border: 2px dashed #d0d0d0;
        border-radius: 12px;
        padding: 25px 20px;
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
    
    .current-file {
        background: #e3f2fd;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 13px;
        color: #1565c0;
        display: inline-block;
        margin-top: 5px;
    }
    
    .media-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #F4B41A;
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
    }
    
    .btn-back:hover {
        background: #2C4066;
        color: white;
    }
</style>

<div class="admin-content" style="padding: 30px 0; background: #f8f9fa; min-height: 100vh;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-edit" style="color: #F4B41A;"></i> Edit Materi
            </h4>
            <a href="materi.php" class="btn-back">
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
                        <label for="judul">Judul Materi <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" 
                               value="<?php echo htmlspecialchars($materi['judul']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="tipe_materi">Tipe Materi <span style="color: red;">*</span></label>
                        <select class="form-control" id="tipe_materi" name="tipe_materi" required onchange="toggleMediaFields()">
                            <option value="teks" <?php echo $materi['tipe_materi'] == 'teks' ? 'selected' : ''; ?>>📖 Teks / Bacaan</option>
                            <option value="video" <?php echo $materi['tipe_materi'] == 'video' ? 'selected' : ''; ?>>🎬 Video</option>
                            <option value="audio" <?php echo $materi['tipe_materi'] == 'audio' ? 'selected' : ''; ?>>🎧 Audio (Listening)</option>
                            <option value="interaktif" <?php echo $materi['tipe_materi'] == 'interaktif' ? 'selected' : ''; ?>>🔄 Interaktif</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="kategori">Kategori <span style="color: red;">*</span></label>
                        <select class="form-control" id="kategori" name="kategori" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Grammar" <?php echo $materi['kategori'] == 'Grammar' ? 'selected' : ''; ?>>Grammar</option>
                            <option value="Vocabulary" <?php echo $materi['kategori'] == 'Vocabulary' ? 'selected' : ''; ?>>Vocabulary</option>
                            <option value="Speaking" <?php echo $materi['kategori'] == 'Speaking' ? 'selected' : ''; ?>>Speaking</option>
                            <option value="Reading" <?php echo $materi['kategori'] == 'Reading' ? 'selected' : ''; ?>>Reading</option>
                            <option value="Writing" <?php echo $materi['kategori'] == 'Writing' ? 'selected' : ''; ?>>Writing</option>
                            <option value="Listening" <?php echo $materi['kategori'] == 'Listening' ? 'selected' : ''; ?>>Listening</option>
                            <option value="Business" <?php echo $materi['kategori'] == 'Business' ? 'selected' : ''; ?>>Business</option>
                            <option value="Academic" <?php echo $materi['kategori'] == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                            <option value="Test Preparation" <?php echo $materi['kategori'] == 'Test Preparation' ? 'selected' : ''; ?>>Test Preparation</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="tingkat">Tingkat</label>
                        <select class="form-control" id="tingkat" name="tingkat">
                            <option value="Beginner" <?php echo $materi['tingkat'] == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo $materi['tingkat'] == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo $materi['tingkat'] == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="durasi">Durasi</label>
                        <input type="text" class="form-control" id="durasi" name="durasi" 
                               value="<?php echo htmlspecialchars($materi['durasi']); ?>">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="deskripsi">Deskripsi <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required><?php echo htmlspecialchars($materi['deskripsi']); ?></textarea>
                    </div>
                    
                    <!-- Video Section -->
                    <div class="col-md-12 mb-3" id="video_section" style="display: none;">
                        <div class="media-section">
                            <div class="section-title">
                                <i class="fas fa-video"></i> Upload Video
                            </div>
                            <?php if(!empty($materi['video_url']) && file_exists('../' . $materi['video_url'])): ?>
                                <div class="current-file">
                                    <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                    Video saat ini: <?php echo basename($materi['video_url']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="upload-box" onclick="document.getElementById('video_file').click()">
                                <i class="fas fa-video" style="color: #F4B41A;"></i>
                                <p><strong>Klik untuk ganti video</strong></p>
                                <small>Support: MP4, AVI, MOV, MKV, WEBM (Max 100MB)</small>
                                <input type="file" class="d-none" id="video_file" name="video_file" accept="video/*" onchange="showFileInfo(this, 'video_info')">
                                <div id="video_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="video_name"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Atau masukkan URL video (YouTube/Vimeo)</label>
                                <input type="text" class="form-control" id="video_url" name="video_url" 
                                       value="<?php echo htmlspecialchars($materi['video_url'] ?? ''); ?>"
                                       placeholder="https://www.youtube.com/watch?v=...">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Audio Section -->
                    <div class="col-md-12 mb-3" id="audio_section" style="display: none;">
                        <div class="media-section">
                            <div class="section-title">
                                <i class="fas fa-music"></i> Upload Audio (Listening)
                            </div>
                            <?php if(!empty($materi['audio_url']) && file_exists('../' . $materi['audio_url'])): ?>
                                <div class="current-file">
                                    <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                    Audio saat ini: <?php echo basename($materi['audio_url']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="upload-box" onclick="document.getElementById('audio_file').click()">
                                <i class="fas fa-music" style="color: #F4B41A;"></i>
                                <p><strong>Klik untuk ganti audio</strong></p>
                                <small>Support: MP3, WAV, M4A, OGG, WMA (Max 50MB)</small>
                                <input type="file" class="d-none" id="audio_file" name="audio_file" accept="audio/*" onchange="showFileInfo(this, 'audio_info')">
                                <div id="audio_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="audio_name"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Atau masukkan URL audio</label>
                                <input type="text" class="form-control" id="audio_url" name="audio_url" 
                                       value="<?php echo htmlspecialchars($materi['audio_url'] ?? ''); ?>"
                                       placeholder="https://example.com/audio.mp3">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Konten -->
                    <div class="col-md-12 mb-3" id="konten_section">
                        <label for="konten">Konten Materi</label>
                        <textarea class="form-control" id="konten" name="konten" rows="10"><?php echo htmlspecialchars($materi['konten']); ?></textarea>
                        <small class="text-muted">💡 Gunakan tag HTML untuk format teks (misal: &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, dll)</small>
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i>Update Materi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMediaFields() {
    var tipe = document.getElementById('tipe_materi').value;
    document.getElementById('video_section').style.display = tipe == 'video' ? 'block' : 'none';
    document.getElementById('audio_section').style.display = tipe == 'audio' ? 'block' : 'none';
    document.getElementById('konten_section').style.display = (tipe == 'teks' || tipe == 'interaktif') ? 'block' : 'block';
}

function showFileInfo(input, infoId) {
    var file = input.files[0];
    var info = document.getElementById(infoId);
    var nameSpan = info.querySelector('span');
    
    if (file) {
        var fileSize = (file.size / (1024 * 1024)).toFixed(2);
        nameSpan.textContent = file.name + ' (' + fileSize + ' MB)';
        info.classList.add('show');
    }
}

// Inisialisasi
toggleMediaFields();
</script>

<?php include '../includes/footer.php'; ?>