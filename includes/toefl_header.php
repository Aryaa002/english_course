<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOEFL - English Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Style khusus TOEFL */
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .toefl-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        /* Timer di pojok kanan atas */
        .toefl-timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1B2A4A;
            color: #F4B41A;
            padding: 12px 25px;
            border-radius: 12px;
            font-size: 28px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border: 2px solid rgba(244, 180, 26, 0.3);
        }
        .toefl-timer.warning {
            color: #dc3545;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .toefl-timer .label {
            font-size: 12px;
            font-weight: 400;
            color: rgba(255,255,255,0.6);
            display: block;
            text-align: center;
        }
        /* Tombol kembali */
        .toefl-back {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 9999;
            background: rgba(27, 42, 74, 0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        .toefl-back:hover {
            background: #1B2A4A;
            color: #F4B41A;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Timer -->
    <div class="toefl-timer" id="toeflTimer">
        <span class="label">Waktu Tersisa</span>
        <span id="timerDisplay">--:--</span>
    </div>
    
    <!-- Tombol Kembali -->
    <a href="toefl.php" class="toefl-back">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>