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
  function isRestrictedPath($path);

  /**
   * Determines if the request IP is restricted.
   *
   * @return boolean
   */
  function isRestrictedIp($ip);

  /**
   * Negotiates a basic authentication check.
   *
   * @return boolean
   */
  function isAuthorised();

  /**
   * Determines if the IP or path is restricted for the user.
   *
   * @return boolean
   */
  function isRestricted();

}
