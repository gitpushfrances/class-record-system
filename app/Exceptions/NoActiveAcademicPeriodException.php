<?php

namespace App\Exceptions;

use Exception;

class NoActiveAcademicPeriodException extends Exception
{
    public function __construct()
    {
        parent::__construct('No active academic period is set. Ask the Admin to activate one before adding students.');
    }
}
