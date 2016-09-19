<?php

/**
 * @file
 * Contains Drupal\restrict\IpRule.
 */

namespace Drupal\restrict\Rules;

use Drupal\restrict\Rules\RulesInterface;

class IpRule extends RulesInterface {

  /**
   * Assert that an IP exists in a given list of IPs.
   */
  public function assert() {
    $ip = $this->get('ip');
    foreach ($this->get('list') as $item) {

      // Match IPs in CIDR format.
      if (strpos($item, '/') !== FALSE) {
        list($range, $mask) = explode('/', $item);
        // Take the binary form of the IP and range.
        $ip_dec = ip2long($ip);
        $range_dec = ip2long($range);
        // Verify the given IPs are valid IPv4 addresses
        if (!$ip_dec || !$range_dec) {
          continue;
        }
        // Create the binary form of netmask.
        $mask_dec = ~ (pow(2, (32 - $mask)) - 1);
        // Run a bitwise AND to determine whether the IP and range exist
        // within the same netmask.
        if (($mask_dec & $ip_dec) == ($mask_dec & $range_dec)) {
          return TRUE;
        }
      }
      // Match against wildcard IPs or IP ranges.
      elseif (strpos($item, '*') !== FALSE || strpos($item, '-') !== FALSE) {
        // Construct a range from wildcard IPs
        if (strpos($item, '*') !== FALSE) {
          $item = str_replace('*', 0, $item) . '-' . str_replace('*', 255, $item);
        }

        // Match against ranges by converting to long IPs.
        list($start, $end) = explode('-', $item);
        $start_dec = ip2long($start);
        $end_dec = ip2long($end);
        $ip_dec = ip2long($ip);

        // Verify the given IPs are valid IPv4 addresses
        if (!$start_dec || !$end_dec || !$ip_dec) {
          continue;
        }

        if ($start_dec <= $ip_dec && $ip_dec <= $end_dec) {
          return TRUE;
        }

      }
      // Match against single IPs
      elseif ($ip === $item) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
