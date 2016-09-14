<?php

/**
 * @file
 * Contains Drupal\ip_restrict\IpRestrictManagerInterface
 */

namespace Drupal\restrict;

interface IpRestrictManager {

  public function setRequest();

  /**
   * Determines if the request path is restricted.
   *
   * @return boolean
   */
  public function isRestrictedPath(string $path = NULL);

  /**
   * Determins if the request IP is restricted.
   *
   * @return boolean
   */
  public function isRestrictedIP(string $ip = NULL);

  /**
   * [basicAuth description]
   * 
   * @return boolean
   */
  public function isAuthorised();

}
