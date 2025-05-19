<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        header("Location: ../pages/index.php");
    } else {
        $error = "Email already registered.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6">Register</h2>
        <?php if (!empty($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <input type="text" name="name" placeholder="Full Name" required class="w-full p-2 border rounded mb-4">
        <input type="email" name="email" placeholder="Email" required class="w-full p-2 border rounded mb-4">
        <input type="password" name="password" placeholder="Password" required class="w-full p-2 border rounded mb-4">
        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Register</button>
        <p class="mt-4 text-sm text-center">Already have an account? <a href="login.php" class="text-blue-600 underline">Login</a></p>
    </form>
</body>
</html>
