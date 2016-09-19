<?php

namespace Drupal\restrict;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Drupal\restrict\Rules\IpRule;
use Drupal\restrict\Rules\PathRule;

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
   * Available rule instances.
   *
   * @var Array
   */
  protected $rules;

  /**
   * Construct the RestrictManager object.
   *
   * @param Settings $settings
   *   Site settings.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
    $this->rules = [
      'ip' => new IpRule(),
      'path' => new PathRule(),
    ]
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

    // Set the IP context.
    $this->rules['ip']->set('ip', $this->request->getClientIp());

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
        // If the requesting IP is found in the IP blacklist we should restrict
        // the request based on other AH Configuration values.
        return $this->getDeniedNotFound();
      }
    }

    if (!empty($this->getRestrictedPaths())) {
      $this->rules['path']->set('current_path', $this->request->getCurrentPath());
      $this->rules['path']->set('paths', $this->getRestrictedPaths());
      
      if ($this->rules['path']->assert()) {
        return $this->getDeniedNotFound();
      }
    }

    return FALSE;
  }
}
