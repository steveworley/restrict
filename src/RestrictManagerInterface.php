<?php

/**
 * @file
 * Contains Drupal\ip_restrict\IpRestrictManagerInterface
 */

namespace Drupal\restrict;

use Symfony\Component\HttpFoundation\Request;

interface RestrictManagerInterface {

  function setRequest(Request $request);

  /**
   * Determines if the request path is restricted.
   *
   * @return boolean
   */
  function isRestrictedPath($path = NULL);

  /**
   * Determins if the request IP is restricted.
   *
   * @return boolean
   */
  function isRestrictedIP($ip = NULL);

  /**
   * [basicAuth description]
   *
   * @return boolean
   */
  function isAuthorised();

}
