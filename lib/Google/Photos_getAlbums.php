<?php

  /**
   * Purpose: Get list of Google Photos albums
   * Output: <array> $albums
   */

  // Create REST URI
  $restUri = 'https://picasaweb.google.com/data/feed/api/user/default'
           . '?alt=json'
           . '&access_token=' . $this->token;

  // Perform cURL request
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $restUri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true
  ]);
  $response = curl_exec($curl);
  if ($response !== false) {
    $albums = [];
    $albumsRaw = json_decode($response, true)['feed']['entry'];
    foreach ($albumsRaw as $albumRaw) {
      $album = [
        'id' => $albumRaw['gphoto$id']['$t'],
        'name' => $albumRaw['title']['$t'],
        'updated' => $albumRaw['updated']['$t']
      ];
      if (preg_match('/^(Auto Backup|Profile Photos|Hangout:(.+)|([0-9]{4}-[0-9]{2}-[0-9]{2}))$/', $album['name']) === 0) {
        $albums[] = $album;
      }
    }
  } else {
    $albums = false;
  }
  curl_close($curl);

?>