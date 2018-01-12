<?php

  /**
   * Purpose: Set default fields for Drive REST API
   * Input: <string> $fields
   * Output: -
   */

  // Check input
  if (!is_string($fields)) throw new Exception('Argument $fields must be a string');

  // Update property
  $this->fields = $fields;

?>
