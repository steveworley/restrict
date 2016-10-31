<?php

/**
 * @file
 * Contains Drupal\restrict\RulesInterface.
 */

namespace Drupal\restrict\Rules;

abstract class RulesInterface {

  /**
   * Magic setter.
   *
   * @param string $key
   *   Property for the rule.
   * @param mixed $value
   *   Value for the $key.
   *
   * @return \Drupal\restrict\Rules\RulesInterface
   */
  public function set($key, $value) {
    $this->{strtolower($key)} = $value;
    return $this;
  }

  /**
   * Magic getter.
   *
   * @param string $key
   *   The property to access.
   *
   * @return mixed|array
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
