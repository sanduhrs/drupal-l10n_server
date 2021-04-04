<?php

declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoReaderInterface;
use Drupal\Component\Gettext\PoWriterInterface;

class PoDatabaseWriter implements PoWriterInterface {

  /**
   * {@inheritdoc}
   */
  private $header;

  /**
   * {@inheritdoc}
   */
  private $langcode;

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * @inheritDoc
   */
  public function setHeader(PoHeader $header) {
    $this->header = $header;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader(): PoHeader {
    return $this->header;
  }

  /**
   * {@inheritdoc}
   */
  public function writeItem(PoItem $item) {
    if ($item->isPlural()) {
      $item->setSource(implode(PoItem::DELIMITER, $item->getSource()));
      $item->setTranslation(implode(PoItem::DELIMITER, $item->getTranslation()));
    }
    $this->importString($item);
  }

  /**
   * {@inheritdoc}
   */
  public function writeItems(PoReaderInterface $reader, $count = -1) {
    $forever = $count == -1;
    while (($count-- > 0 || $forever) && ($item = $reader->readItem())) {
      $this->writeItem($item);
    }
  }

  /**
   * Imports one string into the database.
   *
   * @param \Drupal\Component\Gettext\PoItem $item
   *   The item being imported.
   */
  private function importString(PoItem $item) {
    /** @var \Drupal\l10n_server\SourceString $source_string_storage */
    $source_string_storage = \Drupal::service('l10n_server.source_string_storage');
    $source_string_storage->save($item);
  }

}
