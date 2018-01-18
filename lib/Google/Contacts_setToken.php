<?php

  /**
   * Purpose: Set token for Photos REST API
   * Input: <string> $token
   * Output: -
   */

  // Check input
  if (!is_string($token) && $token !== null) throw new Exception('Argument $token must be a string or null');

  // Update property
  $this->token = $token;

?>
