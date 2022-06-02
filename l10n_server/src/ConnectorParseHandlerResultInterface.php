<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

/**
 * Connector scan handler result interface.
 */
interface ConnectorParseHandlerResultInterface {

  /**
   * Gets the file count.
   *
   * @return int
   *   The file count integer.
   */
  public function getFileCount(): int;

  /**
   * Sets the file count.
   *
   * @param int $count
   *   The file count integer.
   *
   * @return $this
   */
  public function setFileCount(int $count): self;

  /**
   * Increase file count.
   *
   * @param int|null $count
   *   The number to increase or NULL to increase one.
   *
   * @return $this
   */
  public function increaseFileCount(int $count = NULL): self;

  /**
   * Gets the line count.
   *
   * @return int
   *   The line count integer.
   */
  public function getLineCount(): int;

  /**
   * Sets the line count.
   *
   * @param int $count
   *   The line count integer.
   *
   * @return $this
   */
  public function setLineCount(int $count): self;

  /**
   * Increase line count.
   *
   * @param int|null $count
   *   The number to increase or NULL to increase one.
   *
   * @return $this
   */
  public function increaseLineCount(int $count = NULL): self;

  /**
   * Gets the string count.
   *
   * @return int
   *   The string count integer.
   */
  public function getStringCount(): int;

  /**
   * Sets the string count.
   *
   * @param int $count
   *   The string count integer.
   *
   * @return $this
   */
  public function setStringCount(int $count): self;

  /**
   * Increase string count.
   *
   * @param int|null $count
   *   The number to increase or NULL to increase one.
   *
   * @return $this
   */
  public function increaseStringCount(int $count = NULL): self;

  /**
   * Gets the error count.
   *
   * @return int
   *   The error count integer.
   */
  public function getErrorCount(): int;

  /**
   * Sets the error count.
   *
   * @param int $count
   *   The error count integer.
   *
   * @return $this
   */
  public function setErrorCount(int $count): self;

  /**
   * Increase error count.
   *
   * @param int|null $count
   *   The number to increase or NULL to increase one.
   *
   * @return $this
   */
  public function increaseErrorCount(int $count = NULL): self;

}
