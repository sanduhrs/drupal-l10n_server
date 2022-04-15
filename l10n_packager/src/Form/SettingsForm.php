<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures l10n_packager settings.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'l10n_packager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'l10n_packager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $settings = $this->config('l10n_server.settings');
    $manager = \Drupal::service('l10n_packager.manager');

    $cron = $settings->get('l10n_packager_cron') ?? L10N_PACKAGER_CRON;
    $form['l10n_packager_cron'] = array(
      '#title' => t('Generate packages on every Drupal cron run'),
      '#type' => 'checkbox',
      '#default_value' => $cron,
    );

    $directory = $settings->get('l10n_packager_directory') ?? $manager->directory();
    $form['l10n_packager_directory'] = array(
      '#title' => t('Directory for generated packages'),
      '#description' => t('The directory on the local file system to use to store packages generated. Either relative to the Drupal installation directory or an absolute path on your file system. Drupal should have read and write access to the files and directories found there.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $directory,
      // Create directory by default if possible.
      //@todo: port l10n_packager_admin_check_directory().
      /*'#after_build' => array('l10n_packager_admin_check_directory'),*/
    );

    $update_url = $settings->get('l10n_packager_update_url') ?? L10N_PACKAGER_UPDATE_URL;
    $form['l10n_packager_update_url'] = array(
      '#title' => t('Root URL for translation downloads'),
      '#type' => 'textfield',
      '#default_value' => $update_url,
      '#description' => t('Root URL for the client to build file URLs and fetch updates. The public facing URL for the package directory defined above. Leave blank for not providing any.'),
    );

    $file_path = $settings->get('l10n_packager_filepath') ?? L10N_PACKAGER_FILEPATH;
    $form['l10n_packager_filepath'] = array(
      '#title' => t('Path structure for generated packages'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $file_path,
      '#description' => t("Available tokens are: %project, %release, %core, %version, %extra, %language."),
    );

    $options = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90, 100];
    $release_limit = $settings->get('l10n_packager_release_limit') ?? L10N_PACKAGER_RELEASE_LIMIT;
    $form['l10n_packager_release_limit'] = array(
      '#title' => t('Number of releases to check at once'),
      '#description' => t('The number of releases to check on a manual or cron run.'),
      '#type' => 'select',
      '#options' => array_combine($options, $options),
      '#default_value' => $release_limit,
    );

    $options = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90, 100];
    $file_limit = $settings->get('l10n_packager_file_limit') ?? L10N_PACKAGER_FILE_LIMIT;
    $form['l10n_packager_file_limit'] = array(
      '#title' => t('Maximum number of files to package at once'),
      '#description' => t('The number of files to package on a manual or cron run.'),
      '#type' => 'select',
      '#options' => array_combine($options, $options),
      '#default_value' => $file_limit,
    );

    // Logging settings
    $update = $settings->get('l10n_packager_update') ?? L10N_PACKAGER_UPDATE;
    $options = [3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800];
    $callback = function ($value) {
      return \Drupal::service('date.formatter')->formatInterval($value);
    };
    $period = array(0 => t('Never'), 1 => t('Every cron run')) + array_map($callback, array_combine($options, $options));
    $form['l10n_packager_update'] = array(
      '#title' => t('Repackaging interval'),
      '#type' => 'select',
      '#options' => $period,
      '#default_value' => $update,
      '#description' => t('Time interval for the translations to be automatically repackaged.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::service('l10n_packager.manager');

    $cron = $form_state->getValue('l10n_packager_cron', L10N_PACKAGER_CRON);
    $directory = $form_state->getValue('l10n_packager_directory', $manager->directory());
    $update_url = $form_state->getValue('l10n_packager_update_url', L10N_PACKAGER_UPDATE_URL);
    $file_path = $form_state->getValue('l10n_packager_filepath', L10N_PACKAGER_FILEPATH);
    $release_limit = $form_state->getValue('l10n_packager_release_limit', L10N_PACKAGER_RELEASE_LIMIT);
    $file_limit = $form_state->getValue('l10n_packager_file_limit', L10N_PACKAGER_FILE_LIMIT);
    $update = $form_state->getValue('l10n_packager_update', L10N_PACKAGER_UPDATE);

    $this->config('l10n_packager.settings')
      ->set('l10n_packager_cron', $cron)
      ->set('l10n_packager_directory', $directory)
      ->set('l10n_packager_update_url', $update_url)
      ->set('l10n_packager_filepath', $file_path)
      ->set('l10n_packager_release_limit', $release_limit)
      ->set('l10n_packager_file_limit', $file_limit)
      ->set('l10n_packager_update', $update)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
