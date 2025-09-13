<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$game_id = $_POST['game_id'];
$player_id = $_POST['player_id'];
$field = $_POST['field'];
$value = $_POST['value'];

$allowed_fields = ['rushes', 'rush_yards', 'rush_tds', 'pass_attempts', 'pass_completions', 'pass_yards', 'pass_tds', 'pass_ints', 'receptions', 'receiving_yards', 'receiving_tds', 'tackles_assisted', 'tackles_unassisted', 'tackles_for_loss', 'sacks', 'fumbles_forced', 'fumbles_recovered', 'fumbles_td', 'interceptions', 'interception_yards', 'interception_tds'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

$stmt = $pdo->prepare("UPDATE playergamestats SET $field = ? WHERE gID = ? AND pID = ?");
$success = $stmt->execute([$value, $game_id, $player_id]);

echo json_encode(['success' => $success, 'message' => $success ? 'Saved' : 'Failed to save']);
?>