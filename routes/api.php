<?php

use App\Http\Controllers\IncidentController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::get('/bulletins', [BulletinController::class, 'index']);
Route::get('/incidents', [IncidentController::class, 'index']);
Route::post('/incidents', [IncidentController::class, 'store']);
Route::post('/admin/incidents', [IncidentController::class, 'storeInstitutional']);
Route::patch('/admin/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
Route::get('/stations', [StationController::class, 'index']);
Route::get('/stations/{stationCode}/history', [StationController::class, 'history']);
Route::post('/admin/stations', [StationController::class, 'register']);
Route::post('/iot/stations/{stationCode}/readings', [StationController::class, 'ingest']);

Route::get('/site-settings', [\App\Http\Controllers\SiteSettingsController::class, 'index']);
