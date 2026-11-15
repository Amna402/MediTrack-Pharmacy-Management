<?php
/* Contact Us endpoint: saves the message in the contact_messages table. */
define('API_MODE', true);
require __DIR__ . '/includes/bootstrap.php';

$input = json_input();

try {
    $clean = (new Validator($input))
        ->personName('name', 'Name')
        ->email('email')
        ->text('subject', 'Subject', 3, 100)
        ->text('message', 'Message', 10, 500)
        ->validate();

    (new ContactRepository())->save($clean);
    json_reply(201, ['message' => 'Message saved.']);
} catch (ValidationException $e) {
    json_reply(422, ['message' => $e->getMessage(), 'errors' => $e->getErrors()]);
} catch (Throwable $e) {
    api_error($e);
}
