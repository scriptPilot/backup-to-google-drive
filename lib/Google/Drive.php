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

    private function getMimeType($fileName) { require(__DIR__ . '/Drive_getMimeType.php'); return $mimeType; }

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
    public function updateFolder($id, $properties) { require(__DIR__ . '/Drive_updateFolder.php'); return $folder; }
    public function createFile($properties, $content) { require(__DIR__ . '/Drive_createFile.php'); return $file; }
    public function updateFile($id, $properties, $content = null) { require(__DIR__ . '/Drive_updateFile.php'); return $file; }
    public function ensureFolder($properties) { require(__DIR__ . '/Drive_ensureFolder.php'); return $folder; }
    public function getFile($id) { require(__DIR__ . '/Drive_getFile.php'); return $file; }
    public function downloadFile($id) { require(__DIR__ . '/Drive_downloadFile.php'); return $content; }
    public function exportFile($id, $mimeType) { require(__DIR__ . '/Drive_exportFile.php'); return $content; }
    public function trash($id) { require(__DIR__ . '/Drive_trash.php'); return $file; }
    public function untrash($id) { require(__DIR__ . '/Drive_untrash.php'); return $file; }
    public function emptyTrash() { require(__DIR__ . '/Drive_emptyTrash.php'); return $trashed; }
    public function delete($id) { require(__DIR__ . '/Drive_delete.php'); return $deleted; }


  }

?>