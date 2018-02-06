<?php

  /**
   * Purpose: Sync all Google Photo albums to Google drive for saved credentials
   */

  /**
   * Common settings, Google object initialization
   */
  require('common.php');
  $maxRuntime = 1800; // not more than script runtime or token expiry

  $errors = 0;
  $actions = 0;

  /**
   * Lock cronjob
   */

  $lockFile = 'cron_syncAlbums.lock.log';
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

  // Loop files in folder
  $dir = '.credentials';
  if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
      if (substr($file, -11) === '.google.php') {

        // Extract credentials
        $content = file_get_contents($dir . '/' . $file);
        preg_match('/\/\/(.+)\\n/', $content, $search);
        $credentials = json_decode(trim($search[1]), true);

        // Update token
        $auth->setCredentials($credentials);
        $photos->setToken($auth->getToken());
        $drive->setToken($auth->getToken());

        /**
         * Synchronization
         */

        // Show user name
        echo 'Albums synchronization starts for <b>'. $auth->getUserInfo()['displayName'] . '</b><br />';

        // Load albums, add ident
        $albums = [];
        foreach ($photos->getAlbums() as $album) {
          $ident = 'album/' . $album['id'];
          $albums[$ident] = $album;
        }
        echo '- '. count($albums) . ' album' . (count($albums) !== 1 ? 's' : '') . ' found in Google Photos<br />';

        // Get backup folder
        $folderId = $drive->ensureFolder(DEFAULT_BACKUP_FOLDER_ALBUMS)['id'];

        // Load unqiue sub folders, add ident
        $folders = [];
        $foldersSearch = $drive->search(['q' => 'trashed=false and "' . $folderId . '" in parents', 'orderBy' => 'name']);
        foreach ($foldersSearch as $folder) {
          $ident = (!$folder['description'] || $folder['description'] === '' || array_key_exists($folder['description'], $folders)) ? $folder['id'] : $folder['description'];
          $folders[$ident] = $folder;
        }
        echo '- ' . count($folders) . ' folder' . (count($folders) !== 1 ? 's' : '') . ' found in Google Drive<br />';

        // Loop folders
        foreach ($folders as $ident => $folder) {

          // No match or no folder > trash
          if (!isset($albums[$ident]) || $folder['mimeType'] !== 'application/vnd.google-apps.folder') {
            $trash = $drive->trash($folder['id']);
            if ($trash) {
              echo '<span style="color: orange">- Trashed folder "' . $folder['name'] . '"</span><br />';
              unset($folders[$ident]);
              $actions += 1;
            } else {
              echo '<span style="color: red">- Failed to trash folder "' . $folder['name'] . '"</span><br />';
              $errors += 1;
            }

          // Name changed > rename
          } else if ($folder['name'] !== $albums[$ident]['name']) {
            $rename = $drive->rename($folder['id'], $albums[$ident]['name']);
            if ($rename) {
              echo '<span style="color: blue">- Renamed folder "' . $folder['name'] . '" to "' . $albums[$ident]['name'] . '"</span><br />';
              $folders[$ident]['name'] = $albums[$ident]['name'];
              $actions += 1;
            } else {
              echo '<span style="color: red">- Failed to rename folder "' . $folder['name'] . '"</span><br />';
              $errors += 1;
            }
          }

        }

        // Loop albums
        foreach ($albums as $ident => $album) {

          // Create missing folders
          if (!isset($folders[$ident])) {
            $newFolder = $drive->createFolder([
              'name' => $album['name'],
              'parents' => [$folderId],
              'description' => $ident
            ]);
            if ($newFolder) {
              echo '<span style="color: blue">- Created folder "' . $album['name'] . '"</span><br />';
              $folders[$ident] = $newFolder;
              $backupFolderUpdated = true;
              $actions += 1;
            } else {
              echo '<span style="color: red">- Failed to create folder "' . $album['name'] . '"</span><br />';
              $errors += 1;
            }
          }

          // If album exists (exclude albums where folder creation failed before)
          if (isset($folders[$ident])) {

            // Get folder information
            $folder = $folders[$ident];
            $folderUpdated = false;

            // Load all photos, add identifier and filename > log
            $allPhotos = [];
            $getPhotos = $photos->getPhotos($album['id']);
            $photoNo = 0;
            if (is_array($getPhotos)) {
              foreach ($getPhotos as $currentPhoto) {
                $photoNo++;
                if (substr($currentPhoto['mimeType'], 0, 6) === 'image/') {
                  $ext = str_replace('jpeg', 'jpg', substr($currentPhoto['mimeType'], 6));
                  $currentPhoto['fileName'] = $album['name'] . ' #' . str_pad($photoNo, strlen(count($getPhotos)), '0', STR_PAD_LEFT) . '.' . $ext;
                  $photoIdent = 'album/' . $album['id'] . '/photo/' . $currentPhoto['id'] . '/updated/' . $currentPhoto['updated'];
                  $allPhotos[$photoIdent] = $currentPhoto;
                } else {
                  echo '<span style="color: orange">- Skipped file ' . $currentPhoto['name'] . '</span><br />';
                }
              }
            }
            echo '- ' . count($allPhotos) . ' photo' . (count($allPhotos) !== 1 ? 's' : '') . ' found in album "' . $album['name'] . '"<br />';

            // Load all files, add identifier > log
            $allFiles = [];
            $filesSearch = $drive->search(['q' => 'trashed=false and "' . $folder['id'] . '" in parents', 'orderBy' => 'name']);
            foreach ($filesSearch as $currentFile) {
              $ident = (!$currentFile['description'] || $currentFile['description'] === '' || array_key_exists($currentFile['description'], $allFiles)) ? $currentFile['id'] : $currentFile['description'];
              $allFiles[$ident] = $currentFile;
            }
            echo '- ' . count($allFiles) . ' file' . (count($allFiles) !== 1 ? 's' : '') . ' found in folder "' . $folder['name'] . '"<br />';

            // Loop files
            foreach ($allFiles as $fileIdent => $file) {

              // Finish script five seconds before max runtime exceeded
              if (time() - $scriptStartTime > $maxRuntime - 5) {
                echo 'Maximum runtime of ' . $maxRuntime . ' seconds exceeded<br />';
                if ($errors === 0 && $actions === 0) echo '<b style="color: green">Cronjob finished successfull without any action</b><br />';
                else if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull with ' . $actions . ' action' . ($actions !== 1? 's' : '') . '</b><br />';
                else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';
                unlink($lockFile);
                exit();
              }

              // No match > trash
              if (!isset($allPhotos[$fileIdent])) {

                $trash = $drive->trash($file['id']);
                if ($trash) {
                  echo '<span style="color: orange">- Trashed file "' . $file['name'] . '"</span><br />';
                  $backupFolder = true;
                  $actions += 1;
                } else {
                  echo '<span style="color: red">- Failed to trash file "' . $file['name'] . '"</span><br />';
                  $errors += 1;
                }

              // Name changed > rename
              } else if ($file['name'] !== $allPhotos[$fileIdent]['fileName']) {
                $rename = $drive->rename($file['id'], $allPhotos[$fileIdent]['fileName']);
                if ($rename) {
                  echo '<span style="color: blue">- Renamed file "' . $file['name'] . '" to "' . $allPhotos[$fileIdent]['fileName'] . '"</span><br />';
                  $backupFolder = true;
                  $actions += 1;
                } else {
                  echo '<span style="color: red">- Failed to rename file "' . $file['name'] . '"</span><br />';
                  $errors += 1;
                }
              }

            }

            // Loop photos
            foreach ($allPhotos as $photoIdent => $photo) {

              // Create missing photos
              if (!isset($allFiles[$photoIdent])) {
                $newPhoto = $drive->createFile([
                  'name' => $photo['fileName'],
                  'parents' => [$folder['id']],
                  'description' => $photoIdent
                ], file_get_contents($photo['uri'] . '?imgmax=9999'));
                if ($newPhoto) {
                  echo '<span style="color: blue">- Created file "' . $photo['fileName'] . '"</span><br />';
                  $backupFolder = true;
                  $actions += 1;
                } else {
                  echo '<span style="color: red">- Failed to create file "' . $photo['fileName'] . '"</span><br />';
                  $errors += 1;
                }
              }

              // Finish script ten seconds before max runtime exceeded
              if (time() - $scriptStartTime > $maxRuntime - 10) {
                echo 'Maximum runtime of ' . $maxRuntime . ' seconds is nearly exceeded<br />';
                if ($errors === 0 && $actions === 0) echo '<b style="color: green">Cronjob finished successfull without any action</b><br />';
                else if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull with ' . $actions . ' action' . ($actions !== 1? 's' : '') . '</b><br />';
                else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';
                unlink($lockFile);
                exit();
              }

            }

          }

        }

      }
    }
  }

  if ($errors === 0 && $actions === 0) echo '<b style="color: green">Cronjob finished successfull without any action</b><br />';
  else if ($errors === 0) echo '<b style="color: green">Cronjob finished successfull with ' . $actions . ' action' . ($actions !== 1? 's' : '') . '</b><br />';
  else echo '<b style="color: red">Cronjob finished with ' . $errors . ' error' . ($errors !== 1? 's' : '') . '</b><br />';

  // Unlock cronjob
  unlink($lockFile);

?>
