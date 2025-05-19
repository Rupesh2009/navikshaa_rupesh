<?php
session_start();
require '../config/db.php';
require '../config/mailer.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$slot_id = $_POST['slot_id'] ?? null;
$slot_date = $_POST['date'] ?? null;

if (!$slot_id || !$slot_date) {
    echo json_encode(['success' => false, 'message' => 'Slot and date are required']);
    exit();
}


$stmt = $conn->prepare("SELECT time_slot FROM slots WHERE id = ?");
$stmt->bind_param("i", $slot_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot']);
    exit();
}
$slot = $res->fetch_assoc();


function parseSlotDateTime($dateStr, $timeStr) {
    $dateTimeStr = $dateStr . ' ' . $timeStr;
    $dt = DateTime::createFromFormat('Y-m-d h:i A', $dateTimeStr);
    return $dt ?: null;
}

$slotDateTime = parseSlotDateTime($slot_date, $slot['time_slot']);
$now = new DateTime();

if (!$slotDateTime) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot time format']);
    exit();
}

if ($slotDateTime < $now) {
    echo json_encode(['success' => false, 'message' => 'Cannot book a slot in the past']);
    exit();
}


$stmt = $conn->prepare("SELECT * FROM bookings WHERE slot_id = ? AND slot_date = ? AND status = 'booked'");
$stmt->bind_param("is", $slot_id, $slot_date);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Slot already booked for this date']);
    exit();
}


$stmt = $conn->prepare("INSERT INTO bookings (user_id, slot_id, slot_date, status) VALUES (?, ?, ?, 'booked')");
$stmt->bind_param("iis", $user_id, $slot_id, $slot_date);
$stmt->execute();


$user_stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$subject = "Slot Booking Confirmation - Navikshaa";
$body = "
    <p>Dear {$user['name']},</p>
    <p>Thank you for booking a session with Navikshaa.</p>
    <p>Your slot on <strong>{$slot_date}</strong> at <strong>{$slot['time_slot']}</strong> has been successfully booked.</p>
    <p>If you need to modify or cancel your booking, please visit your <a href='https://phpstack-1417858-5529447.cloudwaysapps.com/pages/mybookings.php'>My Bookings</a> page.</p>
    <p>Best regards,<br>Navikshaa Team</p>
";

sendEmail($user['email'], $subject, $body);


header("Location: ../pages/mybookings.php");
exit();

