<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Создаем пользователя
            $user = User::create([
                'FirstName' => $request->first_name,
                'LastName' => $request->last_name,
                'Email' => $request->email,
                'PasswordHash' => Hash::make($request->password),
                'Salt' => bin2hex(random_bytes(16)), // Генерируем случайную соль
                'Phone' => $request->phone,
                'RoleID' => $request->role_id ?? 1, // По умолчанию роль студента
                'GroupID' => $request->group_id,
                'IsActive' => true,
            ]);

            // Создаем токен для API
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Пользователь успешно зарегистрирован',
                'user' => [
                    'id' => $user->UserID,
                    'first_name' => $user->FirstName,
                    'last_name' => $user->LastName,
                    'email' => $user->Email,
                    'phone' => $user->Phone,
                    'role_id' => $user->RoleID,
                    'group_id' => $user->GroupID,
                    'is_active' => $user->IsActive,
                    'created_at' => $user->CreatedAt,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при регистрации пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Ищем пользователя по email
            $user = User::where('Email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->PasswordHash)) {
                throw ValidationException::withMessages([
                    'email' => ['Неверные учётные данные.'],
                ]);
            }

            if (!$user->IsActive) {
                return response()->json([
                    'message' => 'Ваш аккаунт деактивирован'
                ], 403);
            }

            // Удаляем старые токены
            $user->tokens()->delete();

            // Создаем новый токен
            $token = $user->createToken('auth_token')->plainTextToken;


            return response()->json([
                'message' => 'Успешный вход в систему',
                'user' => [
                    'id' => $user->UserID,
                    'first_name' => $user->FirstName,
                    'last_name' => $user->LastName,
                    'email' => $user->Email,
                    'phone' => $user->Phone,
                    'role_id' => $user->RoleID,
                    'group_id' => $user->GroupID,
                    'is_active' => $user->IsActive,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка входа в систему',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Успешный выход из системы'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Ошибка при выходе из системы',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'Выход из всех устройств'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Ошибка при выходе со всех устройств',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $user->load(['role', 'group.faculty']);

            return response()->json([
                'user' => [
                    'id' => $user->UserID,
                    'first_name' => $user->FirstName,
                    'last_name' => $user->LastName,
                    'email' => $user->Email,
                    'phone' => $user->Phone,
                    'role' => $user->role ? [
                        'id' => $user->role->RoleID,
                        'name' => $user->role->Name,
                    ] : null,
                    'group' => $user->group ? [
                        'id' => $user->group->GroupID,
                        'name' => $user->group->Name,
                        'faculty' => $user->group->faculty ? [
                            'id' => $user->group->faculty->FacultyID,
                            'name' => $user->group->faculty->Name,
                        ] : null,
                    ] : null,
                    'is_active' => $user->IsActive,
                    'created_at' => $user->CreatedAt,
                    'last_login_at' => $user->LastLoginAt,
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Ошибка при получении профиля',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validatedData = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->UserID,
                'group_id' => 'sometimes|exists:groups,GroupID',
            ]);

            $user->update($validatedData);

            return response()->json([
                'message' => 'Профиль успешно обновлён',
                'user' => [
                    'id' => $user->UserID,
                    'first_name' => $user->FirstName,
                    'last_name' => $user->LastName,
                    'email' => $user->Email,
                    'phone' => $user->Phone,
                    'group_id' => $user->GroupID,
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Ошибка при обновлении профиля',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->PasswordHash)) {
                return response()->json([
                    'message' => 'Неверный текущий пароль'
                ], 422);
            }

            $user->update([
                'PasswordHash' => Hash::make($request->new_password)
            ]);

            $currentToken = $request->user()->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();

            return response()->json([
                'message' => 'Пароль успешно изменён'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Ошибка при смене пароля',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function index()
    {

    }
}
