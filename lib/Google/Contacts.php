<?php

  /**
   * Purpose: Simplify Google Contacts (People) REST API usage
   */

  namespace Google;

  class Contacts {

    /**
     * Private properties
     */

    private $token;

    /**
     * Public methods
     */

    public function __construct($token) {
      $this->setToken($token);
    }
    public function setToken($token) { require(__DIR__ . '/Contacts_setToken.php'); }
    public function getContacts() { require(__DIR__ . '/Contacts_getContacts.php'); return $contacts; }
    public function createVCard($contact) { require(__DIR__ . '/Contacts_createVCard.php'); return $content; }

  }

?>