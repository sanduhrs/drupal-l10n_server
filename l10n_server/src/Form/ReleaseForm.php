<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the default form handler for the Release entity.
 */
class ReleaseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\l10n_server\Entity\Release $project */
    $release = $this->entity;
    if ($release->isNew()) {
      $release->setProject($this->getRequest()->get('l10n_server_project'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    /** @var \Drupal\l10n_server\Entity\Release $release */
    $release = $this->entity;
    $form_state->setRedirect($this->entity->toUrl('collection')->getRouteName(), ['l10n_server_project' => $release->getProjectId()]);
    return $saved;
  }

}