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
    '.json' => 'application/json',
    '.vcf' => 'text/x-vcard',
    '.pdf' => 'application/pdf',
    '.zip' => 'application/zip'
  ];
  if (array_key_exists(substr($fileName, -5), $mimeTypes)) $mimeType = $mimeTypes[substr($fileName, -5)];
  else if (array_key_exists(substr($fileName, -4), $mimeTypes)) $mimeType = $mimeTypes[substr($fileName, -4)];
  else $mimeType = null;

?>