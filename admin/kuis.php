<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Ambil daftar materi untuk filter
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

// Filter berdasarkan materi
$materi_id = isset($_GET['materi_id']) ? (int)$_GET['materi_id'] : 0;

$query = "SELECT k.*, m.judul as materi_judul, 
          (SELECT COUNT(*) FROM pertanyaan_kuis WHERE kuis_id = k.id) as total_soal,
          (SELECT COUNT(*) FROM hasil_kuis WHERE kuis_id = k.id) as total_peserta
          FROM kuis k 
          LEFT JOIN materi m ON k.materi_id = m.id";
$params = [];

if ($materi_id > 0) {
    $query .= " WHERE k.materi_id = ?";
    $params[] = $materi_id;
}

$query .= " ORDER BY k.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kuis_list = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
    .admin-content {
        padding: 30px 0;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .table-custom {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .table-custom thead {
        background: #1B2A4A;
        color: white;
    }
    
    .table-custom tbody tr:hover {
        background: rgba(244, 180, 26, 0.05);
    }
    
    .btn-action {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin: 0 3px;
    }
    
    .btn-edit {
        background: #F4B41A;
        color: #1B2A4A;
    }
    
    .btn-edit:hover {
        background: #d4a015;
        color: #1B2A4A;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete:hover {
        background: #c82333;
        color: white;
    }
    
    .btn-soal {
        background: #17a2b8;
        color: white;
    }
    
    .btn-soal:hover {
        background: #138496;
        color: white;
    }
    
    .btn-nilai {
        background: #28a745;
        color: white;
    }
    
    .btn-nilai:hover {
        background: #218838;
        color: white;
    }
    
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-graduation-cap" style="color: #F4B41A;"></i> Manajemen Kuis/Ujian
            </h4>
            <div>
                <a href="tambah_kuis.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                    <i class="fas fa-plus me-2"></i>Tambah Kuis
                </a>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="" class="d-flex gap-2">
                    <select name="materi_id" class="form-control" style="border-radius: 25px;">
                        <option value="0">Semua Materi</option>
                        <?php foreach($materi_list as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $materi_id == $m['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['judul']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn" style="background: #1B2A4A; color: white; border-radius: 25px;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <span style="color: #666;">Total Kuis: <strong><?php echo count($kuis_list); ?></strong></span>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-custom">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 20%;">Judul Kuis</th>
                        <th style="width: 15%;">Materi</th>
                        <th style="width: 10%;">Soal</th>
                        <th style="width: 10%;">Waktu</th>
                        <th style="width: 10%;">Passing</th>
                        <th style="width: 10%;">Peserta</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($kuis_list) > 0): ?>
                        <?php foreach($kuis_list as $kuis): ?>
                        <tr>
                            <td><?php echo $kuis['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($kuis['judul']); ?></strong></td>
                            <td><?php echo htmlspecialchars($kuis['materi_judul']); ?></td>
                            <td><span class="badge" style="background: #1B2A4A; color: white;"><?php echo $kuis['total_soal']; ?></span></td>
                            <td><?php echo $kuis['waktu']; ?> menit</td>
                            <td><span class="badge" style="background: #F4B41A; color: #1B2A4A;"><?php echo $kuis['passing_grade']; ?>%</span></td>
                            <td><?php echo $kuis['total_peserta']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $kuis['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $kuis['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_kuis.php?id=<?php echo $kuis['id']; ?>" class="btn btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="soal_kuis.php?kuis_id=<?php echo $kuis['id']; ?>" class="btn btn-action btn-soal">
                                    <i class="fas fa-question-circle"></i>
                                </a>
                                <a href="nilai_kuis.php?kuis_id=<?php echo $kuis['id']; ?>" class="btn btn-action btn-nilai">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <a href="hapus_kuis.php?id=<?php echo $kuis['id']; ?>" class="btn btn-action btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus kuis ini? Semua data nilai juga akan terhapus.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                <p style="color: #999; margin-top: 10px;">Belum ada kuis</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>