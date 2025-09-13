$(document).ready(function() {
    // Draft timer
    var draftTimeText = $('#draft-timer').data('draft-time');
    console.log('Draft time text:', draftTimeText);
    var draftTime = null;
    if (draftTimeText) {
        try {
            draftTime = new Date(draftTimeText).getTime();
            console.log('Parsed draft time (local):', draftTime, new Date(draftTime));
        } catch (e) {
            console.log('Error parsing draft time:', e.message);
        }
    }
    if (draftTime && !isNaN(draftTime)) {
        var draftTimer = setInterval(function() {
            var now = new Date().getTime();
            var distance = now - draftTime;
            console.log('Draft timer distance:', distance, 'Local time:', new Date(now));
            if (distance < 0) {
                clearInterval(draftTimer);
                $('#draft-timer').text('Time not available');
                console.log('Draft time invalid');
                return;
            }
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            $('#draft-timer').text(days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's');
        }, 1000);
    } else {
        $('#draft-timer').text('Time not available');
        console.log('Draft time invalid or unset');
    }


    
    // Updated JavaScript in scripts.js for the countdown timer
var campDateText = $('#countdown').data('camp-date');
console.log('Camp date text:', campDateText);
var campDate = null;
if (campDateText) {
    try {
        campDate = new Date(campDateText).getTime();
        console.log('Parsed camp date (local):', campDate, new Date(campDate));
    } catch (e) {
        console.log('Error parsing camp date:', e.message);
    }
}
if (campDate && !isNaN(campDate)) {
    var now = new Date().getTime();
    var distance = campDate - now;
    if (distance < 0) {
        $('.countdown-section').hide();
        console.log('Camp has already started - hiding countdown section');
    } else {
        var countdown = setInterval(function() {
            var now = new Date().getTime();
            var distance = campDate - now;
            console.log('Countdown distance:', distance, 'Local time:', new Date(now));
            if (distance < 0) {
                clearInterval(countdown);
                $('.countdown-section').hide();
                console.log('Camp has started - hiding countdown section');
                return;
            }
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            $('#countdown').text(days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's');
        }, 1000);
    }
} else {
    $('#countdown').text('Camp date TBD');
    console.log('Camp date invalid or unset');
}
    
    // Toggle details row
    $(document).on('click', '.toggle-details', function() {
        var playerId = $(this).data('player-id');
        var $detailsRow = $(`.details-row[data-player-id="${playerId}"]`);
        var isVisible = $detailsRow.is(':visible');
        $(this).text(isVisible ? 'Show' : 'Hide');
        $detailsRow.toggle();
    });

    // Sorting table
    $(document).on('click', '.sortable', function() {
        var $table = $(this).closest('table');
        var $tbody = $table.find('tbody');
        var column = $(this).data('sort');
        var order = $(this).hasClass('asc') ? 'desc' : 'asc';
        var isRookiesOrDraftTable = $table.hasClass('rookies-table') || $table.hasClass('draft-table');

        // Reset sort indicators
        $table.find('.sortable').removeClass('asc desc');
        $table.find('.sort-indicator').text('');
        $(this).addClass(order);
        $(this).find('.sort-indicator').text(order === 'asc' ? '↑' : '↓');

        // Get all player rows
        var rows = [];
        $tbody.find('tr:not(.details-row)').each(function() {
            var $playerRow = $(this);
            if (isRookiesOrDraftTable) {
                rows.push({ player: $playerRow, details: null });
            } else {
                var $detailsRow = $playerRow.next('.details-row');
                rows.push({ player: $playerRow, details: $detailsRow });
            }
        });

        // Sort rows
        rows.sort(function(a, b) {
            var aValue, bValue;
if (column === 'active' || column === 'web') {
    var $inputA = a.player.find(`input[data-field="${column}"]`);
    aValue = $inputA.length ? $inputA.is(':checked') : (a.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text().trim() === 'Yes');
    var $inputB = b.player.find(`input[data-field="${column}"]`);
    bValue = $inputB.length ? $inputB.is(':checked') : (b.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text().trim() === 'Yes');
} else if (column === 'weight' || column === 'grade' || column === 'fortySpeed') {
    aValue = parseFloat(a.player.find(`input[data-field="${column}"]`).val()) || parseFloat(a.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text()) || Infinity;
    bValue = parseFloat(b.player.find(`input[data-field="${column}"]`).val()) || parseFloat(b.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text()) || Infinity;
} else if (column === 'height') {
    var aHeightText = a.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text();
    var bHeightText = b.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text();
    aValue = aHeightText ? parseFloat(aHeightText.match(/(\d+)\s*ft\s*(\d+)\s*in/) ? (parseInt(aHeightText.match(/(\d+)\s*ft/)[1]) * 12 + parseInt(aHeightText.match(/(\d+)\s*in/)[1])) : Infinity) : Infinity;
    bValue = bHeightText ? parseFloat(bHeightText.match(/(\d+)\s*ft\s*(\d+)\s*in/) ? (parseInt(bHeightText.match(/(\d+)\s*ft/)[1]) * 12 + parseInt(bHeightText.match(/(\d+)\s*in/)[1])) : Infinity) : Infinity;
} else {
    aValue = a.player.find(`[data-field="${column}"]`).val() || a.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text();
    bValue = b.player.find(`[data-field="${column}"]`).val() || b.player.find(`td:nth-child(${$(`th[data-sort="${column}"]`).index() + 1})`).text();
    aValue = aValue.toLowerCase();
    bValue = bValue.toLowerCase();
}

            if (aValue === bValue) return 0;
            if (aValue === Infinity) return 1;
            if (bValue === Infinity) return -1;
            return order === 'asc' ? (aValue < bValue ? -1 : 1) : (aValue > bValue ? -1 : 1);
        });

        // Reattach sorted rows
        $tbody.empty();
        $.each(rows, function(index, row) {
            $tbody.append(row.player);
            if (row.details && row.details.length) {
                $tbody.append(row.details);
            }
        });
    });

    // Modal data entry for inputs
    $(document).on('change blur', '.modal-input', function() {
        var $this = $(this);
        var userId = $this.data('user-id');
        var playerId = $this.data('player-id');
        var seasonId = $this.data('season-id');
        var gameId = $this.data('game-id');
        var field = $this.data('field');
        var value;

        if ($this.hasClass('role-checkbox')) {
            value = $this.is(':checked') ? 'true' : 'false';
        } else if ($this.is(':checkbox')) {
            value = $this.is(':checked') ? 1 : 0;
        } else if (field === 'height_feet' || field === 'height_inches') {
            var $container = $this.closest('.details-content');
            var feet = parseInt($container.find('.height-input[data-field="height_feet"]').val()) || 0;
            var inches = parseFloat($container.find('.height-input[data-field="height_inches"]').val()) || 0;
            value = (feet > 0 || inches > 0) ? (feet * 12 + inches) : null;
            field = 'height';
        } else {
            value = $this.val() !== '' ? $this.val() : null;
        }
        
        var data = { field: field, value: value };
        if (userId) data.user_id = userId;
        if (playerId) data.player_id = playerId;
        if (seasonId) data.season_id = seasonId;
        if (gameId) data.game_id = gameId;

        $.ajax({
            url: userId ? '/cvpwfl/admin/save_user.php' : gameId ? '/cvpwfl/admin/save_gamestats.php' : '/cvpwfl/coach/save_player.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // ...
                } else {
                    alert('Failed to save: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', xhr.responseText, status, error);
                alert('Error saving data: ' + xhr.responseText);
            }
        });
    });

    // Player stats modal
    $(document).on('click', '.player-stats', function(e) {
        e.preventDefault();
        var playerId = $(this).data('player-id');
        $.ajax({
            url: '/cvpwfl/player_stats.php',
            method: 'GET',
            data: { player_id: playerId },
            dataType: 'json',
            success: function(data) {
                var html = '<h2>' + data.player.player + '</h2>';
                html += '<table><thead><tr><th>Season</th><th>Team</th><th>Rushes</th><th>Rush Yards</th><th>Rush TDs</th><th>Pass Att</th><th>Pass Comp</th><th>Pass Yards</th><th>Pass TDs</th><th>Pass Int</th><th>Receptions</th><th>Rec Yards</th><th>Rec TDs</th><th>Tackles Ast</th><th>Tackles Unast</th><th>TFL</th><th>Sacks</th><th>Fumbles Forced</th><th>Fumbles Rec</th><th>Fumble TDs</th><th>Int</th><th>Int Yards</th><th>Int TDs</th></tr></thead><tbody>';
                data.stats.forEach(function(stat) {
                    html += '<tr><td>' + stat.seasonID + '</td><td>' + (stat.team_name || 'N/A') + '</td><td>' + (stat.rushes || 0) + '</td><td>' + (stat.rush_yards || 0) + '</td><td>' + (stat.rush_tds || 0) + '</td><td>' + (stat.pass_attempts || 0) + '</td><td>' + (stat.pass_completions || 0) + '</td><td>' + (stat.pass_yards || 0) + '</td><td>' + (stat.pass_tds || 0) + '</td><td>' + (stat.pass_ints || 0) + '</td><td>' + (stat.receptions || 0) + '</td><td>' + (stat.receiving_yards || 0) + '</td><td>' + (stat.receiving_tds || 0) + '</td><td>' + (stat.tackles_assisted || 0) + '</td><td>' + (stat.tackles_unassisted || 0) + '</td><td>' + (stat.tackles_for_loss || 0) + '</td><td>' + (stat.sacks || 0) + '</td><td>' + (stat.fumbles_forced || 0) + '</td><td>' + (stat.fumbles_recovered || 0) + '</td><td>' + (stat.fumbles_td || 0) + '</td><td>' + (stat.interceptions || 0) + '</td><td>' + (stat.interception_yards || 0) + '</td><td>' + (stat.interception_tds || 0) + '</td></tr>';
                });
                html += '</tbody></table>';
                $('#player-stats-content').html(html);
                $('#player-stats-modal').show();
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', xhr.responseText, status, error);
                alert('Error loading player stats');
            }
        });
    });

    // Draft confirmation modal
    $(document).on('click', '.draft-submit-button', function(e) {
        e.preventDefault();
        var formId = $(this).data('form-id');
        $('#confirm-draft-modal').data('form-id', formId).show();
    });

    $(document).on('click', '.confirm-button', function() {
        var formId = $('#confirm-draft-modal').data('form-id');
        $('#' + formId).submit();
        $('#confirm-draft-modal').hide();
    });

    $(document).on('click', '.cancel-button, #confirm-draft-modal .close', function() {
        $('#confirm-draft-modal').hide();
    });

    $(document).on('click', function(event) {
        if (event.target.id === 'confirm-draft-modal' || event.target.id === 'player-stats-modal') {
            $('#' + event.target.id).hide();
        }
    });

    // Week tabs for game recaps
    $(document).on('click', '.week-tab', function(e) {
        e.preventDefault();
        $('.recap').hide();
        $('#recap-week-' + $(this).data('week')).show();
    });

    // Stat collection - new OFF poss. button (now team-specific, reloads page)
    $(document).on('click', '.new-poss-button', function() {
        var teamId = $(this).data('team-id');
        var gameId = $('#game-info').data('game-id');
        window.location.href = '?game_id=' + gameId + '&off_team_id=' + teamId;
    });

    // Stat collection - OT Scoring button (shows modal)
    $(document).on('click', '.ot-scoring-button', function() {
        var teamId = $(this).data('team-id');
        $('#ot-modal').data('team-id', teamId).show();
    });

    // Stat collection - Game Over button (shows modal)
    $(document).on('click', '.game-over-button', function() {
        $('#gameover-modal').show();
    });

    // Submit drive start
    $(document).on('click', '#submit-drive-start', function() {
        var driveNum = $('.drive-clock').data('drive');
        var clock = $('.drive-clock').val();
        var qtr = $('.drive-qtr').val();
        var start = $('.drive-start').val();
        var gameId = $('#game-info').data('game-id');
        var teamId = $(this).closest('.stat-section').data('team-id');

        if (!clock.match(/^([0-5]?[0-9]):([0-5][0-9])$/)) {
            alert('Invalid clock format. Use mm:ss');
            return;
        }

        $.ajax({
            url: '/cvpwfl/stats/save_stats.php',
            method: 'POST',
            data: { game_id: gameId, team_id: teamId, type: 'drive_start', drive_num: driveNum, clock: clock, qtr: qtr, start: start },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('A new offensive drive has been started');
                    location.reload(); // Simple refresh for updated stats
                } else {
                    alert('Failed to save drive data: ' + (response.message || 'Unknown error'));
                }
            }
        });
    });

    // Rush button click
    $(document).on('click', '.rush-button', function() {
        var playerId = $(this).data('player-id');
        var teamId = $(this).closest('.stat-section').data('team-id');
        $('#rush-modal').data('player-id', playerId).data('team-id', teamId).show();
    });

    // Pass button click
    $(document).on('click', '.pass-button', function() {
        var playerId = $(this).data('player-id');
        var teamId = $(this).closest('.stat-section').data('team-id');
        $('#pass-modal').data('player-id', playerId).data('team-id', teamId).show();
    });

    // Submit rush
    $(document).on('click', '#submit-rush', function() {
        var playerId = $('#rush-modal').data('player-id');
        var teamId = $('#rush-modal').data('team-id');
        var result = $('input[name="rush-result"]:checked').val();
        var pos = (result === 'normal') ? $('#rush-pos').val() : (result === 'td' ? 0 : null);
        var fumbleRec = (result === 'fumble') ? $('#fumble-rec-player').val() : null;
        var gameId = $('#game-info').data('game-id');

        $.ajax({
            url: '/cvpwfl/stats/save_stats.php',
            method: 'POST',
            data: { game_id: gameId, team_id: teamId, player_id: playerId, type: 'rush', result: result, pos: pos, fumble_rec: fumbleRec },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#rush-modal').hide();
                    console.log('Rush result:', result);
                    if (result === 'td') {
                        console.log('Showing extra point modal for rush TD');
                        $('#extra-point-modal').data('team-id', teamId).show();
                    } else {
                        location.reload(); // Refresh if not TD
                    }
                } else {
                    alert('Failed to save rush: ' + (response.message || 'Unknown error'));
                }
            }
        });
    });

