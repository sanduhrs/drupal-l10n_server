<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the error entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_error",
 *   label = @Translation("Error"),
 *   label_collection = @Translation("Errors"),
 *   label_singular = @Translation("error"),
 *   label_plural = @Translation("errors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count errors",
 *     plural = "@count errors",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerErrorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerErrorForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerErrorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerErrorHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_error",
 *   admin_permission = "administer l10n server error",
 *   entity_keys = {
 *     "id" = "eid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-error",
 *     "add-form" = "/l10n-server-error/add",
 *     "canonical" = "/l10n-server-error/{l10n_server_error}",
 *     "edit-form" = "/l10n-server-error/{l10n_server_error}",
 *     "delete-form" = "/l10n-server-error/{l10n_server_error}/delete",
 *   },
 * )
 */
class L10nServerError extends ContentEntityBase implements L10nServerErrorInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Release'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_release}.rid of the parent release.'))
      ->setSetting('target_type', 'l10n_server_release')
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
    $fields['value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Value'))
      ->setDescription('Text of the error message.')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getReleaseId(): int {
    return (int) $this->get('rid')->first()->getValue()['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function setReleaseId(int $rid): self {
    $this->set('rid', $rid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(): string {
    return $this->get('value')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue(string $value): self {
    $this->set('value', $value);
    return $this;
  }

}
