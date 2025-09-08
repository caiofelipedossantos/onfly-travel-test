<?php

use App\Http\Controllers\TravelRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('travel-requests', TravelRequestController::class);
