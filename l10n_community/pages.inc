<?php
// $Id$

/**
 * @file
 *   Overview and translation editing pages for localization community.
 */

/**
 * Translation status overview for all languages.
 */
function l10n_community_overview_languages() {
  
  // Checking whether we have languages and strings.
  if (!$languages = l10n_community_get_languages('name')) {
    drupal_set_message(t('No languages to list.'), 'error');
    return '';
  }
  if (!$num_source = l10n_community_get_string_count('all')) {
    drupal_set_message(t('No strings to translate.'), 'error');
    return '';
  }
  
  // Generate listing of all languages with summaries.
  $groups = l10n_community_get_groups();
  $table = array();
  $string_counts = l10n_community_get_string_count('languages');
  foreach ($languages as $langcode => $language) {
    $perm = l10n_community_get_permission($langcode);
    $group = isset($groups[$langcode]) ? l(t('Group'), 'node/'. $groups[$langcode]->nid, array('title' => t('Go to the group homepage.'))) : t('n/a');
    $table[] = array_merge(
      array(
        array('data' => "<strong>$language</strong> ($langcode)", 'class' => 'language'),
        l(t('Projects'), 'translate/languages/'. $langcode, array('title' => t('Go to the project list.'))),
        ($perm == L10N_PERM_NONE ? t('n/a') : l(t('Import'), 'translate/languages/'. $langcode . '/import')),
        $group,
      ),
      isset($string_counts[$langcode]) ? theme('l10n_progress_columns', $num_source, $string_counts[$langcode][0], $string_counts[$langcode][1]) : theme('l10n_progress_columns', $num_source, 0, 0)
    );
  }
  return theme(
    'table',
    array(
      array('data' => t('Language'), 'class' => 'language'),
      t('Projects'),
      t('Import'),
      t('Group'),
      t('Overall status'),
      t('Translated'),
      t('Suggested'),
      t('Untranslated')
    ),
    $table,
    array('id' => 'l10n-community-overview')
  );
}

/**
 * Translation status overview for all projects.
 *
 * Because projects are much more numerous then languages, we need pager on this page.
 */
function l10n_community_overview_projects() {
  $output = '';
  if ($projects = l10n_community_get_projects(NULL, 10)) {
    $output = theme("pager", NULL, 10, 0);
    $table = array();
    $string_counts = l10n_community_get_string_count('projects');
    foreach ($projects as $uri => $project) {
      if (isset($string_counts[$project->pid])) {
        list($sum, $translated, $suggested) = $string_counts[$project->pid];
        $table[] = array_merge(
          array(
            l($project->title, 'translate/projects/'. $uri), 
            l(t('Export'), 'translate/projects/'. $uri . '/export'),
          ), 
          theme('l10n_progress_columns', $sum, $translated, $suggested)
        );
      }
    }
    $output .= theme('table', array(t('Project'), t('Export template'), t('Overall status'), t('Translated'), t('Suggested'), t('Untranslated')), $table);
    $output .= theme("pager", NULL, 10, 0);
  }
  else {
    drupal_set_message(t('No projects found.'), 'error');
  }
  return $output;
}

/**
 * Translation status page of all projects from a given language.
 *
 * @param $langcode
 *   Language code, for example 'hu', 'pt-br', 'de', 'it'.
 */
function l10n_community_status_projects($langcode = NULL) {
  if (!isset($langcode)) {
    drupal_set_message(t('No language selected.'), 'error');
    return '';
  }
  $perm = l10n_community_get_permission($langcode);
  
  drupal_set_title(t('@language projects overview', array('@language' => drupal_get_title())));
  
  if ($projects = l10n_community_get_projects(NULL, 10)) {
    $output = theme("pager", NULL, 10, 0);
    $table = array();
    $string_counts = l10n_community_get_string_count('projects', $langcode);
    foreach ($projects as $uri => $project) {
      if (isset($string_counts[$project->pid])) {
      	list($sum, $translated, $suggested) = $string_counts[$project->pid];
        $table[] = array_merge(
          array(
            l($project->title, 'translate/languages/'. $langcode .'/' . $uri),
            ($perm != L10N_PERM_NONE ? l(t('Import'), 'translate/languages/'. $langcode . '/'. $uri .'/import') : t('n/a')),
            l(t('Export'), 'translate/languages/'. $langcode . '/'. $uri .'/export')
          ),
          theme('l10n_progress_columns', $sum, $translated, $suggested)
        );
      }
    }
    $output .= theme('table', array(t('Project'), t('Import'), t('Export'), t('Overall status'), t('Translated'), t('Suggested'), t('Untranslated')), $table);
    $output .= theme("pager", NULL, 10, 0);
  }
  return $output;
}

