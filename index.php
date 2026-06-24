<?php
require_once 'config/database.php';

// Ambil 6 materi terbaru
$stmt = $pdo->query("SELECT * FROM materi ORDER BY created_at DESC LIMIT 6");
$materi_terbaru = $stmt->fetchAll();

// Ambil statistik
$total_materi = $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_soal = $pdo->query("SELECT COUNT(*) FROM soal_latihan")->fetchColumn();

// Ambil testimoni
$testimonials = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll();

include 'includes/header.php';
?>

<style>
/* ===== HERO SECTION ===== */
.hero-section {
    background: linear-gradient(135deg, #0f1a2e 0%, #1B2A4A 40%, #2C4066 100%);
    padding: 50px 0 30px;
    position: relative;
    overflow: hidden;
    min-height: 90vh;
    display: flex;
    align-items: center;
}

.min-vh-80 {
    min-height: 85vh;
}

/* ===== FLOATING SHAPES BACKGROUND ===== */
.hero-section .floating-shapes {
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
    opacity: 0.08;
    animation: floatShape 20s infinite ease-in-out;
}

.floating-shapes .shape:nth-child(1) {
    width: 300px;
    height: 300px;
    background: #F4B41A;
    top: -50px;
    right: -50px;
    animation-delay: 0s;
}

.floating-shapes .shape:nth-child(2) {
    width: 200px;
    height: 200px;
    background: #F4B41A;
    bottom: 100px;
    left: -50px;
    animation-delay: -5s;
}

.floating-shapes .shape:nth-child(3) {
    width: 150px;
    height: 150px;
    background: #F4B41A;
    top: 50%;
    left: 50%;
    animation-delay: -10s;
}

.floating-shapes .shape:nth-child(4) {
    width: 100px;
    height: 100px;
    background: #F4B41A;
    top: 70%;
    left: 80%;
    animation-delay: -3s;
}

@keyframes floatShape {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    25% { transform: translate(50px, -30px) scale(1.1) rotate(45deg); }
    50% { transform: translate(-30px, 50px) scale(0.9) rotate(90deg); }
    75% { transform: translate(40px, 30px) scale(1.05) rotate(135deg); }
}

/* ===== GLOW EFFECT ===== */
.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(244, 180, 26, 0.06) 0%, transparent 70%);
    border-radius: 50%;
    animation: pulseGlow 8s ease-in-out infinite;
    z-index: 0;
}

.hero-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(244, 180, 26, 0.04) 0%, transparent 70%);
    border-radius: 50%;
    animation: pulseGlow 10s ease-in-out infinite reverse;
    z-index: 0;
}

@keyframes pulseGlow {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 1; }
}

/* ===== HERO CONTENT ===== */
.hero-content {
    position: relative;
    z-index: 1;
}

.hero-content .badge-hero {
    display: inline-block;
    background: rgba(244, 180, 26, 0.15);
    color: #F4B41A;
    padding: 6px 18px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
    border: 1px solid rgba(244, 180, 26, 0.2);
}

.hero-content h1 {
    font-size: 48px;
    font-weight: 800;
    color: white;
    line-height: 1.1;
    margin: 15px 0;
}

.hero-content h1 span {
    color: #F4B41A;
    position: relative;
}

.hero-content h1 span::after {
    content: '';
    position: absolute;
    bottom: 5px;
    left: 0;
    right: 0;
    height: 6px;
    background: rgba(244, 180, 26, 0.2);
    border-radius: 4px;
}

.hero-content .subtitle {
    font-size: 17px;
    color: rgba(255,255,255,0.7);
    line-height: 1.8;
    max-width: 480px;
    margin-bottom: 25px;
}

.hero-content .btn-primary-custom {
    background: #F4B41A;
    color: #1B2A4A;
    padding: 12px 35px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 15px;
    border: none;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.hero-content .btn-primary-custom:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 10px 30px rgba(244, 180, 26, 0.4);
    color: #1B2A4A;
}

