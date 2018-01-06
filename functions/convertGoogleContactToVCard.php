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
    $familyName = $googleContact['names'][0] ? $googleContact['names'][0]['givenName'] : '';
    $displayName = $googleContact['names'][0] ? $googleContact['names'][0]['displayName'] : '';
    $vcard->addName($familyName, $givenName);

    // Add phone numbers
    $phoneNumbers = [];
    if (isset($googleContact['phoneNumbers'])) {
      foreach ($googleContact['phoneNumbers'] as $number) {
        $vcard->addPhoneNumber($number['canonicalForm']);
      }
    }

    // Add email addresses
    $emailAddresses = [];
    if (isset($googleContact['emailAddresses'])) {
      foreach ($googleContact['emailAddresses'] as $email) {
        $vcard->addEmail($email['value']);
      }
    }

    // Add addresses
    $addresses = [];
    if (isset($googleContact['addresses'])) {
      foreach ($googleContact['addresses'] as $address) {
        $street = $address['streetAddress'] ? $address['streetAddress'] : '';
        $postalCode = $address['postalCode'] ? $address['postalCode'] : '';
        $city = $address['city'] ? $address['city'] : '';
        $vcard->addAddress(null, null, $street, $city, null, $postcode, null);
      }
    }

    // Get birthday
    $date = $googleContact['birthdays'][0] ? $googleContact['birthdays'][0]['date'] : [];
    if ($date['day'] && $date['month'] && $date['year']) $vcard->addBirthday($date['year'] . '-' . $date['month'] . '-' . $date['day']);
     else if ($date['day'] && $date['month']) $vcard->addBirthday('--' . $date['month'] . '-' . $date['day']);

    // Get (bigger) photo
    if ($googleContact['photos'][0] && strpos($googleContact['photos'][0]['url'], '_8B/') === false) {
      $url = str_replace('/s100/', '/s512/', $googleContact['photos'][0]['url']);
      $vcard->addPhoto($url);
    }

    // Return vCard content
    return $vcard->getOutput();

  }

?>