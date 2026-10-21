<?php
/* Open http://localhost/meditrack/test_connection.php and take a screenshot for sir */
$host = 'localhost'; $dbname = 'meditrack'; $user = 'root'; $pass = '';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Database Connection Test | MediTrack</title>
<style>body{font-family:Arial,sans-serif;margin:40px;color:#12263a}.ok{color:#0b7a6b}.bad{color:#b3391b}</style></head>
<body>
<h1>MediTrack: Database Connection Test</h1>
<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo '<h2 class="ok">Connected to MySQL successfully.</h2>';
    echo '<p>Database: <strong>' . htmlspecialchars($dbname) . '</strong></p>';
    $users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $msgs  = $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
    echo '<p>Registered users: <strong>' . (int)$users . '</strong></p>';
    echo '<p>Contact messages: <strong>' . (int)$msgs . '</strong></p>';
} catch (PDOException $e) {
    echo '<h2 class="bad">Connection failed.</h2>';
    echo '<p>Start MySQL in XAMPP and import schema.sql in phpMyAdmin.</p>';
}
?>
</body>
</html>
