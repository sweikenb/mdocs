<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Exceptions;

use Exception;

class NavigationAlreadyParsedException extends Exception
{
    public function __construct(?string $msg = null)
    {
        parent::__construct($msg ?? 'Navigation already parsed', 500);
    }
}