/**
 * Translation status page of all languages for a given project.
 *
 * @param $uri
 *   Project URI.
 */
function l10n_community_status_languages($uri = NULL) {
  if (!isset($uri)) {
    drupal_set_message(t('No project selected.'), 'error');
    return '';
  }
  drupal_set_title(t('@project languages overview', array('@project' => drupal_get_title())));
  
  if (!$languages = l10n_community_get_languages('name')) {
    drupal_set_message(t('No languages to list.'), 'error');
    return '';
  }

  $project = l10n_community_get_projects($uri);
  $num_source = l10n_community_get_string_count('project', $project->pid);
  $string_counts = l10n_community_get_string_count('languages', $project->pid);
  
  $table = array();
  foreach ($languages as $langcode => $language) {
    $perm = l10n_community_get_permission($langcode);
    $table[] = array_merge(
      array(
        array('data' => l("<strong>$language</strong> ($langcode)", 'translate/projects/'. $uri . '/'. $langcode, array(), NULL, NULL, FALSE, TRUE), 'class' => 'language'),
        ($perm != L10N_PERM_NONE) ? l(t('Import'), 'translate/projects/'. $uri . '/'. $langcode .'/import') : t('n/a'),
        l(t('Export'), 'translate/projects/'. $uri . '/'. $langcode .'/export'),
      ),
      theme('l10n_progress_columns', $num_source, $string_counts[$langcode][0], $string_counts[$langcode][1])
    );
  }

  return theme(
    'table',
    array(
      array('data' => t('Language'), 'class' => 'language'),
      t('Import'),
      t('Export'),
      t('Overall status'),
      t('Translated'),
      t('Suggested'),
      t('Untranslated')
    ),
    $table,
    array('id' => 'l10n-community-overview')
  );
}

// = Translation web interface =================================================

function l10n_community_translate_page($langcode = NULL, $uri = NULL) {
  
  // Provide accurate page title, depending on permission. At this point, the
  // user already selected a language, and the project is the most important
  // information, so we only format that as a placeholder.
  $project = l10n_community_get_projects($uri);
  $languages = l10n_community_get_languages();
  $perm = l10n_community_get_permission($langcode);
  if ($perm == L10N_PERM_NONE) {
    drupal_set_title(t('@language %project translations', array('%project' => $project->title, '@language' => $languages[$langcode]->name)));
  }
  else {
    drupal_set_title(t('Translate %project to @language', array('%project' => $project->title, '@language' => $languages[$langcode]->name)));
  }

  drupal_add_js(drupal_get_path('module', 'l10n_community') .'/l10n_community.js');

  // Build values for the form generation.
  list ($status, $release, $search) = l10n_community_filter_build();
  $output = drupal_get_form('l10n_community_filter_form', $uri, $status, $release, $search);
  // Retrieve values stored from the form changes.
  list ($status, $release, $search) = l10n_community_filter_build();
  
  $strings = l10n_community_get_strings($project, $languages[$langcode]->locale, $status, $release, $search, 10);
  if (!count($strings)) {
    drupal_set_message(t('No strings found with this filter. Try adjusting the filter options.'));
  }
  elseif ($perm != L10N_PERM_NONE) {
    $output .= drupal_get_form('l10n_community_translate_form', $strings, $languages[$langcode], $uri, $perm);
  }
  else {
    $output .= drupal_get_form('l10n_community_translate_view', $strings, $languages[$langcode], $uri);
  }
  return $output;
}

/**
 * Translate form filter.
 */
