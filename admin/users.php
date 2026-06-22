<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Ambil semua users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

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
    
    .role-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .role-admin {
        background: #F4B41A;
        color: #1B2A4A;
    }
    
    .role-user {
        background: #d4edda;
        color: #155724;
    }
</style>

<div class="admin-content">
    <div class="container">
        <h4 style="color: #1B2A4A; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-users" style="color: #F4B41A;"></i> Manajemen Pengguna
        </h4>
        
        <div class="table-custom">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge <?php echo ($user['role'] == 'admin') ? 'role-admin' : 'role-user'; ?>">
                                <?php echo strtoupper($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>