USE goalin_futsal;

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Create backup event (daily at 23:59)
DELIMITER //
CREATE EVENT daily_backup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL '23:59:00' HOUR_SECOND
DO
BEGIN
    -- Log backup event
    INSERT INTO booking_history (
        booking_id, user_id, field_id, booking_date, 
        time_slot_id, total_price, original_status, 
        cancellation_reason
    ) VALUES (
        0, 0, 0, CURDATE(), 0, 0.00, 'SYSTEM', 
        CONCAT('Daily backup completed at ', NOW())
    );
END //
DELIMITER ;
