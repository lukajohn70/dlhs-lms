<?php
session_start();
require_once __DIR__ . '/sessionTime.php';

if (!isset($_SESSION['staffId'])) {
    header("Location: ../../index.php");
    exit;
}

// Enforce default password change before accessing smartboard
if (isset($_SESSION['forcePasswordChange']) && (int)$_SESSION['forcePasswordChange'] === 1) {
    header('Location: changePassword.php?force=1');
    exit;
}

require_once __DIR__ . '/../../db_connection/dlhs_db_connection.php';

$staffId = $_SESSION['staffId'];
$staffName = $_SESSION['staffName'];

// 1. Fetch teacher's files — query the correct table: file_uploads
require_once __DIR__ . '/../../scripts/file_assignment_helper.php';
dlhsEnsureAllFileManagementTablesExist($connection); // ensure column migrations run
$filesResult = $connection->query(
    "SELECT f.*, s.subjectName
     FROM file_uploads f
     LEFT JOIN subjects s ON f.subjectId = s.subjectId
     WHERE f.uploadedBy = '$staffId' AND f.isActive = 1
     ORDER BY f.fileId DESC"
);
$myFiles = [];
if ($filesResult) {
    while ($row = $filesResult->fetch_assoc()) {
        $myFiles[] = $row;
    }
}

