<?php

namespace Drupal\restrict;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

class RestrictManager implements RestrictManagerInterface {

  const RESTRICT_NOT_FOUND = -1;
  const RESTRICT_UNAUTHORISED = -2;

  /**
   * A Request object.
   *
   * @var Symfony\Component\HttpFoundation\Request.
   */
  protected $request;

  /**
   * The site settings.
   *
   * @var Drupal\Core\Site\Settings.
   */
  protected $settings;

  /**
   * Construct the RestrictManager object.
   *
   * @param Settings $settings
   *   Site settings.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * [setRequest description]
   * @param Request $request [description]
   */
  public function setRequest(Request $request) {
    $this->request = $request;
    return $this;
  }

  /**
   * [getWhitelist description]
   * @return [type] [description]
   */
  protected function getWhitelist() {
    return $this->settings->get('ah_whitelist', []);
  }

  /**
   * [getBlacklist description]
   * @return [type] [description]
   */
  protected function getBlacklist() {
    return $this->settings->get('ah_blacklist', []);
  }

  /**
   * [getAuth description]
   * @return [type] [description]
   */
  protected function getAuth() {
    return $this->settings->get('ah_basic_auth_credentials', []);
  }

  /**
   * [getDeniedNotFound description]
   * @return [type] [description]
   */
  protected function getDeniedNotFound() {
    return $this->settings->get('ah_denied_not_found') ? self::RESTRICT_NOT_FOUND : self::RESTRICT_UNAUTHORISED;
  }

  /**
   * [getRestrictedPaths description]
   * @return [type] [description]
   */
  protected function getRestrictedPaths() {
    return $this->settings->get('ah_restricted_paths', []);
  }

  public function isRestricted() {
    $ip = $this->request->getClientIp();
    $path = $this->request->getCurrentRoute();

    if (empty($ip)) {
      return self::RESTRICT_UNAUTHORISED;
    }

    if ($this->isRestrictedIP($ip)) {
      return self::RESTRICT_UNAUTHORISED;
    }

    if ($this->isRestrictedPath($path)) {
      return self::RESTRICT_UNAUTHORISED;
    }

    return FALSE;
  }

  /**
   * [isRestrictedIP description]
   * @return boolean [description]
   */
  public function isRestrictedIP($ip = NULL) {
    $whitelist = $this->getWhitelist();
    $blacklist = $this->getBlacklist();

    if ($this->isIpInList($ip, $whitelist)) {
      // If the IP is in the whitelist; we do not have a restricted IP.
      return FALSE;
    }

    // If the IP is in the blacklist we have a restricted IP.
    return $this->isIpInList($ip, $blacklist);
  }

  /**
   * [isRestrictedPath description]
   * @return boolean [description]
   */
  public function isRestrictedPath($path = NULL) {
    $matcher = UrlMatcher($this->getRestrictedPaths());
    return (bool) $matcher->match($path);
  }

  /**
   * [isAuthorised description]
   * @return boolean [description]
   */
  public function isAuthorised($user = NULL, $pass = NULL) {
    $basic_auth = $this->getAuth();

    // Basic auth has not been configured for this site.
    if (empty($basic_auth)) {
      return TRUE;
    }

    // Test to see if the user is valid with the credentials provided.
    if (isset($basic_auth[$user]) && $basic_auth[$user] == $pass) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * [isIpInList description]
   * @param  [type]  $ip      [description]
   * @param  [type]  $ip_list [description]
   * @return boolean          [description]
   */
  protected static function isIpInList($ip, $list) {
    foreach ($list as $item) {
      // Match IPs in CIDR format.
      if (strpos($item, '/') !== false) {
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
      elseif (strpos($item, '*') !== false || strpos($item, '-') !== false) {
        // Construct a range from wildcard IPs
        if (strpos($item, '*') !== false) {
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
