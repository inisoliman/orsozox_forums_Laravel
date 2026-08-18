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
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pagetext.required' => 'محتوى الرد مطلوب.',
        ];
    }

    protected function minChars(): int
    {
        return (int) config('security.firewall.quick_reply_min_chars', 10);
    }

    protected function maxChars(): int
    {
        return (int) config('security.firewall.quick_reply_max_chars', 10000);
    }

    protected function plainTextLength(): int
    {
        $text = strip_tags($this->input('pagetext', ''));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return mb_strlen(trim($text), 'UTF-8');
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $min = $this->minChars();
            $max = $this->maxChars();
            $length = $this->plainTextLength();

            if ($length === 0) {
                $validator->errors()->add('pagetext', 'محتوى الرد مطلوب.');
                return;
            }

            if ($length < $min) {
                $validator->errors()->add('pagetext', 'الرد قصير جداً — الحد الأدنى ' . $min . ' أحرف.');
            }

            if ($length > $max) {
                $validator->errors()->add('pagetext', 'حجم الرد يتجاوز الحد المسموح به (' . $max . ' أحرف).');
            }
        });
    }
}
