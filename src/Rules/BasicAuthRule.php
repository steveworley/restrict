<?php
/**
 * @file
 * Contains Drupal\restrict\Rules\BasicAuthRule
 */

namespace Drupal\restrict\Rules;


class BasicAuthRule extends RulesInterface {

  /**
   * {@inheritdoc}
   */
  public function assert() {
    $credentials = $this->get('credentials');

    if (empty($credentials)) {
      // No credentials to check.
      return TRUE;
    }

    // Get auth from request headers.
    $username = $this->get('request')->headers->get('PHP_AUTH_USER');
    $password = $this->get('request')->headers->get('PHP_AUTH_PW');

    if (isset($username) && isset($password)) {
      if (isset($credentials[$username]) && $credentials[$username] == $password) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
