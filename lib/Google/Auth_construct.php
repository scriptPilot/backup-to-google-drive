<?php

  /**
   * Purpose: Define clientId and redirectUri
   * Input: <string> clientId, <string> clientSecret, <string> redirectUri
   */

  // Check arguments
  if (!is_string($clientId)) throw new Exception('argument clientId must be a string');
  if (!is_string($clientSecret)) throw new Exception('argument clientSecret must be a string');
  if (!is_string($redirectUri)) throw new Exception('argument redirectUri must be a string');

  // Update properties
  $this->clientId = $clientId;
  $this->clientSecret = $clientSecret;
  $this->redirectUri = $redirectUri;

?>