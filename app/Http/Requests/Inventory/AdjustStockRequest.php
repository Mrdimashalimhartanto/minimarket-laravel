<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'adjustment_type' => [
                'required',
                Rule::in(['increase', 'decrease']),
            ],

            // Stok final setelah koreksi, tidak boleh minus
            'adjusted_stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product wajib diisi.',
            'product_id.integer' => 'Product id harus berupa angka.',
            'product_id.exists' => 'Product yang dipilih tidak ditemukan.',

            'adjustment_type.required' => 'Tipe penyesuaian stok wajib diisi.',
            'adjustment_type.in' => 'Tipe penyesuaian stok hanya boleh increase atau decrease.',

            'adjusted_stock.required' => 'Stok akhir (adjusted_stock) wajib diisi.',
            'adjusted_stock.integer' => 'Stok akhir (adjusted_stock) harus berupa angka.',
            'adjusted_stock.min' => 'Stok akhir (adjusted_stock) minimal 0.',

            'reason.string' => 'Reason harus berupa teks.',
            'reason.max' => 'Reason maksimal 255 karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'produk',
            'adjustment_type' => 'tipe penyesuaian',
            'adjusted_stock' => 'stok akhir',
            'reason' => 'alasan',
        ];
    }
}
