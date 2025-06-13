<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_booking'])) {
        $booking_id = (int)$_POST['booking_id'];
        $status = $_POST['status'];
        $payment_status = $_POST['payment_status'];
        $admin_notes = trim($_POST['admin_notes']);
        
        // Call stored procedure
        $query = "CALL updateBookingStatus(?, ?, ?, ?, @result)";
        $stmt = $db->prepare($query);
        $stmt->execute([$booking_id, $status, $payment_status, $admin_notes]);
        
        // Get result
        $result = $db->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
        
        if (strpos($result['result'], 'SUCCESS') === 0) {
            $success = 'Booking berhasil diperbarui';
        } else {
            $error = str_replace('ERROR: ', '', $result['result']);
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "b.booking_date = ?";
    $params[] = $date_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR f.name LIKE ? OR b.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get bookings
$query = "
    SELECT b.*, u.full_name, u.email, u.phone, f.name as field_name, 
           ts.start_time, ts.end_time
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN fields f ON b.field_id = f.id
    JOIN time_slots ts ON b.time_slot_id = ts.id
    $where_clause
    ORDER BY b.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-refunded { background: #f8d7da; color: #721c24; }
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
                        <a class="nav-link active" href="bookings.php">
                            <i class="fas fa-calendar-alt me-2"></i>Kelola Booking
                        </a>
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-money-bill me-2"></i>Transaksi
                        </a>
                        <a class="nav-link" href="payment-proofs.php">
                            <i class="fas fa-receipt me-2"></i>Verifikasi Transfer
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Kelola Booking</h2>
                        <button class="btn btn-success" onclick="backupDatabase()">
                            <i class="fas fa-download me-2"></i>Backup Database
                        </button>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="table-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date" class="form-label">Tanggal</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Cari</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Nama pengguna, lapangan, atau ID booking" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Bookings Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Pengguna</th>
                                        <th>Lapangan</th>
                                        <th>Tanggal & Waktu</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Pembayaran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Tidak ada booking ditemukan</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>#<?php echo $booking['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['field_name']); ?></td>
                                                <td>
                                                    <?php echo formatDate($booking['booking_date']); ?><br>
                                                    <small><?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['end_time']); ?></small>
                                                </td>
                                                <td><?php echo formatCurrency($booking['total_price']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge payment-<?php echo $booking['payment_status']; ?>">
                                                        <?php echo ucfirst($booking['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Booking Modal -->
    <div class="modal fade" id="editBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_booking_id" name="booking_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ID Booking</label>
                                <input type="text" class="form-control" id="edit_booking_display_id" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pengguna</label>
                                <input type="text" class="form-control" id="edit_user_name" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Lapangan</label>
                                <input type="text" class="form-control" id="edit_field_name" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal & Waktu</label>
                                <input type="text" class="form-control" id="edit_datetime" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status Booking</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_payment_status" class="form-label">Status Pembayaran</label>
                                <select class="form-select" id="edit_payment_status" name="payment_status" required>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catatan User</label>
                            <textarea class="form-control" id="edit_user_notes" rows="2" readonly></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_admin_notes" class="form-label">Catatan Admin</label>
                            <textarea class="form-control" id="edit_admin_notes" name="admin_notes" rows="3" 
                                      placeholder="Tambahkan catatan admin..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_booking" class="btn btn-primary">Update Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBooking(booking) {
            document.getElementById('edit_booking_id').value = booking.id;
            document.getElementById('edit_booking_display_id').value = '#' + booking.id;
            document.getElementById('edit_user_name').value = booking.full_name;
            document.getElementById('edit_field_name').value = booking.field_name;
            document.getElementById('edit_datetime').value = formatDate(booking.booking_date) + ' ' + 
                formatTime(booking.start_time) + ' - ' + formatTime(booking.end_time);
            document.getElementById('edit_status').value = booking.status;
            document.getElementById('edit_payment_status').value = booking.payment_status;
            document.getElementById('edit_user_notes').value = booking.notes || '';
            document.getElementById('edit_admin_notes').value = booking.admin_notes || '';
            
            new bootstrap.Modal(document.getElementById('editBookingModal')).show();
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('id-ID');
        }
        
        function formatTime(timeStr) {
            return timeStr.substring(0, 5);
        }
    </script>
    <script>
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
