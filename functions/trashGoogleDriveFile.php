<?php

  /**
   * Purpose:  Trash file in Google Drive
   * Input:    <string> $fileId
   * Output:   <boolean>
   * Requires: $_SESISON['GOOGLE_TOKEN']
   *           php-curl-class/php-curl-class
   */

  function trashGoogleDriveFile($fileId) {

    // Check arguments
    if (!isset($_SESSION['GOOGLE_TOKEN'])) throw new Exception('deleteGoogleDriveFile() requires $_SESSION[\'GOOGLE_TOKEN\']');
      else if (!is_string($fileId)) throw new Exception('deleteGoogleDriveFile() requires a string as first argument');

    // Create REST URL
    $url = 'https://www.googleapis.com/drive/v3/files/' . $fileId
         . '?oauth_token=' . $_SESSION['GOOGLE_TOKEN']['access_token'];

    // Perform request
    $curl = new \Curl\Curl();
    $curl->delete($url);

    // Return result
    return $curl->error ? false : true;

  }

?>