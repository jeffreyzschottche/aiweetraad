<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null || $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'in:1,-1'],
        ];
    }
}
