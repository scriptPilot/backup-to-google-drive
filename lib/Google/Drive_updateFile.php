<?php

  /**
   * Purpose: Update file
   * Input: <string> $id, <array> $properties, <string> $content
   * Output: <array> $file
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');
  if (!is_string($content) && $content !== null) throw new Exception('Argument $content must be a string or null');
  if (!is_array($properties)) throw new Exception('Argument $properties must be an array');

  // Get mime type from existing file
  if (!isset($properties['mimeType']) && $content) {
    $properties['mimeType'] = $this->getFile($id)['mimeType'];
  }

  // Create REST URI
  $restUri = 'https://www.googleapis.com/' . ($content ? 'upload' : '') . '/drive/v3/files/' . $id
           . '?access_token=' . $this->token
           . '&uploadType=multipart';

  // Content provided
  if ($content) {

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
      CURLOPT_CUSTOMREQUEST => 'PATCH',
      CURLOPT_POSTFIELDS => $body
    ]);

  // No content provided
  } else {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $restUri,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_CUSTOMREQUEST => 'PATCH',
      CURLOPT_POSTFIELDS => json_encode($properties)
    ]);
  }

  // Handle request response
  $response = curl_exec($curl);
  if ($response !== false) {
    $file = json_decode($response, true);
  } else {
    var_dump(curl_error($curl));
    $file = false;
  }
  curl_close($curl);

?>