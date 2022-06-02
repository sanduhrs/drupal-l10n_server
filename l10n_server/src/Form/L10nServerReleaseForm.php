<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the release entity edit forms.
 */
class L10nServerReleaseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New release %label has been created.', $message_arguments));
        $this->logger('l10n_server')->notice('Created new release %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The release %label has been updated.', $message_arguments));
        $this->logger('l10n_server')->notice('Updated release %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.l10n_server_release.canonical', ['l10n_server_release' => $entity->id()]);

    return $result;
  }

}
