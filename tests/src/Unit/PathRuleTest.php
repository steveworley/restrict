<?php

/**
 * @file
 * Constains Drupal\Tests\restrict\Unit\PathRuleTest;
 */

namespace Drupal\Tests\restrict\Unit;

use Drupal\restrict\Rules\PathRule;

/**
 * @coversDefaultClass \Drupal\restrict\PathRule
 */
class PathRuleTest extends UnitTestCase {

  /**
   * Create a new PathRule object for each test.
   */
  public function setup() {
    $rule = new PathRule();
    $rule->set('current_path', '/test');
    $this->rule = $rule;
  }

  /**
   * Test current paht is found in the list.
   */
  public function testCurrentPathMatch() {
    $paths = ['/test'];
    $this->rule->set('paths', $paths);
    $this->assertTrue($this->rule->assert());
  }

  /**
   * Test is the current path is not found in list.
   */
  public function testCurrentPathNotMatch() {
    $paths = ['/test2'];
    $this->rule->set('paths', $paths);
    $this->assertFalse($this->rule->assert());
  }
}