// Pass submit
$(document).on('click', '#submit-pass', function() {
    var result = $('input[name="pass-result"]:checked').val();
    var receiver = $('#pass-receiver').val();
    var pos = $('#pass-pos').val();
    var fumble_rec = $('#fumble-rec-player-pass').val();
    var playerId = $('#pass-modal').data('player-id');
    var teamId = $('#pass-modal').data('team-id');
    var gameId = $('#game-info').data('game-id');
    var data = { game_id: gameId, team_id: teamId, type: 'pass', player_id: playerId, result: result };
    if (result === 'complete' || result === 'complete_fumble' || result === 'td') {
        data.receiver = receiver;
    }
    if (result === 'complete' || result === 'complete_fumble') {
        data.pos = pos;
    }
    if (result === 'complete_fumble') {
        data.fumble_rec = fumble_rec;
    }
    $.ajax({
        url: '/cvpwfl/stats/save_stats.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#pass-modal').hide();
                var result = $('input[name="pass-result"]:checked').val();
                if (result === 'td') {
                    $('#extra-point-modal').data('team-id', teamId).show();
                } else if (result === 'intercepted') {
                    $('#drive-over-modal').data('team-id', teamId).show();
                } else {
                    location.reload();
                }
            } else {
                alert('Failed to save pass: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX error:', xhr.responseText, status, error);
            alert('Error submitting pass: ' + error);
        }
    });
});

