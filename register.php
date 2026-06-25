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
    
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username atau email sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $email, $hashed_password])) {
                $success = 'Registrasi berhasil! Silakan login.';
                echo '<meta http-equiv="refresh" content="2;url=login.php?registered=success">';
            } else {
                $error = 'Registrasi gagal, silakan coba lagi!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - English Course</title>
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
            padding: 30px 20px;
            position: relative;
            overflow-y: auto;
        }
        
        /* ===== SCROLLBAR STYLING ===== */
        body::-webkit-scrollbar {
            width: 6px;
        }
        
        body::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }
        
        body::-webkit-scrollbar-thumb {
            background: #F4B41A;
            border-radius: 10px;
        }
        
        /* ===== BACKGROUND ANIMATION ===== */
        body::before {
            content: '';
            position: fixed;
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
            position: fixed;
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
        
        /* ===== REGISTER CONTAINER ===== */
        .register-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            margin: auto;
        }
        
        /* ===== REGISTER CARD ===== */
        .register-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 35px 35px 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: slideUp 0.8s ease-out;
            max-height: 95vh;
            overflow-y: auto;
        }
        
        /* ===== SCROLLBAR DI CARD ===== */
        .register-card::-webkit-scrollbar {
            width: 4px;
        }
        
        .register-card::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }
        
        .register-card::-webkit-scrollbar-thumb {
            background: rgba(244, 180, 26, 0.5);
            border-radius: 10px;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        /* ===== BRAND ===== */
        .brand {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .brand .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #F4B41A, #e6a010);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 28px;
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
            font-size: 24px;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .brand h1 span {
            color: #F4B41A;
        }
        
        .brand p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            margin: 2px 0 0;
        }
        
        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 14px;
            position: relative;
        }
        
        .form-group label {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }
        
        .form-group .input-wrapper {
            position: relative;
        }
        
        .form-group .input-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 14px;
            transition: color 0.3s;
            pointer-events: none;
        }
        
        .form-group .input-wrapper input {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 14px;
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
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: color 0.3s;
            padding: 5px;
        }
        
        .form-group .input-wrapper .toggle-password:hover {
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* ===== BUTTON ===== */
        .btn-register {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #F4B41A, #e6a010);
            color: #1B2A4A;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(244, 180, 26, 0.3);
        }
        
        .btn-register:hover::before {
            left: 100%;
        }
        
        /* ===== DIVIDER ===== */
        .divider {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 18px 0;
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
            font-size: 12px;
            white-space: nowrap;
        }
        
        /* ===== LOGIN LINK ===== */
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .login-link p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
            margin: 0;
        }
        
        .login-link a {
            color: #F4B41A;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: #e6a010;
            text-decoration: underline;
        }
        
        /* ===== ALERT ===== */
        .alert-custom {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 12px;
            padding: 10px 14px;
            color: #f8d7da;
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-out;
        }
        
        .alert-custom i {
            color: #dc3545;
        }
        
        .alert-success-custom {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.2);
            border-radius: 12px;
            padding: 10px 14px;
            color: #d4edda;
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success-custom i {
            color: #28a745;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            body {
                padding: 15px 12px;
            }
            
            .register-card {
                padding: 22px 16px 20px;
                border-radius: 18px;
                max-height: 98vh;
            }
            
            .brand .icon {
                width: 50px;
                height: 50px;
                font-size: 22px;
                border-radius: 14px;
            }
            
            .brand h1 {
                font-size: 20px;
            }
            
            .brand p {
                font-size: 12px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .form-group label {
                font-size: 11px;
            }
            
            .form-group .input-wrapper input {
                padding: 10px 12px 10px 38px;
                font-size: 13px;
                border-radius: 10px;
            }
            
            .form-group .input-wrapper .input-icon {
                font-size: 13px;
                left: 12px;
            }
            
            .form-group .input-wrapper .toggle-password {
                font-size: 13px;
                right: 12px;
            }
            
            .btn-register {
                padding: 11px;
                font-size: 14px;
                border-radius: 10px;
            }
            
            .divider {
                margin: 14px 0;
            }
            
            .login-link {
                margin-top: 12px;
            }
            
            .login-link p {
                font-size: 12px;
            }
        }
        
        @media (max-width: 360px) {
            body {
                padding: 10px 8px;
            }
            
            .register-card {
                padding: 16px 12px 14px;
                border-radius: 14px;
            }
            
            .brand .icon {
                width: 42px;
                height: 42px;
                font-size: 18px;
                border-radius: 12px;
            }
            
            .brand h1 {
                font-size: 17px;
            }
            
            .brand p {
                font-size: 11px;
            }
            
            .form-group .input-wrapper input {
                padding: 8px 10px 8px 34px;
                font-size: 12px;
            }
            
            .form-group .input-wrapper .input-icon {
                font-size: 12px;
                left: 10px;
            }
            
            .btn-register {
                padding: 9px;
                font-size: 13px;
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
    
    <div class="register-wrapper">
        <div class="register-card">
            <!-- Brand -->
            <div class="brand">
                <div class="icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>English <span>Course</span></h1>
                <p>Daftar Akun Baru</p>
            </div>
            
            <!-- Alert -->
            <?php if($error): ?>
                <div class="alert-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Form Register -->
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="full_name" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-tag input-icon"></i>
                        <input type="text" name="username" placeholder="Pilih username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="Masukkan email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="Minimal 6 karakter" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-check-double input-icon"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Ulangi password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i>Daftar
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>sudah punya akun?</span>
            </div>
            
            <!-- Login Link -->
            <div class="login-link">
                <p><a href="login.php">Login Sekarang</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>