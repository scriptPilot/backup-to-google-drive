# PHP Google Contacts Wrapper

## Usage

```php
// Require class file
require('Contacts.php');

// Create contacts object (with token)
$contacts = new \Google\Contacts('valid-google-oauth2-token');

// Call method
$contacts = $contacts->getContacts();
foreach ($contacts as $contact) echo $contact['displayName'] . '<br />';
```

## Methods

### setToken(*string* $token)

Set new token for Google Drive API requests.

### getContacts()

Return *array* with contacts or *false* in case of any error.

### createVCard(*array* $contact)

Return *string* wit vCard content for a given contact *array*.

## Example

This example script manages the sign-in and sign-out and displays five of your Google contacts.

As a prerequisite, you have to create a project in https://console.developers.google.com/ with an OAuth2 Web Client, enabled Google People API and allowed script URI as redirect URI.

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
  require('lib/Google/Contacts.php');

  /**
   * Create class objects
   */

  $auth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
  $auth->addScope('https://www.googleapis.com/auth/drive');
  $contacts = $auth->getToken() ? new \Google\Contacts($auth->getToken()) : null;

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
     * List contacts from Google Contacts
     */

    $contacts = $contacts->getContacts();
    foreach ($contacts as $contact) {
      echo $contact['displayName'] . '<br />';
    }

  }

?>
```
