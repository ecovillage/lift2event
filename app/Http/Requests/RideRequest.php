<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasLocationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RideRequest extends FormRequest
{
    use HasLocationRules;

    public function authorize(): bool
    {
        // Shared by the admin-managed and public guest ride endpoints, which
        // authorize themselves separately (policy check vs. edit-token check).
        return true;
    }

    public function rules(): array
    {
        $dir  = $this->input('direction');
        $type = $this->input('type');

        return array_merge([
            'type'              => ['required', Rule::in(['offer', 'request'])],
            'direction'         => ['required', Rule::in(['both-ways', 'outbound-only', 'return-only'])],
            'outbound_at'       => [
                in_array($dir, ['both-ways', 'outbound-only']) ? 'required' : 'nullable',
                'date',
            ],
            'return_at'         => [
                in_array($dir, ['both-ways', 'return-only']) ? 'required' : 'nullable',
                'date',
            ],
            // Ride offers may drop to 0 seats to mark themselves as fully booked;
            // ride requests always need at least 1 seat.
            'seats'             => ['required', 'integer', $type === 'offer' ? 'min:0' : 'min:1', 'max:8'],
            'name'              => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:200'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'contact_methods'   => ['required', 'array', 'min:1'],
            'contact_methods.*' => [Rule::in(['email', 'signal', 'telegram', 'whatsapp', 'sms', 'call'])],
            'info'              => ['nullable', 'string', 'max:2000'],
            'locale'            => ['sometimes', 'nullable', Rule::in(['de', 'en', 'fr', 'zh'])],
        ], $this->locationRules());
    }
}
