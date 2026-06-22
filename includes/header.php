<?php
// Tidak perlu define ulang, karena sudah di database.php
// Tapi pastikan file ini selalu di-include setelah database.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Course - Belajar Bahasa Inggris Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1B2A4A;
            --secondary: #F4B41A;
            --white: #FFFFFF;
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
        
        .navbar {
            background: var(--primary) !important;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: var(--white) !important;
            font-weight: 700;
            font-size: 24px;
        }
        
        .navbar-brand span {
            color: var(--secondary);
        }
        
        .nav-link {
            color: var(--white) !important;
            font-weight: 500;
            transition: all 0.3s;
            margin: 0 10px;
        }
        
        .nav-link:hover {
            color: var(--secondary) !important;
        }
        
        .btn-custom {
            background: var(--secondary);
            color: var(--primary);
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 180, 26, 0.4);
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
        }
        
        .btn-outline-custom:hover {
            background: var(--secondary);
            color: var(--primary);
        }
        
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        footer {
            background: var(--primary);
            color: var(--white);
            padding: 40px 0 20px;
        }
        
        footer a {
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        footer a:hover {
            color: var(--secondary);
        }
        
        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: var(--secondary);
            color: var(--primary);
        }
        
        .navbar .nav-link.active {
            color: var(--secondary) !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                English <span>Course</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/materi.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/materi.php">Materi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/latihan_soal.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/latihan_soal.php">Latihan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/hasil_latihan.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>pages/hasil_latihan.php">Hasil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'pages/kuis.php') !== false ? 'active' : ''; ?>" 
                        href="<?php echo BASE_URL; ?>pages/kuis.php">Kuis</a>
                    </li>
                    <?php if(isLoggedIn()): ?>
                        <?php if(isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/index.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <span class="nav-link" style="color: var(--secondary);">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-custom" href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-custom me-2" href="<?php echo BASE_URL; ?>login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-custom" href="<?php echo BASE_URL; ?>register.php">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>