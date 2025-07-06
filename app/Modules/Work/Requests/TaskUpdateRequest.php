<?php

namespace App\Modules\Work\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:' . implode(',', TaskStatus::values()),
            'priority' => 'sometimes|string|in:' . implode(',', TaskPriority::values()),
            'assigned_to' => 'nullable|integer|exists:users,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'actual_hours' => 'nullable|numeric|min:0|max:999.99',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'title.max' => 'Task title cannot exceed 255 characters',
            'assigned_to.exists' => 'Selected user does not exist',
            'project_id.exists' => 'Selected project does not exist',
            'estimated_hours.numeric' => 'Estimated hours must be a number',
            'estimated_hours.min' => 'Estimated hours cannot be negative',
            'estimated_hours.max' => 'Estimated hours cannot exceed 999.99',
            'actual_hours.numeric' => 'Actual hours must be a number',
            'actual_hours.min' => 'Actual hours cannot be negative',
            'actual_hours.max' => 'Actual hours cannot exceed 999.99',
            'tags.array' => 'Tags must be an array',
            'tags.*.string' => 'Each tag must be a string',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'attachments.*.file' => 'Each attachment must be a file',
            'attachments.*.max' => 'Each attachment cannot exceed 10MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert status and priority to enum values if provided
        if ($this->has('status')) {
            $this->merge([
                'status' => TaskStatus::from($this->status),
            ]);
        }

        if ($this->has('priority')) {
            $this->merge([
                'priority' => TaskPriority::from($this->priority),
            ]);
        }
    }
}
