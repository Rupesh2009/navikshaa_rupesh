<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$date = $_GET['date'] ?? null;

if (!$date) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}


$stmt = $conn->prepare("SELECT id, time_slot, status FROM slots WHERE slot_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$slots = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'slots' => $slots]);
