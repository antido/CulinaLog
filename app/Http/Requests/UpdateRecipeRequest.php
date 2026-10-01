<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateRecipeRequest extends StoreRecipeRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('recipe'));
    }

    /**
     * The same rules as creating a recipe, with two differences.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $recipe = $this->route('recipe');

        return [
            ...parent::rules(),
            // A recipe may stay in its current category even if that
            // category has been disabled since.
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($recipe): void {
                    $query->where('is_active', true)->orWhere('id', $recipe->category_id);
                }),
            ],
            'remove_image' => ['boolean'],
        ];
    }
}
