<?php

namespace Drupal\restrict;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

class RestrictManager implements RestrictManagerInterface {

  const RESTRICT_NOT_FOUND = -1;
  const RESTRICT_UNAUTHORISED = -2;
  const RESTRICT_FORBIDDEN = -3;

  /**
   * A Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings.
   */
  protected $settings;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface.
   */
  protected $pathMatcher;

  /**
   * Construct the RestrictManager object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Site settings.
   * @param \Drupal\Core\Path\PathMatcherInterface $matcher
   *   The path matcher class.
   */
  public function __construct(Settings $settings, PathMatcherInterface $matcher) {
    $this->settings = $settings;
    $this->pathMatcher = $matcher;
  }

  /**
   * [setRequest description]
   * @param Request $request
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * [getWhitelist description]
   * @return array [description]
   */
  protected function getWhitelist() {
    return $this->settings->get('restrict_whitelist', []);
  }

  /**
   * [getBlacklist description]
   * @return array [description]
   */
  protected function getBlacklist() {
    return $this->settings->get('restrict_blacklist', []);
  }

  /**
   * [getBasicAuthCredentials description]
   * @return array [description]
   */
  protected function getBasicAuthCredentials() {
    return $this->settings->get('restrict_basic_auth_credentials', []);
  }

  /**
   * [getRestrictedPaths description]
   * @return array
   */
  protected function getRestrictedPaths() {
    return $this->settings->get('restrict_restricted_paths', []);
  }

  /**
   * {@inheritdoc}
   */
  public function isRestricted() {

    $response_code = $this->settings->get('restrict_response_code', self::RESTRICT_FORBIDDEN);
    $trusted_proxies = $this->settings->get('restrict_trusted_proxies', []);
    if (!empty($trusted_proxies)) {
      $this->request->setTrustedProxies($trusted_proxies);
    }
    else {
      $this->request->setTrustedProxies(array($this->request->server->get('REMOTE_ADDR')));
    }

    $ip = $this->request->getClientIp();

    if (empty($ip)) {
      return $response_code;
    }

    if ($this->isRestrictedIp($ip)) {
      return $response_code;
    }

    // @TODO do we want to check for restricted routes?
    if ($this->isRestrictedPath($this->request->getPathInfo())) {
//      $config['system.performance']['cache']['page']['max_age'] = 0;

      return $response_code;
    }

    return false;
  }


  /**
   * {@inheritdoc}
   */
  public function isRestrictedIp($ip) {

    // If the IP is in the whitelist, we do not have a restricted IP.
    if ($this->isIpInList($ip, $this->getWhitelist())) {
      return false;
    }

    // If the IP is in the blacklist, we have a restricted IP.
    return $this->isIpInList($ip, $this->getBlacklist());
  }

  /**
   * {@inheritdoc}
   */
  public function isRestrictedPath($path) {
    // @TODO do we want to use a strtolower here? e.g. $pages = Unicode::strtolower($this->configuration['pages']);

    // We have to convert the array into a set of patterns separated by a newline.
    $restrictedPaths = implode("\n", $this->getRestrictedPaths());

    return $this->pathMatcher->matchPath($path, $restrictedPaths);
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthorised($username = NULL, $password = NULL) {

    $allowedCredentials = $this->getBasicAuthCredentials();

    // Basic auth has not been configured for this site.
    if (empty($allowedCredentials)) {
      return true;
    }

    $username = $this->request->headers->get('PHP_AUTH_USER');
    $password = $this->request->headers->get('PHP_AUTH_PW');

    if (isset($username) && isset($password)) {
      foreach ($allowedCredentials as $phpAuthUser => $phpAuthPassword) {
        if ($username == $phpAuthUser && $password == $phpAuthPassword) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * [isIpInList description]
   * @param  string  $ip      [description]
   * @param  array  $list [description]
   * @return boolean          [description]
   */
  protected static function isIpInList($ip, Array $list) {
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
          return true;
        }
      }
      // Match against single IPs
      elseif ($ip === $item) {
        return true;
      }
    }
    return false;
  }
}
