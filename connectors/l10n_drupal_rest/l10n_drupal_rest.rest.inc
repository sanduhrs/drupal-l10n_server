<?php

/**
 * Syncronize the project list.
 */
function l10n_drupal_rest_refresh_project_list() {
  $connector_name = 'l10n_drupal_rest_restapi';
  $projects = $releases = array();
  $project_count = $release_count = 0;

  // Only sync releases which are at most one day older then our last
  // sync date. This ensures time zone issues and releases published while the
  // previous cron run will not be a problem, but we only look at a relatively
  // small list of releases at any given time. We only sync tagged releases,
  // which will not get rebuilt later anytime.
  $last_sync = variable_get('l10n_drupal_rest_last_sync', 0);
  $before = $last_sync - 60 * 60 * 24;

  // Fetch projects and releases since last sync.
  $file_path = file_directory_path() . '/releases.tsv';
  $url = variable_get('l10n_drupal_rest_refresh_url', 'https://drupal.org/files/releases.tsv');
  // This will take some time, so we need to increase timeout.
  $response = drupal_http_request($url, array(), 'GET', NULL, 3, 300);
  if ($response->code == 200) {
    // Save as temporary file and release the memory.
    file_save_data($response->data, $file_path, FILE_EXISTS_REPLACE);
    unset($response);
    _l10n_drupal_rest_read_tsv($file_path, $before, $projects, $releases);
    // Remove file
    file_delete($file_path);
  }
  else {
    watchdog($connector_name, 'Releases URL %url is unreacheable.', array(
      '%url' => $url,
    ));
    return;
  }

  // Record all non-existing projects in our local database.
  foreach (array_keys($projects) as $project) {
    if (($existing_project = db_fetch_object(db_query("SELECT * FROM {l10n_server_project} WHERE uri = '%s'", $project))) === FALSE) {
      $project_count++;
      // @TODO Grab titles and statuses from somewhere.
      db_query("INSERT INTO {l10n_server_project} (uri, title, last_parsed, home_link, connector_module, status) VALUES ('%s', '%s', %d, '%s', '%s', %d)", $project, $project, time(), 'http://drupal.org/project/' . $project, $connector_name, 1);
    }
  }

  // Record all releases in our local database.
  foreach ($releases as $release) {
    $download_link = "http://ftp.drupal.org/files/projects/{$release['project']}-{$release['version']}.tar.gz";
    if ($existing_release = db_fetch_object(db_query("SELECT * FROM {l10n_server_release} WHERE download_link = '%s'", $download_link))) {
      // @TODO What happens to unpublished releases? drop data outright?
    }
    else {
      $release_count++;
      // Get the project pid
      $pid = db_result(db_query("SELECT pid FROM {l10n_server_project} WHERE uri = '%s'", $release['project']));

      // @TODO What about filehash?
      $filehash = '';
      // New published release, not recorded before.
      db_query("INSERT INTO {l10n_server_release} (pid, title, download_link, file_date, file_hash, last_parsed) VALUES (%d, '%s', '%s', %d, '%s', %d)", $pid, $release['version'], $download_link, $release['created'],
        $filehash, 0);
    }
  }

  // Report some informations.
  if ($release_count || $project_count) {
    watchdog($connector_name, 'Fetched info about %p projects and %r releases.',
      array(
        '%p' => $project_count,
        '%r' => $release_count,
      ));
  }
  else {
    watchdog($connector_name, 'No new info about projects and releases.');
  }

  // Set last sync time to limit number of releases to look at next time.
  variable_set('l10n_drupal_rest_last_sync', time());
}

/**
 * Parse the release file for projects and releases newer than before
 *
 * @param $file_path
 * @param $before
 * @param $projects
 * @param $releases
 * @return mixed
 */
function _l10n_drupal_rest_read_tsv($file_path, $before, &$projects, &$releases) {
  $skip = 1;
  if (($handle = fopen($file_path, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
      // Filter out headers and sandboxes.
      if ($skip-- > 0 || is_numeric($data[1])) {
        continue;
      }
      $time = strtotime($data[0]);
      if ($before < $time) {
        // A first array for projects.
        $projects[trim($data[1])] = TRUE;
        // A second array for releases.
        $releases[] = array(
          'created' => $time,
          'project' => trim($data[1]),
          'version' => $data[2],
        );
      }
      else {
        fclose($handle);
        return TRUE;
      }
    }
  }
  return FALSE;
}
