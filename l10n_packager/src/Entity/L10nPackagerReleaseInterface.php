<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a packager release entity type.
 */
interface L10nPackagerReleaseInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the release status.
   *
   * @return int
   *   The release status integer.
   */
  public function getStatus(): int;

  /**
   * Sets the release status.
   *
   * @param int $status
   *   The release status integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setStatus(int $status): self;

  /**
   * Gets the time release was checked last.
   *
   * @return int
   *   The time the release was checked last.
   */
  public function getCheckedTime(): int;

  /**
   * Sets the time release was checked last.
   *
   * @param int $timestamp
   *   The time the release was checked last.
   *
   * @return $this
   *   The entity object.
   */
  public function setCheckedTime(int $timestamp): self;

}
