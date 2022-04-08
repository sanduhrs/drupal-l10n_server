<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form allowing user to pick projects to show available translation downloads for.
 */
final class DownloadTranslationForm extends FormBase {

  public const DEFAULT_PROJECT = 'Drupal core';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'l10n_packager_download_translation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    string $project = self::DEFAULT_PROJECT
  ): array {
    $defaultValues = $form_state->cleanValues()->getValues();

    $form['project'] = [
      '#default_value' => $defaultValues['project'] ?? $project,
      '#title' => t('Pick a project'),
      '#type' => 'textfield',
      '#autocomplete_path' => 'translate/project-autocomplete',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Show downloads'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    parent::validateForm($form, $form_state);
    $defaultValues = $form_state->cleanValues()->getValues();

    if (empty($defaultValues['project'])) {
      $form_state->setErrorByName('project', $this->t('Invalid project name.'));
    }

    // @todo: port these methods to D9.
    //$project = l10n_server_get_projects(
    //  ['uri' => l10n_community_project_uri_by_title($defaultValues['values']['project'])]
    //);
    //if ($project) {
    //  $form_state['values']['uri'] = $project->uri;
    //}
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $form_state->setRedirect(
      'l10n_packager.download_project_translations',
      ['project' => $form_state->getValue('project') ?? self::DEFAULT_PROJECT]
    );
  }

}
