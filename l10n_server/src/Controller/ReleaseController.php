<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReleaseController extends EntityController {

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    dpm(func_get_args(), __METHOD__);
    return parent::create($container);
  }


  /**
   * @inheritDoc
   */
  public function addTitle($entity_type_id) {
    #$this->entityTypeManager->getStorage('l10n_server_project')->loadByProperties();

    return parent::addTitle($entity_type_id);
  }

}
