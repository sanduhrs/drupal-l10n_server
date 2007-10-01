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
 * @param $langcode
 *   Language code.
 * @param $sid
 *   Source string id.
 */
function l10n_community_string_details($langcode = NULL, $sid = 0) {

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
    $approve_button = $perm == L10N_PERM_ALL ? '<img src="' . drupal_get_path('module', 'l10n_community') . '/images/approve.png" class="action" title="'. t('Approve suggestion.') .'" onclick="return l10nCommunity.approveSuggestion('. $suggestion->tid .','. $suggestion->sid .');" /> ' : '';
    
    $copy_button = '<img src="' . drupal_get_path('module', 'l10n_community') . '/images/edit.png" class="action" title="'. t('Edit suggestion.') .'" onclick="return l10nCommunity.copySuggestion('. $suggestion->sid .',\''. check_plain(str_replace(array("'", "\0"), array("\\'", "\O"), $suggestion->translation)) .'\');" /> ';
    
    // Plural versions are shown in a short form.
    $translation = strpos($suggestion->translation, "\0") ? str_replace(chr(0), '; ', $suggestion->translation) : $suggestion->translation;
    
    $suggestions[] = $approve_button . $copy_button . $translation; 
  }
  $suggestion_info = count($suggestions) ? '<div><strong>'. t('Outstanding suggestions:') .'</strong>'. theme('item_list', $suggestions) .'</div>' : ''; 
  
  print $usage_info . $translation_info . $suggestion_info;
  exit;
}

/**
 * Records approval of a previous string suggestion.
 * 
 * This callback is invoked from JavaScript.
 *
 * @param $tid
 *   Suggestion ID.
 */
function l10n_community_string_approve($tid = 0) {
  global $user;
  
  if (($suggestion = db_fetch_object(db_query("SELECT * FROM {l10n_community_translation} WHERE tid = %d AND is_suggestion = 1 AND is_active = 1", $tid))) && (l10n_community_get_permission($suggestion->language) == L10N_PERM_ALL)) {
    // Mark existing translations and suggestions as inactive.
    db_query("UPDATE {l10n_community_translation} SET is_active = 0 WHERE sid = %d", $suggestion->sid);
    // Remove placeholder translation record (which was there if
    // first came suggestions, before an actual translation).
    db_query("DELETE FROM {l10n_community_translation} WHERE sid = %d AND translation = ''", $suggestion->sid);
    // Mark this exact suggestion as active, and set approval time.
    db_query("UPDATE {l10n_community_translation} SET time_approved = %d, uid_approved = %d, has_suggestion = 0, is_suggestion = 0, is_active = 1 WHERE tid = %d;", time(), $user->uid, $suggestion->tid);
    // Return something so the client sees we are OK.
    print 'done';
    exit;
  }
  
  print 'error';
  exit;
}
