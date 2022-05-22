<?php

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\Entity\Project;
use Drupal\l10n_server\Entity\Release;

/**
 * Provides a Localization packager form.
 */
class PackagerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'l10n_packager_package';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::database()
      ->select('l10n_server_project', 'p')
      ->fields('p', ['pid']);
    $count = $query->countQuery()->execute()->fetchField();

    $form['projects'] = [
      '#type' => 'fieldset',
      '#title' => t('Project translations'),
      '#description' => t('Repackage all translations of a single project or release.'),
    ];
    $form['projects']['project'] = [
      '#title' => t(' Project'),
      '#required' => TRUE,
    ];

    if ($count <= 30) {
      // Radio box widget for as much as 5 projects, select widget for 5-30 projects.
      $form['projects']['project']['#type'] = ($count <= 5 ? 'radios' : 'select');
      $form['projects']['project']['#options'] = [];

      $projects = Project::loadMultiple();
      foreach ($projects as $project) {
        // Title used to conform to the autocomplete behavior.
        $form['projects']['project']['#options'][$project->id()] = $project->label();
      }
    }
    else {
      // Autocomplete field for more than 30 projects.
      $form['projects']['project'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t(' Project'),
        '#required' => TRUE,
        '#target_type' => 'l10n_server_project',
        '#selection_handler' => 'default',
      ];
    }

    $form['projects']['release'] = [
      '#title' => $this->t('Release'),
      '#type' => 'textfield',
      '#description' => $this->t('Optionally select a release name like <em>6.x-1.0-beta1</em> or a partial release name like <em>6.x%</em>.'),
    ];
    $form['projects']['languages'] = [
      '#type' => 'select',
      '#title' => $this->t('Only for these languages'),
      '#multiple' => TRUE,
      '#default_value' => [],
      '#options' => array_combine(
        array_keys(\Drupal::languageManager()->getLanguages()),
        array_keys(\Drupal::languageManager()->getLanguages())
      ),
      '#description' => $this->t('Select none for all languages. Otherwise pick the languages you want repackaged.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['repackage'] = [
      '#type' => 'submit',
      '#value' => $this->t('Repackage now'),
      '#button_type' => 'primary',
      '#name' => 'repackage',
    ];
    $form['actions']['mark'] = [
      '#type' => 'submit',
      '#value' => $this->t('Mark for repackaging'),
      '#button_type' => 'primary',
      '#name' => 'mark',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database()
      ->select('l10n_server_release', 'r');
    $query
      ->innerJoin('l10n_server_project', 'p', 'r.pid = p.pid');
    $query
      ->fields('r', ['pid']);
    $query->condition('p.pid', $form_state->getValue('project'), '=');

    if ($release = $form_state->getValue('release')) {
      $query->condition('r.title', $release, 'LIKE');
    }

    $count = $query->countQuery()->execute()->fetchField();
    if (!$count) {
      $form_state->setErrorByName('release', t('No releases found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pid = $form_state->getValue('project');
    $release = $form_state->getValue('release');
    $languages = $form_state->getValue('languages');

    $query = \Drupal::database()
      ->select('l10n_server_release', 'r')
      ->fields('r', ['rid']);
    if ($pid) {
      $query = $query->condition('pid', $pid);
    }
    if ($release) {
      $query = $query->condition('title', $release, 'LIKE');
    }

    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#name'] === 'repackage') {
      $rids = $query->execute()->fetchCol();
      if (!empty($rids)) {
        $batch = $this->releaseBatch($rids, $languages);
        batch_set($batch);
      }
      else {
        $this->messenger()->addError(t('No releases found for repackaging.'), 'error');
      }
    }
    else if ($trigger['#name'] === 'mark') {
      $affected_rows = \Drupal::database()
        ->update('l10n_packager_release')
        ->fields([
          'updated' => 0,
          'checked' => 0,
          'status'  => L10N_PACKAGER_ACTIVE,
        ])
        ->condition('rid', $query, 'IN')
        ->execute();
      $this->messenger()
        ->addStatus(t("Marked %count releases for repackaging.", [
          '%count' => $affected_rows,
        ]));
    }
  }

  /**
   * Create batch for repackaging releases.
   *
   * @param $rid
   *   Release id or array of release ids.
   * @param $languages
   *   Array of language codes to repackage or none.
   *
   * @return
   *   Batch array.
   */
  public function releaseBatch($rid, $languages = NULL): array {
    $rids = is_array($rid) ? $rid : [$rid];

    // All languages if no languages passed
    $languages = !empty($languages) ? $languages : array_keys(\Drupal::languageManager()->getLanguages());
    foreach ($rids as $rid) {
      foreach ($languages as $langcode) {
        $operations[] = [static::class . '::batchRepackage', [$rid, $langcode]];
      }
    }

    if (!empty($operations)) {
      return $this->buildBatch($operations, t('Repackaging translation files.'));
    }
    return [];
  }

  /**
   * Get batch stub.
   */
  private static function buildBatch($operations = [], $title = '', $init_message = '', $progress_message = '', $error_message = '', $finished = '') {
    return [
      'title' => $title ?: t('Translations packager.'),
      'init_message' => $init_message ?: t('Commencing'),
      'progress_message' => $progress_message ?: t('Processed @current out of @total.'),
      'error_message' => $error_message ?: t('An error occurred during processing.'),
      'finished' => $finished ?: static::class . '::finishBatch',
      'operations' => $operations,
    ];
  }

  /**
   * Finish batch process.
   *
   * @param $success
   * @param $results
   * @param $operations
   *
   * @return void
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One post processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addStatus($message);
  }

  /**
   * Batch callback to repackage a release.
   *
   * @param $rid
   *   Release id.
   * @param $langcode
   *   Language object to package.
   */
  public static function batchRepackage($rid, $langcode) {
    if ($release = Release::load($rid)) {
      /** @var \Drupal\l10n_packager\PackagerManager $packagerManager */
      $packagerManager = \Drupal::service('l10n_packager.manager');

      $languages = \Drupal::languageManager()->getLanguages();
      $language = $languages[$langcode];
      $updates = $packagerManager->releaseCheck($release, TRUE, 0, $language);
      if ($file = current($updates)) {
        \Drupal::messenger()->addMessage(t("Repackaged release @release for @language. Created file @filename.", [
          '@release' => $release->get('uri') . '-' . $release->label(),
          '@filename' => $file->filename,
          '@language' => t($language->getName()),
        ]));
      }
    }
  }

}
