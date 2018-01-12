<?php

  /**
  * Set content type
  */

  header('Content-type: text/html;charset=utf-8');

  /**
   * Load configuration and classes
   */

  require('config.php');
  require('lib/Google/Auth.php');

  /**
   * Create class instances
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('https://www.googleapis.com/auth/contacts');

  /**
   * Actions
   */

  if ($_GET['action'] === 'signIn') $auth->signIn();
  else if ($_GET['action'] === 'signOut') $auth->signOut();
  else if (!$auth->getToken()) echo '<p><a href="?action=signIn">Sign-in to Google</a></p>';
  else {
    // Show sign-out link
    echo '<p><a href="?action=signOut">Google sign-out</a></p>';
    // List five contacts
    $restUri = 'https://people.googleapis.com/v1/people/me/connections'
            . '?pageSize=5&personFields=names'
            . '&access_token=' . $auth->getToken();
    $response = json_decode(file_get_contents($restUri), true);
    foreach ($response['connections'] as $contact) {
      echo $contact['names'][0]['displayName'] . '<br />';
    }
  }

?>