.hero-content .btn-outline-custom {
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px 35px;
    border-radius: 50px;
    font-weight: 600;
    background: transparent;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.hero-content .btn-outline-custom:hover {
    background: rgba(255,255,255,0.1);
    border-color: white;
    transform: translateY(-3px);
}

.hero-stats {
    display: flex;
    gap: 30px;
    margin-top: 30px;
}

.hero-stats .stat-item {
    text-align: center;
}

.hero-stats .stat-item .number {
    font-size: 28px;
    font-weight: 700;
    color: #F4B41A;
    display: block;
    transition: all 0.3s;
}

.hero-stats .stat-item .number:hover {
    transform: scale(1.1);
}

.hero-stats .stat-item .label {
    color: rgba(255,255,255,0.6);
    font-size: 13px;
}

/* ===== HERO IMAGE ===== */
.hero-image {
    position: relative;
    z-index: 1;
}

.hero-image .image-wrapper {
    position: relative;
    padding: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Main Card */
.main-card {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 30px;
    padding: 30px 35px;
    text-align: center;
    max-width: 420px;
    width: 100%;
    position: relative;
    z-index: 2;
    transition: all 0.4s;
}

.main-card:hover {
    transform: translateY(-5px);
    border-color: rgba(244, 180, 26, 0.2);
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.main-card .main-card-icon {
    font-size: 60px;
    color: #F4B41A;
    margin-bottom: 15px;
    animation: pulseIcon 3s ease-in-out infinite;
}

@keyframes pulseIcon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.main-card .main-card-text {
    color: white;
    font-size: 18px;
    font-weight: 300;
    line-height: 1.6;
    margin-bottom: 20px;
}

.main-card .main-card-stats {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.main-card .main-card-stats .stat-chip {
    background: rgba(255,255,255,0.06);
    padding: 8px 16px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.06);
    min-width: 70px;
    transition: all 0.3s;
}

.main-card .main-card-stats .stat-chip:hover {
    background: rgba(244, 180, 26, 0.1);
    border-color: rgba(244, 180, 26, 0.2);
    transform: translateY(-3px);
}

.main-card .main-card-stats .stat-chip .stat-number {
    color: #F4B41A;
    font-size: 16px;
    font-weight: 700;
    display: block;
}

.main-card .main-card-stats .stat-chip .stat-label {
    color: rgba(255,255,255,0.5);
    font-size: 11px;
}

/* Floating Cards */
.hero-image .image-wrapper .floating-card {
    position: absolute;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 12px 16px;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: floatCard 4s ease-in-out infinite;
    z-index: 3;
    transition: all 0.3s;
}

.hero-image .image-wrapper .floating-card:hover {
    transform: translateY(-8px) scale(1.05);
    border-color: rgba(244, 180, 26, 0.3);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.floating-card .icon {
    width: 35px;
    height: 35px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.floating-card .icon.gold {
    background: rgba(244, 180, 26, 0.2);
    color: #F4B41A;
}

.floating-card .icon.green {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.floating-card .icon.blue {
    background: rgba(23, 162, 184, 0.2);
    color: #17a2b8;
}

.floating-card .text small {
    display: block;
    font-size: 11px;
    opacity: 0.6;
}

.floating-card .text strong {
    font-size: 13px;
}

/* Floating Card Positions */
.floating-card.fc-1 {
    top: -10px;
    right: -20px;
    animation-delay: 0s;
}

.floating-card.fc-2 {
    bottom: 30px;
    left: -30px;
    animation-delay: -2s;
}

.floating-card.fc-3 {
    top: 50%;
    right: -50px;
    animation-delay: -4s;
    transform: translateY(-50%);
}

@keyframes floatCard {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-12px); }
}

.floating-card.fc-3 {
    animation: floatCardRight 4s ease-in-out infinite;
}

@keyframes floatCardRight {
    0%, 100% { transform: translateY(-50%) translateX(0); }
    50% { transform: translateY(-55%) translateX(5px); }
}

/* ===== AOS ANIMATION OVERRIDE ===== */
[data-aos] {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease-out;
}

[data-aos].aos-animate {
    opacity: 1;
    transform: translateY(0);
}

[data-aos="fade-left"] {
    transform: translateX(-30px);
}

[data-aos="fade-left"].aos-animate {
    transform: translateX(0);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 991px) {
    .hero-content h1 {
        font-size: 36px;
    }
    .hero-image .image-wrapper .floating-card {
        display: none;
    }
    .hero-stats {
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .main-card {
        padding: 20px;
        max-width: 100%;
    }
    .hero-section {
        padding: 40px 0 20px;
        min-height: auto;
    }
    .min-vh-80 {
        min-height: auto;
    }
    .hero-section::before,
    .hero-section::after {
        display: none;
    }
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 28px;
    }
    .hero-content .subtitle {
        font-size: 15px;
    }
    .hero-stats .stat-item .number {
        font-size: 22px;
    }
    .hero-content .btn-primary-custom,
    .hero-content .btn-outline-custom {
        padding: 10px 25px;
        font-size: 14px;
        width: 100%;
        text-align: center;
    }
    .hero-content .btn-group-custom {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .main-card .main-card-text {
        font-size: 16px;
    }
    .main-card .main-card-stats .stat-chip {
        padding: 6px 12px;
        min-width: 60px;
    }
    .main-card .main-card-icon {
        font-size: 45px;
    }
    .floating-card.fc-1 {
        top: -5px;
        right: -10px;
        padding: 8px 12px;
    }
    .floating-card.fc-2 {
        bottom: 20px;
        left: -15px;
        padding: 8px 12px;
    }
    .floating-card.fc-3 {
        display: none;
    }
}

@media (max-width: 576px) {
    .hero-section {
        padding: 20px 0 15px;
    }
    .hero-content h1 {
        font-size: 24px;
    }
    .hero-content .badge-hero {
        font-size: 11px;
        padding: 4px 14px;
    }
    .main-card .main-card-icon {
        font-size: 40px;
    }
    .main-card .main-card-stats {
        gap: 8px;
    }
    .main-card .main-card-stats .stat-chip .stat-number {
        font-size: 14px;
    }
    .floating-card.fc-1,
    .floating-card.fc-2 {
        display: none;
    }
    .hero-stats {
        gap: 15px;
    }
    .hero-stats .stat-item .number {
        font-size: 20px;
    }
    .hero-stats .stat-item .label {
        font-size: 11px;
    }
}

/* ===== FEATURES SECTION ===== */
.features-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header .badge-section {
    display: inline-block;
    background: rgba(244, 180, 26, 0.1);
    color: #F4B41A;
    padding: 5px 20px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
}

.section-header h2 {
    font-size: 38px;
    font-weight: 700;
    color: #1B2A4A;
    margin: 10px 0;
}

.section-header h2 span {
    color: #F4B41A;
    position: relative;
}

.section-header h2 span::after {
    content: '';
    position: absolute;
    bottom: 5px;
    left: 0;
    right: 0;
    height: 6px;
    background: rgba(244, 180, 26, 0.2);
    border-radius: 4px;
}

.section-header p {
    color: #666;
    font-size: 17px;
    max-width: 550px;
    margin: 0 auto;
}

/* ===== FEATURE CARDS ===== */
.feature-card {
    background: white;
    border-radius: 20px;
    padding: 35px 25px;
    text-align: center;
    box-shadow: 0 5px 30px rgba(0,0,0,0.05);
    transition: all 0.4s;
    height: 100%;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.03);
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #F4B41A, #1B2A4A);
    transform: scaleX(0);
    transition: transform 0.4s;
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
}

.feature-card .icon-box {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 32px;
    transition: all 0.4s;
}

.feature-card:hover .icon-box {
    transform: scale(1.1) rotate(-5deg);
}

.feature-card .icon-box.gold {
    background: rgba(244, 180, 26, 0.1);
    color: #F4B41A;
}

.feature-card .icon-box.blue {
    background: rgba(27, 42, 74, 0.1);
    color: #1B2A4A;
}

.feature-card .icon-box.green {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.feature-card .icon-box.red {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.feature-card h5 {
    color: #1B2A4A;
    font-weight: 700;
    margin-bottom: 12px;
    font-size: 18px;
}

.feature-card p {
    color: #666;
    font-size: 14px;
    line-height: 1.7;
    margin: 0;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .features-section {
        padding: 50px 0;
    }
    .section-header h2 {
        font-size: 28px;
    }
    .section-header p {
        font-size: 15px;
        padding: 0 15px;
    }
    .feature-card {
        padding: 25px 20px;
    }
    .feature-card .icon-box {
        width: 60px;
        height: 60px;
        font-size: 24px;
    }
    .feature-card h5 {
        font-size: 16px;
    }
}

@media (max-width: 576px) {
    .section-header h2 {
        font-size: 24px;
    }
    .section-header .badge-section {
        font-size: 11px;
        padding: 4px 14px;
    }
    .feature-card {
        padding: 20px 15px;
    }
    .feature-card .icon-box {
        width: 50px;
        height: 50px;
        font-size: 20px;
        margin-bottom: 15px;
    }
}
    
    /* ===== MATERI SECTION ===== */
    .materi-section {
        padding: 80px 0;
        background: white;
    }
    
    .materi-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 30px rgba(0,0,0,0.05);
        transition: all 0.4s;
        height: 100%;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .materi-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    }
    
    .materi-card .card-header-custom {
        padding: 20px 25px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .materi-card .card-header-custom .kategori {
        background: #1B2A4A;
        color: #F4B41A;
        padding: 4px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .materi-card .card-header-custom .tingkat {
        background: rgba(244, 180, 26, 0.1);
        color: #F4B41A;
        padding: 4px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .materi-card .card-body-custom {
        padding: 20px 25px;
    }
    
    .materi-card .card-body-custom h5 {
        color: #1B2A4A;
        font-weight: 700;
        margin-bottom: 10px;
        font-size: 18px;
    }
    
    .materi-card .card-body-custom p {
        color: #666;
        font-size: 14px;
        line-height: 1.7;
        margin-bottom: 15px;
    }
    
    .materi-card .card-footer-custom {
        padding: 15px 25px;
        background: #f8f9fa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    
    .materi-card .card-footer-custom .durasi {
        color: #999;
        font-size: 13px;
    }
    
    .materi-card .card-footer-custom .durasi i {
        color: #F4B41A;
        margin-right: 5px;
    }
    
    .materi-card .card-footer-custom .btn-learn {
        background: #1B2A4A;
        color: white;
        padding: 6px 20px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .materi-card .card-footer-custom .btn-learn:hover {
        background: #F4B41A;
        color: #1B2A4A;
        transform: translateX(3px);
    }
    
    .view-all-btn {
        display: inline-block;
        color: #1B2A4A;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        border-bottom: 2px solid transparent;
    }
    
    .view-all-btn:hover {
        color: #F4B41A;
        border-bottom-color: #F4B41A;
    }
    
    /* ===== TESTIMONI SECTION ===== */
    .testimoni-section {
        padding: 80px 0;
        background: #f8f9fa;
    }
    
    .testimoni-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.05);
        transition: all 0.4s;
        height: 100%;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .testimoni-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    }
    
    .testimoni-card .rating {
        color: #F4B41A;
        margin-bottom: 15px;
    }
    
    .testimoni-card .testimoni-text {
        color: #444;
        font-size: 15px;
        line-height: 1.8;
        font-style: italic;
        margin-bottom: 20px;
    }
    
    .testimoni-card .testimoni-author {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .testimoni-card .testimoni-author .avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #1B2A4A;
        color: #F4B41A;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 20px;
    }
    
    .testimoni-card .testimoni-author .info .name {
        font-weight: 600;
        color: #1B2A4A;
        margin: 0;
    }
    
    .testimoni-card .testimoni-author .info .profesi {
        color: #999;
        font-size: 13px;
        margin: 0;
    }
    
    /* ===== CTA SECTION ===== */
    .cta-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #0f1a2e 0%, #1B2A4A 100%);
        position: relative;
        overflow: hidden;
    }
    
    .cta-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(244, 180, 26, 0.1) 0%, transparent 70%);
        border-radius: 50%;
        animation: pulseGlow 6s ease-in-out infinite;
    }
    
    .cta-section .cta-content {
        position: relative;
        z-index: 1;
        text-align: center;
    }
    
    .cta-section .cta-content h2 {
        font-size: 38px;
        font-weight: 700;
        color: white;
        margin-bottom: 15px;
    }
    
    .cta-section .cta-content h2 span {
        color: #F4B41A;
    }
    
    .cta-section .cta-content p {
        color: rgba(255,255,255,0.7);
        font-size: 17px;
        max-width: 550px;
        margin: 0 auto 30px;
    }
    
    .cta-section .cta-content .btn-cta {
        background: #F4B41A;
        color: #1B2A4A;
        padding: 16px 50px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 17px;
        border: none;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    
    .cta-section .cta-content .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 40px rgba(244, 180, 26, 0.3);
        color: #1B2A4A;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 991px) {
        .hero-content h1 {
            font-size: 40px;
        }
        .hero-image .image-wrapper .floating-card {
            display: none;
        }
        .hero-stats {
            gap: 20px;
            flex-wrap: wrap;
        }
    }
    
    @media (max-width: 768px) {
        .hero-section {
            padding: 60px 0;
            min-height: auto;
        }
        .hero-content h1 {
            font-size: 32px;
        }
        .hero-content .subtitle {
            font-size: 16px;
        }
        .hero-stats .stat-item .number {
            font-size: 24px;
        }
        .section-header h2 {
            font-size: 28px;
        }
        .feature-card {
            padding: 25px 20px;
        }
        .hero-content .btn-primary-custom,
        .hero-content .btn-outline-custom {
            padding: 12px 25px;
            font-size: 14px;
            width: 100%;
            text-align: center;
        }
        .hero-content .btn-group-custom {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<!-- ============================================================ -->
<!-- HERO SECTION - DENGAN ANIMASI LENGKAP -->
<!-- ============================================================ -->
<section class="hero-section">
    <!-- Floating Shapes Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape" style="width: 100px; height: 100px; top: 70%; left: 80%; animation-delay: -3s;"></div>
    </div>
    
    <div class="container">
        <div class="row align-items-center min-vh-80">
            <!-- Left Content -->
            <div class="col-lg-6 hero-content">
                <div class="badge-hero" data-aos="fade-up" data-aos-duration="800">
                    <i class="fas fa-sparkles"></i> Platform Belajar #1
                </div>
                
                <h1 data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                    Tingkatkan <br>
                    Kemampuan <span>Bahasa Inggris</span> Anda
                </h1>
                
                <p class="subtitle" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                    Belajar bahasa Inggris dengan materi berkualitas, latihan interaktif, 
                    dan persiapan TOEFL. Mulai perjalanan belajar Anda sekarang!
                </p>
                
                <div class="btn-group-custom" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
                    <?php if(!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn-primary-custom me-3 mb-2 mb-sm-0">
                            <i class="fas fa-rocket me-2"></i>Mulai Belajar
                        </a>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn-outline-custom">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>pages/materi.php" class="btn-primary-custom">
                            <i class="fas fa-book-open me-2"></i>Lihat Materi
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="hero-stats" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                    <div class="stat-item">
                        <span class="number" data-count="<?php echo $total_materi; ?>">0</span>
                        <span class="label">Materi</span>
                    </div>
                    <div class="stat-item">
                        <span class="number" data-count="<?php echo $total_users; ?>">0</span>
                        <span class="label">Siswa Aktif</span>
                    </div>
                    <div class="stat-item">
                        <span class="number" data-count="<?php echo $total_soal; ?>">0</span>
                        <span class="label">Soal Latihan</span>
                    </div>
                </div>
            </div>
            
            <!-- Right Content - Image dengan Animasi -->
            <div class="col-lg-6 hero-image" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                <div class="image-wrapper">
                    <!-- Main Card -->
                    <div class="main-card">
                        <div class="main-card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="main-card-text">
                            "Belajar bahasa Inggris <br>
                            <span style="color: #F4B41A; font-weight: 700;">menjadi lebih mudah</span> <br>
                            dan menyenangkan"
                        </div>
                        <div class="main-card-stats">
                            <div class="stat-chip">
                                <span class="stat-number">12+</span>
                                <span class="stat-label">Materi</span>
                            </div>
                            <div class="stat-chip">
                                <span class="stat-number">100+</span>
                                <span class="stat-label">Soal</span>
                            </div>
                            <div class="stat-chip">
                                <span class="stat-number">24/7</span>
                                <span class="stat-label">Akses</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating Cards dengan Animasi -->
                    <div class="floating-card fc-1" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                        <div class="icon gold"><i class="fas fa-star"></i></div>
                        <div class="text">
                            <strong>Rating 4.9/5</strong>
                            <small>Dari 500+ siswa</small>
                        </div>
                    </div>
                    <div class="floating-card fc-2" data-aos="fade-up" data-aos-duration="800" data-aos-delay="500">
                        <div class="icon green"><i class="fas fa-check-circle"></i></div>
                        <div class="text">
                            <strong>100% Gratis</strong>
                            <small>Akses penuh</small>
                        </div>
                    </div>
                    <div class="floating-card fc-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
                        <div class="icon blue"><i class="fas fa-certificate"></i></div>
                        <div class="text">
                            <strong>Sertifikat</strong>
                            <small>Setelah selesai</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- FEATURES SECTION -->
<!-- ============================================================ -->
<section class="features-section" id="fitur">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="badge-section"><i class="fas fa-sparkles"></i> Mengapa Kami?</span>
            <h2>Kenapa Belajar di <span>English Course?</span></h2>
            <p>Kami menyediakan pengalaman belajar yang lengkap dengan berbagai fitur unggulan</p>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-card">
                    <div class="icon-box gold">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h5>Materi Lengkap</h5>
                    <p>12+ materi dari Grammar, Vocabulary, Speaking, hingga persiapan TOEFL dengan konten yang update</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card">
                    <div class="icon-box blue">
                        <i class="fas fa-puzzle-piece"></i>
                    </div>
                    <h5>Latihan Interaktif</h5>
                    <p>100+ soal latihan dan kuis yang dirancang untuk menguji pemahaman Anda secara bertahap</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-card">
                    <div class="icon-box green">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h5>Persiapan TOEFL</h5>
                    <p>Simulasi TOEFL lengkap dengan listening, structure, reading, dan writing untuk persiapan ujian</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-card">
                    <div class="icon-box red">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h5>Media Interaktif</h5>
                    <p>Materi dilengkapi dengan audio dan video untuk mendukung pembelajaran listening dan speaking</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-card">
                    <div class="icon-box gold">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Pelacakan Kemajuan</h5>
                    <p>Pantau perkembangan belajar Anda melalui hasil latihan dan nilai kuis yang tersimpan</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-card">
                    <div class="icon-box blue">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h5>Sertifikat</h5>
                    <p>Dapatkan sertifikat setelah menyelesaikan semua materi dan lulus ujian TOEFL</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- MATERI TERBARU SECTION -->
<!-- ============================================================ -->
<!-- Materi Terbaru Section -->
<section style="padding: 80px 0;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #1B2A4A; font-weight: 700; font-size: 32px;">
                Materi <span style="color: #F4B41A;">Terbaru</span>
            </h2>
            <a href="<?php echo BASE_URL; ?>pages/materi.php" style="color: #F4B41A; text-decoration: none; font-weight: 600;">
                Lihat Semua <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row">
            <?php foreach($materi_terbaru as $materi): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; transition: transform 0.3s; cursor: pointer;">
                    <div class="card-body" style="padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <span style="background: #1B2A4A; color: #F4B41A; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?php echo htmlspecialchars($materi['kategori']); ?>
                            </span>
                            <span style="background: #F4B41A20; color: #1B2A4A; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?php echo htmlspecialchars($materi['tingkat']); ?>
                            </span>
                        </div>
                        <h5 style="color: #1B2A4A; font-weight: 600; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($materi['judul']); ?>
                        </h5>
                        <p style="color: #666; font-size: 14px;">
                            <?php echo substr(htmlspecialchars($materi['deskripsi']), 0, 100) . '...'; ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                            <span style="color: #1B2A4A; font-size: 14px;">
                                <i class="fas fa-signal" style="color: #F4B41A;"></i> <?php echo htmlspecialchars($materi['tingkat']); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>pages/detail_materi.php?id=<?php echo $materi['id']; ?>" 
                               style="background: #F4B41A; color: #1B2A4A; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
                                Pelajari
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>  

<!-- ============================================================ -->
<!-- TESTIMONI SECTION -->
<!-- ============================================================ -->
<?php if(count($testimonials) > 0): ?>
<section class="testimoni-section" id="testimoni">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="badge-section"><i class="fas fa-quote-left"></i> Testimoni</span>
            <h2>Apa Kata <span>Mereka?</span></h2>
            <p>Pengalaman siswa yang telah belajar di English Course</p>
        </div>
        
        <div class="row">
            <?php foreach($testimonials as $index => $t): ?>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo ($index * 150) + 100; ?>">
                <div class="testimoni-card">
                    <div class="rating">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $t['rating'] ? '' : 'text-muted'; ?>" 
                               style="<?php echo $i <= $t['rating'] ? 'color: #F4B41A;' : 'opacity: 0.2;'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="testimoni-text">"<?php echo htmlspecialchars($t['testimoni']); ?>"</p>
                    <div class="testimoni-author">
                        <div class="avatar">
                            <?php echo strtoupper(substr($t['nama'], 0, 1)); ?>
                        </div>
                        <div class="info">
                            <p class="name"><?php echo htmlspecialchars($t['nama']); ?></p>
                            <p class="profesi"><?php echo htmlspecialchars($t['profesi']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- CTA SECTION -->
<!-- ============================================================ -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content" data-aos="fade-up">
            <h2>Siap Meningkatkan <span>Kemampuan Inggris</span> Anda?</h2>
            <p>Bergabunglah sekarang dan mulai perjalanan belajar bahasa Inggris dengan cara yang menyenangkan</p>
            <?php if(!isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn-cta">
                    <i class="fas fa-rocket me-2"></i>Daftar Sekarang
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>pages/materi.php" class="btn-cta">
                    <i class="fas fa-book-open me-2"></i>Mulai Belajar
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- ============================================================ -->
<!-- ADDITIONAL SCRIPTS -->
<!-- ============================================================ -->
<script>
    // =============================================
    // ANIMASI COUNTER
    // =============================================
    (function() {
        const counters = document.querySelectorAll('.hero-stats .stat-item .number');
        
        const animateCounter = (element) => {
            const target = parseInt(element.getAttribute('data-count'));
            const duration = 2000;
            const step = Math.max(1, Math.floor(target / 60));
            let current = 0;
            
            const updateCounter = () => {
                current += step;
                if (current >= target) {
                    element.textContent = target;
                    return;
                }
                element.textContent = current;
                requestAnimationFrame(updateCounter);
            };
            
            updateCounter();
        };
        
        // Trigger counter when element is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.number');
                    counters.forEach(counter => {
                        animateCounter(counter);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        document.querySelectorAll('.hero-stats').forEach(stat => {
            observer.observe(stat);
        });
    })();
    
    // =============================================
    // ANIMASI ON SCROLL (AOS Style)
    // =============================================
    (function() {
        const elements = document.querySelectorAll('[data-aos]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        elements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.8s ease-out';
            observer.observe(el);
        });
    })();
    
    // =============================================
    // SMOOTH SCROLL NAVIGATION
    // =============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // =============================================
    // PARALLAX EFFECT ON HERO
    // =============================================
    document.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const hero = document.querySelector('.hero-section');
        if (hero) {
            hero.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });
    
    console.log('✨ English Course - Website Belajar Bahasa Inggris');
    console.log('📚 Selamat belajar dan semoga sukses!');
</script>