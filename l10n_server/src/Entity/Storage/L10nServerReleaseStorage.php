<?php

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for refreshable entities.
 */
class L10nServerReleaseStorage extends SqlContentEntityStorage implements L10nRefreshStorageInterface {

  /**
   * Refresh period 4 weeks.
   */
  const REFRESH_PERIOD = 2419200;

  /**
   * {@inheritdoc}
   */
  public function getIdsToRefresh(): array {
    return $this->database
      ->select($this->getBaseTable(), 'r')
      ->fields('r', ['rid'])
      ->condition('last_parsed', time() - static::REFRESH_PERIOD, '<')
      ->condition('queued', 0)
      ->orderBy('file_date', 'ASC')
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getIdsToQueue(): array {
    return $this->database
      ->select($this->getBaseTable(), 'r')
      ->fields('r', ['rid'])
      ->condition('last_parsed', 0)
      ->condition('queued', 0)
      ->orderBy('file_date', 'ASC')
      ->execute()
      ->fetchCol();
  }

}
