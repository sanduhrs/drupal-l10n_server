<?php
// $Id$

/**
 * @file
 *   Translation view and editing pages for localization community.
 */

// = Translation interface hub =================================================

/**
 * Menu callback for the translation pages.
 *
 * Displays a translation view or translation edit page depending
 * on permissions. If no strings are found, an error is printed.
 *
 * @param $langcode
 *   Language code, for example 'hu', 'pt-br', 'de', 'it'.
 */
function l10n_community_translate_page($langcode = NULL, $mode = 'view') {
  
  // Add missing breadcrumb.
  drupal_set_breadcrumb(
    array(
      l(t('Home'), NULL),
      l(t('Translate'), 'translate')
    )    
  );
  
  $languages = l10n_community_get_languages();
  $perm = l10n_community_get_permission($langcode);

  // Build filter values for the form generation.
  $uri = l10n_community_filter_value('project');
  if (isset($uri)) {
    $project = l10n_community_get_projects($uri);
    $release = l10n_community_filter_value('release');
    if (isset($release) && ($releases = l10n_community_get_releases($uri)) && !isset($releases[$release])) {
      // Invalidate release selection if the project was changed.
      $release = NULL;
    }
  }
  else {
    $project = NULL;
    $release = NULL;
  }
  // Thankfully L10N_STATUS_ALL is 0, so non-number bad data goes to no filter. 
  $status  = intval(l10n_community_filter_value('status'));
  $search  = (string) l10n_community_filter_value('search');
  
  // Remember cleaned data for future use.
  $_SESSION['l10n_community']['project'] = $uri;
  $_SESSION['l10n_community']['release'] = $release;
  $_SESSION['l10n_community']['status'] = $status;
  $_SESSION['l10n_community']['search'] = $search;
  
  $output = drupal_get_form('l10n_community_filter_form', $project, $status, $release, $search);
  
  $strings = l10n_community_get_strings($languages[$langcode]->locale, $project, $status, $release, $search, 10);
  if (!count($strings)) {
    drupal_set_message(t('No strings found with this filter. Try adjusting the filter options.'));
  }
  elseif ($perm == L10N_PERM_NONE || $mode == 'view') {
    // For users without permission to translate or suggest, display the view.
    drupal_set_title(t('@language translations', array('@language' => $languages[$langcode]->name)));
    $output .= l10n_community_translate_view($strings, $languages[$langcode], $uri);
  }
  else {
    // For users with some permission, display the form.
    drupal_add_js(drupal_get_path('module', 'l10n_community') .'/l10n_community.js');
    drupal_set_title(t('Translate to @language', array('@language' => $languages[$langcode]->name)));
    $output .= drupal_get_form('l10n_community_translate_form', $strings, $languages[$langcode], $uri, $perm);
  }
  return $output;
}

// = Filter form handling ======================================================

/**
 * Translate form filter.
 */
