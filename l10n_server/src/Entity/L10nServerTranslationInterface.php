<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a translation entity type.
 */
interface L10nServerTranslationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets string ID.
   *
   * @return int
   *   The string ID integer.
   */
  public function getStringId(): int;

  /**
   * Sets string ID.
   *
   * @param int $sid
   *   The string ID integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setStringId(int $sid): self;

  /**
   * Gets String.
   *
   * @return \Drupal\l10n_server\Entity\L10nServerString
   *   The string entity.
   */
  public function getString(): L10nServerString;

  /**
   * Sets string.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerString $string
   *   The string entity.
   *
   * @return $this
   *   The entity object.
   */
  public function setString(L10nServerString $string): self;

  /**
   * Gets language.
   *
   * @return string
   *   The language string.
   */
  public function getLanguage(): string;

  /**
   * Sets language.
   *
   * @param string $language
   *   The language string.
   *
   * @return $this
   *   The entity object.
   */
  public function setLanguage(string $language): self;

  /**
   * Gets translation.
   *
   * @return string
   *   The translation string.
   */
  public function getTranslationString(): string;

  /**
   * Sets translation.
   *
   * @param string $translation
   *   The translation string.
   *
   * @return $this
   *   The entity object.
   */
  public function setTranslationString(string $translation): self;

  /**
   * Gets UID.
   *
   * @return int
   *   The UID integer.
   */
  public function getUid(): int;

  /**
   * Sets UID.
   *
   * @param int $uid
   *   The UID integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setUid(int $uid): self;

  /**
   * Gets creation timestamp.
   *
   * @return int
   *   The creation timestamp integer.
   */
  public function getCreated(): int;

  /**
   * Sets creation timestamp.
   *
   * @param int $created
   *   The creation timestamp integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setCreated(int $created): self;

  /**
   * Gets modification timestamp.
   *
   * @return int
   *   The modification timestamp integer.
   */
  public function getChanged(): int;

  /**
   * Sets modification timestamp.
   *
   * @param int $changed
   *   The modification timestamp integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setChanged(int $changed): self;

  /**
   * Gets suggestion status.
   *
   * @return bool
   *   The suggestion status boolean.
   */
  public function isSuggestion(): bool;

  /**
   * Sets suggestion status.
   *
   * @param bool $suggestion
   *   The suggestion status boolean.
   *
   * @return $this
   *   The entity object.
   */
  public function setSuggestion(bool $suggestion): self;

  /**
   * Gets status.
   *
   * @return int
   *   The status integer.
   */
  public function getStatus(): int;

  /**
   * Sets status.
   *
   * @param int $status
   *   The status integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setStatus(int $status): self;

}
