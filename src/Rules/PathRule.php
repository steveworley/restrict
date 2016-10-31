<?php

/**
 * @file
 * Contains Drupal\restrict\IpRule.
 */

namespace Drupal\restrict\Rules;

use Drupal\restrict\Rules\RulesInterface;

class PathRule extends RulesInterface {

  public function getRule($rule, $with = []) {
    // @TODO: Register rules as services so we don't manage these hashes.
    $rules = ['auth' => new BasicAuthRule()];

    if (empty($rules[$rule])) {
      throw new \Exception('Invalid rule requested.');
    }

    if (!empty($with)) {
      foreach ($with as $key => $value) {
        $rules[$rule]->set($key, $value);
      }
    }

    return $rules[$rule];
  }

  /**
   * Determine if a given path is valid.
   *
   * {@inheritdoc}
   */
  public function assert() {
    $paths = [];
    $matcher = \Drupal::service('path.matcher');

    foreach ($this->get('paths') as $key => $value) {
      $path = is_numeric($key) ? $value : $key;
      $opts = is_numeric($key) ? [] : $value;

      $matched = $matcher->matchPath($this->get('current_path'), $path);

      if (!$matched) {
        // Move the the next path if this path was not matched.
        continue;
      }

      if ($matched && isset($opts['auth'])) {
        $auth_rule = $this->getRule('auth', [
          'request' => \Drupal::request(),
          'credentials' => $opts['auth'],
        ]);

        return $auth_rule->assert();
      }

      // Path was found and has no addtional rules.
      return TRUE;
    }

    // Current path does not match a rule in settings.
    return FALSE;
  }

}
