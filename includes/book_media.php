<?php

function book_media_directory() {
    return __DIR__ . '/../uploads/books';
}

function store_book_media_upload($file, $kind) {
    if ($file === null) {
        return null;
    }
    if (!is_array($file) || !isset($file['error'])) {
        throw new RuntimeException('The uploaded file could not be read.');
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'The uploaded file exceeds the server upload limit.'
            : 'The file upload failed. Please try again.';
        throw new RuntimeException($message);
    }

    $maxBytes = $kind === 'cover' ? 5 * 1024 * 1024 : 25 * 1024 * 1024;
    if (!isset($file['size']) || (int)$file['size'] <= 0 || (int)$file['size'] > $maxBytes) {
        $limit = $kind === 'cover' ? '5 MB' : '25 MB';
        throw new RuntimeException('The file must be greater than 0 bytes and no larger than ' . $limit . '.');
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The uploaded file is invalid.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($temporaryPath);
    if ($kind === 'cover') {
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowedTypes[$mimeType])) {
            throw new RuntimeException('Cover images must be JPEG, PNG or WebP files.');
        }
        $imageInfo = getimagesize($temporaryPath);
        if (
            $imageInfo === false
            || $imageInfo['mime'] !== $mimeType
            || $imageInfo[0] > 10000
            || $imageInfo[1] > 10000
            || $imageInfo[0] * $imageInfo[1] > 40000000
        ) {
            throw new RuntimeException('The uploaded cover image is invalid.');
        }
    } elseif ($kind === 'ebook' && $mimeType === 'application/pdf') {
        $allowedTypes = ['application/pdf' => 'pdf'];
    } else {
        throw new RuntimeException('E-books must be PDF files.');
    }

    $directory = book_media_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('The book media directory could not be created.');
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
    if (!move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $storedName)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }

    return $storedName;
}

function book_media_path($storedName) {
    if (!is_string($storedName) || !preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp|pdf)\z/', $storedName)) {
        return null;
    }

    return book_media_directory() . DIRECTORY_SEPARATOR . $storedName;
}

function delete_book_media_upload($storedName) {
    $path = book_media_path($storedName);
    if ($path !== null && is_file($path) && !unlink($path)) {
        error_log('Unable to remove book media file: ' . basename($path));
    }
}
