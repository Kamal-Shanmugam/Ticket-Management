<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'You must select an employee to assign.',
            'employee_id.exists' => 'The selected employee is invalid.',
        ];
    }
}
