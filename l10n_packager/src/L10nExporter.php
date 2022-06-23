<?php

namespace Drupal\l10n_packager;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service description.
 */
class L10nExporter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a L10nExporter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory
  ) {
    $this->configFactory = $config_factory;
  }

  /**
   * Generates the PO(T) files contents and wrap them in a tarball for a given
   * project.
   *
   * @param $uri string Project URI.
   * @param $release int
   *   Release number (rid) to generate tarball for, or NULL to generate
   *   with all releases considered.
   * @param $language object Language object.
   * @param $template bool
   *   TRUE if templates should be exported, FALSE if translations.
   * @param $compact bool
   *   A compact export will skip outputting the comments, superfluous
   *   newlines, empty translations and the list of files.
   * @param $installer bool
   *   Whether we should only export the translations needed for the installer
   *   and not those needed for the runtime site.
   * @param $suggestions bool
   * @return array
   */
  function export($uri, $release = NULL, $language = NULL, $template = TRUE, $compact = FALSE, $installer = FALSE, $suggestions = FALSE) {
    $project = $this->getProjects(array('uri' => $uri));

    $query = db_select('l10n_server_file', 'f');
    $query->innerJoin('l10n_server_line', 'l', 'f.fid = l.fid');
    $query->innerJoin('l10n_server_string', 's', 'l.sid = s.sid');
    $query
      ->fields('s', array('sid', 'value', 'context'))
      ->fields('f', array('location', 'revision'))
      ->fields('l', array('lineno', 'type'))
      ->condition('f.pid', $project->pid)
      ->orderBy('s.sid');

    if (!$template) {
      // Join differently based on compact method, so we can skip strings without
      // translation for compact method export.
      $translation_join = ($compact) ? 'innerJoin' : 'leftJoin';

      // Improve the query for templates
      $query->leftJoin('l10n_server_status_flag', 'st',
                       'st.sid = s.sid AND st.language = :language',
                       array(':language' => $language->language));
      $query->$translation_join('l10n_server_translation', 't',
                                's.sid = t.sid AND t.language = :language AND t.is_active = 1',
                                array(':language' => $language->language));
      $query
        ->fields('t', array('translation', 'is_suggestion'))
        ->fields('st', array('has_suggestion'))
        ->orderBy('t.is_suggestion')
        ->orderBy('t.time_entered', 'DESC');

      // Installer strings are POTX_STRING_INSTALLER or POTX_STRING_BOTH.
      if ($installer) {
        $query->condition('type', array(0,1), 'IN');
      }

      // Only include suggestions if requested, otherwise filter out.
      if (!$suggestions) {
        $query->condition('t.is_suggestion', '0');
      }
    }

    if (isset($release)) {
      // Release restriction.
      $query->condition('f.rid', $release);
      $releases = $this->getReleases($uri);
      $release = $releases[$release];
    }

    $previous_sid = $sid_count = 0;
    $export_string = $po_data = array();

    $result = $query->execute();
    foreach ($result as $string) {
      if ($string->sid != $previous_sid) {
        // New string in the stream. Store all the info about *the previous one*
        // (if any).
        $this->exportStringFiles($po_data, $uri, $language, $template, $export_string, $compact, $suggestions);

        // Now fill in the new string values.
        $previous_sid = $string->sid;
        $export_string = array(
          'comment' => array(),
          'value' => $string->value,
          'context' => $string->context,
          'translation' => (!empty($string->translation) && !$string->is_suggestion) ? $string->translation : '',
          'suggestions' => array(),
          'revisions' => array(),
          'changed' => isset($string->time_approved) ? $string->time_approved : 0,
          'type' => $string->type,
          'has_suggestion' => @$string->has_suggestion,
        );

        // Count this source string with this first occurrence found.
        $sid_count++;
      }
      else {
        // Existing string but with new location information.
        if ($export_string['type'] != 0 && $export_string['type'] != $string->type) {
          // Elevate string type if it is not already 0 (POTX_STRING_BOTH), and
          // the currently found string type is different to the previously found.
          $export_string['type'] = 0;
        }
      }
      // Uniquely collected, so we use array keys for speed.
      $export_string['comment'][$string->location][$string->lineno] = 1;
      $export_string['revisions'][$string->revision] = 1;
      if ($string->is_suggestion) {
        $export_string['suggestions'][$string->translation] = 1;
      }
    }
    if ($previous_sid > 0) {
      // Store the last string because that only has all its accumulated
      // information available after the loop ended.
      $this->exportStringFiles($po_data, $uri, $language, $template, $export_string, $compact, $suggestions);
    }

    if (empty($po_data)) {
      // No strings were found.
      if (isset($release)) {
        $message = t('There are no strings in the %release release of %project to export.', array('%project' => $project->title, '%release' => $release->title));
      }
      else {
        $message = t('There are no strings in any releases of %project to export.', array('%project' => $project->title));
      }
      // Message to the user.
      drupal_set_message($message);
      // Message to watchdog for possible automated packaging.
      watchdog('l10n_community', $message);
      return NULL;
    }

    // Generate a 'unique' temporary filename for this package.
    $tempfile = tempnam(file_directory_temp(), 'l10n_community-' . $uri);

    if (!$compact) {
      if (count($po_data['revisions']) == 1) {
        $file_list = '# Generated from file: ' . $po_data['revisions'][0] . "\n";
      }
      else {
        $file_list = '# Generated from files:' . "\n#  " . join("\n#  ", $po_data['revisions']) . "\n";
      }
    }
    else {
      $file_list = '';
    }

    $release_title = $project->title . ' (' . (isset($release) ? $release->title : 'all releases') . ')';
    if (!$template) {
      $header = '# ' . $language->name . ' translation of ' . $release_title . "\n";
      $header .= "# Copyright (c) " . date('Y') . ' by the ' . $language->name . " translation team\n";
      $header .= $file_list;
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: " . $release_title . "\\n\"\n";
      $header .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      // Use date placeholder, if we have no date information (no translation here yet).
      $header .= "\"PO-Revision-Date: " . (!empty($po_data['changed']) ? date("Y-m-d H:iO", $po_data['changed']) : 'YYYY-mm-DD HH:MM+ZZZZ') . "\\n\"\n";
      $header .= "\"Language-Team: " . $language->name . "\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if ((!empty($language->formula) || $language->formula === "0") && $language->plurals) {
        $header .= "\"Plural-Forms: nplurals=" . $language->plurals . "; plural=" . strtr($language->formula, array('$' => '')) . ";\\n\"\n";
      }
    }
    else {
      $language_title = (isset($language) ? $language->name : 'LANGUAGE');
      $header = "# " . $language_title . " translation of " . $release_title . "\n";
      $header .= "# Copyright (c) " . date('Y') . "\n";
      $header .= $file_list;
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: " . $release_title . "\\n\"\n";
      $header .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
      $header .= "\"Language-Team: " . $language_title . "\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if (isset($language) && (!empty($language->formula) || $language->formula === "0") && $language->plurals) {
        $header .= "\"Plural-Forms: nplurals=" . $language->plurals . "; plural=" . strtr($language->formula, array('$' => '')) . ";\\n\"\n";
      }
      else {
        $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
      }
    }
    // Write to file directly. We should only do this once.
    $fh = fopen($tempfile, 'w');
    fwrite($fh, $header . "\n" . $po_data['file']);
    fclose($fh);

    // Output a single PO(T) file.
    return array('text/plain', $tempfile, $uri . '-' . (isset($release) ? $release->title : 'all') . (isset($language) ? '.' . $language->language : '') . ($template ? '.pot' : '.po'), $sid_count);
  }

  /**
   * Provides a list of projects from the database, ordered by uri.
   *
   * @param $options
   *   Associative array of options
   *    - 'uri': Project URI, if requesting information about one project only.
   *      If not specified, information about all projects is returned.
   *    - 'pager': Number of projects to return a pager query result with. If
   *      NULL, no pager is used.
   *    - 'all': If not specified, unpublished projects are excluded (default).
   *      If TRUE, even unpublished projects are returned (for admin pages).
   * @return
   *   An associative array keyed with project uris.
   */
  function getProjects($options = array()) {
    static $projects = array();

    $select = db_select('l10n_server_project', 'p')->fields('p');

    // Consider returning all projects or just published ones.
    if (empty($options['all'])) {
      $select->condition('status', 1);
    }

    if (isset($options['initial'])) {
      $initials = $this->getProjectInitials();
      if (isset($initials[$options['initial']])) {
        $args = $initials[$options['initial']]['values'];
        for ($i = 0 ; $i < sizeof($args) ; $i++) {
          $arguments[':p_' . $i] = $args[$i];
        }
        $placeholders = implode(',', array_keys($arguments));
        $select->where("SUBSTRING(title, 1, 1) IN ($placeholders)", $arguments);
      }
    }
    if (isset($options['pager'])) {
      // If a pager view was asked for, collect data independently.
      $select->orderBy('title');
      $result = $select->extend('PagerDefault')
        ->limit($options['pager'])
        ->execute();
      $pager_results = $result->fetchAllAssoc('uri');

      // Save project information for later, if someone asks for it by uri.
      $projects = array_merge($projects, $pager_results);

      return $pager_results;
    }
    elseif (isset($options['uri'])) {
      // A specific project was asked for.
      if (isset($projects[$options['uri']])) {
        // Can be served from the local cache.
        return $projects[$options['uri']];
      }
      // Not found in cache, so query and cache before returning.
      $result = db_query("SELECT * FROM {l10n_server_project} WHERE uri = :uri", array(':uri' => $options['uri']));
      if ($project = $result->fetchObject()) {
        $projects[$options['uri']] = $project;
        return $project;
      }
    }
    else {
      // A list of *all* projects was asked for.
      $results = $select->orderBy('uri')->execute();
      foreach ($results as $project) {
        $projects[$project->uri] = $project;
      }
      return $projects;
    }
  }

  /**
   * Get all releases of a project.
   *
   * @param $uri
   *   Project code to look up releases for.
   * @param $parsed_only
   *   If TRUE, only releases which already have their tarballs downloaded and
   *   parsed for translatables are returned. Otherwise all releases recorded in
   *   the database are returned.
   * @return
   *   Array of release objects for project, keyed by release id.
   */
  function getReleases($uri, $parsed_only = TRUE) {
    $releases = array();
    $query = "SELECT r.* FROM {l10n_server_release} r LEFT JOIN {l10n_server_project} p ON r.pid = p.pid WHERE p.uri = :uri ";
    if ($parsed_only) {
      $query .= 'AND r.last_parsed > 0 ';
    }
    $result = db_query($query, array(':uri' => $uri));
    $releases = $result->fetchAllAssoc('rid');
    uasort($releases, '_l10n_server_version_compare');
    return $releases;
  }

  /**
   * Helper function to store the export string.
   *
   * @param $po_data
   * @param $uri
   * @param $language
   * @param $template
   * @param $export_string
   * @param bool $compact
   * @param bool $suggestions
   */
  private function exportStringFiles(&$po_data, $uri, $language, $template, $export_string, $compact = FALSE, $suggestions = FALSE) {
    $output = '';

    if (!empty($export_string)) {

      // Location comments are constructed in fileone:1,2,5; filetwo:123,537
      // format, where the numbers represent the line numbers of source
      // occurances in the respective source files.
      $comment = array();
      foreach ($export_string['comment'] as $path => $lines) {
        $comment[] = preg_replace('!(^[^/]+/)!', '', $path) . ':' . join(',', array_keys($lines));
      }
      $comment = '#: ' . join('; ', $comment) . "\n";
      if (!$compact) {
        $output = $comment;
      }

      $fuzzy = FALSE;
      if ($suggestions) {
        $all_suggestions = array_keys($export_string['suggestions']);
        // Export information about suggestions if inclusion was requested.
        if ($export_string['has_suggestion']) {
          // If we had suggestions, add comment to let reviewers know.
          $output .= count($all_suggestions) > 1 ? "# Suggestions on the localization server:\n" : "# Suggestion on the localization server:\n";
        }
        if (empty($export_string['translation']) && !empty($all_suggestions)) {
          // If no translation, make the translation the first identified suggestion
          // and mark the translation fuzzy (so it keeps to be a suggestion on
          // reimport).
          $export_string['translation'] = array_shift($all_suggestions);
          $fuzzy = TRUE;
        }
        if (!empty($all_suggestions)) {
          if (strpos($export_string['value'], "\0")) {
            foreach ($all_suggestions as $i => $suggestion) {
              // Format the suggestions in a readable format, if plurals.
              $all_suggestions[$i] = str_replace("\0", ' / ', $suggestion);
            }
          }
          // Add all other suggestions as comment lines. Multiline suggestions will
          // appear on multiple lines, people need to figure these out manually.
          $output .= '# ' . str_replace("\n", "\n# ", join("\n", $all_suggestions)) . "\n";
        }
      }
      if ($fuzzy) {
        $output .= "#, fuzzy\n";
      }

      if (strpos($export_string['value'], "\0") !== FALSE) {
        // This is a string with plural variants.
        list($singular, $plural) = explode("\0", $export_string['value']);
        if (!empty($export_string['context'])) {
          $output .= 'msgctxt ' . $this->exportString($export_string['context']);
        }
        $output .= 'msgid ' . $this->exportString($singular) . 'msgid_plural ' . $this->exportString($plural);
        if (!$template && !empty($export_string['translation'])) {
          // Export translations we have.
          foreach (explode("\0", $export_string['translation']) as $id => $value) {
            $output .= 'msgstr[' . $id . '] ' . $this->exportString($value);
          }
        }
        elseif (isset($language)) {
          // Empty msgstrs based on plural formula for language. Could be
          // a plural without translation or a template generated for a
          // specific language.
          for ($pi = 0; $pi < $language->plurals; $pi++) {
            $output .= 'msgstr[' . $pi . '] ""' . "\n";
          }
        }
        else {
          // Translation template without language, assume two msgstrs.
          $output .= 'msgstr[0] ""' . "\n";
          $output .= 'msgstr[1] ""' . "\n";
        }
      }
      else {
        // Simple string (and possibly translation pair).
        if (!empty($export_string['context'])) {
          $output .= 'msgctxt ' . $this->exportString($export_string['context']);
        }
        $output .= 'msgid ' . $this->exportString($export_string['value']);
        if (!empty($export_string['translation'])) {
          $output .= 'msgstr ' . $this->exportString($export_string['translation']);
        }
        else {
          $output .= 'msgstr ""' . "\n";
        }
      }

      if (empty($po_data)) {
        $po_data = array(
          'file' => '',
          'changed' => 0,
          'revisions' => array(),
        );
      }

      // Add to existing file storage.
      $po_data['file'] .= $output;
      if (!$compact) {
        $po_data['file'] .= "\n";
      }
      if (!$template) {
        $po_data['changed'] = max($po_data['changed'], $export_string['changed']);
      }
      $po_data['revisions'] = array_unique(array_merge($po_data['revisions'], array_keys($export_string['revisions'])));
    }
  }

  /**
   * Build a list of initials of active projects for listings.
   */
  function getProjectInitials() {
    // Grab the unique initials of all active projects
    $result = db_query('SELECT DISTINCT(SUBSTR(title, 1, 1)) AS initial FROM {l10n_server_project} WHERE status = :status ORDER BY initial ASC', array(':status' => 1));

    // Create an array of elements, all non-letters are grouped in '#'.
    $initials = array();
    foreach ($result as $row) {
      $initial = $row->initial;
      if (preg_match('/[A-Za-z]/', $initial)) {
        $initials[strtolower($initial)] = array(
          'title' => strtoupper($initial),
          'values' => array(strtoupper($initial)),
        );
      }
      elseif (!isset($initials[0])) {
        $initials[0] = array(
          'title' => '#',
          'values' => array($initial),
        );
      }
      else {
        $initials[0]['values'][] = $initial;
      }
    }
    return $initials;
  }

  /**
   * Print out a string on multiple lines
   *
   * @param $str
   * @return string
   */
  private function exportString($str) {
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
      $curparts = explode("\n", $this->exportWrap($curstr, 70));
      $parts = array_merge($parts, $curparts);
    }

    // Multiline string
    if (count($parts) > 1) {
      return "\"\"\n\"" . implode("\"\n\"", $parts) . "\"\n";
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
   *
   * @param $str
   * @param $len
   * @return string
   */
  private function exportWrap($str, $len) {
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
        $ret[] = $cur . " ";
        $cur = $word;
      }
      else {
        $cur = "$cur $word";
      }
    }
    $ret[] = $cur;

    return implode("\n", $ret);
  }

}
