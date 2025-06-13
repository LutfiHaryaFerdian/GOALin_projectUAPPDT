-- Tabel untuk menyimpan bukti pembayaran
CREATE TABLE IF NOT EXISTS payment_proofs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tambahkan kolom bank_account ke tabel bookings jika belum ada
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS bank_account VARCHAR(100) NULL;

-- Tambahkan trigger untuk mencatat aktivitas verifikasi pembayaran
DELIMITER //
CREATE TRIGGER after_payment_proof_verification
AFTER UPDATE ON payment_proofs
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO admin_logs (user_id, action, details, ip_address)
        VALUES (
            NEW.verified_by,
            CONCAT('payment_verification_', NEW.status),
            CONCAT('Verified payment proof #', NEW.id, ' for booking #', NEW.booking_id, ' as ', NEW.status),
            '0.0.0.0'
        );
    END IF;
END //
DELIMITER ;

-- Tambahkan tabel admin_logs jika belum ada
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
