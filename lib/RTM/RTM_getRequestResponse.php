<?php

  /**
   * Purpose: Perform request to RTM API and return response as array
   * Input: <array> $parameters
   * Output: <array> $response
   */

  // Check arguments
  if (!is_array($parameters)) throw new Exception('Argument $parameters must be an array');
  if (!isset($parameters['method'])) throw new Exception('Argument $parameters must have property method');

  // Get REST URI
  $baseUri = 'https://api.rememberthemilk.com/services/rest/';
  $restUri = $this->getRestUri($baseUri, $parameters);

  // Perform request
  $json = file_get_contents($restUri);

  // Tranform to array
  $array = json_decode($json, true);
  if (!is_array($array) or isset($array['rsp']['err'])) throw new Exception('Error #' . $array['rsp']['err']['code'] . ': ' . $array['rsp']['err']['msg']);

  // Return response
  $response = $array['rsp'];

?>