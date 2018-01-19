<?php

  /**
   * Purpose: Perform API request and return response
   * Input: <string> $path
   * Output: <array> $response
   */

  // Check arguments
  if (!is_string($path)) throw new Exception('Argument $path must be a string');

  // No access token
  if (!is_array($this->getCredentials()) or !isset($this->getCredentials()['access_token'])) {
    $response = null;

  // Access token
  } else {

    // Get REST URI
    $restUri = 'https://api.github.com/' . $path;

    // Perform cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $restUri,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => [
        'User-Agent: request',
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . $this->getCredentials()['access_token']
      ]
    ]);
    $response = curl_exec($curl);
    if ($response !== false) {
      $response = json_decode($response, true);
    } else {
      $response = false;
    }
    curl_close($curl);

  }

?>