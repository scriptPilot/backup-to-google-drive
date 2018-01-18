<?php

  /**
   * Common settings, Google object initialization
   */
  require('common.php');

  /**
   * Purpose: Refresh all tokens in .credentials folder
   */

  // Loop files in folder
  $files = scandir('.credentials');
  foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
      // Extract credentials
      $content = file_get_contents('.credentials/' . $file);
      preg_match('/\/\/credentials:(.+)\\n/', $content, $search);
      $credentials = json_decode(trim($search[1]), true);
      // Get new credentials
      $auth->setCredentials($credentials);
      $auth->refreshToken();
      $newCredentials = $auth->getCredentials();
      $credentialsFile = '.credentials/' . $auth->getUserInfo()['id']. '.php';
      $content = '<?php'. "\n"
               . "\n"
               . '  //credentials:' . json_encode($auth->getCredentials()) . "\n"
               . "\n"
               . '?>' . "\n";
      if (!is_dir('.credentials')) mkdir('.credentials');
      file_put_contents($credentialsFile, $content);
    }
  }

?>