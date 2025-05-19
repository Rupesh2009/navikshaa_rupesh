<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$currentDateTime = new DateTime('now');


$sql = "SELECT b.id AS booking_id, b.slot_date, s.id AS slot_id, s.time_slot 
        FROM bookings b 
        JOIN slots s ON b.slot_id = s.id 
        WHERE b.user_id = ? AND b.status = 'booked'
        ORDER BY b.slot_date, s.time_slot";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen p-6">

   
    <?php if (isset($_GET['error'])): ?>
        <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white p-6 rounded shadow-lg max-w-md w-full text-center">
                <h2 class="text-lg font-bold mb-2 text-red-600">Error</h2>
                <p class="mb-4"><?= htmlspecialchars($_GET['error']) ?></p>
                <button onclick="window.location.href='mybookings.php'" class="bg-blue-500 text-white px-4 py-2 rounded">Close</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">My Booked Slots</h1>

        <?php
        $hasValidBooking = false;
        ?>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">Date</th>
                    <th class="p-2">Time Slot</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $result->fetch_assoc()): 
                    $bookingDateTime = DateTime::createFromFormat('Y-m-d h:i A', $booking['slot_date'] . ' ' . $booking['time_slot']);
                    if ($bookingDateTime && $bookingDateTime > $currentDateTime):
                        $hasValidBooking = true;
                ?>
                    <tr class="border-b">
                        <td class="p-2"><?= htmlspecialchars($booking['slot_date']) ?></td>
                        <td class="p-2"><?= htmlspecialchars($booking['time_slot']) ?></td>
                        <td class="p-2 space-x-2">
                           
                            <form action="../actions/update.php" method="POST" class="inline-flex items-center space-x-2">
                                <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">

                                <input
                                    type="text"
                                    name="new_date"
                                    class="p-1 border rounded w-28"
                                    placeholder="Select date"
                                    value="<?= htmlspecialchars($booking['slot_date']) ?>"
                                    required
                                />

                                <select name="new_slot_id" class="p-1 border rounded" required>
                                    <?php
                                    $slots_res = $conn->query("SELECT id, time_slot FROM slots ORDER BY id ASC");
                                    while ($slot = $slots_res->fetch_assoc()):
                                    ?>
                                        <option value="<?= $slot['id'] ?>" <?= $slot['id'] == $booking['slot_id'] ? 'selected' : '' ?>>
                                            <?= $slot['time_slot'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>

                                <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Modify</button>
                            </form>

                            
                            <form action="../actions/cancel.php" method="POST" class="inline">
                                <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Cancel</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; endwhile; ?>
            </tbody>
        </table>

        <?php if (!$hasValidBooking): ?>
            <p class="text-gray-600">You have no upcoming bookings.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr('input[name="new_date"]', {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    </script>
</body>
</html>
