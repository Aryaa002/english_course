<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    redirect('kuis.php');
}

// Ambil data kuis
$stmt = $pdo->prepare("SELECT * FROM kuis WHERE id = ?");
$stmt->execute([$id]);
$kuis = $stmt->fetch();

if (!$kuis) {
    redirect('kuis.php');
}

// Ambil daftar materi
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $materi_id = (int)$_POST['materi_id'];
    $waktu = (int)$_POST['waktu'];
    $passing_grade = (int)$_POST['passing_grade'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_acak = isset($_POST['is_acak']) ? 1 : 0;
    
    if (empty($judul) || empty($deskripsi) || $materi_id == 0) {
        $error = 'Field wajib harus diisi!';
    } else {
        $stmt = $pdo->prepare("UPDATE kuis SET 
                              judul = ?, deskripsi = ?, materi_id = ?, waktu = ?, 
                              passing_grade = ?, is_active = ?, is_acak = ? 
                              WHERE id = ?");
        if ($stmt->execute([$judul, $deskripsi, $materi_id, $waktu, $passing_grade, $is_active, $is_acak, $id])) {
            $success = 'Kuis berhasil diupdate!';
            echo '<meta http-equiv="refresh" content="2;url=kuis.php">';
        } else {
            $error = 'Gagal mengupdate kuis!';
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
</style>

<div class="admin-content" style="padding: 30px 0; background: #f8f9fa; min-height: 100vh;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-edit" style="color: #F4B41A;"></i> Edit Kuis/Ujian
            </h4>
            <a href="kuis.php" class="btn-back">
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
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="judul">Judul Kuis/Ujian <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" 
                               value="<?php echo htmlspecialchars($kuis['judul']); ?>" required>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="deskripsi">Deskripsi <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required><?php echo htmlspecialchars($kuis['deskripsi']); ?></textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="materi_id">Pilih Materi <span style="color: red;">*</span></label>
                        <select class="form-control" id="materi_id" name="materi_id" required>
                            <option value="0">Pilih Materi</option>
                            <?php foreach($materi_list as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $kuis['materi_id'] == $m['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['judul']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="waktu">Durasi (menit) <span style="color: red;">*</span></label>
                        <input type="number" class="form-control" id="waktu" name="waktu" 
                               value="<?php echo $kuis['waktu']; ?>" min="5" max="180" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="passing_grade">Passing Grade (%) <span style="color: red;">*</span></label>
                        <input type="number" class="form-control" id="passing_grade" name="passing_grade" 
                               value="<?php echo $kuis['passing_grade']; ?>" min="0" max="100" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                   <?php echo $kuis['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Aktif (dapat dikerjakan siswa)</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_acak" name="is_acak" 
                                   <?php echo $kuis['is_acak'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_acak">Acak Soal</label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i>Update Kuis
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>