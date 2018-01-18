<?php

  /**
   * Purpose: Create file
   * Input: <array> $properties, <string> $content
   * Output: <array> $file
   */

  // Check input
  if (!is_string($content)) throw new Exception('Argument $content must be a string');
  if (!is_array($properties)) throw new Exception('Argument $properties must be an array');
  if (!is_string($properties['name'])) throw new Exception('Argument $properties[\'name\'] must be a string');

  // Try to get mime type automatically from file extension
  if (!isset($properties['mimeType'])) {
    $mimeType = $this->getMimeType($properties['name']);
    if ($mimeType) $properties['mimeType'] = $mimeType;
    else throw new Exception('Argument $properties[\'mimeType\'] must be a string');
  }

  // Create REST URI
  $restUri = 'https://www.googleapis.com/upload/drive/v3/files'
           . '?access_token=' . $this->token
           . '&uploadType=multipart';

  // Create body (double quotation marks and \r\n is important!)
  $boundary = "---------------------" . md5(mt_rand() . microtime());
  $body = "--" . $boundary . "\r\n"
        . "Content-Type: application/json; charset=UTF-8\r\n"
        . "\r\n"
        . json_encode($properties) . "\r\n"
        . "\r\n"
        . "--" . $boundary . "\r\n"
        . "Content-Type: " . $properties['mimeType'] . "\r\n"
        . "\r\n"
        . $content . "\r\n"
        . "--" . $boundary . "--";

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $restUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: multipart/related; boundary=' . $boundary,
      'Content-Length: ' . strlen($body)
    ],
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $body
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    $file = json_decode($response, true);
    $this->touch($file['id']);
  } else {
    $file = false;
  }
  curl_close($curl);

?>