<?php

/**
 * @file
 * Contains Drupal\restrict\IpRule.
 */

namespace Drupal\restrict\Rules;

use Drupal\restrict\Rules\RulesInterface;

class PathRule extends RulesInterface {

  /**
   * Determine if a given path is valid.
   *
   * {@inheritdoc}
   */
  public function assert() {

    // @TODO do we want to use a strtolower here? e.g. $pages = Unicode::strtolower($this->configuration['pages']);

    // We have to convert the array into a set of patterns separated by a newline.
    $paths = implode("\n", $this->get('paths'));
    return \Drupal::service('path.matcher')->matchPath($this->get('current_path'), $paths);
  }

}
