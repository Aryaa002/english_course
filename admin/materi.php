<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query
$query = "SELECT * FROM materi";
$params = [];

if ($search) {
    $query .= " WHERE judul LIKE ? OR kategori LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materi = $stmt->fetchAll();

// Total data untuk pagination
$countQuery = "SELECT COUNT(*) FROM materi";
if ($search) {
    $countQuery .= " WHERE judul LIKE ? OR kategori LIKE ?";
}
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

include '../includes/header.php';
?>
<!-- Sisa HTML sama seperti sebelumnya -->

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
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="color: #1B2A4A; font-weight: 700;">
                <i class="fas fa-book" style="color: #F4B41A;"></i> Manajemen Materi
            </h4>
            <a href="tambah_materi.php" class="btn" style="background: #F4B41A; color: #1B2A4A; padding: 10px 25px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-plus me-2"></i>Tambah Materi
            </a>
        </div>
        
        <!-- Search -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Cari materi..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 25px 0 0 25px;">
                        <button class="btn" type="submit" style="background: #1B2A4A; color: white; border-radius: 0 25px 25px 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-custom">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 25%;">Judul</th>
                        <th style="width: 20%;">Kategori</th>
                        <th style="width: 15%;">Tingkat</th>
                        <th style="width: 15%;">Durasi</th>
                        <th style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($materi) > 0): ?>
                        <?php foreach($materi as $m): ?>
                        <tr>
                            <td><?php echo $m['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($m['judul']); ?></strong></td>
                            <td><span class="status-badge status-active"><?php echo htmlspecialchars($m['kategori']); ?></span></td>
                            <td><?php echo htmlspecialchars($m['tingkat']); ?></td>
                            <td><?php echo htmlspecialchars($m['durasi']); ?></td>
                            <td>
                                <a href="edit_materi.php?id=<?php echo $m['id']; ?>" class="btn btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="hapus_materi.php?id=<?php echo $m['id']; ?>" class="btn btn-action btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus materi ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                <p style="color: #999; margin-top: 10px;">Belum ada materi</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>