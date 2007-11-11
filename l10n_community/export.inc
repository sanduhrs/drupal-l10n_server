<?php
// $Id$

/**
 * @file
 *   Gettext export for localization community.
 */

/**
 * User interface for the translation export screen.
 */
function l10n_community_export_page($uri, $langcode = NULL) {
  // Set a matching title with the translation page.
  $project = l10n_community_get_projects($uri);
  if (isset($langcode)) {
    $languages = l10n_community_get_languages();
    drupal_set_title(t('Translate %project to @language', array('%project' => $project->title, '@language' => $languages[$langcode]->name)));
  }
  else {
    drupal_set_title(t('Export @project templates', array('@project' => $project->title)));
  }
  return drupal_get_form('l10n_community_export_form', $uri, $langcode);
}

/**
 * Translation export form.
 */
function l10n_community_export_form($uri, $langcode = NULL) {
  $releases = l10n_community_get_releases($uri);
  $release_options = array('all' => t('All'));
  foreach ($releases as $rid => $this_release) {
    $release_options[$rid] = $this_release->title;
  }
  $form['release'] = array(
    '#title' => t('Release number'),
    '#type' => 'select',
    '#options' => $release_options,
    '#default_value' => isset($release) ? $release : 'all',
    '#description' => t('Exporting a template or translation package for all releases is useful for translators, but is not optimal to use for end users because unused data would clutter up their database, when files get imported. Export for all releases if you would like to provide a complete translation, and you work with a desktop tool.'),
  );
  if (isset($langcode)) {
    // Only include the type selector if we are not presenting
    // a template export page (which does not have a language).
    $form['type'] = array(
      '#title' => t('Type of export'),
      '#type' => 'radios',
      '#options' => array('translation' => t('Translation'), 'template' => t('Translation template')),
      '#default_value' => 'translation',
      '#description' => t('Templates only contain the source (English) strings, translations are represented by empty placeholders.')
    );
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $langcode,
    );
    $form['version'] = array(
      '#title' => t('Format suitable for'),
      '#type' => 'radios',
      '#options' => array('drupal-6' => t('Drupal 6'), 'drupal-5' => t('Drupal 5 with autolocale module')),
      '#default_value' => 'drupal-6',
      '#description' => t("Drupal 5's autolocale module and Drupal 6 use different conventions for directory naming. Select which one to use here."),
    );
  }
  $form['uri'] = array(
    '#type' => 'value',
    '#value' => $uri,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Export'),
  );
  return $form;
}

/**
 * Generate translation or template package on the fly based on
 * all details available and return the output via HTTP.
 */
function l10n_community_export_form_submit($form_id, &$form) {   
  $language = NULL;
  if (isset($form['langcode'])) {
    $languages = l10n_community_get_languages();
    $language = $languages[$form['langcode']];
  }
  $type = (isset($form['type']) ? $form['type'] : 'template');

  // Generate tarball and get file name.
  $filename = l10n_community_export($form['uri'], ($form['release'] == 'all' ? NULL : $form['release']), $language, ($type != 'translation'), $form['version']);
  
  // Return compressed archive to user.
  header('Content-Disposition: attachment; filename='. $form['uri'] . (isset($language) ? '-'. $language->locale : '') . ($type == 'template' ? '-templates' : '-translations') .'.tgz');
  header('Content-Type: application/x-compressed');
  echo file_get_contents($filename);
  unlink($filename);
  die();
}

/**
 * Generates the PO(T) files contents and wrap them in a tarball for a given
 * project.
 *
 * @param $uri
 *   Project URI.
 * @param $release
 *   Release number (rid) to generate tarball for, or NULL to generate
 *   with all releases considered.
 * @param $language
 *   Language object.
 * @param $template
 *   TRUE if templates should be exported, FALSE if translations.
 *
 * @todo
 *   Look into possibly exporting suggestions as fuzzy translations.
 */
