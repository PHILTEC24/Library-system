<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/book_media.php';

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mediaType = $_GET['type'] ?? '';
if (!$bookId || !in_array($mediaType, ['cover', 'ebook'], true)) {
    http_response_code(404);
    exit('File not found.');
}

$column = $mediaType === 'cover' ? 'cover_image' : 'ebook_file';
$statement = $pdo->prepare("SELECT $column FROM books WHERE book_id = ?");
$statement->execute([$bookId]);
$storedName = $statement->fetchColumn();
$path = book_media_path($storedName);
if ($path === null || !is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('File not found.');
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$contentTypes = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'pdf' => 'application/pdf',
];
$isCover = $mediaType === 'cover';
$download = isset($_GET['download']) && $_GET['download'] === '1';

header('Content-Type: ' . $contentTypes[$extension]);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . (($isCover || !$download) ? 'inline' : 'attachment') . '; filename="book-' . $bookId . '.' . $extension . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($path);
