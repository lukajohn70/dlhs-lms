<?php
session_start();
require_once 'userExpiredSession.php';

if (!isset($_SESSION['staffLoggedIn'])) {
    header('location:../index.php');
    exit;
}

$filename = 'question_template.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, array(
    'questionSerialNo',
    'question',
    'optionA',
    'optionB',
    'optionC',
    'optionD',
    'correctOption',
    'markForQuestion'
));

fputcsv($output, array(
    '1',
    'Quel mot signifie "school" en francais?',
    'Ecole',
    'Maison',
    'Livre',
    'Table',
    'A',
    '1'
));

fclose($output);
exit;
