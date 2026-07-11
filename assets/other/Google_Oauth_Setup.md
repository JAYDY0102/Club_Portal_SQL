# Google Oauth Setup Documentation

Currently, the Google Oauth is set up through my school email. Since my school email is destroyed when I leave, it needs to be set up again by someone else once or before that happens. Here are the steps to set it up.

## Setting up a Google Cloud Project

A Google Cloud project is required to set up the Google Oauth. While I believe using our own Coding Club account to create a Google Cloud project would be better, I would stay with the safe option and make it in the stu.siskorea.org organization. Although more tedious setting it up every year, using an outside unverified Google app might be blocked by the school (Haven't tried though).

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Click on "Select a project" and then "New project".
3. Give the project a name and click "Create". If you are using a school email, you will be defaulted to selecting the "stu.siskorea.org" organization.
4. Click on "Select Project" when the notification pops up. If no notification pops up, open the project picker and select the project.

## Setting up the Google Oauth
1. Select "APIs & Services" from the center icons.
2. Click on the "Get started" button.
3. Fill in the form. Make sure to select "External" for the audience type. 
4. Click on "Create".
5. From the left sidebar, select "Clients".
6. Click on "Create client".
7. Fill in the form. Select "Web application" for the application type. For the authorization JavaScript origins, add "https://tigerclubs.org" and for the redirect URIs, add "https://tigerclubs.org/auth/callback.php". Click "Create".
8. Once pressing "Create", you will be shown a client ID and client secret. Copy them and keep them for now.
9. From the left sidebar, select "Audience".
10. Publish the app. 

## Setting up the Google Oauth in the Coding Club website
1. Open the secret_modify.php file in the auth/ directory.
2. Replace the placeholders for the client ID and client secret with the ones you copied from the previous step.
3. Fill in the rest of the secret.php file with the appropriate information. Ask Mr. Warkentin for the SQL-related information if unsure.
4. Rename the file to secret.php. This is to prevent the Google client ID and secret from being exposed in the repository.
5. Upload the secret.php file to the auth/ directory of the website. Make sure to not commit this file to the repository.

Hopefully, this will be all you need to do to set up the Google Oauth. If you have any questions, feel free to reach out to me through [here](mailto:jayden.oh0102@gmail.com).