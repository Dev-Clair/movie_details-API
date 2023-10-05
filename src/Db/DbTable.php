<?php

declare(strict_types=1);

namespace src\Db;

use PDO;
use PDOStatement;
use PDOException;
use RuntimeException;

class DbTable
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Begin a transaction.
     */
    protected function beginTransaction()
    {
        $this->conn->beginTransaction();
    }


    /**
     * Commit the current transaction.
     */
    protected function commit()
    {
        $this->conn->commit();
    }


    /**
     * Rollback the current transaction.
     */
    protected function rollback()
    {
        $this->conn->rollBack();
    }


    /**
     * Executes an SQL query and returns the PDOStatement.
     * 
     * @param string $sql The SQL query to execute.
     * @param array $params The parameters to bind to the query (optional).
     * @return PDOStatement The PDOStatement object.
     */
    protected function executeQuery(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }


    // Create/Drop/Truncate/Alter Table Operations

    /**
     * @param string $tableName Name of table to be created in database
     * @param string $fieldNames 
     * @return PDOStatement if the table was created successfully, false otherwise
     */
    public function createTable(string $tableName, string $fieldNames): PDOStatement
    {
        $sql = "CREATE TABLE $tableName ($fieldNames)";

        $query_result = $this->executeQuery(sql: $sql);
        return $query_result;
    }


    /**
     * @param string $tableName Name of the table to be altered in the database
     * @param string $alterStatement Statement to modify the table structure
     * @return PDOStatement if the table was altered successfully, false otherwise
     */
    public function alterTable(string $tableName, string $alterStatement): PDOStatement
    {
        $sql = "ALTER TABLE $tableName $alterStatement";

        $query_result = $this->executeQuery(sql: $sql);
        return $query_result;
    }


    /**
     * @param string $tableName Name of the table to be truncated in the database
     * @return PDOStatement if the table was truncated successfully, false otherwise
     */
    public function truncateTable(string $tableName): PDOStatement
    {
        $sql = "TRUNCATE TABLE $tableName";

        $query_result = $this->executeQuery(sql: $sql);
        return $query_result;
    }


    /**
     * @param string $tableName Name of the table to be dropped in the database
     * @return PDOStatement if the table was dropped successfully, false otherwise
     */
    public function dropTable(string $tableName): PDOStatement
    {
        $sql = "DROP TABLE $tableName";

        $query_result = $this->executeQuery(sql: $sql);
        return $query_result;
    }

    // Table Read and Write Operations

    private function modifyFieldReference(array $fieldName): string
    {
        $fields = implode(
            ",",
            array_map(
                function ($field) {
                    return "`$field`";
                },
                array_keys($fieldName)
            )
        );

        return $fields;
    }


    public function createResource(string $tableName, array $sanitizedData): bool
    {
        $fieldNames = implode(
            ",",
            array_map(
                function ($field) {
                    return "`$field`";
                },
                array_keys($sanitizedData)
            )
        );

        $placeholders = implode(",", array_fill(0, count($sanitizedData), "?"));

        $sql = "INSERT INTO $tableName ($fieldNames) VALUES ($placeholders)";
        $params = array_values($sanitizedData);

        $this->beginTransaction();

        try {
            $stmt =  $this->executeQuery(sql: $sql, params: $params);
            $this->commit();

            $query_result = $stmt->rowCount() > 0;

            return $query_result;
        } catch (PDOException $e) {
            $this->rollback();

            throw new RuntimeException('Cannot create new resource in ' . $tableName . ': ' . $e->getMessage());
        }
    }


    public function retrieveAllResources(string $tableName): array|false
    {
        $sql_query = "SELECT * FROM $tableName";

        try {
            $stmt = $this->executeQuery(sql: $sql_query);
            $query_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $query_result;
        } catch (PDOException $e) {

            throw new RuntimeException('Cannot retrieve resources: ' . $e->getMessage());
        }
    }


    public function updateResource(string $tableName, array $sanitizedData, array $fieldName, mixed $fieldValue): bool
    {
        $updateFields = $updateFields = implode(",", array_map(function ($column) {
            return "`$column`=?";
        }, array_keys($sanitizedData)));
        $fieldName = $this->modifyFieldReference($fieldName);

        $sql_query = "UPDATE $tableName SET $updateFields WHERE $fieldName = ?";

        $params = array_values($sanitizedData);
        $params[] = $fieldValue;

        $this->beginTransaction();

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: $params);
            $this->commit();

            $query_result = $stmt->rowCount() > 0;

            return $query_result;
        } catch (PDOException $e) {
            $this->rollback();

            throw new RuntimeException('Cannot update resource for ' . $fieldValue . ': ' . $e->getMessage());
        }
    }


    public function deleteResource(string $tableName, array $fieldName, mixed $fieldValue): bool
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "DELETE FROM $tableName WHERE $fieldName = ?";

        $this->beginTransaction();

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$fieldValue]);
            $this->commit();

            $query_result = $stmt->rowCount() > 0;

            return $query_result;
        } catch (PDOException $e) {
            $this->rollback();

            throw new RuntimeException('Cannot delete resource for ' . $fieldValue . ': ' . $e->getMessage());
        }
    }


    public function retrieveResource_SingleFieldValue(string $tableName, array $fieldName, string $compareFieldName, mixed $compareFieldValue): PDOStatement
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "SELECT $fieldName FROM $tableName WHERE $compareFieldName = ?";

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$compareFieldValue]);
            $query_result = $stmt->fetchColumn();

            return $query_result !== false ? $query_result : null;
        } catch (PDOException $e) {

            throw new RuntimeException('Cannot retrieve resource for ' . $compareFieldValue . ': ' . $e->getMessage());
        }
    }


    public function retrieveResource_MultipleFieldValues(string $tableName, array $fieldName, string $compareFieldName, mixed $compareFieldValue): array|false
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "SELECT $fieldName FROM $tableName WHERE $compareFieldName = ?";

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$compareFieldValue]);
            $query_result = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return $query_result;
        } catch (PDOException $e) {

            throw new RuntimeException('Cannot retrieve resource for ' . $compareFieldValue . ': ' . $e->getMessage());
        }
    }


    public function retrieveSpecificResource_firstOccurrence(string $tableName, array $fieldName, $fieldValue): PDOStatement
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "SELECT * FROM $tableName WHERE $fieldName = ?";

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$fieldValue]);
            $query_result = $stmt->fetch(PDO::FETCH_ASSOC); // Fetches First Occurence for specified field value

            return $query_result ?: [];
        } catch (PDOException $e) {

            throw new RuntimeException('Cannot retrieve resource for ' . $fieldValue . ': ' . $e->getMessage());
        }
    }


    public function retrieveSpecificResource_allOccurrence(string $tableName, array $fieldName, $fieldValue): array|false
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "SELECT * FROM $tableName WHERE $fieldName = ?";

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$fieldValue]);
            $query_result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetches All Occurence for specified field value

            return $query_result;
        } catch (PDOException $e) {

            throw new RuntimeException('Cannot retrieve resource for ' . $fieldValue . ': ' . $e->getMessage());
        }
    }


    public function validateResource(string $tableName, array $fieldName, $fieldValue): bool
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "SELECT * FROM $tableName WHERE $fieldName = ?";

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$fieldValue]);
            $query_result = $stmt->rowCount() > 0;

            return $query_result;
        } catch (PDOException $e) {

            throw new RuntimeException('Cannot validate resource for ' . $fieldValue . ': ' . $e->getMessage());
        }
    }


    public function searchResource(string $tableName, array $fieldName, string $fieldValue): array|false
    {
        $fieldName = $this->modifyFieldReference($fieldName);
        $sql_query = "SELECT * FROM $tableName WHERE $fieldName LIKE ?";
        $searchValue = "%$fieldValue%";

        try {
            $stmt = $this->executeQuery(sql: $sql_query, params: [$searchValue]);
            $query_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $query_result;
        } catch (PDOException $e) {

            throw new RuntimeException('Resource not found for ' . $fieldValue . ': ' . $e->getMessage());
        }
    }


    public function sortResource(string $tableName, string $fieldName): array|false
    {
        $sql_query = "SELECT * FROM $tableName ORDER BY $fieldName";

        try {
            $stmt = $this->executeQuery(sql: $sql_query);
            $query_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $query_result;
        } catch (PDOException $e) {

            throw new RuntimeException('Resource not found for ' . $fieldName . ': ' . $e->getMessage());
        }
    }
}
