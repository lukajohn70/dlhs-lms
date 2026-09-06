<?php
session_start();
require_once "../db_connection/dlhs_db_connection.php";

// Set headers for standard HTML5
header("X-Frame-Options: ALLOWALL");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DLHS Smartboard Connect</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="../staffLogin/staffPanel/css/font-awesome.css" rel="stylesheet" />
    
    <style>
        :root {
            --dlhs-blue: #00AEEF;
            --dlhs-magenta: #E91E63;
            --dlhs-navy: #020b1a;
            --dlhs-ink: #10233a;
            --dlhs-soft: rgba(255, 255, 255, 0.58);
            --dlhs-border: rgba(255, 255, 255, 0.72);
            --dlhs-sidebar: rgba(2, 11, 26, 0.82);
            --dlhs-shadow: 0 24px 60px rgba(9, 27, 53, 0.18);
            
            --primary: #00AEEF;
            --text-muted: #5d7389;
            --accent-red: #E91E63;
            --accent-blue: #00AEEF;
            --accent-yellow: #f59e0b;
            --accent-green: #22c55e;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            background: linear-gradient(135deg, #eef5fb 0%, #dcecff 40%, #f9f1f6 100%);
            color: var(--dlhs-ink);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Background Orbs */
        .background-orb {
            position: fixed;
            border-radius: 999px;
            filter: blur(110px);
            opacity: 0.2;
            z-index: 0;
            pointer-events: none;
        }

        .background-orb.one {
            width: 32rem;
            height: 32rem;
            top: -10rem;
            left: -8rem;
            background: var(--dlhs-blue);
        }

        .background-orb.two {
            width: 28rem;
            height: 28rem;
            right: -6rem;
            bottom: -6rem;
            background: var(--dlhs-magenta);
        }

        /* Glassmorphism Container */
        .smartboard-container {
            width: 90%;
            max-width: 1400px;
            min-height: 80vh;
            background: var(--dlhs-soft);
            backdrop-filter: blur(22px) saturate(160%);
            -webkit-backdrop-filter: blur(22px) saturate(160%);
            border: 1px solid var(--dlhs-border);
            border-radius: 28px;
            box-shadow: var(--dlhs-shadow);
            z-index: 10;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            transition: all 0.5s ease-in-out;
        }

        /* Header Style */
        .smartboard-header {
            position: absolute;
            top: 30px;
            left: 40px;
            right: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(17, 39, 63, 0.08);
            padding-bottom: 15px;
            z-index: 20;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-section img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid var(--dlhs-blue);
        }

        .logo-text h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #12273f;
        }

        .logo-text p {
            font-size: 11px;
            color: var(--dlhs-blue);
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-weight: 700;
        }

        /* Pairing View */
        .pairing-layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            align-items: center;
            padding-top: 60px;
        }

        .pairing-instructions h2 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
            color: #12273f;
            letter-spacing: -0.04em;
        }

        .pairing-instructions p {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .pin-box {
            background: rgba(255, 255, 255, 0.82);
            border: 2px dashed rgba(0, 174, 239, 0.3);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            position: relative;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 174, 239, 0.08);
        }

        .pin-box:hover {
            border-color: var(--dlhs-blue);
            box-shadow: 0 0 20px rgba(0, 174, 239, 0.15);
        }

        .pin-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--dlhs-blue);
            font-weight: 800;
            margin-bottom: 10px;
        }

        .pin-code {
            font-size: 64px;
            font-weight: 800;
            letter-spacing: 6px;
            color: var(--dlhs-ink);
            font-family: monospace;
        }

        .qr-card {
            background: #fff;
            padding: 20px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(9, 27, 53, 0.18);
            transition: all 0.3s ease;
            border: 1px solid rgba(17, 39, 63, 0.05);
        }

        .qr-card:hover {
            transform: translateY(-5px);
        }

        .qr-card img {
            width: 220px;
            height: 220px;
        }

        .qr-label {
            margin-top: 15px;
            color: var(--dlhs-ink);
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        /* Presentation Mode Viewports */
        .presentation-viewport {
            width: 100%;
            height: 70vh;
            margin-top: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(17, 39, 63, 0.08);
            box-shadow: 0 10px 30px rgba(9, 27, 53, 0.1);
        }

        .presentation-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .presentation-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .presentation-video {
            width: 100%;
            height: 100%;
        }

        /* Gamified Assessment CSS */
        .game-lobby {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 60px;
            text-align: center;
        }

        .game-lobby h2 {
            font-size: 48px;
            font-weight: 800;
            color: var(--dlhs-ink);
            margin-bottom: 10px;
            letter-spacing: -0.04em;
        }

        .game-pin-large {
            font-size: 72px;
            font-weight: 800;
            color: var(--dlhs-blue);
            letter-spacing: 4px;
            margin: 20px 0;
            text-shadow: 0 10px 20px rgba(0, 174, 239, 0.2);
        }

        .player-grid-title {
            font-size: 20px;
            color: var(--text-muted);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            width: 100%;
            max-width: 1000px;
            margin-top: 20px;
            max-height: 35vh;
            overflow-y: auto;
            padding: 10px;
        }

        .player-chip {
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(17, 39, 63, 0.1);
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-transform: capitalize;
            color: var(--dlhs-ink);
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(9, 27, 53, 0.05);
        }

        .player-chip i {
            color: var(--dlhs-blue);
        }

        /* Active Question Display */
        .question-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding-top: 60px;
            height: 100%;
        }

        .timer-container {
            position: absolute;
            top: 80px;
            right: 40px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid var(--dlhs-blue);
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            color: var(--dlhs-blue);
            box-shadow: 0 10px 25px rgba(0, 174, 239, 0.2);
            animation: pulse 1s infinite alternate;
        }

        .question-text {
            font-size: 36px;
            font-weight: 800;
            color: var(--dlhs-ink);
            text-align: center;
            max-width: 900px;
            margin-bottom: 40px;
            line-height: 1.4;
            letter-spacing: -0.02em;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
            max-width: 1100px;
            margin-top: 20px;
        }

        .option-card {
            border-radius: 16px;
            padding: 30px;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 15px 35px rgba(9, 27, 53, 0.15);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .option-card.red { background: linear-gradient(135deg, #E91E63, #c2185b); }
        .option-card.blue { background: linear-gradient(135deg, #00AEEF, #0081b3); }
        .option-card.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .option-card.green { background: linear-gradient(135deg, #22c55e, #15803d); }

        .option-marker {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
        }

        /* Results Distribution Chart */
        .results-chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 40px;
            width: 100%;
            height: 300px;
            margin-top: 40px;
            border-bottom: 2px solid rgba(17, 39, 63, 0.1);
            padding-bottom: 10px;
        }

        .chart-bar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 120px;
        }

        .chart-bar {
            width: 80px;
            border-radius: 8px 8px 0 0;
            transition: height 1s ease-out;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 10px;
            font-weight: 800;
            font-size: 20px;
            color: #fff;
            box-shadow: 0 10px 20px rgba(9, 27, 53, 0.2);
        }

        .chart-bar.red { background: var(--accent-red); }
        .chart-bar.blue { background: var(--dlhs-blue); }
        .chart-bar.yellow { background: var(--accent-yellow); }
        .chart-bar.green { background: var(--accent-green); }

        .chart-label {
            margin-top: 10px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-muted);
        }

        /* Leaderboard Podiums */
        .leaderboard-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding-top: 40px;
        }

        .leaderboard-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--dlhs-blue);
            margin-bottom: 30px;
            letter-spacing: -0.02em;
        }

        .podium-wrapper {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 30px;
            width: 100%;
            max-width: 800px;
            margin-bottom: 40px;
        }

        .podium-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 180px;
            text-align: center;
        }

        .podium-player-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--dlhs-ink);
            margin-bottom: 8px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            width: 100%;
        }

        .podium-player-score {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .podium-block {
            width: 100%;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 15px 35px rgba(9, 27, 53, 0.2);
        }

        .podium-block.gold {
            height: 180px;
            background: linear-gradient(135deg, #ffd700, #daa520);
            border: 2px solid #fff3a8;
        }

        .podium-block.silver {
            height: 140px;
            background: linear-gradient(135deg, #c0c0c0, #808080);
            border: 2px solid #e1e1e1;
        }

        .podium-block.bronze {
            height: 100px;
            background: linear-gradient(135deg, #cd7f32, #8b4513);
            border: 2px solid #ffbc9b;
        }

        .leaderboard-list {
            width: 100%;
            max-width: 700px;
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(17, 39, 63, 0.1);
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(9, 27, 53, 0.08);
        }

        .leaderboard-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(17, 39, 63, 0.05);
        }

        .leaderboard-row:last-child {
            border-bottom: none;
        }

        .leaderboard-rank {
            font-weight: 800;
            color: var(--dlhs-blue);
            width: 30px;
        }

        .leaderboard-name {
            font-weight: 700;
            flex-grow: 1;
            margin-left: 10px;
            color: var(--dlhs-ink);
        }

        .leaderboard-score {
            font-weight: 800;
            color: var(--dlhs-ink);
        }

        /* Animations */
        @keyframes popIn {
            0% { transform: scale(0.6); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 15px rgba(0, 174, 239, 0.2); }
            100% { transform: scale(1.05); box-shadow: 0 0 30px rgba(0, 174, 239, 0.5); }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(17, 39, 63, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(17, 39, 63, 0.2);
            border-radius: 3px;
        }
    </style>
</head>
<body>

    <div class="background-orb one"></div>
    <div class="background-orb two"></div>

    <div class="smartboard-container">
        <div class="smartboard-header">
            <div class="logo-section">
                <img src="../images/dlhslogo3.jpg" alt="DLHS Logo">
                <div class="logo-text">
                    <h1>DLHS Smartboard Connect</h1>
                    <p>Interactive Classroom Learning</p>
                </div>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <button onclick="toggleFullScreen()" style="background: rgba(0,174,239,0.1); border: 1px solid rgba(0,174,239,0.2); color: var(--dlhs-blue); border-radius: 8px; padding: 6px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12px; transition: 0.3s;">
                    <i class="fa fa-arrows-alt"></i> Fullscreen
                </button>
                <div id="connectionStatus" style="font-size: 13px; font-weight: 700; background: rgba(233,30,99,0.1); border: 1px solid rgba(233,30,99,0.2); color: #c2185b; padding: 6px 16px; border-radius: 30px; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 8px; height: 8px; background: var(--accent-red); border-radius: 50%; display: inline-block;" id="statusIndicator"></span>
                    <span id="statusLabel">Disconnected</span>
                </div>
            </div>
        </div>

        <!-- Render Target Area -->
        <div id="smartboardViewport" style="width: 100%; margin-top: 50px; flex-grow: 1; display: flex; flex-direction: column;">
            <!-- Loading initial state... -->
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh; width: 100%;">
                <i class="fa fa-spinner fa-spin" style="font-size: 40px; color: var(--dlhs-blue); margin-bottom: 20px;"></i>
                <p style="color: var(--text-muted); font-weight: 600;">Initializing DLHS Smartboard Interface...</p>
            </div>
        </div>
    </div>

    <!-- Javascripts -->
    <script src="../staffLogin/staffPanel/js/jquery.js"></script>
    
    <script>
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.webkitRequestFullscreen) { /* Safari */
                    document.documentElement.webkitRequestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) { /* IE11 */
                    document.documentElement.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { /* Safari */
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) { /* IE11 */
                    document.msExitFullscreen();
                }
            }
        }

        let currentPin = '';
        let currentSessionId = null;
        let currentState = 'lobby'; // lobby, active, game_lobby, game_question, game_answers, game_leaderboard
        let lastUpdated = '';
        let pollTimer = null;
        let activeGamePin = '';
        let activeGameId = null;
        
        $(document).ready(function() {
            // Start connection and initialization
            initSmartboard();
        });

        function initSmartboard() {
            // Query a new or pending session from the database
            $.ajax({
                url: 'smartboard_sync.php',
                type: 'GET',
                data: { action: 'init' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        currentPin = response.pin;
                        currentSessionId = response.sessionId;
                        
                        // Set connection status to pending pairing
                        $('#statusIndicator').css('background', 'var(--accent-yellow)');
                        $('#statusLabel').text('Awaiting Pairing...');
                        
                        renderPairingScreen(currentPin);
                        
                        // Start polling for pairing status
                        startPolling();
                    } else {
                        // Display error
                        $('#smartboardViewport').html(`
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh; text-align: center;">
                                <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: var(--accent-red); margin-bottom: 20px;"></i>
                                <h3 style="font-size: 24px; margin-bottom: 10px;">Connection Failure</h3>
                                <p style="color: var(--text-muted);">${response.message || 'Unable to establish a new smartboard session.'}</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#smartboardViewport').html(`
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh; text-align: center;">
                            <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: var(--accent-red); margin-bottom: 20px;"></i>
                            <h3 style="font-size: 24px; margin-bottom: 10px;">Network Failure</h3>
                            <p style="color: var(--text-muted);">Please check database connection or refresh the page.</p>
                        </div>
                    `);
                }
            });
        }

        function renderPairingScreen(pin) {
            const pairingUrl = window.location.origin + '/staffLogin/staffPanel/smartboard_remote.php?pin=' + pin;
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(pairingUrl);
            
            let html = `
                <div class="pairing-layout">
                    <div class="pairing-instructions">
                        <h2>Secure Interactive Display</h2>
                        <p>Project your learning materials and gamified interactive assessments seamlessly to this smartboard directly from your authorized staff portal.</p>
                        
                        <div class="pin-box">
                            <div class="pin-label">Smartboard Pairing PIN</div>
                            <div class="pin-code">${pin}</div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 15px; background: rgba(0, 174, 239, 0.05); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(0, 174, 239, 0.15);">
                            <i class="fa fa-info-circle" style="color: var(--dlhs-blue); font-size: 20px;"></i>
                            <span style="font-size: 14px; color: #16314d; line-height: 1.4; font-weight: 600;">
                                On your staff dashboard, navigate to <strong>Smartboard Remote</strong>. Enter the 6-digit PIN above or scan the QR code to establish a secure link.
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: center;">
                        <div class="qr-card">
                            <img src="${qrUrl}" alt="Pairing QR Code">
                            <div class="qr-label">Scan to Connect</div>
                        </div>
                    </div>
                </div>
            `;
            $('#smartboardViewport').html(html);
        }

        // YouTube IFrame API global
        let ytPlayer = null;
        let ytPlayerReady = false;
        let ytApiLoaded = false;

        // Called automatically by YouTube API script
        window.onYouTubeIframeAPIReady = function() {
            ytApiLoaded = true;
            if (window._pendingYTVideoId) {
                initYouTubePlayer(window._pendingYTVideoId);
                window._pendingYTVideoId = null;
            }
        };

        function initYouTubePlayer(videoId) {
            ytPlayerReady = false;
            if (ytPlayer && typeof ytPlayer.destroy === 'function') {
                ytPlayer.destroy();
                ytPlayer = null;
            }
            // Ensure the container exists
            if (!document.getElementById('ytPlayerContainer')) return;
            ytPlayer = new YT.Player('ytPlayerContainer', {
                videoId: videoId,
                playerVars: { autoplay: 1, rel: 0, modestbranding: 1 },
                events: {
                    onReady: function(e) {
                        ytPlayerReady = true;
                        e.target.playVideo();
                    },
                    onError: function(e) {
                        console.warn('YouTube player error:', e.data);
                    }
                }
            });
        }

        function startPolling() {
            if (pollTimer) clearInterval(pollTimer);
            
            pollTimer = setInterval(function() {
                let vParams = {};

                // HTML5 video telemetry
                const vid = document.getElementById('smartboardVideoPlayer');
                if (vid) {
                    vParams = {
                        videoCurrentTime: vid.currentTime,
                        videoDuration: vid.duration || 0,
                        videoIsPaused: vid.paused ? 1 : 0,
                        videoIsMuted: vid.muted ? 1 : 0
                    };
                }

                // YouTube player telemetry
                if (ytPlayer && ytPlayerReady && typeof ytPlayer.getCurrentTime === 'function') {
                    try {
                        const ytState = ytPlayer.getPlayerState(); // 1=playing, 2=paused
                        vParams = {
                            videoCurrentTime: ytPlayer.getCurrentTime() || 0,
                            videoDuration: ytPlayer.getDuration() || 0,
                            videoIsPaused: (ytState === 2 || ytState === 0) ? 1 : 0,
                            videoIsMuted: ytPlayer.isMuted() ? 1 : 0
                        };
                    } catch(e) {}
                }

                $.ajax({
                    url: 'smartboard_sync.php',
                    type: 'GET',
                    data: $.extend({ 
                        action: 'poll_smartboard', 
                        sessionId: currentSessionId, 
                        pin: currentPin 
                    }, vParams),
                    success: function(response) {
                        if (response.success) {
                            if (response.status === 'active') {
                                $('#connectionStatus').css({
                                    'background': 'rgba(34, 197, 94, 0.1)',
                                    'border': '1px solid rgba(34, 197, 94, 0.2)',
                                    'color': '#15803d'
                                });
                                $('#statusIndicator').css('background', 'var(--accent-green)');
                                $('#statusLabel').text('Connected: ' + response.teacherName);
                                
                                handleActiveSessionState(response.state);

                                const state = response.state;
                                if (state && state.videoCommand) {
                                    const cmd = state.videoCommand;
                                    const val = state.videoCommandValue;

                                    // HTML5 video commands
                                    if (vid) {
                                        if (cmd === 'play') vid.play().catch(e => {});
                                        else if (cmd === 'pause') vid.pause();
                                        else if (cmd === 'mute') vid.muted = true;
                                        else if (cmd === 'unmute') vid.muted = false;
                                        else if (cmd === 'seek') vid.currentTime = parseFloat(val);
                                        else if (cmd === 'volume') vid.volume = parseFloat(val);
                                    }

                                    // YouTube player commands
                                    if (ytPlayer && ytPlayerReady) {
                                        try {
                                            if (cmd === 'play') ytPlayer.playVideo();
                                            else if (cmd === 'pause') ytPlayer.pauseVideo();
                                            else if (cmd === 'mute') ytPlayer.mute();
                                            else if (cmd === 'unmute') ytPlayer.unMute();
                                            else if (cmd === 'seek') ytPlayer.seekTo(parseFloat(val), true);
                                            else if (cmd === 'volume') ytPlayer.setVolume(parseFloat(val) * 100);
                                        } catch(e) { console.warn('YT command error:', e); }
                                    }
                                }
                            } else if (response.status === 'expired') {
                                initSmartboard();
                            }
                        }
                    },
                    error: function() {}
                });
            }, 1200);
        }

        // Active paired states controller
        let activeFileId = null;
        let activePage = 1;
        let activeGameStatus = '';
        let activeQuestionIndex = -1;
        
        function handleActiveSessionState(state) {
            if (!state) return;
            
            // 1. If there's an active gamified Kahoot session launching/active
            if (state.activeGame) {
                const game = state.activeGame;
                activeGameId = game.gameId;
                activeGamePin = game.gamePin;
                
                if (game.gameStatus === 'lobby') {
                    renderGameLobby(game.gamePin, game.players);
                } else if (game.gameStatus === 'showing_question') {
                    if (activeQuestionIndex !== game.currentQuestionIndex || activeGameStatus !== 'showing_question') {
                        renderGameQuestion(game);
                    }
                } else if (game.gameStatus === 'showing_answers') {
                    if (activeGameStatus !== 'showing_answers' || activeQuestionIndex !== game.currentQuestionIndex) {
                        renderQuestionAnswers(game);
                    }
                } else if (game.gameStatus === 'leaderboard') {
                    if (activeGameStatus !== 'leaderboard' || activeQuestionIndex !== game.currentQuestionIndex) {
                        renderGameLeaderboard(game.leaderboard);
                    }
                } else if (game.gameStatus === 'finished') {
                    renderGameFinished(game.leaderboard);
                }
                
                activeGameStatus = game.gameStatus;
                activeQuestionIndex = game.currentQuestionIndex;
                return;
            }
            
            // Clear any active game status to allow file loading
            activeGameStatus = '';
            activeQuestionIndex = -1;
            
            // 2. Otherwise handle normal file presentation
            if (state.activeFileId) {
                if (activeFileId !== state.activeFileId || activePage !== state.activePage) {
                    activeFileId = state.activeFileId;
                    activePage = state.activePage;
                    renderFilePresentation(state.fileDetails, activePage);
                }
            } else {
                // Connected, but no file is selected yet
                activeFileId = null;
                $('#smartboardViewport').html(`
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh; text-align: center;">
                        <i class="fa fa-television" style="font-size: 64px; color: var(--primary); margin-bottom: 20px; animation: pulse 2s infinite alternate;"></i>
                        <h3 style="font-size: 28px; margin-bottom: 10px; font-weight: 700;">Smartboard Connected Successfully!</h3>
                        <p style="color: var(--text-muted); max-width: 600px; font-size: 16px;">Pairing active. Use your personal device remote control to open presentation materials, slides, slides, or start an interactive class quiz.</p>
                    </div>
                `);
            }
        }

        // Render File viewers
        function renderFilePresentation(file, page) {
            if (!file) return;

            const fileType = (file.fileType || '').toLowerCase();
            // fileName is the stored filename on disk; for YouTube, filePath IS the URL
            const isYouTube = (fileType === 'youtube');
            const ext = isYouTube ? 'youtube' : (file.fileName || file.originalName || '').split('.').pop().toLowerCase();

            // Web-accessible URL for local files
            const fileUrl = isYouTube
                ? (file.filePath || file.originalName)          // filePath stores the YouTube URL
                : ('../uploads/resources/' + (file.fileName || '')); // local files

            let viewerHtml = '';
            let modeBadge = '';

            if (isYouTube) {
                modeBadge = '▶ YouTube Video';
                const videoId = (function(url) {
                    const reg = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:.*v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
                    const match = (url || '').match(reg);
                    return match ? match[1] : null;
                })(file.filePath || file.originalName);
                if (videoId) {
                    // Use YouTube IFrame API for full playback control from the remote
                    viewerHtml = `<div id="ytPlayerContainer" style="width:100%;height:100%;"></div>`;
                    // Defer player initialization until the DOM is updated
                    setTimeout(function() {
                        if (typeof YT !== 'undefined' && YT.Player) {
                            initYouTubePlayer(videoId);
                        } else {
                            window._pendingYTVideoId = videoId;
                            // Load API if not already loaded
                            if (!document.getElementById('yt-iframe-api')) {
                                var tag = document.createElement('script');
                                tag.id = 'yt-iframe-api';
                                tag.src = 'https://www.youtube.com/iframe_api';
                                document.head.appendChild(tag);
                            }
                        }
                    }, 100);
                } else {
                    viewerHtml = `<div style="text-align:center;padding:60px;color:var(--text-muted);"><i class="fa fa-youtube-play" style="font-size:64px;color:#E91E63;"></i><p style="margin-top:20px;">Invalid YouTube URL</p></div>`;
                }

            } else if (ext === 'pdf') {
                modeBadge = '📄 PDF Presentation';
                viewerHtml = `<iframe class="presentation-iframe" src="${fileUrl}#page=${page}" allowfullscreen></iframe>`;

            } else if (['jpg','jpeg','png','gif','webp','bmp'].includes(ext)) {
                modeBadge = '🖼️ Image';
                viewerHtml = `<img class="presentation-image" src="${fileUrl}" alt="${escapeHtml(file.title || '')}" style="max-width:100%;max-height:80vh;object-fit:contain;display:block;margin:0 auto;">`;

            } else if (['mp4','webm','ogg','mov','avi','mkv'].includes(ext)) {
                modeBadge = '🎬 Video';
                viewerHtml = `<video id="smartboardVideoPlayer" class="presentation-video" src="${fileUrl}" controls autoplay style="width:100%;max-height:80vh;"></video>`;

            } else if (['ppt','pptx'].includes(ext)) {
                modeBadge = `📊 PPTX Presentation`;
                if (file.pptxData && file.pptxData.slides) {
                    const totalSlides = file.pptxData.slides.length;
                    const slideIndex = Math.max(0, Math.min(page - 1, totalSlides - 1));
                    const currentSlide = file.pptxData.slides[slideIndex];
                    
                    let elementsHtml = '';
                    if (currentSlide && currentSlide.elements && currentSlide.elements.length > 0) {
                        currentSlide.elements.forEach(el => {
                            let style = `position: absolute; left: ${el.left}%; top: ${el.top}%; width: ${el.width}%; height: ${el.height}%; box-sizing: border-box; overflow: hidden;`;
                            if (el.fill_color) {
                                style += ` background-color: ${el.fill_color};`;
                            }
                            if (el.border_color) {
                                style += ` border: 2px solid ${el.border_color};`;
                            }
                            
                            if (el.type === 'image' && el.src) {
                                elementsHtml += `
                                    <div style="${style}">
                                        <img src="${el.src}" style="width: 100%; height: 100%; object-fit: contain; display: block;">
                                    </div>`;
                            } else if (el.type === 'text') {
                                let paragraphsHtml = '';
                                if (el.paragraphs && el.paragraphs.length > 0) {
                                    el.paragraphs.forEach(p => {
                                        let runsHtml = '';
                                        if (p.runs && p.runs.length > 0) {
                                            p.runs.forEach(run => {
                                                let runStyle = '';
                                                if (run.bold) runStyle += ' font-weight: bold;';
                                                if (run.italic) runStyle += ' font-style: italic;';
                                                if (run.underline) runStyle += ' text-decoration: underline;';
                                                if (run.color) runStyle += ` color: ${run.color};`;
                                                if (run.size) {
                                                    // Perfect text scaling matching container dimensions
                                                    runStyle += ` font-size: calc(${run.size}px * 0.95);`;
                                                }
                                                if (run.font_name) {
                                                    runStyle += ` font-family: "${run.font_name}", sans-serif;`;
                                                }
                                                runsHtml += `<span style="${runStyle}">${escapeHtml(run.text)}</span>`;
                                            });
                                        }
                                        paragraphsHtml += `<div style="text-align: ${p.align || 'left'}; margin-bottom: 4px; line-height: 1.3;">${runsHtml || escapeHtml(p.text)}</div>`;
                                    });
                                } else {
                                    paragraphsHtml = `<div style="text-align: left; line-height: 1.3; font-size: 16px; color: #fff;">${escapeHtml(el.text || '')}</div>`;
                                }
                                
                                elementsHtml += `
                                    <div style="${style} padding: 6px; display: flex; flex-direction: column; justify-content: flex-start; text-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                                        <div style="width: 100%; height: 100%;">${paragraphsHtml}</div>
                                    </div>`;
                            } else {
                                elementsHtml += `<div style="${style} border-radius: 4px;"></div>`;
                            }
                        });
                    } else {
                        elementsHtml = `
                            <div style="text-align: center; color: rgba(255,255,255,0.3); padding: 50px; position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <i class="fa fa-file-powerpoint-o" style="font-size: 70px; margin-bottom: 15px; opacity:0.6;"></i>
                                <h3>Slide is Empty</h3>
                            </div>`;
                    }
                    
                    const slideBg = currentSlide.background || '#0f172a';
                    viewerHtml = `
                        <div class="pptx-slide-canvas animate-fade-in" style="width: 100%; aspect-ratio: 16/9; position: relative; background: ${slideBg}; overflow: hidden; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255, 255, 255, 0.08);">
                            <div style="position: absolute; inset: 0; pointer-events: none;">
                                ${elementsHtml}
                            </div>
                            
                            <div style="position: absolute; bottom: 15px; right: 15px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 4px 14px; display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; color: #cbd5e1; z-index: 100;">
                                <span style="width:6px;height:6px;background:#00aeef;border-radius:50%;display:inline-block;animation:pulse 1s infinite alternate;"></span>
                                <span>Slide ${page} of ${totalSlides}</span>
                            </div>
                        </div>`;
                } else {
                    viewerHtml = `
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:40px;">
                            <i class="fa fa-spinner fa-spin" style="font-size:64px;color:var(--primary);margin-bottom:20px;"></i>
                            <h3 style="font-size:24px;font-weight:700;color:#fff;">Preparing Presentation...</h3>
                            <p style="color:var(--text-muted);font-size:16px;margin-top:10px;">Parsing slides content for premium 100% layout rendering.</p>
                        </div>`;
                }

            } else if (['doc','docx','xls','xlsx'].includes(ext)) {
                // Office files can't be rendered inline on localhost — show a styled download card
                modeBadge = `📊 ${ext.toUpperCase()} Presentation`;
                const iconMap = { doc:'fa-file-word-o', docx:'fa-file-word-o', xls:'fa-file-excel-o', xlsx:'fa-file-excel-o' };
                const icon = iconMap[ext] || 'fa-file-o';
                viewerHtml = `
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:40px;">
                        <i class="fa ${icon}" style="font-size:90px;color:var(--primary);margin-bottom:28px;opacity:0.85;"></i>
                        <h2 style="font-size:26px;font-weight:800;color:#fff;margin-bottom:10px;">${escapeHtml(file.title || file.fileName || '')}</h2>
                        <p style="font-size:16px;color:var(--text-muted);max-width:540px;margin-bottom:30px;">
                            This ${ext.toUpperCase()} file is projected. Open it in its native application on this device for the best presentation experience.
                        </p>
                        <a href="${fileUrl}" download
                           style="background:var(--primary);color:#fff;text-decoration:none;padding:14px 36px;border-radius:50px;font-weight:700;font-size:16px;display:inline-block;box-shadow:0 8px 20px rgba(0,174,239,0.3);">
                            <i class="fa fa-download"></i> Open / Download ${ext.toUpperCase()}
                        </a>
                    </div>`;

            } else {
                modeBadge = `📁 ${ext.toUpperCase()} File`;
                viewerHtml = `
                    <div style="text-align:center;padding:50px;color:var(--text-muted);">
                        <i class="fa fa-file-o" style="font-size:64px;color:var(--primary);margin-bottom:20px;"></i>
                        <h3>${escapeHtml(file.title || '')}</h3>
                        <p style="margin-top:10px;">This file type (${ext.toUpperCase()}) cannot be previewed inline.</p>
                        <a href="${fileUrl}" download style="background:var(--primary);color:#fff;text-decoration:none;padding:10px 24px;border-radius:6px;display:inline-block;margin-top:15px;" >Download File</a>
                    </div>`;
            }

            $('#smartboardViewport').html(`
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h3 style="font-size:22px;font-weight:700;color:#fff;">${escapeHtml(file.title || '')}</h3>
                    <span style="font-size:13px;background:rgba(255,255,255,0.06);padding:5px 14px;border-radius:20px;border:1px solid rgba(255,255,255,0.1);color:var(--text-muted);font-weight:600;">
                        ${modeBadge}
                    </span>
                </div>
                <div class="presentation-viewport">
                    ${viewerHtml}
                </div>
            `);
        }

        // Render Kahoot Lobby screen
        function renderGameLobby(pin, players) {
            let playerChips = '';
            if (players && players.length > 0) {
                players.forEach(player => {
                    playerChips += getFancyPlayerMarkup(player.nickname || player.studentName, player.studentId, true);
                });
            } else {
                playerChips = `
                    <div style="grid-column: 1 / -1; color: var(--text-muted); font-size: 16px; margin-top: 20px;">
                        <i class="fa fa-hourglass-start" style="animation: pulse 1s infinite alternate; color: var(--accent-yellow); margin-right: 8px;"></i>
                        Waiting for players to join...
                    </div>
                `;
            }
            
            let lobbyHtml = `
                <div class="game-lobby">
                    <h2>Join the Classroom Game!</h2>
                    <p style="font-size: 18px; color: var(--text-muted);">Open your student portal and choose "Join Live Game" on your sidebar</p>
                    
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 25px 40px; border-radius: 20px; display: inline-block; margin-top: 30px;">
                        <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: var(--primary); font-weight: 700;">GAME PIN</div>
                        <div class="game-pin-large">${pin}</div>
                    </div>
                    
                    <div style="width: 100%; max-width: 900px; margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 30px;">
                        <div class="player-grid-title">
                            Joined Players (<span id="lobbyPlayerCount" style="color: var(--accent-green); font-weight: 700;">${players ? players.length : 0}</span>)
                        </div>
                        <div class="player-grid">
                            ${playerChips}
                        </div>
                    </div>
                </div>
            `;
            $('#smartboardViewport').html(lobbyHtml);
        }

        // Render Game Question
        let questionTimerInterval = null;
        
        function renderGameQuestion(game) {
            if (questionTimerInterval) clearInterval(questionTimerInterval);
            
            const quest = game.question;
            const timerLimit = game.questionTimer;
            
            // Calculate seconds remaining
            let secondsLeft = timerLimit;
            if (game.timeElapsed !== undefined) {
                secondsLeft = Math.max(0, timerLimit - game.timeElapsed);
            } else if (game.timerStartedAt) {
                // Fallback for older server state
                const start = new Date(game.timerStartedAt.replace(/-/g, '/')).getTime();
                const now = new Date().getTime();
                const diff = Math.floor((now - start) / 1000);
                secondsLeft = Math.max(0, timerLimit - diff);
            }
            
            let optionsHtml = '';
            const markers = ['A', 'B', 'C', 'D'];
            const colors = ['red', 'blue', 'yellow', 'green'];
            
            quest.options.forEach((opt, idx) => {
                optionsHtml += `
                    <div class="option-card ${colors[idx]}">
                        <div class="option-marker">${markers[idx]}</div>
                        <div class="option-text">${escapeHtml(opt)}</div>
                    </div>
                `;
            });
            
            let screenHtml = `
                <div class="question-screen">
                    <div class="timer-container" id="questionTimerVal">${secondsLeft}</div>
                    
                    <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: var(--primary); font-weight: 700; margin-bottom: 10px;">
                        Question ${game.currentQuestionIndex + 1} of ${game.totalQuestions}
                    </div>
                    
                    <div class="question-text">
                        ${quest.questionText}
                    </div>
                    
                    <div class="options-grid">
                        ${optionsHtml}
                    </div>
                </div>
            `;
            $('#smartboardViewport').html(screenHtml);
            
            // Local count down ticker
            questionTimerInterval = setInterval(function() {
                if (secondsLeft > 0) {
                    secondsLeft--;
                    $('#questionTimerVal').text(secondsLeft);
                    if (secondsLeft <= 5) {
                        $('#questionTimerVal').css({
                            'border-color': 'var(--accent-red)',
                            'color': 'var(--accent-red)',
                            'box-shadow': '0 0 25px rgba(239, 68, 68, 0.4)'
                        });
                    }
                } else {
                    clearInterval(questionTimerInterval);
                }
            }, 1000);
        }

        // Render Results statistics screen
        function renderQuestionAnswers(game) {
            if (questionTimerInterval) clearInterval(questionTimerInterval);
            
            const quest = game.question;
            const stats = game.responseStats || { A: 0, B: 0, C: 0, D: 0 };
            const correctOptionIndex = game.correctOptionIndex; // index of correct option (0-3)
            
            const markers = ['A', 'B', 'C', 'D'];
            const colors = ['red', 'blue', 'yellow', 'green'];
            
            // Find max count to scale the bars
            const maxVal = Math.max(1, stats.A, stats.B, stats.C, stats.D);
            
            let chartHtml = '';
            markers.forEach((marker, idx) => {
                const count = stats[marker] || 0;
                const pctHeight = Math.max(8, Math.round((count / maxVal) * 230)); // max 230px
                
                chartHtml += `
                    <div class="chart-bar-wrapper">
                        <div class="chart-bar ${colors[idx]}" style="height: ${pctHeight}px;">
                            ${count}
                        </div>
                        <div class="chart-label">Option ${marker}</div>
                    </div>
                `;
            });
            
            let answersGridHtml = '';
            quest.options.forEach((opt, idx) => {
                const isCorrect = (idx === correctOptionIndex);
                const borderStyle = isCorrect ? 'border: 4px solid var(--accent-green); box-shadow: 0 0 20px rgba(34, 197, 94, 0.35);' : 'opacity: 0.4;';
                const checkIcon = isCorrect ? '<i class="fa fa-check-circle" style="color: var(--accent-green); font-size: 28px; margin-left: auto;"></i>' : '';
                
                answersGridHtml += `
                    <div class="option-card ${colors[idx]}" style="${borderStyle}">
                        <div class="option-marker">${markers[idx]}</div>
                        <div class="option-text">${escapeHtml(opt)}</div>
                        ${checkIcon}
                    </div>
                `;
            });
            
            let screenHtml = `
                <div class="question-screen" style="padding-top: 30px;">
                    <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: var(--accent-green); font-weight: 700; margin-bottom: 5px;">
                        <i class="fa fa-clock-o"></i> Time's Up! Showing Responses
                    </div>
                    
                    <div class="question-text" style="font-size: 26px; margin-bottom: 20px; line-height: 1.3;">
                        ${quest.questionText}
                    </div>
                    
                    <div class="results-chart-container">
                        ${chartHtml}
                    </div>
                    
                    <div class="options-grid" style="margin-top: 30px;">
                        ${answersGridHtml}
                    </div>
                </div>
            `;
            $('#smartboardViewport').html(screenHtml);
        }

        // Render Leaderboard Podium screen
        function renderGameLeaderboard(leaderboard) {
            if (!leaderboard || leaderboard.length === 0) return;
            
            // Sort leaderboard
            const sorted = [...leaderboard].sort((a,b) => b.score - a.score);
            
            // Extract top 3 players for podiums
            const gold = sorted[0] || { nickname: 'Empty', score: 0 };
            const silver = sorted[1] || null;
            const bronze = sorted[2] || null;
            
            let podiumHtml = '';
            
            // 2nd Place (Silver)
            if (silver) {
                podiumHtml += `
                    <div class="podium-step">
                        <div class="podium-player-name" style="margin-bottom: 8px;">${getFancyPlayerMarkup(silver.nickname || silver.studentName, silver.studentId, false)}</div>
                        <div class="podium-player-score">${silver.score} pts</div>
                        <div class="podium-block silver">2</div>
                    </div>
                `;
            }
            
            // 1st Place (Gold)
            podiumHtml += `
                <div class="podium-step" style="transform: translateY(-20px);">
                    <i class="fa fa-trophy" style="font-size: 40px; color: #ffd700; margin-bottom: 10px; animation: pulse 1s infinite alternate;"></i>
                    <div class="podium-player-name" style="font-size: 22px; color: #ffd700; margin-bottom: 8px;">${getFancyPlayerMarkup(gold.nickname || gold.studentName, gold.studentId, false)}</div>
                    <div class="podium-player-score" style="font-size: 16px;">${gold.score} pts</div>
                    <div class="podium-block gold">1</div>
                </div>
            `;
            
            // 3rd Place (Bronze)
            if (bronze) {
                podiumHtml += `
                    <div class="podium-step">
                        <div class="podium-player-name" style="margin-bottom: 8px;">${getFancyPlayerMarkup(bronze.nickname || bronze.studentName, bronze.studentId, false)}</div>
                        <div class="podium-player-score">${bronze.score} pts</div>
                        <div class="podium-block bronze">3</div>
                    </div>
                `;
            }
            
            // Generate rows for remaining players (rank 4+)
            let rowsHtml = '';
            if (sorted.length > 3) {
                for (let i = 3; i < Math.min(10, sorted.length); i++) {
                    const player = sorted[i];
                    rowsHtml += `
                        <div class="leaderboard-row">
                            <span class="leaderboard-rank">${i+1}</span>
                            <span class="leaderboard-name">${getFancyPlayerMarkup(player.nickname || player.studentName, player.studentId, false)}</span>
                            <span class="leaderboard-score">${player.score} pts</span>
                        </div>
                    `;
                }
            }
            
            let listHtml = '';
            if (rowsHtml) {
                listHtml = `
                    <div class="leaderboard-list">
                        ${rowsHtml}
                    </div>
                `;
            }
            
            let screenHtml = `
                <div class="leaderboard-container">
                    <h2 class="leaderboard-title"><i class="fa fa-star"></i> Leaderboard Standings</h2>
                    
                    <div class="podium-wrapper">
                        ${podiumHtml}
                    </div>
                    
                    ${listHtml}
                </div>
            `;
            $('#smartboardViewport').html(screenHtml);
        }

        // Render Game Finished screen
        function renderGameFinished(leaderboard) {
            if (questionTimerInterval) clearInterval(questionTimerInterval);
            
            // Sort leaderboard
            const sorted = [...leaderboard].sort((a,b) => b.score - a.score);
            const winner = sorted[0] || { nickname: 'No one', score: 0 };
            
            let screenHtml = `
                <div class="leaderboard-container" style="padding-top: 60px; text-align: center;">
                    <i class="fa fa-trophy" style="font-size: 96px; color: #ffd700; margin-bottom: 20px; animation: pulse 1s infinite alternate;"></i>
                    <h1 style="font-size: 52px; font-weight: 800; color: #fff; margin-bottom: 10px;">Game Completed!</h1>
                    <p style="font-size: 20px; color: var(--text-muted); margin-bottom: 30px;">Congratulations to the champion</p>
                    
                    <div style="background: rgba(255,255,255,0.03); border: 2px solid var(--accent-yellow); padding: 30px 60px; border-radius: 24px; display: inline-block; box-shadow: 0 15px 40px rgba(234,179,8,0.15); margin-bottom: 40px;">
                        <h2 style="font-size: 36px; color: var(--accent-yellow); font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: 10px;">
                            ${getFancyPlayerMarkup(winner.nickname || winner.studentName, winner.studentId, false)}
                        </h2>
                        <p style="font-size: 18px; color: #fff; font-weight: 600; margin-top: 8px;">Total Score: ${winner.score} points</p>
                    </div>
                    
                    <div>
                        <button onclick="initSmartboard()" class="btn" style="background: var(--primary); color:#fff; border:none; padding:12px 30px; font-size:16px; font-weight:600; border-radius:30px; cursor:pointer;">
                            <i class="fa fa-refresh"></i> Host Another Game
                        </button>
                    </div>
                </div>
            `;
            $('#smartboardViewport').html(screenHtml);
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
                    <div class="player-chip fancy-player-badge" style="background: ${gradient}; color: #ffffff; border: 1px solid rgba(255,255,255,0.25); box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-shadow: 0 1px 2px rgba(0,0,0,0.3); font-weight: 700;">
                        <span style="font-size: 18px; margin-right: 4px;">${emoji}</span>
                        <span>${escapeHtml(name)}</span>
                    </div>
                `;
            } else {
                return `
                    <span class="fancy-player-inline" style="background: ${gradient}; color: #ffffff; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-family: 'Outfit', sans-serif; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.2); text-shadow: 0 1px 1px rgba(0,0,0,0.2);">
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
    </script>

</body>
</html>
