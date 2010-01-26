<?php
// $Id$

/**
 * @file
 *   Translation view and editing pages for localization community.
 */

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
    L10N_STATUS_ALL            => '<'. t('Any') .'>',
    L10N_STATUS_UNTRANSLATED   => t('Untranslated'),
    L10N_STATUS_TRANSLATED     => t('Translated'),
  );
  $suggestion_options = array(
    L10N_STATUS_ALL            => '<'. t('Any') .'>',
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
      '#autocomplete_path' => 'translate/project-autocomplete',
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

// = Translation view ==========================================================

/**
 * Menu callback: List translations and suggestions.
 */
function l10n_community_translate_page($langcode) {
  drupal_add_css(drupal_get_path('module', 'l10n_community') .'/editor.css');
  drupal_add_js(drupal_get_path('module', 'l10n_community') .'/jquery.worddiff.js');
  drupal_add_js(drupal_get_path('module', 'l10n_community') .'/editor.js');

  $language = l10n_community_get_language($langcode);
  $filters = l10n_community_build_filter_values($_GET);
  $strings = l10n_community_get_strings($langcode, $filters, $filters['limit']);

  // Set the most appropriate title.
  if ($filters['project']) {
    drupal_set_title(t('Translate %project to @language', array('%project' => $filters['project']->title, '@language' => $language->name)));
  }
  else {
    drupal_set_title(t('Translate to @language', array('@language' => $language->name)));
  }

  // Add the filter form.
  $output = drupal_get_form('l10n_community_filter_form', $filters);

  // Output the actual strings.
  if (!count($strings)) {
    drupal_set_message(t('No strings found with this filter. Try adjusting the filter options.'));
  }
  else {
    $output .= drupal_get_form('l10n_community_translate_form', $language, $filters, $strings);
  }
  return $output;
}

/**
 * Form callback: List translations and suggestions.
 *
 * @param $form_state
 *   The form state array.
 * @param $language
 *   A language object.
 * @param $filters
 *   An array of filters applied to the strings.
 * @param $strings
 *   The strings to render.
 */
function l10n_community_translate_form(&$form_state, $language, $filters, $strings) {
  $pager = theme('pager', NULL, $filters['limit'], 0);
  $redirect_url = $_GET;
  unset($redirect_url['q']);

  $form = array(
    '#submit' => array('l10n_community_translate_submit'),
    '#redirect' => array($_GET['q'], $redirect_url),
    'langcode' => array('#type' => 'value', '#value' => $language->language),
    'pager_top' => array('#weight' => -10, '#value' => $pager),
    'submit_top' => array('#type' => 'submit', '#value' => t('Save changes'), '#access' => user_access('submit suggestions')),
    'strings' => array('#tree' => TRUE, '#theme' => 'l10n_community_translate_table'),
    'submit' => array('#type' => 'submit', '#value' => t('Save changes'), '#access' => user_access('submit suggestions')),
    'pager_bottom' => array('#weight' => 10, '#value' => $pager),
  );

  foreach ($strings as $string) {
    $form['strings'][$string->sid] = _l10n_community_translate_string($form_state, $string, $language);
  }
  return $form;
}

/**
 * Return a marked-up string.
 */
function _l10n_community_translate_render_strings($strings, $empty = '') {
  if ($empty) {
    $empty = ' data-empty="'. check_plain($empty) .'"';
  }
  return "<span$empty>". implode("</span><br /><span$empty>", array_map('check_plain', $strings)) .'</span>';
}

/**
 * Creates the form fragment for a source string.
 */
function _l10n_community_translate_string(&$form_state, $source, $language) {
  // Normalize empty default translation.
  if (!$source->translation) {
    $source->tid = '0';
    $source->translation = array(t('(not translated)'));
    $source->is_active = '1';
    $source->is_suggestion = '0';
  }
  else {
    $source->translation = l10n_community_unpack_string($source->translation);
  }

  $source->value = l10n_community_unpack_string($source->value);

  $form = array(
    '#string' => $source,
    '#langcode' => $language->language,
    'source' => array(
      'string' => array('#value' => _l10n_community_translate_render_strings($source->value)),
    ),
  );

  if (user_access('submit suggestions')) {
    $form['source']['edit'] = array(
      '#value' => t('Edit Copy'),
      '#prefix' => '<label title="'. t('Edit Copy') .'">',
      '#suffix' => '</label>',
    );
  }

  // Add the current string (either a approved translation or a mock object
  // for the "untranslated" string).
  $form[$source->tid] = _l10n_community_translate_translation($form_state, $source, $source);

  // When there are suggestions, load them from the database.
  if ($source->has_suggestion) {
    $result = db_query("SELECT t.tid, t.sid, t.translation, t.uid_entered, t.time_entered, t.is_active, t.is_suggestion, u.name as username FROM {l10n_community_translation} t LEFT JOIN {users} u ON u.uid = t.uid_entered WHERE t.language = '%s' AND t.sid = %d AND t.is_active = 1 AND t.is_suggestion = 1 ORDER BY t.time_entered", $language->language, $source->sid);
    while ($suggestion = db_fetch_object($result)) {
      $suggestion->translation = l10n_community_unpack_string($suggestion->translation);
      // Add the suggestion to the list.
      $form[$suggestion->tid] = _l10n_community_translate_translation($form_state, $suggestion, $source);
    }
  }

  // If the user may add new suggestions, display a textarea.
  if (user_access('submit suggestions')) {
    $textarea = _l10n_community_translate_translation_textarea($source);
    $form[$textarea->tid] = _l10n_community_translate_translation($form_state, $textarea, $source);
  }
  return $form;
}

/**
 * Build mock object for new textarea.
 */
function _l10n_community_translate_translation_textarea($source) {
  global $user;

  return (object)array(
    'sid' => $source->sid,
    'tid' => 'new',
    'translation' => array_fill(0, count($source->value), ''),
    'is_active' => '1',
    'is_suggestion' => '1',
    'uid_entered' => $user->uid,
  );
}

/**
 * Generates the byline containing meta information about a string.
 */
function l10n_community_translate_byline($string) {
  $params = array(
    '!author' => theme('username', (object)array('name' => $string->username, 'uid' => $string->uid_entered)),
    '@date' => format_date($string->time_entered),
    '@ago' => t('@time ago', array('@time' => format_interval(time() - $string->time_entered))),
  );
  if (!empty($string->uid_approved) && $string->uid_entered === $string->uid_approved && $string->time_entered === $string->time_approved) {
    return t('translated and approved by !author <span title="@ago">on @date</span>', $params);
  }
  else {
    $title = t('suggested by !author <span title="@ago">on @date</span>', $params);
    if (!empty($string->uid_approved)) {
      $title .= '<br />'. t('approved by !author <span title="@ago">on @date</span>', array(
        '!author' => theme('username', (object)array('name' => $string->username_approved, 'uid' => $string->uid_approved)),
        '@date' => format_date($string->time_approved),
        '@ago' => t('@time ago', array('@time' => format_interval(time() - $string->time_approved))),
      ));
    }
    return $title;
  }
}

/**
 * Creates the form fragment for a translated string.
 */
function _l10n_community_translate_translation(&$form_state, $string, $source) {
  global $user;

  $is_own = $user->uid == $string->uid_entered;
  $is_active = $string->is_active && !$string->is_suggestion;
  $is_new = $string->tid == 'new';
  $may_moderate = ($is_own ? user_access('moderate own suggestions') : user_access('moderate suggestions from others'));
  //$may_stabilize = $permission & ($is_own ? L10N_PERM_STABILIZE_OWN : L10N_PERM_STABILIZE_OTHERS);

  $form = array(
    '#theme' => 'l10n_community_translate_translation',
    'original' => array('#type' => 'value', '#value' => $string),
  );

  $form['active'] = array(
    '#type' => 'radio',
    '#theme' => 'l10n_community_translate_radio',
    '#title' => _l10n_community_translate_render_strings($string->translation, $is_new ? t('(empty)') : FALSE),
    '#return_value' => $string->tid,
    '#default_value' => $is_active ? $string->tid : NULL,
    '#parents' => array('strings', $string->sid, 'active'),
    '#disabled' => !$may_moderate && !$is_active,
    '#attributes' => array('class' => 'selector'),
  );

  if ($string->tid) {
    if ($may_moderate && $string->tid != 'new') {
      $form['declined'] = array(
        '#type' => 'checkbox',
        '#title' => t('Declined'),
        '#default_value' => !($string->is_active || $string->is_suggestion),
      );
    }
    // if ($may_stabilize) {
    //   $form['stable'] = array(
    //     '#type' => 'checkbox',
    //     '#title' => t('Stable'),
    //     '#default_value' => FALSE, // $string->is_stable,
    //   );
    // }
    if ($string->tid == 'new') {
      $form['value'] = array_fill(0, count($source->value), array(
        '#type' => 'textarea',
        '#cols' => 60,
        '#rows' => 3,
        '#default_value' => t('<New translation>'),
      ));
    }
    else {
      if (user_access('submit suggestions')) {
        $form['edit'] = array(
          '#value' => t('Edit Copy'),
          '#prefix' => '<label title="'. t('Edit Copy') .'">',
          '#suffix' => '</label>',
        );
      }
      if (isset($string->username)) {
        $title = l10n_community_translate_byline($string);
        $form['author'] = array(
          '#value' => $title,
        );
      }
    }
  }
  return $form;
}

function theme_l10n_community_translate_actions($element) {
  $actions = '';
  foreach (array('declined', /*'stable', */'edit') as $type) {
    if (isset($element[$type])) {
      $actions .= '<li class="'. $type .'">'. drupal_render($element[$type]) .'</li>';
    }
  }
  if (!empty($actions)) {
    return '<ul class="actions">'. $actions .'</ul>';
  }
  else {
    return '';
  }
}

function theme_l10n_community_translate_translation($element) {
  if (!isset($element['#attributes']['class'])) {
    $element['#attributes']['class'] = '';
  }
  $element['#attributes']['class'] .= ' clearfix translation';

  switch ($element['active']['#return_value']) {
    case 'new':
      $element['#attributes']['class'] .= ' new-translation';
      break;
    case '0':
      $element['#attributes']['class'] .= ' no-translation';
      // Fallthrough.
    default:
      if ($element['active']['#value'] !== '') {
        $element['#attributes']['class'] .= ' is-active default';
      }
  }

  $output = '<li'. drupal_attributes($element['#attributes']) .'>';
  $output .= theme('l10n_community_translate_actions', $element);
  $output .= drupal_render($element['active']);

  if (isset($element['author'])) {
    $output .= '<div class="author">'. drupal_render($element['author']) .'</div>';
  }

  if (isset($element['value'])) {
    $output .= drupal_render($element['value']);
  }
  return $output  . '</li>';
}

function theme_l10n_community_translate_radio($element) {
  _form_set_class($element, array('form-radio'));
  $output = '<input type="radio" ';
  $output .= 'id="'. $element['#id'] .'" ';
  $output .= 'name="'. $element['#name'] .'" ';
  $output .= 'value="'. $element['#return_value'] .'" ';
  $output .= (check_plain($element['#value']) == $element['#return_value']) ? ' checked="checked" ' : ' ';
  $output .= drupal_attributes($element['#attributes']) .' />';

  if (isset($element['#title'])) {
    $output .= '<label class="l10n-string" for="'. $element['#id'] .'">'. $element['#title'] .'</label>';
  }
  return $output;
}

function theme_l10n_community_translate_translation_list($element) {
  $output = '<ul>';
  foreach (element_children($element) as $child) {
    if (is_numeric($child) || $child == 'new') {
      $output .= drupal_render($element[$child]);
    }
  }
  $output .= '</ul>';

  return $output;
}

function theme_l10n_community_translate_source($element) {
  $output = theme('l10n_community_translate_actions', $element['source']);
  $output .= '<label class="l10n-string">'. drupal_render($element['source']['string']) .'</label>';
  $output .= theme('l10n_community_in_context', $element['#string']);
  $output .= '<div class="l10n-usage"><a href="'. url('translate/details/'. $element['#langcode'] .'/'. $element['#string']->sid) .'" class="l10n-more">'. t('More information') .'</a></div>';
  return $output;
}

function theme_l10n_community_translate_table($element) {
  $header = array(
    t('Source Text'),
    t('Translations'),
  );

  $rows = array();

  foreach (element_children($element) as $key) {
    $rows[] = array(
      array('class' => 'source', 'data' => theme('l10n_community_translate_source', $element[$key])),
      array('class' => 'translation', 'data' => theme('l10n_community_translate_translation_list', $element[$key])),
    );
  }

  return theme('table', $header, $rows, array('class' => 'l10n-table'));
}

function l10n_community_translate_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];

  foreach ($form_state['values']['strings'] as $sid => $string) {
    foreach ($string as $tid => $options) {
      // Store new suggestion.
      $empty_values = 0;
      if (isset($options['value']) && is_array($options['value'])) {
        foreach ($options['value'] as $key => $value) {
          if ($value === t('<New translation>')) {
            $options['value'] = '';
            $empty_values++;
          }
        }
        if ($tid === 'new' && count($options['value']) > $empty_values) {
          $tid = l10n_community_add_suggestion($langcode, $sid, $options['value']);
          if ($tid) {
            l10n_community_counter(L10N_COUNT_ADDED);
            if ($string['active'] === 'new') {
              $string['active'] = $tid;
            }
          }
        }
      }

      if (is_numeric($tid) && $tid > 0) {
        if ($tid == $string['active']) {
          if ($options['original']->is_suggestion) {
            l10n_community_approve_string($langcode, $sid, $tid);
            l10n_community_counter(L10N_COUNT_APPROVED);
          }
          // if (/*!$options['original']->is_stable && */!empty($options['stable'])) {
          //   dsm('mark as stable');
          //   l10n_community_counter('stabilized');
          // }
        }
        elseif (!empty($options['declined'])) {
          // also remove stable flag!
          l10n_community_counter($options['original']->is_suggestion ? L10N_COUNT_SUGGESTION_DECLINED : L10N_COUNT_DECLINED);
          l10n_community_decline_string($langcode, $sid, $tid);
        }
      }
    }
  }

  l10n_community_update_message();
}

