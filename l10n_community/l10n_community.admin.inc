<?php
// $Id$

/**
 * @file
 *   Administrative functions for the Localization Server.
 */

/**
 * This is the same admin block display function as in system.module.
 *
 * We need to write this wrapper code so that the right include file is
 * loaded to run that function.
 */
function l10n_community_settings_overview() {
  include_once drupal_get_path('module', 'system') .'/system.admin.inc';
  return system_admin_menu_block_page();
}

/**
 * Settings form callback.
 */
function l10n_community_settings_form() {
  $form = array();
  $form['l10n_community_project_per_page'] = array(
    '#title' => t('Number of projects to display per page'),
    '#description' => t('Number of projects displayed per page in overviews. It is not advised to change this setting on live sites, as it changes what is displayed on each page, so links will break to existing project listings.'),
    '#type' => 'select',
    '#options' => drupal_map_assoc(array(4, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40)),
    '#default_value' => variable_get('l10n_community_project_per_page', 10),
  );
  return system_settings_form($form);
}
