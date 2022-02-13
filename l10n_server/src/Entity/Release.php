<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides the Release entity.
 *
 * @ContentEntityType(
 *   id = "l10n_server_release",
 *   label = @Translation("Release"),
 *   label_collection = @Translation("Releases"),
 *   label_singular = @Translation("release"),
 *   label_plural = @Translation("releases"),
 *   label_count = @PluralTranslation(
 *     singular = "@count release",
 *     plural = "@count releases",
 *   ),
 *   base_table = "l10n_server_release",
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Entity\Routing\ReleaseRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\l10n_server\Form\ReleaseForm",
 *       "delete" = "Drupal\l10n_server\Form\ReleaseDeleteForm",
 *     },
 *     "list_builder" = "Drupal\l10n_server\Entity\Handler\ReleaseListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "\Drupal\l10n_server\ReleaseStorageSchema",
 *   },
 *   admin_permission = "administer localization server",
 *   entity_keys = {
 *    "id" = "rid",
 *    "label" = "title"
 *   },
 *   links = {
 *     "collection" = "/admin/config/l10n_server/projects/{l10n_server_project}/releases",
 *     "add-form" = "/admin/config/l10n_server/projects/{l10n_server_project}/releases/add",
 *     "delete-form" = "/admin/config/l10n_server/projects/releases/{l10n_server_release}/delete",
 *     "edit-form" = "/admin/config/l10n_server/projects/releases/{l10n_server_release}/edit",
 *   },
 * )
 */
class Release extends ContentEntityBase implements ReleaseInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['pid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reference to the {l10n_server_project}.pid of the parent project.'))
      ->setSetting('target_type', 'l10n_server_project')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Release version'))
      ->setDescription(t('Version name or code name of release.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setSettings([
        'max_length' => 128,
      ]);
    $fields['download_link'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Download link'))
      ->setDescription(t('Download link for this release of the software.'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
      ]);


    $fields['file_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Unix timestamp with release file date'))
      ->setReadOnly(TRUE)
      ->setRequired(FALSE)
      ->setDescription(t('Unix timestamp with release file date. Used to identify file changes.'));

    $fields['file_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash of file'))
      ->setReadOnly(TRUE)
      ->setRequired(FALSE)
      ->setDescription(t('Hash of file. Used to identify file changes.'));

    $fields['last_parsed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last time project was parsed'))
      ->setReadOnly(TRUE)
      ->setRequired(FALSE)
      ->setDescription(t('Unix timestamp of last time release was parsed.'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Release weight used for sorting. Lower weights float up to the top.'))
      ->setDefaultValue(0)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
      ]);

    $fields['sid_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of source strings'))
      ->setDescription(t('Count of source strings in this project release.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setProject(ProjectInterface $project): ReleaseInterface {
    $this->set('pid', $project);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectId(): int {
    return (int) $this->getProject()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getProject(): ProjectInterface {
    return $this->get('pid')->first()
      ->get('entity')
      ->getTarget()
      ->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setLastParsed(): ReleaseInterface {
    $this->set('last_parsed', \Drupal::time()->getRequestTime());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceStringCounter(int $count): ReleaseInterface {
    $this->set('sid_count', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastTimeParsed(): int {
    return (int) $this->get('last_parsed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadLink(): ?string {
    return $this->get('download_link')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileDate(): ?int {
    return $this->get('file_date')->value;
  }

}
