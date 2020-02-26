<?php

/**
 * @file
 *  Drupal.org project database integration for l10n_drupal.
 */

/**
 * Amount of releases parsed in a batch.
 */
define('L10N_DRUPAL_DRUPALORG_BATCH_PARSE_AMOUNT', 50);

/**
 * Page callback for manual scanning.
 */
function l10n_drupal_drupalorg_scan_page() {
  return drupal_get_form('l10n_drupal_drupalorg_batch_parse_form');
}

/**
 * Form to initiate batch parsing of projects to speed up catching-up.
 *
 * @see l10n_drupal_drupalorg_batch_parse_form_submit()
 * @ingroup forms
 */
function l10n_drupal_drupalorg_batch_parse_form($form) {
  return array(
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Parse @count releases in a batch process', array('@count' => L10N_DRUPAL_DRUPALORG_BATCH_PARSE_AMOUNT)),
    ),
  );
}

/**
 * Form submit handler to l10n_drupal_drupalorg_batch_parse_form().
 *
 * @see l10n_drupal_drupalorg_batch_parse_form()
 */
function l10n_drupal_drupalorg_batch_parse_form_submit() {
  // Then pick the oldest releases we did not parse yet.
  $operations = array();
  $result = db_query_range("
    SELECT r.*
    FROM {l10n_server_project} p INNER JOIN
         {l10n_server_release} r ON p.pid = r.pid
    WHERE p.connector_module = 'l10n_project_drupalorg' AND p.status = 1 AND r.download_link != '' AND r.last_parsed = 0 ORDER BY r.file_date ASC",
            0, L10N_DRUPAL_DRUPALORG_BATCH_PARSE_AMOUNT);
  foreach ($result as $release) {
    $operations[] = array('l10n_drupal_drupalorg_batch_operation', array($release));
  }

  $batch = array(
    'operations' => $operations,
    'title' => t('Processing releases'),
    'init_message' => t('First release is being selected'),
    'progress_message' => t('Parsed @current out of @total.'),
    'error_message' => t('Parsing process has encountered an error.'),
    'file' => drupal_get_path('module', 'l10n_drupal') . '/l10n_drupal.drupalorg.inc',
  );
  batch_set($batch);
}

/**
 * Batch operations function for processing a project release.
 */
function l10n_drupal_drupalorg_batch_operation($release, &$context) {
  // Parse the release we were given.
  l10n_drupal_drupalorg_parse_release($release);
}

/**
 * Download and parse the picked release.
 */
function l10n_drupal_drupalorg_parse_release($release) {
  // This could take a long time.
  @set_time_limit(0);

  $error = '';
  $filename = basename($release->download_link);
  $file = file_directory_temp() . '/' . $filename;

  watchdog('l10n_drupal', 'Retrieving @filename for parsing.', array('@filename' => $filename));

  // Check filename for a limited set of allowed chars. Help make sure
  // that all the command line code later is not exploited.
  if (!preg_match('!^([a-zA-Z0-9_.-])+$!', $filename)) {
    $error_arguments = array('%file' => $file);
    $error_t = t('Filename %file contains malicious characters.', $error_arguments);
    $error = 'Filename %file contains malicious characters.';
  }

  // Already downloaded. Probably result of faulty file download left around,
  // so remove file, letting the code come back to this release later.
  elseif (file_exists($file)) {
    $error_arguments = array('%file' => $file);
    $error_t = t('File %file already exists, deleting.', $error_arguments);
    $error = 'File %file already exists, deleting.';
    unlink($file);
  }

  // Download the tar.gz file from drupal.org and save it in the local filesystem.
  elseif (!(($contents = drupal_http_request($release->download_link)) && ($contents->code == 200) && file_put_contents($file, $contents->data))) {
    $error_arguments = array(
      '%download_link' => $release->download_link,
      '%error' => $contents->code . ' ' . $contents->error,
    );
    $error_t = t('Unable to download and save %download_link file (%error).', $error_arguments);
    $error = 'Unable to download and save %download_link file (%error).';
  }

  // Extract and parse the local file and remove the tarball.
  else {
    $return = l10n_drupal_drupalorg_parse_package($file, $release);
    // Clear stats cache, so new data shows up.
    cache_clear_all('l10n:stats', 'cache');
  }

  if ($error) {
    watchdog('l10n_drupal', $error, $error_arguments, WATCHDOG_ERROR);
    $return['error'] = $error_t;
  }
  return $return;
}

/**
 * Parses contents of a specific local package file.
 *
 * @param $package_file
 *   Path to the package file to be extracted and parsed.
 * @param $release
 *   Release object.
 */
