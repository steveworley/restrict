<?php

namespace Drupal\restrict\Services;

use Drupal\restrict\RestrictManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\restrict\RestrictManager;
use Drupal\Component\Render\FormattableMarkup;
/**
 * Provides support for IP restrictions.
 */
class RestrictMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings.
   */
  protected $settings;


  /**
   * The restrict manager.
   *
   * @var \Drupal\restrict\RestrictManagerInterface
   */
  protected $restrictManager;

  /**
   * Constructs a RestrictMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(HttpKernelInterface $http_kernel, RestrictManagerInterface $manager) {
    $this->httpKernel = $http_kernel;
    $this->restrictManager = $manager;
  }

  /**
   * Abstract CLI check for unit test mocking.
   *
   * @return boolean
   */
  public function isCli() {
    return PHP_SAPI === 'cli';
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {

    // Don't apply restrictions to cli requests ie. Drush.
    if ($this->isCli()) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Set the RestrictManager request context.
    $this->restrictManager->setRequest($request);

    if (!$this->restrictManager->isAuthorised()) {
      $response = new Response();
      $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', $request->getHttpHost()));
      $response->setContent(new FormattableMarkup('401 Unauthorized: Access Denied (@ip)', ['@ip' => $request->getClientIp()]));
      $response->setStatusCode(401);
      return $response;
    }

    // @TODO should isRestricted be a property not a function?
    switch ($this->restrictManager->isRestricted()) {
      case RestrictManager::RESTRICT_NOT_FOUND:
        return new Response(new FormattableMarkup('<h1>Not Found</h1><p>The requested URL @path was not found on this server.</p>', ['@path' => $request->getPathInfo()]), 404);
        break;
      case RestrictManager::RESTRICT_FORBIDDEN:
        return new Response(new FormattableMarkup('403 Forbidden: Access Denied (@ip)', ['@ip' => $request->getClientIp()]), 403);
        break;
    }

    // Process the request normally.
    return $this->httpKernel->handle($request, $type, $catch);
  }
}
