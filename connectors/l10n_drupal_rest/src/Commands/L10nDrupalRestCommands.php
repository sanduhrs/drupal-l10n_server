<?php

declare(strict_types=1);

namespace Drupal\l10n_drupal_rest\Commands;

use Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector\DrupalRest;
use Drupal\l10n_server\Entity\Release;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class L10nDrupalRestCommands extends DrushCommands {

  /**
   * Refresh project list.
   *
   * @command l10n-drupal-rest:refresh-project-list
   * @aliases ldrrpl
   */
  public function refreshProjectList() {
    /** @var \Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector\DrupalRest $connector */
    $connector = \Drupal::service('plugin.manager.l10n_server.connector')
      ->createInstance('drupal_rest:restapi', []);
    /** @var \Drupal\l10n_server\Plugin\l10n_server\Source\RestApi $source */
    $source = \Drupal::service('plugin.manager.l10n_server.source')
      ->createInstance('restapi', []);

    try {
      $connector->refreshProjectList($source);
      $this->logger()
        ->success(dt('Project list has been refreshed.'));
    }
    catch (\Exception $e) {
      $this->logger()
        ->error(dt('Could not refresh project list. Error @code: @message', [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]));
    }
  }

  /**
   * Parse projects.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException|\Exception
   *
   * @option limit
   *   The limit.
   *
   * @command l10n-drupal-rest:parse
   * @aliases ldrp
   */
  public function parse(array $options = ['limit' => 1]) {
    /** @var \Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector\DrupalRest $connector */
    $connector = \Drupal::service('plugin.manager.l10n_server.connector')
      ->createInstance('drupal_rest:restapi', []);

    $goal = $options['limit'];

    if ($connector->isEnabled()) {
      $success = $offset = $failed = 0;

      do {
        // Pick the oldest releases we did not parse yet.
        $query = \Drupal::database()
          ->select('l10n_server_release', 'sr');
        $query
          ->join('l10n_server_project', 'sp');
        $query
          ->fields('sr', ['rid'])
          ->condition('sp.connector_module', [DrupalRest::PROJECT_CONNECTOR_MODULE], 'IN')
          ->condition('sp.enabled', 1)
          ->condition('sr.download_link', '', '<>')
          ->condition('sr.last_parsed', 0)
          ->distinct()
          // @todo Adding this forces mysql to create unwanted temporary tables.
          // ->orderBy('sr.file_date')
          ->range($offset, $goal - $success);
        $result = $query->execute();

        foreach ($result as $record) {
          $release = Release::load($record->rid);
          if ($connector->drupalOrgParseRelease($release)) {
            $success++;
          }
          else {
            $failed++;
          }
          $offset++;
        }
      } while ($success < $goal && $query->countQuery()->execute()->fetchField() > 0);

      $this->logger()->notice(
        'Parsed @success releases successfully, @failed failed parsing.', [
          '@success' => $success,
          '@failed' => $failed,
        ]);
    }
  }

}
