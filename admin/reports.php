<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get date range (default to current month)
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Revenue report
$query = "SELECT hitungTotalRevenue(?, ?) as total_revenue";
$stmt = $db->prepare($query);
$stmt->execute([$date_from, $date_to]);
$revenue_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Booking statistics
$booking_stats = [];
$statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

foreach ($statuses as $status) {
    $query = "SELECT hitungBookingByStatus(?, ?, ?) as count";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $date_from, $date_to]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $booking_stats[$status] = $result['count'];
}

// Field popularity
$query = "
    SELECT f.name, COUNT(b.id) as booking_count, SUM(b.total_price) as revenue
    FROM fields f
    LEFT JOIN bookings b ON f.id = b.field_id 
        AND b.booking_date BETWEEN ? AND ?
        AND b.payment_status = 'paid'
    GROUP BY f.id, f.name
    ORDER BY booking_count DESC
";
$stmt = $db->prepare($query);
$stmt->execute([$date_from, $date_to]);
$field_popularity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daily revenue chart data
$query = "
    SELECT DATE(b.booking_date) as date, SUM(b.total_price) as daily_revenue
    FROM bookings b
    WHERE b.booking_date BETWEEN ? AND ?
    AND b.payment_status = 'paid'
    GROUP BY DATE(b.booking_date)
    ORDER BY date
";
$stmt = $db->prepare($query);
$stmt->execute([$date_from, $date_to]);
$daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top customers
$query = "
    SELECT u.full_name, u.email, COUNT(b.id) as booking_count, SUM(b.total_price) as total_spent
    FROM users u
    JOIN bookings b ON u.id = b.user_id
    WHERE b.booking_date BETWEEN ? AND ?
    AND b.payment_status = 'paid'
    GROUP BY u.id, u.full_name, u.email
    ORDER BY total_spent DESC
    LIMIT 10
";
$stmt = $db->prepare($query);
$stmt->execute([$date_from, $date_to]);
$top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f8f9fa; }
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
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.2); color: white; }
        .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.primary { border-left-color: #007bff; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.info { border-left-color: #17a2b8; }
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
                        <a class="nav-link" href="index.php">
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
                        </a>
                        <a class="nav-link active" href="reports.php">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Laporan</h2>
                        <div>
                            <button class="btn btn-success me-2" onclick="backupDatabase()">
                                <i class="fas fa-download me-2"></i>Backup Database
                            </button>
                            <button class="btn btn-success" onclick="printReport()">
                                <i class="fas fa-print me-2"></i>Print Laporan
                            </button>
                        </div>
                    </div>
                    
                    <!-- Date Filter -->
                    <div class="report-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Generate Laporan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo formatCurrency($revenue_data['total_revenue']); ?></h4>
                                        <p class="text-muted mb-0">Total Revenue</p>
                                    </div>
                                    <i class="fas fa-money-bill fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $booking_stats['confirmed']; ?></h4>
                                        <p class="text-muted mb-0">Booking Confirmed</p>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $booking_stats['pending']; ?></h4>
                                        <p class="text-muted mb-0">Booking Pending</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $booking_stats['cancelled']; ?></h4>
                                        <p class="text-muted mb-0">Booking Cancelled</p>
                                    </div>
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    
                    
                    <!-- Tables Row -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="report-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-trophy me-2"></i>
                                    Popularitas Lapangan
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Lapangan</th>
                                                <th>Booking</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($field_popularity as $field): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($field['name']); ?></td>
                                                    <td><?php echo $field['booking_count']; ?></td>
                                                    <td><?php echo formatCurrency($field['revenue']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="report-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-users me-2"></i>
                                    Top Customers
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Booking</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_customers as $customer): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                    </td>
                                                    <td><?php echo $customer['booking_count']; ?></td>
                                                    <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode($daily_revenue); ?>;
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => item.date),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(item => item.daily_revenue),
                    borderColor: '#2E8B57',
                    backgroundColor: 'rgba(46, 139, 87, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($booking_stats); ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Cancelled', 'Completed'],
                datasets: [{
                    data: [statusData.pending, statusData.confirmed, statusData.cancelled, statusData.completed],
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
        
        function printReport() {
            window.print();
        }

        function backupDatabase() {
            if (confirm('Apakah Anda yakin ingin membuat backup database?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'backup.php';
                form.target = '_blank';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'backup_database';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        }
    </script>
</body>
</html>
