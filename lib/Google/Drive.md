# PHP Google Drive Wrapper

## Usage

```php
// Require class file
require('Drive.php');

// Create drive object (with token)
$auth = new \Google\Drive('valid-google-oauth2-token');

// Call method
$files = $drive->search(['q' => 'trashed=false and "root" in parents', 'pageSize' => 5]);
foreach ($files as $file) echo $file['name'] . '<br />';
```

## Methods

### setFields(*string* $fields)

tbd

### setToken(*string* $token)

tbd

### search(*string* $parameters)

tbd

## Example

This example script manages the sign-in and sign-out and displays five of your Google Drive root folder files.

As a prerequisite, you have to create a project in https://console.developers.google.com/ with an OAuth2 Web Client, enabled Google Drive API and allowed script URI as redirect URI.

```php
<?php

  /**
  * Set content type
  */

  header('Content-type: text/html;charset=utf-8');

  /**
   * Set configuration constants
   */

  define('GOOGLE_CLIENT_ID', 'your-google-client-id');
  define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
  define('GOOGLE_REDIRECT_URI', 'your-script-uri');

  /**
   * Load classes
   */

  require('lib/Google/Auth.php');
  require('lib/Google/Drive.php');

  /**
   * Create class objects
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('https://www.googleapis.com/auth/drive');
  $drive = $auth->getToken() ? new \Google\Drive($auth->getToken()) : null;

  /**
   * Handle sign-in and sign-out
   */

  if ($_GET['action'] === 'signIn') $auth->signIn();
  else if ($_GET['action'] === 'signOut') $auth->signOut();
  else if (!$auth->getToken()) echo '<p><a href="?action=signIn">Sign-in to Google</a></p>';
  else {

    // Show sign-out link
    echo '<p><a href="?action=signOut">Google sign-out</a></p>';

    /**
     * List files from Google Drive
     */

    $files = $drive->search([
      'q' => 'trashed=false and "root" in parents',
      'orderBy' => 'name',
      'pageSize' => 5
    ]);
    foreach ($files as $file) {
      echo $file['name'] . '<br />';
    }

  }

?>
```
