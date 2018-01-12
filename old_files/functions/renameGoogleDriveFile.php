<?php

  /**
   * Purpose:  Rename file in Google Drive
   * Input:    <string> $fileId
   *           <string> $name
   * Output:   <boolean>
   * Requires: $_SESISON['GOOGLE_TOKEN']
   *           php-curl-class/php-curl-class
   */

  function renameGoogleDriveFile($fileId, $name) {

    // Check arguments
    if (!isset($_SESSION['GOOGLE_TOKEN'])) throw new Exception('renameGoogleDriveFile() requires $_SESSION[\'GOOGLE_TOKEN\']');
      else if (!is_string($fileId)) throw new Exception('renameGoogleDriveFile() requires a string as first argument');
      else if (!is_string($name)) throw new Exception('renameGoogleDriveFile() requires a string as second argument');

    // Create REST URL
    $url = 'https://www.googleapis.com/drive/v2/files/' . $fileId
         . '?oauth_token=' . $_SESSION['GOOGLE_TOKEN']['access_token'];

    // Create body
    $body = ['title' => $name, 'fields' => 'id'];

    // Perform request
    $curl = new \Curl\Curl();
    $curl->setHeader('Content-Type', 'application/json');
    $curl->patch($url, $body);

    // Return result
    return $curl->error ? false : true;

  }

?>