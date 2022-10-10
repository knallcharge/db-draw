<?php

namespace Jawira\DbDraw\Relational;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use \Doctrine\DBAL\Schema\Table;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function in_array;
use function sprintf;
use function strval;

/**
 * @author  Jawira Portugal
 */
class Entity implements ElementInterface
{
  /**
   * @var \Doctrine\DBAL\Schema\Table
   */
  protected $table;

  /**
   * @var ElementInterface[]
   */
  protected $columns = [];

  /**
   * @var Raw
   */
  protected $header = null;

  /**
   * @var Raw
   */
  protected $footer = null;

  public function __construct(Table $table)
  {
    $this->table = $table;
  }

  public function generateHeaderAndFooter(): self
  {
    $this->header = new Raw(sprintf('entity %s {', $this->table->getName()));
    $this->footer = new Raw('}');

    return $this;
  }

  public function generateColumns(): self
  {
    $pkNames    = $this->table->hasPrimaryKey() ? $this->table->getPrimaryKeyColumns() : [];
    $allColumns = $this->table->getColumns();

    $pkOnly       = fn(DoctrineColumn $column): bool => in_array($column->getName(), $pkNames);
    $exceptPk     = fn(DoctrineColumn $column): bool => !$pkOnly($column);
    $instantiator = fn(DoctrineColumn $column) => new Column($column);

    $pk            = array_filter($allColumns, $pkOnly);
    $columns       = array_filter($allColumns, $exceptPk);
    $this->columns = array_merge(array_map($instantiator, $pk), [new Raw('--')], array_map($instantiator, $columns));

    return $this;
  }

  public function __toString(): string
  {
    $puml = strval($this->header);
    $puml = array_reduce($this->columns, '\\Jawira\\DbDraw\\Toolbox::reducer', $puml);
    $puml .= strval($this->footer);

    return $puml;
  }
}
