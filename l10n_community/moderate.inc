<?php
// $Id$

/**
 * @file
 *   Moderation functionality or localization community.
 */

// = Admin screens =============================================================

/**
 * Moderation page menu callback.
 *
 * Displays a string approval page useful for bulk string operations.
 * Permissions are handled by the menu system.
 *
 * @param $langcode
 *   Language code, for example 'hu', 'pt-br', 'de', 'it'.
 */
function l10n_community_moderate_page($langcode) {
  module_load_include('inc', 'l10n_community', 'translate');
  $languages = l10n_community_get_languages();
  $filters = l10n_community_build_filter_values($_GET, TRUE);

  $output = drupal_get_form('l10n_community_filter_form', $filters, TRUE);
  
  $strings = l10n_community_get_suggestions($langcode, $filters);
  if (!count($strings)) {
    drupal_set_message(t('No strings found with this filter. Try adjusting the filter options.'));
  }
  else{
    // For users with some permission, display the form.
    drupal_set_title(t('Suggestions for @language', array('@language' => $languages[$langcode]->name)));
    $output .= drupal_get_form('l10n_community_moderation_form', $strings, $languages[$langcode], $filters);
  }
  return $output;  
}

// == Moderation form ==========================================================

/** 
 * Translation web interface.
 *
 * @param $strings
 *   Array of string objects to display.
 * @param $language
 *   Language object.
 * @param $filters
 *   Filters used to present this moderation view.
 */
function l10n_community_moderation_form(&$form_state, $strings = array(), $language = NULL, $filters) {

  $form['#redirect'] = array($_GET['q'], l10n_community_flat_filters($filters));
  
  $form['pager'] = array(
    '#value' => theme('pager', NULL, $filters['limit'], 0)
  );

  // Keep language code in form for further reference.
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $language->language
  );

  // Operations.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['options']['approve'] = array(
    '#type' => 'submit',
    '#value' => t('Approve all selected'),
  );
  $form['options']['decline'] = array(
    '#type' => 'submit',
    '#value' => t('Decline all selected'),
  );
  
  // All strings and suggestions
  $form['strings'] = array(
    '#tree' => TRUE,
  );
  foreach ($strings as $string) {
    $form['strings']['tid'][$string->tid] = array(
      '#type' => 'checkbox',
    );
    $form['strings']['sid'][$string->tid] = array(
      '#type' => 'value', '#value' => $string->sid,
    );
    $form['strings']['source'][$string->tid] = array(
      //'#type' => 'item',
      '#value' => l10n_community_format_string($string->source, FALSE) .' '. theme('l10n_community_in_context', $string),
    );
    $form['strings']['translation'][$string->tid] = array(
      //'#type' => 'item',
      '#value' => l10n_community_format_string($string->translation, FALSE),
    );    
    $form['strings']['suggestion'][$string->tid] = array(
      //'#type' => 'item',
      '#value' => l10n_community_format_string($string->suggestion, FALSE),
    );
  }
  return $form;
}

/**
 * Moderation form submission callback.
 */
function l10n_community_moderation_form_submit($form, &$form_state) {
  // Filter out unchecked translations.
  $translations = array_filter($form_state['values']['strings']['tid']);
  if (!empty($translations)) {
    // Use these tids to get the list of sids.
    $strings = array_intersect_key($form_state['values']['strings']['sid'], $translations);

    // The tids are the keys of the array.
    $tids = array_keys($translations);
    // The strings array has tid => sid.
    $sids = array_unique($strings);
    if ($form_state['values']['op'] == t('Approve all selected')) {
      l10n_community_moderate_approve_selected($form_state['values']['langcode'], $tids, $sids);
    }
    elseif ($form_state['values']['op'] == t('Decline all selected')) {
      l10n_community_moderate_decline_selected($form_state['values']['langcode'], $tids, $sids);
    }
  }
  else {
    drupal_set_message(t('No suggestions selected for operation. Please select one or more suggestions to run the operation on.'), 'error');
  }
}


// == API functions ============================================================

/**
 * Get suggestions based on some conditions.
 *
 * @param $langcode
 *   Language code, for example 'hu', 'pt-br', 'de', 'it'.
 * @param $filters
 *   See l10n_community_get_strings().
 * @return
 *   An array of suggestion records from database.
 */
