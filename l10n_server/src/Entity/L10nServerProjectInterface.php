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
   * Get connector.
   *
   * @return \Drupal\l10n_server\ConnectorInterface|null
   *   A connector or null.
   */
  public function getConnector(): ?ConnectorInterface;

  /**
   * Gets title.
   *
   * @return string
   *   The title string.
   */
  public function getTitle(): string;

  /**
   * Sets title.
   *
   * @param string $title
   *   The title string.
   *
   * @return $this
   *   The entity.
   */
  public function setTitle(string $title): self;

  /**
   * Gets URI.
   *
   * @return string
   *   The URI string.
   */
  public function getUri(): string;

  /**
   * Sets URI.
   *
   * @param string $uri
   *   The URI string.
   *
   * @return $this
   *   The entity.
   */
  public function setUri(string $uri): self;

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
   *   The entity.
   */
  public function setConnectorModule(string $module): self;

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
   * @param string $homepage
   *   A homepage URL string.
   *
   * @return $this
   *   The entity.
   */
  public function setHomepage(string $homepage): self;

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
   *   The entity.
   */
  public function setLastParsed(int $timestamp): self;

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

  /**
   * Gets weight.
   *
   * @return int
   *   The weight integer.
   */
  public function getWeight(): int;

  /**
   * Sets weight.
   *
   * @param int $weight
   *   The weight integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setWeight(int $weight): self;

}