function l10n_community_filter_form($uri, $status = L10N_STATUS_ALL, $release = 'all', $search  = '') {
  $status_options = array(
    L10N_STATUS_ALL => t('All'),
    L10N_STATUS_UNTRANSLATED => t('Untranslated'),
    L10N_STATUS_HAS_SUGGESTION => t('Has suggestions'),
    L10N_STATUS_TRANSLATED => t('Translated'),
  );
  $form['filters'] = array(
    '#title' => t('Display settings'),
    '#type' => 'fieldset',
    '#prefix' => '<div id="l10n-community-filter" class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['filters']['status'] = array(
    '#title' => t('Status'),
    '#type' => 'select',
    '#options' => $status_options,
    '#default_value' => $status,
  );
  $releases = l10n_community_get_releases($uri);
  $release_options = array('all' => t('All'));
  foreach ($releases as $rid => $this_release) {
    $release_options[$rid] = $this_release->title;
  }
  $form['filters']['release'] = array(
    '#title' => t('Release'),
    '#type' => 'select',
    '#options' => $release_options,
    '#default_value' => isset($release) ? $release : 'all',
  );
  $form['filters']['search'] = array(
    '#title' => t('Search'),
    '#type' => 'textfield',
    '#default_value' => $search,
  );
  $form['filters']['submit'] = array(
    '#value' => t('Filter'),
    '#type' => 'submit',
  );
  return $form;
}

/**
 * Submission handler for filtering form.
 */
function l10n_community_filter_form_submit($form_id, &$form) {
  // Remember filters for later use, so translators don't need to
  // reenter them, pagers keep their value and so on. Note: these will
  // be 'personal', ie. not included in website links.
  $_SESSION['l10n_community_filter'] = array(
    'status'  => intval($form['status']),
    'release' => is_numeric($form['release']) ? $form['release'] : NULL,
    'search'  => $form['search'],
  );
  // This redirects back to the first translation page, by design.
}

/**
 * Builds a list of 'status', 'release' and 'search' values, as remembered from the request.
 */
function l10n_community_filter_build() {
  return array(
    isset($_SESSION['l10n_community_filter']['status']) ? intval($_SESSION['l10n_community_filter']['status']) : L10N_STATUS_ALL,
    isset($_SESSION['l10n_community_filter']['release']) ? $_SESSION['l10n_community_filter']['release'] : NULL,
    isset($_SESSION['l10n_community_filter']['search']) ? $_SESSION['l10n_community_filter']['search'] : '',
  );
}

/**
 * Translation web interface.
 *
 * @param $strings
 *   Array of strings to display.
 * @param $language
 *   Language object.
 * @param $uri
 *   Project URI.
 * @param $perm
 *   Community permission level of user watching the page.
 */
