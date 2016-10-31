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

  use RequestMockTrait;

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
   * Mocked object to check paths.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $matcher;

  /**
   * Build the required mocks for testing.
   */
  public function setup() {
    $this->rule = $this->getMockBuilder('Drupal\restrict\Rules\PathRule')
      ->disableOriginalConstructor()
      ->setMethods(['getRule', 'getMatcher', 'getRequest'])
      ->getMock();

    $this->rule->set('current_path', $this->current_path);

    $this->rule->expects($this->any())
      ->method('getRequest')
      ->willReturn($this->getRequestMock());

    $this->matcher = $this->getMockBuilder('Drupal\Core\Path\PathMatcher')
      ->disableOriginalConstructor()
      ->setMethods(['matchPath'])
      ->getMock();

    // Set up a mock BasicAuthRule.
    $this->authRule = $this->getMockBuilder('Drupal\restrict\Rules\BasicAuthRule')
      ->disableOriginalConstructor()
      ->setMethods(['assert'])
      ->getMock();
  }

  /**
   * Ensure paths are matched correctly.
   */
  public function testPathIsRestricted() {
    $paths = [$this->current_path];
    $this->rule->set('paths', $paths);

    $this->matcher->expects($this->once())
      ->method('matchPath')
      ->willReturn(TRUE);

    $this->rule->expects($this->once())->method('getMatcher')->willReturn($this->matcher);
    $this->rule->expects($this->never())->method('getRule');

    $this->assertTrue($this->rule->assert());
  }

  /**
   * Ensure paths can be matched in the array.
   */
  public function testPathInRestricted() {
    $cur_path = $this->current_path;
    $paths = [
      '/test',
      '/test2',
      $cur_path,
      '/test3',
    ];
    $this->rule->set('paths', $paths);

    $this->matcher->expects($this->exactly(3))
      ->method('matchPath')
      ->withConsecutive(...array_map(function($p) use ($cur_path) { return [$cur_path, $p]; }, $paths))
      ->willReturnOnConsecutiveCalls(FALSE, FALSE, TRUE, FALSE);

    $this->rule->expects($this->once())->method('getMatcher')->willReturn($this->matcher);
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

    $this->matcher->expects($this->once())
      ->method('matchPath')
      ->willReturn(TRUE);

    $this->rule->set('paths', $paths);

    $this->rule->expects($this->once())->method('getMatcher')->willReturn($this->matcher);

    $authRule = $this->authRule;

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
      ->method('assert')
      ->willReturn(FALSE);

    $this->rule->expects($this->once())
      ->method('getRule')
      ->willReturn($this->authRule);

    $this->matcher->expects($this->once())
      ->method('matchPath')
      ->willReturn(TRUE);

    $this->rule->set('paths', $paths);
    $this->rule->expects($this->once())->method('getMatcher')->willReturn($this->matcher);

    $this->assertFalse($this->rule->assert());
  }

}
