USE goalin_futsal;

-- Trigger to log cancelled bookings
DELIMITER //
CREATE TRIGGER log_cancelled_booking
    AFTER UPDATE ON bookings
    FOR EACH ROW
BEGIN
    IF OLD.status != 'cancelled' AND NEW.status = 'cancelled' THEN
        INSERT INTO booking_history (
            booking_id, user_id, field_id, booking_date, 
            time_slot_id, total_price, original_status, 
            cancellation_reason
        ) VALUES (
            NEW.id, NEW.user_id, NEW.field_id, NEW.booking_date,
            NEW.time_slot_id, NEW.total_price, OLD.status,
            COALESCE(NEW.admin_notes, CONCAT('Booking cancelled at ', NOW()))
        );
    END IF;
END //
DELIMITER ;

-- Trigger to update booking timestamp
DELIMITER //
CREATE TRIGGER update_booking_timestamp
    BEFORE UPDATE ON bookings
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- Trigger to log booking status changes
DELIMITER //
CREATE TRIGGER log_booking_status_change
    AFTER UPDATE ON bookings
    FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.payment_status != NEW.payment_status THEN
        INSERT INTO booking_history (
            booking_id, user_id, field_id, booking_date, 
            time_slot_id, total_price, original_status, 
            cancellation_reason
        ) VALUES (
            NEW.id, NEW.user_id, NEW.field_id, NEW.booking_date,
            NEW.time_slot_id, NEW.total_price, 
            CONCAT(OLD.status, '/', OLD.payment_status),
            CONCAT('Status changed to ', NEW.status, '/', NEW.payment_status, ' at ', NOW())
        );
    END IF;
END //
DELIMITER ;
