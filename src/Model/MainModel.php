<?php

declare(strict_types=1);

namespace src\Model;

use src\Utils\DbGateway;
use src\Db\DbTableOp;

abstract class MainModel
{
    protected array $databaseNames = ['movies', 'backup'];
    protected DbTableOp $dbTableOp;

    public function __construct(private ?string $databaseName)
    {
        if ($databaseName !== null && !in_array($databaseName, $this->databaseNames)) {
            throw new \InvalidArgumentException("Invalid database name provided.");
        }

        $this->databaseName = $databaseName ?? $this->databaseName;

        $this->dbTableOp = DbGateway::getTableOpConnection($this->databaseName);
    }
}