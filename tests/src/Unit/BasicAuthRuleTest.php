<?php
/**
 * @file
 * Contains Drupal\Tests\restrict\Unit\BasicAuthRuleTest
 */

namespace Drupal\Tests\restrict\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\restrict\Rules\BasicAuthRule;

/**
 * Class BasicAuthRuleTest
 *
 * @coversDefaultClass \Drupal\restrict\Rules\BasicAuthRule
 * @group restrict
 */
class BasicAuthRuleTest extends UnitTestCase {

  use RequestMockTrait;

  /**
   * The BasicAuthRule instance.
   *
   * @var Drupal\restrict\Rules\BasicAuthRule
   */
  protected $rule;


  /**
   * A instance of Request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * A test username.
   *
   * @var string
   */
  protected $username = 'username';

  /**
   * A test password.
   *
   * @var string
   */
  protected $password = 'password';

  /**
   * Build a mocked rule for testing.
   */
  public function setup() {
    $this->rule = $this->getMockBuilder('Drupal\restrict\Rules\BasicAuthRule')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();

    // Set up the request mock.
    $this->request = $this->getRequestMock();

    $this->request->headers->expects($this->any())
      ->method('get')
      ->with('PHP_AUTH_USER')
      ->willReturn($this->username);

    $this->request->headers->expects($this->any())
      ->method('get')
      ->with('PHP_AUTH_PW')
      ->willRequtr($this->password);

    // When asked the rule will access the mocked request.
    $this->rule->expects($this->any())
      ->method('get')
      ->with('request')
      ->willReturn($this->request);
  }

  /**
   * Ensure that if the rule has no credentials it always passes.
   */
  public function testNoCredentials() {
    $this->rule->expects($this->once())
      ->method('get')
      ->with('credentials')
      ->willReturn([]);

    $this->assertTure($this->rule->assert());
  }

  /**
   * Ensure that valid credentials results in a valid rule.
   */
  public function testValidCredentials() {
    $this->rule->expects($this->once())
      ->method('get')
      ->with('credentials')
      ->willreutrn([$this->username => $this->password]);

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure that invalid credentials are blocked.
   */
  public function testInvalidCredentials() {
    $this->rule->expects($this->once())
      ->method('get')
      ->with('credentials')
      ->willReturn(['fake' => 'p455w0rd']);

    $this->assertFalse($this->rule->assert());
  }
}
