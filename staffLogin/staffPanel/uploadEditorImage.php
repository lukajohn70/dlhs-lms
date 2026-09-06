<?php

session_start();
header('X-Content-Type-Options: nosniff');

$uploadDirectory = '../../questUploadImages/questionImages/';
$publicPrefix = '/questUploadImages/questionImages/';
$allowedExtensions = array('jpg', 'jpeg', 'gif', 'png', 'webp');
$allowedMimeTypes = array(
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
);

if (!is_dir($uploadDirectory)) {
    @mkdir($uploadDirectory, 0777, true);
}

function respondToEditorUpload($uploaded, $url, $message)
{
    $functionNumber = isset($_GET['CKEditorFuncNum']) ? (int) $_GET['CKEditorFuncNum'] : 0;

    if ($functionNumber > 0) {
        $url = str_replace("'", "\\'", (string) $url);
        $message = str_replace("'", "\\'", (string) $message);
        echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction({$functionNumber}, '{$url}', '{$message}');</script>";
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(array(
        'uploaded' => $uploaded ? 1 : 0,
        'url' => $url,
        'message' => $message
    ));
    exit;
}

function buildUploadedImageName($extension)
{
    return 'editor_' . date('YmdHis') . '_' . mt_rand(1000, 999999) . '.' . strtolower($extension);
}

function saveImageBinary($binary, $extension, $uploadDirectory, $publicPrefix)
{
    $fileName = buildUploadedImageName($extension);
    $destination = $uploadDirectory . $fileName;

    if (@file_put_contents($destination, $binary) === false) {
        return array(false, '', 'Unable to save the pasted image.');
    }

    return array(true, $publicPrefix . $fileName, '');
}

if (isset($_FILES['upload']) && isset($_FILES['upload']['tmp_name']) && is_uploaded_file($_FILES['upload']['tmp_name'])) {
    $fileName = isset($_FILES['upload']['name']) ? $_FILES['upload']['name'] : 'image.png';
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeType = isset($_FILES['upload']['type']) ? strtolower((string) $_FILES['upload']['type']) : '';

    if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
        respondToEditorUpload(false, '', 'Only JPG, PNG, GIF, and WEBP images are allowed.');
    }

    $binary = @file_get_contents($_FILES['upload']['tmp_name']);
    if ($binary === false) {
        respondToEditorUpload(false, '', 'The uploaded image could not be read.');
    }

    list($saved, $url, $message) = saveImageBinary($binary, $extension, $uploadDirectory, $publicPrefix);
    respondToEditorUpload($saved, $url, $message);
}

if (!empty($_POST['imageData'])) {
    $imageData = trim((string) $_POST['imageData']);
    if (!preg_match('/^data:image\/([a-zA-Z0-9+]+);base64,/', $imageData, $matches)) {
        respondToEditorUpload(false, '', 'Invalid pasted image data.');
    }

    $extension = strtolower($matches[1]);
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }

    if (!in_array($extension, $allowedExtensions, true)) {
        respondToEditorUpload(false, '', 'Only JPG, PNG, GIF, and WEBP images are allowed.');
    }

    $base64 = substr($imageData, strpos($imageData, ',') + 1);
    $binary = base64_decode($base64, true);
    if ($binary === false) {
        respondToEditorUpload(false, '', 'The pasted image could not be decoded.');
    }

    list($saved, $url, $message) = saveImageBinary($binary, $extension, $uploadDirectory, $publicPrefix);
    respondToEditorUpload($saved, $url, $message);
}

respondToEditorUpload(false, '', 'No image was received.');

