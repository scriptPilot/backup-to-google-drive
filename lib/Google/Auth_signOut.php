<?php

  /**
   * Purpose: Sign out from Google (reset token) but without revoking the granted access
   */

  // Reset property
  $this->credentials = null;

  // Reset session
  unset($_SESSION['GOOGLE_CREDENTIALS']);

  // Reload redirect URI
  header('Location: ' . $this->redirectUri);

?>