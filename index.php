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
   * Show logout link
   */

  echo '<a href="index.php?googleLogout">Google logout</a><br />'
     . '<br />';

  /**
   * Show form
   */

  echo '<form method="post" action="index.php">'
     . '  Destination folder: &nbsp; <input type="text" name="destinationFolder" value="' . (isset($_POST['destinationFolder']) ? $_POST['destinationFolder'] : DEFAULT_BACKUP_FOLDER_CONTACTS) . '" /> &nbsp; <input type="submit" value="Start backup" onclick="this.value=\'Please wait ...\'" />'
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
    $params = [
      'personFields' => 'names,phoneNumbers,emailAddresses,addresses,birthdays,photos,biographies',
      'sortOrder' => 'FIRST_NAME_ASCENDING',
      'pageSize' => 1000
    ];
    $contacts = performGoogleRequest($url, $params);
    $contacts = $contacts['connections'];

    // Get identifiers from contacts (people/{id}/{etag})
    $contactsIdents = [];
    foreach ($contacts as $contact)  {
      $contactsIdents[] = $contact['resourceName'] . '/' . $contact['etag'];
    }

    // Get Google Drive folder id
    $folderId = getGoogleDriveFolderId($destinationFolder, true);

    // Get identifiers from Google Drive (page by page)
    function getDriveIdents($folderId, $pageToken = null) {
      $url = 'https://www.googleapis.com/drive/v3/files';
      $params = [
        'q' => 'trashed=false and parents="' . $folderId . '"',
        'fields' => 'nextPageToken,files(id, name, description)',
        'orderBy' => 'name',
        'pageSize' => 1000
      ];
      if ($pageToken !== null) $params['pageToken'] = $pageToken;
      $filesInGoogleDrive = performGoogleRequest($url, $params);
      $driveIdents = [];
      foreach ($filesInGoogleDrive['files'] as $file)  {
        $driveIdents[$file['id']] = [
          'id' => $file['id'],
          'name' => $file['name'],
          'ident' => substr($file['description'], 0, 7) === 'people/' ? $file['description'] : ''
        ];
      }
      if (isset($filesInGoogleDrive['nextPageToken'])) {
        return array_merge($driveIdents, getDriveIdents($folderId, $filesInGoogleDrive['nextPageToken']));
      } else {
        return $driveIdents;
      }
    }
    $driveIdents = getDriveIdents($folderId);

    // Remove duplicated files (remove the identifier, so they will be trashed later on)
    $uniqueDriveIdents = [];
    foreach ($driveIdents as $fileId => $file) {
      if (in_array($file['ident'], $uniqueDriveIdents)) {
        $driveIdents[$fileId]['ident'] = '';
      } else {
        $uniqueDriveIdents[] = $file['ident'];
      }
    }

    // Remove additional files (ident not found in contacts anymore)
    foreach ($driveIdents as $file) {
      if (!in_array($file['ident'], $contactsIdents)) {
        $trashed = trashGoogleDriveFile($file['id']);
        if ($trashed) echo '<span style="color: orange">' . $file['name'] . ' trashed</span><br />';
        else echo '<span color="red">Failed to trash ' . $file['name'] . '</span><br />';
      }
    }

    // Create missing files
    foreach ($contacts as $contact) {
      $ident = $contact['resourceName'] . '/' . $contact['etag'];
      if (!in_array($ident, $uniqueDriveIdents) && (isset($contact['names'][0]['displayName']) or isset($contact['emailAddresses'][0]['value']))) {
        $fileName = (isset($contact['names'][0]['displayName']) ? $contact['names'][0]['displayName'] : $contact['emailAddresses'][0]['value']) . '.vcf';
        $vCard = convertGoogleContactToVCard($contact);
        $upload = uploadFileToGoogleDrive($googleDriveService, $folderId, $fileName, $vCard, $contact['resourceName'] . '/' . $contact['etag']);
        if ($upload !== false) echo '<span style="color: green">' . $fileName . ' created</span><br />';
        else echo '<span style="color: red">Failed to upload ' . $fileName . '</span><br />';
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