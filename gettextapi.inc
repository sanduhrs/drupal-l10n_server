<?php
// $Id$

/**
 * @file
 *   Gettext import/export API.
 */

// We are reusing some appropriate code from locale.inc.
include_once 'includes/locale.inc';

// Import ======================================================================

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

// Export ======================================================================

/**
 * Generates the PO(T) files contents and wrap them in a tarball for a given
 * project.
 *
 * @param $uri
 *   Project URI.
 * @param $release
 *   Release number (rid) to generate tarball for, or NULL to generate
 *   with all releases in mind.
 * @param $language
 *   Language object.
 * @param $type
 *   Export type. Either 'template' or 'translation'.
 */
function gettextapi_export($uri, $release = NULL, $language = NULL, $type = 'template') {
  include_once 'Archive/Tar.php';
  
  $template = ($type != 'translation');
  $project = l10n_server_get_projects($uri);
  if ($template) {
    // The type field might not be set, if we are exporting a template explicitly.
    $sql = 'SELECT s.sid, s.value, s.plural, f.location, f.revision, l.lineno FROM {l10n_server_release} r INNER JOIN {l10n_server_file} f ON r.rid = f.rid INNER JOIN {l10n_server_line} l ON f.fid = l.fid INNER JOIN {l10n_server_string} s ON l.sid = s.sid WHERE r.pid = %d';
    $sql_args = array($project->pid);
  }
  else {
    $sql = "SELECT s.sid, s.value, s.plural, f.location, f.revision, l.lineno, t.translation, t.uid, t.changed FROM {l10n_server_release} r INNER JOIN {l10n_server_file} f ON r.rid = f.rid INNER JOIN {l10n_server_line} l ON f.fid = l.fid INNER JOIN {l10n_server_string} s ON l.sid = s.sid  LEFT JOIN {l10n_server_translation} t ON s.sid = t.sid AND t.language = '%s' WHERE r.pid = %d";
    $sql_args = array($language->locale, $project->pid);
  }
  
  if (isset($release)) {
    // Release restriction.
    $sql_args[] = $release;
    $sql .= ' AND r.rid = %d';
    $releases = l10n_server_get_releases($uri, TRUE);
    $release = $releases[$release];
  }
  
  // Source strings will be repeated as many times as they appear, so to generate
  // the export file properly, order by the source id.
  $sql .= ' ORDER BY s.sid';
  
  $result = db_query($sql, $sql_args);
  $previous_sid = 0;
  $export_string = $string_files = array();
    
  while ($string = db_fetch_object($result)) {
    if ($string->sid != $previous_sid) {
      // New string in the stream.
      if (!empty($export_string)) {
        
        // We have an existing string to store.
        if (count(array_keys($export_string['comment'])) > 1) {
          // Appeared in more then one file, so goes to general.po.
          $filename = './translations/general';
        }
        else {
          // Putting into one specific directory, and there to a file.
          $filename = preg_replace('!(^[^/]+/)!', '', array_shift(array_keys($export_string['comment'])));
          $filename = dirname($filename) .'/translations/'. str_replace(array('.', '_'), '-', basename($filename));
        }
        $filename .= ($template ? '.pot' : '.po');
        
        // Location comments are constructed in fileone:1,2,5; filetwo:123,537
        // format, where the numbers represent the line numbers of source
        // occurances in the respective source files.
        $comment = array();
        foreach ($export_string['comment'] as $path => $lines) {
          $comment[] = preg_replace('!(^[^/]+/)!', '', $path) .':'. join(',', $lines);
        }
        $comment = '#: '. join('; ', $comment) ."\n";
        
        if (strpos($export_string['value'], "\0") !== FALSE) {
          // This is a string with plural variants.
          list($singular, $plural) = explode("\0", $export_string['value']); 
          $output = $comment . 'msgid '. gettextapi_export_string($singular) .'msgid_plural '. gettextapi_export_string($plural);
          if (!$template && !empty($export_string['translation'])) {
            foreach (explode("\0", $export_string['translation']) as $id => $value) {
              $output .= 'msgstr['. $id . '] ' . gettextapi_export_string($value);
            }
          }
          elseif (isset($language)) {
            // Empty msgstrs based on plural formula for language. Could be
            // a plural without translation or a template generated for a
            // specific language.
            for ($pi = 0; $pi < $language->nplurals; $pi++) {
              $output .= 'msgstr['. $pi . '] ""'."\n";
            }
          }
          else {
            // Translation template without language, assume two msgstrs.
            $output .= 'msgstr[0] ""'."\n";
            $output .= 'msgstr[1] ""'."\n";
          }
        }
        else {
          // Simple string (and possibly translation pair).
          $output = $comment . 'msgid '. gettextapi_export_string($export_string['value']);
          if (!empty($export_string['translation'])) {
            $output .= 'msgstr ' . gettextapi_export_string($export_string['translation']);
          }
          else {
            $output .= 'msgstr ""'."\n";
          }
        }
        $string_files[$filename]['file'] .= $output ."\n";
        if (!$template) {
          $string_files[$filename]['changed'] = max($string_files[$filename]['changed'], $export_string['changed']);
        }
        if (isset($string_files[$filename]['revisions'])) {
          $string_files[$filename]['revisions'] = array_unique(array_merge($string_files[$filename]['revisions'], $export_string['revisions']));
        }
        else {
          $string_files[$filename]['revisions'] = array_unique($export_string['revisions']);
        }
      }
      
      // Now fill in the new string values.
      $previous_sid = $string->sid;
      $export_string = array(
        'comment'     => array($string->location => array($string->lineno)),
        'value'       => $string->value,
        'translation' => isset($string->translation) ? $string->translation : '',
        'revisions'   => array($string->revision),
        'changed'     => $string->changed,
      );
    }
    else {
      // Existing string but with new location information.
      $export_string['comment'][$string->location][] = $string->lineno;
      $export_string['revisions'][] = $string->revision;
    }
  }
    
  // Generate a unique temporary filename for this package.
  $tempfile = tempnam(file_directory_temp(), 'l10n_server-'. $uri);
  
  // Generate tgz file with all files added.
  $tar = new Archive_Tar($tempfile, 'gz');
  foreach ($string_files as $filename => $fileinfo) {
    if (count($fileinfo['revisions']) == 1) {
      $file_list = '# Generated from file: '. $fileinfo['revisions'][0] . "\n";
    }
    else {
      $file_list = '# Generated from files:' . "\n#  " . join("\n#  ", $fileinfo['revisions']) . "\n";
    }
    
    $release_title = $project->title .' ('. (isset($release) ? $release->title : 'all releases') .')';
    if (!$template) {
      $header = '# '. $language->name .' translation of '. $release_title ."\n";
      $header .= "# Copyright (c) ". date('Y') . "\n";
      $header .= $file_list;
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: ". $release_title . "\\n\"\n";
      $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
      // Use date placeholder, if we have no date information (no translation here yet).
      $header .= "\"PO-Revision-Date: ". (!empty($fileinfo['changed']) ? date("Y-m-d H:iO", $fileinfo['changed']) : 'YYYY-mm-DD HH:MM+ZZZZ') ."\\n\"\n";
      $header .= "\"Language-Team: ". $language->name . "\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if ($language->formula && $language->plurals) {
        $header .= "\"Plural-Forms: nplurals=". $language->plurals ."; plural=". strtr($language->formula, array('$' => '')) .";\\n\"\n";
      }
    }
    else {
      $language_title = (isset($language) ? $language->name : 'LANGUAGE');
      $header = "# ". $language_title . " translation of ". $release_title . "\n";
      $header .= "# Copyright (c) ". date('Y') . "\n";
      $header .= $file_list;
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: ". $release_title . "\\n\"\n";
      $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
      $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
      $header .= "\"Language-Team: ". $language_title . "\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if (isset($language) && $language->formula && $language->plurals) {
        $header .= "\"Plural-Forms: nplurals=". $language->plurals ."; plural=". strtr($language->formula, array('$' => '')) .";\\n\"\n";
      }
      else {
        $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
      }
    }
    $tar->addString($filename, $header ."\n". $fileinfo['file']);
  }
  return $tempfile;
}  

/**
 * Print out a string on multiple lines
 */
function gettextapi_export_string($str) {
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
    $curparts = explode("\n", _locale_export_wrap($curstr, 70));
    $parts = array_merge($parts, $curparts);
  }

  // Multiline string
  if (count($parts) > 1) {
    return "\"\"\n\"". implode("\"\n\"", $parts) ."\"\n";
  }
  // Single line string
  elseif (count($parts) == 1) {
    return "\"$parts[0]\"\n";
  }
  // No translation
  else {
    return "\"\"\n";
  }
}

/**
 * Custom word wrapping for Portable Object (Template) files.
 */
function gettextapi_export_wrap($str, $len) {
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
