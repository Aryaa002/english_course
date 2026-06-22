<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                redirect('admin/index.php');
            } else {
                redirect('index.php');
            }
        } else {
            $error = 'Username atau password salah!';
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
                        <i class="fas fa-graduation-cap" style="color: #F4B41A;"></i>
                        English Course
                    </h3>
                    <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 14px;">Login ke akun Anda</p>
                </div>
                <div class="card-body" style="padding: 40px;">
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-user"></i> Username atau Email
                            </label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Masukkan username atau email">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label" style="font-weight: 600; color: #1B2A4A;">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   style="border-radius: 10px; padding: 12px; border: 2px solid #e0e0e0;"
                                   placeholder="Masukkan password">
                        </div>
                        <button type="submit" class="btn w-100" 
                                style="background: #F4B41A; color: #1B2A4A; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 16px; border: none; transition: all 0.3s;">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p style="color: #666;">Belum punya akun? 
                            <a href="<?php echo BASE_URL; ?>register.php" style="color: #F4B41A; font-weight: 600; text-decoration: none;">
                                Daftar Sekarang
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>