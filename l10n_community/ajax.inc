<?php
// $Id$

/**
 * @file
 *   AJAX callbacks for localization community.
 */

/**
 * Return a HTML list of projects, releases and counts of where strings
 * appear in the managed projects.
 *
 * We could have been provided much more information, but usability should
 * also be kept in mind. It is possible to investigate hidden information
 * sources though, like tooltips on the release titles presented.
 *
 * This callback is invoked from JavaScript and is used as an AHAH provider.
 *
 * @param $sid
 *   String id.
 * @param $langcode
 *   Language code.
 * @param $form_index
 *   Index of form element to copy suggestions to.
 */
function l10n_community_string_details($sid = 0, $langcode = NULL, $form_index = 0) {
  
  // List of project releases, where this string is used.
  $result = db_query('SELECT p.pid, p.title project_title, r.rid, r.title release_title, COUNT(l.lineno) as occurance_count FROM {l10n_community_project} p INNER JOIN {l10n_community_release} r ON p.pid = r.pid INNER JOIN {l10n_community_file} f ON r.rid = f.rid INNER JOIN {l10n_community_line} l ON f.fid = l.fid INNER JOIN {l10n_community_string} s ON l.sid = s.sid WHERE s.sid = %d GROUP BY r.rid ORDER by p.pid, r.rid', $sid);
  
  $list = array();
  $output = array();
  $previous_project = '';
  while ($instance = db_fetch_object($result)) {
    if ($instance->project_title != $previous_project) {
      if (!empty($list)) {
        $output[] = join(', ', $list);
      }
      $list = array('<em>'. $instance->project_title . ':</em> '. $instance->release_title .' ('. $instance->occurance_count .')');
    }
    else {
      $list[] = $instance->release_title .' ('. $instance->occurance_count .')';
    }
    $previous_project = $instance->project_title; 
  }
  $output[] = join(', ', $list);
  $usage_info = '<div style="float:right"><strong>'. t('Used in:') .'</strong>'. theme('item_list', $output) .'</div>';
  
  // Information about translator and translation timestamp.
  $translation_info = '';
  $translation = db_fetch_object(db_query("SELECT translation, uid_entered, time_entered FROM {l10n_community_translation} WHERE language = '%s' AND sid = %d AND is_active = 1 AND is_suggestion = 0", $langcode, $sid));
  if (!empty($translation->translation)) {
    $account = user_load(array('uid' => $translation->uid_entered));
    $translation_info = '<div>'. t('<strong>Translated by:</strong><br /> %username at %date', array('%username' => $account->name, '%date' => format_date($translation->time_entered))) .'</div>';
  }
  
  // Existing, "unresolved" suggestions.
  $suggestions = array();
  $result = db_query("SELECT * FROM {l10n_community_translation} WHERE language = '%s' AND sid = %d AND is_active = 1 AND is_suggestion = 1", $langcode, $sid);
  $perm = l10n_community_get_permission($langcode);
  while ($suggestion = db_fetch_object($result)) {
    // This detail pane is only retrieved from JS, so we are free to output obtrusive JS here.
    $approve_button = $perm == L10N_PERM_ALL ? '<img src="' . drupal_get_path('module', 'l10n_community') . '/images/approve.png" class="copy" title="'. t('Approve suggestion.') .'" onclick="l10nCommunity.approveSuggestion(\''. url('/translate/approve/'. $langcode .'/'. $suggestion->gid) .'\','. intval($form_index) .','. $suggestion->sid .'); return false;" /> ' : '';
    $suggestions[] = $approve_button .'<img src="' . drupal_get_path('module', 'l10n_community') . '/images/edit.png" class="copy" title="'. t('Edit suggestion.') .'" onclick="$(\'#l10n-community-new-translation-'. intval($form_index) . '\').val(\''. str_replace("'", "\\'", $suggestion->translation) . '\'); $(\'.l10n-community-wrapper-'. intval($form_index) .'\').css(\'display\', \'block\'); return false;" /> ' . $suggestion->translation; 
  }
  $suggestion_info = count($suggestions) ? '<div><strong>'. t('Outstanding suggestions:') .'</strong>'. theme('item_list', $suggestions) .'</div>' : ''; 
  
  print $sid ."\n\n". $usage_info . $translation_info . $suggestion_info;
  exit;
}

/**
 * Records approval of a previous string suggestion.
 * 
 * This callback is invoked from JavaScript.
 *
 * @param $gid
 *   Suggestion ID.
 */
function l10n_community_string_approve($langcode = '', $gid = 0) {
  global $user;
  
  if ((l10n_community_get_permission($langcode) == L10N_PERM_ALL) && ($suggestion = db_fetch_object(db_query("SELECT * FROM {l10n_community_suggestion} WHERE gid = %d", $gid)))) {
    // If there was a suggestion, we surely have at least an empty translation,
    // which acted as a placeholder to communicate that we have suggestions,
    // so we can update the translation right away.
    db_query("UPDATE {l10n_community_translation} SET translation = '%s', time_entered = %d, uid_entered = %d, has_suggestion = 0 WHERE sid = %d AND language = '%s';", $suggestion->suggestion, time(), $suggestion->uid, $suggestion->sid, $suggestion->language);
    // Mark all existing suggestions are "handled".
    db_query("UPDATE {l10n_community_suggestion} SET status = 0 WHERE sid = %d", $suggestion->sid);
    // Record approval in history.
    db_query("INSERT INTO {l10n_community_history} (sid, language, type, description, uid_entered, time_entered) VALUES (%d, '%s', '%s', '%s', %d, %d)", $suggestion->sid, $suggestion->language, 'approve', $gid, $user->uid, time());
    // Return something so the client sees we are OK.
    print 'done';
    exit;
  }
  
  print 'error';
  exit;
}
