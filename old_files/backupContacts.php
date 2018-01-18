<?php

  function backupContacts($googleDriveService) {

    // Remove duplicated files (remove the identifier, so they will be trashed later on)
    $uniqueDriveIdents = [];
    foreach ($driveIdents as $fileId => $file) {
      if (in_array($file['ident'], $uniqueDriveIdents)) {
        $driveIdents[$fileId]['ident'] = '';
      } else {
        $uniqueDriveIdents[] = $file['ident'];
      }
    }

    // Remove additional files (ident not found in contacts anymore)
    foreach ($driveIdents as $file) {
      if (!in_array($file['ident'], $contactsIdents)) {
        $trashed = trashGoogleDriveFile($file['id']);
        if ($trashed) echo '<span style="color: orange">' . $file['name'] . ' trashed</span><br />';
        else echo '<span style="color: red">Failed to trash ' . $file['name'] . '</span><br />';
      }
    }

    // Create missing files
    foreach ($contacts as $contact) {
      $ident = $contact['resourceName'] . '/' . $contact['etag'];
      if (!in_array($ident, $uniqueDriveIdents) && (isset($contact['names'][0]['displayName']) or isset($contact['emailAddresses'][0]['value']))) {
        $fileName = (isset($contact['names'][0]['displayName']) ? $contact['names'][0]['displayName'] : $contact['emailAddresses'][0]['value']) . '.vcf';
        $vCard = convertGoogleContactToVCard($contact);
        $upload = uploadFileToGoogleDrive($googleDriveService, $folderId, $fileName, $vCard, $contact['resourceName'] . '/' . $contact['etag']);
        if ($upload !== false) echo '<span style="color: green">' . $fileName . ' created</span><br />';
        else echo '<span style="color: red">Failed to upload ' . $fileName . '</span><br />';
      }
    }

  }

?>