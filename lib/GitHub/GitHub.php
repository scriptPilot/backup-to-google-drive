<?php

  /**
   * Purpose: Simplify GitHub API usage
   * Requires: <constant> GITHUB_CLIENT_ID, <constant> GITHUB_CLIENT_SECRET, <constant> GITHUB_REDIRECT_URI
   */

  namespace GitHub;

  class GitHub {

    /**
     * Private properties
     */

    private $credentials;
    private $userInfo;

    /**
     * Private functions
     */

    private function handleSubmittedCode() { require(__DIR__ . '/GitHub_handleSubmittedCode.php'); }

    /**
     * Public functions
     */

    public function __construct() { require(__DIR__ . '/GitHub_construct.php'); }
    public function setCredentials($credentials) { require(__DIR__ . '/GitHub_setCredentials.php'); }
    public function getCredentials() { require(__DIR__ . '/GitHub_getCredentials.php'); return $credentials; }
    public function getAuthUri() { require(__DIR__ . '/GitHub_getAuthUri.php'); return $authUri; }
    public function get($path) { require(__DIR__ . '/GitHub_get.php'); return $response; }
    public function getUserInfo() { require(__DIR__ . '/GitHub_getUserInfo.php'); return $userInfo; }

  }

?>