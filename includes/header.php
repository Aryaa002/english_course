<?php
// Pastikan BASE_URL sudah didefinisikan
if (!defined('BASE_URL')) {
    define('BASE_URL', '/english-course/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Course - Belajar Bahasa Inggris Online</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1B2A4A;
            --secondary: #F4B41A;
            --white: #FFFFFF;
            --dark: #1B2A4A;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: var(--white);
        }
        
        /* ===== NAVBAR ===== */
        .navbar {
            background: var(--primary) !important;
            padding: 12px 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            color: var(--white) !important;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 0.5px;
        }
        
        .navbar-brand span {
            color: var(--secondary);
        }
        
        .navbar-brand i {
            color: var(--secondary);
            margin-right: 8px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            transition: all 0.3s;
            margin: 0 8px;
            padding: 8px 16px !important;
            border-radius: 8px;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--white) !important;
            background: rgba(255,255,255,0.08);
        }
        
        .nav-link.active {
            color: var(--secondary) !important;
            background: rgba(244, 180, 26, 0.15);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: var(--secondary);
            border-radius: 3px;
        }
        
        .nav-link i {
            margin-right: 6px;
        }
        
        /* ===== BUTTONS ===== */
        .btn-custom {
            background: var(--secondary);
            color: var(--primary);
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(244, 180, 26, 0.4);
            color: var(--primary);
        }
        
        .btn-outline-custom {
            border: 2px solid var(--secondary);
            color: var(--secondary);
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            background: transparent;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-outline-custom:hover {
            background: var(--secondary);
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .btn-logout {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            border: 1px solid rgba(220, 53, 69, 0.3);
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-logout:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-logout i {
            margin-right: 6px;
        }
        
        /* ===== USER AVATAR ===== */
        .user-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(244, 180, 26, 0.2);
            color: var(--secondary);
            border: 2px solid var(--secondary);
            font-weight: 700;
            font-size: 14px;
            margin-right: 8px;
        }
        
        .user-name {
            color: var(--white);
            font-weight: 500;
        }
        
        .user-name i {
            color: var(--secondary);
            margin-right: 4px;
        }
        
        /* ===== DROPDOWN ===== */
        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 8px 0;
            min-width: 200px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .dropdown-item:hover {
            background: rgba(244, 180, 26, 0.08);
            color: var(--primary);
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
            color: var(--secondary);
        }
        
        .dropdown-divider {
            margin: 6px 0;
        }
        
        /* ===== NOTIFICATION ===== */
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        
        .notification-badge .badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }
        
        /* ===== TOGGLER ===== */
        .navbar-toggler {
            border: 2px solid rgba(255,255,255,0.2);
            padding: 8px 12px;
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(244, 180, 26, 0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 991px) {
            .navbar-nav {
                padding: 15px 0;
            }
            
            .nav-link {
                padding: 10px 16px !important;
                margin: 2px 0;
            }
            
            .nav-link.active::after {
                display: none;
            }
            
            .navbar-nav .btn-custom,
            .navbar-nav .btn-outline-custom,
            .navbar-nav .btn-logout {
                display: inline-block;
                margin: 5px 0;
                text-align: center;
                width: 100%;
            }
            
            .user-info-mobile {
                display: flex;
                align-items: center;
                padding: 10px 16px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                margin-bottom: 10px;
            }
        }
        
        @media (min-width: 992px) {
            .user-info-mobile {
                display: none;
            }
        }
        
        /* ===== CARD ===== */
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        /* ===== FOOTER ===== */
        footer {
            background: var(--primary);
            color: var(--white);
            padding: 50px 0 20px;
            margin-top: 40px;
        }
        
        footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        footer a:hover {
            color: var(--secondary);
            padding-left: 5px;
        }
        
        footer .footer-brand {
            color: white;
            font-weight: 700;
            font-size: 24px;
        }
        
        footer .footer-brand span {
            color: var(--secondary);
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            margin: 0 5px;
            transition: all 0.3s;
            color: white;
            text-decoration: none;
        }
        
        .social-icons a:hover {
            background: var(--secondary);
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 8px;
        }
        
        .footer-links li a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links li a:hover {
            color: var(--secondary);
            padding-left: 5px;
        }
        
        .footer-divider {
            border-color: rgba(255,255,255,0.08);
            margin: 20px 0;
        }
        
        .footer-bottom {
            opacity: 0.6;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- ============================================ -->
    <!-- NAVBAR -->
    <!-- ============================================ -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                <i class="fas fa-graduation-cap"></i> English <span>Course</span>
            </a>
            
            <!-- Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navbar Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <!-- User Info (Mobile) -->
                    <?php if(isLoggedIn()): ?>
                    <li class="user-info-mobile w-100">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="user-name">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                        </span>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Menu Items -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>index.php">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/materi.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/materi.php">
                            <i class="fas fa-book"></i> Materi
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/latihan_soal.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/latihan_soal.php">
                            <i class="fas fa-puzzle-piece"></i> Latihan
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/toefl.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/toefl.php">
                            <i class="fas fa-graduation-cap"></i> TOEFL
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/hasil_latihan.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/hasil_latihan.php">
                            <i class="fas fa-chart-bar"></i> Hasil
                        </a>
                    </li>
                    
                    <!-- User Actions -->
                    <?php if(isLoggedIn()): ?>
                        <!-- Admin Link -->
                        <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>admin/index.php">
                                <i class="fas fa-cog"></i> Dashboard
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar" style="display: inline-flex; width: 32px; height: 32px; font-size: 12px;">
                                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <span class="user-name d-none d-lg-inline">
                                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <span class="dropdown-item-text" style="font-weight: 600; color: #1B2A4A;">
                                        <i class="fas fa-user-circle"></i> 
                                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                                    </span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/materi.php">
                                        <i class="fas fa-book"></i> Materi Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/hasil_latihan.php">
                                        <i class="fas fa-chart-bar"></i> Hasil Latihan
                                    </a>
                                </li>
                                <?php if(isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/index.php">
                                        <i class="fas fa-cog"></i> Admin Panel
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php" style="color: #dc3545;">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                    <?php else: ?>
                        <!-- Login & Register (Not Logged In) -->
                        <li class="nav-item">
                            <a class="btn btn-outline-custom me-2" href="<?php echo BASE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-custom" href="<?php echo BASE_URL; ?>register.php">
                                <i class="fas fa-user-plus"></i> Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>