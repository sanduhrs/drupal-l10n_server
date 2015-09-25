<?php

/**
 * @file
 *   Development helpers for localization server.
 */

// == Language generation ======================================================

/**
 * User interface to generate a number of languages.
 */
function l10n_server_generate_languages_form($form, &$form_state) {
  include_once DRUPAL_ROOT . '/' . 'includes/iso.inc';
  $predefined = _locale_get_predefined_list();
  $form['num'] = array(
    '#type' => 'textfield',
    '#title' => t('How many languages would you like to generate (maximum @count possible)?', array('@count' => count($predefined))),
    '#default_value' => 30,
    '#size' => 10,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Do it!'),
  );
  return $form;
}

/**
 * Submission function to generate a number of languages.
 */
function l10n_server_generate_languages_form_submit($form_id, &$form_state) {
  include_once DRUPAL_ROOT . '/' . 'includes/iso.inc';
  db_delete('languages') ->condition('language', 'en', '<>') ->execute();
  db_delete('locales_target') ->execute();
  drupal_set_message(t('Languages deleted.'));
  $num = $form_state['values']['num'];
  if ($num > 0) {
    $predefined = _locale_get_predefined_list();
    if ($num > count($predefined)) {
      $languages = $predefined;
    }
    else {
      $languages = array();
      while (count($languages) < $num) {
        $key = array_rand($predefined);
        $languages[$key] = $predefined[$key];
        unset($predefined[$key]);
      }
    }

    $formulas = l10n_server_plural_formulas();
    $default_formula = $default = 'nplurals=2; plural=(n!=1);';
    foreach ($languages as $langcode => $language) {
      $plural_formula = (isset($formulas['langcode']) ? $formulas['langcode'] : $default_formula);
      // We know we get a valid formula here so no need for error handling.
      list($plurals, $formula) = l10n_server_parse_plural_formula($plural_formula);
      db_insert('languages')
	->fields(array(
            'language' => $langcode,
            'name' => $language[0],
            'native' => isset($language[1]) ? $language[1] : $language[0],
            'direction' => isset($language[2]) ? $language[2] : LANGUAGE_LTR,
            'enabled' => 1,
            'plurals' => $plurals,
            'formula' => $formula,
            'domain' => '',
            'prefix' => $langcode,
            'weight' => 0,
            'javascript' => '',
          ))
	->execute();
    }
    drupal_set_message(format_plural($num, '1 language created.', '@count languages created.'));
  }
}

// == Translation / suggestion generation ======================================

/**
 * User interface to generate translations and languages.
 */
function l10n_server_generate_translations_form($form, &$form_state) {
  $count = db_query('SELECT COUNT(*) FROM {l10n_server_string}')->fetchField();
  $limit = (int) $count * 0.75;
  if (empty($count)) {
    drupal_set_message(t('No source strings found, cannot generate translations and suggestions.'));
  }
  else {
    $form['limit'] = array(
      '#type' => 'textfield',
      '#title' => "Number of translations to generate per language",
      '#default_value' => (int) $limit,
      '#element_validate' => array('element_validate_integer_positive'),
    );
    $form['delete_existing'] = array(
      '#type' => 'checkbox',
      '#title' => "Delete all existing translations",
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Do it!'),
    );
    return $form;
  }
}

/**
 * Submission function to generate translations and suggestions.
 *
 * @todo Add suggestion generation.
 */
function l10n_server_generate_translations_form_submit($form_id, &$form_state) {
  if ($form_state['values']['delete_existing']) {
    db_delete('l10n_server_translation')->execute();
    db_delete('l10n_server_status_flag')->execute();
    drupal_set_message(t('Previous translations and suggestions deleted.'));
  }

  $limit = (int) $form_state['values']['limit'];
  $languages = language_list();
  foreach ($languages as $language) {
    if ($language->language != 'en') {
      $uid = db_select('users', 'u')
        ->fields('u', array('uid'))
        ->orderBy('RAND()')
        ->execute()
        ->fetchField();

      $query = db_select('l10n_server_string', 's')->range(0, $limit);
      $query->addExpression('sid', 'sid');
      $query->addExpression(':lng', 'language', array(':lng' => $language->language));
      $query->addExpression('value', 'translation');
      $query->addExpression($uid, 'uid_entered');
      $query->addExpression(REQUEST_TIME, 'time_entered');
      $query->addExpression(1, 'is_suggestion');
      $query->addExpression(1, 'is_active');
      $query->addExpression(REQUEST_TIME, 'time_changed');
      db_insert('l10n_server_translation')
        ->from($query)
        ->execute();

      $query = db_select('l10n_server_string', 's')->range(0, $limit);
      $query->addExpression('sid', 'sid');
      $query->addExpression(':lng', 'language', array(':lng' => $language->language));
      $query->addExpression(1, 'has_suggestion');
      $query->addExpression(1, 'has_translation');
      db_insert('l10n_server_status_flag')
        ->from($query)
        ->execute();
    }
  }
  drupal_set_message(format_plural($limit * count($languages), '1 translation created.', '@count translations created.'));
}
