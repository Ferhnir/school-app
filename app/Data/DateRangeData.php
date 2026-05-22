<?php

namespace App\Data;

use Carbon\Carbon;

class DateRangeData
{
    public function __construct(
        public Carbon $start_date,
        public Carbon $end_date,
    ) {}
}
