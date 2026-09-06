<?php
session_start();
session_write_close(); // Prevent session locking during high-frequency polling
require_once "../db_connection/dlhs_db_connection.php";

// ── Schema migration: ensure createdAt column exists on smartboard_games ──
$_colCheck = $connection->query("SHOW COLUMNS FROM smartboard_games LIKE 'createdAt'");
if ($_colCheck && $_colCheck->num_rows === 0) {
    $connection->query("ALTER TABLE smartboard_games ADD COLUMN createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    
    // Action 1: Initialize a new smartboard viewport session
    case 'init':
        // Generate a unique 6-digit PIN
        $pin = '';
        $unique = false;
        while (!$unique) {
            $pin = "SB-" . rand(100000, 999999);
            $check = $connection->query("SELECT sessionId FROM smartboard_sessions WHERE pairingPin = '$pin'");
            if ($check && $check->num_rows === 0) {
                $unique = true;
            }
        }
        
        $insert = $connection->query("INSERT INTO smartboard_sessions (pairingPin, status) VALUES ('$pin', 'pending')");
        if ($insert) {
            $sessionId = $connection->insert_id;
            echo json_encode([
                'success' => true,
                'pin' => $pin,
                'sessionId' => $sessionId
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to register smartboard session in database.'
            ]);
        }
        break;
        
    // Action 2: Smartboard polls to check if paired and retrieve display states
    case 'poll_smartboard':
        $sessionId = (int)$_GET['sessionId'];
        $pin = mysqli_real_escape_string($connection, $_GET['pin']);
        
        $sessionQuery = "SELECT s.*, CONCAT(st.firstName, ' ', st.surname) as staffName FROM smartboard_sessions s 
                         LEFT JOIN stafflogin st ON s.teacherId = st.staffId 
                         WHERE s.sessionId = $sessionId AND s.pairingPin = '$pin' LIMIT 1";
        $sessionResult = $connection->query($sessionQuery);
        
        if ($sessionResult && $sessionResult->num_rows > 0) {
            $session = $sessionResult->fetch_assoc();
            
            if ($session['status'] === 'active') {
                $teacherId = $session['teacherId'];
                
                // Update video playback telemetry from parameters if provided
                if (isset($_GET['videoCurrentTime'])) {
                    $currentTime = (float)$_GET['videoCurrentTime'];
                    $duration = (float)$_GET['videoDuration'];
                    $paused = (int)$_GET['videoIsPaused'];
                    $muted = (int)$_GET['videoIsMuted'];
                    
                    $connection->query("UPDATE smartboard_sessions SET 
                        videoCurrentTime = $currentTime, 
                        videoDuration = $duration, 
                        videoIsPaused = $paused, 
                        videoIsMuted = $muted 
                        WHERE sessionId = " . (int)$session['sessionId']);
                }

                // Construct the active state payload
                $state = [];
                
                // A. Check for any active gamified quiz for this teacher
                $gameQuery = "SELECT g.*, t.testName, t.tableName FROM smartboard_games g 
                              INNER JOIN tests t ON g.testId = t.testId 
                              WHERE t.staffId = $teacherId AND g.gameStatus != 'finished' 
                              ORDER BY g.gameId DESC LIMIT 1";
                $gameResult = $connection->query($gameQuery);
                
                if ($gameResult && $gameResult->num_rows > 0) {
                    $game = $gameResult->fetch_assoc();
                    $gameId = $game['gameId'];
                    $testTableName = $game['tableName'];
                    
                    // Fetch players joined
                    $players = [];
                    $playersResult = $connection->query("SELECT p.*, s.surname, s.firstName 
                                                         FROM smartboard_game_players p 
                                                         INNER JOIN studentlogin s ON p.studentId = s.studentId 
                                                         WHERE p.gameId = $gameId");
                    while ($pRow = $playersResult->fetch_assoc()) {
                        $players[] = [
                            'playerId' => $pRow['playerId'],
                            'studentId' => $pRow['studentId'],
                            'nickname' => $pRow['nickname'],
                            'studentName' => $pRow['firstName'] . ' ' . $pRow['surname']
                        ];
                    }
                    
                    // Fetch all questions for this test to calculate progress and load the current question
                    $questions = [];
                    $questResult = $connection->query("SELECT * FROM `$testTableName` ORDER BY questionSeriaNo ASC, questionId ASC");
                    while ($qRow = $questResult->fetch_assoc()) {
                        $questions[] = $qRow;
                    }
                    
                    $totalQuestions = count($questions);
                    $currentIdx = (int)$game['currentQuestionIndex'];
                    
                    $activeQuestion = null;
                    $correctOptionIndex = -1;
                    
                    if ($currentIdx >= 0 && $currentIdx < $totalQuestions) {
                        $qData = $questions[$currentIdx];
                        
                        // Parse options array
                        $opts = [];
                        if (!empty($qData['optionA'])) $opts[] = $qData['optionA'];
                        if (!empty($qData['optionB'])) $opts[] = $qData['optionB'];
                        if (!empty($qData['optionC'])) $opts[] = $qData['optionC'];
                        if (!empty($qData['optionD'])) $opts[] = $qData['optionD'];
                        
                        $activeQuestion = [
                            'questionId' => $qData['questionId'],
                            'questionText' => $qData['question'],
                            'options' => $opts
                        ];
                        
                        // Correct option mapping: A->0, B->1, C->2, D->3
                        $correctOpt = strtoupper(trim($qData['correctOption']));
                        if ($correctOpt === 'A') $correctOptionIndex = 0;
                        elseif ($correctOpt === 'B') $correctOptionIndex = 1;
                        elseif ($correctOpt === 'C') $correctOptionIndex = 2;
                        elseif ($correctOpt === 'D') $correctOptionIndex = 3;
                    }
                    
                    // Fetch response statistics if showing answers
                    $stats = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
                    if ($game['gameStatus'] === 'showing_answers' && $activeQuestion) {
                        $qId = $activeQuestion['questionId'];
                        $statsQuery = $connection->query("SELECT answerSelected, COUNT(*) as cnt 
                                                          FROM smartboard_game_responses 
                                                          WHERE gameId = $gameId AND questionId = $qId 
                                                          GROUP BY answerSelected");
                        if ($statsQuery) {
                            while ($statRow = $statsQuery->fetch_assoc()) {
                                $ans = strtoupper(trim($statRow['answerSelected']));
                                if (isset($stats[$ans])) {
                                    $stats[$ans] = (int)$statRow['cnt'];
                                }
                            }
                        }
                    }
                    
                    // Fetch leaderboard data
                    $leaderboard = [];
                    $leadQuery = $connection->query("SELECT p.*, s.surname, s.firstName 
                                                     FROM smartboard_game_players p 
                                                     INNER JOIN studentlogin s ON p.studentId = s.studentId 
                                                     WHERE p.gameId = $gameId 
                                                     ORDER BY p.score DESC LIMIT 10");
                    while ($lRow = $leadQuery->fetch_assoc()) {
                        $leaderboard[] = [
                            'studentId' => $lRow['studentId'],
                            'nickname' => $lRow['nickname'],
                            'studentName' => $lRow['firstName'] . ' ' . $lRow['surname'],
                            'score' => (int)$lRow['score'],
                            'streak' => (int)$lRow['streak']
                        ];
                    }
                    
                    $state['activeGame'] = [
                        'gameId' => $gameId,
                        'gamePin' => $game['gamePin'],
                        'gameStatus' => $game['gameStatus'],
                        'currentQuestionIndex' => $currentIdx,
                        'totalQuestions' => $totalQuestions,
                        'questionTimer' => (int)$game['questionTimer'],
                        'timerStartedAt' => $game['timerStartedAt'],
                        'timeElapsed' => time() - strtotime($game['timerStartedAt']),
                        'question' => $activeQuestion,
                        'correctOptionIndex' => $correctOptionIndex,
                        'players' => $players,
                        'responseStats' => $stats,
                        'leaderboard' => $leaderboard
                    ];
                    
                } else {
                    // B. Otherwise handle standard slide presentation
                    $state['activeFileId'] = $session['activeFileId'] ? (int)$session['activeFileId'] : null;
                    $state['activePage'] = (int)$session['activePage'];
                    
                    if ($state['activeFileId']) {
                        // Fetch active file details from the correct table
                        $fileDetails = $connection->query("SELECT * FROM file_uploads WHERE fileId = {$state['activeFileId']} LIMIT 1");
                        if ($fileDetails && $fileDetails->num_rows > 0) {
                            $fDetails = $fileDetails->fetch_assoc();
                            
                            $fName = $fDetails['fileName'];
                            $fType = $fDetails['fileType'];
                            
                            if ($fType === 'pptx' || $fType === 'ppt' || preg_match('/\.(pptx|ppt)$/i', $fName)) {
                                $pptxPath = __DIR__ . '/../uploads/resources/' . $fName;
                                $jsonPath = $pptxPath . '.json';
                                if (!file_exists($jsonPath) && file_exists($pptxPath)) {
                                    $escaped = escapeshellarg($pptxPath);
                                    shell_exec("python3 " . __DIR__ . "/../scripts/parse_pptx.py $escaped 2>&1");
                                }
                                if (file_exists($jsonPath)) {
                                    $fDetails['pptxData'] = json_decode(file_get_contents($jsonPath), true);
                                }
                            }
                            $state['fileDetails'] = $fDetails;
                        }
                    }

                    // Support one-time video playback command forwarding
                    $state['videoCommand'] = $session['videoCommand'];
                    $state['videoCommandValue'] = $session['videoCommandValue'];
                    if (!empty($session['videoCommand'])) {
                        $connection->query("UPDATE smartboard_sessions SET videoCommand = NULL, videoCommandValue = NULL WHERE sessionId = " . (int)$session['sessionId']);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'status' => 'active',
                    'teacherName' => $session['staffName'],
                    'state' => $state
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'status' => 'pending'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'status' => 'expired',
                'message' => 'Session expired or not found.'
            ]);
        }
        break;
        
    // Action 3: Remote pairing command (pairs the staff portal to the smartboard via PIN)
    case 'pair_remote':
        $pin = mysqli_real_escape_string($connection, $_POST['pin']);
        $teacherId = (int)$_POST['teacherId'];
        
        // Find pending session
        $findSession = $connection->query("SELECT sessionId FROM smartboard_sessions WHERE pairingPin = '$pin' AND status = 'pending' LIMIT 1");
        if ($findSession && $findSession->num_rows > 0) {
            $session = $findSession->fetch_assoc();
            $sessionId = $session['sessionId'];
            
            // Update session status to active
            $update = $connection->query("UPDATE smartboard_sessions SET status = 'active', teacherId = $teacherId WHERE sessionId = $sessionId");
            if ($update) {
                echo json_encode([
                    'success' => true,
                    'sessionId' => $sessionId,
                    'message' => 'Smartboard paired successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to pair remote in database.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired pairing PIN. Please check the Smartboard.'
            ]);
        }
        break;
        
    // Action 4: Remote polls to retrieve current state
    case 'get_remote_state':
        $sessionId = (int)$_GET['sessionId'];
        $teacherId = (int)$_GET['teacherId'];
        
        $sessionQuery = "SELECT s.*, CONCAT(st.firstName, ' ', st.surname) as staffName FROM smartboard_sessions s 
                         LEFT JOIN stafflogin st ON s.teacherId = st.staffId 
                         WHERE s.sessionId = $sessionId AND s.teacherId = $teacherId AND s.status = 'active' LIMIT 1";
        $sessionResult = $connection->query($sessionQuery);
        
        if ($sessionResult && $sessionResult->num_rows > 0) {
            $session = $sessionResult->fetch_assoc();
            
            // Check for active live quiz
            $gameQuery = "SELECT g.*, t.testName, t.tableName FROM smartboard_games g 
                          INNER JOIN tests t ON g.testId = t.testId 
                          WHERE t.staffId = $teacherId AND g.gameStatus != 'finished' 
                          ORDER BY g.gameId DESC LIMIT 1";
            $gameResult = $connection->query($gameQuery);
            $activeGame = null;
            
            if ($gameResult && $gameResult->num_rows > 0) {
                $game = $gameResult->fetch_assoc();
                $gameId = $game['gameId'];
                
                // Fetch players count
                $playerCountRes = $connection->query("SELECT COUNT(*) as cnt FROM smartboard_game_players WHERE gameId = $gameId");
                $playerCount = $playerCountRes ? $playerCountRes->fetch_assoc()['cnt'] : 0;
                
                // Fetch dynamic questions count
                $testTableName = $game['tableName'];
                $questCountRes = $connection->query("SELECT COUNT(*) as cnt FROM `$testTableName` WHERE question != ''");
                $totalQuestions = $questCountRes ? $questCountRes->fetch_assoc()['cnt'] : 0;
                
                $activeGame = [
                    'gameId' => $gameId,
                    'gamePin' => $game['gamePin'],
                    'gameStatus' => $game['gameStatus'],
                    'currentQuestionIndex' => (int)$game['currentQuestionIndex'],
                    'totalQuestions' => (int)$totalQuestions,
                    'playerCount' => (int)$playerCount
                ];
            }
            
            // Enrich response with file type/name so the remote can show correct controls
            $activeFileType  = null;
            $activeFileName  = null;
            $activeFileTitle = null;
            $pptxData        = null;
            if (!empty($session['activeFileId'])) {
                $fRes = $connection->query("SELECT fileType, fileName, originalName, title FROM file_uploads WHERE fileId = " . (int)$session['activeFileId'] . " LIMIT 1");
                if ($fRes && $fRes->num_rows > 0) {
                    $fRow = $fRes->fetch_assoc();
                    $activeFileType  = $fRow['fileType'];
                    $activeFileName  = $fRow['fileName'];
                    $activeFileTitle = $fRow['title'];
                    
                    // Parse PPTX on-the-fly for Presenter View
                    if ($activeFileType === 'pptx' || $activeFileType === 'ppt' || preg_match('/\.(pptx|ppt)$/i', $activeFileName)) {
                        $pptxPath = __DIR__ . '/../uploads/resources/' . $activeFileName;
                        $jsonPath = $pptxPath . '.json';
                        if (!file_exists($jsonPath) && file_exists($pptxPath)) {
                            $escaped = escapeshellarg($pptxPath);
                            shell_exec("python3 " . __DIR__ . "/../scripts/parse_pptx.py $escaped 2>&1");
                        }
                        if (file_exists($jsonPath)) {
                            $pptxData = json_decode(file_get_contents($jsonPath), true);
                        }
                    }
                }
            }

            echo json_encode([
                'success'        => true,
                'activeFileId'   => $session['activeFileId'] ? (int)$session['activeFileId'] : null,
                'activePage'     => (int)$session['activePage'],
                'activeFileType' => $activeFileType,
                'activeFileName' => $activeFileName,
                'activeFileTitle'=> $activeFileTitle,
                'activeGame'     => $activeGame,
                'pptxData'       => $pptxData,
                'videoTelemetry' => [
                    'currentTime' => (float)$session['videoCurrentTime'],
                    'duration'    => (float)$session['videoDuration'],
                    'isPaused'    => (int)$session['videoIsPaused'],
                    'isMuted'     => (int)$session['videoIsMuted']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Remote connection session is expired or not paired.'
            ]);
        }
        break;
        
    // Action 5: Remote updates the presentation states (navigates page or file preview)
    case 'update_presentation':
        $sessionId = (int)$_POST['sessionId'];
        $teacherId = (int)$_POST['teacherId'];
        
        $activeFileId = isset($_POST['activeFileId']) && $_POST['activeFileId'] !== '' ? (int)$_POST['activeFileId'] : 'NULL';
        $activePage = isset($_POST['activePage']) ? (int)$_POST['activePage'] : 1;
        
        $update = $connection->query("UPDATE smartboard_sessions 
                                      SET activeFileId = $activeFileId, activePage = $activePage 
                                      WHERE sessionId = $sessionId AND teacherId = $teacherId AND status = 'active'");
        if ($update) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update smartboard viewport in database.']);
        }
        break;

    // Action 5B: Remote sends play/pause/seek controls for videos
    case 'update_video_command':
        $sessionId = (int)$_POST['sessionId'];
        $teacherId = (int)$_POST['teacherId'];
        $videoCommand = mysqli_real_escape_string($connection, $_POST['videoCommand']);
        $videoCommandValue = isset($_POST['videoCommandValue']) ? mysqli_real_escape_string($connection, $_POST['videoCommandValue']) : '';
        
        $update = $connection->query("UPDATE smartboard_sessions 
                                      SET videoCommand = '$videoCommand', videoCommandValue = '$videoCommandValue' 
                                      WHERE sessionId = $sessionId AND teacherId = $teacherId AND status = 'active'");
        if ($update) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update video playback command in database.']);
        }
        break;
        
    // Action 6: Handles live quiz actions (create, start, navigate, leaderboard, lifelines)
    case 'game_action':
        $teacherId = (int)$_POST['teacherId'];
        $gameAction = mysqli_real_escape_string($connection, $_POST['gameAction']);
        
        if ($gameAction === 'create') {
            $testId = (int)$_POST['testId'];
            
            // Terminate all previously active games by this teacher to avoid duplicates/abandoned sessions
            $connection->query("UPDATE smartboard_games g 
                                INNER JOIN tests t ON g.testId = t.testId 
                                SET g.gameStatus = 'finished' 
                                WHERE t.staffId = $teacherId AND g.gameStatus != 'finished'");
            
            // Generate a secure game PIN (e.g. random 4-digit number)
            $gamePin = rand(1000, 9999);
            while ($connection->query("SELECT gameId FROM smartboard_games WHERE gamePin = 'GAME-$gamePin'")->num_rows > 0) {
                $gamePin = rand(1000, 9999);
            }
            $fullPin = "GAME-" . $gamePin;
            
            $insert = $connection->query("INSERT INTO smartboard_games (gamePin, testId, gameStatus, questionTimer) VALUES ('$fullPin', $testId, 'lobby', 30)");
            if ($insert) {
                echo json_encode([
                    'success' => true,
                    'gameId' => $connection->insert_id,
                    'gamePin' => $fullPin
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create interactive quiz session.']);
            }
            
        } else {
            $gameId = (int)$_POST['gameId'];
            
            // Load game details
            $gameRes = $connection->query("SELECT g.*, t.tableName FROM smartboard_games g 
                                           INNER JOIN tests t ON g.testId = t.testId 
                                           WHERE g.gameId = $gameId LIMIT 1");
            if (!$gameRes || $gameRes->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Active game session not found.']);
                exit;
            }
            
            $game = $gameRes->fetch_assoc();
            $testTableName = $game['tableName'];
            
            if ($gameAction === 'start') {
                $connection->query("UPDATE smartboard_games 
                                    SET gameStatus = 'showing_question', currentQuestionIndex = 0, timerStartedAt = NOW() 
                                    WHERE gameId = $gameId");
                echo json_encode(['success' => true]);
                
            } elseif ($gameAction === 'show_answers') {
                $connection->query("UPDATE smartboard_games SET gameStatus = 'showing_answers' WHERE gameId = $gameId");
                echo json_encode(['success' => true]);
                
            } elseif ($gameAction === 'show_leaderboard') {
                $connection->query("UPDATE smartboard_games SET gameStatus = 'leaderboard' WHERE gameId = $gameId");
                echo json_encode(['success' => true]);
                
            } elseif ($gameAction === 'next_question') {
                // Fetch total questions
                $questCountRes = $connection->query("SELECT COUNT(*) as cnt FROM `$testTableName` WHERE question != ''");
                $totalQuestions = $questCountRes ? $questCountRes->fetch_assoc()['cnt'] : 0;
                
                $nextIndex = (int)$game['currentQuestionIndex'] + 1;
                
                if ($nextIndex < $totalQuestions) {
                    $connection->query("UPDATE smartboard_games 
                                        SET gameStatus = 'showing_question', currentQuestionIndex = $nextIndex, timerStartedAt = NOW() 
                                        WHERE gameId = $gameId");
                    echo json_encode(['success' => true, 'finished' => false]);
                } else {
                    $connection->query("UPDATE smartboard_games SET gameStatus = 'finished' WHERE gameId = $gameId");
                    echo json_encode(['success' => true, 'finished' => true]);
                }
                
            } elseif ($gameAction === 'finish') {
                $connection->query("UPDATE smartboard_games SET gameStatus = 'finished' WHERE gameId = $gameId");
                echo json_encode(['success' => true]);
            }
        }
        break;
        
    // Action 7: Student joins live smartboard game
    case 'student_join_game':
        $gamePin = mysqli_real_escape_string($connection, $_POST['gamePin']);
        $studentId = (int)$_POST['studentId'];
        $nickname = mysqli_real_escape_string($connection, $_POST['nickname']);
        
        $gameQuery = "SELECT * FROM smartboard_games WHERE gamePin = '$gamePin' AND gameStatus != 'finished' LIMIT 1";
        $gameResult = $connection->query($gameQuery);
        if ($gameResult && $gameResult->num_rows > 0) {
            $game = $gameResult->fetch_assoc();
            $gameId = $game['gameId'];
            
            // Check if already joined
            $checkPlayer = $connection->query("SELECT playerId FROM smartboard_game_players WHERE gameId = $gameId AND studentId = $studentId LIMIT 1");
            if ($checkPlayer && $checkPlayer->num_rows > 0) {
                // Already joined
                echo json_encode([
                    'success' => true,
                    'gameId' => $gameId,
                    'nickname' => $nickname,
                    'message' => 'Rejoined lobby!'
                ]);
            } else {
                $insert = $connection->query("INSERT INTO smartboard_game_players (gameId, studentId, nickname, score, streak) VALUES ($gameId, $studentId, '$nickname', 0, 0)");
                if ($insert) {
                    echo json_encode([
                        'success' => true,
                        'gameId' => $gameId,
                        'nickname' => $nickname,
                        'message' => 'Joined lobby successfully!'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to register player.']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Game PIN not found or game has already finished.']);
        }
        break;

    // Action 8: Student polls active game state
    case 'student_poll_state':
        $gameId = (int)$_GET['gameId'];
        $studentId = (int)$_GET['studentId'];
        
        $gameRes = $connection->query("SELECT g.*, t.tableName FROM smartboard_games g 
                                       INNER JOIN tests t ON g.testId = t.testId 
                                       WHERE g.gameId = $gameId LIMIT 1");
        if ($gameRes && $gameRes->num_rows > 0) {
            $game = $gameRes->fetch_assoc();
            $testTableName = $game['tableName'];
            $gameStatus = $game['gameStatus'];
            $currentIdx = (int)$game['currentQuestionIndex'];
            
            // Fetch player details
            $playerRes = $connection->query("SELECT * FROM smartboard_game_players WHERE gameId = $gameId AND studentId = $studentId LIMIT 1");
            $player = $playerRes ? $playerRes->fetch_assoc() : ['score' => 0, 'streak' => 0];
            
            // Find current active question
            $questions = [];
            $questResult = $connection->query("SELECT * FROM `$testTableName` ORDER BY questionSeriaNo ASC, questionId ASC");
            while ($qRow = $questResult->fetch_assoc()) {
                $questions[] = $qRow;
            }
            $totalQuestions = count($questions);
            
            $qId = null;
            $hasAnswered = false;
            $selectedAnswer = '';
            $correctOpt = '';
            $questionText = '';
            $options = [];
            
            if ($currentIdx >= 0 && $currentIdx < $totalQuestions) {
                $currentQuest = $questions[$currentIdx];
                $qId = $currentQuest['questionId'];
                $correctOpt = strtoupper(trim($currentQuest['correctOption']));
                $questionText = $currentQuest['question'];
                $options = [
                    'A' => $currentQuest['optionA'],
                    'B' => $currentQuest['optionB'],
                    'C' => $currentQuest['optionC'],
                    'D' => $currentQuest['optionD']
                ];
                
                // Check if student has already answered this question
                $ansQuery = $connection->query("SELECT * FROM smartboard_game_responses WHERE gameId = $gameId AND questionId = $qId AND studentId = $studentId LIMIT 1");
                if ($ansQuery && $ansQuery->num_rows > 0) {
                    $hasAnswered = true;
                    $ansData = $ansQuery->fetch_assoc();
                    $selectedAnswer = $ansData['answerSelected'];
                }
            }
            
            // If the game is finished, return success = true with gameStatus = finished so student_game_controller can display the complete scoreboard
            if ($gameStatus === 'finished') {
                echo json_encode([
                    'success'              => true,
                    'gameStatus'           => 'finished',
                    'currentQuestionIndex' => $currentIdx,
                    'totalQuestions'       => $totalQuestions,
                    'questionId'           => $qId,
                    'questionText'         => $questionText,
                    'options'              => $options,
                    'hasAnswered'          => $hasAnswered,
                    'selectedAnswer'       => $selectedAnswer,
                    'correctOption'        => $correctOpt,
                    'score'                => (int)$player['score'],
                    'streak'               => (int)$player['streak']
                ]);
                break;
            }

            echo json_encode([
                'success'              => true,
                'gameStatus'           => $gameStatus,
                'currentQuestionIndex' => $currentIdx,
                'totalQuestions'       => $totalQuestions,
                'questionId'           => $qId,
                'questionText'         => $questionText,
                'options'              => $options,
                'hasAnswered'          => $hasAnswered,
                'selectedAnswer'       => $selectedAnswer,
                'correctOption'        => $correctOpt,
                'score'                => (int)$player['score'],
                'streak'               => (int)$player['streak']
            ]);
        } else {
            echo json_encode(['success' => false, 'reason' => 'not_found', 'message' => 'Game session not found.']);
        }
        break;

    // Action 9: Student submits colored key answer for active question
    case 'student_submit_answer':
        $gameId = (int)$_POST['gameId'];
        $questionId = (int)$_POST['questionId'];
        $studentId = (int)$_POST['studentId'];
        $answerSelected = strtoupper(trim(mysqli_real_escape_string($connection, $_POST['answerSelected'])));
        $responseTimeMs = (int)$_POST['responseTimeMs'];
        
        // Check duplicate
        $dupCheck = $connection->query("SELECT responseId FROM smartboard_game_responses WHERE gameId = $gameId AND questionId = $questionId AND studentId = $studentId");
        if ($dupCheck && $dupCheck->num_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Answer already submitted.']);
            exit;
        }
        
        // Find correct option from test question table
        $gameQuery = $connection->query("SELECT g.testId, t.tableName FROM smartboard_games g 
                                         INNER JOIN tests t ON g.testId = t.testId 
                                         WHERE g.gameId = $gameId LIMIT 1");
        if ($gameQuery && $gameQuery->num_rows > 0) {
            $game = $gameQuery->fetch_assoc();
            $testTableName = $game['tableName'];
            
            $qQuery = $connection->query("SELECT correctOption FROM `$testTableName` WHERE questionId = $questionId LIMIT 1");
            if ($qQuery && $qQuery->num_rows > 0) {
                $correctOpt = strtoupper(trim($qQuery->fetch_assoc()['correctOption']));
                
                $isCorrect = ($answerSelected === $correctOpt) ? 1 : 0;
                
                // Calculate points
                $pointsEarned = 0;
                if ($isCorrect) {
                    $basePoints = 500;
                    $timerLimit = 30000; // 30 seconds max
                    $bonus = round(500 * (1 - (min($responseTimeMs, $timerLimit) / $timerLimit)));
                    $pointsEarned = $basePoints + $bonus;
                }
                
                // Insert response
                $insert = $connection->query("INSERT INTO smartboard_game_responses (gameId, questionId, studentId, answerSelected, responseTimeMs, isCorrect) 
                                              VALUES ($gameId, $questionId, $studentId, '$answerSelected', $responseTimeMs, $isCorrect)");
                
                if ($insert) {
                    // Update score and streak on players table
                    if ($isCorrect) {
                        $connection->query("UPDATE smartboard_game_players 
                                            SET score = score + $pointsEarned, streak = streak + 1 
                                            WHERE gameId = $gameId AND studentId = $studentId");
                    } else {
                        $connection->query("UPDATE smartboard_game_players 
                                            SET streak = 0 
                                            WHERE gameId = $gameId AND studentId = $studentId");
                    }
                    
                    // Fetch updated score
                    $playerRes = $connection->query("SELECT score, streak FROM smartboard_game_players WHERE gameId = $gameId AND studentId = $studentId LIMIT 1");
                    $player = $playerRes->fetch_assoc();
                    
                    echo json_encode([
                        'success' => true,
                        'isCorrect' => ($isCorrect === 1),
                        'pointsEarned' => $pointsEarned,
                        'totalScore' => (int)$player['score'],
                        'streak' => (int)$player['streak']
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to record student answer.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Question not found in test table.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Game test table not found.']);
        }
        break;

    // Action 10: Teacher fetches history of all their finished games
    case 'get_game_history':
        $teacherId = (int)$_GET['teacherId'];
        $histQuery = "SELECT g.gameId, g.gamePin, g.gameStatus, g.createdAt,
                             t.testName, s.subjectName,
                             COUNT(DISTINCT p.playerId) AS playerCount,
                             MAX(p.score) AS topScore,
                             (SELECT p2.nickname FROM smartboard_game_players p2
                              WHERE p2.gameId = g.gameId
                              ORDER BY p2.score DESC LIMIT 1) AS topPlayer
                      FROM smartboard_games g
                      INNER JOIN tests t ON g.testId = t.testId
                      LEFT JOIN subjects s ON t.subject = s.subjectId
                      LEFT JOIN smartboard_game_players p ON p.gameId = g.gameId
                      WHERE t.staffId = $teacherId AND g.gameStatus = 'finished'
                      GROUP BY g.gameId
                      ORDER BY g.gameId DESC
                      LIMIT 50";
        $histResult = $connection->query($histQuery);
        $histGames  = [];
        if ($histResult) {
            while ($hRow = $histResult->fetch_assoc()) {
                $histGames[] = $hRow;
            }
        }
        echo json_encode(['success' => true, 'games' => $histGames]);
        break;

    // Action 11: Teacher fetches full leaderboard + question stats for one game
    case 'get_game_results':
        $gameId    = (int)$_GET['gameId'];
        $teacherId = (int)$_GET['teacherId'];

        // Verify game belongs to this teacher's test
        $grCheck = $connection->query(
            "SELECT g.*, t.testName, t.tableName, s.subjectName
             FROM smartboard_games g
             INNER JOIN tests t ON g.testId = t.testId
             LEFT JOIN subjects s ON t.subject = s.subjectId
             WHERE g.gameId = $gameId AND t.staffId = $teacherId LIMIT 1"
        );
        if (!$grCheck || $grCheck->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Game not found or access denied.']);
            break;
        }
        $grGame = $grCheck->fetch_assoc();

        // Leaderboard
        $grLeaderboard = [];
        $lbRes = $connection->query(
            "SELECT p.playerId, p.nickname, p.score, p.streak,
                    CONCAT(st.firstName, ' ', st.surname) AS fullName,
                    COUNT(r.responseId) AS answeredCount,
                    SUM(r.isCorrect) AS correctCount
             FROM smartboard_game_players p
             LEFT JOIN studentlogin st ON p.studentId = st.studentId
             LEFT JOIN smartboard_game_responses r
                    ON r.gameId = p.gameId AND r.studentId = p.studentId
             WHERE p.gameId = $gameId
             GROUP BY p.playerId
             ORDER BY p.score DESC"
        );
        if ($lbRes) {
            $grRank = 1;
            while ($lbRow = $lbRes->fetch_assoc()) {
                $lbRow['rank'] = $grRank++;
                $grLeaderboard[] = $lbRow;
            }
        }

        // Per-question stats
        $grQuestions  = [];
        $grTestTable  = $grGame['tableName'];
        $grQuestRes   = $connection->query(
            "SELECT * FROM `$grTestTable` ORDER BY questionSeriaNo ASC, questionId ASC"
        );
        if ($grQuestRes) {
            $grIdx = 1;
            while ($grQ = $grQuestRes->fetch_assoc()) {
                $grQId  = $grQ['questionId'];
                $grStats = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
                $grTotal = 0;
                $grStatRes = $connection->query(
                    "SELECT answerSelected, COUNT(*) as cnt
                     FROM smartboard_game_responses
                     WHERE gameId = $gameId AND questionId = $grQId
                     GROUP BY answerSelected"
                );
                if ($grStatRes) {
                    while ($grStat = $grStatRes->fetch_assoc()) {
                        $grAns = strtoupper(trim($grStat['answerSelected']));
                        if (isset($grStats[$grAns])) $grStats[$grAns] = (int)$grStat['cnt'];
                        $grTotal += (int)$grStat['cnt'];
                    }
                }
                $grQuestions[] = [
                    'index'         => $grIdx++,
                    'questionId'    => $grQId,
                    'questionText'  => $grQ['question'],
                    'correctOption' => strtoupper(trim($grQ['correctOption'])),
                    'optionA'       => $grQ['optionA'],
                    'optionB'       => $grQ['optionB'],
                    'optionC'       => $grQ['optionC'],
                    'optionD'       => $grQ['optionD'],
                    'stats'         => $grStats,
                    'totalAnswers'  => $grTotal,
                ];
            }
        }

        echo json_encode([
            'success'     => true,
            'game'        => [
                'gameId'         => $grGame['gameId'],
                'gamePin'        => $grGame['gamePin'],
                'testName'       => $grGame['testName'],
                'subjectName'    => $grGame['subjectName'],
                'createdAt'      => $grGame['createdAt'],
                'totalQuestions' => count($grQuestions),
            ],
            'leaderboard' => $grLeaderboard,
            'questions'   => $grQuestions,
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action unrecognized or invalid parameters.'
        ]);
}
?>
