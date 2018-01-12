<?php

  /**
   * Purpose: Simplify Google Photos REST API usage
   */

  namespace Google;

  class Photos {

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
    public function setToken($token) { require(__DIR__ . '/Photos_setToken.php'); }
    public function getAlbums() { require(__DIR__ . '/Photos_getAlbums.php'); return $albums; }
    public function getPhotos($albumId) { require(__DIR__ . '/Photos_getPhotos.php'); return $photos; }

  }

?>