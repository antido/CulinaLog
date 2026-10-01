<?php

namespace App\Http\Requests;

use App\Enums\ForumStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreForumReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * ForumTopicPolicy::reply() covers members, guests, locked and hidden topics.
     */
    public function authorize(): bool
    {
        $topic = $this->route('topic');

        // A topic the person may not even see answers "not found".
        abort_unless(Gate::allows('view', $topic), 404);

        return Gate::allows('reply', $topic);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            // A nested reply must point at a visible reply in this same topic.
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('forum_replies', 'id')
                    ->where('topic_id', $this->route('topic')->id)
                    ->where('status', ForumStatus::Visible->value),
            ],
            // Spam trap, as for comments.
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => 'reply',
            'parent_id' => 'reply you are answering',
        ];
    }
}
