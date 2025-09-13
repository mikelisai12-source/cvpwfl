<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'];
$field = $_POST['field'];
$value = $_POST['value'];

$allowed_fields = ['firstName', 'lastName', 'email', 'phone', 'teamID', 'admin', 'headCoach', 'asstCoach', 'stats'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

if (in_array($field, ['admin', 'headCoach', 'asstCoach', 'stats'])) {
    $value = $value == 'true' ? 1 : 0;
}

$stmt = $pdo->prepare("UPDATE users SET $field = ? WHERE userID = ?");
$success = $stmt->execute([$value, $user_id]);

echo json_encode(['success' => $success, 'message' => $success ? 'Saved' : 'Failed to save']);