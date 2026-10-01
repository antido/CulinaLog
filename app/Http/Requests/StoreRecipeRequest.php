<?php

namespace App\Http\Requests;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Models\Recipe;
use App\Services\ImageService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Recipe::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * There is no user_id rule on purpose: the owner is always the
     * signed-in user and is never read from the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'image' => ImageService::rules(),
            'prep_time' => ['required', 'integer', 'min:0', 'max:1440'],
            'cook_time' => ['required', 'integer', 'min:0', 'max:1440'],
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'difficulty' => ['required', Rule::enum(RecipeDifficulty::class)],
            'status' => ['required', Rule::enum(RecipeStatus::class)],

            // The order of each list is the order of the rows in the request.
            'ingredients' => ['required', 'array', 'min:1', 'max:50'],
            'ingredients.*.name' => ['required', 'string', 'max:150'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:30'],

            'instructions' => ['required', 'array', 'min:1', 'max:50'],
            'instructions.*.instruction' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ingredients.required' => 'Add at least one ingredient.',
            'ingredients.*.name.required' => 'Enter the ingredient name, or remove this row.',
            'ingredients.*.quantity.numeric' => 'Use a number such as 2 or 0.5.',
            'instructions.required' => 'Add at least one step.',
            'instructions.*.instruction.required' => 'Describe this step, or remove it.',
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
            'category_id' => 'category',
            'prep_time' => 'preparation time',
            'cook_time' => 'cooking time',
        ];
    }
}
