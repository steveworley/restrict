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
      ->withConsecutive(
        ['PHP_AUTH_USER', NULL, FALSE],
        ['PHP_AUTH_PW', NULL, FALSE]
      )
      ->willReturnOnConsecutiveCalls($this->username, $this->password);
  }

  /**
   * Ensure that if the rule has no credentials it always passes.
   */
  public function testNoCredentials() {
    $this->rule->expects($this->exactly(1))
      ->method('get')
      ->withConsecutive(['credentials'])
      ->willReturnOnConsecutiveCalls([]);

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure that valid credentials results in a valid rule.
   */
  public function testValidCredentials() {
      $this->rule->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(['credentials'], ['request'], ['request'])
      ->willReturnOnConsecutiveCalls([$this->username => $this->password], $this->request, $this->request);

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure that invalid credentials are blocked.
   */
  public function testInvalidCredentials() {
    $this->rule->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(['credentials'], ['request'], ['request'])
      ->willReturnOnConsecutiveCalls(['fake' => 'p455w0rd'], $this->request, $this->request);

    $this->assertFalse($this->rule->assert());
  }
}
