<?php

namespace App\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255'
            ],
            'password' => [
                'required',
                'string',
                'min:1'
            ],
            'remember_me' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен для заполнения.',
            'email.string' => 'Email должен быть строкой.',
            'email.email' => 'Email должен быть корректным адресом электронной почты.',
            'email.max' => 'Email не должен превышать 255 символов.',

            'password.required' => 'Пароль обязателен для заполнения.',
            'password.string' => 'Пароль должен быть строкой.',
            'password.min' => 'Пароль не может быть пустым.',

            'remember_me.boolean' => 'Поле "Запомнить меня" должно быть логическим значением.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'email',
            'password' => 'пароль',
            'remember_me' => 'запомнить меня'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Ошибка валидации данных ввода',
                'errors' => $validator->errors(),
                'status' => 'validation_failed'
            ], 422)
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }
}
