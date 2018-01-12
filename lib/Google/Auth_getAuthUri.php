<?php

  /**
   * Purpose: Create authentication URI (Google sign-in page)
   * Input: -
   * Output: <string> $authUri
   */

  // Check if scope is added
  if (!$this->scope) throw new Error('Please add one or more scope first');

  // Define base URI
  $baseUri = 'https://accounts.google.com/o/oauth2/v2/auth';

  // Define required parameters
  $params = [
    'client_id' => $this->clientId,
    'redirect_uri' => $this->redirectUri,
    'scope' => implode($this->scope, ' '),
    'access_type' => 'offline',
    'prompt' => 'consent',
    'response_type' => 'code'
  ];

  // Create auth URI
  $authUri = $baseUri;

  // Add params
  foreach ($params as $key => $val) {
    $connector = strpos($authUri, '?') === false ? '?' : '&';
    $authUri = $authUri . $connector . $key . '=' . urlencode($val);
  }

?>