<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

/**
 * Connector scan handler result interface.
 */
class ConnectorParseHandlerResult implements ConnectorParseHandlerResultInterface {

  /**
   * File counter.
   *
   * @var int
   */
  protected int $files;

  /**
   * Line counter.
   *
   * @var int
   */
  protected int $lines;

  /**
   * String counter.
   *
   * @var int
   */
  protected int $strings;

  /**
   * Errors counter.
   *
   * @var int
   */
  protected int $errors;

  /**
   * Class constructor.
   *
   * @param array $options
   *   The constructor options:
   *   - files: a files count integer.
   *   - lines: a lines count integer.
   *   - strings: a strings count integer.
   */
  public function __construct(array $options = []) {
    $this->files = $options['files'] ?? 0;
    $this->lines = $options['lines'] ?? 0;
    $this->strings = $options['strings'] ?? 0;
    $this->errors = $options['errors'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileCount(): int {
    return (int) $this->files;
  }

  /**
   * {@inheritdoc}
   */
  public function setFileCount(int $count): self {
    $this->files = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increaseFileCount(int $count = NULL): self {
    if ($count) {
      $this->files = $this->files + $count;
    }
    else {
      $this->files++;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineCount(): int {
    return (int) $this->lines;
  }

  /**
   * {@inheritdoc}
   */
  public function setLineCount(int $count): self {
    $this->lines = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increaseLineCount(int $count = NULL): self {
    if ($count) {
      $this->lines = $this->lines + $count;
    }
    else {
      $this->lines++;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringCount(): int {
    return (int) $this->strings;
  }

  /**
   * {@inheritdoc}
   */
  public function setStringCount(int $count): self {
    $this->strings = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increaseStringCount(int $count = NULL): self {
    if ($count) {
      $this->strings = $this->strings + $count;
    }
    else {
      $this->strings++;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorCount(): int {
    return (int) $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorCount(int $count): self {
    $this->errors = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increaseErrorCount(int $count = NULL): self {
    if ($count) {
      $this->errors = $this->errors + $count;
    }
    else {
      $this->errors++;
    }
    return $this;
  }

}
