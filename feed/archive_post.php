<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

function respond(
    int $status,
    bool $success,
    string $message,
    array $extra = []
): never {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Only POST requests are allowed.');
}

$user = $_SESSION['user'] ?? null;

if (!$user || empty($user['Email'])) {
    respond(401, false, 'You must be signed in.');
}

$postID = filter_input(INPUT_POST, 'PostID', FILTER_VALIDATE_INT);

if ($postID === false || $postID === null || $postID < 1) {
    respond(422, false, 'A valid post ID is required.');
}

$email = trim((string) $user['Email']);
$secret = require __DIR__ . '/../auth/secret.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

    $postStatement = $conn->prepare(
        'SELECT
            feed.PostID,
            feed.Visible,
            clubs.Name AS ClubName,
            clubs.Executives,
            clubs.Advisors
        FROM feed
        INNER JOIN clubs
            ON clubs.ClubID = feed.ClubID
        WHERE feed.PostID = ?'
    );

    $postStatement->bind_param('i', $postID);
    $postStatement->execute();

    $post = $postStatement->get_result()->fetch_assoc();
    $postStatement->close();

    if (!$post) {
        respond(404, false, 'The requested post does not exist.');
    }

    $executives = array_filter(
        array_map(
            'trim',
            explode(',', (string) ($post['Executives'] ?? ''))
        )
    );

    $advisors = array_filter(
        array_map(
            'trim',
            explode(',', (string) ($post['Advisors'] ?? ''))
        )
    );

    $isExecutive = in_array($email, $executives, true);
    $isAdvisor = in_array($email, $advisors, true);

    if (!$isAdmin && !$isExecutive && !$isAdvisor) {
        respond(
            403,
            false,
            'You do not have permission to archive this post.'
        );
    }

    if ((int) $post['Visible'] === 0) {
        respond(
            200,
            true,
            'This post is already archived.',
            [
                'postID' => $postID,
                'clubName' => $post['ClubName'],
            ]
        );
    }

    $archiveStatement = $conn->prepare(
        'UPDATE feed
         SET Visible = 0
         WHERE PostID = ? AND Visible = 1'
    );

    $archiveStatement->bind_param('i', $postID);
    $archiveStatement->execute();
    $archiveStatement->close();

    respond(
        200,
        true,
        'Post archived successfully.',
        [
            'postID' => $postID,
            'clubName' => $post['ClubName'],
        ]
    );
} catch (Throwable $error) {
    error_log('Feed post archive failed: ' . $error->getMessage());

    respond(500, false, 'The post could not be archived.');
}