<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_payment'])) {
        $proof_id = (int)$_POST['proof_id'];
        $booking_id = (int)$_POST['booking_id'];
        $status = $_POST['status']; // 'verified' or 'rejected'
        $admin_notes = trim($_POST['admin_notes']);
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Update payment proof status
            $query = "UPDATE payment_proofs SET status = ?, admin_notes = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$status, $admin_notes, $_SESSION['user_id'], $proof_id]);
            
            // If verified, update booking payment status
            if ($status == 'verified') {
                $query = "UPDATE bookings SET payment_status = 'paid' WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$booking_id]);
            }
            
            $db->commit();
            $success = 'Bukti pembayaran berhasil ' . ($status == 'verified' ? 'diverifikasi' : 'ditolak');
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Gagal memproses verifikasi: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "pp.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "pp.upload_date >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where_conditions[] = "pp.upload_date <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get payment proofs
$query = "
    SELECT pp.*, b.id as booking_id, b.booking_date, b.total_price, b.payment_status,
           u.full_name, u.email, f.name as field_name, 
           ts.start_time, ts.end_time,
           a.full_name as admin_name
    FROM payment_proofs pp
    JOIN bookings b ON pp.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN fields f ON b.field_id = f.id
    JOIN time_slots ts ON b.time_slot_id = ts.id
    LEFT JOIN users a ON pp.verified_by = a.id
    $where_clause
    ORDER BY pp.upload_date DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$payment_proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by status
