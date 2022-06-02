<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

/**
 * Connector scan handler result interface.
 */
interface ConnectorScanHandlerResultInterface {

  /**
   * Gets the project count.
   *
   * @return int
   *   The project count.
   */
  public function getProjectCount(): int;

  /**
   * Sets the project count.
   *
   * @param int $count
   *   The project count.
   *
   * @return $this
   */
  public function setProjectCount(int $count): self;

  /**
   * Increase project count.
   *
   * @param int|null $count
   *   An integer to increase the count with.
   *
   * @return $this
   */
  public function increaseProjectCount(int $count = NULL): self;

  /**
   * Gets the release count.
   *
   * @return int
   *   The release count.
   */
  public function getReleaseCount(): int;

  /**
   * Sets the release count.
   *
   * @param int $count
   *   The release count.
   *
   * @return $this
   */
  public function setReleaseCount(int $count): self;

  /**
   * Increase release count.
   *
   * @param int|null $count
   *   An integer to increase the count with.
   *
   * @return $this
   */
  public function increaseReleaseCount(int $count = NULL): self;

  /**
   * Gets the sum of all counters.
   *
   * @return int
   *   The count integer.
   */
  public function getSum(): int;

}
