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
      $list = array('<em>'. $instance->project_title .':</em> '. $instance->release_title .' ('. $instance->occurance_count .')');
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
  $result = db_query("SELECT t.tid, t.sid, t.translation, t.uid_entered, t.time_entered, u.name FROM {l10n_community_translation} t LEFT JOIN {users} u ON u.uid = t.uid_entered WHERE t.language = '%s' AND t.sid = %d AND t.is_active = 1 AND t.is_suggestion = 1 ORDER BY t.time_entered", $langcode, $sid);

  $perm = l10n_community_get_permission($langcode);
  while ($suggestion = db_fetch_object($result)) {
    
    // This detail pane is only retrieved from JS, so we are free to output obtrusive JS here.
    $approve_button = $perm == L10N_PERM_ALL ? theme('l10n_community_button', 'approve', 'approve', 'onclick="return l10nCommunity.approveSuggestion('. $suggestion->tid .','. $suggestion->sid .');" title="'. t('Approve suggestion.') .'"') : '';
    
    $decline_button = $perm == L10N_PERM_ALL ? theme('l10n_community_button', 'decline', 'decline', 'onclick="return l10nCommunity.declineSuggestion('. $suggestion->tid .','. $suggestion->sid .');" title="'. t('Decline suggestion.') .'"') : '';
    
    $copy_button = theme('l10n_community_button', 'edit', 'copy', 'onclick="return l10nCommunity.copySuggestion('. $suggestion->sid .','. check_plain(str_replace("\\0", "\O", drupal_to_js($suggestion->translation))) .');" title="'. t('Copy suggestion text to the edit field.') .'"');
    
    // Plural versions are shown in a short form.
    $translation = check_plain(strpos($suggestion->translation, "\0") ? str_replace(chr(0), '; ', $suggestion->translation) : $suggestion->translation);
    
    $suggestions[] = $decline_button . $approve_button . $copy_button . $translation . '<div class="l10n-suggestion-by">' . t('by %user at %date', array('%user' => (empty($suggestion->name) ? variable_get('anonymous', t('Anonymous')) : $suggestion->name), '%date' => format_date($suggestion->time_entered))) . '</div>';  
  }
  if (count($suggestions)) {
    // List of suggestions.
    $suggestion_info = '<div class="l10n-suggestions"><strong>'. t('Outstanding suggestions:') .'</strong>'. theme('item_list', $suggestions) .'</div>';
  }
  else {
    $suggestion_info .= '<div class="l10n-no-suggestions"></div>';
  }
  
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
    // Mark existing translations and suggestions as inactive in this language.
    db_query("UPDATE {l10n_community_translation} SET is_active = 0 WHERE sid = %d AND language = '%s'", $suggestion->sid, $suggestion->language);
    // Remove placeholder translation record (which was there if
    // first came suggestions, before an actual translation).
    db_query("DELETE FROM {l10n_community_translation} WHERE sid = %d AND translation = '' AND language = '%s'", $suggestion->sid, $suggestion->language);
    // Mark this exact suggestion as active, and set approval time.
    db_query("UPDATE {l10n_community_translation} SET time_approved = %d, uid_approved = %d, has_suggestion = 0, is_suggestion = 0, is_active = 1 WHERE tid = %d;", time(), $user->uid, $suggestion->tid);
    // Return something so the client sees we are OK.
    print 'done';
    exit;
  }
  print 'error';
  exit;
}

/**
 * Records decline action of a previous string suggestion.
 * 
 * This callback is invoked from JavaScript.
 *
 * @param $tid
 *   Suggestion ID.
 */
function l10n_community_string_decline($tid = 0) {
  if (($suggestion = db_fetch_object(db_query("SELECT * FROM {l10n_community_translation} WHERE tid = %d AND is_suggestion = 1 AND is_active = 1", $tid))) && (l10n_community_get_permission($suggestion->language) == L10N_PERM_ALL)) {
    // Mark this suggestion as inactive.
    db_query("UPDATE {l10n_community_translation} SET is_active = 0 WHERE tid = %d", $tid);
    // Let's see if we have any other suggestion remaining in this language.
    $count = db_result(db_query("SELECT COUNT(*) FROM {l10n_community_translation} WHERE sid = %d AND is_suggestion = 1 AND is_active = 1 AND language = '%s'", $suggestion->sid, $suggestion->language));
    if (!$count) {
      // No remaining suggestions in this language.
      db_query("UPDATE {l10n_community_translation} SET has_suggestion = 0 WHERE sid = %d AND is_suggestion = 0 AND is_active = 1 AND language = '%s'", $suggestion->sid, $suggestion->language);
    }
    // Return something so the client sees we are OK.
    print 'done';
    exit;
  }
  print 'error';
  exit;
}

/**
 * Remote submission, no form exposed, so we get the data by POST.
 *
 * We try our best to inform the remote submitter of the error condition, if
 * it was not possible to save the translation for some reason.
 */
function l10n_community_string_remote($langcode) {
  global $user;
  
  // Output information about version of this interface.
  echo '<!-- v1.0 Localization community remote translation interface -->';
  
  if (user_access('access localization community')) {
    if (($languages = l10n_community_get_languages('name')) && isset($languages[$langcode])) {
      if (l10n_community_get_permission($langcode) != L10N_PERM_NONE) {
        if (!empty($_POST['source']) && !empty($_POST['target'])) {
          if ($sid = db_result(db_query("SELECT sid FROM {l10n_community_string} WHERE value = '%s'", $_POST['source']))) {
            // Always save this as suggestion.
            l10n_community_target_save(
              $sid, $_POST['target'], $langcode, $user->uid, TRUE,
              $inserted, $updated, $unchanged, $suggested
            );
            // @todo: output JS to mark string as shared in l10n_client
            // (eg. mark it blue instead of green for that request)
          }
          else {
            echo '<script>alert("'. t("This source string does not exist on the localization server. Translation not shared.") .'");</script>';
          }
        }
        else {
          echo '<script>alert("'. t("No POST data with source and translation string. Translation not shared.") .'");</script>';
        }
      }
      else {
        echo '<script>alert("'. t("You don't have permission to submit suggestions for this language on the localization server. Translation not shared.") .'");</script>';
      }
    }
    else {
      echo '<script>alert("'. t("No such language set up on the localization server. Translation not shared.") .'");</script>';
    }
  }
  else {
    echo '<script>alert("'. t("You don't have permissions to access the localization server. Please log in on the server. Translation not shared.") .'");</script>';
  }

  // Get the iframe we were invoked from not to reload with page
  // reloads (this is a hack dependent on how l10n_client works).
  echo '<script>document.location="about:blank";</script>';
}
