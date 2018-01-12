<?php

  /**
  * Purpose: Handle Google OAuth2 sign-in process
  * Note: No browser output allowed before
  */

  // Get auth URI
  $authUri = $this->getAuthUri();

  // Forward to Google sign-in page
  header('Location: '  . $authUri);

?>