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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f1a2e 0%, #1B2A4A 50%, #2C4066 100%);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* ===== BACKGROUND ANIMATION ===== */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(244, 180, 26, 0.03) 0%, transparent 60%),
                        radial-gradient(circle at 70% 80%, rgba(244, 180, 26, 0.02) 0%, transparent 40%);
            animation: rotateBg 60s linear infinite;
            z-index: 0;
        }
        
        @keyframes rotateBg {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ===== FLOATING SHAPES ===== */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        
        .floating-shapes .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.05;
            background: #F4B41A;
            animation: floatShape 20s ease-in-out infinite;
        }
        
        .floating-shapes .shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .floating-shapes .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: -50px;
            animation-delay: -7s;
        }
        
        .floating-shapes .shape:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 50%;
            animation-delay: -14s;
        }
        
        @keyframes floatShape {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -30px) scale(1.1); }
            66% { transform: translate(-30px, 40px) scale(0.9); }
        }
        
        /* ===== LOGIN CONTAINER ===== */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }
        
        /* ===== LOGIN CARD ===== */
        .login-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 45px 40px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        /* ===== BRAND ===== */
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #F4B41A, #e6a010);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            color: #1B2A4A;
            box-shadow: 0 10px 30px rgba(244, 180, 26, 0.3);
            animation: pulseIcon 3s ease-in-out infinite;
        }
        
        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .brand h1 {
            color: white;
            font-weight: 700;
            font-size: 28px;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .brand h1 span {
            color: #F4B41A;
        }
        
        .brand p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin: 5px 0 0;
            letter-spacing: 0.5px;
        }
        
        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        
        .form-group .input-wrapper {
            position: relative;
        }
        
        .form-group .input-wrapper .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 16px;
            transition: color 0.3s;
            pointer-events: none;
        }
        
        .form-group .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
        }
        
        .form-group .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }
        
        .form-group .input-wrapper input:focus {
            border-color: #F4B41A;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(244, 180, 26, 0.08);
        }
        
        .form-group .input-wrapper input:focus + .input-icon,
        .form-group .input-wrapper input:focus ~ .input-icon {
            color: #F4B41A;
        }
        
        .form-group .input-wrapper .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
            padding: 5px;
        }
        
        .form-group .input-wrapper .toggle-password:hover {
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* ===== OPTIONS ===== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0 25px;
        }
        
        .form-options .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            cursor: pointer;
        }
        
        .form-options .remember-me input[type="checkbox"] {
            accent-color: #F4B41A;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .form-options .forgot-link {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .form-options .forgot-link:hover {
            color: #F4B41A;
        }
        
        /* ===== BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #F4B41A, #e6a010);
            color: #1B2A4A;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(244, 180, 26, 0.3);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* ===== DIVIDER ===== */
        .divider {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 25px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
        }
        
        .divider span {
            color: rgba(255, 255, 255, 0.3);
            font-size: 13px;
            white-space: nowrap;
        }
        
        /* ===== REGISTER LINK ===== */
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
            margin: 0;
        }
        
        .register-link a {
            color: #F4B41A;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: #e6a010;
            text-decoration: underline;
        }
        
        /* ===== ALERT ===== */
        .alert-custom {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            color: #f8d7da;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-out;
        }
        
        .alert-custom i {
            color: #dc3545;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        .alert-success-custom {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            color: #d4edda;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success-custom i {
            color: #28a745;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px 25px;
                border-radius: 20px;
            }
            
            .brand .icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }
            
            .brand h1 {
                font-size: 24px;
            }
            
            .form-group .input-wrapper input {
                padding: 12px 14px 12px 44px;
                font-size: 14px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 15px;
            }
        }
        
        @media (max-width: 360px) {
            .login-card {
                padding: 20px 15px;
            }
            
            .brand .icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            
            .brand h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Shapes Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Brand -->
            <div class="brand">
                <div class="icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>English <span>Course</span></h1>
                <p>Belajar Bahasa Inggris dengan Mudah</p>
            </div>
            
            <!-- Alert -->
            <?php if($error): ?>
                <div class="alert-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle"></i>
                    Registrasi berhasil! Silakan login.
                </div>
            <?php endif; ?>
            
            <!-- Form Login -->
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username atau Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" placeholder="Masukkan username atau email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>
                    <a href="#" class="forgot-link">Lupa password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>atau</span>
            </div>
            
            <!-- Register Link -->
            <div class="register-link">
                <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>