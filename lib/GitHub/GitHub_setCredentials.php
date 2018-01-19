<?php

  /**
   * Purpose: Set credentials of GitHub instance
   * Input: <string/null> $credentials
   * Output: -
   */

  // Check arguments
  if (!is_array($credentials) && $credentials !== null) throw new Exception('Argument $credentials must be an array or null');

  // Update credentials
  $this->credentials = $credentials;

  // Update user info
  $this->userInfo = $this->get('user');

?>