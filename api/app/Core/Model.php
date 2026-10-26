<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected static string $table = '';

    public static function db(): \PDO { return Database::pdo(); }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM " . static::$table . " WHERE $column = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $ph   = implode(',', array_fill(0, count($cols), '?'));
        $sql  = "INSERT INTO " . static::$table . " (" . implode(',', $cols) . ") VALUES ($ph)";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $set  = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        $sql  = "UPDATE " . static::$table . " SET $set WHERE id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([...array_values($data), $id]);
    }
}