<?php

/**
 * @file
 *   Front end pages for translation packaging.
 */

/**
 * Generate list of file downloads for given project.
 */
function l10n_packager_show_downloads($project) {
  $files = $branches = [];

  $query = \Drupal::database()->select('l10n_server_release', 'r');
  $query->innerJoin('l10n_packager_release', 'pr', 'r.rid = pr.rid');
  $query->innerJoin('l10n_packager_file', 'lf', 'r.rid = lf.rid');
  $query->innerJoin('file_managed', 'f', 'lf.fid = f.fid');
  $query->fields('r')->fields('pr');
  $query->addField('pr', 'checked', 'release_checked');
  $query->condition('r.pid', $project->pid);
  $result = $query->execute();

  foreach ($result as $item) {
    // Trim versions to only the major version, and any preceeding components.
    // For example, 1.2.3 → 1.x, 8.x-1.0-beta1 → 8.x-1.x.
    $branch = preg_replace('/(\.\d+)?\.[^.]+$/', '', $item->title) . '.x';
    $branches[$branch] = TRUE;
    $files[$item->language][$branch][$item->rid] = $item;
  }

  if (empty($branches)) {
    return t('No translation downloads found for %project.', ['%project' => $project->title]);
  }

  ksort($branches);
  // $languages = l10n_community_get_languages();
  $languages = \Drupal::languageManager()->getLanguages();

  $table = array();
  foreach ($languages as $langcode => $language) {
    $row = [];
    // Start off the row with the language name and code.
    $row[] = [
      'data' => [
        '#type' => 'link',
        '#title' => t($language->getName()),
        '#href' => 'translate/languages/' . $langcode,
      ]
    ];
    foreach (array_keys($branches) as $branch) {
      // Generate a cell for each major version.
      if (!empty($files[$langcode][$branch])) {
        krsort($files[$langcode][$branch]);
        $latest_item = array_shift($files[$langcode][$branch]);
        $cell = '<p>' . l($latest_item->title . ' (' . format_size($latest_item->filesize) . ')'/*, l10n_packager_get_download_url($project, $branch, $latest_item)*/) . '</p>';
        $cell .= '<p class="l10n-packager-meta">' . t('Generated: @generated', ['@generated' => /*format_date*/($latest_item->timestamp/*, 'custom', 'Y-M-d H:i'*/)]) . '</p>';
        $up_to_date = max($latest_item->checked, $latest_item->release_checked);
        if ($up_to_date > $latest_item->timestamp) {
          $cell .= '<p class="l10n-packager-meta">' . t('Up to date as of: @checked', array('@checked' => /*format_date*/($up_to_date/*, 'custom', 'Y-M-d H:i'*/))) . '</p>';
        }
        $row[] = $cell;
      }
      else {
        $row[] = t('n/a');
      }
    }
    $table[] = $row;
  }

  $header = array_merge(array(t('Languages')), array_keys($branches));
  $form['table'] = [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $table,
  ];
  return $form;
}
