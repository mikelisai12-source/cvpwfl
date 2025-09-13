<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || (!$_SESSION['stats'] && !$_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$game_id = $_POST['game_id'] ?? 0;
$team_id = $_POST['team_id'] ?? 0;
$type = $_POST['type'] ?? '';
$value = $_POST['value'] ?? null;
$player_id = $_POST['player_id'] ?? 0;

$league_grade = 6; // Peewee default; change if high school

function translate_pos($input) {
    if ($input < 0) {
        return -$input;
    } else {
        return 100 - $input;
    }
}

function reverse_translate_pos($internal) {
    if ($internal > 50) {
        return 100 - $internal;
    } else {
        return - $internal;
    }
}

try {
    if ($type === 'drive_start') {
        $drive_num = $_POST['drive_num'];
        $clock_input = $_POST['clock'];
        list($min, $sec) = explode(':', $clock_input);
        $clock = sprintf('00:%02d:%02d', (int)$min, (int)$sec);
        $qtr = $_POST['qtr'];
        $start = $_POST['start'];
        $pos = translate_pos($start);

        // Ensure row exists
        $stmt_check = $pdo->prepare("SELECT 1 FROM games WHERE gameID = ? AND teamID = ?");
        $stmt_check->execute([$game_id, $team_id]);
        if ($stmt_check->rowCount() == 0) {
            $season_id = $pdo->query("SELECT lCurrentSeason FROM league LIMIT 1")->fetchColumn();
            $stmt_insert = $pdo->prepare("INSERT INTO games (gameID, teamID, seasonID) VALUES (?, ?, ?)");
            $stmt_insert->execute([$game_id, $team_id, $season_id]);
        }

        $stmt = $pdo->prepare("UPDATE games SET d{$drive_num}_clock = ?, d{$drive_num}_qtr = ?, d{$drive_num}_start = ?, current_drive = ?, current_position = ?, current_qtr = ? WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$clock, $qtr, $start, $drive_num, $pos, $qtr, $game_id, $team_id]);

        if ($stmt->rowCount() == 0) {
            throw new Exception('Failed to update drive data');
        }
    } elseif ($type === 'rush') {
        $result = $_POST['result'];
        $pos_input = isset($_POST['pos']) ? $_POST['pos'] : null;
        $fumble_rec = isset($_POST['fumble_rec']) ? $_POST['fumble_rec'] : 0;

        // Ensure row exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
        $stmt->execute([$game_id, $player_id]);

        // Get current position and qtr
        $stmt = $pdo->prepare("SELECT current_position, current_qtr FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $team_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_pos = $game['current_position'];
        $qtr = $game['current_qtr'];

        if (is_null($current_pos) || is_null($qtr)) {
            throw new Exception('No active drive');
        }

        $yards = 0;
        $is_td = 0;
        $update_pos = true;

        if ($result === 'normal') {
            $new_pos = translate_pos($pos_input);
            $yards = $new_pos - $current_pos;
            if ($new_pos >= 100) {
                $is_td = 1;
                $yards = 100 - $current_pos;
            }
        } elseif ($result === 'td') {
            $new_pos = 100;
            $yards = 100 - $current_pos;
            $is_td = 1;
        } elseif ($result === 'fumble') {
            $update_pos = false;
        }

        $stmt = $pdo->prepare("UPDATE playergamestats SET rushes = rushes + 1, rush_yards = rush_yards + ? WHERE gID = ? AND pID = ?");
        $stmt->execute([$yards, $game_id, $player_id]);

        if ($result === 'fumble') {
            $stmt = $pdo->prepare("UPDATE playergamestats SET fumbles_lost = fumbles_lost + 1 WHERE gID = ? AND pID = ?");
            $stmt->execute([$game_id, $player_id]);
            if ($fumble_rec) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                $stmt->execute([$game_id, $fumble_rec]);
                $stmt = $pdo->prepare("UPDATE playergamestats SET fumbles_recovered = fumbles_recovered + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $fumble_rec]);
            }
        } elseif ($is_td) {
            $stmt = $pdo->prepare("UPDATE playergamestats SET rush_tds = rush_tds + 1 WHERE gID = ? AND pID = ?");
            $stmt->execute([$game_id, $player_id]);
            // REMOVED: Team score update for TD (moved to extra point submission)
        }

        if ($update_pos) {
            $stmt = $pdo->prepare("UPDATE games SET current_position = ? WHERE gameID = ? AND teamID = ?");
            $stmt->execute([$new_pos, $game_id, $team_id]);
        }
    } elseif ($type === 'pass') {
        $result = $_POST['result'];
        $receiver = isset($_POST['receiver']) ? $_POST['receiver'] : 0;
        $pos_input = isset($_POST['pos']) ? $_POST['pos'] : null;
        $fumble_rec = isset($_POST['fumble_rec']) ? $_POST['fumble_rec'] : 0;

        // Ensure row exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
        $stmt->execute([$game_id, $player_id]);

        // Get current position and qtr
        $stmt = $pdo->prepare("SELECT current_position, current_qtr FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $team_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_pos = $game['current_position'];
        $qtr = $game['current_qtr'];

        if (is_null($current_pos) || is_null($qtr)) {
            throw new Exception('No active drive');
        }

        $yards = 0;
        $is_td = 0;
        $update_pos = true;

        $stmt = $pdo->prepare("UPDATE playergamestats SET pass_attempts = pass_attempts + 1 WHERE gID = ? AND pID = ?");
        $stmt->execute([$game_id, $player_id]);

        if ($result === 'complete' || $result === 'complete_fumble') {
            $new_pos = translate_pos($pos_input);
            $yards = $new_pos - $current_pos;
            if ($new_pos >= 100) {
                $is_td = 1;
                $yards = 100 - $current_pos;
            }
            $stmt = $pdo->prepare("UPDATE playergamestats SET pass_completions = pass_completions + 1, pass_yards = pass_yards + ? WHERE gID = ? AND pID = ?");
            $stmt->execute([$yards, $game_id, $player_id]);
            $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
            $stmt->execute([$game_id, $receiver]);
            $stmt = $pdo->prepare("UPDATE playergamestats SET receptions = receptions + 1, receiving_yards = receiving_yards + ? WHERE gID = ? AND pID = ?");
            $stmt->execute([$yards, $game_id, $receiver]);
            if ($is_td) {
                $stmt = $pdo->prepare("UPDATE playergamestats SET pass_tds = pass_tds + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $player_id]);
                $stmt = $pdo->prepare("UPDATE playergamestats SET receiving_tds = receiving_tds + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $receiver]);
                // REMOVED: Team score update for TD (moved to extra point submission)
            }
            if ($result === 'complete_fumble') {
                $stmt = $pdo->prepare("UPDATE playergamestats SET fumbles_lost = fumbles_lost + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $receiver]);
                if ($fumble_rec) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                    $stmt->execute([$game_id, $fumble_rec]);
                    $stmt = $pdo->prepare("UPDATE playergamestats SET fumbles_recovered = fumbles_recovered + 1 WHERE gID = ? AND pID = ?");
                    $stmt->execute([$game_id, $fumble_rec]);
                }
                $update_pos = false;
            }
        } elseif ($result === 'td') {
            if (!$receiver) {
                throw new Exception('Receiver required for TD pass');
            }
            $new_pos = 100;
            $yards = 100 - $current_pos;
            $is_td = 1;
            $stmt = $pdo->prepare("UPDATE playergamestats SET pass_completions = pass_completions + 1, pass_yards = pass_yards + ? WHERE gID = ? AND pID = ?");
            $stmt->execute([$yards, $game_id, $player_id]);
            $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
            $stmt->execute([$game_id, $receiver]);
            $stmt = $pdo->prepare("UPDATE playergamestats SET receptions = receptions + 1, receiving_yards = receiving_yards + ? WHERE gID = ? AND pID = ?");
            $stmt->execute([$yards, $game_id, $receiver]);
            $stmt = $pdo->prepare("UPDATE playergamestats SET pass_tds = pass_tds + 1 WHERE gID = ? AND pID = ?");
            $stmt->execute([$game_id, $player_id]);
            $stmt = $pdo->prepare("UPDATE playergamestats SET receiving_tds = receiving_tds + 1 WHERE gID = ? AND pID = ?");
            $stmt->execute([$game_id, $receiver]);
            // REMOVED: Team score update for TD (moved to extra point submission)
        } elseif ($result === 'intercepted') {
            $stmt = $pdo->prepare("UPDATE playergamestats SET pass_ints = pass_ints + 1 WHERE gID = ? AND pID = ?");
            $stmt->execute([$game_id, $player_id]);
            $update_pos = false;
        } elseif ($result === 'incomplete') {
            $update_pos = false;
        }

        if ($update_pos) {
            $stmt = $pdo->prepare("UPDATE games SET current_position = ? WHERE gameID = ? AND teamID = ?");
            $stmt->execute([$new_pos, $game_id, $team_id]);
        }
    } elseif ($type === 'extra') {
        $td_time_input = $_POST['td_time'];
        list($min, $sec) = explode(':', $td_time_input);
        $td_time = sprintf('00:%02d:%02d', (int)$min, (int)$sec);
        $td_qtr = $_POST['td_qtr'];
        $extra_type = $_POST['extra_type'];
        $result = $_POST['result'] ?? null; // null for rush_pass
        $points = 0;
        $yards = 3; // fixed for XP

        // Get current drive to save end time/qtr
        $stmt = $pdo->prepare("SELECT current_drive FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $team_id]);
        $current_drive = $stmt->fetchColumn();

        if ($extra_type === 'rush') {
            $rusher_id = $_POST['xp_rusher_id'] ?? 0;
            if ($rusher_id) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                $stmt->execute([$game_id, $rusher_id]);
                $stmt = $pdo->prepare("UPDATE playergamestats SET xp_rushes = xp_rushes + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $rusher_id]);
                if ($result === 'success') {
                    $stmt = $pdo->prepare("UPDATE playergamestats SET xp_rush_yards = xp_rush_yards + ?, xp_rush_tds = xp_rush_tds + 1 WHERE gID = ? AND pID = ?");
                    $stmt->execute([$yards, $game_id, $rusher_id]);
                    $points = $league_grade <= 6 ? 1 : 2;
                }
            }
        } elseif ($extra_type === 'pass') {
            $passer_id = $_POST['xp_passer_id'] ?? 0;
            $receiver_id = $_POST['xp_receiver_id'] ?? 0;
            if ($passer_id) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                $stmt->execute([$game_id, $passer_id]);
                $stmt = $pdo->prepare("UPDATE playergamestats SET xp_pass_atts = xp_pass_atts + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $passer_id]);
                if ($result === 'success' && $receiver_id) {
                    $stmt = $pdo->prepare("UPDATE playergamestats SET xp_pass_comps = xp_pass_comps + 1, xp_pass_yards = xp_pass_yards + ?, xp_pass_tds = xp_pass_tds + 1 WHERE gID = ? AND pID = ?");
                    $stmt->execute([$yards, $game_id, $passer_id]);
                    $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                    $stmt->execute([$game_id, $receiver_id]);
                    $stmt = $pdo->prepare("UPDATE playergamestats SET xp_recepts = xp_recepts + 1, xp_recepts_yards = xp_recepts_yards + ?, xp_recepts_tds = xp_recepts_tds + 1 WHERE gID = ? AND pID = ?");
                    $stmt->execute([$yards, $game_id, $receiver_id]);
                    $points = $league_grade <= 6 ? 1 : 2;
                }
            }
        } elseif ($extra_type === 'kick') {
            if ($result === 'success') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                $stmt->execute([$game_id, $player_id]);
                $stmt = $pdo->prepare("UPDATE playergamestats SET xp_kick_att = xp_kick_att + 1, xp_kick_result = xp_kick_result + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $player_id]);
                $points = $league_grade <= 6 ? 2 : 1;
            } else {
                $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
                $stmt->execute([$game_id, $player_id]);
                $stmt = $pdo->prepare("UPDATE playergamestats SET xp_kick_att = xp_kick_att + 1 WHERE gID = ? AND pID = ?");
                $stmt->execute([$game_id, $player_id]);
            }
        }

        // Always add TD (6) + extra points to the specified TD quarter
        $total_points = 6 + $points;
        $stmt = $pdo->prepare("UPDATE games SET score_qtr$td_qtr = score_qtr$td_qtr + ?, score_final = score_final + ? WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$total_points, $total_points, $game_id, $team_id]);

        // Save TD time and qtr as drive end if current_drive is active
        if ($current_drive) {
            $stmt = $pdo->prepare("UPDATE games SET d{$current_drive}_clock_end = ?, d{$current_drive}_qtr_end = ?, d{$current_drive}_fp_end = 0 WHERE gameID = ? AND teamID = ?");
            $stmt->execute([$td_time, $td_qtr, $game_id, $team_id]);
        }

    } elseif ($type === 'ot') {
        $points = $_POST['points'];
        $stmt = $pdo->prepare("UPDATE games SET score_qtr5 = ?, score_final = score_final + ? WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$points, $points, $game_id, $team_id]);
    } elseif ($type === 'gameover') {
        $winner_team_id = $_POST['winner_team_id'];
        $stmt = $pdo->prepare("UPDATE games SET winner = 1 WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $winner_team_id]);
        $stmt = $pdo->prepare("UPDATE games SET winner = 0 WHERE gameID = ? AND teamID != ?");
        $stmt->execute([$game_id, $winner_team_id]);

        // Update standings
        // Fetch seasonID (assuming consistent across teams in the game)
        $stmt = $pdo->prepare("SELECT seasonID FROM games WHERE gameID = ? LIMIT 1");
        $stmt->execute([$game_id]);
        $season_id = $stmt->fetchColumn();

        if (!$season_id) {
            throw new Exception('Season ID not found for game');
        }

        // Fetch both teams' data
        $stmt = $pdo->prepare("SELECT teamID, score_final, winner FROM games WHERE gameID = ?");
        $stmt->execute([$game_id]);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($teams) !== 2) {
            throw new Exception('Invalid number of teams found for game');
        }

        // Identify teams and scores
        $team1 = $teams[0];
        $team2 = $teams[1];
        $team1_id = $team1['teamID'];
        $team1_score = $team1['score_final'];
        $team1_winner = $team1['winner'];
        $team2_id = $team2['teamID'];
        $team2_score = $team2['score_final'];
        $team2_winner = $team2['winner'];

        // Update standings for team1
        $stmt = $pdo->prepare("UPDATE standings SET 
            sWins = sWins + ?, 
            sLosses = sLosses + ?, 
            sPointsFor = sPointsFor + ?, 
            sPointsAgainst = sPointsAgainst + ? 
            WHERE steamID = ? AND ssesaonID = ?");
        $stmt->execute([
            $team1_winner ? 1 : 0,
            $team1_winner ? 0 : 1,
            $team1_score,
            $team2_score,
            $team1_id,
            $season_id
        ]);

        if ($stmt->rowCount() == 0) {
            throw new Exception('Failed to update standings for team ' . $team1_id);
        }

        // Update standings for team2
        $stmt->execute([
            $team2_winner ? 1 : 0,
            $team2_winner ? 0 : 1,
            $team2_score,
            $team1_score,
            $team2_id,
            $season_id
        ]);

        if ($stmt->rowCount() == 0) {
            throw new Exception('Failed to update standings for team ' . $team2_id);
        }
    } elseif ($type === 'end_drive') {
        // Get current drive first
        $stmt = $pdo->prepare("SELECT current_drive, current_position FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $team_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_drive = $row['current_drive'];
        $current_position = $row['current_position'];

        $set_ends = false;
        if (isset($_POST['clock_end']) && isset($_POST['qtr_end'])) {
            $set_ends = true;
            $clock_end_input = $_POST['clock_end'];
            if (!empty($clock_end_input)) {
                list($min, $sec) = explode(':', $clock_end_input);
                $clock_end = sprintf('00:%02d:%02d', (int)$min, (int)$sec);
            } else {
                $clock_end = null;
            }
            $qtr_end = $_POST['qtr_end'];
            $fp_end = reverse_translate_pos($current_position);
        }

        // Null out currents
        $stmt = $pdo->prepare("UPDATE games SET current_drive = NULL, current_position = NULL, current_qtr = NULL WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $team_id]);

        // Set ends if provided (from modal)
        if ($current_drive && $set_ends) {
            $stmt = $pdo->prepare("UPDATE games SET d{$current_drive}_clock_end = ?, d{$current_drive}_qtr_end = ?, d{$current_drive}_fp_end = ? WHERE gameID = ? AND teamID = ?");
            $stmt->execute([$clock_end, $qtr_end, $fp_end, $game_id, $team_id]);
        }
    } elseif ($type === 'defense') {
        $stat = $_POST['stat'] ?? '';
        $delta = (int) $_POST['value'] ?? 0; // +1 or -1

        $allowed_stats = ['tackles_assisted', 'tackles_unassisted', 'sacks', 'fumbles_forced', 'fumbles_recovered', 'interceptions'];
        if (!in_array($stat, $allowed_stats)) {
            throw new Exception('Invalid stat');
        }

        // Ensure row exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO playergamestats (gID, pID) VALUES (?, ?)");
        $stmt->execute([$game_id, $player_id]);

        // Update stat with delta, preventing negative
        $stmt = $pdo->prepare("UPDATE playergamestats SET `$stat` = GREATEST(0, `$stat` + ?) WHERE gID = ? AND pID = ?");
        $stmt->execute([$delta, $game_id, $player_id]);
    } elseif ($type === 'penalty') { // NEW: Penalty case added
        $off_team_id = $_POST['off_team_id'] ?? 0;
        $penalized_team_id = $_POST['penalized_team_id'] ?? 0;
        $new_field = $_POST['new_pos'] ?? null;

        if (!$new_field || $new_field < -49 || $new_field > 49) {
            throw new Exception('Invalid new field position');
        }

        $new_internal = translate_pos($new_field);

        // Get current position (from offense team's row)
        $stmt = $pdo->prepare("SELECT current_position, current_qtr FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $off_team_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_internal = $game['current_position'] ?? null;

        if (is_null($current_internal)) {
            throw new Exception('No active drive or position');
        }

        $diff = $new_internal - $current_internal;
        $yards = abs($diff);

        // Update position in offense team's row
        $stmt = $pdo->prepare("UPDATE games SET current_position = ? WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$new_internal, $game_id, $off_team_id]);

        // Increment penalties and penalty_yards for penalized team
        $stmt = $pdo->prepare("UPDATE games SET penalties = penalties + 1, penalty_yards = penalty_yards + ? WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$yards, $game_id, $penalized_team_id]);

    }    

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}