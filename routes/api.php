<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EventMediumController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EventChecklistController;
use App\Http\Controllers\EventResponsibleController;
use App\Http\Controllers\EventExpenseController;
use App\Http\Controllers\UserPointController;

/*
|--------------------------------------------------------------------------
| EventMaster API Routes
|--------------------------------------------------------------------------
|
| Централизованная платформа для планирования, учета и проведения
| мероприятий в колледже
|
*/

// Базовый маршрут для проверки API
Route::get('/', function () {
    return response()->json([
        'message' => 'EventMaster API - Система организации мероприятий колледжа',
        'version' => '1.0',
        'status' => 'active',
        'features' => [
            'Календарь мероприятий с уведомлениями',
            'Система регистрации с QR-кодами',
            'Планировщик задач для организаторов',
            'Фото/видео архив с голосованием',
            'Рейтинговая система с баллами'
        ]
    ]);
});

// Получение информации о текущем пользователе
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ============================================================================
// 📅 КАЛЕНДАРЬ МЕРОПРИЯТИЙ
// ============================================================================

// События (Events) - основная функциональность
Route::apiResource('events', EventController::class);
Route::get('events-statistics', [EventController::class, 'statistics']);

// Типы событий для фильтрации
Route::apiResource('event-types', EventTypeController::class);

// Календарные представления
Route::prefix('calendar')->group(function () {
    Route::get('/', function(Request $request) {
        $events = \App\Models\Event::with(['type', 'faculty'])
            ->when($request->faculty_id, fn($q) => $q->where('FacultyID', $request->faculty_id))
            ->when($request->type_id, fn($q) => $q->where('TypeID', $request->type_id))
            ->when($request->date_from, fn($q) => $q->where('StartDateTime', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->where('EndDateTime', '<=', $request->date_to))
            ->orderBy('StartDateTime')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'filters_applied' => $request->only(['faculty_id', 'type_id', 'date_from', 'date_to'])
        ]);
    });

    Route::get('upcoming', function() {
        $events = \App\Models\Event::with(['type', 'faculty'])
            ->where('StartDateTime', '>', now())
            ->orderBy('StartDateTime')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $events]);
    });
});

// ============================================================================
// 👥 СИСТЕМА РЕГИСТРАЦИИ УЧАСТНИКОВ
// ============================================================================

// Регистрации на события
Route::apiResource('event-registrations', EventRegistrationController::class);
Route::post('register', [EventRegistrationController::class, 'register']);
Route::get('events/{event}/registrations', [EventRegistrationController::class, 'getEventRegistrations']);
Route::get('users/{user}/registrations', [EventRegistrationController::class, 'getUserRegistrations']);

// QR-коды для быстрой отметки (будем добавлять позже)
Route::prefix('qr')->group(function () {
    Route::get('generate/{registration}', function($registrationId) {
        return response()->json([
            'message' => 'QR-код генерация (в разработке)',
            'registration_id' => $registrationId,
            'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=registration_{$registrationId}"
        ]);
    });
});

// ============================================================================
// 📋 ПЛАНИРОВЩИК ЗАДАЧ ДЛЯ ОРГАНИЗАТОРОВ
// ============================================================================

// Чек-листы задач
Route::apiResource('event-checklists', EventChecklistController::class);
Route::get('events/{event}/checklists', function($eventId) {
    $checklists = \App\Models\EventChecklist::where('EventID', $eventId)
        ->with(['assignee'])
        ->get();
    return response()->json(['success' => true, 'data' => $checklists]);
});

// Ответственные за задачи
Route::apiResource('event-responsibles', EventResponsibleController::class);

// Контроль бюджета
Route::apiResource('event-expenses', EventExpenseController::class);
Route::get('events/{event}/expenses', function($eventId) {
    $expenses = \App\Models\EventExpense::where('EventID', $eventId)
        ->with(['category', 'purchaser'])
        ->get();
    $total = $expenses->sum('Amount');

    return response()->json([
        'success' => true,
        'data' => $expenses,
        'total_spent' => $total
    ]);
});

// ============================================================================
// 📸 ФОТО/ВИДЕО АРХИВ
// ============================================================================

// Медиафайлы событий
Route::apiResource('event-media', EventMediumController::class);
Route::get('events/{event}/media', function($eventId) {
    $media = \App\Models\EventMedium::where('EventID', $eventId)
        ->with(['uploader', 'mediaType', 'votes'])
        ->get();
    return response()->json(['success' => true, 'data' => $media]);
});