// 2. Fetch teacher's tests
$testsResult = $connection->query("SELECT t.*, s.subjectName FROM tests t 
                                   INNER JOIN subjects s ON t.subject = s.subjectId 
                                   WHERE t.staffId = '$staffId' 
                                   ORDER BY t.testId DESC");
$myTests = [];
if ($testsResult) {
    while ($row = $testsResult->fetch_assoc()) {
        $myTests[] = $row;
    }
}

// Check if paired PIN is in GET parameters
$prefilledPin = isset($_GET['pin']) ? htmlspecialchars($_GET['pin']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smartboard Remote Control | Deeper Life High School</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --dlhs-blue: #00AEEF;
            --dlhs-magenta: #E91E63;
            --dlhs-navy: #020b1a;
            --dlhs-ink: #10233a;
            --dlhs-soft: rgba(255, 255, 255, 0.58);
            --dlhs-border: rgba(255, 255, 255, 0.72);
            --dlhs-shadow: 0 24px 60px rgba(9, 27, 53, 0.18);
            
            --primary: #00AEEF;
            --primary-dark: #0081b3;
            --border-glow: rgba(0, 174, 239, 0.3);
            --accent-green: #22c55e;
            --accent-yellow: #f59e0b;
            --accent-red: #E91E63;
            --text-muted: #5d7389;
        }

        .remote-container {
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            background: rgba(255, 255, 255, 0.85);
            color: var(--dlhs-ink);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(9, 27, 53, 0.08);
            min-height: calc(100vh - 120px);
            padding-bottom: 30px;
            border: 1px solid rgba(17, 39, 63, 0.08);
        }

        .remote-container .header-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(17, 39, 63, 0.08);
            padding: 15px 25px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .remote-container .status-badge {
            font-size: 12px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .remote-container .status-badge.connected {
            background: rgba(34, 197, 94, 0.1);
            color: #15803d;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .remote-container .status-badge.disconnected {
            background: rgba(233, 30, 99, 0.1);
            color: #c2185b;
            border: 1px solid rgba(233, 30, 99, 0.2);
        }

        .remote-container .remote-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(17, 39, 63, 0.08);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(9, 27, 53, 0.05);
        }

        .remote-container .remote-card h3 {
            color: var(--dlhs-ink);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .remote-container .remote-btn {
            background: linear-gradient(135deg, var(--dlhs-blue), #67d8ff);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            padding: 12px 20px;
            transition: all 0.2s ease;
            box-shadow: 0 8px 16px rgba(0, 174, 239, 0.2);
        }

        .remote-container .remote-btn:hover, .remote-container .remote-btn:active {
            background: linear-gradient(135deg, #0081b3, var(--dlhs-blue));
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 174, 239, 0.3);
        }

        .remote-container .control-pad {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr;
            gap: 15px;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }

        .remote-container .slide-nav-btn {
            background: rgba(0, 174, 239, 0.05);
            border: 1px solid rgba(0, 174, 239, 0.15);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--dlhs-blue);
            margin: 0 auto;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .remote-container .slide-nav-btn:active {
            background: var(--dlhs-blue);
            color: white;
            transform: scale(0.95);
        }

        .remote-container .media-list-item {
            background: #fff;
            border: 1px solid rgba(17, 39, 63, 0.08);
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(9, 27, 53, 0.03);
        }

        .remote-container .media-list-item:hover {
            border-color: var(--dlhs-blue);
            background: rgba(0, 174, 239, 0.02);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(0, 174, 239, 0.08);
        }

        .remote-container .item-meta h5 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 800;
            color: var(--dlhs-ink);
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            max-width: 180px;
        }

        .remote-container .item-meta span {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Game controller specific */
        .remote-container .game-panel {
            background: linear-gradient(135deg, #12273f, #020b1a);
        .remote-container .game-panel {
            background: linear-gradient(135deg, #12273f, #020b1a);
            border: 1px solid var(--accent-yellow);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.15);
            margin-bottom: 25px;
            color: #fff;
        }

        .remote-container .game-panel h3, .remote-container .game-panel h4 {
            color: #fff;
        }

        .remote-container .action-highlight-btn {
            background: linear-gradient(135deg, var(--accent-green), #15803d);
            font-size: 18px;
            font-weight: 800;
            padding: 15px 30px;
            border-radius: 30px;
            border: none;
            color: #fff;
            width: 100%;
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
            margin-top: 15px;
            transition: all 0.2s ease;
        }

        .remote-container .action-highlight-btn:active {
            transform: scale(0.98);
        }

        .remote-container .nav-tabs {
            border-bottom: 1px solid rgba(17, 39, 63, 0.08);
            margin-bottom: 20px;
        }

        .remote-container .nav-tabs .nav-link {
            color: var(--text-muted);
            border: none;
            font-weight: 700;
            padding: 10px 20px;
        }

        .remote-container .nav-tabs .nav-link.active {
            background: transparent;
            color: var(--dlhs-blue);
            border-bottom: 3px solid var(--dlhs-blue);
        }
    </style>
</head>
<body>
  <section id="container" class="">
	<!--Header-->
	<?php include 'header.php'; ?>

    <!--Sidebar-->
	<?php include 'sideBar.php'; ?>
	       
      <section id="main-content">
          <section class="wrapper">
            <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header" style="font-weight: 700; color: #2e3e38;"><i class="fa fa-television"></i> Smartboard Remote Control</h3>
					<ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-television"></i>Smartboard Remote</li>
					</ol>
				</div>
			</div>
            
            <div class="remote-container">

    <!-- Header bar -->
    <div class="header-bar">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dlhs-ink);">📺 Smartboard Remote</h4>
                <p style="margin: 0; font-size: 11px; color: var(--dlhs-blue); font-weight: 700; text-transform: uppercase; letter-spacing: 0.18em;">DLHS PRESENTATION CENTER</p>
            </div>
            
            <div id="pairingBadge">
                <span class="status-badge disconnected">
                    <span style="width: 6px; height: 6px; background: var(--accent-red); border-radius: 50%;"></span>
                    Not Synced
                </span>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        
        <!-- Pairing Card -->
        <div id="pairingCard" class="remote-card">
            <h4 style="font-size: 18px; font-weight: 800; margin-bottom: 12px; color: var(--dlhs-ink);"><i class="fa fa-plug" style="color: var(--dlhs-blue);"></i> Pair with Smartboard</h4>
            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.4; margin-bottom: 20px;">
                Look at the Smartboard display in your classroom and enter the 6-digit PIN code displayed on screen to establish a secure presentation remote link.
            </p>
            
            <form id="pairingForm">
                <div class="form-group mb-3">
                    <label style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--dlhs-blue);" for="pinInput">Pairing PIN Code</label>
                    <input type="text" class="form-control" id="pinInput" placeholder="SB-XXXXXX" value="<?php echo $prefilledPin; ?>" required style="background: rgba(255,255,255,0.8); border: 2px solid rgba(17,39,63,0.1); color: var(--dlhs-ink); font-size: 24px; text-align: center; letter-spacing: 2px; font-weight: 800; height: 56px; border-radius: 12px;">
                </div>
                
                <button type="submit" class="btn btn-primary w-100 remote-btn" style="height: 50px; font-size: 16px;">
                    <i class="fa fa-link"></i> Sync Device
                </button>
            </form>
        </div>

        <!-- Paired Dashboard View (Hidden initially) -->
        <div id="dashboardView" style="display: none;">
                    <!-- Presentation Slide controls (Only shows when a file is active on board) -->
            <div id="activePresentationCard" class="remote-card" style="display: none; border-color: var(--dlhs-blue); border-width: 2px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span style="font-size: 11px; background: rgba(0,174,239,0.1); padding: 4px 10px; border-radius: 4px; color: var(--dlhs-blue); font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em;">
                            Now Projecting
                        </span>
                        <span id="activeFileTypeBadge" style="margin-left:6px;"></span>
                    </div>
                    <button class="btn btn-sm btn-link p-0 text-danger" onclick="stopPresentation()" style="font-size: 13px; font-weight: 700; text-decoration: none;">
                        <i class="fa fa-times"></i> Stop
                    </button>
                </div>
                <h4 id="activeFileTitle" style="font-size: 16px; font-weight: 800; margin: 10px 0 8px 0; color: var(--dlhs-ink);">—</h4>

                <!-- Navigation pad (PDF and PPTX) -->
                <div id="slideControls" style="display: none;">
                    <div class="control-pad">
                        <div>
                            <div class="slide-nav-btn" onclick="prevSlide()">
                                <i class="fa fa-chevron-left"></i>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 6px; font-weight: 600;">Prev Slide</span>
                        </div>
                        <div>
                            <div style="font-size: 32px; font-weight: 800; color: var(--dlhs-ink);" id="currentPageNum">1</div>
                            <span style="font-size: 11px; color: var(--dlhs-blue); font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em;">Slide Number</span>
                        </div>
                        <div>
                            <div class="slide-nav-btn" onclick="nextSlide()">
                                <i class="fa fa-chevron-right"></i>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 6px; font-weight: 600;">Next Slide</span>
                        </div>
                    </div>
                </div>

                <!-- Presenter's View Panel -->
                <div id="presenterViewPanel" style="display: none; margin-top: 15px; border-top: 1px solid rgba(17,39,63,0.08); padding-top: 15px;">
                    <h5 style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--dlhs-blue); margin-bottom: 10px; letter-spacing: 0.05em; display: flex; align-items: center; gap: 5px;">
                        <i class="fa fa-television"></i> Presenter's View
                    </h5>
                    
                    <!-- Current Slide Box -->
                    <div style="background: #0f172a; border-radius: 10px; padding: 12px; margin-bottom: 10px; border-left: 4px solid #00aeef; box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);">
                        <div id="pvCurrentSlideHeader" style="font-size: 10px; color: #38bdf8; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Current Slide</div>
                        <div id="pvCurrentSlideContent" style="color: #f1f5f9; font-size: 12px; max-height: 120px; overflow-y: auto; text-align: left; line-height: 1.5; font-family: sans-serif;">—</div>
                    </div>
                    
                    <!-- Next Slide Box -->
                    <div style="background: rgba(17,39,63,0.03); border-radius: 10px; padding: 10px; border: 1px dashed rgba(17,39,63,0.15);">
                        <div id="pvNextSlideHeader" style="font-size: 10px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 3px;">Next Slide</div>
                        <div id="pvNextSlideContent" style="color: var(--dlhs-ink); font-size: 11px; max-height: 80px; overflow-y: auto; text-align: left; line-height: 1.4; font-family: sans-serif;">—</div>
                    </div>
                </div>

                <!-- Video Playback Controls Panel -->
                <div id="videoControlsPanel" style="display: none; margin-top: 15px; border-top: 1px solid rgba(17,39,63,0.08); padding-top: 15px; text-align: center;">
                    <h5 style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--dlhs-blue); margin-bottom: 12px; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 5px;">
                        <i class="fa fa-film"></i> Board Playback Controls
                    </h5>
                    
                    <!-- Telemetry Progress -->
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-muted); margin-bottom: 4px; font-weight: 700;">
                        <span id="vidCurrentTime">00:00</span>
                        <span id="vidDuration">00:00</span>
                    </div>
                    
                    <!-- Progress Bar (Seekable!) -->
                    <input type="range" id="vidSeekBar" min="0" max="100" value="0" style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; outline: none; margin-bottom: 15px; cursor: pointer; -webkit-appearance: none;">
                    
                    <!-- Play/Pause, Mute -->
                    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 15px;">
                        <!-- Mute toggle -->
                        <button id="vidMuteBtn" class="btn btn-default" style="width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0,0,0,0.1); background: #fff;" onclick="sendVideoCommand('mute_toggle')">
                            <i class="fa fa-volume-up"></i>
                        </button>
                        
                        <!-- Play / Pause -->
                        <button id="vidPlayBtn" class="btn btn-primary" style="width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 15px rgba(0,174,239,0.3); background: linear-gradient(135deg, var(--dlhs-blue), #008cc0); border: none;" onclick="sendVideoCommand('play_toggle')">
                            <i class="fa fa-play" style="font-size: 18px; color: #fff;"></i>
                        </button>
                        
                        <!-- Refresh/Restart -->
                        <button class="btn btn-default" style="width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0,0,0,0.1); background: #fff;" onclick="sendVideoCommand('seek', '0')">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                    
                    <!-- Volume Slider -->
                    <div style="display: flex; align-items: center; gap: 10px; background: rgba(17,39,63,0.03); padding: 8px 12px; border-radius: 10px; margin-top: 10px; border: 1px solid rgba(0,0,0,0.03);">
                        <i class="fa fa-volume-down" style="color: var(--text-muted); font-size: 12px;"></i>
                        <input type="range" id="vidVolumeBar" min="0" max="100" value="100" style="flex: 1; height: 4px; cursor: pointer; -webkit-appearance: none; background: #cbd5e1; border-radius: 2px;">
                        <i class="fa fa-volume-up" style="color: var(--text-muted); font-size: 12px;"></i>
                    </div>
                </div>

                <!-- Non-PDF/Non-Video/Non-PPTX playing indicator -->
                <div id="nonSlideControls" style="display: none; text-align: center; padding: 15px 0; background: rgba(17,39,63,0.05); border-radius: 12px;">
                    <i id="playingIcon" class="fa fa-play-circle" style="font-size: 40px; color: var(--dlhs-blue);"></i>
                    <p id="playingLabel" style="font-size: 13px; color: var(--text-muted); margin-top: 8px; margin-bottom: 0; font-weight: 600;">Media is playing on the Board.</p>
                </div> </div>
            </div>

            <!-- Gamified Active Game Controller (Only shows when quiz is running) -->
            <div id="activeGameCard" class="game-panel" style="display: none; background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 16px; padding: 25px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size: 11px; background: rgba(0, 174, 239, 0.1); padding: 4px 10px; border-radius: 4px; color: var(--dlhs-blue); font-weight: 700; text-transform: uppercase;">
                        Classroom Game Active
                    </span>
                    <button class="btn btn-sm btn-link p-0 text-danger" onclick="quitGame()" style="font-size: 12px; font-weight: 600; text-decoration: none;">
                        <i class="fa fa-times"></i> End Game
                    </button>
                </div>
                
                <!-- Game states: 1. Lobby -->
                <div id="gameLobbyState" style="display: none;">
                    <h3 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Game PIN</h3>
                    <div style="font-size: 38px; font-weight: 800; color: var(--dlhs-blue); letter-spacing: 2px;" id="gamePinVal">GAME-1829</div>
                    
                    <div style="margin: 20px 0; background: rgba(0, 174, 239, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(0, 174, 239, 0.15);">
                        <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Joined Players</span>
                        <div style="font-size: 28px; font-weight: 800; color: var(--dlhs-ink);" id="gamePlayerCount">0</div>
                    </div>
                    
                    <button class="action-highlight-btn" onclick="startGame()">
                        <i class="fa fa-rocket"></i> Start Game
                    </button>
                </div>
                
                <!-- Game states: 2. Question View -->
                <div id="gameQuestionState" style="display: none;">
                    <h5 id="questionLabel" style="font-size: 14px; color: var(--text-muted); text-transform: uppercase;">Question 1 of 10</h5>
                    <div style="font-size: 18px; font-weight: 600; margin: 15px 0; min-height: 50px; color: var(--dlhs-ink);" id="questionTextVal">Loading Question...</div>
                    
                    <button class="action-highlight-btn" onclick="showQuestionAnswers()" style="background: linear-gradient(135deg, var(--accent-yellow), #d97706); box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);">
                        <i class="fa fa-clock-o"></i> End Timer / Reveal Answers
                    </button>
                </div>
                
                <!-- Game states: 3. Answers View -->
                <div id="gameAnswersState" style="display: none;">
                    <h5 style="font-size: 14px; color: var(--accent-green); text-transform: uppercase; font-weight: 700;">Answers Revealed</h5>
                    <p style="font-size: 13px; color: var(--text-muted);">View student choices chart on the smartboard.</p>
                    
                    <button class="action-highlight-btn" onclick="showGameLeaderboard()" style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);">
                        <i class="fa fa-bar-chart"></i> View Scoreboard
                    </button>
                </div>
                
                <!-- Game states: 4. Leaderboard View -->
                <div id="gameLeaderboardState" style="display: none;">
                    <h5 style="font-size: 14px; color: var(--accent-yellow); text-transform: uppercase; font-weight: 700;">Leaderboard</h5>
                    <p style="font-size: 13px; color: var(--text-muted);">See student rankings on the smartboard screen.</p>
                    
                    <button class="action-highlight-btn" id="nextQuestionBtn" onclick="nextQuestion()">
                        <i class="fa fa-arrow-circle-right"></i> Next Question
                    </button>
                </div>
            </div>

            <!-- Paired Dashboard View Panels -->
            <ul class="nav nav-tabs justify-content-center" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="files-tab" data-toggle="tab" href="#files" role="tab"><i class="fa fa-folder-open"></i> Presentations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="quizzes-tab" data-toggle="tab" href="#quizzes" role="tab"><i class="fa fa-gamepad"></i> Classroom Quiz</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Panel A: Lesson Presentation Materials -->
                <div class="tab-pane fade show active" id="files" role="tabpanel">
                    <h5 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--primary);">Lesson Presentation Materials</h5>
                    
                    <?php if (count($myFiles) > 0): ?>
                        <?php foreach($myFiles as $file): ?>
                            <?php
                                $fileType  = strtolower($file['fileType'] ?? '');
                                $isYouTube = ($fileType === 'youtube');
                                if ($isYouTube) {
                                    $iconClass   = 'fa-youtube-play';
                                    $typeLabel   = 'YouTube';
                                    $presentType = 'youtube';
                                } else {
                                    $ext = strtolower(pathinfo($file['originalName'], PATHINFO_EXTENSION));
                                    $iconClass = 'fa-file-text-o';
                                    if (in_array($ext, ['pdf', 'ppt', 'pptx'])) $iconClass = 'fa-file-pdf-o';
                                    elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $iconClass = 'fa-file-image-o';
                                    elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'])) $iconClass = 'fa-file-video-o';
                                    $typeLabel   = strtoupper($ext) . ' File';
                                    $presentType = $ext;
                                }
                                $isPublished = !empty($file['isPublishedToStudents']);
                                $toggleLabel = $isPublished ? 'Visible' : 'Hidden';
                                $toggleIcon  = $isPublished ? 'fa-eye' : 'fa-eye-slash';
                                $toggleColor = $isPublished ? '#22c55e' : '#94a3b8';
                            ?>
                            <div class="media-list-item" id="file-row-<?php echo $file['fileId']; ?>" style="flex-wrap: wrap; gap: 8px;">
                                <div class="item-meta" style="flex: 1; min-width: 0;">
                                    <h5><?php echo htmlspecialchars($file['title']); ?></h5>
                                    <span><i class="fa <?php echo $iconClass; ?>" style="color: <?php echo $isYouTube ? '#E91E63' : 'inherit'; ?>"></i> <?php echo $typeLabel; ?></span>
                                    <?php if (!empty($file['subjectName'])): ?>
                                        <span style="display:inline-block; margin-left:6px; color:#888; font-size:10px;"><?php echo htmlspecialchars($file['subjectName']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap:6px; align-items:center; flex-shrink:0;">
                                    <!-- Student Visibility Toggle -->
                                    <button
                                        id="vis-btn-<?php echo $file['fileId']; ?>"
                                        onclick="toggleStudentVisibility(<?php echo $file['fileId']; ?>, this)"
                                        title="<?php echo $isPublished ? 'Click to hide from students' : 'Click to make visible to students'; ?>"
                                        style="border-radius:8px; border:1px solid <?php echo $toggleColor; ?>; color:<?php echo $toggleColor; ?>; font-weight:700; font-size:11px; height:34px; padding:0 10px; background:rgba(255,255,255,0.9); cursor:pointer; display:flex; align-items:center; gap:5px;">
                                        <i class="fa <?php echo $toggleIcon; ?>"></i> <span class="vis-label"><?php echo $toggleLabel; ?></span>
                                    </button>
                                    <!-- Preview -->
                                    <button
                                        onclick="showPreview(<?php echo $file['fileId']; ?>)"
                                        title="Quick Preview"
                                        style="border-radius:8px; border:1px solid rgba(17,39,63,0.12); color:var(--text-muted); font-weight:700; font-size:11px; height:34px; padding:0 10px; background:rgba(255,255,255,0.9); cursor:pointer; display:flex; align-items:center; gap:4px;">
                                        <i class="fa fa-eye"></i> Preview
                                    </button>
                                    <!-- Project on Smartboard -->
                                    <button
                                        onclick="presentFile(<?php echo $file['fileId']; ?>, '<?php echo addslashes(htmlspecialchars($file['title'])); ?>', '<?php echo $presentType; ?>')"
                                        style="border-radius:8px; border:1px solid rgba(0,174,239,0.25); color:var(--dlhs-ink); font-weight:700; font-size:12px; height:34px; padding:0 12px; background:rgba(255,255,255,0.8); cursor:pointer;">
                                        <i class="fa fa-television" style="color:var(--primary);"></i> Project
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                            <i class="fa fa-cloud-upload" style="font-size: 40px; margin-bottom: 15px; color: var(--primary);"></i>
                            <p style="font-size: 13px;">No uploaded materials found.<br>Go to <strong>File Sharing Hub</strong> to upload lesson materials.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Panel B: Interactive Kahoot Quizzes -->
                <div class="tab-pane fade" id="quizzes" role="tabpanel">
                    <h5 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--primary);">DLHS Classroom Quizzes</h5>
                    
                    <?php if (count($myTests) > 0): ?>
                        <?php foreach($myTests as $test): ?>
                            <div class="media-list-item" style="border-color: rgba(245, 158, 11, 0.1);">
                                <div class="item-meta">
                                    <h5 style="max-width: 200px;"><?php echo htmlspecialchars($test['testName']); ?></h5>
                                    <span style="color: var(--accent-yellow); font-weight: 600;"><i class="fa fa-book"></i> <?php echo htmlspecialchars($test['subjectName']); ?></span>
                                </div>
                                <button class="btn btn-sm btn-outline-warning" onclick="hostGame(<?php echo $test['testId']; ?>)" style="border-radius: 8px; font-weight: 600; font-size: 12px; height: 36px; padding: 0 12px; border-color: rgba(245, 158, 11, 0.2); color: var(--accent-yellow);">
                                    <i class="fa fa-gamepad"></i> Host Game
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                            <i class="fa fa-trophy" style="font-size: 40px; margin-bottom: 15px; color: var(--accent-yellow);"></i>
                            <p style="font-size: 13px;">No created quizzes found.<br>Go to <strong>Create Test</strong> in your panel to add game questions first.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Disconnect session button -->
            <button class="btn btn-danger w-100 mt-4" onclick="disconnectRemote()" style="border-radius: 12px; height: 50px; font-weight: 600; font-size: 15px; background: #3b0712; border-color: rgba(239, 68, 68, 0.2); color: var(--accent-red);">
                <i class="fa fa-chain-broken"></i> Disconnect Smartboard
            </button>
        </div>
    </div>

    <!-- ===== Preview Bottom Sheet ===== -->
    <div id="previewSheet" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.55);" onclick="if(event.target===this) closePreview();">
        <div style="position:absolute; bottom:0; left:0; right:0; max-height:85vh; background:#fff; border-radius:22px 22px 0 0; overflow:hidden; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid rgba(17,39,63,0.07);">
                <h5 id="previewTitle" style="margin:0; font-size:15px; font-weight:800; color:var(--dlhs-ink);">Preview</h5>
                <button onclick="closePreview()" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-muted); line-height:1;">&times;</button>
            </div>
            <div id="previewBody" style="overflow-y:auto; padding:20px; flex:1;"></div>
            <div style="padding:16px 20px; border-top:1px solid rgba(17,39,63,0.07); display:flex; gap:10px;">
                <button id="previewProjectBtn" style="flex:1; background:linear-gradient(135deg,var(--dlhs-blue),#67d8ff); border:none; border-radius:12px; color:#fff; font-weight:700; height:46px; font-size:15px; cursor:pointer;">
                    <i class="fa fa-television"></i> Project on Smartboard
                </button>
                <button onclick="closePreview()" style="background:rgba(17,39,63,0.06); border:none; border-radius:12px; color:var(--text-muted); font-weight:700; height:46px; padding:0 20px; cursor:pointer;">Close</button>
            </div>
        </div>
    </div>

            </div> <!-- End remote container -->
          </section>
      </section>
  </section>

  <!-- Javascripts -->
  <script src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.scrollTo.min.js"></script>
  <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
  <!-- Custom Scripts -->
  <script src="js/custom.js"></script>
    
  <script>
        const teacherId = <?php echo $staffId; ?>;
        // File data map for preview feature
        const remoteFilesData = <?php echo json_encode(array_values($myFiles)); ?>;
        const remoteFilesMap  = {};
        remoteFilesData.forEach(function(f){ remoteFilesMap[f.fileId] = f; });
        let sessionId = null;
        let pollTimer = null;
        let activeFileId   = null;
        let activePage     = 1;
        let activeFileType = '';   // e.g. 'youtube', 'application/pdf', 'video/mp4'
        let activeFileName = '';   // stored filename on disk
        let isPdf = false;
        
        // Game specific
        let activeGameId = null;
        let activeGamePin = '';
        let activeGameStatus = '';
        
        $(document).ready(function() {
            // Check if there is a saved paired session in localStorage
            const savedSessionId = localStorage.getItem('smartboard_session_id');
            if (savedSessionId) {
                sessionId = parseInt(savedSessionId);
                verifyPairedSession();
            }
            
            // Handle pairing form submission
            $('#pairingForm').submit(function(e) {
                e.preventDefault();
                const pin = $('#pinInput').val().trim();
                
                $.ajax({
                    url: '../../smartboard/smartboard_sync.php',
                    type: 'POST',
                    data: {
                        action: 'pair_remote',
                        pin: pin,
                        teacherId: teacherId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            sessionId = response.sessionId;
                            localStorage.setItem('smartboard_session_id', sessionId);
                            
                            showPairedDashboard();
                        } else {
                            dlhsAlert('Pairing Failed: ' + response.message, 'error');
                        }
                    },
                    error: function() {
                        dlhsAlert('Connection error. Please check your network and try again.', 'error');
                    }
                });
            });
        });

        function verifyPairedSession() {
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'GET',
                data: {
                    action: 'get_remote_state',
                    sessionId: sessionId,
                    teacherId: teacherId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showPairedDashboard();
                        handleRemoteStateUpdate(response);
                    } else {
                        // Expired session
                        localStorage.removeItem('smartboard_session_id');
                        sessionId = null;
                        showPairingCard();
                    }
                },
                error: function() {
                    // Try showing dashboard offline or keep trying
                    showPairingCard();
                }
            });
        }

        function showPairedDashboard() {
            $('#pairingCard').hide();
            $('#dashboardView').show();
            
            // Render paired badge
            $('#pairingBadge').html(`
                <span class="status-badge connected">
                    <span style="width: 6px; height: 6px; background: var(--accent-green); border-radius: 50%; display: inline-block;"></span>
                    Synced Live
                </span>
            `);
            
            // Start state sync polling
            startRemotePolling();
        }

        function showPairingCard() {
            $('#pairingCard').show();
            $('#dashboardView').hide();
            
            $('#pairingBadge').html(`
                <span class="status-badge disconnected">
                    <span style="width: 6px; height: 6px; background: var(--accent-red); border-radius: 50%; display: inline-block;"></span>
                    Not Synced
                </span>
            `);
            
            if (pollTimer) clearInterval(pollTimer);
        }

        function startRemotePolling() {
            if (pollTimer) clearInterval(pollTimer);
            
            pollTimer = setInterval(function() {
                $.ajax({
                    url: '../../smartboard/smartboard_sync.php',
                    type: 'GET',
                    data: {
                        action: 'get_remote_state',
                        sessionId: sessionId,
                        teacherId: teacherId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            handleRemoteStateUpdate(response);
                        } else {
                            showPairingCard();
                        }
                    }
                });
            }, 1500);
        }

        function handleRemoteStateUpdate(response) {
            // A. Handle Active Game State
            if (response.activeGame) {
                const game = response.activeGame;
                activeGameId = game.gameId;
                activeGamePin = game.gamePin;
                activeGameStatus = game.gameStatus;
                
                $('#activePresentationCard').hide();
                $('#activeGameCard').show();
                
                if (game.gameStatus === 'lobby') {
                    $('#gameLobbyState').show();
                    $('#gameQuestionState').hide();
                    $('#gameAnswersState').hide();
                    $('#gameLeaderboardState').hide();
                    
                    $('#gamePinVal').text(game.gamePin);
                    $('#gamePlayerCount').text(game.playerCount);
                } else if (game.gameStatus === 'showing_question') {
                    $('#gameLobbyState').hide();
                    $('#gameQuestionState').show();
                    $('#gameAnswersState').hide();
                    $('#gameLeaderboardState').hide();
                    
                    $('#questionLabel').text(`Question ${game.currentQuestionIndex + 1} of ${game.totalQuestions}`);
                } else if (game.gameStatus === 'showing_answers') {
                    $('#gameLobbyState').hide();
                    $('#gameQuestionState').hide();
                    $('#gameAnswersState').show();
                    $('#gameLeaderboardState').hide();
                } else if (game.gameStatus === 'leaderboard') {
                    $('#gameLobbyState').hide();
                    $('#gameQuestionState').hide();
                    $('#gameAnswersState').hide();
                    $('#gameLeaderboardState').show();
                    
                    // Toggle text on button if it's the last question
                    if (game.currentQuestionIndex + 1 >= game.totalQuestions) {
                        $('#nextQuestionBtn').html('<i class="fa fa-trophy"></i> Finish Game');
                    } else {
                        $('#nextQuestionBtn').html('<i class="fa fa-arrow-circle-right"></i> Next Question');
                    }
                }
                
                return;
            }
            
            // Clear active game card
            $('#activeGameCard').hide();
            activeGameId = null            // B. Handle Active Presentation file state
            if (response.activeFileId) {
                activeFileId = response.activeFileId;
                activePage   = response.activePage || 1;
                if (response.activeFileType !== undefined) {
                    activeFileType = response.activeFileType || '';
                    activeFileName = response.activeFileName || '';
                }
                updateActivePresentationCard(response);
            } else {
                $('#activePresentationCard').hide();
                activeFileId   = null;
                activeFileType = '';
                activeFileName = '';
            }
        }

        // Action controls
        function presentFile(fileId, title, fileType) {
            activeFileId   = fileId;
            activePage     = 1;
            activeFileType = fileType || '';
            updateActivePresentationCard(title);
            updatePresentationState();
        }

        let isDraggingSeekBar = false;
        let activeDuration = 0;

        function formatTime(secs) {
            if (isNaN(secs) || secs === null || secs === undefined) return '00:00';
            const m = Math.floor(secs / 60).toString().padStart(2, '0');
            const s = Math.floor(secs % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }

        function sendVideoCommand(cmd, val = '') {
            if (cmd === 'play_toggle') {
                const isPaused = $('#vidPlayBtn').find('.fa-play').length > 0;
                cmd = isPaused ? 'play' : 'pause';
            } else if (cmd === 'mute_toggle') {
                const isMuted = $('#vidMuteBtn').hasClass('active');
                cmd = isMuted ? 'unmute' : 'mute';
            }
            
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'update_video_command',
                    sessionId: sessionId,
                    teacherId: teacherId,
                    videoCommand: cmd,
                    videoCommandValue: val
                },
                dataType: 'json',
                success: function(res) {
                    // Command successfully queued!
                }
            });
        }

        // Event listener bindings for video controls
        $(document).on('mousedown touchstart', '#vidSeekBar', function() {
            isDraggingSeekBar = true;
        });
        $(document).on('mouseup touchend', '#vidSeekBar', function() {
            isDraggingSeekBar = false;
            const pct = parseFloat($(this).val());
            const seekTime = (pct / 100) * activeDuration;
            sendVideoCommand('seek', seekTime);
        });
        $(document).on('input', '#vidVolumeBar', function() {
            const vol = parseFloat($(this).val()) / 100;
            sendVideoCommand('volume', vol);
        });

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Sets the activePresentationCard UI based on the current activeFileType
        function updateActivePresentationCard(data) {
            $('#activePresentationCard').show();
            
            let title = '';
            let pptxData = null;
            let telemetry = null;
            
            if (typeof data === 'object' && data !== null) {
                title = data.activeFileTitle || '';
                pptxData = data.pptxData || null;
                telemetry = data.videoTelemetry || null;
            } else {
                title = data || '—';
            }
            
            $('#activeFileTitle').text(title || '—');

            const ft   = activeFileType.toLowerCase();
            const fn   = (activeFileName || '').toLowerCase();
            const isPdf = (ft === 'application/pdf' || fn.endsWith('.pdf'));
            const isYT  = (ft === 'youtube');
            const isVid = ft.startsWith('video/') || ['mp4','webm','ogg','mov','avi','mkv'].some(e => fn.endsWith('.' + e));
            const isImg = ft.startsWith('image/');
            const isPptx = (ft === 'presentation' || fn.endsWith('.pptx') || fn.endsWith('.ppt') || ft === 'pptx' || ft === 'ppt');

            // Type badge
            let badge = '', icon = 'fa-play-circle', label = 'Projected on the Board.', iconColor = 'var(--dlhs-blue)';
            if (isPdf) {
                badge = '<span style="font-size:11px;background:rgba(0,174,239,0.12);padding:3px 10px;border-radius:20px;color:var(--dlhs-blue);font-weight:700;">📄 PDF</span>';
                label = 'PDF presentation is on the Board.';
            } else if (isYT) {
                badge = '<span style="font-size:11px;background:rgba(233,30,99,0.12);padding:3px 10px;border-radius:20px;color:#E91E63;font-weight:700;">▶ YouTube</span>';
                icon  = 'fa-youtube-play'; iconColor = '#E91E63';
                label = 'YouTube video is playing on the Board.';
            } else if (isVid) {
                badge = '<span style="font-size:11px;background:rgba(34,197,94,0.12);padding:3px 10px;border-radius:20px;color:var(--accent-green);font-weight:700;">🎬 Video</span>';
                icon  = 'fa-film';
                label = 'Video is playing on the Board.';
            } else if (isImg) {
                badge = '<span style="font-size:11px;background:rgba(245,158,11,0.12);padding:3px 10px;border-radius:20px;color:var(--accent-yellow);font-weight:700;">🖼️ Image</span>';
                icon  = 'fa-picture-o'; iconColor = 'var(--accent-yellow)';
                label = 'Image is displayed on the Board.';
            } else if (isPptx) {
                badge = '<span style="font-size:11px;background:rgba(0,174,239,0.15);padding:3px 10px;border-radius:20px;color:var(--dlhs-blue);font-weight:700;">📊 PPTX</span>';
                label = 'PPTX presentation is on the Board.';
            } else {
                badge = '<span style="font-size:11px;background:rgba(0,174,239,0.08);padding:3px 10px;border-radius:20px;color:var(--text-muted);font-weight:700;">📊 Presentation</span>';
                label = 'Presentation is shown on the Board.';
            }
            $('#activeFileTypeBadge').html(badge);

            if (isPdf || isPptx) {
                $('#slideControls').show();
                $('#nonSlideControls').hide();
                $('#videoControlsPanel').hide();
                $('#currentPageNum').text(activePage);
            } else {
                $('#slideControls').hide();
                if (isVid || isYT) {
                    $('#nonSlideControls').hide();
                } else {
                    $('#nonSlideControls').show();
                    $('#playingIcon').attr('class', 'fa ' + icon).css('color', iconColor);
                    $('#playingLabel').text(label);
                }
            }

            // PPTX Presenter's View handling
            if (isPptx && pptxData && pptxData.slides) {
                const totalSlides = pptxData.slides.length;
                const sIdx = Math.max(0, Math.min(activePage - 1, totalSlides - 1));
                const curr = pptxData.slides[sIdx];
                const next = pptxData.slides[sIdx + 1];
                
                let currText = '';
                if (curr && curr.elements) {
                    curr.elements.forEach(el => {
                        const txt = (el.text || '').trim();
                        if (txt && !['deeper life high school', 'contact', 'about us', 'service', 'home'].includes(txt.toLowerCase())) {
                            currText += `<div style="margin-bottom:6px; font-weight:600;"><i class="fa fa-caret-right" style="color:#00aeef;"></i> ${escapeHtml(txt)}</div>`;
                        }
                    });
                }
                $('#pvCurrentSlideHeader').text(`Current Slide (${sIdx + 1} of ${totalSlides})`);
                $('#pvCurrentSlideContent').html(currText || '<em>No text content on this slide.</em>');
                
                let nextText = '';
                if (next && next.elements) {
                    next.elements.forEach(el => {
                        const txt = (el.text || '').trim();
                        if (txt && !['deeper life high school', 'contact', 'about us', 'service', 'home'].includes(txt.toLowerCase())) {
                            nextText += `• ${escapeHtml(txt)}<br>`;
                        }
                    });
                }
                $('#pvNextSlideHeader').text(next ? `Next Slide (${sIdx + 2} of ${totalSlides})` : 'Next Slide');
                $('#pvNextSlideContent').html(nextText || '<em>End of presentation.</em>');
                
                $('#presenterViewPanel').show();
            } else {
                $('#presenterViewPanel').hide();
            }

            // Video telemetry controls handling (local video AND YouTube)
            if (isVid || isYT) {
                // Update panel header label
                const panelIcon = isYT ? 'fa-youtube-play' : 'fa-film';
                const panelColor = isYT ? '#E91E63' : 'var(--dlhs-blue)';
                $('#videoControlsPanel').find('h5').html(`<i class="fa ${panelIcon}" style="color:${panelColor};"></i> ${isYT ? 'YouTube' : 'Video'} Playback Controls`);
                $('#videoControlsPanel').show();
                if (telemetry) {
                    activeDuration = telemetry.duration || 0;
                    $('#vidCurrentTime').text(formatTime(telemetry.currentTime));
                    $('#vidDuration').text(formatTime(telemetry.duration));
                    
                    if (!isDraggingSeekBar) {
                        const pct = telemetry.duration > 0 ? (telemetry.currentTime / telemetry.duration) * 100 : 0;
                        $('#vidSeekBar').val(pct);
                    }
                    
                    if (telemetry.isPaused) {
                        $('#vidPlayBtn').html('<i class="fa fa-play" style="font-size:18px; color:#fff;"></i>');
                    } else {
                        $('#vidPlayBtn').html('<i class="fa fa-pause" style="font-size:18px; color:#fff;"></i>');
                    }
                    
                    if (telemetry.isMuted) {
                        $('#vidMuteBtn').html('<i class="fa fa-volume-off" style="color:var(--accent-red);"></i>').addClass('active');
                    } else {
                        $('#vidMuteBtn').html('<i class="fa fa-volume-up"></i>').removeClass('active');
                    }
                }
            } else {
                $('#videoControlsPanel').hide();
            }
        }

        function nextSlide() {
            activePage++;
            $('#currentPageNum').text(activePage);
            updatePresentationState();
        }

        function prevSlide() {
            if (activePage > 1) {
                activePage--;
                $('#currentPageNum').text(activePage);
                updatePresentationState();
            }
        }

        function stopPresentation() {
            activeFileId = null;
            activePage = 1;
            $('#activePresentationCard').hide();
            
            updatePresentationState();
        }

        function updatePresentationState() {
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'update_presentation',
                    sessionId: sessionId,
                    teacherId: teacherId,
                    activeFileId: activeFileId || '',
                    activePage: activePage
                },
                dataType: 'json'
            });
        }

        // Gamified live quiz controls
        function hostGame(testId) {
            dlhsConfirm('🎮 Host a live DLHS Classroom Quiz on the Smartboard for this test?', function() {
                $.ajax({
                    url: '../../smartboard/smartboard_sync.php',
                    type: 'POST',
                    data: {
                        action: 'game_action',
                        gameAction: 'create',
                        teacherId: teacherId,
                        testId: testId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            activeGameId = response.gameId;
                            activeGamePin = response.gamePin;
                            
                            $('#activePresentationCard').hide();
                            $('#activeGameCard').show();
                            
                            $('#gameLobbyState').show();
                            $('#gameQuestionState').hide();
                            $('#gameAnswersState').hide();
                            $('#gameLeaderboardState').hide();
                            
                            $('#gamePinVal').text(response.gamePin);
                            $('#gamePlayerCount').text('0');
                        } else {
                            dlhsAlert('Classroom Quiz start failed: ' + response.message, 'error');
                        }
                    }
                });
            });
        }

        function startGame() {
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'game_action',
                    gameAction: 'start',
                    teacherId: teacherId,
                    gameId: activeGameId
                },
                dataType: 'json',
                success: function() {
                    $('#gameLobbyState').hide();
                    $('#gameQuestionState').show();
                }
            });
        }

        function showQuestionAnswers() {
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'game_action',
                    gameAction: 'show_answers',
                    teacherId: teacherId,
                    gameId: activeGameId
                },
                dataType: 'json',
                success: function() {
                    $('#gameQuestionState').hide();
                    $('#gameAnswersState').show();
                }
            });
        }

        function showGameLeaderboard() {
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'game_action',
                    gameAction: 'show_leaderboard',
                    teacherId: teacherId,
                    gameId: activeGameId
                },
                dataType: 'json',
                success: function() {
                    $('#gameAnswersState').hide();
                    $('#gameLeaderboardState').show();
                }
            });
        }

        function nextQuestion() {
            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'game_action',
                    gameAction: 'next_question',
                    teacherId: teacherId,
                    gameId: activeGameId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.finished) {
                            $('#gameLeaderboardState').hide();
                            $('#activeGameCard').hide();
                            activeGameId = null;
                            dlhsAlert('🏆 DLHS Classroom Quiz complete! The final leaderboard is now showing on the Smartboard.', 'success');
                        } else {
                            $('#gameLeaderboardState').hide();
                            $('#gameQuestionState').show();
                        }
                    }
                }
            });
        }

        function quitGame() {
            dlhsConfirm('⚠️ Are you sure you want to end this live DLHS Classroom Quiz?', function() {
                $.ajax({
                    url: '../../smartboard/smartboard_sync.php',
                    type: 'POST',
                    data: {
                        action: 'game_action',
                        gameAction: 'finish',
                        teacherId: teacherId,
                        gameId: activeGameId
                    },
                    dataType: 'json',
                    success: function() {
                        $('#activeGameCard').hide();
                        activeGameId = null;
                    }
                });
            });
        }

        function disconnectRemote() {
            dlhsConfirm('🔌 Disconnect this Smartboard session?', function() {
                localStorage.removeItem('smartboard_session_id');
                sessionId = null;
                showPairingCard();
            });
        }

        // Toggle student visibility for a file
        function toggleStudentVisibility(fileId, btn) {
            const label = btn.querySelector('.vis-label');
            const icon  = btn.querySelector('i');
            btn.disabled = true;

            $.ajax({
                url: 'toggle_file_visibility.php',
                type: 'POST',
                data: { fileId: fileId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        const visible = (res.newStatus == 1);
                        label.textContent  = visible ? 'Visible' : 'Hidden';
                        icon.className     = 'fa ' + (visible ? 'fa-eye' : 'fa-eye-slash');
                        const color        = visible ? '#22c55e' : '#94a3b8';
                        btn.style.color    = color;
                        btn.style.borderColor = color;
                        btn.title          = visible ? 'Click to hide from students' : 'Click to make visible to students';

                        // Brief toast
                        const toast = $('<div style="position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;z-index:99999;box-shadow:0 4px 16px rgba(0,0,0,0.25);">' + res.message + '</div>');
                        $('body').append(toast);
                        setTimeout(function(){ toast.fadeOut(400, function(){ toast.remove(); }); }, 2500);
                    } else {
                        dlhsAlert('Error: ' + res.message, 'error');
                    }
                },
                error: function() {
                    dlhsAlert('Connection error. Please try again.', 'error');
                },
                complete: function() {
                    btn.disabled = false;
                }
            });
        }

        // ---- Preview bottom-sheet ----
        let _previewFileId = null;

        function showPreview(fileId) {
            const file = remoteFilesMap[fileId];
            if (!file) return;

            _previewFileId = fileId;
            $('#previewTitle').text(file.title || file.fileName);

            const ft       = (file.fileType || '').toLowerCase();
            const fn       = (file.fileName  || file.originalName || '').toLowerCase();
            const isYT     = ft === 'youtube';
            const ext      = isYT ? 'youtube' : fn.split('.').pop();
            const isPdf    = (ext === 'pdf');
            const isImg    = ['jpg','jpeg','png','gif','webp','bmp'].includes(ext);
            const isVid    = ['mp4','webm','ogg','mov','avi','mkv'].includes(ext);

            let html = '';

            if (isYT) {
                const url     = file.filePath || file.originalName || '';
                const reg     = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:.*v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
                const match   = url.match(reg);
                const videoId = match ? match[1] : null;
                if (videoId) {
                    html = `<img src="https://img.youtube.com/vi/${videoId}/mqdefault.jpg"
                                 style="width:100%;border-radius:12px;display:block;margin-bottom:12px;"
                                 onerror="this.style.display='none'">
                            <p style="font-size:13px;color:var(--text-muted);margin:0;">
                                <i class="fa fa-youtube-play" style="color:#E91E63;"></i>
                                YouTube · <a href="${url}" target="_blank" style="color:var(--dlhs-blue);">Open on YouTube</a>
                            </p>`;
                } else {
                    html = `<p style="color:var(--text-muted);font-size:14px;">Invalid YouTube URL.</p>`;
                }

            } else if (isImg) {
                const src = '../../uploads/resources/' + (file.fileName || '');
                html = `<img src="${src}" style="width:100%;border-radius:12px;display:block;" onerror="this.src='';this.alt='Could not load image';">`;

            } else {
                const iconMap = {
                    pdf:  { icon:'fa-file-pdf-o',       color:'#E91E63', label:'PDF Document' },
                    ppt:  { icon:'fa-file-powerpoint-o', color:'#D04423', label:'PowerPoint Presentation' },
                    pptx: { icon:'fa-file-powerpoint-o', color:'#D04423', label:'PowerPoint Presentation' },
                    doc:  { icon:'fa-file-word-o',       color:'#2B579A', label:'Word Document' },
                    docx: { icon:'fa-file-word-o',       color:'#2B579A', label:'Word Document' },
                    mp4:  { icon:'fa-file-video-o',      color:'#7C3AED', label:'Video File' },
                    mov:  { icon:'fa-file-video-o',      color:'#7C3AED', label:'Video File' },
                    avi:  { icon:'fa-file-video-o',      color:'#7C3AED', label:'Video File' },
                };
                const info  = iconMap[ext] || { icon:'fa-file-o', color:'var(--dlhs-blue)', label: ext.toUpperCase() + ' File' };
                const sizeKb = Math.round((parseInt(file.fileSize) || 0) / 1024);
                const sizeStr = sizeKb > 1024 ? (sizeKb/1024).toFixed(1) + ' MB' : sizeKb + ' KB';
                html = `
                    <div style="text-align:center;padding:20px 0;">
                        <i class="fa ${info.icon}" style="font-size:72px;color:${info.color};opacity:0.9;"></i>
                        <p style="margin:14px 0 4px;font-size:15px;font-weight:700;color:var(--dlhs-ink);">${file.title || ''}</p>
                        <p style="margin:0;font-size:12px;color:var(--text-muted);">${info.label} · ${sizeStr}</p>
                        ${file.subjectName ? `<p style="margin:6px 0 0;font-size:12px;color:var(--dlhs-blue);font-weight:700;">${file.subjectName}</p>` : ''}
                        ${file.description ? `<p style="margin:12px auto 0;font-size:13px;color:#555;max-width:320px;line-height:1.5;">${file.description}</p>` : ''}
                    </div>`;
            }

            $('#previewBody').html(html);

            // Wire up the "Project" button in the sheet
            const presentType = isYT ? 'youtube' : ext;
            $('#previewProjectBtn').off('click').on('click', function() {
                closePreview();
                presentFile(fileId, file.title || file.fileName, presentType);
            });

            $('#previewSheet').fadeIn(180);
        }

        function closePreview() {
            $('#previewSheet').fadeOut(160);
            _previewFileId = null;
        }
  </script>

	<!-- ===== DLHS Universal Confirm Modal ===== -->
	<div id="dlhsConfirmModal" style="display:none; position:fixed; inset:0; z-index:999990; background:rgba(0,0,0,0.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center;">
		<div style="background:#fff; width:100%; max-width:460px; border-radius:20px; box-shadow:0 28px 64px rgba(0,0,0,0.22); overflow:hidden; margin:0 16px;" onclick="event.stopPropagation()">
			<div id="dlhsConfirmIconWrap" style="padding:30px 24px 4px; text-align:center; font-size:42px; color:#f59e0b;">
				<i class="fa fa-question-circle"></i>
			</div>
			<div style="padding:12px 32px 32px;">
				<p id="dlhsConfirmMsg" style="font-size:15px; color:#222; text-align:center; margin:0 0 26px; line-height:1.65;"></p>
				<div style="display:flex; gap:12px; justify-content:center;">
					<button id="dlhsConfirmCancelBtn" type="button" style="flex:1; max-width:150px; padding:12px 0; border-radius:50px; border:1.5px solid #ddd; background:#f5f5f5; color:#555; font-size:14px; font-weight:600; cursor:pointer; transition:background 0.2s;">Cancel</button>
					<button id="dlhsConfirmOkBtn" type="button" style="flex:1; max-width:150px; padding:12px 0; border-radius:50px; border:none; background:linear-gradient(135deg,#003366,#0055aa); color:#ffd700; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0,51,102,0.28); transition:all 0.2s;">Confirm</button>
				</div>
			</div>
		</div>
	</div>

	<!-- ===== DLHS Universal Alert Modal ===== -->
	<div id="dlhsAlertModal" style="display:none; position:fixed; inset:0; z-index:999991; background:rgba(0,0,0,0.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center;">
		<div style="background:#fff; width:100%; max-width:420px; border-radius:20px; box-shadow:0 28px 64px rgba(0,0,0,0.22); overflow:hidden; margin:0 16px;" onclick="event.stopPropagation()">
			<div id="dlhsAlertIconWrap" style="padding:30px 24px 4px; text-align:center; font-size:42px; color:#3b82f6;">
				<i class="fa fa-info-circle"></i>
			</div>
			<div style="padding:12px 32px 32px;">
				<p id="dlhsAlertMsg" style="font-size:15px; color:#222; text-align:center; margin:0 0 26px; line-height:1.65;"></p>
				<div style="text-align:center;">
					<button id="dlhsAlertOkBtn" type="button" style="padding:12px 48px; border-radius:50px; border:none; background:linear-gradient(135deg,#003366,#0055aa); color:#ffd700; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0,51,102,0.28);">OK</button>
				</div>
			</div>
		</div>
	</div>

	<style>
		@keyframes dlhsModalPop {
			from { opacity:0; transform:scale(0.86) translateY(16px); }
			to   { opacity:1; transform:scale(1) translateY(0); }
		}
		#dlhsConfirmModal.dlhs-open, #dlhsAlertModal.dlhs-open { display:flex !important; }
		#dlhsConfirmModal.dlhs-open > div, #dlhsAlertModal.dlhs-open > div {
			animation: dlhsModalPop 0.28s cubic-bezier(0.34,1.56,0.64,1);
		}
		#dlhsConfirmCancelBtn:hover { background:#e8e8e8; }
		#dlhsConfirmOkBtn:hover { background:linear-gradient(135deg,#00285c,#004499); transform:translateY(-1px); }
		#dlhsAlertOkBtn:hover { background:linear-gradient(135deg,#00285c,#004499); transform:translateY(-1px); }
	</style>

	<script>
	(function(){
		var _dlhsCb = null;

		window.dlhsConfirm = function(message, onConfirm) {
			_dlhsCb = onConfirm || null;
			document.getElementById('dlhsConfirmMsg').textContent = message;
			document.getElementById('dlhsConfirmIconWrap').innerHTML = '<i class="fa fa-question-circle" style="color:#f59e0b;"></i>';
			document.getElementById('dlhsConfirmModal').classList.add('dlhs-open');
		};

		window.dlhsAlert = function(message, type) {
			document.getElementById('dlhsAlertMsg').textContent = message;
			var iconWrap = document.getElementById('dlhsAlertIconWrap');
			if (type === 'error') {
				iconWrap.innerHTML = '<i class="fa fa-times-circle" style="color:#ef4444;"></i>';
			} else if (type === 'success') {
				iconWrap.innerHTML = '<i class="fa fa-check-circle" style="color:#22c55e;"></i>';
			} else {
				iconWrap.innerHTML = '<i class="fa fa-info-circle" style="color:#3b82f6;"></i>';
			}
			document.getElementById('dlhsAlertModal').classList.add('dlhs-open');
		};

		document.addEventListener('DOMContentLoaded', function(){
			document.getElementById('dlhsConfirmOkBtn').addEventListener('click', function(){
				document.getElementById('dlhsConfirmModal').classList.remove('dlhs-open');
				if (typeof _dlhsCb === 'function') { var cb = _dlhsCb; _dlhsCb = null; cb(); }
			});
			document.getElementById('dlhsConfirmCancelBtn').addEventListener('click', function(){
				document.getElementById('dlhsConfirmModal').classList.remove('dlhs-open');
				_dlhsCb = null;
			});
			document.getElementById('dlhsConfirmModal').addEventListener('click', function(e){
				if (e.target === this) { this.classList.remove('dlhs-open'); _dlhsCb = null; }
			});
			document.getElementById('dlhsAlertOkBtn').addEventListener('click', function(){
				document.getElementById('dlhsAlertModal').classList.remove('dlhs-open');
			});
			document.getElementById('dlhsAlertModal').addEventListener('click', function(e){
				if (e.target === this) { this.classList.remove('dlhs-open'); }
			});
		});
	})();
	</script>

</body>
</html>
