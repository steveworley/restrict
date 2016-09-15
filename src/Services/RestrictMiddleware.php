<?php

namespace Drupal\restrict\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\restrict\RestrictManager;
use Drupal\Component\Utility\SafeMarkup;
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
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $settings;

  /**
   * Constructs a RestrictMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(HttpKernelInterface $http_kernel, RestrictManager $manager) {
    $this->httpKernel = $http_kernel;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {

    if (PHP_SAPI === 'cli') {
      // Don't apply restrictions to cli requests ie. Drush.
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Set the RestrictManager request context.
    $this->manager->setRequest($request);

    if (!$this->manager->isAuthorised()) {
      return new Response(SafeMarkup::format('401 Unauthorized: Access Denied (@ip)', ['@ip' => $ip]), 401);
    }

    switch ($this->manager->isRestricted()) {
      case RestrictManager::RESTRICT_NOT_FOUND:
        return new Response(SafeMarkup::format('<h1>Not Found</h1><p>The requested URL @path was not found on this server.</p>', ['@path' => $path]), 404);
        break;
      case RestrictManager::RESTRICT_UNAUTHORISED:
        return new Response(SafeMarkup::format('403 Forbidden: Access Deined (@ip)', ['@ip' => $ip]), 403);
        break;
    }

    // Process the request normally.
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
