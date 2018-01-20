# Backup to Google Drive

> Backup to Google Drive from different sources

## Scope

* [x] **Google Contacts** with contact photos as vCards
* [x] **Google Photo Albums** with modified photos in right order per album
* [x] **Remember The Milk** with subtasks as json file
* [x] **GitHub Repositories** with all public and private Git folders and all issues

## Setup

1. Create Google API Key and OAuth2 Client
2. Save config.example.php as config.php and update values
3. Upload files to server
4. Open index.php on server to configure users
5. Setup cronjobs for all cron_....php files

## Notes

- Requires PHP and Git installed on server
- To force updates, change the ident definition (in more then one place maybe)
