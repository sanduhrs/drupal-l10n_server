<?php
declare(strict_types=1);

namespace Drupal\Tests\Functional\l10n_server;

use Drupal\Tests\BrowserTestBase;

/**
 * @group l10n_server
 */
class ProjectTests extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'l10n_server',
    'l10n_gettext'
  ];

  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer localization server']);
    $this->drupalLogin($admin_user);
  }

  public function testAddProject() {
    $this->config('l10n_server.settings')->set('enabled_connectors', ['gettext'])->save();
    $this->drupalGet('admin/config/l10n_server/projects/add');
  #  $this->assertSession()->statusCodeEquals(200);
  #  $this->assertSession()->fieldExists('title[0][value]');
  #  $this->assertSession()->fieldExists('uri');
  #  $this->assertSession()->fieldExists('connector_module');

    $edit = [
      'title[0][value]' => $this->randomString(),
      'uri' => mb_strtolower($this->randomString()),
    ];
    $this->submitForm($edit, 'Save');
  }
}
