<?php

/**
 * @file
 * Contains Drupal\restrict\IpRule.
 */

namesapce Drupal\restrict\Rules;

use Drupal\restrict\Rules\RulesInterface;

class PathRule extends RulesInterface {

  /**
   * Determine if a given path is valid.
   *
   * {@inheritdoc}
   */
  public function assert() {

    // @TODO do we want to use a strtolower here? e.g. $pages = Unicode::strtolower($this->configuration['pages']);
    // @TODO dependency inject the PathMatcher instead of duplicating this function.

    // We have to convert the array into a set of patterns separated by a newline.
    // $restrictedPaths = implode("\n", $this->getRestrictedPaths());

    // Borrow some code from drupal_match_path()
    foreach ($this->get('paths') as &$path) {
      $path = preg_quote($path, '/');
    }

    $paths = preg_replace('/\\\\\*/', '.*', $this->get('paths'));
    $paths = '/^(' . join('|', $paths) . ')$/';

    // If this is a restricted path, return TRUE.
    if (preg_match($paths, $this->get('current_path'))) {
      return TRUE;
    }

    return FALSE;
  }

}
