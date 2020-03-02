<?php

/**
 * @file
 *  Drupal.org project database integration for l10n_drupal.
 */

/**
 * Download and parse the picked release.
 */
function l10n_drupal_drupalorg_parse_release($release) {
  $filename = basename($release->download_link);
  $package_file = file_directory_temp() . '/' . $filename;

  watchdog('l10n_drupal', 'Retrieving @filename for parsing.', array('@filename' => $filename));

  // Check filename for a limited set of allowed chars. Help make sure
  // that all the command line code later is not exploited.
  if (!preg_match('!^([a-zA-Z0-9_.-])+$!', $filename)) {
    watchdog('l10n_drupal', 'Filename %file contains malicious characters.', ['%file' => $package_file], WATCHDOG_ERROR);
    return FALSE;
  }

  // Already downloaded. Probably result of faulty file download left around,
  // so remove file, letting the code come back to this release later.
  if (file_exists($package_file)) {
    unlink($package_file);
    watchdog('l10n_drupal', 'File %file already exists, deleting.', ['%file' => $package_file], WATCHDOG_WARNING);
  }

  // Download the tar.gz file from drupal.org and save it in the local filesystem.
  if (!(($contents = drupal_http_request($release->download_link)) && ($contents->code == 200) && file_put_contents($package_file, $contents->data))) {
    watchdog('l10n_drupal', 'Unable to download and save %download_link file (%error).', [
      '%download_link' => $release->download_link,
      '%error' => $contents->code . ' ' . $contents->error,
    ], WATCHDOG_ERROR);
    return FALSE;
  }

  // Potx module is already a dependency.
  module_load_include('inc', 'potx');
  module_load_include('inc', 'l10n_drupal', 'l10n_drupal.files');
  module_load_include('inc', 'l10n_drupal', 'l10n_drupal.potx');
  module_load_include('inc', 'l10n_packager');

  // Set up status messages if not in automated mode.
  potx_status('set', POTX_STATUS_MESSAGE);

  // Generate temp folder to extract the tarball.
  $temp_path = drush_tempdir();

  // Nothing to do if the file is not there or the extraction folder is taken.
  if (!file_exists($package_file)) {
    watchdog('l10n_drupal', 'Package to parse (%file) does not exist.', ['%file' => $package_file], WATCHDOG_ERROR);
    return FALSE;
  }

  // Extract the local file to the temporary directory.
  if (!drush_shell_exec('tar -xvvzf %s -C %s', $package_file, $temp_path)) {
    watchdog('l10n_drupal', 'Failed to extract %file.', ['%file' => $package_file], WATCHDOG_ERROR);
    return FALSE;
  }

  watchdog('l10n_drupal', 'Parsing extracted @filename for strings.', ['@filename' => $filename]);

  // Get all source files and save strings with our callback for this release.
  $release->uri = explode('-', $filename)[0];
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
  drush_shell_exec('rm -rf %s', $temp_path);
  unlink($package_file);

  // Record changes of the scanned project in the database.
  watchdog('l10n_drupal', '@filename (@files files, @sids strings) scanned.', [
    '@filename' => $filename,
    '@files' => count($files),
    '@sids' => $sid_count,
  ]);

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

  // Clear stats cache, so new data shows up.
  cache_clear_all('l10n:stats', 'cache');

  return TRUE;
}