function l10n_drupal_drupalorg_parse_package($package_file, $release) {
  $error = $message = '';

  // Potx module is already a dependency.
  module_load_include('inc', 'potx');
  module_load_include('inc', 'l10n_drupal', 'l10n_drupal.files');
  module_load_include('inc', 'l10n_drupal', 'l10n_drupal.potx');
  module_load_include('inc', 'l10n_packager');

  // Set up status messages if not in automated mode.
  potx_status('set', POTX_STATUS_MESSAGE);

  // Generate temp folder to extract the tarball. tempnam() creates a regular
  // file, thus reserving the name. But we need a directory. Because $temp_name
  // is unique, we can append '_dir', and it will still be unique.
  $temp_name = tempnam('/tmp', 'l10n_drupal_drupalorg_');
  $temp_path = $temp_name . '_dir';

  // Nothing to do if the file is not there or the extraction folder is taken.
  if (!file_exists($package_file)) {
    $error_arguments = array('%file' => $package_file);
    $error_t = t('Package to parse (%file) does not exist.', $error_arguments);
    $error = 'Package to parse (%file) does not exist.';
  }
  elseif (is_dir($temp_path)) {
    $error_arguments = array('%path' => $temp_path);
    $error_t = t('Temporary directory %path already exists.', $error_arguments);
    $error = 'Temporary directory %path already exists.';
  }

  // Extract the local file to the temporary directory.
  else {
    if (l10n_drupal_drupalorg_exec('mkdir ' . escapeshellarg($temp_path))) {
      watchdog('l10n_drupal', 'Extracting @filename.', array('@filename' => basename($package_file)));

      // Try extracting the tarball to this directory.
      if (l10n_drupal_drupalorg_exec('tar -xvvzf ' . escapeshellarg($package_file) . ' -C ' . escapeshellarg($temp_path))) {
        watchdog('l10n_drupal', 'Parsing extracted @filename for strings.', array('@filename' => basename($package_file)));

        // Get all source files and save strings with our callback for this release.
        $release->uri = explode('-', basename($package_file))[0];
        l10n_packager_release_set_branch($release);
        if ($release->core === 'all') {
          $version = POTX_API_8;
        }
        else {
          $version = explode('.', $release->core)[0];
        }
        _l10n_drupal_potx_init();
        $files = _potx_explore_dir($temp_path, '*', $version);
        l10n_drupal_save_file(array($release->pid, $release->rid));
        l10n_drupal_added_string_counter(NULL, TRUE);
        foreach ($files as $name) {
          _potx_process_file($name, strlen($temp_path) + 1, 'l10n_drupal_save_string', 'l10n_drupal_save_file', $version);
        }
        potx_finish_processing('l10n_drupal_save_string', $version);

        $sid_count = l10n_drupal_added_string_counter();

        // Delete directory now that parsing is done.
        l10n_drupal_drupalorg_exec('rm -rf ' . escapeshellarg($temp_path));
        unlink($package_file);

        // Record changes of the scanned project in the database.
        $message_arguments = array(
          '@filename' => basename($package_file),
          '@files' => count($files),
          '@sids' => $sid_count,
        );
        $message_t = t('@filename (@files files, @sids strings) scanned.', $message_arguments);
        $message = '@filename (@files files, @sids strings) scanned.';

        // Parsed this releases files.
        db_update('l10n_server_release')
          ->fields(array(
              'sid_count' => $sid_count,
              'last_parsed' => REQUEST_TIME,
            ))
          ->condition('rid', $release->rid)
          ->execute();

        // Update error list for this release. Although the errors are related to
        // files, we are not interested in the fine details, the file names are in
        // the error messages as text. We assume no other messages are added while
        // importing, so we can safely use drupal_get_message() to grab our errors.
        db_delete('l10n_server_error') ->condition('rid', $release->rid) ->execute();
        $messages = drupal_get_messages('error');
        if (isset($messages['error']) && is_array($messages['error'])) {
          foreach ($messages['error'] as $error_message) {
            db_insert('l10n_server_error')
              ->fields(array(
                  'rid' => $release->rid,
                  'value' => $error_message,
                ))
              ->execute();
          }
        }
      }
      else {
        // Delete directory if we did not manage to extract the tarball.
        l10n_drupal_drupalorg_exec('rm -rf ' . escapeshellarg($temp_path));
      }
    }
  }

  // Remove temporary file we used to reserve the name for the directory.
  unlink($temp_name);

  $return = array();
  if ($error) {
    watchdog('l10n_drupal', $error, $error_arguments, WATCHDOG_ERROR);
    $return['error'] = $error_t;
  }
  if ($message) {
    watchdog('l10n_drupal', $message, $message_arguments);
    $return['message'] = $message_t;
  }

  return $return;
}

/**
 * Wrapper for exec() that logs errors to the watchdog.
 *
 * Taken from project/release/package-release-nodes.php.
 *
 * @param $cmd
 *   String of the command to execute (assumed to be safe, the caller is
 *   responsible for calling escapeshellcmd()).
 *
 * @return
 *   TRUE if the command was successful (0 exit status), else FALSE.
 */
function l10n_drupal_drupalorg_exec($cmd) {
  // Make sure we grab stderr, too.
  $output = array();
  exec("$cmd 2>&1", $output, $rval);
  if ($rval) {
    $output = htmlspecialchars(is_array($output) ? implode("\n", $output) : $output);
    watchdog('l10n_drupal', '%cmd failed with status !rval<pre>' . $output . '</pre>', array('%cmd' => $cmd, '!rval' => $rval), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}