function l10n_community_get_suggestions($langcode, $filters) {
  $join = $join_args = $where = $where_args = array();
  $sql = $sql_count = '';

  $select = "SELECT DISTINCT ts.tid, ts.sid, tt.translation, ts.translation AS suggestion, s.value AS source, s.context FROM {l10n_community_translation} ts";
  $select_count = "SELECT COUNT(DISTINCT(ts.tid)) FROM {l10n_community_translation} ts";
  $join[] = "INNER JOIN {l10n_community_string} s ON ts.sid = s.sid";
  $where[] = "ts.is_suggestion = 1 AND ts.is_active = 1 AND ts.language = '%s'";
  $where_args[] = $langcode;
    
  // Add submitted by condition
  if (!empty($filters['author'])) {
    $where[] = "ts.uid_entered = %d";
    $where_args[] = $filters['author']->uid;
  }
  
  // Restrict based on project or release.
  $release = empty($filters['release']) || $filters['release'] === 'all' ? NULL : $filters['release'];
  $project = $filters['project'];
  if ($release || $project) {
    $join[] = "INNER JOIN {l10n_community_line} l ON ts.sid = l.sid";
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

  // Search in the source or target (suggestion) strings.
  if (!empty($filters['search'])) {
    $where_args[] = $filters['search'];
    $where_args[] = $filters['search'];
    $where[] = "(s.value LIKE '%%%s%%' OR ts.translation LIKE '%%%s%%')";
  }

  // Restriction based on string status.
  if ($filters['status'] & L10N_STATUS_UNTRANSLATED) {
    $join[] = "LEFT JOIN {l10n_community_translation} tt ON tt.sid = ts.sid AND tt.is_suggestion = 0 AND tt.is_active = 1 AND tt.language = '%s'";
    $join_args[] = $langcode;
    $where[] = "(tt.tid IS NULL OR tt.translation = '')";
  }
  elseif ($filters['status'] & L10N_STATUS_TRANSLATED) {
    $join[] = "INNER JOIN {l10n_community_translation} tt ON tt.sid = ts.sid AND tt.is_suggestion = 0 AND tt.is_active = 1 AND tt.language = '%s'";
    $join_args[] = $langcode;
  }
  else {
    $join[] = "LEFT JOIN {l10n_community_translation} tt ON tt.sid = ts.sid AND tt.is_suggestion = 0 AND tt.is_active = 1 AND tt.language = '%s'";
    $join_args[] = $langcode;
  }

  // Build the queries
  $sql_args = array_merge($join_args, $where_args);
  $sql_where = implode(' ', $join) .' WHERE '. implode(' AND ', $where);
  $sql = $select .' '. $sql_where;
  $sql_count = $select_count .' '. $sql_where;

  $strings = pager_query($sql, $filters['limit'], 0, $sql_count, $sql_args);
  $result = array();
  while ($string = db_fetch_object($strings)) {
    $result[] = $string;
  }
  return $result;
}

/**
 * Mass approve callback.
 */
function l10n_community_moderate_approve_selected($langcode, $tids, $strings) {
  global $user;
  
  // In case we have more than one sugggestion for an sid, error.
  $sids = array_unique($strings);
  if (count($sids) < count($strings)) {
    drupal_set_message(t('You cannot approve more than one suggestion per string.'), 'error');
    return;
  }
  $tid_placeholders = db_placeholders($tids);
  $sid_placeholders = db_placeholders($sids);
  
  // Mark existing translations and suggestions as inactive in this language.
  $args = array_merge($sids, array($langcode));
  db_query("UPDATE {l10n_community_translation} SET is_active = 0 WHERE sid IN ($sid_placeholders) AND language = '%s'", $args);
  
  // Remove placeholder translation record (which was there if
  // first came suggestions, before an actual translation).
  db_query("DELETE FROM {l10n_community_translation} WHERE sid IN ($sid_placeholders) AND translation = '' AND language = '%s'", $args);
  
  // Mark this exact suggestions as active, and set approval time.
  $args = array_merge(array(time(), $user->uid), $tids);
  db_query("UPDATE {l10n_community_translation} SET time_approved = %d, uid_approved = %d, has_suggestion = 0, is_suggestion = 0, is_active = 1 WHERE tid IN ($tid_placeholders)", $args);

  drupal_set_message(format_plural(count($tids), 'A suggestion has been approved.', '@count suggestions have been approved.'));
}

/**
 * Mass decline callback.
 */
function l10n_community_moderate_decline_selected($langcode, $tids, $sids) {
  // We are not interested in duplicated sids.
  $sids = array_unique($sids);
  $tid_placeholders = db_placeholders($tids);
  $sid_placeholders = db_placeholders($sids);

  // Deactive the selected suggestions.
  db_query("UPDATE {l10n_community_translation} SET is_active = 0 WHERE tid IN ($tid_placeholders)", $tids);
  drupal_set_message(format_plural(db_affected_rows(), 'A suggestion has been declined.', '@count suggestions have been declined.'));
  
  // Update 'has suggestion' option for remaining string translations
  $args = array_merge(array($langcode), $sids);
  $result = db_query(
    "SELECT tt.tid 
     FROM {l10n_community_translation} tt 
     LEFT JOIN {l10n_community_translation} ts ON tt.sid = ts.sid AND tt.language = ts.language AND ts.is_active = 1 AND ts.is_suggestion = 1 
     WHERE tt.is_active = 1 AND tt.is_suggestion = 0 AND tt.has_suggestion = 1 AND tt.language = '%s' AND tt.sid IN ($sid_placeholders) 
     GROUP BY tt.tid 
     HAVING COUNT(ts.tid) = 0",
    $args
  );
  while ($string = db_fetch_object($result)) {
    db_query("UPDATE {l10n_community_translation} SET has_suggestion = 0 WHERE tid = %d", $string->tid);
  }
}

// == Theme functions ==========================================================

/**
 * Theme the approval form
 */
function theme_l10n_community_moderation_form($form) {
  $output = '';
  $pager = isset($form['pager']) ? drupal_render($form['pager']) : '';
  $output .= $pager;
  $output .= drupal_render($form['options']);  
  $header = array(theme('table_select_header_cell'), t('Suggestion'), t('Translation'), t('Source'));
  foreach (element_children($form['strings']['tid']) as $tid) {
    $rows[] = array(
      drupal_render($form['strings']['tid'][$tid]),
      drupal_render($form['strings']['suggestion'][$tid]),
      drupal_render($form['strings']['translation'][$tid]),
      array('class' => 'source', 'data' => drupal_render($form['strings']['source'][$tid])),
    );
  }
  $output .= theme('table', $header, $rows);
  $output .= $pager;
  $output .= drupal_render($form);
  return $output;
}
