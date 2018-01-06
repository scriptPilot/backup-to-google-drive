<?php

  /**
   * Purpose: Create Google Drive folder
   * Input: <string> $name
   *        <string> $parent
   * Output: <string> id or false
   * Requires: performGoogleRequest() function
   */

  function createGoogleDriveFolder($name, $parent = 'root') {

    $params = [
      'uploadType' => 'multipart',
      'fields' => 'id'
    ];
    $body = [
      'mimeType' => 'application/vnd.google-apps.folder',
      'name' => $name,
      'parents' => [$parent]
    ];
    $createdFolder = performGoogleRequest('https://www.googleapis.com/drive/v3/files', $params, 'POST', $body);
    return $createdFolder['id'];

  }