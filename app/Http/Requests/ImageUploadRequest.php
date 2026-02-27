<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'upload' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120', // 5 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'upload.required' => 'يرجى اختيار صورة للرفع.',
            'upload.mimes' => 'الصيغ المسموحة: JPG, JPEG, PNG, WebP.',
            'upload.max' => 'الحد الأقصى لحجم الصورة هو 5 ميجابايت.',
        ];
    }
}
