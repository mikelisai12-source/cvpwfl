<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$player_id = $_POST['player_id'] ?? null;
$season_id = $_POST['season_id'] ?? null;

if (!$player_id || !$season_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM players WHERE playerID = ? AND seasonID = ?");
    $success = $stmt->execute([$player_id, $season_id]);
    echo json_encode(['success' => $success, 'message' => $success ? 'Deleted' : 'Failed to delete']);
} catch (PDOException $e) {
    error_log('Delete player error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

