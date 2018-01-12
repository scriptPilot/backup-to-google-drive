<?php

  /**
   * Purpose: Set token for Photos REST API
   * Input: <string> $token
   * Output: -
   */

  // Check input
  if (!is_string($token)) throw new Exception('Argument $token must be a string');

  // Update property
  $this->token = $token;

?>
