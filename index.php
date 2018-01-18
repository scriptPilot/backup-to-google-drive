<?php

  /**
   * Common settings, Google object initialization
   */
  require('common.php');

  /**
   * Handle sign-in and sign-out
   */

  if ($_GET['action'] === 'signIn') $auth->signIn();
  else if ($_GET['action'] === 'signOut') $auth->signOut();
  else if (!$auth->getToken()) echo '<p><a href="?action=signIn">Sign-in to Google</a></p>';
  else if ($_GET['action'] === 'syncContacts') require('index_syncContacts.php');
  else if ($_GET['action'] === 'syncAlbums') require('index_syncAlbums.php');
  else {

    $drive->touch('1hLfpwLKL1kcnVRQiQ68UzJQc0vx4ol4m');

    // Show user info
    $user = $auth->getUserInfo();
    echo '<h1>' . $user['displayName'] . '</h1>';
    echo '<p><img src="' . $user['photoUri'] . '?imgmax=80" /></p>';

    // Show sign-out link
    echo '<p><a href="?action=signOut">Google sign-out</a></p>';

    // Connected services
    echo '<h2>Connected services</h2>';

    // RememberTheMilk
    if ($_GET['action'] === 'addRTM') {
      echo '<p>Get ...</p>';
    } else {
      echo '<p><a href="">Add Remember The Milk</a></p>';
    }

    // Save(update)/delete user credentials
    echo '<h2>Backup script</h2>';
    $credentialsFile = '.credentials/' . $user['id'] . '.php';
    if ($_GET['action'] === 'saveCredentials' or (file_exists($credentialsFile) && $_GET['action'] !== 'deleteCredentials')) {
      $content = '<?php'. "\n"
               . "\n"
               . '  //credentials:' . json_encode($auth->getCredentials()) . "\n"
               . "\n"
               . '?>' . "\n";
      if (!is_dir('.credentials')) mkdir('.credentials');
      file_put_contents($credentialsFile, $content);
    } else if ($_GET['action'] === 'deleteCredentials') {
      unlink($credentialsFile);
    }
    if (!file_exists($credentialsFile)) {
      echo '<p style="color: gray">Not active</p>';
      echo '<p><a href="?action=saveCredentials">Activate</a></p>';
    } else {
      echo '<p style="color: green"><b>Active</b></p>';
      echo '<p><a href="?action=deleteCredentials">Deactivate</a></p>';
    }

  }

?>