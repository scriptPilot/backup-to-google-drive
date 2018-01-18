<?php

  /**
   * Purpose: Get new credentials with refresh token
   */

  // Create credentials uri
  $credentialsUri = 'https://www.googleapis.com/oauth2/v4/token';

  // Create credentials fields
  $credentialsFields = [
    'client_id' => $this->clientId,
    'client_secret' => $this->clientSecret,
    'grant_type' => 'refresh_token',
    'refresh_token' => $this->getCredentials()['refresh_token']
  ];

  var_dump($credentialsUri);

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $credentialsUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query($credentialsFields),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    // Get credentials as array
    $credentials = json_decode($response, true);
    // Copy existing refresh token
    $credentials['refresh_token'] = $this->getCredentials()['refresh_token'];
    // Update token
    $this->setCredentials($credentials);
  } else {
    echo curl_error($curl);
    $credentials = false;
  }
  curl_close($curl);

?>