<?php

  # Configuration
  define('GOOGLE_CREDENTIALS_FILE', __DIR__ . '/googleCredentials.json');
  define('GOOGLE_API_KEY', 'AIzaSyBSEUPznSLRbr0FKbhpNJDh18oeuVwndZI');
  define('GOOGLE_SCOPE', 'profile https://picasaweb.google.com/data/ https://www.googleapis.com/auth/drive');
  define('GITHUB_CLIENT_ID', 'c4a5dfbeedfb5d5c3582');
  define('GITHUB_CLIENT_SECRET', '0dde1986aafe56c4307e42365a002fb15f988cce');
  define('RTM_API_KEY', 'e062a7ea930f4c383b1de2dd11d29e87');
  define('RTM_SHARED_SECRET', '1a8a46afa6807e80');

  # Include Composer modules
  require('./vendor/autoload.php');

  # Start session
  session_start();

  # Encoding
  header('Content-Type: text/html; charset=utf-8');

  # Determine this file URL
  define('FILE_URL', 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

  # RTM request wrapper
  function rtmRequest($params) {

    // Add default parameters
    if (!isset($params['api_key'])) $params['api_key'] = RTM_API_KEY;
    if (!isset($params['format'])) $params['format'] = 'json';

    // Add auth token if method id provided
    if (isset($params['method']) && !isset($params['auth_token'])) $params['auth_token'] = $_SESSION['rtmToken'];

    // Build signature
    ksort($params);
    $concatenate = RTM_SHARED_SECRET;
    foreach ($params as $key => $val) {
      $concatenate = $concatenate . $key . $val;
    }
    $signature = md5($concatenate);

    // URL encode parameters
    foreach ($params as $key => $val) {
      $params[$key] = urlencode($val);
    }

    // Build URL
    $url = !isset($params['method']) ? 'https://www.rememberthemilk.com/services/auth/' : 'https://api.rememberthemilk.com/services/rest/';
    $paramNo = 0;
    foreach ($params as $key => $val) {
      $paramNo++;
      $connector = $paramNo === 1 ? '?' : '&';
      $url = $url . $connector . $key . '=' . $val;
    }
    $url = $url . '&api_sig=' . $signature;

    // Return only URL if no method id provided (for authentication link)
    if (!isset($params['method'])) return $url;

    // Do request
    $response = json_decode(file_get_contents($url));

    // Return response
    return $response->rsp;

  }

  # Handle Google Sign-In and Sign-Out
  function startGoogleAuth() {
    $client = new Google_Client();
    $client->setDeveloperKey(GOOGLE_API_KEY);
    $client->setAuthConfig(GOOGLE_CREDENTIALS_FILE);
    $client->addScope(GOOGLE_SCOPE);
    $client->setPrompt('select_account');
    $client->setRedirectUri(FILE_URL);
    if (isset($_GET['code'])) {
      unset($_SESSION['authProcess']);
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      if ($token['access_token']) {
        $_SESSION['googleToken'] = $token['access_token'];
        header('Location: ' . FILE_URL);
      } else {
        echo 'Error: Failed to get the Google token.';
      }
    } else {
      $_SESSION['authProcess'] = 'google';
      $auth_url = $client->createAuthUrl();
      header('Location: ' . $auth_url);
    }
  }
  if ($_GET['action'] === 'googleSignIn' || $_SESSION['authProcess'] === 'google') {
    startGoogleAuth();
  }
  if ($_GET['action'] === 'googleSignOut') {
    unset($_SESSION['googleToken']);
    header('Location: ' . FILE_URL);
  }

  # Handle GitHub Sign-In and Sign-Out
  function startGithubAuth() {
    if ($_SESSION['authProcess'] !== 'github') {
      $_SESSION['authProcess'] = 'github';
      $auth_url = 'https://github.com/login/oauth/authorize'
                . '?client_id=' . urlencode(GITHUB_CLIENT_ID)
                . '&redirect_uri=' . urlencode(FILE_URL)
                . '&scope=user repo'
                . '&state=anystate';
      header('Location: ' . $auth_url);
    } else if ($_GET['code']) {
      unset($_SESSION['authProcess']);
      if ($_GET['state'] !== 'anystate') {
        echo '<p>Error: GitHub state comparison failed.</p>';
        exit;
      } else {
        $request = new Curl\Curl();
        $request->post('https://github.com/login/oauth/access_token', [
          'client_id' => GITHUB_CLIENT_ID,
          'client_secret' => GITHUB_CLIENT_SECRET,
          'code' => $_GET['code']
        ]);
        parse_str($request->response, $params);
        $_SESSION['githubToken'] = $params['access_token'];
        header('Location: ' . FILE_URL);
      }
    } else {
      unset($_SESSION['authProcess']);
      echo '<p>Error: GitHub authorization process failed.</p>';
      exit;
    }
  }
  if ($_GET['action'] === 'githubSignIn' || $_SESSION['authProcess'] === 'github') {
    startGithubAuth();
  }
  if ($_GET['action'] === 'githubSignOut') {
    unset($_SESSION['githubToken']);
    header('Location: ' . FILE_URL);
  }

  # Handle RTM sign-in and sign-out
  function startRTMAuth() {
    // Frob is not given > show form
    if (!isset($_POST['frob']) OR $_POST['frob'] === '') {

      $_SESSION['authProcess'] = 'rtm';

      // Get auth URL
      $urlResponse = rtmRequest(['perms' => 'write']);

      // Show Link and form for frob input
      echo '<p><a href="' . $urlResponse . '" target="_blank">Get Frob</a></p>';
      echo '<form method="post" url="' . FILE_URL . '">Frob: <input type="text" name="frob" /> <button type="submit">Get Token</button></form>';

      // No more output
      exit;

    // Frob is given > request token
    } else {

      unset($_SESSION['authProcess']);

      // Get token
      $response = rtmRequest([
        'method' => 'rtm.auth.getToken',
        'frob' => $_POST['frob']
      ]);

      // Extract token
      $token = $response->auth->token;

      // If no token found
      if (!isset($token) or $token === '') {
        echo '<p>Error: Failed to get RTM token.</p>';
        exit;

      // If token found
      } else {
        $_SESSION['rtmToken'] = $token;
        header('Location: ' . FILE_URL);
      }

    }
  }
  if ($_GET['action'] === 'rtmSignIn' || $_SESSION['authProcess'] === 'rtm') {
    startRTMAuth();
  }
  if ($_GET['action'] === 'rtmSignOut') {
    unset($_SESSION['rtmToken']);
    header('Location: ' . FILE_URL);
  }

  # Show sign-in links
  echo '<h2>Sign-In</h2>';
  if (!$_SESSION['googleToken']) {
    echo '<p><a href="' . FILE_URL . '?action=googleSignIn">Google</a></p>';
  }
  if (!$_SESSION['githubToken']) {
    echo '<p><a href="' . FILE_URL . '?action=githubSignIn">GitHub</a></p>';
  }
  if (!$_SESSION['rtmToken']) {
    echo '<p><a href="' . FILE_URL . '?action=rtmSignIn">RememberTheMilk</a></p>';
  }

  # Google request wrapper
  function googleRequest($url, $data = [], $method = 'GET') {
    $request = new Curl\Curl();
    $request->setHeader('Authorization', 'Bearer ' . $_SESSION['googleToken']);
    if ($method === 'POST') $request->post($url, $data);
      else $request->get($url, $data);
    if ($googleRequest->error) {
      $code = $request->response->error->code || $request->errorCode;
      $message = $request->response->error->message || $request->errorMessage;
      echo '<p>Error: ' . $code . ' - ' . $message . '</p>';
      $request->close();
      exit;
    } else {
      $json = json_encode($request->response);
      $array = json_decode($json, true);
      $request->close();
      return $array;
    }
  }
  function googlePostJson($url, $data) {
    $request = new Curl\Curl();
    $request->setHeader('Authorization', 'Bearer ' . $_SESSION['googleToken']);
    $request->setHeader('Content-Type', 'application/json');
    $request->post($url, $data);
    if ($googleRequest->error) {
      $code = $request->response->error->code || $request->errorCode;
      $message = $request->response->error->message || $request->errorMessage;
      echo '<p>Error: ' . $code . ' - ' . $message . '</p>';
      $request->close();
      exit;
    } else {
      $json = json_encode($request->response);
      $array = json_decode($json, true);
      $request->close();
      return $array;
    }
  }
  function googlePatch($url, $data) {
    $request = new Curl\Curl();
    $request->setHeader('Authorization', 'Bearer ' . $_SESSION['googleToken']);
    $request->setHeader('Content-Type', 'application/json');
    $request->patch($url, $data);
    if ($googleRequest->error) {
      $code = $request->response->error->code || $request->errorCode;
      $message = $request->response->error->message || $request->errorMessage;
      echo '<p>Error: ' . $code . ' - ' . $message . '</p>';
      $request->close();
      exit;
    } else {
      $json = json_encode($request->response);
      $array = json_decode($json, true);
      $request->close();
      return $array;
    }
  }

  # GitHub request wrapper
  $githubRequest = new Curl\Curl();
  $githubRequest->setHeader('Authorization', 'Token ' . $_SESSION['githubToken']);

  # Google API test
  if ($_SESSION['googleToken']) {

    # Show user information
    $info = googleRequest('https://people.googleapis.com/v1/people/me', ['personFields' => 'emailAddresses,photos']);
    echo '<h3>Google</h3>'
       . '<p><b>' . $info['emailAddresses'][0]['value'] . '</b></p>'
       . '<p><img src="' . $info['photos'][0]['url'] . '" width="80" /></p>'
       . '<p><a href="' . FILE_URL . '?action=googleSignOut">Google Sign-Out</a></p>';

  }

  # GitHub API test
  if ($_SESSION['githubToken']) {

    # Show user information
    $githubRequest->get('https://api.github.com/user');
    if ($githubRequest->error) {
      echo '<p>Error: ' . $githubRequest->errorCode . ' - ' . $githubRequest->errorMessage . '</p>';
    } else {
      echo '<h3>GitHub</h3>'
         . '<p><b>' . $githubRequest->response->login . '</b></p>'
         . '<p><img src="' . $githubRequest->response->avatar_url . '" width="80" /></p>'
         . '<p><a href="' . FILE_URL . '?action=githubSignOut">GitHub Sign-Out</a></p>';
    }

  }

  # RTM API test
  if ($_SESSION['rtmToken']) {

    # Show user information
    $response = rtmRequest(['method' => 'rtm.test.login']);
    if ($response->stat !== 'ok') {
      echo '<p>Error: ' . $response->err->code . ' - ' . $response->err->msg . '</p>';
    } else {
      echo '<h3>RememberTheMilk</h3>'
         . '<p><b>' . $response->user->username . '</b></p>'
         . '<p><a href="' . FILE_URL . '?action=rtmSignOut">RememberTheMilk Sign-Out</a></p>';
    }

  }

  # Sync links
  if ($_SESSION['googleToken']) {
    echo '<h2>Synchronization</h2>';
    echo '<p><a href="' . FILE_URL . '?sync=photoAlbums">Synchronize Photo Albums</a></p>';
  }

  # Sync actions
  if ($_GET['sync']) {
    echo '<h3>Result</h3>';
  }
  if ($_GET['sync'] === 'photoAlbums') {
    # Start array with relevant Picasa albums
    $picasaAlbums = [];
    # Loop all Picasa albums
    $allAlbums = googleRequest('https://picasaweb.google.com/data/feed/api/user/default');
    foreach ($allAlbums['entry'] as $album) {
      # Extract album id
      preg_match('/\/([0-9]+)$/', $album['id'], $id);
      $albumId = $id[1];
      # Proceed only with real albums, exlude "Auto Backup", "Profile Photos", "Hangout:..." and "YYYY-MM-DD" albums
      if ($album['title'] !== 'Profile Photos' && $album['title'] !== 'Auto Backup' && substr($album['title'], 0, 8) !== 'Hangout:' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $album['title']) === 0) {
        # Request all files in album
        $allAlbumFiles = googleRequest('https://picasaweb.google.com/data/feed/api/user/default/albumid/' . $albumId);
        # Workaround if only one photo is in the album and props are on first level
        if ($allAlbumFiles['entry']['id']) {
          $tmp = $allAlbumFiles['entry'];
          $allAlbumFiles['entry'] = [$tmp];
        }
        # Create array of Picasa files
        $picasaFiles = [];
        # Loop Picasa files
        foreach ($allAlbumFiles['entry'] as $file) {
          # Extract photo id
          preg_match('/\/([0-9]+)$/', $file['id'], $id);
          $fileId = $id[1];
          # Check file type
          $action = preg_match('/\.(jpeg|jpg|png|gif)$/i', $file['title']) ? 'sync' : 'skip';
          # Add file to array
          $picasaFiles[] = [
            'id' => $fileId,
            'name' => $file['title'],
            'link' => $file['content']['@attributes']['src'],
            'action' => $action
          ];
        }
        # Add album to array
        $picasaAlbums[] = [
          'id' => $albumId,
          'name' => $album['title'],
          'action' => 'sync',
          'files' => $picasaFiles
        ];
      } else {
        # Add album to array
        $picasaAlbums[] = [ 'id' => $albumId, 'name' => $album['title'], 'action' => 'skip' ];
      }
    }

    # Define folder for photo albums
    $driveFolder = 'Google Fotoalben';
    $driveFolderId = null;
    # Search for existing folder
    $search = googleRequest('https://www.googleapis.com/drive/v3/files', ['q' => 'name="' . $driveFolder . '" and trashed=false']);
    if (count($search['files']) === 1) {
      $driveFolderId = $search['files'][0]['id'];
    # Create new folder
    } else if (count($search['files']) === 0) {
      $create = googlePostJson('https://www.googleapis.com/drive/v3/files', [ 'name' => $driveFolder, 'mimeType' => 'application/vnd.google-apps.folder' ]);
      $driveFolderId = $create['id'];
    # Folder not clear
    } else {
      echo '<p>Error: Folder "' . $driveFolder . '" found multiple times in Google Drive.</p>';
      exit;
    }
    # Search sub folders
    $googleDriveAlbums = [];
    $search = googleRequest('https://www.googleapis.com/drive/v3/files', ['q' => '"' . $driveFolderId . '" in parents and trashed=false']);
    foreach ($search['files'] as $file) {
      # Create folder object
      $folder = [
        'id' => $file['id'],
        'name' => $file['name'],
        'action' => $file['mimeType'] === 'application/vnd.google-apps.folder' ? 'sync' : 'delete'
      ];
      # Loop files in folders
      if ($file['mimeType'] === 'application/vnd.google-apps.folder') {
        $files = [];
        $subSearch = googleRequest('https://www.googleapis.com/drive/v3/files', ['q' => '"' . $file['id'] . '" in parents and trashed=false']);
        foreach ($subSearch['files'] as $subFile) {
          if ($subFile['mimeType'] !== 'application/vnd.google-apps.folder') {
            $files[] = ['id' => $subFile['id'], 'name' => $subFile['name']];
          }
        }
        $folder['files'] = $files;
      }
      # Add to array
      $googleDriveAlbums[] = $folder;
    }
    # Create missing folders
    foreach ($picasaAlbums as $from) {
      if ($from['action'] === 'sync') {
        $found = false;
        foreach ($googleDriveAlbums as $to) {
          if ($to['name'] === $from['name']) $found = true;
        }
        if (!$found) {
          $new = $from;
          $new['action'] = 'create';
          $googleDriveAlbums[] = $new;
        }
      }
    }
    # Delete addition folder and files
    foreach ($googleDriveAlbums as $key => $to) {
      if ($to['action'] !== 'create') {
        $found = false;
        foreach ($picasaAlbums as $from) {
          if ($from['name'] === $to['name']) $found = true;
        }
        if (!$found) {
          $googleDriveAlbums[$key]['action'] = 'delete';
        }
      }
    }
    # Apply changes
    foreach ($googleDriveAlbums as $key => $album) {
      if ($album['action'] === 'create') {
        $create = googlePostJson('https://www.googleapis.com/drive/v3/files', [ 'name' => $album['name'], 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$driveFolderId] ]);
        $googleDriveAlbums[$key]['id'] = $create['id'];
        $album['id'] = $create['id'];
      } else if ($album['action'] === 'delete') {
        $delete = googlePatch('https://www.googleapis.com/drive/v3/files/' . $album['id'], ['trashed' => true]);
      }
      foreach ($album['files'] as $file) {
        # Download
        $request = new Curl\Curl();
        $request->setHeader('Authorization', 'Bearer ' . $_SESSION['googleToken']);
        $request->download($file['link'] . '?imgmax=9999', 'tmp.jpg');
        # Upload
        $client = new Google_Client();
        $client->setDeveloperKey(GOOGLE_API_KEY);
        $client->setAuthConfig(GOOGLE_CREDENTIALS_FILE);
        $client->setAccessToken($_SESSION['googleToken']);
        $driveService = new Google_Service_Drive($client);
        $fileMetadata = new Google_Service_Drive_DriveFile([
          'name' => $file['name'],
          'parents' => [$album['id']]
        ]);
        $content = file_get_contents('tmp.jpg');
        $file = $driveService->files->create($fileMetadata, [
          'data' => $content,
          'mimeType' => 'image/jpeg',
          'uploadType' => 'multipart',
          'fields' => 'id'
        ]);
      }
    }
    echo '<pre>'; print_r([$picasaAlbums, $googleDriveAlbums]); echo '</pre>';
  }

?>