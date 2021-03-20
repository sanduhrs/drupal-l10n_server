<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\l10n_server\Entity\ProjectInterface;
use function \assert;
use function \array_merge;

/**
 * Provides the list builder handler for the Project entity.
 */
class ReleaseListBuilder extends EntityListBuilder {

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    dpm(\Drupal::routeMatch(), __METHOD__);
  }


  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['label'] = ['data' => $this->t('Release')];
    return array_merge($header, parent::buildHeader());
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof ProjectInterface);
    $row = [];
    $row['label']['data'] = $entity->label();
    return array_merge($row, parent::buildRow($entity));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle(): TranslatableMarkup {
    return $this->t('Releases');
  }

}
