<?php

  /**
   * Purpose: Sync GitHub repositories and issues per user to Google Drive
   */

  // Common includes and object initializations
  require('common.php');
  $maxRuntime = 1800; // not more than script runtime or token expiry

  /**
   * ZIP function
   */

  function zipData($source, $destination) {
    if (extension_loaded('zip')) {
      if (file_exists($source)) {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
          $source = realpath($source);
          if (is_dir($source)) {
            $iterator = new RecursiveDirectoryIterator($source);
            // skip dot files while iterating
            $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
              $file = realpath($file);
              if (is_dir($file)) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
              } else if (is_file($file)) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
              }
            }
          } else if (is_file($source)) {
            $zip->addFromString(basename($source), file_get_contents($source));
          }
        }
        return $zip->close();
      }
    }
    return false;
  }

  /**
  * Remove folder function
  */

  function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (is_dir($dir."/".$object))
           rrmdir($dir."/".$object);
         else
           unlink($dir."/".$object);
       }
     }
     rmdir($dir);
   }
 }

  /**
  * Lock cronjob
  */

  $lockFile = 'cron_syncGitHub.lock.log';
  $scriptStartTime = time();
  if (file_exists($lockFile)) {
    $start = intval(file_get_contents($lockFile));
    $duration = $scriptStartTime - $start;
	if ($duration > $maxRuntime + 5 * 60) {
      unlink($lockFile);
      echo '<span style="color: orange">Process unlocked automatically</span><br />';
      $actions += 1;
    } else if ($duration > $maxRuntime) {
      echo '<span style="color: red">Process locked for ' . $duration . ' seconds now</span><br />';
      echo '<b style="color: red">Cronjob finished with an error</b>';
	  exit();
    } else {
      echo 'Process locked for ' . $duration . ' seconds now<br />';
      echo '<b style="color: green">Cronjob finished successfull without any action</b>';
	  exit();
    }
  }
  file_put_contents($lockFile, $scriptStartTime);

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
        $githubFile = $dir . '/' . str_replace('.google.', '.github.', $file);
        if (file_exists($githubFile)) {

          // Extract Google credentials
          $content = file_get_contents($dir . '/' . $file);
          preg_match('/\/\/(.+)\\n/', $content, $search);
          $credentials = json_decode(trim($search[1]), true);

          // Update Google credentials and token
          $auth->setCredentials($credentials);
          $drive->setToken($auth->getToken());

          // Extract GitHub credentials
          $content = file_get_contents($githubFile);
          preg_match('/\/\/(.+)\\n/', $content, $search);
          $credentials = json_decode(trim($search[1]), true);
          $github->setCredentials($credentials);

          // Show user name
          echo 'GitHub synchronization starts for <b>'. $auth->getUserInfo()['displayName'] . '</b><br />';

          // Load repositories by name
          $repos = [];
          $searchRepos = $github->get('user/repos?sort=full_name');
          foreach ($searchRepos as $repo) $repos[$repo['name']] = $repo;
          echo '- '. count($repos) . ' repositor' . (count($repos) !== 1 ? 'ies' : 'y') . ' found in GitHub<br />';

          // Get backup folder
          $backupFolder = $drive->ensureFolder(DEFAULT_BACKUP_FOLDER_GITHUB)['id'];

          // Load folders by name
          $folders = [];
          $searchFolders = $drive->search(['q' => 'trashed=false and "' . $backupFolder . '" in parents', 'orderBy' => 'name', 'pageSize' => 1000]);
          foreach ($searchFolders as $folder) $folders[$folder['name']] = $folder;
          echo '- ' . count($folders) . ' folder' . (count($folders) !== 1 ? 's' : '') . ' found in Google Drive<br />';

          // Trash additional files and folders
          foreach ($folders as $folderName => $folder) {

            // No match or no folder > trash
            if (!isset($repos[$folderName]) || $folder['mimeType'] !== 'application/vnd.google-apps.folder') {
              $trash = $drive->trash($folder['id']);
              if ($trash) {
                echo '<span style="color: orange">- Trashed "' . $folder['name'] . '"</span><br />';
                unset($folders[$folderName]);
                $actions += 1;
              } else {
                echo '<span style="color: red">- Failed to trash "' . $folder['name'] . '"</span><br />';
                $errors += 1;
              }
            }

          }

          // Loop repositories
          foreach ($repos as $repoName => $repo) {

            // Finish script five seconds before max runtime exceeded
            if (time() - $scriptStartTime > $maxRuntime - 5) {
              echo 'Maximum runtime of ' . $maxRuntime . ' seconds exceeded<br />';
              if ($errors === 0 && $actions === 0) echo '<b style="color: green">Cronjob finished successfull without any action</b><br />';
              else if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull with ' . $actions . ' action' . ($actions !== 1? 's' : '') . '</b><br />';
              else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';
              unlink($lockFile);
              exit();
            }

            // Create missing repo backup folder
            if (!isset($folders[$repoName])) {
              $created = $drive->createFolder(['name' => $repoName, 'parents' => [$backupFolder]]);
              if ($created) {
                echo '<span style="color: blue">- Created folder "' . $repoName . '"</span><br />';
                $folders[$repoName] = $created;
                $actions += 1;
              } else {
                echo '<span style="color: red">- Failed to create folder "' . $repoName . '"</span><br />';
                $errors += 1;
              }
            }

            // Folder exists
            if (isset($folders[$repoName])) {
              $repoFolderId = $folders[$repoName]['id'];

              // Load files
              $files = [];
              $filesByIdent = [];
              $issueFile = null;
              $repoFile = null;
              $searchFiles = $drive->search(['q' => 'trashed=false and "' . $repoFolderId . '" in parents', 'orderBy' => 'name']);
              foreach ($searchFiles as $file) {
                $files[$file['name']] = $file;
                $filesByIdent[$file['description']] = $file;
              }
              echo '- ' . count($files) . ' file' . (count($files) !== 1 ? 's' : '') . ' found for repo "' . $repoName . '"<br />';

              // Trash additional files
              foreach ($files as $fileName => $file) {

                // Issue.json file
                if ($fileName === 'Issues.json') {
                  $issueFile = $file['id'];

                // Repo zip file
                } else if ($fileName === 'Git Repository.zip') {
                  $repoFile = $file['id'];

                // Additional files
                } else {
                  $trash = $drive->trash($file['id']);
                  if ($trash) {
                    echo '<span style="color: orange">- Trashed "' . $fileName . '"</span><br />';
                    unset($files[$fileName]);
                    $actions += 1;
                  } else {
                    echo '<span style="color: red">- Failed to trash "' . $fileName. '"</span><br />';
                    $errors += 1;
                  }
                }

              }

              /**
               * Update Issues.json
               */

              // Create missing Issues.json file
              if (!$issueFile) {
                $created = $drive->createFile(['name' => 'Issues.json', 'parents' => [$repoFolderId]], '');
                if ($created) {
                  $issueFile = $created['id'];
                  echo '<span style="color: blue">- Created "Issues.json"</span><br />';
                  $actions += 1;
                } else {
                  echo '<span style="color: red">- Failed to create "Issues.json"</span><br />';
                  $errors += 1;
                }
              }

              // Update Issues.json
              if ($issueFile) {

                // Get issues
                $issues = $github->get('repos/' . $repo['full_name'] . '/issues?filter=all&state=all');
                foreach ($issues as $issueKey => $issue) {
                  $issues[$issueKey]['comments'] = $github->get('repos/' . $repo['full_name'] . '/issues/' . $issue['number'] . '/comments');
                }

                // Update file
                $updated = $drive->updateFile($issueFile, [], json_encode($issues));
                if ($updated) {
                  echo '<span style="color: blue">- Updated "Issues.json"</span><br />';
                  $actions += 1;
                } else {
                  echo '<span style="color: red">- Failed to update "Issues.json"</span><br />';
                  $errors += 1;
                }

              }

              /**
               * Update GIT folder
               */

              // Create missing repo file
              if (!$repoFile) {
                $created = $drive->createFile(['name' => 'Git Repository.zip', 'parents' => [$repoFolderId]], '');
                if ($created) {
                  $repoFile = $created['id'];
                  echo '<span style="color: blue">- Created "Git Repository.zip"</span><br />';
                  $actions += 1;
                } else {
                  echo '<span style="color: red">- Failed to create "Git Repository.zip"</span><br />';
                  $errors += 1;
                }
              }

              // Empty tmp folder and tmp file
              if (is_dir('.tmp')) rrmdir('.tmp');
              if (file_exists('.tmp.zip')) unlink('.tmp.zip');

              // Clone repository
              exec('git clone https://' . $github->getToken() . ':x-oauth-basic@github.com/' . strtolower($repo['full_name']) . '.git .tmp');

              // Create zip file
              zipData('.tmp', '.tmp.zip');

              // Update file
              $updated = $drive->updateFile($repoFile, [], file_get_contents('.tmp.zip'));
              if ($updated) {
                echo '<span style="color: blue">- Updated "Git Repository.zip"</span><br />';
                $actions += 1;
              } else {
                echo '<span style="color: red">- Failed to update "Git Repository.zip"</span><br />';
                $errors += 1;
              }

              // Remove tmp folder and tmp file
              if (is_dir('.tmp')) rrmdir('.tmp');
              unlink('.tmp.zip');

            }

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