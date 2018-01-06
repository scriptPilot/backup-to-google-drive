<?php

  /**
   * Purpose: Upload file to Google Drive
   * Input: <object> $googleDriveService
   *        <string> $parent
   *        <string> $name
   *        <string> $content
   *        <string> $description
   * Output: <string> ID or false
   */

  function uploadFileToGoogleDrive($googleDriveService, $parent, $name, $content, $description = '') {

    // Check arguments
    if (!isset($_SESSION['GOOGLE_TOKEN'])) throw new Exception('uploadFileToGoogleDrive() requires $_SESSION[\'GOOGLE_TOKEN\']');
      else if (!is_object($googleDriveService)) throw new Exception('uploadFileToGoogleDrive() requires an object as first argument');
      else if (!is_string($parent)) throw new Exception('uploadFileToGoogleDrive() requires a string as second argument');
      else if (!is_string($name)) throw new Exception('uploadFileToGoogleDrive() requires a string as third argument');
      else if (!is_string($content)) throw new Exception('uploadFileToGoogleDrive() requires a string as fourth argument');
      else if (!is_string($description)) throw new Exception('uploadFileToGoogleDrive() requires a string as fifths argument');

    // Define meta data and body
    $meta = new Google_Service_Drive_DriveFile([
      'name' => $name,
      'parents' => [$parent]
    ]);
    if ($description !== '') $meta->setDescription($description);
    $body = [
      'data' => $content,
      'uploadType' => 'multipart',
      'fields' => 'id'
    ];

    // Proceed upload
    $upload = $googleDriveService->files->create($meta, $body);

    // Return file id
    return $upload->id;

  }

?>