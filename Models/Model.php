<?php

namespace Models;

use PDO;
use ReflectionClass;
use ReflectionProperty;
use System\Database;
use System\Exceptions\SystemException;

class Model {

    public function Load($primaryKey): bool
    {
        $db = Database::Init();

        $modelName = get_class($this);
        $rfClass = new ReflectionClass($modelName);

        $primaryKeyColumn = $this->PrimaryKey($rfClass);

        $table = strtolower(basename($modelName));
        $qry = $db->prepare("SELECT * FROM {$table} WHERE {$primaryKeyColumn->name} = :{$primaryKeyColumn->name}");
        $qry->bindValue($primaryKeyColumn->name, $primaryKey);
        $qry->execute();
        
        if (($row = $qry->fetch(PDO::FETCH_ASSOC)) === FALSE) {
            return FALSE;
        }

        foreach ($rfClass->getProperties() as $key) {
            $prop = $key->name;
            $val = $row[$prop];
            $this->$prop = $val;
        }

        return TRUE;
    }

    public function Save(): void
    {
        $db = Database::Init();

        $modelName = get_class($this);
        $rfClass = new ReflectionClass($modelName);
        $primaryKeyColumn = $this->PrimaryKey($rfClass);

        $modelName = get_class($this);
        $payload = (array)$this;
        
        $table = strtolower(basename($modelName));

        if ($primaryKeyColumn->isInitialized($this)) {
            $this->Update($db, $payload, $table, $primaryKeyColumn->name);
        } else {
            $this->Insert($db, $payload, $table, $primaryKeyColumn->name);
        }
    }

    public function Delete(): void
    {
        $db = Database::Init();

        $modelName = get_class($this);
        $rfClass = new ReflectionClass($modelName);

        $primaryKeyColumn = $this->PrimaryKey($rfClass);
        if (!$primaryKeyColumn->isInitialized($this)) {
            throw new SystemException();
        }

        $primaryKeyColumnName = $primaryKeyColumn->name;

        $table = strtolower(basename($modelName));
        $qry = $db->prepare("DELETE FROM {$table} WHERE {$primaryKeyColumnName} = :{$primaryKeyColumnName}");
        $qry->bindValue($primaryKeyColumnName, $this->$primaryKeyColumnName);
        $qry->execute();
    }

    private function Insert(PDO $db, array $payload, string $table, string $primaryKeyColumnName): void
    {
        $keys = "";
        foreach ($payload as $key => $val) {
            $keys .= "{$key}, ";
        }
        $keys = trim($keys, ", ");

        $values = "";
        foreach ($payload as $key => $val) {
            $values .= ":{$key}, ";
        }
        $values = trim($values, ", ");

        $qry = $db->prepare("INSERT INTO {$table} ({$keys}) VALUES ({$values})");
        $qry->execute($payload);

        $this->$primaryKeyColumnName = $db->lastInsertId();
    }

    private function Update(PDO $db, array $payload, string $table, string $primaryKeyColumnName): void
    {
        $keyVal = "";
        foreach ($payload as $key => $val) {
            if ($key !== $primaryKeyColumnName) {
                $keyVal .= "{$key} = :{$key}, ";
            }
        }
        $keyVal = trim($keyVal, ", ");

        $qry = $db->prepare("UPDATE {$table} SET {$keyVal} WHERE {$primaryKeyColumnName} = :{$primaryKeyColumnName}");
        $qry->execute($payload);
    }

    private function PrimaryKey(ReflectionClass $class): ReflectionProperty
    {
        foreach ($class->getProperties() as $rfProp) {
            $doc = $rfProp->getDocComment();
            if ($doc === FALSE) {
                continue;
            }

            if (preg_match_all("#(.*(@attr)\s(?'key'[^\s]+))#", $doc, $matches)) {
                return $rfProp;
            }
        }

        throw new SystemException();
    }

}