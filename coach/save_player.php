<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || (!$_SESSION['admin'] && !$_SESSION['headCoach'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$player_id = $_POST['player_id'] ?? null;
$season_id = $_POST['season_id'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? null;

if (!$player_id || !$season_id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$allowed_fields = ['firstName', 'lastName', 'email', 'phone', 'birthday', 'grade', 'jerseyNumber', 'height', 'weight', 'fortySpeed', 'active', 'web', 'teamID'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field: ' . htmlspecialchars($field)]);
    exit;
}

// Convert empty string to NULL for database compatibility (fixes error when clearing numeric fields)
if ($value === '') {
    $value = null;
}

// Additional checks for head coaches
if (!$_SESSION['admin']) {
    // Prevent editing restricted fields
    if (in_array($field, ['firstName', 'lastName', 'grade'])) {
        echo json_encode(['success' => false, 'message' => 'Cannot edit this field']);
        exit;
    }

    // Verify the player belongs to the coach's team, is in current season (already in query), and grade < 7
    $checkStmt = $pdo->prepare("SELECT teamID, grade FROM players WHERE playerID = ? AND seasonID = ?");
    $checkStmt->execute([$player_id, $season_id]);
    $playerData = $checkStmt->fetch();
    if (!$playerData || $playerData['teamID'] !== $_SESSION['teamID'] || $playerData['grade'] >= 7) {
        echo json_encode(['success' => false, 'message' => 'Player not on your team or ineligible for editing']);
        exit;
    }
}

// Validate birthday field
if ($field === 'birthday' && $value !== null) {
    // Check if value is a valid date in YYYY-MM-DD format
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("UPDATE players SET $field = ? WHERE playerID = ? AND seasonID = ?");
    $success = $stmt->execute([$value, $player_id, $season_id]);
    echo json_encode(['success' => $success, 'message' => $success ? 'Saved' : 'Failed to save']);
} catch (PDOException $e) {
    error_log('Save player error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}