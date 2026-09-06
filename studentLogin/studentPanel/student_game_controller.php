<?php
session_start();
require_once __DIR__ . '/sessionTime.php';

if (!isset($_SESSION['studentId'])) {
    header("Location: ../../index.php");
    exit;
}

// Enforce default password change before accessing live quiz
if (isset($_SESSION['forcePasswordChange']) && (int)$_SESSION['forcePasswordChange'] === 1) {
    header("Location: changePassword.php?force=1");
    exit;
}

require_once __DIR__ . '/../../db_connection/dlhs_db_connection.php';

$studentId = $_SESSION['studentId'];
$studentName = $_SESSION['studentName'];

$prefilledPin = isset($_GET['pin']) ? htmlspecialchars($_GET['pin']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DLHS Classroom Quiz | Deeper Life High School</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap and CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.css" rel="stylesheet" />

    <style>
        :root {
            --dlhs-blue: #00AEEF;
            --dlhs-navy: #020b1a;
            --dlhs-ink: #10233a;
            --dlhs-gold: #ffd700;
            --dlhs-border: rgba(0, 174, 239, 0.15);
            --dlhs-card-bg: rgba(255, 255, 255, 0.85);
            --dlhs-card-border: rgba(17, 39, 63, 0.08);
            --accent-green: #22c55e;
            --accent-yellow: #f59e0b;
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --text-muted: #5d7389;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 40px;
        }

        /* Ambient glows */
        .ambient-top {
            position: fixed;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,174,239,0.06) 0%, transparent 70%);
            top: -100px; left: -100px;
            pointer-events: none;
            z-index: 0;
        }
        .ambient-bottom {
            position: fixed;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(0,85,170,0.04) 0%, transparent 70%);
            bottom: -80px; right: -80px;
            pointer-events: none;
            z-index: 0;
        }

        /* Header */
        .header-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(17, 39, 63, 0.08);
            padding: 14px 20px;
            position: sticky;
            top: 0;
            z-index: 200;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-brand .logo-ring {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #003366, #00AEEF);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffd700;
            box-shadow: 0 4px 12px rgba(0, 174, 239, 0.15);
        }

        .header-brand h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .header-brand p {
            margin: 0;
            font-size: 9px;
            color: var(--dlhs-blue);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }

        .status-pill {
            font-size: 10px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            background: rgba(17, 39, 63, 0.05);
            border: 1px solid rgba(17, 39, 63, 0.08);
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .status-pill.lobby    { background: rgba(245,158,11,0.12); border-color: rgba(245,158,11,0.2); color: var(--accent-yellow); }
        .status-pill.active   { background: rgba(34,197,94,0.12); border-color: rgba(34,197,94,0.2); color: var(--accent-green); }
        .status-pill.waiting  { background: rgba(0,174,239,0.12); border-color: rgba(0,174,239,0.2); color: var(--dlhs-blue); }
        .status-pill.finished { background: rgba(255,215,0,0.12); border-color: rgba(255,215,0,0.2); color: var(--dlhs-gold); }

        /* Cards */
        .game-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--dlhs-card-border);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 22px;
            padding: 28px 24px;
            margin-top: 22px;
            box-shadow: 0 20px 40px rgba(9, 27, 53, 0.06), inset 0 1px 0 rgba(255,255,255,0.6);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .game-card-accent {
            border-color: rgba(0,174,239,0.3);
            box-shadow: 0 20px 40px rgba(0, 174, 239, 0.08), 0 0 0 1px rgba(0,174,239,0.05), inset 0 1px 0 rgba(255,255,255,0.8);
        }

        /* Join Form */
        .dlhs-input {
            background: rgba(255,255,255,0.9);
            border: 1.5px solid rgba(17, 39, 63, 0.12);
            color: #0f172a;
            border-radius: 14px;
            height: 52px;
            padding: 0 18px;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .dlhs-input:focus {
            outline: none;
            border-color: var(--dlhs-blue);
            box-shadow: 0 0 0 3px rgba(0,174,239,0.15);
        }

        .dlhs-input::placeholder { color: rgba(15,23,42,0.35); }

        .dlhs-input.pin-input {
            font-size: 24px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 3px;
            height: 60px;
        }

        .dlhs-label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--dlhs-blue);
            text-align: left;
            margin-bottom: 8px;
        }

        .dlhs-btn-primary {
            background: linear-gradient(135deg, #003366, #00AEEF);
            border: none;
            border-radius: 14px;
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            height: 52px;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 24px rgba(0, 174, 239, 0.25);
            letter-spacing: 0.05em;
        }

        .dlhs-btn-primary:hover {
            background: linear-gradient(135deg, #00254d, #009ad4);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 174, 239, 0.3);
        }

        .dlhs-btn-primary:active { transform: translateY(0); }

        /* Options grid - exactly like smartboard */
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            width: 100%;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }

        .option-card {
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 15px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #fff;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(9, 27, 53, 0.15);
            transition: all 0.2s ease;
            cursor: pointer;
            width: 100%;
            text-align: left;
            min-height: 100px;
        }
        
        .option-card:active {
            transform: scale(0.95);
        }

        .option-card.red { background: linear-gradient(135deg, #E91E63, #c2185b); box-shadow: 0 8px 24px rgba(233, 30, 99, 0.3); }
        .option-card.blue { background: linear-gradient(135deg, #00AEEF, #0081b3); box-shadow: 0 8px 24px rgba(0, 174, 239, 0.3); }
        .option-card.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3); }
        .option-card.green { background: linear-gradient(135deg, #22c55e, #15803d); box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3); }

        .option-marker {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .option-text {
            font-size: 15px;
            word-break: break-word;
            line-height: 1.3;
        }

        /* Feedback screens */
        .feedback-screen {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px 24px;
            border-radius: 24px;
            margin-top: 22px;
            animation: feedbackPop 0.35s cubic-bezier(0.34,1.56,0.64,1);
            position: relative;
            z-index: 1;
        }

        @keyframes feedbackPop {
            0% { opacity: 0; transform: scale(0.88) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .feedback-screen.correct {
            background: linear-gradient(145deg, rgba(220,252,231,0.9), rgba(240,253,244,0.95));
            border: 1px solid rgba(34,197,94,0.4);
            box-shadow: 0 20px 45px rgba(34,197,94,0.1);
        }

        .feedback-screen.incorrect {
            background: linear-gradient(145deg, rgba(254,226,226,0.9), rgba(255,241,241,0.95));
            border: 1px solid rgba(239,68,68,0.4);
            box-shadow: 0 20px 45px rgba(239,68,68,0.1);
        }

        .feedback-screen.waiting {
            background: linear-gradient(145deg, rgba(239,246,255,0.9), rgba(248,250,252,0.95));
            border: 1px solid rgba(0,174,239,0.3);
            box-shadow: 0 20px 45px rgba(0,174,239,0.08);
        }

        /* Score / stats pill */
        .stat-chip {
            background: rgba(255,255,255,0.6);
            border: 1px solid rgba(17, 39, 63, 0.08);
            border-radius: 30px;
            padding: 8px 22px;
            display: inline-block;
            font-size: 15px;
            font-weight: 700;
            margin: 12px 0;
        }

        /* Info boxes */
        .info-box {
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(17, 39, 63, 0.08);
            border-radius: 14px;
            padding: 16px 18px;
            margin-top: 18px;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Progress bar */
        .question-progress-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.7);
            border: 1px solid rgba(0,174,239,0.15);
            border-radius: 10px;
            padding: 9px 16px;
            font-size: 12px;
            margin-top: 18px;
            position: relative;
            z-index: 1;
        }

        /* Scoreboard card */
        .score-display {
            background: linear-gradient(135deg, #003366, #00AEEF);
            border-radius: 18px;
            padding: 22px;
            margin-top: 22px;
            box-shadow: 0 12px 32px rgba(0,174,239,0.15);
        }

        .score-number {
            font-size: 52px;
            font-weight: 800;
            color: var(--dlhs-gold);
            line-height: 1;
            text-shadow: 0 4px 16px rgba(255,215,0,0.3);
        }

        /* Spinning loader */
        @keyframes spinPulse {
            0%   { transform: rotate(0deg) scale(1); opacity: 1; }
            50%  { transform: rotate(180deg) scale(1.05); opacity: 0.8; }
            100% { transform: rotate(360deg) scale(1); opacity: 1; }
        }

        .spin-icon { animation: spinPulse 1.6s ease-in-out infinite; }

        /* ===== DLHS Modal System ===== */
        #dlhsConfirmModal, #dlhsAlertModal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 999990;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        #dlhsConfirmModal .modal-inner,
        #dlhsAlertModal .modal-inner {
            background: #fff;
            width: 100%;
            max-width: 400px;
            border-radius: 22px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.35);
            overflow: hidden;
        }

        #dlhsConfirmModal.dlhs-open,
        #dlhsAlertModal.dlhs-open { display: flex !important; }

        #dlhsConfirmModal.dlhs-open .modal-inner,
        #dlhsAlertModal.dlhs-open .modal-inner {
            animation: dlhsModalPop 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }

        @keyframes dlhsModalPop {
            from { opacity:0; transform:scale(0.82) translateY(20px); }
            to   { opacity:1; transform:scale(1) translateY(0); }
        }

        .modal-icon { padding: 28px 24px 4px; text-align: center; font-size: 44px; }
        .modal-body { padding: 10px 30px 30px; }
        .modal-msg  { font-size: 15px; color: #1e293b; text-align: center; margin: 0 0 24px; line-height: 1.6; font-family: 'Outfit', sans-serif; font-weight: 500; }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }

        .modal-btn-cancel {
            flex: 1; max-width: 140px; padding: 12px 0; border-radius: 50px;
            border: 1.5px solid #ddd; background: #f5f5f5; color: #555;
            font-size: 14px; font-weight: 700; cursor: pointer;
            font-family: 'Outfit', sans-serif; transition: background 0.2s;
        }
        .modal-btn-cancel:hover { background: #e8e8e8; }

        .modal-btn-ok {
            flex: 1; max-width: 140px; padding: 12px 0; border-radius: 50px;
            border: none; background: linear-gradient(135deg,#003366,#0055aa);
            color: #ffd700; font-size: 14px; font-weight: 800; cursor: pointer;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 4px 14px rgba(0,51,102,0.3);
            transition: all 0.2s;
        }
        .modal-btn-ok:hover { background: linear-gradient(135deg,#00285c,#004499); transform: translateY(-1px); }

        .modal-btn-solo {
            padding: 12px 44px; border-radius: 50px; border: none;
            background: linear-gradient(135deg,#003366,#0055aa); color: #ffd700;
            font-size: 14px; font-weight: 800; cursor: pointer;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 4px 14px rgba(0,51,102,0.3);
            transition: all 0.2s;
        }
        .modal-btn-solo:hover { background: linear-gradient(135deg,#00285c,#004499); transform: translateY(-1px); }
    </style>
</head>
<body>

    <div class="ambient-top"></div>
    <div class="ambient-bottom"></div>

    <!-- ====== Header ====== -->
    <div class="header-bar">
        <div class="header-brand">
            <div class="logo-ring"><i class="fa fa-gamepad"></i></div>
            <div>
                <h4>DLHS Live Quiz</h4>
                <p>Deeper Life High School</p>
            </div>
        </div>
        <div id="gameStatusIndicator" class="status-pill">OFFLINE</div>
    </div>

    <div class="container" style="max-width: 480px; position: relative; z-index: 1; margin: 40px auto;">

        <!-- ====== View 1: Join card ====== -->
        <div id="joinCard" class="game-card game-card-accent">
            <div style="width:64px; height:64px; background:linear-gradient(135deg,#003366,#00AEEF); border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 18px; box-shadow:0 10px 24px rgba(0,174,239,0.3);">
                <i class="fa fa-gamepad" style="font-size:28px; color:#ffd700;"></i>
            </div>

            <h4 style="font-size:20px; font-weight:800; margin-bottom:6px; color:#0f172a;">Join Classroom Quiz</h4>
            <p style="font-size:12px; color:var(--text-muted); margin-bottom:24px; line-height:1.5;">Your teacher has launched a live DLHS Classroom Quiz. Enter the PIN shown on the classroom Smartboard to join.</p>

            <form id="joinForm">
                <div style="margin-bottom:16px; text-align:left;">
                    <label class="dlhs-label" for="gamePinInput">Quiz PIN (shown on Smartboard)</label>
                    <input type="text" class="dlhs-input pin-input" id="gamePinInput"
                           placeholder="GAME-XXXX"
                           value="<?php echo $prefilledPin; ?>"
                           required autocomplete="off">
                </div>

                <div style="margin-bottom:22px; text-align:left;">
                    <label class="dlhs-label" for="nicknameInput">Your Display Name</label>
                    <input type="text" class="dlhs-input" id="nicknameInput"
                           placeholder="e.g. John O."
                           value="<?php echo htmlspecialchars($studentName); ?>"
                           readonly style="cursor: not-allowed; background: rgba(17, 39, 63, 0.03); color: rgba(15,23,42,0.6);"
                           required>
                    <p style="font-size:10px; color:var(--text-muted); margin-top:6px; text-align:left;">This is the name your teacher and classmates will see.</p>
                </div>

                <button type="submit" class="dlhs-btn-primary">
                    <i class="fa fa-sign-in"></i>&nbsp; Join Quiz
                </button>
            </form>
        </div>

        <!-- ====== View 2: Lobby Waiting ====== -->
        <div id="lobbyWaitingCard" class="game-card" style="display:none; padding:40px 24px;">
            <i class="fa fa-hourglass-half spin-icon" style="font-size:52px; color:var(--accent-yellow); margin-bottom:20px;"></i>
            <h4 style="font-size:20px; font-weight:800; color:#0f172a; margin-bottom:8px;">You're in the Lobby!</h4>
            <p id="playerNicknameVal" style="color:var(--dlhs-blue); font-weight:800; font-size:18px; margin:8px 0 22px;">—</p>

            <div class="info-box" style="margin-bottom:20px;">
                <i class="fa fa-television" style="color:var(--dlhs-blue); margin-right:6px;"></i>
                Watch the classroom Smartboard — your name should appear in the lobby list. The quiz will start shortly.
            </div>

            <button type="button" onclick="exitGame()" class="btn btn-default" style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.15); border-radius:14px; color:#ef4444; font-family:'Outfit',sans-serif; font-size:14px; font-weight:700; padding:12px 30px; cursor:pointer; width:100%; transition:all 0.2s;">
                <i class="fa fa-sign-out"></i>&nbsp; Leave Lobby
            </button>
        </div>

        <!-- ====== View 3: Active Response Pad ====== -->
        <div id="gamePadView" style="display:none; flex-grow:1; flex-direction:column;">
            <div class="question-progress-bar">
                <span id="questionProgressLabel" style="font-weight:700; color:#0f172a;">Question 1</span>
                <span style="color:var(--dlhs-gold); font-weight:800;">
                    <i class="fa fa-star"></i>&nbsp;Score:&nbsp;<span id="currentScoreVal">0</span>
                </span>
            </div>

            <div id="studentQuestionText" style="margin-top: 15px; font-size: 18px; font-weight: 700; color: #0f172a; text-align: center; padding: 0 10px; line-height: 1.4;">
                Loading question...
            </div>

            <div class="options-grid">
                <button class="option-card red" onclick="submitAnswer('A')">
                    <div class="option-marker">A</div>
                    <div class="option-text" id="tileLabelA">Option A</div>
                </button>
                <button class="option-card blue" onclick="submitAnswer('B')">
                    <div class="option-marker">B</div>
                    <div class="option-text" id="tileLabelB">Option B</div>
                </button>
                <button class="option-card yellow" onclick="submitAnswer('C')">
                    <div class="option-marker">C</div>
                    <div class="option-text" id="tileLabelC">Option C</div>
                </button>
                <button class="option-card green" onclick="submitAnswer('D')">
                    <div class="option-marker">D</div>
                    <div class="option-text" id="tileLabelD">Option D</div>
                </button>
            </div>
        </div>

        <!-- ====== View 4a: Answer Submitted – Waiting ====== -->
        <div id="feedbackSubmitted" class="feedback-screen waiting" style="display:none;">
            <div style="width:80px; height:80px; background:rgba(0,174,239,0.15); border:2px solid rgba(0,174,239,0.3); border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:20px;">
                <i class="fa fa-check" style="font-size:36px; color:var(--dlhs-blue);"></i>
            </div>
            <h3 style="font-size:22px; font-weight:800; color:#0f172a; margin-bottom:8px;">Answer Submitted!</h3>
            <p style="color:#475569; font-size:13px; line-height:1.5; max-width:260px;">
                Waiting for all participants and the timer to finish…
            </p>
        </div>

        <!-- ====== View 4b: Correct ====== -->
        <div id="feedbackCorrect" class="feedback-screen correct" style="display:none;">
            <i class="fa fa-check-circle" style="font-size:80px; color:var(--accent-green); margin-bottom:16px;"></i>
            <h2 style="font-size:38px; font-weight:800; letter-spacing:-0.02em; color:#166534;">CORRECT!</h2>
            <div class="stat-chip" style="color:#15803d; border-color:rgba(34,197,94,0.25); background:rgba(34,197,94,0.06); margin:16px 0;">
                +<span id="pointsEarnedVal">0</span> pts
            </div>
            <p style="font-size:14px; font-weight:600; color:#1e293b;">
                <i class="fa fa-bolt" style="color:var(--accent-yellow);"></i>&nbsp;
                Streak: <span id="streakCountVal">0</span> in a row!
            </p>
        </div>

        <!-- ====== View 4c: Incorrect ====== -->
        <div id="feedbackIncorrect" class="feedback-screen incorrect" style="display:none;">
            <i class="fa fa-times-circle" style="font-size:80px; color:var(--accent-red); margin-bottom:16px;"></i>
            <h2 style="font-size:34px; font-weight:800; letter-spacing:-0.02em; color:#991b1b;">INCORRECT</h2>
            <div class="stat-chip" style="color:#b91c1c; border-color:rgba(239,68,68,0.25); background:rgba(239,68,68,0.06); margin:16px 0;">
                +0 points
            </div>
            <p style="font-size:13px; color:#475569;">Your streak has been reset. Keep going — you've got the next one!</p>
        </div>

        <!-- ====== View 5: Scoreboard ====== -->
        <div id="scoreboardView" class="game-card" style="display:none; padding:38px 24px;">
            <i class="fa fa-bar-chart" style="font-size:52px; color:var(--dlhs-blue); margin-bottom:16px;"></i>
            <h4 style="font-size:20px; font-weight:800; margin-bottom:8px; color:#0f172a;">Check the Smartboard!</h4>
            <p style="font-size:13px; color:var(--text-muted); line-height:1.5; max-width:280px; margin:0 auto 20px;">
                The live class rankings are displaying on the main Smartboard. See your position among your classmates!
            </p>

            <div class="score-display">
                <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.15em; color:rgba(255,215,0,0.8); margin-bottom:8px;">Your Current Score</div>
                <div class="score-number" id="scoreboardTotalVal">0</div>
                <div style="font-size:11px; color:rgba(255,255,255,0.8); margin-top:6px;">points</div>
            </div>
        </div>

    </div><!-- /container -->

    <!-- ====== Javascripts ====== -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <script>
        const studentId = <?php echo $studentId; ?>;
        let gameId = null;
        let gamePin = '';
        let nickname = '';
        let pollTimer = null;

        let currentQuestionId = null;
        let questionShownTime = 0;
        let hasAnsweredCurrent = false;
        let activeGameStatus = '';
        let activeQuestionIndex = -1;

        $(document).ready(function() {
            // Pre-fill nickname from session
            if ($('#nicknameInput').val() === '') {
                $('#nicknameInput').val('<?php echo addslashes($studentName); ?>');
            }

            // Restore saved game session
            const savedGameId  = localStorage.getItem('student_game_id');
            const savedGamePin = localStorage.getItem('student_game_pin');
            const savedNick    = localStorage.getItem('student_game_nickname');

            if (savedGameId && savedGamePin && savedNick) {
                gameId   = parseInt(savedGameId);
                gamePin  = savedGamePin;
                nickname = savedNick;
                showLobbyWaiting();
            }

            // Handle Join Form
            $('#joinForm').submit(function(e) {
                e.preventDefault();
                gamePin  = $('#gamePinInput').val().trim().toUpperCase();
                nickname = $('#nicknameInput').val().trim();

                if (!gamePin || !nickname) return;

                $.ajax({
                    url: '../../smartboard/smartboard_sync.php',
                    type: 'POST',
                    data: {
                        action: 'student_join_game',
                        gamePin: gamePin,
                        nickname: nickname,
                        studentId: studentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            gameId = response.gameId;
                            localStorage.setItem('student_game_id',       gameId);
                            localStorage.setItem('student_game_pin',      gamePin);
                            localStorage.setItem('student_game_nickname', nickname);
                            showLobbyWaiting();
                        } else {
                            dlhsAlert('Unable to join: ' + response.message, 'error');
                        }
                    },
                    error: function() {
                        dlhsAlert('Network error. Please check your connection and try again.', 'error');
                    }
                });
            });
        });

        // ===== View transitions =====

        function showLobbyWaiting() {
            $('#joinCard').hide();
            $('#lobbyWaitingCard').show();
            $('#gamePadView').hide();
            $('#playerNicknameVal').html(getFancyPlayerMarkup(nickname, studentId));
            setStatusPill('lobby', 'LOBBY');
            startControllerPolling();
        }

        function setStatusPill(cls, label) {
            $('#gameStatusIndicator')
                .attr('class', 'status-pill ' + cls)
                .text(label);
        }

        // ===== Polling =====

        function startControllerPolling() {
            if (pollTimer) clearInterval(pollTimer);

            pollTimer = setInterval(function() {
                $.ajax({
                    url: '../../smartboard/smartboard_sync.php',
                    type: 'GET',
                    cache: false,
                    data: {
                        action: 'student_poll_state',
                        gameId: gameId,
                        studentId: studentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            handleGameStateUpdate(response);
                        } else {
                            exitGame();
                        }
                    }
                });
            }, 1200);
        }

        function handleGameStateUpdate(game) {
            if (game.gameStatus === 'lobby') {
                setStatusPill('lobby', 'LOBBY');
                $('#lobbyWaitingCard').show();
                $('#gamePadView').hide();
                hideAllFeedback();
                $('#scoreboardView').hide();

            } else if (game.gameStatus === 'showing_question') {
                setStatusPill('active', 'LIVE');
                $('#lobbyWaitingCard').hide();
                $('#scoreboardView').hide();

                if (activeQuestionIndex !== game.currentQuestionIndex || activeGameStatus !== 'showing_question') {
                    hasAnsweredCurrent = game.hasAnswered;
                    currentQuestionId  = game.questionId;
                    questionShownTime  = Date.now();
                    $('#questionProgressLabel').text('Question ' + (game.currentQuestionIndex + 1) + ' of ' + game.totalQuestions);
                    $('#currentScoreVal').text(game.score);
                    
                    if(game.questionText) {
                        $('#studentQuestionText').html(game.questionText);
                        $('#tileLabelA').text(game.options && game.options['A'] ? game.options['A'] : 'Option A');
                        $('#tileLabelB').text(game.options && game.options['B'] ? game.options['B'] : 'Option B');
                        $('#tileLabelC').text(game.options && game.options['C'] ? game.options['C'] : 'Option C');
                        $('#tileLabelD').text(game.options && game.options['D'] ? game.options['D'] : 'Option D');
                    }
                }

                if (hasAnsweredCurrent) {
                    $('#gamePadView').hide();
                    hideAllFeedback();
                    $('#feedbackSubmitted').show();
                } else {
                    $('#gamePadView').show();
                    hideAllFeedback();
                }

            } else if (game.gameStatus === 'showing_answers') {
                setStatusPill('waiting', 'RESULTS');
                $('#gamePadView').hide();
                $('#lobbyWaitingCard').hide();
                $('#scoreboardView').hide();

                if (game.hasAnswered) {
                    $.ajax({
                        url: '../../smartboard/smartboard_sync.php',
                        type: 'GET',
                        data: { action: 'student_poll_state', gameId: gameId, studentId: studentId },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success && res.hasAnswered) {
                                const isCorrect = (res.selectedAnswer === res.correctOption);
                                hideAllFeedback();
                                if (isCorrect) {
                                    const basePoints   = 500;
                                    const responseTime = localStorage.getItem('last_response_time_' + game.questionId) || 5000;
                                    const bonus        = Math.round(500 * (1 - (Math.min(responseTime, 30000) / 30000)));
                                    $('#pointsEarnedVal').text(basePoints + bonus);
                                    $('#streakCountVal').text(res.streak);
                                    $('#feedbackCorrect').show();
                                } else {
                                    $('#feedbackIncorrect').show();
                                }
                            }
                        }
                    });
                } else {
                    hideAllFeedback();
                    $('#feedbackIncorrect').show();
                }

            } else if (game.gameStatus === 'leaderboard') {
                setStatusPill('waiting', 'SCORES');
                $('#gamePadView').hide();
                $('#lobbyWaitingCard').hide();
                hideAllFeedback();
                $('#scoreboardTotalVal').text(game.score);
                $('#scoreboardView').show();

            } else if (game.gameStatus === 'finished') {
                setStatusPill('finished', 'FINISHED');
                if (pollTimer) clearInterval(pollTimer);
                localStorage.removeItem('student_game_id');
                localStorage.removeItem('student_game_pin');
                localStorage.removeItem('student_game_nickname');

                $('#gamePadView').hide();
                hideAllFeedback();

                $('#scoreboardView').html(`
                    <div style="width:80px;height:80px;background:linear-gradient(135deg,#003366,#ffd700);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 12px 28px rgba(255,215,0,0.25);">
                        <i class="fa fa-trophy" style="font-size:34px;color:#fff;"></i>
                    </div>
                    <h3 style="font-size:24px;font-weight:800;color:#0f172a;margin-bottom:8px;">Quiz Complete!</h3>
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin-bottom:24px;">
                        The final podium rankings are now showing on the Smartboard. Great effort!
                    </p>
                    <div class="score-display" style="margin-bottom: 20px;">
                        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.15em;color:rgba(255,215,0,0.8);margin-bottom:8px;">Your Final Score</div>
                        <div class="score-number">${game.score}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.8);margin-top:6px;">points</div>
                    </div>
                    <button onclick="exitGame()" style="background:rgba(17, 39, 63, 0.05);border:1px solid rgba(17, 39, 63, 0.1);border-radius:14px;color:#475569;font-family:'Outfit',sans-serif;font-size:14px;font-weight:700;padding:12px 30px;cursor:pointer;width:100%;transition:background 0.2s;">
                        <i class="fa fa-sign-out"></i>&nbsp; Leave Quiz
                    </button>
                `);
                $('#scoreboardView').show();
            }

            activeGameStatus    = game.gameStatus;
            activeQuestionIndex = game.currentQuestionIndex;
        }

        function submitAnswer(option) {
            if (hasAnsweredCurrent) return;
            hasAnsweredCurrent = true;

            const responseTimeMs = Date.now() - questionShownTime;
            localStorage.setItem('last_response_time_' + currentQuestionId, responseTimeMs);

            $('#gamePadView').hide();
            $('#feedbackSubmitted').show();

            $.ajax({
                url: '../../smartboard/smartboard_sync.php',
                type: 'POST',
                data: {
                    action: 'student_submit_answer',
                    gameId: gameId,
                    questionId: currentQuestionId,
                    studentId: studentId,
                    answerSelected: option,
                    responseTimeMs: responseTimeMs
                },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        dlhsAlert('Submission error: ' + response.message, 'error');
                    }
                }
            });
        }

        function hideAllFeedback() {
            $('#feedbackSubmitted').hide();
            $('#feedbackCorrect').hide();
            $('#feedbackIncorrect').hide();
        }

        function getFancyPlayerMarkup(name, studentId, isChip = true) {
            let hash = 0;
            const idStr = String(studentId || name);
            for (let i = 0; i < idStr.length; i++) {
                hash = idStr.charCodeAt(i) + ((hash << 5) - hash);
            }
            const emojis = ['⚡', '🚀', '👑', '💎', '🌟', '🔮', '🦄', '🦁', '🦊', '🦉', '🐝', '🎨', '🔥', '🏆', '👾', '🌈', '🎯'];
            const emoji = emojis[Math.abs(hash) % emojis.length];
            const hue1 = Math.abs(hash) % 360;
            const hue2 = (hue1 + 40) % 360;
            const gradient = `linear-gradient(135deg, hsl(${hue1}, 80%, 45%), hsl(${hue2}, 85%, 35%))`;
            
            if (isChip) {
                return `
                    <div style="background: ${gradient}; color: #ffffff; border: 1px solid rgba(255,255,255,0.25); box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-shadow: 0 1px 2px rgba(0,0,0,0.3); font-weight: 700; padding: 12px 20px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <span style="font-size: 18px;">${emoji}</span>
                        <span>${escapeHtml(name)}</span>
                    </div>
                `;
            } else {
                return `
                    <span style="background: ${gradient}; color: #ffffff; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-family: 'Outfit', sans-serif; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.2); text-shadow: 0 1px 1px rgba(0,0,0,0.2);">
                        <span>${emoji}</span>
                        <span>${escapeHtml(name)}</span>
                    </span>
                `;
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function exitGame() {
            if (pollTimer) clearInterval(pollTimer);
            localStorage.removeItem('student_game_id');
            localStorage.removeItem('student_game_pin');
            localStorage.removeItem('student_game_nickname');

            gameId = null; gamePin = ''; nickname = '';

            $('#joinCard').show();
            $('#lobbyWaitingCard').hide();
            $('#gamePadView').hide();
            hideAllFeedback();
            $('#scoreboardView').hide();
            setStatusPill('', 'OFFLINE');
        }
    </script>

    <!-- ===== DLHS Universal Confirm Modal ===== -->
    <div id="dlhsConfirmModal" onclick="if(event.target===this){this.classList.remove('dlhs-open');_dlhsCb=null;}">
        <div class="modal-inner" onclick="event.stopPropagation()">
            <div class="modal-icon" id="dlhsConfirmIconWrap" style="color:#f59e0b;">
                <i class="fa fa-question-circle"></i>
            </div>
            <div class="modal-body">
                <p class="modal-msg" id="dlhsConfirmMsg"></p>
                <div class="modal-actions">
                    <button class="modal-btn-cancel" id="dlhsConfirmCancelBtn">Cancel</button>
                    <button class="modal-btn-ok"     id="dlhsConfirmOkBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DLHS Universal Alert Modal ===== -->
    <div id="dlhsAlertModal" onclick="if(event.target===this){this.classList.remove('dlhs-open');}">
        <div class="modal-inner" onclick="event.stopPropagation()">
            <div class="modal-icon" id="dlhsAlertIconWrap" style="color:#3b82f6;">
                <i class="fa fa-info-circle"></i>
            </div>
            <div class="modal-body">
                <p class="modal-msg" id="dlhsAlertMsg"></p>
                <div style="text-align:center;">
                    <button class="modal-btn-solo" id="dlhsAlertOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>

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
            var iw = document.getElementById('dlhsAlertIconWrap');
            if      (type === 'error')   iw.innerHTML = '<i class="fa fa-times-circle" style="color:#ef4444;"></i>';
            else if (type === 'success') iw.innerHTML = '<i class="fa fa-check-circle" style="color:#22c55e;"></i>';
            else                          iw.innerHTML = '<i class="fa fa-info-circle" style="color:#3b82f6;"></i>';
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
            document.getElementById('dlhsAlertOkBtn').addEventListener('click', function(){
                document.getElementById('dlhsAlertModal').classList.remove('dlhs-open');
            });
        });
    })();
    </script>

</body>
</html>
