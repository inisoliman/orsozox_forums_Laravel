<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'pagetext' => [
                'required',
                'string',
                'max:' . (int) config('security.firewall.quick_reply_max_chars', 10000),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pagetext.required' => 'محتوى الرد مطلوب.',
            'pagetext.max' => 'حجم الرد يتجاوز الحد المسموح به.',
        ];
    }
}
