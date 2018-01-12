<?php

  /**
   * Purpose: Create a folder (structure) in Google Drive
   * Input: <string> $id, <array> $properties
   * Output: <array> $folder or false
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');
  if (!is_array($properties)) throw new Exception('Argument $properties must be an array');

  // Set correct mime type
  $properties['mimeType'] = 'application/vnd.google-apps.folder';

  // Create REST URI
  $restUri = 'https://www.googleapis.com/drive/v3/files/' . $id
           . '?access_token=' . $this->token
           . '&uploadType=multipart';

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $restUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_POSTFIELDS => json_encode($properties)
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    $folder = json_decode($response, true);
  } else {
    $folder = false;
  }
  curl_close($curl);

?>