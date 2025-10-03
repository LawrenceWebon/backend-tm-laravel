<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'priority' => 'sometimes|in:high,medium,low',
            'order' => 'sometimes|integer',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status'] = 'sometimes|in:pending,completed';
        }

        return $rules;
    }
}
