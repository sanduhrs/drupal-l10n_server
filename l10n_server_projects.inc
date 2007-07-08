<?php
// $Id$

/**
 * @file
 *   Project data updates for l10n_server.
 */

/**
 * Central update server, from where project updates come from.
 */
define('L10N_PROJECT_RELEASES_URL', 'http://updates.drupal.org/release-history');

/**
 * Syncronize local project list with all available projects on the update server.
 *
 * @todo
 *   There is no service interface for this yet on the server, so for now this
 *   is just a stub, implement stuff later.
 */
function l10n_server_sync_projects() {
  // TEST DATA!
  $projects = array(
    'drupal' => (object) array(
      'uri' => 'drupal',
      'title' => 'Drupal',
      'core_compatibility' => array('4.7.x', '5.x', '6.x'),
    ),
    'ecommerce' => (object) array(
      'uri' => 'ecommerce',
      'title' => 'E-Commerce',
      'core_compatibility' => array('4.7.x', '5.x'),
    ),
    'og' => (object) array(
      'uri' => 'og',
      'title' => 'Organic groups',
      'core_compatibility' => array('4.7.x', '5.x'),
    ),
  );
  // Record all projects in the database.
  foreach ($projects as $uri => $project) {
    if ($existing_project = db_fetch_object(db_query("SELECT * FROM {l10n_server_project} WHERE uri = '%s'", $uri))) {
      if (serialize($project->core_compatibility) != serialize($existing_project->core_compatibility) || $project->title != $existing_project->title) {
        // Update if core compatibility or title is different (uri should not
        // change, other data is internal). Also set the parsed timestamp to
        // the past, so we will reparse the releases later.
        db_query("UPDATE {l10n_server_project} SET title = '%s', core_compatibility = '%s', last_parsed = 0 WHERE uri = '%s'", $project->title, serialize($project->core_compatibility), $uri);
      }
    }
    else {
      db_query("INSERT INTO {l10n_server_project} (uri, title, core_compatibility, last_parsed) VALUES ('%s', '%s', '%s', 0)", $uri, $project->title, serialize($project->core_compatibility));
    }
  }
}

/**
 * Syncronize release data for a given project with all core versions.
 *
 * @param $project
 *   Project object.
 */
function l10n_server_sync_releases($project) {
 
  foreach ($project->core_compatibility as $core_version) {
    // Request the update XML file with releases for this project for this core version.
    $xml = drupal_http_request(L10N_PROJECT_RELEASES_URL .'/'. $project->uri .'/'. $core_version);
    if (!$xml->data) {
      // No data, keep our lists.
      continue;
    }
    
    $parser = new l10n_server_project_xml_parser;
    $releases = $parser->parse(array($xml->data));
    
    if (isset($releases[$project->uri]['releases'])) {
      // We have releases to work with.
      foreach ($releases[$project->uri]['releases'] as $release) {
        if ($release['status'] != 'published') {
          // Skip unpublished releases.
          continue;
        }
        if ($existing_release = db_fetch_object(db_query("SELECT * FROM {l10n_server_release} WHERE pid = %d AND title = '%s'", $project->pid, $release['version']))) {
          if ($release['date'] != $existing_release->file_date) {
            // New file with possibly new date for the same release (dev snapshot
            // probably), so update data, set parsed date to past.
            db_query("UPDATE {l10n_server_release} SET download_link = '%s', file_date = %d, last_parsed = 0 WHERE rid = %d", $release['download_link'], $release['date'], $existing_release->rid);
          }
        }
        else {
          // No such release recorded yet.
          db_query("INSERT INTO {l10n_server_release} (pid, title, download_link, file_date, last_parsed) VALUES (%d, '%s', '%s', %d, 0)", $project->pid, $release['version'], $release['download_link'], $release['date']);
        }
      }
    }
  }
  
  // Parsed this projects releases.
  db_query("UPDATE {l10n_server_project} SET last_parsed = %d WHERE pid = %d", time(), $project->pid);
}

/**
 * Downloads files for a particular release and parses contents.
 *
 * @param $release
 *   Release object.
 */
