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
   * Init Google client
   */

  $googleClient = createGoogleClient(['https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/drive']);

  /**
   * Show header
   */

  showHeader();

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

    // Remember start time
    $startTime = time();

    // Load contacts
    $url = 'https://people.googleapis.com/v1/people/me/connections';
    $params = ['personFields' => 'names,phoneNumbers,emailAddresses,addresses,birthdays,photos', 'pageSize' => 3];
    $contacts = performGoogleRequest($url, $params);

    // Get Google Drive folder id
    $folderId = getGoogleDriveFolderId($destinationFolder, true);
    show($folderId);


    /*
    foreach ($contacts['connections'] as $contact) {
      $vCard = convertGoogleContactToVCard($contact);
      echo $vCard . '<hr />';
    }
    */

    // Show run time
    $endTime = time();
    $duration = $endTime - $startTime;
    echo '<span style="color: grey">Run: ' . date('H:i:s') . ' / ' . $duration . ' second' . ($duration !== 1 ? 's' : '') . '</span>';

  }

  /**
   * Show footer
   */

  showFooter();

?>