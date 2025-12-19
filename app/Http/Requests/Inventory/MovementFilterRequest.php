<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryMovementType;
use App\Support\EnumHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovementFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'exists:products,id'],

            'type' => [
                'nullable',
                'string',
                Rule::in(EnumHelper::values(InventoryMovementType::class)),
            ],

            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
