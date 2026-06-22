<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

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
    
    // Validasi dasar
    if (empty($judul) || empty($deskripsi) || empty($kategori)) {
        $error = 'Field wajib (Judul, Deskripsi, Kategori) harus diisi!';
    } else {
        // Upload file video
        $video_path = '';
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $allowed = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $video_name = time() . '_video_' . uniqid() . '.' . $ext;
                $video_path = 'uploads/videos/' . $video_name;
                move_uploaded_file($_FILES['video_file']['tmp_name'], "../" . $video_path);
            } else {
                $error = 'Format video tidak didukung. Gunakan: MP4, AVI, MOV, MKV, WEBM';
            }
        }
        
        // Upload file audio
        $audio_path = '';
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
            $allowed = ['mp3', 'wav', 'm4a', 'ogg', 'wma'];
            $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $audio_name = time() . '_audio_' . uniqid() . '.' . $ext;
                $audio_path = 'uploads/audios/' . $audio_name;
                move_uploaded_file($_FILES['audio_file']['tmp_name'], "../" . $audio_path);
            } else {
                $error = 'Format audio tidak didukung. Gunakan: MP3, WAV, M4A, OGG, WMA';
            }
        }
        
        // Jika tidak ada error, simpan ke database
        if (empty($error)) {
            $video_final = !empty($video_url) ? $video_url : $video_path;
            $audio_final = !empty($audio_url) ? $audio_url : $audio_path;
            
            $stmt = $pdo->prepare("INSERT INTO materi (judul, deskripsi, konten, kategori, tingkat, durasi, tipe_materi, video_url, audio_url) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$judul, $deskripsi, $konten, $kategori, $tingkat, $durasi, $tipe_materi, $video_final, $audio_final])) {
                $success = 'Materi berhasil ditambahkan!';
                echo '<meta http-equiv="refresh" content="2;url=materi.php">';
            } else {
                $error = 'Gagal menambahkan materi!';
            }
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
    
    .form-container textarea {
        min-height: 150px;
        resize: vertical;
    }
    
    .upload-box {
        border: 2px dashed #d0d0d0;
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        background: #fafafa;
    }
    
    .upload-box:hover {
        border-color: #F4B41A;
        background: rgba(244, 180, 26, 0.05);
        transform: translateY(-2px);
    }
    
    .upload-box i {
        font-size: 50px;
        color: #1B2A4A;
        display: block;
        margin-bottom: 10px;
    }
    
    .upload-box .upload-icon {
        color: #F4B41A;
    }
    
    .upload-box p {
        margin: 5px 0;
        color: #666;
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
    
    .file-info i {
        margin-right: 8px;
    }
    
    .media-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #F4B41A;
    }
    
    .media-section .section-title {
        font-weight: 600;
        color: #1B2A4A;
        margin-bottom: 15px;
    }
    
    .media-section .section-title i {
        color: #F4B41A;
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
    }
    
    .btn-back:hover {
        background: #2C4066;
        color: white;
    }
    
    .preview-file {
        display: inline-block;
        background: #e3f2fd;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 13px;
        color: #1565c0;
        margin-top: 5px;
    }
</style>

<div class="admin-content" style="padding: 30px 0; background: #f8f9fa; min-height: 100vh;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Materi Baru
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
                    <!-- Informasi Dasar -->
                    <div class="col-md-12 mb-3">
                        <label for="judul">Judul Materi <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" 
                               placeholder="Masukkan judul materi" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="tipe_materi">Tipe Materi <span style="color: red;">*</span></label>
                        <select class="form-control" id="tipe_materi" name="tipe_materi" required onchange="toggleMediaFields()">
                            <option value="teks">📖 Teks / Bacaan</option>
                            <option value="video">🎬 Video</option>
                            <option value="audio">🎧 Audio (Listening)</option>
                            <option value="interaktif">🔄 Interaktif</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="kategori">Kategori <span style="color: red;">*</span></label>
                        <select class="form-control" id="kategori" name="kategori" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Grammar">Grammar</option>
                            <option value="Vocabulary">Vocabulary</option>
                            <option value="Speaking">Speaking</option>
                            <option value="Reading">Reading</option>
                            <option value="Writing">Writing</option>
                            <option value="Listening">Listening</option>
                            <option value="Business">Business</option>
                            <option value="Academic">Academic</option>
                            <option value="Test Preparation">Test Preparation</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="tingkat">Tingkat</label>
                        <select class="form-control" id="tingkat" name="tingkat">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="durasi">Durasi</label>
                        <input type="text" class="form-control" id="durasi" name="durasi" 
                               placeholder="Contoh: 10 Jam">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="deskripsi">Deskripsi <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" 
                                  placeholder="Masukkan deskripsi materi" required></textarea>
                    </div>
                    
                    <!-- Media Upload untuk Video -->
                    <div class="col-md-12 mb-3" id="video_section" style="display: none;">
                        <div class="media-section">
                            <div class="section-title">
                                <i class="fas fa-video"></i> Upload Video
                            </div>
                            <div class="upload-box" onclick="document.getElementById('video_file').click()">
                                <i class="fas fa-video upload-icon"></i>
                                <p><strong>Klik untuk upload video</strong></p>
                                <small>Support: MP4, AVI, MOV, MKV, WEBM (Max 100MB)</small>
                                <input type="file" class="d-none" id="video_file" name="video_file" accept="video/*" onchange="showFileInfo(this, 'video_info')">
                                <div id="video_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="video_name"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Atau masukkan URL video (YouTube/Vimeo)</label>
                                <input type="text" class="form-control" id="video_url" name="video_url" 
                                       placeholder="https://www.youtube.com/watch?v=...">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Media Upload untuk Audio -->
                    <div class="col-md-12 mb-3" id="audio_section" style="display: none;">
                        <div class="media-section">
                            <div class="section-title">
                                <i class="fas fa-music"></i> Upload Audio (Listening)
                            </div>
                            <div class="upload-box" onclick="document.getElementById('audio_file').click()">
                                <i class="fas fa-music upload-icon"></i>
                                <p><strong>Klik untuk upload audio</strong></p>
                                <small>Support: MP3, WAV, M4A, OGG, WMA (Max 50MB)</small>
                                <input type="file" class="d-none" id="audio_file" name="audio_file" accept="audio/*" onchange="showFileInfo(this, 'audio_info')">
                                <div id="audio_info" class="file-info">
                                    <i class="fas fa-check-circle"></i> <span id="audio_name"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Atau masukkan URL audio</label>
                                <input type="text" class="form-control" id="audio_url" name="audio_url" 
                                       placeholder="https://example.com/audio.mp3">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Konten Teks -->
                    <div class="col-md-12 mb-3" id="konten_section">
                        <label for="konten">Konten Materi</label>
                        <textarea class="form-control" id="konten" name="konten" rows="10" 
                                  placeholder="Masukkan konten materi (gunakan tag HTML untuk format)"></textarea>
                        <small class="text-muted">💡 Gunakan tag HTML untuk format teks (misal: &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, dll)</small>
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i>Simpan Materi
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
    var videoSection = document.getElementById('video_section');
    var audioSection = document.getElementById('audio_section');
    var kontenSection = document.getElementById('konten_section');
    
    // Sembunyikan semua
    videoSection.style.display = 'none';
    audioSection.style.display = 'none';
    kontenSection.style.display = 'none';
    
    // Tampilkan sesuai tipe
    if (tipe == 'video') {
        videoSection.style.display = 'block';
        kontenSection.style.display = 'block';
    } else if (tipe == 'audio') {
        audioSection.style.display = 'block';
        kontenSection.style.display = 'block';
    } else if (tipe == 'teks' || tipe == 'interaktif') {
        kontenSection.style.display = 'block';
    }
}

function showFileInfo(input, infoId) {
    var file = input.files[0];
    var info = document.getElementById(infoId);
    var nameSpan = info.querySelector('span');
    
    if (file) {
        var fileSize = (file.size / (1024 * 1024)).toFixed(2);
        nameSpan.textContent = file.name + ' (' + fileSize + ' MB)';
        info.classList.add('show');
    } else {
        info.classList.remove('show');
    }
}

// Drag and drop visual feedback
document.querySelectorAll('.upload-box').forEach(box => {
    box.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#F4B41A';
        this.style.background = 'rgba(244, 180, 26, 0.1)';
    });
    
    box.addEventListener('dragleave', function(e) {
        this.style.borderColor = '#d0d0d0';
        this.style.background = '#fafafa';
    });
    
    box.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d0d0d0';
        this.style.background = '#fafafa';
    });
});

// Inisialisasi
toggleMediaFields();
</script>

<?php include '../includes/footer.php'; ?>