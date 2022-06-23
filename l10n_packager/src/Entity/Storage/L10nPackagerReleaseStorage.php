<?php

namespace Drupal\l10n_packager\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\l10n_packager\Entity\L10nPackagerRelease;
use Drupal\l10n_packager\L10nPackager;
use Drupal\l10n_server\Entity\Storage\L10nRefreshStorageInterface;

/**
 * Defines the storage handler class for refreshable entities.
 */
class L10nPackagerReleaseStorage extends SqlContentEntityStorage implements L10nRefreshStorageInterface {

  /**
   * Refresh period 4 weeks.
   */
  const REFRESH_PERIOD = 2419200;

  /**
   * {@inheritdoc}
   */
  public function getIdsToRefresh(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIdsToQueue(): array {
    $query = $this->database
      ->select('l10n_server_release', 'r');
    $query
      ->innerJoin('l10n_server_project', 'p', 'r.pid = p.pid');
    $query
      ->leftJoin('l10n_packager_release', 'pr', 'pr.rid = r.rid');
    $query
      ->fields('r', ['rid']);
      // ->fields('r', ['rid', 'pid', 'title'])
      // ->fields('p', ['uri'])
      // ->fields('pr', ['checked', 'changed', 'status']);

    $orGroup0 = $query->orConditionGroup()
      ->condition('pr.checked', \Drupal::time()->getRequestTime() - static::REFRESH_PERIOD, '<')
      ->condition('pr.changed', \Drupal::time()->getRequestTime() - static::REFRESH_PERIOD, '<');
    $andGroup1 = $query->andConditionGroup()
      ->condition('pr.status', L10nPackagerRelease::ACTIVE)
      ->condition($orGroup0);
    $orGroup1 = $query->orConditionGroup()
      ->condition('pr.status', NULL, 'IS NULL')
      ->condition($andGroup1);

    $query
      ->condition($orGroup1)
      ->orderBy('pr.checked');
    return $query
      ->execute()
      ->fetchCol();
  }

}
