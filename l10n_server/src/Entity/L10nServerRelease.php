<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the release entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_release",
 *   label = @Translation("Release"),
 *   label_collection = @Translation("Releases"),
 *   label_singular = @Translation("release"),
 *   label_plural = @Translation("releases"),
 *   label_count = @PluralTranslation(
 *     singular = "@count releases",
 *     plural = "@count releases",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerReleaseListBuilder",
 *     "storage" = "Drupal\l10n_server\Entity\Storage\L10nServerReleaseStorage",
 *     "storage_schema" = "Drupal\l10n_server\Entity\L10nServerReleaseStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerReleaseForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerReleaseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_release",
 *   admin_permission = "administer l10n server release",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-release",
 *     "add-form" = "/l10n-server-release/add",
 *     "canonical" = "/l10n-server-release/{l10n_server_release}",
 *     "edit-form" = "/l10n-server-release/{l10n_server_release}/edit",
 *     "delete-form" = "/l10n-server-release/{l10n_server_release}/delete",
 *   },
 * )
 */
class L10nServerRelease extends ContentEntityBase implements L10nServerReleaseInterface {

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if ($update) {
      return;
    }

    // Queue release to be parsed.
    $queue = \Drupal::queue('l10n_server_parser_queue');
    if ($queue->createItem($this)) {
      // Add timestamp to avoid queueing item more than once.
      $this->setQueuedTime(\Drupal::time()->getRequestTime());
      $this->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
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
    $fields['pid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Project'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_project}.pid of the parent project.'))
      ->setSetting('target_type', 'l10n_server_project')
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
    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setDescription('Version of the release.')
      ->setSetting('max_length', 32)
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
    $fields['download_link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Download'))
      ->setDescription('Link to download this release.')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['file_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('File hash'))
      ->setDescription('Hash of file for easy identification of changed files')
      ->setSetting('max_length', 32)
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
    $fields['file_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('File date'))
      ->setDescription(t('Unix timestamp with release file date. Used to identify file changes.'))
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
    $fields['last_parsed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last parsed'))
      ->setDescription(t('Release weight used for sorting. Lower weights float up to the top.'))
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
    $fields['queued'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Queued'))
      ->setDescription(t('Time when this release was queued for refresh, 0 if not queued.'))
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
    $fields['sid_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Strings'))
      ->setDescription(t('Count of source strings in this project release.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ]);
    $fields['fid_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Files'))
      ->setDescription(t('Count of files in this project release.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ]);
    $fields['eid_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Warnings'))
      ->setDescription(t('Count of warnings parsing this project release.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ]);
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Project weight used for sorting. Lower weights float up to the top.'))
      ->setDefaultValue(0);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectId(): int {
    return (int) $this->get('pid')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getProject(): L10nServerProjectInterface {
    return L10nServerProject::load($this->get('pid'));
  }

  /**
   * {@inheritdoc}
   */
  public function setProject(L10nServerProjectInterface $project): self {
    $this->set('pid', $project->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): string {
    return (string) $this->get('queued')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion(string $version): self {
    $this->set('version', $version);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueuedTime(): int {
    return (int) $this->get('queued')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setQueuedTime(int $timestamp): self {
    $this->set('queued', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastParsed(): int {
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
  public function getSourceStringCount(): int {
    return (int) $this->get('sid_count')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceStringCount(int $count): self {
    $this->set('sid_count', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineCount(): int {
    return (int) $this->get('lid_count')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setLineCount(int $count): self {
    $this->set('lid_count', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorCount(): int {
    return (int) $this->get('eid_count')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorCount(int $count): self {
    $this->set('eid_count', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadLink(): ?string {
    return (string) $this->get('download_link')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDownloadLink(string $link): self {
    $this->set('download_link', $link);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileDate(): ?int {
    return (int) $this->get('file_date')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setFileDate(int $timestamp): self {
    $this->set('file_date', $timestamp);
    return $this;
  }

}
