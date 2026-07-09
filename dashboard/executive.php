<?php
session_start();

$secret = require __DIR__ . '/../auth/secret.php';
$SignedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;

$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal - Executive Dashboard</title>
    <link rel="stylesheet" href="../styles.css"/>
</head>
<body>
<div id="top-nav-bar" class="classic">
    <div id="pagetop" class="sis-bar notranslate primary-white">
        <a id="sis-logo" href="../index.php" class="sis-bar-item sis-button sis-left" title="Home">
            <i class="fa" aria-hidden="true">1</i>
        </a>
        <nav class="tnb-desktop-nav sis-bar-item">
            <a id="inactive" href="../index.php" class="sis-bar-item sis-padding-16 sis-button ">Home</a>
            <a id="inactive" href="../feed" class="sis-bar-item  sis-padding-16 sis-button">Feed</a>
            <a id="inactive" href="../calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
            <a id="active" href="executive.php" class="sis-bar-item sis-padding-16 sis-button">Executive Dashboard</a>
        </nav>
        <div class="tnb-right-section">
            <a href="../auth/signout.php">
                <div id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button"
                     title="Sign in to your account">
                    <span class="button-text">Sign Out</span>
                </div>
            </a>
            <a href="../assets/site_images/fair_map.png" class="tnb-right-side-btn sis-bar-item sis-button sis-right" title="Club Fair Map" aria-label="Club Fair Map">Fair Map</a>
        </div>
    </div>
</div>
<div class="topnavbackground"></div>
<div class="topnavcontainer">
    Placeholder for announcements
</div>
<div class="background-image"></div>
</body>
</html>