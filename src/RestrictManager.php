<?php

namespace Drupal\restrict;

use Psr\Log\LoggerInterface;
use Drupal\Core\Site\Settings;
use Drupal\restrict\Rules\IpRule;
use Drupal\restrict\Rules\PathRule;
use Symfony\Component\HttpFoundation\Request;
use \InvalidArgumentException;

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
   * Available rule instances.
   *
   * @var Array
   */
  protected $rules;

  /**
   * The logger object.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Construct the RestrictManager object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Site settings.
   */
  public function __construct(Settings $settings, LoggerInterface $logger) {
    $this->settings = $settings;
    $this->logger = $logger;

    // @TODO: Rules should be passed in so this can be reused.
    $this->rules = [
      'ip' => new IpRule(),
      'path' => new PathRule(),
    ];
  }

  /**
   * Getter for the Settings object.
   *
   * @return \Drupal\Core\Site\Settings
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Getter for the rules defined for the manager.
   *
   * @return array
   */
  public function getRules($name = NULL) {
    return isset($this->rules[$name]) ? $this->rules[$name] : $this->rules;
  }

  /**
   * Set the request context for this object.
   *
   * @param Request $request
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Getter for the request context.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the whitelist property from settings.
   *
   * @return array
   *   A list of whitelisted IPs.
   */
  public function getWhitelist() {
    $ip_list = $this->getSettings()->get('restrict_whitelist', []);

    if (!is_array($ip_list)) {
      $this->logger->handleInvalidConfiguration('restrict_restricted_paths', 'array', gettype($ip_list));
      $ip_list = [$ip_list];
    }

    return $ip_list;
  }

  /**
   * Get the blacklist property from settings.
   *
   * @return array
   *   A list of blacklisted IPs.
   */
  public function getBlacklist() {
    $ip_list = $this->getSettings()->get('restrict_blacklist', []);

    if (!is_array($ip_list)) {
      $this->logger->handleInvalidConfiguration('restrict_restricted_paths', 'array', gettype($ip_list));
      $ip_list = [$ip_list];
    }

    return $ip_list;
  }

  /**
   * Get the basic auth credentials from settings.
   *
   * @return array
   *   An array of valid users.
   */
  public function getBasicAuthCredentials() {
    $credentails = $this->getSettings()->get('restrict_basic_auth_credentials', []);

    if (!is_array($credentails)) {
      throw new InvalidArgumentException('restrict_basic_auth_credentials must be an associative array.');
    }

    if (array_keys($credentails) === range(0, count($credentails) -1)) {
      throw new InvalidArgumentException('restrict_basic_auth_credentials must be an associative array.');
    }

    return $credentails;
  }

  /**
   * Get the restricted paths from settings.
   *
   * @return array
   *   A list of paths to restrict.
   */
  public function getRestrictedPaths() {
    $paths = $this->getSettings()->get('restrict_restricted_paths', []);

    if (!is_array($paths)) {
      $this->logger->handleInvalidConfiguration('restrict_restricted_paths', 'array', gettype($path));
      $paths = [$path];
    }

    return $paths;
  }

  /**
   * Get the return response code.
   *
   * @TODO: validation.
   *
   * @return int
   *   The return response code.
   */
  public function getResponseCode() {
    $code = $this->getSettings()->get('restrict_response_code', 'RESTRICT_FORBIDDEN');

    $valid_response_codes = [
      'RESTRICT_NOT_FOUND',
      'RESTRICT_FORBIDDEN',
      'RESTRICT_UNAUTHORISED',
    ];

    if (!in_array($code, $valid_response_codes)) {
      $this->logger->handleInvalidConfiguration('restrict_response_code', join(', ', $valid_response_codes), $code);
      $code = 'RESTRICT_FORBIDDEN';
    }

    $code = \constant("self::{$code}");
    return $code;
  }

  /**
   * Get the trusted proxy information.
   *
   * @return array
   *   A list of valid proxy IPs.
   */
  public function getTrustedProxies() {
    $proxies = $this->settings->get('restrict_trusted_proxies', []);

    if (!is_array($proxies)) {
      $this->logger->handleInvalidConfiguration('restrict_trusted_proxies', 'array', gettype($proxies));
      $proxies = [$proxies];
    }

    return $proxies;
  }

  /**
   * Set trusted proxies for the request object.
   */
  public function setRequestTrustedProxies() {

    // Ensure that trusted proxies are only set if they haven't been set prior.
    if (Request::getTrustedProxies()) {
      return;
    }

    $request = $this->getRequest();
    $trusted_proxies = $this->getTrustedProxies();

    $proxies = !empty($trusted_proxies) ? $trusted_proxies : [$request->server->get('REMOTE_ADDR')];
    Request::setTrustedProxies($proxies);
  }

  /**
   * {@inheritdoc}
   */
  public function isRestricted() {

    $ip = $this->getRequest()->getClientIp();

    if (empty($ip)) {
      return $this->getResponseCode();
    }

    // Set the trusted proxies for this request.
    $this->setRequestTrustedProxies();

    // Set the IP context.
    $this->getRules('ip')->set('ip', $ip);

    if (!empty($this->getWhitelist())) {
      $this->getRules('ip')->set('list', $this->getWhitelist());

      if ($this->getRules('ip')->assert()) {
        // If the requesting IP is found in the IP whitelist this request should
        // not be restricted so we return early.
        return FALSE;
      }
    }

    if (!empty($this->getBlacklist())) {
      $this->getRules('ip')->set('list', $this->getBlacklist());

      if ($this->getRules('ip')->assert()) {
        // If the IP is in the blacklist, we have a restricted IP.
        return $this->getResponseCode();
      }
    }

    if (!empty($this->getRestrictedPaths())) {
      $this->getRules('path')->set('current_path', $this->getRequest()->getRequestUri());
      $this->getRules('path')->set('paths', $this->getRestrictedPaths());

      if ($this->getRules('path')->assert()) {
        return $this->getResponseCode();
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthorised($username = NULL, $password = NULL) {

    $allowedCredentials = $this->getBasicAuthCredentials();

    // Basic auth has not been configured for this site.
    if (empty($allowedCredentials)) {
      return TRUE;
    }

    $username = $this->getRequest()->headers->get('PHP_AUTH_USER');
    $password = $this->getRequest()->headers->get('PHP_AUTH_PW');

    if (isset($username) && isset($password)) {
      if (isset($allowedCredentials[$username]) && $allowedCredentials[$username] == $password) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
