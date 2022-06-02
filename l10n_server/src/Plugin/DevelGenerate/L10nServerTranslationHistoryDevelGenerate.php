<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\l10n_server\Entity\L10nServerTranslationHistory;

/**
 * Provides a Devel Generate plugin.
 *
 * @DevelGenerate(
 *   id = "l10n_server_translation_history",
 *   label = @Translation("History"),
 *   description = @Translation("Generate a given number of histories. Optionally delete current histories."),
 *   url = "history",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 5,
 *     "kill" = FALSE
 *   }
 * )
 */
class L10nServerTranslationHistoryDevelGenerate extends L10nServerDevelGenerateBase {

  const ENTITY_TYPE = 'l10n_server_translation_history';

  const ACTION_TYPES = [
    L10nServerTranslationHistory::ACTION_ADD,
    L10nServerTranslationHistory::ACTION_APPROVE,
    L10nServerTranslationHistory::ACTION_DECLINE,
    L10nServerTranslationHistory::ACTION_DEMOTE,
    L10nServerTranslationHistory::ACTION_READD,
  ];

  const MEDIUM_TYPES = [
    L10nServerTranslationHistory::MEDIUM_UNKNOWN,
    L10nServerTranslationHistory::MEDIUM_WEB,
    L10nServerTranslationHistory::MEDIUM_IMPORT,
    L10nServerTranslationHistory::MEDIUM_REMOTE,
  ];

  /**
   * {@inheritdoc}
   */
  protected function createEntity(): EntityInterface {
    return $this->entityStorage->create([
      'uid_action' => $this->getRandomReference('user'),
      'time_action' => rand(0, time()),
      'type_action' => static::ACTION_TYPES[array_rand(static::ACTION_TYPES)],
      'medium_action' => static::MEDIUM_TYPES[array_rand(static::MEDIUM_TYPES)],
    ]);
  }

}
