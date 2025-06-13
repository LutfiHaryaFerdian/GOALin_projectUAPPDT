<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total bookings
$query = "SELECT COUNT(*) as total FROM bookings";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending bookings
$query = "SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue this month
$query = "SELECT hitungTotalRevenue(?, ?) as revenue";
$stmt = $db->prepare($query);
$stmt->execute([date('Y-m-01'), date('Y-m-t')]);
$stats['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Total fields
$query = "SELECT COUNT(*) as total FROM fields WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_fields'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending payment proofs
$query = "SELECT COUNT(*) as total FROM payment_proofs WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_proofs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent bookings
$query = "
    SELECT b.*, u.full_name, f.name as field_name, 
           ts.start_time, ts.end_time
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN fields f ON b.field_id = f.id
    JOIN time_slots ts ON b.time_slot_id = ts.id
    ORDER BY b.created_at DESC
    LIMIT 10
";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #2E8B57 0%, #228B22 100%);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.primary { border-left-color: #2E8B57; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4 class="text-center">
                            <i class="fas fa-futbol me-2"></i>
                            <?php echo SITE_NAME; ?>
                        </h4>
                        <p class="text-center mb-0 opacity-75">Admin Panel</p>
                    </div>
                    <nav class="nav flex-column p-3">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="fields.php">
                            <i class="fas fa-futbol me-2"></i>Kelola Lapangan
                        </a>
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-calendar-alt me-2"></i>Kelola Booking
                        </a>
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-money-bill me-2"></i>Transaksi
                        </a>
                        <a class="nav-link" href="payment-proofs.php">
                            <i class="fas fa-receipt me-2"></i>Verifikasi Transfer
                            <?php if ($stats['pending_proofs'] > 0): ?>
                                <span class="badge bg-warning rounded-pill ms-1"><?php echo $stats['pending_proofs']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Laporan
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Pengguna
                        </a>
                        <a class="nav-link" href="restore.php">
                            <i class="fas fa-upload me-2"></i>Restore Database
                        </a>
                        <hr class="my-3">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-2"></i>Ke Website
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Dashboard Admin</h2>
                            <p class="text-muted mb-0">Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        </div>
                        <div>
                            <button class="btn btn-success me-2" onclick="backupDatabase()">
                                <i class="fas fa-download me-2"></i>Backup Database
                            </button>
                            <div class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d F Y'); ?>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['backup_error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Terjadi kesalahan saat membuat backup database. Silakan coba lagi.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $stats['total_bookings']; ?></h3>
                                        <p class="text-muted mb-0">Total Booking</p>
                                    </div>
                                    <i class="fas fa-calendar-alt stat-icon text-primary"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $stats['pending_bookings']; ?></h3>
                                        <p class="text-muted mb-0">Booking Pending</p>
                                    </div>
                                    <i class="fas fa-clock stat-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo formatCurrency($stats['monthly_revenue']); ?></h3>
                                        <p class="text-muted mb-0">Revenue Bulan Ini</p>
                                    </div>
                                    <i class="fas fa-money-bill stat-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $stats['total_fields']; ?></h3>
                                        <p class="text-muted mb-0">Lapangan Aktif</p>
                                    </div>
                                    <i class="fas fa-futbol stat-icon text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Verification Alert -->
                    <?php if ($stats['pending_proofs'] > 0): ?>
                        <div class="alert alert-warning mb-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-receipt fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading mb-1">Bukti Pembayaran Menunggu Verifikasi</h5>
                                    <p class="mb-0">Terdapat <?php echo $stats['pending_proofs']; ?> bukti pembayaran yang menunggu verifikasi.</p>
                                </div>
                                <div class="ms-auto">
                                    <a href="payment-proofs.php?status=pending" class="btn btn-warning">
                                        <i class="fas fa-check-circle me-1"></i>Verifikasi Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Backup & Restore Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="table-card">
                                <div class="card-header bg-white border-0 p-4">
                                    <h5 class="mb-0">
                                        <i class="fas fa-database me-2"></i>
                                        Backup & Restore Database
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Backup Database</h6>
                                            <p class="text-muted">Download backup lengkap database dalam format SQL</p>
                                            <button class="btn btn-success" onclick="backupDatabase()">
                                                <i class="fas fa-download me-2"></i>Download Backup
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Restore Database</h6>
                                            <p class="text-muted">Upload dan restore database dari file backup</p>
                                            <a href="restore.php" class="btn btn-warning">
                                                <i class="fas fa-upload me-2"></i>Restore Database
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings -->
                    <div class="table-card">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Booking Terbaru
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Pengguna</th>
                                        <th>Lapangan</th>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['field_name']); ?></td>
                                            <td><?php echo formatDate($booking['booking_date']); ?></td>
                                            <td><?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['end_time']); ?></td>
                                            <td><?php echo formatCurrency($booking['total_price']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-0 p-4">
                            <a href="bookings.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>Lihat Semua Booking
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function backupDatabase() {
    if (confirm('Apakah Anda yakin ingin membuat backup database?')) {
        // Show loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Membuat Backup...';
        btn.disabled = true;
        
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'backup.php';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'backup_database';
        input.value = '1';
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        
        // Reset button after delay
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            document.body.removeChild(form);
        }, 3000);
    }
}
</script>
</body>
</html>