function l10n_community_filter_form($project = NULL, $status = L10N_STATUS_ALL, $release = 'all', $search  = '') {
  $projects = l10n_community_get_projects();

  $status_options = array(
    L10N_STATUS_ALL            => t('All'),
    L10N_STATUS_UNTRANSLATED   => t('Untranslated'),
    L10N_STATUS_HAS_SUGGESTION => t('Has suggestion'),
    L10N_STATUS_TRANSLATED     => t('Translated'),
  );
  $form['filters'] = array(
    '#title' => t('Show only strings where'),
    '#type' => 'fieldset',
  );
  
  // Provide link to current page with filters intact for bookmarking.
  $page = intval($_GET['page']);
  $query = 'project='. urlencode(isset($project) ? $project->uri : '') .'&release='.
           urlencode(isset($release) ? $release : 'all') .'&status='.
           urlencode(isset($status) ? $status : L10N_STATUS_ALL) .'&search='.
           urlencode(isset($search) ? $search : '') . ($page ? '&page='. $page : '');
  $form['filters']['link'] = array(
    '#type' => 'markup',
    '#value' => l(t('Link to this page'), $_GET['q'], array(), $query)
  );

  $form['filters']['project'] = array(
    '#title' => t('Project'),
    '#default_value' => isset($project) ? $project->title : '',
  );
  if (($count = count($projects)) <= 30) {
    // Select widget for 1-30 projects.
    $form['filters']['project']['#type'] = 'select';
    $form['filters']['project']['#options'] = array('' => t('All'));
    foreach ($projects as $this_project) {
      // title used to conform to the autocomplete behavior.
      $form['filters']['project']['#options'][$this_project->title] = $this_project->title;
    }
  }
  else {
    // Autocomplete field for more then 30 projects.
    $form['filters']['project'] += array(
      '#type' => 'textfield',
      '#autocomplete_path' => 'translate/projects/autocomplete',
    );
  }
  
  if (isset($project)) {
    $releases = l10n_community_get_releases($project->uri);
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
  }
  
  $form['filters']['status'] = array(
    '#title' => t('Status'),
    '#type' => 'select',
    '#options' => $status_options,
    '#default_value' => $status,
  );
  $form['filters']['search'] = array(
    '#title' => t('Contains'),
    '#type' => 'textfield',
    '#default_value' => $search,
    '#prefix' => '<br />',
  );
  $form['filters']['submit'] = array(
    '#value' => t('Filter'),
    '#type' => 'submit',
    '#prefix' => '<br />',
  );
  $form['filters']['reset'] = array(
    '#value' => t('Reset'),
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

  // Always reset filter values.
  $_SESSION['l10n_community']['project'] = $_SESSION['l10n_community']['release'] = NULL;
  $_SESSION['l10n_community']['status'] = 0;
  $_SESSION['l10n_community']['search'] = '';

  // Fill up with validated values, if we do filtering.
  if ($form['op'] == t('Filter')) {
    // Remember filters for later use, so translators don't need to
    // reenter them, pagers keep their value and so on.
    $query = array();
    if (!empty($form['project'])) {
      if ($uri = l10n_community_project_uri_by_title($form['project'])) {
        // Project found so save.
        $_SESSION['l10n_community']['project'] = $uri;
        if (!empty($form['release']) && ($releases = l10n_community_get_releases($uri)) && isset($releases[$form['release']])) {
          // Release number valid for project (might be changed independently). 
          $_SESSION['l10n_community']['release'] = $form['release'];
        }
        else {
          $_SESSION['l10n_community']['release'] = NULL;
        }
      }
      else {
        drupal_set_message(t('No project found with name %name. Reverted to filter to all projects.', array('%name' => $form['project'])));
        // Clear project and release selection.
      }
    }
    if (!empty($form['status'])) {
      $_SESSION['l10n_community']['status'] = intval($form['status']);
    }
    if (!empty($form['search'])) {
      $_SESSION['l10n_community']['search'] = $form['search'];
    }
  }
  
  // We keep staying on the same page.
}

// = Translation viewer ========================================================

/**
 * Form for translations display.
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
  
  $output = '<div id="l10n-community-translate-view">'. $pager;
  foreach ($strings as $string) {

    $output .= '<div class="l10n-community-string">';

    // Source string display.
    $is_plural = strpos($string->value, "\0");
    if ($is_plural) {
      // Multiple source strings if we deal with plurals.
      $source = theme('item_list', array_map('l10n_community_format_text', explode(chr(0), $string->value)), '');
    }
    else {
      // Single source string otherwise.
      $source = l10n_community_format_text($string->value);
    }
    $output .= '<div class="source">'. $source .'</div>';
    
    // Translation display.
    if (!empty($string->translation)) {
      if ($is_plural) {
        $translations = explode(chr(0), l10n_community_format_text($string->translation));
        // Fill in any missing items, so it is shown that not all items are done.
        if (count($translations) < $language->plurals) {
          $translations = array_merge($translations, array_fill(0, count($translations) - $language->plurals, '')); 
        }
        $translation = theme('item_list', $translations);
      }
      else {
        $translation = l10n_community_format_text($string->translation);
      }
      $output .= '<div class="translation">'. $translation .'</div>';
    }
    $output .= '</div>';
  }
  $output .= str_replace('class="pager"', 'class="pager pager-bottom"', $pager) .'</div>';
  return $output;
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
      '#value' => '<div class="toolbox">'. ($string->has_suggestion ? theme('l10n_community_button', 'has-suggestion', 'l10n-has-suggestion', 'title="'. t('Has suggestions.') .'"') : theme('l10n_community_button', 'has-no-suggestion', 'l10n-has-no-suggestion', 'title="'. t('Has no suggestions.') .'"') ) .'</div><div class="messagebox"></div>'
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
        for ($i = 0; $i < $language->plurals; $i++) {
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
      
      for ($i = 0; $i < $language->plurals; $i++) {
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
        '#title' => t('Flag as suggestion for later discussion'),
        '#type' => 'checkbox',
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

      'l10n_image_path'       => base_path() . drupal_get_path('module', 'l10n_community') .'/images/',

      'l10n_lookup_help'      => t('Show detailed information.'),
      'l10n_approve_error'    => t('There was an error approving this suggestion. You might not have permission or the suggestion id was invalid.'),
      'l10n_approve_confirm'  => t('!icon Suggestion approved.', array('!icon' => '&#10004;')),

      'l10n_decline_error'    => t('There was an error declining this suggestion. You might not have permission or the suggestion id was invalid.'),
      'l10n_decline_confirm'  => t('Suggestion declined.'),

      'l10n_details_callback' => url('translate/details/'. $language->locale .'/'),
      'l10n_approve_callback' => url('translate/approve/'),
      'l10n_decline_callback' => url('translate/decline/'),
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
 * Copy button for string values.
 *
 * @param $sid
 *   Source string ID.
 */
function theme_l10n_community_copy_button($sid = 0) {
  return theme('l10n_community_button', 'edit', 'l10n-community-copy', 'id="l10n-community-copy-'. $sid .'"');
}

// = API functions =============================================================

/**
 * Get strings under some conditions.
 *
 * @param $langcode
 *   Language code to use for the lookup.
 * @param $project
 *   Project object to look up strings for.
 * @param $status
 *   Filter strings by status. See L10N_STATUS_ALL,
 *   L10N_STATUS_UNTRANSLATED, L10N_STATUS_HAS_SUGGESTION and
 *   L10N_STATUS_TRANSLATED.
 * @param $release
 *   Release id of the particular project release to filter with.
 *   Use NULL to not filter on releases.
 * @param $search
 *   Substring to search for in all source and translation strings.
 * @param $pager
 *   Number of strings to be returned in a pager. Should be NULL if
 *   no pager should be used.
 * @return
 *   An array of string records from database.
 */
function l10n_community_get_strings($langcode, $project = NULL, $status = L10N_STATUS_ALL, $release = NULL, $search = NULL, $pager = NULL) {
  
  $sql = $sql_count = '';
  $sql_args = array();
  
  if (!isset($project)) {
    // No project based filtering.
    $sql = "SELECT DISTINCT s.sid, s.value, t.tid, t.language, t.translation, t.uid_entered, t.uid_approved, t.time_entered, t.time_approved, t.has_suggestion, t.is_suggestion, t.is_active FROM {l10n_community_string} s LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND t.is_active = 1 AND t.is_suggestion = 0 WHERE";
    $sql_count = "SELECT COUNT(DISTINCT(s.sid)) FROM {l10n_community_string} s LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND t.is_active = 1 AND t.is_suggestion = 0 WHERE";
    $sql_args = array($langcode);
  }
  else {
    // Project based filtering and language based filtering built in.
    $sql = "SELECT DISTINCT s.sid, s.value, t.tid, t.language, t.translation, t.uid_entered, t.uid_approved, t.time_entered, t.time_approved, t.has_suggestion, t.is_suggestion, t.is_active FROM {l10n_community_release} r INNER JOIN {l10n_community_file} f ON r.rid = f.rid INNER JOIN {l10n_community_line} l ON f.fid = l.fid INNER JOIN {l10n_community_string} s ON l.sid = s.sid LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND t.is_active = 1 AND t.is_suggestion = 0 WHERE r.pid = %d";
    $sql_count = "SELECT COUNT(DISTINCT(s.sid)) FROM {l10n_community_release} r INNER JOIN {l10n_community_file} f ON r.rid = f.rid INNER JOIN {l10n_community_line} l ON f.fid = l.fid INNER JOIN {l10n_community_string} s ON l.sid = s.sid LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND t.is_active = 1 AND t.is_suggestion = 0 WHERE r.pid = %d";
    $sql_args = array($langcode, $project->pid);
  }

  if (!empty($search)) {
    // Search in the source or target strings.
    $sql_args[] = $search;
    $sql_args[] = $search;
    $search_sql = " AND (s.value LIKE '%%%s%%' OR t.translation LIKE '%%%s%%')";
    $sql .= $search_sql;
    $sql_count .= $search_sql;
  }

  if (isset($release) && $release != 'all') {
    // Release restriction.
    $sql_args[] = $release;
    $sql_args[] = $release;
    $release_sql = ' AND r.rid = %d';
    $sql .= $release_sql;
    $sql_count .= $release_sql;
  }
    
  // Restriction based on string status.
  switch ($status) {
    case L10N_STATUS_UNTRANSLATED:
      // We are doing a LEFT JOIN especially to look into the case, when we have nothing 
      // to match in the translation table, but we still have the string. (We get our 
      // records in the result set in this case). The translation field is empty or
      // NULL in this case, as we are not allowing NULL there and only saving an empty
      // translation if there are suggestions but no translation yet.
      $status_sql = " AND (t.translation is NULL OR t.translation = '')";
      $sql .= $status_sql;
      $sql_count .= $status_sql;
      break;
    case L10N_STATUS_TRANSLATED:
      $status_sql = " AND t.translation != ''";
      $sql .= $status_sql;
      $sql_count .= $status_sql;
      break;
    case L10N_STATUS_HAS_SUGGESTION:
      // Note that we are not searching in the suggestions themselfs, only
      // the source and active translation values. The user interface underlines
      // that we are  looking for strings which have suggestions, not the
      // suggestions themselfs.
      $status_sql = " AND t.has_suggestion = 1";
      $sql .= $status_sql;
      $sql_count .= $status_sql;
      break;
    case L10N_STATUS_ALL:
    default:
      // Leave query alone on this case.
      break;
  }
  
  // Morph "WHERE AND" to "WHERE" and remove closing "WHERE". The first appears
  // if there was no project filtering but further filtering was made. The
  // second appears if only language filtering was used.
  $sql = str_replace('WHERE AND', 'WHERE', preg_replace('!(WHERE$)!', '', $sql));
  $sql_count = str_replace('WHERE AND', 'WHERE', preg_replace('!(WHERE$)!', '', $sql_count));

  // We either need a pager or a full result.
  if (isset($pager)) {
    $strings = pager_query($sql, $pager, 0, $sql_count, $sql_args);
  }
  else {
    $strings = db_query($sql, $sql_args);
  }
  $result = array();
  while ($string = db_fetch_object($strings)) {
    $result[] = $string;
  }
  return $result;
}
