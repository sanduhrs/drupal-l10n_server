<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an error entity type.
 */
interface L10nServerErrorInterface extends ContentEntityInterface {

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
   *   The entity object
   */
  public function setReleaseId(int $rid): self;

  /**
   * Gets value.
   *
   * @return string
   *   The value string.
   */
  public function getValue(): string;

  /**
   * Sets value.
   *
   * @param string $value
   *   The value string.
   *
   * @return $this
   *   The entity object
   */
  public function setValue(string $value): self;

}
