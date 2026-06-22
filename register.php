<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        // Cek username atau email sudah terdaftar
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username atau email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $email, $hashed_password])) {
                $success = 'Registrasi berhasil! Silakan login.';
                // Redirect ke login setelah 2 detik
                echo '<meta http-equiv="refresh" content="2;url=login.php">';
            } else {
                $error = 'Registrasi gagal, silakan coba lagi!';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 50px 0;">
    <div class="row w-100" style="max-width: 500px;">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #1B2A4A 0%, #2C4066 100%); padding: 30px; text-align: center;">
                    <h3 style="color: white; font-weight: 700; margin: 0;">
                        <i class="fas fa-user-plus" style="color: #F4B41A;"></i>
                        Daftar Akun
                    </h3>
                    <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 14px;">Mulai belajar bahasa Inggris sekarang</p>
                </div>
                <div class="card-body" style="padding: 40px;">
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
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="full_name" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-user"></i> Nama Lengkap
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Masukkan nama lengkap" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-user-tag"></i> Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Pilih username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Masukkan email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Minimal 6 karakter">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-check-double"></i> Konfirmasi Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Ulangi password">
                        </div>
                        <button type="submit" class="btn w-100" 
                                style="background: #F4B41A; color: #1B2A4A; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 16px; border: none; transition: all 0.3s;">
                            <i class="fas fa-user-plus me-2"></i>Daftar
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p style="color: #666;">Sudah punya akun? 
                            <a href="login.php" style="color: #F4B41A; font-weight: 600; text-decoration: none;">
                                Login Sekarang
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>