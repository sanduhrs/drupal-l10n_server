<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\l10n_server\Entity\L10nServerString;

/**
 * Provides a Devel Generate plugin.
 *
 * @DevelGenerate(
 *   id = "l10n_server_translation",
 *   label = @Translation("Translation"),
 *   description = @Translation("Generate a given number of translations. Optionally delete current translations."),
 *   url = "translation",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 5,
 *     "kill" = FALSE
 *   }
 * )
 */
class L10nServerTranslationDevelGenerate extends L10nServerDevelGenerateBase {

  const ENTITY_TYPE = 'l10n_server_translation';

  /**
   * {@inheritdoc}
   */
  public function generateElements(array $values): void {
    if (!$string = $this->getRandomReference('l10n_server_string')) {
      $this->messenger()->addError('Please import some l10n_project_string entities first.');
      return;
    }
    parent::generateElements($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity(): EntityInterface {
    $sid = $this->getRandomReference('l10n_server_string');
    $string = L10nServerString::load($sid);
    $time_entered = rand(0, time());
    $time_changed = rand($time_entered, time());
    return $this->entityStorage->create([
      'sid' => $this->getRandomReference('l10n_server_string'),
      'language' => $this->getRandomLanguage()->getId(),
      'translation' => $this->randomSentenceOfLength(
        mb_strlen($string->get('value')->first()->getValue()['value'])
      ),
      'uid_entered' => $this->getRandomReference('user'),
      'time_entered' => $time_entered,
      'time_changed' => $time_changed,
      'is_suggestion' => rand(0, 1),
      'is_active' => rand(0, 1),
    ]);
  }

}