function l10n_community_translate_form($strings = array(), $language = NULL, $uri = NULL, $perm = L10N_PERM_SUGGEST) {
  $form = $js_strings = array();
  
  $form['#tree'] = TRUE;
  $form['pager'] = array(
    '#value' => theme('pager', NULL, 10, 0)
  );
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $language->locale
  );
  $form['project'] = array(
    '#type' => 'value',
    '#value' => $uri
  );

  foreach ($strings as $id => $string) {
    $form[$id] = array(
      '#type' => 'fieldset',
      '#attributes' => array('id' => 'l10n-community-fields-'. $string->sid),
    );
    $form[$id]['sid'] = array(
      '#type' => 'value',
      '#value' => $string->sid
    );
    // Include sid as hidden field for JavaScript to call us back with it.
    $form[$id]['sid_callback'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => 'l10n-community-sid-callback'),
      '#value' => url('translate/details/'. $string->sid .'/'. $language->locale .'/'. $id)
    );
    $form[$id]['toolbox'] = array(
      '#type' => 'markup',
      '#value' => '<div class="toolbox" id="l10n-community-toolbox-'. $id .'">' . ($string->has_suggestion ? '<span class="suggestions"></span>' : '') .'</div>'
    );

    $is_plural = strpos($string->value, "\0");
    if ($is_plural) {
      // Multiple source strings if we deal with plurals.
      $parts = explode(chr(0), $string->value);
      $string_parts = array();
      foreach ($parts as $i => $part) {
        $target = $id .'-'. $i;
        $js_strings[$target] = $part;
        $string_parts[] = theme('l10n_community_copy_button', $target) .' '. l10n_community_format_text(check_plain($part));
      }
      $source = theme('item_list', $string_parts);
    }
    else {
      // Single source string otherwise.
      $source = theme('l10n_community_copy_button', $id) .' '. l10n_community_format_text(check_plain($string->value));
      $js_strings[$id] = $string->value;
    }

    $form[$id]['source'] = array(
      '#prefix' => '<div>',
      '#type' => 'markup',
      '#value' => $source,
      '#suffix' => '</div>',
    );
    
    $translated = !empty($string->translation);
    $form[$id]['new_translation'] = array(
      '#type' => 'item',
      // Hide textareas of translated stuff to save some space and guide user eyes.
      '#prefix' => '<div class="'. ($translated ? 'hidden ' : '') .'l10n-community-wrapper-'. $id .'">',
      '#suffix' => '</div>',
    );

    if ($is_plural) {
      $translations = ($translated ? explode("\0", $string->translation) : array_fill(0, $language->plurals, ''));
      if ($translated) {
        $string_parts = array();
        for($i = 0; $i < $language->plurals; $i++) {
          $target = $id .'-'. $i;
          $string_parts[] = theme('l10n_community_copy_button', $target .'-t') .' '. l10n_community_format_text(check_plain($translations[$i]));
          $js_strings[$target .'-t'] = $translations[$i];
        }
        $form[$id]['translation'] = array(
          '#prefix' => '<div class="translation">',
          '#type' => 'markup',
          '#value' => theme('item_list', $string_parts),
          '#suffix' => '</div>',
        );
      }
      
      for($i = 0; $i < $language->plurals; $i++) {
        
        $target = $id .'-'. $i;
        if ($translated) {
          $title = ($perm == L10N_PERM_SUGGEST) ? t('New suggestion for variant #%d', array('%d' => $i)) : t('New translation for variant #%d', array('%d' => $i));
        }
        else {
          $title = ($perm == L10N_PERM_SUGGEST) ? t('Suggestion for variant #%d', array('%d' => $i)) : t('Translation for variant #%d', array('%d' => $i));
        }
        
        $form[$id]['new_translation']['value'][$i] = array(
          '#type' => 'textarea',
          '#title' => $title,
          '#rows' => 1,
          '#id' => 'l10n-community-new-translation-'. $target,
        );
      }
    }
    else {
      if ($translated) {
        $form[$id]['translation'] = array(
          '#prefix' => '<div class="translation">',
          '#type' => 'markup',
          '#value' => theme('l10n_community_copy_button', $id .'-t') .' '. l10n_community_format_text(check_plain($string->translation)),
          '#suffix' => '</div>',
        );
        $js_strings[$id .'-t'] = $string->translation;
      }
      $form[$id]['new_translation']['value'] = array(
        // Hide textareas of translated stuff to save some space and guide user eyes.
        '#type' => 'textarea',
        '#title' => $translated ? (($perm == L10N_PERM_SUGGEST) ? t('New suggestion') : t('New translation')) : (($perm == L10N_PERM_SUGGEST) ? t('Suggestion') : t('Translation')),
        '#rows' => 1,
        '#id' => 'l10n-community-new-translation-'. $id,
      );
    }
    
    if ($perm == L10N_PERM_SUGGEST) {
      // User with suggestion capability only.
      $form[$id]['new_translation']['is_suggestion'] = array(
        '#type' => 'value',
        '#value' => TRUE
      );
    }
    else {
      // User with full privileges.
      $form[$id]['new_translation']['is_suggestion'] = array(
        '#title' => t('Store as suggestion'),
        '#type' => 'checkbox',
        '#description' => t('Check if the translation you provided needs discussion.'),
      );
    }
  }
  
  // Keep being on the same page when the form is submitted.
  $form['destination'] = array(
    '#type' => 'hidden',
    '#value' => $_GET['q'] . (isset($_GET['page']) ? '?page='. $_GET['page'] : ''),
  );
  
  
  drupal_add_js(
    array(
      'l10n_strings' => $js_strings,
      'l10n_image_path' => drupal_get_path('module', 'l10n_community') . '/images/',
      'l10n_expand_help' => t('Show the translation editing field.'),
      'l10n_lookup_help' => t('Show detailed information about this string.'),
    ),
    'setting'
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save translations')
  );
  return $form;
}

/**
 * Form for translation *viewing* (for those without privileges).
 */
