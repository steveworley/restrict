<?php
/**
 * @file
 * Contains Drupal\restrict\Logger\RestrictChannel
 */

namespace Drupal\restrict\Logger;

use Drupal\Core\Logger\LoggerChannel;
use \Drupal\Component\Render\FormattableMarkup;

class RestrictChannel extends LoggerChannel {
  /**
   * Abstract logging for invalid configuration values.
   *
   * @param $field
   *   A field name.
   * @param string $expected
   *   The expected variable type.
   * @param string $actual
   *   The actual variable type.
   */
  public function handleInvalidConfiguration($field, $expected = 'array', $actual = 'string') {
    $message = new FormattableMarkup('<strong>Invalid Configuration:</strong> :field was :actual expecting :expected', [
      ':field' => $field,
      ':actual' => $actual,
      ':expected' => $expected,
    ]);

    $this->warning($message);
  }
}
