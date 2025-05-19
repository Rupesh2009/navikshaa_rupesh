<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
require '../config/mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $booking_id = $_POST['booking_id'] ?? null;

    if (!$booking_id) {
        header("Location: ../pages/mybookings.php?error=" . urlencode("No booking selected for cancellation."));
        exit();
    }

 
    $stmt = $conn->prepare("
        SELECT b.slot_id, b.slot_date, s.time_slot, u.name, u.email 
        FROM bookings b
        JOIN slots s ON b.slot_id = s.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'booked'
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $booking = $result->fetch_assoc();

     
        $stmtCancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmtCancel->bind_param("ii", $booking_id, $user_id);

        if ($stmtCancel->execute()) {
      
            $subject = 'Booking Cancelled - Navikshaa';
            $body = "Hello {$booking['name']},<br><br>Your booking on <strong>{$booking['slot_date']}</strong> for time slot <strong>{$booking['time_slot']}</strong> has been successfully cancelled.<br><br>Thank you,<br>Navikshaa Team";
            sendEmail($booking['email'], $subject, $body);

            header("Location: ../pages/mybookings.php");
            exit();
        } else {
            header("Location: ../pages/mybookings.php?error=" . urlencode("Failed to cancel the booking. Please try again."));
            exit();
        }
    } else {
        header("Location: ../pages/mybookings.php?error=" . urlencode("Invalid booking or you do not have permission to cancel it."));
        exit();
    }
} else {
    header("Location: ../pages/mybookings.php?error=" . urlencode("Invalid request method."));
    exit();
}
