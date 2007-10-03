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
 *
 * @todo
 *   See whether we can do something about having a sum of all translation
 *   group members. Look into organic groups API for this.
 */
function l10n_drupalorg_welcome_page() {
  $output = '<div id="l10n-community-welcome" class="admin clear-block"><div class="left clear-block">';

  $block = array(
    'title' => t('About this interface'),
    'description' => t('Community for Drupal translations'),
    'content' => t('This interface serves as a community hub for translating Drupal projects, so translators only need to use a browser to contribute. Our system knows about all Drupal projects and their respective releases, meaning that all text added to these packages are translatable. New projects and releases are scanned regularly. Languages are maintained by translation groups. Every group has a community space, where members can share information, discuss specific questions and document best practices. Finally, translations are packaged on drupal.org for each project release.')
  );
  $output .= theme('admin_block', $block);

  //$users = db_result(db_query('SELECT COUNT(*) FROM {users}'));
  //$groups = db_result(db_query('SELECT COUNT(*) FROM {l10n_groups_group}'));
  $projects = db_result(db_query('SELECT COUNT(*) FROM {l10n_community_project}'));
  $files = db_result(db_query('SELECT COUNT(*) FROM {l10n_community_file}'));
  $strings = db_result(db_query('SELECT COUNT(*) FROM {l10n_community_string}'));
  $translations = db_result(db_query('SELECT COUNT(*) FROM {l10n_community_translation} WHERE is_suggestion = 0 AND is_active = 1'));
  $suggestions = db_result(db_query('SELECT COUNT(*) FROM {l10n_community_translation} WHERE is_suggestion = 1 AND is_active = 1'));
  $block = array(
    'title' => t('Quick stats'),
    'description' => t('Some facts about the system'),
    'content' => theme('item_list', array(
      //format_plural($groups, '1 translation group', '@count translation groups'),
      //t('%num registered users', array('%num' => $users)),
      format_plural($projects, '1 project managed', '@count projects managed'),
      format_plural($files, '1 file scanned', '@count files scanned'),
      format_plural($strings, '1 string to translate', '@count strings to translate'),
      format_plural($translations, '1 translation recorded', '@count translations recorded'),
      format_plural($suggestions, '1 suggestion awaiting approval', '@count suggestions awaiting approval'),
    )),
  );
  $output .= theme('admin_block', $block);

  $output .= '</div><div class="right clear-block">';
  // Only display account creation task if not logged in.
  if (!$GLOBALS['user']->uid) {
    $items = array(array('title' => t('Create an account or log in'), 'path' => 'user', 'description' => t('Create an account on the site and log in.')));
  }
  else {
    $items = array();
  }
  $items[] = array('title' => t('Subscribe to groups of your interest'), 'path' => 'og', 'description' => t('Choose any translation groups you would like to participate in, and subscribe.'));
  // Emphasize this list item, so it stands out.
  $items[] = array('title' => '<strong><big>'. t('Translate') .'</big></strong>', 'path' => 'translate/languages', 'description' => t('Pick projects to translate to your language; translate on the web interface or import existing translations.'));
  $items[] = array('title' => t('Export'), 'path' => 'translate/languages', 'description' => t('Translations are automatically packaged on drupal.org, but you can export translations to test them yourself, even if logged out.'));
  $content = '';
  foreach ($items as $item) {
    $content .= '<dt>'. l($item['title'], $item['path'], array(), NULL, NULL, FALSE, TRUE) .'</dt>';
    $content .= '<dd>'. $item['description'] .'</dd>';
  }
  $block = array(
    'title' => t('How to participate?'),
    'description' => t('Where should I start?'),
    'content' => '<dl class="admin-list">'. $content . '</dl>'
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
    'description' => t('Some important pointers'),
    'content' => '<dl class="admin-list">'. $content . '</dl>'
  );
  $output .= theme('admin_block', $block);
  $output .= '</div></div>';
  return $output;
}
