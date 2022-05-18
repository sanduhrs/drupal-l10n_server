<?php

namespace Drupal\l10n_drupal_rest\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Localization server for Drupal (REST API) routes.
 */
class L10nDrupalRestController extends ControllerBase {

  const PROJECT_TITLE = 'Drupal core';

  /**
   * Builds the response.
   */
  public function build() {
    $project_id = \Drupal::database()
      ->select('l10n_server_project', 'sp')
      ->condition('sp.title', static::PROJECT_TITLE)
      ->fields('sp', ['pid'])
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $releases = \Drupal::database()
      ->select('l10n_server_release', 'sr')
      ->condition('pid', $project_id)
      ->fields('sr', ['rid', 'title'])
      ->execute()
      ->fetchAllKeyed();
    uksort($releases, 'version_compare');
    $rid = array_reverse(array_keys($releases))[0];

    require_once __DIR__ . '/../../../../l10n_community/pages.inc';
    [$num_source, $string_counts] = l10n_community_get_l10n_packager_string_count($project_id, $rid);

    $build['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Status based on @num source strings found in Drupal @release. Links are to untranslated strings in the release for that group.', [
          '@num' => number_format($num_source),
          '@release' => $releases[$rid],
        ]),
    ];

    $languages = \Drupal::languageManager()->getLanguages();

    $table_rows = [];
    foreach ($languages as $langcode => $language) {
      // @todo Fix by porting l10n_packager module.
      // $query = \Drupal::database()->select('l10n_server_translation')
      //   ->condition('is_suggestion', 0)
      //   ->condition('is_active', 1)
      //   ->condition('language', $langcode);
      // $user_count = $query->countQuery()->execute()->fetchObject();
      $table_rows[] = [
        [
          'data' => new FormattableMarkup('<a href=":link">' . t('@language_name', ['@language_name' => $language->getName()]) . '</a>', [':link' => '/translate/languages/' . $langcode]),
          // 'sortdata' => t('@language_name', ['@language_name' => $language->getName()]),
        ],
        [
          'data' => ['#theme' => 'progress_bar', '#percent' => 35, '#message' => $this->t('@percent% translated (@num strings to go)', ['@percent' => 35, '@num' => 0])],
          // 'sortdata' => ($num_source == 0 ? 0 : round(($string_counts[$langcode]['translations'] ?? 0) / $num_source * 100, 2)),
        ],
        [
          'data' => $contributors ?? $this->t('n/a'),
          // 'sortdata' => $contributors ?? $this->t('n/a'),
        ],
      ];
    }

    $header = [
      ['data' => t('Language'), 'field' => 'language', 'class' => ['rowhead']],
      ['data' => t('Drupal @release core progress', ['@release' => $releases[$rid]]), 'field' => 'progress'],
      ['data' => t('Contributors'), 'field' => 'contributors'],
    ];
    $build['progress'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $table_rows,
      '#empty' =>t('No files found'),
      '#sticky' => TRUE,
    ];
    return $build;
  }

}
