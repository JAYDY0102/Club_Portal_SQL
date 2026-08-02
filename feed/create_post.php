<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

function respond(int $status, bool $success, string $message, array $extra = []): never
{
    http_response_code($status);

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra),
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

function textLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Only POST requests are allowed.');
}

$user = $_SESSION['user'] ?? null;

if (!$user || empty($user['Email'])) {
    respond(401, false, 'You must be signed in.');
}

$email = trim((string) $user['Email']);
$clubID = filter_input(INPUT_POST, 'ClubID', FILTER_VALIDATE_INT);
$title = trim((string) ($_POST['Title'] ?? ''));
$description = trim((string) ($_POST['Description'] ?? ''));

if ($clubID === false || $clubID === null || $clubID < 1) {
    respond(422, false, 'Please select a valid club.');
}

if ($title === '') {
    respond(422, false, 'A post title is required.');
}

if (textLength($title) > 255) {
    respond(422, false, 'The title must be 255 characters or fewer.');
}

if ($description === '') {
    respond(422, false, 'A post description is required.');
}

if (textLength($description) > 4095) {
    respond(422, false, 'The description must be 4095 characters or fewer.');
}



$imageUploaded = isset($_FILES['Image'])
    && $_FILES['Image']['error'] !== UPLOAD_ERR_NO_FILE;

if ($imageUploaded) {
    $image = $_FILES['Image'];

    if ($image['error'] !== UPLOAD_ERR_OK) {
        respond(422, false, 'The image upload failed.');
    }

    $maximumImageSize = 5 * 1024 * 1024;

    if ($image['size'] > $maximumImageSize) {
        respond(422, false, 'The image must be 5 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($image['tmp_name']);

    if ($mimeType !== 'image/png') {
        respond(422, false, 'Only PNG images are allowed.');
    }

    $imageInformation = @getimagesize($image['tmp_name']);

    if (
        $imageInformation === false ||
        ($imageInformation['mime'] ?? '') !== 'image/png'
    ) {
        respond(422, false, 'The uploaded file is not a valid PNG image.');
    }
}

$secret = require __DIR__ . '/../auth/secret.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
$savedImagePath = null;

try {
    $conn = new mysqli(
        $secret['host'],
        $secret['username'],
        $secret['password'],
        $secret['dbname']
    );

    $conn->set_charset('utf8mb4');

    $userStatement = $conn->prepare(
        'SELECT AdminFlag FROM users WHERE Email = ?'
    );
    $userStatement->bind_param('s', $email);
    $userStatement->execute();

    $databaseUser = $userStatement->get_result()->fetch_assoc();
    $userStatement->close();

    if (!$databaseUser) {
        respond(403, false, 'Your user account could not be verified.');
    }

    $isAdmin = (int) $databaseUser['AdminFlag'] === 1;

    $clubStatement = $conn->prepare(
        'SELECT ClubID, Name, Executives, Advisors
        FROM clubs
        WHERE ClubID = ?'
    );
    $clubStatement->bind_param('i', $clubID);
    $clubStatement->execute();

    $club = $clubStatement->get_result()->fetch_assoc();
    $clubStatement->close();

    if (!$club) {
        respond(404, false, 'The selected club does not exist.');
    }

    $executives = array_filter(
        array_map(
            'trim',
            explode(',', (string) ($club['Executives'] ?? ''))
        )
    );

    $advisors = array_filter(
        array_map(
            'trim',
            explode(',', (string) ($club['Advisors'] ?? ''))
        )
    );

    $isExecutive = in_array($email, $executives, true);
    $isAdvisor = in_array($email, $advisors, true);

    if (!$isAdmin && !$isExecutive && !$isAdvisor) {
        respond(
            403,
            false,
            'You do not have permission to post for this club.'
        );
    }

    $conn->begin_transaction();

    $insertStatement = $conn->prepare(
        'INSERT INTO feed (
            ClubID,
            UploadTime,
            Title,
            Description,
            ImageID,
            Visible
        ) VALUES (?, NOW(), ?, ?, NULL, 1)'
    );

    $insertStatement->bind_param(
        'iss',
        $clubID,
        $title,
        $description
    );

    $insertStatement->execute();
    $postID = $conn->insert_id;
    $insertStatement->close();

    if ($imageUploaded) {
        $uploadDirectory = __DIR__ . '/../assets/feed';

        if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
            throw new RuntimeException('The feed image directory is not writable.');
        }

        $savedImagePath = $uploadDirectory . '/' . $postID . '.png';

        if (!move_uploaded_file($_FILES['Image']['tmp_name'], $savedImagePath)) {
            throw new RuntimeException('The image could not be saved.');
        }

        $imageStatement = $conn->prepare(
            'UPDATE feed SET ImageID = ? WHERE PostID = ?'
        );

        $imageStatement->bind_param('ii', $postID, $postID);
        $imageStatement->execute();
        $imageStatement->close();
    }

    $conn->commit();

    respond(
        201,
        true,
        'Post published successfully.',
        [
            'postID' => $postID,
            'clubName' => $club['Name'],
        ]
    );
} catch (Throwable $error) {
    if ($conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }

    if ($savedImagePath !== null && is_file($savedImagePath)) {
        unlink($savedImagePath);
    }

    error_log('Feed post creation failed: ' . $error->getMessage());

    respond(500, false, 'The post could not be published.');
}