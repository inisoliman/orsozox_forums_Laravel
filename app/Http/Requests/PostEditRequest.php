<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostEditRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'pagetext' => 'required|string|max:16777215', // MEDIUMTEXT
        ];
    }

    public function messages()
    {
        return [
            'pagetext.required' => 'محتوى الرد مطلوب.',
            'pagetext.max' => 'حجم المحتوى يتجاوز الحد المسموح به.',
        ];
    }
}
