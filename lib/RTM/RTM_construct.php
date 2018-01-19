<?php

  /**
   * Purpose: Initialize RTM instance
   * Input: <"read"/"write"/"delete"> $permission (default = "delete")
   */

  // Check arguments
  if ($permission !== 'read' && $permission !== 'write' && $permission !== 'delete') throw new Exception('Argument $permission must be "read", "write" or "delete"');

  // Store permission
  $this->permission = $permission;

  // Use given frob to get credentials
  if (isset($_GET['frob'])) {

    // Clean frob
    $frob = trim($_GET['frob']);

    // Get credentials
    $response = $this->getRequestResponse(['method' => 'rtm.auth.getToken', 'frob' => $frob, 'perms' => $this->permission]);

    // Store credentials
    $this->setCredentials($response['auth']);

  }

?>