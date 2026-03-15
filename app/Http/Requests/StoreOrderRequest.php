<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager', 'cashier']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pos_session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'order_type' => ['required', 'string', 'in:retail,dine_in,takeaway,delivery'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:cash,card,wallet,bank_transfer,mixed'],
            'amount_tendered' => ['required', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_type' => $this->input('order_type', 'retail'),
            'payment_method' => $this->input('payment_method', 'cash'),
            'amount_tendered' => $this->input('amount_tendered', 0),
        ]);
    }
}
