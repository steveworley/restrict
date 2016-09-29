<?php

namespace Drupal\restrict;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Drupal\restrict\Rules\IpRule;
use Drupal\restrict\Rules\PathRule;

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
   * Construct the RestrictManager object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Site settings.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
    $this->pathMatcher = $matcher;

    // @TODO: Rules should be passed in so this can be reused.
    $this->rules = [
      'ip' => new IpRule(),
      'path' => new PathRule(),
    ];
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
   * [getResponseCode description]
   * @return [type] [description]
   */
  protected function getResponseCode() {
    return $this->settings->get('restrict_response_code', self::RESTRICT_FORBIDDEN);
  }

  /**
   * [getTrustedProxies description]
   * @return [type] [description]
   */
  protected function getTrustedProxies() {
    return $this->settings->get('restrict_trusted_proxies', []);
  }

  /**
   * {@inheritdoc}
   */
  public function isRestricted() {

    $ip = $this->request->getClientIp();

    if (empty($ip)) {
      return $this->getResponseCode();
    }

    if (!empty($this->getTrustedProxies())) {
      $this->request->setTrustedProxies($this->getTrustedProxies());
    }
    else {
      $this->request->setTrustedProxies([$this->request->server->get('REMOTE_ADDR')]);
    }

    // Set the IP context.
    $this->rules['ip']->set('ip', $ip);

    if (!empty($this->getWhitelist())) {
      $this->rules['ip']->set('list', $this->getWhitelist());

      if ($this->rules['ip']->assert()) {
        // If the requesting IP is found in the IP whitelist this request should
        // not be restricted so we return early.
        return FALSE;
      }
    }

    if (!empty($this->getBlacklist())) {
      $this->rules['ip']->set('list', $this->getBlacklist());

      if ($this->rules['ip']->assert()) {
        // If the IP is in the blacklist, we have a restricted IP.
        return $this->getResponseCode();
      }
    }

    // @TODO do we want to check for restricted routes?
    if (!empty($this->getRestrictedPaths())) {
      // $config['system.performance']['cache']['page']['max_age'] = 0;
      $this->rules['path']->set('current_path', $this->request->getRequestUri());
      $this->rules['path']->set('paths', $this->getRestrictedPaths());

      if ($this->rules['path']->assert()) {
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

    $username = $this->request->headers->get('PHP_AUTH_USER');
    $password = $this->request->headers->get('PHP_AUTH_PW');

    if (isset($username) && isset($password)) {
      if (isset($allowedCredentials[$username]) && $allowedCredentials[$username] == $password) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
