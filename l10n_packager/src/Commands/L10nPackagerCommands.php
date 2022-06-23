<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Commands;

use Drupal\l10n_server\Entity\L10nServerRelease;
use Drupal\l10n_server\L10nHelper;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class L10nPackagerCommands extends DrushCommands {

  /**
   * Queue releases for packaging.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option force
   *
   * @usage l10n_packager-queue
   *   Queue releases for parsing.
   *
   * @command l10n_packager:queue
   *
   * @aliases lpq
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function queue(array $options = ['force' => NULL]): void {

    if ($options['force']) {
//      \Drupal::database()
//        ->update('l10n_server_release')
//        ->fields(['queued' => 0])
//        ->execute();
    }

    // Queue releases to be parsed.
    $queue = \Drupal::queue('l10n_packager_queue');
    $ids = \Drupal::entityTypeManager()
      ->getStorage('l10n_packager_release')
      ->getIdsToQueue();
    $this->logger()->notice(dt('Queuing releases to be packaged...'));

    $i = 0;
    $releases = L10nServerRelease::loadMultiple($ids);
    foreach ($releases as $release) {
      if ($queue->createItem($release)) {
        // Add timestamp to avoid queueing item more than once.
//        $release->setQueuedTime(\Drupal::time()->getRequestTime());
//        $release->save();
        $i++;
      }
    }

    $this->logger()->notice(dt('Found @count releases, @queued queued for packaging.', [
      '@count' => count($releases),
      '@queued' => $i,
    ]));
  }

  /**
   * Package releases.
   *
   * @param string $project
   *   The project to scan.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option all
   *
   * @usage l10n_packager-package
   *   Queue releases for packaging.
   *
   * @command l10n_packager:package
   *
   * @aliases lpp
   */
  public function package(string $project = '', array $options = ['force' => NULL]): void {
    $l10n_packager = \Drupal::service('l10n_packager.packager');

    $releases = L10nServerRelease::loadMultiple();
    $releases = array_rand($releases);

    $release = reset($releases);
    L10nHelper::releaseSetBranch($release);

    $l10n_packager->check($release);

  }

}
