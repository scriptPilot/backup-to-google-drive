<?php

  /**
   * Purpose: Liste files with parameters
   * Input: <array> options
   * Output: <array> files
   */

  // Check input
  if (!is_array($parameters)) throw new Exception('Argument $parameters must be a string');

  // Create REST URI
  $baseUri = 'https://www.googleapis.com/drive/v3/files';
  $params = $parameters;
  if (!isset($params['fields'])) $params['fields'] = 'files(' . $this->fields . ')';
  $params['access_token'] = $this->token;
  $restUri = $baseUri . '?' . http_build_query($params);

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $restUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_HEADERS
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    $files = json_decode($response, true)['files'];
  }
  curl_close($curl);

?>