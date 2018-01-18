<?php

  /**
   * Purpose: Update file/folder updated time
   * Input: <string> $id
   * Output: <array> $file
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');

  // Function to touch file
  if (!function_exists('touchSingleFile')) {
    function touchSingleFile($id, $token) {

      // Create REST URI
      $restUri = 'https://www.googleapis.com/drive/v2/files/' . $id . '/touch/'
               . '?access_token=' . $token;

      // Perform cURL request
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => $restUri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => ['Content-Length: 0']
      ]);

      // Handle request response
      $response = curl_exec($curl);
      if ($response !== false) {
        $file = json_decode($response, true);
        if ($file['parents'][0]['isRoot']) return true;
          else return touchSingleFile($file['parents'][0]['id'], $token);
      } else {
        return false;
      }
      curl_close($curl);

    }
  }

  // Touch first file
  $file = touchSingleFile($id, $this->token);

?>