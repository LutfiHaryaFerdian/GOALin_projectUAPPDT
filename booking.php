<?php
require_once 'config/config.php';
requireLogin();
requireUser(); 

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';
$selected_field_id = isset($_GET['field_id']) ? (int)$_GET['field_id'] : 0;


$query = "SELECT * FROM fields WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);


$query = "SELECT * FROM time_slots ORDER BY start_time";
$stmt = $db->prepare($query);
$stmt->execute();
$time_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);


$bank_accounts = [
    'bca' => 'BCA - 1234567890 (GOALin Futsal)',
    'bni' => 'BNI - 0987654321 (GOALin Futsal)',
    'mandiri' => 'Mandiri - 1122334455 (GOALin Futsal)',
    'bri' => 'BRI - 5544332211 (GOALin Futsal)'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $field_id = (int)$_POST['field_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot_id = (int)$_POST['time_slot_id'];
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    $bank_account = '';
    
    if ($payment_method == 'transfer') {
        $bank_account = isset($_POST['bank_account']) ? $_POST['bank_account'] : '';
        if (empty($bank_account)) {
            $error = 'Silakan pilih rekening bank tujuan transfer';
        }
    }
    
    if (empty($field_id) || empty($booking_date) || empty($time_slot_id) || empty($payment_method)) {
        $error = 'Semua field wajib diisi';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = 'Tanggal booking tidak boleh kurang dari hari ini';
    } else {
    
        $db->beginTransaction();
        
        try {
 
            $query = "CALL buatBooking(?, ?, ?, ?, ?, ?, @booking_id, @status)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_SESSION['user_id'],
                $field_id,
                $booking_date,
                $time_slot_id,
                $payment_method,
                $notes
            ]);
            

            $result = $db->query("SELECT @booking_id as booking_id, @status as status")->fetch(PDO::FETCH_ASSOC);
            
            if (strpos($result['status'], 'SUCCESS') === 0) {
                $booking_id = $result['booking_id'];
                

                if ($payment_method == 'transfer' && !empty($bank_account)) {
                    $query = "UPDATE bookings SET bank_account = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$bank_account, $booking_id]);
                }
                

                if ($payment_method == 'transfer' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    $file = $_FILES['payment_proof'];
                    
                    if (!in_array($file['type'], $allowed_types)) {
                        throw new Exception('Tipe file tidak diizinkan. Hanya JPG, PNG, dan PDF yang diperbolehkan.');
                    }
                    
                    if ($file['size'] > $max_size) {
                        throw new Exception('Ukuran file terlalu besar. Maksimal 5MB.');
                    }
                    

                    $upload_dir = 'uploads/payment_proofs/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    

                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'payment_' . $booking_id . '_' . time() . '.' . $extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {

                        $query = "INSERT INTO payment_proofs (booking_id, file_path) VALUES (?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$booking_id, $filepath]);
                    } else {
                        throw new Exception('Gagal mengupload file. Silakan coba lagi.');
                    }
                }
                
                $db->commit();
                $success = 'Booking berhasil dibuat! ID Booking: ' . $booking_id;
                
                if ($payment_method == 'transfer' && (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] != 0)) {
                    $success .= '<br>Silakan upload bukti pembayaran di halaman riwayat booking.';
                }
            } else {
                throw new Exception(str_replace('ERROR: ', '', $result['status']));
            }
        } catch (Exception $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Lapangan - <?php echo SITE_NAME; ?></title>
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
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .booking-header {
            background: linear-gradient(45deg, #2E8B57, #32CD32);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #2E8B57, #32CD32);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #32CD32;
            box-shadow: 0 0 0 0.2rem rgba(50, 205, 50, 0.25);
        }
        
        .availability-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .time-slot {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .time-slot.available {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        .time-slot.unavailable {
            background: #f8d7da;
            border-color: #f5c6cb;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .time-slot.selected {
            background: #32CD32;
            color: white;
            border-color: #32CD32;
        }
        
        .payment-method-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method-card:hover {
            border-color: #32CD32;
        }
        
        .payment-method-card.selected {
            border-color: #32CD32;
            background-color: #f0fff0;
        }
        
        .payment-proof-section {
            display: none;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .bank-account-section {
            display: none;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-futbol text-success me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isUser()): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="booking.php">Booking</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php">Riwayat Booking</a>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="booking-card">
                    <div class="booking-header">
                        <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                        <h2>Booking Lapangan Futsal</h2>
                        <p class="mb-0">Pilih lapangan dan waktu yang tersedia</p>
                    </div>
                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <br><a href="my-bookings.php" class="alert-link">Lihat riwayat booking</a>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="bookingForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="field_id" class="form-label">Pilih Lapangan</label>
                                    <select class="form-select" id="field_id" name="field_id" required onchange="updateAvailability()">
                                        <option value="">-- Pilih Lapangan --</option>
                                        <?php foreach ($fields as $field): ?>
                                            <option value="<?php echo $field['id']; ?>" 
                                                    data-price="<?php echo $field['price_per_hour']; ?>"
                                                    <?php echo ($selected_field_id == $field['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($field['name']); ?> - <?php echo formatCurrency($field['price_per_hour']); ?>/jam
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="booking_date" class="form-label">Tanggal Booking</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required onchange="updateAvailability()">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Pilih Waktu</label>
                                <div class="availability-grid" id="timeSlots">
                                  
                                </div>
                                <input type="hidden" id="time_slot_id" name="time_slot_id" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="payment-method-card" onclick="selectPaymentMethod('cash')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" required>
                                                <label class="form-check-label" for="payment_cash">
                                                    <i class="fas fa-money-bill text-success me-2"></i>Cash
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1">Bayar langsung di tempat</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="payment-method-card" onclick="selectPaymentMethod('transfer')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="payment_transfer" value="transfer" required>
                                                <label class="form-check-label" for="payment_transfer">
                                                    <i class="fas fa-university text-primary me-2"></i>Transfer Bank
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1">Transfer ke rekening kami</small>
                                        </div>
                                    </div>
                                </div>
                                
                              
                                <div id="bankAccountSection" class="bank-account-section">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Silakan transfer ke salah satu rekening di bawah ini dan upload bukti transfer
                                    </div>
                                    <div class="mb-3">
                                        <label for="bank_account" class="form-label">Pilih Rekening Tujuan</label>
                                        <select class="form-select" id="bank_account" name="bank_account">
                                            <option value="">-- Pilih Rekening --</option>
                                            <?php foreach ($bank_accounts as $key => $account): ?>
                                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($account); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_proof" class="form-label">Upload Bukti Transfer</label>
                                        <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/jpeg,image/png,image/jpg,application/pdf">
                                        <div class="form-text">
                                            Format: JPG, PNG, atau PDF. Maksimal 5MB.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Total Harga</label>
                                    <div class="form-control bg-light" id="totalPrice">Pilih lapangan terlebih dahulu</div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Tambahkan catatan khusus untuk booking Anda..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-calendar-check me-2"></i>Konfirmasi Booking
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-muted">← Kembali ke beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const timeSlots = <?php echo json_encode($time_slots); ?>;
        let selectedTimeSlot = null;
        
        function updateAvailability() {
            const fieldId = document.getElementById('field_id').value;
            const bookingDate = document.getElementById('booking_date').value;
            const timeSlotsContainer = document.getElementById('timeSlots');
            
            if (!fieldId || !bookingDate) {
                timeSlotsContainer.innerHTML = '<p class="text-muted">Pilih lapangan dan tanggal terlebih dahulu</p>';
                updateTotalPrice();
                return;
            }
            
          
            timeSlotsContainer.innerHTML = '<p class="text-muted">Memuat ketersediaan...</p>';
            
            
            fetch('check-availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `field_id=${fieldId}&booking_date=${bookingDate}`
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                timeSlots.forEach(slot => {
                    const isAvailable = data.availability[slot.id] || false;
                    const className = isAvailable ? 'available' : 'unavailable';
                    const onclick = isAvailable ? `selectTimeSlot(${slot.id}, '${slot.start_time}', '${slot.end_time}')` : '';
                    
                    html += `
                        <div class="time-slot ${className}" ${onclick ? `onclick="${onclick}"` : ''} id="slot-${slot.id}">
                            <strong>${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</strong>
                            <br><small>${isAvailable ? 'Tersedia' : 'Tidak Tersedia'}</small>
                        </div>
                    `;
                });
                timeSlotsContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                timeSlotsContainer.innerHTML = '<p class="text-danger">Gagal memuat ketersediaan</p>';
            });
            
            updateTotalPrice();
        }
        
        function selectTimeSlot(slotId, startTime, endTime) {
            
            if (selectedTimeSlot) {
                document.getElementById(`slot-${selectedTimeSlot}`).classList.remove('selected');
            }
            
            
            selectedTimeSlot = slotId;
            document.getElementById(`slot-${slotId}`).classList.add('selected');
            document.getElementById('time_slot_id').value = slotId;
        }
        
        function selectPaymentMethod(method) {
          
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            
            document.querySelector(`#payment_${method}`).closest('.payment-method-card').classList.add('selected');
            
            
            document.querySelector(`#payment_${method}`).checked = true;
            
            
            const bankAccountSection = document.getElementById('bankAccountSection');
            if (method === 'transfer') {
                bankAccountSection.style.display = 'block';
            } else {
                bankAccountSection.style.display = 'none';
            }
        }
        
        function updateTotalPrice() {
            const fieldSelect = document.getElementById('field_id');
            const totalPriceDiv = document.getElementById('totalPrice');
            
            if (fieldSelect.value) {
                const selectedOption = fieldSelect.options[fieldSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                totalPriceDiv.textContent = formatCurrency(price);
            } else {
                totalPriceDiv.textContent = 'Pilih lapangan terlebih dahulu';
            }
        }
        
        function formatTime(time) {
            return time.substring(0, 5);
        }
        
        function formatCurrency(amount) {
            return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
        }
        
        
        if (document.getElementById('field_id').value) {
            updateTotalPrice();
        }
        
       
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Silakan pilih metode pembayaran');
                return;
            }
            
            if (paymentMethod.value === 'transfer') {
                const bankAccount = document.getElementById('bank_account').value;
                if (!bankAccount) {
                    e.preventDefault();
                    alert('Silakan pilih rekening tujuan transfer');
                    return;
                }
            }
        });
    </script>
</body>
</html>
