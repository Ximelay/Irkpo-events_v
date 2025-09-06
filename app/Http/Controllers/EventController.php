<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Faculty;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Event::with(['type', 'organizer', 'faculty']);

            if ($request->has('type_id')) {
                $query->where('TypeID', $request->type_id);
            }

            if ($request->has('faculty_id')) {
                $query->where('FacultyID', $request->faculty_id);
            }

            // Фильтрация по дате
            if ($request->has('date_from')) {
                $query->where('StartDateTime', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('EndDateTime', '<=', $request->date_to);
            }

            if ($request->has('search')) {
                $query->where('Title', 'like', '%' . $request->search . '%');
            }

            $events = $query->orderBy('StartDateTime', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $events
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении мероприятий',
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
            $eventTypes = EventType::all();
            $faculties = Faculty::all();
            $organizers = User::whereHas('role', function ($query) {
                $query->whereIn('RoleName', ['admin', 'organizer']);
            })->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'event_types' => $eventTypes,
                    'faculties' => $faculties,
                    'organizers' => $organizers
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке данных для создания мероприятия',
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
                'Title' => 'required|string|max:255',
                'Description' => 'nullable|string',
                'TypeID' => 'required|exists:App\Models\EventType,TypeID',
                'StartDateTime' => 'required|date|after:now',
                'EndDateTime' => 'required|date|after:StartDateTime',
                'Location' => 'required|string|max:255',
                'OrganizerID' => 'required|exists:App\Models\User,UserID',
                'MaxParticipants' => 'nullable|integer|min:1',
                'FacultyID' => 'nullable|exists:App\Models\Faculty,FacultyID',
                'Budget' => 'nullable|numeric|min:0',
                'ImageURL' => 'nullable|url'
            ]);

            $event = Event::create($validatedData);

            $event->load(['type', 'organizer', 'faculty']);

            return response()->json([
                'success' => true,
                'message' => 'Мероприятие успешно создано!',
                'data' => $event
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
                'message' => 'Ошибка при создании мероприятия:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): JsonResponse
    {
        try {
            $event->load([
                'type',
                'organizer',
                'faculty',
                'media',
                'eventRegistrations.user',
                'eventExpenses.category',
                'notifications',
            ]);

            return response()->json([
                'success' => true,
                'data' => $event
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении мероприятия:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): JsonResponse
    {
        try {
            $event->load(['type', 'organizer', 'faculty']);

            $eventTypes = EventType::all();
            $faculties = Faculty::all();
            $organizers = User::whereHas('role', function ($query) {
                $query->whereIn('RoleName', ['admin', 'organizer']);
            })->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => $event,
                    'event_types' => $eventTypes,
                    'faculties' => $faculties,
                    'organizers' => $organizers
                ]
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке данных для редактирования:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'Title' => 'required|string|max:255',
                'Description' => 'nullable|string',
                'TypeID' => 'required|exists:App\Models\EventType,TypeID',
                'StartDateTime' => 'required|date|after:now',
                'EndDateTime' => 'required|date|after:StartDateTime',
                'Location' => 'required|string|max:255',
                'OrganizerID' => 'required|exists:App\Models\User,UserID',
                'MaxParticipants' => 'nullable|integer|min:1',
                'FacultyID' => 'nullable|exists:App\Models\Faculty,FacultyID',
                'Budget' => 'nullable|numeric|min:0',
                'ImageURL' => 'nullable|url'
            ]);

            $event->update($validatedData);
            $event->load(['type', 'organizer', 'faculty']);

            return response()->json([
                'success' => true,
                'message' => 'Мероприятие успешно обновлено',
                'data' => $event
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации:',
                'errors' => $ex->errors()
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении мероприятия',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        try {
            $registrationCount = $event->eventRegistrations()->count();

            if ($registrationCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить мероприятие с зарегистрированными участниками'
                ], 422);
            }

            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Мероприятие успешно удалено'
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении мероприятия:',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_events' => Event::count(),
                'upcoming_events' => Event::where('StartDateTime', '>', now())->count(),
                'past_events' => Event::where('EndDateTime', '<', now())->count(),
                'events_by_type' => Event::with('type')
                    ->selectRaw('TypeID, COUNT(*) as count')
                    ->groupBy('TypeID')
                    ->get(),
                'events_by_faculty' => Event::with('faculty')
                    ->selectRaw('FacultyID, COUNT(*) as count')
                    ->groupBy('FacultyID')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики',
                'error' => $ex->getMessage()
            ], 500);
        }
    }
}
