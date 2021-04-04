<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\l10n_server\Entity\ProjectInterface;

class ReleaseListController extends EntityListController {

  /**
   * Page title of the release collection page.
   *
   * @param \Drupal\l10n_server\Entity\ProjectInterface $l10n_server_project
   *   The project of this release.
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\l10n_server\Entity\Routing\ReleaseRouteProvider::getCollectionRoute
   */
  public function title(ProjectInterface $l10n_server_project): TranslatableMarkup {
    return $this->t('Releases of project @project', ['@project' => $l10n_server_project->label()]);
  }

}
