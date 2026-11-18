<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrf($token)) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token, please refresh the page.'], 419);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
}

$pdo = getDB();
$check = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
$check->execute([$email]);

if ($check->fetch()) {
    jsonResponse(['success' => true, 'message' => "You're already subscribed. Thanks for being with us!"]);
}

$stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())');
$stmt->execute([$email]);

jsonResponse(['success' => true, 'message' => 'Thanks for subscribing!']);
