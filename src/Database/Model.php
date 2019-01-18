<?php
/**
 * This file contains the base Model class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Database.
 */

namespace Kingga\Gui\Database;

/**
 * This class defines a set of methods which will prove
 * helpful in the creation of a model. Creating a model
 * should be as simple as creating a class with an empty
 * body. If the $table property has not been defined in
 * the model, the class name will be used instead.
 */
class Model
{
    /**
     * All columns which have been changed. Note that some
     * columns may not exist in the table and until you
     * save the insert/update there won't be any errors.
     *
     * @var array
     */
    private $columns = [];

    /**
     * Is the model instance a new entry or one that should
     * be updated on save?
     *
     * @var boolean
     */
    private $new_entry = true;

    /**
     * Has a row changed? If it hasn't don't bother inserting or
     * updating it.
     *
     * @var boolean
     */
    private $row_changed = false;

    /**
     * The name of the table, if left blank the class name will be
     * used instead. E.g. AppInfo will become app_info.
     *
     * @var string
     */
    protected $table = '';

    /**
     * Find and retrieve the row with the given ID.
     *
     * @param integer $id The ID of the row.
     * @return Model|null
     */
    public static function find(int $id): ?Model
    {
        $model = new static;

        $row = DB::table($model->getTableName())
            ->select()
            ->where('id = :id', [':id' => $id])
            ->one()
            ->run();

        $model->id = $id;

        return $model;
    }

    /**
     * Run a select statement, this will return a query builder.
     *
     * @return SimpleCrud\Queries\Sqlite\Select
     */
    public static function select()
    {
        $model = new static;
        return DB::table($model->getTableName())
            ->select();
    }

    /**
     * Returns a select where query builder.
     *
     * @param string $col The column to check the condition against.
     * @param mixed  $val The value to check the column against.
     * @return SimpleCrud\Queries\Sqlite\Where
     */
    public static function where(string $col, $val)
    {
        return static::select()
            ->where("$col = :$col", [$col => $val]);
    }

    /**
     * Get the current value of 'x' column.
     *
     * @param string $name The name of the column.
     * @return mixed
     */
    public function __get(string $name)
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }
    }

    /**
     * Sets the value of 'x' column.
     *
     * @param string $name  The name of the column.
     * @param mixed  $value The value of the column.
     */
    public function __set(string $name, $value)
    {
        // If some data has been set/changed and the columns are empty then it
        // is a new result so insert it rather than update it.
        if (!empty($this->columns) && !$this->row_changed) {
            $this->new_entry = false;
        }

        if (isset($this->columns[$name]) && $this->columns[$name] !== $value) {
            $this->columns[$name] = $value;
            $this->row_changed = true;
        } elseif (!isset($this->columns[$name])) {
            $this->columns[$name] = $value;
            $this->row_changed = true;
        }
    }

    /**
     * Get the name of the table for this model. If not defined
     * by the property, use the classes name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        if (empty($this->table)) {
            $this->table = $this->classToTableName();
        }

        return $this->table;
    }

    /**
     * Convert the classes name into the tables name. It does this by
     * finding every upper case character (not including the first) and
     * adding an underscore before it. All characters will be lower case
     * by the time this method has finished processing it. E.g. AppInfo
     * will becode app_info.
     *
     * @return string
     */
    private function classToTableName(): string
    {
        // Get the class and remove the namespace.
        $class = get_called_class();
        $class = substr($class, strrpos($class, '\\') + 1);

        $len = strlen($class);
        $chars = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];
        $out = '';

        for ($i = 0; $i < $len; $i++) {
            if ($i === 0) {
                $out .= strtolower($class[$i]);
            } elseif (in_array($class[$i], $chars)) {
                $out .= '_' . strtolower($class[$i]);
            } else {
                $out .= $class[$i];
            }
        }

        return $out;
    }

    /**
     * If there has been any change, insert/update the row in the database.
     *
     * @return int|null The ID of the row.
     */
    public function save(): ?int
    {
        if (!$this->row_changed) {
            // There's not reason to insert/update this if nothing has been changed.
            return isset($this->columns['id']) ? $this->columns['id'] : null;
        }

        // Create an entry or update it.
        $query = DB::table($this->getTableName());
        $row = $query->create($this->columns);

        $id = $row->save();
        $this->columns['id'] = $id;
        return $id;
    }
}
