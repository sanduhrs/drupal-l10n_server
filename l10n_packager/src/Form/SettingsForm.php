<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\File\FileSystemInterface;
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
    $config = $this->config('l10n_packager.settings');
    $form['cron'] = [
      '#title' => t('Generate packages on every Drupal cron run'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cron'),
    ];
    $form['directory'] = [
      '#title' => t('Directory for generated packages'),
      '#description' => t('The directory on the local file system to use to store packages generated. Either relative to the Drupal installation directory or an absolute path on your file system. Drupal should have read and write access to the files and directories found there.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('directory'),
    ];
    $form['update_url'] = [
      '#title' => t('Root URL for translation downloads'),
      '#type' => 'textfield',
      '#default_value' => $config->get('update_url'),
      '#description' => t('Root URL for the client to build file URLs and fetch updates. The public facing URL for the package directory defined above. Leave blank for not providing any.'),
    ];
    $form['filepath'] = [
      '#title' => t('Path structure for generated packages'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('filepath') ,
      '#description' => t("Available tokens are: %project, %release, %core, %version, %extra, %language."),
    ];
    $limits = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90, 100];
    $form['release_limit'] = [
      '#title' => t('Number of releases to check at once'),
      '#description' => t('The number of releases to check on a manual or cron run.'),
      '#type' => 'select',
      '#options' => array_combine($limits, $limits),
      '#default_value' => $config->get('release_limit'),
    ];
    $form['file_limit'] = [
      '#title' => t('Maximum number of files to package at once'),
      '#description' => t('The number of files to package on a manual or cron run.'),
      '#type' => 'select',
      '#options' => array_combine($limits, $limits),
      '#default_value' => $config->get('file_limit'),
    ];
    $interval = [3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800];
    $callback = function ($value) {
      return \Drupal::service('date.formatter')->formatInterval($value);
    };
    $period = [0 => t('Never'), 1 => t('Every cron run')] + array_map($callback, array_combine($interval, $interval));
    $form['update'] = [
      '#title' => t('Repackaging interval'),
      '#type' => 'select',
      '#options' => $period,
      '#default_value' => $config->get('update'),
      '#description' => t('Time interval for the translations to be automatically repackaged.'),
    ];

    // Rebuild metafile after submission.
    $form['#submit'][] = 'l10n_packager_export_metafile';
    $form['#submit'][] = 'l10n_packager_export_metafile';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->prepareDirectory(
      $form_state->getValue('directory'),
      FileSystemInterface::CREATE_DIRECTORY
    );

    /** @var \Drupal\l10n_packager\PackagerManager $packagerManager */
    $packagerManager = \Drupal::service('l10n_packager.manager');
    $packagerManager->exportMetafile();

    $this->config('l10n_packager.settings')
      ->set('cron', $form_state->getValue('cron'))
      ->set('directory', $form_state->getValue('directory'))
      ->set('update_url', $form_state->getValue('update_url'))
      ->set('filepath', $form_state->getValue('filepath'))
      ->set('release_limit', $form_state->getValue('release_limit'))
      ->set('file_limit', $form_state->getValue('file_limit'))
      ->set('update', $form_state->getValue('update'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
