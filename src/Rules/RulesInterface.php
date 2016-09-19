<?php

/**
 * @file
 * Contains Drupal\restrict\RulesInterface.
 */

namespace Drupal\restrict\Rules;

abstract class RulesInsterface {

  /**
   * Magic setter.
   * @param [type] $key   [description]
   * @param [type] $value [description]
   */
  public function __set($key, $value) {
    $this->{strtolower($key)} = $value;
    return $this;
  }

  /**
   * Magic getter.
   * @param  [type] $key [description]
   * @return [type]      [description]
   */
  public function __get($key) {
    return isset($this->{strtolower($key)}) ? $this->{strtolower($key)} : [];
  }

  /**
   * Assert the rule definition.
   *
   * @return boolean
   */
  public function assert();

}
