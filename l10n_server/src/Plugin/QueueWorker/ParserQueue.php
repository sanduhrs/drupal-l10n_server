<?php

namespace Drupal\l10n_server\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\l10n_server\Entity\L10nServerProject;
use Drupal\l10n_server\Entity\L10nServerRelease;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;

/**
 * Defines 'l10n_server_parser_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "l10n_server_parser_queue",
 *   title = @Translation("L10n Server Parser Queue"),
 *   cron = {"time" = 60}
 * )
 */
class ParserQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof L10nServerReleaseInterface) {

      // Reload the current release object.
      $release = L10nServerRelease::load($data->id());

      // Regardless of successful or not, indicate that it has been checked.
      $release->setQueuedTime(0);
      $release->save();

      // Skip if already parsed.
      if ($release->getLastParsed()) {
        return;
      }

      // Get the associated project and connector.
      $project = L10nServerProject::load($release->getProjectId());
      $connector = $project->getConnector();
      if ($connector->isEnabled()
        && $connector->isParsable()) {

        // Parse the release.
        /** @var \Drupal\l10n_server\ConnectorParseHandlerInterface $connector */
        $connector->setRelease($release);
        $connector->parseHandler();
      }
    }
  }

}
