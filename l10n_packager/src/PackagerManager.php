<?php

declare(strict_types=1);

namespace Drupal\l10n_packager;

/**
 * Packager manager class.
 */
class PackagerManager {

  /**
   * Check releases that need repackaging.
   */
  public function checkUpdates() {
    $config = \Drupal::config('l10n_server.settings');

    $count_check = $count_files = $time = 0;
    $updates = [];

    if ($interval = $config->get('update')) {
      $time_pre = microtime(true);

      module_load_include('inc', 'l10n_packager');
      $timestamp = \Drupal::time()->getRequestTime() - $interval;
      $file_limit = $config->get('file_limit');

      $query = \Drupal::database()
        ->select('l10n_server_release', 'r');
      $query
        ->innerJoin('l10n_server_project', 'p');
      $query
        ->leftJoin('l10n_packager_release', 'pr');
      $orGroup1 = $query->orConditionGroup()
        ->condition('pr.checked', $timestamp, '<')
        ->condition('pr.updated', $timestamp, '<');
      $andGroup = $query->andConditionGroup()
        ->condition('pr.status', 1)
        ->condition($orGroup1);
      $orGroup2 = $query->orConditionGroup()
        ->condition('pr.status', NULL, 'IS NULL')
        ->condition($andGroup);
      $query->condition($orGroup1);
      $query->condition($andGroup);
      $query->condition($orGroup2);
      $result = $query->range(0, $config->get('release_limit'))
        ->execute();

      while ((!$file_limit || $file_limit > $count_files)
          && ($release = $result->fetchObject())) {
        // Set the release branch
        l10n_packager_release_set_branch($release);
        $updates = l10n_packager_release_check($release, FALSE, $file_limit - $count_files, NULL, TRUE);
        $count_files += count($updates);
        $count_check++;
      }
      $time_post = microtime(true);
      $time = $time_post - $time_pre;

      \Drupal::logger('l10n_packager')
        ->notice('@ms ms for @checked releases/@repack files.', [
          '@checked' => $count_check,
          '@repack' => $count_files,
          '@ms' => $time,
        ]);
    }
    return [$count_check, $count_files, $time];
  }

  /**
   * Get directory for creating files
   */
  function directory() {
    return \Drupal::config('l10n_server.settings')->get('directory');
  }
}
