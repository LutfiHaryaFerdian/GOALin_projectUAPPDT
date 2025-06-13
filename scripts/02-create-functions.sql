USE goalin_futsal;

-- Function to check field availability
DELIMITER //
CREATE FUNCTION cekKetersediaan(
    p_field_id INT,
    p_booking_date DATE,
    p_time_slot_id INT
) RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE slot_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO slot_count
    FROM bookings 
    WHERE field_id = p_field_id 
    AND booking_date = p_booking_date 
    AND time_slot_id = p_time_slot_id
    AND status IN ('pending', 'confirmed');
    
    RETURN slot_count = 0;
END //
DELIMITER ;

-- Function to calculate total revenue
DELIMITER //
CREATE FUNCTION hitungTotalRevenue(
    p_start_date DATE,
    p_end_date DATE
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_revenue DECIMAL(15,2) DEFAULT 0;
    
    SELECT COALESCE(SUM(total_price), 0) INTO total_revenue
    FROM bookings 
    WHERE booking_date BETWEEN p_start_date AND p_end_date
    AND payment_status = 'paid';
    
    RETURN total_revenue;
END //
DELIMITER ;

-- Function to get booking count by status
DELIMITER //
CREATE FUNCTION hitungBookingByStatus(
    p_status VARCHAR(20),
    p_start_date DATE,
    p_end_date DATE
) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE booking_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO booking_count
    FROM bookings 
    WHERE status = p_status
    AND booking_date BETWEEN p_start_date AND p_end_date;
    
    RETURN booking_count;
END //
DELIMITER ;
