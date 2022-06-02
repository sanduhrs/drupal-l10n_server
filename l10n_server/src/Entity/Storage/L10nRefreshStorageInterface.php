<?php

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for aggregator feed entity storage classes.
 */
interface L10nRefreshStorageInterface extends ContentEntityStorageInterface {

  /**
   * Returns the ids of the entities that need to be refreshed.
   *
   * @return array
   *   A list of entity ids to be refreshed.
   */
  public function getIdsToRefresh(): array;

  /**
   * Returns the ids of the entities that need to be queued.
   *
   * @return array
   *   A list of entity ids to be queued.
   */
  public function getIdsToQueue(): array;

}
