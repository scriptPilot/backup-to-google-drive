<?php

  /**
   * Purpose: Return URI to GitHub sign-in page
   * Input: -
   * Output: <string> $authUri
   */

  // Create authentication URI
  $authUri = 'https://github.com/login/oauth/authorize'
           . '?client_id=' . GITHUB_CLIENT_ID
           . '&scope=user,repo'
           . '&redirect_uri=' . urlencode(GITHUB_REDIRECT_URI);

?>