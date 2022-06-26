<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a line entity type.
 */
interface L10nServerLineInterface extends ContentEntityInterface {

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
   *   The entity object.
   */
  public function setReleaseId(int $rid): self;

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
   *   The entity object.
   */
  public function setFileId(int $fid): self;

  /**
   * Gets line number.
   *
   * @return int
   *   The line number integer.
   */
  public function getLineNumber(): int;

  /**
   * Sets line number.
   *
   * @param int $line_number
   *   The line number integer.
   *
   * @return $this
   *   The entity object.
   */
  public function setLineNumber(int $line_number): self;

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
   * Gets type.
   *
   * @return string
   *   The type string.
   */
  public function getType(): string;

  /**
   * Sets type.
   *
   * @param string $type
   *   The type string.
   *
   * @return $this
   *   The entity object.
   */
  public function setType(string $type): self;

}
