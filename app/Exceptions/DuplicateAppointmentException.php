<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateAppointmentException extends RuntimeException
{
    public function __construct(string $message = 'This parent already has an appointment with this teacher on the selected day.')
    {
        parent::__construct($message);
    }
}
