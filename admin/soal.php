<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Ambil daftar materi untuk filter
$materi_list = $pdo->query("SELECT id, judul FROM materi ORDER BY judul")->fetchAll();

// Filter berdasarkan materi
$materi_id = isset($_GET['materi_id']) ? (int)$_GET['materi_id'] : 0;

$query = "SELECT s.*, m.judul as materi_judul FROM soal_latihan s 
          LEFT JOIN materi m ON s.materi_id = m.id";
$params = [];

if ($materi_id > 0) {
    $query .= " WHERE s.materi_id = ?";
    $params[] = $materi_id;
}

$query .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$soal_list = $stmt->fetchAll();

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
    
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-mudah {
        background: #d4edda;
        color: #155724;
    }
    
    .status-sedang {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-sulit {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-question-circle" style="color: #F4B41A;"></i> Manajemen Soal Latihan
            </h4>
            <a href="tambah_soal.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-plus me-2"></i>Tambah Soal
            </a>
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
                <span style="color: #666;">Total Soal: <strong><?php echo count($soal_list); ?></strong></span>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-custom">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 25%;">Pertanyaan</th>
                        <th style="width: 20%;">Materi</th>
                        <th style="width: 15%;">Pilihan</th>
                        <th style="width: 10%;">Jawaban</th>
                        <th style="width: 10%;">Tingkat</th>
                        <th style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($soal_list) > 0): ?>
                        <?php foreach($soal_list as $soal): ?>
                        <tr>
                            <td><?php echo $soal['id']; ?></td>
                            <td><?php echo substr(htmlspecialchars($soal['pertanyaan']), 0, 60) . '...'; ?></td>
                            <td><span class="status-badge status-mudah"><?php echo htmlspecialchars($soal['materi_judul']); ?></span></td>
                            <td>
                                <span style="font-size: 12px;">
                                    A: <?php echo htmlspecialchars($soal['pilihan_a']); ?><br>
                                    B: <?php echo htmlspecialchars($soal['pilihan_b']); ?>
                                </span>
                            </td>
                            <td><strong style="color: #28a745;"><?php echo $soal['jawaban_benar']; ?></strong></td>
                            <td>
                                <span class="status-badge <?php echo 'status-' . $soal['tingkat_kesulitan']; ?>">
                                    <?php echo ucfirst($soal['tingkat_kesulitan']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_soal.php?id=<?php echo $soal['id']; ?>" class="btn btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="hapus_soal.php?id=<?php echo $soal['id']; ?>" class="btn btn-action btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus soal ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                <p style="color: #999; margin-top: 10px;">Belum ada soal latihan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>