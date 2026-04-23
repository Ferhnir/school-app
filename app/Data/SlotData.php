<?php

namespace App\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SlotData
{
    public function __construct(
        public Carbon $start_date,
        public Carbon $end_date,
        public array $days,
        public string $start_time,
        public string $end_time,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            start_date: Carbon::parse($request->input('start_date')),
            end_date: Carbon::parse($request->input('end_date')),
            days: $request->input('days'),
            start_time: $request->input('start_time'),
            end_time: $request->input('end_time'),
        );
    }
}
