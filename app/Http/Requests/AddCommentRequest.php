<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,zip,txt,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'A reply message is required.',
            'attachments.*.max' => 'Each attachment must be less than 10MB.',
            'attachments.*.mimes' => 'Allowed file types are: PDF, PNG, JPG, JPEG, ZIP, TXT, DOC, DOCX.',
        ];
    }
}
