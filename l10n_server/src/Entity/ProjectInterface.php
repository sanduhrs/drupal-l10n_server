<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\l10n_server\ConnectorInterface;

/**
 * Interface for Project entities.
 */
interface ProjectInterface extends ContentEntityInterface {

  public function getHomepage(): string;
  public function getConnectorModule(): string;
  public function getConnector(): ?ConnectorInterface;
  public function getEnabled(): bool;
}
