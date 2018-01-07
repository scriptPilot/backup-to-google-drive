<?php

  /**
   * Include config and modules
   */

  // Include configuration
  require_once('config.php');

  // Include vendor modules
  require_once('vendor/autoload.php');

  // Include functions
  $functionsDir = opendir('functions');
  while ($file = readdir($functionsDir)) if ($file !== '.' && $file !== '..') require_once('functions/' . $file);

  // Extend script timeout to 24 hours
  set_time_limit(60 * 60 * 24);

  /**
   * Init Google client and service
   */

  $googleClient = createGoogleClient([
    'https://www.googleapis.com/auth/contacts',
    'https://picasaweb.google.com/data/',
    'https://www.googleapis.com/auth/drive'
  ]);
  $googleDriveService = new Google_Service_Drive($googleClient);

  /**
   * Show header
   */

  showHeader();

  /**
   * Show logout link
   */

  echo '<a href="index.php?googleLogout">Google logout</a><br />'
     . '<br />';

  /**
   * Show form
   */

  echo '<form method="post" action="index.php">'
     . 'Synchronization / Destination Folders:<br />'
     . '<br />'
     . '<input type="checkbox" name="backupContacts" value="yes" ' . (($_POST['backupContacts'] === 'yes' or !$_POST) ? 'checked="checked"' : '') . ' /> &nbsp; <input type="text" name="contactsFolder" value="' . (isset($_POST['contactsFolder']) ? $_POST['contactsFolder'] : DEFAULT_BACKUP_FOLDER_CONTACTS) . '" size="50" /> for contacts<br />'
     . '<input type="checkbox" name="backupAlbums" value="yes" ' . (($_POST['backupAlbums'] === 'yes' or !$_POST) ? 'checked="checked"' : '') . ' /> &nbsp; <input type="text" name="albumsFolder" value="' . (isset($_POST['albumsFolder']) ? $_POST['albumsFolder'] : DEFAULT_BACKUP_FOLDER_ALBUMS) . '" size="50" /> for albums<br />'
     . '<br />'
     . '<input type="submit" value="Start backup" onclick="this.value=\'Please wait ...\'" />'
     . '</form>';

  /**
   * Form submitted
   */

  if ($_POST) {

    // Check folders
    if ($_POST['backupContacts'] === 'yes' and trim($_POST['contactsFolder']) === '') {
      echo '<span style="color: red">Please type contacts folder</span><br />';
    } else if ($_POST['backupAlbums'] === 'yes' and trim($_POST['albumsFolder']) === '') {
      echo '<span style="color: red">Please type albums folder</span><br />';
    } else if ($_POST['backupContacts'] === 'yes' || $_POST['backupAlbums'] === 'yes') {

      // Remember start time
      $startTime = time();

      // Do backup
      if ($_POST['backupContacts'] === 'yes') backupContacts($googleDriveService);
      if ($_POST['backupAlbums'] === 'yes') backupAlbums($googleDriveService);

      // Show run time
      $endTime = time();
      $duration = $endTime - $startTime;
      echo '<br />'
         . '<span style="color: grey">Run: ' . date('H:i:s') . ' / ' . $duration . ' second' . ($duration !== 1 ? 's' : '') . '</span>';

    }

  }

  /**
   * Show footer
   */

  showFooter();

?>