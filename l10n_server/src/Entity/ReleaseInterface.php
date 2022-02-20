<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Release entities.
 */
interface ReleaseInterface extends ContentEntityInterface {

  /**
   * ID of the referenced project.
   *
   * @return int
   */
  public function getProjectId(): int;

  /**
   * Referenced project.
   *
   * @return \Drupal\l10n_server\Entity\ProjectInterface
   */
  public function getProject(): ProjectInterface;

  public function setProject(ProjectInterface $project): ReleaseInterface;

  public function setLastParsed(?int $time): ReleaseInterface;
  public function setSourceStringCounter(int $count): ReleaseInterface;

  public function getLastTimeParsed(): int;
  public function getDownloadLink(): ?string;
  public function getFileDate(): ?int;
}
