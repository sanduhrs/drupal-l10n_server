<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

class ReleaseRouteProvider extends AdminHtmlRouteProvider {


  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $entity_type_id = 'l10n_server_project';

    if ($route) {
      $route->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);
    }
    return $route;
  }

}
