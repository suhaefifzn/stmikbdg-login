<?php

use Illuminate\Support\Facades\Route;

// * Controller
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// * Route - authentications
Route::controller(AuthController::class)
    ->group(function () {
        Route::get('/login', 'index');
        Route::post('/authenticate', 'authenticate');
        Route::get('/logout', 'logout');
    });