// Extra point submit
$(document).on('click', '#submit-extra', function() {
    var tdTime = $('#td-time').val();
    var tdQtr = $('#td-qtr').val();
    var extraType = $('input[name="extra-type"]:checked').val();
    var extraResult = $('input[name="extra-result"]:checked').val();
    var gameId = $('#game-info').data('game-id');
    var teamId = $('#extra-point-modal').data('team-id');
    var data = { game_id: gameId, team_id: teamId, type: 'extra', td_time: tdTime, td_qtr: tdQtr, extra_type: extraType, result: extraResult };

    // Add type-specific fields
    if (extraType === 'kick') {
        data.player_id = $('#extra-kick-player').val();
    } else if (extraType === 'rush') {
        data.xp_rusher_id = $('#xp-rusher').val();
    } else if (extraType === 'pass') {
        data.xp_passer_id = $('#xp-passer').val();
        if (extraResult === 'success') {
            data.xp_receiver_id = $('#xp-receiver').val();
        }
    }

    $.ajax({
        url: '/cvpwfl/stats/save_stats.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#extra-point-modal').hide();
                // Automatically end the drive after extra point
                $.ajax({
                    url: '/cvpwfl/stats/save_stats.php',
                    method: 'POST',
                    data: { game_id: gameId, team_id: teamId, type: 'end_drive' },
                    dataType: 'json',
                    success: function(endResponse) {
                        if (endResponse.success) {
                            window.location.href = '/cvpwfl/stats/stats_collection.php?game_id=' + gameId;
                        } else {
                            alert('Failed to end drive: ' + (endResponse.message || 'Unknown error'));
                        }
                    }
                });
            } else {
                alert('Failed to save extra point: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX error:', xhr.responseText, status, error);
            alert('Error submitting extra point: ' + error);
        }
    });
});

    // OT scoring submit
    $(document).on('click', '#submit-ot', function() {
        var points = $('#ot-points').val();
        var gameId = $('#game-info').data('game-id');
        var teamId = $('#ot-modal').data('team-id');

        $.ajax({
            url: '/cvpwfl/stats/save_stats.php',
            method: 'POST',
            data: { game_id: gameId, team_id: teamId, type: 'ot', points: points },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#ot-modal').hide();
                } else {
                    alert('Failed to save OT scoring: ' + (response.message || 'Unknown error'));
                }
            }
        });
    });

    // Game over submit
    $(document).on('click', '#submit-gameover', function() {
        var winnerTeamId = $('input[name="winner"]:checked').val();
        var gameId = $('#game-info').data('game-id');

        $.ajax({
            url: '/cvpwfl/stats/save_stats.php',
            method: 'POST',
            data: { game_id: gameId, type: 'gameover', winner_team_id: winnerTeamId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Game over saved');
                    window.location.href = '/cvpwfl/index.php';
                } else {
                    alert('Failed to save game over: ' + (response.message || 'Unknown error'));
                }
            }
        });
    });

    // Close modals
    $(document).on('click', '.close, .cancel', function() {
        $(this).closest('.modal').hide();
    });

    // Show/hide based on radio choices for rush
