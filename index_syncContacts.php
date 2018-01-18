<?php

  /**
   * Purpose: Sync Contacts in steps, reload script for each step / log each step
   *
   * Steps
   * - Load all contacts
   * - Load all files
   * - Loop all files
   *   - Trash not matching files
   * - Loop all contacts
   *   - for each
   *     - Create missing files
   */

  /**
   * Log functions
   */

  function logText($text) {
    $file = fopen('syncAlbums.log', 'a+');
    fwrite($file, date('d.m.Y H:i:s') . ' - ' . $text . "\r\n");
    fclose($file);
  }

  /**
   * State management
   */

  $stateStep = isset($_GET['step']) ? $_GET['step'] : null;
  $stateContact = isset($_GET['contact']) ? intval($_GET['contact']) : null;
  function nextState($step, $contact = null) {
    $uri = 'index.php?action=syncContacts&step=' . $step
         . ($contact ? '&contact=' . $contact : '');
    header('Refresh: 0; url=' . $uri);
  }

  /**
   * No step
   */

  if (!$stateStep) nextState('clean-up');

  /**
   * Clean-up
   */

  if ($stateStep === 'clean-up') {

    // Clear session
    unset($_SESSION['contacts']);
    unset($_SESSION['files']);

    // Delete log file
    if (file_exists('syncAlbums.log')) unlink('syncContacts.log');

    // Log
    logText('Clean-up done');

    // Next state
    nextState('loadContacts');

  }

  /**
   * Load contacts
   */

  if ($stateStep === 'loadContacts') {

    // Load contacts, add ident
    $_SESSION['contacts'] = [];
    $allContacts = $contacts->getContacts();
    foreach ($allContacts as $contact) {
      $_SESSION['contacts'][$contact['ident']] = $contact;
    }

    // Log
    logText(count($_SESSION['contacts']) . ' contact' . (count($_SESSION['contacts']) !== 1 ? 's' : '') . ' found in Google Contacts');

    // Next state
    nextState('loadFiles');

  }

  /**
   * Load files
   */

  if ($stateStep === 'loadFiles') {

    // Get folder id
    $folderId = $drive->ensureFolder(DEFAULT_BACKUP_FOLDER_CONTACTS);

    // List files
    $allFiles = $drive->search(['q' => '"' . $folderId . '" in parents and trashed=false', 'orderBy' => 'name']);
    $_SESSION['files'] = [];
    foreach ($allFiles as $file) {
      $ident = $file['description'] ? $file['description'] : $file['id'];
      $_SESSION['files'][$ident] = $file;
    }

    // Log
    logText(count($_SESSION['files']) . ' file' . (count($_SESSION['files']) !== 1 ? 's' : '') . ' found in Google Drive');

    // Next state
    nextState('loopFiles');

  }

  /**
   * Loop files
   */

  if ($stateStep === 'loopFiles') {

    foreach ($_SESSION['files'] as $ident => $file) {
      echo $ident . ': ' . $file['name'] . '<br />';
    }

  }

  /**
   * Completed
   */

  if ($stateStep === 'completed') {

    // Clear session
    unset($_SESSION['contacts']);
    unset($_SESSION['files']);
    logText('Cleaned-up the session');

    logText('Synchronization completed successfully');

    rename('syncContacts.log', 'syncContacts - ' . date('d.m.Y H:i:s') . '.log');

    echo '<p><b>Synchronization completed successfully</b></p>'
       . '<p><a href="index.php">go to index page</a></p>';

  }

?>