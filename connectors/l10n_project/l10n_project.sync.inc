<?php
// $Id$

/**
 * @file
 *   Project data updates for l10n_project.
 */
 
/**
 * The term ID for translations on Drupal.org.
 */
define('L10N_PROJECT_TRANSLATION_TID', 29); 

/**
 * Syncronize the project list from the drupal.org database.
 */
function l10n_project_sync() {
    
  /*include_once 'includes/locale.inc';
  list($plurals, $formula) = _locale_import_parse_plural_forms('nplurals=2; plural=(n!=1);', 'automatic install of test');
  db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", $plurals, $formula, 'test');
  return;
      
  db_query('DELETE FROM l10n_community_project');
  db_query('DELETE FROM l10n_community_release');*/
  
  // settings.php has a $db_url['projects'] entry, which gives us access to
  // the projects database.
  db_set_active('projects');
  
  // Got the limited list of translation projects first to exclude from the
  // upcoming query. We cannot join on the term_node table there, since we
  // can have nodes with multiple terms, so just adding 
  // tid != L10N_PROJECT_TRANSLATION_TID would not help, the project would 
  // still match on other terms. 
  $result = db_query(
    "SELECT DISTINCT pp.nid 
     FROM project_projects pp INNER JOIN 
          term_node tn ON tn.nid = pp.nid
     WHERE tn.tid = %d", L10N_PROJECT_TRANSLATION_TID
  );
  $exclude_projects = array();
  while ($project = db_fetch_object($result)) {
    // Cast to int to make sure we are secure.
    $exclude_projects[] = (int) $project->nid;  
  }

  // Get all projects - published, unpublished equally, so we can update project
  // status on our copy for projects which were previously published. Only get
  // projects however, where there were any releases. We only parse releases,
  // so there is no point to have data from other projects. Also, drupal.org
  // has some pseudo-projects for the webmasters or infrastructure team to 
  // maintain issues queues, which are also only identifiable by looking at
  // the list of releases. Don't use {} wrapping in query, since it is a 
  // different DB and it does not use prefixes anyway.
  $result = db_query(
    "SELECT DISTINCT pp.nid, pp.uri, n.title, n.status 
     FROM project_projects pp INNER JOIN 
          project_release_nodes prn ON pp.nid = prn.pid LEFT JOIN
          node n ON pp.nid = n.nid
     WHERE pp.nid NOT IN (". join(', ', $exclude_projects) .")
     ORDER BY pp.nid"
  );
  
  // Fill in an array, so we can go back to our database and add in these
  // details.
  $projects = array();
  while ($project = db_fetch_object($result)) {
    $projects[] = $project;
  }
  
  // Switch back to our local database.
  db_set_active();
  
  // Record all projects in our local database.
  foreach ($projects as $project) {
    if ($existing_project = db_fetch_object(db_query("SELECT * FROM {l10n_community_project} WHERE uri = '%s'", $project->uri))) {
      if ($existing_project->connector_module == 'l10n_project') {
        if (($project->title != $existing_project->title) || 
            ($project->status != $existing_project->status)) {
          // Update if title or status is different (uri should not change,
          // other data is internal to our database).
          db_query("UPDATE {l10n_community_project} SET title = '%s', status = %d, last_parsed = %d WHERE uri = '%s' AND connector_module = 'l10n_project'", $project->title, $project->status, time(), $project->uri);
        }
      }
      else {
        // Log error on existing project with another connector and skip the
        // rest of this function.
        $t_args = array('%uri' => $uri, '%other_connector' => $existing_project->connector_module, '%this_connector' => 'l10n_project');
        watchdog('l10n_drupalorg', 'An existing project under the URI %uri is already handled by the %other_connector module. Not possible to add it with %this_connector.', $t_args);
        drupal_set_message(t('An existing project under the URI %uri is already handled by the %other_connector module. Not possible to add it with %this_connector.', $t_args), 'error');
        continue;
      }
    }
    else {
      // New project, not recorded before.
      db_query(
        "INSERT INTO {l10n_community_project} (uri, title, last_parsed, home_link, connector_module, status) VALUES ('%s', '%s', %d, '%s', '%s', %d)",
        $project->uri, $project->title, time(), 'http://drupal.org/project/'. $project->uri, 'l10n_project', $project->status
      );
    }
  }
  
  // Go back to projects database for releases.
  db_set_active('projects');

  // Get a list of all releases for all projects ever released.
  // @todo: actually, we should only get the latest releases for all projects
  // in all branches  
  $result = db_query("
    SELECT prn.nid, prn.version, f.filepath, f.timestamp, prf.filehash, nn.status
    FROM project_projects pp INNER JOIN 
         project_release_nodes prn ON pp.nid = prn.pid LEFT JOIN 
         node n ON pp.nid = n.nid INNER JOIN
         project_release_file prf ON prn.nid = prf.nid INNER JOIN
         files f ON prf.fid = f.fid INNER JOIN
         node nn ON prn.nid = nn.nid"
  );
  
  // Fill in an array, so we can go back to our database and add in these
  // details.
  $releases = array();
  while ($relase = db_fetch_object($result)) {
    $releases[] = $release;
  }
  
  // Switch back to our local database.
  db_set_active();
  
  // Record all releases in our local database.
  foreach ($releases as $release) {
    $download_link = 'http://ftp.drupal.org/' . $release->filepath;
    if ($existing_release = db_fetch_object(db_query("SELECT * FROM {l10n_community_release} WHERE download_link = '%s'", $download_link))) {
      // @todo: what happens with our release data, when file changes?
      //        need a "reindex" column or something like that
      // @todo: what happens to unpublished releases? drop data outright? 
    }
    elseif (!empty($release->status)) {
      // New published release, not recorded before.
      db_query(
        "INSERT INTO {l10n_community_release} (pid, title, download_link, file_date, file_hash, last_parsed) VALUES (%d, '%s', '%s', %d, '%s', %d)",
        $release->nid, $release->version, $download_link, $release->timestamp, $release->filehash, 0
      );
    }
  }

}
