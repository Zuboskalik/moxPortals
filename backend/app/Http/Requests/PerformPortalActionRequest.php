<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PerformPortalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:stabilize,close,dispatch_observer,mark_under_review'],
            'force_evacuate' => ['nullable', 'boolean'],
        ];
    }
}
