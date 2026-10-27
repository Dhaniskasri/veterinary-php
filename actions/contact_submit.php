<?php
/**
 * Contact Message Submission Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contact.php');
}

$senderName = trim($_POST['sender_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if (empty($senderName)) {
    $errors[] = 'Please enter your name.';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}
if (empty($subject)) {
    $errors[] = 'Please enter a message subject.';
}
if (empty($message)) {
    $errors[] = 'Please enter your message.';
}

if (!empty($errors)) {
    redirect('contact.php', implode(' ', $errors), 'danger');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (sender_name, email, subject, message, status, created_at)
        VALUES (:sender_name, :email, :subject, :message, 'unread', NOW())
    ");
    $stmt->execute([
        ':sender_name' => substr($senderName, 0, 100),
        ':email'       => substr($email, 0, 150),
        ':subject'     => substr($subject, 0, 200),
        ':message'     => $message
    ]);

    $insertId = (int)$pdo->lastInsertId();
    log_activity($pdo, null, 'CONTACT_MESSAGE_SUBMITTED', 'contact_message', $insertId);

    redirect('contact.php', 'Thank you! Your message has been sent successfully. Our clinical administrative team will review it shortly.', 'success');
} catch (PDOException $e) {
    error_log("Contact submit error: " . $e->getMessage());
    redirect('contact.php', 'An unexpected error occurred while saving your message. Please try again.', 'danger');
}
