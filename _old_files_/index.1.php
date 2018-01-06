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
  while ($file = readdir($functionsDir)) if ($file !== '.' && $file !== '..') require_once($file);

  /**
   * Init Google client
   */

  $googleClient = createGoogleClient(['https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/drive']);

  /**
   * Show form
   */

  echo '<form method="post" action="index.php">'
     . '  Destination folder: &nbsp; <input type="text" name="destinationFolder" value="' . $_POST['destinationFolder'] . '" /> &nbsp; <input type="submit" value="Start backup" />'
     . '</form>';

  /**
   * Do sync
   */

  // Check destination folder input
  $destinationFolder = isset($_POST['destinationFolder']) ? trim($_POST['destinationFolder']) : '';
  if (isset($_POST['destinationFolder']) && $destinationFolder === '') echo '<span style="color: red">Please type the destination folder</span><br /><br />';

  // Destination folder is provided
  if ($destinationFolder !== '') {

    // Get contacts
    $url = 'https://people.googleapis.com/v1/people/me/connections';
    $params = ['personFields' => 'names,phoneNumbers,emailAddresses,addresses,birthdays,photos', 'pageSize' => 3];
    $response = performGoogleRequest($url, $params);
    $vcards = [];
    if ($response !== false) {
      foreach ($response['connections'] as $contact) {

        // Create new vCard
        $vcard = new JeroenDesloovere\VCard\VCard();

        // Add name
        $givenName = $contact['names'][0] ? $contact['names'][0]['givenName'] : '';
        $familyName = $contact['names'][0] ? $contact['names'][0]['givenName'] : '';
        $displayName = $contact['names'][0] ? $contact['names'][0]['displayName'] : '';
        $vcard->addName($familyName, $givenName);

        // Add phone numbers
        $phoneNumbers = [];
        if (isset($contact['phoneNumbers'])) {
          foreach ($contact['phoneNumbers'] as $number) {
            $vcard->addPhoneNumber($number['canonicalForm']);
          }
        }

        // Add email addresses
        $emailAddresses = [];
        if (isset($contact['emailAddresses'])) {
          foreach ($contact['emailAddresses'] as $email) {
            $vcard->addEmail($email['value']);
          }
        }

        // Add addresses
        $addresses = [];
        if (isset($contact['addresses'])) {
          foreach ($contact['addresses'] as $address) {
            $street = $address['streetAddress'] ? $address['streetAddress'] : '';
            $postalCode = $address['postalCode'] ? $address['postalCode'] : '';
            $city = $address['city'] ? $address['city'] : '';
            $vcard->addAddress(null, null, $street, $city, null, $postcode, null);
          }
        }

        // Get birthday
        $date = $contact['birthdays'][0] ? $contact['birthdays'][0]['date'] : [];
        if ($date['day'] && $date['month'] && $date['year']) $vcard->addBirthday($date['year'] . '-' . $date['month'] . '-' . $date['day']);
         else if ($date['day'] && $date['month']) $vcard->addBirthday('--' . $date['month'] . '-' . $date['day']);

        // Get (bigger) photo
        if ($contact['photos'][0] && strpos($contact['photos'][0]['url'], '_8B/') === false) {
          $url = str_replace('/s100/', '/s512/', $contact['photos'][0]['url']);
          $vcard->addPhoto($url);
        }

        // Add vcard to array
        $vcards[$displayName . '.vcf'] = $vcard;

      }
    }

    // Define destination folder
    $folderId = null;

    // Search for existing folder
    $folders = $googleDriveService->files->listFiles([
      'fields' => 'files(id, name)',
      'q' => 'trashed=false and parents="root" and mimeType="application/vnd.google-apps.folder"'
    ]);
    foreach ($folders as $folder) {
      if ($folder->name === $destinationFolder) $folderId = $file->id;
    }

    // Create new folder
    if ($folderId === null) {
      $create = $googleDriveService->files->create([
        'mimeType' => 'application/vnd.google-apps.folder',
        'name' => $destinationFolder,
        'fields' => 'id'
      ]);
      $folderId = $create->id;
    }

    // Load files in folder

    $googleDriveService = new Google_Service_Drive($googleClient);

    $files = $googleDriveService->files->listFiles([
      'pageSize' => 3,
      'fields' => 'kind,files(name)'
    ]);
    foreach ($files as $file) echo $file->name . '<br />';


    // Loop vcards
    $googleDriveService = new Google_Service_Drive($googleClient);
    foreach ($vcards as $fileName => $vcard) {

      // Save vcard to Google Drive


      $meta = new Google_Service_Drive_DriveFile([
        'name' => $fileName,
        'parents' => [$folderId]
      ]);
      $content = $vcard->getOutput();
      $file = $googleDriveService->files->create($meta, [
        'data' => $content,
        'mimeType' => 'text/x-vCard',
        'uploadType' => 'multipart',
        'fields' => 'id'
      ]);


      $url = 'https://www.googleapis.com/upload/drive/v3/files';
      $params = ['personFields' => 'names,phoneNumbers,emailAddresses,addresses,birthdays,photos', 'pageSize' => 3];
      $response = performGoogleRequest($url, $params);


      //echo $fileName . ' created<br />';

    }

  }

  echo 'Run: ' . date('H:i:s');

  // Include footer
  require(__DIR__ . '/footer.php');
*/
?>