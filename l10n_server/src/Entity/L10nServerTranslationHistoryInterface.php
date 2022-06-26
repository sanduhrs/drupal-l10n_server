<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a history entity type.
 */
interface L10nServerTranslationHistoryInterface extends ContentEntityInterface {

  /**
   * Gets action UID.
   *
   * @return int
   *   The action UID integer.
   */
  public function getActionUid(): int;

  /**
   * Sets action UID.
   *
   * @param int $uid
   *   The action UID integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setActionUid(int $uid): self;

  /**
   * Gets action time.
   *
   * @return int
   *   The action time integer.
   */
  public function getActionTime(): int;

  /**
   * Sets action time.
   *
   * @param int $time
   *   The action time integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setActionTime(int $time): self;

  /**
   * Gets action type.
   *
   * @return string
   *   The action type string.
   */
  public function getActionType(): string;

  /**
   * Sets action type.
   *
   * @param string $type
   *   The action type string.
   *
   * @return $this
   *   The entity object.
   */
  public function setActionType(string $type): self;

  /**
   * Gets action medium.
   *
   * @return string
   *   The action medium string.
   */
  public function getActionMedium(): string;

  /**
   * Sets action medium.
   *
   * @param string $medium
   *   The action medium string.
   *
   * @return $this
   *   The entity object.
   */
  public function setActionMedium(string $medium): self;

}
