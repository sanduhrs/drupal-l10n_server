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
  $file_path = file_directory_temp() . '/releases.tsv';
  $url = variable_get('l10n_drupal_rest_refresh_url', 'https://drupal.org/files/releases.tsv');
  // This will take some time, so we need to increase timeout.
  $response = drupal_http_request($url, array(), 'GET', NULL, 3, 300);
  if ($response->code == 200) {
    // Save as temporary file and release the memory.
    file_put_contents($file_path, $response->data);
    unset($response);
    _l10n_drupal_rest_read_tsv($file_path, $before, $projects, $releases);
    // Remove file
    unlink($file_path);
  }
  else {
    watchdog($connector_name, 'Releases URL %url is unreacheable.', array(
      '%url' => $url,
    ));
    return;
  }

  // Record all non-existing projects in our local database.
  foreach ($projects as $project_name => $project_title) {
    if (($existing_project = db_fetch_object(db_query("SELECT * FROM {l10n_server_project} WHERE uri = '%s'", $project_name)))) {
      // Check that the title is correct
      if ($existing_project->title != $project_title) {
        db_query("UPDATE {l10n_server_project} SET title = '%s' WHERE uri = '%s'", $project_title, $project_name);
        watchdog($connector_name, 'Project %n renamed to %t.', array(
          '%t' => $project_title,
          '%n' => $project_name,
        ));
      }
    }
    else {
      $project_count++;
      // @TODO Grab titles and statuses from somewhere.
      db_query("INSERT INTO {l10n_server_project} (uri, title, last_parsed, home_link, connector_module, status) VALUES ('%s', '%s', %d, '%s', '%s', %d)", $project_name, $project_title, time(), 'http://drupal.org/project/' . $project_name, $connector_name, 1);
    }
  }

  // Record all releases in our local database.
  foreach ($releases as $release) {
    $download_link = "http://ftp.drupal.org/files/projects/{$release['machine_name']}-{$release['version']}.tar.gz";
    if ($existing_release = db_fetch_object(db_query("SELECT * FROM {l10n_server_release} WHERE download_link = '%s'", $download_link))) {
      // @TODO What happens to unpublished releases? drop data outright?
    }
    else {
      $release_count++;
      // Get the project pid
      $pid = db_result(db_query("SELECT pid FROM {l10n_server_project} WHERE uri = '%s'", $release['machine_name']));

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
  $headers = array();
  if (($handle = fopen($file_path, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
      // Get headers
      if (empty($headers)) {
        $headers = array_flip($data);
        continue;
      }
      // Filter out sandboxes.
      if (is_numeric($data[$headers['project_machine_name']])) {
        continue;
      }
      $time = strtotime($data[$headers['created']]);
      if ($before < $time) {
        $machine_name = trim($data[$headers['project_machine_name']]);
        $title = trim($data[$headers['project_name']]);
        // A first array for projects.
        $projects[$machine_name] = $title;
        // A second array for releases.
        $releases[] = array(
          'created'      => $time,
          'machine_name' => $machine_name,
          'title'        => $title,
          'version'      => $data[$headers['version']],
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
