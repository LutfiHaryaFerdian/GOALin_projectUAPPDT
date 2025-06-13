<?php
require_once 'config/config.php';
requireLogin();
requireUser(); // Only users can access this page

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle upload payment proof
if (isset($_POST['upload_payment_proof']) && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Verify booking belongs to user
    $query = "SELECT * FROM bookings WHERE id = ? AND user_id = ? AND payment_method = 'transfer'";
    $stmt = $db->prepare($query);
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['payment_proof'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Tipe file tidak diizinkan. Hanya JPG, PNG, dan PDF yang diperbolehkan.';
        } elseif ($file['size'] > $max_size) {
            $error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            // Create directory if not exists
            $upload_dir = 'uploads/payment_proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . $booking_id . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Check if payment proof already exists
                $query = "SELECT * FROM payment_proofs WHERE booking_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$booking_id]);
                $existing_proof = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_proof) {
                    // Update existing record
                    $query = "UPDATE payment_proofs SET file_path = ?, upload_date = NOW(), status = 'pending' WHERE booking_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$filepath, $booking_id]);
                } else {
                    // Insert new record
                    $query = "INSERT INTO payment_proofs (booking_id, file_path) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$booking_id, $filepath]);
                }
                
                $success = 'Bukti pembayaran berhasil diunggah. Admin akan memverifikasi pembayaran Anda.';
            } else {
                $error = 'Gagal mengunggah file. Silakan coba lagi.';
            }
        }
    } else {
        $error = 'Booking tidak ditemukan atau bukan milik Anda.';
    }
}

// Handle cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Verify booking belongs to user and can be cancelled
    $query = "SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        $query = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$booking_id])) {
            $success = 'Booking berhasil dibatalkan.';
        } else {
            $error = 'Gagal membatalkan booking. Silakan coba lagi.';
        }
    } else {
        $error = 'Booking tidak ditemukan atau tidak dapat dibatalkan.';
    }
}

// Get user's bookings with payment proof status
$query = "
    SELECT b.*, f.name as field_name, f.image_path, 
           ts.start_time, ts.end_time,
           pp.id as proof_id, pp.file_path as proof_path, pp.status as proof_status
    FROM bookings b
    JOIN fields f ON b.field_id = f.id
    JOIN time_slots ts ON b.time_slot_id = ts.id
    LEFT JOIN payment_proofs pp ON b.id = pp.booking_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, ts.start_time DESC
