<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasLocationRules;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    use HasLocationRules;

    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event === null || $this->user()->can('manage', $event);
    }

    public function rules(): array
    {
        return array_merge([
            'name'     => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at'   => ['required', 'date', 'after:start_at'],
        ], $this->locationRules());
    }
}
