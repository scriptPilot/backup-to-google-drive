<?php

  /**
   * Purpose: Return array with files of Google Drive folder
   * Input: <string> $folderId
   * Output: <array>
   * Requires: $_SESSION['token']
   */

  function getGoogleDriveFiles($folderId, $recursive = false) {

    // Chech arguments
    if (!isset($_SESSION['GOOGLE_TOKEN'])) throw new Exception('getGoogleDriveFiles() requires $_SESSION[\'GOOGLE_TOKEN\']');
      else if (!is_string($folderId)) throw new Exception('getGoogleDriveFiles() requires a string as first argument');
      else if (!is_bool($recursive)) throw new Exception('getGoogleDriveFiles() requires true or false as seconds argument');

    // Define recursive function
    function getDriveFiles($folderId, $recursive, $pageToken = null) {
      $url = 'https://www.googleapis.com/drive/v3/files';
      $params = [
        'q' => 'trashed=false and parents="' . $folderId . '"',
        'fields' => 'nextPageToken,files(id, name, description, mimeType)',
        'orderBy' => 'name',
        'pageSize' => 1000
      ];
      if ($pageToken !== null) $params['pageToken'] = $pageToken;
      $filesInGoogleDrive = performGoogleRequest($url, $params);
      $files = [];
      foreach ($filesInGoogleDrive['files'] as $file)  {
        $isFolder = $file['mimeType'] === 'application/vnd.google-apps.folder';
        $subFiles = $isFolder && $recursive === true ? getDriveFiles($file['id'], true) : [];
        $files[$file['id']] = [
          'id' => $file['id'],
          'name' => $file['name'],
          'description' => $file['description'],
          'mimeType' => $file['mimeType'],
          'isFolder' => $isFolder,
          'files' => $subFiles
        ];
      }
      if (isset($filesInGoogleDrive['nextPageToken'])) {
        return array_merge($files, getDriveFiles($folderId, $recursive, $filesInGoogleDrive['nextPageToken']));
      } else {
        return $files;
      }
    }

    // Return
    return getDriveFiles($folderId, $recursive);

  }

?>