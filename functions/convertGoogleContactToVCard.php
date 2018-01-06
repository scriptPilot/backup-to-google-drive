<?php

  /**
   * Purpose: Convert Google contact to vCard string
   * Input: <array> $googleContact
   * Output: <string> vCard content
   * Requires: jeroendesloovere/vcard
   */

  function convertGoogleContactToVCard($googleContact) {

    // Create new vCard
    $vcard = new JeroenDesloovere\VCard\VCard();

    // Add name
    $givenName = $googleContact['names'][0] ? $googleContact['names'][0]['givenName'] : '';
    $familyName = $googleContact['names'][0] ? $googleContact['names'][0]['familyName'] : '';
    $displayName = $googleContact['names'][0] ? $googleContact['names'][0]['displayName'] : '';
    $vcard->addName($familyName, $givenName);

    // Add phone numbers
    if (isset($googleContact['phoneNumbers'])) {
      foreach ($googleContact['phoneNumbers'] as $number) {
        $tel = isset($number['canonicalForm']) ? $number['canonicalForm'] : $number['value'];
        $type = $number['type'] === 'home' ? 'HOME' : ($number['type'] === 'work' ? 'WORK' : null);
        $vcard->addPhoneNumber($tel, $type);
      }
    }

    // Add email addresses
    if (isset($googleContact['emailAddresses'])) {
      foreach ($googleContact['emailAddresses'] as $email) {
        $type = $email['type'] === 'home' ? 'HOME' : ($email['type'] === 'work' ? 'WORK' : null);
        $vcard->addEmail($email['value'], $type);
      }
    }

    // Add addresses
    if (isset($googleContact['addresses'])) {
      foreach ($googleContact['addresses'] as $address) {
        $type = $address['type'] === 'home' ? 'HOME' : ($address['type'] === 'work' ? 'WORK' : null);
        $street = $address['streetAddress'] ? $address['streetAddress'] : '';
        $postalCode = $address['postalCode'] ? $address['postalCode'] : '';
        $city = $address['city'] ? $address['city'] : '';
        $vcard->addAddress(null, null, $street, $city, null, $postcode, null, $type);
      }
    }

    // Get birthday
    if ($googleContact['birthdays'][0]['date']) {
      $date = $googleContact['birthdays'][0]['date'];
      $year = $date['year'];
      $month = str_pad($date['month'], 2, '0', STR_PAD_LEFT);
      $day = str_pad($date['day'], 2, '0', STR_PAD_LEFT);
      $dateStr = ($year !== null ? $year : '-') . '-' . $month . '-' . $day;
      $vcard->addBirthday($dateStr);
    }

    // Get notes
    if (isset($googleContact['biographies'])) {
      foreach ($googleContact['biographies'] as $note) {
        $vcard->addNote($note['value']);
      }
    }

    // Get (bigger) photo
    if ($googleContact['photos'][0] && strpos($googleContact['photos'][0]['url'], '_8B/') === false) {
      $url = str_replace('/s100/', '/s512/', $googleContact['photos'][0]['url']);
      $vcard->addPhoto($url);
    }

    // Return vCard content
    return $vcard->getOutput();

  }

?>