";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get bank accounts
$bank_accounts = [
    'bca' => 'BCA - 1234567890 (GOALin Futsal)',
    'bni' => 'BNI - 0987654321 (GOALin Futsal)',
    'mandiri' => 'Mandiri - 1122334455 (GOALin Futsal)',
    'bri' => 'BRI - 5544332211 (GOALin Futsal)'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2E8B57 0%, #228B22 100%);
            min-height: 100vh;
            padding-top: 80px;
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        
        .payment-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-refunded { background: #f8d7da; color: #721c24; }
        
        .proof-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .proof-pending { background: #fff3cd; color: #856404; }
        .proof-verified { background: #d4edda; color: #155724; }
        .proof-rejected { background: #f8d7da; color: #721c24; }
        
        .btn-cancel {
            background: #dc3545;
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .btn-cancel:hover {
            background: #c82333;
            color: white;
        }
        
        .btn-upload {
            background: #17a2b8;
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .btn-upload:hover {
            background: #138496;
            color: white;
        }
        
        .payment-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-futbol text-success me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isUser()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="booking.php">Booking</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my-bookings.php">Riwayat Booking</a>
                        </li>
                    <?php endif; ?>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-4">
                    <h2 class="text-white">
                        <i class="fas fa-history me-2"></i>
                        Riwayat Booking Saya
                    </h2>
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
                
                <?php if (empty($bookings)): ?>
                    <div class="text-center">
                        <div class="bg-white rounded-3 p-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h4>Belum Ada Booking</h4>
                            <p class="text-muted">Anda belum memiliki riwayat booking lapangan.</p>
                            <a href="booking.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Booking Sekarang
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="row g-0">
                                <div class="col-md-3">
                                    <img src="<?php echo $booking['image_path'] ?: '/placeholder.svg?height=200&width=300'; ?>" 
                                         class="img-fluid h-100 w-100" style="object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($booking['field_name']); ?>">
                                </div>
                                <div class="col-md-9">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-futbol text-success me-2"></i>
                                                <?php echo htmlspecialchars($booking['field_name']); ?>
                                            </h5>
                                            <div>
                                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                                <span class="payment-badge payment-<?php echo $booking['payment_status']; ?> ms-2">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                                <?php if ($booking['payment_method'] == 'transfer' && isset($booking['proof_status'])): ?>
                                                    <span class="proof-badge proof-<?php echo $booking['proof_status']; ?> ms-2">
                                                        Bukti: <?php echo ucfirst($booking['proof_status'] ?: 'Belum Upload'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-calendar text-muted me-2"></i>
                                                    <strong>Tanggal:</strong> <?php echo formatDate($booking['booking_date']); ?>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-clock text-muted me-2"></i>
                                                    <strong>Waktu:</strong> <?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['end_time']); ?>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-money-bill text-muted me-2"></i>
                                                    <strong>Total:</strong> <?php echo formatCurrency($booking['total_price']); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-credit-card text-muted me-2"></i>
                                                    <strong>Pembayaran:</strong> <?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-hashtag text-muted me-2"></i>
                                                    <strong>ID Booking:</strong> #<?php echo $booking['id']; ?>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-calendar-plus text-muted me-2"></i>
                                                    <strong>Dibuat:</strong> <?php echo formatDate($booking['created_at']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['notes']): ?>
                                            <div class="mt-2">
                                                <p class="card-text">
                                                    <i class="fas fa-sticky-note text-muted me-2"></i>
                                                    <strong>Catatan:</strong> <?php echo htmlspecialchars($booking['notes']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['admin_notes']): ?>
                                            <div class="mt-2">
                                                <p class="card-text">
                                                    <i class="fas fa-user-shield text-muted me-2"></i>
                                                    <strong>Catatan Admin:</strong> <?php echo htmlspecialchars($booking['admin_notes']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['payment_method'] == 'transfer'): ?>
                                            <div class="payment-info mt-3">
                                                <h6><i class="fas fa-university me-2"></i>Informasi Transfer</h6>
                                                <?php if ($booking['bank_account'] && isset($bank_accounts[$booking['bank_account']])): ?>
                                                    <p class="mb-2">
                                                        <strong>Rekening Tujuan:</strong> <?php echo htmlspecialchars($bank_accounts[$booking['bank_account']]); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['proof_path']): ?>
                                                    <p class="mb-2">
                                                        <strong>Bukti Pembayaran:</strong> 
                                                        <a href="<?php echo htmlspecialchars($booking['proof_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>Lihat Bukti
                                                        </a>
                                                    </p>
                                                    
                                                    <?php if ($booking['proof_status'] == 'rejected'): ?>
                                                        <div class="alert alert-danger py-2">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Bukti pembayaran ditolak. Silakan upload ulang.
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <p class="text-warning mb-2">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        Anda belum mengupload bukti pembayaran
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (($booking['payment_status'] == 'pending' || ($booking['proof_status'] == 'rejected')) && $booking['status'] != 'cancelled'): ?>
                                                    <button type="button" class="btn btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal<?php echo $booking['id']; ?>">
                                                        <i class="fas fa-upload me-1"></i>
                                                        <?php echo $booking['proof_path'] ? 'Upload Ulang Bukti' : 'Upload Bukti Pembayaran'; ?>
                                                    </button>
                                                    
                                                    <!-- Upload Modal -->
                                                    <div class="modal fade" id="uploadModal<?php echo $booking['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Upload Bukti Pembayaran</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="payment_proof<?php echo $booking['id']; ?>" class="form-label">Bukti Pembayaran</label>
                                                                            <input type="file" class="form-control" id="payment_proof<?php echo $booking['id']; ?>" name="payment_proof" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                                                                            <div class="form-text">
                                                                                Format: JPG, PNG, atau PDF. Maksimal 5MB.
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="alert alert-info">
                                                                            <i class="fas fa-info-circle me-2"></i>
                                                                            Pastikan bukti pembayaran jelas dan menunjukkan:
                                                                            <ul class="mb-0 mt-1">
                                                                                <li>Tanggal dan waktu transfer</li>
                                                                                <li>Jumlah transfer sesuai dengan total booking</li>
                                                                                <li>Rekening tujuan yang benar</li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" name="upload_payment_proof" class="btn btn-primary">Upload Bukti</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <?php if ($booking['status'] == 'pending'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan booking ini?')">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" name="cancel_booking" class="btn btn-cancel">
                                                        <i class="fas fa-times me-1"></i>Batalkan
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] == 'confirmed' && $booking['payment_status'] == 'pending'): ?>
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Menunggu pembayaran
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-home me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
