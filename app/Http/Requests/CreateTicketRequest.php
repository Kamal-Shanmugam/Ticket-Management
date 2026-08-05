<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'department_id' => ['required', 'exists:departments,id'],
            'ticket_priority_id' => ['required', 'exists:ticket_priorities,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,zip,txt,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'The selected department is invalid.',
            'ticket_priority_id.exists' => 'The selected priority level is invalid.',
            'attachments.*.max' => 'Each attachment must be less than 10MB.',
            'attachments.*.mimes' => 'Allowed file types are: PDF, PNG, JPG, JPEG, ZIP, TXT, DOC, DOCX.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Get the authenticated customer
            $customer = Auth::guard('customer')->user() ?: Auth::user();

            if ($customer && $customer instanceof \App\Models\Customer) {
                // Check if a ticket with the same details was created within 5 minutes
                $duplicateExists = \App\Models\Ticket::where('customer_id', $customer->id)
                    ->where('title', $this->title)
                    ->where('description', $this->description)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add('title', 'Duplicate ticket detected! You raised a ticket with the exact same title and description in the past 5 minutes.');
                }
            }
        });
    }
}
