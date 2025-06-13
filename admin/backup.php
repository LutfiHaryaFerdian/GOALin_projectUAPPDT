<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['backup_database'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Get database name from config
        $db_name = 'goalin_futsal';
        
        // Start building SQL backup
        $backup = "-- GOALin Futsal Database Backup\n";
        $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Database: $db_name\n\n";
        
        $backup .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $backup .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $backup .= "SET time_zone = \"+00:00\";\n\n";
        
        // Get all tables
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Backup each table
        foreach ($tables as $table) {
            $backup .= "-- --------------------------------------------------------\n";
            $backup .= "-- Table structure for table `$table`\n";
            $backup .= "-- --------------------------------------------------------\n\n";
            
            // Drop table if exists
            $backup .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Get table structure
            $result = $db->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $backup .= $row['Create Table'] . ";\n\n";
            
            // Get table data
            $backup .= "-- Dumping data for table `$table`\n";
            $backup .= "-- --------------------------------------------------------\n\n";
            
            $result = $db->query("SELECT * FROM `$table`");
            $num_fields = $result->columnCount();
            
            if ($result->rowCount() > 0) {
                $backup .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $row_data = "(";
                    for ($j = 0; $j < $num_fields; $j++) {
                        if (isset($row[$j])) {
                            $row_data .= "'" . addslashes($row[$j]) . "'";
                        } else {
                            $row_data .= "NULL";
                        }
                        if ($j < ($num_fields - 1)) {
                            $row_data .= ',';
                        }
                    }
                    $row_data .= ")";
                    $rows[] = $row_data;
                }
                
                $backup .= implode(",\n", $rows) . ";\n\n";
            } else {
                $backup .= "-- No data found for table `$table`\n\n";
            }
        }
        
        // Backup stored procedures
        $backup .= "-- --------------------------------------------------------\n";
        $backup .= "-- Stored Procedures\n";
        $backup .= "-- --------------------------------------------------------\n\n";
        
        $result = $db->query("SHOW PROCEDURE STATUS WHERE Db = '$db_name'");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $proc_name = $row['Name'];
            $proc_result = $db->query("SHOW CREATE PROCEDURE `$proc_name`");
            $proc_row = $proc_result->fetch(PDO::FETCH_ASSOC);
            $backup .= "DROP PROCEDURE IF EXISTS `$proc_name`;\n";
            $backup .= "DELIMITER //\n";
            $backup .= $proc_row['Create Procedure'] . " //\n";
            $backup .= "DELIMITER ;\n\n";
        }
        
        // Backup functions
        $backup .= "-- --------------------------------------------------------\n";
        $backup .= "-- Functions\n";
        $backup .= "-- --------------------------------------------------------\n\n";
        
        $result = $db->query("SHOW FUNCTION STATUS WHERE Db = '$db_name'");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $func_name = $row['Name'];
            $func_result = $db->query("SHOW CREATE FUNCTION `$func_name`");
            $func_row = $func_result->fetch(PDO::FETCH_ASSOC);
            $backup .= "DROP FUNCTION IF EXISTS `$func_name`;\n";
            $backup .= "DELIMITER //\n";
            $backup .= $func_row['Create Function'] . " //\n";
            $backup .= "DELIMITER ;\n\n";
        }
        
        // Backup triggers
        $backup .= "-- --------------------------------------------------------\n";
        $backup .= "-- Triggers\n";
        $backup .= "-- --------------------------------------------------------\n\n";
        
        foreach ($tables as $table) {
            $result = $db->query("SHOW TRIGGERS LIKE '$table'");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $trigger_name = $row['Trigger'];
                $backup .= "DROP TRIGGER IF EXISTS `$trigger_name`;\n";
                $backup .= "DELIMITER //\n";
                $backup .= "CREATE TRIGGER `$trigger_name` " . $row['Timing'] . " " . $row['Event'] . " ON `" . $row['Table'] . "` FOR EACH ROW\n";
                $backup .= $row['Statement'] . " //\n";
                $backup .= "DELIMITER ;\n\n";
            }
        }
        
        $backup .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Log backup activity
        $query = "INSERT INTO booking_history (booking_id, user_id, field_id, booking_date, time_slot_id, total_price, original_status, cancellation_reason) VALUES (0, ?, 0, CURDATE(), 0, 0.00, 'BACKUP', ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], 'Database backup created by ' . $_SESSION['full_name'] . ' at ' . date('Y-m-d H:i:s')]);
        
        // Set headers for download
        $filename = 'goalin_futsal_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output the backup
        echo $backup;
        exit();
        
    } catch (Exception $e) {
        // Log error
        error_log("Backup error: " . $e->getMessage());
        
        // Redirect with error
        header('Location: index.php?backup_error=1');
        exit();
    }
} else {
    // Redirect if not proper request
    header('Location: index.php');
    exit();
}
?>
