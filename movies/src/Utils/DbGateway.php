<?php

declare(strict_types=1);

namespace src\Utils;

use Dotenv\Dotenv;
use src\Db\DbConn;
use src\Db\DbTable;
use src\Db\DbTableOp;

class DbGateway
{
    /**
     * *************************************************************************************
     * 
     * Establishes and Provides Resource: PDO Connection Object
     * 
     * *************************************************************************************
     */
    private static function getConnection(?string $databaseName = null)
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $conn = new DbConn(
            driver: $_ENV["DSN_DRIVER"],
            serverName: $_ENV["DATABASE_HOSTNAME"],
            userName: $_ENV["DATABASE_USERNAME"],
            password: $_ENV["DATABASE_PASSWORD"],
            database: $databaseName
        );

        return $conn->getConnection();
    }

    /**
     * *************************************************************************************
     * 
     * Provides Resource: PDO Connection Object to Create / Drop Database
     * 
     * *************************************************************************************
     */
    public static function dbConn(): ?\PDO
    {
        $conn = static::getConnection();
        return $conn;
    }

    /**
     * *************************************************************************************
     * 
     * Provides Resource: PDO Connection Object to Create/Drop/Truncate/Alter Table
     * 
     * *************************************************************************************
     */
    public static function getTableConnection(?string $databaseName = null): DbTable
    {
        $conn = static::getConnection($databaseName);
        return new DbTable($conn);
    }

    /**
     * *************************************************************************************
     * 
     * Provides Resource: PDO Connection Object for Table Read and Write Operations
     * 
     * *************************************************************************************
     */
    public static function getTableOpConnection(?string $databaseName = null): DbTableOp
    {
        $conn = static::getConnection($databaseName);
        return new DbTableOp($conn);
    }
}