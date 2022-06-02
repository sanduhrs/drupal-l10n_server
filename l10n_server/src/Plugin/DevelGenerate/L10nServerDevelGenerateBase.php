<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Devel Generate Base plugin.
 */
abstract class L10nServerDevelGenerateBase extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  const ENTITY_TYPE = '';

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $entityStorage;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityStorageInterface $entity_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage(static::ENTITY_TYPE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of entities?'),
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
      $this->deleteEntities();
      $this->setMessage($this->t('Deleted existing entities.'));
    }

    $new_entities = [];
    for ($i = 0; $i < $values['num']; $i++) {
      $entity = $this->createEntity();
      $entity->save();
      $new_entities[] = $entity->label();
    }

    if (!empty($new_entities)) {
      $this->setMessage($this->t('Created the following new entities: @entities', [
        '@entities' => implode(', ', $new_entities),
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
      throw new \Exception(dt('Invalid number of entities: @num.', [
        '@num' => $values['num'],
      ]));
    }
    return $values;
  }

  /**
   * Deletes all entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function deleteEntities(): void {
    $this->entityStorage->delete(
      $this->entityStorage->loadMultiple()
    );
  }

  /**
   * Gets a random entity reference id by entity type.
   *
   * @param string $entity_type
   *   The entity type string.
   * @param array $properties
   *   The properties to filter by.
   *
   * @return int|string
   *   An entity id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getRandomReference(string $entity_type, array $properties = []): int|string {
    $storage = $this->entityTypeManager->getStorage($entity_type);

    if ($properties) {
      $entity_ids = $storage->loadByProperties($properties);
    }
    else {
      $entity_ids = $storage->loadMultiple();
    }

    if ($entity_ids) {
      shuffle($entity_ids);
      $entity_id = reset($entity_ids);
      return $entity_id->id();
    }
    return 0;
  }

  /**
   * Gets a random language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   A language object.
   */
  protected function getRandomLanguage(): LanguageInterface {
    $languages = \Drupal::languageManager()->getLanguages();
    $default_language = \Drupal::languageManager()->getDefaultLanguage();
    unset($languages[$default_language->getId()]);
    return $languages[array_rand($languages)];
  }

  /**
   * Creates an entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An entity object.
   */
  abstract protected function createEntity(): EntityInterface;

}
