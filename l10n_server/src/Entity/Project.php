<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\l10n_server\ConnectorInterface;
use function t;

/**
 * Provides the Project entity.
 *
 * @ContentEntityType(
 *   id = "l10n_server_project",
 *   label = @Translation("Project"),
 *   label_collection = @Translation("Projects"),
 *   label_singular = @Translation("project"),
 *   label_plural = @Translation("projects"),
 *   label_count = @PluralTranslation(
 *     singular = "@count project",
 *     plural = "@count projects",
 *   ),
 *   base_table = "l10n_server_project",
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\l10n_server\Form\ProjectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\l10n_server\Entity\Handler\ProjectListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\l10n_server\ProjectStorageSchema",
 *   },
 *   admin_permission = "administer localization server",
 *   entity_keys = {
 *    "id" = "pid",
 *    "label" = "title",
 *    "published" = "enabled",
 *    "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/l10n_server/projects/add",
 *     "collection" = "/admin/config/l10n_server/projects",
 *     "delete-form" = "/admin/config/l10n_server/projects/{l10n_server_project}/delete",
 *     "edit-form" = "/admin/config/l10n_server/projects/{l10n_server_project}/edit",
 *     "releases" = "/admin/config/l10n_server/projects/{l10n_server_project}/releases"
 *   },
 * )
 */
class Project extends ContentEntityBase implements ProjectInterface, EntityPublishedInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uuid']->setDescription(t('The project UUID.'));

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Project name'))
      ->setDescription(t('Human readable name of project.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setSettings([
        'max_length' => 128,
      ]);
    $fields['uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Project URI'))
      ->setDescription(t('Short name of project used in paths. This will appear in paths like translate/projects/uri at the end. Suggested to use lowercase only.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
      ])->addConstraint('L10nServerProjectUriUnique');
    $fields['connector_module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Connector handling project data'))
      ->setDescription(t('Data and source handler for this project. Cannot be modified later.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
      ]);
    $fields['homepage'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Homepage'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
      ])
      ->setDescription(t('Link to project home page.'));

    // Override some properties of the published field added by
    // \Drupal\Core\Entity\EntityPublishedTrait::publishedBaseFieldDefinitions().
    $fields['enabled']->setLabel(t('Enabled'));
    $fields['enabled']->setDescription(t('Disable to stop scanning and parsing new releases.'));
    $fields['enabled']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ]]);

    $fields['last_parsed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last time project was parsed'))
      ->setReadOnly(TRUE)
      ->setDescription(t('Unix timestamp of last time project was parsed.'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Project weight used for sorting. Lower weights float up to the top.'))
      ->setDefaultValue(0)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
      ]);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('l10n_server_release');
    /** @var \Drupal\l10n_server\Entity\ReleaseInterface[] $releases */
    $releases = $storage->loadByProperties(['pid' => $this->id()]);
    $storage->delete($releases);
  }

  /**
   * {@inheritdoc}
   */
  public function getHomepage(): ?string {
    return $this->get('homepage')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectorModule(): string {
    return $this->get('connector_module')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnector(): ?ConnectorInterface {
    /** @var \Drupal\l10n_server\ConnectorManager $manager */
    $manager = \Drupal::service('plugin.manager.l10n_server.connector');
    /** @var \Drupal\l10n_server\ConnectorInterface $plugin */
    $plugin = $manager->createInstance($this->getConnectorModule());
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabled(): bool {
    return (bool) $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastTimeParsed(): ?int {
    return $this->get('last_parsed')->value;
  }
}
