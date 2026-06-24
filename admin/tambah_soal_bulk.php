<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Statistik untuk sidebar
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();
$total_toefl = $pdo->query("SELECT COUNT(*) FROM toefl_tests")->fetchColumn();

// Ambil daftar materi
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

$error = '';
$success = '';
$inserted = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $materi_id = (int)$_POST['materi_id'];
    $soals = $_POST['soal'] ?? [];
    
    if ($materi_id == 0) {
        $error = 'Silakan pilih materi terlebih dahulu!';
    } elseif (empty($soals)) {
        $error = 'Silakan tambahkan minimal 1 soal!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO soal_latihan (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, tingkat_kesulitan) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($soals as $soal) {
            $pertanyaan = trim($soal['pertanyaan'] ?? '');
            $pilihan_a = trim($soal['pilihan_a'] ?? '');
            $pilihan_b = trim($soal['pilihan_b'] ?? '');
            $pilihan_c = trim($soal['pilihan_c'] ?? '');
            $pilihan_d = trim($soal['pilihan_d'] ?? '');
            $jawaban_benar = $soal['jawaban_benar'] ?? '';
            $tingkat_kesulitan = $soal['tingkat_kesulitan'] ?? 'sedang';
            
            if (!empty($pertanyaan) && !empty($pilihan_a) && !empty($pilihan_b) && !empty($jawaban_benar)) {
                $stmt->execute([$materi_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $jawaban_benar, $tingkat_kesulitan]);
                $inserted++;
            }
        }
        
        if ($inserted > 0) {
            $success = "Berhasil menambahkan $inserted soal!";
            echo '<meta http-equiv="refresh" content="2;url=soal.php">';
        } else {
            $error = 'Tidak ada soal yang valid untuk disimpan!';
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
    
    .soal-row {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid #F4B41A;
        position: relative;
    }
    
    .soal-row .btn-remove-soal {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .soal-row .btn-remove-soal:hover {
        transform: scale(1.1);
    }
    
    .btn-add-soal {
        background: #28a745;
        color: white;
        padding: 10px 25px;
        border-radius: 25px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
    }
    
    .btn-add-soal:hover {
        background: #218838;
        transform: translateY(-2px);
        color: white;
    }
    
    .btn-submit-bulk {
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
    
    .btn-submit-bulk:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4);
        color: #1B2A4A;
    }
    
    .option-group-small {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    
    .option-group-small .option-letter {
        font-weight: 700;
        color: #1B2A4A;
        min-width: 25px;
    }
    
    .option-group-small input {
        flex: 1;
    }
    
    .soal-number-badge {
        display: inline-block;
        background: #1B2A4A;
        color: white;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .sidebar-toggle { display: none; background: #1B2A4A; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-size: 20px; cursor: pointer; }
    .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
    .overlay.active { display: block; }
    
    @media (max-width: 768px) { .sidebar { transform: translateX(-100%); width: 280px; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 15px; } .sidebar-toggle { display: block; } }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Soal Massal
            </h4>
            <a href="soal.php" class="btn" style="background: #1B2A4A; color: white; padding: 8px 20px; border-radius: 25px;">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" id="formBulk">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="materi_id">Pilih Materi <span style="color: red;">*</span></label>
                        <select class="form-control" id="materi_id" name="materi_id" required>
                            <option value="0">-- Pilih Materi --</option>
                            <?php foreach($materi_list as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['judul']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="soalContainer">
                    <!-- Soal 1 -->
                    <div class="soal-row" data-index="0">
                        <button type="button" class="btn-remove-soal" onclick="removeSoal(this)" title="Hapus soal ini">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="soal-number-badge">Soal #1</div>
                        <div class="mb-2">
                            <label>Pertanyaan <span style="color: red;">*</span></label>
                            <textarea class="form-control" name="soal[0][pertanyaan]" rows="2" placeholder="Masukkan pertanyaan..." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="option-group-small">
                                    <span class="option-letter">A.</span>
                                    <input type="text" class="form-control" name="soal[0][pilihan_a]" placeholder="Pilihan A" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="option-group-small">
                                    <span class="option-letter">B.</span>
                                    <input type="text" class="form-control" name="soal[0][pilihan_b]" placeholder="Pilihan B" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="option-group-small">
                                    <span class="option-letter">C.</span>
                                    <input type="text" class="form-control" name="soal[0][pilihan_c]" placeholder="Pilihan C (opsional)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="option-group-small">
                                    <span class="option-letter">D.</span>
                                    <input type="text" class="form-control" name="soal[0][pilihan_d]" placeholder="Pilihan D (opsional)">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <label>Jawaban Benar <span style="color: red;">*</span></label>
                                <select class="form-control" name="soal[0][jawaban_benar]" required>
                                    <option value="">Pilih</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Tingkat Kesulitan</label>
                                <select class="form-control" name="soal[0][tingkat_kesulitan]">
                                    <option value="mudah">Mudah</option>
                                    <option value="sedang" selected>Sedang</option>
                                    <option value="sulit">Sulit</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mb-4">
                    <button type="button" class="btn-add-soal" onclick="tambahSoal()">
                        <i class="fas fa-plus me-2"></i>Tambah Soal Lagi
                    </button>
                </div>
                
                <div class="col-md-12">
                    <button type="submit" class="btn-submit-bulk">
                        <i class="fas fa-save me-2"></i>Simpan Semua Soal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let soalCount = 1;

function tambahSoal() {
    const container = document.getElementById('soalContainer');
    const index = soalCount;
    
    const div = document.createElement('div');
    div.className = 'soal-row';
    div.setAttribute('data-index', index);
    div.innerHTML = `
        <button type="button" class="btn-remove-soal" onclick="removeSoal(this)" title="Hapus soal ini">
            <i class="fas fa-times"></i>
        </button>
        <div class="soal-number-badge">Soal #${index + 1}</div>
        <div class="mb-2">
            <label>Pertanyaan <span style="color: red;">*</span></label>
            <textarea class="form-control" name="soal[${index}][pertanyaan]" rows="2" placeholder="Masukkan pertanyaan..." required></textarea>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="option-group-small">
                    <span class="option-letter">A.</span>
                    <input type="text" class="form-control" name="soal[${index}][pilihan_a]" placeholder="Pilihan A" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="option-group-small">
                    <span class="option-letter">B.</span>
                    <input type="text" class="form-control" name="soal[${index}][pilihan_b]" placeholder="Pilihan B" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="option-group-small">
                    <span class="option-letter">C.</span>
                    <input type="text" class="form-control" name="soal[${index}][pilihan_c]" placeholder="Pilihan C (opsional)">
                </div>
            </div>
            <div class="col-md-6">
                <div class="option-group-small">
                    <span class="option-letter">D.</span>
                    <input type="text" class="form-control" name="soal[${index}][pilihan_d]" placeholder="Pilihan D (opsional)">
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6">
                <label>Jawaban Benar <span style="color: red;">*</span></label>
                <select class="form-control" name="soal[${index}][jawaban_benar]" required>
                    <option value="">Pilih</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="col-md-6">
                <label>Tingkat Kesulitan</label>
                <select class="form-control" name="soal[${index}][tingkat_kesulitan]">
                    <option value="mudah">Mudah</option>
                    <option value="sedang" selected>Sedang</option>
                    <option value="sulit">Sulit</option>
                </select>
            </div>
        </div>
    `;
    
    container.appendChild(div);
    soalCount++;
}

function removeSoal(btn) {
    const row = btn.closest('.soal-row');
    const totalSoal = document.querySelectorAll('.soal-row').length;
    if (totalSoal <= 1) {
        alert('Minimal harus ada 1 soal!');
        return;
    }
    if (confirm('Yakin ingin menghapus soal ini?')) {
        row.remove();
        // Update nomor soal
        document.querySelectorAll('.soal-row').forEach((el, idx) => {
            el.querySelector('.soal-number-badge').textContent = `Soal #${idx + 1}`;
            el.setAttribute('data-index', idx);
            // Update name attribute
            const inputs = el.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/soal\[\d+\]/, `soal[${idx}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        soalCount = document.querySelectorAll('.soal-row').length;
    }
}
</script>

<?php include '../includes/footer.php'; ?>