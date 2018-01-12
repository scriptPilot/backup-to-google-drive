<?php

  /**
   * Purpose: Return token or null
   * Input: -
   * Output: <string> token or null
   */

  $token = is_array($this->credentials) ? $this->credentials['access_token'] : null;

?>