<?php
declare(strict_types=1);

namespace Drupal\Tests\Functional\l10n_server;

use Drupal\Tests\BrowserTestBase;

class SmokeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'l10n_server',
  ];

  public function testAdminBackend(): void {
    $this->drupalGet('admin/config/l10n_server');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/l10n_server/connectors');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/l10n_server/projects');
    $this->assertSession()->statusCodeEquals(403);


    $admin_user = $this->drupalCreateUser(['administer localization server']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/l10n_server');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/l10n_server/connectors');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('No localization server connectors found.');

    $this->drupalGet('admin/config/l10n_server/projects');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no projects yet.');

    $this->drupalGet('admin/config/l10n_server/projects/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('You need to enable at least one localization server connector.');

  }
}
