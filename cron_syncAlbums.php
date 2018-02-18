<?php

  /**
   * Purpose: Sync all Google Photo albums to Google drive for saved credentials
   */

  class albumsSync {

    private $startTime;
    private $maxRuntime = 580; // should be 20 seconds less than the cronjob interval
    private $logs = [];
    private $errors = 0;
    private $actions = 0;
    private $gAuth;
    private $gDrive;
    private $gPhotos;
    private $folderId;
    private $userId;
    private $albums;
    private $photos;
    private $skipNotUpdatedAlbums = false; // The updated date is not reliable for photo edits inside but improves performance

    public function start() {

      header('Content-type: text/html;charset=utf-8');
      date_default_timezone_set('Europe/Berlin');

      $this->startTime = time();
      $this->lockScript();
      $this->log('Started synchronization script');
      
      require('config.php');
      require('lib/Google/Auth.php');
      require('lib/Google/Drive.php');
      require('lib/Google/Photos.php');

      $this->gAuth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
      $this->gAuth->addScope('https://www.googleapis.com/auth/drive');
      $this->gAuth->addScope('https://picasaweb.google.com/data/');
      $this->gAuth->addScope('profile');
      $this->gDrive = new \Google\Drive($this->gAuth->getToken());
      $this->gPhotos = new \Google\Photos($this->gAuth->getToken());

      $this->doSync();

    }

    private function stop($unlock = true) {
      $this->writeCache();
      $this->printLogs();
      if ($unlock !== false) $this->unlockScript();
      exit;
    }

    private function getLockFileName() {
      $name = str_replace('.php', '.lock', $_SERVER['SCRIPT_FILENAME']);
      return $name;
    }

    private function lockScript() {
      $lockFile = $this->getLockFileName();
      if (file_exists($lockFile)) {
        $start = intval(file_get_contents($lockFile));
        $duration = $this->startTime - $start;
        if ($duration > $this->maxRuntime + 5 * 60) {
          $this->unlockScript();
          $this->log('Unlocked script automatically');
        } else {
          $this->log('Script locked for ' . $duration . ' seconds now');
          $this->stop(false);
        }
      } else {
        file_put_contents($lockFile, $this->startTime);
      }
    }

    private function unlockScript() {
      $name = $this->getLockFileName();
      if (file_exists($name)) unlink($name);
    }

    private function getCredentials() {
      $credentials = [];
      $dir = '.credentials';
      if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
          if (substr($file, -11) === '.google.php') {
            $content = file_get_contents($dir . '/' . $file);
            preg_match('/\/\/(.+)\\n/', $content, $search);
            $credentials[] = json_decode(trim($search[1]), true);
          }
        }
      }
      return $credentials;
    }

    private function doSync() {
      // Loop users
      $credentials = $this->getCredentials();
      foreach ($credentials as $credential) {

        $this->checkRuntime();

        // Update credentials
        $this->gAuth->setCredentials($credential);
        $this->gDrive->setToken($this->gAuth->getToken());
        $this->gPhotos->setToken($this->gAuth->getToken());
        $userInfo = $this->gAuth->getUserInfo();
        $this->userId = $userInfo['id'];

        // Read cache
        $this->readCache();

        // Log current user name
        $this->log('<b>Started synchronization for albums of ' . $userInfo['displayName'] . '</b>');

        // Get backup folder
        $this->folderId = $this->gDrive->ensureFolder(DEFAULT_BACKUP_FOLDER_ALBUMS)['id'];

        // Sync albums
        $this->syncAlbums();
        
        // Write cache
        $this->writeCache();

      }
      $this->stop();
    }

    private function syncAlbums() {
      // Load albums, add ident and lastSync from cache
      $albums = [];
      foreach ($this->gPhotos->getAlbums() as $album) {
        $ident = 'album/' . $album['id'];
        $albums[$ident] = $album;
        if (isset($this->albums[$ident]['lastSync'])) $albums[$ident]['lastSync'] = $this->albums[$ident]['lastSync'];
      }
      $this->albums = $albums;
      $this->log(count($albums) . ' album' . (count($albums) !== 1 ? 's' : '') . ' found in Google Photos');

      $this->checkRuntime();

      // Load unique sub folders, add ident
      $folders = [];
      $foldersSearch = $this->gDrive->search(['q' => 'trashed=false and "' . $this->folderId . '" in parents', 'orderBy' => 'name']);
      foreach ($foldersSearch as $folder) {
        $ident = (!$folder['description'] || $folder['description'] === '' || array_key_exists($folder['description'], $folders)) ? $folder['id'] : $folder['description'];
        $folders[$ident] = $folder;
      }
      $this->log(count($folders) . ' folder' . (count($folders) !== 1 ? 's' : '') . ' found in Google Drive');

      // Loop folders
      foreach ($folders as $ident => $folder) {

        $this->checkRuntime();

        // Album found for folder
        if (isset($this->albums[$ident])) {

          // Update folder ID
          $this->albums[$ident]['folderId'] = $folder['id'];

          // Folder name is different to album name
          if ($folder['name'] !== $this->albums[$ident]['name']) {

            // Rename
            $rename = $this->gDrive->rename($folder['id'], $this->albums[$ident]['name']);
            if ($rename) $this->log('Renamed folder "' . $folder['name'] . '" to "' . $this->albums[$ident]['name'] . '"');
              else $this->log('Failed to rename folder "' . $folder['name'] . '"');

          }

        // No album found for folder
        } else {

          // Trash folder
          $trash = $this->gDrive->trash($folder['id']);
          if ($trash) $this->log('Trashed folder "' . $folder['name'] . '"');
            else $this->log('Failed to trash folder "' . $folder['name'] . '"');

        }

      }

      // Loop albums
      foreach ($this->albums as $ident => $album) {
        
        if ($this->skipNotUpdatedAlbums === true) {
          if ($album['updated'] === $album['lastSync']) {
            $this->log('Album "' . $album['name'] . '" is up-to-date');
          } else {
            $this->syncAlbum($ident, $album);
          }
        } else {
          $this->syncAlbum($ident, $album);
        }

      }
    }

    private function syncAlbum($albumIdent, $album) {

      $this->checkRuntime();

      // Create missing folders
      if (!isset($album['folderId'])) {
        $newFolder = $this->gDrive->createFolder([
          'name' => $album['name'],
          'parents' => [$this->folderId],
          'description' => $albumIdent
        ]);
        if ($newFolder) {
          $this->log('Created folder "' . $album['name'] . '"');
          $this->albums[$albumIdent]['folderId'] = $newFolder['id'];
          $album['folderId'] = $newFolder['id'];
        } else {
          $this->log('Failed to create folder "' . $album['name'] . '"');
        }
      }

      $this->checkRuntime();

      // Load all photos, add identifier and filename
      $allPhotos = [];
      $getPhotos = $this->gPhotos->getPhotos($album['id']);
      $photoNo = 0;
      if (is_array($getPhotos)) {
        foreach ($getPhotos as $currentPhoto) {

          $this->checkRuntime();

          $photoNo++;
          if (substr($currentPhoto['mimeType'], 0, 6) === 'image/') {
            $ext = str_replace('jpeg', 'jpg', substr($currentPhoto['mimeType'], 6));
            $currentPhoto['fileName'] = $album['name'] . ' #' . str_pad($photoNo, strlen(count($getPhotos)), '0', STR_PAD_LEFT) . '.' . $ext;
            $photoIdent = 'album/' . $album['id'] . '/photo/' . $currentPhoto['id'];
            if ($this->photos[$album['id']][$photoIdent]['updated'] === $currentPhoto['updated'] && isset($this->photos[$album['id']][$photoIdent]['hash'])) {
              $currentPhoto['hash'] = $this->photos[$album['id']][$photoIdent]['hash'];
            } else {
              $currentPhoto['hash'] = sha1_file($currentPhoto['uri'] . '?imgmax=80');
              $this->log('Get hash for photo "' . $currentPhoto['fileName'] . '"');
            }
            $allPhotos[$photoIdent] = $currentPhoto;
            $this->photos[$album['id']][$photoIdent] = $currentPhoto;
          } else {
            $this->log('Skipped file "' . $currentPhoto['name'] . '"');
          }
        }
      }
      $this->log(count($allPhotos) . ' photo' . (count($allPhotos) !== 1 ? 's' : '') . ' found in album "' . $album['name'] . '"');
      $this->photos[$album['id']] = $allPhotos;

      // Load all files, add identifier > log
      $allFiles = [];
      $filesSearch = $this->gDrive->search(['q' => 'trashed=false and "' . $album['folderId'] . '" in parents', 'orderBy' => 'name']);
      foreach ($filesSearch as $currentFile) {
        $ident = (!$currentFile['description'] || $currentFile['description'] === '' || array_key_exists($currentFile['description'], $allFiles)) ? $currentFile['id'] : $currentFile['description'];
        if (strpos($ident, '/hash/') > -1) $currentFile['hash'] = substr($ident, strpos($ident, '/hash/') + 6);
        $ident = strpos($ident, '/hash/') > -1 ? substr($ident, 0, strpos($ident, '/hash/')) : $ident;
        $allFiles[$ident] = $currentFile;
      }
      $this->log(count($allFiles) . ' file' . (count($allFiles) !== 1 ? 's' : '') . ' found in folder "' . $album['name'] . '"');

      $this->checkRuntime();

      // Workaround to avoid deletion of all photos
      // Run only if there are more than zero photos
      // To empty/trash album, trash it in Google Photos!
      if (count($allPhotos) > 0) {
        $this->syncPhotos($albumIdent, $allPhotos, $allFiles);
      }

    }

    private function syncPhotos($albumIdent, $photos, $files) {

      // Loop files
      foreach ($files as $ident => $file) {

        $this->checkRuntime();

        // File ident not in photos or hash different
        if (!array_key_exists($ident, $photos) || $file['hash'] !== $photos[$ident]['hash']) {

          // Trash
          $trash = $this->gDrive->trash($file['id']);
          if ($trash) $this->log('Trashed file "' . $file['name'] . '"');
            else $this->log('Failed to trash file "' . $file['name'] . '"');

        // File has different name
        } else if ($file['name'] !== $photos[$ident]['fileName']) {

          // Rename
          $rename = $this->gDrive->rename($file['id'], $photos[$ident]['fileName']);
          if ($rename) $this->log('Renamed file "' . $file['name'] . '" to "' . $photos[$ident]['fileName'] . '"');
            else $this->log('Failed to rename file "' . $file['name'] . '"');

        }

      }

      // Loop photos
      foreach ($photos as $ident => $photo) {

        $this->checkRuntime();

        // Create missing or updated photos
        if (!array_key_exists($ident, $files) || $photo['hash'] !== $files[$ident]['hash']) {
          $newPhoto = $this->gDrive->createFile([
            'name' => $photo['fileName'],
            'parents' => [$this->albums[$albumIdent]['folderId']],
            'description' => $ident . '/hash/' . $photo['hash']
          ], file_get_contents($photo['uri'] . '?imgmax=9999'));
          if ($newPhoto) $this->log('Created file "' . $photo['fileName'] . '"');
            else $this->log('Failed to create file "' . $photo['fileName'] . '"');
        }

      }

      $this->albums[$albumIdent]['lastSync'] = $this->albums[$albumIdent]['updated'];
      
    }

    private function checkRuntime() {
      $now = time();
      if ($now - $this->startTime >= $this->maxRuntime) {
        $this->log('Stopped script automatically, the maximum runtime of ' . $this->maxRuntime . ' seconds is reached');
        $this->stop();
      }
    }

    private function readCache() {
      if ($this->userId !== null) {
        $name = '.cache/' . $this->userId . '.albums';
        $this->albums = file_exists($name) ? unserialize(file_get_contents($name)) : [];
      }
      if ($this->userId !== null) {
        $name = '.cache/' . $this->userId . '.photos';
        $this->photos = file_exists($name) ? unserialize(file_get_contents($name)) : [];
      }
    }

    private function writeCache() {
      if (!is_dir('.cache')) mkdir('.cache');
      if ($this->userId !== null && $this->albums !== null) {
        $name = '.cache/' . $this->userId . '.albums';
        file_put_contents($name, serialize($this->albums));
      }
      if ($this->userId !== null && $this->photos !== null) {
        $name = '.cache/' . $this->userId . '.photos';
        file_put_contents($name, serialize($this->photos));
      }
    }

    private function log($text) {
      $timeStr = '<span style="color: grey">' . date('H:i:s') . ' - </span>';
      $text = $timeStr . $text;
      if (strpos($text, 'Failed') > -1) {
        $this->logs[] = '<span style="color: red">' . $text . '</span>';
        $this->errors += 1;
      } else if (strpos($text, 'Created') > -1) {
        $this->logs[] = '<span style="color: green">' . $text . '</span>';
        $this->actions += 1;
      } else if (strpos($text, 'Updated') > -1 || strpos($text, 'Renamed') > -1) {
        $this->logs[] = '<span style="color: blue">' . $text . '</span>';
        $this->actions += 1;
      } else if (strpos($text, 'Trashed') > -1) {
        $this->logs[] = '<span style="color: orange">' . $text . '</span>';
        $this->actions += 1;
      } else {
        $this->logs[] = $text;
      }
    }

    private function printLogs() {
      echo '<html><head></head><body>';
      echo implode('<br />', $this->logs);
      if (count($this->logs) > 0) echo '<br />';
      if ($this->errors > 0) {
        echo '<span style="color: red">Cronjob finished with ' . $this->errors . ' error' . ($this->errors !== 1 ? 's' : '') . '</span>';
      } else if ($this->actions > 0) {
        echo '<span style="color: green">Cronjob finished successfull with ' . $this->actions . ' action' . ($this->actions !== 1 ? 's' : '') . '.</span>';
      } else {
        echo '<span style="color: green">Cronjob finished successfull without any action.</span>';
      }
      echo '</body></html>';
    }

    private function debug($input) {
      echo '<pre>';
      print_r($input);
      echo '</pre>';
    }

  }

  $sync = new albumsSync;
  $sync->start();

?>
