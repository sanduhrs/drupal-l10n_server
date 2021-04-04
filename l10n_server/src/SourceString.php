<?php

declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Database\Connection;
use function md5;

class SourceString {

  private const TABLE = 'l10n_server_string';

  public static $counter = 0;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * SourceString constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  public function save(PoItem $item) {
    $return = $this->connection->upsert(self::TABLE)->fields([
      'value' => $item->getSource(),
      'context' => $item->getContext(),
      'hashkey' => md5($item->getSource() . $item->getContext()),
    ])->key('hashkey')->execute();
    if ($return) {
      self::$counter++;
    }
  }

}
