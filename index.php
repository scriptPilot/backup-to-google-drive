<?php

  /**
   * Common settings, object initialization
   */
  require('common.php');

  /**
   * Handle Google sign-in and sign-out
   */

  if ($_GET['action'] === 'signIn') $auth->signIn();
  else if ($_GET['action'] === 'signOut') $auth->signOut();
  else if (!$auth->getToken()) echo '<p><a href="?action=signIn">Sign-in to Google</a></p>';
  else if ($_GET['action'] === 'syncContacts') require('index_syncContacts.php');
  else if ($_GET['action'] === 'syncAlbums') require('index_syncAlbums.php');
  else {

    /**
     * Headline
     */

    echo '<h1>Backup to Google Drive</h1>';

    /**
     * Google
     */

    // Headline
    echo '<h2>Google</h2>';

    // Restore credentials
    $googleCredentialsFile = './credentials/' . $auth->getUserInfo()['id'] . '.google.php';
    if (file_exists($googleCredentialsFile)) {
      $content = file_get_contents($googleCredentialsFile);
      preg_match('/\/\/(.+)\\n/', $content, $search);
      $credentials = json_decode(trim($search[1]), true);
      $auth->setCredentials($credentials);
    }

    // Show user info
    $user = $auth->getUserInfo();
    echo '<p><img src="' . $user['photoUri'] . '?imgmax=80" /></p>';

    // Show sign-out link
    echo '<p>' . $user['displayName'] . ' - <a href="?action=signOut">sign-out</a></p>';

    // Save(update)/delete user credentials
    $credentialsFile = '.credentials/' . $user['id'] . '.google.php';
    if ($_GET['action'] === 'saveCredentials' or (file_exists($credentialsFile) && $_GET['action'] !== 'deleteCredentials')) {
      $content = '<?php'. "\n"
               . '  //' . json_encode($auth->getCredentials()) . "\n"
               . '?>' . "\n";
      if (!is_dir('.credentials')) mkdir('.credentials');
      file_put_contents($credentialsFile, $content);
    } else if ($_GET['action'] === 'deleteCredentials') {
      unlink($credentialsFile);
    }
    if (!file_exists($credentialsFile)) {
      echo '<p style="color: gray">Not active - <a href="?action=saveCredentials">activate</a></p>';
    } else {
      echo '<p style="color: green"><b>Active</b> - <a href="?action=deleteCredentials">deactivate</a></p>';
    }

    /**
     * Remember the Milk
     */

    // Headline
    echo '<h2>Remember The Milk</h2>';

    // Credentials file
    $rtmCredentialsFile = '.credentials/' . $auth->getUserInfo()['id'] . '.rtm.php';

    // Sign-out
    if ($_GET['action'] === 'rtmSignOut') {
      unlink($rtmCredentialsFile);
      $rtm->setCredentials(null);

    // Restore credentials
    } else if ($rtm->getUserInfo() == null && file_exists($rtmCredentialsFile)) {
      $content = file_get_contents($rtmCredentialsFile);
      preg_match('/\/\/(.+)\\n/', $content, $search);
      $credentials = json_decode(trim($search[1]), true);
      $rtm->setCredentials($credentials);
    }

    // Display sign-out link
    if ($rtm->getUserInfo()) {

      echo '<p>' . $rtm->getUserInfo()['fullname'] . ' - <a href="?action=rtmSignOut">sign-out</a></p>';

      // Update credentials on disk
      $content = '<?php' . "\n"
               . '  //' . json_encode($rtm->getCredentials()) . "\n"
               . '?>';
      file_put_contents($rtmCredentialsFile, $content);

    // Display sign-in form
    } else {
      echo '<form method="get">';
      echo '<p>1. Sign-in to <a href="' . $rtm->getAuthUri() . '" target="_blank">Remember The Milk</a></p>';
      echo '<p>2. Enter frob <input name="frob" /></p>';
      echo '<p>3. <input type="submit" />';
      echo '</form>';
    }

    /**
     * GitHub
     */

    // Headline
    echo '<h2>GitHub</h2>';

    // Credentials file
    $githubCredentialsFile = '.credentials/' . $auth->getUserInfo()['id'] . '.github.php';

    // Sign-out
    if ($_GET['action'] === 'githubSignOut') {
      unlink($githubCredentialsFile);
      $github->setCredentials(null);

    // Restore credentials
    } else if (!$github->getUserInfo() && file_exists($githubCredentialsFile)) {
      $content = file_get_contents($githubCredentialsFile);
      preg_match('/\/\/(.+)\\n/', $content, $search);
      $credentials = json_decode(trim($search[1]), true);
      $github->setCredentials($credentials);
    }

    // Display sign-out link
    if ($github->getUserInfo()) {

     echo '<p><img src="' . $github->getUserInfo()['avatar_url'] . '&s=80" /></p>';
     echo '<p>' . $github->getUserInfo()['login']. ' - <a href="?action=githubSignOut">sign-out</a></p>';

      // Update credentials on disk
      $content = '<?php' . "\n"
               . '  //' . json_encode($github->getCredentials()) . "\n"
               . '?>';
      file_put_contents($githubCredentialsFile, $content);

    // Display sign-in form
    } else {
      echo '<p>Sign-in to <a href="' . $github->getAuthUri() . '">GitHub</a></p>';
    }

  }

?>