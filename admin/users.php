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

// Proses CRUD
$message = '';
$message_type = '';

// Tambah User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Tambah User Baru
    if ($action == 'tambah') {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            $message = 'Semua field wajib diisi!';
            $message_type = 'danger';
        } else {
            // Cek username/email sudah ada
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $message = 'Username atau email sudah terdaftar!';
                $message_type = 'danger';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$full_name, $username, $email, $hashed_password, $role, $is_active])) {
                    $message = 'Pengguna berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan pengguna!';
                    $message_type = 'danger';
                }
            }
        }
    }
    
    // Edit User
    elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $reset_password = isset($_POST['reset_password']) ? trim($_POST['reset_password']) : '';
        
        if (empty($full_name) || empty($username) || empty($email)) {
            $message = 'Field wajib diisi!';
            $message_type = 'danger';
        } else {
            // Cek username/email tidak bentrok dengan user lain
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $id]);
            if ($stmt->rowCount() > 0) {
                $message = 'Username atau email sudah digunakan oleh pengguna lain!';
                $message_type = 'danger';
            } else {
                // Update data
                $sql = "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, is_active = ?";
                $params = [$full_name, $username, $email, $role, $is_active];
                
                // Jika ada reset password
                if (!empty($reset_password)) {
                    $hashed_password = password_hash($reset_password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $params[] = $hashed_password;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $id;
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $message = 'Pengguna berhasil diupdate!';
                    if (!empty($reset_password)) {
                        $message .= ' Password telah direset.';
                    }
                    $message_type = 'success';
                } else {
                    $message = 'Gagal mengupdate pengguna!';
                    $message_type = 'danger';
                }
            }
        }
    }
    
    // Hapus User (POST)
    elseif ($action == 'hapus') {
        $id = (int)$_POST['id'];
        // Cek bukan admin yang login
        if ($id == $_SESSION['user_id']) {
            $message = 'Tidak bisa menghapus akun sendiri!';
            $message_type = 'danger';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Pengguna berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menghapus pengguna!';
                $message_type = 'danger';
            }
        }
    }
}

// Hapus User (GET)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id == $_SESSION['user_id']) {
        $message = 'Tidak bisa menghapus akun sendiri!';
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Pengguna berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus pengguna!';
            $message_type = 'danger';
        }
    }
}

