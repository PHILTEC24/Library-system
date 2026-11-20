<?php
$cookieJar = tempnam(sys_get_temp_dir(), 'lms_cookie_');

function http_request($url, $method = 'GET', $postFields = null, $followLocation = true) {
    global $cookieJar;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followLocation);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postFields) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }
    }

    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $body,
        'redirect' => $redirectUrl,
        'error' => $error
    ];
}

echo "=== STARTING END-TO-END HTTP TESTS ===\n\n";

// 1. Root redirect check
echo "1. Checking Root URL (/) redirect:\n";
$res = http_request("http://127.0.0.1:8000/", 'GET', null, false);
echo "   Status code: {$res['code']}\n";
echo "   Redirect location: {$res['redirect']}\n";
if ($res['code'] === 302) {
    echo "   [PASS] Successfully redirects to {$res['redirect']}\n\n";
} else {
    echo "   [FAIL] Expected 302, got {$res['code']}\n\n";
    exit(1);
}

// 2. Login Page check
echo "2. Loading Login Page (/library-system/auth/login.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/auth/login.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && str_contains($res['body'], 'Library Management')) {
    echo "   [PASS] Login page rendered successfully\n\n";
} else {
    echo "   [FAIL] Login page failed to render properly\n\n";
    exit(1);
}

// 3. Perform login against Neon DB users
echo "3. Submitting credentials (admin / password) to login:\n";
$res = http_request(
    "http://127.0.0.1:8000/library-system/auth/login.php",
    'POST',
    ['username' => 'admin', 'password' => 'password'],
    false
);
echo "   Login status code: {$res['code']}\n";
echo "   Redirect target: {$res['redirect']}\n";
if ($res['code'] === 302 && str_contains($res['redirect'], 'dashboard')) {
    echo "   [PASS] Authentication against Neon PostgreSQL succeeded!\n\n";
} else {
    echo "   [FAIL] Authentication failed\n\n";
    exit(1);
}

// 4. Access Dashboard with active session
echo "4. Loading Dashboard (/library-system/dashboard/index.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/dashboard/index.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && str_contains($res['body'], 'copies on the shelf') && str_contains($res['body'], 'Overdue')) {
    echo "   [PASS] Dashboard loaded with live stats from Neon!\n\n";
} else {
    echo "   [FAIL] Dashboard failed to load correctly\n";
    echo "   Response preview: " . substr(strip_tags($res['body']), 0, 300) . "\n\n";
    exit(1);
}

// 5. Access Books page
echo "5. Loading Books Catalogue (/library-system/books/index.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/books/index.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && str_contains($res['body'], 'Nineteen Eighty-Four') && str_contains($res['body'], 'George Orwell')) {
    echo "   [PASS] Books catalogue loaded with book & author records from Neon!\n\n";
} else {
    echo "   [FAIL] Books page failed to load correctly\n\n";
    exit(1);
}

// 6. Access Members page
echo "6. Loading Members Page (/library-system/members/index.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/members/index.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && str_contains($res['body'], 'Alice Johnson') && str_contains($res['body'], 'MEM001')) {
    echo "   [PASS] Members list loaded with member records from Neon!\n\n";
} else {
    echo "   [FAIL] Members page failed to load correctly\n\n";
    exit(1);
}

// 7. Access Loans page
echo "7. Loading Loans Page (/library-system/loans/index.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/loans/index.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && (str_contains($res['body'], 'active') || str_contains($res['body'], 'overdue') || str_contains($res['body'], 'returned'))) {
    echo "   [PASS] Loans list loaded with loan records from Neon!\n\n";
} else {
    echo "   [FAIL] Loans page failed to load correctly\n\n";
    exit(1);
}

// 8. Access Categories page
echo "8. Loading Categories Page (/library-system/categories/index.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/categories/index.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && str_contains($res['body'], 'Fiction') && str_contains($res['body'], 'Technology')) {
    echo "   [PASS] Categories page loaded with category data from Neon!\n\n";
} else {
    echo "   [FAIL] Categories page failed to load correctly\n\n";
    exit(1);
}

// 9. Access Authors page
echo "9. Loading Authors Page (/library-system/authors/index.php):\n";
$res = http_request("http://127.0.0.1:8000/library-system/authors/index.php");
echo "   Status code: {$res['code']}\n";
if ($res['code'] === 200 && str_contains($res['body'], 'George Orwell') && str_contains($res['body'], 'Stephen Hawking')) {
    echo "   [PASS] Authors page loaded with author data from Neon!\n\n";
} else {
    echo "   [FAIL] Authors page failed to load correctly\n\n";
    exit(1);
}

echo "=== ALL 9 END-TO-END TESTS PASSED SUCCESSFULLY! ===\n";

if (file_exists($cookieJar)) {
    unlink($cookieJar);
}
