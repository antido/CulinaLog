<?php

namespace App\Http\Requests\Moderation;

use App\Enums\ForumStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateForumTopicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('moderate', $this->route('topic'));
    }

    /**
     * A moderator changes one or more of: pinned, locked, visible/hidden.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_pinned' => ['sometimes', 'boolean'],
            'is_locked' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in([ForumStatus::Visible->value, ForumStatus::Hidden->value])],
        ];
    }
}
