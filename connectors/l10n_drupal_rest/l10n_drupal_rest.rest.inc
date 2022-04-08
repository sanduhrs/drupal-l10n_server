<?php

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
      // Filter out sandboxes and malformed releases.
      if (count($data) < 4 || is_numeric($data[$headers['project_machine_name']])) {
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
          'created' => $time,
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
