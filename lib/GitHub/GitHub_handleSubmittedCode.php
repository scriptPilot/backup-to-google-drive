<?php

  /**
   * Purpose: Handle submitted code by Google
   */

  // Code submitted
  if (isset($_GET['code']) && strpos($_SERVER['HTTP_REFERER'], 'accounts.google.com') === false) {


    // Clean code
    $code = trim($_GET['code']);

    // Create credentials uri
    $credentialsUri = 'https://github.com/login/oauth/access_token';

    // Create credentials fields
    $credentialsFields = [
      'code' => $code,
      'client_id' => GITHUB_CLIENT_ID,
      'client_secret' => GITHUB_CLIENT_SECRET,
      'redirect_uri' => GITHUB_REDIRECT_URI
    ];

    // Perform cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $credentialsUri,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => http_build_query($credentialsFields),
      CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $response = curl_exec($curl);
    if ($response !== false) {
      // Get credentials as array
      $credentials = json_decode($response, true);
      // Update token
      $this->setCredentials($credentials);
      // Redirect
      //header('Location: ' . GITHUB_REDIRECT_URI);
    }
    curl_close($curl);

  }

?>