# Club Portal SQL

A revised and improved version of SIS's club portal. Revised to run mostly on SQL and PHP sessions.

## Features

- Display club information through SQL
- User authentication and authorization
- Club management (create, edit, delete clubs)
    - Dependent on user roles (admin, executive, advisor, basic user)
- And more...

## Documentation
- [General Documentation](https://docs.google.com/document/d/1OUWDLhOaK6GCYXpYlEebaH7BezBv5snpdQzy2XnBOj0/edit?usp=sharing)
- [SQL Setup Documentation](assets/other/SQL_Setup.md)
- [Google OAuth Setup](assets/other/Google_Oauth_Setup.md)

This is the general documentation re-written more clearly compared to the Google doc version. 
Since this is merely an attempt to clarify the documentation, if anything is unclear, please feel free to contact me. [Contact](#contact)
### File Structure
Below is the file structure as should be in the www/ directory.
```text
html/
  ├── assets/
  │   ├── banners/
  │   │   └── All banners in png format.
  │   ├── fonts/
  │   │   ├── GoogleSans-VariableFont_GRAD,opsz,wght.ttf
  │   │   └── sis_logo.ttf
  │   ├── other/
  │   ├── site_images/
  │   │   └── All site images in png format.
  ├── auth/
  │   ├── callback.php
  │   ├── secret.php
  │   ├── sigin.php
  │   └── signout.php
  ├── calendar/
  │   └── index.php
  ├── dashboard/
  │   ├── admin.php
  │   ├── advisor.php
  │   └── executive.php
  ├── feed/
  │   └── index.php
  ├── index.php
  └── style.css
```
For secret.php, modify the secret_modify.php file to include the Google client ID and secret, then rename it to secret.php. This is to prevent the Google client ID and secret from being exposed in the repository.
### Database Structure
For the database structure, refer to the [SQL Setup Documentation](assets/other/SQL_Setup.md).
### Login Functionality
Currently, I've built the site to run on Google OAuth 2.0. Here is a brief description of how it works:
1. (User is in /auth/signin.php) User clicks the login button.
2. The redirecting URL is built using PHP. The Google documentation of how to build the URL can be found [here](https://developers.google.com/identity/protocols/oauth2/web-server).
3. After the user signs in with Google, they are redirected to the callback.php file. [Detailed information here](assets/other/Google_Oauth_Setup.md).
4. The callback.php checks if the user already exists in the database, and if not, registers the user to the SQL user table.
5. Callback.php redirects the user to the main page after setting the PHP session variables.
### Index.php
The index.php file is the main page of the site. It displays the list of clubs and is the landing page of https://tigerclubs.org. The site fetches the data from the SQL database. The grid class section displays card class articles generated through the PHP script.
### Dashboard/ANY.php
The dashboard sites are the pages that the user can access when given the proper role.
## Contact
Hyunjun Jayden Oh – [Email](mailto:jayden.oh0102@gmail.com) [Instagram](https://instagram.com/jaydy0102)
## License
[MIT](LICENSE)