$(document).on('change', 'input[name="rush-result"]', function() {
    var val = $(this).val();
    $('#rush-pos-label').css('display', (val === 'normal' || val === 'fumble') ? 'block' : 'none');
    $('#fumble-rec-label').css('display', val === 'fumble' ? 'block' : 'none');
});

// Optional: Trigger the change event initially to apply the logic on page load (since 'normal' is checked by default)
$('input[name="rush-result"]:checked').trigger('change');

// Show/hide based on radio choices for pass
$(document).on('change', 'input[name="pass-result"]', function() {
    var val = $(this).val();
    var isCompleteOrTd = val === 'complete' || val === 'complete_fumble' || val === 'td';
    $('#complete-receiver-label').toggle(isCompleteOrTd);
    $('#complete-pos-label').toggle(val === 'complete' || val === 'complete_fumble');
    $('#fumble-rec-label-pass').toggle(val === 'complete_fumble');
});

    // Show/hide for extra point options
    $(document).on('change', 'input[name="extra-type"]', function() {
        var val = $(this).val();
        $('#xp-rush-result-label, #xp-rusher-label').toggle(val === 'rush');
        $('#xp-pass-result-label, #xp-passer-label').toggle(val === 'pass');
        $('#xp-receiver-label').toggle(val === 'pass' && $('input[name="extra-result"]:checked').length > 0 && $('input[name="extra-result"]:checked').val() === 'success');
        $('#kick-player-label, #result-label').toggle(val === 'kick');
        checkExtraForm();
    });

    // Show/hide receiver for pass success
    $(document).on('change', 'input[name="extra-result"]', function() {
        if ($('input[name="extra-type"]:checked').val() === 'pass') {
            $('#xp-receiver-label').toggle(this.value === 'success');
        }
        checkExtraForm();
    });

    // Validate extra point form and enable/disable submit
    function checkExtraForm() {
        var type = $('input[name="extra-type"]:checked').val();
        var result = $('input[name="extra-result"]:checked').val();
        var tdTime = $('#td-time').val();
        var tdQtr = $('#td-qtr').val();
        var isValid = tdTime && tdQtr && type;

        if (type === 'rush') {
            isValid = isValid && result && $('#xp-rusher').val();
        } else if (type === 'pass') {
            isValid = isValid && result && $('#xp-passer').val();
            if (result === 'success') {
                isValid = isValid && $('#xp-receiver').val();
            }
        } else if (type === 'kick') {
            isValid = isValid && result && $('#extra-kick-player').val();
        }

        $('#submit-extra').prop('disabled', !isValid);
    }

    // Attach change listeners for extra point form validation
    $(document).on('change', 'input[name="extra-type"], input[name="extra-result"], #xp-rusher, #xp-passer, #xp-receiver, #extra-kick-player, #td-qtr', checkExtraForm);
    $(document).on('blur', '#td-time-min, #td-time-sec', function() {
        updateTdClock();
        checkExtraForm();
    });

    // Run validation on modal show
    $(document).on('show', '#extra-point-modal', checkExtraForm);

    // Clock input formatting for drive start
    $(document).on('input', '#drive-time-min', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        $(this).val(val);
    });

    $(document).on('blur', '#drive-time-min', function() {
        let val = $(this).val();
        let num = parseInt(val || 0);
        if (num > 12) val = '12';
        $(this).val(val.padStart(2, '0'));
        updateDriveClock();
    });

    $(document).on('input', '#drive-time-sec', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        $(this).val(val);
    });

    $(document).on('blur', '#drive-time-sec', function() {
        let val = $(this).val();
        let num = parseInt(val || 0);
        if (num > 59) val = '59';
        $(this).val(val.padStart(2, '0'));
        updateDriveClock();
    });

    function updateDriveClock() {
        let min = $('#drive-time-min').val();
        let sec = $('#drive-time-sec').val();
        if (min && sec) {
            $('.drive-clock').val(min + ':' + sec);
        }
    }

    // Clock input formatting for TD time
    $(document).on('input', '#td-time-min', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        $(this).val(val);
    });

    $(document).on('blur', '#td-time-min', function() {
        let val = $(this).val();
        let num = parseInt(val || 0);
        if (num > 12) val = '12';
        $(this).val(val.padStart(2, '0'));
        updateTdClock();
    });

    $(document).on('input', '#td-time-sec', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        $(this).val(val);
    });

    $(document).on('blur', '#td-time-sec', function() {
        let val = $(this).val();
        let num = parseInt(val || 0);
        if (num > 59) val = '59';
        $(this).val(val.padStart(2, '0'));
        updateTdClock();
    });

    function updateTdClock() {
        let min = $('#td-time-min').val();
        let sec = $('#td-time-sec').val();
        if (min && sec) {
            $('#td-time').val(min + ':' + sec);
        }
    }
    
        // Clock input formatting for drive end time
    $(document).on('input', '#drive-end-time-min', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        $(this).val(val);
    });

    $(document).on('blur', '#drive-end-time-min', function() {
        let val = $(this).val();
        let num = parseInt(val || 0);
        if (num > 12) val = '12';
        $(this).val(val.padStart(2, '0'));
        updateDriveEndClock();
    });

    $(document).on('input', '#drive-end-time-sec', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        $(this).val(val);
    });

    $(document).on('blur', '#drive-end-time-sec', function() {
        let val = $(this).val();
        let num = parseInt(val || 0);
        if (num > 59) val = '59';
        $(this).val(val.padStart(2, '0'));
        updateDriveEndClock();
    });

    function updateDriveEndClock() {
        let min = $('#drive-end-time-min').val();
        let sec = $('#drive-end-time-sec').val();
        if (min && sec) {
            $('#drive-end-time').val(min + ':' + sec);
        }
    }
    
        // Show drive over modal
    $(document).on('click', '#drive-over-button', function() {
        var teamId = $(this).closest('.stat-section').data('team-id');
        $('#drive-over-modal').data('team-id', teamId).show();
    });

    // Submit drive over
    $(document).on('click', '#submit-drive-over', function() {
        updateDriveEndClock(); // Ensure hidden field is updated
        var gameId = $('#game-info').data('game-id');
        var teamId = $('#drive-over-modal').data('team-id');
        var endTime = $('#drive-end-time').val();
        var endQtr = $('#drive-end-qtr').val();
        var endPos = $('#drive-end-pos').val();

        $.ajax({
            url: '/cvpwfl/stats/save_stats.php',
            method: 'POST',
            data: { game_id: gameId, team_id: teamId, type: 'end_drive', clock_end: endTime, qtr_end: endQtr, fp_end: endPos },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#drive-over-modal').hide();
                    window.location.href = '/cvpwfl/stats/stats_collection.php?game_id=' + gameId;
                } else {
                    alert('Failed to end drive: ' + (response.message || 'Unknown error'));
                }
            }
        });
    });

    // Button pressed effect for all buttons on page
    $(document).on('mousedown', 'button, a.stat-button', function() {
        $(this).css({
            'transform': 'scale(0.98)',
            'box-shadow': 'inset 0 0 5px rgba(0,0,0,0.2)'
        });
    }).on('mouseup mouseleave', 'button, a.stat-button', function() {
        $(this).css({
            'transform': '',
            'box-shadow': ''
        });
    });

    // Delete player confirmation and AJAX
    $(document).on('click', '.delete-player', function() {
        var playerId = $(this).data('player-id');
        var seasonId = $(this).data('season-id');
        var $row = $(this).closest('tr');
        var $detailsRow = $row.next('.details-row');

        if (confirm('Are you sure you want to delete this player? This action cannot be undone.')) {
            $.ajax({
                url: '/cvpwfl/coach/delete_player.php',
                method: 'POST',
                data: { player_id: playerId, season_id: seasonId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $row.remove();
                        $detailsRow.remove();
                    } else {
                        alert('Failed to delete: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', xhr.responseText, status, error);
                    alert('Error deleting player: ' + xhr.responseText);
                }
            });
        }
    });
    
