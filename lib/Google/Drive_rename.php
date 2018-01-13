<?php

  /**
   * Purpose: Rename folder or file
   * Input: <string> $id, <string> $name
   * Output: <array> $file
   */

  // Check input
  if (!is_string($id)) throw new Exception('Argument $id must be a string');
  if (!is_string($name)) throw new Exception('Argument $name must be a string');

  // Rename file
  $file = $this->updateFile($id, ['name' => $name]);

?>