<?php
session_start();
require '../config/db.php';

$date = $_GET['date'] ?? null;
if (!$date) {
    echo json_encode(['success' => false, 'message' => 'Date required']);
    exit();
}


$slots_result = $conn->query("SELECT * FROM slots ORDER BY id ASC");
$slots = $slots_result->fetch_all(MYSQLI_ASSOC);


$stmt = $conn->prepare("SELECT slot_id FROM bookings WHERE slot_date = ? AND status = 'booked'");
$stmt->bind_param("s", $date);
$stmt->execute();
$booked_result = $stmt->get_result();

$bookedSlots = [];
while ($row = $booked_result->fetch_assoc()) {
    $bookedSlots[] = (int)$row['slot_id'];
}


$now = new DateTime();
$isToday = ($date === $now->format('Y-m-d'));


function parseSlotDateTime($dateStr, $timeStr) {
    return DateTime::createFromFormat('Y-m-d h:i A', $dateStr . ' ' . $timeStr);
}


foreach ($slots as &$slot) {
    $slotDateTime = parseSlotDateTime($date, $slot['time_slot']);
    $isPast = $isToday && $slotDateTime < $now;

    $slot['available'] = (!$isPast) && !in_array($slot['id'], $bookedSlots);
}

echo json_encode(['success' => true, 'slots' => $slots]);
