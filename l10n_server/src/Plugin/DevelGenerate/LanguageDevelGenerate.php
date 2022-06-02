<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Devel Generate plugin.
 *
 * @DevelGenerate(
 *   id = "l10n_language",
 *   label = @Translation("Language"),
 *   description = @Translation("Generate a given number of languages. Optionally delete current languages."),
 *   url = "language",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 5,
 *     "kill" = FALSE
 *   }
 * )
 */
class LanguageDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected LanguageManager $languageManager;

  /**
   * Language storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $languageStorage;

  /**
   * The class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      array $plugin_definition,
      LanguageManager $language_manager,
      EntityStorageInterface $entity_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->languageStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('entity_type.manager')->getStorage('configurable_language')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of languages?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing languages before generating new ones.'),
      '#default_value' => $this->getSetting('kill'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateElements(array $values): void {
    if ($values['kill']) {
      $this->deleteLanguages();
      $this->setMessage($this->t('Deleted existing languages.'));
    }

    $new_languages = [];
    $predefined_languages = $this->languageManager
      ->getStandardLanguageListWithoutConfigured();
    for ($i = 0; $i < $values['num']; $i++) {
      $language = ConfigurableLanguage::createFromLangcode(
        array_rand($predefined_languages)
      );
      $language->save();

      $new_languages[] = $language->label();
      unset($predefined_languages[$language->id()]);
    }

    if (!empty($new_languages)) {
      $this->setMessage($this->t('Created the following new languages: @languages', [
        '@languages' => implode(', ', $new_languages),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $values = [
      'num' => array_shift($args),
      'kill' => $options['kill'],
    ];
    if (!$this->isNumber($values['num'])) {
      throw new \Exception(dt('Invalid number of languages: @num.', [
        '@num' => $values['num'],
      ]));
    }
    return $values;
  }

  /**
   * Deletes all languages.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function deleteLanguages(): void {
    $languages = $this->languageStorage->loadMultiple();
    /** @var \Drupal\language\Entity\ConfigurableLanguage $language */
    foreach ($languages as $language) {
      if ($language->isDefault()) {
        unset($languages[$language->id()]);
      }
    }
    $this->languageStorage->delete($languages);
  }

}
