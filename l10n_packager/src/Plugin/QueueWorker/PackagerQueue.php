<?php

namespace Drupal\l10n_packager\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\l10n_server\Entity\L10nServerRelease;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;

/**
 * Defines 'l10n_packager_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "l10n_packager_queue",
 *   title = @Translation("Package releases"),
 *   cron = {"time" = 60}
 * )
 */
class PackagerQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof L10nServerReleaseInterface) {
      // Reload the current release object.
      $release = L10nServerRelease::load($data->id());
    }
  }

}
