<?php

  /**
   * Purpose: Log input formatted
   * Input: <mixed>
   */

  function show() {
    $args = func_get_args();
    echo '<pre>';
    print_r(count($args) === 1 ? $args[0] : $args);
    echo '</pre>';
  }

?>