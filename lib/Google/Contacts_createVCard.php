<?php

  /**
   * Purpose: Create VCard content from contact details
   * Input: <array> $contact
   * Output: <string> $content
   */

  // Check input
  if (!is_array($contact)) throw new Exception('Argument $contact must be an array');

  // Create new vCard
  $vcard = new JeroenDesloovere\VCard\VCard();

  // Add name
  $vcard->addName($contact['familyName'], $contact['givenName']);

  // Add phone numbers
  foreach ($contact['phone'] as $phone) {
    $vcard->addPhoneNumber($phone['value'], $phone['type']);
  }

  // Add email addresses
  foreach ($contact['email'] as $email) {
    $vcard->addEmail($email['value'], $email['type']);
  }

  // Add addresses
  foreach ($contact['addresses'] as $address) {
    $vcard->addAddress(null, null, $address['street'], $address['city'], null, $address['postcode'], null, $address['type']);
  }

  // Get birthday
  if ($contact['birthday']) {
    $vcard->addBirthday($contact['birthday']['string']);
  }

  // Get notes
  if ($contact['notes']) {
    $vcard->addNote($contact['notes']);
  }

  // Get (bigger) photo
  if ($contact['photoUri']) {
    $vcard->addPhoto($contact['photoUri']);
  }

  // Return vCard content
  $content = $vcard->getOutput();

?>
