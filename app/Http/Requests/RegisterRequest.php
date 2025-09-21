<?php

namespace App\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u'
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:Users,Email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'unique:Users,Phone',
                'regex:/^(\+7|8)?[\s\-]?\(?[489][0-9]{2}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/'
            ],
            'role_id' => [
                'sometimes',
                'integer',
                'exists:Roles,RoleID'
            ],
            'group_id' => [
                'required_if:role_id,1', // обязательно для студентов
                'nullable',
                'integer',
                'exists:Groups,GroupID'
            ],
            'terms_accepted' => [
                'required',
                'accepted'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Имя обязательно для заполнения.',
            'first_name.string' => 'Имя должно быть строкой',
            'first_name.max' => 'Имя не должно превышать 255 символов.',
            'first_name.regex' => 'Имя может содержать только буквы, пробелы и дефисы',

            'last_name.required' => 'Фамилия обязательна для заполнения.',
            'last_name.string' => 'Фамилия должна быть строкой',
            'last_name.max' => 'Фамилия не должна превышать 255 символов.',
            'last_name.regex' => 'Фамилия может содержать только буквы, пробелы и дефисы',

            'email.required' => 'Email обязателен для заполнения.',
            'email.string' => 'Email должен быть строкой',
            'email.email' => 'Email должен быть корректным адресом электронной почты',
            'email.max' =>  'Email не должен превышать 255 символов',
            'email.unique' => 'Пользователь с таким email уже существует',

            'password.required' => 'Пароль обязателен для заполнения',
            'password.string' => 'Пароль должен быть строкой',
            'password.min' => 'Пароль должен содержать минимум 8 символов',
            'password.confirmed' => 'Подтверждение пароля не совпадает',
            'password.regex' => 'Пароль должен содержать минимум одну заглавную букву, одну строчную букву, одну цифру и один специальный символ.',

            'phone.required' => 'Номер телефона обязателен для заполнения',
            'phone.string' => 'Номер телефона должен быть строкой',
            'phone.max' => 'Номер телефона не должен превышать 20 символов',
            'phone.unique' => 'Пользователь с таким телефоном уже зарегистрирован',
            'phone.regex' => 'Номер телефона должен быть в корректном российском формате.',

            'role_id.integer' => 'ID роли должен быть числом',
            'role_id.exists' => 'Выбранная роль не существует',

            'group_id.required_if' => 'Группа обязательна для студентов',
            'group_id.integer' => 'ID группы должно быть числом',
            'group_id.exists' => 'Выбранная группа не существует',

            'terms_accepted.required' => 'Необходимо принять условия использования.',
            'terms_accepted.accepted' => 'Необходимо принять условия использования.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'имя',
            'last_name' => 'фамилия',
            'email' => 'email',
            'password' => 'пароль',
            'phone' => 'номер телефона',
            'role_id' => 'роль',
            'group_id' => 'группа',
            'term_accepted' => 'условия использования',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Ошибка валидации данных регистрации',
                'errors' => $validator->errors(),
                'status' => 'validation_failed'
            ], 422)
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'email' => strtolower(trim($this->email)),
            'phone' => preg_replace('/[\s\-\(\)]/', '', $this->phone), // Убираем пробелы и символы
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Дополнительная проверка: если роль студента, группа обязательна
            if ($this->role_id == 1 && !$this->group_id) {
                $validator->errors()->add('group_id', 'Для студентов группа обязательна.');
            }

            // Проверка на дублирование имени и фамилии в одной группе (опционально)
            if ($this->group_id) {
                $existingUser = \App\Models\User::where('FirstName', $this->first_name)
                    ->where('LastName', $this->last_name)
                    ->where('GroupID', $this->group_id)
                    ->first();

                if ($existingUser) {
                    $validator->errors()->add('first_name', 'Пользователь с таким именем и фамилией уже существует в данной группе.');
                }
            }
        });
    }

    public function authorize(): bool
    {
        return true;
    }
}
