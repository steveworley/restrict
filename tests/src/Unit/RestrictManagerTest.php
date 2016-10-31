<?php
/**
 * @file
 * Contains Drupal\Tests\restrict\Unit\RestrictManagerTest
 */

namespace Drupal\Tests\restrict\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\restrict\RestrictManager;

/**
 * Unit tests for RestrictManager.
 *
 * @coversDefaultClass \Drupal\restrict\RestrictManager
 * @group restrict
 */
class RestrictManagerTest extends UnitTestCase {

  use RequestMockTrait;

  /**
   * Return a mock manager.
   *
   * @return array
   *   A mocked RestrictManager
   */
  public function getRestrictManagerMock() {
    $manager = $this->getMockBuilder('Drupal\restrict\RestrictManager')
      ->disableOriginalConstructor();
    return $manager;
  }

  /**
   * Set up a rule that catches.
   *
   * @return MockObject
   */
  public function getPassRuleMock() {
    $rule = $this->getMockBuilder('Rule')
      ->setMethods(['set', 'assert'])
      ->getMock();

    $rule->expects($this->any())->method('assert')->willReturn(TRUE);
    return $rule;
  }

  /**
   * Set up a rule that does not catch.
   *
   * @return mixed
   */
  public function getFailRuleMock() {
    $rule = $this->getMockBuilder('Rule')
      ->setMethods(['set', 'assert'])
      ->getMock();

    $rule->expects($this->any())->method('assert')->willReturn(FALSE);
    return $rule;
  }

  /**
   * Ensure IP is specified with the request.
   */
  public function testNoRequestIp() {
    $request = $this->getRequestMock();

    $request->headers->expects($this->any())
      ->method('get')
      ->willReturn('user');

    $request->expects($this->once())
      ->method('getClientIp')
      ->willReturn(NULL);

    $manager = $this->getRestrictManagerMock()
      ->setMethods(['getResponseCode', 'getRequest'])
      ->getMock();

    $manager->expects($this->once())
      ->method('getResponseCode')
      ->willReturn(RestrictManager::RESTRICT_FORBIDDEN);

    // Add the request context.
    $manager->expects($this->once())
      ->method('getRequest')
      ->willReturn($request);

    $this->assertEquals(RestrictManager::RESTRICT_FORBIDDEN, $manager->isRestricted());
  }

  /**
   * Expect false if request IP is in whitelist.
   */
  public function testWhitelist() {
    // Setup the request mock.
    $request = $this->getRequestMock();

    $request->headers->expects($this->any())
      ->method('get')
      ->willReturn('user');

    $request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('10.0.0.1');

    $manager = $this->getRestrictManagerMock()
      ->setMethods([
        'getWhitelist',
        'setRequestTrustedProxies',
        'getRequest',
        'getTrustedProxies',
        'getRules'
      ])
      ->getMock();

    $manager->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $manager->expects($this->exactly(2))
      ->method('getWhitelist')
      ->willReturn(['10.0.0.1']);

    $manager->expects($this->any())
      ->method('getRules')
      ->willReturn($this->getPassRuleMock());

    $this->assertFalse($manager->isRestricted());
  }

  /**
   * Ensure that if the request is restricted we receive a forbidden.
   */
  public function testBlacklist() {
    // Setup the request mock.
    $request = $this->getRequestMock();
    $request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('10.0.0.1');

    $manager = $this->getRestrictManagerMock()
      ->setMethods([
        'getWhitelist',
        'setRequestTrustedProxies',
        'getRequest',
        'getTrustedProxies',
        'getRules',
        'getBlacklist',
        'getResponseCode',
      ])
      ->getMock();

    $manager->expects($this->once())
      ->method('getResponseCode')
      ->willReturn(RestrictManager::RESTRICT_FORBIDDEN);

    $manager->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $manager->expects($this->once())
      ->method('getWhitelist')
      ->willReturn([]);

    $manager->expects($this->any())
      ->method('getRules')
      ->willReturn($this->getPassRuleMock());

    $manager->expects($this->exactly(2))
      ->method('getBlacklist')
      ->willReturn(['10.0.0.1']);

    $this->assertEquals(RestrictManager::RESTRICT_FORBIDDEN, $manager->isRestricted());
  }

