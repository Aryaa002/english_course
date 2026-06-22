<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$kuis_id = isset($_GET['kuis_id']) ? (int)$_GET['kuis_id'] : 0;
if ($kuis_id == 0) {
    redirect('kuis.php');
}

// Ambil data kuis
$stmt = $pdo->prepare("SELECT * FROM kuis WHERE id = ?");
$stmt->execute([$kuis_id]);
$kuis = $stmt->fetch();

if (!$kuis) {
    redirect('kuis.php');
}

// Ambil soal kuis
$stmt = $pdo->prepare("SELECT * FROM pertanyaan_kuis WHERE kuis_id = ? ORDER BY id");
$stmt->execute([$kuis_id]);
$soal_list = $stmt->fetchAll();

$error = '';
$success = '';

// Proses tambah soal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_soal'])) {
    $pertanyaan = trim($_POST['pertanyaan']);
    $pilihan_a = trim($_POST['pilihan_a']);
    $pilihan_b = trim($_POST['pilihan_b']);
    $pilihan_c = trim($_POST['pilihan_c']);
    $pilihan_d = trim($_POST['pilihan_d']);
    $jawaban_benar = $_POST['jawaban_benar'];
    $poin = (int)$_POST['poin'];
    
    if (empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($jawaban_benar)) {
        $error = 'Field wajib harus diisi!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO pertanyaan_kuis (kuis_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, poin) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$kuis_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $jawaban_benar, $poin])) {
            $success = 'Soal berhasil ditambahkan!';
            // Update jumlah soal di kuis
            $pdo->prepare("UPDATE kuis SET jumlah_soal = (SELECT COUNT(*) FROM pertanyaan_kuis WHERE kuis_id = ?) WHERE id = ?")
                ->execute([$kuis_id, $kuis_id]);
            echo '<meta http-equiv="refresh" content="1">';
        } else {
            $error = 'Gagal menambahkan soal!';
        }
    }
}

// Proses hapus soal
if (isset($_GET['hapus_soal'])) {
    $soal_id = (int)$_GET['hapus_soal'];
    $stmt = $pdo->prepare("DELETE FROM pertanyaan_kuis WHERE id = ? AND kuis_id = ?");
    $stmt->execute([$soal_id, $kuis_id]);
    // Update jumlah soal
    $pdo->prepare("UPDATE kuis SET jumlah_soal = (SELECT COUNT(*) FROM pertanyaan_kuis WHERE kuis_id = ?) WHERE id = ?")
        ->execute([$kuis_id, $kuis_id]);
    redirect('soal_kuis.php?kuis_id=' . $kuis_id);
}

include '../includes/header.php';
?>

