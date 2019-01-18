<?php
/**
 * This file contains the SQLiteExtensionNotLoaded exception.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Exceptions
 */

namespace Kingga\Gui\Exceptions;

/**
 * This exception should be thrown when the 'sqlite3' extension has
 * not been installed.
 */
class SQLiteExtensionNotLoaded extends \Exception
{
}