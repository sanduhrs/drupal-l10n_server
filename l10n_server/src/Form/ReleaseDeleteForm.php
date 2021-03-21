<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

class ReleaseDeleteForm extends ContentEntityDeleteForm {

  /**
   * @{inheritDoc}
   */
  protected function getRedirectUrl() {
    /** @var \Drupal\l10n_server\Entity\Release $release */
    $release = $this->getEntity();
    return Url::fromRoute($release->toUrl('collection')->getRouteName(), ['l10n_server_project' => $release->getProjectId()]);
  }

}
