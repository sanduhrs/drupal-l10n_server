<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Component\Render\FormattableMarkup;
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
   * Get default project.
   *
   * @return \Drupal\l10n_server\Entity\Project
   */
  private static function getDefaultProject(): Project {
    $query = \Drupal::entityQuery('l10n_server_project')
      ->condition('title', static::DEFAULT_PROJECT);
    $pids = $query->execute();
    return Project::load(reset($pids));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Project $project = NULL): array {
    $query = \Drupal::database()->select('l10n_server_project', 'p')->fields('p', ['pid']);
    $count = $query->countQuery()->execute()->fetchField();

    if (empty($project)) {
      $project = DownloadTranslationForm::getDefaultProject();
    }
    $form['project'] = [
      '#title' => t('Project'),
      '#type' => 'textfield',
      '#default_value' => $project?->id(),
    ];

    if ($count <= 30) {
      // Radio box widget for as much as 5 projects, select widget for 5-30 projects.
      $form['project']['#type'] = ($count <= 5 ? 'radios' : 'select');
      $form['project']['#options'] = [];

      $projects = Project::loadMultiple();
      foreach ($projects as $project) {
        // Title used to conform to the autocomplete behavior.
        $form['project']['#options'][$project->id()] = $project->label();
      }
    }
    else {
      // Autocomplete field for more than 30 projects.
      $form['project'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'l10n_server_project',
        //'#default_value' => $project->id(),
        '#selection_handler' => 'default',
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show downloads'),
      '#button_type' => 'primary',
    ];

    $form += $this->l10n_packager_show_downloads($project);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('l10n_packager.download_project_translations', [
      'project' => $form_state->getValue('project') ?? self::DEFAULT_PROJECT,
    ]);
  }

  /**
   * Generate list of file downloads for given project.
   */
  private function l10n_packager_show_downloads($project) {
    /** @var \Drupal\l10n_packager\PackagerManager $packagerManager */
    $packagerManager = \Drupal::service('l10n_packager.manager');
    $date_formatter = \Drupal::service('date.formatter');

    $files = $branches = [];

    $query = \Drupal::database()->select('l10n_server_release', 'r');
    $query->innerJoin('l10n_packager_release', 'pr', 'r.rid = pr.rid');
    $query->innerJoin('l10n_packager_file', 'lf', 'r.rid = lf.rid');
    $query->innerJoin('file_managed', 'f', 'lf.fid = f.fid');
    $query->fields('r');
    $query->fields('pr');
    $query->addField('pr', 'checked', 'release_checked');
    $query->condition('r.pid', $project->id());
    $result = $query->execute();

    foreach ($result as $item) {
      // Trim versions to only the major version, and any preceeding components.
      // For example, 1.2.3 → 1.x, 8.x-1.0-beta1 → 8.x-1.x.
      $branch = preg_replace('/(\.\d+)?\.[^.]+$/', '', $item->title) . '.x';
      $branches[$branch] = TRUE;
      $files[$item->language][$branch][$item->rid] = $item;
    }

    if (empty($branches)) {
      $form['empty'] = [
        '#markup' => '<p>' . t('No translation downloads found for <em>%project</em>.', ['%project' => $project->label()]) . '</p>',
        '#weight' => 100,
      ];
      return $form;
    }

    ksort($branches);
    $languages = \Drupal::languageManager()->getLanguages();

    $table = [];
    foreach ($languages as $langcode => $language) {
      $row = [];
      // Start off the row with the language name and code.
      $row[] = [
        'data' => new FormattableMarkup('<a href=":link">@language_name</a>', [
          '@language_name' => $language->getName(),
          ':link' => '/translate/languages/' . $langcode,
        ]
      )];
      foreach (array_keys($branches) as $branch) {
        // Generate a cell for each major version.
        if (!empty($files[$langcode][$branch])) {
          krsort($files[$langcode][$branch]);
          $latest_item = array_shift($files[$langcode][$branch]);
          // @todo Fix l().
          $cell = '<p>' . l($latest_item->title . ' (' . format_size($latest_item->filesize) . ')', $packagerManager->getDownloadUrl($project, $branch, $latest_item)) . '</p>';
          $cell .= '<p class="l10n-packager-meta">' . t('Generated: @generated', ['@generated' => $date_formatter->format($latest_item->timestamp, 'custom', 'Y-M-d H:i')]) . '</p>';
          $up_to_date = max($latest_item->checked, $latest_item->release_checked);
          if ($up_to_date > $latest_item->timestamp) {
            $cell .= '<p class="l10n-packager-meta">' . t('Up to date as of: @checked', ['@checked' => $date_formatter->format($up_to_date, 'custom', 'Y-M-d H:i')]) . '</p>';
          }
          $row[] = $cell;
          $row[] = new FormattableMarkup($cell, []);
        }
        else {
          $row[] = t('n/a');
        }
      }
      $table[] = $row;
    }

    $header = array_merge([t('Languages')], array_keys($branches));
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $table,
      '#weight' => 100,
    ];
    return $form;
  }

}
