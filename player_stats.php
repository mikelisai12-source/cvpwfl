<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$player_id = $_GET['player_id'] ?? 0;
$stmt = $pdo->prepare("SELECT firstName, lastName FROM players WHERE playerID = ? LIMIT 1");
$stmt->execute([$player_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT p.seasonID, t.name as team_name, 
    SUM(pg.rushes) as rushes, SUM(pg.rush_yards) as rush_yards, SUM(pg.rush_tds) as rush_tds,
    SUM(pg.pass_attempts) as pass_attempts, SUM(pg.pass_completions) as pass_completions, 
    SUM(pg.pass_yards) as pass_yards, SUM(pg.pass_tds) as pass_tds, SUM(pg.pass_ints) as pass_ints,
    SUM(pg.receptions) as receptions, SUM(pg.receiving_yards) as receiving_yards, SUM(pg.receiving_tds) as receiving_tds,
    SUM(pg.tackles_assisted) as tackles_assisted, SUM(pg.tackles_unassisted) as tackles_unassisted,
    SUM(pg.tackles_for_loss) as tackles_for_loss, SUM(pg.sacks) as sacks,
    SUM(pg.fumbles_forced) as fumbles_forced, SUM(pg.fumbles_recovered) as fumbles_recovered,
    SUM(pg.fumbles_td) as fumbles_td, SUM(pg.interceptions) as interceptions,
    SUM(pg.interception_yards) as interception_yards, SUM(pg.interception_tds) as interception_tds
    FROM players p 
    LEFT JOIN playergamestats pg ON p.playerID = pg.pID 
    LEFT JOIN teams t ON p.teamID = t.teamID
    WHERE p.playerID = ? 
    GROUP BY p.seasonID 
    ORDER BY p.seasonID DESC");
$stmt->execute([$player_id]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'player' => $player,
    'stats' => $stats
]);
?>