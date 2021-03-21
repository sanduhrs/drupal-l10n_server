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
}
