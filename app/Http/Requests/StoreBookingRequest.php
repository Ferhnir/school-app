<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['date' => $this->route('date')?->toDateString()]);
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'parent_id'  => ['required', 'exists:users,id'],
            'start_time' => ['required', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge(['parent' => User::find($this->parent_id)]);
    }
}
