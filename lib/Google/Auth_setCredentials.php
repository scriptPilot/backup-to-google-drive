<?php

  /**
   * Purpose: Set credentials (access_token, refresh_token etc.)
   * Input: <array> $credentials
   * Output: -
   */

  // Check arguments
  if (!is_array($credentials)) throw new Exception('argument token must be an array');
  if (!isset($credentials['access_token'])) throw new Exception('argument token must have property access_token');
  if (!isset($credentials['refresh_token'])) throw new Exception('argument token must habe property refresh_token');

  // Update token property
  $this->credentials = $credentials;

  // Update token in session
  $_SESSION['GOOGLE_CREDENTIALS'] = $credentials;

?>