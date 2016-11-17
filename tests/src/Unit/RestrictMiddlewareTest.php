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

/**
 * Unit tests for RestrictMiddleware.
 *
 * @coversDefaultClass \Drupal\restrict\Services\RestrictMiddleware
 * @group restrict
 */
class RestrictMiddlewareTest extends UnitTestCase {

  /**
   * A HTTP Kernel.
   *
   * @var Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $kernel;

  /**
   * The restrict manager object.
   *
   * @var Drupal\restrict\RestrictManager
   */
  protected $restrictManager;

  /**
   * The restrict middleware.
   *
   * @var Drupal\restrict\Services\RestrictMiddleware
   */
  protected $restrictMiddleware;

  /**
   * Build the objects during setup of the test.
   */
  public function setup() {
    parent::setup();

    $this->kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock();
    $this->restrictManager = $this->getMockBuilder('Drupal\restrict\RestrictManagerInterface')->getMock();
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

  /**
   * Test no configuration request handling.
   */
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
