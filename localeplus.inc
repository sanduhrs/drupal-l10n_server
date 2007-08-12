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
