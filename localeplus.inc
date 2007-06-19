<?php
// $Id$
/**
 * @file localeplus.inc
 * Based on locale module, it can import and export many types of translation files.
 *
 * Its a optimized version of locale.inc, found on Drupal core.
 * While the locale.inc was designed to work only with PO files
 * and write/read only the locale tables, this file transform the
 * whole import/export process into a small API.
 *
 * This new API allow users to create new importers and exporters and
 * write/read the results into different tables, which is crucial to
 * Live Translation Server.
 *
 * Here are only the funtions that are not the same on locale.inc.
 */

include_once "./includes/locale.inc";

/**
 * Process the locale import form submission.
 */
function _localeplus_admin_import_submit($form_id, $form_values) {
  if ($file = file_save_upload('file')) { // Get the file content
    $fd = fopen($file->filepath, 'rb');    // File will get closed by PHP on return
    if (empty($fd)) {
      drupal_set_message(t('The translation import failed, because the file %filename could not be read.', array('%filename' => $file->filepath)), 'error');
      return FALSE;
    }
//   while (!feof($fd)) {
//     $line = fgets($fd, 10*1024); // A line should not be this long
//     $lineno++;
//     $line = trim(strtr($line, array("\\\n" => "")));
// drupal_set_message("--". $line."--". $lineno);
// }
    // Add language, if not yet supported
    $languages = locale_supported_languages(FALSE, TRUE);
    $langcode  = $form_values['langcode'];
    if (!isset($languages["name"][$langcode])) {
      $isocodes = _locale_get_iso639_list();
      _locale_add_language($langcode, $isocodes[$langcode][0], FALSE);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($isocodes[$langcode][0]))));
    }
    if (empty($form_values['importer'])) {
      $form_values['importer'] = "_localeplus_import_po";
    }
    if (empty($form_values['saver'])) {
      $form_values['saver'] = "_localeplus_import_locale_saver";
    }
    _localeplus_import($fd, $form_values['mode'], $form_values['langcode'], $form_values['group'], $form_values['importer'], $form_values['saver']);
  }
  else {
    drupal_set_message(t('File to import not found.'), 'error');
  }
  $form_state['redirect'] = !empty($_GET["destination"]) ? $_GET["destination"] :
    !empty($_POST["destination"]) ? $_POST["destination"] : 'admin/build/translate/import';
  return;
}

function _localeplus_export_po_form($languages) {
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export translation'),
    '#collapsible' => TRUE,
  );
  $form['export']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#options' => $languages,
    '#description' => t('Select the language you would like to export in gettext Portable Object (.po) format.'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  return $form;
}

