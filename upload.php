<?php

require_once('config.php');

$types = [
    'image/png' => 'png',
    'image/jpeg' => 'jpeg',
    'image/gif' => 'gif',
    'video/mp4' => 'mp4',
    'text/plain' => 'txt',
    'audio/mpeg' => 'mp3',
    'video/x-matroska' => 'mkv'
];

function generateFileName($shortName, $mimeType)
{
    global $types;
    
    if (!isset($types[$mimeType])) {
        return false; // Unsupported file type
    }

    $randomString = bin2hex(random_bytes(4));
    return $shortName . $randomString . '.' . $types[$mimeType];
}

function getRequestHeaders()
{
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $header = str_replace('_', '-', ucwords(strtolower(substr($key, 5))));
            $headers[$header] = $value;
        }
    }
    return $headers;
}

function respondWithError($message)
{
    echo json_encode(['title' => 'Error', 'responseurl' => $message]);
    exit;
}

$headers = getRequestHeaders();
$headkey = $_POST['key'] ?? $headers['Key'] ?? null;
if (!$headkey || $headkey !== $key) {
    respondWithError("Incorrect API key.");
}

if (!isset($_FILES['uploadme']) || $_FILES['uploadme']['error'] !== UPLOAD_ERR_OK) {
    respondWithError("File upload failed.");
}

$file = $_FILES['uploadme'];
if ($file['size'] > $maxsize) {
    respondWithError("File too large.");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actualMimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!array_key_exists($actualMimeType, $types)) {
    respondWithError("Invalid file type.");
}

$fileName = generateFileName($shortName, $actualMimeType);
if (!$fileName) {
    respondWithError("Error generating file name.");
}

$filePath = realpath($uploadPath) . DIRECTORY_SEPARATOR . basename($fileName);
if (strpos($filePath, realpath($uploadPath)) !== 0) {
    respondWithError("Invalid file path detected.");
}

$fileRelativePath = $uploadPath.basename($fileName);
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    respondWithError("File save failed.");
}

chmod($filePath, 0644);

$fullUrl = $url . $fileRelativePath;
echo json_encode(['responseurl' => $fullUrl]);

?>
