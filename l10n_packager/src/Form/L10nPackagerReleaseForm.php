<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the packager release entity edit forms.
 */
class L10nPackagerReleaseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New packager release %label has been created.', $message_arguments));
        $this->logger('l10n_packager')->notice('Created new packager release %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The packager release %label has been updated.', $message_arguments));
        $this->logger('l10n_packager')->notice('Updated packager release %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.l10n_packager_release.collection');

    return $result;
  }

}
