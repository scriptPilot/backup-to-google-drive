<?php

  /**
   * Purpose: Return array with user info or null
   * Input: -
   * Output: <array> $userInfo or <null>
   */

  // Credentials with user info found
  if (isset($this->credentials['user'])) {
    $userInfo = $this->credentials['user'];

  // User info not found
  } else {
    $userInfo = null;
  }

?>