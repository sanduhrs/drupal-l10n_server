<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\Entity\File;
use Drupal\l10n_server\Entity\L10nServerRelease;

/**
 * Provides an interface defining a packager file entity type.
 */
interface L10nPackagerFileInterface extends ContentEntityInterface {

  /**
   * Gets the packager file's release.
   *
   * @return \Drupal\l10n_server\Entity\L10nServerRelease
   *   The packager file's release.
   */
  public function getRelease(): int;

  /**
   * Sets the packager file's release.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerRelease $release
   *   The packager file's release.
   *
   * @return $this
   *   The packager file entity.
   */
  public function setRelease(L10nServerRelease $release): self;

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
   *   The packager file entity.
   */
  public function setReleaseId(int $rid): self;

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
   *   The packager file entity.
   */
  public function setLanguage(string $language): self;

  /**
   * Gets file.
   *
   * @return \Drupal\file\Entity\File
   *   The file entity.
   */
  public function getFile(): File;

  /**
   * Sets file.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity.
   *
   * @return $this
   *   The packager file entity.
   */
  public function setFile(File $file): self;

  /**
   * Gets file ID.
   *
   * @return int
   *   The file ID integer.
   */
  public function getFileId(): int;

  /**
   * Sets file ID.
   *
   * @param int $fid
   *   The file ID integer.
   *
   * @return $this
   *   The packager file entity.
   */
  public function setFileId(int $fid): self;

  /**
   * Gets the number of strings in the release.
   *
   * @return int
   *   The number of strings in the release.
   */
  public function getStringCount(): int;

  /**
   * Sets the number of strings in the release.
   *
   * @param int $count
   *   The number of strings in the release.
   *
   * @return $this
   *   The packager file entity.
   */
  public function setStringCount(int $count): self;

  /**
   * Gets last checked time.
   *
   * @return int
   *   The last checked time.
   */
  public function getCheckedTime(): int;

  /**
   * Sets last checked time.
   *
   * @param int $timestamp
   *   The last checked time.
   *
   * @return $this
   *   The packager file entity.
   */
  public function setCheckedTime(int $timestamp): self;

}
