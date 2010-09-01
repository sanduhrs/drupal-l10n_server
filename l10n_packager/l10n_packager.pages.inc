<?php
// $Id$

/**
 * @file
 *   Front end pages for translation packaging.
 */

function l10n_packager_explore_downloads() {
  $projects = l10n_server_get_projects();
  $output = drupal_get_form('l10n_packager_download_project_form', $projects);
  $output .= l10n_packager_show_downloads();
  return $output;
}

/**
 * Form callback. Allow user to pick project to show downloads for.
 */
function l10n_packager_download_project_form(&$form_state, $projects) {
  if (empty($form_state['values'])) {
    $form_state['values'] = array(
      // @todo: bad, bad hardcoding of Drupal as project.
      'project' => (isset($_GET['project']) && isset($projects[$_GET['project']]) ? $projects[$_GET['project']]->title : 'Drupal core'),
    );
  }
  $form['project'] = array(
    '#default_value' => $form_state['values']['project'],
    '#title' => t('Pick a project'),
  );
  if (($count = count($projects)) <= 30) {
    // Radio box widget for as much as 5 projects, select widget for 5-30 projects.
    $form['project']['#type'] = ($count <= 5 ? 'radios' : 'select');
    $form['project']['#options'] = array();
    foreach ($projects as $project) {
      // Title used to conform to the autocomplete behavior.
      $form['project']['#options'][$project->title] = $project->title;
    }
  }
  else {
    // Autocomplete field for more then 30 projects.
    $form['project'] += array(
      '#type' => 'textfield',
      '#autocomplete_path' => 'translate/project-autocomplete',
    );
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Show downloads'));
  return $form;
}

/**
 * Form validation.
 */
function l10n_packager_download_project_form_validate($form, &$form_state) {
  if (!empty($form_state['values']['project'])) {
    if ($project = l10n_server_get_projects(array('uri' => l10n_community_project_uri_by_title($form_state['values']['project'])))) {
      $form_state['values']['uri'] = $project->uri;
    }
    else {
      form_set_error('project', t('Invalid project name.'));
    }
  }
}

/**
 * Form submission.
 */
function l10n_packager_download_project_form_submit($form, $form_state) {
  drupal_goto('translate/downloads', 'project='. $form_state['values']['uri']);
}

/**
 * Generate list of file downloads for given project.
 */
function l10n_packager_show_downloads() {
  if (empty($_GET['project'])) {
    if ($drupal = l10n_server_get_projects(array('uri' => 'drupal'))) {
      $project = $drupal;
    }
    else {
      return '';
    }
  }
  else {
    $project = l10n_server_get_projects(array('uri' => $_GET['project']));
    if (empty($project)) {
      drupal_set_message(t('Requested project not found.'), 'warning');
      return '';
    }
  }

  $files = array();
  $majors = array();
  $result = db_query('SELECT * FROM {l10n_server_release} r INNER JOIN {l10n_packager_file} lf ON r.rid = lf.rid INNER JOIN {files} f ON lf.fid = f.fid WHERE r.pid = %d', $project->pid);
  while ($item = db_fetch_object($result)) {
    $majors[intval($item->title)] = TRUE;
    $files[$item->language][intval($item->title)][$item->title] = $item;
  }

  if (empty($majors)) {
    drupal_set_message(t('No translation downloads found for %project.', array('%project' => $project->title)), 'warning');
    return '';
  }

  // Set proper title for given project.
  drupal_set_title(t('@project translation downloads', array('@project' => $project->title)));

  ksort($majors);
  $languages = l10n_community_get_languages();
  $download_url = variable_get('l10n_packager_update_url', file_create_url(l10n_packager_directory()));

  $table = array();
  foreach ($languages as $langcode => $language) {
    $row = array();
    // Start off the row with the language name and code.
    $row[] = array('data' => t($language->name) .' ('. $langcode .')', 'class' => 'rowhead');
    foreach ($majors as $major) {
      $major_cell = array();
      // Generate a cell for each major version.
      var_dump($files[$langcode][$major]);
      if (!empty($files[$langcode][$major])) {
        ksort($files[$langcode][$major]);
        foreach ($files[$langcode][$major] as $item) {
          $major_cell[] = l(
            $item->title,
            // @todo: bad, bad practice to hardcode the path format here.
            $download_url .'/'. $major .'.x/'. $project->uri .'/'. $item->filename,
            array('attributes' =>
              array('title' =>
                t('Generated: @time, Size: @size',
                  array('@time' => format_date($item->timestamp, 'custom', 'r', 0), '@size' => format_size($item->filesize))
                )
              )
            )
          );
        }
      }
      $row[] = join(', ', $major_cell);
    }
    $table[] = $row;
  }

  $header = array(t('Languages'));
  foreach (array_keys($majors) as $major) {
    $header[] = $major .'.x';
  }

  return theme('table', $header, $table);
}