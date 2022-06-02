<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a Devel Generate plugin.
 *
 * @DevelGenerate(
 *   id = "l10n_server_status_flag",
 *   label = @Translation("Status flag"),
 *   description = @Translation("Generate a given number of status flags. Optionally delete current status flags."),
 *   url = "status-flag",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 5,
 *     "kill" = FALSE
 *   }
 * )
 */
class L10nServerStatusFlagDevelGenerate extends L10nServerDevelGenerateBase {

  const ENTITY_TYPE = 'l10n_server_status_flag';

  /**
   * {@inheritdoc}
   */
  protected function createEntity(): EntityInterface {
    return $this->entityStorage->create([
      'language' => $this->getRandomLanguage()->getId(),
      'has_suggestion' => rand(0, 1),
      'has_translation' => rand(0, 1),
    ]);
  }

}
