<?php
// $Id$

/**
 * @file
 *   Overview and translation editing pages for localization community.
 */

// = Overview screens ==========================================================

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
  
  // Generate listing of all languages with summaries. The list of languages
  // is relatively "short", compared to projects, so we don't need a pager here.
  $groups = array();
  if (module_exists('l10n_groups')) {
    $groups = l10n_groups_get_groups();
  }
  $table = array();
  $string_counts = l10n_community_get_string_count('languages');
  foreach ($languages as $langcode => $language) {
    $perm = l10n_community_get_permission($langcode);
    $table[] = array_merge(
      array(
        array('data' => "<strong>$language</strong> ($langcode)", 'class' => 'rowhead'),
        l(t('Projects'), 'translate/languages/'. $langcode, array('title' => t('Go to the project list.'))),
        ($perm == L10N_PERM_NONE ? t('n/a') : l(t('Import'), 'translate/languages/'. $langcode . '/import')),
      ),
      count($groups) ? (isset($groups[$langcode]) ? array(l(t('Group'), 'node/'. $groups[$langcode]->nid, array('title' => t('Go to the group homepage.')))) : array(t('n/a'))) : array(),
      isset($string_counts[$langcode]) ? theme('l10n_community_progress_columns', $num_source, $string_counts[$langcode][0], $string_counts[$langcode][1]) : theme('l10n_community_progress_columns', $num_source, 0, 0)
    );
  }
  $header = array(
    array('data' => t('Language'), 'class' => 'rowhead'),
    t('Projects'),
    t('Import'),
    t('Group'),
    t('Overall status'),
    t('Translated'),
    t('Has suggestion'),
    t('Untranslated')
  );
  if (!count($groups)) {
    unset($header[3]);
  }

  return theme('table', $header, $table, array('id' => 'l10n-community-overview'));
}

/**
 * Translation status overview for all projects.
 *
 * Because projects are much more numerous then languages, we need
 * a pager on this screen.
 */
function l10n_community_overview_projects() {
  $output = '';
  if (!$projects = l10n_community_get_projects(NULL, 10)) {
    drupal_set_message(t('No projects found.'), 'error');
    return '';
  }

  $output = theme('pager', NULL, 10, 0);
  $table = array();
  $string_counts = l10n_community_get_string_count('projects');
  foreach ($projects as $uri => $project) {
    if (isset($string_counts[$project->pid])) {
      list($sum, $translated, $suggested) = $string_counts[$project->pid];
      $table[] = array_merge(
        array(
          array('data' => $project->title, 'class' => 'rowhead'), 
          l(t('Languages'), 'translate/projects/'. $uri), 
          l(t('Export'), 'translate/projects/'. $uri .'/export'),
        ), 
        theme('l10n_community_progress_columns', $sum, $translated, $suggested)
      );
    }
  }
  $output .= theme(
    'table',
    array(
      array('data' => t('Project'), 'class' => 'rowhead'),
      t('Languages'),
      t('Export template'),
      t('Overall status'),
      t('Translated'),
      t('Has suggestion'),
      t('Untranslated')
    ),
    $table,
    array('id' => 'l10n-community-overview')
  );
  $output .= theme('pager', NULL, 10, 0);
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
  
  drupal_set_title(t('@language projects overview', array('@language' => drupal_get_title())));
  
  if (!$projects = l10n_community_get_projects(NULL, 10)) {
    drupal_set_message(t('No projects found.'), 'error');
  }

  $perm = l10n_community_get_permission($langcode);
  $output = theme('pager', NULL, 10, 0);
  $table = array();
  $string_counts = l10n_community_get_string_count('projects', $langcode);
  foreach ($projects as $uri => $project) {
    if (isset($string_counts[$project->pid])) {
      list($sum, $translated, $suggested) = $string_counts[$project->pid];
      $table[] = array_merge(
        array(
          array('data' => l($project->title, 'translate/languages/'. $langcode .'/' . $uri), 'class' => 'rowhead'),
          ($perm != L10N_PERM_NONE ? l(t('Import'), 'translate/languages/'. $langcode . '/'. $uri .'/import') : t('n/a')),
          l(t('Export'), 'translate/languages/'. $langcode . '/'. $uri .'/export')
        ),
        theme('l10n_community_progress_columns', $sum, $translated, $suggested)
      );
    }
  }
  $output .= theme(
    'table',
    array(
      array('data' => t('Project'), 'class' => 'rowhead'),
      t('Import'),
      t('Export'),
      t('Overall status'),
      t('Translated'),
      t('Has suggestion'),
      t('Untranslated')
    ),
    $table,
    array('id' => 'l10n-community-overview')
  );
  $output .= theme('pager', NULL, 10, 0);
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
        array('data' => l("<strong>$language</strong> ($langcode)", 'translate/projects/'. $uri . '/'. $langcode, array(), NULL, NULL, FALSE, TRUE), 'class' => 'rowhead'),
        ($perm != L10N_PERM_NONE) ? l(t('Import'), 'translate/projects/'. $uri . '/'. $langcode .'/import') : t('n/a'),
        l(t('Export'), 'translate/projects/'. $uri . '/'. $langcode .'/export'),
      ),
      theme('l10n_community_progress_columns', $num_source, $string_counts[$langcode][0], $string_counts[$langcode][1])
    );
  }

  return theme(
    'table',
    array(
      array('data' => t('Language'), 'class' => 'rowhead'),
      t('Import'),
      t('Export'),
      t('Overall status'),
      t('Translated'),
      t('Has suggestions'),
      t('Untranslated')
    ),
    $table,
    array('id' => 'l10n-community-overview')
  );
}

