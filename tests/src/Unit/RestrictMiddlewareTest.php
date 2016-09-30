<?php
/**
 * @file
 * Contains Drupal\Tests\restrict\Unit\RestrictMiddlewareTest
 */

namespace Drupal\Tests\restrict\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\restrict\RestrictManager;
use Drupal\restrict\Services\RestrictMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Core\Site\Settings;

class RestrictMiddlewareTest extends UnitTestCase {

  protected $kernel;

  protected $restrictManager;

  protected $restrictMiddleware;

  protected $settings;

  public function setup() {
    parent::setup();

    $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->restrictManager = $this->getMock('Drupal\restrict\RestrictManagerInterface');
    $this->restrictMiddleware = $this->getMockBuilder('Drupal\restrict\Services\RestrictMiddleware')
      ->setConstructorArgs([$this->kernel, $this->restrictManager])
      ->setMethods(['isCli'])
      ->getMock();
  }

  /**
   * Test a CLI request.
   */
  public function testIsCli() {
    $this->restrictMiddleware
      ->expects($this->once())
      ->method('isCli')
      ->willReturn(TRUE);

    $request = Request::create('/test-path');

    $this->kernel->expects($this->once())
      ->method('handle')
      ->with($request, HttpKernelInterface::MASTER_REQUEST, TRUE);

    $this->restrictMiddleware->handle($request);
  }

  /**
   * Test unauthorised requests.
   */
  public function testUnauthorised() {
    $this->restrictManager->expects($this->once())->method('setRequest');
    $this->restrictManager->expects($this->once())
      ->method('isAuthorised')
      ->willReturn(FALSE);

    $request = Request::create('/test-path');
    $response = $this->restrictMiddleware->handle($request);

    $this->kernel->expects($this->never())->method('handle');
    $this->assertEquals(401, $response->getStatusCode());
  }

  /**
   * Test forbidden requests.
   */
  public function testIsRestrictedForbidden() {
    $this->restrictManager->expects($this->once())->method('setRequest');
    $this->restrictManager->expects($this->once())
      ->method('isAuthorised')
      ->willReturn(TRUE);

    $this->restrictManager->expects($this->once())
      ->method('isRestricted')
      ->willReturn(RestrictManager::RESTRICT_FORBIDDEN);

    $request = Request::create('/test-path');
    $response = $this->restrictMiddleware->handle($request);

    $this->kernel->expects($this->never())->method('handle');
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Test not found requests.
   */
  public function testIsRestrictedNotFound() {
    $this->restrictManager->expects($this->once())->method('setRequest');
    $this->restrictManager->expects($this->once())
      ->method('isAuthorised')
      ->willReturn(TRUE);

    $this->restrictManager->expects($this->once())
      ->method('isRestricted')
      ->willReturn(RestrictManager::RESTRICT_NOT_FOUND);

    $request = Request::create('/test-path');
    $response = $this->restrictMiddleware->handle($request);

    $this->kernel->expects($this->never())->method('handle');
    $this->assertEquals(404, $response->getStatusCode());
  }

  public function testDefaultRouteHandling() {
    $request = Request::create('/test-path');

    $this->restrictMiddleware
      ->expects($this->once())
      ->method('isCli')
      ->willReturn(FALSE);

    $this->restrictManager->expects($this->once())->method('setRequest');

    $this->restrictManager->expects($this->once())
      ->method('isAuthorised')
      ->willReturn(TRUE);

    $this->restrictManager->expects($this->once())
      ->method('isRestricted')
      ->willReturn(FALSE);

    $this->kernel->expects($this->once())
      ->method('handle')
      ->with($request);

    $this->restrictMiddleware->handle($request);
  }
}