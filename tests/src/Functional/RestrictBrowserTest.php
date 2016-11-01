<?php
/**
 * @file
 * Contains Drupal\Tests\restrict\Functional\RestrictBrowserTest
 */

namespace Drupal\Tests\restrict\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Site\Settings;

/**
 * Class UnauthorisedTest
 *
 * @group restrict
 * @runTestsInSeparateProcesses
 */
class RestrictBrowserTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['restrict'];

  /**
   * Ensure that unauthorised requests are blocked.
   */
  public function testUnauthorised() {
    $settings['settings'] = [
      'restrict_basic_auth_credentials' => (object) [
        'value' => ['admin' => 'welcome'],
        'required' => TRUE,
      ],
    ];

    $this->writeSettings($settings);
    $this->drupalGet('/node');

    $this->assertSession()->statusCodeEquals(401);
  }

  /**
   * Ensure that unauthorised requests are blocked.
   */
  public function testPathRestricted() {
    $settings['settings'] = [
      'restrict_restricted_paths' => (object) [
        'value' => ['/node'],
        'required' => TRUE,
      ],
    ];

    $this->writeSettings($settings);

    // Make a request to node and ensure 401.
    $this->drupalGet('/node');
    $this->assertSession()->statusCodeEquals(403);

    // Make a request to another URL and ensure the status code.
    $this->drupalGet('/not-found');
    $this->assertSession()->statusCodeNotEquals(403);
  }

  /**
   * Ensure that we can specify a not found response.
   */
  public function testResponseCodeNotFound() {
    $settings['settings'] = [
      'restrict_response_code' => (object) [
        'value' => 'RESTRICT_NOT_FOUND',
        'required' => TRUE,
      ],
      'restrict_restricted_paths' => (object) [
        'value' => ['/node'],
        'required' => TRUE,
      ],
    ];

    $this->writeSettings($settings);
    $this->drupalGet('/node');

    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Ensure that a forbidden response is correctly sent.
   */
  public function testResponseCodeForbidden() {
    $settings['settings'] = [
      'restrict_response_code' => (object) [
        'value' => 'RESTRICT_FORBIDDEN',
        'required' => TRUE,
      ],
      'restrict_restricted_paths' => (object) [
        'value' => ['/node'],
        'required' => TRUE,
      ],
    ];

    $this->writeSettings($settings);
    $this->drupalGet('/node');

    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Ensure that if given valid credentials we have access to the site.
   */
  public function testValidAuthorisation() {
    $settings['settings'] = [
      'restrict_basic_auth_credentials' => (object) [
        'value' => ['admin' => 'welcome'],
        'required' => TRUE,
      ],
    ];

    $this->writeSettings($settings);

    $session = $this->getSession();

    // Set PHP Authorisation cookies and make the request.
    $session->setRequestHeader('PHP_AUTH_USER', 'admin');
    $session->setRequestHeader('PHP_AUTH_PW', 'welcome');

    $this->drupalGet('/node');

    $this->assertSession()->statusCodeNotEquals(401);
  }
}
