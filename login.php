<?php
/* Sign In: finds the user in MySQL and checks the password */
require 'db.php';

$email    = strtolower(trim(field($input, 'email')));
$password = field($input, 'password');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    reply(400, ['message' => 'Enter a valid email and password.']);
}

try {
    $stmt = $pdo->prepare('SELECT full_name, role, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        reply(401, ['message' => 'Incorrect email or password.']);
    }

    session_start();
    $_SESSION['name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    reply(200, ['message' => 'Signed in.', 'name' => $user['full_name'], 'role' => $user['role']]);
} catch (PDOException $e) {
    reply(500, ['message' => 'Server error. Please try again.']);
}
