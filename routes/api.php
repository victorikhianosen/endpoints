<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YoutubeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/resolution', [YoutubeController::class, 'getResolutions']);

Route::post('/download', [YoutubeController::class, 'download']);

Route::post('/download-resolution', [YoutubeController::class, 'downloadWithResolution']);