// Show offense or defense sections on button click
$(document).on('click', '.stat-button[data-mode]', function() {
    var mode = $(this).data('mode');
    if (mode === 'offense') {
        $('#offense-section').show();
    } else if (mode === 'defense') {
        $('#defense-section').show();
    }
    // Existing code for 'ot' and 'gameover' can be added here if not already present
});

// Toggle +/- mode for defense stats (per player row)
$(document).on('click', '.toggle-mode', function() {
    var $btn = $(this);
    var subtractNext = $btn.data('subtract-next');
    if (!subtractNext) {
        $btn.text('-');
        $btn.data('subtract-next', true);
    } else {
        $btn.text('+');
        $btn.data('subtract-next', false);
    }
});

// Handle defense stat button clicks (add/subtract via AJAX)
$(document).on('click', '.stat-btn', function() {
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var playerId = $row.data('player-id');
    var statType = $btn.data('stat');
    var delta = 1;
    var $toggle = $row.find('.toggle-mode');
    var subtract = $toggle.data('subtract-next');
    var teamId = $row.closest('.stat-section').data('team-id');
    var gameId = $('#game-info').data('game-id');

    if (subtract) {
        delta = -1;
        $toggle.text('+');
        $toggle.data('subtract-next', false);
    }

    $.ajax({
        url: '/cvpwfl/stats/save_stats.php',
        method: 'POST',
        data: {
            game_id: gameId,
            team_id: teamId,
            type: 'defense',
            player_id: playerId,
            stat: statType,
            value: delta
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update display
                var $display = $row.find('.stat-display[data-stat="' + statType + '"]');
                var current = parseFloat($display.text()) || 0;
                var newValue = Math.max(0, current + delta); // Prevent negative on client
                $display.text(newValue);
            } else {
                alert('Failed to save: ' + (response.message || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error saving defense stat');
        }
    });
}); 

// NEW: Penalty button click (open modal)
$(document).on('click', '.penalty-button', function() {
    var section = $(this).closest('.stat-section');
    var penalizedTeamId = section.data('team-id');
    var offTeamId = $('#offense-section').data('team-id');
    $('#penalty-modal').data('penalized-team-id', penalizedTeamId).data('off-team-id', offTeamId).show();
});

// NEW: Submit penalty
$(document).on('click', '#submit-penalty', function() {
    var penalizedTeamId = $('#penalty-modal').data('penalized-team-id');
    var offTeamId = $('#penalty-modal').data('off-team-id');
    var newPos = $('#penalty-pos').val();
    var gameId = $('#game-info').data('game-id');

    if (!newPos || parseInt(newPos) < -49 || parseInt(newPos) > 49) {
        alert('Invalid field position (-49 to 49)');
        return;
    }

    $.ajax({
        url: '/cvpwfl/stats/save_stats.php',
        method: 'POST',
        data: { game_id: gameId, off_team_id: offTeamId, penalized_team_id: penalizedTeamId, type: 'penalty', new_pos: newPos },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#penalty-modal').hide();
                location.reload(); // Refresh to show updated position
            } else {
                alert('Failed to save penalty: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX error:', xhr.responseText, status, error);
            alert('Error submitting penalty: ' + error);
        }
    });
});
    
});