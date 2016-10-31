<?php
/**
 * @file
 * Contains Drupal\Tests\restrict\Unit\PathRuleTest
 */

namespace Drupal\Tests\restrict\Unit;

use Drupal\restrict\Rules\PathRule;
use Drupal\restrict\Rules\BasicAuthRule;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for path checking with options.
 *
 * @coversDefaultClass \Drupal\restrict\Rules\PathRule
 * @group restrict
 */
class PathRuleTest extends UnitTestCase {

  /**
   * The current path.
   *
   * @var string
   */
  protected $current_path = '/path';

  /**
   * The PathRule instance.
   *
   * @var Drupal\restrict\Rules\PathRule
   */
  protected $rule;

  /**
   * The BasicAuthRule instance.
   *
   * @var Drupal\restrict\Rules\BasicAuthRule
   */
  protected $authRule;

  /**
   * Build the required mocks for testing.
   */
  public function setup() {
    $this->rule = $this->getMockBuilder('Drupal\restrict\Rules\PathRule')
      ->disableOriginalConstructor()
      ->setMethods(['getRule'])
      ->getMock();

    $this->rule->set('current_path', $this->current_path);

    // Set up a mock BasicAuthRule.
    $this->authRule = $this->getMockBuilder('Drupal\restrict\Rules\BasicAuthRule')
      ->disableOriginalConstructor()
      ->setMethods(['set', 'assert'])
      ->getMock();
  }

  /**
   * Ensure paths are matched correctly.
   */
  public function testPathIsRestricted() {
    $paths = [$this->current_path];
    $this->rule->set('paths', $paths);

    $this->rule->expects($this->never())->method('getRule');

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure paths can be matched in the array.
   */
  public function testPathInRestricted() {
    $paths = [
      '/test',
      '/test2',
      $this->current_path,
      '/test3',
    ];
    $this->rule->set('paths', $paths);

    $this->rule->expects($this->never())->method('getRule');

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure that a path restriction with correct login information will return true.
   */
  public function testPathAuthAccepted() {
    $paths = [
      $this->current_path => [
        'auth' => ['username' => 'password'],
      ],
    ];

    $authRule = $this->authRule;

    $authRule->expects($this->once())
      ->method('set')
      ->with('credentials', ['username' => 'password']);

    $authRule->expects($this->once())
      ->method('assert')
      ->willReturn(TRUE);

    $this->rule->expects($this->once())
      ->method('getRule')
      ->willReturn($this->authRule);

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure that a path restriction with incorrect login informaiton will return false.
   */
  public function testPathAuthRestricted() {
    $paths = [
      $this->current_path => [
        'auth' => ['username' => 'password'],
      ],
    ];

    $authRule = $this->authRule;

    $authRule->expects($this->once())
      ->method('set')
      ->with('credentials', ['username' => 'password']);

    $authRule->expects($this->once())
      ->method('assert')
      ->willReturn(FALSE);

    $this->rule->expects($this->once())
      ->method('getRule')
      ->willReturn($this->authRule);

    $this->assertFalse($this->rule->assert());
  }

}
