<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThreadEditRequest extends FormRequest
{
    public function authorize()
    {
        // Authorization is handled by Policies in the Controller
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'pagetext' => 'required|string|max:16777215', // MEDIUMTEXT max size
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'عنوان الموضوع مطلوب.',
            'title.max' => 'عنوان الموضوع يجب ألا يتجاوز :max حرف.',
            'pagetext.required' => 'محتوى الموضوع مطلوب.',
            'pagetext.max' => 'حجم المحتوى يتجاوز الحد المسموح به.',
        ];
    }
}
