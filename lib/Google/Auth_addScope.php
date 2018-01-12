<?php

  /**
   * Purpose: Add scope
   * Input: <string> scope
   */

  // Check arguments
  if (!is_string($scope)) throw new Exception('argument scope must be a string');

  // Split up multiple scopes
  $scope = explode(' ', $scope);

  // Update scope property
  if (is_array($this->scope)) {
    $this->scope = array_merge($this->scope, $scope);
  } else {
    $this->scope = $scope;
  }