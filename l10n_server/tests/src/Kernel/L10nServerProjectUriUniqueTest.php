<?php
declare(strict_types=1);

namespace Drupal\Tests\l10n_server\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\l10n_server\Entity\Project;
use \Drupal\KernelTests\KernelTestBase;

/**
 * @coversClass \Drupal\l10n_server\Plugin\Validation\Constraint\L10nServerProjectUriUnique
 */
class L10nServerProjectUriUniqueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['l10n_server'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('l10n_server_project');
  }

  public function testConstraint() {
    $edit = [
      'title' => $this->getRandomGenerator()->name(),
      'uri' => 'drupal',
      'connector_module' => 'gettext',
    ];
    /** @var \Drupal\l10n_server\Entity\ProjectInterface $project */
    $project = Project::create($edit);
    $violations = $project->validate();
    $this->assertSame(0, $violations->count());
    $project->save();
    $this->assertSame(1, (int) $project->id());

    $project = Project::create($edit);
    $violations = $project->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('The project uri <em class="placeholder">drupal</em> is already taken.', (string) $violations->getByField('uri')->get(0)->getMessage());

    // Unique uri. Therefore, this should throw the exception.
    $this->expectException(EntityStorageException::class);
    $project->save();
  }
}
