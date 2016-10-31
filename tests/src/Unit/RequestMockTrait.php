<?php
/**
 * @file
 * Contains Drupal\Tests\restrict\Unit\RequestMockTrait
 */

namespace Drupal\Tests\restrict\Unit;


trait RequestMockTrait {

  /**
   * Generate a Request Mock object.
   *
   * @return Symfony\Component\HttpFoundation\Request
   */
  public function getRequestMock() {
    $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();

    // Setup a mock for $request->request parameter bag.
    $params = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
      ->disableOriginalConstructor()
      ->setMethods(['getClientIp', 'getRequestUri'])
      ->getMock();

    // Setup a mock for $request->server parameter bag.
    $server = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();

    $server->expects($this->any())
      ->method('get')
      ->with('REMOTE_ADDR')
      ->willReturn('127.0.0.1');

    // Setup a mock for $request->headers parameter bag.
    $headers = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();

    $request->server = $server;
    $request->request = $params;
    $request->headers = $headers;

    return $request;
  }

}
