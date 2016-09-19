<?php

/**
 * @file
 * Contains Drupal\restrict\RulesInterface.
 */

namespace Drupal\restrict\Rules;

abstract class RulesInterface {

  /**
   * Magic setter.
   * @param [type] $key   [description]
   * @param [type] $value [description]
   */
  public function set($key, $value) {
    $this->{strtolower($key)} = $value;
    return $this;
  }

  /**
   * Magic getter.
   * @param  [type] $key [description]
   * @return [type]      [description]
   */
  public function get($key) {
    return isset($this->{strtolower($key)}) ? $this->{strtolower($key)} : [];
  }

  /**
   * Assert the rule definition.
   *
   * @return boolean
   */
  abstract public function assert();

}