function l10n_community_export($uri, $release = NULL, $language = NULL, $template = TRUE, $version = NULL) {
  include_once 'Archive/Tar.php';
  
  $project = l10n_community_get_projects($uri);
  if ($template) {
    // The type field might not be set, if we are exporting a template explicitly.
    $sql = 'SELECT s.sid, s.value, f.location, f.revision, l.lineno FROM {l10n_community_release} r INNER JOIN {l10n_community_file} f ON r.rid = f.rid INNER JOIN {l10n_community_line} l ON f.fid = l.fid INNER JOIN {l10n_community_string} s ON l.sid = s.sid WHERE r.pid = %d';
    $sql_args = array($project->pid);
  }
  else {
    // We only export active translations, not suggestions.
    $sql = "SELECT s.sid, s.value, f.location, f.revision, l.lineno, t.translation, t.uid_approved, t.time_approved FROM {l10n_community_release} r INNER JOIN {l10n_community_file} f ON r.rid = f.rid INNER JOIN {l10n_community_line} l ON f.fid = l.fid INNER JOIN {l10n_community_string} s ON l.sid = s.sid  LEFT JOIN {l10n_community_translation} t ON s.sid = t.sid AND t.language = '%s' AND is_active = 1 AND is_suggestion = 0 WHERE r.pid = %d";
    $sql_args = array($language->locale, $project->pid);
  }
  
  if (isset($release)) {
    // Release restriction.
    $sql_args[] = $release;
    $sql .= ' AND r.rid = %d';
    $releases = l10n_community_get_releases($uri, TRUE);
    $release = $releases[$release];
  }
  
  // Source strings will be repeated as many times as they appear, so to generate
  // the export file properly, order by the source id.
  $sql .= ' ORDER BY s.sid';
  
  $result = db_query($sql, $sql_args);
  $previous_sid = 0;
  $export_string = $string_files = array();
  $version == 'drupal-5' ? $po_folder = 'po' : 'translations';
    
  while ($string = db_fetch_object($result)) {
    if ($string->sid != $previous_sid) {
      // New string in the stream.
      if (!empty($export_string)) {
        
        // We have an existing string to store.
        if (count(array_keys($export_string['comment'])) > 1) {
          // Appeared in more then one file, so goes to general.po.
          // Note that some modules like ubercart might not have their
          // root module in the root folder, so this needs to be rethought.
          $filename = './'. $po_folder .'/general';
        }
        else {
          // Putting into one specific directory, and there to a file.
          $filename = preg_replace('!(^[^/]+/)!', '', array_shift(array_keys($export_string['comment'])));
          $filename = dirname($filename) .'/'. $po_folder .'/'. str_replace(array('.', '_'), '-', basename($filename));
        }
        $filename .= ($template ? '.pot' : '.'. $language->locale .'.po');
        
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
          $output = $comment . 'msgid '. _l10n_community_export_string($singular) .'msgid_plural '. _l10n_community_export_string($plural);
          if (!$template && !empty($export_string['translation'])) {
            // Export translations we have.
            foreach (explode("\0", $export_string['translation']) as $id => $value) {
              $output .= 'msgstr['. $id . '] ' . _l10n_community_export_string($value);
            }
          }
          elseif (isset($language)) {
            // Empty msgstrs based on plural formula for language. Could be
            // a plural without translation or a template generated for a
            // specific language.
            for ($pi = 0; $pi < $language->plurals; $pi++) {
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
          $output = $comment . 'msgid '. _l10n_community_export_string($export_string['value']);
          if (!empty($export_string['translation'])) {
            $output .= 'msgstr ' . _l10n_community_export_string($export_string['translation']);
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
        'changed'     => $string->time_approved,
      );
    }
    else {
      // Existing string but with new location information.
      $export_string['comment'][$string->location][] = $string->lineno;
      $export_string['revisions'][] = $string->revision;
    }
  }
    
  // Generate a 'unique' temporary filename for this package.
  $tempfile = tempnam(file_directory_temp(), 'l10n_community-'. $uri);
  
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
      $header .= "# Copyright (c) ". date('Y') .' by the '. $language->name ." translation team\n";
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
function _l10n_community_export_string($str) {
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
    $curparts = explode("\n", _l10n_community_export_wrap($curstr, 70));
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
function _l10n_community_export_wrap($str, $len) {
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
