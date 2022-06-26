<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Localization packager settings for this site.
 */
class L10nPackagerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'l10n_packager_l10n_packager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['l10n_packager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filepath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filepath'),
      '#default_value' => $this->config('l10n_packager.settings')->get('filepath'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('l10n_packager.settings')
      ->set('filepath', $form_state->getValue('filepath'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