$query = "SELECT status, COUNT(*) as count FROM payment_proofs GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$status_counts = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $status_counts[$row['status']] = $row['count'];
}
$pending_count = $status_counts['pending'] ?? 0;
$verified_count = $status_counts['verified'] ?? 0;
$rejected_count = $status_counts['rejected'] ?? 0;
$total_count = $pending_count + $verified_count + $rejected_count;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - <?php echo SITE_NAME; ?></title>
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.info { border-left-color: #17a2b8; }
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
        .status-verified { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .payment-proof-img {
            max-height: 300px;
            object-fit: contain;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .payment-proof-pdf {
            width: 100%;
            height: 300px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
        }
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
                        <a class="nav-link active" href="payment-proofs.php">
                            <i class="fas fa-receipt me-2"></i>Verifikasi Transfer
                            <?php if ($pending_count > 0): ?>
                                <span class="badge bg-warning rounded-pill ms-1"><?php echo $pending_count; ?></span>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Verifikasi Pembayaran</h2>
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
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $pending_count; ?></h4>
                                        <p class="text-muted mb-0">Menunggu Verifikasi</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $verified_count; ?></h4>
                                        <p class="text-muted mb-0">Terverifikasi</p>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $rejected_count; ?></h4>
                                        <p class="text-muted mb-0">Ditolak</p>
                                    </div>
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1"><?php echo $total_count; ?></h4>
                                        <p class="text-muted mb-0">Total Bukti Pembayaran</p>
                                    </div>
                                    <i class="fas fa-receipt fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="table-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                                        <option value="verified" <?php echo $status_filter == 'verified' ? 'selected' : ''; ?>>Terverifikasi</option>
                                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Filter
                                        </button>
                                        <a href="payment-proofs.php" class="btn btn-secondary">
                                            <i class="fas fa-sync-alt me-1"></i>Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Payment Proofs Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Pengguna</th>
                                        <th>Booking</th>
                                        <th>Tanggal Upload</th>
                                        <th>Total Bayar</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payment_proofs)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Tidak ada bukti pembayaran ditemukan</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payment_proofs as $proof): ?>
                                            <tr>
                                                <td>#<?php echo $proof['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($proof['full_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($proof['email']); ?></small>
                                                </td>
                                                <td>
                                                    <strong>#<?php echo $proof['booking_id']; ?> - <?php echo htmlspecialchars($proof['field_name']); ?></strong><br>
                                                    <small><?php echo formatDate($proof['booking_date']); ?> <?php echo formatTime($proof['start_time']) . ' - ' . formatTime($proof['end_time']); ?></small>
                                                </td>
                                                <td><?php echo formatDateTime($proof['upload_date']); ?></td>
                                                <td><?php echo formatCurrency($proof['total_price']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $proof['status']; ?>">
                                                        <?php 
                                                        if ($proof['status'] == 'pending') echo 'Menunggu Verifikasi';
                                                        elseif ($proof['status'] == 'verified') echo 'Terverifikasi';
                                                        elseif ($proof['status'] == 'rejected') echo 'Ditolak';
                                                        ?>
                                                    </span>
                                                    <?php if ($proof['status'] != 'pending'): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            oleh <?php echo htmlspecialchars($proof['admin_name']); ?>
                                                            <br>
                                                            <?php echo formatDateTime($proof['verified_at']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewProof(<?php echo htmlspecialchars(json_encode($proof)); ?>)">
                                                        <i class="fas fa-eye"></i>
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

    <!-- View Payment Proof Modal -->
    <div class="modal fade" id="viewProofModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Informasi Booking</h6>
                            <p class="mb-1">
                                <strong>ID Booking:</strong> <span id="view_booking_id"></span>
                            </p>
                            <p class="mb-1">
                                <strong>Lapangan:</strong> <span id="view_field_name"></span>
                            </p>
                            <p class="mb-1">
                                <strong>Tanggal & Waktu:</strong> <span id="view_datetime"></span>
                            </p>
                            <p class="mb-1">
                                <strong>Total Bayar:</strong> <span id="view_total_price"></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Pengguna</h6>
                            <p class="mb-1">
                                <strong>Nama:</strong> <span id="view_user_name"></span>
                            </p>
                            <p class="mb-1">
                                <strong>Email:</strong> <span id="view_user_email"></span>
                            </p>
                            <p class="mb-1">
                                <strong>Tanggal Upload:</strong> <span id="view_upload_date"></span>
                            </p>
                            <p class="mb-1">
                                <strong>Status:</strong> <span id="view_status"></span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Bukti Pembayaran</h6>
                        <div id="proof_container" class="text-center">
                            <!-- Proof image or PDF will be displayed here -->
                        </div>
                    </div>
                    
                    <div id="verification_form" class="mt-4">
                        <form method="POST">
                            <input type="hidden" id="proof_id" name="proof_id">
                            <input type="hidden" id="booking_id" name="booking_id">
                            
                            <div class="mb-3">
                                <label class="form-label">Verifikasi Pembayaran</label>
                                <div class="d-flex">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio" name="status" id="status_verified" value="verified" checked>
                                        <label class="form-check-label" for="status_verified">
                                            <i class="fas fa-check-circle text-success me-1"></i>Terima
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="status_rejected" value="rejected">
                                        <label class="form-check-label" for="status_rejected">
                                            <i class="fas fa-times-circle text-danger me-1"></i>Tolak
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_notes" class="form-label">Catatan Admin</label>
                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                          placeholder="Tambahkan catatan untuk pengguna..."></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="verification_info">Jika disetujui, status pembayaran booking akan berubah menjadi "Paid".</span>
                            </div>
                            
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" name="verify_payment" class="btn btn-primary">Simpan Verifikasi</button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="verification_result" class="mt-4" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Bukti pembayaran ini sudah diverifikasi oleh <strong id="verified_by"></strong> pada <span id="verified_at"></span>.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status Verifikasi</label>
                            <div id="verification_status"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin</label>
                            <div class="form-control bg-light" id="admin_notes_display" style="min-height: 80px;"></div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProof(proof) {
            // Set booking and user info
            document.getElementById('view_booking_id').textContent = '#' + proof.booking_id;
            document.getElementById('view_field_name').textContent = proof.field_name;
            document.getElementById('view_datetime').textContent = formatDate(proof.booking_date) + ' ' + 
                formatTime(proof.start_time) + ' - ' + formatTime(proof.end_time);
            document.getElementById('view_total_price').textContent = formatCurrency(proof.total_price);
            document.getElementById('view_user_name').textContent = proof.full_name;
            document.getElementById('view_user_email').textContent = proof.email;
            document.getElementById('view_upload_date').textContent = formatDateTime(proof.upload_date);
            
            // Set status with appropriate styling
            let statusText = '';
            let statusClass = '';
            if (proof.status === 'pending') {
                statusText = 'Menunggu Verifikasi';
                statusClass = 'status-pending';
            } else if (proof.status === 'verified') {
                statusText = 'Terverifikasi';
                statusClass = 'status-verified';
            } else if (proof.status === 'rejected') {
                statusText = 'Ditolak';
                statusClass = 'status-rejected';
            }
            
            document.getElementById('view_status').innerHTML = 
                `<span class="status-badge ${statusClass}">${statusText}</span>`;
            
            // Display proof image or PDF
            const proofContainer = document.getElementById('proof_container');
            const filePath = proof.file_path;
            const fileExt = filePath.split('.').pop().toLowerCase();
            
            if (fileExt === 'pdf') {
                proofContainer.innerHTML = `
                    <object class="payment-proof-pdf" data="${filePath}" type="application/pdf">
                        <p>Browser Anda tidak mendukung tampilan PDF. <a href="${filePath}" target="_blank">Download PDF</a></p>
                    </object>
                `;
            } else {
                proofContainer.innerHTML = `
                    <img src="${filePath}" class="payment-proof-img" alt="Bukti Pembayaran">
                    <div class="mt-2">
                        <a href="${filePath}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Lihat Ukuran Penuh
                        </a>
                    </div>
                `;
            }
            
            // Set form values for verification
            document.getElementById('proof_id').value = proof.id;
            document.getElementById('booking_id').value = proof.booking_id;
            
            // Show/hide verification form based on status
            if (proof.status === 'pending') {
                document.getElementById('verification_form').style.display = 'block';
                document.getElementById('verification_result').style.display = 'none';
            } else {
                document.getElementById('verification_form').style.display = 'none';
                document.getElementById('verification_result').style.display = 'block';
                
                // Set verification result info
                document.getElementById('verified_by').textContent = proof.admin_name;
                document.getElementById('verified_at').textContent = formatDateTime(proof.verified_at);
                
                let verificationStatusHtml = '';
                if (proof.status === 'verified') {
                    verificationStatusHtml = `
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>Bukti pembayaran diterima
                        </div>
                    `;
                } else if (proof.status === 'rejected') {
                    verificationStatusHtml = `
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i>Bukti pembayaran ditolak
                        </div>
                    `;
                }
                document.getElementById('verification_status').innerHTML = verificationStatusHtml;
                
                // Set admin notes
                document.getElementById('admin_notes_display').textContent = proof.admin_notes || '-';
            }
            
            // Update verification info based on selected status
            document.querySelectorAll('input[name="status"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const infoElement = document.getElementById('verification_info');
                    if (this.value === 'verified') {
                        infoElement.textContent = 'Jika disetujui, status pembayaran booking akan berubah menjadi "Paid".';
                    } else {
                        infoElement.textContent = 'Jika ditolak, pengguna harus mengupload ulang bukti pembayaran.';
                    }
                });
            });
            
            // Show modal
            new bootstrap.Modal(document.getElementById('viewProofModal')).show();
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        }
        
        function formatTime(timeStr) {
            return timeStr.substring(0, 5);
        }
        
        function formatDateTime(dateTimeStr) {
            const date = new Date(dateTimeStr);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) + 
                   ' ' + date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
        
        function formatCurrency(amount) {
            return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
        }
        
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
