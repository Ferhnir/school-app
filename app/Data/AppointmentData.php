<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentData
{
    public function __construct(
        public Carbon $date,
        public string $start_time,
        public string $end_time,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            date: Carbon::parse($request->input('date')),
            start_time: $request->input('start_time'),
            end_time: $request->input('end_time'),
        );
    }
}
