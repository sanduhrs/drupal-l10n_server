<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a translation entity type.
 */
interface L10nServerTranslationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
