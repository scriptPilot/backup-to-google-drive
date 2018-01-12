# PHP Google Auth Wrapper

## Usage

```php
// Require class file
require('Auth.php');

// Create authentication object
$auth = new \Google\Auth('your-client-id', 'your-client-secret', 'your-redirect-uri');

// Add scope
$auth->addScope('https://www.googleapis.com/auth/contacts');

// Call method
if (!auth->getToken()) $auth->signIn();
else echo 'Token: ' . $auth->getToken;
```

## Methods

### addScope(*string* $scope)

To add one or more scope.

#### Example

```php
// Add one scope
addScope('https://www.googleapis.com/auth/contacts');
// Add two scope
addScope('https://www.googleapis.com/auth/contacts');
addScope('https://www.googleapis.com/auth/drive');
// or
addScope('https://www.googleapis.com/auth/contacts https://www.googleapis.com/auth/drive');
```

### setCredentials(*array* $credentials)

Set credentials to the authentication object.

#### Example

```php
setCredentials([
  'access_token' => '1/fFAGRNJru1FTz70BzhT3Zg',
  'expires_in' => '3920',
  'token_type' => 'Bearer',
  'refresh_token' => '1/xEoDL4iW3cxlI7yDbSRFYNG01kVKM2C-259HOF2aQbI'
]);
```

### getCredentials()

Return *array* credentials or *null*.

#### Example

```php
getCredentials();
// Return
[
  'access_token' => '1/fFAGRNJru1FTz70BzhT3Zg',
  'expires_in' => '3920',
  'token_type' => 'Bearer',
  'refresh_token' => '1/xEoDL4iW3cxlI7yDbSRFYNG01kVKM2C-259HOF2aQbI'
];
```

### getToken()

Return *string* token or *null*. You should use this function to decide whether the user is signed-in or not.

### signIn()

Forward to Google sign-in page and handle the further process. At the end, the authentication objects should have new credentials. They are stored in the session with `$_SESSION['GOOGLE_CREDENTIALS']` as well.

### signOut()

Remove credentials from authentication object and session.

## Example

This example script manages the sign-in and sign-out and displays five of your Google Contacts.

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
   * Load class
   */

  require('lib/Google/Auth.php');

  /**
   * Create authentication object
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
    // Call Google Contacts REST API
    $restUri = 'https://people.googleapis.com/v1/people/me/connections'
            . '?pageSize=5&personFields=names'
            . '&access_token=' . $auth->getToken();
    $response = json_decode(file_get_contents($restUri), true);
    // List contacts
    foreach ($response['connections'] as $contact) {
      echo $contact['names'][0]['displayName'] . '<br />';
    }
  }

?>
```
