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
    // @todo Remove when finished porting l10n_packager module.
//    if (!\Drupal::moduleHandler()->moduleExists('l10n_packager')) {
//      $build['content'] = [
//        '#type' => 'item',
//        '#markup' => $this->t('The <em>l10n_packager</em> module is not available.'),
//      ];
//      return $build;
//    }

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
    // [$num_source, $string_counts] = static::l10n_community_get_l10n_packager_string_count($project_id, $rid);

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

  /**
   * Replacement for l10n_community_get_string_count when former stats are too
   * slow.
   *
   * @todo Taken from l10n_community/pages.inc
   *
   * @param int $pid
   *   The project id to get string counts for.
   * @param int $rid
   *   (Optional) The release id to get string counts for. If not provided, the
   *   latest release of the project will be considered.
   *
   * @return array
   *    first element is the number of original strings
   *    second element is the number of translations for each language
   */
  function l10n_community_get_l10n_packager_string_count($pid, $rid = NULL) {
    // Faster queries with l10n_packager, first get latest release id from the
    // l10n_packager_file table for the project requested, then get the
    // translation count of the release.
    if (empty($rid)) {
      $query = \Drupal::database()
        ->select('l10n_server_release', 'sr');
      $query
        ->innerJoin('l10n_packager_file', 'pf', 'sr.rid = pf.rid');
      $query
        ->fields('sr', ['rid'])
        ->condition('pid', $pid)
        ->orderBy('rid', 'DESC')
        ->range(0, 1);
      $rid = $query->execute()->fetchField();
    }
    $results = \Drupal::database()
      ->select('l10n_packager_file', 'pf')
      ->fields('pf', ['language', 'sid_count'])
      ->condition('rid', $rid)
      ->execute()
      ->fetchAllKeyed();
    foreach ($results as $language => $sid_count) {
      $sums[$language]['translations'] = $sid_count;
    }
    ksort($sums);
    // Finally, get the string count of the release
    $query = \Drupal::database()
      ->select('l10n_server_line', 'sl')
      ->fields('sl', ['sid'])
      ->condition('rid', $rid)
      ->distinct();
    $num_source = $query->countQuery()->execute()->fetchField();
    return array($num_source, $sums);
  }

}
