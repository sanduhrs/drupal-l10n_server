<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * @file
 *   Localization packager module drush integration.
 */
class packagerCommands extends DrushCommands {

  /**
   * Check translations and refresh files for updated ones.
   *
   * @command l10n_packager
   *
   * @usage l10n_packager
   */
  public function packager() {
    $settings = \Drupal::config('l10n_server.settings');
    $release_limit = $settings->get('l10n_packager_release_limit') ?? L10N_PACKAGER_RELEASE_LIMIT;
    [$checked, $updated, $time] = \Drupal::service('l10n_packager.manager')->checkUpdates();
    $vars = array(
      '!checkmax' => $release_limit,
      '!checked' => $checked,
      '!updated' => $updated,
      '!ms' => $time,
    );
    Drush::output()->writeln(dt("!ms ms for !checked releases/!updated files.", $vars));
  }

}
