<?php

  /**
   * Purpose: Do requests to Google REST APIs
   * Input: <string> url
   *        <array> params
   *        <string> method (POST/GET)
   *        <array> payload
   * Output:   <array> or false
   * Requires: $_SESSION['GOOGLE_TOKEN']
   *           php-curl-class/php-curl-class
   */

  function performGoogleRequest($url, $params = [], $method = 'GET', $payload = []) {

    // Check parameters
    if (!isset($_SESSION['GOOGLE_TOKEN'])) throw new Exception('performGoogleRequest() requires $_SESSION[\'GOOGLE_TOKEN\']');
      else if (!is_string($url)) throw new Exception('performGoogleRequest() requires a string as first argument');
      else if (!is_array($params)) throw new Exception('performGoogleRequest() requires an array as second argument');
      else if (!in_array($method, ['POST', 'GET'])) throw new Exception('performGoogleRequest() requires POST/GET as third argument');
      else if (!is_array($payload)) throw new Exception('performGoogleRequest() requires an array as fourth argument');

    // Attach session to url
    $url = $url . (strpos($url, '?') === false ? '?' : '&') . 'oauth_token=' . $_SESSION['GOOGLE_TOKEN']['access_token'];

    // Attach parameters to url
    foreach ($params as $key => $value) $url = $url . '&' . $key . '=' . urlencode($value);

    // Perform request
    $curl = new \Curl\Curl();
    $curl->setHeader('Content-Type', 'application/json');
    if ($method === 'POST') $curl->post($url, $payload);
      else $curl->get($url);

    // Error result
    if ($curl->error) {

      // Log error
      echo '<pre>';
      echo '<b>Error</b><br />';
      echo $curl->errorCode . ': ' . $curl->errorMessage . '<br />';
      echo '<b>URL</b><br />';
      print_r($url); echo '<br />';
      echo '<b>Parameters</b><br />';
      print_r($params);
      echo '<b>Method</b><br />';
      print_r($method); echo '<br />';
      echo '<b>Payload</b><br />';
      print_r($payload); echo '<br />';
      echo '</pre>';
      die();

      // Return false
      return false;

    } else {

      // Return response as an array
      return json_decode(json_encode($curl->response), true);

    }

  }


?>