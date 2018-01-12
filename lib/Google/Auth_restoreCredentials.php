<?php

  /**
   * Purpose: Restore credentials after page reload
   */

  if (isset($_SESSION['GOOGLE_CREDENTIALS'])) {
    $this->setCredentials($_SESSION['GOOGLE_CREDENTIALS']);
  }

?>