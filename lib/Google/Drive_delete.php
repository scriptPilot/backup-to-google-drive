<?php

  /**
   * Purpose: Delete file or folder
   * Input: <string> $id
   * Output: <array> $folder or false
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');

  // Set correct mime type
  $properties['mimeType'] = 'application/vnd.google-apps.folder';

  // Create REST URI
  $restUri = 'https://www.googleapis.com/drive/v3/files/' . $id
           . '?access_token=' . $this->token;

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $restUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_CUSTOMREQUEST => 'DELETE'
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    $deleted = true;
  } else {
    $deleted = false;
  }
  curl_close($curl);

?>