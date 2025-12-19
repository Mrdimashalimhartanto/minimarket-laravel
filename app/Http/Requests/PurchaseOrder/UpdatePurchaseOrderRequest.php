<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Supplier wajib ada dan harus valid di tabel suppliers
            'supplier_id' => ['required', 'exists:suppliers,id'],

            // Minimal 1 item
            'items' => ['required', 'array', 'min:1'],

            // Setiap item harus punya product_id valid
            'items.*.product_id' => ['required', 'exists:products,id'],

            // Qty > 0
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],

            // Harga satuan >= 0
            'items.*.unit_cost' => ['required', 'integer', 'min:0'],

            // Catatan PO (optional)
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Optional: rename attribute untuk error message yang lebih manusiawi.
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier',
            'items' => 'daftar item',
            'items.*.product_id' => 'produk',
            'items.*.quantity_ordered' => 'jumlah pesanan',
            'items.*.unit_cost' => 'harga satuan',
        ];
    }

    /**
     * Optional: custom message (boleh dikosongin kalau nggak perlu).
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada satu item di purchase order.',
            'items.min'      => 'Minimal harus ada satu item di purchase order.',
        ];
    }
}
