<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\l10n_server\Entity\ProjectInterface;
use function \assert;
use function \array_merge;

/**
 * Provides the list builder handler for the Project entity.
 */
class ProjectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['label'] = ['data' => $this->t('Project')];
    $header['homepage'] = ['data' => $this->t('Homepage')];
    $header['last_parsed'] = ['data' => $this->t('Last time project was parsed')];
    $header['releases'] = ['data' => $this->t('Releases')];
    return array_merge($header, parent::buildHeader());
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof ProjectInterface);
    $row = [];
    $row['label']['data'] = $entity->label();
    $row['homepage']['data'] = $entity->getHomepage() ?? '-';
    $row['last_parsed']['data'] = $entity->getLastTimeParsed() ?? '-';
    $row['releases']['data'] = Link::createFromRoute(t('Releases'), 'entity.l10n_server_release.collection', ['l10n_server_project' => $entity->id()]);
    return array_merge($row, parent::buildRow($entity));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle(): TranslatableMarkup {
    return $this->t('Projects and releases');
  }

}
