<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "online_exam";
$port = 3306;

// Only connect if the database variable is requested (so setup_db doesn't fail if it includes this, though we won't include it in setup)
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error . " (Make sure to run setup_db.php first)");
}
?>
