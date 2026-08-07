<?php
session_start();

$RequestType = $_POST['RequestType'] ?? '';

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
} else if ($RequestType == 'calendar-fetch') {
    $Year = $_POST['Year'] ?? '1970';
    $Month = $_POST['Month'] ?? '01';
    $Requester = $_POST['Requester'] ?? '';

    $monthStart = sprintf('%04d-%02d-01 00:00:00', (int)$Year, (int)$Month);
    $nextMonth = new DateTime($monthStart);
    $nextMonth->modify('+1 month');
    $monthEnd = $nextMonth->format('Y-m-d 00:00:00');

    $stmtClubs = $conn->prepare("SELECT MemberOf,AdminFlag FROM users WHERE Email = ?");
    $stmtClubs->bind_param("s", $Requester);
    $stmtClubs->execute();
    $result = $stmtClubs->get_result();
    $row = $result->fetch_assoc() ?? [];
    $memberOf = $row['MemberOf'] ?? '';
    $AdminFlag = $row['AdminFlag'] ?? '0';
    $clubs = array_values(array_filter(array_map('intval', explode(',', $memberOf))));
    $stmtClubs->close();

    $events = [];

    $stmt = $conn->prepare("SELECT * FROM calendar WHERE Date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ClubID = $row['ClubID'];
        $EventID = $row['EventID'];
        $Date = $row['Date'];
        $EventName = $row['EventName'];
        $EventDescription = $row['EventDescription'];
        $Visibility = $row['Visible'];
        $ClubName = 'General';
        $DirName = 'general';
        $executives = [];
        $advisors = [];
        if ($ClubID != 0 && $ClubID != null) {
            $stmtClubName = $conn->prepare("SELECT Name,DirName,Executives,Advisors FROM clubs WHERE ClubID = ?");
            $stmtClubName->bind_param("i", $ClubID);
            $stmtClubName->execute();
            $resultClubName = $stmtClubName->get_result();
            $clubRow = $resultClubName->fetch_assoc();
            $ClubName = $clubRow['Name'] ?? 'General';
            $executives = array_map('trim', explode(',', $clubRow['Executives'] ?? ''));
            $advisors = array_map('trim', explode(',', $clubRow['Advisors'] ?? ''));
            $DirName = $clubRow['DirName'] ?? 'general';
        }
        if ($AdminFlag == '1' || $Visibility != 0 || in_array($ClubID, $clubs, true) || in_array($Requester, $executives, true) || in_array($Requester, $advisors, true)) {
            $events[] = [
                'clubDirName' => $DirName,
                'clubName' => $ClubName,
                'clubID' => $ClubID,
                'date' => substr($Date, 0, 10),
                'dateDisplay' => date('M j, Y', strtotime($Date)),
                'timeDisplay' => date('H:i:s', strtotime($Date)),
                'eventName' => $EventName,
                'eventDescription' => $EventDescription,
                'eventId' => $EventID,
                'visible' => $Visibility
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($events);
} else if ($RequestType == 'event-add') {
    $EventName = trim($_POST['EventName'] ?? '');
    $EventDescription = trim($_POST['EventDescription'] ?? '');
    $Date = $_POST['Date'] ?? '1970-01-01 00:00:00';
    $DirName = $_POST['DirName'] ?? 'general';
    $Visible = (int)($_POST['Visible'] ?? 1);

    $ClubID = null;

    if ($DirName !== 'general'  && $DirName !== '') {
        $stmtClub = $conn->prepare("SELECT ClubID FROM clubs WHERE DirName = ?");
        $stmtClub->bind_param("s", $DirName);
        $stmtClub->execute();
        $clubResult = $stmtClub->get_result();
        $clubRow = $clubResult->fetch_assoc();
        $ClubID = $clubRow['ClubID'] ?? null;
        $stmtClub->close();
    }

    $stmt = $conn->prepare("INSERT INTO calendar(EventName, EventDescription, Date, ClubID, Visible) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $EventName, $EventDescription, $Date, $ClubID, $Visible);

    if ($stmt->execute()) {
        echo "rei;Event added successfully!";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
    $stmt->close();
} else if ($RequestType == 'event-save') {
    $EventID = (int)($_POST['EventID'] ?? 0);
    $EventName = trim($_POST['EventName'] ?? '');
    $EventDescription = trim($_POST['EventDescription'] ?? '');
    $Date = $_POST['Date'] ?? '1970-01-01 00:00:00';
    $DirName = $_POST['DirName'] ?? 'general';
    $Visible = (int)($_POST['Visible'] ?? 1);

    if ($EventID <= 0) {
        echo "shinji-01;Invalid event ID";
        exit;
    }

    $ClubID = null;

    if ($DirName !== 'general' && $DirName !== '') {
        $stmtClub = $conn->prepare("SELECT ClubID FROM clubs WHERE DirName = ?");
        $stmtClub->bind_param("s", $DirName);
        $stmtClub->execute();
        $clubResult = $stmtClub->get_result();
        $clubRow = $clubResult->fetch_assoc();
        $ClubID = $clubRow['ClubID'] ?? null;
        $stmtClub->close();
    }

    $stmt = $conn->prepare("
        UPDATE calendar
        SET EventName = ?, EventDescription = ?, Date = ?, ClubID = ?, Visible = ?
        WHERE EventID = ?
    ");
    $stmt->bind_param("sssiii", $EventName, $EventDescription, $Date, $ClubID, $Visible, $EventID);

    if ($stmt->execute()) {
        echo "rei;Event updated successfully!";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
    $stmt->close();
} else if ($RequestType == 'event-delete') {
    $EventID = (int)($_POST['EventID'] ?? 0);

    if ($EventID <= 0) {
        echo "shinji-01;Invalid event ID";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM calendar WHERE EventID = ?");
    $stmt->bind_param("i", $EventID);

    if ($stmt->execute()) {
        echo "rei;Event deleted successfully!";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
} else if ($RequestType == 'user-update') {

    $ClubID = $_POST['ClubID'] ?? 0;
    $UpdateType = $_POST['UpdateType'] ?? '';
    $Requester = $_POST['Requester'] ?? '';
    if ($ClubID === '' || $Requester === '') {
        echo "shinji-01;Missing ClubID or Requester";
        exit;
    }
    $stmt = $conn->prepare("SELECT MemberOf FROM users WHERE Email = ?");
    $stmt->bind_param("s", $Requester);
    $stmt->execute();
    $MemberOf = $stmt->get_result()->fetch_assoc()['MemberOf'] ?? '';
    $clubs = array_map('trim', explode(',', $MemberOf));
    if ($UpdateType === 'Register') {
        if (!in_array($ClubID, $clubs, true)) {
            $clubs[] = $ClubID;
        }
    } else if ($UpdateType === 'Unregister') {
        $clubs = array_filter($clubs, fn($id) => $id !== $ClubID);
    }
    $newMemberOf = implode(',', $clubs);
    $stmt = $conn->prepare("UPDATE users SET MemberOf = ? WHERE Email = ?");
    $stmt->bind_param("ss", $newMemberOf, $Requester);
    if ($stmt->execute()) {
        if ($UpdateType === 'Register') {
            echo "rei;Registered to club successfully!";
        } else {
            echo "asuka;Unregistered from club successfully!";
        }
    } else {
        echo "shinji-13;" . $stmt->error;
    }
} else if ($RequestType == 'post-fetch') {
    $Requester = $_POST['Requester'] ?? '';
    $Type = $_POST['Type'] ?? '';
    $stmt = $conn->prepare("SELECT AdminFlag, MemberOf FROM users WHERE Email = ?");
    $stmt->bind_param("s", $Requester);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?? [];
    $memberOf = $row['MemberOf'] ?? '';
    $AdminFlag = $row['AdminFlag'] ?? '0';
    $clubs = array_values(array_filter(array_map('intval', explode(',', $memberOf))));
    $Posts = [];
    if ($Type === 'all-posts') {
        $PublicPost = $conn->prepare("SELECT * FROM feed ORDER BY UploadTime DESC");
        $PublicPost->execute();
        $PublicPostResult = $PublicPost->get_result();
        while ($row=$PublicPostResult->fetch_assoc()) {
            $ClubID = $row['ClubID'];
            $UploadTime = $row['UploadTime'];
            $Visible = $row['Visible'];
            $stmtClubName = $conn->prepare("SELECT DirName,Name,Executives,Advisors FROM clubs WHERE ClubID = ?");
            $stmtClubName->bind_param("i", $ClubID);
            $stmtClubName->execute();
            $resultClubName = $stmtClubName->get_result();
            $clubRow = $resultClubName->fetch_assoc();
            $clubName = $clubRow['Name'] ?? 'General Post';
            $dirName = $clubRow['DirName'] ?? 'general';
            $executives = array_map('trim', explode(',', $clubRow['Executives'] ?? ''));
            $advisors = array_map('trim', explode(',', $clubRow['Advisors'] ?? ''));
            $canManage = false;
            if (in_array($Requester, $executives, true) || in_array($Requester, $advisors, true) || $AdminFlag =='1' ) {
                $canManage = true;
            }
            if (($Visible == 0 && in_array($ClubID, $clubs, true))|| $Visible == 1 || $AdminFlag =='1') {
                $Posts[] = [
                    'dirName' => $dirName,
                    'canManage' => $canManage,
                    'clubName' => $clubName,
                    'dateValue' => $UploadTime,
                    'date' => date('M j, Y', strtotime($UploadTime)),
                    'postID' => $row['PostID'],
                    'postTitle' => $row['Title'],
                    'postDescription' => $row['Description'],
                    'postImage' => $row['ImageID'],
                ];
            }
        }
    } else if ($Type === 'for-you') {
        $PersonalizedPost = $conn->prepare("SELECT * FROM feed WHERE ClubID IN (" . implode(',', $clubs) . ") ORDER BY UploadTime DESC");
        $PersonalizedPost->execute();
        $PersonalizedPostResult = $PersonalizedPost->get_result();
        while ($row=$PersonalizedPostResult->fetch_assoc()) {
            $ClubID = $row['ClubID'];
            $UploadTime = $row['UploadTime'];
            $stmtClub = $conn->prepare("SELECT DirName,Name,Executives,Advisors FROM clubs WHERE ClubID = ?");
            $stmtClub ->bind_param("i", $ClubID);
            $stmtClub ->execute();
            $resultClubName = $stmtClub ->get_result();
            $clubRow = $resultClubName->fetch_assoc();
            $clubName = $clubRow['Name'] ?? 'General Post';
            $dirName = $clubRow['DirName'] ?? 'general';
            $executives = array_map('trim', explode(',', $clubRow['Executives'] ?? ''));
            $advisors = array_map('trim', explode(',', $clubRow['Advisors'] ?? ''));
            $canManage = false;
            if (in_array($Requester, $executives, true) || in_array($Requester, $advisors, true) || $AdminFlag =='1' ) {
                $canManage = true;
            }
            $Posts[] = [
                'canManage' => $canManage,
                'clubName' => $clubName,
                'clubDirName' => $dirName,
                'dateValue' => $UploadTime,
                'date' => date('M j, Y', strtotime($UploadTime)),
                'postID' => $row['PostID'],
                'postTitle' => $row['Title'],
                'postDescription' => $row['Description'],
                'postImage' => $row['ImageID'],
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($Posts);
} else if ($RequestType == 'post-add'){
    $DirName = $_POST['DirName'] ?? '';
    $PostTitle = $_POST['PostTitle'] ?? '';
    $PostDescription = $_POST['PostDescription'] ?? '';
    $PostVisibility = $_POST['PostVisibility'] ?? '';
    $PostImages = $_FILES['PostImages'] ?? '';
    $Date = new DateTime();
    $SQLImages = '';
    $stmtClub = $conn->prepare("SELECT ClubID FROM clubs WHERE DirName = ?");
    $stmtClub->bind_param("s", $DirName);
    $stmtClub->execute();
    $clubResult = $stmtClub->get_result();
    $clubRow = $clubResult->fetch_assoc();
    $ClubID = $clubRow['ClubID'] ?? null;
    if ($PostImages && isset($PostImages['name']) && is_array($PostImages['name'])) {
        $totalImages = count($PostImages['name']);
        $uploadDir = 'assets/feed/'.$DirName. $Date->format('YmdHis');
        for ($i=0; $i<$totalImages; $i++) {
            $uploadPath = $uploadDir . '_' . $i . '.png';
            if (move_uploaded_file($PostImages['tmp_name'][$i], $uploadPath)) {
                $SQLImages .= $uploadPath;
                if ($i != $totalImages - 1) {
                    $SQLImages .= ',';
                }
            } else {
                http_response_code(500);
                echo "shinji-01;Failed to upload image";
            }
        }
    } else {
        $SQLImages = null;
    }
    $stmt = $conn->prepare("INSERT INTO feed(ClubID, Title, Description, ImageID, UploadTime) VALUES (?, ?, ?, ?, ?)");
    $SQLDate = $Date->format('Y-m-d H:i:s');
    $stmt->bind_param("issss", $ClubID, $PostTitle, $PostDescription, $SQLImages, $SQLDate);
    if ($stmt->execute()){
        echo "rei;Post added successfully!";
    } else {
        echo "shinji-13;" . $stmt->error;
    }
} else if ($RequestType == 'post-update'){
    $PostID = $_POST['PostID'] ?? '';
    $Title = $_POST['Title'] ?? '';
    $Description = $_POST['Description'] ?? '';
    $Visible = $_POST['PostVisibility'] ?? '';
    $PostImages = $_FILES['PostImages'] ?? '';
    $DirName = $_POST['dirName'] ?? '';
    $RemoveImages = $_POST['RemoveImages'] ?? '';
    $Date = new DateTime();
    $stmt = $conn->prepare("SELECT ImageID FROM feed WHERE PostID = ?");
    $stmt->bind_param("i", $PostID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $SQLImages = $row['ImageID'] ?? '';
    $images = [];
    if ($SQLImages !== null && $SQLImages !== '') {
        $images = array_map('trim', explode(',', $SQLImages));
    }
    if ($PostImages && isset($PostImages['name']) && is_array($PostImages['name'])) {
        $totalImages = count($PostImages['name']);
        $uploadDir = 'assets/feed/'.$DirName. $Date->format('YmdHis');
        for ($i=0; $i<$totalImages; $i++) {
            $uploadPath = $uploadDir . '_' . $i . '.png';
            if (move_uploaded_file($PostImages['tmp_name'][$i], $uploadPath)) {
                $images[] .= $uploadPath;
            } else {
                http_response_code(500);
                echo "shinji-01;Failed to upload image";
                exit;
            }
        }
    }
    $toRemove = [];
    if (!empty($RemoveImages)) {
        if (is_array($RemoveImages)) {
            $toRemove = array_map('trim', $RemoveImages);
        } else {
            $toRemove = array_map('trim', explode(',', (string)$RemoveImages));
        }
        $toRemove = array_filter($toRemove, fn($v) => $v !== '');
        $toRemove = array_values($toRemove);
    }
    if (!empty($toRemove)) {
        $images = array_values(array_diff($images, $toRemove));
    }
    $SQLImages = !empty($images) ? implode(',', $images) : null;
    $stmt = $conn->prepare("UPDATE feed SET Title = ?, Description = ?, ImageID = ?, Visible = ? WHERE PostID = ?");
    $stmt->bind_param("ssssi", $Title, $Description, $SQLImages, $Visible, $PostID);
    if ($stmt->execute()){
        echo "rei;Post updated successfully!;$SQLImages;$RemoveImages[0]";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
} else if ($RequestType == 'post-delete'){
    $PostID = $_POST['PostID'] ?? '';
    $stmt = $conn->prepare("DELETE FROM feed WHERE PostID = ?");
    $stmt->bind_param("i", $PostID);
    if ($stmt->execute()){
        echo "rei;Post deleted successfully!";
    } else {
        echo "shinji-01;" . $stmt->error;
    }
} else if ($RequestType == 'announcement-update'){
    $NewAnnouncement = $_POST['NewAnnouncement'] ?? '';
    $RemoveAnnouncement = $_POST['RemoveAnnouncements'] ?? [];
    if (!is_array($RemoveAnnouncement)) {
        $RemoveAnnouncement = [$RemoveAnnouncement];
    }
    $RemoveAnnouncement = array_map('intval', $RemoveAnnouncement);
    $messages = [];
    if (!empty($RemoveAnnouncement)) {
        $placeholders = implode(',', array_fill(0, count($RemoveAnnouncement), '?'));
        $stmt = $conn->prepare("DELETE FROM announcements WHERE AnnouncementID IN ($placeholders)");
        if (!$stmt) {
            echo "shinji-13;" . $conn->error;
            exit;
        }
        $types = str_repeat('i', count($RemoveAnnouncement));
        $stmt->bind_param($types, ...$RemoveAnnouncement);
        if ($stmt->execute()) {
            $messages[] = "removed";
        } else {
            echo "shinji-13;" . $stmt->error;
            exit;
        }
        $stmt->close();
    }
    if ($NewAnnouncement !== '') {
        $stmtadd = $conn->prepare("INSERT INTO announcements(Announcement) VALUES (?)");
        if (!$stmtadd) {
            echo "shinji-13;" . $conn->error;
            exit;
        }
        $stmtadd->bind_param("s", $NewAnnouncement);
        if ($stmtadd->execute()) {
            $messages[] = "added";
        } else {
            echo "shinji-01;" . $stmtadd->error;
            exit;
        }
        $stmtadd->close();
    }
    if (empty($messages)) {
        echo "asuka;No announcement changes submitted.";
    } else {
        echo "rei;Announcement update successful!";
    }
}

$conn->close();
