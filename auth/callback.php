<?php
session_start();

$secret = require __DIR__ . '/secret.php';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

if (!$code) {
    http_response_code(400);
    exit('Missing authorization code.');
}

if (!in_array($state, ['student', 'staff'], true)) {
    http_response_code(400);
    exit('Invalid login state.');
}

$tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'code' => $code,
            'client_id' => $secret['google_client_id'],
            'client_secret' => $secret['google_client_secret'],
            'redirect_uri' => $secret['google_redirect_uri'],
            'grant_type' => 'authorization_code',
        ]),
    ]
]));

if ($tokenResponse === false) {
    http_response_code(500);
    exit('Failed to fetch Google token.');
}

$tokenData = json_decode($tokenResponse, true);
if (!isset($tokenData['access_token'])) {
    http_response_code(500);
    exit('Google token response invalid.');
}
$userResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer " . $tokenData['access_token'] . "\r\n"
    ]
]));

if ($userResponse === false) {
    http_response_code(500);
    exit('Failed to fetch Google user info.');
}

$userData = json_decode($userResponse, true);

$email = $userData['email'] ?? '';
$name = $userData['name'] ?? '';
$googleId = $userData['id'] ?? '';

if (!$email || !$name || !$googleId) {
    http_response_code(400);
    exit('Incomplete Google profile.');
}

$isStudent = str_ends_with($email, '@' . $secret['student_domain']);
$isStaff = str_ends_with($email, '@' . $secret['staff_domain']);

if ($state === 'student' && !$isStudent) {
    exit('Student accounts must use a stu.siskorea.org email.');
}

if ($state === 'staff' && !$isStaff) {
    exit('Staff accounts must use a siskorea.org email.');
}
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}
$stmt =  $conn->prepare("SELECT * FROM users WHERE Email = ?");
if (!$stmt) {
    http_response_code(500);
    exit('Database query failed.');
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    $insertStmt = $conn->prepare("INSERT INTO users (Email, Name, UserName) VALUES (?, ?, ?)");
    if (!$insertStmt) {
        http_response_code(500);
        exit('Database insert failed.');
    }

    $insertStmt->bind_param("sss", $email, $name, $name);
    if (!$insertStmt->execute()) {
        http_response_code(500);
        exit('Failed to create user account.');
    }
    $insertStmt->close();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();

$_SESSION['user'] = $user;

header('Location: ../index.php');
exit;