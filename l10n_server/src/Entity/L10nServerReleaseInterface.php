<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a release entity type.
 */
interface L10nServerReleaseInterface extends ContentEntityInterface {

  /**
   * Get ID of the referenced project.
   *
   * @return int
   *   An project identifier integer.
   */
  public function getProjectId(): int;

  /**
   * Get referenced project.
   *
   * @return \Drupal\l10n_server\Entity\L10nServerProjectInterface
   *   A project entity.
   */
  public function getProject(): L10nServerProjectInterface;

  /**
   * Set referenced project.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerProjectInterface $project
   *   A project entity.
   *
   * @return $this
   */
  public function setProject(L10nServerProjectInterface $project): self;

  /**
   * Get version string.
   *
   * @return string
   *   The version string.
   */
  public function getVersion(): string;

  /**
   * Set version string.
   *
   * @param string $version
   *   The version string.
   *
   * @return $this
   */
  public function setVersion(string $version): self;

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
   */
  public function setQueuedTime(int $timestamp): self;

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
   */
  public function setLastParsed(int $timestamp): self;

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
   */
  public function setLineCount(int $count): self;

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
   */
  public function setErrorCount(int $count): self;

  /**
   * Get download link.
   *
   * @return string|null
   *   A link string or null.
   */
  public function getDownloadLink(): ?string;

  /**
   * Set download link.
   *
   * @param string $link
   *   A link string.
   *
   * @return $this
   */
  public function setDownloadLink(string $link): self;

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
   */
  public function setFileDate(int $timestamp): self;

}
