<?php

namespace Drupal\l10n_packager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\l10n_packager\L10nPackagerReleaseInterface;

/**
 * Defines the packager release entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_packager_release",
 *   label = @Translation("Packager Release"),
 *   label_collection = @Translation("Packager Releases"),
 *   label_singular = @Translation("packager release"),
 *   label_plural = @Translation("packager releases"),
 *   label_count = @PluralTranslation(
 *     singular = "@count packager releases",
 *     plural = "@count packager releases",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_packager\Entity\ListBuilder\L10nPackagerReleaseListBuilder",
 *     "storage" = "Drupal\l10n_packager\Entity\Storage\L10nPackagerReleaseStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_packager\Form\L10nPackagerReleaseForm",
 *       "edit" = "Drupal\l10n_packager\Form\L10nPackagerReleaseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_packager\Routing\L10nPackagerReleaseHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_packager_release",
 *   admin_permission = "administer l10n packager release",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/l10n-packager-release",
 *     "add-form" = "/l10n-packager-release/add",
 *     "canonical" = "/l10n-packager-release/{l10n_packager_release}",
 *     "edit-form" = "/l10n-packager-release/{l10n_packager_release}",
 *     "delete-form" = "/l10n-packager-release/{l10n_packager_release}/delete",
 *   },
 * )
 */
class L10nPackagerRelease extends ContentEntityBase implements L10nPackagerReleaseInterface {

  use EntityChangedTrait;

  /**
   * Release packager status: do not repackage anymore.
   */
  const DISABLED = 0;

  /**
   * Release packager status: keep repackaging.
   */
  const ACTIVE = 1;

  /**
   * Release packager status: error.
   */
  const ERROR = 2;

  /**
   * Gets the timestamp of the last entity change for the current translation.
   *
   * @return int
   *   The timestamp of the last entity save operation.
   */
  public function getCheckedTime(): int {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Packaging status for this release. One of L10N_PACKAGER_DISABLED, L10N_PACKAGER_ACTIVE and L10N_PACKAGER_ERROR.'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'tiny')
      ->setDefaultValue(static::ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'integer',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'integer',
        'weight' => 0,
        'settings' => [
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Checked'))
      ->setDescription(t('Unix timestamp of last time this release was checked.'))
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
    $fields['changed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Changed'))
      ->setDescription(t('Unix timestamp of last time files for this release were updated.'))
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
    return $fields;
  }

}
