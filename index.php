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

  /**
   * Init Google client and service
   */

  $googleClient = createGoogleClient(['https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/drive']);
  $googleDriveService = new Google_Service_Drive($googleClient);

  /**
   * Show header
   */

  showHeader();

  /**
   * Show form
   */

  echo '<form method="post" action="index.php">'
     . '  Destination folder: &nbsp; <input type="text" name="destinationFolder" value="' . (isset($_POST['destinationFolder']) ? $_POST['destinationFolder'] : DEFAULT_BACKUP_FOLDER_CONTACTS) . '" /> &nbsp; <input type="submit" value="Start backup" />'
     . '</form>';

  /**
   * Do contacts sync
   */

  // Check destination folder input
  $destinationFolder = isset($_POST['destinationFolder']) ? trim($_POST['destinationFolder']) : '';
  if (isset($_POST['destinationFolder']) && $destinationFolder === '') echo '<span style="color: red">Please type the destination folder</span><br /><br />';

  // Destination folder is provided
  if ($destinationFolder !== '') {

    // Remember start time
    $startTime = time();

    // Load contacts
    $url = 'https://people.googleapis.com/v1/people/me/connections';
    $params = ['personFields' => 'names,phoneNumbers,emailAddresses,addresses,birthdays,photos,biographies', 'pageSize' => 1000];
    $contacts = performGoogleRequest($url, $params);
    $contacts = $contacts['connections'];

    // Get etags from contacts
    $contactsEtags = [];
    foreach ($contacts as $contact)  {
      $contactsEtags[] = $contact['etag'];
    }

    // Get Google Drive folder id
    $folderId = getGoogleDriveFolderId($destinationFolder, true);

    // Get etags from Google Drive
    $url = 'https://www.googleapis.com/drive/v3/files';
    $params = [
      'q' => 'trashed=false and parents="' . $folderId . '"',
      'fields' => 'files(id, name, description)'
    ];
    $filesInGoogleDrive = performGoogleRequest($url, $params)['files'];
    $driveEtags = [];
    foreach ($filesInGoogleDrive as $file)  {
      $driveEtags[$file['id']] = [
        'id' => $file['id'],
        'name' => $file['name'],
        'etag' => substr($file['description'], 0, 5) === 'etag:' ? substr($file['description'], 5) : ''
      ];
    }

    // Remove duplicated files (remove the etag, so they will be trashed later on)
    $uniqueDriveEtags = [];
    foreach ($driveEtags as $fileId => $file) {
      if (in_array($file['etag'], $uniqueDriveEtags)) {
        $driveEtags[$fileId]['etag'] = '';
      } else {
        $uniqueDriveEtags[] = $file['etag'];
      }
    }

    // Remove additional files (etag not found in contacts anymore)
    foreach ($driveEtags as $file) {
      if (!in_array($file['etag'], $contactsEtags)) {
        $trashed = trashGoogleDriveFile($file['id']);
        if ($trashed) echo '<span style="color: orange">' . $file['name'] . ' trashed</span><br />';
        else echo '<span color="red">Failed to trash ' . $file['name'] . '</span><br />';
      }
    }

    // Create missing files
    foreach ($contacts as $contact) {
      if (!in_array($contact['etag'], $uniqueDriveEtags)) {
        $fileName = $contact['names'][0]['displayName'] . '.vcf';
        $vCard = convertGoogleContactToVCard($contact);
        $upload = uploadFileToGoogleDrive($googleDriveService, $folderId, $fileName, $vCard, 'etag:' .$contact['etag']);
        echo '<span style="color: green">' . $fileName . ' created</span><br />';
      }
    }

    // Show run time
    $endTime = time();
    $duration = $endTime - $startTime;
    echo '<br />'
       . '<span style="color: grey">Run: ' . date('H:i:s') . ' / ' . $duration . ' second' . ($duration !== 1 ? 's' : '') . '</span>';

  }

  /**
   * Show footer
   */

  showFooter();

?>