function l10n_community_translate_view($strings = array(), $language = NULL, $uri = NULL) {
  $form['#tree'] = TRUE;
  $form['pager'] = array(
    '#value' => theme('pager', NULL, 10, 0)
  );
  foreach ($strings as $id => $string) {
    $form[$id] = array(
      '#type' => 'fieldset',
    );
    $is_plural = strpos($string->value, "\0");
    if ($is_plural) {
      // Multiple source strings if we deal with plurals.
      $source = theme('item_list', array_map('l10n_community_format_text', array_map('check_plain', explode(chr(0), $string->value))));
    }
    else {
      // Single source string otherwise.
      $source = l10n_community_format_text(check_plain($string->value));
    }
    $form[$id]['source'] = array(
      '#type' => 'markup',
      '#value' => $source,
    );
    if (!empty($string->translation)) {
      if ($is_plural) {
        $translations = explode("\0", $string->translation);
        for($i = 0; $i < $language->plurals; $i++) {
          $form[$id]['translation'][$i] = array(
            '#type' => 'markup',
            '#value' => '<br />'. $translations[$i],
          );
        }
      }
      else {
        $form[$id]['translation'] = array(
          '#type' => 'markup',
          '#value' => !empty($string->translation) ? '<br />'. $string->translation : '',
        );
      }
    }
  }
  return $form;
}

/**
 * Format translatable strings with custom icons for greater
 * visibility of some items. Newlines and replacement strings
 * are made more visible.
 *
 * @param $string
 *   Source strings to translate.
 */
function l10n_community_format_text($string) {
  $path = base_path() . drupal_get_path('module', 'l10n_community');
  $string = str_replace(array("\n", "\\\\n"), '<img src="'. $path .'/images/newline.png" alt="newline" /><br />', $string);
  $string = preg_replace('~((%|!|@)[0-9a-zA-Z_-]+)~', '<em class="l10n-community-marker">\\1</em>', $string);
  return $string;
}

/**
 * Save translations entered in the web form.
 */
function l10n_community_translate_form_submit($form_id, &$form) {
  global $user;

  $inserted = $updated = $unchanged = $suggested = 0;
  foreach ($form as $item => $value) {
    if (!is_array($form[$item]) || !isset($form[$item]['new_translation'])) {
      // Skip, if we don't have translations in this form item.
      continue;
    }
    
    $text = '';
    if (is_string($form[$item]['new_translation']['value'])) {
      // Single string representation: simple translation.
      $text = $form[$item]['new_translation']['value'];
    }
    if (is_array($form[$item]['new_translation']['value'])) {
      // Array -> plural variants are provided. Join them with a NULL separator.
      $text = join("\0", $form[$item]['new_translation']['value']);
      if (trim($text) == '') {
        // If the whole string only contains NULL bytes, empty the string, so
        // we don't save an empty translation. Otherwise the NULL bytes need
        // to be there, so we know plural variant indices.
        $text = '';
      }
    }
      
    if ($text) {
      // We have some string to save.
      l10n_community_target_save($form[$item]['sid'], $text, $form['langcode'], $user->uid, ($form[$item]['new_translation']['is_suggestion'] == TRUE), $inserted, $updated, $unchanged, $suggested);
    }
  }
  l10n_community_update_message($inserted, $updated, $unchanged, $suggested);
}

// = Theme functions ===========================================================

/**
 * Progress bar display for translation status information.
 *
 * @param $numbers
 *   An array with: (number of strings for translation, translated ones, suggestions).
 */
function theme_l10n_progress_columns($sum, $translated, $suggested) {
  // Compute numbers, percentages and provide alternate text titles.
  $status = array(
    'translated' => array((int) $translated, round($translated / $sum * 100, 2), t('!percent translated')),
    'suggested' => array((int) $suggested,  round($suggested / $sum * 100, 2),  t('!percent suggested')),
    'untranslated' => array($sum - $translated - $suggested, 0, t('!percent untranslated')),
  );
  $status['untranslated'][1] = 100 - $status['suggested'][1] - $status['translated'][1];
  
  // Visual summary with a progress bar.
  $bar = '<div class="l10n-community-progress">';
  foreach ($status as $key => $values) {
    if ($values[1] > 0) {
      $bar .= '<div class="l10n-community-progress-'. $key . '" style="width:'. $values[1] .'%;" title="'. strtr($values[2], array('!percent' => $values[1] .'%')) .'"></div>';
    }
  }
  $bar .= '</div>';
  
  return array($bar, $status['translated'][0] .' ('. $status['translated'][1] .'%)', $status['suggested'][0], $status['untranslated'][0]);
}

/**
 * Copy button for string values.
 */
function theme_l10n_community_copy_button($source_id = 0) {
  $title = t('Copy value to edit field.');
  return ' <span title="'. $title .'" id="l10n-community-copy-'. $source_id .'" class="l10n-community-copy"></span>';
}
