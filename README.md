
# ⚽ Goalin Futsal Booking System

Sistem ini merupakan aplikasi pemesanan lapangan futsal berbasis web yang dibangun dengan PHP dan MySQL. Proyek ini mendukung manajemen pengguna, pemesanan jadwal, unggahan bukti pembayaran, serta panel admin untuk monitoring transaksi dan backup data otomatis.

![homepage](https://github.com/user-attachments/assets/7a0ec133-5a33-4c51-9b37-d8c6ca02a3ae)
![home](https://github.com/user-attachments/assets/af8292aa-6885-4236-86f3-7eb6c61e66da)

## 📌 Detail Konsep

### 🧠 Stored Procedure

Stored procedure berperan sebagai SOP (Standard Operating Procedure) dalam proses bisnis pemesanan dan manajemen status booking lapangan futsal. Semua logic yang krusial — seperti validasi ketersediaan waktu, pengecekan harga, dan transaksi — ditangani di tingkat database untuk menjamin **konsistensi dan integritas data**.

![procedure](https://github.com/user-attachments/assets/d13f14f6-5f05-42b9-86e3-36759ec160d0)

#### Beberapa procedure penting yang digunakan:

`scripts/03-create-procedures.sql`

* `buatBooking(p_user_id, p_field_id, p_booking_date, p_time_slot_id, p_payment_method, p_notes, OUT p_booking_id, OUT p_status)`:
  Membuat data pemesanan baru hanya jika slot tersedia dan lapangan aktif. Termasuk validasi lapangan dan pemanggilan function `cekKetersediaan`.

  ```sql
  CALL buatBooking(1, 2, '2025-06-15', 3, 'Transfer', 'Catatan khusus', @booking_id, @status);
  SELECT @booking_id, @status;
  ```

  Cuplikan prosedur:

  ```sql
  START TRANSACTION;
  SELECT price_per_hour INTO v_price FROM fields WHERE id = p_field_id AND status = 'active';
  SELECT cekKetersediaan(...) INTO v_available;
  INSERT INTO bookings (...);
  COMMIT;
  ```

  Fitur:

  * Validasi lapangan aktif
  * Validasi ketersediaan waktu
  * Transaksi otomatis (commit/rollback)
  * Menghasilkan ID booking dan status keterangan

* `updateBookingStatus(p_booking_id, p_status, p_payment_status, p_admin_notes, OUT p_result)`
  Mengubah status booking dan status pembayaran oleh admin. Validasi ID booking dilakukan sebelum update.

  ```sql
  CALL updateBookingStatus(10, 'confirmed', 'paid', 'Sudah dicek oleh admin', @result);
  SELECT @result;
  ```

  Cuplikan prosedur:

  ```sql
  START TRANSACTION;
  SELECT COUNT(*) INTO v_count FROM bookings WHERE id = p_booking_id;
  UPDATE bookings SET status = ..., payment_status = ..., updated_at = NOW();
  COMMIT;
  ```

  Fitur:

  * Validasi eksistensi ID booking
  * Update status sekaligus catatan admin
  * Transaksi aman (rollback saat error)

---

### 🚨 Trigger

Trigger pada sistem **Goalin Futsal** berfungsi sebagai *penjaga integritas data otomatis* di sisi database. Setiap kali ada proses booking, trigger akan memverifikasi berbagai aspek penting sebelum data disimpan atau diubah.

![triggers](https://github.com/user-attachments/assets/8a1b570e-4c08-43e9-94f2-c6e4a5e179be)

#### Tujuan penggunaan trigger:

* Mencegah pemesanan ganda (double booking)
* Menolak input dengan nilai tidak logis (misalnya ID tidak valid)
* Otomatis mencatat waktu perubahan data

#### Contoh Trigger (Rencana Implementasi):

* **Trigger: `prevent_double_booking`**

  Aktif sebelum `INSERT` ke tabel `bookings`, memastikan bahwa lapangan belum dibooking pada slot waktu yang sama.

  ```sql
  CREATE TRIGGER prevent_double_booking
  BEFORE INSERT ON bookings
  FOR EACH ROW
  BEGIN
      DECLARE v_exists INT;
      SELECT COUNT(*) INTO v_exists
      FROM bookings
      WHERE field_id = NEW.field_id
        AND booking_date = NEW.booking_date
        AND time_slot_id = NEW.time_slot_id;

      IF v_exists > 0 THEN
          SIGNAL SQLSTATE '45000'
          SET MESSAGE_TEXT = 'Time slot already booked for this field.';
      END IF;
  END;

  ```

  Fitur:

  * Menolak booking duplikat
  * Validasi dilakukan langsung di sisi database
---

### 🔄 Transaction (Transaksi)

Dalam sistem **Goalin Futsal**, proses penting seperti **pembuatan booking** dan **pembaruan status transaksi** dibungkus dalam **unit transaksi atomik** menggunakan `START TRANSACTION`, `COMMIT`, dan `ROLLBACK`. Ini memastikan bahwa:

* Tidak ada data yang tertulis sebagian.
* Jika satu langkah gagal, seluruh proses dibatalkan.
* Integritas data tetap terjaga di setiap skenario.

Sistem memanfaatkan fitur transaksi pada **stored procedure** yang sudah dibuat di `scripts/03-create-procedures.sql`.

#### Contoh 1: Transaksi saat Pemesanan (`buatBooking`)

Proses pemesanan mencakup:

1. Validasi ketersediaan lapangan
2. Pengambilan harga
3. Pembuatan data booking
4. Jika salah satu gagal, seluruh proses dibatalkan

```sql
START TRANSACTION;

SELECT price_per_hour INTO v_price
FROM fields WHERE id = p_field_id AND status = 'active';

SELECT cekKetersediaan(...) INTO v_available;

INSERT INTO bookings (...);

COMMIT;
```

Dengan fallback otomatis:

```sql
DECLARE EXIT HANDLER FOR SQLEXCEPTION
BEGIN
    ROLLBACK;
    SET p_status = 'ERROR: Transaction failed';
END;
```

Contoh pemanggilan:

```sql
CALL buatBooking(1, 2, '2025-06-15', 3, 'Transfer', 'Catatan', @booking_id, @status);
```

#### Contoh 2: Transaksi saat Update Status (`updateBookingStatus`)

Prosedur ini digunakan oleh admin untuk mengubah status pemesanan dan pembayaran pengguna. Proses ini juga dilindungi oleh transaksi.

```sql
START TRANSACTION;

SELECT COUNT(*) INTO v_count FROM bookings WHERE id = p_booking_id;

UPDATE bookings 
SET status = p_status, 
    payment_status = p_payment_status,
    admin_notes = p_admin_notes,
    updated_at = CURRENT_TIMESTAMP
WHERE id = p_booking_id;

COMMIT;
```

Jika booking tidak ditemukan:

```sql
IF v_count = 0 THEN
    SET p_result = 'ERROR: Booking not found';
    ROLLBACK;
END IF;
```

---



### 📺 Stored Function

Stored function digunakan untuk mengambil data informasi pengguna atau status lapangan.

![function](https://github.com/user-attachments/assets/7b747df9-8bac-47bd-a18f-9831ff0bbba5)

Contoh function `get_user_balance(user_id)`:
```sql
SELECT get_user_balance('U123');
```
--- 

### 🛡️ Backup Otomatis

Sistem **Goalin Futsal** dilengkapi dengan fitur **backup database otomatis** yang dapat diakses oleh admin dari halaman transaksi. Fitur ini sangat penting untuk memastikan keamanan dan ketersediaan data, serta menjaga sistem tetap bisa dipulihkan jika terjadi kerusakan.

#### 🔧 Mekanisme

Ketika admin menekan tombol “Backup Database”, sistem akan:

1. Mengambil seluruh struktur dan isi semua tabel (`SHOW TABLES`, `SHOW CREATE TABLE`, `SELECT * FROM`)
2. Menyimpan prosedur tersimpan (`SHOW PROCEDURE STATUS`, `SHOW CREATE PROCEDURE`)
3. Menyimpan fungsi (`SHOW FUNCTION STATUS`, `SHOW CREATE FUNCTION`)
4. Menyimpan semua trigger (`SHOW TRIGGERS`)
5. Menonaktifkan foreign key (`SET FOREIGN_KEY_CHECKS = 0`) untuk keperluan pemulihan
6. Mencatat aktivitas backup ke dalam tabel `booking_history` dengan status khusus `"BACKUP"`

#### 📄 Contoh log backup otomatis:

```sql
INSERT INTO booking_history (
    booking_id, user_id, field_id, booking_date, time_slot_id, 
    total_price, original_status, cancellation_reason
) 
VALUES (
    0, 3, 0, '2025-06-14', 0, 0.00, 'BACKUP', 
    'Database backup created by Admin at 2025-06-14 09:32:18'
);
```

#### 📥 File Backup

* Format: `.sql`
* Nama file menggunakan timestamp agar unik dan mudah ditelusuri:

  ```
  goalin_futsal_backup_2025-06-14_09-32-18.sql
  ```

#### 💡 Contoh Tombol Backup di UI Admin:

```html
<button class="btn btn-success me-2" onclick="backupDatabase()">
    <i class="fas fa-download me-2"></i>Backup Database
</button>
```

Dan fungsi JavaScript:

```javascript
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
```

---