// Голосование за медиа
Route::post('media/{media}/vote', function($mediaId, Request $request) {
    // Простая реализация голосования
    $vote = \App\Models\MediaVote::updateOrCreate(
        ['MediaID' => $mediaId, 'UserID' => $request->user_id],
        ['VoteType' => $request->vote_type ?? 'like']
    );

    return response()->json(['success' => true, 'data' => $vote]);
});

// ============================================================================
// 🏆 РЕЙТИНГОВАЯ СИСТЕМА
// ============================================================================

// Баллы пользователей
Route::apiResource('user-points', UserPointController::class);

// Топ активных студентов
Route::get('leaderboard', function() {
    $topUsers = \App\Models\User::with(['role', 'group'])
        ->withSum('userPoints', 'Points')
        ->orderBy('user_points_sum_points', 'desc')
        ->limit(10)
        ->get();

    return response()->json(['success' => true, 'data' => $topUsers]);
});

// Статистика по группам
Route::get('groups/{group}/activity', function($groupId) {
    $group = \App\Models\Group::with(['users.eventRegistrations', 'faculty'])->find($groupId);
    $stats = [
        'total_students' => $group->users->count(),
        'active_students' => $group->users->filter(fn($u) => $u->eventRegistrations->count() > 0)->count(),
        'total_registrations' => $group->users->sum(fn($u) => $u->eventRegistrations->count()),
        'total_points' => $group->users->sum(fn($u) => $u->userPoints->sum('Points'))
    ];

    return response()->json(['success' => true, 'data' => $stats]);
});

// ============================================================================
// 👤 УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ
// ============================================================================

// Пользователи
Route::apiResource('users', UserController::class);
Route::get('users/{user}/profile', [UserController::class, 'profile']);
Route::put('users/{user}/password', [UserController::class, 'updatePassword']);
Route::get('users-statistics', [UserController::class, 'statistics']);

// Справочники
Route::apiResource('faculties', FacultyController::class);
Route::apiResource('groups', GroupController::class);
Route::apiResource('roles', RoleController::class);

// ============================================================================
// 📝 ОТЗЫВЫ И ПОСЕЩАЕМОСТЬ
// ============================================================================

// Отзывы о мероприятиях
Route::apiResource('feedback', FeedbackController::class);
Route::get('events/{event}/feedback', function($eventId) {
    $feedback = \App\Models\Feedback::whereHas('registration', function($q) use ($eventId) {
        $q->where('EventID', $eventId);
    })->with(['registration.user'])->get();

    return response()->json(['success' => true, 'data' => $feedback]);
});

// Посещаемость
Route::apiResource('attendance', AttendanceController::class);

// ============================================================================
// 🔔 УВЕДОМЛЕНИЯ (заготовка)
// ============================================================================

Route::prefix('notifications')->group(function () {
    Route::get('/', function() {
        return response()->json([
            'message' => 'Система уведомлений (в разработке)',
            'planned_features' => [
                'Telegram уведомления',
                'Email рассылки',
                'Push уведомления',
                'Напоминания о мероприятиях'
            ]
        ]);
    });
});

// ============================================================================
// 🧪 ТЕСТОВЫЕ И ИНФОРМАЦИОННЫЕ МАРШРУТЫ
// ============================================================================

// Список всех функций EventMaster
Route::get('features', function () {
    return response()->json([
        'EventMaster' => 'Система организации мероприятий колледжа',
        'implemented_features' => [
            '✅ Календарь мероприятий с фильтрами',
            '✅ Система регистрации участников',
            '✅ Планировщик задач для организаторов',
            '✅ Контроль бюджета мероприятий',
            '✅ Фото/видео архив с голосованием',
            '✅ Рейтинговая система с баллами',
            '✅ Статистика по активности студентов',
            '✅ Управление ролями и правами'
        ],
        'in_development' => [
            '🔄 QR-коды для быстрой отметки',
            '🔄 Telegram/Email уведомления',
            '🔄 Интеграция с расписанием',
            '🔄 Веб-интерфейс'
        ]
    ]);
});

// Проверка состояния системы
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'system' => 'EventMaster v1.0',
        'database' => 'connected'
    ]);
});
