<?php

  /**
   * Purpose: Create a folder (structure) in Google Drive
   * Input: <array> $properties
   * Output: <array> $folder or false
   */

  // Check input
  if (!is_string($properties) && !is_array($properties)) throw new Exception('Argument $properties must be a string or an array');

  // Function to create new folder
  if (!function_exists('createFolder')) {
    function createFolder($properties, $token) {

       // Ensure $properties array (if only name passed as string)
      $properties = is_array($properties) ? $properties : ['name' => $properties];

      // Set correct mime type
      $properties['mimeType'] = 'application/vnd.google-apps.folder';

      // Create REST URI
      $restUri = 'https://www.googleapis.com/drive/v3/files'
               . '?access_token=' . $token
               . '&uploadType=multipart';

      // Perform cURL request
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => $restUri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($properties)
      ]);
      $response = curl_exec($curl);
      if ($response !== false) {
        $folder = json_decode($response, true);
      } else {
        $folder = false;
      }
      curl_close($curl);
      return $folder;

    }
  }

  // Function to create folder recursively
  if (!function_exists('createFolder')) {
    function createFolderRecursively($folders, $parent, $token) {
      // Force parent to be an array
      if (is_string($parent)) $parent = ['id' => $parent];
      // No more folders
      if (count($folders) === 0) return $parent;
      // Get next folder
      $nextFolder = trim(array_shift($folders));
      // Next folder empty
      if ($nextFolder === '') return createFolderRecursively($folders, $parent, $token);
      // Create new folder
      $folder = createFolder(['name' => $nextFolder, 'parents' => [$parent['id']]], $token);
      return createFolderRecursively($folders, $folder, $token);
    }
  }

  // Path provided (= new folder structure)
  if (is_string($properties) && strpos($properties, '/') > -1) {
    $folder = createFolderRecursively(explode('/', $properties), 'root', $this->token);
    $this->touch($folder['id']);

  // No path provided
  } else {
    $folder = createFolder($properties, $this->token);
    $this->touch($folder['id']);
  }

?>