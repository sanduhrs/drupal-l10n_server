<?php

namespace Drupal\l10n_drupal_rest\Controller;

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

    // @todo l10n_drupal_rest.drupal_core.inc::l10n_drupal_rest_drupal_core_status
    $num_source = 10666;

    $build['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Status based on @num source strings found in Drupal @release. Links are to untranslated strings in the release for that group.', [
          '@num' => number_format($num_source),
          '@release' => $releases[$rid],
        ]),
    ];
    return $build;
  }

}
