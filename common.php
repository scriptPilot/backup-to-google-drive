<?php

  /**
  * Set content type and time zone
  */

  header('Content-type: text/html;charset=utf-8');
  date_default_timezone_set('Europe/Berlin');

  /**
   * Load vendor modules
   */

  require('vendor/autoload.php');

  /**
   * Load configuration and classes
   */

  require('config.php');
  require('lib/Google/Auth.php');
  require('lib/Google/Drive.php');
  require('lib/Google/Photos.php');
  require('lib/Google/Contacts.php');
  require('lib/RTM/RTM.php');
  require('lib/GitHub/GitHub.php');

  /**
   * Create class objects
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('https://www.googleapis.com/auth/drive');
  $auth->addScope('https://picasaweb.google.com/data/');
  $auth->addScope('https://www.googleapis.com/auth/contacts');
  $auth->addScope('profile');
  $drive = new \Google\Drive($auth->getToken());
  $photos = new \Google\Photos($auth->getToken());
  $contacts = new \Google\Contacts($auth->getToken());
  $rtm = new \RTM\RTM('read');
  $github = new \GitHub\GitHub();

?>