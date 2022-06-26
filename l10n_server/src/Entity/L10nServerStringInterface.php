<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a string entity type.
 */
interface L10nServerStringInterface extends ContentEntityInterface {

  /**
   * Gets context.
   *
   * @return string
   *   The context string.
   */
  public function getContext(): string;

  /**
   * Sets context.
   *
   * @param string $context
   *   The context string.
   *
   * @return $this
   *   The entity object.
   */
  public function setContext(string $context): self;

  /**
   * Gets hashkey.
   *
   * @return string
   *   The hashkey string.
   */
  public function getHashkey(): string;

  /**
   * Sets hashkey.
   *
   * @param string $hashkey
   *   The hashkey string.
   *
   * @return $this
   *   The entity object.
   */
  public function setHashkey(string $hashkey): self;

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
   *   The entity object.
   */
  public function setValue(string $value): self;

}
