<?php
/**
 * export_pptx_slides.php
 * Uses Microsoft PowerPoint (via AppleScript) to export PPTX slides as PNG images.
 * Returns JSON with slide image paths.
 */
session_start();
session_write_close();
require_once "../db_connection/dlhs_db_connection.php";
header('Content-Type: application/json');

$pptxFile = isset($_GET['file']) ? $_GET['file'] : '';
if (!$pptxFile) {
    echo json_encode(['success' => false, 'message' => 'No file specified.']);
    exit;
}

// Sanitize: only allow basename, no path traversal
$pptxFile = basename($pptxFile);
$pptxPath = realpath(__DIR__ . '/../uploads/resources/' . $pptxFile);
$uploadsDir = realpath(__DIR__ . '/../uploads/resources');

if (!$pptxPath || strpos($pptxPath, $uploadsDir) !== 0 || !file_exists($pptxPath)) {
    echo json_encode(['success' => false, 'message' => 'File not found.']);
    exit;
}

$ext = strtolower(pathinfo($pptxPath, PATHINFO_EXTENSION));
if (!in_array($ext, ['pptx', 'ppt'])) {
    echo json_encode(['success' => false, 'message' => 'Not a PPTX file.']);
    exit;
}

// Output directory for slide images
$slideDir = __DIR__ . '/../uploads/resources/pptx_slides/' . $pptxFile . '/';
$slideDirWeb = '../uploads/resources/pptx_slides/' . rawurlencode($pptxFile) . '/';

// If slides already exported, return them
if (is_dir($slideDir)) {
    $images = glob($slideDir . 'slide_*.png');
    if ($images && count($images) > 0) {
        natsort($images);
        $slides = [];
        foreach ($images as $img) {
            $slides[] = $slideDirWeb . basename($img);
        }
        echo json_encode(['success' => true, 'slides' => array_values($slides), 'total' => count($slides)]);
        exit;
    }
}

// Create output directory
if (!is_dir($slideDir)) {
    mkdir($slideDir, 0755, true);
}

// AppleScript to export PPTX slides as PNG via Microsoft PowerPoint
$appleScript = <<<APPLESCRIPT
tell application "Microsoft PowerPoint"
    set pptxFilePath to POSIX file "{$pptxPath}"
    open pptxFilePath
    delay 2
    set thePresentation to active presentation
    set slideCount to count of slides of thePresentation
    repeat with i from 1 to slideCount
        set theSlide to slide i of thePresentation
        set exportPath to "{$slideDir}slide_" & i & ".png"
        export theSlide to POSIX file exportPath as save as PNG
    end repeat
    close thePresentation saving no
end tell
APPLESCRIPT;

$appleScriptEscaped = str_replace('"', '\\"', $appleScript);
$cmd = "osascript -e \"$appleScriptEscaped\" 2>&1";
$output = shell_exec($cmd);

// Check results
$images = glob($slideDir . 'slide_*.png');
if ($images && count($images) > 0) {
    natsort($images);
    $slides = [];
    foreach ($images as $img) {
        $slides[] = $slideDirWeb . basename($img);
    }
    echo json_encode(['success' => true, 'slides' => array_values($slides), 'total' => count($slides)]);
} else {
    // Fallback: try python-pptx approach
    $jsonPath = $pptxPath . '.json';
    if (!file_exists($jsonPath)) {
        $escaped = escapeshellarg($pptxPath);
        shell_exec("python3 " . __DIR__ . "/../scripts/parse_pptx.py $escaped 2>&1");
    }
    if (file_exists($jsonPath)) {
        $data = json_decode(file_get_contents($jsonPath), true);
        echo json_encode([
            'success' => true, 
            'slides' => null,
            'pptxData' => $data,
            'total' => count($data['slides'] ?? []),
            'fallback' => true,
            'exportOutput' => $output
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Export failed.', 'output' => $output]);
    }
}
