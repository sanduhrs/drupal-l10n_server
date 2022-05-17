<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\Entity\Project;

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
  public function buildForm(array $form, FormStateInterface $form_state, Project $project = NULL): array {
    $form['project'] = [
      '#title' => t('Pick a project'),
      '#type' => 'textfield',
      '#default_value' => $project?->id(),
    ];
    $projects = l10n_server_get_projects();
    if (($count = count($projects)) <= 30) {
      // Radio box widget for as much as 5 projects, select widget for 5-30 projects.
      $form['project']['#type'] = ($count <= 5 ? 'radios' : 'select');
      $form['project']['#options'] = [];
      foreach ($projects as $project) {
        // Title used to conform to the autocomplete behavior.
        $form['project']['#options'][$project->pid] = $project->title;
      }
    }
    else {
      // Autocomplete field for more than 30 projects.
      $form['project'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'l10n_server_project',
        '#default_value' => $project,
        '#selection_handler' => 'default',
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show downloads'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect(
      'l10n_packager.download_project_translations',
      ['project' => $form_state->getValue('project') ?? self::DEFAULT_PROJECT]
    );
  }

}
