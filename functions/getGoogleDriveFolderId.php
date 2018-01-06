<?php

  /**
   * Purpose: Get Google Drive folder ID, based on path name
   * Input: <string> $path (eg. "Folder/Sub Folder")
   *        <boolean> $create (if not found)
   * Output: <string> ID or false
   * Requires: performGoogleRequest() function
   */

  function getGoogleDriveFolderId($path, $create = false) {

    // Check argument
    if (!is_string($path)) throw new Exception('getGoogleDriveFolderId() requires a string as first argument');
      else if (!is_bool($create)) throw new Exception('getGoogleDriveFolderId() requires true or false as second argument');

    // Define function to get folder id recursive
    function getFolderId($path, $create, $parent = 'root') {

      // Split path if not done before
      if (!is_array($path)) $path = explode('/', $path);

      // Search for current folder
      $folderName = array_shift($path);
      $folderId = null;
      $params = [
        'q' => 'trashed=false and parents="' . $parent . '" and mimeType="application/vnd.google-apps.folder"',
        'fields' => 'files(id, name)'
      ];
      $filesResult = performGoogleRequest('https://www.googleapis.com/drive/v3/files', $params);
      foreach ($filesResult['files'] as $file) if ($file['name'] === $folderName) $folderId = $file['id'];

      // Folder not found, but should be created
      if ($folderId === null && $create === true) {
        $folderId = createGoogleDriveFolder($folderName, $parent);
      }

      // Folder Id still unknown
      if ($folderId === null) return false;

      // Sub folders exists
      else if (count($path) > 0) return getFolderId($path, $create, $folderId);

      // Return this id
      else return $folderId;

    }

    // Return folder id or false
    return getFolderId($path, $create);

  }

?>