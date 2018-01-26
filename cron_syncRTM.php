<?php

  /**
   * Purpose: Sync RTM tasks to Google drive for saved credentials
   */

  // Common includes and object initializations
  require('common.php');

   /**
   * Lock cronjob
   */

  $lockFile = 'cron_syncRTM.lock.log';
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

  /**
   * Synchronize
   */

  $errors = 0;
  $actions = 0;

  // Loop files in folder
  $dir = '.credentials';
  if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
      if (substr($file, -11) === '.google.php') {

        // RTM credentials file exists as well
        $rtmFile = $dir . '/' . str_replace('.google.', '.rtm.', $file);
        if (file_exists($rtmFile)) {

          // Extract Google credentials
          $content = file_get_contents($dir . '/' . $file);
          preg_match('/\/\/(.+)\\n/', $content, $search);
          $credentials = json_decode(trim($search[1]), true);

          // Update Google credentials and token
          $auth->setCredentials($credentials);
          $drive->setToken($auth->getToken());

          // Extract RTM credentials
          $content = file_get_contents($rtmFile);
          preg_match('/\/\/(.+)\\n/', $content, $search);
          $credentials = json_decode(trim($search[1]), true);
          $rtm->setCredentials($credentials);

          // Show user name
          echo 'RTM synchronization starts for <b>'. $auth->getUserInfo()['displayName'] . '</b><br />';

          // Get backup file id
          $fileId = null;
          $fileName = 'RememberTheMilk.json';
          $folderId = $drive->ensureFolder(DEFAULT_BACKUP_FOLDER_RTM)['id'];
          $files = $drive->search(['q' => '"' . $folderId . '" in parents and trashed=false']);
          foreach ($files as $file) {
            if ($file['name'] === $fileName) $fileId = $file['id'];
          }

          // Create missing file
          if (!$fileId) {
            $created = $drive->createFile(['name' => $fileName, 'parents' => [$folderId]], '');
            if ($created) {
              $fileId = $created['id'];
              $actions += 1;
            } else {
              echo '<span style="color: red">- Failed to create "'. $fileName . '"</span><br />';
              $errors += 1;
            }
          }

          // Get lists
          $lists = $rtm->getRequestResponse(['method' => 'rtm.lists.getList'])['lists']['list'];
          $listsCount = count($lists);
          echo '- Found ' . $listsCount . ' list' . ($listsCount !== 1 ? 's' : '') . ' in RTM<br />';

          // Get taskseries per list
          $json = [];
          $tasksCount = 0;
          foreach ($lists as $list) {
            if ($list['smart'] === '1') $taskseries = null;
            else $taskseries = $rtm->getRequestResponse(['method' => 'rtm.tasks.getList', 'list_id' => $list['id']])['tasks']['list'][0]['taskseries'];
            $list['taskseries'] = $taskseries;
            $json[] = $list;
            if ($taskseries !== null) $tasksCount += count($taskseries);
          }
          echo '- Found ' . $tasksCount . ' tasks serie' . ($tasksCount !== 1 ? 's' : '') . ' in RTM<br />';

          // Create json object
          $jsonString = json_encode($json);

          // Update file
          $updated = $drive->updateFile($fileId, [], $jsonString);
          if ($updated) {
            echo '<span style="color: blue">- Updated file "'. $fileName. '"</span><br />';
            $actions += 1;
          } else {
            echo '<span style="color: red">- Failed to update "'. $fileName . '"</span><br />';
            $errors += 1;
          }

        }
      }
    }
  }

  /**
   * Show final log
   */

  if ($errors === 0 && $actions === 0) echo '<b style="color: green">Cronjob finished successfull without any action</b><br />';
  else if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull with ' . $actions . ' action' . ($actions !== 1? 's' : '') . '</b><br />';
  else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';

  /**
   * Unlock
   */

  unlink($lockFile);

?>