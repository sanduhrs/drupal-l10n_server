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

  $filters = l10n_community_build_filter_values($_GET);
  $output = drupal_get_form('l10n_community_filter_form', $filters);

  $strings = l10n_community_get_strings($languages[$langcode]->language, $filters, $filters['limit']);
  if (!count($strings)) {
    drupal_set_message(t('No strings found with this filter. Try adjusting the filter options.'));
  }
  elseif (!($perm & L10N_PERM_SUGGEST) || $mode == 'view') {
    // For users without permission to translate or suggest, display the view.
    drupal_set_title(t('@language translations', array('@language' => $languages[$langcode]->name)));
    $output .= l10n_community_translate_view($strings, $languages[$langcode], $filters);
  }
  else {
    // For users with some permission, display the form.
    drupal_add_js(drupal_get_path('module', 'l10n_community') .'/l10n_community.js');
    drupal_set_title(t('Translate to @language', array('@language' => $languages[$langcode]->name)));
    $output .= drupal_get_form('l10n_community_translate_form', $strings, $languages[$langcode], $filters, $perm);
  }
  return $output;
}

// = Filter form handling ======================================================

/**
 * Translate form filter.
 *
 * @param $filters
 *   Array of filter options.
 * @param $limited
 *   Return limited set of filters (no suggestion filters).
 */
