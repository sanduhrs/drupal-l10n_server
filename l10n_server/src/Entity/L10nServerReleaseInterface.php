<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a release entity type.
 */
interface L10nServerReleaseInterface extends ContentEntityInterface {

  /**
   * Gets title.
   *
   * @return string
   *   The title string.
   */
  public function getTtitle(): string;

  /**
   * Sets title.
   *
   * @param string $title
   *   The title string.
   *
   * @return $this
   *   The entity object.
   */
  public function setTitle(string $title): self;

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
   * Gets project object.
   *
   * @return \Drupal\l10n_server\Entity\L10nServerProjectInterface
   *   The project object.
   */
  public function getProject(): L10nServerProjectInterface;

  /**
   * Sets project object.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerProjectInterface $project
   *   A project object.
   *
   * @return $this
   *   The entity object.
   */
  public function setProject(L10nServerProjectInterface $project): self;

  /**
   * Gets version string.
   *
   * @return string
   *   The version string.
   */
  public function getVersion(): string;

  /**
   * Sets version string.
   *
   * @param string $version
   *   The version string.
   *
   * @return $this
   *   The entity object.
   */
  public function setVersion(string $version): self;

  /**
   * Gets download link.
   *
   * @return string|null
   *   A link string or null.
   */
  public function getDownloadLink(): ?string;

  /**
   * Sets download link.
   *
   * @param string $link
   *   A link string.
   *
   * @return $this
   *   The entity object.
   */
  public function setDownloadLink(string $link): self;

  /**
   * Gets file hash.
   *
   * @return string|null
   *   The file hash string.
   */
  public function getFileHash(): ?string;

  /**
   * Sets file hash.
   *
   * @param string $hash
   *   The file hash string.
   *
   * @return $this
   *   The entity object.
   */
  public function setFileHash(string $hash): self;

  /**
   * Get file date.
   *
   * @return int|null
   *   A timestamp integer or null.
   */
  public function getFileDate(): ?int;

  /**
   * Set file date.
   *
   * @param int $timestamp
   *   A timestamp integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setFileDate(int $timestamp): self;

  /**
   * Get last parsed.
   *
   * @return int
   *   A timestamp integer.
   */
  public function getLastParsed(): int;

  /**
   * Set last parsed.
   *
   * @param int $timestamp
   *   A timestamp integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setLastParsed(int $timestamp): self;

  /**
   * Get queued time.
   *
   * @return int
   *   A timestamp integer.
   */
  public function getQueuedTime(): int;

  /**
   * Set queued time.
   *
   * @param int $timestamp
   *   A timestamp integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setQueuedTime(int $timestamp): self;

  /**
   * Get source string count.
   *
   * @return int
   *   A count integer.
   */
  public function getSourceStringCount(): int;

  /**
   * Set source string count.
   *
   * @param int $count
   *   A count integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setSourceStringCount(int $count): self;

  /**
   * Get line count.
   *
   * @return int
   *   A count integer.
   */
  public function getLineCount(): int;

  /**
   * Set line count.
   *
   * @param int $count
   *   A count integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setLineCount(int $count): self;

  /**
   * Get file count.
   *
   * @return int
   *   The file count integer.
   */
  public function getFileCount(): int;

  /**
   * Set file count.
   *
   * @param int $count
   *   The file count integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setFileCount(int $count): self;

  /**
   * Get error string count.
   *
   * @return int
   *   A count integer.
   */
  public function getErrorCount(): int;

  /**
   * Set error string count.
   *
   * @param int $count
   *   A count integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setErrorCount(int $count): self;

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
