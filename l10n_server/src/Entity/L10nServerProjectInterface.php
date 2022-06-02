<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\l10n_server\ConnectorInterface;

/**
 * Provides an interface defining a project entity type.
 */
interface L10nServerProjectInterface extends ContentEntityInterface {

  /**
   * Is enabled?
   *
   * @return bool
   *   Whether enabled or not boolean.
   */
  public function isEnabled(): bool;

  /**
   * Get connector module.
   *
   * @return string
   *   The connector module identifier.
   */
  public function getConnectorModule(): string;

  /**
   * Set connector module.
   *
   * @param string $module
   *   The connector module identifier.
   *
   * @return $this
   */
  public function setConnectorModule(string $module): self;

  /**
   * Get connector.
   *
   * @return \Drupal\l10n_server\ConnectorInterface|null
   *   A connector or null.
   */
  public function getConnector(): ?ConnectorInterface;

  /**
   * Get homepage.
   *
   * @return string|null
   *   A link string or null.
   */
  public function getHomepage(): ?string;

  /**
   * Set homepage.
   *
   * @param string $link
   *   A link string.
   *
   * @return $this
   */
  public function setHomepage(string $link): self;

  /**
   * Get last parsed.
   *
   * @return int|null
   *   A timestamp integer or null.
   */
  public function getLastParsed(): ?int;

  /**
   * Set last parsed.
   *
   * @param int $timestamp
   *   A timestamp integer.
   *
   * @return $this
   */
  public function setLastParsed(int $timestamp): self;

}
