<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['role', 'group.faculty']);

            if ($query->has('role_id')) {
                $query->where('RoleID', $request->role_id);
            }

            // Фильтрация по группе
            if ($request->has('group_id')) {
                $query->where('GroupID', $request->group_id);
            }

            // Поиск по имени или email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('FirstName', 'like', '%' . $search . '%')
                        ->orWhere('LastName', 'like', '%' . $search . '%')
                        ->orWhere('Email', 'like', '%' . $search . '%');
                });
            }

            // Фильтр по активности
            if ($request->has('is_active')) {
                $query->where('IsActive', $request->boolean('is_active'));
            }

            $users = $query->orderBy('LastName')->orderBy('FirstName')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка пользователей',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        try {
            $roles = Role::all();
            $groups = Group::with('faculty')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $roles,
                    'groups' => $groups
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке данных для создания пользователя!',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'FirstName' => 'required|string|max:255',
                'LastName' => 'required|string|max:255',
                'Email' => 'required|email|unique:App\Models\User,Email',
                'Phone' => 'nullable|string|max:20',
                'TelegramID' => 'nullable|string|max:255|unique:App\Models\User,TelegramID',
                'Password' => 'required|string|min:8|confirmed',
                'RoleID' => 'required|exists:App\Models\Role,RoleID',
                'GroupID' => 'nullable|exists:App\Models\Group,GroupID'
            ]);

            $validatedData['PasswordHash'] = \Hash::make($validatedData['Password']);
            unset($validatedData['Password']);
            unset($validatedData['Password_confirmation']);

            $user = User::create($validatedData);
            $user->load(['role', 'group.faculty']);

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно создан',
                'data' => $user
            ], 201);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных!',
                'errors' => $ex->errors()
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load([
                'role',
                'group.faculty',
                'events' => function ($query) {
                    $query->orderBy('StartDateTime', 'desc')->limit(10);
                },
                'eventRegistrations.event',
                'uploadedMedia.event',
                'votes.media'
            ]);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении пользователя',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): JsonResponse
    {
        try {
            $user->load(['role', 'group.faculty']);

            $roles = Role::all();
            $groups = Group::with('faculty')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'roles' => $roles,
                    'groups' => $groups
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке данных для редактирования',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'FirstName' => 'required|string|max:255',
                'LastName' => 'required|string|max:255',
                'Email' => 'required|email|unique:App\Models\User,Email',
                'Phone' => 'nullable|string|max:20',
                'TelegramID' => 'nullable|string|max:255|unique:App\Models\User,TelegramID',
                'Password' => 'required|string|min:8|confirmed',
                'RoleID' => 'required|exists:App\Models\Role,RoleID',
                'GroupID' => 'nullable|exists:App\Models\Group,GroupID',
                'IsActive' => 'boolean'
            ]);

            if ($request->has('Password')) {
                $request->validate([
                    'Password' => 'string|min:8|confirmed'
                ]);
                $validatedData['PasswordHash'] = Hash::make($request->Password);
            }

            $user->update($validatedData);
            $user->load(['role', 'group.faculty']);

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно обновлён',
                'data' => $user
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $ex->errors()
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении данных пользователя:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $eventsCount = $user->events()->count();
            $registrationsCount = $user->eventRegistrations()->count();

            if ($eventsCount > 0 || $registrationsCount > 0) {
                $user->update(['IsActive' => false]);

                return response()->json([
                    'success' => true,
                    'message' => 'Пользователь деактивирован (имеет связанные данные)'
                ]);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно удалён'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении пользователя',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function profile(User $user): JsonResponse
    {
        try {
            $user->load(['role', 'group.faculty']);

            $stats = [
                'organized_events' => $user->events()->count(),
                'participated_events' => $user->eventRegistrations()->count(),
                'uploaded_media' => $user->uploadedMedia()->count(),
                'total_points' => $user->userPoints()->sum('Points'),
                'recent_activities' => [
                    'events' => $user->events()->orderBy('StartDateTime', 'desc')->limit(5)->get(),
                    'registrations' => $user->eventRegistrations()->with('event')
                        ->orderBy('RegistrationDate', 'desc')->limit(5)->get()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'statistics' => $stats
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении профиля пользователя:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function updatePassword(Request $request, User $user): JsonResponse
    {
        try {
            $validatedDate = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed'
            ]);

            if (!Hash::check($validatedDate['current_password'], $user->PasswordHash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный текущий пароль'
                ], 422);
            }

            $user->update([
                'PasswordHash' => Hash::make($validatedDate['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Пароль успешно обновлён!'
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $ex->errors()
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении пароля:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('IsActive', true)->count(),
                'users_by_role' => User::with('role')
                    ->selectRaw('RoleID, COUNT(*) as count')
                    ->groupBy('RoleID')
                    ->get(),
                'users_by_faculty' => User::with('group.faculty')
                    ->join('Groups', 'Users.GroupID', '=', 'Group.GroupID')
                    ->selectRaw('Group.FacultyID, COUNT(*) as count')
                    ->groupBy('Groups.FacultyID')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики пользователей',
                'error' => $ex->getMessage()
            ], 500);
        }
    }
}
