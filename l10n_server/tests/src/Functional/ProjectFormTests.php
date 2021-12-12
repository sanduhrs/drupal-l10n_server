<?php
declare(strict_types=1);

namespace Drupal\Tests\Functional\l10n_server;

use Drupal\Tests\BrowserTestBase;

/**
 * @group l10n_server
 */
class ProjectFormTests extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'l10n_server',
    'l10n_gettext',
  ];

  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer localization server']);
    $this->drupalLogin($admin_user);
  }

  public function testCUDProject() {
    $this->drupalGet('admin/config/l10n_server/projects/add');

    // Initial values
    $this->assertSession()->fieldValueEquals('enabled[value]', 1);
    $this->assertSession()->fieldValueEquals('connector_module', 'gettext');
    $this->assertSession()->fieldValueEquals('weight[0][value]', 0);
    $this->assertSession()->fieldValueEquals('title[0][value]', '');
    $this->assertSession()->fieldValueEquals('uri', '');

    $project_name = 'Drupal';
    $edit = [
      'title[0][value]' => $project_name,
      'uri' => 'drupal',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContainsOnce(\sprintf('Created new project %s.', $project_name));

    $this->drupalGet('admin/config/l10n_server/projects/1/edit');
    $this->submitForm(['title[0][value]' => 'Drupal2',], 'Save');
    $this->assertSession()->pageTextContainsOnce('Updated project Drupal2.');


    // Test that the uri must be unique.
    $this->drupalGet('admin/config/l10n_server/projects/add');
    $edit = [
      'title[0][value]' => $project_name,
      'uri' => 'drupal',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContainsOnce('The machine-readable name is already in use. It must be unique.');


    $this->drupalGet('admin/config/l10n_server/projects/1/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContainsOnce('The project Drupal2 has been deleted.');

  }
}
