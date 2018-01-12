<?php // Purpose: Handle Google OAuth2

  namespace Google;

  class Auth {

    /**
     * Private properties
     */

    private $clientId;
    private $redirectUri;
    private $scope;
    private $credentials;

    /**
     * Private methods
     */

    private function restoreCredentials() { require(__DIR__ . '/Auth_restoreCredentials.php'); }
    private function handleSubmittedCode() { require(__DIR__ . '/Auth_handleSubmittedCode.php'); }
    private function getAuthUri() { require(__DIR__ . '/Auth_getAuthUri.php'); return $authUri; }

    /**
     * Public methods
     */

    public function __construct($clientId, $clientSecret, $redirectUri) {
      if (!$_SESSION) session_start();
      require(__DIR__ . '/Auth_construct.php');
      $this->restoreCredentials();
      $this->handleSubmittedCode();
    }
    public function addScope($scope) { require(__DIR__ . '/Auth_addScope.php'); }
    public function setCredentials($credentials) { require(__DIR__ . '/Auth_setCredentials.php'); }
    public function getCredentials() { require(__DIR__ . '/Auth_getCredentials.php'); return $credentials; }
    public function getToken() { require(__DIR__ . '/Auth_getToken.php'); return $token; }
    public function signIn() { require(__DIR__ . '/Auth_signIn.php'); }
    public function signOut() { require(__DIR__ . '/Auth_signOut.php'); }

  }

?>