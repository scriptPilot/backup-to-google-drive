<?php

  /**
   * Purpose: Start HTML page
   * Prerequisuite: File config.php with constant HTML_TITLE
   */

  function showHeader() {

    // Include configuration file
    require_once(__DIR__ . '/../config.php');

    // Set content type to HTML / UTF-8
    $contentTypeSet = false;
    foreach (headers_list() as $header) {
      if (strtolower(substr($header, 0, 13)) === 'content-type:') $contentTypeSet = true;
    }
    if ($contentTypeSet === false) header('Content-Type: text/html; charset=utf-8');

    // Start HTML page
    echo '<html>'
       . '  <head>'
       . '    <title>' . HTML_TITLE . '</title>'
       . '  </head>'
       . '  <body>';

  }

?>