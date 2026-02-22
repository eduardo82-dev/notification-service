<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Redirect root to API docs
Route::get('/', fn () => redirect('/api/documentation'));
