<?php

namespace App\Http\Requests\Pos;

use App\Enums\PaymentMethod;
use App\Support\EnumHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
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
            'note' => ['nullable', 'string', 'max:255'],

            'payment_method' => [
                'required',
                'string',
                Rule::in(EnumHelper::values(PaymentMethod::class)),
            ],

            'paid_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }
}
