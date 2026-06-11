<?php

namespace App\Http\Requests;

use App\Rules\IsoDateTime;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time'    => ['required', new IsoDateTime],
            'end_time'      => ['required', new IsoDateTime, 'after:start_time'],
            'slot_duration' => ['required', 'integer', 'in:5,10,15,20,30'],
        ];
    }
}
