<?php

  /**
   * Purpose: Get contacts
   * Output: <array> $contacts
   */

  // Define parameters
  $params = [
    'personFields' => 'names,phoneNumbers,emailAddresses,addresses,birthdays,photos,biographies',
    'sortOrder' => 'FIRST_NAME_ASCENDING',
    'pageSize' => 1000
  ];

  // Create REST URI
  $restUri = 'https://people.googleapis.com/v1/people/me/connections'
           . '?' . http_build_query($params)
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
    $contactsRaw = json_decode($response, true)['connections'];
    $contacts = [];
    foreach ($contactsRaw as $raw) {

      // Start new contact
      $contact = [];

      // Add ID
      $contact['id'] = substr($raw['resourceName'], 7);

      // Add etag
      $contact['etag'] = substr($raw['etag'], 7);

      // Add identifier
      $contact['ident'] = 'contacts/' . $contact['id'] . '/etag/' . $contact['etag'];

      // Add name
      $contact['givenName'] = $raw['names'][0] ? $raw['names'][0]['givenName'] : '';
      $contact['familyName'] = $raw['names'][0] ? $raw['names'][0]['familyName'] : '';
      $contact['displayName'] = $raw['names'][0] ? $raw['names'][0]['displayName'] : '';

      // Add phone numbers
      $contact['phone'] = [];
      if (isset($raw['phoneNumbers'])) {
        foreach ($raw['phoneNumbers'] as $tel) {
          $type = $tel['type'] === 'home' ? 'HOME' : ($tel['type'] === 'work' ? 'WORK' : null);
          $number = isset($tel['canonicalForm']) ? $tel['canonicalForm'] : $tel['value'];
          $contact['phone'][] = ['type' => $type, 'number' => $number];
        }
      }

      // Add email addresses
      $contact['email'] = [];
      if (isset($raw['emailAddresses'])) {
        foreach ($raw['emailAddresses'] as $email) {
          $type = $email['type'] === 'home' ? 'HOME' : ($email['type'] === 'work' ? 'WORK' : null);
          $contact['email'][] = ['type' => $type, 'address' => $email['value']];
        }
      }

      // Add addresses
      $contact['addresses'] = [];
      if (isset($raw['addresses'])) {
        foreach ($raw['addresses'] as $address) {
          $type = $address['type'] === 'home' ? 'HOME' : ($address['type'] === 'work' ? 'WORK' : null);
          $street = $address['streetAddress'] ? $address['streetAddress'] : '';
          $postalCode = $address['postalCode'] ? $address['postalCode'] : '';
          $city = $address['city'] ? $address['city'] : '';
          $contact['addresses'][] = ['type' => $type, 'street' => $street, 'postcode' => $postalCode, 'city' => $city];
        }
      }

      // Get birthday
      $contact['birthday'] = null;
      if ($raw['birthdays'][0]['date']) {
        $date = $raw['birthdays'][0]['date'];
        $year = $date['year'];
        $month = str_pad($date['month'], 2, '0', STR_PAD_LEFT);
        $day = str_pad($date['day'], 2, '0', STR_PAD_LEFT);
        $dateStr = ($year !== null ? $year : '-') . '-' . $month . '-' . $day;
        $contact['birthday'] = ['year' => $date['year'], 'month' => $date['month'], 'day' => $date['day'], 'string' => $dateStr];
      }

      // Get notes
      $contact['notes'] = null;
      if (isset($raw['biographies'])) {
        foreach ($raw['biographies'] as $note) {
          $contact['notes'] = $note['value'];
        }
      }

      // Get (bigger) photo
      $contact['photoUri'] = null;
      if ($raw['photos'][0] && strpos($raw['photos'][0]['url'], '_8B/') === false) {
        $uri = str_replace('/s100/', '/s512/', $raw['photos'][0]['url']);
        $contact['photoUri'] = $uri;
      }

      // Add to array
      $contacts[] = $contact;

    }
  } else {
    $contacts = false;
  }
  curl_close($curl);

?>