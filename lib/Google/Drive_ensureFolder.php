<?php

  /**
   * Purpose: Ensure that a folder (stucture) exists in Google Drive
   * Input: <array> $properties
   * Output: <array> $folder or false
   */

  // Check input
  if (!is_string($properties) && !is_array($properties)) throw new Exception('Argument $properties must be a string or an array');

  // Function to ensure folder
  if (!function_exists('ensureFolder')) {
    function ensureFolder($properties, $class) {

      // Get parent
      $parent = is_array($properties) && is_array($properties['parents']) ? $properties['parents'][0] : 'root';

      // Get name
      $name = is_array($properties) ? $properties['name'] : $properties;

      // Search for folder
      $search = $class->search([
        'q' => 'trashed=false and mimeType="application/vnd.google-apps.folder" and "' . $parent . '" in parents and name="' . $name . '"',
        'orderBy' => 'name',
        'pageSize' => 1
      ]);

      // Found
      if (count($search) === 1) {
        return $search[0];

      // Not Found
      } else {
        return $class->createFolder($properties);
      }

    }
  }

  // Function to ensure folder recursively
  if (!function_exists('ensureFolderRecursively')) {
    function ensureFolderRecursively($folders, $parent, $class) {
      // Force parent to be an array
      if (is_string($parent)) $parent = ['id' => $parent];
      // No more folders
      if (count($folders) === 0) return $parent;
      // Get next folder
      $nextFolder = trim(array_shift($folders));
      // Next folder empty
      if ($nextFolder === '') return ensureFolderRecursively($folders, $parent, $class);
      // Ensure folder
      $folder = ensureFolder(['name' => $nextFolder, 'parents' => [$parent['id']]], $class);
      return ensureFolderRecursively($folders, $folder, $class);
    }
  }

  // Path provided
  if (is_string($properties) && strpos($properties, '/') > -1) {
    $folder = ensureFolderRecursively(explode('/', $properties), 'root', $this);

  // No path provided
  } else {
    $folder = ensureFolder($properties, $this);
  }

?>