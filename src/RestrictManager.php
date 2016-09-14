<?php

namespace Drupal\restrict;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Route;

class RestrictManager implements RestrictManagerInterface {

  const RESTRICT_NOT_FOUND = -1;
  const RESTRICT_UNAUTHORISED = -2;

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
   * [getWhitelist description]
   * @return [type] [description]
   */
  public function getWhitelist() {
    return $this->settings->get('ah_whitelist', []);
  }

  /**
   * [getBlacklist description]
   * @return [type] [description]
   */
  public function getBlacklist() {
    return $this->settings->get('ah_blacklist', []);
  }

  /**
   * [getAuth description]
   * @return [type] [description]
   */
  public function getAuth() {
    return $this->settings->get('ah_basic_auth_credentials', []);
  }

  /**
   * [getDeniedNotFound description]
   * @return [type] [description]
   */
  public function getDeniedNotFound() {
    return $this->settings->get('ah_denied_not_found') ? self::RESTRICT_NOT_FOUND : self::RESTRICT_UNAUTHORISED
  }

  /**
   * [getRestrictedPaths description]
   * @return [type] [description]
   */
  public function getRestrictedPaths() {
    $routes = $this->settings->get('ah_restricted_paths', []);
    $collection = new RouteCollection();

    foreach ($routes as &$route) {
      $obj = new Route($route);
      $collection->add($route, $obj);
    }

    return $collection;
  }

  /**
   * [isRestrictedIP description]
   * @return boolean [description]
   */
  public function isRestrictedIP(string $ip = NULL) {
    $whitelist = $this->getWhitelist();
    $blacklist = $this->getBlacklist();

    if (empty($ip)) {
      // All requests should have an IP address.
      return $this->getDeniedNotFound();
    }

    if (self::isIpInList($ip, $whitelist)) {
      return FALSE;
    }

    if (!self::isIpInList($ip, $blacklist)) {
      return $this->getDeniedNotFound();
    }

    return FALSE;
  }

  /**
   * [isRestrictedPath description]
   * @return boolean [description]
   */
  public function isRestrictedPath(string $path = NULL) {
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
  public static function isIpInList($ip, $ip_list) {
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
