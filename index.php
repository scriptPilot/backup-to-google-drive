<?php

  # Configuration
  define('GOOGLE_CREDENTIALS_FILE', __DIR__ . '/googleCredentials.json');
  define('GOOGLE_API_KEY', 'AIzaSyAX6mF10Z5quMkD27_QZehnnH25gvq0tlA');
  define('GOOGLE_SCOPE', 'profile https://picasaweb.google.com/data/ https://www.googleapis.com/auth/drive');

  # Include Composer modules
  require('./vendor/autoload.php');

  # Start session
  session_start();

  # Determine this file URL
  define('FILE_URL', 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

  # Handle Google Sign-In and Sign-Out
  function startGoogleAuth() {
    $client = new Google_Client();
    $client->setDeveloperKey(GOOGLE_API_KEY);
    $client->setAuthConfig(GOOGLE_CREDENTIALS_FILE);
    $client->addScope(GOOGLE_SCOPE);
    $client->setPrompt('select_account');
    $client->setRedirectUri(FILE_URL);
    if (isset($_GET['code'])) {
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      if ($token['access_token']) {
        $_SESSION['googleToken'] = $token['access_token'];
        header('Location: ' . FILE_URL);
      } else {
        echo 'Error: Failed to get the Google token.';
      }
    } else {
      $auth_url = $client->createAuthUrl();
      header('Location: ' . $auth_url);
    }
  }
  if ($_GET['action'] === 'googleSignIn' || $_GET['code']) {
    startGoogleAuth();
  }
  if ($_GET['action'] === 'googleSignOut') {
    unset($_SESSION['googleToken']);
    header('Location: ' . FILE_URL);
  }

  # Google request wrapper
  $googleRequest = new Curl\Curl();
  $googleRequest->setHeader('Authorization', 'Bearer ' . $_SESSION['googleToken']);

  # Show sign-in links
  echo '<h2>Sign-In</h2>';
  if (!$_SESSION['googleToken']) {
    echo '<p><a href="' . FILE_URL . '?action=googleSignIn">Google</a></p>';
  }

  # Google API test
  if ($_SESSION['googleToken']) {

    # Show user information
    $googleRequest->get('https://people.googleapis.com/v1/people/me', ['personFields' => 'emailAddresses,photos']);
    if ($googleRequest->response->error) {
      echo '<p>Error: ' . $googleRequest->response->error->code . ' - ' . $googleRequest->response->error->message . '</p>';
    } else {
      echo '<h3>Google</h3>'
         . '<p><b>' . $googleRequest->response->emailAddresses[0]->value . '</b></p>'
         . '<p><img src="' . $googleRequest->response->photos[0]->url . '" /></p>'
         . '<p><a href="' . FILE_URL . '?action=googleSignOut">Google Sign-Out</a></p>';
    }

  }

?>