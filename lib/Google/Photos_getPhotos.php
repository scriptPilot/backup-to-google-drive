<?php

  /**
   * Purpose: Get list of photos of Google Photos album
   * Input: <string> $albumId
   * Output: <array> $photos
   */

  // Check input
  if (!is_string($albumId)) throw new Exception('Argument $albumId must be a string');

  // Create REST URI
  $restUri = 'https://picasaweb.google.com/data/feed/api/user/default/albumid/' . $albumId
           . '?alt=json&fields=entry(gphoto:id,title,content,updated)'
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
    $photos = [];
    $photosRaw = json_decode($response, true)['feed']['entry'];
    foreach ($photosRaw as $photoRaw) {
      $photo = [
        'id' => $photoRaw['gphoto$id']['$t'],
        'name' => $photoRaw['title']['$t'],
        'mimeType' => $photoRaw['content']['type'],
        'uri' => $photoRaw['content']['src'],
        'updated' => $photoRaw['updated']['$t']
      ];
      $photos[] = $photo;
    }
  } else {
    var_dump(curl_error($curl));
    $photos = false;
  }
  curl_close($curl);

?>