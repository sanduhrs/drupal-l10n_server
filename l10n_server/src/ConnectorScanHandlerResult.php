<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

/**
 * Connector scan handler result interface.
 */
class ConnectorScanHandlerResult implements ConnectorScanHandlerResultInterface {

  /**
   * Project counter.
   *
   * @var int
   */
  protected int $projects;

  /**
   * Release counter.
   *
   * @var int
   */
  protected int $releases;

  /**
   * Class constructor.
   *
   * @param array $options
   *   The constructor options:
   *   - projects: a project count integer.
   *   - releases: a release count integer.
   */
  public function __construct(array $options = []) {
    $this->projects = $options['projects'] ?? 0;
    $this->releases = $options['releases'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectCount(): int {
    return (int) $this->projects;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectCount(int $count): self {
    $this->projects = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increaseProjectCount(int $count = NULL): self {
    if ($count) {
      $this->projects = $this->projects + $count;
    }
    else {
      $this->projects++;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReleaseCount(): int {
    return (int) $this->releases;
  }

  /**
   * {@inheritdoc}
   */
  public function setReleaseCount(int $count): self {
    $this->releases = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increaseReleaseCount(int $count = NULL): self {
    if ($count) {
      $this->releases = $this->releases + $count;
    }
    else {
      $this->releases++;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSum(): int {
    return (int) ($this->projects + $this->releases);
  }

}
