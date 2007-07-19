<?php
// $Id$

/**
 * @file
 *   Introduction page for the l10n_community site.
 *
 *   All string are in PHP source code, so translators can translate
 *   them to their own language, without resorting to i18n module or
 *   some other solution for node translation.
 */

$output = '<div id="l10n-community-home" class="admin clear-block"><div class="left clear-block">';

$block = array(
  'title' => t('About this site'),
  'description' => t('Community for Drupal translations'),
  'content' => t('This web site serves as a community interface for translating Drupal projects, so translators only need to use a browser to contribute. Our system knows about all Drupal projects and their respective releases, so all text added to these packages are translatable. New projects and releases are scanned regularly. Languages are maintained by translation groups. Every group has a community space, where members can share information, discuss specific questions and document best practices. Finally, translations are packaged on drupal.org for each project release.')
);
$output .= theme('admin_block', $block);

$users = db_result(db_query('SELECT COUNT(*) FROM {users}'));
$groups = db_result(db_query('SELECT COUNT(*) FROM {l10n_community_group}'));
$projects = db_result(db_query('SELECT COUNT(*) FROM {l10n_server_project}'));
$files = db_result(db_query('SELECT COUNT(*) FROM {l10n_server_file}'));
$strings = db_result(db_query('SELECT COUNT(*) FROM {l10n_server_string}'));
$block = array(
  'title' => t('Quick stats'),
  'description' => t('Some facts about this web site'),
  'content' => theme('item_list', array(
    t('%num translation groups', array('%num' => $groups)),
    t('%num registered users', array('%num' => $users)),
    t('%num projects managed', array('%num' => $projects)),
    t('%num files scanned', array('%num' => $files)),
    t('%num strings to translate', array('%num' => $strings)),
  )),
);
$output .= theme('admin_block', $block);

$output .= '</div><div class="right clear-block">';
// Only display account task if not logged in.
if (!$GLOBALS['user']->uid) {
  $items = array(array('title' => t('Create an account or log in'), 'path' => 'user', 'description' => t('Create an account on the site and log in.')));
}
else {
  $items = array();
}
$items[] = array('title' => t('Subscribe to groups of your interest'), 'path' => 'og', 'description' => t('Choose any translation groups you would like to participate in, and subscribe.'));
  // Emphasize this list item, so it stands out.
$items[] = array('title' => '<strong><big>'. t('Translate') .'</big></strong>', 'path' => 'translations', 'description' => t('Pick projects to translate to your language; translate on the web interface or import existing translations.'));
$items[] = array('title' => t('Export'), 'path' => 'translations', 'description' => t('Translations are automatically packaged on drupal.org, but you can export translations to test them yourself, even if logged out.'));
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

