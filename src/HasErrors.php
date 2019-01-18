<?php
/**
 * This file contains the HasErrors trait.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui
 */

namespace Kingga\Gui;

/**
 * This trait defines methods around getting and adding
 * silent errors.
 */
trait HasErrors
{
    /**
     * A list of errors.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Get the last error from the stack.
     *
     * @return string
     */
    public function getLastError(): string
    {
        return $this->errors[count($this->errors) - 1];
    }

    /**
     * Add an error message to the stack.
     *
     * @param string $errmsg
     * @return void
     */
    protected function addError(string $errmsg)
    {
        $this->errors[] = $errmsg;
    }
}
