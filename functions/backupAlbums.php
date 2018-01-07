<?php

  function backupAlbums($googleDriveService) {

    // Load Photos albums
    $url = 'https://picasaweb.google.com/data/feed/api/user/default';
    $albums = performGoogleRequest($url);
    $photosAlbums = [];
    foreach ($albums['entry'] as $album) {
      $id = preg_replace('/^(.+)\/albumid\/([0-9]+)$/', '$2', $album['id']);
      $name = $album['title'];
      $ident = 'photos/album/' . $id;
      $toConsider = preg_match('/^(Auto Backup|Profile Photos|([0-9]{4}-[0-9]{2}-[0-9]{2}))$/', $name) === 0;
      if ($toConsider)  $photosAlbums[$ident] = ['id' => $id, 'name' => $name, 'ident' => $ident, 'photos' => []];
    }

    // Load photos per Photos album
    foreach ($photosAlbums as $album) {
      $url = 'https://picasaweb.google.com/data/feed/api/user/default/albumid/' . $album['id'];
      $photos = performGoogleRequest($url);
      $photoNumberDigits = strlen(count($photos['entry']));
      $photoNumber = 0;
      if (is_array($photos['entry'])) {
        $photosArray = is_array($photos['entry'][0]) ? $photos['entry'] : [$photos['entry']];
        foreach ($photosArray as $photo) {
          $photoNumber += 1;
          $toConsider = preg_match('/\.(jpg|png|gif)$/i', $photo['title']);
          if ($toConsider) {
            $id = preg_replace('/^(.+)\/photoid\/([0-9]+)$/', '$2', $photo['id']);
            $name = $album['name'] . ' #' . str_pad($photoNumber, $photoNumberDigits, '0', STR_PAD_LEFT) . '.' . strtolower(substr($photo['title'], -3));
            $url = $photo['content']['@attributes']['src'] . '?imgmax=9999';
            $ident = 'photos/album/' . $album['id'] . '/photo/' . $id . '/update/' . $photo['updated'] . '/size/9999';
            $photosAlbums[$album['ident']]['photos'][$ident] = [
              'id' => $id,
              'name' => $name,
              'url' => $url,
              'ident' => $ident
            ];
          }
        }
      }
    }

    // Get Drive folder id
    $driveFolderId = getGoogleDriveFolderId(trim($_POST['albumsFolder']), true);

    // Load Drive files recursively
    $driveFiles = getGoogleDriveFiles($driveFolderId, true);

    // Get Drive albums (unique)
    $driveAlbums = [];
    foreach ($driveFiles as $file) {
      $id = $file['id'];
      $name = $file['name'];
      $ident = $file['description'];

      // No dublicate and in Photos albums
      if (!array_key_exists($ident, $driveFiles) and array_key_exists($ident, $photosAlbums)) {

        // Get photos from album (unique)
        $photos = [];
        foreach ($file['files'] as $subFile) {
          $subId = $subFile['id'];
          $subName = $subFile['name'];
          $subUrl = null;
          $subIdent = $subFile['description'];

          // No dublicate and in Photos albums photos
          if (!array_key_exists($subIdent, $photos) and array_key_exists($subIdent, $photosAlbums[$ident]['photos'])) {
            $photos[$subIdent] = ['id' => $subId, 'name' => $subName, 'url' => $subUrl, 'ident' => $subIdent];
          } else {
            // Trash dublicated or additional Drive photo file
            $subTrashed = trashGoogleDriveFile($subId);
            if ($subTrashed)  echo '<span style="color: orange">' . $subName . ' trashed</span><br />';
            else echo '<span style="color: red">Failed to trash ' . $subName . '</span><br />';
          }

        }

        $driveAlbums[$ident] = ['id' => $id, 'name' => $name, 'ident' => $ident, 'photos' => $photos];
      } else {

        // Trash dublicated or additional Drive album file
        $trashed = trashGoogleDriveFile($id);
        if ($trashed)  echo '<span style="color: orange">' . $name . ' trashed</span><br />';
        else echo '<span style="color: red">Failed to trash ' . $name . '</span><br />';

      }
    }

    // Rename updated Drive albums
    foreach ($driveAlbums as $ident => $driveAlbum) {
      if ($driveAlbum['name'] !== $photosAlbums[$ident]['name']) {
        $rename = renameGoogleDriveFile($driveAlbum['id'], $photosAlbums[$ident]['name']);
        if ($rename !== false) echo '<span style="color: green">' . $photosAlbums[$ident]['name']. ' renamed</span><br />';
        else echo '<span style="color: red">Failed to rename ' . $photosAlbums[$ident]['name'] . '</span><br />';
      }
    }

    // Create missing Drive albums
    foreach ($photosAlbums as $ident => $photosAlbum) {
      if (!array_key_exists($ident, $driveAlbums)) {
        $create = createGoogleDriveFolder($photosAlbum['name'], $driveFolderId, $ident);
        if ($create !== false) {
          $driveAlbums[$ident] = ['id' => $create, 'name' => $photosAlbum['name'], 'ident' => $ident, 'photos' => []];
          echo '<span style="color: green">' . $photosAlbum['name'] . ' created</span><br />';
        } else {
          echo '<span style="color: red">Failed to create ' . $photosAlbum['name'] . '</span><br />';
        }
      }
    }

    // Synchronize photos
    foreach ($photosAlbums as $albumIdent => $photosAlbum) {

      // Rename updated Drive photos
      foreach ($driveAlbums[$albumIdent]['photos'] as $drivePhotoIdent => $drivePhoto) {
        if ($drivePhoto['name'] !== $photosAlbum['photos'][$drivePhotoIdent]['name']) {
          $rename = renameGoogleDriveFile($drivePhoto['id'], $photosAlbum['photos'][$drivePhotoIdent]['name']);
          if ($rename !== false) echo '<span style="color: green">' . $photosAlbum['photos'][$drivePhotoIdent]['name']. ' renamed</span><br />';
          else echo '<span style="color: red">Failed to rename ' . $photosAlbum['photos'][$drivePhotoIdent]['name'] . '</span><br />';
        }
      }

      // Create missing Drive photos
      foreach ($photosAlbum['photos'] as $photoIdent => $photosPhoto) {
        if (!array_key_exists($photoIdent, $driveAlbums[$albumIdent]['photos'])) {
          $upload = uploadFileToGoogleDrive($googleDriveService, $driveAlbums[$albumIdent]['id'], $photosPhoto['name'], file_get_contents($photosPhoto['url']), $photoIdent);
          if ($upload !== false) {
            $driveAlbums[$albumIdent]['photos'][$photoIdent] = ['id' => $create, 'name' => $photosPhoto['name'], 'url' => null, 'ident' => $photoIdent];
            echo '<span style="color: green">' . $photosPhoto['name'] . ' created</span><br />';
          } else {
            echo '<span style="color: red">Failed to create ' . $photosPhoto['name'] . '</span><br />';
          }
        }
      }

    }

  }

?>