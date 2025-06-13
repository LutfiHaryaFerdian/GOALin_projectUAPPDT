<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_field'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price_per_hour'];
        
        if (empty($name) || empty($price)) {
            $error = 'Nama lapangan dan harga harus diisi';
        } else {
            $image_path = '';
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'field_' . time() . '.' . $filetype;
                    $upload_path = '../' . UPLOAD_PATH . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_path = UPLOAD_PATH . $new_filename;
                    }
                }
            }
            
            $query = "INSERT INTO fields (name, description, price_per_hour, image_path) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$name, $description, $price, $image_path])) {
                $success = 'Lapangan berhasil ditambahkan';
            } else {
                $error = 'Gagal menambahkan lapangan';
            }
        }
    } elseif (isset($_POST['update_field'])) {
        $field_id = (int)$_POST['field_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price_per_hour'];
        $status = $_POST['status'];
        
        if (empty($name) || empty($price)) {
            $error = 'Nama lapangan dan harga harus diisi';
        } else {
            $image_path = $_POST['current_image'];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'field_' . time() . '.' . $filetype;
                    $upload_path = '../' . UPLOAD_PATH . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        // Delete old image
                        if ($image_path && file_exists('../' . $image_path)) {
                            unlink('../' . $image_path);
                        }
                        $image_path = UPLOAD_PATH . $new_filename;
                    }
                }
            }
            
            $query = "UPDATE fields SET name = ?, description = ?, price_per_hour = ?, image_path = ?, status = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$name, $description, $price, $image_path, $status, $field_id])) {
                $success = 'Lapangan berhasil diperbarui';
            } else {
                $error = 'Gagal memperbarui lapangan';
            }
        }
    }
}

// Get all fields
$query = "SELECT * FROM fields ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - <?php echo SITE_NAME; ?></title>
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
        .field-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .field-card:hover { transform: translateY(-2px); }
        .field-image {
            height: 200px;
            object-fit: cover;
        }
        .status-active { color: #28a745; }
        .status-maintenance { color: #ffc107; }
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
                        <a class="nav-link active" href="fields.php">
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
                        <h2>Kelola Lapangan</h2>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFieldModal">
                                <i class="fas fa-plus me-2"></i>Tambah Lapangan
                            </button>
                        </div>
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
                    
                    <div class="row">
                        <?php foreach ($fields as $field): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="field-card">
                                    <img src="<?php echo $field['image_path'] ? '../' . $field['image_path'] : '/placeholder.svg?height=200&width=400'; ?>" 
                                         class="card-img-top field-image" alt="<?php echo htmlspecialchars($field['name']); ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?php echo htmlspecialchars($field['name']); ?></h5>
                                            <span class="status-<?php echo $field['status']; ?>">
                                                <i class="fas fa-circle"></i>
                                            </span>
                                        </div>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($field['description']); ?></p>
                                        <p class="card-text">
                                            <strong><?php echo formatCurrency($field['price_per_hour']); ?>/jam</strong>
                                        </p>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Status: <?php echo ucfirst($field['status']); ?>
                                            </small>
                                        </p>
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="editField(<?php echo htmlspecialchars(json_encode($field)); ?>)">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Field Modal -->
    <div class="modal fade" id="addFieldModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Lapangan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lapangan</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="price_per_hour" class="form-label">Harga per Jam (Rp)</label>
                            <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" min="0" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Gambar Lapangan</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_field" class="btn btn-primary">Tambah Lapangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Field Modal -->
    <div class="modal fade" id="editFieldModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Lapangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editFieldForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_field_id" name="field_id">
                        <input type="hidden" id="edit_current_image" name="current_image">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nama Lapangan</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price_per_hour" class="form-label">Harga per Jam (Rp)</label>
                            <input type="number" class="form-control" id="edit_price_per_hour" name="price_per_hour" min="0" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Aktif</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Gambar Lapangan</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                            <div class="form-text">Kosongkan jika tidak ingin mengubah gambar.</div>
                            <div id="current_image_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_field" class="btn btn-primary">Update Lapangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editField(field) {
            document.getElementById('edit_field_id').value = field.id;
            document.getElementById('edit_name').value = field.name;
            document.getElementById('edit_description').value = field.description || '';
            document.getElementById('edit_price_per_hour').value = field.price_per_hour;
            document.getElementById('edit_status').value = field.status;
            document.getElementById('edit_current_image').value = field.image_path || '';
            
            // Show current image preview
            const preview = document.getElementById('current_image_preview');
            if (field.image_path) {
                preview.innerHTML = `<img src="../${field.image_path}" class="img-thumbnail" style="max-width: 200px;">`;
            } else {
                preview.innerHTML = '<p class="text-muted">Tidak ada gambar</p>';
            }
            
            new bootstrap.Modal(document.getElementById('editFieldModal')).show();
        }

        function backupDatabase() {
            if (confirm('Apakah Anda yakin ingin membuat backup database?')) {
                window.open('backup.php', '_blank');
                
                // Submit form for backup
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
