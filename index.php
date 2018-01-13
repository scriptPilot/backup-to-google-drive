<?php

  /**
  * Set content type and time zone
  */

  header('Content-type: text/html;charset=utf-8');
  date_default_timezone_set('Europe/Berlin');

  /**
   * Load configuration and classes
   */

  require('config.php');
  require('lib/Google/Auth.php');
  require('lib/Google/Drive.php');
  require('lib/Google/Photos.php');

  /**
   * Create class objects
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('https://www.googleapis.com/auth/drive');
  $auth->addScope('https://picasaweb.google.com/data/');
  $drive = $auth->getToken() ? new \Google\Drive($auth->getToken()) : null;
  $photos = $auth->getToken() ? new \Google\Photos($auth->getToken()) : null;

  /**
   * Handle sign-in and sign-out
   */

  if ($_GET['action'] === 'signIn') $auth->signIn();
  else if ($_GET['action'] === 'signOut') $auth->signOut();
  else if (!$auth->getToken()) echo '<p><a href="?action=signIn">Sign-in to Google</a></p>';
  else if ($_GET['action'] === 'syncAlbums') require('index_syncAlbums.php');
  else {

    // Show sign-out link
    echo '<p><a href="?action=signOut">Google sign-out</a></p>';

    // Show album sync link
    echo '<p><a href="?action=syncAlbums">Sync Albums</a></p>';

  }

?>