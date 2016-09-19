<?php

/**
 * @file
 * Constains namespace Drupal\Tests\restrict\Unit\IpRuleTest;
 */

namespace Drupal\Tests\restrict\Unit;

use Drupal\restrict\Rules\IpRule;

/**
 * @coversDefaultClass \Drupal\restrict\IpRule
 */
class IpRuleTest extends UnitTestCase {

  /**
   * Create a new IpRule object for each test.
   */
  public function setup() {
    $rule = new IpRule();
    $rule->set('ip', '127.0.0.1');
    $this->rule = $rule;
  }

  /**
   * Test an IP exists in a single length $list.
   */
  public function testInSingleList() {
    $list = ['127.0.0.1'];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertTrue($result);
  }

  /**
   * Test an IP exists when match is at end of $list.
   */
  public function testEndMultipleList() {
    $list = [
      '10.0.0.1',
      '10.0.0.2',
      '10.0.0.3',
      '10.0.0.4',
      '127.0.0.1',
    ];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertTrue($result);
  }

  /**
   * Test an IP exists when match is in $list.
   */
  public function testInList() {
    $list = [
      '10.0.0.1',
      '10.0.0.2',
      '127.0.0.1',
      '10.0.0.3',
      '10.0.0.4',
    ];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertTrue($result);
  }

  /**
   * Test matching against a valid CIDR IP address.
   */
  public function testCIDR() {
    $list = ['127.0.0.1/2'];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertTrue($result);
  }

  /**
   * Test matching against wildcards.
   */
  public function testWildcard() {
    $list = ['127.0.0.*'];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertTrue($result);
  }

  /**
   * Test correct return value when IP is not found.
   */
  public function testNotInList() {
    $list = ['10.0.0.1'];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertFalse($result);
  }

  /**
   * Test correct return value when IP is not found given a wildcard.
   */
  public function testNotInWildcard() {
    $list = ['10.0.0.*'];
    $this->rule->set('list', $list);

    $result = $this->rule->assert();
    $this->assertFalse($result);
  }

}
