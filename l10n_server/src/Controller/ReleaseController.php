<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Controller;

use Drupal\Core\Entity\Controller\EntityController;

/**
 * Class ReleaseController
 *
 * @package Drupal\l10n_server\Controller
 */
class ReleaseController extends EntityController {

  /**
   * @inheritDoc
   */
  public function addTitle($entity_type_id) {
    #$this->entityTypeManager->getStorage('l10n_server_project')->loadByProperties();

    return parent::addTitle($entity_type_id);
  }

}
