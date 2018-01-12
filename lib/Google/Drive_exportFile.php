<?php

  /**
   * Purpose: Export file
   * Input: <string> $id, <string> $mimeType
   * Output: <string> $content
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');
  if (!is_string($mimeType)) throw new Exception('Argument $mimeType must be a string');

  // Create REST URI
  $restUri = 'https://www.googleapis.com/drive/v3/files/' . $id . '/export'
           . '?mimeType=' . urlencode($mimeType)
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
  } else {
    $content = false;
  }
  curl_close($curl);

?>