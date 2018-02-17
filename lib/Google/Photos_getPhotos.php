<?php

  /**
   * Purpose: Get list of photos of Google Photos album
   * Input: <string> $albumId
   * Output: <array> $photos
   */

  // Check input
  if (!is_string($albumId)) throw new Exception('Argument $albumId must be a string');

  // Create REST URI
  // - gphoto:checksum is empty
  // - gphoto:position does reflect the same (maybe incorrect) order as the default one
  $restUri = 'https://picasaweb.google.com/data/feed/api/user/default/albumid/' . $albumId
           . '?alt=json&xfields=entry(gphoto:id,title,content,updated)'
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
    if (is_array($photosRaw)) {
      foreach ($photosRaw as $photoRaw) {
        $name = $photoRaw['title']['$t'];
        $ext = pathinfo(strtolower($name))['extension'];
        if ($ext === 'jpg' or $ext === 'png' or $ext === 'gif' or $ext === 'bmp') {
          $photo = [
            'id' => $photoRaw['gphoto$id']['$t'],
            'name' => $name,
            'mimeType' => $photoRaw['content']['type'],
            'uri' => $photoRaw['content']['src'],
            'updated' => $photoRaw['updated']['$t']
          ];
          $photos[] = $photo;
        }
      }
    }
  } else {
    $photos = false;
  }
  curl_close($curl);

?>