function l10n_community_filter_form(&$form_state, $filters, $limited = FALSE) {
  $projects = l10n_community_get_projects();

  $translation_options = array(
    L10N_STATUS_ALL            => t('<Any>'),
    L10N_STATUS_UNTRANSLATED   => t('Untranslated'),
    L10N_STATUS_TRANSLATED     => t('Translated'),
  );
  $suggestion_options = array(
    L10N_STATUS_ALL            => t('<Any>'),
    L10N_STATUS_NO_SUGGESTION  => t('Has no suggestion'),
    L10N_STATUS_HAS_SUGGESTION => t('Has suggestion'),
  );
  
  $form['project'] = array(
    '#title' => t('Project'),
    '#default_value' => isset($filters['project']) ? $filters['project']->title : '',
  );
  if (($count = count($projects)) <= 30) {
    // Select widget for 1-30 projects.
    $form['project']['#type'] = 'select';
    $form['project']['#options'] = array('' => t('All'));
    foreach ($projects as $project) {
      // URI used to shorten the lookup cycle in filter sanitization.
      $form['project']['#options'][$project->title] = $project->title;
    }
  }
  else {
    // Autocomplete field for more then 30 projects.
    $form['project'] += array(
      '#type' => 'textfield',
      '#autocomplete_path' => 'translate/projects/autocomplete',
      '#size' => 20,
    );
  }

  if (isset($filters['project'])) {
    $releases = l10n_community_get_releases($filters['project']->uri);
    $release_options = array('all' => t('All'));
    foreach ($releases as $rid => $release) {
      $release_options[$rid] = $release->title;
    }
    $form['release'] = array(
      '#title' => t('Release'),
      '#type' => 'select',
      '#options' => $release_options,
      '#default_value' => isset($filters['release']) ? $filters['release'] : 'all',
    );
  }

  if (count($contexts = l10n_community_get_contexts()) > 1) {
    $form['context'] = array(
     '#title' => t('Context'),
      '#type' => 'select',
      '#options' => array('all' => t('All')) + $contexts,
      '#default_value' => $filters['context'],
    );
  }

  $form['status'] = array(
    '#title' => t('Status'),
    '#tree' => TRUE,
  );
  $form['status']['translation'] = array(
    '#type' => 'select',
    '#options' => $translation_options,
    '#default_value' => $filters['status'] & (L10N_STATUS_TRANSLATED | L10N_STATUS_UNTRANSLATED),
  );
  if (!$limited) {
    $form['status']['suggestion'] = array(
      '#type' => 'select',
      '#options' => $suggestion_options,
      '#default_value' => $filters['status'] & (L10N_STATUS_HAS_SUGGESTION | L10N_STATUS_NO_SUGGESTION),
    );
  }

  $form['author']= array(
    '#type' => 'textfield',
    '#title' => t('Submitted by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => isset($filters['author']) ? $filters['author']->name : '',
    '#size' => 15,
  );

  $form['search'] = array(
    '#title' => t('Contains'),
    '#type' => 'textfield',
    '#default_value' => $filters['search'],
    '#size' => 20,
  );

  $form['limit'] = array(
    '#type' => 'select',
    '#title' => t('Limit'),
    '#default_value' => isset($filters['limit']) ? $filters['limit'] : 10,
    '#options' => drupal_map_assoc(array(5, 10, 20, 30)),
    '#default_value' => $filters['limit'],
  );

  $form['submit'] = array(
    '#value' => t('Filter'),
    '#type' => 'submit',
  );
  $form['reset'] = array(
    '#value' => t('Reset'),
    '#type' => 'submit',
  );
  $form['#theme'] = 'l10n_community_filter_form';
  return $form;
}

/**
 * Submission handler for filtering form.
 */
function l10n_community_filter_form_submit($form, &$form_state) {

  if ($form_state['values']['op'] == t('Reset')) {
    // Just go with the redirection flow => removes URL params.
    return;
  }

  if ($form_state['values']['op'] == t('Filter')) {
    $filters = l10n_community_build_filter_values($form_state['values']);
    // Redirect keeping the relevant filters intact in the URL.
    $form_state['redirect'] = array($_GET['q'], l10n_community_flat_filters($filters));
  }
}

// = Translation viewer ========================================================

/**
 * Form for translations display.
 *
 * @param $strings
 *   Array of string objects to display on the page.
 * @param $language
 *   Language object corresponding to the page displayed.
 * @param $filters
 *   Filters used to present this listing view.
 */
function l10n_community_translate_view($strings = array(), $language = NULL, $filters = array()) {
  $output = '';
  $rows = array();
  foreach ($strings as $string) {
    $row = array();
    // Source display
    $source = l10n_community_format_string($string->value);
    $source .= theme('l10n_community_in_context', $string);
    $row[] = array('data' => $source, 'class' => 'source');

    // Translation display.
    if (!empty($string->translation)) {
      if (strpos($string->value, chr(0)) !== FALSE) {
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
      $row[] = $translation;
    }
    else {
      $row[] = '';
    }
    $rows[] = $row;
  }
  $output .= ($pager = theme('pager', NULL, $filters['limit'], 0));
  $output .= theme('table', array(t('Source Text'), t('Translations')), $rows, array('class' => 'l10n-server-translate'));
  $output .= $pager;
  $output = "<div id='l10n-community-translate-view'>". $output ."</div>";
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
 * @param $filters
 *   Filters used to present this editing view.
 * @param $perm
 *   Community permission level of user watching the page.
 */
function l10n_community_translate_form(&$form_state, $strings = array(), $language = NULL, $filters = array(), $perm = L10N_PERM_SUGGEST) {

  if (isset($_GET['page'])) {
    // Ensure that we keep all filter values, even the page number, so
    // after submission, the same page can be shown.
    $filters['page'] = (int) $_GET['page'];
  }

  $form = array(
    '#tree' => TRUE,
    '#redirect' => array($_GET['q'], l10n_community_flat_filters($filters))
  );
  $form['pager_top'] = array(
    '#weight' => -10,
    '#value' => ($pager = theme('pager', NULL, $filters['limit'], 0)),
  );
  $form['pager_bottom'] = array(
    '#weight' => 10,
    '#value' => $pager,
  );
  // Keep language code and URI in form for further reference.
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $language->language
  );
  $form['project'] = array(
    '#type' => 'value',
    '#value' => isset($project) ? $project->uri : NULL
  );

  foreach ($strings as $string) {
    $form[$string->sid] = array(
      '#tree' => TRUE,
    );

    // A toolbox which displays action icons on each string editor fieldset.
    $toolbox = theme('l10n_community_button', 'translate', 'l10n-translate active');
    $toolbox .= theme('l10n_community_button', 'lookup', 'l10n-lookup');
    $toolbox .= $string->has_suggestion ? theme('l10n_community_button', 'has-suggestion', 'l10n-suggestions') : "";
    $toolbox = "<div class='toolbox'>$toolbox</div>";
    $form[$string->sid]['toolbox'] = array(
      '#type' => 'markup',
      '#value' => $toolbox,
    );
    $form[$string->sid]['messagebox'] = array(
      '#type' => 'markup',
      '#value' => "<div class='messagebox'></div>",
    );

    $is_plural = strpos($string->value, "\0");
    // Multiple source strings if we deal with plurals. The form item and
    // consequently the JavaScript strings identifiers are the sid and then
    // the index of the plural being displayed.
    $string_parts = explode(chr(0), $string->value);
    foreach ($string_parts as $delta => &$part) {
      $part = l10n_community_format_text($part, $string->sid, (count($string_parts) > 1) ? $delta : NULL);
    }
    $source = theme('l10n_community_strings', $string_parts);
    $source .= theme('l10n_community_in_context', $string);

    $form[$string->sid]['source'] = array(
      '#type' => 'item',
      '#value' => $source,
    );

    $translated = !empty($string->translation);
    $form[$string->sid]['translation'] = array(
      '#type' => 'item',
      // Hide editing controls of translated stuff to save some space and guide user eyes.
      '#prefix' => '<div id="l10n-community-wrapper-'. $string->sid .'"'. ($translated ? ' class="hidden"' : '') .'>',
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
          $string_parts[] = l10n_community_format_text($translations[$i], $string->sid, $i);
        }
        $form[$string->sid]['translation_existing'] = array(
          '#type' => 'item',
          '#value' => theme('l10n_community_strings', $string_parts),
        );
      }

      $string_parts = explode(chr(0), $string->value);

      for ($i = 0; $i < $language->plurals; $i++) {
        $target = $string->sid .'-'. $i;
        if ($translated) {
          // Already translated so we ask for new translation or suggestion.
          $description = !($perm & L10N_PERM_MODERATE_OWN) ? t('New suggestion for variant #%d', array('%d' => $i)) : t('New translation for variant #%d', array('%d' => $i));
        }
        else {
          // Not translated yet, so we ask for initial translation or suggestion.
          $description = !($perm & L10N_PERM_MODERATE_OWN) ? t('Suggestion for variant #%d', array('%d' => $i)) : t('Translation for variant #%d', array('%d' => $i));
        }

        // Include editing area for each plural variant.
        $source_index = ($i > 0 ? 1 : 0);
        $form[$string->sid]['translation']['value'][$i] = array(
          // Use textarea for long and multiline strings.
          '#type' => ((strlen($string_parts[$source_index]) > 45) || (count(explode("\n", $string_parts[$source_index])) > 1)) ? 'textarea' : 'textfield',
          '#description' => $description,
          '#rows' => 1,
          '#id' => 'l10n-community-translation-'. $target,
        );
      }
    }

    // Dealing with a simple string (no plurals).

    else {
      if ($translated) {
        $form[$string->sid]['translation_existing'] = array(
          '#type' => 'item',
          '#value' => theme('l10n_community_strings', array(l10n_community_format_text($string->translation, $string->sid))),
        );
      }
      $form[$string->sid]['translation']['value'] = array(
        // Use textarea for long and multiline strings.
        '#type' => ((strlen($string->value) > 45) || (count(explode("\n", $string->value)) > 1)) ? 'textarea' : 'textfield',
        // Provide accurate title based on previous data and permission.
        '#description' => $translated ? (!($perm & L10N_PERM_MODERATE_OWN) ? t('Add a new suggestion') : t('Add a new translation')) : (!($perm & L10N_PERM_MODERATE_OWN) ? t('Suggestion') : ''),
        '#rows' => 4,
        '#resizable' => FALSE,
        '#cols' => NULL,
        '#size' => NULL,
        '#id' => 'l10n-community-translation-'. $string->sid,
      );
      if (strlen($string->value) > 200) {
        $form[$string->sid]['translation']['value']['#rows'] = floor(strlen($string->value) * .03);
        $form[$string->sid]['translation']['value']['#resizable'] = TRUE;
      }
    }

    // Add AJAX saving buttons
    $form[$string->sid]['translation']['save'] = array(
      '#prefix' => "<span class='l10n-approval-buttons'>",
      '#value' => theme('l10n_community_button', 'save', 'l10n-save'),
      '#type' => 'markup',
    );
    $form[$string->sid]['translation']['clear'] = array(
      '#suffix' => "</span>",
      '#value' => theme('l10n_community_button', 'clear', 'l10n-clear'),
      '#type' => 'markup',
    );

    if (!($perm & L10N_PERM_MODERATE_OWN)) {
      // User with suggestion capability only, record this.
      $form[$string->sid]['translation']['is_suggestion'] = array(
        '#type' => 'value',
        '#value' => TRUE
      );
    }
    else {
      // User with full privileges, offer option to submit suggestion.
      $form[$string->sid]['translation']['is_suggestion'] = array(
        '#title' => t('Suggestion for discussion'),
        '#type' => 'checkbox',
      );
    }
  }

  // Add all strings for copy-pasting and some helpers.
  drupal_add_js(
    array(
      'l10n_lookup_help'      => t('Show detailed information.'),
      'l10n_approve_error'    => t('There was an error approving this suggestion. You might not have permission or the suggestion id was invalid.'),
      'l10n_approve_confirm'  => t('!icon Suggestion approved.', array('!icon' => '&#10004;')),

      'l10n_decline_error'    => t('There was an error declining this suggestion. You might not have permission or the suggestion id was invalid.'),
      'l10n_decline_confirm'  => t('Suggestion declined.'),

      'l10n_details_callback' => url('translate/details/'. $language->language .'/'),
      'l10n_suggestions_callback' => url('translate/suggestions/'. $language->language .'/'),
      'l10n_approve_callback' => url('translate/approve/'),
      'l10n_decline_callback' => url('translate/decline/'),
      'l10n_form_token_path'  => variable_get('clean_url', '0') ? '?form_token=' : '&form_token=',
      'l10n_num_plurals'      => $language->plurals
    ),
    'setting'
  );

  // Let the user submit the form.
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => !($perm & L10N_PERM_MODERATE_OWN) ? t('Save suggestions') : t('Save translations')
  );

  $form['#theme'] = 'l10n_community_translate_form';

  return $form;
}

