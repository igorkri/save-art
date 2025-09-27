<?php

use App\Http\Controllers\SaveArt\HomeController;
use Illuminate\Support\Facades\Route;


Route::get('/', [HomeController::class, 'index']);
