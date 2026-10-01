<?php

namespace App\Http\Requests;

use App\Enums\CommentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Members need the comments.create permission. Guests may comment
     * only while guest comments are switched on in config/community.php.
     */
    public function authorize(): bool
    {
        // Nobody can comment on a recipe they are not allowed to see.
        abort_unless($this->route('recipe')->isVisible(), 404);

        $user = $this->user();

        return $user !== null
            ? $user->can('comments.create')
            : (bool) config('community.guest_comments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'guest_name' => ['nullable', 'string', 'max:40'],
            // A reply must point at a visible comment on this same recipe.
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')
                    ->where('recipe_id', $this->route('recipe')->id)
                    ->where('status', CommentStatus::Visible->value),
            ],
            // Spam trap: a field people never see. See CommentController::store().
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
            'body' => 'comment',
            'guest_name' => 'name',
            'parent_id' => 'comment you are replying to',
        ];
    }
}
