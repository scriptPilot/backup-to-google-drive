<?php

  /**
   * Purpose: Simplify Google Drive REST API usage
   */

  namespace Google;

  class Drive {

    /**
     * Private properties
     */

    private $token;
    private $fields;

    /**
     * Private methods
     */

    /**
     * Public methods
     */

    public function __construct($token) {
      $this->setToken($token);
      $this->setFields('id,name,description,mimeType');
    }
    public function setFields($fields) { require(__DIR__ . '/Drive_setFields.php'); }
    public function setToken($token) { require(__DIR__ . '/Drive_setToken.php'); }
    public function search($parameters) { require(__DIR__ . '/Drive_search.php'); return $files; }
    public function createFolder($properties) { require(__DIR__ . '/Drive_createFolder.php'); return $folder; }
    public function upload($content, $properties) { require(__DIR__ . '/Drive_upload.php'); return $file; }
    public function ensureFolder($properties) { require(__DIR__ . '/Drive_ensureFolder.php'); return $folder; }
    public function export($id, $mimeType) { require(__DIR__ . '/Drive_export.php'); return $content; }


  }

?>