<?php
// $Id$

/**
 * @file
 *   Gettext import/export API.
 */

// We are reusing some appropriate code from locale.inc.
include_once 'includes/locale.inc';

define('GETTEXTAPI_MODE_KEEP', 0);
define('GETTEXTAPI_MODE_OVERWRITE', 1);
 
/**
 * Parses Gettext Portable Object file and saves strings.
 *
 * Modified version of _locale_import_po() from locale.inc:
 *   - does not support in-memory import ($op parameter)
 *   - calls gettextapi_import_one_string() to save string
 *   - algorithm untouched
 *
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $mode
 *   Should existing translations be replaced (GETTEXTAPI_MODE_KEEP or GETTEXTAPI_MODE_OVERWRITE).
 * @param $langcode
 *   Language code.
 */
function gettextapi_import($file, $langcode, $mode = GETTEXTAPI_MODE_KEEP) {
  $fd = fopen($file->filepath, "rb"); // File will get closed by PHP on return
  if (!$fd) {
    _locale_import_message('The translation import failed, because the file %filename could not be read.', $file);
    return FALSE;
  }

  $context = "COMMENT"; // Parser context: COMMENT, MSGID, MSGID_PLURAL, MSGSTR and MSGSTR_ARR
  $current = array();   // Current entry being read
  $plural = 0;          // Current plural form
  $lineno = 0;          // Current line

  while (!feof($fd)) {
    $line = fgets($fd, 10*1024); // A line should not be this long
    $lineno++;
    $line = trim(strtr($line, array("\\\n" => "")));

    if (!strncmp("#", $line, 1)) { // A comment
      if ($context == "COMMENT") { // Already in comment context: add
        $current["#"][] = substr($line, 1);
      }
      elseif (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        gettextapi_import_one_string($current, $mode, $langcode);
        $current = array();
        $current["#"][] = substr($line, 1);
        $context = "COMMENT";
      }
      else { // Parse error
        _locale_import_message('The translation file %filename contains an error: "msgstr" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
    }
    elseif (!strncmp("msgid_plural", $line, 12)) {
      if ($context != "MSGID") { // Must be plural form for current entry
        _locale_import_message('The translation file %filename contains an error: "msgid_plural" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 12));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgid"] = $current["msgid"] ."\0". $quoted;
      $context = "MSGID_PLURAL";
    }
    elseif (!strncmp("msgid", $line, 5)) {
      if ($context == "MSGSTR") {   // End current entry, start a new one
        gettextapi_import_one_string($current, $mode, $langcode);
        $current = array();
      }
      elseif ($context == "MSGID") { // Already in this context? Parse error
        _locale_import_message('The translation file %filename contains an error: "msgid" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 5));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file,  $lineno);
        return FALSE;
      }
      $current["msgid"] = $quoted;
      $context = "MSGID";
    }
    elseif (!strncmp("msgstr[", $line, 7)) {
      if (($context != "MSGID") && ($context != "MSGID_PLURAL") && ($context != "MSGSTR_ARR")) { // Must come after msgid, msgid_plural, or msgstr[]
        _locale_import_message('The translation file %filename contains an error: "msgstr[]" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      if (strpos($line, "]") === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $frombracket = strstr($line, "[");
      $plural = substr($frombracket, 1, strpos($frombracket, "]") - 1);
      $line = trim(strstr($line, " "));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgstr"][$plural] = $quoted;
      $context = "MSGSTR_ARR";
    }
    elseif (!strncmp("msgstr", $line, 6)) {
      if ($context != "MSGID") {   // Should come just after a msgid block
        _locale_import_message('The translation file %filename contains an error: "msgstr" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 6));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgstr"] = $quoted;
      $context = "MSGSTR";
    }
    elseif ($line != "") {
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
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
        _locale_import_message('The translation file %filename contains an error: there is an unexpected string on line %line.', $file, $lineno);
        return FALSE;
      }
    }
  }

  // End of PO file, flush last entry
  if (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) {
    gettextapi_import_one_string($current, $mode, $langcode);
  }
  elseif ($context != "COMMENT") {
    _locale_import_message('The translation file %filename ended unexpectedly at line %line.', $file, $lineno);
    return FALSE;
  }
  
  return TRUE;
}

/**
 * Imports a string into the database.
 *
 * @param $value
 *   Details of the string stored.
 * @param $mode
 *   Should existing translations be replaced (GETTEXTAPI_MODE_KEEP or GETTEXTAPI_MODE_OVERWRITE).
 * @param $langcode
 *   Language to store the string in.
 */
function gettextapi_import_one_string($value = NULL, $mode = NULL, $langcode = NULL) {
  global $user;
  
  static $inserted = 0;
  static $updated = 0;
  static $unchanged = 0;
  static $strings = array();

  if ($value == NULL) {
    // Result stats queried.
    return array($inserted, $updated, $unchanged);
  }
  elseif (isset($value['msgid']) && $value['msgid'] != '') {
    // Some real string to import, not header information, which we don't care about.
    $comments = _locale_import_shorten_comments($value['#']);
    
    if ($sid = db_result(db_query("SELECT sid FROM {l10n_server_string} WHERE value = '%s'", $value['msgid']))) {
      // We have this source string (otherwise we don't save anything).
      if ($translation = db_result(db_query("SELECT translation FROM {l10n_server_translation} WHERE sid = %d", $sid))) {
        // We have a translation saved for this already, so only act on it, if we should.
        if ($mode == GETTEXTAPI_MODE_OVERWRITE) {
          l10n_community_target_save($sid, $value['msgstr'], $langcode, $user->uid, $inserted, $updated, $unchanged);
        }
        else {
          // We certainly did not update this one.
          $unchanged++;
        }
      }
      else {
        // No translation yet, so save anyway.
        l10n_community_target_save($sid, $value['msgstr'], $langcode, $user->uid, $inserted, $updated, $unchanged);
      }
    }
  }
}
