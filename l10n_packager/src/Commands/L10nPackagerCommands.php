<?php

namespace Drupal\l10n_packager\Commands;

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
class L10nPackagerCommands extends DrushCommands {

  /**
   * Package download updates.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option interval
   *   The interval.
   * @option release-limit
   *   The release limit.
   * @option file-limit
   *   The file limit.
   * @usage l10n_packager:package --release-limit=10
   *   Usage description
   *
   * @command l10n_packager:package
   * @aliases lnpp
   */
  public function package(array $options = ['interval' => 1, 'release-limit' => 10, 'file-limit' => 1]) {
    /** @var \Drupal\l10n_packager\PackagerManager $l10n_packager_manager */
    $l10n_packager_manager = \Drupal::service('l10n_packager.manager');
    [$checked, $updated, $time] = $l10n_packager_manager
      ->checkUpdates($options['interval'], $options['release-limit'], $options['file-limit']);
    $this->logger()->success(dt('!ms ms for !checked releases/!updated files.', [
      '!checked' => $checked,
      '!updated' => $updated,
      '!ms' => $time,
    ]));
  }

}
