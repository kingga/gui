<?php
/**
 * This file contains the static DB class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Database
 */

namespace Kingga\Gui\Database;

use \PDO;
use SimpleCrud\SimpleCrud;

/**
 * This class defines methods around creating PDO and CRUD
 * instances as well as other basic features around the
 * SimpleCrud library.
 */
class DB
{
    /**
     * The PDO instance created from createPdo().
     * 
     * @see createPdo()
     * @var PDO|null
     */
    private static $pdo;

    /**
     * The CRUD instance created from createCRUD().
     *
     * @see createCRUD()
     * @var SimpleCrud
     */
    private static $db;

    /**
     * If you have created your own PDO instance you
     * can pass it into this method otherwise one will
     * be created automatically from the .env file.
     *
     * @param PDO $pdo
     * @return void
     */
    public static function setDB(PDO $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * This method checks if a PDO has been created and if
     * it hasn't, one will be generated. After a PDO instance
     * has been defined it will return it.
     *
     * @return PDO
     */
    public static function getPdo(): PDO
    {
        if (!self::$pdo) {
            self::createPdo();
        }

        return self::$pdo;
    }

    /**
     * Create a SimpleCrud instance.
     *
     * @return void
     */
    private static function createCRUD()
    {
        self::$db = new SimpleCrud(self::getPdo());
    }

    /**
     * This method checks if a CRUD instance has been created
     * and will create one if it hasn't. Once an instance exists,
     * the CRUD will be returned.
     *
     * @return SimpleCrud
     */
    protected static function getCRUD()
    {
        if (!self::$db) {
            self::createCRUD();
        }

        return self::$db;
    }

    /**
     * Create a PDO instance using the defined SQLite file from
     * the environment setting 'DB_FILE'.
     *
     * @throws SQLiteExtensionNotLoaded If the extension 'sqlite3' has not been installed.
     * 
     * @return void
     */
    private static function createPdo()
    {
        // Check if extension is loaded.
        if (!extension_loaded('sqlite3')) {
            throw new SQLiteExtensionNotLoaded('The SQLite extension has not been installed.');
        }


        $dsn = sprintf('sqlite:%s', database_path(env('DB_FILE', 'database.sqlite')));
        self::$pdo = new PDO($dsn);
    }

    /**
     * Return a table (if it exists) so a query can be build from it.
     *
     * @param string $table The name of the table.
     * @return void
     */
    public static function table(string $table)
    {
        $db = self::getCRUD();

        return $db->{$table};
    }
}
