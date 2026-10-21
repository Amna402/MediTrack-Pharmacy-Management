<?php
/* Sign Up: validates again on the server, then saves the user in MySQL */
require 'db.php';

$full_name = trim(field($input, 'full_name'));
$email     = strtolower(trim(field($input, 'email')));
$phone     = trim(field($input, 'phone'));
$role      = field($input, 'role');
$password  = field($input, 'password');

$ok = preg_match("/^[A-Za-z][A-Za-z .'-]{2,49}$/", $full_name)
   && strlen($email) <= 100 && filter_var($email, FILTER_VALIDATE_EMAIL)
   && preg_match('/^03[0-9]{9}$/', $phone)
   && in_array($role, ['pharmacist', 'cashier', 'supplier'], true)
   && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,64}$/', $password);

if (!$ok) {
    reply(400, ['message' => 'Please check your details and try again.']);
}

try {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        reply(409, ['message' => 'This email is already registered. Please sign in.']);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);   // password is never stored as plain text
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, role, password_hash) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$full_name, $email, $phone, $role, $hash]);
    reply(200, ['message' => 'Account created.']);
} catch (PDOException $e) {
    reply(500, ['message' => 'Server error. Please try again.']);
}
