<?php

  /**
   * Purpose: Provide MimeType by file exstension
   * Input: <string> $fileName
   * Output: <string> $mimeType or null
   */

  // Check input
  if (!is_string($fileName)) throw new Exception('Argument $fileName must be a string');

  $mimeTypes = [
    '.jpg' => 'image/jpeg',
    '.png' => 'image/png',
    '.gif' => 'image/gif',
    '.txt' => 'text/plain',
    '.vcf' => 'text/x-vcard',
    '.pdf' => 'application/pdf'
  ];
  $ext = substr($fileName, -4);
  if (array_key_exists($ext, $mimeTypes)) $mimeType = $mimeTypes[$ext];
  else $mimeType = null;

?>