// = Miscellaneous =============================================================

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

  $select = "SELECT DISTINCT s.sid, s.value, s.context, t.tid, t.language, t.translation, t.uid_entered, t.uid_approved, t.time_entered, t.time_approved, t.has_suggestion, t.is_suggestion, t.is_active, u.name as username, u2.name as username_approved FROM {l10n_community_string} s";
  $select_count = "SELECT COUNT(DISTINCT(s.sid)) FROM {l10n_community_string} s";
  $join[] = "LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND t.is_active = 1 AND t.is_suggestion = 0";
  $join_args[] = $langcode;
  $join[] = "LEFT JOIN {users} u ON u.uid = t.uid_entered";
  $join[] = "LEFT JOIN {users} u2 ON u2.uid = t.uid_approved";

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
  if (isset($filters['status'])) {
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
 * Ensures that there is a mock translation for a given language/string.
 *
 * @param $langcode
 *   The language to be checked for a mock translation.
 * @param $sid
 *   The string ID that needs to have a mock translation.
 */
function l10n_community_mock_translation($langcode, $sid) {
  if (!db_result(db_query("SELECT COUNT(*) FROM {l10n_community_translation} WHERE sid = %d AND language = '%s' AND is_suggestion = 0 AND is_active = 1", $sid, $langcode))) {
    // Insert mock tuple that acts as placeholder.
    db_query("INSERT INTO {l10n_community_translation} (sid, translation, language, uid_entered, time_entered, is_suggestion, is_active) VALUES (%d, '', '%s', 0, 0, 0, 1)", $sid, $langcode);
  }
}

/**
 * Adds a suggestion to a language/string.
 *
 * @param $langcode
 *   The language of the new translation.
 * @param $sid
 *   The string ID for which a new translation should be added.
 * @param $translation
 *   An array of strings which constitute the new translation.
 */
function l10n_community_add_suggestion($langcode, $sid, $translation) {
  global $user;

  // Load source string and adjust translation whitespace based on source.
  $source_string = db_result(db_query('SELECT value FROM {l10n_community_string} WHERE sid = %d', $sid));
  $translation = l10n_community_pack_string($translation);
  $translation = l10n_community_trim($translation, $source_string);

  // Don't store empty translations.
  if ($translation === '') {
    return NULL;
  }

  // Look for an existing active translation, if any.
  $existing = db_fetch_object(db_query("SELECT tid FROM {l10n_community_translation} WHERE sid = %d AND language = '%s' AND translation = '%s'", $sid, $langcode, $translation));
  if (!empty($existing)) {
    // The translation is already in the db. Make it an active suggestion again.
    db_query("UPDATE {l10n_community_translation} SET is_suggestion = 1, is_active = 1 WHERE tid = %d", $existing->tid);
    $tid = $existing->tid;
  }
  else {
    // This is a new translation.
    l10n_community_mock_translation($langcode, $sid);
    // Insert the new suggestion.
    db_query("INSERT INTO {l10n_community_translation} (sid, translation, language, uid_entered, time_entered, is_suggestion, is_active) VALUES (%d, '%s', '%s', %d, %d, 1, 1)", $sid, $translation, $langcode, $user->uid, time());
    $tid = db_last_insert_id('l10n_community_translation', 'tid');
  }

  // Mark the existing or mock translation has having suggestions.
  l10n_community_update_suggestion_status($langcode, $sid);

  return $tid;
}

/**
 * Marks a translation as declined.
 *
 * @param $langcode
 *   The language of the declined translation.
 * @param $sid
 *   The string ID the translation belongs to.
 * @param $tid
 *   The translation ID of the translation.
 */
function l10n_community_decline_string($langcode, $sid, $tid) {
  // Mark this translation as inactive.
  db_query("UPDATE {l10n_community_translation} SET is_suggestion = 0, is_active = 0 WHERE tid = %d", $tid);

  // Make sure the mock translation is there in case we declined the active translation.
  l10n_community_mock_translation($langcode, $sid);
  l10n_community_update_suggestion_status($langcode, $sid);
}

/**
 * Updates the has_suggestion flag for the active translation.
 *
 * @param $langcode
 *   The language of the string.
 * @param $sid
 *   The string ID that should be updated.
 */
+function l10n_community_update_suggestion_status($langcode, $sid) {
  // Let's see if we have any suggestions remaining in this language.
  $count = db_result(db_query("SELECT COUNT(*) FROM {l10n_community_translation}
      WHERE sid = %d AND is_suggestion = 1 AND is_active = 1 AND language = '%s'", $sid, $langcode));

  // Update the status according to the number of suggestions.
  db_query("UPDATE {l10n_community_translation} SET has_suggestion = %d
      WHERE sid = %d AND is_suggestion = 0 AND is_active = 1 AND language = '%s'", $count ? 1 : 0, $sid, $langcode);
}

/**
 * Marks a translation as approve.
 *
 * @param $langcode
 *   The language of the approved translation.
 * @param $sid
 *   The string ID the translation belongs to.
 * @param $tid
 *   The translation ID of the translation.
 */
function l10n_community_approve_string($langcode, $sid, $tid) {
  global $user;

  // Remove placeholder translation record (which was there if
  // first came suggestions, before an actual translation).
  db_query("DELETE FROM {l10n_community_translation} WHERE sid = %d AND translation = '' AND language = '%s'", $sid, $langcode);

  // Make the existing approved string a suggestion.
  db_query("UPDATE {l10n_community_translation} SET is_suggestion = 1 WHERE sid = %d AND language = '%s' AND is_suggestion = 0 AND is_active = 1", $sid, $langcode);

  // Mark this exact suggestion as active, and set approval time.
  db_query("UPDATE {l10n_community_translation} SET time_approved = %d, uid_approved = %d, is_suggestion = 0, is_active = 1 WHERE tid = %d;", time(), $user->uid, $tid);
  l10n_community_update_suggestion_status($langcode, $sid);
}

/**
 * Unpacks a string as retrieved from the database.
 *
 * @param $string
 *   The string with separation markers (NULL byte)
 * @return
 *   An array of strings with one element for each plural form in case of
 *   a plural string, or one element in case of a regular string.
 */
function l10n_community_unpack_string($string) {
  return explode("\0", $string);
}

/**
 * Packs a string for storage in the database.
 *
 * @param $string
 *   An array of strings.
 * @return
 *   A packed string with NULL bytes separating each string.
 */
function l10n_community_pack_string($strings) {
  return implode("\0", $strings);
}

// = AJAX callbacks ============================================================

/**
 * Return a HTML list of projects, releases and counts of where strings
 * appear in the managed projects.
 *
 * We could provide much more information, but usability should also be kept
 * in mind. It is possible to investigate hidden information sources though,
 * like tooltips on the release titles presented.
 *
 * This callback is invoked from JavaScript and is used as an AHAH provider.
 *
 * @param $langcode
 *   Language code.
 * @param $sid
 *   Source string id.
 */
function l10n_community_string_details($langcode = NULL, $sid = 0) {
  // Prevent devel module information.
  $GLOBALS['devel_shutdown'] = FALSE;

  // List of project releases, where this string is used.
  $result = db_query('SELECT l.pid, p.title project_title, l.rid, r.title release_title, COUNT(l.lineno) as occurance_count FROM {l10n_community_line} l INNER JOIN {l10n_community_project} p ON l.pid = p.pid INNER JOIN {l10n_community_release} r ON l.rid = r.rid WHERE l.sid = %d AND p.status = 1 GROUP BY l.rid ORDER BY l.pid, l.rid', $sid);

  $list = array();
  $output = array();
  $previous_project = '';
  while ($instance = db_fetch_object($result)) {
    if ($instance->project_title != $previous_project) {
      if (!empty($list)) {
        $output[] = join(', ', $list);
      }
      $list = array('<em>'. $instance->project_title .':</em> '. $instance->release_title .' ('. $instance->occurance_count .')');
    }
    else {
      $list[] = $instance->release_title .' ('. $instance->occurance_count .')';
    }
    $previous_project = $instance->project_title;
  }
  $output[] = join(', ', $list);
  print '<strong>'. t('Used in:') .'</strong>'. theme('item_list', $output);

  exit;
}
