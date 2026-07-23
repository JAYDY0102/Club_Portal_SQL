<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$secret = require __DIR__ . '/auth/secret.php';
$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$RequestType = $_POST['RequestType'] ?? '';

if ($RequestType == 'Banner') {
    if (!isset($_FILES['File']) || !isset($_POST['DirName'])) {
        http_response_code(400);
        echo "asuka-a";
        exit;
    }
    $File = $_FILES['File'];
    $DirName = $_POST['DirName'];
    $uploadDir = 'assets/banners/';

    if ($DirName === 'newentry') {
        $randomName = 'tmp-' . bin2hex(random_bytes(16));
        $fileName = $randomName . '.png';
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($File['tmp_name'], $uploadPath)) {
            echo "asuka;" . $randomName;
        } else {
            http_response_code(500);
            echo "shinji-01";
        }
    } else {
        $fileName = $DirName . '.png';
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($File['tmp_name'], $uploadPath)) {
            echo "rei;" . time();
        } else {
            http_response_code(500);
            echo "shinji-13;" . $uploadPath;
        }
    }
} else if ($RequestType == 'club-fetch') {
    $DirName = $_POST['DirName'] ?? '';
    if ($DirName === 'newentry') {
        echo "asuka";
    } else {
        $stmt = $conn->prepare("SELECT * FROM clubs WHERE DirName = ?");
        $stmt->bind_param("s", $DirName);
        if (!$stmt->execute()) {
            echo "shinji-01;Query failed";
            exit;
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            echo "shinji-13;Club not found";
            exit;
        }

        $response = $row['Name'] . ';' . $row['ClubType'] . ';' . $row['Summary'] . ';' . $row['About']
            . ';' . $row['MeetDay'] . ';' . $row['Location'] . ';' . $row['MemberCount']
            . ';' . $row['Advisors'] . ';' . $row['Executives'] . ';' . $row['Instagram']
            . ';' . $row['Youtube'] . ';' . $row['Website'] . ';' . $row['Social'];
        echo "rei;" . $response;
        $stmt->close();
    }
} else if ($RequestType == 'club-update') {
    $DirName = $_POST['DirName'] ?? '';
    $Name = $_POST['Name'] ?? '';
    $Type = $_POST['Type'] ?? '';
    $MemberCount = $_POST['MemberCount'] ?? 0;
    $MeetDay = $_POST['MeetDay'] ?? '';
    $Location = $_POST['Location'] ?? '';
    $Summary = $_POST['Summary'] ?? '';
    $About = $_POST['About'] ?? '';
    $Instagram = $_POST['Instagram'] ?? '';
    $Youtube = $_POST['Youtube'] ?? '';
    $Website = $_POST['Website'] ?? '';
    $Social = $_POST['Social'] ?? '';
    $Advisors = $_POST['Advisors'] ?? '';
    $Executives = $_POST['Executives'] ?? '';

    $AdvisorList = array_map('trim', explode(',', $Advisors));
    $ExecutiveList = array_map('trim', explode(',', $Executives));

    foreach ($AdvisorList as $advisor) {
        $sqlAdvisor = "SELECT Name FROM users WHERE Email = '$advisor'";
        $AdvisorResult = $conn->query($sqlAdvisor);
        if ($AdvisorResult && ($advisorRow = $AdvisorResult->fetch_assoc())) {
            $advisorstmt = $conn->prepare("UPDATE users SET Role='advisor' WHERE Email = ?");
            $advisorstmt->bind_param("s", $advisor);
            $advisorstmt->execute();
        }
    }

    foreach ($ExecutiveList as $executive) {
        $sqlExecutive = "SELECT Name FROM users WHERE Email = '$executive'";
        $ExecutiveResult = $conn->query($sqlExecutive);
        if ($ExecutiveResult && ($executiveRow = $ExecutiveResult->fetch_assoc())) {
            $executivestmt = $conn->prepare("UPDATE users SET Role='executive' WHERE Email = ?");
            $executivestmt->bind_param("s", $executive);
            $executivestmt->execute();
        }
    }

    $stmt = $conn->prepare("UPDATE clubs SET Name = ?, ClubType = ?, MemberCount = ?, MeetDay = ?, Location = ?, Summary = ?, About = ?, Instagram = ?, Youtube = ?, Website = ?, Social = ?, Advisors = ?, Executives = ? WHERE DirName = ?");
    $stmt->bind_param(
        "ssisssssssssss",
        $Name, $Type, $MemberCount, $MeetDay, $Location, $Summary, $About,
        $Instagram, $Youtube, $Website, $Social, $Advisors, $Executives, $DirName
    );
    if ($stmt->execute()) {
        echo "rei;Club updated successfully!";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
} else if ($RequestType == 'club-add') {
    $DirName = $_POST['DirName'] ?? '';
    $Name = $_POST['Name'] ?? '';
    $Type = $_POST['Type'] ?? '';
    $MemberCount = $_POST['MemberCount'] ?? 0;
    $MeetDay = $_POST['MeetDay'] ?? '';
    $Location = $_POST['Location'] ?? '';
    $Summary = $_POST['Summary'] ?? '';
    $About = $_POST['About'] ?? '';
    $Instagram = $_POST['Instagram'] ?? '';
    $Youtube = $_POST['Youtube'] ?? '';
    $Website = $_POST['Website'] ?? '';
    $Social = $_POST['Social'] ?? '';
    $Advisors = $_POST['Advisors'] ?? '';
    $Executives = $_POST['Executives'] ?? '';
    $tmpBanner = $_POST['Banner'] ?? '';

    $response = '';

    if ($DirName === 'newentry') {
        $DirName = 'club-' . bin2hex(random_bytes(8));
    }

    if ($tmpBanner !== '') {
        $originPath = 'assets/banners/' . $tmpBanner . '.png';
        $destPath = 'assets/banners/' . $DirName . '.png';

        if (file_exists($originPath)) {
            if (rename($originPath, $destPath)) {
                $response .= "banner-success;";
            } else {
                $response .= "banner-fail;";
            }
        } else {
            $response .= "banner-missing;";
        }
    } else {
        $response .= "no-banner;";
    }

    $AdvisorList = array_map('trim', explode(',', $Advisors));
    $ExecutiveList = array_map('trim', explode(',', $Executives));

    foreach ($AdvisorList as $advisor) {
        $sqlAdvisor = "SELECT Name FROM users WHERE Email = '$advisor'";
        $AdvisorResult = $conn->query($sqlAdvisor);
        if ($AdvisorResult && ($advisorRow = $AdvisorResult->fetch_assoc())) {
            $advisorstmt = $conn->prepare("UPDATE users SET Role='advisor' WHERE Email = ?");
            $advisorstmt->bind_param("s", $advisor);
            $advisorstmt->execute();
        }
    }

    foreach ($ExecutiveList as $executive) {
        $sqlExecutive = "SELECT Name FROM users WHERE Email = '$executive'";
        $ExecutiveResult = $conn->query($sqlExecutive);
        if ($ExecutiveResult && ($executiveRow = $ExecutiveResult->fetch_assoc())) {
            $executivestmt = $conn->prepare("UPDATE users SET Role='executive' WHERE Email = ?");
            $executivestmt->bind_param("s", $executive);
            $executivestmt->execute();
        }
    }

    $stmt = $conn->prepare("INSERT INTO clubs(DirName, Name, ClubType, MemberCount, MeetDay, Location, Summary, About, Instagram, Youtube, Website, Social, Advisors, Executives) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $response .= "prepare-fail;" . $conn->error;
        echo $response;
        exit;
    }

    $stmt->bind_param(
        "sssissssssssss",
        $DirName, $Name, $Type, $MemberCount, $MeetDay, $Location, $Summary, $About,
        $Instagram, $Youtube, $Website, $Social, $Advisors, $Executives
    );

    if ($stmt->execute()) {
        $response .= "asuka;New club added successfully!";
    } else {
        $response .= "shinji-13;" . $stmt->error;
    }

    echo $response;
    $stmt->close();
} else if ($RequestType == 'club-delete') {
    $DirName = $_POST['DirName'] ?? '';
    $stmt = $conn->prepare("DELETE FROM clubs WHERE DirName = ?");
    $stmt->bind_param("s", $DirName);
    if ($stmt->execute()) {
        echo "rei;Club deleted successfully!";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
}

$conn->close();