/**
 * Save translations entered in the web form.
 */
function l10n_community_translate_form_submit($form, &$form_state) {
  global $user;

  $inserted = $updated = $unchanged = $suggested = $duplicates = $ignored = 0;

  foreach ($form_state['values'] as $sid => $item) {
    if (!is_array($item) || !isset($item['translation'])) {
      // Skip, if we don't have translations in this form item,
      // which means this is some other form value.
      continue;
    }

    $source_string = db_result(db_query('SELECT value FROM {l10n_community_string} WHERE sid = %d', $sid));
    $text = '';
    if (is_string($item['translation']['value']) && strlen(trim($item['translation']['value']))) {
      // Single string representation: simple translation.
      $text = l10n_community_trim($item['translation']['value'], $source_string);
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

    if (!empty($text)) {
      // Check for duplicate translation or suggestion.
      if (l10n_community_is_duplicate($text, $sid, $form_state['values']['langcode'])) {
        $duplicates++;
        continue;
      }

      // We have some string to save.
      l10n_community_target_save(
        $sid, $text, $form_state['values']['langcode'], $user->uid,
        ($item['translation']['is_suggestion'] == TRUE),
        $inserted, $updated, $unchanged, $suggested
      );
    }
  }

  // Inform user about changes made to the database.
  l10n_community_update_message($inserted, $updated, $unchanged, $suggested, $duplicates, $ignored);
}

// = Theme functions ===========================================================

/**
 * Theme function for l10n_community_filter_form.
 */
function theme_l10n_community_filter_form($form) {
  $row = array();
  $labels = array();
  // Only display these elements in distinct table cells
  $elements = array('project', 'release', 'context', 'status', 'author', 'search', 'limit');
  foreach ($form as $id => &$element) {
    if (in_array($id, $elements)) {
      $labels[] = $element['#title'];
      unset($element['#title']);
      $row[] = drupal_render($element);
    }
  }
  // Fill in the rest of the header above the buttons.
  $labels[] = '';
  // Display the rest of the form in the last cell
  $row[] = array('data' => drupal_render($form), 'class' => 'last');
  return theme('table', $labels, array($row), array('class' => 'l10n-server-filter'));
}

/**
 * Theme function for l10n_community_translate_form.
 */
function theme_l10n_community_translate_form($form) {
  $rows = array();
  $output = '';

  foreach ($form as $id => &$element) {
    // if the form id is numeric, this form element is for editing a string
    if (is_numeric($id)) {
      $source_pane = drupal_render($element['source']);
      $translation_pane = "<div class='l10n-panes clear-block'>";
      $translation_pane .= drupal_render($element['toolbox']);
      $translation_pane .= "<div class='pane translate'>";
      $translation_pane .= !empty($element['translation_existing']) ? drupal_render($element['translation_existing']) : '';
      $translation_pane .= drupal_render($element['messagebox']);
      $translation_pane .= drupal_render($element['translation']) .'</div>';
      $translation_pane .= "<div class='pane suggestions'></div><div class='pane lookup'></div>";
      $translation_pane .= "</div>";
      $row = array(
        array(
          'data' => $source_pane,
          'class' => 'source',
          'id' => 'spane-'. $id,
        ),
        array(
          'data' => $translation_pane,
          'class' => 'translation',
          'id' => 'tpane-'. $id,
        ),
      );
      $rows[] = $row;
      unset($form[$id]);
    }
  }
  $output .= drupal_render($form['pager_top']);
  $output .= theme('table', array(t('Source Text'), t('Translations')), $rows, array('class' => 'l10n-server-translate'));
  $output .= drupal_render($form);
  return $output;
}

/**
 * Theme context information for source strings.
 *
 * @param $string
 *   Source string object (based on l10n_community_string columns).
 */
function theme_l10n_community_in_context($string) {
  if (!empty($string->context)) {
    return '<div class="string-context">'. t('in context: @context', array('@context' => $string->context)) .'</div>';
  }
  return '';
}

// = API functions =============================================================

/**
 * Get strings under some conditions.
 *
 * @param $langcode
 *   Language code to use for the lookup.
 * @param $filters
 *   - 'project'
 *     Project object to look up strings for.
 *   - 'status'
 *     Filter strings by status. See L10N_STATUS_ALL,
 *     L10N_STATUS_UNTRANSLATED, L10N_STATUS_HAS_SUGGESTION and
 *     L10N_STATUS_TRANSLATED.
 *   - 'release'
 *     Release id of the particular project release to filter with.
 *     Use NULL to not filter on releases.
 *   - 'search'
 *     Substring to search for in all source and translation strings.
 *   - 'context'
 *     From Drupal 7, separate contexts are supported. POTX_CONTEXT_NONE is
 *     the default, if the code does not specify a context otherwise.
 * @param $pager
 *   Number of strings to be returned in a pager. Should be NULL if
 *   no pager should be used.
 * @return
 *   An array of string records from database.
 */
function l10n_community_get_strings($langcode, $filters, $pager = NULL) {
  $join = $join_args = $where = $where_args = array();
  $sql = $sql_count = '';

  $select = "SELECT DISTINCT s.sid, s.value, s.context, t.tid, t.language, t.translation, t.uid_entered, t.uid_approved, t.time_entered, t.time_approved, t.has_suggestion, t.is_suggestion, t.is_active FROM {l10n_community_string} s";
  $select_count = "SELECT COUNT(DISTINCT(s.sid)) FROM {l10n_community_string} s";
  $join[] = "LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND t.is_active = 1 AND t.is_suggestion = 0";
  $join_args[] = $langcode;

  // Add submitted by condition
  if (!empty($filters['author'])) {
    $where[] = "t.uid_entered = %d";
    $where_args[] = $filters['author']->uid;
  }

  // Release restriction.
  $release = empty($filters['release']) || $filters['release'] === 'all' ? NULL : $filters['release'];
  $project = $filters['project'];
  if ($release || $project) {
    $join[] = "INNER JOIN {l10n_community_line} l ON s.sid = l.sid";
    // If we have a release we ignore the project
    if ($release) {
      // Release restriction.
      $where_args[] = $release;
      $where[] = 'l.rid = %d';
    }  
    elseif ($project) {
      $where[] = "l.pid = %d";
      $where_args[] = $project->pid;
    }
  }

  // Context based filtering.
  if (isset($filters['context']) && $filters['context'] != 'all') {
    // We use 'none' for no context, so '' can be the defaut (for all contexts).
    $where_args[] = $filters['context'] == 'none' ? '' : $filters['context'];
    $where[] = "s.context = '%s'";
  }

  if (!empty($filters['search'])) {
    // Search in the source or target strings.
    $where_args[] = $filters['search'];
    $where_args[] = $filters['search'];
    $where[] = "(s.value LIKE '%%%s%%' OR t.translation LIKE '%%%s%%')";
  }

  // Restriction based on string status by translation / suggestions.
  $status_sql = '';
  if ($filters['status'] & L10N_STATUS_UNTRANSLATED) {
    // We are doing a LEFT JOIN especially to look into the case, when we have nothing
    // to match in the translation table, but we still have the string. (We get our
    // records in the result set in this case). The translation field is empty or
    // NULL in this case, as we are not allowing NULL there and only saving an empty
    // translation if there are suggestions but no translation yet.
    $where[] = "(t.translation is NULL OR t.translation = '')";
  } 
  elseif ($filters['status'] & L10N_STATUS_TRANSLATED) {
    $where[] = "t.translation != ''";
  }
  if ($filters['status'] & L10N_STATUS_HAS_SUGGESTION) {
    // Note that we are not searching in the suggestions themselfs, only
    // the source and active translation values. The user interface underlines
    // that we are  looking for strings which have suggestions, not the
    // suggestions themselfs.
    $where[] = "t.has_suggestion = 1";
  }
  elseif ($filters['status'] & L10N_STATUS_NO_SUGGESTION) {
    $where[] = "((t.has_suggestion IS NULL) OR (t.has_suggestion = 0))";
  }

  // Build the queries
  $sql_args = array_merge($join_args, $where_args);
  $sql_where = implode(' ', $join) . (count($where) ? (' WHERE '. implode(' AND ', $where)) : '');
  $sql = $select .' '. $sql_where;
  $sql_count = $select_count .' '. $sql_where;

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

/**
 * Check and sanitize arguments and build filter array.
 *
 * @param $params
 *   Associative array with unsanitized values.
 * @param $suggestions
 *   Whether we build the filters for the suggestions (TRUE) or not (FALSE).
 */
function l10n_community_build_filter_values($params, $suggestions = FALSE) {
  $project = $release = NULL;

  // Convert array representation of flags to one integer.
  if (isset($params['status']) && is_array($params['status'])) {
    if (isset($params['status']['suggestion'])) {
      $params['status'] = ((int) $params['status']['translation']) | ((int) $params['status']['suggestion']);
    }
    else {
      $params['status'] = (int) $params['status']['translation'];
    }
  }

  $filter = array(
    'project' => NULL,
    'status' => isset($params['status']) ? (int) $params['status'] : L10N_STATUS_ALL,
    'release' => 'all',
    'search' => !empty($params['search']) ? (string) $params['search'] : '',
    'author' => !empty($params['author']) && ($account = user_load(array('name' => $params['author']))) ? $account : NULL,
    // Dropdown, validated by form API.
    'context' => isset($params['context']) ? (string) $params['context'] : 'all',
    'limit' => (isset($params['limit']) && in_array($params['limit'], array(5, 10, 20, 30))) ? (int) $params['limit'] : 10,
  );
  
  // The project can be a dropdown or text field depending on number of 
  // projects. So we need to sanitize its value. 
  if (isset($params['project'])) {
    // Try to load project by uri or title, but give URI priority. URI is used
    // to shorten the URL and have simple redirects. Title is used if the
    // filter form was submitted, but that one is simplified to the URI on
    // redirect to make the URL shorter.
    $project = l10n_community_get_projects(array('uri' => $params['project']));
    if (empty($project)) {
      $project = db_fetch_object(db_query("SELECT * FROM {l10n_community_project} WHERE title = '%s'", $params['project']));
    }
    if (!empty($project)) {
      $filter['project'] = $project;
      if (isset($params['release']) && ($releases = l10n_community_get_releases($project->uri)) && isset($releases[$params['release']])) {
        // Allow to select this release, if belongs to current project only.
        $filter['release'] = $params['release'];
      }
    }
  }
  return $filter;
}

/**
 * Replace complex data filters (objects or arrays) with string representations.
 *
 * @param $filters
 *   Associative array with filters passed.
 * @return
 *   The modified filter array only containing string and number values.
 */
function l10n_community_flat_filters($filters) {
  foreach (array('project' => 'uri', 'author' => 'name') as $name => $key) {
    if (!empty($filters[$name])) {
      $filters[$name] = $filters[$name]->$key;
    }
  }
  return $filters;
}
