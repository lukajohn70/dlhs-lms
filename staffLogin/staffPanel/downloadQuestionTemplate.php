<?php
error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow both staff and admin
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

$hasFiveOptions = isset($_GET['options']) && $_GET['options'] == '5';
$filename = $hasFiveOptions ? 'question_template_5_options.csv' : 'question_template.csv';

// Clean any previous output buffers to prevent PHP notices or whitespace in file
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if ($hasFiveOptions) {
    $headers = array(
        'questionSerialNo',
        'question',
        'optionA',
        'optionB',
        'optionC',
        'optionD',
        'optionE',
        'correctOption',
        'markForQuestion'
    );
    $sample1 = array(
        '1',
        'Quel mot signifie "school" en francais?',
        'Ecole',
        'Maison',
        'Livre',
        'Table',
        'Voiture',
        'A',
        '1'
    );
    $sample2 = array(
        '2',
        'What is the capital city of Nigeria?',
        'Lagos',
        'Abuja',
        'Kano',
        'Ibadan',
        'Port Harcourt',
        'B',
        '1'
    );
} else {
    $headers = array(
        'questionSerialNo',
        'question',
        'optionA',
        'optionB',
        'optionC',
        'optionD',
        'correctOption',
        'markForQuestion'
    );
    $sample1 = array(
        '1',
        'Quel mot signifie "school" en francais?',
        'Ecole',
        'Maison',
        'Livre',
        'Table',
        'A',
        '1'
    );
    $sample2 = array(
        '2',
        'What is the capital city of Nigeria?',
        'Lagos',
        'Abuja',
        'Kano',
        'Port Harcourt',
        'B',
        '1'
    );
}

// Explicitly provide delimiter, enclosure, and escape character for PHP 8.4+ compatibility
fputcsv($output, $headers, ',', '"', "\\");
fputcsv($output, $sample1, ',', '"', "\\");
fputcsv($output, $sample2, ',', '"', "\\");

fclose($output);
exit;