function _localeplus_export_pot_form() {
  // Complete template export of the strings
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export template'),
    '#collapsible' => TRUE,
    '#description' => t('Generate a gettext Portable Object Template (.pot) file with all the interface strings from the Drupal locale database.'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  $form['#base'] = '_localeplus_export_po_form';
  return $form;
}

/**
 * User interface for the translation export screen
 */
function _localeplus_admin_export_screen() {
  $languages = localeplus_supported_languages(FALSE, TRUE);
  $languages = array_map('t', $languages['name']);
  unset($languages['en']);

  $output = '';
  // Offer language specific export if any language is set up
  if (count($languages)) {
    $output = drupal_get_form('_localeplus_export_po_form', $languages);
  }

  $output .= drupal_get_form('_localeplus_export_pot_form');

  return $output;
}

/**
 * Process a locale export form submissions.
 */
function _localeplus_export_po_form_submit($form_id, $form_values) {
  _localeplus_export_po($form_values['langcode']);
}

/**
 * Delete a language string.
 */
function _localeplus_string_delete($lid) {
  db_query('DELETE FROM {locales_source} WHERE lid = %d', $lid);
  db_query('DELETE FROM {locales_target} WHERE lid = %d', $lid);
  localeplus_refresh_cache();
  drupal_set_message(t('The string has been removed.'));

  drupal_goto('admin/settings/locale/string/search');
}

/**
 * Coordenates all exporting process
 *
 * @param $data
 *   string, all file content
 * @param $mode
 *   string, "overwrite" or "keep" modes
 * @param $importer
 *   string, function name that will write the translation file format
 * @param $language
 *   string, Language code to generate the output for, or NULL if
 *   generating translation template.
 * @param $saver
 *   string, function name that will save on database all translated strings
 * @param $group
 *   string, Text group to export PO file from (eg. 'default' for
 *   interface translations)
 */
function _localeplus_import($data, $mode, $language = NULL, $group = NULL, $importer = "_localeplus_import_po", $saver = "_localeplus_import_locale_saver") {
  // get all strings from the file
  $strings = call_user_func_array($importer, array($data, $language, $mode, $group, $saver));
  if (empty($strings)) { // no strings found
    $message = t('The translation import failed.');
    drupal_set_message($message, 'error');
    watchdog('locale', $message, WATCHDOG_ERROR);
  }
  else {
    // Get status information on import process
    list($headerdone, $additions, $updates) = call_user_func_array($saver, array('db-report'));
    if (empty($headerdone)) {
      _localeplus_import_message('The translation appears to have a missing or malformed header.');
    }
    cache_clear_all("locale:$language", 'cache'); // rebuild locale cache
    menu_rebuild(); // rebuild the menu, strings may have changed
    list($additions, $updates) = $strings;
    drupal_set_message(t('The translation was successfully imported. There are %number newly created translated strings and %update strings were updated.',
      array('%number' => $additions,
      '%update' => $updates)));
    watchdog('locale', t('Imported into %locale: %number new strings added and %update updated.',
      array('%locale' => $language,
      '%number' => $additions,
      '%update' => $updates)));
  }
} // end of _localeplus_import()

/**
 * Imports a string into the database
 *
 * @param $op
 *   Operation to perform: 'db-store', 'db-report', 'mem-store' or 'mem-report'
 * @param $value
 *   Details of the string stored
 * @param $mode
 *   Should existing translations be replaced ('overwrite' or 'keep')
 * @param $lang
 *   Language to store the string in
 * @param $file
 *   Object representation of file being imported, only required when op is 'db-store'
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _localeplus_import_locale_saver($op, $value = NULL, $lang = NULL, $mode = NULL, $group = 'default') {
  static $additions = 0;
  static $updates = 0;
  static $headerdone = FALSE;
  static $strings = array();

  switch ($op) {
    // Return stored strings
    case 'mem-report':
      return $strings;

    // Store string in memory (only supports single strings)
    case 'mem-store':
      $strings[$value['msgid']] = $value['msgstr'];
      return;

    // Called at end of import to inform the user
    case 'db-report':
      return array($headerdone, $additions, $updates);

    // Store the string we got in the database
    case 'db-store':
      // We got header information
      if ($value['msgid'] == '') {
        $hdr = _locale_import_parse_header($value['msgstr']);

        // Get the plural formula
        if (isset($hdr["Plural-Forms"]) && $p = _locale_import_parse_plural_forms($hdr["Plural-Forms"])) {
          list($nplurals, $plural) = $p;
          db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", $nplurals, $plural, $lang);
        }
        else {
          db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", 0, '', $lang);
        }
        $headerdone = TRUE;
      }

      // Some real string to import
      else {
        $comments = _locale_import_shorten_comments(empty($value['#']) ? array() : $value['#']);

        // Handle a translation for some plural string
        if (strpos($value['msgid'], "\0")) {
          $english = explode("\0", $value['msgid'], 2);
          $entries = array_keys($value['msgstr']);
          for ($i = 3; $i <= count($entries); $i++) {
            $english[] = $english[1];
          }
          $translation = array_map('_locale_import_append_plural', $value['msgstr'], $entries);
          $english = array_map('_locale_import_append_plural', $english, $entries);
          foreach ($translation as $key => $trans) {
            if ($key == 0) {
              $plid = 0;
            }
            $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english[$key], $group));
            if (!empty($loc->lid)) { // a string exists
              $lid = $loc->lid;
              // update location field
              db_query("UPDATE {locales_source} SET location = '%s' WHERE lid = %d", $comments, $lid);
              $trans2 = db_fetch_object(db_query("SELECT lid, translation, plid, plural FROM {locales_target} WHERE lid = %d AND language = '%s'",
                $lid, $lang));
              if (!isset($trans2->lid)) { // no translation in current language
                db_query("INSERT INTO {locales_target} (lid, language, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)",
                  $lid, $lang, $trans, $plid, $key);
                $additions++;
              } // translation exists
              else if ($mode == 'overwrite' || $trans2->translation == '') {
                db_query("UPDATE {locales_target} SET translation = '%s', plid = %d, plural = %d WHERE language = '%s' AND lid = %d",
                  $trans, $plid, $key, $lang, $lid);
                if ($trans2->translation == '') {
                  $additions++;
                }
                else {
                  $updates++;
                }
              }
            }
            else { // no string
              db_query("INSERT INTO {locales_source} (location, source, textgroup) VALUES ('%s', '%s', '%s')",
                $comments, $english[$key], $group);
              $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'",
                $english[$key], $group));
              $lid = $loc->lid;
              db_query("INSERT INTO {locales_target} (lid, language, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)",
                $lid, $lang, $trans, $plid, $key);
              if ($trans != '') {
                $additions++;
              }
            }
            $plid = $lid;
          }
        }

        // A simple translation
        else {
          $english = $value['msgid'];
          $translation = $value['msgstr'];
          $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english, $group));
          if (!empty($loc->lid)) { // a string exists
            $lid = $loc->lid;
            // update location field
            db_query("UPDATE {locales_source} SET location = '%s' WHERE source = '%s'", $comments, $english);
            $trans = db_fetch_object(db_query("SELECT lid, translation FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $lang));
            if (!isset($trans->lid)) { // no translation in current language
              db_query("INSERT INTO {locales_target} (lid, language, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
              $additions++;
            } // translation exists
            else if ($mode == 'overwrite') { //overwrite in any case
              db_query("UPDATE {locales_target} SET translation = '%s' WHERE language = '%s' AND lid = %d", $translation, $lang, $lid);
              if ($trans->translation == '') {
                $additions++;
              }
              else {
                $updates++;
              }
            } // overwrite if empty string
            else if ($trans->translation == '') {
              db_query("UPDATE {locales_target} SET translation = '%s' WHERE language = '%s' AND lid = %d", $translation, $lang, $lid);
              $additions++;
            }
          }
          else { // no string
            db_query("INSERT INTO {locales_source} (location, source, textgroup) VALUES ('%s', '%s', '%s')", $comments, $english, $group);
            $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english, $group));
            $lid = $loc->lid;
            db_query("INSERT INTO {locales_target} (lid, language, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
            if ($translation != '') {
              $additions++;
            }
          }
        }
      }
  } // end of db-store operation
} // end of _locale_import_locale_saver()

/**
 * Parses Gettext Portable Object file into an array
 *
 * @param $op
 *   Storage operation type: db-store or mem-store
 * @param $mode
 *   Should existing translations be replaced ('overwrite' or 'keep')
 * @param $lang
 *   Language code
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _localeplus_import_po($data, $lang = NULL, $mode = NULL, $group = NULL, $tables = "_localeplus_import_locale_saver", $file = NULL) {
  // If not in 'safe mode', increase the maximum execution time:
  if (!ini_get('safe_mode')) {
    set_time_limit(240);
  }
  $strings = array();
  $additions = $updates = 0;
  $context = "COMMENT"; // Parser context: COMMENT, MSGID, MSGID_PLURAL, MSGSTR and MSGSTR_ARR
  $current = array();   // Current entry being read
  $plural = 0;          // Current plural form
  $lineno = 0;          // Current line

  while (!feof($data)) {
    $line = fgets($data, 10*1024); // A line should not be this long
    $lineno++;
    $line = trim(strtr($line, array("\\\n" => "")));
    $additions_tmp = $updates_tmp = 0;
    if (!strncmp("#", $line, 1)) { // A comment
      if ($context == "COMMENT") { // Already in comment context: add
        $current["#"][] = substr($line, 1);
      }
      elseif (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        list($additions_tmp, $updates_tmp) = call_user_func_array($tables, array("db-store", $current, $lang, $mode, $group));
        $current = array();
        $current["#"][] = substr($line, 1);
        $context = "COMMENT";
      }
      else { // Parse error
        _localeplus_import_message('The translation contains an error: "msgstr" was expected but not found.', $line, $lineno, $file);
        return FALSE;
      }
    }
    elseif (!strncmp("msgid_plural", $line, 12)) {
      if ($context != "MSGID") { // Must be plural form for current entry
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      $line = trim(substr($line, 12));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      $current["msgid"] = $current["msgid"] ."\0". $quoted;
      $context = "MSGID_PLURAL";
    }
    elseif (!strncmp("msgid", $line, 5)) {
      if ($context == "MSGSTR") {   // End current entry, start a new one
        list($additions_tmp, $updates_tmp) = call_user_func_array($tables, array("db-store", $current, $lang, $mode, $group));
        $current = array();
      }
      elseif ($context == "MSGID") { // Already in this context? Parse error
        _localeplus_import_message('The translation contains an error: "msgid" is unexpected.', $line, $lineno, $file);
        return FALSE;
      }
      $line = trim(substr($line, 5));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      $current["msgid"] = $quoted;
      $context = "MSGID";
    }
    elseif (!strncmp("msgstr[", $line, 7)) {
      if (($context != "MSGID") && ($context != "MSGID_PLURAL") && ($context != "MSGSTR_ARR")) { // Must come after msgid, msgid_plural, or msgstr[]
        _localeplus_import_message('The translation contains an error: "msgstr[]" is unexpected on line %lineno.', $line, $lineno, $file);
        return FALSE;
      }
      if (strpos($line, "]") === FALSE) {
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      $frombracket = strstr($line, "[");
      $plural = substr($frombracket, 1, strpos($frombracket, "]") - 1);
      $line = trim(strstr($line, " "));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      $current["msgstr"][$plural] = $quoted;
      $context = "MSGSTR_ARR";
    }
    elseif (!strncmp("msgstr", $line, 6)) {
      if ($context != "MSGID") {   // Should come just after a msgid block
        _localeplus_import_message('The translation file contains an error: "msgstr" is unexpected.', $line, $lineno, $file);
        return FALSE;
      }
      $line = trim(substr($line, 6));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      $current["msgstr"] = $quoted;
      $context = "MSGSTR";
    }
    elseif ($line != "") {
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _localeplus_import_message('The translation contains a syntax error.', $line, $lineno, $file);
        return FALSE;
      }
      if (($context == "MSGID") || ($context == "MSGID_PLURAL")) {
        $current["msgid"] .= $quoted;
      }
      elseif ($context == "MSGSTR") {
        $current["msgstr"] .= $quoted;
      }
      elseif ($context == "MSGSTR_ARR") {
        $current["msgstr"][$plural] .= $quoted;
      }
      else {
        _localeplus_import_message('The translation contains an error: there is an unexpected string.', $line, $lineno, $file);
        return FALSE;
      }
    }
    $additions += $additions_tmp;
    $updates   += $updates_tmp;
  }

  // End of PO file, flush last entry
  if (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) {
    call_user_func_array($tables, array("db-store", $current, $lang, $mode, $group));
  }
  elseif ($context != "COMMENT") {
    _localeplus_import_message('The translation ended unexpectedly.', $line, $lineno, $file);
    return FALSE;
  }
  return array($additions, $updates);
}

/**
 * Sets an error message occurred during locale file parsing.
 *
 * @param $message
 *   string, The message to be translated
 * @param $line
 *   string, The broken line
 * @param $lineno
 *   number, An optional line number argument
 * @param $filename
 *   srting, Drupal file object corresponding to the PO file to import
 */
function _localeplus_import_message($message, $line = NULL, $lineno = NULL, $filename = NULL) {
  $t = get_t();
  $vars = array();
  if (!empty($line)) {
    $message = $message .' %filename(line %lineno): %line';
    $vars['%filename']  = $filename;
    $vars['%lineno']    = $lineno;
    $vars['%line']      = $line;
  }
  drupal_set_message($t($message, $vars), 'error');
}

/**
 * Exports a Portable Object (Template) file for a language
 *
 * @param $language Selects a language to generate the output for
 */
function _localeplus_export_po($language) {
  global $user;

  // Get language specific strings, or all strings
  if ($language) {
    $meta = db_fetch_object(db_query("SELECT * FROM {locales_meta} WHERE locale = '%s'", $language));
    $result = db_query("SELECT s.lid, s.source, s.location, t.translation, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE t.locale = '%s' ORDER BY t.plid, t.plural", $language);
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.location, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid ORDER BY t.plid, t.plural");
  }

  // Build array out of the database results
  $parent = array();
  while ($child = db_fetch_object($result)) {
    if ($child->source != '') {
      $parent[$child->lid]['comment'] = $child->location;
      $parent[$child->lid]['msgid'] = $child->source;
      $parent[$child->lid]['translation'] = $child->translation;
      if ($child->plid) {
        $parent[$child->lid]['child'] = 1;
        $parent[$child->plid]['plural'] = $child->lid;
      }
    }
  }

  // Generating Portable Object file for a language
  if ($language) {
    $filename = $language .'.po';
    $header .= "# $meta->name translation of ". variable_get('site_name', 'Drupal') ."\n";
    $header .= '# Copyright (c) '. date('Y') .' '. $user->name .' <'. $user->mail .">\n";
    $header .= "#\n";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
    $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"PO-Revision-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"Last-Translator: ". $user->name .' <'. $user->mail .">\\n\"\n";
    $header .= "\"Language-Team: ". $meta->name .' <'. $user->mail .">\\n\"\n";
    $header .= "\"MIME-Version: 1.0\\n\"\n";
    $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
    $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
    if ($meta->formula && $meta->plurals) {
      $header .= "\"Plural-Forms: nplurals=". $meta->plurals ."; plural=". strtr($meta->formula, array('$' => '')) .";\\n\"\n";
    }
    $header .= "\n";
    watchdog('locale', t('Exported %locale translation file: %filename.', array('%locale' => $meta->name, '%filename' => $filename)));
  }

  // Generating Portable Object Template
  else {
    $filename = 'drupal.pot';
    $header .= "# LANGUAGE translation of PROJECT\n";
    $header .= "# Copyright (c) YEAR NAME <EMAIL@ADDRESS>\n";
    $header .= "#\n";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
    $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
    $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
    $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
    $header .= "\"MIME-Version: 1.0\\n\"\n";
    $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
    $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
    $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
    $header .= "\n";
    watchdog('locale', t('Exported translation file: %filename.', array('%filename' => $filename)));
  }

  // Start download process
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/plain; charset=utf-8");

  print $header;

  foreach ($parent as $lid => $message) {
    if (!isset($message['child'])) {
      if ($message['comment']) {
        print '#: '. $message['comment'] ."\n";
      }
      print 'msgid '. _localeplus_export_print($message['msgid']);
      if ($plural = $message['plural']) {
        print 'msgid_plural '. _localeplus_export_print($parent[$plural]['msgid']);
        if ($language) {
          $translation = $message['translation'];
          for ($i = 0; $i < $meta->plurals; $i++) {
            print 'msgstr['. $i .'] '. _localeplus_export_print($translation);
            if ($plural) {
              $translation = $parent[$plural]['translation'];
              if ($i > 1) {
                $translation = _localeplus_export_remove_plural($translation);
              }
              $plural = $parent[$plural]['plural'];
            }
            else {
              $translation = '';
            }
          }
        }
        else {
          print 'msgstr[0] ""'."\n";
          print 'msgstr[1] ""'."\n";
        }
      }
      else {
        if ($language) {
          print 'msgstr '. _localeplus_export_print($message['translation']);
        }
        else {
          print 'msgstr ""'."\n";
        }
      }
      print "\n";
    }
  }
  die();
}

/**
 * Print out a string on multiple lines
 */
function _localeplus_export_print($str) {
  $stri = addcslashes($str, "\0..\37\\\"");
  $parts = array();

  // Cut text into several lines
  while ($stri != "") {
    $i = strpos($stri, "\\n");
    if ($i === FALSE) {
      $curstr = $stri;
      $stri = "";
    }
    else {
      $curstr = substr($stri, 0, $i + 2);
      $stri = substr($stri, $i + 2);
    }
    $curparts = explode("\n", _localeplus_export_wrap($curstr, 70));
    $parts = array_merge($parts, $curparts);
  }

  if (count($parts) > 1) {
    return "\"\"\n\"". implode("\"\n\"", $parts) ."\"\n";
  }
  else {
    return "\"$parts[0]\"\n";
  }
}

/**
 * Custom word wrapping for Portable Object (Template) files.
 */
function _localeplus_export_wrap($str, $len) {
  $words = explode(' ', $str);
  $ret = array();

  $cur = "";
  $nstr = 1;
  while (count($words)) {
    $word = array_shift($words);
    if ($nstr) {
      $cur = $word;
      $nstr = 0;
    }
    elseif (strlen("$cur $word") > $len) {
      $ret[] = $cur ." ";
      $cur = $word;
    }
    else {
      $cur = "$cur $word";
    }
  }
  $ret[] = $cur;

  return implode("\n", $ret);
}

/**
 * Removes plural index information from a string
 */
function _localeplus_export_remove_plural($entry) {
  return preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
}
