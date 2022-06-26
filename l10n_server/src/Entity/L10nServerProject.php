<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\l10n_server\ConnectorInterface;

/**
 * Defines the project entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_project",
 *   label = @Translation("Project"),
 *   label_collection = @Translation("Projects"),
 *   label_singular = @Translation("project"),
 *   label_plural = @Translation("projects"),
 *   label_count = @PluralTranslation(
 *     singular = "@count projects",
 *     plural = "@count projects",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerProjectListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerProjectStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerProjectForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerProjectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_project",
 *   admin_permission = "administer l10n server project",
 *   entity_keys = {
 *     "id" = "pid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-project",
 *     "add-form" = "/l10n-server-project/add",
 *     "canonical" = "/l10n-server-project/{l10n_server_project}",
 *     "edit-form" = "/l10n-server-project/{l10n_server_project}/edit",
 *     "delete-form" = "/l10n-server-project/{l10n_server_project}/delete",
 *     "releases" = "/admin/localization/l10n-server-project/{l10n_server_project}/releases"
 *   },
 * )
 */
class L10nServerProject extends ContentEntityBase implements L10nServerProjectInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Human readable name for project used on the interface.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 256)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URI'))
      ->setDescription(t('A unique short name to identify the project nicely in paths.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['connector_module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Connector'))
      ->setDescription(t('Connector module for this project, such as l10n_localpacks or l10n_drupalorg.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['homepage'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Homepage'))
      ->setDescription('Link to project home page.')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);
    $fields['last_parsed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last parsed'))
      ->setDescription(t('Unix timestamp of last time project was parsed.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Status flag. 1 if new project releases should be looked for, 0 if new scanning and parsing is disabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Project weight used for sorting. Lower weights float up to the top.'))
      ->setDefaultValue(0);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->get('status')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectorModule(): string {
    return (string) $this->get('connector_module')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setConnectorModule(string $module): self {
    $this->set('connector_module', $module);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnector(): ?ConnectorInterface {
    /** @var \Drupal\l10n_server\ConnectorManagerInterface $connectorManager */
    $connectorManager = \Drupal::service('plugin.manager.l10n_server.connector');
    /** @var \Drupal\l10n_server\ConnectorInterface $connector */
    $connector = $connectorManager->createInstance($this->getConnectorModule());
    return $connector;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomepage(): ?string {
    return (string) $this->get('homepage')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setHomepage(string $homepage): self {
    $this->set('homepage', $homepage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastParsed(): ?int {
    return (int) $this->get('last_parsed')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setLastParsed(int $timestamp): self {
    $this->set('last_parsed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return (string) $this->get('title')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): self {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri(): string {
    return (string) $this->get('uri')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setUri(string $uri): self {
    $this->set('uri', $uri);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): int {
    return (int) $this->get('status')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(int $status): self {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->get('weight')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): L10nServerProjectInterface {
    $this->set('weight', $weight);
    return $this;
  }

}