// = Translation interface hub =================================================

/**
 * Menu callback for the translation pages.
 *
 * Displays a translation view or translation edit page depending
 * on permissions. If no strings are found, an error is printed.
 *
 * @param $langcode
 *   Language code, for example 'hu', 'pt-br', 'de', 'it'.
 * @param $uri
 *   Project URI.
 */
function l10n_community_translate_page($langcode = NULL, $uri = NULL) {
  
  $project = l10n_community_get_projects($uri);
  $languages = l10n_community_get_languages();
  $perm = l10n_community_get_permission($langcode);

  // Build filter values for the form generation.
  list ($status, $release, $search) = l10n_community_filter_build();
  $output = drupal_get_form('l10n_community_filter_form', $uri, $status, $release, $search);
  // Retrieve values stored from the form changes (in case of submit).
  list ($status, $release, $search) = l10n_community_filter_build();
  
  $strings = l10n_community_get_strings($project, $languages[$langcode]->locale, $status, $release, $search, 10);
  if (!count($strings)) {
    drupal_set_message(t('No strings found with this filter. Try adjusting the filter options.'), 'error');
  }
  elseif ($perm == L10N_PERM_NONE) {
    // For users without permission to translate or suggest, display the view.
    drupal_set_title(t('@language %project translations', array('%project' => $project->title, '@language' => $languages[$langcode]->name)));
    $output .= drupal_get_form('l10n_community_translate_view', $strings, $languages[$langcode], $uri);
  }
  else {
    // For users with some permission, display the form.
    drupal_add_js(drupal_get_path('module', 'l10n_community') .'/l10n_community.js');
    drupal_set_title(t('Translate %project to @language', array('%project' => $project->title, '@language' => $languages[$langcode]->name)));
    $output .= drupal_get_form('l10n_community_translate_form', $strings, $languages[$langcode], $uri, $perm);
  }
  return $output;
}

// = Filter form handling ======================================================

/**
 * Translate form filter.
 */
