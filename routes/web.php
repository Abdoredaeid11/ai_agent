<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationalShipmentController;

Route::get('/', [ConversationalShipmentController::class, 'showChat']);
Route::post('/chat', [ConversationalShipmentController::class, 'processMessage'])->name('chat');
Route::get('/conversation/{id}', [ConversationalShipmentController::class, 'getConversation']);


