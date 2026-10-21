<?php
/* Contact Us: saves the message in the contact_messages table */
require 'db.php';

$name    = trim(field($input, 'name'));
$email   = trim(field($input, 'email'));
$subject = trim(field($input, 'subject'));
$message = trim(field($input, 'message'));

$ok = preg_match("/^[A-Za-z][A-Za-z .'-]{2,49}$/", $name)
   && strlen($email) <= 100 && filter_var($email, FILTER_VALIDATE_EMAIL)
   && mb_strlen($subject) >= 3 && mb_strlen($subject) <= 100
   && mb_strlen($message) >= 10 && mb_strlen($message) <= 500;

if (!$ok) {
    reply(400, ['message' => 'Please check your details and try again.']);
}

try {
    $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $subject, $message]);
    reply(200, ['message' => 'Message saved.']);
} catch (PDOException $e) {
    reply(500, ['message' => 'Server error. Please try again.']);
}