  /**
   * Test restricted paths.
   */
  public function testPaths() {
    // Setup the request mock.
    $request = $this->getRequestMock();

    $request->headers->expects($this->any())
      ->method('get')
      ->willReturn('user');

    $request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('10.0.0.1');

    $request->expects($this->once())
      ->method('getRequestUri')
      ->willReturn('/mypath');

    $manager = $this->getRestrictManagerMock()
      ->setMethods([
        'getWhitelist',
        'setRequestTrustedProxies',
        'getRequest',
        'getTrustedProxies',
        'getRules',
        'getBlacklist',
        'getResponseCode',
        'getRestrictedPaths',
      ])
      ->getMock();

    $manager->expects($this->once())
      ->method('getResponseCode')
      ->willReturn(RestrictManager::RESTRICT_FORBIDDEN);

    $manager->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $manager->expects($this->once())
      ->method('getWhitelist')
      ->willReturn([]);

    $manager->expects($this->any())
      ->method('getRules')
      ->willReturn($this->getPassRuleMock());

    $manager->expects($this->once())
      ->method('getBlacklist')
      ->willReturn([]);

    $manager->expects($this->exactly(2))
      ->method('getRestrictedPaths')
      ->willReturn(['/mypath']);

    $this->assertEquals(RestrictManager::RESTRICT_FORBIDDEN, $manager->isRestricted());
  }

  /**
   * Ensure requests aren't restricted if not in path or blacklist.
   */
  public function testNotInBlacklistOrRestrictedPaths() {
    // Setup the request mock.
    $request = $this->getRequestMock();
    $request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('10.0.0.1');

    $request->expects($this->once())
      ->method('getRequestUri')
      ->willReturn('/mypath');

    $manager = $this->getRestrictManagerMock()
      ->setMethods([
        'getWhitelist',
        'setRequestTrustedProxies',
        'getRequest',
        'getTrustedProxies',
        'getRules',
        'getBlacklist',
        'getResponseCode',
        'getRestrictedPaths',
      ])
      ->getMock();

    $manager->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $manager->expects($this->exactly(2))
      ->method('getWhitelist')
      ->willReturn(['10.0.0.1']);

    $manager->expects($this->exactly(2))
      ->method('getBlacklist')
      ->willReturn(['10.0.0.1']);

    $manager->expects($this->exactly(2))
      ->method('getRestrictedPaths')
      ->willReturn(['/mypath']);

    $manager->expects($this->any())
      ->method('getRules')
      ->willReturn($this->getFailRuleMock());

    $manager->expects($this->never())->method('getResponseCode');

    $this->assertFalse($manager->isRestricted());
  }

  /**
   * Ensure requests aren't restricted if not in path or blacklist.
   */
  public function testUnrestricted() {
    // Setup the request mock.
    $request = $this->getRequestMock();

    $request->headers->expects($this->any())
      ->method('get')
      ->willReturn('user');

    $request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('10.0.0.1');

    $manager = $this->getRestrictManagerMock()
      ->setMethods([
        'getWhitelist',
        'setRequestTrustedProxies',
        'getRequest',
        'getTrustedProxies',
        'getRules',
        'getBlacklist',
        'getResponseCode',
        'getRestrictedPaths',
      ])
      ->getMock();

    $manager->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $manager->expects($this->once())
      ->method('getWhitelist')
      ->willReturn([]);

    $manager->expects($this->once())
      ->method('getBlacklist')
      ->willReturn([]);

    $manager->expects($this->once())
      ->method('getRestrictedPaths')
      ->willReturn([]);

    $manager->expects($this->once())->method('getRules')->willReturn($this->getFailRuleMock());
    $manager->expects($this->never())->method('getResponseCode');

    $this->assertFalse($manager->isRestricted());
  }

  /**
   * Ensure if correct credentials are given the request is authorised.
   */
  public function testValidBasicAuth() {
    $manager = $this->getRestrictManagerMock()
      ->setMethods(['getBasicAuthCredentials', 'getRequest', 'getRules'])
      ->getMock();

    $manager->expects($this->once())
      ->method('getRequest')
      ->willReturn([]);

    $manager->expects($this->once())
      ->method('getBasicAuthCredentials')
      ->willReturn([]);


    $manager->expects($this->once())
      ->method('getRules')
      ->with('auth')
      ->willReturn($this->getPassRuleMock());

    $this->assertTrue($manager->isAuthorised());
  }

  /**
   * Ensure that requests are restricted if auth fails.
   */
  public function testInvalidBasicAuth() {
    $manager = $this->getRestrictManagerMock()
      ->setMethods(['getBasicAuthCredentials', 'getRequest', 'getRules'])
      ->getMock();

    $manager->expects($this->once())
      ->method('getRequest')
      ->willReturn([]);

    $manager->expects($this->once())
      ->method('getBasicAuthCredentials')
      ->willReturn([]);

    $manager->expects($this->once())
      ->method('getRules')
      ->with('auth')
      ->willReturn($this->getFailRuleMock());

    $this->assertFalse($manager->isAuthorised());
  }
}
