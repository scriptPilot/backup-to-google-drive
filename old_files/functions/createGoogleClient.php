<?php

  /**
   * Purpose: Expose a function to create a Google client
   * Parameters:
   * - <null/string/array> $scope
   * - <null/boolean> $login
   * Output: Google client or die
   * Prerequisites:
   * - Installed "google/apiclient: ^2.0" with Composer
   * - Loaded constants
   *   - GOOGLE_API_KEY
   *   - GOOGLE_CLIENT_ID
   *   - GOOGLE_CLIENT_SECRET
   *
   * In addition, the logout could be performed by URL parameter ?googleLogout
   */

  // Define function
  function createGoogleClient($scope = null, $login = true) {

    // Check scope
    if (is_string($scope)) $scope = [$scope];
      else if ($scope === null) $scope = [];
      else if (!is_array($scope)) throw new Exception('createGoogleClient() requires string or array as argument');

    // Start Google client
    $client = new Google_Client();

    // Set access type
    $client->setAccessType('offline');

    // Set API key
    $client->setDeveloperKey($apiKey);

    // Set client id and secret
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);

    // Set redirect URL
    $redirectUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    $client->setRedirectUri($redirectUrl);

    // Set scope
    foreach ($scope as $s) {
      $client->addScope($s);
    }

    // Perform login/logout
    if ($login or isset($_GET['googleLogout'])) {

      // Start session
      if (!isset($_SESSION)) session_start();

      // Logout should be performed
      if (isset($_GET['googleLogout'])) {

        // Remove token from session
        unset($_SESSION['GOOGLE_TOKEN']);

        // Redirect to redirect URL
        header('Location: ' . $redirectUrl);

      // Google token found in session
      } else if (isset($_SESSION['GOOGLE_TOKEN'])) {

        // Set access token to client
        $client->setAccessToken($_SESSION['GOOGLE_TOKEN']);

        // Token is expired
        if ($client->isAccessTokenExpired() && isset($_SESSION['GOOGLE_TOKEN']['refresh_token'])) {

          // Refresh access token
          $client->refreshToken($_SESSION['GOOGLE_TOKEN']['refresh_token']);

          // Update new access token in session
          $_SESSION['GOOGLE_TOKEN'] = $client->getAccessToken();

          // Return client
          return $client;

        // Token is expired but refresh token is missing
        } else if ($client->isAccessTokenExpired() && !isset($_SESSION['GOOGLE_TOKEN']['refresh_token'])) {

          // Revoke token
          $client->revokeToken();

          // Clear session
          unset($_SESSION['GOOGLE_TOKEN']);

          // Redirect to redirect URL
          header('Location: ' . $redirectUrl);

        // Token is not expired
        } else {

          // Return client
          return $client;

        }

      // Google token not found in session
      } else {

        // Code provided by URL
        if (isset($_GET['code'])) {

          // Authenticate with the code
          $client->authenticate($_GET['code']);

          // Store the token (array with token, refresh token ...) in the session
          $token = $client->getAccessToken();
          $_SESSION['GOOGLE_TOKEN'] = $token;

          // Redirect to redirect URL
          header('Location: ' . $redirectUrl);

        // Error provided by URL
        } else if (isset($_GET['error'])) {

          // Show error message
          echo '<pre>';
          echo '<b>Error</b><br />';
          echo $_GET['error'];
          echo '</pre>';

          // Exit
          die();

        // No code or error provided by URL
        } else {

          // Redirect to Google authentication page
          $authUrl = $client->createAuthUrl();
          header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));

        }

      }

    }

    // Return client
    return $client;

  }

?>