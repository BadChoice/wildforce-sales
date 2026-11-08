<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscoveryCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-growth') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'segments' => ['required', 'string', 'max:255'],
            'locations' => ['required', 'string', 'max:1000'],
            'niches' => ['nullable', 'string', 'max:1000'],
            'language' => ['required', 'in:ca,es'],
            'daily_candidate_limit' => ['required', 'integer', 'between:1,25'],
        ];
    }
}
