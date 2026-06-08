<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API-only app — `login` is named so route('login') resolves (Laravel's
// default unauthenticated handler calls it for non-JSON requests). Returns
// a 401 JSON instead of redirecting to a page that doesn't exist.
Route::get('/login', fn () => ApiResponse::error('Unauthenticated.', null, 401))->name('login');
