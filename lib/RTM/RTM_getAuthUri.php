<?php

  /**
   * Purpose: Return URI to RTM sign-in page
   * Input: -
   * Output: <string> $authUri
   */

  // Base URI
  $baseUri = 'https://www.rememberthemilk.com/services/auth/';

  // Create authentication URI
  $authUri = $this->getRestUri($baseUri, ['perms' => $this->permission]);

?>