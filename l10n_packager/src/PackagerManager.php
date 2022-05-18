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
  public function checkUpdates(int $interval = 1, int $release_limit = 10, int $file_limit = 1) {
    $count_check = $count_files = $time = 0;
    $updates = [];

    if ($interval) {
      $time_pre = microtime(true);

      $timestamp = \Drupal::time()->getRequestTime() - $interval;

      $query = \Drupal::database()
        ->select('l10n_server_release', 'r');
      $query
        ->innerJoin('l10n_server_project', 'p', 'r.pid = p.pid');
      $query
        ->leftJoin('l10n_packager_release', 'pr', 'r.rid = pr.rid');
      $query
        ->fields('r', ['rid', 'pid','title']);
      $query
        ->fields('p', ['uri']);
      $query
        ->fields('pr', ['checked', 'updated','status']);
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
      if ($release_limit) {
        $query->range(0, $release_limit);
      }
      $result = $query->execute();

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
