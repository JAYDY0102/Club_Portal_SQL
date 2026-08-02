<?php
session_start();

$secret = require __DIR__ . '/../auth/secret.php';
$user = $_SESSION['user'] ?? null;
$SignedIn = isset($_SESSION['user']);

$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

$role = null;
$admin = null;
function e($value): string
{
    return htmlspecialchars(($value ?? ''), ENT_QUOTES, 'UTF-8');
}
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}
$email = $user['Email'];
$stmt = $conn->prepare("SELECT Role, AdminFlag FROM users WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$role = $row['Role'];
$admin = $row['AdminFlag'];

if (!$SignedIn) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal - Calendar</title>
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
            <a id="active" href="../calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
            <?php if ($admin == '1'): ?>
                <a id="inactive" href="../dashboard/admin.php" class="sis-bar-item sis-padding-16 sis-button">Admin
                    Dashboard</a>
            <?php elseif ($role == 'advisor'): ?>
                <a id="inactive" href="../dashboard/advisor.php" class="sis-bar-item sis-padding-16 sis-button">Advisor
                    Dashboard</a>
            <?php elseif ($role == 'executive'): ?>
                <a id="inactive" href="../dashboard/executive.php" class="sis-bar-item sis-padding-16 sis-button">Executive
                    Dashboard</a>
            <?php else: ?>
                <a id="inactive" onClick="alert('You do not have permissions to use the Dashboard')"
                   class="sis-bar-item sis-padding-16 sis-button">Dashboard</a>
            <?php endif; ?>
        </nav>
        <a id="inactive" class="sis-bar-item sis-button sis-padding-16 mobile-menu" data-state="closed">
            Menu ▾
        </a>
        <div class="spacer sis-bar-item">
            <div class="space-inner"></div>
        </div>
        <div class="tnb-right-section">
            <div id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button"
                 title="Sign out of your account" onClick="window.location.href='auth/signout.php'">
                <span class="button-text">Sign Out</span>
            </div>
            <a href="../assets/site_images/fair_map.png" class="tnb-right-side-btn sis-bar-item sis-button sis-right"
               title="Club Fair Map" aria-label="Club Fair Map">Fair Map</a>
        </div>
    </div>
    <nav id="tnb-mobile-nav" class="tnb-mobile-nav">
        <div class="mobile-container">
            <div class="tnb-mobile-nav-section" data-section="home" onClick="window.location.href='../index.php'">
                <div class="sis-button">
                    <span class="tnb-title">Home</span>
                </div>
            </div>
            <div class="tnb-mobile-nav-section" data-section="feed" onClick="window.location.href='../feed'">
                <div class="sis-button">
                    <span class="tnb-title">Feed</span>
                </div>
            </div>
            <div class="tnb-mobile-nav-section" data-section="calendar" onClick="window.location.href='../calendar'">
                <div class="sis-button">
                    <span class="tnb-title">Calendar</span>
                </div>
            </div>
            <?php if ($admin == '1'): ?>
                <div class="tnb-mobile-nav-section" data-section="admin"
                     onClick="window.location.href='../dashboard/admin.php'">
                    <div class="sis-button">
                        <span class="tnb-title">Admin Dashboard</span>
                    </div>
                </div>
            <?php elseif ($role == 'advisor'): ?>
                <div class="tnb-mobile-nav-section" data-section="advisor"
                     onClick="window.location.href='../dashboard/advisor.php'">
                    <div class="sis-button">
                        <span class="tnb-title">Advisor Dashboard</span>
                    </div>
                </div>
            <?php elseif ($role == 'executive'): ?>
                <div class="tnb-mobile-nav-section" data-section="executive"
                     onClick="window.location.href='../dashboard/executive.php'">
                    <div class="sis-button">
                        <span class="tnb-title">Executive Dashboard</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="tnb-mobile-nav-section" data-section="dashboard"
                     onClick="alert('You do not have permissions to use the Dashboard')">
                    <div class="sis-button">
                        <span class="tnb-title">Dashboard</span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="tnb-mobile-nav-section" data-section="fairmap" onClick="window.location.href='../assets/site_images/fair_map.png'">
                <div class="sis-button">
                    <span class="tnb-title">Club Fair Map</span>
                </div>
            </div>
        </div>
        <div class="sis-button tnb-close-btn">
            <span>×</span>
        </div>
    </nav>
</div>
<div class="topnavbackground"></div>
<div class="topnavcontainer">
    <div class="subtopnav">
        <div class="scroll-left-btn"></div>
        <div class="scroll-right-btn"></div>
        <?php
        $sql = "SELECT Announcement FROM announcements";
        $result = $conn->query($sql);
        $announcements = [];

        while ($row = $result->fetch_assoc()) {
            if (trim($row['Announcement']) !== '') {
                $announcements[] = $row['Announcement'];
            }
        }

        $totalLength = strlen(implode('', $announcements));
        $repeatCount = max(2, ceil(200 / max($totalLength, 1)));

        echo "<div class='announcement-track'>";

        for ($i = 0; $i < $repeatCount * 2; $i++) {
            foreach ($announcements as $announcement) {
                echo "<a>" . e($announcement) . "</a>";
            }
        }

        echo "</div>";
        ?>
    </div>
</div>
<div class="background-image"></div>
<div class="contentcontainer">
    <div class="belowtopnavcontainer">
        <div class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Calendar</h2>
                    <p>Check club events</p>
                </div>
                <div class="calendar-panel">
                    <div class="calendar-section">
                        <h2>Calendar</h2>
                        <div class="calendar-group see-thru">
                            <header class="calendar-header" aria-label="Calendar">
                                <button id="prev-btn">◀</button>
                                <h2 id="month-year-display"></h2>
                                <button id="next-btn">▶</button>
                            </header>
                            <div class="weekdays-grid">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>
                            <div id="days-grid" class="days-grid"></div>
                        </div>
                    </div>
                    <div class="calendar-section">
                        <h2>Upcoming Events</h2>
                    </div>
                </div>
                <?php if ($admin == '1' || $role == 'executive' || $role == 'advisor'): ?>
                <div class="event-panel">
                    <div class="calendar-section">
                        <h2 id="event-form-heading">Event Registration</h2>
                        <div class="event-management">
                            <div class="form-group see-thru">
                                <div class="form-group-title">Select Club</div>
                                <div class="club-list" style="margin-bottom: 0">
                                    <label for="club-options" style="display: none">Clubs</label>
                                    <select id="club-options" class="club-options" size="10" style="background-color: var(--primary-white)">
                                        <?php if ($admin == '1') {
                                            $sql = "SELECT DirName, Name FROM clubs ORDER BY Name ASC";
                                            $result = $conn->query($sql);
                                            echo "<option value='general'>General Event</option>";
                                        } else {
                                            if ($role == 'executive') {
                                                $stmt = $conn->prepare("SELECT DirName, Name FROM clubs WHERE FIND_IN_SET(?, Executives) > 0 ORDER BY Name ASC");
                                            } else {
                                                $stmt = $conn->prepare("SELECT DirName, Name FROM clubs WHERE FIND_IN_SET(?, Advisors) > 0 ORDER BY Name ASC");
                                            }
                                            $stmt->bind_param("s", $email);
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                        }
                                        if (!$result) {
                                            die("Query failed: " . $conn->error);
                                        }
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row["DirName"] . "'>" . $row["Name"] . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group see-thru">
                                <div class="form-group-title">Event Name</div>
                                <div class="form-grid">
                                    <label for="event-name">Event Name</label>
                                    <input id="event-name" type="text" class="form-input" placeholder="Event Name">
                                </div>
                            </div>
                            <div class="form-group see-thru">
                                <div class="form-group-title">Event Description</div>
                                <div class="form-grid">
                                    <label for="event-description">Detailed event description that shows up once inspection</label>
                                    <textarea id="event-description" class="form-input" placeholder="Event Description"></textarea>
                                </div>
                            </div>
                            <div class="form-group see-thru">
                                <div class="form-group-title">Event Date</div>
                                <div class="form-grid">
                                    <label for="event-date">Event Date in DD-MM-YYYY format</label>
                                    <input id="event-date" type="date" class="form-input" placeholder="Event Date">
                                </div>
                            </div>
                            <div class="form-group see-thru">
                                <div class="form-group-title">Event Time</div>
                                <div class="form-grid">
                                    <label for="event-time">Event Time in 00:00 format, in the 24-hour system</label>
                                    <input id="event-time" type="time" class="form-input" placeholder="Event Time">
                                </div>
                            </div>
                            <div class="form-group see-thru">
                                <div class="form-group-title">Event Visibility</div>
                                <div class="form-grid">
                                    <label for="event-visibility">Select to make event public to non-members</label>
                                    <input id="event-visibility" type="checkbox" class="form-input">
                                </div>
                            </div>
                            <div class="form-btn-group">
                                <div class="form-btn" id="add-btn">Add Event</div>
                                <div class="form-btn" id="clear-btn" style="display: none">New Event</div>
                                <div class="form-btn" id="delete-btn" style="display: none">Delete Event</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class ="wrappercontainer">
    <div class="footerwrapper">
        <div class="spacefooter">
            <div class="footerlinks" style="overflow-x:auto;">
                <div class="footerlinks_1">
                    <a href="https://tigerclubs.org/index.php" aria-label="Tigerclubs.org">
                        <i class="fa fa-logo">1</i>
                    </a>
                </div>
                <div class="footerlinks_1">
                    <a href="https://forms.gle/mgUxnthy2izYn4yi8" title="Submit a request to add an image on the main banner">BANNER REQUEST</a>
                </div>
                <div class="footerlinks_1">
                    <a href="https://forms.gle/QwJxodQaQRro4cqB7" title="Submit an interest form cooperatively create a website for your own club with Coding Club">INTEREST FORM</a>
                </div>
                <div class="footerlinks_1">
                    <a href="https://forms.gle/KFqJG2EHqEsWUuB47" title="Submit a bug report that you have encountered on the website">BUG REPORT</a>
                </div>
                <div class="footerlinks_1">
                    <?php
                    $sqlContact = "SELECT Executives FROM clubs WHERE DirName='coding_club'";
                    $resultContact = $conn->query($sqlContact);
                    $ExecutivesContacts = $resultContact->fetch_assoc()['Executives'];
                    $ExecutivesContactList = array_map('trim', explode(',', $ExecutivesContacts));
                    $PresidentContact = $ExecutivesContactList[0];
                    echo
                    "<a href='mailto:$PresidentContact' title='Contact Us!'>CONTACT US</a>";
                    $conn->close();
                    ?>
                </div>
            </div>
            <div class="footertext">
                Tigerclubs.org is made to promote connectivity across all clubs of SIS. It prioritizes accessibility over functionality.
                <br>
                Select members of Coding Club are constantly working to improve the website, but we cannot warrant that it will be free of bugs.
                <br>
                Please use the links below to submit any main banner request, club-specific website interest form, or bug reports if you happen to notice any.
                <br>
                <br>
                <a href="https://github.com/JAYDY0102/Club_Portal_SQL/blob/master/LICENSE">MIT License</a>
                of the website's source code.
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="event-id" value="">
</body>
</html>
<script>
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileNav = document.querySelector('.tnb-mobile-nav');
    const closeNav = document.querySelector('.tnb-close-btn');
    const monthYearDisplay = document.getElementById('month-year-display');
    const daysGrid = document.getElementById('days-grid');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const addBtn = document.getElementById('add-btn');
    const deleteBtn = document.getElementById('delete-btn');
    const clubOptions = document.getElementById('club-options');
    const nameInput = document.getElementById('event-name');
    const descriptionInput = document.getElementById('event-description');
    const dateInput = document.getElementById('event-date');
    const timeInput = document.getElementById('event-time');
    const visibilityInput = document.getElementById('event-visibility');
    const eventIdInput = document.getElementById('event-id');
    const clearBtn = document.getElementById('clear-btn');
    const calendarSection = document.getElementById('event-form-heading');

    const state = {
        currentDate: new Date(),
        renderToken: 0
    }

    const monthNames = [
        "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
    ];

    mobileMenu.addEventListener('click', () => {
        const state = mobileMenu.getAttribute('data-state');
        if (state === 'closed') {
            mobileMenu.innerHTML = 'Menu ▴';
            mobileMenu.setAttribute('data-state', 'open');
            mobileNav.style.display = 'block';
        } else if (state === 'open') {
            mobileMenu.innerHTML = 'Menu ▾';
            mobileMenu.setAttribute('data-state', 'closed');
            mobileNav.style.display = 'none';
        }
    })

    closeNav.addEventListener('click', () => {
        mobileMenu.innerHTML = 'Menu ▾';
        mobileMenu.setAttribute('data-state', 'closed');
        mobileNav.style.display = 'none';
    })

    prevBtn.addEventListener('click', () => {
        state.currentDate.setMonth(state.currentDate.getMonth() - 1);
        renderCalendar();
    })

    nextBtn.addEventListener('click', () => {
        state.currentDate.setMonth(state.currentDate.getMonth() + 1);
        renderCalendar();
    })

    daysGrid.addEventListener('click', (e) => {
        const eventEl = e.target.closest('.event');
        if (!eventEl) return;

        document.querySelectorAll('.event.selected').forEach(el => {
            el.classList.remove('selected');
        });
        eventEl.classList.add('selected');

        eventIdInput.value = eventEl.dataset.id || '';
        clubOptions.value = eventEl.dataset.club || 'general';
        nameInput.value = eventEl.dataset.title || '';
        descriptionInput.value = eventEl.dataset.description || '';
        dateInput.value = eventEl.dataset.date || '';

        setFormMode('edit');
    });

    clubOptions.addEventListener('change', () => {

    })

    addBtn.addEventListener('click', async () => {
        const formData = new FormData();
        formData.append('EventID', eventIdInput.value);
        formData.append('DirName', clubOptions.value);
        formData.append('EventName', nameInput.value);
        formData.append('EventDescription', descriptionInput.value);
        formData.append('Date', `${dateInput.value} ${timeInput.value}:00`);
        formData.append('Visible', visibilityInput.checked ? '1' : '0');

        formData.append('RequestType', eventIdInput.value ? 'event-save' : 'event-add');

        const response = await fetch('../post.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.text();
        const status = result.split(';');

        if (status[0] === 'rei') {
            alert('Event Successfully Added/Updated');
            window.location.reload();
        } else {
            alert('Failed to add/update event. Please try again.');
        }
    });

    clearBtn.addEventListener('click', () => {
        document.querySelectorAll('.event.selected').forEach(el => {
            el.classList.remove('selected');
        });
        eventIdInput.value = '';
        clubOptions.value = 'general';
        nameInput.value = '';
        descriptionInput.value = '';
        dateInput.value = '';
        timeInput.value = '';
        visibilityInput.checked = false;

        setFormMode('add');
    });

    deleteBtn.addEventListener('click', async () => {
        const eventId = eventIdInput.value;
        if (!eventId) return alert('No event selected.');

        const formData = new FormData();
        formData.append('RequestType', 'event-delete');
        formData.append('EventID', eventId);

        const response = await fetch('../post.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.text();
        const status = result.split(';');

        if (status[0] === 'rei') {
            alert('Event Successfully Deleted');
            window.location.reload();
        } else {
            alert('Delete failed');
        }
    });

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    renderCalendar();

    async function fetchEvents(year, month) {
        const formData = new FormData();
        formData.append('RequestType', 'calendar-fetch');
        formData.append('Year', String(year));
        formData.append('Month', String(month + 1));
        formData.append('Requester', '<?php echo $email ?>');
        const response = await fetch('../post.php', {
            method: 'POST',
            body: formData
        });

        return response.json();
    }

    function groupEventsByDate(events) {
        const map = {};
        for (const event of events) {
            const dateKey = event.date;
            if (!map[dateKey]) map[dateKey] = [];
            map[dateKey].push(event);
        }
        return map;
    }

    async function renderCalendar() {
        const token = ++state.renderToken;

        const year = state.currentDate.getFullYear();
        const month = state.currentDate.getMonth();

        monthYearDisplay.textContent = `${monthNames[month]} ${year}`;
        daysGrid.innerHTML = '';

        const firstDayIndex = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();
        const today = new Date();

        let eventsByDate = {};

        try {
            const events = await fetchEvents(year, month);
            if (token !== state.renderToken) return;
            eventsByDate = groupEventsByDate(events);
        } catch (error) {
            console.error('Error fetching events:', error);
        }
        for (let i = 1; i <= totalDays; i++) {
            if (token !== state.renderToken) return;
            const dayCell = document.createElement('div');
            dayCell.classList.add('day');
            if (i === 1){
                dayCell.style.gridColumnStart = firstDayIndex + 1;
            }
            if (i ===today.getDate()&&month===today.getMonth()&&year===today.getFullYear()){
                dayCell.classList.add('day-today');
            }
            const dateKey = `${year}-${pad2(month + 1)}-${pad2(i)}`;
            const events = eventsByDate[dateKey] || [];
            let eventsHtml = '';
            for (const event of events) {
                eventsHtml += `<div class="event"
                    data-club-id="${event.clubID}"
                    data-date="${event.date}"
                    data-name="${event.eventName}"
                    data-description="${event.eventDescription}"
                    data-id="${event.eventId}">
                    ${event.eventName} (${event.clubName})
                </div>`;
            }

            dayCell.innerHTML = `
                <a class="date">${i}</a>
                <div class="events">${eventsHtml}</div>
            `;

            daysGrid.appendChild(dayCell);
        }
    }
    function setFormMode(mode) {
        if (mode === 'edit') {
            addBtn.innerText = 'Save Event';
            clearBtn.style.display = 'inline-block';
            deleteBtn.style.display = 'inline-block';
            calendarSection.textContent = 'Event Modification';
        } else {
            addBtn.innerText = 'Add Event';
            clearBtn.style.display = 'none';
            deleteBtn.style.display = 'none';
            calendarSection.textContent = 'Event Registration';
        }
    }
</script>