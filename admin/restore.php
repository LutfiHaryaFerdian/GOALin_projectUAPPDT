<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $uploadedFile = $_FILES['backup_file'];
        
        // Validate file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file');
        }
        
        if ($uploadedFile['size'] > 50 * 1024 * 1024) { // 50MB limit
            throw new Exception('File terlalu besar. Maksimal 50MB');
        }
        
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'sql') {
            throw new Exception('File harus berformat .sql');
        }
        
        // Read SQL file
        $sqlContent = file_get_contents($uploadedFile['tmp_name']);
        if ($sqlContent === false) {
            throw new Exception('Gagal membaca file backup');
        }
        
        // Disable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Split SQL statements
        $statements = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $db->exec($statement);
            }
        }
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Log restore activity
        $query = "INSERT INTO booking_history (booking_id, user_id, field_id, booking_date, time_slot_id, total_price, original_status, cancellation_reason) VALUES (0, ?, 0, CURDATE(), 0, 0.00, 'RESTORE', ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], 'Database restored by ' . $_SESSION['full_name'] . ' at ' . date('Y-m-d H:i:s')]);
        
        $success = 'Database berhasil direstore dari backup!';
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
        error_log("Restore error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Database - <?php echo SITE_NAME; ?></title>
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
        .restore-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #2E8B57;
            background: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #2E8B57;
            background: #e8f5e8;
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
                        <a class="nav-link" href="payment-proofs.php">
                            <i class="fas fa-receipt me-2"></i>Verifikasi Transfer
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Laporan
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Pengguna
                        </a>
                        <a class="nav-link active" href="restore.php">
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
                        <h2>
                            <i class="fas fa-upload me-2"></i>
                            Restore Database
                        </h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
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
                    
                    <div class="restore-card">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Peringatan:</strong> Proses restore akan mengganti semua data yang ada dengan data dari file backup. 
                            Pastikan Anda telah membuat backup data saat ini sebelum melanjutkan.
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="restoreForm">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Upload File Backup</h5>
                                <p class="text-muted">Drag & drop file .sql atau klik untuk memilih</p>
                                <input type="file" class="form-control" id="backup_file" name="backup_file" 
                                       accept=".sql" required style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('backup_file').click()">
                                    <i class="fas fa-folder-open me-2"></i>Pilih File
                                </button>
                            </div>
                            
                            <div id="fileInfo" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <strong>File dipilih:</strong> <span id="fileName"></span>
                                    <br><strong>Ukuran:</strong> <span id="fileSize"></span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmRestore" required>
                                    <label class="form-check-label" for="confirmRestore">
                                        Saya memahami bahwa proses ini akan mengganti semua data yang ada
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-danger" id="restoreBtn" disabled>
                                    <i class="fas fa-upload me-2"></i>Restore Database
                                </button>
                                <a href="index.php" class="btn btn-secondary ms-2">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('backup_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const confirmCheck = document.getElementById('confirmRestore');
        const restoreBtn = document.getElementById('restoreBtn');
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });
        
        fileInput.addEventListener('change', handleFileSelect);
        
        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                checkFormValid();
            }
        }
        
        confirmCheck.addEventListener('change', checkFormValid);
        
        function checkFormValid() {
            const hasFile = fileInput.files.length > 0;
            const isConfirmed = confirmCheck.checked;
            restoreBtn.disabled = !(hasFile && isConfirmed);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form submission
        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            if (!confirm('Apakah Anda yakin ingin melakukan restore? Semua data saat ini akan diganti!')) {
                e.preventDefault();
                return false;
            }
            
            restoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            restoreBtn.disabled = true;
        });
    </script>
</body>
</html>
