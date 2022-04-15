<?php

declare(strict_types=1);

namespace Drupal\l10n_packager;

use Drush\Drush;

class PackagerManager {

  /**
   * Check releases that need repackaging.
   */
  public function checkUpdates() {
    $count_check = $count_files = $time = 0;
    $updates = array();

    $settings = \Drupal::config('l10n_server.settings');
    $interval = $settings->get('l10n_packager_update') ?? 0;

    Drush::output()->writeln(dt('$interval: ' . $interval));

    if ($interval) {

      timer_start('l10n_packager');
      module_load_include('inc', 'l10n_packager');
      $timestamp = REQUEST_TIME - $interval;
      $file_limit = variable_get('l10n_packager_file_limit', 1);
      $count_files = $count_check = 0;

      // Go for it: check releases for repackaging. We need project_uri for later.
      $query = "SELECT r.rid, r.pid, r.title, pr.checked, pr.updated, pr.status, p.uri
              FROM {l10n_server_release} r
              INNER JOIN {l10n_server_project} p ON r.pid = p.pid
              LEFT JOIN {l10n_packager_release} pr ON pr.rid = r.rid
              WHERE pr.status IS NULL
                 OR (pr.status = :status AND (pr.checked < :checked OR pr.updated < :updated))
              ORDER BY pr.checked";
      $result = db_query_range($query, 0, variable_get('l10n_packager_release_limit', 10),
        array(':status' => L10N_PACKAGER_ACTIVE, ':checked' => $timestamp, ':updated' => $timestamp));
      while ((!$file_limit || $file_limit > $count_files) && ($release = $result->fetchObject())) {
        // Set the release branch
        l10n_packager_release_set_branch($release);
        $updates = l10n_packager_release_check($release, FALSE, $file_limit - $count_files, NULL, TRUE);
        $count_files += count($updates);
        $count_check++;
      }
      $timer = timer_stop('l10n_packager');
      $time = $timer['time'];

      watchdog('l10n_packager', '@ms ms for %checked releases/%repack files.', array('%checked' => $count_check, '%repack' => $count_files, '@ms' => $time));
    }

    return array($count_check, $count_files, $time);
  }

  /**
   * Get directory for creating files
   */
  function directory() {
    return \Drupal::config('l10n_server.settings')->get('l10n_packager_directory') ?? \Drupal::config('system.file')->get('default_scheme') . '://l10n_packager';
  }
}
