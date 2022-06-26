<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a status flag entity type.
 *
 * @todo Add has_suggestion and has_translation.
 */
interface L10nServerStatusFlagInterface extends ContentEntityInterface {

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
   * Whether the string has a translation.
   *
   * @return bool
   *   The translation status boolean.
   */
  public function hasTranslationString(): bool;

  /**
   * Whether the string has a suggestion.
   *
   * @return bool
   *   The suggestion status boolean.
   */
  public function hasSuggestionString(): bool;

}
