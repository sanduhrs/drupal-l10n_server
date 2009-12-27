<?php
// $Id$

/**
 * @file
 *   Introduction page for an l10n_community setup.
 */

/**
 * Introduction page for an l10n_community setup.
 *
 * All string are in PHP source code, so translators can translate
 * them to their own language, without resorting to i18n module or
 * some other solution for node translation.
 *
 * This page is based on the admin page layout from system.module,
 * so we are using classes and concepts from there. This might not
 * fit perfectly with all listings, so I bended some stuff more to comply.
 */
function l10n_community_welcome_page() {
  $output = '<div id="l10n-community-welcome" class="admin clear-block"><div class="left clear-block">';

  $block = array(
    'title' => t('About this interface'),
    'description' => t('Community for Drupal translations'),
    'content' => '<p class="info">'. t('This interface serves as a community hub for translating Drupal projects, so translators only need to use a browser to contribute. Our system knows about Drupal projects, such as modules, themes and install profiles; with their respective releases. This means that all text added to these packages are translatable. New projects and releases are scanned regularly. Languages are maintained by translation groups. Every group has a community space, where members can share information, discuss specific questions and document best practices. Finally, translations are packaged on drupal.org for each project release.') .'</p>'
  );
  $output .= theme('admin_block', $block);

  $stats_block = l10n_community_block_stats();
  $block = array(
    'title' => t('Quick stats'),
    'description' => t('Some facts about this community'),
    'content' => $stats_block['content'],
  );
  $output .= theme('admin_block', $block);

  $items = array(
    array('title' => t('Translator\'s guide'), 'path' => 'http://drupal.org/translators', 'description' => t('Documentation for translators on drupal.org.')),
    array('title' => t('Translations group'), 'path' => 'http://groups.drupal.org/translations', 'description' => t('Translations group at groups.drupal.org.')),
    array('title' => t('Mailing list'), 'path' => 'http://lists.drupal.org/listinfo/translations', 'description' => t('Translators mailing list hosted at drupal.org.')),
  );
  $content = '';
  foreach ($items as $item) {
    $content .= '<dt>'. l($item['title'], $item['path']) .'</dt>';
    $content .= '<dd>'. $item['description'] .'</dd>';
  }
  $block = array(
    'title' => t('Handy links'),
    'description' => t('Documentation, discussion spaces'),
    'content' => '<dl class="admin-list">'. $content .'</dl>'
  );
  $output .= theme('admin_block', $block);

  $output .= '</div><div class="right clear-block">';
  $content = '';
  if (!$GLOBALS['user']->uid) {
    // Only display account creation task if not logged in.
    $content = '<p>'. t('You can review the work being done here, but to participate, first you need to <a href="@register">create an account and/or log in</a>.', array('@register' => url('user/login'))) .'</p>';
  }

  $content .= drupal_get_form('l10n_community_pick_go') .'<div class="clearing"></div>';

  $block = array(
    'title' => t('Contribute'),
    'description' => t('Suggest, translate, review'),
    'content' => '<div class="admin-list">'. $content .'</div>'
  );
  $output .= str_replace('class="admin-panel"', 'class="admin-panel admin-panel-contribute"', theme('admin_block', $block));

  if ($project = l10n_community_get_highlighted_project()) {
    // Display progress status of the highlighted project.
    include_once drupal_get_path('module', 'l10n_community') .'/pages.inc';
    $output .= l10n_community_language_progress_for_project($project, l10n_community_get_languages(), t('Translation status for %project', array('%project' => $project->title)), t('Highlighted project'));
  }

  $output .= '</div></div>';
  return $output;
}

function l10n_community_pick_go() {
  $form = array();
  $languages = l10n_community_get_languages();
  $projects = l10n_community_get_projects();

  // Drop languages without a plural formula from the options.
  $language_list = array();
  foreach ($languages as $langcode => $language) {
    if (!empty($language->plurals)) {
      $language_list[$langcode] = $language->name;
    }
  }

  if (!count($language_list) || !count($projects)) {
    $form['dummy'] = array('#type' => 'markup', '#value' => t('No languages with plural formulas defined or no projects scanned yet.'));
    return $form;
  }

  if (count($language_list) > 1) {
    // If more then one language.
    $form['langcode'] = array(
      // For three or less languages, display radio buttons, select otherwise.
      '#type' => (count($language_list) <= 3) ? 'radios' : 'select',
      '#title' => t('Pick a language'),
      '#options' => array('' => t('All')) + $language_list,
      '#description' => t('Alternatively you can <a href="@explore">explore languages</a>.', array('@explore' => url('translate/languages')))
    );
  }
  else {
    // Only one language, store as form value.
    list($language_code, $language_name) = each($language_list);
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $language_code
    );
  }

  if (count($language_list) > 1) {
    $projects_title = user_access('browse translations') ? t('And/or pick a project') : t('Or pick a project');
  }
  else {
    $projects_title = t('Pick a project for @language translation', array('@language' => $language_name));
  }
  $form['project'] = array(
    '#title' => $projects_title,
    '#description' => t('Alternatively you can <a href="@explore">explore projects</a>.', array('@explore' => url('translate/projects')))
  );
  if (($count = count($projects)) <= 30) {
    // Radio box widget for as much as 5 projects, select widget for 5-30 projects.
    $form['project']['#type'] = ($count <= 5 ? 'radios' : 'select');
    $form['project']['#options'] = array('' => t('All'));
    foreach ($projects as $project) {
      // title used to conform to the autocomplete behavior.
      $form['project']['#options'][$project->title] = $project->title;
    }
  }
  else {
    // Autocomplete field for more then 30 projects.
    $form['project'] += array(
      '#type' => 'textfield',
      '#autocomplete_path' => 'translate/projects/autocomplete',
    );
  }

  // Either we have languages or projects, so we can jump.
  $form['submit'] = array(
    '#type' => 'submit',
    '#prefix' => '<div class="clearing"></div>',
    '#value' => t('Choose'),
  );
  return $form;
}

function l10n_community_pick_go_validate($form, &$form_state) {
  if (empty($form_state['values']['project']) && empty($form_state['values']['langcode'])) {
    // We require at least one to be selected to be effective.
    form_set_error('langcode', t('At least select a language or a project.'));
  }
}

/**
 * Submission handler for l10n_community_pick_go().
 *
 * Redirects the user to the selected project page with language selection
 * if both are provided, or to the project page with project only or the
 * language page with language only.
 */
function l10n_community_pick_go_submit($form, &$form_state) {
  if (!empty($form_state['values']['project']) && ($uri = l10n_community_project_uri_by_title($form_state['values']['project']))) {
    // Project surely selected, possibly with language too.
    if (!empty($form_state['values']['langcode'])) {
      if (user_access('browse translations')) {
        $action = user_access('submit suggestions') ? 'edit' : 'view';
        drupal_goto('translate/languages/'. $form_state['values']['langcode'] .'/'. $action, 'project='. $uri);
      }
      else {
        // Both language and project set, no access to browse > go to project.
        drupal_goto('translate/projects/'. $uri);
      }
    }
    else {
      // Only project set.
      drupal_goto('translate/projects/'. $uri);
    }
  }
  elseif (!empty($form_state['values']['langcode'])) {
    // Only language set.
    drupal_goto('translate/languages/'. $form_state['values']['langcode']);
  }
}
