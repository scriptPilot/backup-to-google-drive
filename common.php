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
  require('lib/Google/Contacts.php');

  /**
   * Create class objects
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('profile');
  $auth->addScope('https://www.googleapis.com/auth/drive');
  $auth->addScope('https://picasaweb.google.com/data/');
  $auth->addScope('https://www.googleapis.com/auth/contacts');
  $drive = $auth->getToken() ? new \Google\Drive($auth->getToken()) : null;
  $photos = $auth->getToken() ? new \Google\Photos($auth->getToken()) : null;
  $contacts = $auth->getToken() ? new \Google\Contacts($auth->getToken()) : null;

?>