# PHP Google Photos Wrapper

## Usage

```php
// Require class file
require('Photos.php');

// Create photos object (with token)
$photos = new \Google\Photos('valid-google-oauth2-token');

// Call method
$albums = $photos->getAlbums();
foreach ($albums as $album) echo $album['name'] . '<br />';
```

## Methods

### setToken(*string/null* $token)

Set new token for Google Photos API requests.

### getAlbums()

Get array with albums, each with `id` and `name`.

### getPhotos(*string* $albumId)

Get array of photos for a given `$albumId`, each with `id`, `name`, `mimeType`, `uri` and `updated` iso time string.

To download photos with specific maximum size, add `?imgmax={size}` to the photo uri.

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
  require('lib/Google/Photos.php');

  /**
   * Create class objects
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('https://picasaweb.google.com/data/');
  $photos = $auth->getToken() ? new \Google\Photos($auth->getToken()) : null;

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
     * List albums from Google Photos
     */

    $albums = $photos->getAlbums();
    foreach ($albums as $album) echo $album['name'] . '<br />';

  }

?>
```
