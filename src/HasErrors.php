<?php

namespace Kingga\Gui;

trait HasErrors
{
    private $errors = [];

    public function getLastError(): string
    {
        return $this->errors[count($this->errors) - 1];
    }

    protected function addError(string $errmsg)
    {
        $this->errors[] = $errmsg;
    }
}
