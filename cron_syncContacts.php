<?php

  /**
   * Purpose: Sync all contacts to Google drive for saved credentials
   */

  /**
   * Common settings, Google object initialization
   */
  require('common.php');

  /**
   * Lock cronjob
   */

  $lockFile = 'cron_syncContacts.lock.log';
  if (file_exists($lockFile)) {
    $start = intval(file_get_contents($lockFile));
    $duration = time() - $start;
    if ($duration > 1800) {
      echo '<span style="color: red">Process locked for ' . $duration . ' seconds now</span><br />';
      echo '<b style="color: red">Cronjob finished with an error</b>';
    } else {
      echo 'Process locked for ' . $duration . ' seconds now<br />';
      echo '<b style="color: green">Cronjob finished successfull</b>';
    }
    exit();
  } else {
    file_put_contents($lockFile, time());
  }

  $errors = 0;

  // Loop files in folder
  $dir = '.credentials';
  if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file !== '.' && $file !== '..') {
        // Extract credentials
        $content = file_get_contents($dir . '/' . $file);
        preg_match('/\/\/credentials:(.+)\\n/', $content, $search);
        $credentials = json_decode(trim($search[1]), true);

        // Update credentials and token
        $auth->setCredentials($credentials);
        $contacts->setToken($auth->getToken());
        $drive->setToken($auth->getToken());

        /**
         * Synchronization
         */

        // Show user name
        echo 'Contacts synchronization starts for <b>'. $auth->getUserInfo()['displayName'] . '</b><br />';

        // Load contacts, add ident
        $allContacts = [];
        $contactSearch = $contacts->getContacts();
        foreach ($contactSearch as $contact) {
          $allContacts[$contact['ident']] = $contact;
        }
        echo '- Found ' . count($allContacts) . ' contact'. (count($allContacts) !== 1 ? 's' : '') . ' in Google Contacts<br />';

        // Get folder id
        $folderId = $drive->ensureFolder(DEFAULT_BACKUP_FOLDER_CONTACTS)['id'];

        // Load files
        $allFiles = [];
        $fileSearch = $drive->search(['q' => '"' . $folderId . '" in parents and trashed=false', 'orderBy' => 'name', 'pageSize' => 1000]);
        foreach ($fileSearch as $file) {
          $ident = $file['description'] ? $file['description'] : $file['id'];
          $allFiles[$ident] = $file;
        }
        echo '- Found ' . count($allFiles) . ' file'. (count($allFiles) !== 1 ? 's' : '') . ' in Google Drive<br />';

        // Trash additional (and so as well changed) files
        foreach ($allFiles as $ident => $file) {
          if (!array_key_exists($ident, $allContacts)) {
            $trashed = $drive->trash($file['id']);
            if ($trashed) echo '<span style="color: orange">- Trashed file "'. $file['name'] . '"</span><br />';
            else {
              echo '<span style="color: red">- Failed to trash "'. $file['name'] . '"</span><br />';
              $errors += 1;
            }
          }
        }

        // Create missing files
        foreach ($allContacts as $ident => $contact) {
          if (!array_key_exists($ident, $allFiles)) {
            $content = $contacts->createVCard($contact);
            $created = $drive->createFile(['name' => $contact['displayName'] . '.vcf', 'description' => $ident, 'parents' => [$folderId]], $content);
            if ($created) echo '<span style="color: blue">- Created file "'. $created['name'] . '"</span><br />';
            else {
              echo '<span style="color: red">- Failed to create "'. $created['name'] . '"</span><br />';
              $errors += 1;
            }
          }
        }

      }
    }
  }

  if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull</b><br />';
  else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';

  // Unlock cronjob
  unlink($lockFile);

?>