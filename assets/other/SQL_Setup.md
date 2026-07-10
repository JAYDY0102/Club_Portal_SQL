# SQL Setup Documentation

As I will not be at SIS forever, this is for anyone who will manage this in the future.

## SQL Setup

### CREATING THE DATABASE

- If for whatever reason you have to set up the SQL database again, here is how to do it.
```sql
CREATE DATABASE tiger_clubs;
```
- To delete the database, use the following command:
```sql
DROP DATABASE tiger_clubs;
```

### CREATING THE TABLES

- The following tables are required for the club portal to work properly. To create them, use the following commands:

```sql
CREATE TABLE clubs(
    ClubID INT AUTO_INCREMENT,
    DirName VARCHAR(255),
    Name VARCHAR(255),
    ClubType VARCHAR(255),
    MemberCount INT DEFAULT 0,
    MeetDay ENUM('Monday', 'Wednesday', 'Thursday A', 'Thursday B', 'Friday', 'Other') DEFAULT 'Monday',
    Location VARCHAR(255),
    Summary VARCHAR(255),
    About VARCHAR(4095),
    Instagram VARCHAR(255),
    Youtube VARCHAR(255),
    Website VARCHAR(255),
    Social VARCHAR(255),
    Advisors VARCHAR(255),
    Executives VARCHAR(255),
    PRIMARY KEY (ClubID, DirName)
);
```
```sql
CREATE TABLE users(
    Email VARCHAR(255),
    Name VARCHAR(255),
    UserName VARCHAR(255),
    MemberOf VARCHAR(255),
    Role ENUM('user', 'executive', 'advisor') DEFAULT 'user',
    AdminFlag BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (Email) 
);
```
```sql
CREATE TABLE feed(
    ClubID INT,
    PostID INT AUTO_INCREMENT,
    UploadTime TIMESTAMP,
    Title VARCHAR(255),
    Description VARCHAR(4095),
    ImageID INT,
    Visible BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (PostID)
);
```
```sql
CREATE TABLE calendar(
    ClubID INT,
    EventID INT AUTO_INCREMENT,
    Date TIMESTAMP,
    EventName VARCHAR(255),
    EventDescription VARCHAR(4095),
    Visible BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (ClubID)
);
```

### MODIFYING THE TABLES

## Launching the Website

### FILLING IN THE TABLES WITH DATA

- User tables will be filled in automatically as people sign in.
    - However, to modify the user's status as an admin, this must be done through SQL.
    - Other roles such as executives and advisors can be modified through the website(By an admin or an advisor).
```sql
USE tiger_clubs
UPDATE users SET AdminFlag = TRUE WHERE Email = '(Place email address here)';
```
- Adding clubs can also be done by admins through the website. 
    - However, if you want to add a club through SQL, use the following command (Example done with Coding Club):
    - **DO THIS WITH CAUTION**. You cannot insert a club with the same ClubID or DirName as another club (Leave the ClubID out unless you know what you're doing).
```sql
INSERT INTO clubs(
    DirName, Name, ClubType, MemberCount, 
    MeetDay, Location, Summary, About, Instagram, 
    Youtube, Website, Social, Advisors, Executives
) VALUES (
    'coding_club', 
    'Coding Club',
    'STEM', 
    99, 
    'Wednesday', 
    'M116', 
    'SIS Coding Club is where students turn ideas into real software.', 
    'SIS Coding Club is where students turn ideas into real software. Join us to code, collaborate, and create things that matter.', 
    '', 
    '', 
    'https://tigerclubs.org/codingclub', 
    '', 
    'warkentinn@siskorea.org', 
    'hyunjun.oh27@stu.siskorea.org, woong.cho29@stu.siskorea.org, jiwu.lee27@stu.siskorea.org'
);
```
## Closing Notes

- I have spent a lot of time on this project, and writing this is in the hope that it will be useful for the future coding club executives. Please take good care of this project. If you have any questions, feel free to reach out to me through [here](mailto:jayden.oh0102@gmail.com).