function l10n_server_sync_files($release) {
  // This could take a long time.
  @set_time_limit(0);
  $error = $message = '';

  // We depend on the PEAR Tar class and potx module.
  include_once('Archive/Tar.php');
  require_once(drupal_get_path('module', 'potx') .'/potx.inc');

  // Set up status messages if not in automated mode.
  _potx_status($automated ? POTX_STATUS_SILENT : POTX_STATUS_MESSAGE);
  
  $filename = basename($release->download_link);
  $tempdir = file_directory_temp() .'/';
  
  // Location to save the tarball.
  $file = $tempdir . $filename;
  // Location to extract the tarball.
  $path = $tempdir .'l10n_server';
    
  // Nothing to do if already downloaded and/or the extraction folder is taken.
  if (file_exists($file) or is_dir($path)) {
    $error = t('File %file or path %path already exists.', array('%file' => $file, '%path' => $path));
  }
  
  // Download the tar.gz file from drupal.org and save it in the local filesystem.
  elseif (!copy($release->download_link, $file)) {
    $error = t('Unbale to download and save %download_link file.', array('%download_link' => $release->download_link));
  }
  
  // Extract the local file to the l10n_server directory and remove the tarball.
  else {
    $obj = new Archive_Tar($file);
    if (!$obj->extract($path)) {
      $error = t('Error on untaring %filename file.', array('%filename' => $filename));
      unlink($file);
    }
    else {
      // Get all source files and save strings with our callback for this release.
      $files = _potx_explore_dir($path);
      l10n_server_save_file($release->rid);
      foreach ($files as $name) {
        _potx_process_file($name, strlen($path) + 1, 'l10n_server_save_string', 'l10n_server_save_file');
      }
      
      // Delete the extracted files and the tarball.
      l10n_server_rmdir_recursive($path);
      unlink($file);
      
      // Record changes of the scanned project in the database.
      $message = t('Contents of %filename have been scanned.', array('%filename' => $filename));

      // Parsed this releases files.
      db_query("UPDATE {l10n_server_release} SET last_parsed = %d WHERE rid = %d", time(), $release->rid);
    }
  }
  
  $return = array();
  if ($error) {
    // WARNING: We should not do this reuse in the 6.x port, or otherwise
    // the extractor cannot pick the watchdog() strings up.
    watchdog('l10n_server', $error, WATCHDOG_ERROR);
    $return['error'] = $error;
  }
  if ($message) {
    watchdog('l10n_server', $message);
    $return['message'] = $message;
  }
  
  return $return;
}

/**
 * CVS revision saver callback for potx. We call it with a release id
 * if $file is not given. And we ask for a file ID (to save the string
 * with), if $revision is not given.
 *
 * This is called:
 *  - before any file parsing with ($rid, NULL)
 *  - just as a new file is found by potx with ($revision, $file)
 *  - just as a new string is found by our own callback with (NULL, $file)
 *
 * @param $revision
 *   CVS revision information about $file. If not given, the recorded
 *   fid of $file will be returned.
 * @param $file
 *   File location in package. If not given, $revision is taken as a
 *   release id to use to store the file list.
 */
function l10n_server_save_file($revision = NULL, $file = NULL) {
  static $rid = 0;
  static $files = array();
  
  if (!isset($file)) {
    // We get the release number for the files.
    $rid = $revision;
  }
  elseif (!isset($revision)) {
    // We return data for a specific file.
    return $files[$file];
  }
  else {
    if ($existing_file = db_fetch_object(db_query("SELECT * FROM {l10n_server_file} WHERE rid = %d AND location = '%s'", $rid, $file))) {
      // Changed revision on a file.
      db_query("UPDATE {l10n_server_file} SET revision = '%s' WHERE fid = %d", $revision, $existing_file->fid);
      $fid = $existing_file->fid;
    }
    else {
      // New file in this release.
      db_query("INSERT INTO {l10n_server_file} (rid, location, revision) VALUES(%d, '%s', '%s')", $rid, $file, $revision);
      $fid = db_result(db_query("SELECT fid FROM {l10n_server_file} WHERE rid = %d and location = '%s'", $rid, $file));
    }
    $files[$file] = $fid;
  }
}

/**
 * String saving callback for potx.
 *
 * @todo
 *   More elegant plural handling.
 * @todo
 *   Find a way to properly use POTX constants before potx.inc is
 *   loaded.
 *
 * @param $value
 *   String value to store.
 * @param $file
 *   Name of file the string occured in.
 * @param $line
 *   Number of line the string was found.
 * @param $string_mode
 *   String mode: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME
 *   or POTX_STRING_BOTH.
 */
