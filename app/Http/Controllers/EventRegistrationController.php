<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\RegistrationStatus;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EventRegistration::with(['event', 'user', 'status']);

            if ($request->has('event_id')) {
                $query->where('EventID', $request->event_id);
            }

            // Фильтрация по пользователю
            if ($request->has('user_id')) {
                $query->where('UserID', $request->user_id);
            }

            // Фильтрация по статусу
            if ($request->has('status_id')) {
                $query->where('StatusID', $request->status_id);
            }

            // Фильтрация по дате регистрации
            if ($request->has('date_from')) {
                $query->where('RegistrationDate', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('RegistrationDate', '<=', $request->date_to);
            }

            $registrations = $query->orderBy('RegistrationDate', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $registrations
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка регистраций',
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
            $events = Event::where('StartDateTime', '>', now())
                ->with(['type', 'faculty'])
                ->orderBy('StartDateTime')
                ->get();

            $users = User::where('IsActive', true)
                ->with(['role', 'group'])
                ->orderBy('LastName')
                ->get();

            $statuses = RegistrationStatus::all();

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'users' => $users,
                    'statuses' => $statuses
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке данных для регистрации',
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
                'EventID' => 'required|exists:App\Models\Event,EventID',
                'UserID' => 'required|exists:App\Models\User,UserID',
                'StatusID' => 'nullable|exists:App\Models\RegistrationStatus,StatusID'
            ]);

            $existingRegistration = EventRegistration::where('EventID', $validatedData['EventID'])
                ->where('UserID', $validatedData['UserID'])
                ->first();

            if ($existingRegistration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь уже зарегистрирован на данное мероприятие'
                ], 422);
            }

            $event = Event::find($validatedData['EventID']);
            if ($event->MaxParticipants) {
                $currentRegistrations = EventRegistration::where('EventID', $validatedData['EventID'])
                    ->whereHas('status', function ($query) {
                        $query->whereIn('StatusName', ['confirmed', 'pending']);
                    })
                    ->count();

                if ($currentRegistrations >= $event->MaxParticipants) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Достигнут лимит участников'
                    ], 422);
                }
            }

            if ($event->StartDateTime <= now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя зарегистрироваться на прошедшее мероприятие'
                ], 422);
            }

            if (!isset($validatedData['StatusID'])) {
                $defaultStatus = RegistrationStatus::where('StatusName', 'pending')->first();
                $validatedData['StatusID'] = $defaultStatus ? $defaultStatus->StatusID : 1;
            }

            $validatedData['RegistrationDate'] = now();

            $registration = EventRegistration::create($validatedData);
            $registration->load(['event', 'user', 'status']);

            return response()->json([
                'success' => true,
                'message' => 'Регистрация успешна создана',
                'data' => $registration
            ], 201);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных:',
                'errors' => $ex->errors()
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании регистрации:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(EventRegistration $eventRegistration): JsonResponse
    {
        try {
            $eventRegistration->load([
                'event.type',
                'event.organizer',
                'event.faculty',
                'user.role',
                'user.group',
                'status',
                'attendance',
                'feedback'
            ]);

            return response()->json([
                'success' => true,
                'data' => $eventRegistration
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении регистраций',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventRegistration $eventRegistration): JsonResponse
    {
        try {
            $eventRegistration->load('event', 'user', 'status');
            $statuses = RegistrationStatus::all();

            return response()->json([
                'success' => true,
                'data' => [
                    'registration' => $eventRegistration,
                    'statuses' => $statuses
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
    public function update(Request $request, EventRegistration $eventRegistration): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'StatusID' => 'required|exists:App\Models\RegistrationStatus,StatusID'
            ]);

            $eventRegistration->update($validatedData);
            $eventRegistration->load(['event', 'user', 'status']);

            return response()->json([
                'success' => true,
                'message' => 'Регистрация успешно обновлена!',
                'data' => $eventRegistration
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных:',
                'errors' => $ex->errors()
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении регистрации',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventRegistration $eventRegistration): JsonResponse
    {
        try {
            $event = $eventRegistration->event;

            if ($event->StartDateTime <= now()->addHour()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзся отменить регистрацию менее чем за час до начала мероприятия'
                ], 422);
            }

            $eventRegistration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Регистрация успешна отменена!'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене регистрации',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Register current user for event
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'event_id' => 'required|exists:Events,EventID'
            ]);

            // Получаем текущего пользователя (предполагаем, что есть аутентификация)
            // $userId = auth()->id(); // Раскомментировать при настройке аутентификации
            $userId = $request->input('user_id'); // Временно для тестирования

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не аутентифицирован'
                ], 401);
            }

            return $this->store(new Request([
                'EventID' => $validatedData['event_id'],
                'UserID' => $userId
            ]));

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Get registrations for specific event
     */
    public function getEventRegistrations(Event $event): JsonResponse
    {
        try {
            $registrations = $event->eventRegistrations()
                ->with(['user.role', 'user.group', 'status'])
                ->orderBy('RegistrationDate')
                ->get();

            $stats = [
                'total_registrations' => $registrations->count(),
                'confirmed' => $registrations->where('status.StatusName', 'confirmed')->count(),
                'pending' => $registrations->where('status.StatusName', 'pending')->count(),
                'cancelled' => $registrations->where('status.StatusName', 'cancelled')->count(),
                'available_spots' => $event->MaxParticipants ?
                    max(0, $event->MaxParticipants - $registrations->whereIn('status.StatusName', ['confirmed', 'pending'])->count()) :
                    null
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => $event->load(['type', 'organizer', 'faculty']),
                    'registrations' => $registrations,
                    'statistics' => $stats
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении регистраций события',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get registrations for specific user
     */
    public function getUserRegistrations(User $user): JsonResponse
    {
        try {
            $registrations = $user->eventRegistrations()
                ->with(['event.type', 'event.organizer', 'status'])
                ->orderBy('RegistrationDate', 'desc')
                ->get();

            $stats = [
                'total_registrations' => $registrations->count(),
                'upcoming_events' => $registrations->whereHas('event', function($query) {
                    $query->where('StartDateTime', '>', now());
                })->count(),
                'past_events' => $registrations->whereHas('event', function($query) {
                    $query->where('EndDateTime', '<', now());
                })->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->load(['role', 'group.faculty']),
                    'registrations' => $registrations,
                    'statistics' => $stats
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении регистраций пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
