<?php
$host = 'localhost';
$user = 'hugkhketpc';
$pass = 'A6UgH5MQNW';
$db = 'hugkhketpc';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>