function l10n_server_save_string($value = NULL, $file = NULL, $line = 0, $string_mode = 2 /*POTX_STRING_RUNTIME*/) {
  static $files = array();
  
  if (!isset($files[$file])) {
    // Get file ID for saving, locally cache.
    $files[$file] = l10n_server_save_file(NULL, $file);
  }
  else {
    // Multiple parts mean a string with plural variants. This means two
    // pieces, first being the singular form, second being the plural. We
    // link these two together with a psid (parent sid).
    $parts = explode("\0", $value, 2);
    $psid = 0;
    foreach ($parts as $value) {
      if (!$sid = db_result(db_query("SELECT sid FROM {l10n_server_string} WHERE value = '%s'", $value))) {
        // String does not exist.
        db_query("INSERT INTO {l10n_server_string} (value, psid) VALUES ('%s', %d)", $value, $psid);
        $sid = $psid = db_result(db_query("SELECT sid FROM {l10n_server_string} WHERE value = '%s'", $value));
      }
      if (!db_result(db_query("SELECT fid FROM {l10n_server_line} WHERE fid = %d AND lineno = %d", $fid, $line))) {
        // Location does not exist.
        db_query("INSERT INTO {l10n_server_line} (fid, sid, lineno) VALUES (%d, %d, %d)", $files[$file], $sid, $line);
      }
    }
  }
}

/**
 * Delete the complete contents of a directory recursively.
 *
 * @param $dirname
 *  The directory name to be deleted.
 */
function l10n_server_rmdir_recursive($directory) {
  if (!is_dir($directory)) {
    return;
  }
  if (substr($directory, -1) != '/') {
    $directory .= '/';
  }
  if ($handle = opendir($directory)) {
    while ($file = readdir($handle)) {
        if ($file == '.' or $file == '..') {
          continue;
        }
        $path = $directory .'/'. $file;
        if (is_dir($path)) {
          l10n_server_rmdir_recursive($path);
        }
        else {
          unlink($path);
        }
    }
    rmdir($directory);
    closedir($handle);
  }
}

/**
 * XML Parser object to read Drupal's release history info files.
 * This uses PHP4's lame XML parsing, but it works.
 *
 * Copied from update.module being developed for Drupal 6.
 */
class l10n_server_project_xml_parser {
  var $projects = array();
  var $current_project;
  var $current_release;
  var $current_term;
  var $current_tag;
  var $current_object;

  /**
   * Parse an array of XML data files.
   */
  function parse($data) {
    foreach ($data as $datum) {
      $parser = xml_parser_create();
      xml_set_object($parser, $this);
      xml_set_element_handler($parser, 'start', 'end');
      xml_set_character_data_handler($parser, 'data');
      xml_parse($parser, $datum);
      xml_parser_free($parser);
    }
    return $this->projects;
  }

  function start($parser, $name, $attr) {
    $this->current_tag = $name;
    switch ($name) {
      case 'PROJECT':
        unset($this->current_object);
        $this->current_project = array();
        $this->current_object = &$this->current_project;
        break;
      case 'RELEASE':
        unset($this->current_object);
        $this->current_release = array();
        $this->current_object = &$this->current_release;
        break;
      case 'TERM':
        unset($this->current_object);
        $this->current_term = array();
        $this->current_object = &$this->current_term;
        break;
    }
  }

  function end($parser, $name) {
    switch ($name) {
      case 'PROJECT':
        unset($this->current_object);
        $this->projects[$this->current_project['short_name']] = $this->current_project;
        $this->current_project = array();
        break;
      case 'RELEASE':
        unset($this->current_object);
        $this->current_project['releases'][$this->current_release['version']] = $this->current_release;
        break;
      case 'RELEASES':
        $this->current_object = &$this->current_project;
        break;
      case 'TERM':
        unset($this->current_object);
        $term_name = $this->current_term['name'];
        if (!isset($this->current_release['terms'])) {
          $this->current_release['terms'] = array();
        }
        if (!isset($this->current_release['terms'][$term_name])) {
          $this->current_release['terms'][$term_name] = array();
        }
        $this->current_release['terms'][$term_name][] = $this->current_term['value'];
        break;
      case 'TERMS':
        $this->current_object = &$this->current_release;
        break;
      default:
        $this->current_object[strtolower($this->current_tag)] = trim($this->current_object[strtolower($this->current_tag)]);
        $this->current_tag = '';
    }
  }

  function data($parser, $data) {
    if ($this->current_tag && !in_array($this->current_tag, array('PROJECT', 'RELEASE', 'RELEASES', 'TERM', 'TERMS'))) {
      $tag = strtolower($this->current_tag);
      if (isset($this->current_object[$tag])) {
        $this->current_object[$tag] .= $data;
      }
      else {
        $this->current_object[$tag] = $data;
      }
    }
  }
}
