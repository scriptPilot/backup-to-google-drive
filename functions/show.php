<?php

  /**
   * Purpose: Log input formatted
   * Input: <mixed>
   */

  function show() {
    echo '<pre>';
    var_dump(func_get_args());
    echo '</pre>';
  }

?>