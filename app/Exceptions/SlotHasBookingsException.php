<?php

namespace App\Exceptions;

use RuntimeException;

class SlotHasBookingsException extends RuntimeException
{
    public function __construct(string $message = 'Cannot modify availability: existing bookings found in this date range.')
    {
        parent::__construct($message);
    }
}
