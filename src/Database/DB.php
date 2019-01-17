<?php

namespace Kingga\Gui\Database;

use \PDO;
use SimpleCrud\SimpleCrud;

class DB
{
    private static $pdo;

    private static $db;

    public static function setDB(PDO $pdo)
    {
        self::$pdo = $pdo;
    }

    public static function getPdo(): PDO
    {
        if (!self::$pdo) {
            self::createPdo();
        }

        return self::$pdo;
    }

    private static function createCRUD()
    {
        if (!self::$pdo) {
            self::createPdo();
        }

        self::$db = new SimpleCrud(self::$pdo);
    }

    protected static function getCRUD()
    {
        if (!self::$db) {
            self::createCRUD();
        }

        return self::$db;
    }

    private static function createPdo()
    {
        // Check if extension is loaded.
        if (!extension_loaded('sqlite3')) {
            throw new SQLiteExtensionNotLoaded('The SQLite extension has not been installed.');
        }


        $dsn = sprintf('sqlite:%s', database_path(env('DB_FILE', 'database.sqlite')));
        self::$pdo = new PDO($dsn);
    }

    public static function table(string $table)
    {
        $db = self::getCRUD();

        return $db->{$table};
    }
}
