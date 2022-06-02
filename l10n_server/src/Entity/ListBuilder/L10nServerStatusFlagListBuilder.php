<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the status flag entity type.
 */
class L10nServerStatusFlagListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build['table'] = parent::render();
    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $build['summary']['#markup'] = $this->t('Total status flags: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['language'] = $this->t('Language');
    $header['has_suggestion'] = $this->t('Has suggestion');
    $header['has_translation'] = $this->t('Has translation');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerStatusFlagInterface $entity */
    $row['id'] = $entity->id();
    $row['language'] = $entity->get('language')->first()->getValue()['value'];
    $row['has_suggestion'] = $entity->get('has_suggestion')->first()->getValue()['value'];
    $row['has_translation'] = $entity->get('has_suggestion')->first()->getValue()['value'];
    return $row + parent::buildRow($entity);
  }

}