function l10n_community_filter_form($uri, $status = L10N_STATUS_ALL, $release = 'all', $search  = '') {
  $status_options = array(
    L10N_STATUS_ALL            => t('All'),
    L10N_STATUS_UNTRANSLATED   => t('Untranslated'),
    L10N_STATUS_HAS_SUGGESTION => t('Has suggestion'),
    L10N_STATUS_TRANSLATED     => t('Translated'),
  );
  $form['filters'] = array(
    '#title' => t('Display settings'),
    '#type' => 'fieldset',
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
 *
 * @todo
 *   Think about possibilities to permalink with specified filters.
 */
function l10n_community_filter_form_submit($form_id, &$form) {
  // Remember filters for later use, so translators don't need to
  // reenter them, pagers keep their value and so on.
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

// = Translation viewer ========================================================

/**
 * Form for translation *viewing* (for those without privileges).
 *
 * @param $strings
 *   Array of string objects to display on the page.
 * @param $language
 *   Language object corresponding to the page displayed.
 * @param $uri
 *   Project URI.
 */
function l10n_community_translate_view($strings = array(), $language = NULL, $uri = NULL) {
  $pager = theme('pager', NULL, 10, 0);
  
  $form = array(
    '#tree' => TRUE
  );
  $form['pager_top'] = array(
    '#value' => $pager,
  );
  foreach ($strings as $string) {
    // Include a fieldset for each source string. Although sid is not globally
    // unique, it is unique if we already limit to one language, which
    // fits here. So we can use that for form item identification.
    $form[$string->sid] = array(
      '#type' => 'fieldset',
    );
    $is_plural = strpos($string->value, "\0");
    if ($is_plural) {
      // Multiple source strings if we deal with plurals.
      $source = theme('item_list', array_map('l10n_community_format_text', explode(chr(0), $string->value)), '');
    }
    else {
      // Single source string otherwise.
      $source = l10n_community_format_text($string->value);
    }
    $form[$string->sid]['source'] = array(
      '#type' => 'markup',
      '#value' => $source,
    );
    if (!empty($string->translation)) {
      if ($is_plural) {
        $translations = explode(chr(0), $string->translation);
        // Fill in any missing items, so it is shown that not all items are done.
        if (count($translations) < $language->plurals) {
          $translations = array_merge($translations, array_fill(0, count($translations) - $language->plurals, '')); 
        }
        $translation = theme('item_list', $translations);
      }
      else {
        $translation = $string->translation;
      }
      $form[$string->sid]['translation'] = array(
        '#type' => 'markup',
        '#value' => '<div class="translation">'. $translation .'</div>',
      );
    }
  }
  $form['pager_bottom'] = array(
    '#value' => $pager,
  );
  return $form;
}

// = Translation editor ========================================================

/**
 * Translation web interface.
 *
 * @param $strings
 *   Array of string objects to display.
 * @param $language
 *   Language object.
 * @param $uri
 *   Project URI.
 * @param $perm
 *   Community permission level of user watching the page.
 */
function l10n_community_translate_form($strings = array(), $language = NULL, $uri = NULL, $perm = L10N_PERM_SUGGEST) {
  
  // We are collecting a list of strings to make copy-pasteable in JS.
  // These need their own identifiers, so we need to collect them.
  $js_strings = array();
  
  $form = array(
    '#tree' => TRUE
  );
  $form['pager'] = array(
    '#value' => theme('pager', NULL, 10, 0)
  );
  // Keep language code and URI in form for further reference.
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $language->locale
  );
  $form['project'] = array(
    '#type' => 'value',
    '#value' => $uri
  );

  foreach ($strings as $string) {
    $form[$string->sid] = array(
      '#type' => 'fieldset',
      '#attributes' => array('id' => 'l10n-community-editor-'. $string->sid),
    );

    // A toolbox which displays action icons on each string editor fieldset.
    $form[$string->sid]['toolbox'] = array(
      '#type' => 'markup',
      '#value' => '<div class="toolbox">' . ($string->has_suggestion ? '<span class="has-suggestion"></span>' : '') .'</div>'
    );

    $is_plural = strpos($string->value, "\0");
    if ($is_plural) {
      // Multiple source strings if we deal with plurals. The form item and
      // consequently the JavaScript strings identifiers are the sid and then
      // the index of the plural being displayed.
      $parts = explode(chr(0), $string->value);
      $string_parts = array();
      foreach ($parts as $i => $part) {
        $target = $string->sid .'-'. $i;
        $js_strings[$target] = $part;
        $string_parts[] = theme('l10n_community_copy_button', $target) .' '. l10n_community_format_text($part);
      }
      $source = theme('item_list', $string_parts);
    }
    else {
      // Single source string. We use the sid for the form identifier.
      $source = theme('l10n_community_copy_button', $string->sid) .' '. l10n_community_format_text($string->value);
      $js_strings[$string->sid] = $string->value;
    }

    $form[$string->sid]['source'] = array(
      '#prefix' => '<div>',
      '#type' => 'markup',
      '#value' => $source,
      '#suffix' => '</div>',
    );
    
    $translated = !empty($string->translation);
    $form[$string->sid]['translation'] = array(
      '#type' => 'item',
      // Hide editing controls of translated stuff to save some space and guide user eyes.
      '#prefix' => '<div id="l10n-community-wrapper-'. $string->sid .'"'. ($translated ? ' class="hidden"' : '') .'">',
      '#suffix' => '</div>',
    );

    if ($is_plural) {
      
      // Dealing with a string with plural versions.
      if ($translated) {
        // Add translation form element with all plural versions.
        $translations = explode("\0", $string->translation);
        $string_parts = array();
        for($i = 0; $i < $language->plurals; $i++) {
          $target = $string->sid .'-'. $i;
          $string_parts[] = theme('l10n_community_copy_button', $target .'-t') .' '. l10n_community_format_text($translations[$i]);
          $js_strings[$target .'-t'] = $translations[$i];
        }
        $form[$string->sid]['translation_existing'] = array(
          '#prefix' => '<div class="translation">',
          '#type' => 'markup',
          '#value' => theme('item_list', $string_parts),
          '#suffix' => '</div>',
        );
      }
      
      for($i = 0; $i < $language->plurals; $i++) {
        $target = $string->sid .'-'. $i;
        if ($translated) {
          // Already translated so we ask for new translation or suggestion.
          $title = ($perm == L10N_PERM_SUGGEST) ? t('New suggestion for variant #%d', array('%d' => $i)) : t('New translation for variant #%d', array('%d' => $i));
        }
        else {
          // Not translated yet, so we ask for initial translation or suggestion.
          $title = ($perm == L10N_PERM_SUGGEST) ? t('Suggestion for variant #%d', array('%d' => $i)) : t('Translation for variant #%d', array('%d' => $i));
        }
        
        // Include a textarea for each plural variant.
        $form[$string->sid]['translation']['value'][$i] = array(
          '#type' => 'textarea',
          '#title' => $title,
          '#rows' => 1,
          '#id' => 'l10n-community-translation-'. $target,
        );
      }
    }
    
    else {
      // Dealing with a simple string (no plurals).
      
      if ($translated) {
        $form[$string->sid]['translation_existing'] = array(
          '#prefix' => '<div class="translation">',
          '#type' => 'markup',
          '#value' => theme('l10n_community_copy_button', $string->sid .'-t') .' '. l10n_community_format_text($string->translation),
          '#suffix' => '</div>',
        );
        $js_strings[$string->sid .'-t'] = $string->translation;
      }
      $form[$string->sid]['translation']['value'] = array(
        '#type' => 'textarea',
        // Provide accurate title based on previous data and permission.
        '#title' => $translated ? (($perm == L10N_PERM_SUGGEST) ? t('New suggestion') : t('New translation')) : (($perm == L10N_PERM_SUGGEST) ? t('Suggestion') : t('Translation')),
        '#rows' => 1,
        '#id' => 'l10n-community-translation-'. $string->sid,
      );
    }
    
    if ($perm == L10N_PERM_SUGGEST) {
      // User with suggestion capability only, record this.
      $form[$string->sid]['translation']['is_suggestion'] = array(
        '#type' => 'value',
        '#value' => TRUE
      );
    }
    else {
      // User with full privileges, offer option to submit suggestion.
      $form[$string->sid]['translation']['is_suggestion'] = array(
        '#title' => t('Store as suggestion'),
        '#type' => 'checkbox',
        '#description' => t('Check if the translation you provided needs discussion.'),
      );
    }
  }
  
  // Keep being on the same pager page when the form is submitted.
  $form['destination'] = array(
    '#type' => 'hidden',
    '#value' => $_GET['q'] . (isset($_GET['page']) ? '?page='. $_GET['page'] : ''),
  );
  
  // Add all strings for copy-pasting and some helpers.
  drupal_add_js(
    array(
      'l10n_strings'          => $js_strings,

      'l10n_image_path'       => base_path() . drupal_get_path('module', 'l10n_community') . '/images/',

      'l10n_expand_help'      => t('Show the translation editing field.'),
      'l10n_lookup_help'      => t('Show detailed information about this string.'),
      'l10n_approve_error'    => t('There was an error approving this suggestion. You might not have permission or the suggestion id was invalid.'),
      'l10n_approve_confirm'  => t('Suggestion approved.'),

      'l10n_details_callback' => url('translate/details/'. $language->locale .'/'),
      'l10n_approve_callback' => url('translate/approve/'),
    ),
    'setting'
  );
  
  // Let the user submit the form.
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => ($perm == L10N_PERM_SUGGEST) ? t('Save suggestions') : t('Save translations')
  );
  return $form;
}

/**
 * Save translations entered in the web form.
 */
function l10n_community_translate_form_submit($form_id, &$form) {
  global $user;

  $inserted = $updated = $unchanged = $suggested = 0;
  foreach ($form as $sid => $item) {
    if (!is_array($item) || !isset($item['translation'])) {
      // Skip, if we don't have translations in this form item,
      // which means this is some other form value.
      continue;
    }
    
    $text = '';
    if (is_string($item['translation']['value'])) {
      // Single string representation: simple translation.
      $text = $item['translation']['value'];
    }
    if (is_array($item['translation']['value'])) {
      // Array -> plural variants are provided. Join them with a NULL separator.
      $text = join("\0", $item['translation']['value']);
      if (trim($text) == '') {
        // If the whole string only contains NULL bytes, empty the string, so
        // we don't save an empty translation. Otherwise the NULL bytes need
        // to be there, so we know plural variant indices.
        $text = '';
      }
    }
      
    if ($text) {
      // We have some string to save.
      l10n_community_target_save(
        $sid, $text, $form['langcode'], $user->uid,
        ($item['translation']['is_suggestion'] == TRUE),
        $inserted, $updated, $unchanged, $suggested
      );
    }
  }
  
  // Inform user about changes made to the database.
  l10n_community_update_message($inserted, $updated, $unchanged, $suggested);
}

// = Theme functions ===========================================================

/**
 * Format translatable strings with custom icons.
 *
 * We emphasize some parts of strings, so those are easy to recognize.
 * Newlines and replacement strings are made more visible.
 *
 * @param $string
 *   Source string to translate.
 */
function l10n_community_format_text($string) {
  static $path = NULL, $title = NULL;
  
  if (!isset($path)) {
    $path = base_path() . drupal_get_path('module', 'l10n_community');
    $title = t('line break');
  }

  // Replace all newline chars in the string with an indicator image.
  $string = str_replace(
    array("\n", "\\\\n"),
    '<img src="'. $path .'/images/newline.png" alt="'. $title .'" title="'. $title .'" /><br />',
    check_plain($string)
  );
  // Make all %, ! and @ marked pladeholders emphasized.
  $string = preg_replace(
    '~((%|!|@)[0-9a-zA-Z_-]+)~',
    '<em class="l10n-community-marker">\\1</em>',
    $string
  );
  return $string;
}

/**
 * Progress bar and table column display for translation status information.
 *
 * @param $sum
 *   The number of all translatable strings to count with.
 * @param $translated
 *   Number of strings translated (and without outstanding
 *   suggestions) out of the $sum.
 * @param $has_suggestion
 *   Number of strings which are not translated yet (or are
 *   translated) and have at least one suggestion attached.
 * @return
 *   An indexed array with four elements, first being the progress bar,
 *   and the other three being calculated and percentages.
 */
function theme_l10n_community_progress_columns($sum, $translated, $has_suggestion) {
  // Compute numbers, percentages and provide alternate text titles.
  $status = array(
    'translated'     => array((int) $translated, round($translated / $sum * 100, 2), t('!percent translated')),
    'has-suggestion' => array((int) $has_suggestion, round($has_suggestion / $sum * 100, 2),  t('!percent has suggestion')),
    'untranslated'   => array($sum - $translated, 0, t('!percent untranslated')),
  );
  $status['untranslated'][1] = 100 - $status['translated'][1];
  
  // Visual summary with a progress bar.
  $bar = '<div class="l10n-community-progress">';
  foreach ($status as $key => $values) {
    if ($values[1] > 0) {
      $bar .= '<div class="l10n-community-progress-'. $key . '" style="width:'. $values[1] .'%;" title="'. strtr($values[2], array('!percent' => $values[1] .'%')) .'"></div>';
    }
  }
  $bar .= '</div>';
  
  return array(
    $bar,
    $status['translated'][0] .' ('. $status['translated'][1] .'%)',
    $status['has-suggestion'][0],
    $status['untranslated'][0]
  );
}

/**
 * Copy button for string values.
 *
 * @param $sid
 *   Source string ID.
 */
function theme_l10n_community_copy_button($sid = 0) {
  $title = t('Copy value to edit field.');
  return ' <span title="'. $title .'" id="l10n-community-copy-'. $sid .'" class="l10n-community-copy"></span>';
}
