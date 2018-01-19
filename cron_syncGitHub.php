<?php

  /**
   * Purpose: Sync GitHub repositories and issues per user to Google Drive
   */

  // Common includes and object initializations
  require('common.php');

   /**
   * Lock cronjob
   */

  $lockFile = 'cron_syncGitHub.lock.log';
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
          $searchRepos = $github->get('user/repos');
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
              } else echo '<span style="color: red">- Failed to trash "' . $folder['name'] . '"</span><br />';
            }

          }

          // Loop repositories
          foreach ($repos as $repoName => $repo) {

            // Create missing repo backup folder
            if (!isset($folders[$repoName])) {
              $created = $drive->createFolder(['name' => $repoName, 'parents' => [$backupFolder]]);
              if ($created) {
                echo '<span style="color: blue">- Created folder "' . $repoName . '"</span><br />';
                $folders[$repoName] = $created;
              } else {
                echo '<span style="color: red">- Failed to create folder "' . $repoName . '"</span><br />';
                $errors += 1;
              }
            }

            // Folder exists
            if (isset($folders[$repoName])) {
              $repoFolderId = $folders[$repoName]['id'];

              // Load branches (name, commit(sha,url))
              $branches = [];
              $branchesByIdent = [];
              $searchBranches = $github->get('repos/' . $repo['full_name'] . '/branches');
              foreach ($searchBranches as $branch) {
                $branches[$branch['name']] = $branch;
                $ident = 'branch/' . $branch['name'] . '/sha/' . $branch['commit']['sha'];
                $branchesByIdent[$ident] = $branch;
              }
              echo '- ' . count($branches) . ' branch' . (count($branches) !== 1 ? 'es' : '') . ' found for "' . $repoName . '"<br />';

              // Load files
              $files = [];
              $filesByIdent = [];
              $issueFile = null;
              $searchFiles = $drive->search(['q' => 'trashed=false and "' . $repoFolderId . '" in parents', 'orderBy' => 'name', 'pageSize' => 1000]);
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

                // Not in $branchesByIdent
                } else if (!array_key_exists($file['description'], $branchesByIdent)) {
                  $trash = $drive->trash($file['id']);
                  if ($trash) {
                    echo '<span style="color: orange">- Trashed "' . $fileName . '"</span><br />';
                    unset($files[$fileName]);
                  } else {
                    echo '<span style="color: red">- Failed to trash "' . $fileName. '"</span><br />';
                    $errors += 1;
                  }
                }

              }

              // Create missing Issues.json file
              if (!$issueFile) {
                $created = $drive->createFile(['name' => 'Issues.json', 'parents' => [$repoFolderId]], '');
                if ($created) {
                  $issueFile = $created['id'];
                  echo '<span style="color: blue">- Created "Issues.json"</span><br />';
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
                } else {
                  echo '<span style="color: red">- Failed to update "Issues.json"</span><br />';
                  $errors += 1;
                }

              }

              /*
              // Save missing branch zip files
              foreach ($branchesByIdent as $ident => $branch) {
                if (!in_array($ident, $filesByIdent)) {
                  $zipFileContent = file_get_contents('https://api.github.com/repos/' . strtolower($repo['full_name']) . '/zipball/' . $branch['name'] . '?access_token=' . $github->getCredentials()['access_token']);
                  $created = $drive->createFile(['name' => $branch['name'] . '.zip', 'parents' => [$repoFolderId], 'description' => $ident], $zipFileContent);
                  if ($created) {
                    echo '<span style="color: blue">- Created "' . $branch['name'] . '.zip"</span><br />';
                  } else {
                    echo '<span style="color: red">- Failed to create "' . $branch['name'] . '.zip"</span><br />';
                    $errors += 1;
                  }
                }
              }
              */

            }

          }

        }
      }
    }
  }

  /**
   * Show final log
   */

  if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull</b><br />';
  else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';

  /**
   * Unlock
   */

  unlink($lockFile);

?>