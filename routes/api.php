<?php

use App\Http\Controllers\Api\V1\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:notes-api'])->prefix('v1')->group(function (): void {
    Route::get('/me', fn (Request $request) => response()->json(['data' => [
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'email' => $request->user()->email,
    ]]));
    Route::get('/notes/{note}/revisions', [NoteController::class, 'revisions']);
    Route::post('/notes/{note}/revisions/{revision}/restore', [NoteController::class, 'restore']);
    Route::apiResource('notes', NoteController::class);
});
