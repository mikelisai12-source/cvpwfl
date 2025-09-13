<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$field = $_POST['field'];
$value = $_POST['value'];

$allowed_fields = ['email', 'phone'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if ($field === 'email') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND userID != ?");
    $stmt->execute([$value, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email is already in use']);
        exit;
    }
}

$stmt = $pdo->prepare("UPDATE users SET $field = ? WHERE userID = ?");
$success = $stmt->execute([$value, $user_id]);

echo json_encode(['success' => $success, 'message' => $success ? 'Saved' : 'Failed to save']);
?>

