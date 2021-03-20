<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Project entities.
 */
interface ProjectInterface extends ContentEntityInterface {

  public function getHomepage();
  public function getConnectorModule();
  public function getEnabled();
}
