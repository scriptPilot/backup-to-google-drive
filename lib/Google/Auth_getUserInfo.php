<?php

  /**
   * Purpose: Get information of signed-in user
   */

  // If user is not signed-in
  if ($this->getToken() === null) {
    $userInfo = null;

  // If user is signed-in
  } else {

    // Create REST URI
    $restUri = 'https://people.googleapis.com/v1/people/me'
             . '?personFields=names,photos'
             . '&access_token=' . $this->getToken();

    // Perform cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $restUri,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true
    ]);
    $response = curl_exec($curl);
    if ($response !== false) {
      $info = json_decode($response, true);
      $userInfo = [
        'id' => substr($info['resourceName'], 7),
        'etag' => $info['etag'],
        'displayName' => $info['names'][0]['displayName'],
        'photoUri' => $info['photos'][0]['url']
      ];
    } else {
      $userInfo = false;
    }
    curl_close($curl);

  }

?>