<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a file entity type.
 */
interface L10nServerFileInterface extends ContentEntityInterface {

  /**
   * Gets project ID.
   *
   * @return int
   *   The project ID integer.
   */
  public function getProjectId(): int;

  /**
   * Sets project ID.
   *
   * @param int $pid
   *   The project ID integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setProjectId(int $pid): self;

  /**
   * Gets release ID.
   *
   * @return int
   *   The release ID integer.
   */
  public function getReleaseId(): int;

  /**
   * Sets release ID.
   *
   * @param int $rid
   *   The release ID integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setReleaseId(int $rid): self;

  /**
   * Gets location.
   *
   * @return string
   *   The location string.
   */
  public function getLocation(): string;

  /**
   * Sets location.
   *
   * @param string $location
   *   The location string.
   *
   * @return $this
   *   The entity object.
   */
  public function setLocation(string $location): self;

  /**
   * Gets revision.
   *
   * @return string
   *   The revision string.
   */
  public function getRevision(): string;

  /**
   * Sets revision.
   *
   * @param string $revision
   *   The revision string.
   *
   * @return $this
   *   The entity object.
   */
  public function setRevision(string $revision): self;

}
