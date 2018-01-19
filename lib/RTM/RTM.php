<?php

  /**
   * Purpose: Simplify Remember The Milk API usage
   * Requires: <constant> RTM_API_KEY, <constant> RTM_SHARED_SECRET
   */

  namespace RTM;

  class RTM {

    /**
     * Private properties
     */

    private $permission;
    private $credentials;

    /**
     * Private functions
     */

    private function getRestUri($baseUri, $parameters, $sign = true) { require(__DIR__ . '/RTM_getRestUri.php'); return $restUri; }
    private function getSignature($parameters) { require(__DIR__ . '/RTM_getSignature.php'); return $signature; }

    /**
     * Public functions
     */

    public function __construct($permission = 'delete') { require(__DIR__ . '/RTM_construct.php'); }
    public function setCredentials($credentials) { require(__DIR__ . '/RTM_setCredentials.php'); }
    public function getCredentials() { require(__DIR__ . '/RTM_getCredentials.php'); return $credentials; }
    public function getAuthUri() { require(__DIR__ . '/RTM_getAuthUri.php'); return $authUri; }
    public function getUserInfo() { require(__DIR__ . '/RTM_getUserInfo.php'); return $userInfo; }
    public function getRequestResponse($parameters) { require(__DIR__ . '/RTM_getRequestResponse.php'); return $response; }

  }

?>