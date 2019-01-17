<?php

namespace Kingga\Gui\Database;

class Model
{
    private $columns = [];

    private $new_entry = true;

    private $row_changed = false;

    protected $table = '';

    public function __get(string $name)
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }
    }

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

    public function getTableName()
    {
        if (empty($this->table)) {
            $this->table = $this->classToTableName();
        }

        return $this->table;
    }

    private function classToTableName()
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

    public function save()
    {
        if (!$this->row_changed) {
            // There's not reason to insert/update this if nothing has been changed.
            return;
        }

        // Create an entry or update it.
        $query = DB::table($this->getTableName());
        $row = $query->create($this->columns);

        $id = $row->save();
        $this->columns['id'] = $id;
        return $id;
    }
}
