<?php

  /**
   * Purpose: Liste files with parameters
   * Input: <array> options
   * Output: <array> files
   */

  // Check input
  if (!is_array($parameters)) throw new Exception('Argument $parameters must be an array');

  // Create REST URI
  $baseUri = 'https://www.googleapis.com/drive/v3/files';
  $params = $parameters;
  if (!isset($params['fields'])) $params['fields'] = 'files(' . $this->fields . ')';
  if (!isset($params['pageSize']) && strpos($params['fields'], 'nextPageToken') === false) {
    $params['fields'] = 'nextPageToken,' . $params['fields']; // search for all entries if the user not limits
    $params['pageSize'] = 1000; // max 1000
  }
  $params['access_token'] = $this->token;

  // Get all pages
  $allPages = false;
  $nextPageToken = false;
  $allFiles = [];

  while ($allPages === false && $allFiles !== false) {

    if ($nextPageToken !== false) $params['pageToken'] = $nextPageToken;

    $restUri = $baseUri . '?' . http_build_query($params);

    // Perform cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $restUri,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true
    ]);
    $response = curl_exec($curl);
    if ($response !== false) {
      $json = json_decode($response, true);
      if (isset($json['nextPageToken'])) $nextPageToken = $json['nextPageToken'];
      else $allPages = true;
      $allFiles = array_merge($allFiles, $json['files']);
    } else {
      $allFiles = false;
    }

    curl_close($curl);

  }

  $files = $allFiles;

?>