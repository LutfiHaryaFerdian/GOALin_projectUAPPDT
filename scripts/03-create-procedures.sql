USE goalin_futsal;

-- Stored procedure for creating booking with validation
DELIMITER //
CREATE PROCEDURE buatBooking(
    IN p_user_id INT,
    IN p_field_id INT,
    IN p_booking_date DATE,
    IN p_time_slot_id INT,
    IN p_payment_method VARCHAR(50),
    IN p_notes TEXT,
    OUT p_booking_id INT,
    OUT p_status VARCHAR(50)
)
BEGIN
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_available BOOLEAN DEFAULT FALSE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status = 'ERROR: Transaction failed';
        SET p_booking_id = 0;
    END;

    START TRANSACTION;
    
    -- Check if field exists and get price
    SELECT price_per_hour INTO v_price
    FROM fields 
    WHERE id = p_field_id AND status = 'active';
    
    IF v_price IS NULL THEN
        SET p_status = 'ERROR: Field not found or inactive';
        SET p_booking_id = 0;
        ROLLBACK;
    ELSE
        -- Check availability
        SELECT cekKetersediaan(p_field_id, p_booking_date, p_time_slot_id) INTO v_available;
        
        IF v_available = FALSE THEN
            SET p_status = 'ERROR: Time slot not available';
            SET p_booking_id = 0;
            ROLLBACK;
        ELSE
            -- Create booking
            INSERT INTO bookings (user_id, field_id, booking_date, time_slot_id, total_price, payment_method, notes)
            VALUES (p_user_id, p_field_id, p_booking_date, p_time_slot_id, v_price, p_payment_method, p_notes);
            
            SET p_booking_id = LAST_INSERT_ID();
            SET p_status = 'SUCCESS: Booking created successfully';
            COMMIT;
        END IF;
    END IF;
END //
DELIMITER ;

-- Stored procedure for updating booking status
DELIMITER //
CREATE PROCEDURE updateBookingStatus(
    IN p_booking_id INT,
    IN p_status VARCHAR(20),
    IN p_payment_status VARCHAR(20),
    IN p_admin_notes TEXT,
    OUT p_result VARCHAR(50)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 'ERROR: Update failed';
    END;

    START TRANSACTION;
    
    -- Check if booking exists
    SELECT COUNT(*) INTO v_count FROM bookings WHERE id = p_booking_id;
    
    IF v_count = 0 THEN
        SET p_result = 'ERROR: Booking not found';
        ROLLBACK;
    ELSE
        -- Update booking
        UPDATE bookings 
        SET status = p_status, 
            payment_status = p_payment_status,
            admin_notes = p_admin_notes,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_booking_id;
        
        SET p_result = 'SUCCESS: Booking updated';
        COMMIT;
    END IF;
END //
DELIMITER ;
