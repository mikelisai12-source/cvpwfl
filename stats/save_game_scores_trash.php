<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !($_SESSION['admin'] || $_SESSION['stats'])) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$game_id = $_POST['game_id'];
$home_score = $_POST['homeScore'];
$away_score = $_POST['awayScore'];

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT homeTeamID, awayTeamID FROM games WHERE gameID = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $winner = $home_score > $away_score ? $game['homeTeamID'] : ($away_score > $home_score ? $game['awayTeamID'] : 0);
    $loser = $winner == $game['homeTeamID'] ? $game['awayTeamID'] : ($winner == $game['awayTeamID'] ? $game['homeTeamID'] : 0);
    
    $stmt = $pdo->prepare("UPDATE games SET homeScore = ?, awayScore = ?, winner = ?, loser = ? WHERE gameID = ?");
    $stmt->execute([$home_score, $away_score, $winner, $loser, $game_id]);
    
    $stmt = $pdo->prepare("UPDATE standings SET sWins = sWins + ?, sLosses = sLosses + ?, sPointsFor = sPointsFor + ?, sPointsAgainst = sPointsAgainst + ? WHERE steamID = ? AND ssesaonID = (SELECT MAX(lCurrentSeason) FROM league)");
    if ($winner == $game['homeTeamID']) {
        $stmt->execute([1, 0, $home_score, $away_score, $game['homeTeamID']]);
        $stmt->execute([0, 1, $away_score, $home_score, $game['awayTeamID']]);
    } elseif ($winner == $game['awayTeamID']) {
        $stmt->execute([1, 0, $away_score, $home_score, $game['awayTeamID']]);
        $stmt->execute([0, 1, $home_score, $away_score, $game['homeTeamID']]);
    } else {
        $stmt->execute([0, 0, $home_score, $away_score, $game['homeTeamID']]);
        $stmt->execute([0, 0, $away_score, $home_score, $game['awayTeamID']]);
    }
    
    $pdo->commit();
    header('Location: /cvpwfl/stats/game_stats_offense.php?game_id=' . $game_id);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: /cvpwfl/stats/game_stats_offense.php?game_id=' . $game_id . '&error=' . urlencode($e->getMessage()));
    exit;
}
?>