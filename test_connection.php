<?php
/* Open http://localhost/meditrack/test_connection.php and take a screenshot to show the database connection. */
require __DIR__ . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Connection Test | MediTrack</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #12263a; }
        .ok { color: #0b7a6b; } .bad { color: #b3391b; }
        table { border-collapse: collapse; margin-top: 12px; }
        td, th { border: 1px solid #d5e1de; padding: 8px 14px; text-align: left; }
    </style>
</head>
<body>
<h1>MediTrack: Database Connection Test</h1>
<?php
try {
    $db = Database::connection();
    $name = $db->query('SELECT DATABASE()')->fetchColumn();
    echo '<h2 class="ok">Connected to MySQL successfully.</h2>';
    echo '<p>Database: <strong>' . e($name) . '</strong></p>';
    echo '<table><tr><th>Table</th><th>Rows</th></tr>';
    foreach (['users', 'contact_messages', 'medicines', 'batches', 'sales', 'sale_items'] as $table) {
        $count = $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        echo '<tr><td>' . e($table) . '</td><td>' . (int) $count . '</td></tr>';
    }
    echo '</table>';
} catch (Throwable $e) {
    echo '<h2 class="bad">Connection failed.</h2>';
    echo '<p>Start Apache and MySQL in XAMPP and import database/schema.sql in phpMyAdmin.</p>';
    if (DEBUG) {
        echo '<p>Details: ' . e($e->getMessage()) . '</p>';
    }
}
?>
</body>
</html>
