<?php

  /**
   * Purpose: Handle submitted code by Google
   */

  // Code submitted
  if (isset($_GET['code'])) {

    // Clean code
    $code = trim($_GET['code']);

    // Create credentials uri
    $credentialsUri = 'https://www.googleapis.com/oauth2/v4/token';

    // Create credentials fields
    $credentialsFields = [
      'code' => $code,
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'redirect_uri' => $this->redirectUri,
      'grant_type' => 'authorization_code'
    ];

    // Perform cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $credentialsUri,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => http_build_query($credentialsFields)
    ]);
    $response = curl_exec($curl);
    if ($response !== false) {
      // Get credentials as array
      $credentials = json_decode($response, true);
      // Update token
      $this->setCredentials($credentials);
      // Reload redirect URI
      header('Location: ' . $this->redirectUri);
    }
    curl_close($curl);

  }

?>