// Ambil semua users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Ambil data user untuk edit
$edit_user = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $edit_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        .sidebar { width: 280px; background: #1B2A4A; color: white; padding: 0; position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; transition: all 0.3s; }
        .sidebar-brand { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-brand h3 { color: white; font-weight: 700; margin: 0; }
        .sidebar-brand h3 span { color: #F4B41A; }
        .sidebar-brand .subtitle { color: rgba(255,255,255,0.6); font-size: 13px; margin-top: 5px; }
        .sidebar-user { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-user .avatar { width: 60px; height: 60px; border-radius: 50%; background: rgba(244, 180, 26, 0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; border: 3px solid #F4B41A; }
        .sidebar-user .avatar i { font-size: 28px; color: #F4B41A; }
        .sidebar-user .name { font-weight: 600; font-size: 16px; }
        .sidebar-user .role { color: rgba(255,255,255,0.6); font-size: 12px; }
        .sidebar-nav { padding: 15px 0; }
        .sidebar-nav .nav-label { padding: 10px 25px; font-size: 11px; text-transform: uppercase; color: rgba(255,255,255,0.4); letter-spacing: 1px; }
        .sidebar-nav .nav-link { display: flex; align-items: center; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-nav .nav-link:hover { background: rgba(255,255,255,0.05); color: white; border-left-color: #F4B41A; }
        .sidebar-nav .nav-link.active { background: rgba(244, 180, 26, 0.1); color: #F4B41A; border-left-color: #F4B41A; }
        .sidebar-nav .nav-link i { width: 24px; margin-right: 12px; font-size: 16px; }
        .sidebar-nav .nav-link .badge { margin-left: auto; background: #F4B41A; color: #1B2A4A; }
        
        .main-content { margin-left: 280px; flex: 1; padding: 20px 30px; min-height: 100vh; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #e0e0e0; margin-bottom: 25px; }
        .top-bar .page-title h4 { color: #1B2A4A; font-weight: 700; margin: 0; }
        .top-bar .page-title p { color: #999; font-size: 14px; margin: 0; }
        .top-bar .user-info { display: flex; align-items: center; gap: 15px; }
        .top-bar .user-info .date { color: #666; font-size: 14px; }
        .top-bar .user-info .logout-btn { background: #dc3545; color: white; border: none; padding: 8px 20px; border-radius: 25px; font-weight: 600; text-decoration: none; transition: all 0.3s; }
        .top-bar .user-info .logout-btn:hover { background: #c82333; transform: translateY(-2px); }
        
        .table-custom { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .table-custom thead { background: #1B2A4A; color: white; }
        .table-custom tbody tr:hover { background: rgba(244, 180, 26, 0.05); }
        
        .role-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .role-admin { background: #F4B41A; color: #1B2A4A; }
        .role-user { background: #d4edda; color: #155724; }
        
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .btn-action { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin: 0 3px; }
        .btn-edit { background: #F4B41A; color: #1B2A4A; }
        .btn-edit:hover { background: #d4a015; color: #1B2A4A; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; color: white; }
        .btn-reset { background: #17a2b8; color: white; }
        .btn-reset:hover { background: #138496; color: white; }
        
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .form-container label { font-weight: 600; color: #1B2A4A; }
        .form-container .form-control { border-radius: 10px; border: 2px solid #e0e0e0; padding: 10px 15px; }
        .form-container .form-control:focus { border-color: #F4B41A; box-shadow: 0 0 0 0.2rem rgba(244, 180, 26, 0.25); }
        .btn-submit { background: #F4B41A; color: #1B2A4A; padding: 10px 30px; border-radius: 25px; font-weight: 600; border: none; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4); color: #1B2A4A; }
        .btn-cancel { background: #6c757d; color: white; padding: 10px 30px; border-radius: 25px; font-weight: 600; border: none; transition: all 0.3s; text-decoration: none; }
        .btn-cancel:hover { background: #5a6268; color: white; }
        
        .sidebar-toggle { display: none; background: #1B2A4A; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-size: 20px; cursor: pointer; }
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .overlay.active { display: block; }
        
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); width: 280px; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 15px; } .sidebar-toggle { display: block; } }
        
        .modal-backdrop {
            z-index: 1040 !important;
        }
        .modal-content {
            border-radius: 15px !important;
            border: none !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            background: #1B2A4A;
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h3>English <span>Course</span></h3>
                <div class="subtitle">Admin Panel</div>
            </div>
            <div class="sidebar-user">
                <div class="avatar"><i class="fas fa-user-cog"></i></div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
                <div class="role">Administrator</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main Menu</div>
                <a href="index.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="materi.php" class="nav-link">
                    <i class="fas fa-book"></i> Materi
                    <span class="badge"><?php echo $total_materi; ?></span>
                </a>
                <a href="soal.php" class="nav-link">
                    <i class="fas fa-question-circle"></i> Soal Latihan
                    <span class="badge"><?php echo $total_soal; ?></span>
                </a>
                <a href="toefl.php" class="nav-link">
                    <i class="fas fa-graduation-cap"></i> TOEFL
                    <span class="badge"><?php echo $total_toefl; ?></span>
                </a>
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users"></i> Pengguna
                    <span class="badge"><?php echo $total_users; ?></span>
                </a>
                <div class="nav-label mt-3">Lainnya</div>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> Lihat Website
                </a>
                <a href="../logout.php" class="nav-link" style="color: #dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <div class="d-flex align-items-center">
                        <button class="sidebar-toggle me-3" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h4><i class="fas fa-users" style="color: #F4B41A;"></i> Kelola Pengguna</h4>
                            <p>Manajemen semua pengguna website</p>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Form Tambah/Edit User -->
            <div class="form-container">
                <?php if($edit_user): ?>
                    <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                        <i class="fas fa-edit" style="color: #F4B41A;"></i> Edit Pengguna
                    </h6>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Nama Lengkap *</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Username *</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Email *</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Role</label>
                                <select class="form-control" name="role">
                                    <option value="user" <?php echo $edit_user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Status</label>
                                <select class="form-control" name="is_active">
                                    <option value="1" <?php echo $edit_user['is_active'] ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo !$edit_user['is_active'] ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Reset Password (kosongkan jika tidak diubah)</label>
                                <input type="password" class="form-control" name="reset_password" placeholder="Masukkan password baru">
                                <small class="text-muted">Isi jika ingin mereset password pengguna</small>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn-submit"><i class="fas fa-save me-2"></i>Update Pengguna</button>
                                <a href="users.php" class="btn-cancel ms-2"><i class="fas fa-times me-2"></i>Batal</a>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <h6 style="color: #1B2A4A; font-weight: 600; margin-bottom: 15px;">
                        <i class="fas fa-plus-circle" style="color: #F4B41A;"></i> Tambah Pengguna Baru
                    </h6>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="tambah">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Nama Lengkap *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Username *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Password *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Role</label>
                                <select class="form-control" name="role">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-center">
                                <div class="form-check mt-3">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn-submit w-100"><i class="fas fa-user-plus me-2"></i>Tambah Pengguna</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Daftar Pengguna -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span style="color: #666;">Total Pengguna: <strong><?php echo count($users); ?></strong></span>
                </div>
            </div>
            
            <div class="table-custom">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 18%;">Nama Lengkap</th>
                            <th style="width: 15%;">Username</th>
                            <th style="width: 20%;">Email</th>
                            <th style="width: 10%;">Role</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 12%;">Tanggal Daftar</th>
                            <th style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($users) > 0): ?>
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
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-action btn-reset" data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-key"></i> Reset
                                        </button>
                                        <a href="users.php?hapus=<?php echo $user['id']; ?>" class="btn btn-action btn-delete" 
                                           onclick="return confirm('Yakin ingin menghapus pengguna <?php echo htmlspecialchars($user['full_name']); ?>?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    <?php else: ?>
                                        <span class="badge" style="background: #6c757d; color: white;">Akun Sendiri</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Modal Reset Password -->
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <div class="modal fade" id="resetModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-key" style="color: #F4B41A;"></i> Reset Password</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                                <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $user['is_active']; ?>">
                                                
                                                <p>Reset password untuk pengguna:</p>
                                                <h6 style="color: #1B2A4A;"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                <p style="color: #666; font-size: 14px;">Username: <?php echo htmlspecialchars($user['username']); ?></p>
                                                
                                                <div class="mb-3">
                                                    <label>Password Baru *</label>
                                                    <input type="text" class="form-control" name="reset_password" required minlength="6" placeholder="Masukkan password baru (min 6 karakter)">
                                                </div>
                                                <div class="mb-3">
                                                    <label>Konfirmasi Password</label>
                                                    <input type="text" class="form-control" id="confirm_password_<?php echo $user['id']; ?>" placeholder="Ketik ulang password" oninput="validatePassword(this, <?php echo $user['id']; ?>)">
                                                    <small id="password_msg_<?php echo $user['id']; ?>" style="color: #dc3545; display: none;">Password tidak cocok!</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn" style="background: #F4B41A; color: #1B2A4A; font-weight: 600;" id="resetBtn_<?php echo $user['id']; ?>">
                                                    <i class="fas fa-save me-2"></i>Reset Password
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">Belum ada pengguna terdaftar</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('overlay').classList.remove('active');
            }
        });
        
        function validatePassword(input, userId) {
            var password = document.querySelector('input[name="reset_password"]').value;
            var confirm = input.value;
            var msg = document.getElementById('password_msg_' + userId);
            var btn = document.getElementById('resetBtn_' + userId);
            
            if (confirm.length > 0 && password !== confirm) {
                msg.style.display = 'block';
                btn.disabled = true;
                btn.style.opacity = '0.5';
            } else {
                msg.style.display = 'none';
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        }
        
        // Auto close alert setelah 5 detik
        document.addEventListener('DOMContentLoaded', function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    var closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html>