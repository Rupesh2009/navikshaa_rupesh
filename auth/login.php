<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: ../pages/index.php");
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6">Login</h2>
        <?php if (!empty($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <input type="email" name="email" placeholder="Email" required class="w-full p-2 border rounded mb-4">
        <input type="password" name="password" placeholder="Password" required class="w-full p-2 border rounded mb-4">
        <button type="submit" class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">Login</button>
        <p class="mt-4 text-sm text-center">Don't have an account? <a href="register.php" class="text-blue-600 underline">Register</a></p>
    </form>
</body>
</html>
