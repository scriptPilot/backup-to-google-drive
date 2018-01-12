<?php

  /**
   * Purpose: Empty trash
   */

  // Create REST URI
  $restUri = 'https://www.googleapis.com/drive/v3/files/trash'
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
    $trashed = true;
  } else {
    $trashed = false;
  }
  curl_close($curl);

?>