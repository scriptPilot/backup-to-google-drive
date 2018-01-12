# PHP Google Drive Wrapper

## Usage

```php
// Require class file
require('Drive.php');

// Create drive object (with token)
$drive = new \Google\Drive('valid-google-oauth2-token');

// Call method
$files = $drive->search(['q' => 'trashed=false and "root" in parents', 'pageSize' => 5]);
foreach ($files as $file) echo $file['name'] . '<br />';
```

## Methods

### setToken(*string* $token)

Set new token for Google Drive API requests.

### setFields(*string* $fields)

Define returned file meta fields. Comma separated values, default is `id,name,description,mimeType`.

Available fields:
https://developers.google.com/drive/v3/reference/files#resource

### search(*array* $options)

Perform search on folder and files. Returns file list.

Parameters to be used are listed here:
https://developers.google.com/drive/v3/reference/files/list

### createFolder(*string/array* $properties)

Create new folder or folder structure, return folder information.

All properties:
https://developers.google.com/drive/v3/reference/files/create

#### Examples

```php
// Create folder in Google Drive root
createFolder('New Folder');

// Create starred folder in Google Drive root
createFolder(['name' => 'Starred folder', 'starred': true]);

// Create new folder path
createFolder('Main Folder/Sub Folder/Sub Sub Folder');

// Return
[
  'id' => '1whjoGNwTcr4KD7sirAZyxMrAQJvhdyht',
  'name' => 'Sub Sub Folder'
]
```

The array accepts properties as listed here:
https://developers.google.com/drive/v3/reference/files/create

### ensureFolder(*string/array* $properties)

Works like `createFolder()`, but if the folder exists, it will not create a new one.

### updateFolder(*string* id, *array* $properties)

Update folder properties.

All properties:
https://developers.google.com/drive/v3/reference/files/create

### createFile(*array* $properties, *string* $content)

Create a file to Google Drive. Properties `name` and `mimeType` are required.
For the following file extensions, the mimeType is detected automatically:

jpg, png, gif, txt, vcf, pdf

All properties:
https://developers.google.com/drive/v3/reference/files/create

### updatefile(*string* $id, *array* $properties, [*string* $content])

Update the file with `$id` in Google Drive. Only new or updated properties are to provide, the old ones will remain. A new `$content` is optional and must have the same mime type as the original file.

All properties:
https://developers.google.com/drive/v3/reference/files/create

### getFile(*string* $id)

Get file meta information. Returns array with meta information.

### downloadFile(*string* id)

Download file. Returns file content as string.

### exportFile(*string* $id, *string* mimeType)

Export Google documents in several formats. Returns exported file content as string.

#### Example

```php
$content = $drive->export('3ZjkoE3kJxioLz2Vfo1CKKPrRq_DEhy1k0r3B1KF-guo', 'application/pdf');
file_put_contents('exported_document.pdf', $content);
```

### trash(*string* $id)

Trash file or folder. Returns file information.

### untrash(*string* $id)

Untrash file or folder. Returns file information.

### emptyTrash()

Permanently deletes all of the user's trashed files. NO WAY BACK!
Returns true or false.

### delete(*string* $id)

Delete file or folder. Returns file information. NO WAY BACK!
Returns true or false.

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
