<?php

  /**
   * Purpose: Return token or null
   * Input: -
   * Output: <string/null> $token
   */

  $token = $this->getCredentials() ? $this->getCredentials()['access_token'] : null;

?>