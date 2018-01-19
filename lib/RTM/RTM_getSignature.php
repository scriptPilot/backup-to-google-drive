<?php

  /**
   * Purpose: Return signature for given parameters
   * Input: <array> $parameters
   * Output: <string> $signature
   */

  // Check arguments
  if (!is_array($parameters)) throw new Exception('Argument $parameters must be an array');

  // Sort array by keys
  ksort($parameters);

  // Concatenate keys and values
  $concatenatedString = '';
  foreach ($parameters as $key => $value) $concatenatedString .= $key . $value;

  // Prefix shared secret
  $concatenatedString = RTM_SHARED_SECRET . $concatenatedString;

  // Calculate signature as the hash of the concatenated string
  $signature = md5($concatenatedString);

?>