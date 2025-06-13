<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$field_id = (int)$_POST['field_id'];
$booking_date = $_POST['booking_date'];

if (!$field_id || !$booking_date) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all time slots
$query = "SELECT id FROM time_slots ORDER BY start_time";
$stmt = $db->prepare($query);
$stmt->execute();
$time_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

$availability = [];

// Check availability for each time slot
foreach ($time_slots as $slot_id) {
    $query = "SELECT cekKetersediaan(?, ?, ?) as available";
    $stmt = $db->prepare($query);
    $stmt->execute([$field_id, $booking_date, $slot_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $availability[$slot_id] = (bool)$result['available'];
}

echo json_encode(['availability' => $availability]);
?>