<style>
    .admin-content {
        padding: 30px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .soal-item {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border-left: 3px solid #F4B41A;
    }
    
    .soal-item .nomor {
        font-weight: 700;
        color: #1B2A4A;
        margin-right: 10px;
    }
    
    .soal-item .opsi {
        margin: 5px 0;
        padding: 5px 10px;
        border-radius: 5px;
    }
    
    .soal-item .opsi.benar {
        background: #d4edda;
        border-left: 3px solid #28a745;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="color: #1B2A4A; font-weight: 700;">
                    <i class="fas fa-question-circle" style="color: #F4B41A;"></i> 
                    Soal Kuis: <?php echo htmlspecialchars($kuis['judul']); ?>
                </h4>
                <p style="color: #666; margin: 0;">Total Soal: <?php echo count($soal_list); ?></p>
            </div>
            <a href="kuis.php" class="btn" style="background: #1B2A4A; color: white; padding: 8px 20px; border-radius: 25px;">
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
        
        <!-- Form Tambah Soal -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-header" style="background: #1B2A4A; color: white; border-radius: 15px 15px 0 0; font-weight: 600;">
                <i class="fas fa-plus-circle me-2"></i>Tambah Soal
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label>Pertanyaan *</label>
                            <textarea class="form-control" name="pertanyaan" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>A. *</label>
                            <input type="text" class="form-control" name="pilihan_a" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>B. *</label>
                            <input type="text" class="form-control" name="pilihan_b" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>C.</label>
                            <input type="text" class="form-control" name="pilihan_c">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>D.</label>
                            <input type="text" class="form-control" name="pilihan_d">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Jawaban Benar *</label>
                            <select class="form-control" name="jawaban_benar" required>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Poin</label>
                            <input type="number" class="form-control" name="poin" value="1" min="1">
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="tambah_soal" class="btn" style="background: #F4B41A; color: #1B2A4A; font-weight: 600;">
                                <i class="fas fa-save me-2"></i>Tambah Soal
                            </button>
                        </div>
                        // Di bagian form tambah soal, tambahkan:
                        <div class="row">
                            <!-- ... field sebelumnya ... -->
                            <div class="col-md-12 mb-3">
                                <label for="media_type">Tipe Media Pendukung</label>
                                <select class="form-control" id="media_type" name="media_type" onchange="toggleMediaSection()">
                                    <option value="none">Tidak Ada</option>
                                    <option value="audio">🎧 Audio (Listening)</option>
                                    <option value="video">🎬 Video</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3" id="mediaSection" style="display: none;">
                                <div class="media-section">
                                    <div class="upload-box" onclick="document.getElementById('media_file').click()">
                                        <i class="fas fa-upload" style="color: #F4B41A;"></i>
                                        <p><strong>Klik untuk upload media</strong></p>
                                        <small id="mediaInfo">Support: MP3, WAV, M4A (Audio) atau MP4, WebM (Video)</small>
                                        <input type="file" class="d-none" id="media_file" name="media_file" accept="audio/*,video/*" onchange="showFileInfo(this)">
                                        <div id="media_file_info" class="file-info">
                                            <i class="fas fa-check-circle"></i> <span id="file_name"></span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label>Atau masukkan URL media</label>
                                        <input type="text" class="form-control" id="media_url" name="media_url" placeholder="https://example.com/media.mp3">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                        function toggleMediaSection() {
                            var mediaType = document.getElementById('media_type').value;
                            var section = document.getElementById('mediaSection');
                            var fileInput = document.getElementById('media_file');
                            
                            if (mediaType == 'none') {
                                section.style.display = 'none';
                                fileInput.removeAttribute('required');
                            } else {
                                section.style.display = 'block';
                                fileInput.setAttribute('required', 'required');
                                if (mediaType == 'video') {
                                    document.querySelector('.upload-box i').className = 'fas fa-video';
                                    document.getElementById('mediaInfo').textContent = 'Support: MP4, WEBM, AVI, MOV (Max 50MB)';
                                    fileInput.accept = 'video/*';
                                } else {
                                    document.querySelector('.upload-box i').className = 'fas fa-music';
                                    document.getElementById('mediaInfo').textContent = 'Support: MP3, WAV, M4A, OGG (Max 20MB)';
                                    fileInput.accept = 'audio/*';
                                }
                            }
                        }
                        </script>
                    </div>
                </form>
            </div>
        </div>  
        
        <!-- Daftar Soal -->
        <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">Daftar Soal</h6>
        
        <?php if(count($soal_list) > 0): ?>
            <?php $no = 1; foreach($soal_list as $soal): ?>
            <div class="soal-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="flex: 1;">
                        <p style="font-weight: 600; margin-bottom: 10px;">
                            <span class="nomor"><?php echo $no++; ?>.</span> 
                            <?php echo htmlspecialchars($soal['pertanyaan']); ?>
                            <span class="badge" style="background: #1B2A4A; color: white; font-size: 11px; margin-left: 10px;">
                                Poin: <?php echo $soal['poin']; ?>
                            </span>
                        </p>
                        <div class="opsi <?php echo $soal['jawaban_benar'] == 'A' ? 'benar' : ''; ?>">
                            A. <?php echo htmlspecialchars($soal['pilihan_a']); ?>
                            <?php if($soal['jawaban_benar'] == 'A'): ?>
                                <span style="color: #28a745;"> ✓</span>
                            <?php endif; ?>
                        </div>
                        <div class="opsi <?php echo $soal['jawaban_benar'] == 'B' ? 'benar' : ''; ?>">
                            B. <?php echo htmlspecialchars($soal['pilihan_b']); ?>
                            <?php if($soal['jawaban_benar'] == 'B'): ?>
                                <span style="color: #28a745;"> ✓</span>
                            <?php endif; ?>
                        </div>
                        <?php if(!empty($soal['pilihan_c'])): ?>
                        <div class="opsi <?php echo $soal['jawaban_benar'] == 'C' ? 'benar' : ''; ?>">
                            C. <?php echo htmlspecialchars($soal['pilihan_c']); ?>
                            <?php if($soal['jawaban_benar'] == 'C'): ?>
                                <span style="color: #28a745;"> ✓</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($soal['pilihan_d'])): ?>
                        <div class="opsi <?php echo $soal['jawaban_benar'] == 'D' ? 'benar' : ''; ?>">
                            D. <?php echo htmlspecialchars($soal['pilihan_d']); ?>
                            <?php if($soal['jawaban_benar'] == 'D'): ?>
                                <span style="color: #28a745;"> ✓</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="soal_kuis.php?kuis_id=<?php echo $kuis_id; ?>&hapus_soal=<?php echo $soal['id']; ?>" 
                       class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus soal ini?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; background: white; border-radius: 10px;">
                <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                <p style="color: #999; margin-top: 10px;">Belum ada soal untuk kuis ini</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>