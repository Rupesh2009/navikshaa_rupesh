<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config/db.php';
require '../config/mailer.php';

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); 
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); 
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $booking_id = $_POST['booking_id'] ?? null;
    $new_date = $_POST['new_date'] ?? null;
    $new_slot_id = $_POST['new_slot_id'] ?? null;

    if (empty($booking_id) || empty($new_date) || empty($new_slot_id)) {
        http_response_code(400); 
        exit();
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $new_date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $new_date) {
        http_response_code(422); 
        exit();
    }

    // Check booking exists for this user
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'booked'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        http_response_code(403); 
        exit();
    }

    // Check if slot exists
    $stmt = $conn->prepare("SELECT time_slot FROM slots WHERE id = ?");
    $stmt->bind_param("i", $new_slot_id);
    $stmt->execute();
    $slot = $stmt->get_result()->fetch_assoc();

    if (!$slot) {
        http_response_code(404);
        exit();
    }

    
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE slot_id = ? AND slot_date = ? AND status = 'booked' AND id != ?");
    $stmt->bind_param("isi", $new_slot_id, $new_date, $booking_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409); 
        exit();
    }

    // Update booking
    $stmt = $conn->prepare("UPDATE bookings SET slot_id = ?, slot_date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("isii", $new_slot_id, $new_date, $booking_id, $user_id);
    if (!$stmt->execute()) {
        http_response_code(500);
        exit();
    }

    
    $stmtUser = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();

    
    $subject = "Booking Modified - Navikshaa";
    $body = "
        <p>Dear {$user['name']},</p>
        <p>Your booking has been successfully updated to:</p>
        <p><strong>Date:</strong> {$new_date}<br>
           <strong>Time Slot:</strong> {$slot['time_slot']}</p>
        <p>If you need to make further changes, please visit your <a href='https://phpstack-1417858-5529447.cloudwaysapps.com/pages/mybookings.php'>My Bookings</a> page.</p>
        <p>Thank you,<br>Navikshaa Team</p>
    ";
    sendEmail($user['email'], $subject, $body);

    // ✅ Redirect after success
    header("Location: ../pages/mybookings.php");
    exit();

} catch (Exception $e) {
    http_response_code(500);
    exit();
}
