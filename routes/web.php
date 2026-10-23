<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\WebServerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccidentProneAreaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordRecoveryController;
use App\Http\Controllers\RegistrationRequestController;

// (user-facing landing and auth routes removed)
Route::get('/admin', fn () => redirect()->route('dashboard'))
    ->middleware(['auth', 'role:admin']);
Route::get('/login', [AuthController::class, 'loginForm'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::get('/register', [AuthController::class, 'registerForm'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/register/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('guest');
Route::get('/request-access', [RegistrationRequestController::class, 'create'])->middleware('guest')->name('registration-requests.create');
Route::post('/request-access', [RegistrationRequestController::class, 'store'])->middleware(['guest', 'throttle:3,1'])->name('registration-requests.store');
Route::get('/request-access/{token}/verify', [RegistrationRequestController::class, 'verificationForm'])->middleware('guest')->name('registration-requests.verify');
Route::post('/request-access/{token}/verify', [RegistrationRequestController::class, 'verify'])->middleware(['guest', 'throttle:5,1'])->name('registration-requests.verify.submit');
Route::get('/forgot-password', [AuthController::class, 'forgotForm'])->middleware('guest');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware(['guest', 'throttle:10,1']);
Route::post('/user-password/reset', [UserPasswordRecoveryController::class, 'reset'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('users.password.reset');
Route::post('/user-password/request-code', [UserPasswordRecoveryController::class, 'requestCode'])
    ->middleware(['guest', 'throttle:3,1'])
    ->name('users.password.request-code');
Route::get('/user-password/reset', [UserPasswordRecoveryController::class, 'resetForm'])
    ->middleware('guest')
    ->name('users.password.reset-form');
Route::post('/user-password/reset-with-code', [UserPasswordRecoveryController::class, 'resetWithCode'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('users.password.reset-with-code');
Route::get('/reset-password', [AuthController::class, 'resetForm'])->middleware('guest');
Route::post('/reset-password', [AuthController::class, 'verifyResetCode'])->middleware(['guest', 'throttle:10,1']);
Route::get('/reset-password/new', [AuthController::class, 'resetPasswordForm'])->middleware('guest');
Route::post('/reset-password/new', [AuthController::class, 'reset'])->middleware(['guest', 'throttle:5,1']);
Route::get('/auth/google', [GoogleController::class, 'redirect'])->middleware('guest');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->middleware('guest');
Route::get('/logout', fn () => redirect('/'))->middleware('auth');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// (user-facing landing and auth routes removed)

// Dashboard root
Route::get('/', [DashboardController::class, 'dashboardOverview'])
    ->middleware(['auth', 'role:admin'])
    ->name('dashboard');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/registration-requests', [RegistrationRequestController::class, 'index'])->name('registration-requests.index');
    Route::post('/registration-requests/{registrationRequest:id}/send-verification-code', [RegistrationRequestController::class, 'sendVerificationCode'])
        ->middleware('throttle:5,1')
        ->name('registration-requests.send-verification-code');
    Route::post('/registration-requests/{registrationRequest:id}/approve', [RegistrationRequestController::class, 'approve'])->name('registration-requests.approve');
    Route::post('/registration-requests/{registrationRequest:id}/reject', [RegistrationRequestController::class, 'reject'])->name('registration-requests.reject');
    Route::delete('/registration-requests/{registrationRequest:id}', [RegistrationRequestController::class, 'destroy'])->name('registration-requests.destroy');
    Route::get('/password-reset-requests', [UserPasswordRecoveryController::class, 'index'])->name('users.password-requests.index');
    Route::post('/password-reset-requests/send-code', [UserPasswordRecoveryController::class, 'sendCode'])
        ->middleware('throttle:5,1')
        ->name('users.password-requests.send-code');
    Route::get('/nearby-alert-targets', [DashboardController::class, 'nearbyAlertTargets'])->name('dashboard.nearby-alert-targets');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/brake-events', [IncidentController::class, 'index'])->name('brake-events.index');
    Route::get('/brake-events/export', [IncidentController::class, 'export'])->name('brake-events.export');
    Route::delete('/brake-events/dataset', [IncidentController::class, 'clearDataset'])->name('brake-events.clear');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/web-server', [WebServerController::class, 'index'])->name('web-server.index');
    Route::get('/web-server/download', [WebServerController::class, 'downloadBrakeEvents'])->name('web-server.download');
    Route::get('/apa', [AnalyticsController::class, 'apa'])->name('analytics.apa');
    Route::post('/apa/locations', [AccidentProneAreaController::class, 'store'])->name('analytics.apa.locations.store');
    Route::put('/apa/locations/{accidentProneArea}', [AccidentProneAreaController::class, 'update'])->name('analytics.apa.locations.update');
    Route::delete('/apa/locations/{accidentProneArea}', [AccidentProneAreaController::class, 'destroy'])->name('analytics.apa.locations.destroy');
    Route::post('/brake-events/import', [DashboardController::class, 'importBrakeEvents'])->name('brake-events.import');
});
