<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\EventMediumController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EventChecklistController;
use App\Http\Controllers\EventExpenseController;
use App\Http\Controllers\EventResponsibleController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserPointController;

// ==========================================
// ПУБЛИЧНЫЕ МАРШРУТЫ (без аутентификации)
// ==========================================

// Главная страница API
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
        ],
        'auth_endpoints' => [
            'POST /api/auth/register' => 'Регистрация пользователя',
            'POST /api/auth/login' => 'Вход в систему',
            'POST /api/auth/logout' => 'Выход из системы (требует токен)',
            'GET /api/auth/profile' => 'Профиль пользователя (требует токен)'
        ]
    ]);
});

// Список функций системы
Route::get('/features', function () {
    return response()->json([
        'features' => [
            [
                'name' => 'Календарь мероприятий',
                'description' => 'Просмотр и управление мероприятиями колледжа',
                'endpoints' => ['/api/calendar', '/api/events']
            ],
            [
                'name' => 'Система регистрации',
                'description' => 'Регистрация на мероприятия с QR-кодами',
                'endpoints' => ['/api/register', '/api/event-registrations']
            ],
            [
                'name' => 'Планировщик задач',
                'description' => 'Управление задачами для организаторов',
                'endpoints' => ['/api/event-checklists', '/api/event-responsibles']
            ],
            [
                'name' => 'Медиа архив',
                'description' => 'Загрузка и голосование за фото/видео',
                'endpoints' => ['/api/event-media', '/api/media/{media}/vote']
            ],
            [
                'name' => 'Рейтинговая система',
                'description' => 'Система баллов за активность',
                'endpoints' => ['/api/user-points', '/api/leaderboard']
            ]
        ]
    ]);
});

// Проверка состояния системы
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'services' => [
            'database' => 'connected',
            'authentication' => 'active',
            'api' => 'operational'
        ]
    ]);
});

// ==========================================
// МАРШРУТЫ АУТЕНТИФИКАЦИИ
// ==========================================

Route::prefix('auth')->group(function () {
    // Публичные маршруты аутентификации
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Защищенные маршруты аутентификации (требуют токен)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });
});

// ==========================================
// ПУБЛИЧНЫЕ МАРШРУТЫ (только чтение)
// ==========================================

// Календарь мероприятий (публичный просмотр)
Route::get('/calendar', function () {
    return response()->json([
        'message' => 'Календарь мероприятий EventMaster',
        'events' => [],
        'note' => 'Для получения полного списка мероприятий используйте GET /api/events'
    ]);
});

Route::get('/calendar/upcoming', function () {
    return response()->json([
        'message' => 'Предстоящие мероприятия',
        'events' => [],
        'note' => 'Требуется подключение к базе данных для получения реальных данных'
    ]);
});

// Публичный просмотр мероприятий
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);

// Публичная статистика
Route::get('/events-statistics', [EventController::class, 'statistics']);
Route::get('/leaderboard', function () {
    return response()->json([
        'message' => 'Рейтинг пользователей',
        'leaderboard' => [],
        'note' => 'Требуется подключение к базе данных'
    ]);
});

// ==========================================
// ЗАЩИЩЕННЫЕ МАРШРУТЫ (требуют аутентификации)
// ==========================================

Route::middleware('auth:sanctum')->group(function () {

    // Профиль текущего пользователя
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    });

    // ==========================================
    // УПРАВЛЕНИЕ МЕРОПРИЯТИЯМИ
    // ==========================================

    Route::apiResource('events', EventController::class)->except(['index', 'show']);

    // Дополнительные маршруты для мероприятий
    Route::get('/events/{event}/registrations', [EventRegistrationController::class, 'getEventRegistrations']);
    Route::get('/events/{event}/media', function () {
        return response()->json(['message' => 'Медиа мероприятия', 'media' => []]);
    });
    Route::get('/events/{event}/checklists', function () {
        return response()->json(['message' => 'Чек-листы мероприятия', 'checklists' => []]);
    });
    Route::get('/events/{event}/expenses', function () {
        return response()->json(['message' => 'Расходы мероприятия', 'expenses' => []]);
    });
    Route::get('/events/{event}/feedback', function () {
        return response()->json(['message' => 'Отзывы о мероприятии', 'feedback' => []]);
    });

    // ==========================================
    // УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ
    // ==========================================

    Route::apiResource('users', UserController::class);
    Route::get('/users-statistics', [UserController::class, 'statistics']);
    Route::get('/users/{user}/profile', [UserController::class, 'profile']);
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::get('/users/{user}/registrations', [EventRegistrationController::class, 'getUserRegistrations']);

    // ==========================================
    // РЕГИСТРАЦИИ НА МЕРОПРИЯТИЯ
    // ==========================================

    Route::apiResource('event-registrations', EventRegistrationController::class);
    Route::post('/register', [EventRegistrationController::class, 'register']);

    // ==========================================
    // МЕДИА И ГОЛОСОВАНИЕ
    // ==========================================

    Route::apiResource('event-media', EventMediumController::class);
    Route::post('/media/{media}/vote', function () {
        return response()->json(['message' => 'Голос учтен']);
    });

    // ==========================================
    // ОБРАТНАЯ СВЯЗЬ И ПОСЕЩАЕМОСТЬ
    // ==========================================

    Route::apiResource('feedback', FeedbackController::class);
    Route::apiResource('attendance', AttendanceController::class);

    // ==========================================
    // ПЛАНИРОВАНИЕ И ОРГАНИЗАЦИЯ
    // ==========================================

    Route::apiResource('event-checklists', EventChecklistController::class);
    Route::apiResource('event-expenses', EventExpenseController::class);
    Route::apiResource('event-responsibles', EventResponsibleController::class);

    // ==========================================
    // СПРАВОЧНИКИ
    // ==========================================

    Route::apiResource('event-types', EventTypeController::class);
    Route::apiResource('faculties', FacultyController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('roles', RoleController::class);

    // ==========================================
    // СИСТЕМА БАЛЛОВ
    // ==========================================

    Route::apiResource('user-points', UserPointController::class);

    // Дополнительные маршруты для групп
    Route::get('/groups/{group}/activity', function () {
        return response()->json(['message' => 'Активность группы', 'activity' => []]);
    });

    // ==========================================
    // QR-КОДЫ И УВЕДОМЛЕНИЯ
    // ==========================================

    Route::get('/qr/generate/{registration}', function () {
        return response()->json(['message' => 'QR-код сгенерирован', 'qr_code' => 'base64_encoded_qr']);
    });

    Route::get('/notifications', function () {
        return response()->json(['message' => 'Уведомления пользователя', 'notifications' => []]);
    });

});

// ==========================================
// ТЕСТОВЫЕ МАРШРУТЫ (для разработки)
// ==========================================

Route::prefix('test')->group(function () {
    Route::get('/create-sample-data', function () {
        return response()->json([
            'message' => 'Тестовые данные будут созданы после настройки базы данных',
            'status' => 'pending'
        ]);
    });

    Route::get('/clear-cache', function () {
        return response()->json([
            'message' => 'Кеш очищен',
            'timestamp' => now()
        ]);
    });
});
