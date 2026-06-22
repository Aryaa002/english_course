<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$kuis_id = isset($_GET['kuis_id']) ? (int)$_GET['kuis_id'] : 0;
if ($kuis_id == 0) {
    redirect('kuis.php');
}

// Ambil data kuis
$stmt = $pdo->prepare("SELECT * FROM kuis WHERE id = ?");
$stmt->execute([$kuis_id]);
$kuis = $stmt->fetch();

if (!$kuis) {
    redirect('kuis.php');
}

// Ambil semua nilai peserta
$stmt = $pdo->prepare("SELECT h.*, u.full_name, u.username 
                       FROM hasil_kuis h 
                       LEFT JOIN users u ON h.user_id = u.id 
                       WHERE h.kuis_id = ? 
                       ORDER BY h.skor DESC");
$stmt->execute([$kuis_id]);
$nilai_list = $stmt->fetchAll();

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
    
    .status-lulus {
        color: #28a745;
        font-weight: 700;
    }
    
    .status-tidak-lulus {
        color: #dc3545;
        font-weight: 700;
    }
    
    .badge-lulus {
        background: #d4edda;
        color: #155724;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }
    
    .badge-tidak-lulus {
        background: #f8d7da;
        color: #721c24;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }
    
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .stats-card .number {
        font-size: 32px;
        font-weight: 700;
        color: #1B2A4A;
    }
    
    .stats-card .label {
        color: #666;
        font-size: 14px;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="color: #1B2A4A; font-weight: 700;">
                    <i class="fas fa-chart-bar" style="color: #F4B41A;"></i> 
                    Nilai Kuis: <?php echo htmlspecialchars($kuis['judul']); ?>
                </h4>
                <p style="color: #666; margin: 0;">
                    Passing Grade: <strong><?php echo $kuis['passing_grade']; ?>%</strong>
                </p>
            </div>
            <a href="kuis.php" class="btn" style="background: #1B2A4A; color: white; padding: 8px 20px; border-radius: 25px;">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
        
        <!-- Statistik -->
        <?php 
        $total_peserta = count($nilai_list);
        $total_lulus = 0;
        $total_tidak_lulus = 0;
        $total_nilai = 0;
        $nilai_tertinggi = 0;
        
        foreach($nilai_list as $n) {
            if ($n['is_lulus']) {
                $total_lulus++;
            } else {
                $total_tidak_lulus++;
            }
            $total_nilai += $n['skor'];
            if ($n['skor'] > $nilai_tertinggi) {
                $nilai_tertinggi = $n['skor'];
            }
        }
        
        $rata_rata = $total_peserta > 0 ? round($total_nilai / $total_peserta, 1) : 0;
        ?>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="number"><?php echo $total_peserta; ?></div>
                    <div class="label">Total Peserta</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="number" style="color: #28a745;"><?php echo $total_lulus; ?></div>
                    <div class="label">Lulus</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="number" style="color: #dc3545;"><?php echo $total_tidak_lulus; ?></div>
                    <div class="label">Tidak Lulus</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="number" style="color: #F4B41A;"><?php echo $rata_rata; ?></div>
                    <div class="label">Rata-rata Nilai</div>
                </div>
            </div>
        </div>
        
        <!-- Tabel Nilai -->
        <div class="table-custom">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Username</th>
                        <th>Benar</th>
                        <th>Salah</th>
                        <th>Skor</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($nilai_list) > 0): ?>
                        <?php $no = 1; foreach($nilai_list as $n): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($n['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($n['username']); ?></td>
                            <td><span style="color: #28a745;"><?php echo $n['total_benar']; ?></span></td>
                            <td><span style="color: #dc3545;"><?php echo $n['total_salah']; ?></span></td>
                            <td><strong><?php echo $n['skor']; ?></strong></td>
                            <td>
                                <?php if($n['is_lulus']): ?>
                                    <span class="badge-lulus">✅ Lulus</span>
                                <?php else: ?>
                                    <span class="badge-tidak-lulus">❌ Tidak Lulus</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($n['waktu_selesai'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                                <p style="color: #999; margin-top: 10px;">Belum ada peserta yang mengerjakan kuis ini</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>