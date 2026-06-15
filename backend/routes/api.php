<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AiModelController;
use App\Http\Controllers\Api\V1\AnswerController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuestionController;

Route::prefix('v1')->group(function () {
    // ---- Auth ----
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');

    // ---- Public content ----
    Route::get('/home', HomeController::class);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/{question}', [QuestionController::class, 'show']);

    Route::get('/ai-models', [AiModelController::class, 'index']);
    Route::get('/ai-models/leaderboard', [AiModelController::class, 'leaderboard']);

    Route::get('/pages/{page}', [PageController::class, 'show']);
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact');

    // ---- Protected ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile/activity', [ProfileController::class, 'activity']);
        Route::post('/questions', [QuestionController::class, 'store'])->middleware('throttle:questions');
        Route::post('/answers/{answer}/vote', [AnswerController::class, 'vote'])->middleware('throttle:votes');
        Route::post('/email/resend', [AuthController::class, 'resendVerification']);
    });
});
