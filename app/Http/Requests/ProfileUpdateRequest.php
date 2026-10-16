<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            ...($this->user()->role === 'super_admin' ? ['name' => ['required', 'string', 'max:255']] : User::nameRules()),
            ...($this->user()->role === 'super_admin' ? [] : [
                'academic_rank' => ['nullable', 'string', 'max:100'],
                'credentials'   => ['nullable', 'string', 'max:100'],
            ]),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
        ];
    }
}
