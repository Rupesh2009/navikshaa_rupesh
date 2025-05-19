<?php
session_start();
require '../config/db.php';

$loggedIn = isset($_SESSION['user_id']);
$userName = null;

if ($loggedIn) {
    $user_id = $_SESSION['user_id'];
  
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $userName = $user['name'] ?? 'User';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Navikshaa</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<!-- Navbar -->
<nav class="bg-white shadow px-6 py-4 flex justify-between items-center relative">
  <a href="index.php" class="text-xl font-bold text-blue-600">Navikshaa</a>

  <!-- Hamburger Button (Mobile) -->
  <button id="navToggle" class="block md:hidden focus:outline-none z-20">
    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" 
         xmlns="http://www.w3.org/2000/svg" aria-hidden="true" >
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
  </button>

  <!-- Nav Links -->
  <div id="navMenu" class="hidden md:flex md:space-x-4 md:items-center
                          absolute md:static top-full left-0 w-full md:w-auto
                          bg-white md:bg-transparent shadow md:shadow-none
                          flex-col md:flex-row
                          z-10">
    <a href="index.php" class="block px-6 py-3 border-b border-gray-200 md:border-none hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-500">Home</a>

    <?php if (!$loggedIn): ?>
      <a href="../auth/register.php" class="block px-6 py-3 border-b border-gray-200 md:border-none hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-500">Register</a>
      <a href="../auth/login.php" class="block px-6 py-3 border-b border-gray-200 md:border-none hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-500">Login</a>
    <?php else: ?>
      <span class="block px-6 py-3 border-b border-gray-200 md:border-none text-gray-700 md:text-inherit md:px-0">Welcome, <?= htmlspecialchars($userName) ?></span>
      <a href="../pages/mybookings.php" class="block px-6 py-3 border-b border-gray-200 md:border-none hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-500">My Bookings</a>
      <a href="../auth/logout.php" class="block px-6 py-3 border-b border-gray-200 md:border-none hover:bg-red-100 md:hover:bg-transparent md:hover:text-red-500 text-red-600 md:text-inherit">Logout</a>
    <?php endif; ?>
  </div>
</nav>



<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-semibold mb-6">Book Your Session</h1>

  <label for="bookingDate" class="block mb-2 font-medium text-gray-700">Select a Date:</label>
  <input id="bookingDate" type="text" class="p-2 border border-gray-300 rounded w-48 mb-6" placeholder="Select date" readonly />

  <div id="slotsContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <p class="text-gray-600" id="loadingMessage">Please select a date to see available slots.</p>
  </div>
</main>

<script>
  const bookingDateInput = document.getElementById('bookingDate');
  const slotsContainer = document.getElementById('slotsContainer');
  const loadingMessage = document.getElementById('loadingMessage');
  const loggedIn = <?= $loggedIn ? 'true' : 'false' ?>;

  flatpickr(bookingDateInput, {
    altInput: true,
    altFormat: "F j, Y",
    dateFormat: "Y-m-d",
    minDate: "today",
    onChange: function(selectedDates, dateStr) {
      loadSlots(dateStr);
    }
  });

  function parse12HourTimeToDate(dateStr, time12h) {
    

    const [time, modifier] = time12h.split(' ');
    let [hours, minutes] = time.split(':').map(Number);

    if (modifier === 'PM' && hours !== 12) {
      hours += 12;
    } else if (modifier === 'AM' && hours === 12) {
      hours = 0;
    }

    const dateParts = dateStr.split('-');
    return new Date(
      Number(dateParts[0]),        
      Number(dateParts[1]) - 1,     
      Number(dateParts[2]),          
      hours,
      minutes,
      0
    );
  }

  async function loadSlots(date) {
    loadingMessage.style.display = 'block';
    slotsContainer.innerHTML = '';

    try {
      const response = await axios.get('../actions/get_slots.php', { params: { date } });
      if (response.data.success) {
        const slots = response.data.slots;
        if (slots.length === 0) {
          slotsContainer.innerHTML = '<p>No slots defined. Please contact admin.</p>';
          loadingMessage.style.display = 'none';
          return;
        }

        loadingMessage.style.display = 'none';
        const now = new Date();

        slots.forEach(slot => {
        
          let isPast = false;
          if (date === now.toISOString().slice(0, 10)) {
            const slotDateTime = parse12HourTimeToDate(date, slot.time_slot);
            if (slotDateTime <= now) {
              isPast = true;
            }
          }

          const slotDiv = document.createElement('div');
          slotDiv.className = 'p-4 rounded shadow ' + ((slot.available && !isPast) ? 'bg-green-200' : 'bg-red-200');

          let innerHtml = `<p><strong>${slot.time_slot}</strong> - `;
          innerHtml += (slot.available && !isPast) ? 'Available' : (isPast ? 'Expired' : 'Booked');
          innerHtml += '</p>';

          if (slot.available && !isPast) {
            if (loggedIn) {
              innerHtml += `
                <form action="../actions/book.php" method="POST" class="mt-2">
                  <input type="hidden" name="slot_id" value="${slot.id}" />
                  <input type="hidden" name="date" value="${date}" />
                  <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">Book</button>
                </form>`;
            } else {
              innerHtml += '<p class="text-sm mt-2 text-gray-600">Login to book</p>';
            }
          }

          slotDiv.innerHTML = innerHtml;
          slotsContainer.appendChild(slotDiv);
        });

      } else {
        slotsContainer.innerHTML = '<p>Error loading slots.</p>';
        loadingMessage.style.display = 'none';
      }
    } catch (error) {
      slotsContainer.innerHTML = '<p>Error fetching slots.</p>';
      loadingMessage.style.display = 'none';
      console.error(error);
    }
  }
</script>
<script>
  const navToggle = document.getElementById('navToggle');
  const navMenu = document.getElementById('navMenu');

  navToggle.addEventListener('click', () => {
    navMenu.classList.toggle('hidden');
  });
</script>



</body>
</html>
