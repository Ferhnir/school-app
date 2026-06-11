<?php

namespace App\Http\Requests;

use App\Rules\IsoDateTime;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date'    => ['required', new IsoDateTime, 'after_or_equal:today'],
            'end_date'      => ['required', new IsoDateTime, 'after_or_equal:start_date'],
            'days'          => ['required', 'array', 'min:1'],
            'days.*'        => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time'    => ['required', new IsoDateTime],
            'end_time'      => ['required', new IsoDateTime, 'after:start_time'],
            'slot_duration' => ['required', 'integer', 'in:5,10,15,20,30'],
        ];
    }
}
