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
  $googleRequest = new Curl\Curl();
  $googleRequest->setHeader('Authorization', 'Bearer ' . $_SESSION['googleToken']);

  # GitHub request wrapper
  $githubRequest = new Curl\Curl();
  $githubRequest->setHeader('Authorization', 'Token ' . $_SESSION['githubToken']);

  # Google API test
  if ($_SESSION['googleToken']) {

    # Show user information
    $googleRequest->get('https://people.googleapis.com/v1/people/me', ['personFields' => 'emailAddresses,photos']);
    if ($googleRequest->response->error) {
      echo '<p>Error: ' . $googleRequest->response->error->code . ' - ' . $googleRequest->response->error->message . '</p>';
    } else {
      echo '<h3>Google</h3>'
         . '<p><b>' . $googleRequest->response->emailAddresses[0]->value . '</b></p>'
         . '<p><img src="' . $googleRequest->response->photos[0]->url . '" width="80" /></p>'
         . '<p><a href="' . FILE_URL . '?action=googleSignOut">Google Sign-Out</a></p>';
    }

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
  }

?>