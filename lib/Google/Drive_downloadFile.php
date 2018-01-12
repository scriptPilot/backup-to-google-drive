<?php

  /**
   * Purpose: Get file content
   * Input: <string> $id
   * Output: <array> $file
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');

  // Create REST URI
  $restUri = 'https://www.googleapis.com/drive/v3/files/' . $id
           . '?alt=media'
           . '&access_token=' . $this->token;

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $restUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_FOLLOWLOCATION => true
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    $content = $response;
  }
  curl_close($curl);

?>