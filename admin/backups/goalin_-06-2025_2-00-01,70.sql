-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: goalin_futsal
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,1,'payment_verification_verified','Verified payment proof #1 for booking #6 as verified','0.0.0.0','2025-06-12 22:12:33');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_history`
--

DROP TABLE IF EXISTS `booking_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `field_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot_id` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `original_status` varchar(20) DEFAULT NULL,
  `cancellation_reason` text,
  `cancelled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_history`
--

LOCK TABLES `booking_history` WRITE;
/*!40000 ALTER TABLE `booking_history` DISABLE KEYS */;
INSERT INTO `booking_history` VALUES (1,1,2,1,'2025-06-12',13,150000.00,'pending/pending','Status changed to confirmed/paid at 2025-06-12 21:00:01','2025-06-12 14:00:01'),(2,0,1,0,'2025-06-12',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 14:34:02','2025-06-12 14:34:02'),(3,0,1,0,'2025-06-12',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 15:16:52','2025-06-12 15:16:52'),(4,0,1,0,'2025-06-13',0,0.00,'AUTO_BACKUP','Scheduled backup created: goalin_futsal_manual_test_2025-06-12_19-24-16.sql at 2025-06-12 19:24:16','2025-06-12 19:24:16'),(5,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:24:07','2025-06-12 20:24:07'),(6,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:25:07','2025-06-12 20:25:07'),(7,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:31:18','2025-06-12 20:31:18'),(8,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:31:26','2025-06-12 20:31:26'),(9,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:31:33','2025-06-12 20:31:33'),(10,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:31:47','2025-06-12 20:31:47'),(11,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 20:31:57','2025-06-12 20:31:57'),(12,0,1,0,'2025-06-13',0,0.00,'BACKUP','Database backup created by Administrator at 2025-06-12 22:27:16','2025-06-12 22:27:16');
/*!40000 ALTER TABLE `booking_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `field_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot_id` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `bank_account` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_booking` (`field_id`,`booking_date`,`time_slot_id`),
  KEY `user_id` (`user_id`),
  KEY `time_slot_id` (`time_slot_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`),
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,2,1,'2025-06-12',13,150000.00,'confirmed','paid','cash','','','2025-06-12 13:59:11','2025-06-12 14:00:01',NULL),(2,2,1,'2025-06-30',13,150000.00,'pending','pending','cash','',NULL,'2025-06-12 15:19:12','2025-06-12 15:19:12',NULL),(3,2,2,'2025-06-19',12,200000.00,'confirmed','paid','cash','','','2025-06-12 15:23:51','2025-06-12 15:25:09',NULL),(4,2,3,'2025-06-20',9,120000.00,'confirmed','paid','cash','','','2025-06-12 15:24:12','2025-06-12 15:24:59',NULL),(5,2,2,'2025-06-15',11,200000.00,'cancelled','pending','transfer','',NULL,'2025-06-12 21:34:26','2025-06-12 21:52:22',NULL),(6,2,2,'2025-06-15',7,200000.00,'pending','paid','transfer','',NULL,'2025-06-12 21:52:55','2025-06-12 22:12:33',NULL);
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validate_booking_before_insert` BEFORE INSERT ON `bookings` FOR EACH ROW BEGIN
    DECLARE v_field_status VARCHAR(20);
    DECLARE v_user_role VARCHAR(20);
    DECLARE v_existing_booking INT DEFAULT 0;
    
    -- Check if field is active
    SELECT status INTO v_field_status FROM fields WHERE id = NEW.field_id;
    IF v_field_status != 'active' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot book inactive field';
    END IF;
    
    -- Check if user is admin (admins cannot book)
    SELECT role INTO v_user_role FROM users WHERE id = NEW.user_id;
    IF v_user_role = 'admin' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Administrators cannot make bookings';
    END IF;
    
    -- Check for double booking
    SELECT COUNT(*) INTO v_existing_booking 
    FROM bookings 
    WHERE field_id = NEW.field_id 
    AND booking_date = NEW.booking_date 
    AND time_slot_id = NEW.time_slot_id 
    AND status IN ('confirmed', 'pending');
    
    IF v_existing_booking > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Time slot already booked';
    END IF;
    
    -- Set default values
    SET NEW.created_at = CURRENT_TIMESTAMP;
    SET NEW.updated_at = CURRENT_TIMESTAMP;
    SET NEW.status = 'pending';
    SET NEW.payment_status = 'pending';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `update_booking_timestamp` BEFORE UPDATE ON `bookings` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `log_cancelled_booking` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `log_booking_status_change` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `fields`
--

DROP TABLE IF EXISTS `fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price_per_hour` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','maintenance') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fields`
--

LOCK TABLES `fields` WRITE;
/*!40000 ALTER TABLE `fields` DISABLE KEYS */;
INSERT INTO `fields` VALUES (1,'Lapangan A','Lapangan futsal standar dengan rumput sintetis berkualitas tinggi',150000.00,'uploads/field_1749736633.jpg','active','2025-06-12 13:49:00','2025-06-12 13:57:13'),(2,'Lapangan B','Lapangan futsal indoor dengan AC dan pencahayaan LED',200000.00,'uploads/field_1749741770.jpg','active','2025-06-12 13:49:00','2025-06-12 15:22:50'),(3,'Lapangan C','Lapangan futsal outdoor dengan view taman',120000.00,'uploads/field_1749766640.jpg','active','2025-06-12 13:49:00','2025-06-12 22:17:20');
/*!40000 ALTER TABLE `fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_proofs`
--

DROP TABLE IF EXISTS `payment_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_proofs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `admin_notes` text,
  `verified_by` int DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `payment_proofs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_proofs_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_proofs`
--

LOCK TABLES `payment_proofs` WRITE;
/*!40000 ALTER TABLE `payment_proofs` DISABLE KEYS */;
INSERT INTO `payment_proofs` VALUES (1,6,'uploads/payment_proofs/payment_6_1749765600.png','2025-06-12 22:00:00','verified','',1,'2025-06-12 22:12:33');
/*!40000 ALTER TABLE `payment_proofs` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_payment_proof_verification` AFTER UPDATE ON `payment_proofs` FOR EACH ROW BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO admin_logs (user_id, action, details, ip_address)
        VALUES (
            NEW.verified_by,
            CONCAT('payment_verification_', NEW.status),
            CONCAT('Verified payment proof #', NEW.id, ' for booking #', NEW.booking_id, ' as ', NEW.status),
            '0.0.0.0'
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `time_slots`
--

DROP TABLE IF EXISTS `time_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_slots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_slots`
--

LOCK TABLES `time_slots` WRITE;
/*!40000 ALTER TABLE `time_slots` DISABLE KEYS */;
INSERT INTO `time_slots` VALUES (1,'08:00:00','09:00:00','2025-06-12 13:49:00'),(2,'09:00:00','10:00:00','2025-06-12 13:49:00'),(3,'10:00:00','11:00:00','2025-06-12 13:49:00'),(4,'11:00:00','12:00:00','2025-06-12 13:49:00'),(5,'13:00:00','14:00:00','2025-06-12 13:49:00'),(6,'14:00:00','15:00:00','2025-06-12 13:49:00'),(7,'15:00:00','16:00:00','2025-06-12 13:49:00'),(8,'16:00:00','17:00:00','2025-06-12 13:49:00'),(9,'17:00:00','18:00:00','2025-06-12 13:49:00'),(10,'18:00:00','19:00:00','2025-06-12 13:49:00'),(11,'19:00:00','20:00:00','2025-06-12 13:49:00'),(12,'20:00:00','21:00:00','2025-06-12 13:49:00'),(13,'21:00:00','22:00:00','2025-06-12 13:49:00');
/*!40000 ALTER TABLE `time_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@goalin.com','$2y$10$am0K2wOza2KU3LXx6YNASuW1ZLbYcYkCT0YJqt1G7Snqw6FGJNR/e','Administrator','08586','admin','2025-06-12 13:49:00','2025-06-12 22:26:54'),(2,'lutpi','lutfiharyaferdian@gmail.com','$2y$10$yGB7cS/ldwWSPoosFPRWKer6eO8AdZJ4sDlE.YYt/qBunf3PbJjhS','lutfi harya','0882','user','2025-06-12 13:58:18','2025-06-12 13:58:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'goalin_futsal'
--
/*!50003 DROP FUNCTION IF EXISTS `cekKetersediaan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `cekKetersediaan`(
    p_field_id INT,
    p_booking_date DATE,
    p_time_slot_id INT
) RETURNS tinyint(1)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `hitungBookingByStatus` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `hitungBookingByStatus`(
    p_status VARCHAR(20),
    p_start_date DATE,
    p_end_date DATE
) RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE booking_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO booking_count
    FROM bookings 
    WHERE status = p_status
    AND booking_date BETWEEN p_start_date AND p_end_date;
    
    RETURN booking_count;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `hitungTotalRevenue` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `hitungTotalRevenue`(
    p_start_date DATE,
    p_end_date DATE
) RETURNS decimal(15,2)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE total_revenue DECIMAL(15,2) DEFAULT 0;
    
    SELECT COALESCE(SUM(total_price), 0) INTO total_revenue
    FROM bookings 
    WHERE booking_date BETWEEN p_start_date AND p_end_date
    AND payment_status = 'paid';
    
    RETURN total_revenue;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buatBooking` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `buatBooking`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `updateBookingStatus` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `updateBookingStatus`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-15  2:00:02
