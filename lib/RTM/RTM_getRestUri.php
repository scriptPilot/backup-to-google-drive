<?php

  /**
   * Purpose: Return REST URI
   * Input: <string> $baseUri, <array> $parameters, <boolean> $sign
   * Output: <string> $restUri
   */

  // Check arguments
  if (!is_string($baseUri)) throw new Exception('Argument $baseUri must be a string');
  if (!is_array($parameters)) throw new Exception('Argument $parameters must be an array');
  if (!is_bool($sign)) throw new Exception('Argument $baseUri must be true or false');

  // Ensure API version as parameter
  if (!isset($parameters['v'])) $parameters['v'] = 2;

  // Ensure API key as parameter
  if (!isset($parameters['api_key'])) $parameters['api_key'] = RTM_API_KEY;

  // Ensure auth token as parameter
  if (!isset($parameters['auth_token']) && isset($this->credentials['token'])) $parameters['auth_token'] = $this->credentials['token'];

  // Ensure response format as parameter (by default JSON)
  if (!isset($parameters['format'])) $parameters['format'] = 'json';

  // Append signature to parameters
  if ($sign) $parameters['api_sig'] = $this->getSignature($parameters);

  // Create REST URI
  $restUri = $baseUri . '?' . http_build_query($parameters);

?>